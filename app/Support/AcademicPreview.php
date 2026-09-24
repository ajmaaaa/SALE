<?php

namespace App\Support;

class AcademicPreview
{
    public static function config(int $course): array
    {
        LearningPreview::course($course);

        $config = session("academic.config.$course", [
            'cpl' => [['code' => 'CPL-01', 'description' => 'Mampu menganalisis masalah dan menyusun solusi secara sistematis.'], ['code' => 'CPL-02', 'description' => 'Mampu menerapkan pengetahuan komputasi dalam praktik.']],
            'cpmk' => [['code' => 'CPMK-01', 'cpl' => 'CPL-01', 'description' => 'Menganalisis konsep dasar dan memilih pendekatan penyelesaian.', 'threshold' => 50], ['code' => 'CPMK-02', 'cpl' => 'CPL-02', 'description' => 'Menerapkan konsep melalui tugas dan praktikum.', 'threshold' => 80]],
            'components' => [['code' => 'tugas', 'name' => 'Tugas', 'weight' => 25], ['code' => 'kuis', 'name' => 'Kuis', 'weight' => 10], ['code' => 'uts', 'name' => 'UTS', 'weight' => 20], ['code' => 'uas', 'name' => 'UAS', 'weight' => 25], ['code' => 'proyek', 'name' => 'Proyek', 'weight' => 15], ['code' => 'partisipasi', 'name' => 'Kehadiran & partisipasi', 'weight' => 5]],
        ]);
        foreach ($config['cpmk'] as &$cpmk) {
            $cpmk['threshold'] = $cpmk['threshold'] ?? 65;
        }

        return $config;
    }

