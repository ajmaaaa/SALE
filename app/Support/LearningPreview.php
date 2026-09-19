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
            1 => array_merge(self::item(1, 1, 'Tree dan traversal', 'coding', 'Praktikum Binary Tree', 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.', '2026-09-10T23:59'), [
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
                        'options' => "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAxNDAgNzAiPjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHJ4PSI2IiBmaWxsPSIjZjhmYWZjIi8+PGNpcmNsZSBjeD0iNzAiIGN5PSIyMCIgcj0iMTIiIGZpbGw9IiMxMDJmNTAiLz48dGV4dCB4PSI3MCIgeT0iMjQiIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjkiIGZvbnQtd2VpZ2h0PSJib2xkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Ba2FyPC90ZXh0PjxsaW5lIHgxPSI2MCIgeTE9IjI4IiB4Mj0iNDAiIHkyPSI0NiIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48bGluZSB4MT0iODAiIHkxPSIyOCIgeDI9IjEwMCIgeTI9IjQ2IiBzdHJva2U9IiM5NGEzYjgiIHN0cm9rZS13aWR0aD0iMiIvPjxjaXJjbGUgY3g9IjM1IiBjeT0iNTIiIHI9IjEwIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iMzUiIHk9IjU1IiBmaWxsPSIjMzM0MTU1IiBmb250LXNpemU9IjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPktpcmk8L3RleHQ+PGNpcmNsZSBjeD0iMTA1IiBjeT0iNTIiIHI9IjEwIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iMTA1IiB5PSI1NSIgZmlsbD0iIzMzNDE1NSIgZm9udC1zaXplPSI4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5LYW5hbjwvdGV4dD48L3N2Zz4= = Pre-order: Kunjungan Akar → Kiri → Kanan
data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAxNDAgNzAiPjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHJ4PSI2IiBmaWxsPSIjZjhmYWZjIi8+PGNpcmNsZSBjeD0iMzUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiMxMDJmNTAiLz48dGV4dCB4PSIzNSIgeT0iNTUiIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjgiIGZvbnQtd2VpZ2h0PSJib2xkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5LaXJpPC90ZXh0PjxsaW5lIHgxPSI0MyIgeTE9IjQ1IiB4Mj0iNjMiIHkyPSIyNyIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjIwIiByPSIxMiIgZmlsbD0iI2UyZThmMCIvPjx0ZXh0IHg9IjcwIiB5PSIyNCIgZmlsbD0iIzMzNDE1NSIgZm9udC1zaXplPSI5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Ba2FyPC90ZXh0PjxsaW5lIHgxPSI3NyIgeTE9IjI3IiB4Mj0iOTciIHkyPSI0NSIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48Y2lyY2xlIGN4PSIxMDUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiNlMmU4ZjAiLz48dGV4dCB4PSIxMDUiIHk9IjU1IiBmaWxsPSIjMzM0MTU1IiBmb250LXNpemU9IjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkthbmFuPC90ZXh0Pjwvc3ZnPg== = In-order: Kunjungan Kiri → Akar → Kanan
data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAxNDAgNzAiPjxyZWN0IHdpZHRoPSIxNDAiIGhlaWdodD0iNzAiIHJ4PSI2IiBmaWxsPSIjZjhmYWZjIi8+PGNpcmNsZSBjeD0iMzUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiNlMmU4ZjAiLz48dGV4dCB4PSIzNSIgeT0iNTUiIGZpbGw9IiMzMzQxNTUiIGZvbnQtc2l6ZT0iOCIgdGV4dC1hbmNob3I9Im1pZGRsZSI+S2lyaTwvdGV4dD48Y2lyY2xlIGN4PSIxMDUiIGN5PSI1MiIgcj0iMTAiIGZpbGw9IiMxMDJmNTAiLz48dGV4dCB4PSIxMDUiIHk9IjU1IiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSI4IiBmb250LXdlaWdodD0iYm9sZCIgdGV4dC1hbmNob3I9Im1pZGRsZSI+S2FuYW48L3RleHQ+PGxpbmUgeDE9IjQzIiB5MT0iNDUiIHgyPSI2MyIgeTI9IjI3IiBzdHJva2U9IiM5NGEzYjgiIHN0cm9rZS13aWR0aD0iMiIvPjxsaW5lIHgxPSI5NyIgeTE9IjQ1IiB4Mj0iNzciIHkyPSIyNyIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiLz48Y2lyY2xlIGN4PSI3MCIgY3k9IjIwIiByPSIxMiIgZmlsbD0iI2UyZThmMCIvPjx0ZXh0IHg9IjcwIiB5PSIyNCIgZmlsbD0iIzMzNDE1NSIgZm9udC1zaXplPSI5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Ba2FyPC90ZXh0Pjwvc3ZnPg== = Post-order: Kunjungan Kiri → Kanan → Akar",
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
            2 => self::item(2, 1, 'Pengenalan struktur data', 'materi', 'Pengantar struktur data', 'Struktur data membantu mengatur informasi agar operasi pencarian dan perubahan dapat dilakukan secara efisien. Bandingkan array dan linked list berdasarkan akses, penyisipan, serta penggunaan memori.'),
            3 => self::item(3, 1, 'Tree dan traversal', 'materi', 'Memahami traversal pada Binary Tree', 'Inorder mengunjungi kiri, akar, lalu kanan. Preorder mengunjungi akar lebih dahulu; postorder mengunjungi akar terakhir. Gambarkan pohon dengan nilai 8, 3, 10, 1, 6 dan tentukan hasil setiap traversal.'),
            4 => self::item(4, 2, 'Evaluasi usability', 'tugas', 'Laporan Evaluasi Usability', 'Evaluasi satu aplikasi menggunakan lima partisipan. Sertakan skenario pengujian, temuan, bukti gambar, dan rekomendasi perbaikan.', '2026-09-12T17:00'),
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
            'cpmk' => 'Mampu menganalisis dan menerapkan konsep pada permasalahan yang diberikan.',
            'allow_late' => true,
            'duration_enabled' => false,
            'duration_minutes' => 60,
        ];
    }

    public static function course(int $id): array
    {
        abort_unless(isset(self::courses()[$id]), 404);

        return self::courses()[$id];
    }

    public static function resource(int $course, int $item): array
    {
        self::course($course);
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
        return [
            'materi' => 'Materi',
            'tugas' => 'Tugas',
            'coding' => 'Tugas coding',
            'kuis' => 'Kuis',
            'lainnya' => 'Lainnya',
            'pengumuman' => 'Pengumuman',
        ];
    }

    public static function label(?string $type): string
    {
        if (!$type) return 'Konten';
        if (in_array(mb_strtolower($type), ['uts'])) return 'UTS';
        if (in_array(mb_strtolower($type), ['uas'])) return 'UAS';
        $labels = self::labels();
        if (isset($labels[$type])) return $labels[$type];
        $lower = mb_strtolower($type);
        if (isset($labels[$lower])) return $labels[$lower];
        return $type;
    }
}
