<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Support\LearningPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        return view('mahasiswa.assignment');
    }

    public function assignmentCode(int $assignment): View|RedirectResponse
    {
        $assessment = Assessment::findOrFail($assignment);
        return $this->courseCode($assessment->class_section_id, $assignment);
    }

    public function courseCode(int $course, int $item): View|RedirectResponse
    {
        $user = auth()->user();
        $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->findOrFail($course);
        $canAccess = $user?->hasRole(Role::DOSEN)
            ? $user->can('manage', $section)
            : ($user?->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists());

        abort_unless($canAccess, 403, 'Anda tidak terdaftar pada kelas ini.');

        $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);
        if ($user->hasRole(Role::MAHASISWA)) {
            abort_unless($assessment->status === 'published', 403, 'Materi belum tersedia atau sudah ditutup.');
        }

        $resource = LearningPreview::databaseAssessment($assessment);
        $isCodingContent = $resource['type'] === 'coding'
            || ($resource['type'] === 'materi' && ($resource['material_mode'] ?? null) === 'coding')
            || (($resource['task_mode'] ?? null) === 'coding')
            || (($resource['question_type'] ?? null) === 'coding')
            || !empty($resource['coding_steps'])
            || collect($resource['questions'] ?? [])->contains(fn($q) => ($q['type'] ?? '') === 'coding');
        abort_unless($isCodingContent, 404);

        $isLecturer = $user?->hasRole(Role::DOSEN) ?? false;
        $isMaterial = ($resource['type'] ?? '') === 'materi';
        $isCodingTask = !$isMaterial && (
            ($resource['type'] === 'coding')
            || (($resource['task_mode'] ?? null) === 'coding')
            || (($resource['question_type'] ?? null) === 'coding')
            || collect($resource['questions'] ?? [])->contains(fn($q) => ($q['type'] ?? '') === 'coding')
        );

        $reviewStudent = null;
        $submission = null;
        $codingStepsData = [];
        $studentScore = null;
        $codingScoreUrl = null;

        if ($isLecturer) {
            if (!$isMaterial && request()->has('student')) {
                $studentId = (int) request()->query('student');
                $reviewStudent = $section->students()->where('users.id', $studentId)->first();
                if ($reviewStudent && Schema::hasTable('submissions')) {
                    $submission = Submission::where('assessment_id', $assessment->id)
                        ->where(fn ($q) => $q->where('user_id', $reviewStudent->id)->orWhere('mahasiswa_id', $reviewStudent->id))
                        ->latest()
                        ->first();
                }

                if ($reviewStudent) {
                    $codingScoreUrl = route('dosen.penilaian.asesmen.student.coding_scores', [$section->id, $assessment->id, $reviewStudent->id]);

                    if (Schema::hasTable('student_assessment_scores')) {
                        $studentScoreRec = StudentAssessmentScore::where('assessment_id', $assessment->id)
                            ->where('mahasiswa_id', $reviewStudent->id)
                            ->first();
                        $studentScore = $studentScoreRec?->score;
                    }

                    $rawCodingSteps = $resource['coding_steps'] ?? [];
                    if (empty($rawCodingSteps)) {
                        $rawCodingSteps = [[
                            'title' => $assessment->name,
                            'cpmk' => $resource['cpmk'] ?? ($assessment->cpmks()->first()?->code ?? 'CPMK-01'),
                            'points' => 100,
                        ]];
                    }
                    $totalStepsCount = max(1, count($rawCodingSteps));

                    foreach ($rawCodingSteps as $cIdx => $cStep) {
                        $stepNum = $cIdx + 1;
                        $stepQId = (string) $stepNum;
                        $stepMaxPoints = (float) ((isset($cStep['points']) && (float) $cStep['points'] > 0) ? $cStep['points'] : round(100 / $totalStepsCount, 1));
                        $stepCpmk = $cStep['cpmk'] ?? '';

                        $stepAns = null;
                        if ($submission && Schema::hasTable('submission_answers')) {
                            $stepAns = SubmissionAnswer::where('submission_id', $submission->id)
                                ->where(fn ($q) => $q->where('question_id', $stepQId)->orWhere('question_index', $cIdx))
                                ->first();
                        }

                        $codingStepsData[] = [
                            'number' => $stepNum,
                            'title' => $cStep['title'] ?? ('Soal ' . $stepNum),
                            'cpmk' => $stepCpmk,
                            'max_points' => $stepMaxPoints,
                            'current_score' => $stepAns?->earned_score !== null ? (float) $stepAns->earned_score : '',
                            'answer_id' => $stepAns?->id ?? $stepQId,
                        ];
                    }
                }
            } elseif ($isCodingTask) {
                return redirect()->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]);
            }
        } else {
            if (!$isMaterial && $user && Schema::hasTable('submissions')) {
                $submission = Submission::where('assessment_id', $assessment->id)
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                    ->latest()
                    ->first();
            }

            if (!$isMaterial && $user && Schema::hasTable('student_assessment_scores')) {
                $studentScoreRec = StudentAssessmentScore::where('assessment_id', $assessment->id)
                    ->where('mahasiswa_id', $user->id)
                    ->first();
                $studentScore = $studentScoreRec?->score;
            }
        }

        return view('mahasiswa.assignment-code', [
            'item' => $resource,
            'course' => LearningPreview::databaseCourse($section),
            'submission' => $submission,
            'reviewStudent' => $reviewStudent,
            'codingStepsData' => $codingStepsData,
            'studentScore' => $studentScore,
            'codingScoreUrl' => $codingScoreUrl,
            'section' => $section,
            'assessment' => $assessment,
        ]);
    }
}
