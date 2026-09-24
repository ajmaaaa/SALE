<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\CourseDiscussion;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds a small, realistic set of academic data on top of the OBE schema:
 * one prodi, one active semester, several mata kuliah mirroring the names
 * already used in LearningPreview, lecturers, class sections, and students.
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

        $mataKuliah = [
            ['code' => 'IF204', 'name' => 'Struktur Data dan Algoritma', 'sks' => 3, 'lecturer' => 'budi@example.test'],
            ['code' => 'IF230', 'name' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'lecturer' => 'budi@example.test'],
            ['code' => 'IF218', 'name' => 'Interaksi Manusia dan Komputer', 'sks' => 3, 'lecturer' => 'ratna.prameswari@example.test'],
            ['code' => 'IF221', 'name' => 'Kecerdasan Buatan Terapan', 'sks' => 3, 'lecturer' => 'nadia.rahman@example.test'],
            ['code' => 'IF240', 'name' => 'Basis Data Lanjut', 'sks' => 3, 'lecturer' => 'andi.wicaksono@example.test'],
            ['code' => 'IF250', 'name' => 'Jaringan Komputer', 'sks' => 3, 'lecturer' => 'fajar.nugroho@example.test'],
            ['code' => 'IF260', 'name' => 'Pemrograman Web', 'sks' => 3, 'lecturer' => 'sari.lestari@example.test'],
        ];

        $dosen = User::where('email', 'budi@example.test')->first();
        $dosen?->update(['prodi_id' => $prodi->id]);

        $lecturerData = [
            ['name' => 'Dr. Ratna Prameswari, M.Ds.', 'email' => 'ratna.prameswari@example.test', 'nidn' => '198602142012042001'],
            ['name' => 'Prof. Nadia Rahman, Ph.D.', 'email' => 'nadia.rahman@example.test', 'nidn' => '197904032005012002'],
            ['name' => 'Dr. Andi Wicaksono, M.Kom.', 'email' => 'andi.wicaksono@example.test', 'nidn' => '198711202015041003'],
            ['name' => 'Ir. Fajar Nugroho, M.T.', 'email' => 'fajar.nugroho@example.test', 'nidn' => '198305102010121004'],
            ['name' => 'Sari Lestari, S.Kom., M.Cs.', 'email' => 'sari.lestari@example.test', 'nidn' => '199001182018032005'],
        ];
        $lecturers = collect($lecturerData)->mapWithKeys(function (array $lecturer) use ($dosenRole, $prodi) {
            $user = User::updateOrCreate(
                ['email' => $lecturer['email']],
                [
                    'name' => $lecturer['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $dosenRole->id,
                    'prodi_id' => $prodi->id,
                    'nim_nidn' => $lecturer['nidn'],
                    'email_verified_at' => now(),
                ]
            );

            return [$lecturer['email'] => $user];
        });
        if ($dosen) {
            $lecturers->put($dosen->email, $dosen);
        }

        $sections = [];
        $adminOnlyCodes = ['IF218', 'IF221', 'IF240', 'IF250', 'IF260'];
        foreach ($mataKuliah as $mk) {
            $lecturer = $lecturers->get($mk['lecturer']) ?? $dosen;
            $isAdminOnly = in_array($mk['code'], $adminOnlyCodes, true);
            $model = MataKuliah::updateOrCreate(
                ['code' => $mk['code']],
                ['prodi_id' => $prodi->id, 'name' => $mk['name'], 'sks' => $mk['sks']]
            );

            if ($isAdminOnly) {
                // Course tambahan ini hanya ada di Admin Prodi, tidak dibuatkan kelas,
                // sehingga tidak otomatis masuk ke mahasiswa maupun dosen.
                ClassSection::where('mata_kuliah_id', $model->id)->delete();
                continue;
            }

            foreach (['A', 'B'] as $sectionCode) {
                // Only seed section B for the first mata kuliah, to show
                // a lecturer teaching more than one class in the list.
                if ($sectionCode === 'B' && $mk['code'] !== 'IF204') {
                    continue;
                }

                $sec = ClassSection::firstOrCreate(
                    [
                        'mata_kuliah_id' => $model->id,
                        'semester_id' => $semester->id,
                        'section_code' => $sectionCode,
                    ],
                    [
                        'dosen_id' => $lecturer?->id,
                        'dosen_pendamping_id' => null,
                        'capacity' => 40,
                        'enrollment_code' => ClassSection::generateUniqueEnrollmentCode(),
                    ]
                );

                if (! $sec->dosen_id && $lecturer) {
                    $sec->update(['dosen_id' => $lecturer->id]);
                }
                if (empty($sec->enrollment_code)) {
                    $sec->update(['enrollment_code' => ClassSection::generateUniqueEnrollmentCode()]);
                }

                $sections[] = $sec;
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
            $section->students()->syncWithoutDetaching(collect($students)->pluck('id'));
        }

        if (Schema::hasTable('course_discussions')) {
            $firstSection = $sections[0] ?? null;
            if ($firstSection && CourseDiscussion::where('class_section_id', $firstSection->id)->count() === 0) {
                CourseDiscussion::create([
                    'class_section_id' => $firstSection->id,
                    'user_id' => $dosen?->id,
                    'author_name' => $dosen?->name ?? 'Dr. Budi Santoso, M.Kom.',
                    'role' => 'dosen',
                    'sender_key' => $dosen ? 'user:'.$dosen->id : null,
                    'message' => 'Selamat datang di perkuliahan '.$firstSection->mataKuliah->name.'. Silakan ajukan pertanyaan seputar materi atau praktikum kuis di forum kelas ini.',
                    'created_at' => now()->subDays(2),
                ]);
                $firstStudent = $students[0] ?? null;
                if ($firstStudent) {
                    CourseDiscussion::create([
                        'class_section_id' => $firstSection->id,
                        'user_id' => $firstStudent->id,
                        'author_name' => $firstStudent->name,
                        'role' => 'mahasiswa',
                        'sender_key' => 'user:'.$firstStudent->id,
                        'message' => 'Pak, untuk praktikum Binary Tree apakah implementasi delete node juga akan diuji pada kuis akhir nanti?',
                        'created_at' => now()->subDay(),
                    ]);
                }
            }
        }

        $this->command?->info('Data akademik contoh dibuat: 1 prodi, 1 semester aktif, '.count($sections).' kelas, '.count($lecturers).' dosen, dan '.count($students).' mahasiswa.');
    }
}
