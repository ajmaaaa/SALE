<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\Cpmk;
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
            || ! empty($resource['coding_steps'])
            || collect($resource['questions'] ?? [])->contains(fn ($q) => ($q['type'] ?? '') === 'coding');
        abort_unless($isCodingContent, 404);

        $isLecturer = $user?->hasRole(Role::DOSEN) ?? false;
        $isMaterial = ($resource['type'] ?? '') === 'materi';
        $isCodingTask = ! $isMaterial && (
            ($resource['type'] === 'coding')
            || (($resource['task_mode'] ?? null) === 'coding')
            || (($resource['question_type'] ?? null) === 'coding')
            || collect($resource['questions'] ?? [])->contains(fn ($q) => ($q['type'] ?? '') === 'coding')
        );

        $reviewStudent = null;
        $submission = null;
        $codingStepsData = [];
        $studentScore = null;
        $codingScoreUrl = null;

        $completionTimeString = null;
        $assessmentAttempt = null;
        $isAttemptRejected = false;
        $attemptRejectionReason = null;
        $hasExpired = false;

        if ($isLecturer) {
            if (! $isMaterial && request()->has('student')) {
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
                    $durationMinutes = ! empty($resource['duration_enabled']) ? (int) ($resource['duration_minutes'] ?? 60) : null;
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
                            $completionTimeString = $spent.'/'.$durationMinutes.' menit';
                        } elseif ($submission && $submission->submitted_at && $attempt && $attempt->started_at) {
                            $spent = max(1, (int) round($attempt->started_at->diffInMinutes($submission->submitted_at)));
                            $completionTimeString = $spent.'/'.$durationMinutes.' menit';
                        } elseif ($submission && $submission->submitted_at && $submission->created_at) {
                            $spent = max(1, (int) round($submission->created_at->diffInMinutes($submission->submitted_at)));
                            $completionTimeString = $spent.'/'.$durationMinutes.' menit';
                        } elseif ($submission && $submission->submitted_at) {
                            $completionTimeString = $durationMinutes.'/'.$durationMinutes.' menit';
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
                            'title' => $cStep['title'] ?? ('Soal '.$stepNum),
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
            if (! $isMaterial && $user && Schema::hasTable('submissions')) {
                $submission = Submission::with('answers')->where('assessment_id', $assessment->id)
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                    ->latest('id')
                    ->first();
            }

            // Catat awal attempt jika durasi aktif dan belum pernah diserahkan
            $isAttemptRejected = false;
            $attemptRejectionReason = null;
            $hasExpired = false;
            if (! $isMaterial && $user && Schema::hasTable('assessment_attempts')) {
                $durationMinutes = ! empty($resource['duration_enabled']) ? (int) ($resource['duration_minutes'] ?? 60) : null;
                if ($durationMinutes && $durationMinutes > 0) {
                    $assessmentAttempt = AssessmentAttempt::where('assessment_id', $assessment->id)
                        ->where('mahasiswa_id', $user->id)
                        ->latest('attempt')
                        ->first();
                    if (! $assessmentAttempt && ! $submission) {
                        $assessmentAttempt = AssessmentAttempt::create([
                            'assessment_id' => $assessment->id,
                            'mahasiswa_id' => $user->id,
                            'attempt' => 1,
                            'started_at' => now(),
                            'deadline_at' => now()->addMinutes($durationMinutes),
                            'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
                        ]);
                    } elseif ($assessmentAttempt) {
                        $hasExpired = ($assessmentAttempt->status === AssessmentAttempt::STATUS_IN_PROGRESS && $assessmentAttempt->deadline_at && now()->greaterThan($assessmentAttempt->deadline_at));
                        if ($hasExpired || in_array($assessmentAttempt->status, [AssessmentAttempt::STATUS_SUBMITTED, AssessmentAttempt::STATUS_REJECTED], true)) {
                            if ($hasExpired) {
                                $assessmentAttempt->update([
                                    'status' => AssessmentAttempt::STATUS_SUBMITTED,
                                    'submitted_at' => $assessmentAttempt->submitted_at ?? now(),
                                ]);
                            }
                            if (! $submission && Schema::hasTable('submissions')) {
                                $submission = Submission::firstOrCreate([
                                    'assessment_id' => $assessment->id,
                                    'user_id' => $user->id,
                                ], [
                                    'assessment_id' => $assessment->id,
                                    'mahasiswa_id' => $user->id,
                                    'attempt' => $assessmentAttempt->attempt ?? 1,
                                    'version' => 1,
                                    'status' => 'pending',
                                    'submitted_at' => $assessmentAttempt->submitted_at ?? now(),
                                    'answer' => json_encode([]),
                                ]);
                            }
                        }
                    }
                }
            }

            if (! $isMaterial && $user && Schema::hasTable('student_assessment_scores')) {
                $studentScoreRec = StudentAssessmentScore::where('assessment_id', $assessment->id)
                    ->where('mahasiswa_id', $user->id)
                    ->first();
                $studentScore = $studentScoreRec?->score;
            }
        }

        if ($submission) {
            $submission->loadMissing('answers');
        }

        $cpmkThreshold = 65.0;
        if ($assessment) {
            $assessment->loadMissing('cpmks');
            if ($assessment->cpmks->isNotEmpty()) {
                $cpmkThreshold = (float) $assessment->cpmks->avg('threshold');
            } elseif (! empty($resource['cpmk']) && Schema::hasTable('cpmks')) {
                $cpmkObj = Cpmk::where('code', $resource['cpmk'])->first();
                if ($cpmkObj && $cpmkObj->threshold !== null) {
                    $cpmkThreshold = (float) $cpmkObj->threshold;
                }
            }
        }

        $stepAttUuids = [];
        $stepsForAttachments = ! empty($codingStepsData) ? $codingStepsData : ($resource['coding_steps'] ?? []);
        if (! empty($stepsForAttachments)) {
            foreach ($stepsForAttachments as $step) {
                if (! empty($step['attachments'])) {
                    foreach ($step['attachments'] as $att) {
                        $stepAttUuids[] = is_array($att) ? ($att['uuid'] ?? ($att['id'] ?? '')) : (string) $att;
                    }
                }
                if (! empty($step['attachment'])) {
                    $stepAttUuids[] = is_array($step['attachment'])
                        ? ($step['attachment']['uuid'] ?? ($step['attachment']['id'] ?? ''))
                        : (string) $step['attachment'];
                }
            }
        }
        $stepAttachmentsMap = collect();
        if (! empty($stepAttUuids) && Schema::hasTable('attachments')) {
            $stepAttachmentsMap = Attachment::whereIn('uuid', array_filter($stepAttUuids))->get()->keyBy('uuid');
        }

        $hasBeenGraded = false;
        if (! $isLecturer && ! $isMaterial && $user && Schema::hasTable('student_assessment_scores')) {
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
            'isArchived' => $section?->isArchived() ?? false,
            'cpmkThreshold' => $cpmkThreshold,
            'stepAttachmentsMap' => $stepAttachmentsMap,
            'attemptDeadline' => ($hasExpired || ($assessmentAttempt && $assessmentAttempt->status !== AssessmentAttempt::STATUS_IN_PROGRESS) || ($isAttemptRejected ?? false)) ? null : $assessmentAttempt?->deadline_at,
            'isAttemptRejected' => $isAttemptRejected ?? false,
            'attemptRejectionReason' => $attemptRejectionReason ?? null,
            'assessmentAttempt' => $assessmentAttempt,
        ]);
    }

    public function codeSubmittedResult(int $course, int $item): View|RedirectResponse
    {
        $user = auth()->user();
        $section = ClassSection::with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])->findOrFail($course);
        $canAccess = $user?->hasRole(Role::DOSEN)
            ? $user->can('manage', $section)
            : ($user?->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists());

        abort_unless($canAccess, 403, 'Anda tidak terdaftar pada kelas ini.');

        $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);

        $submission = null;
        if (Schema::hasTable('submissions') && $user) {
            $submission = Submission::where('assessment_id', $assessment->id)
                ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                ->latest('id')
                ->first();
        }

        return view('mahasiswa.assignment-code-submitted', [
            'section' => $section,
            'assessment' => $assessment,
            'submission' => $submission,
        ]);
    }
}
