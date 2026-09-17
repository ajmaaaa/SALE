<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\StudentAssessmentScore;
use App\Models\StudentRubricScore;
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
     * yet graded. Thin wrapper kept here so callers never query
     * student_assessment_scores directly and risk treating a missing
     * row as 0.
     */
    public function assessmentScore(int $assessmentId, int $studentId): ?float
    {
        $score = StudentAssessmentScore::query()
            ->where('assessment_id', $assessmentId)
            ->where('mahasiswa_id', $studentId)
            ->value('score');

        return $score === null ? null : (float) $score;
    }

    /**
     * Compute assessment score from rubric criteria for one student.
     *
     * Formula: Nilai Assessment = Σ (Skor Criterion / Max Score × Bobot Criterion)
     * Result is normalized to 0-100 scale.
     *
     * Only criteria that have been graded are included. Returns null if
     * none of the criteria have scores yet.
     */
    public function assessmentScoreFromRubric(Assessment $assessment, int $studentId): ?float
    {
        $rubric = $assessment->rubric;

        if (! $rubric) {
            return null;
        }

        $criteria = $rubric->criteria;

        if ($criteria->isEmpty()) {
            return null;
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($criteria as $criterion) {
            $rubricScore = StudentRubricScore::query()
                ->where('rubric_criterion_id', $criterion->id)
                ->where('mahasiswa_id', $studentId)
                ->value('score');

            if ($rubricScore === null) {
                continue;
            }

            $maxScore = (float) $criterion->max_score;
            $weight = (float) $criterion->weight;

            if ($maxScore <= 0) {
                continue;
            }

            // Normalize criterion score to 0-100, then weight it
            $normalized = ((float) $rubricScore / $maxScore) * 100;
            $weightedSum += $normalized * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        // Re-normalize by weight total to get 0-100 scale
        return round($weightedSum / $weightTotal, 2);
    }

    /**
     * Sync assessment score from rubric criteria calculation into
     * student_assessment_scores. Call this after saving rubric scores
     * so the main assessment score stays in sync.
     */
    public function syncRubricToAssessmentScore(Assessment $assessment, int $studentId, int $gradedById): void
    {
        $score = $this->assessmentScoreFromRubric($assessment, $studentId);

        StudentAssessmentScore::updateOrCreate(
            [
                'assessment_id' => $assessment->id,
                'mahasiswa_id' => $studentId,
            ],
            [
                'score' => $score,
                'graded_by' => $gradedById,
                'graded_at' => now(),
            ],
        );
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
    public function cpmkScore(Cpmk $cpmk, int $studentId, ?int $classSectionId = null): ?float
    {
        if ($classSectionId !== null) {
            $measuringAssessments = $cpmk->assessments()->where('class_section_id', $classSectionId)->get();
        } else {
            // Find section from student enrollment if possible
            $studentSection = ClassSection::whereHas('students', fn ($q) => $q->where('users.id', $studentId))
                ->where('mata_kuliah_id', $cpmk->mata_kuliah_id)
                ->first();

            if ($studentSection) {
                $measuringAssessments = $cpmk->assessments()->where('class_section_id', $studentSection->id)->get();
            } else {
                $measuringAssessments = $cpmk->assessments;
            }
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($measuringAssessments as $assessment) {
            // Check if there is a specific score for this CPMK on this assessment
            $cpmkScoreRow = \App\Models\StudentAssessmentCpmkScore::query()
                ->where('assessment_id', $assessment->id)
                ->where('cpmk_id', $cpmk->id)
                ->where('mahasiswa_id', $studentId)
                ->first();

            if ($cpmkScoreRow !== null && $cpmkScoreRow->score !== null) {
                $rawScore = (float) $cpmkScoreRow->score;
                $maxScore = $this->assessmentCpmkMaxScore($assessment, $cpmk);
                $score = $maxScore > 0 ? ($rawScore / $maxScore) * 100.0 : $rawScore;
            } else {
                $score = $this->assessmentScore($assessment->id, $studentId);
            }

            if ($score === null) {
                continue;
            }

            $weight = $this->assessmentCpmkEffectiveWeight($assessment, $cpmk);
            $weightedSum += (float) $score * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        return round($weightedSum / $weightTotal, 2);
    }

    /**
     * Hitung bobot kontribusi efektif asesmen terhadap CPMK pada Matriks OBE (Tabel C).
     * Jika asesmen memiliki final_weight (bobot terhadap nilai akhir MK),
     * maka bobot efektif dihitung proporsional sehingga total kontribusi seluruh CPMK = final_weight.
     */
    public function assessmentCpmkEffectiveWeight(Assessment $assessment, Cpmk $cpmk): float
    {
        $pivot = $assessment->cpmks->firstWhere('id', $cpmk->id);
        if (! $pivot) {
            return 0.0;
        }

        $pivotWeight = (float) $pivot->pivot->weight;
        $finalWeight = (float) $assessment->final_weight;

        if ($finalWeight <= 0) {
            return $pivotWeight;
        }

        $totalPivot = (float) $assessment->cpmks->sum(fn ($c) => (float) $c->pivot->weight);

        // Jika bobot pivot sudah sesuai dengan final_weight (misal diinput langsung angka bobot akhir)
        if (abs($totalPivot - $finalWeight) < 0.1) {
            return $pivotWeight;
        }

        // Jika pivot 100% pada asesmen ini (mengukur 1 CPMK penuh)
        if ($pivotWeight >= 99.9) {
            return $finalWeight;
        }

        // Jika asesmen mengukur beberapa CPMK dengan total persentase bobot pivot ~100%
        if ($totalPivot >= 99.0) {
            return round(($finalWeight * $pivotWeight) / 100.0, 2);
        }

        return $pivotWeight;
    }

    /**
     * Hitung skor maksimal untuk satu CPMK pada asesmen tertentu
     * berdasarkan proporsi bobotnya (total maksimal seluruh CPMK pada asesmen = 100 poin).
     * Contoh: Jika Tugas 1 mengukur CPMK-01 (6%) dan CPMK-02 (4%),
     * maka max score CPMK-01 = 60 dan CPMK-02 = 40.
     * Jika asesmen hanya mengukur 1 CPMK, maka max score = 100.
     */
    public function assessmentCpmkMaxScore(Assessment $assessment, Cpmk $cpmk): float
    {
        if (! $assessment->relationLoaded('cpmks')) {
            $assessment->load('cpmks');
        }

        $cpmks = $assessment->cpmks;
        if ($cpmks->count() <= 1) {
            return 100.0;
        }

        $effWeight = $this->assessmentCpmkEffectiveWeight($assessment, $cpmk);
        $totalEffWeight = (float) $cpmks->sum(fn ($c) => $this->assessmentCpmkEffectiveWeight($assessment, $c));

        if ($totalEffWeight > 0) {
            return round(($effWeight / $totalEffWeight) * 100.0, 1);
        }

        $pivotWeight = (float) ($cpmks->firstWhere('id', $cpmk->id)?->pivot?->weight ?: 0);
        $totalPivot = (float) $cpmks->sum(fn ($c) => (float) ($c->pivot?->weight ?: 0));

        return $totalPivot > 0 ? round(($pivotWeight / $totalPivot) * 100.0, 1) : 100.0;
    }

    /**
     * CPL score for one student = weighted average of the CPMK scores
     * that build up this CPL, using each CPMK's contribution weight to
     * this CPL (cpl_cpmk.weight). Same "exclude and re-normalize" rule
     * for CPMK that aren't computable yet.
     */
    public function cplScore(Cpl $cpl, int $studentId, ?int $classSectionId = null): ?float
    {
        $contributingCpmks = $cpl->cpmks; // has pivot 'weight'

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($contributingCpmks as $cpmk) {
            $score = $this->cpmkScore($cpmk, $studentId, $classSectionId);

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
     * sum of assessment scores at the matrix cell level (w(j,k) * s(j,k)),
     * which equals the weighted sum of CPMK scores using their RPS weights.
     *
     * IMPORTANT (per requirement): this is a distinct calculation path
     * from the CPL average — it must never be derived from CPL scores.
     * Missing assessments/cells are excluded and weights re-normalized, and
     * `coverage` reports how much of the total course weight is backed by
     * an actual grade, so callers can show a "provisional" indicator
     * instead of presenting an incomplete result as final.
     *
     * @return array{score: ?float, coverage: float} coverage is 0–100.
     */
    public function finalScore(ClassSection $section, int $studentId): array
    {
        $assessments = $section->assessments()->with('cpmks')->get();

        $weightedSum = 0.0;
        $weightGraded = 0.0;
        $weightTotal = 0.0;

        foreach ($assessments as $assessment) {
            if ($assessment->cpmks->isNotEmpty()) {
                foreach ($assessment->cpmks as $cpmk) {
                    $w = $this->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                    $weightTotal += $w;

                    // Check if there is a specific score for this CPMK on this assessment
                    $cpmkScoreRow = \App\Models\StudentAssessmentCpmkScore::query()
                        ->where('assessment_id', $assessment->id)
                        ->where('cpmk_id', $cpmk->id)
                        ->where('mahasiswa_id', $studentId)
                        ->first();

                    if ($cpmkScoreRow !== null && $cpmkScoreRow->score !== null) {
                        $rawScore = (float) $cpmkScoreRow->score;
                        $maxScore = $this->assessmentCpmkMaxScore($assessment, $cpmk);
                        $score = $maxScore > 0 ? ($rawScore / $maxScore) * 100.0 : $rawScore;
                    } else {
                        $score = $this->assessmentScore($assessment->id, $studentId);
                    }

                    if ($score !== null) {
                        $weightedSum += $score * $w;
                        $weightGraded += $w;
                    }
                }
            } else {
                $weight = (float) $assessment->final_weight;
                $weightTotal += $weight;

                $score = $this->assessmentScore($assessment->id, $studentId);

                if ($score !== null) {
                    $weightedSum += $score * $weight;
                    $weightGraded += $weight;
                }
            }
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
     * Hitung total bobot satu CPMK terhadap nilai akhir mata kuliah pada satu kelas.
     * Dihitung dari penjumlahan bobot sel matriks asesmen yang mengukur CPMK tersebut:
     * Bobot CPMK(k) = Σ_j w(j,k)
     */
    public function cpmkWeight(Cpmk $cpmk, ClassSection $section): float
    {
        $assessments = $section->assessments()->with('cpmks')->get();
        $totalWeight = 0.0;

        foreach ($assessments as $assessment) {
            $totalWeight += $this->assessmentCpmkEffectiveWeight($assessment, $cpmk);
        }

        return round($totalWeight, 2);
    }

    /**
     * Map total bobot seluruh CPMK pada satu kelas [cpmk_id => bobot].
     *
     * @param Collection<int, Cpmk> $cpmks
     * @return Collection<int, float>
     */
    public function cpmkWeightsFor(Collection $cpmks, ClassSection $section): Collection
    {
        return $cpmks->mapWithKeys(fn (Cpmk $cpmk) => [$cpmk->id => $this->cpmkWeight($cpmk, $section)]);
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
    public function cpmkScoresFor(Collection $cpmks, int $studentId, ?int $classSectionId = null): Collection
    {
        return $cpmks->mapWithKeys(fn (Cpmk $cpmk) => [$cpmk->id => $this->cpmkScore($cpmk, $studentId, $classSectionId)]);
    }

    /**
     * Convenience: compute CPL scores for every CPL of a prodi, for one
     * student, keyed by CPL id.
     *
     * @return Collection<int, ?float>
     */
    public function cplScoresFor(Collection $cpls, int $studentId, ?int $classSectionId = null): Collection
    {
        return $cpls->mapWithKeys(fn (Cpl $cpl) => [$cpl->id => $this->cplScore($cpl, $studentId, $classSectionId)]);
    }

    /**
     * Predikat Ketercapaian OBE berdasarkan rentang nilai (Bagian 4 desain.md):
     *  ≥ 85.0     : Sangat Baik
     *  70.0 - 84.9: Baik
     *  60.0 - 69.9: Cukup
     *  < 60.0     : Kurang
     */
    public function predicate(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 85.0 => 'Sangat Baik',
            $score >= 70.0 => 'Baik',
            $score >= 60.0 => 'Cukup',
            default => 'Kurang',
        };
    }

    /**
     * CSS classes untuk badge predikat ketercapaian (clean, pastel, non-slopp).
     */
    public function predicateBadgeClass(?string $predicate): string
    {
        return match ($predicate) {
            'Sangat Baik' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Baik' => 'bg-sky-50 text-sky-700 border-sky-200',
            'Cukup' => 'bg-amber-50 text-amber-700 border-amber-200',
            'Kurang' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-canvas text-muted border-line',
        };
    }

    /**
     * Rekap agregat kelas untuk satu CPMK (Level 3 rumus-obe-cpmk-cpl.md):
     *  RataRata_CPMK(k, kelas) = Σ NCPMK(k, mhs) / Jumlah_mahasiswa
     *  %Mahasiswa_Tuntas(k, kelas) = Jumlah(mhs dengan NCPMK ≥ ambang_batas) / Jumlah_mahasiswa × 100
     *
     * @param Collection<int, int> $studentIds
     * @return array{graded_count: int, total_count: int, average: ?float, pass_count: int, pass_rate: ?float, predicate: ?string}
     */
    public function cpmkClassAggregate(Cpmk $cpmk, Collection $studentIds, ?int $classSectionId = null): array
    {
        $scores = [];
        foreach ($studentIds as $id) {
            $score = $this->cpmkScore($cpmk, $id, $classSectionId);
            if ($score !== null) {
                $scores[] = $score;
            }
        }

        $totalCount = $studentIds->count();
        $gradedCount = count($scores);

        if ($gradedCount === 0) {
            return [
                'graded_count' => 0,
                'total_count' => $totalCount,
                'average' => null,
                'pass_count' => 0,
                'pass_rate' => null,
                'predicate' => null,
            ];
        }

        $average = round(array_sum($scores) / $gradedCount, 2);
        $threshold = (float) ($cpmk->threshold ?: 60.0);
        $passCount = count(array_filter($scores, fn ($s) => $s >= $threshold));
        $passRate = round(($passCount / $gradedCount) * 100, 1);

        return [
            'graded_count' => $gradedCount,
            'total_count' => $totalCount,
            'average' => $average,
            'pass_count' => $passCount,
            'pass_rate' => $passRate,
            'predicate' => $this->predicate($average),
        ];
    }

    /**
     * Rekap agregat kelas untuk satu CPL (Level 4 & 5 rumus-obe-cpmk-cpl.md):
     *  RataRata_CPL(m, kelas) = Σ NCPL(m, mhs) / Jumlah_mahasiswa
     *  %Mahasiswa_Tuntas = Jumlah(mhs dengan NCPL ≥ 65) / Jumlah_mahasiswa × 100
     *
     * @param Collection<int, int> $studentIds
     * @return array{graded_count: int, total_count: int, average: ?float, pass_count: int, pass_rate: ?float, predicate: ?string}
     */
    public function cplClassAggregate(Cpl $cpl, Collection $studentIds, ?int $classSectionId = null): array
    {
        $scores = [];
        foreach ($studentIds as $id) {
            $score = $this->cplScore($cpl, $id, $classSectionId);
            if ($score !== null) {
                $scores[] = $score;
            }
        }

        $totalCount = $studentIds->count();
        $gradedCount = count($scores);

        if ($gradedCount === 0) {
            return [
                'graded_count' => 0,
                'total_count' => $totalCount,
                'average' => null,
                'pass_count' => 0,
                'pass_rate' => null,
                'predicate' => null,
            ];
        }

        $average = round(array_sum($scores) / $gradedCount, 2);
        $threshold = 65.0; // Ambang batas CPL standar
        $passCount = count(array_filter($scores, fn ($s) => $s >= $threshold));
        $passRate = round(($passCount / $gradedCount) * 100, 1);

        return [
            'graded_count' => $gradedCount,
            'total_count' => $totalCount,
            'average' => $average,
            'pass_count' => $passCount,
            'pass_rate' => $passRate,
            'predicate' => $this->predicate($average),
        ];
    }
}
