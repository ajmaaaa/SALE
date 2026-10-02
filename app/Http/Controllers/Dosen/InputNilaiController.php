<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Services\ObeCalculationService;
use App\Services\QuizGradingService;
use App\Support\QuizQuestion;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InputNilaiController extends Controller
{
    public function __construct(
        private ObeCalculationService $obe,
        private QuizGradingService $quizGrades,
    ) {}

    /**
     * Halaman Input Nilai — menampilkan semua mahasiswa enrolled
     * dengan kolom input untuk setiap CPMK yang diukur oleh asesmen.
     */
    public function show(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $students = $section->students()->orderBy('name')->get();
        $cpmks = $assessment->cpmks()->orderBy('code')->get();

        if ($cpmks->isEmpty() && ! empty($assessment->learning_payload)) {
            $payload = $assessment->learning_payload;
            $rawQuestions = $payload['questions'] ?? [];
            $manualWeights = $payload['manual_cpmk_weights'] ?? [];
            $mataKuliahCpmks = $section->mataKuliah?->cpmks()->get();
            $allCpmks = ($mataKuliahCpmks && $mataKuliahCpmks->isNotEmpty()) ? $mataKuliahCpmks : (Schema::hasTable('cpmks') ? Cpmk::all() : collect());

            $findCpmk = function ($code) use ($allCpmks) {
                $normTarget = preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim((string) $code))));
                return $allCpmks->first(function ($c) use ($normTarget) {
                    $normC = preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($c->code))));
                    return $normC === $normTarget;
                }) ?? (Schema::hasTable('cpmks') ? Cpmk::where('code', $code)->orWhere('code', str_replace(' ', '-', $code))->first() : null);
            };

            $syncData = [];
            if (! empty($rawQuestions)) {
                $cpmkCounts = array_count_values(array_filter(array_column($rawQuestions, 'cpmk')));
                $totalQ = max(1, count($rawQuestions));
                $accumulated = 0.0;
                $itemsLeft = count($cpmkCounts);
                foreach ($cpmkCounts as $code => $cnt) {
                    $itemsLeft--;
                    $cpmkModel = $findCpmk($code);
                    if ($cpmkModel) {
                        $w = ($itemsLeft === 0) ? round(100.00 - $accumulated, 2) : round(($cnt / $totalQ) * 100, 2);
                        $accumulated += $w;
                        $syncData[$cpmkModel->id] = ['weight' => $w];
                    }
                }
            } elseif (! empty($manualWeights)) {
                foreach ($manualWeights as $code => $weight) {
                    $cpmkModel = $findCpmk($code);
                    if ($cpmkModel && (float) $weight > 0) {
                        $syncData[$cpmkModel->id] = ['weight' => (float) $weight];
                    }
                }
            } elseif (! empty($payload['coding_steps'])) {
                $steps = $payload['coding_steps'];
                $cpmkPoints = [];
                foreach ($steps as $s) {
                    $c = $s['cpmk'] ?? '';
                    if ($c) {
                        $cpmkPoints[$c] = ($cpmkPoints[$c] ?? 0.0) + (float) ((isset($s['points']) && (float) $s['points'] > 0) ? $s['points'] : 1.0);
                    }
                }
                $totalPoints = array_sum($cpmkPoints) ?: 1.0;
                $accumulated = 0.0;
                $itemsLeft = count($cpmkPoints);
                foreach ($cpmkPoints as $code => $pts) {
                    $itemsLeft--;
                    $cpmkModel = $findCpmk($code);
                    if ($cpmkModel) {
                        $w = ($itemsLeft === 0) ? round(100.00 - $accumulated, 2) : round(($pts / $totalPoints) * 100, 2);
                        $accumulated += $w;
                        $syncData[$cpmkModel->id] = ['weight' => $w];
                    }
                }
            }

            if (! empty($syncData)) {
                $assessment->cpmks()->sync($syncData);
                $cpmks = $assessment->cpmks()->orderBy('code')->get();
            }
        }

        // Existing per-CPMK scores: keyed by "{cpmk_id}:{mahasiswa_id}"
        $existingCpmkScores = StudentAssessmentCpmkScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn ($s) => $s->cpmk_id.':'.$s->mahasiswa_id);

        // Prefetch existing overall scores indexed by mahasiswa_id
        $existingScores = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('mahasiswa_id');

        $gradedCount = $existingScores->filter(fn ($s) => $s->score !== null)->count();

        // Prefetch questions & student submissions for viewing
        $payload = $assessment->learning_payload ?? [];
        $rawQuestions = $payload['questions'] ?? [];
        if (! empty($rawQuestions)) {
            $rawQuestions = QuizQuestion::canonicalizeQuestions($rawQuestions);
        }

        $rawTaskMode = $payload['task_mode'] ?? null;
        $isCoding = in_array($assessment->type, ['coding'], true)
            || $rawTaskMode === 'coding'
            || (($payload['type'] ?? '') === 'tugas' && ($payload['question_type'] ?? '') === 'coding');
        $isTipeSoal = ! $isCoding && (
            $rawTaskMode === 'quiz'
            || (! empty($rawQuestions) && $rawTaskMode !== 'regular')
            || ($assessment->type === 'kuis' && $rawTaskMode !== 'regular')
        );
        $isAssignment = ! $isTipeSoal;

        $essayQuestions = collect($rawQuestions)->filter(function ($q) {
            $type = $q['type'] ?? '';
            return in_array($type, ['uraian', 'esai', 'essay'], true);
        })->values();

        $hasEssayQuestions = $essayQuestions->isNotEmpty();
        $hasEssay = $hasEssayQuestions || $isAssignment;

        $dueAt = $assessment->due_at
            ?? (! empty($payload['due']) ? \Carbon\Carbon::parse($payload['due']) : null);

        $submissions = Schema::hasTable('submissions')
            ? Submission::where('assessment_id', $assessment->id)
                ->with('answers')
                ->get()
            : collect();

        $submissionsByStudent = [];
        foreach ($submissions as $sub) {
            $sKey = $sub->mahasiswa_id ?: $sub->user_id;
            if ($sKey) {
                $submissionsByStudent[$sKey] = $sub;
            }
        }

        $studentEssayData = [];
        foreach ($students as $student) {
            $sub = $submissionsByStudent[$student->id] ?? null;
            $hasSubmission = $sub !== null;
            $submittedAtCarbon = $sub?->submitted_at;
            $submittedAt = $submittedAtCarbon?->translatedFormat('d M Y, H:i');
            $isLate = false;
            if ($sub && $submittedAtCarbon && $dueAt) {
                $isLate = $submittedAtCarbon->greaterThan($dueAt);
            }

            $processedQuestions = [];
            $essays = [];
            if ($isTipeSoal && ! empty($rawQuestions)) {
                foreach ($rawQuestions as $idx => $q) {
                    $qId = $q['id'] ?? $idx;
                    $type = $q['type'] ?? 'pilihan';
                    $isEssay = in_array($type, ['uraian', 'esai', 'essay'], true);
                    if (! $isEssay) {
                        continue;
                    }

                    $answerText = null;
                    $dbAns = null;

                    if ($sub && $sub->relationLoaded('answers')) {
                        $dbAns = $sub->answers->first(function ($a) use ($qId, $idx) {
                            return ($a->question_id !== null && (string) $a->question_id === (string) $qId)
                                || ($a->question_index !== null && (int) $a->question_index === (int) $idx);
                        });
                        if ($dbAns && $dbAns->answer_text !== null) {
                            $answerText = $dbAns->answer_text;
                        }
                    }

                    if ($answerText === null && $sub && ! empty($sub->question_answers)) {
                        $qa = $sub->question_answers;
                        if (isset($qa[(string) $qId]['text'])) {
                            $answerText = $qa[(string) $qId]['text'];
                        } elseif (isset($qa[$idx]['text'])) {
                            $answerText = $qa[$idx]['text'];
                        } elseif (isset($qa[(string) $qId]) && is_string($qa[(string) $qId])) {
                            $answerText = $qa[(string) $qId];
                        } elseif (isset($qa[$idx]) && is_string($qa[$idx])) {
                            $answerText = $qa[$idx];
                        }
                    }

                    if ($answerText === null && $essayQuestions->count() === 1 && $isEssay) {
                        $answerText = $sub?->answer;
                    }

                    if ($isEssay && ! $dbAns && $sub && Schema::hasTable('submission_answers')) {
                        $dbAns = SubmissionAnswer::firstOrCreate([
                            'submission_id' => $sub->id,
                            'question_id' => (string) $qId,
                            'version' => $sub->version ?? 1,
                        ], [
                            'answer_text' => $answerText,
                            'max_score' => (float) ($q['points'] ?? 0),
                            'grading_status' => 'manual_pending',
                        ]);
                    }

                    $itemData = [
                        'number' => $idx + 1,
                        'type' => $type,
                        'type_label' => match ($type) {
                            'uraian', 'esai', 'essay' => 'Esai',
                            'pilihan' => 'Pilihan Ganda',
                            'boolean' => 'Benar / Salah',
                            'menjodohkan' => 'Menjodohkan',
                            'kompleks' => 'Pilihan Ganda Kompleks',
                            default => ucfirst($type),
                        },
                        'is_essay' => $isEssay,
                        'prompt' => $q['prompt'] ?? ($q['title'] ?? 'Pertanyaan Soal'),
                        'max_points' => (float) ($q['points'] ?? ($dbAns?->max_score ?? 0)),
                        'cpmk' => $q['cpmk_code'] ?? ($q['cpmk'] ?? null),
                        'answer_text' => $answerText,
                        'student_answer' => null,
                        'current_score' => $dbAns?->earned_score,
                        'answer_id' => $dbAns?->id,
                        'question_id' => (string) $qId,
                        'score_url' => ($isEssay && $dbAns)
                            ? route('dosen.penilaian.asesmen.answer.score', [$section, $assessment, $dbAns])
                            : null,
                    ];

                    $processedQuestions[] = $itemData;
                    if ($isEssay) {
                        $essays[] = $itemData;
                    }
                }
            }

            $attachedFiles = [];
            if ($sub) {
                $fileUuids = is_array($sub->file_ids) ? $sub->file_ids : (! empty($sub->file_ids) ? [$sub->file_ids] : []);
                if (Schema::hasTable('attachments')) {
                    $dbAttachments = \App\Models\Attachment::where('submission_id', $sub->id)->get();
                    foreach ($dbAttachments as $dbAtt) {
                        if (! in_array($dbAtt->uuid, $fileUuids, true)) {
                            $fileUuids[] = $dbAtt->uuid;
                        }
                    }
                }
                $attachments = Schema::hasTable('attachments')
                    ? \App\Models\Attachment::whereIn('uuid', $fileUuids)->get()
                    : collect();
                foreach ($fileUuids as $fid) {
                    $fidStr = is_array($fid) ? ($fid['id'] ?? '') : (string) $fid;
                    if (empty($fidStr)) {
                        continue;
                    }
                    $att = $attachments->firstWhere('uuid', $fidStr);
                    $meta = \App\Support\LearningPreview::fileMeta($fidStr);
                    $name = $att?->name ?? ($meta['name'] ?? (is_array($fid) ? ($fid['name'] ?? $fidStr) : $fidStr));
                    $attachedFiles[] = [
                        'id' => $fidStr,
                        'name' => $name,
                        'url' => route('preview.file', ['file' => $fidStr, 'inline' => 1], false),
                    ];
                }
            }

            $cpmkList = [];
            foreach ($cpmks as $cpmk) {
                $cpmkScoreObj = $existingCpmkScores->get($cpmk->id . ':' . $student->id);
                $maxScore = (int) $this->obe->assessmentCpmkMaxScore($assessment, $cpmk);
                $cpmkList[] = [
                    'id' => $cpmk->id,
                    'code' => $cpmk->code,
                    'description' => $cpmk->description ?? '',
                    'max_score' => $maxScore,
                    'current_score' => $cpmkScoreObj?->score !== null ? (float) $cpmkScoreObj->score : '',
                ];
            }

            $codingStepsData = [];
            if ($isCoding) {
                $rawCodingSteps = $payload['coding_steps'] ?? [];
                if (empty($rawCodingSteps)) {
                    $rawCodingSteps = [[
                        'title' => $assessment->name,
                        'cpmk' => $payload['cpmk'] ?? ($cpmks->first()?->code ?? 'CPMK-01'),
                        'points' => 100,
                    ]];
                }

                $totalStepsCount = max(1, count($rawCodingSteps));
                $parsedSubmittedFiles = [];
                if ($sub && ! empty($sub->answer)) {
                    try {
                        $decoded = json_decode($sub->answer, true);
                        if (is_array($decoded)) {
                            $parsedSubmittedFiles = $decoded;
                        }
                    } catch (\Throwable $e) {}
                }

                foreach ($rawCodingSteps as $cIdx => $cStep) {
                    $stepNum = $cIdx + 1;
                    $stepQId = (string) $stepNum;
                    $stepMaxPoints = (float) ((isset($cStep['points']) && (float) $cStep['points'] > 0) ? $cStep['points'] : round(100 / $totalStepsCount, 1));
                    $stepCpmk = $cStep['cpmk'] ?? '';

                    $stepAns = null;
                    if ($sub && $sub->relationLoaded('answers')) {
                        $stepAns = $sub->answers->first(function ($a) use ($stepQId, $cIdx) {
                            return ($a->question_id !== null && (string) $a->question_id === $stepQId)
                                || ($a->question_index !== null && (int) $a->question_index === (int) $cIdx);
                        });
                    }

                    if (! $stepAns && $sub && Schema::hasTable('submission_answers')) {
                        $stepAns = SubmissionAnswer::firstOrCreate([
                            'submission_id' => $sub->id,
                            'question_id' => $stepQId,
                            'version' => $sub->version ?? 1,
                        ], [
                            'question_index' => $cIdx,
                            'max_score' => $stepMaxPoints,
                            'grading_status' => 'manual_pending',
                        ]);
                    }

                    $matchedFile = collect($parsedSubmittedFiles)->first(function ($f) use ($stepNum) {
                        return (isset($f['step']) && (int) $f['step'] === $stepNum)
                            || (! isset($f['step']) && preg_match('/_soal_' . $stepNum . '\./', $f['name'] ?? ''));
                    }) ?? ($parsedSubmittedFiles[$cIdx] ?? null);

                    $codingStepsData[] = [
                        'number' => $stepNum,
                        'title' => $cStep['title'] ?? ('Soal ' . $stepNum),
                        'cpmk' => $stepCpmk,
                        'max_points' => $stepMaxPoints,
                        'current_score' => $stepAns?->earned_score !== null ? (float) $stepAns->earned_score : '',
                        'answer_id' => $stepAns?->id ?? $stepQId,
                        'has_code' => ! empty($matchedFile['code']),
                        'file_name' => $matchedFile['name'] ?? null,
                    ];
                }
            }

            $currentStudentScore = $existingScores->get($student->id);

            $studentEssayData[$student->id] = [
                'has_submission' => $hasSubmission,
                'submitted_at' => $submittedAt,
                'is_late' => $isLate,
                'due_at' => $dueAt?->translatedFormat('d M Y, H:i'),
                'is_tipe_soal' => $isTipeSoal,
                'is_assignment' => $isAssignment,
                'is_coding' => $isCoding,
                'questions' => $processedQuestions,
                'essays' => $essays,
                'coding_steps' => $codingStepsData,
                'editor_url' => route('course.assignment.code', [$section->id, $assessment->id]) . '?student=' . $student->id,
                'score_url' => route('dosen.penilaian.asesmen.student.score', [$section->id, $assessment->id, $student->id]),
                'coding_score_url' => route('dosen.penilaian.asesmen.student.coding_scores', [$section->id, $assessment->id, $student->id]),
                'essay_score_url' => route('dosen.penilaian.asesmen.student.essay_scores', [$section->id, $assessment->id, $student->id]),
                'answer_text' => $sub?->answer ?? $sub?->answers?->first()?->answer_text,
                'link' => $sub?->link,
                'files' => $attachedFiles,
                'has_cpmks' => $cpmks->isNotEmpty(),
                'cpmk_list' => $cpmkList,
                'single_score' => $currentStudentScore?->score !== null ? (float) $currentStudentScore->score : '',
            ];
        }

        return view('dosen.penilaian.input-nilai', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'students' => $students,
            'cpmks' => $cpmks,
            'existingCpmkScores' => $existingCpmkScores,
            'existingScores' => $existingScores,
            'gradedCount' => $gradedCount,
            'hasEssay' => $hasEssay,
            'hasEssayQuestions' => $hasEssayQuestions,
            'isTipeSoal' => $isTipeSoal,
            'isAssignment' => $isAssignment,
            'studentEssayData' => $studentEssayData,
            'obe' => $this->obe,
        ]);
    }

    public function storeEssayScore(
        Request $request,
        ClassSection $section,
        Assessment $assessment,
        SubmissionAnswer $answer
    ): RedirectResponse {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $answer->loadMissing('submission');
        abort_unless(
            $answer->submission
            && (int) $answer->submission->assessment_id === (int) $assessment->id
            && $section->students()->where('users.id', $answer->submission->mahasiswa_id ?: $answer->submission->user_id)->exists(),
            404
        );

        $payloadQuestions = QuizQuestion::canonicalizeQuestions($assessment->learning_payload['questions'] ?? []);
        $question = collect($payloadQuestions)->first(fn (array $item, int $index) =>
            ($answer->question_id !== null && (string) $item['id'] === (string) $answer->question_id)
            || ($answer->question_index !== null && $index === (int) $answer->question_index)
        );
        abort_unless($question && in_array($question['type'] ?? '', ['uraian', 'esai', 'essay'], true), 422);

        $maxScore = (float) ($question['points'] ?? $answer->max_score ?? 0);
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$maxScore],
        ], [
            'score.max' => 'Skor esai tidak boleh melebihi '.$maxScore.' poin.',
        ]);

        $answer->forceFill(['max_score' => $maxScore])->save();
        $this->quizGrades->gradeEssay($assessment, $answer, (float) $validated['score'], $request->user());

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section, $assessment])
            ->with('notice', 'Skor esai tersimpan dan nilai kuis telah dihitung ulang bersama skor otomatis.');
    }

    /**
     * Simpan seluruh nilai esai mahasiswa sekaligus dari modal tinjau jawaban.
     */
    public function storeStudentEssayScores(
        Request $request,
        ClassSection $section,
        Assessment $assessment,
        \App\Models\User $student
    ): RedirectResponse {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        abort_unless(
            $section->students()->where('users.id', $student->id)->exists(),
            404,
            'Mahasiswa tidak terdaftar pada kelas ini.'
        );

        $submission = Submission::where('assessment_id', $assessment->id)
            ->where(function ($query) use ($student) {
                $query->where('mahasiswa_id', $student->id)
                    ->orWhere('user_id', $student->id);
            })
            ->first();

        abort_unless($submission, 404, 'Lembar jawaban mahasiswa tidak ditemukan.');

        $payloadQuestions = QuizQuestion::canonicalizeQuestions($assessment->learning_payload['questions'] ?? []);
        $answers = $submission->answers()
            ->where('version', $submission->version)
            ->get();

        $scores = $request->input('scores', []);
        abort_unless(is_array($scores), 422, 'Format data skor tidak valid.');

        $lecturer = $request->user() ?? Auth::guard('web')->user();
        $dosenId = $lecturer?->id ?? Auth::guard('web')->id();

        $errors = [];
        foreach ($payloadQuestions as $index => $q) {
            $type = $q['type'] ?? 'pilihan';
            if (! in_array($type, ['uraian', 'esai', 'essay'], true)) {
                continue;
            }

            $qId = (string) ($q['id'] ?? $index);
            $maxScore = (float) ($q['points'] ?? 0);
            $qNumber = $index + 1;

            $answer = $answers->first(fn ($a) =>
                ($a->question_id !== null && (string) $a->question_id === $qId)
                || ($a->question_index !== null && (int) $a->question_index === (int) $index)
            );

            $val = null;
            if ($answer && array_key_exists($answer->id, $scores)) {
                $val = $scores[$answer->id];
            } elseif (array_key_exists($qId, $scores)) {
                $val = $scores[$qId];
            } elseif (array_key_exists($index, $scores)) {
                $val = $scores[$index];
            }

            if ($val === null || $val === '') {
                continue;
            }

            if (! is_numeric($val) || (float) $val < 0) {
                $errors[] = "Skor esai untuk Soal {$qNumber} harus berupa angka tidak negatif.";
            } elseif ((float) $val > $maxScore) {
                $errors[] = "Skor esai untuk Soal {$qNumber} tidak boleh melebihi {$maxScore} poin.";
            }
        }

        if (! empty($errors)) {
            return redirect()
                ->route('dosen.penilaian.asesmen.nilai', [$section, $assessment])
                ->withErrors(['scores' => $errors])
                ->withInput();
        }

        DB::transaction(function () use ($payloadQuestions, $answers, $scores, $dosenId, $assessment, $submission) {
            foreach ($payloadQuestions as $index => $q) {
                $type = $q['type'] ?? 'pilihan';
                if (! in_array($type, ['uraian', 'esai', 'essay'], true)) {
                    continue;
                }

                $qId = (string) ($q['id'] ?? $index);
                $maxScore = (float) ($q['points'] ?? 0);

                $answer = $answers->first(fn ($a) =>
                    ($a->question_id !== null && (string) $a->question_id === $qId)
                    || ($a->question_index !== null && (int) $a->question_index === (int) $index)
                );

                if (! $answer && Schema::hasTable('submission_answers')) {
                    $answer = SubmissionAnswer::firstOrCreate([
                        'submission_id' => $submission->id,
                        'question_id' => $qId,
                        'version' => $submission->version ?? 1,
                    ], [
                        'max_score' => $maxScore,
                        'grading_status' => 'manual_pending',
                    ]);
                }

                if (! $answer) {
                    continue;
                }

                $hasVal = false;
                $val = null;
                if (array_key_exists($answer->id, $scores)) {
                    $val = $scores[$answer->id];
                    $hasVal = true;
                } elseif (array_key_exists($qId, $scores)) {
                    $val = $scores[$qId];
                    $hasVal = true;
                } elseif (array_key_exists($index, $scores)) {
                    $val = $scores[$index];
                    $hasVal = true;
                }

                if (! $hasVal) {
                    continue;
                }

                if ($val !== null && $val !== '') {
                    $answer->forceFill([
                        'max_score' => $maxScore,
                        'earned_score' => (float) $val,
                        'grading_status' => 'manual_graded',
                        'graded_by_id' => $dosenId,
                        'graded_at' => now(),
                    ])->save();
                } else {
                    $answer->forceFill([
                        'max_score' => $maxScore,
                        'earned_score' => null,
                        'grading_status' => 'manual_pending',
                        'graded_by_id' => null,
                        'graded_at' => null,
                    ])->save();
                }
            }

            $this->quizGrades->recalculate($assessment, $submission, $dosenId);
        });

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section, $assessment])
            ->with('notice', "Nilai esai mahasiswa {$student->name} berhasil disimpan dan nilai kuis telah dihitung ulang.");
    }

    /**
     * Simpan nilai tugas mahasiswa dari modal tinjau jawaban.
     */
    public function storeStudentTaskScore(
        Request $request,
        ClassSection $section,
        Assessment $assessment,
        \App\Models\User $student
    ): RedirectResponse {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        abort_unless(
            $section->students()->where('users.id', $student->id)->exists(),
            404,
            'Mahasiswa tidak terdaftar pada kelas ini.'
        );

        $dosenId = Auth::guard('web')->id();
        $cpmks = $assessment->cpmks()->orderBy('code')->get();

        if ($cpmks->isNotEmpty()) {
            $rules = ['cpmk_scores' => ['required', 'array']];
            $messages = [];
            foreach ($cpmks as $cpmk) {
                $maxScore = round($this->obe->assessmentCpmkMaxScore($assessment, $cpmk), 1);
                $rules["cpmk_scores.{$cpmk->id}"] = ['nullable', 'numeric', 'min:0', 'max:'.$maxScore];
                $messages["cpmk_scores.{$cpmk->id}.max"] = "Nilai {$cpmk->code} tidak boleh melebihi {$maxScore}.";
                $messages["cpmk_scores.{$cpmk->id}.min"] = "Nilai {$cpmk->code} tidak boleh kurang dari 0.";
            }

            $validated = $request->validate($rules, $messages);
            $scores = $validated['cpmk_scores'] ?? [];

            $this->obe->syncCpmkScores($assessment, $student->id, $scores, $dosenId, true);
        } else {
            $validated = $request->validate([
                'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ], [
                'score.max' => 'Nilai tidak boleh melebihi 100.',
                'score.min' => 'Nilai tidak boleh kurang dari 0.',
            ]);

            $scoreVal = ($validated['score'] !== null && $validated['score'] !== '')
                ? (float) $validated['score']
                : null;

            $this->obe->syncDirectScore($assessment, $student->id, $scoreVal, $dosenId, true);
        }

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', "Nilai tugas mahasiswa {$student->name} berhasil disimpan.");
    }

    /**
     * Simpan nilai tugas coding per butir soal dari modal tinjau jawaban.
     */
    public function storeStudentCodingScores(
        Request $request,
        ClassSection $section,
        Assessment $assessment,
        \App\Models\User $student
    ): RedirectResponse {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        abort_unless(
            $section->students()->where('users.id', $student->id)->exists(),
            404,
            'Mahasiswa tidak terdaftar pada kelas ini.'
        );

        $dosenId = Auth::guard('web')->id();
        $payload = $assessment->learning_payload ?? [];
        $codingSteps = $payload['coding_steps'] ?? [];
        if (empty($codingSteps)) {
            $codingSteps = [[
                'title' => $assessment->name,
                'cpmk' => $payload['cpmk'] ?? ($assessment->cpmks()->first()?->code ?? 'CPMK-01'),
                'points' => 100,
            ]];
        }

        $submission = Submission::firstOrCreate([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
        ], [
            'mahasiswa_id' => $student->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'pending',
        ]);

        $scores = $request->input('scores', []);
        $totalStepsCount = max(1, count($codingSteps));

        DB::transaction(function () use ($codingSteps, $totalStepsCount, $submission, $scores, $dosenId, $assessment, $student) {
            $totalEarned = 0.0;
            $hasAnyScore = false;
            $cpmkEarned = [];
            $cpmkMax = [];

            foreach ($codingSteps as $idx => $step) {
                $stepNum = $idx + 1;
                $qId = (string) $stepNum;
                $maxScore = (float) ((isset($step['points']) && (float) $step['points'] > 0) ? $step['points'] : round(100 / $totalStepsCount, 1));
                $cpmkCode = (string) ($step['cpmk'] ?? '');

                $answer = SubmissionAnswer::where('submission_id', $submission->id)
                    ->where(fn ($q) => $q->where('question_id', $qId)->orWhere('question_index', $idx))
                    ->first();

                if (! $answer) {
                    $answer = SubmissionAnswer::create([
                        'submission_id' => $submission->id,
                        'question_index' => $idx,
                        'question_id' => $qId,
                        'version' => $submission->version ?? 1,
                        'max_score' => $maxScore,
                        'grading_status' => 'manual_pending',
                    ]);
                }

                $val = null;
                if (array_key_exists($answer->id, $scores)) {
                    $val = $scores[$answer->id];
                } elseif (array_key_exists($qId, $scores)) {
                    $val = $scores[$qId];
                } elseif (array_key_exists($idx, $scores)) {
                    $val = $scores[$idx];
                }

                if ($val !== null && $val !== '') {
                    $earned = min($maxScore, max(0.0, (float) $val));
                    $answer->forceFill([
                        'max_score' => $maxScore,
                        'earned_score' => $earned,
                        'grading_status' => 'manual_graded',
                        'graded_by_id' => $dosenId,
                        'graded_at' => now(),
                    ])->save();

                    $totalEarned += $earned;
                    $hasAnyScore = true;

                    if ($cpmkCode) {
                        $normCode = preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($cpmkCode)));
                        $cpmkEarned[$normCode] = ($cpmkEarned[$normCode] ?? 0.0) + $earned;
                        $cpmkMax[$normCode] = ($cpmkMax[$normCode] ?? 0.0) + $maxScore;
                    }
                } else {
                    $answer->forceFill([
                        'max_score' => $maxScore,
                        'earned_score' => null,
                        'grading_status' => 'manual_pending',
                        'graded_by_id' => null,
                        'graded_at' => null,
                    ])->save();
                }
            }

            $assessment->loadMissing('cpmks');
            $cpmks = $assessment->cpmks;

            if ($hasAnyScore) {
                if ($cpmks->isNotEmpty()) {
                    $cpmkScoresToSync = [];
                    foreach ($cpmks as $cpmk) {
                        $normC = preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($cpmk->code)));
                        $maxCpmkScore = $this->obe->assessmentCpmkMaxScore($assessment, $cpmk);

                        if (isset($cpmkEarned[$normC]) && isset($cpmkMax[$normC]) && $cpmkMax[$normC] > 0) {
                            $ratio = $cpmkEarned[$normC] / $cpmkMax[$normC];
                            $cpmkScoresToSync[$cpmk->id] = round($ratio * $maxCpmkScore, 2);
                        } else {
                            $totalMax = array_sum($cpmkMax);
                            if ($totalMax > 0) {
                                $ratio = $totalEarned / $totalMax;
                                $cpmkScoresToSync[$cpmk->id] = round($ratio * $maxCpmkScore, 2);
                            } else {
                                $cpmkScoresToSync[$cpmk->id] = null;
                            }
                        }
                    }

                    $this->obe->syncCpmkScores($assessment, $student->id, $cpmkScoresToSync, $dosenId, true);
                } else {
                    $totalMax = array_sum($cpmkMax);
                    $finalScore = $totalMax > 0 ? round(($totalEarned / $totalMax) * 100, 2) : round($totalEarned, 2);
                    $this->obe->syncDirectScore($assessment, $student->id, $finalScore, $dosenId, true);
                }
            } else {
                $this->obe->syncDirectScore($assessment, $student->id, null, $dosenId, false);
            }
        });

        if ($request->input('return_to') === 'editor' || $request->input('action') === 'save') {
            return redirect()
                ->route('course.assignment.code', [$section->id, $assessment->id, 'student' => $student->id])
                ->with('notice', "Nilai tugas coding mahasiswa {$student->name} berhasil disimpan.");
        }

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', "Nilai tugas coding mahasiswa {$student->name} berhasil disimpan.");
    }

    /**
     * Simpan nilai dari form input — jika asesmen memiliki CPMK,
     * simpan per-CPMK dan hitung nilai total asesmen secara otomatis.
     */
    public function store(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $cpmks = $assessment->cpmks()->orderBy('code')->get();
        $enrolledIds = $section->students()->pluck('users.id');
        $dosenId = Auth::guard('web')->id();
        $publish = $request->input('intent') === 'publish';

        if ($request->has('rubric_scores') && $assessment->uses_rubric && $assessment->rubric) {
            $request->validate([
                'rubric_scores' => ['required', 'array'],
                'rubric_scores.*' => ['array'],
                'rubric_scores.*.*' => ['nullable', 'numeric', 'min:0'],
            ]);

            DB::transaction(function () use ($request, $assessment, $enrolledIds, $dosenId, $publish) {
                foreach ($request->input('rubric_scores', []) as $mahasiswaId => $criterionScores) {
                    $mahasiswaId = (int) $mahasiswaId;
                    if (! $enrolledIds->contains($mahasiswaId)) {
                        continue;
                    }

                    $this->obe->syncRubricScores($assessment, $mahasiswaId, $criterionScores, $dosenId, $publish);
                }
            });
        } elseif ($request->has('scores') || $cpmks->isEmpty()) {
            // Asesmen dengan input nilai langsung (direct score) atau tanpa CPMK
            $request->validate([
                'scores' => ['required', 'array'],
                'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ]);

            DB::transaction(function () use ($request, $assessment, $enrolledIds, $dosenId, $publish) {
                foreach ($request->input('scores', []) as $mahasiswaId => $score) {
                    $mahasiswaId = (int) $mahasiswaId;
                    if (! $enrolledIds->contains($mahasiswaId)) {
                        continue;
                    }

                    $scoreValue = ($score !== null && $score !== '') ? (float) $score : null;

                    $this->obe->syncDirectScore($assessment, $mahasiswaId, $scoreValue, $dosenId, $publish);
                }
            });
        } else {
            $rules = [
                'cpmk_scores' => ['required', 'array'],
                'cpmk_scores.*' => ['array'],
            ];
            $messages = [];

            foreach ($cpmks as $cpmk) {
                $maxScore = round($this->obe->assessmentCpmkMaxScore($assessment, $cpmk), 1);
                $maxScoreFormatted = rtrim(rtrim(number_format($maxScore, 1), '0'), '.');
                $rules["cpmk_scores.*.{$cpmk->id}"] = ['nullable', 'numeric', 'min:0', 'max:'.$maxScore];
                $messages["cpmk_scores.*.{$cpmk->id}.max"] = "Nilai {$cpmk->code} tidak boleh melebihi batas maksimal {$maxScoreFormatted}.";
                $messages["cpmk_scores.*.{$cpmk->id}.min"] = "Nilai {$cpmk->code} tidak boleh kurang dari 0.";
            }

            $request->validate($rules, $messages);

            DB::transaction(function () use ($request, $assessment, $enrolledIds, $dosenId, $publish) {
                foreach ($request->input('cpmk_scores', []) as $mahasiswaId => $cpmkValues) {
                    $mahasiswaId = (int) $mahasiswaId;
                    if (! $enrolledIds->contains($mahasiswaId)) {
                        continue;
                    }

                    $this->obe->syncCpmkScores($assessment, $mahasiswaId, $cpmkValues, $dosenId, $publish);
                }
            });
        }

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', 'Nilai berhasil disimpan.');
    }

    /**
     * Download template Excel (.xlsx) for bulk import (tanpa feedback, per CPMK jika ada).
     */
    public function downloadTemplate(ClassSection $section, Assessment $assessment): StreamedResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $students = $section->students()->orderBy('name')->get();
        $cpmks = $assessment->cpmks()->orderBy('code')->get();

        $filename = 'template_nilai_'.$assessment->code.'.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Build header row
        if ($cpmks->isNotEmpty()) {
            $header = ['NIM', 'Nama'];
            foreach ($cpmks as $cpmk) {
                $header[] = $cpmk->code;
            }
        } else {
            $header = ['NIM', 'Nama', 'Nilai'];
        }

        $sheet->fromArray([$header], null, 'A1');

        // Data rows
        $rowIdx = 2;
        if ($cpmks->isNotEmpty()) {
            foreach ($students as $student) {
                $row = [$this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];
                foreach ($cpmks as $cpmk) {
                    $row[] = '';
                }
                $sheet->fromArray([$row], null, 'A'.$rowIdx);
                $rowIdx++;
            }
        } else {
            foreach ($students as $student) {
                $sheet->fromArray([[$this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name), '']], null, 'A'.$rowIdx);
                $rowIdx++;
            }
        }

        // Header styling: background #4472C4, white bold Calibri 11
        $colCount = count($header);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $headerRange = 'A1:'.$lastCol.'1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Calibri',
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-width columns
        for ($i = 1; $i <= $colCount; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Halaman import nilai — upload CSV/Excel.
     */
    public function import(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        return view('dosen.penilaian.import-nilai', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'preview' => session('import_preview'),
        ]);
    }

    /**
     * Process CSV upload: parse, validate, preview, then save on confirm.
     *
     * Two-phase flow:
     * - Phase 1 (no 'confirm'): parse CSV, validate, store preview in session
     * - Phase 2 ('confirm' = true): save previewed data to DB
     */
    public function processImport(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        // Phase 2: confirm and save
        if ($request->boolean('confirm')) {
            return $this->confirmImport($section, $assessment);
        }

        // Phase 1: parse and preview
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Pilih file untuk diimport.',
            'file.mimes' => 'Format file harus Excel (.xlsx/.xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        // Excel parsing (xlsx/xls)
        if (in_array($ext, ['xlsx', 'xls'], true)) {
            try {
                $spreadsheet = IOFactory::load($file->getPathname());
            } catch (\Throwable $e) {
                return back()->withErrors(['file' => 'Gagal membaca file Excel: '.$e->getMessage()]);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $sheetRows = $sheet->toArray(null, true, true, false);
            // Remove empty rows
            $sheetRows = array_values(array_filter($sheetRows, function ($r) {
                return is_array($r) && count(array_filter(array_map('strval', $r), fn ($v) => trim($v) !== '')) > 0;
            }));

            if (count($sheetRows) < 2) {
                return back()->withErrors(['file' => 'File Excel kosong atau hanya berisi header.']);
            }

            $rawHeaderCols = array_map(fn ($v) => trim((string) $v), $sheetRows[0]);

            if (count($rawHeaderCols) < 3) {
                return back()->withErrors(['file' => 'Format header tidak valid (minimal 3 kolom: NIM, Nama, Nilai/CPMK).']);
            }

            $assessmentCpmks = $assessment->cpmks()->orderBy('code')->get();
            $cpmkByCode = $assessmentCpmks->keyBy(fn ($c) => strtoupper(trim($c->code)));

            $colsToCheck = array_slice($rawHeaderCols, 2);
            $firstColName = strtolower(trim($colsToCheck[0] ?? ''));

            $mode = 'legacy';
            $cpmkMapping = [];

            if ($assessmentCpmks->isNotEmpty() && $firstColName !== 'nilai' && $firstColName !== 'score' && ! str_starts_with($firstColName, 'nilai')) {
                $mode = 'cpmk';
                foreach ($colsToCheck as $idxOffset => $headerColName) {
                    $colIdx = 2 + $idxOffset;
                    $code = strtoupper(trim($headerColName));
                    if ($code === '') {
                        continue;
                    }
                    if (! $cpmkByCode->has($code)) {
                        return back()->withErrors(['file' => "Kolom header '$headerColName' bukan CPMK yang diukur oleh asesmen ini."]);
                    }
                    $cpmk = $cpmkByCode->get($code);
                    $maxScore = $this->obe->assessmentCpmkMaxScore($assessment, $cpmk);
                    $cpmkMapping[$colIdx] = ['cpmk_id' => $cpmk->id, 'code' => $cpmk->code, 'max' => $maxScore];
                }
                if (empty($cpmkMapping)) {
                    return back()->withErrors(['file' => 'Tidak ditemukan kolom CPMK yang valid pada header.']);
                }
            }

            $enrolledStudents = $section->students()->orderBy('name')->get();
            $studentsByNim = $enrolledStudents->keyBy('nim_nidn');
            $rows = [];
            $errors = [];

            for ($i = 1; $i < count($sheetRows); $i++) {
                $cols = array_map(fn ($v) => trim((string) $v), $sheetRows[$i]);
                $nim = $cols[0] ?? '';
                $student = $studentsByNim[$nim] ?? null;
                if (! $student) {
                    $errors[] = 'Baris '.($i + 1).": NIM '$nim' tidak ditemukan di kelas ini.";
                    continue;
                }

                if ($mode === 'cpmk') {
                    $cpmkScores = [];
                    $sumOfPoints = 0.0;
                    $hasAnyScore = false;
                    $rowHasError = false;
                    foreach ($cpmkMapping as $colIndex => $mapping) {
                        $rawVal = $cols[$colIndex] ?? '';
                        $cpmkId = $mapping['cpmk_id'];
                        $cpmkCode = $mapping['code'];
                        $maxScore = $mapping['max'];
                        if ($rawVal === '') {
                            $cpmkScores[$cpmkId] = null;
                            continue;
                        }
                        if (! is_numeric($rawVal)) {
                            $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} ('$rawVal') tidak valid.";
                            $rowHasError = true;
                            continue;
                        }
                        $numVal = (float) $rawVal;
                        if ($numVal < 0) {
                            $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} tidak boleh kurang dari 0.";
                            $rowHasError = true;
                            continue;
                        }
                        if ($numVal > $maxScore) {
                            $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} ($numVal) melebihi batas maksimal ".(int) $maxScore.'.';
                            $rowHasError = true;
                            continue;
                        }
                        $cpmkScores[$cpmkId] = $numVal;
                        $sumOfPoints += $numVal;
                        $hasAnyScore = true;
                    }
                    if ($rowHasError) {
                        continue;
                    }
                    $overallScore = $hasAnyScore ? min(100.0, round($sumOfPoints, 2)) : null;
                    $rows[] = [
                        'mahasiswa_id' => $student->id,
                        'nim' => $nim,
                        'name' => $student->name,
                        'cpmk_scores' => $cpmkScores,
                        'overall_score' => $overallScore,
                        'status' => $hasAnyScore ? 'valid' : 'kosong',
                    ];
                } else {
                    $score = $cols[2] ?? '';
                    $feedback = $cols[3] ?? '';
                    if ($score !== '' && (! is_numeric($score) || (float) $score < 0 || (float) $score > 100)) {
                        $errors[] = 'Baris '.($i + 1).": Nilai '$score' tidak valid (harus 0-100).";
                        continue;
                    }
                    $scoreVal = $score !== '' ? (float) $score : null;
                    $rows[] = [
                        'mahasiswa_id' => $student->id,
                        'nim' => $nim,
                        'name' => $student->name,
                        'score' => $scoreVal,
                        'feedback' => $feedback,
                        'status' => $scoreVal !== null ? 'valid' : 'kosong',
                    ];
                }
            }

            if (empty($rows) && ! empty($errors)) {
                return back()->withErrors(['file' => 'Semua baris mengandung error.'])->with('import_errors', $errors);
            }

            session([
                'import_preview' => [
                    'mode' => $mode,
                    'cpmk_headers' => array_values($cpmkMapping),
                    'rows' => $rows,
                    'errors' => $errors,
                    'assessment_id' => $assessment->id,
                    'section_id' => $section->id,
                ],
            ]);

            return redirect()
                ->route('dosen.penilaian.asesmen.nilai.import', [$section->id, $assessment->id])
                ->with('import_preview_ready', true);
        }

        // CSV / TXT parsing (fallback)
        $content = file_get_contents($file->getRealPath());
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        // Filter non-empty lines
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== '' && ! str_starts_with(trim($l), '#')));

        if (count($lines) < 2) {
            return back()->withErrors(['file' => 'File CSV kosong atau hanya berisi header.']);
        }

        // Parse header row
        $headerLine = trim($lines[0]);
        $delimiter = str_contains($headerLine, ';') ? ';' : (str_contains($headerLine, "\t") ? "\t" : ',');
        $rawHeaderCols = array_map('trim', str_getcsv($headerLine, $delimiter, '"', '\\'));

        if (count($rawHeaderCols) < 3) {
            return back()->withErrors(['file' => 'Format header CSV tidak valid (minimal 3 kolom: NIM, Nama, Nilai/CPMK).']);
        }

        // Assessment CPMKs
        $assessmentCpmks = $assessment->cpmks()->orderBy('code')->get();
        $cpmkByCode = $assessmentCpmks->keyBy(fn ($c) => strtoupper(trim($c->code)));

        $colsToCheck = array_slice($rawHeaderCols, 2);
        $firstColName = strtolower(trim($colsToCheck[0] ?? ''));

        $mode = 'legacy';
        $cpmkMapping = [];

        // Deteksi mode: Multi-CPMK vs Legacy
        if ($assessmentCpmks->isNotEmpty() && $firstColName !== 'nilai' && $firstColName !== 'score' && ! str_starts_with($firstColName, 'nilai')) {
            $mode = 'cpmk';

            foreach ($colsToCheck as $idxOffset => $headerColName) {
                $colIdx = 2 + $idxOffset;
                $code = strtoupper(trim($headerColName));
                if ($code === '') {
                    continue;
                }

                if (! $cpmkByCode->has($code)) {
                    return back()->withErrors([
                        'file' => "Kolom header '$headerColName' bukan CPMK yang diukur oleh asesmen ini.",
                    ]);
                }

                $cpmk = $cpmkByCode->get($code);
                $maxScore = $this->obe->assessmentCpmkMaxScore($assessment, $cpmk);

                $cpmkMapping[$colIdx] = [
                    'cpmk_id' => $cpmk->id,
                    'code' => $cpmk->code,
                    'max' => $maxScore,
                ];
            }

            if (empty($cpmkMapping)) {
                return back()->withErrors([
                    'file' => 'Tidak ditemukan kolom CPMK yang valid pada header CSV.',
                ]);
            }
        }

        // Get enrolled students indexed by NIM
        $enrolledStudents = $section->students()->orderBy('name')->get();
        $studentsByNim = $enrolledStudents->keyBy('nim_nidn');

        $rows = [];
        $errors = [];

        // Parse data rows
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
            $nim = $cols[0] ?? '';
            $name = $cols[1] ?? '';

            $student = $studentsByNim[$nim] ?? null;

            if (! $student) {
                $errors[] = 'Baris '.($i + 1).": NIM '$nim' tidak ditemukan di kelas ini.";

                continue;
            }

            if ($mode === 'cpmk') {
                $cpmkScores = [];
                $sumOfPoints = 0.0;
                $hasAnyScore = false;
                $rowHasError = false;

                foreach ($cpmkMapping as $colIndex => $mapping) {
                    $rawVal = $cols[$colIndex] ?? '';
                    $cpmkId = $mapping['cpmk_id'];
                    $cpmkCode = $mapping['code'];
                    $maxScore = $mapping['max'];

                    if ($rawVal === '' || $rawVal === null) {
                        $cpmkScores[$cpmkId] = null;

                        continue;
                    }

                    if (! is_numeric($rawVal)) {
                        $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} ('$rawVal') tidak valid (harus berupa angka).";
                        $rowHasError = true;

                        continue;
                    }

                    $numVal = (float) $rawVal;

                    if ($numVal < 0) {
                        $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} tidak boleh kurang dari 0.";
                        $rowHasError = true;

                        continue;
                    }

                    if ($numVal > $maxScore) {
                        $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} ($numVal) melebihi batas maksimal ".(int) $maxScore.'.';
                        $rowHasError = true;

                        continue;
                    }

                    $cpmkScores[$cpmkId] = $numVal;
                    $sumOfPoints += $numVal;
                    $hasAnyScore = true;
                }

                if ($rowHasError) {
                    continue;
                }

                $overallScore = $hasAnyScore ? min(100.0, round($sumOfPoints, 2)) : null;

                $rows[] = [
                    'mahasiswa_id' => $student->id,
                    'nim' => $nim,
                    'name' => $student->name,
                    'cpmk_scores' => $cpmkScores,
                    'overall_score' => $overallScore,
                    'status' => $hasAnyScore ? 'valid' : 'kosong',
                ];
            } else {
                $score = $cols[2] ?? '';
                $feedback = $cols[3] ?? '';

                if ($score !== '' && (! is_numeric($score) || (float) $score < 0 || (float) $score > 100)) {
                    $errors[] = 'Baris '.($i + 1).": Nilai '$score' tidak valid (harus 0-100).";

                    continue;
                }

                $scoreVal = $score !== '' ? (float) $score : null;

                $rows[] = [
                    'mahasiswa_id' => $student->id,
                    'nim' => $nim,
                    'name' => $student->name,
                    'score' => $scoreVal,
                    'feedback' => $feedback,
                    'status' => $scoreVal !== null ? 'valid' : 'kosong',
                ];
            }
        }

        if (empty($rows) && ! empty($errors)) {
            return back()->withErrors(['file' => 'Semua baris mengandung error.'])->with('import_errors', $errors);
        }

        // Store preview in session for confirmation
        session([
            'import_preview' => [
                'mode' => $mode,
                'cpmk_headers' => array_values($cpmkMapping),
                'rows' => $rows,
                'errors' => $errors,
                'assessment_id' => $assessment->id,
                'section_id' => $section->id,
            ],
        ]);

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai.import', [$section->id, $assessment->id])
            ->with('import_preview_ready', true);
    }

    /**
     * Confirm import — saves previewed data to DB.
     */
    private function confirmImport(ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $preview = session('import_preview');

        if (! $preview || $preview['assessment_id'] !== $assessment->id || $preview['section_id'] !== $section->id) {
            return back()->withErrors(['file' => 'Data preview tidak ditemukan. Silakan upload ulang.']);
        }

        $dosenId = Auth::guard('web')->id();
        $mode = $preview['mode'] ?? 'legacy';
        $saved = 0;

        DB::transaction(function () use ($preview, $assessment, $dosenId, $mode, &$saved) {
            foreach ($preview['rows'] as $row) {
                if ($mode === 'cpmk') {
                    $this->obe->syncCpmkScores($assessment, $row['mahasiswa_id'], $row['cpmk_scores'], $dosenId);

                    if ($row['overall_score'] !== null) {
                        $saved++;
                    }
                } else {
                    $this->obe->syncDirectScore(
                        $assessment,
                        $row['mahasiswa_id'],
                        $row['score'],
                        $dosenId,
                        false,
                        $row['feedback'] ?: null
                    );

                    if ($row['score'] !== null) {
                        $saved++;
                    }
                }
            }
        });

        session()->forget('import_preview');

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', "Import berhasil: $saved nilai disimpan.");
    }

    // ── Helpers ──

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount(['assessments' => fn ($q) => $q->whereNotIn('type', ['materi', 'pengumuman', 'lainnya'])])->load(['mataKuliah', 'semester', 'dosen']);

        return $section;
    }

    private function authorizeOwnership(ClassSection $section): void
    {
        Gate::authorize('manage', $section);
    }

    private function authorizeAssessmentBelongsToSection(ClassSection $section, Assessment $assessment): void
    {
        abort_unless($assessment->class_section_id === $section->id, 404);
    }

    private function sanitizeCsv(mixed $value): string
    {
        $string = (string) $value;
        if ($string !== '' && in_array($string[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$string;
        }

        return $string;
    }
}
