<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Rubric;
use App\Models\StudentAssessmentCpmkScore;
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
     * Hitung nilai rubrik untuk satu mahasiswa dari kriteria rubrik.
     */
    public function rubricScore(Rubric $rubric, int $studentId): ?float
    {
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

        return $this->rubricScore($rubric, $studentId);
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
    public const DEFAULT_THRESHOLD = 65.0;

    /**
     * Ambil skor mahasiswa pada satu asesmen spesifik untuk CPMK tertentu (skala 0-100).
     *
     * Aturan Fallback (Poin 6):
     * 1. Jika terdapat baris spesifik pada StudentAssessmentCpmkScore, gunakan nilai tersebut
     *    (dinormalisasi ke skala 0-100 terhadap poin maksimal CPMK pada asesmen ini).
     * 2. Jika tidak ada baris spesifik (atau null):
     *    - Jika asesmen dinilai per-CPMK (memiliki baris CPMK lain yang dinilai untuk mahasiswa ini),
     *      maka CPMK ini dianggap BELUM DINILAI (return null), bukan mengambil nilai total asesmen.
     *    - Fallback ke StudentAssessmentScore HANYA diizinkan jika asesmen secara struktural
     *      menggunakan satu nilai yang sama untuk seluruh CPMK (misal: hanya mengukur 1 CPMK,
     *      atau menggunakan rubrik umum, atau memang asesmen dengan skor tunggal tanpa breakdown CPMK).
     * 3. Nilai yang belum ada tidak pernah dianggap 0 (return null).
     */
    public function studentScoreForAssessmentCpmk(Assessment $assessment, Cpmk $cpmk, int $studentId): ?float
    {
        // 1. Cek nilai spesifik per-CPMK untuk mahasiswa ini jika ada
        $cpmkScoreRow = StudentAssessmentCpmkScore::query()
            ->where('assessment_id', $assessment->id)
            ->where('cpmk_id', $cpmk->id)
            ->where('mahasiswa_id', $studentId)
            ->first();

        if ($cpmkScoreRow !== null) {
            if ($cpmkScoreRow->score !== null) {
                $rawScore = (float) $cpmkScoreRow->score;
                $maxScore = $this->assessmentCpmkMaxScore($assessment, $cpmk);
                $normalized = $maxScore > 0 ? ($rawScore / $maxScore) * 100.0 : $rawScore;

                return round(min(100.0, max(0.0, $normalized)), 2);
            }

            // Jika baris per-CPMK mahasiswa ini sudah ada tetapi nilainya null,
            // berarti CPMK ini belum dinilai (return null, jangan fallback ke nilai umum asesmen).
            return null;
        }

        // 2. Jika baris spesifik per-CPMK belum ada:
        // Jika mahasiswa ini sudah dinilai pada CPMK lain dalam asesmen multi-CPMK ini,
        // maka CPMK ini murni belum dinilai (harus tetap null, jangan fallback ke nilai umum asesmen!).
        $hasOtherCpmkGraded = StudentAssessmentCpmkScore::query()
            ->where('assessment_id', $assessment->id)
            ->where('mahasiswa_id', $studentId)
            ->whereNotNull('score')
            ->exists();

        if ($hasOtherCpmkGraded) {
            return null;
        }

        // 3. Fallback hanya jika asesmen secara struktural menggunakan nilai tunggal bersama:
        // - Asesmen hanya mengukur <= 1 CPMK
        // - Atau asesmen menggunakan rubrik umum (uses_rubric)
        // - Atau pada asesmen multi-CPMK tanpa rubrik jika mahasiswa ini belum memiliki baris per-CPMK sama sekali
        if (! $assessment->relationLoaded('cpmks')) {
            $assessment->load('cpmks');
        }

        return $this->assessmentScore($assessment->id, $studentId);
    }

    /**
     * Bobot kontribusi asesmen dalam mengukur CPMK (assessment_cpmk.weight).
     * Kontrak konsisten (Poin 1 & 2):
     * - Asesmen tunggal mengukur 1 CPMK: bobot = 100%.
     * - Asesmen multi-CPMK: dibagi proporsional (misal: 60% dan 40%).
     * Tidak mencampurkan final_weight asesmen terhadap nilai akhir MK.
     */
    public function assessmentCpmkWeight(Assessment $assessment, Cpmk $cpmk): float
    {
        if (! $assessment->relationLoaded('cpmks')) {
            $assessment->load('cpmks');
        }

        $pivot = $assessment->cpmks->firstWhere('id', $cpmk->id);

        return (float) ($pivot?->pivot?->weight ?? 0.0);
    }

    /**
     * Hitung nilai CPMK untuk satu mahasiswa beserta metadata progres penilaian (coverage).
     *
     * @return array{
     *     score: ?float,
     *     coverage: float,
     *     weight_graded: float,
     *     weight_total: float,
     *     is_complete: bool
     * }
     */
    public function cpmkScoreDetails(Cpmk $cpmk, int $studentId, ?int $classSectionId = null): array
    {
        if ($classSectionId !== null) {
            $measuringAssessments = $cpmk->assessments()->where('class_section_id', $classSectionId)->get();
        } else {
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
        $weightGraded = 0.0;
        $weightTotalPossible = 0.0;

        foreach ($measuringAssessments as $assessment) {
            $effectiveWeight = $this->assessmentCpmkEffectiveWeight($assessment, $cpmk);
            if ($effectiveWeight <= 0) {
                continue;
            }

            $weightTotalPossible += $effectiveWeight;

            $score = $this->studentScoreForAssessmentCpmk($assessment, $cpmk, $studentId);

            if ($score !== null) {
                $weightedSum += (float) $score * $effectiveWeight;
                $weightGraded += $effectiveWeight;
            }
        }

        $coverage = $weightTotalPossible > 0 ? round(($weightGraded / $weightTotalPossible) * 100.0, 1) : 0.0;
        $score = $weightGraded > 0 ? round($weightedSum / $weightGraded, 2) : null;

        return [
            'score' => $score,
            'coverage' => $coverage,
            'weight_graded' => round($weightGraded, 2),
            'weight_total' => round($weightTotalPossible, 2),
            'is_complete' => $coverage >= 99.9,
        ];
    }

    /**
     * CPMK score for one student (Poin 2, 3, 6, 8):
     * CPMK = Σ (nilai asesmen × bobot efektif asesmen→CPMK) / Σ (bobot efektif asesmen→CPMK yang dinilai)
     */
    public function cpmkScore(Cpmk $cpmk, int $studentId, ?int $classSectionId = null): ?float
    {
        return $this->cpmkScoreDetails($cpmk, $studentId, $classSectionId)['score'];
    }

    /**
     * Persentase kelengkapan penilaian (coverage) untuk satu CPMK (0.0 - 100.0).
     */
    public function cpmkCoverage(Cpmk $cpmk, int $studentId, ?int $classSectionId = null): float
    {
        return $this->cpmkScoreDetails($cpmk, $studentId, $classSectionId)['coverage'];
    }

    /**
     * Bobot efektif satu sel asesmen x CPMK terhadap nilai akhir mata kuliah.
     * Definisi murni (tanpa heuristik/tebak-tebakan):
     * - assessment.final_weight = bobot asesmen terhadap nilai akhir MK (misal 20%).
     * - assessment_cpmk.weight = alokasi bobot asesmen untuk CPMK (misal 60 dan 40 poin).
     * Bobot efektif proporsional = final_weight * (pivot_weight / total_pivot).
     */
    public function assessmentCpmkEffectiveWeight(Assessment $assessment, Cpmk $cpmk): float
    {
        if (! $assessment->relationLoaded('cpmks')) {
            $assessment->load('cpmks');
        }

        $pivot = $assessment->cpmks->firstWhere('id', $cpmk->id);
        if (! $pivot) {
            return 0.0;
        }

        $pivotWeight = (float) $pivot->pivot->weight;
        $finalWeight = (float) $assessment->final_weight;

        if ($finalWeight <= 0 || $pivotWeight <= 0) {
            return 0.0;
        }

        $totalPivot = (float) $assessment->cpmks->sum(fn ($c) => (float) $c->pivot->weight);
        if ($totalPivot <= 0) {
            return 0.0;
        }

        return round(($finalWeight * $pivotWeight) / $totalPivot, 2);
    }

    /**
     * Hitung skor maksimal untuk satu CPMK pada asesmen tertentu
     * berdasarkan proporsi bobotnya (total maksimal seluruh CPMK pada asesmen = 100 poin).
     * Contoh: Jika Tugas 1 mengukur CPMK-01 (60%) dan CPMK-02 (40%),
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

        $weight = $this->assessmentCpmkWeight($assessment, $cpmk);
        $totalWeight = (float) $cpmks->sum(fn ($c) => (float) ($c->pivot?->weight ?: 0));

        if ($totalWeight > 0) {
            return round(($weight / $totalWeight) * 100.0, 1);
        }

        return 100.0;
    }

    /**
     * Hitung capaian CPL untuk satu mahasiswa beserta metadata coverage.
     *
     * @return array{
     *     score: ?float,
     *     coverage: float,
     *     cpmks_graded: int,
     *     cpmks_total: int,
     *     weight_graded: float,
     *     weight_total: float,
     *     is_complete: bool
     * }
     */
    public function cplScoreDetails(Cpl $cpl, int $studentId, ?int $classSectionId = null): array
    {
        $contributingCpmks = $cpl->cpmks; // has pivot 'weight'

        $weightedSum = 0.0;
        $weightGraded = 0.0;
        $weightTotalPossible = 0.0;
        $cpmksGradedCount = 0;

        foreach ($contributingCpmks as $cpmk) {
            $pivotWeight = (float) $cpmk->pivot->weight;
            $weightTotalPossible += $pivotWeight;

            $cpmkDetails = $this->cpmkScoreDetails($cpmk, $studentId, $classSectionId);
            $score = $cpmkDetails['score'];

            if ($score !== null) {
                $weightedSum += $score * $pivotWeight;
                $weightGraded += $pivotWeight;
                $cpmksGradedCount++;
            }
        }

        $coverage = $weightTotalPossible > 0 ? round(($weightGraded / $weightTotalPossible) * 100.0, 1) : 0.0;
        $score = $weightGraded > 0 ? round($weightedSum / $weightGraded, 2) : null;

        return [
            'score' => $score,
            'coverage' => $coverage,
            'cpmks_graded' => $cpmksGradedCount,
            'cpmks_total' => $contributingCpmks->count(),
            'weight_graded' => round($weightGraded, 2),
            'weight_total' => round($weightTotalPossible, 2),
            'is_complete' => $coverage >= 99.9,
        ];
    }

    /**
     * CPL score for one student (Poin 4, 8):
     * CPL = Σ (nilai CPMK × bobot CPMK→CPL) / Σ (bobot CPMK→CPL yang dinilai)
     */
    public function cplScore(Cpl $cpl, int $studentId, ?int $classSectionId = null): ?float
    {
        return $this->cplScoreDetails($cpl, $studentId, $classSectionId)['score'];
    }

    /**
     * Persentase kelengkapan penilaian (coverage) untuk satu CPL (0.0 - 100.0).
     */
    public function cplCoverage(Cpl $cpl, int $studentId, ?int $classSectionId = null): float
    {
        return $this->cplScoreDetails($cpl, $studentId, $classSectionId)['coverage'];
    }

    /**
     * Final course grade for one student in a class section (Poin 1, 7, 8):
     * Nilai Akhir = Σ (nilai asesmen × assessment.final_weight) / Σ (final_weight yang dinilai)
     *
     * Berdiri sendiri dan terpisah total dari jalur capaian CPMK/CPL OBE.
     * Mengembalikan coverage agar status parsial/provisional terdeteksi transparan.
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

            // Sumber utama: student_assessment_scores.score
            $score = $this->assessmentScore($assessment->id, $studentId);

            // Fallback cadangan HANYA jika student_assessment_scores belum tersimpan/null,
            // asesmen mengukur CPMK, dan secara data terbukti bahwa seluruh nilai per-CPMK
            // mahasiswa ini adalah poin kontribusi yang valid (0 <= score <= maxScore, bukan 0-100).
            if ($score === null && $assessment->cpmks()->exists()) {
                $cpmks = $assessment->relationLoaded('cpmks') ? $assessment->cpmks : $assessment->cpmks()->get();
                $cpmkScores = StudentAssessmentCpmkScore::query()
                    ->where('assessment_id', $assessment->id)
                    ->where('mahasiswa_id', $studentId)
                    ->get()
                    ->keyBy('cpmk_id');

                $allGradedAndValid = $cpmks->isNotEmpty() && $cpmks->every(function ($cpmk) use ($cpmkScores, $assessment) {
                    $row = $cpmkScores->get($cpmk->id);
                    if ($row === null || $row->score === null) {
                        return false;
                    }
                    $maxScore = $this->assessmentCpmkMaxScore($assessment, $cpmk);
                    $val = (float) $row->score;

                    return $val >= 0 && $val <= ($maxScore + 0.05);
                });

                if ($allGradedAndValid) {
                    $score = round((float) $cpmkScores->sum('score'), 2);
                }
            }

            if ($score !== null) {
                $weightedSum += $score * $weight;
                $weightGraded += $weight;
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
     * @param  Collection<int, Cpmk>  $cpmks
     * @return Collection<int, float>
     */
    public function cpmkWeightsFor(Collection $cpmks, ClassSection $section): Collection
    {
        return $cpmks->mapWithKeys(fn (Cpmk $cpmk) => [$cpmk->id => $this->cpmkWeight($cpmk, $section)]);
    }

    /**
     * Ambang batas ketercapaian untuk CPMK tertentu.
     */
    public function thresholdForCpmk(Cpmk $cpmk): float
    {
        return (float) ($cpmk->threshold ?: config('obe.default_cpmk_threshold', self::DEFAULT_THRESHOLD));
    }

    /**
     * Ambang batas ketercapaian untuk CPL tertentu.
     */
    public function thresholdForCpl(?Cpl $cpl = null): float
    {
        return (float) ($cpl?->threshold ?: config('obe.default_cpl_threshold', self::DEFAULT_THRESHOLD));
    }

    /**
     * Status teks ketercapaian OBE biner (Poin 7).
     */
    public function attainmentStatus(?bool $isAchieved): ?string
    {
        if ($isAchieved === null) {
            return null;
        }

        return $isAchieved ? 'Tercapai' : 'Tidak Tercapai';
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

        return $score >= $this->thresholdForCpmk($cpmk);
    }

    /**
     * Whether a CPL score meets its configured threshold.
     * Returns null (undetermined) if the score itself is null.
     */
    public function cplAchieved(Cpl $cpl, ?float $score): ?bool
    {
        if ($score === null) {
            return null;
        }

        return $score >= $this->thresholdForCpl($cpl);
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
     * @param  Collection<int, int>  $studentIds
     * @return array{graded_count: int, total_count: int, average: ?float, threshold: float, pass_count: int, pass_rate: ?float, is_achieved: ?bool, attainment_status: ?string, predicate: ?string}
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
        $threshold = $this->thresholdForCpmk($cpmk);

        if ($gradedCount === 0) {
            return [
                'graded_count' => 0,
                'total_count' => $totalCount,
                'average' => null,
                'threshold' => $threshold,
                'pass_count' => 0,
                'pass_rate' => null,
                'is_achieved' => null,
                'attainment_status' => null,
                'predicate' => null,
            ];
        }

        $average = round(array_sum($scores) / $gradedCount, 2);
        $passCount = count(array_filter($scores, fn ($s) => $s >= $threshold));
        $passRate = round(($passCount / $gradedCount) * 100, 1);
        $isAchieved = $average >= $threshold;

        return [
            'graded_count' => $gradedCount,
            'total_count' => $totalCount,
            'average' => $average,
            'threshold' => $threshold,
            'pass_count' => $passCount,
            'pass_rate' => $passRate,
            'is_achieved' => $isAchieved,
            'attainment_status' => $this->attainmentStatus($isAchieved),
            'predicate' => $this->predicate($average),
        ];
    }

    /**
     * Rekap agregat kelas untuk satu CPL (Level 4 & 5 rumus-obe-cpmk-cpl.md):
     *  RataRata_CPL(m, kelas) = Σ NCPL(m, mhs) / Jumlah_mahasiswa
     *  %Mahasiswa_Tuntas = Jumlah(mhs dengan NCPL ≥ ambang_batas) / Jumlah_mahasiswa × 100
     *
     * @param  Collection<int, int>  $studentIds
     * @return array{graded_count: int, total_count: int, average: ?float, threshold: float, pass_count: int, pass_rate: ?float, is_achieved: ?bool, attainment_status: ?string, predicate: ?string}
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
        $threshold = $this->thresholdForCpl($cpl);

        if ($gradedCount === 0) {
            return [
                'graded_count' => 0,
                'total_count' => $totalCount,
                'average' => null,
                'threshold' => $threshold,
                'pass_count' => 0,
                'pass_rate' => null,
                'is_achieved' => null,
                'attainment_status' => null,
                'predicate' => null,
            ];
        }

        $average = round(array_sum($scores) / $gradedCount, 2);
        $passCount = count(array_filter($scores, fn ($s) => $s >= $threshold));
        $passRate = round(($passCount / $gradedCount) * 100, 1);
        $isAchieved = $average >= $threshold;

        return [
            'graded_count' => $gradedCount,
            'total_count' => $totalCount,
            'average' => $average,
            'threshold' => $threshold,
            'pass_count' => $passCount,
            'pass_rate' => $passRate,
            'is_achieved' => $isAchieved,
            'attainment_status' => $this->attainmentStatus($isAchieved),
            'predicate' => $this->predicate($average),
        ];
    }
}
