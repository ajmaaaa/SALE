<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Semester;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\StudentRubricScore;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RpsSimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $dosenRole = Role::firstOrCreate(['name' => Role::DOSEN], ['label' => 'Dosen']);
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);

        // 2. Program Studi & Semester
        $prodi = Prodi::firstOrCreate(
            ['code' => 'IF'],
            ['name' => 'Teknik Informatika']
        );

        $semester = Semester::firstOrCreate(
            ['code' => '2026-1'],
            ['name' => 'Ganjil 2026/2027', 'is_active' => true]
        );

        // 3. Akun Dosen Baru
        $dosen = User::updateOrCreate(
            ['email' => 'dosen.rps@sale.ac.id'],
            [
                'name' => 'Dr. Hendra Wijaya, M.T.',
                'nim_nidn' => '0012058801',
                'password' => Hash::make('password123'),
                'role_id' => $dosenRole->id,
                'prodi_id' => $prodi->id,
                'email_verified_at' => now(),
            ]
        );

        // 4. Akun Mahasiswa Baru
        $mhsAditya = User::updateOrCreate(
            ['email' => 'aditya@student.sale.ac.id'],
            [
                'name' => 'Aditya Pratama',
                'nim_nidn' => '2311501001',
                'password' => Hash::make('password123'),
                'role_id' => $mhsRole->id,
                'prodi_id' => $prodi->id,
                'email_verified_at' => now(),
            ]
        );

        $mhsBella = User::updateOrCreate(
            ['email' => 'bella@student.sale.ac.id'],
            [
                'name' => 'Bella Safitri',
                'nim_nidn' => '2311501002',
                'password' => Hash::make('password123'),
                'role_id' => $mhsRole->id,
                'prodi_id' => $prodi->id,
                'email_verified_at' => now(),
            ]
        );

        $mhsCitra = User::updateOrCreate(
            ['email' => 'citra@student.sale.ac.id'],
            [
                'name' => 'Citra Dewi',
                'nim_nidn' => '2311501003',
                'password' => Hash::make('password123'),
                'role_id' => $mhsRole->id,
                'prodi_id' => $prodi->id,
                'email_verified_at' => now(),
            ]
        );

        // 5. Mata Kuliah Baru Sesuai RPS
        $mataKuliah = MataKuliah::updateOrCreate(
            ['code' => 'IF305'],
            [
                'prodi_id' => $prodi->id,
                'name' => 'Pengembangan Aplikasi Terdistribusi',
                'sks' => 3,
            ]
        );

        // 6. Kelas Baru
        $section = ClassSection::updateOrCreate(
            [
                'mata_kuliah_id' => $mataKuliah->id,
                'semester_id' => $semester->id,
                'section_code' => 'A',
            ],
            [
                'dosen_id' => $dosen->id,
                'enrollment_code' => 'DIST305',
                'capacity' => 30,
            ]
        );

        // Daftarkan mahasiswa ke kelas perkuliahan
        $section->students()->syncWithoutDetaching([
            $mhsAditya->id,
            $mhsBella->id,
            $mhsCitra->id,
        ]);

        // 7. CPL05 pada Program Studi
        $cpl05 = Cpl::updateOrCreate(
            [
                'prodi_id' => $prodi->id,
                'code' => 'CPL05',
            ],
            [
                'description' => 'Mampu merancang, mengimplementasikan, dan mengevaluasi sistem perangkat lunak terdistribusi yang handal dan skalabel.',
            ]
        );

        // 8. CPMK051 dan CPMK052 pada Mata Kuliah
        $cpmk051 = Cpmk::updateOrCreate(
            [
                'mata_kuliah_id' => $mataKuliah->id,
                'code' => 'CPMK051',
            ],
            [
                'description' => 'Menganalisis arsitektur, pola desain, dan spesifikasi antarmuka komponen sistem terdistribusi (Sub-CPMK 1 & Sub-CPMK 2).',
                'threshold' => 65.0,
            ]
        );

        $cpmk052 = Cpmk::updateOrCreate(
            [
                'mata_kuliah_id' => $mataKuliah->id,
                'code' => 'CPMK052',
            ],
            [
                'description' => 'Mengimplementasikan, menguji, dan mendokumentasikan layanan sistem terdistribusi berbasis Case Method & PJBL (Sub-CPMK 3 & Sub-CPMK 4).',
                'threshold' => 65.0,
            ]
        );

        // 9. Mapping CPL ke CPMK:
        // CPL05 -> CPMK051: 41%
        // CPL05 -> CPMK052: 59%
        $cpl05->cpmks()->sync([
            $cpmk051->id => ['weight' => 41],
            $cpmk052->id => ['weight' => 59],
        ]);

        // 10. Asesmen dan Bobot Penilaian OBE:
        // UTS (20%)         -> CPMK051 (100% dari 20% = 20%)
        // Case Method (30%) -> CPMK051 21%, CPMK052 9% (70% dan 30% pivot)
        // PJBL (25%)        -> CPMK052 (100% dari 25% = 25%)
        // UAS (25%)         -> CPMK052 (100% dari 25% = 25%)

        $uts = Assessment::updateOrCreate(
            ['class_section_id' => $section->id, 'code' => 'UTS'],
            [
                'name' => 'Ujian Tengah Semester (Teori & Desain)',
                'type' => 'uts',
                'final_weight' => 20,
                'status' => 'published',
            ]
        );
        $uts->cpmks()->sync([
            $cpmk051->id => ['weight' => 100],
        ]);

        $caseMethod = Assessment::updateOrCreate(
            ['class_section_id' => $section->id, 'code' => 'CASE'],
            [
                'name' => 'Analisis Kasus Terdistribusi (Case Method)',
                'type' => 'project',
                'final_weight' => 30,
                'status' => 'published',
            ]
        );
        // Pivot 70% dan 30%:
        // CPMK051 effective = 30 * 70 / 100 = 21%
        // CPMK052 effective = 30 * 30 / 100 = 9%
        $caseMethod->cpmks()->sync([
            $cpmk051->id => ['weight' => 70],
            $cpmk052->id => ['weight' => 30],
        ]);

        $pjbl = Assessment::updateOrCreate(
            ['class_section_id' => $section->id, 'code' => 'PJBL'],
            [
                'name' => 'Project-Based Learning (Pengembangan Sistem)',
                'type' => 'pbl',
                'final_weight' => 25,
                'uses_rubric' => true,
                'status' => 'published',
            ]
        );
        $pjbl->cpmks()->sync([
            $cpmk052->id => ['weight' => 100],
        ]);

        $uas = Assessment::updateOrCreate(
            ['class_section_id' => $section->id, 'code' => 'UAS'],
            [
                'name' => 'Ujian Akhir Semester (Evaluasi Komprehensif)',
                'type' => 'uas',
                'final_weight' => 25,
                'status' => 'published',
            ]
        );
        $uas->cpmks()->sync([
            $cpmk052->id => ['weight' => 100],
        ]);

        // 11. Rubrik Penilaian pada Asesmen PJBL
        $rubric = Rubric::updateOrCreate(
            ['assessment_id' => $pjbl->id],
            ['name' => 'Rubrik Evaluasi Proyek PJBL Sistem Terdistribusi']
        );

        $crit1 = RubricCriterion::updateOrCreate(
            ['rubric_id' => $rubric->id, 'order' => 1],
            [
                'name' => 'Arsitektur & Desain Sistem',
                'description' => 'Ketepatan desain arsitektur terdistribusi, komunikasi antar komponen, dan pemisahan concern.',
                'weight' => 30,
                'max_score' => 100,
            ]
        );

        $crit2 = RubricCriterion::updateOrCreate(
            ['rubric_id' => $rubric->id, 'order' => 2],
            [
                'name' => 'Implementasi & Fungsionalitas Layanan',
                'description' => 'Kelengkapan endpoint API, sinkronisasi data, ketahanan failover, dan performa respon.',
                'weight' => 40,
                'max_score' => 100,
            ]
        );

        $crit3 = RubricCriterion::updateOrCreate(
            ['rubric_id' => $rubric->id, 'order' => 3],
            [
                'name' => 'Kualitas Pengujian & Dokumentasi',
                'description' => 'Cakupan unit/integration testing, kejelasan dokumentasi Swagger/Postman, dan laporan teknis.',
                'weight' => 30,
                'max_score' => 100,
            ]
        );

        // 12. Nilai Awal untuk Aditya Pratama (Pre-graded lengkap untuk verifikasi instan)
        // UTS = 85
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $uts->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 85, 'graded_by' => $dosen->id, 'graded_at' => now()]
        );

        // Case Method:
        // CPMK051: 18 / 21 poin proporsional (85.71%)
        // CPMK052: 8 / 9 poin proporsional (88.89%)
        // Total skor = 18 + 8 = 26 dari 30 (86.67%)
        StudentAssessmentCpmkScore::updateOrCreate(
            ['assessment_id' => $caseMethod->id, 'cpmk_id' => $cpmk051->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 18]
        );
        StudentAssessmentCpmkScore::updateOrCreate(
            ['assessment_id' => $caseMethod->id, 'cpmk_id' => $cpmk052->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 8]
        );
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $caseMethod->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 26, 'graded_by' => $dosen->id, 'graded_at' => now()]
        );

        // PJBL: Dinilai via Rubrik (3 kriteria masing-masing 90) -> Total = 90
        StudentRubricScore::updateOrCreate(
            ['rubric_criterion_id' => $crit1->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 90]
        );
        StudentRubricScore::updateOrCreate(
            ['rubric_criterion_id' => $crit2->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 90]
        );
        StudentRubricScore::updateOrCreate(
            ['rubric_criterion_id' => $crit3->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 90]
        );
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $pjbl->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 90, 'graded_by' => $dosen->id, 'graded_at' => now()]
        );

        // UAS = 80
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $uas->id, 'mahasiswa_id' => $mhsAditya->id],
            ['score' => 80, 'graded_by' => $dosen->id, 'graded_at' => now()]
        );

        // 13. Nilai Awal untuk Citra Dewi (Dinilai Sebagian: baru UTS = 75)
        StudentAssessmentScore::updateOrCreate(
            ['assessment_id' => $uts->id, 'mahasiswa_id' => $mhsCitra->id],
            ['score' => 75, 'graded_by' => $dosen->id, 'graded_at' => now()]
        );

        // Catatan: Bella Safitri sengaja dibiarkan KOSONG agar Anda dapat menguji input nilai manual di browser!
    }
}
