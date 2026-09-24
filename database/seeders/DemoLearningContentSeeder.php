<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ClassSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoLearningContentSeeder extends Seeder
{
    private const MATERIAL_PDF = '00000000-0000-4000-8000-000000000002';

    private const MATERIAL_IMAGE = '00000000-0000-4000-8000-000000000003';

    private const TASK_PDF = '00000000-0000-4000-8000-000000000004';

    private const TASK_IMAGE = '00000000-0000-4000-8000-000000000005';

    public function run(): void
    {
        $this->createSampleFiles();

        $sections = ClassSection::with('mataKuliah')->get();
        $if204 = $sections->first(fn (ClassSection $section) => $section->mataKuliah?->code === 'IF204' && $section->section_code === 'A');

        if ($if204) {
            Assessment::where('code', 'MATERI-DEMO')->where('class_section_id', '!=', $if204->id)->delete();
            Assessment::updateOrCreate(
                ['class_section_id' => $if204->id, 'code' => 'MATERI-DEMO'],
                [
                    'name' => 'Materi Pendukung: '.$if204->mataKuliah->name,
                    'type' => 'materi',
                    'description' => 'Pelajari modul PDF dan gambar rangkuman sebelum mengerjakan latihan pada course ini.',
                    'learning_payload' => [
                        'module' => 'Minggu 1 · Materi Pendukung',
                        'body' => 'Materi ini sudah tersimpan di database dan dilengkapi PDF serta gambar pendukung yang dapat dibuka langsung.',
                        'attachments' => [self::MATERIAL_PDF],
                        'question_image' => self::MATERIAL_IMAGE,
                        'image_alt' => 'Diagram ringkasan alur pembelajaran dan struktur konsep',
                        'formats' => [],
                        'points' => 0,
                    ],
                    'final_weight' => 0,
                    'uses_rubric' => false,
                    'status' => Assessment::STATUS_PUBLISHED,
                    'due_at' => null,
                    'allow_late' => true,
                ]
            );
            Assessment::updateOrCreate(
                ['class_section_id' => $if204->id, 'code' => 'TGS-LAMPIRAN'],
                [
                    'name' => 'Tugas Evaluasi Struktur Data dengan Lampiran',
                    'type' => 'tugas',
                    'description' => 'Gunakan panduan PDF dan gambar studi kasus untuk menyusun laporan evaluasi.',
                    'learning_payload' => [
                        'module' => 'Minggu 2 · Tugas Terlambat',
                        'body' => "Petunjuk dan Perintah Tugas:\n1. Pelajari spesifikasi studi kasus dan kriteria evaluasi struktur data pada dokumen panduan PDF terlampir.\n2. Analisis performa serta kompleksitas ruang dan waktu dari algoritma traversal pohon biner pada diagram stimulus gambar.\n3. Susun laporan analisis teknis yang mencakup perbandingan efisiensi algoritma dan rekomendasi implementasi terbaik.\n4. Tugas ini memiliki tenggat yang sudah lewat untuk menguji status penyerahan terlambat. Unduh PDF panduan dan amati gambar pendukung sebelum mengumpulkan jawaban.",
                        'attachments' => [self::TASK_PDF],
                        'question_image' => self::TASK_IMAGE,
                        'image_alt' => 'Lembar observasi dan wireframe pendukung tugas',
                        'formats' => ['file', 'image', 'link', 'text'],
                        'points' => 100,
                        'question_type' => 'uraian',
                    ],
                    'final_weight' => 0,
                    'uses_rubric' => false,
                    'status' => Assessment::STATUS_PUBLISHED,
                    'due_at' => now()->subDays(2)->setTime(23, 59),
                    'allow_late' => true,
                ]
            );
            Assessment::updateOrCreate(
                ['class_section_id' => $if204->id, 'code' => 'KUIS-01'],
                [
                    'name' => 'Kuis 1: Struktur Data & Tree',
                    'type' => 'kuis',
                    'description' => 'Kuis komprehensif 6 variasi tipe soal untuk menguji pemahaman struktur pohon biner dan traversal.',
                    'learning_payload' => [
                        'module' => 'Minggu 3 · Evaluasi Pemahaman',
                        'body' => 'Kerjakan 6 butir soal berikut secara mandiri dengan teliti.',
                        'attachments' => [],
                        'formats' => ['cbt'],
                        'points' => 100,
                        'duration_enabled' => true,
                        'duration_minutes' => 60,
                        'questions' => \App\Support\LearningPreview::defaultQuizQuestions(),
                    ],
                    'final_weight' => 10,
                    'uses_rubric' => false,
                    'status' => Assessment::STATUS_PUBLISHED,
                    'due_at' => now()->addDays(3)->setTime(23, 59),
                    'allow_late' => false,
                ]
            );
        }

        $this->command?->info('Materi database, tugas terlambat, PDF, dan gambar contoh berhasil dibuat.');
    }

    private function createSampleFiles(): void
    {
        Storage::disk('local')->makeDirectory('testing');
        Storage::disk('local')->put(
            'testing/sample-materi-struktur-data.pdf',
            $this->simplePdf('Modul Materi Pendukung', [
                'Ringkasan konsep pembelajaran untuk course SALE.',
                'Pelajari diagram, contoh penerapan, dan langkah latihan.',
                'Dokumen ini dibuat otomatis oleh seeder demo.',
            ])
        );
        Storage::disk('local')->put(
            'testing/sample-panduan-tugas-usability.pdf',
            $this->simplePdf('Panduan Tugas dengan Lampiran', [
                '1. Baca studi kasus dan identifikasi kebutuhan utama.',
                '2. Gunakan gambar observasi sebagai data pendukung.',
                '3. Kumpulkan analisis dalam bentuk laporan terstruktur.',
            ])
        );

        $this->createDiagramPng(
            Storage::disk('local')->path('testing/sample-diagram-tree.png'),
            'Peta Konsep Materi',
            ['Konsep Dasar', 'Contoh Penerapan', 'Latihan Mandiri']
        );
        $this->createDiagramPng(
            Storage::disk('local')->path('testing/sample-wireframe-usability.png'),
            'Lembar Observasi Tugas',
            ['Temuan Pengguna', 'Bukti Pendukung', 'Rekomendasi']
        );
    }

    private function createDiagramPng(string $path, string $title, array $labels): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $image = imagecreatetruecolor(800, 450);
        $background = imagecolorallocate($image, 245, 248, 252);
        $navy = imagecolorallocate($image, 16, 47, 80);
        $blue = imagecolorallocate($image, 37, 99, 235);
        $white = imagecolorallocate($image, 255, 255, 255);
        $line = imagecolorallocate($image, 184, 199, 216);
        imagefill($image, 0, 0, $background);
        imagefilledrectangle($image, 0, 0, 800, 72, $navy);
        imagestring($image, 5, 28, 27, $title, $white);

        foreach ($labels as $index => $label) {
            $x = 48 + ($index * 250);
            imagefilledrectangle($image, $x, 170, $x + 204, 290, $white);
            imagerectangle($image, $x, 170, $x + 204, 290, $line);
            imagefilledellipse($image, $x + 102, 205, 42, 42, $blue);
            imagestring($image, 5, $x + 96, 197, (string) ($index + 1), $white);
            imagestring($image, 4, $x + 18, 250, $label, $navy);
            if ($index < count($labels) - 1) {
                imageline($image, $x + 204, 230, $x + 250, 230, $blue);
            }
        }

        imagestring($image, 3, 48, 380, 'SALE - Sistem Akademik dan Learning Environment', $navy);
        imagepng($image, $path);
        imagedestroy($image);
    }

    private function simplePdf(string $title, array $lines): string
    {
        $escape = fn (string $text) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $commands = ['BT', '/F1 20 Tf', '60 770 Td', '('.$escape($title).') Tj', '/F1 12 Tf'];
        foreach ($lines as $line) {
            $commands[] = '0 -34 Td';
            $commands[] = '('.$escape($line).') Tj';
        }
        $commands[] = 'ET';
        $stream = implode("\n", $commands);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
