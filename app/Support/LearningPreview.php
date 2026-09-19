<?php

namespace App\Support;

class LearningPreview
{
    public static function courses(): array
    {
        return session('learning.courses', [
            1 => ['id' => 1, 'code' => 'IF204', 'title' => 'Struktur Data dan Algoritma', 'lecturer' => 'Dr. Budi Santoso, M.Kom.', 'description' => 'Struktur data fundamental, analisis kompleksitas, dan penerapannya dalam penyelesaian masalah komputasi.', 'cover' => null, 'video' => null],
            2 => ['id' => 2, 'code' => 'IF218', 'title' => 'Interaksi Manusia dan Komputer', 'lecturer' => 'Dr. Ratna Prameswari, M.Ds.', 'description' => 'Merancang dan mengevaluasi antarmuka yang mudah digunakan melalui pendekatan berpusat pada pengguna.', 'cover' => null, 'video' => null],
            3 => ['id' => 3, 'code' => 'IF221', 'title' => 'Kecerdasan Buatan Terapan', 'lecturer' => 'Prof. Nadia Rahman, Ph.D.', 'description' => 'Membangun model pembelajaran mesin dan memilih metode evaluasi yang sesuai.', 'cover' => null, 'video' => null],
            4 => ['id' => 4, 'code' => 'IF230', 'title' => 'Rekayasa Perangkat Lunak', 'lecturer' => 'Ir. Fajar Nugroho, M.T.', 'description' => 'Dari analisis kebutuhan sampai pengujian perangkat lunak dalam proyek tim.', 'cover' => null, 'video' => null],
        ]);
    }

