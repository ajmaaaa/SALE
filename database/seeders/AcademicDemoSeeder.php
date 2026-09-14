<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a small, realistic set of academic data on top of the OBE schema:
 * one prodi, one active semester, a few mata kuliah mirroring the names
 * already used in LearningPreview (for a consistent feel), two class
 * sections taught by the sample Dosen account, and a handful of enrolled
 * mahasiswa.
 *
 * This is dev/demo data only — it does not touch or replace
 * LearningPreview/AdminPreview, which remain the source of truth for the
 * non-OBE course/material/assignment preview flows.
 */
class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $dosenRole = Role::where('name', Role::DOSEN)->first();
        $mahasiswaRole = Role::where('name', Role::MAHASISWA)->first();

        if (! $dosenRole || ! $mahasiswaRole) {
            $this->command?->warn('Role belum lengkap. Jalankan RoleSeeder terlebih dahulu.');

            return;
        }

        $prodi = Prodi::updateOrCreate(
            ['code' => 'IF'],
            ['name' => 'Teknik Informatika']
        );

        $semester = Semester::updateOrCreate(
            ['code' => '2026-1'],
            ['name' => 'Ganjil 2026/2027', 'is_active' => true]
        );

        $dosen = User::where('email', 'budi@example.test')->first();

        if (! $dosen) {
            $this->command?->warn('Akun Dosen contoh belum ada. Jalankan DosenAccountSeeder terlebih dahulu.');

            return;
        }

        $mataKuliah = [
            ['code' => 'IF204', 'name' => 'Struktur Data dan Algoritma', 'sks' => 3],
            ['code' => 'IF230', 'name' => 'Rekayasa Perangkat Lunak', 'sks' => 3],
        ];

        $sections = [];
        foreach ($mataKuliah as $mk) {
            $model = MataKuliah::updateOrCreate(
                ['code' => $mk['code']],
                ['prodi_id' => $prodi->id, 'name' => $mk['name'], 'sks' => $mk['sks']]
            );

            foreach (['A', 'B'] as $sectionCode) {
                // Only seed section B for the first mata kuliah, to show
                // a lecturer teaching more than one class in the list.
                if ($sectionCode === 'B' && $mk['code'] !== 'IF204') {
                    continue;
                }

                $sections[] = ClassSection::updateOrCreate(
                    [
                        'mata_kuliah_id' => $model->id,
                        'semester_id' => $semester->id,
                        'section_code' => $sectionCode,
                    ],
                    [
                        'dosen_id' => $dosen->id,
                        'capacity' => 40,
                    ]
                );
            }
        }

        $studentNames = [
            ['name' => 'Ahmad Maulana', 'nim' => '231011401234'],
            ['name' => 'Siti Nurhaliza', 'nim' => '231011401235'],
            ['name' => 'Rizky Pratama', 'nim' => '231011401236'],
            ['name' => 'Dewi Anggraini', 'nim' => '231011401237'],
            ['name' => 'Fajar Ramadhan', 'nim' => '231011401238'],
        ];

        $students = [];
        foreach ($studentNames as $s) {
            $students[] = User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $s['name'])).'@student.test'],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $mahasiswaRole->id,
                    'nim_nidn' => $s['nim'],
                    'email_verified_at' => now(),
                ]
            );
        }

        foreach ($sections as $section) {
            // Enroll all sample students in every seeded section for
            // simplicity; adjust once real enrollment rules exist.
            $section->students()->syncWithoutDetaching(collect($students)->pluck('id'));
        }

        $this->command?->info('Data akademik contoh dibuat: 1 prodi, 1 semester aktif, '.count($sections).' kelas, '.count($students).' mahasiswa.');
    }
}