    public static function breakdown(int $course, int $student = 1): array
    {
        $cpmk = [];
        foreach (self::config($course)['cpmk'] as $outcome) {
            $cpmk[$outcome['code']] = [
                'code' => $outcome['code'], 'description' => $outcome['description'],
                'threshold' => $outcome['threshold'], 'earned' => 0, 'max' => 0,
                'score' => null, 'complete' => true, 'passed' => null,
            ];
        }

        $items = [];
        foreach (LearningPreview::items() as $id => $item) {
            if ($item['course'] !== $course || ! in_array($item['type'], ['tugas', 'coding', 'kuis'], true)) {
                continue;
            }
            if (! empty($item['questions'])) {
                $questions = $item['questions'];
            } elseif (($item['scoring_mode'] ?? null) === 'manual_cpmk' && ! empty($item['manual_cpmk_weights'])) {
                $questions = [];
                foreach ($item['manual_cpmk_weights'] as $code => $weight) {
                    $questions[] = ['prompt' => 'Kriteria '.$code, 'cpmk' => $code, 'points' => 100, 'manual_weight' => $weight];
                }
            } else {
                $questions = [[
                    'prompt' => $item['body'] ?? $item['title'],
                    'cpmk' => $item['cpmk'] ?? null, 'points' => $item['points'] ?? 100,
                ]];
            }
            $max = array_sum(array_column($questions, 'points'));
            $grades = session("academic.item_grades.$id.$student.points", []);
            $scoringMode = $item['scoring_mode'] ?? 'legacy_points';
            $groupCounts = array_count_values(array_filter(array_column($questions, 'cpmk')));
            $assessment = [
                'id' => $id, 'title' => $item['title'],
                'component' => $item['component'] ?? ($item['type'] === 'kuis' ? 'kuis' : 'tugas'),
                'questions' => [], 'score' => null, 'complete' => $max > 0,
                'scoring_mode' => $scoringMode,
            ];
            $earned = 0;
            $normalizedScore = 0;
            foreach ($questions as $index => $question) {
                $code = $question['cpmk'] ?? null;
                // Seeded quizzes predate the canonical CPMK-01 code format.
                if (! isset($cpmk[$code]) && preg_match('/^CPMK (\d+)$/', $code ?? '', $match)) {
                    $code = sprintf('CPMK-%02d', (int) $match[1]);
                }
                $points = $grades[$index] ?? null;
                $points = is_numeric($points) ? (float) $points : null;
                $questionMax = $question['points'] ?? 100;
                $weight = $max > 0 ? $questionMax / $max * 100 : 0;
                $withinCpmkWeight = $weight;
                $cpmkWeight = $weight;
                if ($scoringMode === 'automatic_cpmk') {
                    $groupCount = max(1, (int) ($groupCounts[$question['cpmk'] ?? ''] ?? 1));
                    $weight = 100 / max(1, count($questions));
                    $withinCpmkWeight = 100 / $groupCount;
                    $cpmkWeight = $groupCount / max(1, count($questions)) * 100;
                } elseif ($scoringMode === 'manual_cpmk') {
                    $weight = (float) ($question['manual_weight'] ?? 0);
                    $withinCpmkWeight = 100;
                    $cpmkWeight = $weight;
                }
                $assessment['questions'][] = [
                    'prompt' => $question['prompt'], 'cpmk' => $code, 'points' => $questionMax,
                    'earned' => $points, 'weight' => round($weight, 4),
                    'within_cpmk_weight' => round($withinCpmkWeight, 4),
                    'cpmk_weight' => round($cpmkWeight, 4),
                ];
                $earned += $points ?? 0;
                if ($points !== null && in_array($scoringMode, ['automatic_cpmk', 'manual_cpmk'], true)) {
                    $normalizedScore += ($questionMax > 0 ? $points / $questionMax : 0) * $weight;
                }
                if ($points === null) {
                    $assessment['complete'] = false;
                }
                if (isset($cpmk[$code])) {
                    if (in_array($scoringMode, ['automatic_cpmk', 'manual_cpmk'], true)) {
                        $cpmk[$code]['max'] += $weight;
                        $cpmk[$code]['earned'] += $points === null || $questionMax <= 0 ? 0 : $points / $questionMax * $weight;
                    } else {
                        $cpmk[$code]['max'] += $questionMax;
                        $cpmk[$code]['earned'] += $points ?? 0;
                    }
                    if ($points === null) {
                        $cpmk[$code]['complete'] = false;
                    }
                }
            }
            if ($assessment['complete']) {
                $assessment['score'] = in_array($scoringMode, ['automatic_cpmk', 'manual_cpmk'], true)
                    ? round($normalizedScore, 2)
                    : round($earned / $max * 100, 2);
            }
            $items[] = $assessment;
        }

        $complete = count($items) > 0 && count($cpmk) > 0;
        foreach ($items as $item) {
            $complete = $complete && $item['complete'];
        }
        $failed = false;
        foreach ($cpmk as &$outcome) {
            $outcome['complete'] = $outcome['complete'] && $outcome['max'] > 0;
            if ($outcome['complete']) {
                $score = $outcome['earned'] / $outcome['max'] * 100;
                $outcome['score'] = round($score, 2);
                $outcome['passed'] = $score >= $outcome['threshold'];
            }
            $complete = $complete && $outcome['complete'];
            $failed = $failed || $outcome['passed'] === false;
        }

        return ['items' => $items, 'cpmk' => array_values($cpmk), 'complete' => $complete, 'passed' => $failed ? false : ($complete ? true : null)];
    }

    public static function scores(int $course, int $student = 1): array
    {
        $scores = session("academic.scores.$course.$student", $student === 1 ? ['tugas' => 86, 'kuis' => 92, 'uts' => 88, 'uas' => null, 'proyek' => null, 'partisipasi' => 90] : []);
        // Item grades replace the manual component score with their normalized average.
        $graded = [];
        foreach (self::breakdown($course, $student)['items'] as $item) {
            if (! $item['complete']) {
                continue;
            }
            $graded[$item['component']][] = $item['score'];
        }
        foreach ($graded as $component => $values) {
            $scores[$component] = round(array_sum($values) / count($values), 2);
        }

        return $scores;
    }

    public static function result(int $course, int $student = 1): array
    {
        $scores = self::scores($course, $student);
        $total = 0;
        $coverage = 0;
        foreach (self::config($course)['components'] as $component) {
            $score = $scores[$component['code']] ?? null;
            if ($score !== null && $score !== '') {
                $total += $score * $component['weight'] / 100;
                $coverage += $component['weight'];
            }
        }
        $itemsComplete = ! in_array(false, array_column(self::breakdown($course, $student)['items'], 'complete'), true);

        return ['scores' => $scores, 'total' => $coverage > 0 ? round($total, 2) : null, 'average' => $coverage > 0 ? round($total / $coverage * 100, 2) : null, 'coverage' => $coverage, 'complete' => abs($coverage - 100) < 0.001 && $itemsComplete];
    }
}