    public static function items(): array
    {
        return session('learning.items', [
            1 => array_merge(self::item(1, 1, 'Tree dan traversal', 'coding', 'Praktikum Binary Tree', 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.', '2026-09-10T23:59', 'A'), [
                'points' => 100,
                'duration_enabled' => true,
                'duration_minutes' => 60,
                'questions' => [
                    [
                        'id' => 1,
                        'type' => 'pilihan',
                        'prompt' => 'Pada struktur Binary Search Tree (BST), jika suatu simpul memiliki nilai kunci 15, manakah pernyataan yang paling benar mengenai posisi simpul dengan nilai kunci 12 dan 18?',
                        'options' => "Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan\nSimpul 12 dan 18 keduanya harus berada di subtree kiri\nSimpul 12 dan 18 keduanya harus berada di subtree kanan\nPosisi simpul 12 dan 18 ditentukan secara acak tanpa aturan",
                        'points' => 15,
                        'cpmk' => 'CPMK-01',
                        'cpl' => 'CPL-01',
                    ],
                    [
                        'id' => 2,
                        'type' => 'kompleks',
                        'prompt' => 'Pilihlah semua karakteristik dan properti yang berlaku pada Binary Search Tree (BST) yang seimbang (balanced):',
                        'options' => "Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)\nKompleksitas pencarian rata-rata pada balanced BST adalah O(log n)\nSetiap simpul selalu memiliki tepat dua simpul anak (left dan right child)\nOperasi insertion tidak mengubah struktur simpul leluhur (ancestor)",
                        'points' => 15,
                        'cpmk' => 'CPMK-01',
                        'cpl' => 'CPL-01',
                    ],
                    [
                        'id' => 3,
                        'type' => 'benar_salah',
                        'prompt' => 'Jika sebuah Binary Search Tree dibangun dari deretan angka yang sudah terurut sempurna [1, 2, 3, 4, 5, 6], maka pohon akan mengalami degenerasi (skewed) dengan tinggi pohon O(n) sehingga performa pencarian menurun setara linked list.',
                        'options' => '',
                        'points' => 15,
                        'cpmk' => 'CPMK-01',
                        'cpl' => 'CPL-01',
                    ],
                    [
                        'id' => 4,
                        'type' => 'mencocokkan',
                        'prompt' => 'Jodohkan diagram struktur Binary Tree berikut dengan representasi urutan kunjungan simpul yang tepat:',
                        'options' => "Option A = Pre-order: Kunjungan Akar -> Kiri -> Kanan\nOption B = In-order: Kunjungan Kiri -> Akar -> Kanan\nOption C = Post-order: Kunjungan Kiri -> Kanan -> Akar",
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
                ],
            ]),
            2 => self::item(2, 1, 'Pengenalan struktur data', 'materi', 'Pengantar struktur data', 'Struktur data membantu mengatur informasi agar operasi pencarian dan perubahan dapat dilakukan secara efisien. Bandingkan array dan linked list berdasarkan akses, penyisipan, serta penggunaan memori.', null, 'A'),
            3 => self::item(3, 1, 'Tree dan traversal', 'materi', 'Memahami traversal pada Binary Tree (Kelas A)', 'Inorder mengunjungi kiri, akar, lalu kanan. Preorder mengunjungi akar lebih dahulu; postorder mengunjungi akar terakhir. Gambarkan pohon dengan nilai 8, 3, 10, 1, 6 dan tentukan hasil setiap traversal.', null, 'A'),
            4 => self::item(4, 2, 'Evaluasi usability', 'tugas', 'Laporan Evaluasi Usability (Kelas A)', 'Evaluasi satu aplikasi menggunakan lima partisipan. Sertakan skenario pengujian, temuan, bukti gambar, dan rekomendasi perbaikan.', '2026-09-12T17:00', 'A'),
            5 => array_merge(self::item(5, 3, 'Evaluasi model', 'kuis', 'Kuis Evaluasi Model (Kelas A)', 'Sebuah model memprediksi semua pasien sehat pada dataset dengan 95% pasien sehat. Selesaikan serangkaian soal evaluasi model berikut untuk menguji pemahaman Anda.', '2026-09-14T20:00', 'A'), [
                'points' => 100,
                'duration_enabled' => true,
                'duration_minutes' => 45,
                'questions' => [
                    [
                        'id' => 1,
                        'type' => 'pilihan',
                        'prompt' => 'Metrik evaluasi mana yang paling tepat digunakan ketika dataset memiliki ketidakseimbangan kelas (imbalance class) ekstrem?',
                        'options' => "Akurasi (Accuracy)\nF1-Score dan ROC-AUC\nMean Squared Error (MSE)\nPerplexity Skor",
                        'points' => 25,
                        'cpmk' => 'CPMK 1',
                        'cpl' => 'CPL 2',
                    ],
                    [
                        'id' => 2,
                        'type' => 'benar_salah',
                        'prompt' => 'Akurasi sebesar 95% selalu menjamin bahwa model pembelajaran mesin bekerja optimal dalam memprediksi kelas minoritas.',
                        'options' => '',
                        'points' => 20,
                        'cpmk' => 'CPMK 1',
                        'cpl' => 'CPL 2',
                    ],
                    [
                        'id' => 3,
                        'type' => 'mencocokkan',
                        'prompt' => 'Jodohkan istilah metrik evaluasi klasifikasi di sebelah kiri dengan formula / karakteristik yang tepat di sebelah kanan:',
                        'options' => "Precision = True Positive / (True Positive + False Positive)\nRecall = True Positive / (True Positive + False Negative)\nF1-Score = Rata-rata harmonis antara Precision dan Recall\nSpesifisitas = True Negative / (True Negative + False Positive)",
                        'points' => 30,
                        'cpmk' => 'CPMK 2',
                        'cpl' => 'CPL 3',
                    ],
                    [
                        'id' => 4,
                        'type' => 'uraian',
                        'prompt' => 'Jelaskan konsep trade-off antara Precision dan Recall dalam konteks sistem pendeteksi fraud perbankan. Mengapa kita memprioritaskan Recall tinggi?',
                        'options' => '',
                        'points' => 25,
                        'cpmk' => 'CPMK 2',
                        'cpl' => 'CPL 3',
                    ],
                ],
            ]),
            6 => self::item(6, 1, 'Informasi kelas', 'pengumuman', 'Perubahan ruang perkuliahan Kelas A', 'Pertemuan Kamis dipindahkan ke Lab Komputasi 2 pada pukul 10.00.', null, 'A'),

            // Section B mock items
            7 => self::item(7, 1, 'Graf dan Implementasi', 'coding', 'Tugas Coding Graph Traversal (Kelas B)', 'Implementasikan algoritma BFS dan DFS pada Graph berarah. Buat fungsi shortPath() untuk menemukan jalur terpendek.', '2026-09-18T23:59', 'B'),
            8 => self::item(8, 1, 'Graf dan Implementasi', 'materi', 'Modul Deep-Dive Algorithm Graph (Kelas B)', 'Penjelasan detail mengenai Adjacency Matrix vs Adjacency List beserta analisis ruang memori untuk Kelas B Paralel.', null, 'B'),
            9 => self::item(9, 1, 'Informasi kelas', 'pengumuman', 'Pengumuman Kuliah Lapangan Kelas B', 'Mahasiswa Kelas B Paralel diwajibkan hadir pada sesi praktikum mandiri Sabtu mendatang.', null, 'B'),
            10 => self::item(10, 2, 'Design System & Prototyping', 'tugas', 'Studi Kasus UI/UX Redesign App (Kelas B)', 'Buat prototype Figma resolusi tinggi untuk aplikasi e-commerce beserta pengujian usability pada 3 responden.', '2026-09-20T23:59', 'B'),
            11 => self::item(11, 3, 'Deep Learning Frameworks', 'kuis', 'Kuis Deep Learning & Neural Network (Kelas B)', 'Evaluasi pemahaman arsitektur Convolutional Neural Network (CNN) dan fungsi aktivasi ReLU/Sigmoid.', '2026-09-22T20:00', 'B'),
            12 => self::item(12, 4, 'Agile & Scrum Process', 'tugas', 'Sprint Planning & User Stories (Kelas B)', 'Menyusun backlog produk, menentukan story points, dan membuat burndown chart untuk proyek kelompok.', '2026-09-25T17:00', 'B'),
        ]);
    }

    private static function item(int $id, int $course, string $module, string $type, string $title, string $body, ?string $due = null, string $section = 'A'): array
    {
        return compact('id', 'course', 'module', 'type', 'title', 'body', 'due', 'section') + [
            'attachments' => [],
            'link' => null,
            'formats' => ['file', 'image', 'link', 'text'],
            'question_type' => 'uraian',
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

        if (\Illuminate\Support\Facades\Schema::hasTable('class_sections')) {
            $sec = \App\Models\ClassSection::with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])->find($id);
            if ($sec) {
                $dosenKetua = $sec->dosen?->name ?? 'Dosen Pengampu';

                return [
                    'id' => $sec->id,
                    'code' => $sec->display_code,
                    'title' => $sec->mataKuliah->name,
                    'lecturer' => $dosenKetua,
                    'dosen_ketua' => $dosenKetua,
                    'dosen_wakil' => $sec->dosenPendamping?->name ?? null,
                    'description' => 'Perkuliahan ' . $sec->mataKuliah->name . ' (' . $sec->mataKuliah->code . ') kelas ' . $sec->section_code . ' semester ' . ($sec->semester->name ?? 'aktif') . '.',
                    'cover' => null,
                    'video' => null,
                    'sks' => ($sec->mataKuliah->sks ?? 3) . ' SKS',
                    'section_code' => $sec->section_code,
                ];
            }
        }

        abort(404);
    }

