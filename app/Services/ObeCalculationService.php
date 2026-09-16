<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\StudentAssessmentScore;
use Illuminate\Support\Collection;

/**
 * Single source of truth for every OBE calculation: assessment score
 * (from rubric criteria, when used), CPMK score (weighted from the
 * assessments that measure it), CPL score (weighted from its CPMK), and
 * final course grade (weighted from assessments' final_weight).
 *
 * Rules this service exists to enforce everywhere, consistently:
 *  - A NULL/missing score is never treated as 0. If any input a formula
 *    depends on is missing, the result is NULL ("not yet computable"),
 *    not a number computed as if the missing part were zero.
 *  - Per-item weights (assessment→CPMK, CPMK→CPL) are normalized against
 *    the sum of weights that actually have data, so a partially graded
 *    CPMK/CPL still produces a meaningful provisional figure rather than
 *    silently under-counting.
 *  - All of this lives here so Blade views and controllers only ever
 *    call into this service — they must not re-implement any formula.
 */
class ObeCalculationService
{
    /**
     * Get a single student's score for one assessment, or null if not
     * yet graded. If the assessment uses a rubric and rubric scores exist,
     * it calculates the score from the rubric criteria.
     */
    public function assessmentScore(int $assessmentId, int $studentId): ?float
    {
        $assessment = \App\Models\Assessment::with('rubric.criteria')->find($assessmentId);
        if ($assessment && $assessment->uses_rubric && $assessment->rubric) {
            $rubricScore = $this->rubricScore($assessment->rubric, $studentId);
            if ($rubricScore !== null) {
                return $rubricScore;
            }
        }

        $score = StudentAssessmentScore::query()
            ->where('assessment_id', $assessmentId)
            ->where('mahasiswa_id', $studentId)
            ->value('score');

        return $score === null ? null : (float) $score;
    }

    /**
     * Compute assessment score from rubric criteria for one student.
     * Nilai Assessment = Σ (Skor Criterion × Bobot Criterion).
     * Re-normalized across graded criteria. Returns null if no criteria are graded yet.
     */
    public function rubricScore(\App\Models\Rubric $rubric, int $studentId): ?float
    {
        $criteria = $rubric->relationLoaded('criteria') ? $rubric->criteria : $rubric->criteria()->get();

        if ($criteria->isEmpty()) {
            return null;
        }

        $criterionIds = $criteria->pluck('id');
        $scores = \App\Models\StudentRubricScore::whereIn('rubric_criterion_id', $criterionIds)
            ->where('mahasiswa_id', $studentId)
            ->pluck('score', 'rubric_criterion_id');

        $weightedSum = 0.0;
        $weightGraded = 0.0;

        foreach ($criteria as $criterion) {
            $rawScore = $scores->get($criterion->id);
            if ($rawScore === null) {
                continue;
            }

            $score = (float) $rawScore;
            $maxScore = (float) ($criterion->max_score ?: 100);
            $normalizedScore = $maxScore > 0 ? ($score / $maxScore) * 100 : $score;
            $weight = (float) $criterion->weight;

            $weightedSum += $normalizedScore * ($weight / 100);
            $weightGraded += $weight;
        }

        if ($weightGraded <= 0) {
            return null;
        }

        return round($weightedSum / ($weightGraded / 100), 2);
    }

    /**
     * CPMK score for one student = weighted average of the scores from
     * every assessment that measures this CPMK, using each assessment's
     * contribution weight to this CPMK (assessment_cpmk.weight).
     *
     * Assessments the student has not yet been graded on are excluded
     * from both the numerator and the weight total (re-normalized),
     * rather than counted as 0. Returns null if none of the measuring
     * assessments have a score yet.
     */
    public function cpmkScore(Cpmk $cpmk, int $studentId): ?float
    {
        $measuringAssessments = $cpmk->assessments; // has pivot 'weight'

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($measuringAssessments as $assessment) {
            $score = $this->assessmentScore($assessment->id, $studentId);

            if ($score === null) {
                continue;
            }

            $weight = (float) $assessment->pivot->weight;
            $weightedSum += $score * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        return round($weightedSum / $weightTotal, 2);
    }

    /**
     * CPL score for one student = weighted average of the CPMK scores
     * that build up this CPL, using each CPMK's contribution weight to
     * this CPL (cpl_cpmk.weight). Same "exclude and re-normalize" rule
     * for CPMK that aren't computable yet.
     */
    public function cplScore(Cpl $cpl, int $studentId): ?float
    {
        $contributingCpmks = $cpl->cpmks; // has pivot 'weight'

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($contributingCpmks as $cpmk) {
            $score = $this->cpmkScore($cpmk, $studentId);

            if ($score === null) {
                continue;
            }

            $weight = (float) $cpmk->pivot->weight;
            $weightedSum += $score * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        return round($weightedSum / $weightTotal, 2);
    }

    /**
     * Final course grade for one student in a class section = weighted
     * sum of assessment scores using each assessment's final_weight.
     *
     * IMPORTANT (per requirement): this is a distinct calculation path
     * from the CPL average — it must never be derived from CPL scores.
     * Missing assessments are excluded and weights re-normalized, and
     * `coverage` reports how much of the total final_weight is backed by
     * an actual grade, so callers can show a "provisional" indicator
     * instead of presenting an incomplete result as final.
     *
     * @return array{score: ?float, coverage: float} coverage is 0–100.
     */
    public function finalScore(ClassSection $section, int $studentId): array
    {
        $assessments = $section->assessments;

        $weightedSum = 0.0;
        $weightGraded = 0.0;
        $weightTotal = 0.0;

        foreach ($assessments as $assessment) {
            $weight = (float) $assessment->final_weight;
            $weightTotal += $weight;

            $score = $this->assessmentScore($assessment->id, $studentId);

            if ($score === null) {
                continue;
            }

            $weightedSum += $score * $weight;
            $weightGraded += $weight;
        }

        $coverage = $weightTotal > 0 ? round($weightGraded / $weightTotal * 100, 1) : 0.0;

        if ($weightGraded <= 0) {
            return ['score' => null, 'coverage' => $coverage];
        }

        // Re-normalize against the weight actually graded so a partial
        // result reads as a fair provisional average, not a deflated one.
        $score = round($weightedSum / $weightGraded, 2);

        return ['score' => $score, 'coverage' => $coverage];
    }

    /**
     * Whether a CPMK score meets its own configured threshold.
     * Returns null (undetermined) if the score itself is null.
     */
    public function cpmkAchieved(Cpmk $cpmk, ?float $score): ?bool
    {
        if ($score === null) {
            return null;
        }

        return $score >= (float) $cpmk->threshold;
    }

    /**
     * Convenience: compute CPMK scores for every CPMK of a mata kuliah,
     * for one student, keyed by CPMK id. Used by recap pages so they
     * don't re-loop the relationship themselves.
     *
     * @return Collection<int, ?float>
     */
    public function cpmkScoresFor(Collection $cpmks, int $studentId): Collection
    {
        return $cpmks->mapWithKeys(fn (Cpmk $cpmk) => [$cpmk->id => $this->cpmkScore($cpmk, $studentId)]);
    }

    /**
     * Convenience: compute CPL scores for every CPL of a prodi, for one
     * student, keyed by CPL id.
     *
     * @return Collection<int, ?float>
     */
    public function cplScoresFor(Collection $cpls, int $studentId): Collection
    {
        return $cpls->mapWithKeys(fn (Cpl $cpl) => [$cpl->id => $this->cplScore($cpl, $studentId)]);
    }
}
