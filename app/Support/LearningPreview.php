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
            1 => self::item(1, 1, 'Tree dan traversal', 'coding', 'Praktikum Binary Tree', 'Lengkapi metode insert() pada Binary Search Tree. Jelaskan penanganan cabang kiri, kanan, dan nilai duplikat.', '2026-09-10T23:59'),
            2 => self::item(2, 1, 'Pengenalan struktur data', 'materi', 'Pengantar struktur data', 'Struktur data membantu mengatur informasi agar operasi pencarian dan perubahan dapat dilakukan secara efisien. Bandingkan array dan linked list berdasarkan akses, penyisipan, serta penggunaan memori.'),
            3 => self::item(3, 1, 'Tree dan traversal', 'materi', 'Memahami traversal pada Binary Tree', 'Inorder mengunjungi kiri, akar, lalu kanan. Preorder mengunjungi akar lebih dahulu; postorder mengunjungi akar terakhir. Gambarkan pohon dengan nilai 8, 3, 10, 1, 6 dan tentukan hasil setiap traversal.'),
            4 => self::item(4, 2, 'Evaluasi usability', 'tugas', 'Laporan Evaluasi Usability', 'Evaluasi satu aplikasi menggunakan lima partisipan. Sertakan skenario pengujian, temuan, bukti gambar, dan rekomendasi perbaikan.', '2026-09-12T17:00'),
            5 => self::item(5, 3, 'Evaluasi model', 'kuis', 'Kuis Evaluasi Model', 'Sebuah model memprediksi semua pasien sehat pada dataset dengan 95% pasien sehat. Apakah akurasi cukup untuk menilai model? Jelaskan pilihan metrik Anda.', '2026-09-14T20:00'),
            6 => self::item(6, 1, 'Informasi kelas', 'pengumuman', 'Perubahan ruang perkuliahan', 'Pertemuan Kamis dipindahkan ke Lab Komputasi 2 pada pukul 10.00.'),
        ]);
    }

    private static function item(int $id, int $course, string $module, string $type, string $title, string $body, ?string $due = null): array
    {
        return compact('id', 'course', 'module', 'type', 'title', 'body', 'due') + ['attachments' => [], 'link' => null, 'formats' => ['file', 'image', 'link', 'text'], 'question_type' => 'uraian', 'cpmk' => 'Mampu menganalisis dan menerapkan konsep pada permasalahan yang diberikan.'];
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

    public static function labels(): array
    {
        return ['materi' => 'Materi', 'tugas' => 'Tugas', 'coding' => 'Tugas coding', 'kuis' => 'Kuis', 'pengumuman' => 'Pengumuman'];
    }
}
