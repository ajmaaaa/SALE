<?php

namespace App\Support;

class AcademicPreview
{
    public static function config(int $course): array
    {
        LearningPreview::course($course);

        return session("academic.config.$course", [
            'cpl'=>[['code'=>'CPL-01','description'=>'Mampu menganalisis masalah dan menyusun solusi secara sistematis.'],['code'=>'CPL-02','description'=>'Mampu menerapkan pengetahuan komputasi dalam praktik.']],
            'cpmk'=>[['code'=>'CPMK-01','cpl'=>'CPL-01','description'=>'Menganalisis konsep dasar dan memilih pendekatan penyelesaian.'],['code'=>'CPMK-02','cpl'=>'CPL-02','description'=>'Menerapkan konsep melalui tugas dan praktikum.']],
            'components'=>[['code'=>'tugas','name'=>'Tugas','weight'=>25],['code'=>'kuis','name'=>'Kuis','weight'=>10],['code'=>'uts','name'=>'UTS','weight'=>20],['code'=>'uas','name'=>'UAS','weight'=>25],['code'=>'proyek','name'=>'Proyek','weight'=>15],['code'=>'partisipasi','name'=>'Kehadiran & partisipasi','weight'=>5]],
        ]);
    }

    public static function scores(int $course, int $student = 1): array
    {
        $scores = session("academic.scores.$course.$student", $student === 1 ? ['tugas'=>86,'kuis'=>92,'uts'=>88,'uas'=>null,'proyek'=>null,'partisipasi'=>90] : []);
        // Item grades replace the manual component score with their normalized average.
        $graded = [];
        foreach (LearningPreview::items() as $id=>$item) {
            if ($item['course'] !== $course) continue;
            $grade = session("academic.item_grades.$id.$student");
            if (!$grade) continue;
            $component = $item['component'] ?? ($item['type'] === 'kuis' ? 'kuis' : 'tugas');
            $max = !empty($item['questions']) ? array_sum(array_column($item['questions'], 'points')) : ($item['points'] ?? 100);
            $graded[$component][] = array_sum($grade['points']) / max(1,$max) * 100;
        }
        foreach ($graded as $component=>$values) $scores[$component] = round(array_sum($values)/count($values),2);

        return $scores;
    }

    public static function result(int $course, int $student = 1): array
    {
        $scores = self::scores($course,$student);
        $total = 0;
        $coverage = 0;
        foreach (self::config($course)['components'] as $component) {
            $score = $scores[$component['code']] ?? null;
            if ($score !== null && $score !== '') {
                $total += $score * $component['weight'] / 100;
                $coverage += $component['weight'];
            }
        }
        return ['scores'=>$scores,'total'=>$coverage > 0 ? round($total,2) : null,'average'=>$coverage > 0 ? round($total/$coverage*100,2) : null,'coverage'=>$coverage,'complete'=>abs($coverage-100)<0.001];
    }
}
