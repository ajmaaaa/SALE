<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use App\Support\QuizQuestion;
use Illuminate\Support\Collection;

class QuizGradingService
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Simpan hasil penilaian otomatis per butir. Nilai esai sengaja dibiarkan
     * kosong sampai dosen menilainya agar skor pilihan objektif tidak hilang.
     */
    /** @return array{complete: bool, score: ?float, automatic_score: float, essay_score: float} */
    public function recordAutomaticScores(Assessment $assessment, Submission $submission, array $questions, array $answers): array
    {
        foreach (QuizQuestion::canonicalizeQuestions($questions) as $index => $question) {
            $questionId = (string) $question['id'];
            $isEssay = in_array($question['type'] ?? '', ['uraian', 'esai', 'essay'], true);
            $answer = $answers[$questionId] ?? $answers[$index] ?? [];
            $row = $this->answerRow($submission, $questionId, $index);

            if (! $row) {
                continue;
            }

            $row->forceFill([
                'max_score' => (float) ($question['points'] ?? 0),
                'earned_score' => $isEssay ? null : (QuizQuestion::evaluate($question, $answer) ?? 0.0),
                'grading_status' => $isEssay ? 'manual_pending' : 'automatic',
                'graded_by_id' => null,
                'graded_at' => $isEssay ? null : now(),
            ])->save();
        }

        return $this->recalculate($assessment, $submission);
    }

    public function gradeEssay(Assessment $assessment, SubmissionAnswer $answer, float $score, User $lecturer): void
    {
        $answer->forceFill([
            'earned_score' => $score,
            'grading_status' => 'manual_graded',
            'graded_by_id' => $lecturer->id,
            'graded_at' => now(),
        ])->save();

        $this->recalculate($assessment, $answer->submission, $lecturer->id);
    }

    /**
     * Pulihkan skor otomatis submission lama tanpa menyentuh nilai akhir yang
     * telah diterbitkan dan tanpa menebak skor esai.
     *
     * @return array{submissions: int, automatic_answers: int, pending_essays: int}
     */
    public function backfillAutomaticScores(): array
    {
        $summary = ['submissions' => 0, 'automatic_answers' => 0, 'pending_essays' => 0];

        Submission::query()
            ->with(['assessment', 'answers'])
            ->chunkById(100, function ($submissions) use (&$summary) {
                foreach ($submissions as $submission) {
                    $questions = QuizQuestion::canonicalizeQuestions($submission->assessment?->learning_payload['questions'] ?? []);
                    if ($questions === []) {
                        continue;
                    }

                    $summary['submissions']++;
                    $storedAnswers = $submission->question_answers ?? [];
                    foreach ($questions as $index => $question) {
                        $questionId = (string) $question['id'];
                        $row = $this->rowFromCollection($submission->answers, $questionId, $index);
                        if (! $row || (int) $row->version !== (int) $submission->version) {
                            continue;
                        }

                        $isEssay = in_array($question['type'] ?? '', ['uraian', 'esai', 'essay'], true);
                        if ($isEssay) {
                            if ($row->earned_score === null) {
                                $row->forceFill([
                                    'max_score' => (float) ($question['points'] ?? 0),
                                    'grading_status' => 'manual_pending',
                                ])->save();
                                $summary['pending_essays']++;
                            }

                            continue;
                        }

                        $answer = $storedAnswers[$questionId] ?? $storedAnswers[$index] ?? [];
                        $row->forceFill([
                            'max_score' => (float) ($question['points'] ?? 0),
                            'earned_score' => QuizQuestion::evaluate($question, $answer) ?? 0.0,
                            'grading_status' => 'automatic',
                            'graded_at' => $row->graded_at ?? now(),
                        ])->save();
                        $summary['automatic_answers']++;
                    }
                }
            });

        return $summary;
    }

    /** @return array{complete: bool, score: ?float, automatic_score: float, essay_score: float} */
    public function recalculate(Assessment $assessment, Submission $submission, ?int $gradedById = null): array
    {
        $questions = QuizQuestion::canonicalizeQuestions($assessment->learning_payload['questions'] ?? []);
        $rows = $submission->answers()
            ->where('version', $submission->version)
            ->get();

        $groupCounts = array_count_values(array_filter(array_map(
            fn (array $question) => (string) ($question['cpmk'] ?? ''),
            $questions
        )));
        $totalQuestions = max(1, count($questions));
        $contributions = [];
        $automaticScore = 0.0;
        $essayScore = 0.0;
        $complete = true;

        foreach ($questions as $index => $question) {
            $questionId = (string) $question['id'];
            $row = $this->rowFromCollection($rows, $questionId, $index);
            $isEssay = in_array($question['type'] ?? '', ['uraian', 'esai', 'essay'], true);
            $earned = $row?->earned_score;

            if ($isEssay && $earned === null) {
                $complete = false;
            }

            $earned = (float) ($earned ?? 0);
            $max = max(0.0001, (float) ($question['points'] ?? $row?->max_score ?? 0));
            $cpmkCode = (string) ($question['cpmk'] ?? '');
            $groupCount = max(1, (int) ($groupCounts[$cpmkCode] ?? 1));
            $questionShare = 100 / $groupCount;
            $cpmkWeight = ($groupCount / $totalQuestions) * 100;
            $contributions[$cpmkCode] = ($contributions[$cpmkCode] ?? 0)
                + (($earned / $max) * $questionShare * ($cpmkWeight / 100));

            if ($isEssay) {
                $essayScore += $earned;
            } else {
                $automaticScore += $earned;
            }
        }

        if (! $complete) {
            $this->obe->syncDirectScore($assessment, $submission->mahasiswa_id ?: $submission->user_id, null, $gradedById);

            return ['complete' => false, 'score' => null, 'automatic_score' => $automaticScore, 'essay_score' => $essayScore];
        }

        $total = round(min(100, max(0, array_sum($contributions))), 2);
        $studentId = (int) ($submission->mahasiswa_id ?: $submission->user_id);
        $assessment->loadMissing('cpmks');
        $cpmkValues = [];

        foreach ($contributions as $code => $value) {
            $normalized = $this->normalizeCpmkCode($code);
            $cpmk = $assessment->cpmks->first(
                fn ($candidate) => $this->normalizeCpmkCode($candidate->code) === $normalized
            );
            if ($cpmk) {
                $cpmkValues[$cpmk->id] = round($value, 2);
            }
        }

        $allCpmksMatched = $assessment->cpmks->isNotEmpty()
            && $assessment->cpmks->pluck('id')->diff(array_keys($cpmkValues))->isEmpty();

        if ($allCpmksMatched) {
            $this->obe->syncCpmkScores($assessment, $studentId, $cpmkValues, $gradedById, true);
        } else {
            $this->obe->syncDirectScore($assessment, $studentId, $total, $gradedById, true);
        }

        return ['complete' => true, 'score' => $total, 'automatic_score' => $automaticScore, 'essay_score' => $essayScore];
    }

    private function answerRow(Submission $submission, string $questionId, int $index): ?SubmissionAnswer
    {
        return $submission->answers()
            ->where('version', $submission->version)
            ->where(fn ($query) => $query->where('question_id', $questionId)->orWhere('question_index', $index))
            ->latest('id')
            ->first();
    }

    private function rowFromCollection(Collection $rows, string $questionId, int $index): ?SubmissionAnswer
    {
        return $rows->first(fn (SubmissionAnswer $row) => ($row->question_id !== null && (string) $row->question_id === $questionId)
            || ($row->question_index !== null && (int) $row->question_index === $index)
        );
    }

    private function normalizeCpmkCode(?string $code): string
    {
        return preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $code))));
    }
}
