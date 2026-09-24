<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\CourseDiscussion;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningPreview
{
    public static function courses(): array
    {
        $defaultCourses = [
            1 => ['id' => 1, 'code' => 'IF204', 'title' => 'Struktur Data dan Algoritma', 'lecturer' => 'Dr. Budi Santoso, M.Kom.', 'description' => 'Struktur data fundamental, analisis kompleksitas, dan penerapannya dalam penyelesaian masalah komputasi.', 'cover' => null, 'video' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'video_type' => 'url', 'video_title' => 'Video Pengantar: Struktur Data dan Algoritma (YouTube Link)'],
            2 => ['id' => 2, 'code' => 'IF218', 'title' => 'Interaksi Manusia dan Komputer', 'lecturer' => 'Dr. Ratna Prameswari, M.Ds.', 'description' => 'Merancang dan mengevaluasi antarmuka yang mudah digunakan melalui pendekatan berpusat pada pengguna.', 'cover' => null, 'video' => '00000000-0000-4000-8000-000000000001', 'video_type' => 'file', 'video_title' => 'Video Materi: Pengantar Antarmuka dan Usability (.mp4)'],
            3 => ['id' => 3, 'code' => 'IF221', 'title' => 'Kecerdasan Buatan Terapan', 'lecturer' => 'Prof. Nadia Rahman, Ph.D.', 'description' => 'Membangun model pembelajaran mesin dan memilih metode evaluasi yang sesuai.', 'cover' => null, 'video' => 'https://www.youtube.com/watch?v=aircAruvnKk', 'video_type' => 'url', 'video_title' => 'Video Pengantar: AI Terapan (YouTube Link)'],
            4 => ['id' => 4, 'code' => 'IF230', 'title' => 'Rekayasa Perangkat Lunak', 'lecturer' => 'Ir. Fajar Nugroho, M.T.', 'description' => 'Dari analisis kebutuhan sampai pengujian perangkat lunak dalam proyek tim.', 'cover' => null, 'video' => null],
        ];

        foreach (self::sampleFiles() as $sId => $sMeta) {
            if (Storage::disk('local')->exists($sMeta['path'])) {
                session(["learning.files.$sId" => $sMeta]);
            }
        }

        $sessionCourses = session('learning.courses');
        if (! is_array($sessionCourses)) {
            $courses = $defaultCourses;
        } else {
            $courses = $sessionCourses;
            if (! isset($courses[1]['video'])) {
                $courses[1]['video'] = $defaultCourses[1]['video'];
                $courses[1]['video_type'] = $defaultCourses[1]['video_type'];
                $courses[1]['video_title'] = $defaultCourses[1]['video_title'];
            }
            if (! isset($courses[2]['video'])) {
                $courses[2]['video'] = $defaultCourses[2]['video'];
                $courses[2]['video_type'] = $defaultCourses[2]['video_type'];
                $courses[2]['video_title'] = $defaultCourses[2]['video_title'];
            }
        }

        return $courses;
    }

    public static function defaultQuizQuestions(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'pilihan',
                'prompt' => 'Pada struktur Binary Search Tree (BST), jika suatu simpul memiliki nilai kunci 15, manakah pernyataan yang paling benar mengenai posisi simpul dengan nilai kunci 12 dan 18?',
                'options' => "Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan\nSimpul 12 dan 18 keduanya harus berada di subtree kiri\nSimpul 12 dan 18 keduanya harus berada di subtree kanan\nPosisi simpul 12 dan 18 ditentukan secara acak tanpa aturan",
                'correct_answer' => 'Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan',
                'points' => 15,
                'cpmk' => 'CPMK-01',
                'cpl' => 'CPL-01',
            ],
            [
                'id' => 2,
                'type' => 'kompleks',
                'prompt' => 'Pilihlah semua karakteristik dan properti yang berlaku pada Binary Search Tree (BST) yang seimbang (balanced):',
                'options' => "Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)\nKompleksitas pencarian rata-rata pada balanced BST adalah O(log n)\nSetiap simpul selalu memiliki tepat dua simpul anak (left dan right child)\nOperasi insertion tidak mengubah struktur simpul leluhur (ancestor)",
                'correct_answers' => [
                    'Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)',
                    'Kompleksitas pencarian rata-rata pada balanced BST adalah O(log n)',
                ],
                'points' => 15,
                'cpmk' => 'CPMK-01',
                'cpl' => 'CPL-01',
            ],
            [
                'id' => 3,
                'type' => 'benar_salah',
                'prompt' => 'Jika sebuah Binary Search Tree dibangun dari deretan angka yang sudah terurut sempurna [1, 2, 3, 4, 5, 6], maka pohon akan mengalami degenerasi (skewed) dengan tinggi pohon O(n) sehingga performa pencarian menurun setara linked list.',
                'options' => '',
                'correct_answer' => 'Benar',
                'points' => 15,
                'cpmk' => 'CPMK-01',
                'cpl' => 'CPL-01',
            ],
            [
                'id' => 4,
                'type' => 'mencocokkan',
                'prompt' => 'Jodohkan diagram struktur Binary Tree berikut dengan representasi urutan kunjungan simpul yang tepat:',
                'options' => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAxNDAgNzAiPjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHJ4PSI2IiBmaWxsPSIjZjhmYWZjIi8+PGNpcmNsZSBjeD0iNzAiIGN5PSIyMCIgcj0iMTIiIGZpbGw9IiMxMDJmNTAiLz48dGV4dCB4PSI3MCIgeT0iMjQiIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjkiIGZvbnQtd2VpZ2h0PSJib2xkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Ba2FyPC90ZXh0PjxsaW5lIHgxPSI2MCIgeTE9IjI4IiB4Mj0iNDAiIHkyPSI0NiIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48bGluZSB4MT0iODAiIHkxPSIyOCIgeDI9IjEwMCIgeTI9IjQ2IiBzdHJva2U9IjM5NGEzYjgiIHN0cm9rZS13aWR0aD0iMiIvPjxjaXJjbGUgY3g9IjM1IiBjeT0iNTIiIHI9IjEwIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iMzUiIHk9IjU1IiBmaWxsPSIjMzM0MTU1IiBmb250LXNpemU9IjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPktpcmk8L3RleHQ+PGNpcmNsZSBjeD0iMTA1IiBjeT0iNTIiIHI9IjEwIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iMTA1IiB5PSI1NSIgZmlsbD0iIzMzNDE1NSIgZm9udC1zaXplPSI4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5LYW5hbjwvdGV4dD48L3N2Zz4= = Pre-order: Kunjungan Akar → Kiri → Kanan
data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAxNDAgNzAiPjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHJ4PSI2IiBmaWxsPSIjZjhmYWZjIi8+PGNpcmNsZSBjeD0iMzUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiMxMDJmNTAiLz48dGV4dCB4PSIzNSIgeT0iNTUiIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjgiIGZvbnQtd2VpZ2h0PSJib2xkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5LaXJpPC90ZXh0PjxsaW5lIHgxPSI0MyIgeTE9IjQ1IiB4Mj0iNjMiIHkyPSIyNyIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjIwIiByPSIxMiIgZmlsbD0iI2UyZThmMCIvPjx0ZXh0IHg9IjcwIiB5PSIyNCIgZmlsbD0iIzMzNDE1NSIgZm9udC1zaXplPSI5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Ba2FyPC90ZXh0PjxsaW5lIHgxPSI3NyIgeTE9IjI3IiB4Mj0iOTciIHkyPSI0NSIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48Y2lyY2xlIGN4PSIxMDUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiNlMmU4ZjAiLz48dGV4dCB4PSIxMDUiIHk9IjU1IiBmaWxsPSIjMzM0MTU1IiBmb250LXNpemU9IjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkthbmFuPC90ZXh0Pjwvc3ZnPg== = In-order: Kunjungan Kiri → Akar → Kanan
data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAxNDAgNzAiPjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHJ4PSI2IiBmaWxsPSIjZjhmYWZjIi8+PGNpcmNsZSBjeD0iMzUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiNlMmU4ZjAiLz48dGV4dCB4PSIzNSIgeT0iNTUiIGZpbGw9IiMzMzQxNTUiIGZvbnQtc2l6ZT0iOCIgdGV4dC1hbmNob3I9Im1pZGRsZSI+S2lyaTwvdGV4dD48Y2lyY2xlIGN4PSIxMDUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiMxMDJmNTAiLz48dGV4dCB4PSIxMDUiIHk9IjU1IiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSI4IiBmb250LXdlaWdodD0iYm9sZCIgdGV4dC1hbmNob3I9Im1pZGRsZSI+S2FuYW48L3RleHQ+PGxpbmUgeDE9IjQzIiB5MT0iNDUiIHgyPSI2MyIgeTI9IjI3IiBzdHJva2U9IiM5NGEzYjgiIHN0cm9rZS13aWR0aD0iMiIvPjxsaW5lIHgxPSI5NyIgeTE9IjQ1IiB4Mj0iNzciIHkyPSIyNyIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjIwIiByPSIxMiIgZmlsbD0iI2UyZThmMCIvPjx0ZXh0IHg9IjcwIiB5PSIyNCIgZmlsbD0iIzMzNDE1NSIgZm9udC1zaXplPSI5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Ba2FyPC90ZXh0Pjwvc3ZnPg== = Post-order: Kunjungan Kiri → Kanan → Akar',
                'points' => 20,
                'cpmk' => 'CPMK-01',
                'cpl' => 'CPL-01',
            ],
            [
                'id' => 5,
                'type' => 'coding',
                'prompt' => 'Lengkapi implementasi metode insert(val) pada Binary Search Tree berikut agar simpul baru terpasang di cabang yang tepat:',
                'options' => "class Node:\n    def __init__(self, val):\n        self.val = val\n        self.left = None\n        self.right = None\n\nclass BST:\n    def __init__(self):\n        self.root = None\n\n    def insert(self, val):\n        # Implementasikan logika insert di sini\n        pass",
                'points' => 20,
                'cpmk' => 'CPMK-01',
                'cpl' => 'CPL-01',
            ],
            [
                'id' => 6,
                'type' => 'uraian',
                'prompt' => 'Jelaskan mengapa self-balancing binary search tree (seperti AVL Tree atau Red-Black Tree) dibutuhkan pada sistem komputasi skala besar dibandingkan BST standar tanpa penyeimbangan otomatis!',
                'options' => '',
                'points' => 15,
                'cpmk' => 'CPMK-01',
                'cpl' => 'CPL-01',
            ],
        ];
    }

    public static function items(): array
    {
        return session('learning.items', [
            1 => array_merge(self::item(1, 1, 'Tree dan traversal', 'coding', 'Praktikum Binary Tree', 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.', '2026-09-10T23:59'), [
                'points' => 100,
                'duration_enabled' => true,
                'duration_minutes' => 60,
                'questions' => self::defaultQuizQuestions(),
            ]),
            2 => array_merge(self::item(2, 1, 'Pengenalan struktur data', 'materi', 'Pengantar struktur data', 'Struktur data membantu mengatur informasi agar operasi pencarian dan perubahan dapat dilakukan secara efisien. Pelajari dokumen modul PDF terlampir dan cermati diagram alur traversal pohon biner di bawah ini.'), [
                'attachments' => ['00000000-0000-4000-8000-000000000002'],
                'question_image' => '00000000-0000-4000-8000-000000000003',
                'image_alt' => 'Diagram Pohon Biner & Traversal In-Order',
            ]),
            3 => self::item(3, 1, 'Tree dan traversal', 'materi', 'Memahami traversal pada Binary Tree', 'Inorder mengunjungi kiri, akar, lalu kanan. Preorder mengunjungi akar lebih dahulu; postorder mengunjungi akar terakhir. Gambarkan pohon dengan nilai 8, 3, 10, 1, 6 dan tentukan hasil setiap traversal.'),
            4 => array_merge(self::item(
                4,
                2,
                'Evaluasi usability',
                'tugas',
                'Laporan Evaluasi Usability',
                "Petunjuk dan Perintah Tugas:\n1. Lakukan pengujian usability pada purwarupa aplikasi dengan melibatkan minimal 5 responden mahasiswa.\n2. Terapkan instrumen evaluasi System Usability Scale (SUS) dan teknik think-aloud selama sesi pengujian skenario.\n3. Catat temuan kendala navigasi, tingkat penyelesaian tugas (task completion rate), dan waktu pengerjaan pada lembar observasi.\n4. Cocokkan titik permasalahan antarmuka dengan gambar stimulus observasi terlampir.\n5. Susun laporan analisis dalam format PDF terstruktur sesuai dengan panduan dokumen acuan PDF di bawah ini.",
                '2026-09-12T17:00'
            ), [
                'attachments' => ['00000000-0000-4000-8000-000000000004'],
                'question_image' => '00000000-0000-4000-8000-000000000005',
                'image_alt' => 'Lembar Observasi Aspek Usability Pengguna',
            ]),
            5 => array_merge(self::item(5, 3, 'Evaluasi model', 'kuis', 'Kuis Evaluasi Model', 'Sebuah model memprediksi semua pasien sehat pada dataset dengan 95% pasien sehat. Selesaikan serangkaian soal evaluasi model berikut untuk menguji pemahaman Anda.', '2026-09-14T20:00'), [
                'points' => 100,
                'duration_enabled' => true,
                'duration_minutes' => 45,
                'questions' => [
                    [
                        'id' => 1,
                        'type' => 'pilihan',
                        'prompt' => 'Metrik evaluasi mana yang paling tepat digunakan ketika dataset memiliki ketidakseimbangan kelas (imbalance class) ekstrem?',
                        'options' => "Akurasi (Accuracy)\nF1-Score dan ROC-AUC\nMean Squared Error (MSE)\nPerplexity Skor",
                        'correct_answer' => 'F1-Score dan ROC-AUC',
                        'points' => 25,
                        'cpmk' => 'CPMK-01',
                        'cpl' => 'CPL-01',
                    ],
                    [
                        'id' => 2,
                        'type' => 'benar_salah',
                        'prompt' => 'Akurasi sebesar 95% selalu menjamin bahwa model pembelajaran mesin bekerja optimal dalam memprediksi kelas minoritas.',
                        'options' => '',
                        'correct_answer' => 'Salah',
                        'points' => 20,
                        'cpmk' => 'CPMK-01',
                        'cpl' => 'CPL-01',
                    ],
                    [
                        'id' => 3,
                        'type' => 'mencocokkan',
                        'prompt' => 'Jodohkan istilah metrik evaluasi klasifikasi di sebelah kiri dengan formula / karakteristik yang tepat di sebelah kanan:',
                        'options' => "Precision = True Positive / (True Positive + False Positive)\nRecall = True Positive / (True Positive + False Negative)\nF1-Score = Rata-rata harmonis antara Precision dan Recall\nSpesifisitas = True Negative / (True Negative + False Positive)",
                        'points' => 30,
                        'cpmk' => 'CPMK-02',
                        'cpl' => 'CPL-02',
                    ],
                    [
                        'id' => 4,
                        'type' => 'uraian',
                        'prompt' => 'Jelaskan konsep trade-off antara Precision dan Recall dalam konteks sistem pendeteksi fraud perbankan. Mengapa kita memprioritaskan Recall tinggi?',
                        'options' => '',
                        'points' => 25,
                        'cpmk' => 'CPMK-02',
                        'cpl' => 'CPL-02',
                    ],
                ],
            ]),
            6 => self::item(6, 1, 'Informasi kelas', 'pengumuman', 'Perubahan ruang perkuliahan', 'Pertemuan Kamis dipindahkan ke Lab Komputasi 2 pada pukul 10.00.'),
        ]);
    }

    private static function item(int $id, int $course, string $module, string $type, string $title, string $body, ?string $due = null): array
    {
        return compact('id', 'course', 'module', 'type', 'title', 'body', 'due') + [
            'attachments' => [],
            'link' => null,
            'formats' => ['file', 'image', 'link', 'text'],
            'question_type' => 'uraian',
            'language' => 'python',
            'cpmk' => 'Mampu menganalisis dan menerapkan konsep pada permasalahan yang diberikan.',
            'allow_late' => true,
            'duration_enabled' => false,
            'duration_minutes' => 60,
        ];
    }

    public static function course(int $id): array
    {
        $courses = self::courses();
        if (isset($courses[$id])) {
            return $courses[$id];
        }

        if (Schema::hasTable('class_sections')) {
            $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->find($id);
            if ($section) {
                return self::databaseCourse($section);
            }
        }

        abort(404);
    }

    public static function databaseCourse(ClassSection $section): array
    {
        $customVideo = session("learning.course_video.{$section->id}");
        if (! $customVideo && $section->relationLoaded('assessments')) {
            $pinned = $section->assessments
                ->where('type', 'materi')
                ->filter(fn ($asm) => ! empty($asm->learning_payload['pin_video']))
                ->last();
            if ($pinned && ! empty($pinned->learning_payload['video'])) {
                $customVideo = [
                    'video' => $pinned->learning_payload['video'],
                    'video_type' => $pinned->learning_payload['video_type'] ?? 'url',
                    'video_title' => $pinned->learning_payload['video_title'] ?? $pinned->name,
                ];
            }
        }
        $defaultVideo = null;
        $defaultVideoType = 'url';
        $defaultVideoTitle = null;

        // Keep the demo media from the last pushed version available on
        // database-backed sample classes unless a lecturer pins a replacement.
        if (app()->environment(['local', 'testing'])) {
            $courseCode = strtoupper((string) $section->mataKuliah?->code);

            if ($courseCode === 'IF204') {
                $defaultVideo = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';
                $defaultVideoTitle = 'Video Pengantar: Struktur Data dan Algoritma (YouTube)';
            } elseif ($courseCode === 'IF218' && Storage::disk('local')->exists('testing/big-buck-bunny-720p-10s.mp4')) {
                $defaultVideo = '00000000-0000-4000-8000-000000000001';
                $defaultVideoType = 'file';
                $defaultVideoTitle = 'Video Materi Perkuliahan (.mp4)';
            }
        }

        return [
            'id' => $section->id,
            'code' => $section->display_code,
            'title' => $section->mataKuliah->name,
            'lecturer' => $section->dosen?->name ?? 'Belum ditetapkan',
            'dosen_ketua' => $section->dosen?->name ?? 'Belum ditetapkan',
            'dosen_wakil' => $section->dosenPendamping?->name,
            'description' => 'Perkuliahan '.$section->mataKuliah->name.' kelas '.$section->section_code.' semester '.($section->semester?->name ?? 'aktif').'.',
            'cover' => null,
            'video' => $customVideo['video'] ?? $defaultVideo,
            'video_type' => $customVideo['video_type'] ?? $defaultVideoType,
            'video_title' => $customVideo['video_title'] ?? $defaultVideoTitle,
            'sks' => ($section->mataKuliah->sks ?? 0).' SKS',
            'semester' => $section->semester?->name ?? 'Semester aktif',
            'section_code' => $section->section_code,
        ];
    }

    public static function databaseAssessment(Assessment $assessment): array
    {
        $payload = $assessment->learning_payload ?? [];
        $body = $payload['body']
            ?? $assessment->description
            ?? 'Asesmen '.$assessment->name.' dengan bobot '.$assessment->final_weight.'% terhadap nilai akhir.';
        $due = $assessment->due_at?->format('Y-m-d\TH:i');
        $type = match ($assessment->type) {
            'pbl', 'case', 'project', 'proyek' => 'tugas',
            default => $assessment->type,
        };

        if (in_array($type, ['kuis', 'uts', 'uas'], true) && empty($payload['questions'])) {
            $payload['questions'] = self::defaultQuizQuestions();
            $payload['duration_enabled'] = true;
            $payload['duration_minutes'] = $payload['duration_minutes'] ?? 60;
            $payload['points'] = 100;
        }

        return array_merge(self::item(
            $assessment->id,
            $assessment->class_section_id,
            $payload['module'] ?? ($type === 'materi' ? 'Materi Perkuliahan' : 'Asesmen & Tugas'),
            $type,
            $assessment->name,
            $body,
            $due
        ), $payload, [
            'description' => $body,
            'body' => $body,
            'points' => $payload['points'] ?? 100,
            'due' => $due,
            'allow_late' => (bool) $assessment->allow_late,
        ]);
    }

    public static function youtubeEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host);
        $videoId = null;

        if (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
            if (($parts['path'] ?? '') === '/watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $videoId = $query['v'] ?? null;
            } elseif (preg_match('#^/(?:embed|shorts)/([A-Za-z0-9_-]{6,})#', (string) ($parts['path'] ?? ''), $matches)) {
                $videoId = $matches[1];
            }
        } elseif ($host === 'youtu.be') {
            $videoId = trim((string) ($parts['path'] ?? ''), '/');
        }

        return $videoId && preg_match('/^[A-Za-z0-9_-]{6,}$/', $videoId)
            ? 'https://www.youtube-nocookie.com/embed/'.$videoId.'?rel=0'
            : null;
    }

    public static function resource(int $course, int $item): array
    {
        self::course($course);
        $resource = self::items()[$item] ?? null;
        abort_unless($resource && $resource['course'] === $course, 404);

        return $resource;
    }

    public static function courseDiscussions(int $course): array
    {
        if (Schema::hasTable('course_discussions')
            && Schema::hasTable('class_sections')
            && ClassSection::whereKey($course)->exists()) {
            $dbDiscussions = CourseDiscussion::where('class_section_id', $course)
                ->orderBy('created_at', 'asc')
                ->get();

            return $dbDiscussions->map(function ($row) {
                return [
                    'id' => $row->id,
                    'author' => $row->author_name,
                    'sender_key' => $row->sender_key ?? ($row->user_id ? 'user:'.$row->user_id : null),
                    'message' => $row->message,
                    'time' => $row->created_at->format('d M, H:i'),
                    'timestamp' => $row->created_at->timestamp,
                    'date_key' => $row->created_at->toDateString(),
                    'date_label' => $row->created_at->isToday() ? 'Hari ini' : ($row->created_at->isYesterday() ? 'Kemarin' : $row->created_at->translatedFormat('d F Y')),
                    'role' => $row->role,
                ];
            })->all();
        }

        $examples = [
            1 => [
                ['author' => 'Budi Santoso, M.Kom.', 'message' => 'Selamat datang di perkuliahan Struktur Data dan Algoritma. Silakan ajukan pertanyaan seputar materi atau praktikum kuis di forum kelas ini.', 'time' => '10 Sep, 08:00', 'timestamp' => 1788915600, 'role' => 'dosen'],
                ['author' => 'Ahmad Maulana', 'message' => 'Pak, untuk praktikum Binary Tree apakah implementasi delete node juga akan diuji pada kuis akhir nanti?', 'time' => '11 Sep, 14:20', 'timestamp' => 1789024800, 'role' => 'mahasiswa'],
                ['author' => 'Budi Santoso, M.Kom.', 'message' => 'Untuk evaluasi modul ini fokus utama pada operasi dasar insertion dan traversal terlebih dahulu.', 'time' => '11 Sep, 15:05', 'timestamp' => 1789027500, 'role' => 'dosen'],
            ],
            2 => [
                ['author' => 'Prof. Dr. Ir. Rian Saputra, S.T., M.Kom.', 'message' => 'Forum diskusi kelas Interaksi Manusia dan Komputer telah dibuka. Anda dapat berdiskusi mengenai prinsip evaluasi usability dan desain antarmuka di sini.', 'time' => '09 Sep, 09:15', 'timestamp' => 1788832500, 'role' => 'dosen'],
                ['author' => 'Siti Aminah', 'message' => 'Prof, untuk laporan usability testing apakah jumlah partisipan minimal 5 orang?', 'time' => '11 Sep, 11:30', 'timestamp' => 1789014600, 'role' => 'mahasiswa'],
            ],
            3 => [
                ['author' => 'Dr. Maya Kartika, M.Cs.', 'message' => 'Selamat belajar di kelas Pembelajaran Mesin. Silakan berdiskusi mengenai metrik evaluasi model (Confusion Matrix, ROC-AUC) di ruang kelas ini.', 'time' => '08 Sep, 10:00', 'timestamp' => 1788748800, 'role' => 'dosen'],
            ],
        ];

        return session("learning.course_discussions.$course", $examples[$course] ?? []);
    }

    public static function unreadDiscussionCount(?int $course = null): int
    {
        return count(self::unreadDiscussions($course));
    }

    public static function unreadDiscussions(?int $course = null): array
    {
        $user = auth()->user();
        if ($course !== null) {
            $courses = [$course => self::course($course)];
        } elseif ($user && Schema::hasTable('class_sections')) {
            $isDosen = $user->hasRole(Role::DOSEN);
            $sections = $isDosen
                ? ClassSection::where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id)->with('mataKuliah')->get()
                : $user->classSectionsEnrolled()->with('mataKuliah')->get();
            $courses = [];
            foreach ($sections as $sec) {
                $courses[$sec->id] = self::databaseCourse($sec);
            }
        } else {
            $courses = self::courses();
        }
        $viewer = self::discussionViewer();
        $messages = [];

        foreach ($courses as $courseId => $courseData) {
            $courseMessages = self::courseDiscussions((int) $courseId);
            $read = (int) session("learning.discussion_reads.$courseId", 0);

            foreach (array_slice($courseMessages, $read) as $message) {
                $isOwnMessage = isset($message['sender_key'])
                    ? hash_equals($viewer['key'], (string) $message['sender_key'])
                    : trim((string) ($message['author'] ?? '')) === $viewer['name'];

                if ($isOwnMessage) {
                    continue;
                }

                $messages[] = $message + [
                    'course' => (int) $courseId,
                    'course_title' => $courseData['title'],
                    'timestamp' => $message['timestamp'] ?? 0,
                ];
            }
        }

        usort($messages, fn ($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        return $messages;
    }

    public static function pendingTaskCount(): int
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        if ($user && Schema::hasTable('class_sections') && Schema::hasTable('assessments')) {
            $sectionIds = $user->classSectionsEnrolled()->pluck('class_sections.id');
            if ($sectionIds->isNotEmpty()) {
                $scoredIds = [];
                if (Schema::hasTable('student_assessment_scores')) {
                    $scoredIds = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                        ->whereNotNull('score')
                        ->pluck('assessment_id')
                        ->toArray();
                }

                return Assessment::whereIn('class_section_id', $sectionIds)
                    ->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'])
                    ->where('status', 'published')
                    ->get()
                    ->reject(function ($asm) use ($scoredIds) {
                        return in_array($asm->id, $scoredIds, true)
                            || session("learning.submissions.{$asm->id}") !== null
                            || session("learning.grades.{$asm->id}") !== null
                            || session("academic.item_grades.{$asm->id}.1") !== null;
                    })
                    ->count();
            }
        }

        return collect(self::items())
            ->filter(fn ($item) => in_array($item['type'] ?? '', ['tugas', 'coding', 'kuis', 'uts', 'uas'], true))
            ->reject(fn ($item) => session("learning.submissions.{$item['id']}") !== null || session("learning.grades.{$item['id']}") !== null)
            ->count();
    }

    public static function notifications(?User $user = null): array
    {
        if (! $user) {
            $user = auth()->user();
            if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
                $sessionUser = session('auth_user');
                $user = User::where('email', $sessionUser['email'] ?? '')
                    ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                    ->first();
            }
        }

        $readNotifs = session('learning.read_notifications', []);
        $allRead = in_array('all', $readNotifs, true);
        $notifications = [];
        $courses = [];
        $items = [];
        $studentScores = [];

        if ($user && Schema::hasTable('student_assessment_scores')) {
            $studentScores = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                ->get()
                ->keyBy('assessment_id');
        }

        if ($user && Schema::hasTable('class_sections')) {
            $isDosen = $user->hasRole(Role::DOSEN);
            $sections = $isDosen
                ? ClassSection::where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id)->with(['mataKuliah', 'dosen'])->get()
                : $user->classSectionsEnrolled()->with(['mataKuliah', 'dosen'])->get();

            foreach ($sections as $sec) {
                $courses[$sec->id] = [
                    'id' => $sec->id,
                    'code' => $sec->display_code,
                    'title' => $sec->mataKuliah?->name ?? 'Mata Kuliah',
                    'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                ];

                if (Schema::hasTable('assessments')) {
                    $assessments = Assessment::where('class_section_id', $sec->id)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', '!=', Assessment::STATUS_DRAFT);
                        })->get();

                    foreach ($assessments as $asm) {
                        $items[$asm->id] = [
                            'id' => $asm->id,
                            'course' => $sec->id,
                            'title' => $asm->name,
                            'module' => $asm->code,
                            'type' => in_array($asm->type, ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project']) ? (in_array($asm->type, ['pbl', 'project']) ? 'tugas' : $asm->type) : 'tugas',
                            'due' => $asm->due_at?->format('Y-m-d H:i:s') ?? '',
                            'points' => 100,
                        ];
                    }
                }

                $sessionItems = array_filter(self::items(), fn ($i) => ($i['course'] ?? null) === $sec->id);
                foreach ($sessionItems as $sItem) {
                    if (! isset($items[$sItem['id']])) {
                        $items[$sItem['id']] = $sItem;
                    }
                }
            }
        }

        if (empty($courses)) {
            $courses = self::courses();
        }
        if (empty($items)) {
            $items = self::items();
        }

        foreach ($items as $item) {
            $id = $item['id'];
            $courseId = $item['course'] ?? 1;
            $courseTitle = $courses[$courseId]['title'] ?? 'Mata Kuliah';
            $courseCode = $courses[$courseId]['code'] ?? 'MK';
            $type = $item['type'] ?? 'tugas';

            if (! in_array($type, ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case'])) {
                continue;
            }

            $hasDbScore = isset($studentScores[$id]) && $studentScores[$id]->score !== null;
            $sessionGrade = session("learning.grades.{$id}") ?? session("academic.item_grades.{$id}.1");
            $isGraded = $hasDbScore || $sessionGrade !== null;
            $scoreVal = $hasDbScore ? (float) $studentScores[$id]->score : ($sessionGrade !== null ? (is_array($sessionGrade) ? array_sum($sessionGrade['points'] ?? []) : (float) $sessionGrade) : null);
            $isSubmitted = session("learning.submissions.{$id}") || $isGraded;

            $targetUrl = ($type === 'coding')
                ? route('mahasiswa.assignment.code', $id)
                : route('mahasiswa.course.item', [$courseId, $id]);

            if ($isGraded) {
                $notifKey = "grade_{$id}";
                $scoreTime = 'Terbit baru saja';
                $scoreTimestamp = now()->timestamp - 100;
                $feedbackMsg = '';

                if ($hasDbScore && $studentScores[$id]->updated_at) {
                    $scoreTime = $studentScores[$id]->updated_at->diffForHumans();
                    $scoreTimestamp = $studentScores[$id]->updated_at->timestamp;
                    if (! empty($studentScores[$id]->feedback)) {
                        $feedbackMsg = ' Catatan dosen: "'.Str::limit($studentScores[$id]->feedback, 80).'"';
                    }
                }

                $notifications[] = [
                    'id' => $notifKey,
                    'title' => "Nilai Terbit: {$item['title']} ({$courseCode})",
                    'message' => 'Hasil evaluasi pengerjaan Anda telah dinilai oleh dosen pengampu dengan perolehan nilai '.number_format($scoreVal, 0).'/100.'.$feedbackMsg,
                    'time' => $scoreTime,
                    'timestamp' => $scoreTimestamp,
                    'icon_type' => 'check',
                    'link' => $targetUrl,
                    'action_label' => 'Lihat Hasil Nilai',
                    'category' => 'nilai',
                    'is_read' => $allRead || in_array($notifKey, $readNotifs, true),
                ];
            } elseif ($isSubmitted) {
                $notifKey = "submit_{$id}";
                $notifications[] = [
                    'id' => $notifKey,
                    'title' => "Jawaban Terkirim: {$item['title']} ({$courseCode})",
                    'message' => 'Berkas pengerjaan Anda berhasil diunggah ke sistem dan sedang menunggu proses penilaian dosen.',
                    'time' => session("learning.submissions.{$id}.time", 'Hari ini'),
                    'timestamp' => now()->timestamp - 200,
                    'icon_type' => 'check',
                    'link' => $targetUrl,
                    'action_label' => 'Lihat Detail Submission',
                    'category' => 'tugas',
                    'is_read' => $allRead || in_array($notifKey, $readNotifs, true),
                ];
            } else {
                $notifKey = "pending_{$id}";
                $isQuiz = in_array($type, ['kuis', 'uts', 'uas']);
                $hasDue = ! empty($item['due']);
                $dueCarbon = $hasDue ? Carbon::parse($item['due']) : null;
                $dueText = $dueCarbon ? $dueCarbon->translatedFormat('d M Y, H:i') : 'Tanpa batas tenggat';
                $timeText = $dueCarbon ? ($dueCarbon->isPast() ? 'Lewat tenggat' : $dueCarbon->diffForHumans()) : 'Aktif';

                $notifications[] = [
                    'id' => $notifKey,
                    'title' => ($isQuiz ? 'Kuis Tersedia: ' : 'Penugasan: ')."{$item['title']} ({$courseCode})",
                    'message' => "Mata Kuliah {$courseTitle}. Batas tenggat: {$dueText}. Pastikan mempelajari materi pendukung sebelum mengerjakan.",
                    'time' => $timeText,
                    'timestamp' => $dueCarbon ? $dueCarbon->timestamp : now()->timestamp,
                    'icon_type' => 'alert',
                    'link' => $targetUrl,
                    'action_label' => $isQuiz ? 'Mulai Kerjakan Kuis' : 'Buka Lembar Tugas',
                    'category' => 'tugas',
                    'is_read' => $allRead || in_array($notifKey, $readNotifs, true),
                ];
            }
        }

        foreach ($courses as $cId => $cMeta) {
            $discussions = self::courseDiscussions($cId);
            $unreadCount = self::unreadDiscussionCount($cId);
            if ($unreadCount > 0 && ! empty($discussions)) {
                $lastMsg = end($discussions);
                $notifKey = "discuss_{$cId}_{$lastMsg['timestamp']}";
                $notifications[] = [
                    'id' => $notifKey,
                    'title' => "Diskusi Baru: {$cMeta['code']} - {$cMeta['title']}",
                    'message' => "{$lastMsg['author']}: \"".Str::limit($lastMsg['message'], 100).'"',
                    'time' => $lastMsg['time'] ?? 'Baru saja',
                    'timestamp' => $lastMsg['timestamp'] ?? now()->timestamp,
                    'icon_type' => 'chat',
                    'link' => route('mahasiswa.course.show', $cId).'#diskusi-kelas',
                    'action_label' => 'Buka Forum Diskusi',
                    'category' => 'diskusi',
                    'is_read' => $allRead || in_array($notifKey, $readNotifs, true),
                ];
            }
        }

        usort($notifications, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $notifications;
    }

    public static function unreadNotificationCount(?User $user = null): int
    {
        return count(array_filter(self::notifications($user), fn ($n) => empty($n['is_read'])));
    }

    private static function discussionViewer(): array
    {
        $user = auth()->user();
        $sessionUser = session('auth_user', []);
        $role = $user?->role?->name ?? ($sessionUser['role'] ?? 'mahasiswa');
        $name = trim((string) ($user?->name ?? ($sessionUser['name'] ?? 'Ahmad Maulana')));
        $identifier = $user?->getAuthIdentifier()
            ?? ($sessionUser['id'] ?? $sessionUser['number'] ?? $sessionUser['email'] ?? 1);

        return [
            'key' => $user ? 'user:'.$identifier : 'preview:'.$role.':'.$identifier,
            'name' => $name,
        ];
    }

    public static function discussions(int $item): array
    {
        $itemData = self::items()[$item] ?? null;
        if ($itemData && ! empty($itemData['course'])) {
            return self::courseDiscussions($itemData['course']);
        }

        return session("learning.discussions.$item", []);
    }

    public static function recentDiscussions(): array
    {
        $messages = [];
        foreach (self::courses() as $course) {
            foreach (self::courseDiscussions($course['id']) as $message) {
                $messages[] = $message + [
                    'course' => $course['id'],
                    'course_title' => $course['title'],
                    'timestamp' => $message['timestamp'] ?? 0,
                ];
            }
        }
        usort($messages, fn ($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        return array_slice($messages, 0, 3);
    }

    public static function labels(): array
    {
        return [
            'materi' => 'Materi',
            'tugas' => 'Tugas',
            'coding' => 'Tugas coding',
            'kuis' => 'Kuis',
            'uts' => 'UTS',
            'uas' => 'UAS',
            'proyek' => 'Proyek PJBL',
            'pbl' => 'PBL',
            'case' => 'Studi Kasus',
            'pengumuman' => 'Pengumuman',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function label(?string $type): string
    {
        if (! $type) {
            return 'Konten';
        }

        $normalized = mb_strtolower($type);
        if ($normalized === 'uts') {
            return 'UTS';
        }
        if ($normalized === 'uas') {
            return 'UAS';
        }

        return self::labels()[$type] ?? self::labels()[$normalized] ?? $type;
    }

    public static function sampleFiles(): array
    {
        return [
            '00000000-0000-4000-8000-000000000001' => [
                'path' => 'testing/big-buck-bunny-720p-10s.mp4',
                'name' => 'video-pembelajaran-kuliah.mp4',
                'mime' => 'video/mp4',
            ],
            '00000000-0000-4000-8000-000000000002' => [
                'path' => 'testing/sample-materi-struktur-data.pdf',
                'name' => 'Modul-01-Pengantar-Struktur-Data.pdf',
                'mime' => 'application/pdf',
            ],
            '00000000-0000-4000-8000-000000000003' => [
                'path' => 'testing/sample-diagram-tree.png',
                'name' => 'diagram-pohon-biner-dan-traversal.png',
                'mime' => 'image/png',
            ],
            '00000000-0000-4000-8000-000000000004' => [
                'path' => 'testing/sample-panduan-tugas-usability.pdf',
                'name' => 'Panduan-Evaluasi-Usability-Partisipan.pdf',
                'mime' => 'application/pdf',
            ],
            '00000000-0000-4000-8000-000000000005' => [
                'path' => 'testing/sample-wireframe-usability.png',
                'name' => 'lembar-observasi-antarmuka-usability.png',
                'mime' => 'image/png',
            ],
        ];
    }

    public static function fileMeta(string $file): ?array
    {
        $sessionMeta = session("learning.files.{$file}");
        if ($sessionMeta) {
            return $sessionMeta;
        }

        $samples = self::sampleFiles();
        if (isset($samples[$file])) {
            return $samples[$file];
        }

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'learning_payload')) {
            $assessment = Assessment::query()
                ->whereNotNull('learning_payload')
                ->get(['learning_payload'])
                ->first(fn ($item) => isset(($item->learning_payload['file_meta'] ?? [])[$file]));

            if ($assessment) {
                return $assessment->learning_payload['file_meta'][$file];
            }
        }

        return null;
    }
}
