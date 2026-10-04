<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
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
            if ($section->isArchived()) {
                return redirect()->route('mahasiswa.course.item', [$section->id, $assessment->id])
                    ->with('notice', 'Kelas telah diarsipkan. Halaman pemrograman tidak dapat diakses.');
            }
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

        $completionTimeString = null;

        if ($isLecturer) {
            if (!$isMaterial && request()->has('student')) {
                $studentId = (int) request()->query('student');
                $reviewStudent = $section->students()->where('users.id', $studentId)->first()
                    ?? $section->enrollmentRecords()->where('users.id', $studentId)->first()
                    ?? User::where('id', $studentId)->first();
                if ($reviewStudent && Schema::hasTable('submissions')) {
                    $submission = Submission::with('answers')->where('assessment_id', $assessment->id)
                        ->where(fn ($q) => $q->where('user_id', $reviewStudent->id)->orWhere('mahasiswa_id', $reviewStudent->id))
                        ->latest('id')
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

                    // Hitung waktu selesai pengerjaan (misal 40/60 menit)
                    $durationMinutes = !empty($resource['duration_enabled']) ? (int)($resource['duration_minutes'] ?? 60) : null;
                    if ($durationMinutes && $durationMinutes > 0) {
                        $attempt = null;
                        if (Schema::hasTable('assessment_attempts')) {
                            $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)
                                ->where('mahasiswa_id', $reviewStudent->id)
                                ->latest('attempt')
                                ->first();
                        }
                        if ($attempt && $attempt->started_at && $attempt->submitted_at) {
                            $spent = max(1, (int) round($attempt->started_at->diffInMinutes($attempt->submitted_at)));
                            $completionTimeString = $spent . '/' . $durationMinutes . ' menit';
                        } elseif ($submission && $submission->submitted_at && $attempt && $attempt->started_at) {
                            $spent = max(1, (int) round($attempt->started_at->diffInMinutes($submission->submitted_at)));
                            $completionTimeString = $spent . '/' . $durationMinutes . ' menit';
                        } elseif ($submission && $submission->submitted_at && $submission->created_at) {
                            $spent = max(1, (int) round($submission->created_at->diffInMinutes($submission->submitted_at)));
                            $completionTimeString = $spent . '/' . $durationMinutes . ' menit';
                        } elseif ($submission && $submission->submitted_at) {
                            $completionTimeString = $durationMinutes . '/' . $durationMinutes . ' menit';
                        } else {
                            $completionTimeString = 'Belum selesai';
                        }
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
                $submission = Submission::with('answers')->where('assessment_id', $assessment->id)
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                    ->latest('id')
                    ->first();
            }

            // Catat awal attempt jika durasi aktif dan belum pernah diserahkan
            if (!$isMaterial && $user && Schema::hasTable('assessment_attempts')) {
                $durationMinutes = !empty($resource['duration_enabled']) ? (int)($resource['duration_minutes'] ?? 60) : null;
                if ($durationMinutes && $durationMinutes > 0 && !$submission) {
                    $assessmentAttempt = AssessmentAttempt::where('assessment_id', $assessment->id)
                        ->where('mahasiswa_id', $user->id)
                        ->latest('attempt')
                        ->first();
                    if (!$assessmentAttempt) {
                        AssessmentAttempt::create([
                            'assessment_id' => $assessment->id,
                            'class_section_id' => $section->id,
                            'mahasiswa_id' => $user->id,
                            'attempt' => 1,
                            'started_at' => now(),
                            'deadline_at' => now()->addMinutes($durationMinutes),
                            'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
                        ]);
                    }
                }
            }

            if (!$isMaterial && $user && Schema::hasTable('student_assessment_scores')) {
                $studentScoreRec = StudentAssessmentScore::where('assessment_id', $assessment->id)
                    ->where('mahasiswa_id', $user->id)
                    ->first();
                $studentScore = $studentScoreRec?->score;
            }
        }

        $hasBeenGraded = false;
        if (!$isLecturer && !$isMaterial && $user && Schema::hasTable('student_assessment_scores')) {
            $hasBeenGraded = StudentAssessmentScore::where('assessment_id', $assessment->id)
                ->where('mahasiswa_id', $user->id)
                ->where(function ($query) {
                    $query->whereIn('status', [
                        StudentAssessmentScore::STATUS_PARTIAL,
                        StudentAssessmentScore::STATUS_FINAL,
                        StudentAssessmentScore::STATUS_PUBLISHED,
                    ])->orWhereNotNull('score');
                })
                ->exists();
        }

        return view('mahasiswa.assignment-code', [
            'item' => $resource,
            'course' => LearningPreview::databaseCourse($section),
            'submission' => $submission,
            'reviewStudent' => $reviewStudent,
            'completionTimeString' => $completionTimeString,
            'codingStepsData' => $codingStepsData,
            'studentScore' => $studentScore,
            'hasBeenGraded' => $hasBeenGraded,
            'codingScoreUrl' => $codingScoreUrl,
            'section' => $section,
            'assessment' => $assessment,
        ]);
    }
}
