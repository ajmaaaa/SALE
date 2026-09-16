<?php

namespace App\Support;

class AdminPreview
{
    public static function users(): array
    {
        return session('admin.users', [
            1 => ['id' => 1, 'name' => 'Ahmad Maulana', 'email' => 'ahmad.maulana@student.test', 'number' => '231011401234', 'role' => 'mahasiswa', 'status' => 'aktif'],
            2 => ['id' => 2, 'name' => 'Budi Santoso, M.Kom.', 'email' => 'budi@example.test', 'number' => '198501012010121001', 'role' => 'dosen', 'status' => 'aktif'],
            3 => ['id' => 3, 'name' => 'Admin Sistem Akademik', 'email' => 'admin@example.test', 'number' => 'ADM001', 'role' => 'admin', 'status' => 'aktif'],
            4 => ['id' => 4, 'name' => 'Admin Prodi TI', 'email' => 'adminprodi@example.test', 'number' => 'AP001', 'role' => 'admin_prodi', 'status' => 'aktif'],
            5 => ['id' => 5, 'name' => 'Dr. H. Kaprodi, M.T.', 'email' => 'kaprodi@example.test', 'number' => '197501012000031001', 'role' => 'kaprodi', 'status' => 'aktif'],
        ]);
    }

    public static function academic(): array
    {
        $default = [
            1 => ['id' => 1, 'type' => 'fakultas', 'code' => 'FIK', 'name' => 'Fakultas Ilmu Komputer', 'parent' => null, 'course' => null, 'students' => [], 'status' => 'aktif'],
            2 => ['id' => 2, 'type' => 'prodi', 'code' => 'IF', 'name' => 'Teknik Informatika', 'parent' => 1, 'course' => null, 'students' => [], 'status' => 'aktif'],
            3 => ['id' => 3, 'type' => 'semester', 'code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'parent' => null, 'course' => null, 'students' => [], 'status' => 'aktif'],
        ];

        $academic = session('admin.academic', $default);
        $filtered = array_filter($academic, fn ($record) => in_array($record['type'] ?? '', ['fakultas', 'prodi', 'semester']));
        if (count($filtered) !== count($academic)) {
            session(['admin.academic' => $filtered]);
        }

        return $filtered;
    }

    public static function log(string $action): void
    {
        $logs = session('admin.logs', []);
        array_unshift($logs, ['time' => now()->format('d M Y, H:i:s'), 'actor' => 'Admin Akademik', 'action' => $action]);
        session(['admin.logs' => array_slice($logs, 0, 100)]);
    }
}