    public static function resource(int $course, int $item): array
    {
        self::course($course);

        if (\Illuminate\Support\Facades\Schema::hasTable('assessments')) {
            $asm = \App\Models\Assessment::with(['classSection', 'cpmks'])
                ->where('class_section_id', $course)
                ->find($item);

            if ($asm) {
                $cpmkNames = $asm->cpmks->pluck('code')->implode(', ');
                $bodyText = 'Asesmen perkuliahan ' . $asm->name . ' dengan bobot ' . $asm->final_weight . '% terhadap nilai akhir. Silakan kumpulkan hasil pekerjaan sesuai instruksi yang diberikan.';

                return self::item(
                    $asm->id,
                    $course,
                    'Asesmen Perkuliahan',
                    'tugas',
                    $asm->name,
                    $bodyText,
                    null,
                    $asm->classSection?->section_code ?? 'A'
                ) + [
                    'description' => $bodyText,
                    'cpmk' => $cpmkNames ?: 'CPMK Terkait',
                    'points' => 100,
                ];
            }
        }

        $resource = self::items()[$item] ?? null;

        abort_unless($resource && $resource['course'] === $course, 404);

        return $resource;
    }

    public static function discussions(int $item): array
    {
        $examples = [
            1 => [['author' => 'Budi Santoso', 'message' => 'Jika nilai yang dimasukkan sama dengan simpul induk, bagaimana sebaiknya kita menanganinya?', 'time' => 'Contoh percakapan', 'timestamp' => 1]],
            4 => [['author' => 'Siti Aminah', 'message' => 'Bolehkah hasil pengujian usability dilengkapi rekaman layar?', 'time' => 'Contoh percakapan', 'timestamp' => 2]],
            5 => [['author' => 'Raka Putra', 'message' => 'Kapan recall lebih tepat digunakan dibanding akurasi?', 'time' => 'Contoh percakapan', 'timestamp' => 3]],
        ];

        return session("learning.discussions.$item", $examples[$item] ?? []);
    }

    public static function recentDiscussions(): array
    {
        $messages = [];
        foreach (self::items() as $item) {
            foreach (self::discussions($item['id']) as $message) {
                $messages[] = $message + ['item' => $item['id'], 'course' => $item['course'], 'course_title' => self::course($item['course'])['title'], 'timestamp' => 0];
            }
        }
        usort($messages, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($messages, 0, 3);
    }

    public static function labels(): array
    {
        return ['materi' => 'Materi', 'tugas' => 'Tugas', 'coding' => 'Tugas coding', 'kuis' => 'Kuis', 'pengumuman' => 'Pengumuman'];
    }
}
