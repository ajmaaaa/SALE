<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\StudentRubricScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RubricController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman kelola rubric — create/edit criteria untuk satu assessment.
     */
    public function edit(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $rubric = $assessment->rubric;
        $criteria = $rubric ? $rubric->criteria()->orderBy('id')->get() : collect();

        return view('dosen.penilaian.rubrik', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'rubric' => $rubric,
            'criteria' => $criteria,
        ]);
    }

    /**
     * Simpan/update rubric + criteria.
     *
     * Validates that total criterion weight = 100%.
     * Creates rubric if it doesn't exist, upserts criteria, and removes
     * deleted criteria (only if they have no student scores yet).
     */
    public function save(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $request->validate([
            'rubric_name' => ['required', 'string', 'max:120'],
            'criteria' => ['required', 'array', 'min:1', 'max:20'],
            'criteria.*.name' => ['required', 'string', 'max:120'],
            'criteria.*.description' => ['nullable', 'string', 'max:1000'],
            'criteria.*.weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'criteria.*.max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'criteria.*.id' => ['nullable', 'integer'],
        ]);

        // Validate total weight = 100%
        $totalWeight = collect($request->input('criteria'))->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            $label = rtrim(rtrim(number_format($totalWeight, 2), '0'), '.');

            return back()->withErrors(['criteria' => "Total bobot kriteria harus tepat 100% (saat ini {$label}%)."])->withInput();
        }

        DB::transaction(function () use ($request, $assessment) {
            // Create or update rubric
            $rubric = Rubric::updateOrCreate(
                ['assessment_id' => $assessment->id],
                ['name' => $request->input('rubric_name')],
            );

            $keepIds = [];

            foreach ($request->input('criteria') as $criterionData) {
                $existingId = $criterionData['id'] ?? null;

                if ($existingId) {
                    // Update existing criterion
                    $criterion = RubricCriterion::where('id', $existingId)
                        ->where('rubric_id', $rubric->id)
                        ->first();

                    if ($criterion) {
                        $criterion->update([
                            'name' => $criterionData['name'],
                            'description' => $criterionData['description'] ?? null,
                            'weight' => $criterionData['weight'],
                            'max_score' => $criterionData['max_score'],
                        ]);
                        $keepIds[] = $criterion->id;
                    }
                } else {
                    // Create new criterion
                    $criterion = RubricCriterion::create([
                        'rubric_id' => $rubric->id,
                        'name' => $criterionData['name'],
                        'description' => $criterionData['description'] ?? null,
                        'weight' => $criterionData['weight'],
                        'max_score' => $criterionData['max_score'],
                    ]);
                    $keepIds[] = $criterion->id;
                }
            }

            // Delete criteria that were removed (only if no scores exist)
            $toDelete = RubricCriterion::where('rubric_id', $rubric->id)
                ->whereNotIn('id', $keepIds)
                ->get();

            foreach ($toDelete as $criterion) {
                if ($criterion->studentScores()->whereNotNull('score')->exists()) {
                    // Skip deletion — has scores. The user should be warned.
                    continue;
                }
                $criterion->delete();
            }

            // Mark assessment as using rubric
            $assessment->update(['uses_rubric' => true]);
        });

        return redirect()
            ->route('dosen.penilaian.asesmen.rubrik', [$section->id, $assessment->id])
            ->with('notice', 'Rubrik berhasil disimpan.');
    }

    /**
     * Halaman input nilai rubric — tabel besar semua mahasiswa × semua criteria.
     */
    public function scores(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $rubric = $assessment->rubric;
        abort_unless($rubric, 404, 'Assessment ini belum memiliki rubrik.');

        $criteria = $rubric->criteria()->orderBy('id')->get();
        $students = $section->students()->orderBy('name')->get();

        // Prefetch all rubric scores: keyed by "criterion_id:mahasiswa_id"
        $existingScores = StudentRubricScore::whereIn('rubric_criterion_id', $criteria->pluck('id'))
            ->get()
            ->keyBy(fn ($s) => $s->rubric_criterion_id . ':' . $s->mahasiswa_id);

        return view('dosen.penilaian.rubrik-nilai', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'rubric' => $rubric,
            'criteria' => $criteria,
            'students' => $students,
            'existingScores' => $existingScores,
        ]);
    }

    /**
     * Simpan nilai per-criterion untuk semua mahasiswa.
     * Setelah simpan, auto-hitung assessment score dari rubric.
     */
    public function storeScores(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $rubric = $assessment->rubric;
        abort_unless($rubric, 404);

        $criteria = $rubric->criteria()->orderBy('id')->get();
        $criteriaById = $criteria->keyBy('id');
        $enrolledIds = $section->students()->pluck('users.id');
        $dosenId = Auth::guard('web')->id();

        $request->validate([
            'rubric_scores' => ['required', 'array'],
            'rubric_scores.*' => ['array'],
            'rubric_scores.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $assessment, $criteriaById, $enrolledIds, $dosenId) {
            $affectedStudentIds = [];

            foreach ($request->input('rubric_scores', []) as $mahasiswaId => $criterionScores) {
                $mahasiswaId = (int) $mahasiswaId;

                if (! $enrolledIds->contains($mahasiswaId)) {
                    continue;
                }

                foreach ($criterionScores as $criterionId => $score) {
                    $criterionId = (int) $criterionId;
                    $criterion = $criteriaById[$criterionId] ?? null;

                    if (! $criterion) {
                        continue;
                    }

                    $scoreValue = ($score !== null && $score !== '') ? (float) $score : null;

                    // Validate against max_score
                    if ($scoreValue !== null && $scoreValue > (float) $criterion->max_score) {
                        $scoreValue = (float) $criterion->max_score;
                    }

                    StudentRubricScore::updateOrCreate(
                        [
                            'rubric_criterion_id' => $criterionId,
                            'mahasiswa_id' => $mahasiswaId,
                        ],
                        [
                            'score' => $scoreValue,
                        ],
                    );
                }

                $affectedStudentIds[] = $mahasiswaId;
            }

            // Sync calculated assessment scores from rubric for all affected students
            foreach (array_unique($affectedStudentIds) as $studentId) {
                $this->obe->syncRubricToAssessmentScore($assessment, $studentId, $dosenId);
            }
        });

        return redirect()
            ->route('dosen.penilaian.asesmen.rubrik.nilai', [$section->id, $assessment->id])
            ->with('notice', 'Nilai rubrik berhasil disimpan. Nilai asesmen telah dihitung ulang.');
    }

    // ── Helpers ──

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);

        return $section;
    }

    private function authorizeOwnership(ClassSection $section): void
    {
        $currentUserId = Auth::guard('web')->id();

        if (! $currentUserId && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
            $currentUserId = $user?->hasRole(\App\Models\Role::DOSEN) ? $user->id : null;
        }

        abort_unless($currentUserId && ($section->dosen_id === $currentUserId || $section->dosen_pendamping_id === $currentUserId), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    private function authorizeAssessmentBelongsToSection(ClassSection $section, Assessment $assessment): void
    {
        abort_unless($assessment->class_section_id === $section->id, 404);
    }
}
