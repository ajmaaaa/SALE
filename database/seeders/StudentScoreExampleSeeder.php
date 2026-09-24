<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\StudentAssessmentScore;
use Illuminate\Database\Seeder;

/**
 * Seeds example scores for IF204-A only, so the recap/matrix pages have
 * real numbers to calculate against. UAS is deliberately left ungraded
 * for every student to demonstrate that "not yet graded" is preserved
 * as NULL rather than defaulting to 0 anywhere in the pipeline.
 */
class StudentScoreExampleSeeder extends Seeder
{
    public function run(): void
    {
        $section = ClassSection::whereHas('mataKuliah', fn ($q) => $q->where('code', 'IF204'))
            ->where('section_code', 'A')
            ->first();

        if (! $section) {
            $this->command?->warn('Kelas IF204-A belum ada. Jalankan AcademicDemoSeeder dan ObeExampleSeeder terlebih dahulu.');

            return;
        }

        $students = $section->students()->orderBy('name')->get();
        $assessments = Assessment::where('class_section_id', $section->id)->get()->keyBy('code');

        if ($students->isEmpty() || $assessments->isEmpty()) {
            $this->command?->warn('Data mahasiswa/asesmen IF204-A belum lengkap.');

            return;
        }

        // A simple, deterministic score pattern per student index so
        // results are easy to verify by hand, with a bit of variation.
        $baseScores = [85, 78, 92, 66, 74];

        foreach ($students as $index => $student) {
            $base = $baseScores[$index % count($baseScores)];

            foreach (['TGS-01', 'TGS-02', 'KUIS-01', 'PBL-01', 'UTS'] as $code) {
                if (! isset($assessments[$code])) {
                    continue;
                }

                $variation = match ($code) {
                    'TGS-01' => 2,
                    'TGS-02' => -3,
                    'KUIS-01' => 5,
                    'PBL-01' => -1,
                    'UTS' => -4,
                    default => 0,
                };

                $feedback = match ($code) {
                    'TGS-01' => 'Implementasi algoritma dan kode pengujian sudah sangat baik.',
                    'TGS-02' => 'Analisis kompleksitas tepat, pertahankan kerapian struktur koding.',
                    'KUIS-01' => 'Pemahaman konsep dasar binary tree sangat matang.',
                    'PBL-01' => 'Solusi kasus terapan kreatif dengan dokumentasi jelas.',
                    'UTS' => 'Pencapaian CPMK evaluasi tengah semester memuaskan.',
                    default => null,
                };

                StudentAssessmentScore::updateOrCreate(
                    ['assessment_id' => $assessments[$code]->id, 'mahasiswa_id' => $student->id],
                    [
                        'score' => min(100, max(0, $base + $variation)),
                        'feedback' => $feedback,
                        'graded_at' => now(),
                    ]
                );
            }

            // UAS intentionally left ungraded (no row / NULL score) for
            // every student, to demonstrate the not-graded-yet state.
        }

        $this->command?->info('Nilai contoh dibuat untuk IF204-A (UAS sengaja dibiarkan belum dinilai).');
    }
}
