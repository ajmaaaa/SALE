<?php

namespace App\Support;

class AdminPreview
{
    public static function users(): array
    {
        return session('admin.users', [
            1 => ['id' => 1, 'name' => 'Ahmad Maulana', 'email' => 'ahmad@example.test', 'number' => '231011401234', 'role' => 'mahasiswa', 'status' => 'aktif'],
            2 => ['id' => 2, 'name' => 'Budi Santoso', 'email' => 'budi@example.test', 'number' => 'DSN001', 'role' => 'dosen', 'status' => 'aktif'],
            3 => ['id' => 3, 'name' => 'Admin Akademik', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'admin', 'status' => 'aktif'],
        ]);
    }

    public static function academic(): array
    {
        return session('admin.academic', [
            1 => ['id' => 1, 'type' => 'fakultas', 'code' => 'FIK', 'name' => 'Fakultas Ilmu Komputer', 'parent' => null, 'course' => null, 'students' => [], 'status' => 'aktif'],
            2 => ['id' => 2, 'type' => 'prodi', 'code' => 'IF', 'name' => 'Teknik Informatika', 'parent' => 1, 'course' => null, 'students' => [], 'status' => 'aktif'],
            3 => ['id' => 3, 'type' => 'semester', 'code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'parent' => null, 'course' => null, 'students' => [], 'status' => 'aktif'],
            4 => ['id' => 4, 'type' => 'kelas', 'code' => 'IF204-A', 'name' => 'Struktur Data · Kelas A', 'parent' => 2, 'course' => 1, 'students' => [1], 'status' => 'aktif'],
        ]);
    }

    public static function log(string $action): void
    {
        $logs = session('admin.logs', []);
        array_unshift($logs, ['time' => now()->format('d M Y, H:i:s'), 'actor' => 'Admin Akademik', 'action' => $action]);
        session(['admin.logs' => array_slice($logs, 0, 100)]);
    }
}
