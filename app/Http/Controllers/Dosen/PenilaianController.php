<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman 2 — Dashboard Penilaian Kelas (Langkah 1: Matriks Bobot).
     */
    public function dashboard(ClassSection $section): RedirectResponse
    {
        $this->authorizeOwnership($section);

        return redirect()->route('dosen.penilaian.matriks', $section);
    }

    /**
     * Tab: Rekap CPMK (Langkah 3: Satu kartu mandiri per CPMK).
     */
    public function rekap(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $students = $section->students()->orderBy('name')->get();
        $studentIds = $students->pluck('id');
        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();
        $assessmentIds = $assessments->pluck('id');

        // Pre-fetch raw scores untuk efisiensi
        $rawAssessmentScores = \App\Models\StudentAssessmentScore::whereIn('assessment_id', $assessmentIds)->get()
            ->groupBy(fn ($row) => $row->assessment_id . '_' . $row->mahasiswa_id);

        $rawCpmkScores = \App\Models\StudentAssessmentCpmkScore::whereIn('assessment_id', $assessmentIds)->get()
            ->groupBy(fn ($row) => $row->assessment_id . '_' . $row->cpmk_id . '_' . $row->mahasiswa_id);

        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $totalCpmkWeight = (float) $cpmkWeights->sum();

        // Siapkan kartu mandiri untuk setiap CPMK
        $cpmkCards = [];
        foreach ($cpmks as $cpmk) {
            $weight = (float) ($cpmkWeights[$cpmk->id] ?? 0);

            // Komponen asesmen pembentuk CPMK ini
            $components = [];
            foreach ($assessments as $asmt) {
                $w = $this->obe->assessmentCpmkEffectiveWeight($asmt, $cpmk);
                if ($w > 0) {
                    $components[] = [
                        'assessment' => $asmt,
                        'weight' => $w,
                        'weight_formatted' => rtrim(rtrim(number_format($w, 1), '0'), '.'),
                        'max_score' => $this->obe->assessmentCpmkMaxScore($asmt, $cpmk),
                    ];
                }
            }

            // Agregat kelas untuk CPMK ini
            $agg = $this->obe->cpmkClassAggregate($cpmk, $studentIds, $section->id);

            // Data mahasiswa beserta skor per komponen penyusun dan skor akhir CPMK
            $cardStudents = [];
            foreach ($students as $student) {
                $scores = [];
                foreach ($components as $comp) {
                    $asmtId = $comp['assessment']->id;
                    $cpmkScoreKey = $asmtId . '_' . $cpmk->id . '_' . $student->id;
                    $asmtScoreKey = $asmtId . '_' . $student->id;

                    $cpmkSpecific = $rawCpmkScores->get($cpmkScoreKey)?->first();
                    if ($cpmkSpecific !== null && $cpmkSpecific->score !== null) {
                        $scores[$asmtId] = (float) $cpmkSpecific->score;
                    } else {
                        $asmtRow = $rawAssessmentScores->get($asmtScoreKey)?->first();
                        $scores[$asmtId] = $asmtRow?->score !== null ? (float) $asmtRow->score : null;
                    }
                }

                $cpmkScore = $this->obe->cpmkScore($cpmk, $student->id, $section->id);

                $cardStudents[] = [
                    'student' => $student,
                    'scores' => $scores,
                    'cpmk_score' => $cpmkScore,
                ];
            }

            $cpmkCards[] = [
                'cpmk' => $cpmk,
                'weight' => $weight,
                'weight_formatted' => rtrim(rtrim(number_format($weight, 1), '0'), '.'),
                'components' => $components,
                'aggregate' => $agg,
                'students' => $cardStudents,
            ];
        }

        return view('dosen.rekap', [
            'section' => $this->withHeaderCounts($section, null, $cpmks),
            'cpmks' => $cpmks,
            'cpmkWeights' => $cpmkWeights,
            'totalCpmkWeight' => $totalCpmkWeight,
            'cpmkCards' => $cpmkCards,
            'obe' => $this->obe,
        ]);
    }

    /**
     * Tab: Matriks Penilaian (Langkah 1: Matriks Versi C Interaktif).
     */
    public function matriks(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);

        return view('dosen.matriks', [
            'section' => $this->withHeaderCounts($section, null, $cpmks),
            'cpmks' => $cpmks,
            'cpmkWeights' => $cpmkWeights,
            'assessments' => $assessments,
            'obe' => $this->obe,
        ]);
    }

    /**
     * Simpan Matriks Penilaian Versi C:
     * Menyimpan bobot setiap sel CPMK x Asesmen, dan menyinkronkan
     * total kolom ke assessments.final_weight secara otomatis.
     */
    public function saveMatriks(Request $request, ClassSection $section): RedirectResponse
    {
        $this->authorizeOwnership($section);

        $request->validate([
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $matrix = $request->input('matrix', []);
        $assessments = $section->assessments()->get();
        $cpmks = $this->cpmksFor($section);
        $cpmkIds = $cpmks->pluck('id');

        $grandTotal = 0.0;
        $prepared = [];

        foreach ($assessments as $assessment) {
            $colSum = 0.0;
            $syncData = [];

            if (isset($matrix[$assessment->id]) && is_array($matrix[$assessment->id])) {
                foreach ($matrix[$assessment->id] as $cpmkId => $val) {
                    $cpmkId = (int) $cpmkId;
                    if (! $cpmkIds->contains($cpmkId)) {
                        continue;
                    }

                    $weight = ($val !== null && $val !== '') ? (float) $val : 0.0;
                    if ($weight < 0) {
                        return back()->withErrors(['matrix' => 'Bobot pada matriks tidak boleh bernilai negatif.'])->withInput();
                    }
                    if ($weight > 0) {
                        $colSum += $weight;
                        $syncData[$cpmkId] = ['weight' => $weight];
                    }
                }
            }

            $prepared[] = [
                'assessment' => $assessment,
                'final_weight' => round($colSum, 2),
                'sync_data' => $syncData,
            ];
            $grandTotal += $colSum;
        }

        $grandTotal = round($grandTotal, 2);
        $grandTotalFormatted = rtrim(rtrim(number_format($grandTotal, 2), '0'), '.');

        // Opsi 1 (Sangat Ketat): Matriks hanya boleh disimpan jika total tepat 100%
        if (abs($grandTotal - 100.0) > 0.01) {
            $diff = round(abs($grandTotal - 100.0), 2);
            $diffFormatted = rtrim(rtrim(number_format($diff, 2), '0'), '.');
            $detail = $grandTotal > 100.0
                ? "kelebihan {$diffFormatted}%"
                : "kurang {$diffFormatted}%";

            return back()->withErrors([
                'matrix' => "Total bobot matriks penilaian harus tepat 100% (saat ini {$grandTotalFormatted}%, {$detail}). Matriks tidak dapat disimpan sebelum total tepat 100%."
            ])->withInput();
        }

        DB::transaction(function () use ($prepared) {
            foreach ($prepared as $item) {
                $item['assessment']->update(['final_weight' => $item['final_weight']]);
                $item['assessment']->cpmks()->sync($item['sync_data']);
            }
        });

        return redirect()->route('dosen.penilaian.matriks', $section->id)
            ->with('notice', "Matriks penilaian valid (Total Bobot: {$grandTotalFormatted}%). Matriks telah terkunci dan Anda dapat melanjutkan ke Input Nilai.");
    }

    /**
     * Tab: Daftar Asesmen (Langkah 2: Input Nilai per Asesmen).
     */
    public function asesmen(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();

        return view('dosen.penilaian.asesmen', [
            'section' => $this->withHeaderCounts($section),
            'assessments' => $assessments,
            'obe' => $this->obe,
        ]);
    }

    /**
     * Tab: Rekap CPMK (Langkah 3 alias / rute cpmk lama).
     */
    public function cpmk(ClassSection $section)
    {
        return $this->rekap($section);
    }

    /**
     * Tab: Rekap CPL (Langkah 4: Satu kartu mandiri per CPL).
     */
    public function cpl(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $cplCards = [];
        foreach ($cpls as $cpl) {
            $contributingCpmks = $cpl->cpmks; // relasi CPL -> CPMK dengan pivot weight
            $cpmkItems = [];
            foreach ($contributingCpmks as $cpmk) {
                $cWeight = (float) $cpmk->pivot->weight;
                $cAgg = $this->obe->cpmkClassAggregate($cpmk, $studentIds, $section->id);
                $cpmkItems[] = [
                    'cpmk' => $cpmk,
                    'weight' => $cWeight,
                    'weight_formatted' => rtrim(rtrim(number_format($cWeight, 2), '0'), '.'),
                    'class_average' => $cAgg['average'],
                ];
            }

            $agg = $this->obe->cplClassAggregate($cpl, $studentIds, $section->id);

            $cardStudents = [];
            foreach ($students as $student) {
                $cpmkScores = [];
                foreach ($contributingCpmks as $cpmk) {
                    $cpmkScores[$cpmk->id] = $this->obe->cpmkScore($cpmk, $student->id, $section->id);
                }
                $cplScore = $this->obe->cplScore($cpl, $student->id, $section->id);

                $cardStudents[] = [
                    'student' => $student,
                    'cpmk_scores' => $cpmkScores,
                    'cpl_score' => $cplScore,
                ];
            }

            $cplCards[] = [
                'cpl' => $cpl,
                'contributing_cpmks' => $cpmkItems,
                'aggregate' => $agg,
                'students' => $cardStudents,
            ];
        }

        return view('dosen.cpl', [
            'section' => $this->withHeaderCounts($section, $cpls),
            'cpls' => $cpls,
            'cplCards' => $cplCards,
            'obe' => $this->obe,
        ]);
    }

    /**
     * Tab: Pengaturan Penilaian (Dialihkan ke Matriks Penilaian).
     */
    public function pengaturan(ClassSection $section): RedirectResponse
    {
        return redirect()->route('dosen.penilaian.matriks', $section->id);
    }

    private function cpmksFor(ClassSection $section)
    {
        return Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
    }

    private function cplsFor(ClassSection $section)
    {
        $cpmkIds = $this->cpmksFor($section)->pluck('id');

        return Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))
            ->with(['cpmks' => fn ($q) => $q->whereIn('cpmks.id', $cpmkIds)])
            ->orderBy('code')
            ->get();
    }

    private function withHeaderCounts(ClassSection $section, $cpls = null, $cpmks = null): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);
        $section->cpmk_used_count = ($cpmks ?? $this->cpmksFor($section))->count();
        $section->cpl_used_count = ($cpls ?? $this->cplsFor($section))->count();

        return $section;
    }

    private function gradeLetter(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 85 => 'A',
            $score >= 80 => 'AB',
            $score >= 75 => 'B',
            $score >= 70 => 'BC',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }

    /**
     * Data-ownership check fleksibel (Auth Laravel + Switch Account Session).
     */
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
}
