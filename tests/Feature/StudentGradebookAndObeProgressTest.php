<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentGradebookAndObeProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_grades_page_integrates_with_database_and_renders_full_width_score_cards(): void
    {
        $this->seed(RoleSeeder::class);
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['name' => 'Ganjil 2026/2027', 'code' => '20261', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'IF204', 'name' => 'Struktur Data & Algoritma', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $dosen = User::create([
            'name' => 'Dr. Dosen',
            'email' => 'dosen@test.local',
            'nim_nidn' => '12345678',
            'password' => Hash::make('secret'),
            'role_id' => $dosenRoleId,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $student = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@test.local',
            'nim_nidn' => '231011401001',
            'password' => Hash::make('secret'),
            'role_id' => $mhsRoleId,
        ]);
        $student->roles()->sync([$mhsRoleId]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
        $section->students()->attach($student->id);

        $tugas = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1 Linked List',
            'type' => 'tugas',
            'final_weight' => 40,
            'status' => 'published',
        ]);

        $uts = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'UTS-01',
            'name' => 'Ujian Tengah Semester',
            'type' => 'uts',
            'final_weight' => 60,
            'status' => 'published',
        ]);

        // Input nilai real di database
        $obe = app(ObeCalculationService::class);
        $obe->syncDirectScore($tugas, $student->id, 80.0, $dosen->id, true);
        $obe->syncDirectScore($uts, $student->id, 90.0, $dosen->id, true);

        // Kunjungi halaman nilai mahasiswa
        $response = $this->actingAs($student)->get(route('mahasiswa.nilai'));
        $response->assertOk();

        // 1. Header dan ringkasan akademik dari database
        $response->assertSee('Transkrip Nilai &amp; Hasil Studi', false);
        $response->assertSee('Ganjil 2026/2027');
        $response->assertSee('3 SKS');

        // Nilai akhir: (80 * 0.4) + (90 * 0.6) = 32 + 54 = 86.0 (A -> 4.00)
        $response->assertSee('86,0');
        $response->assertSee('4,00');

        // 2. Daftar mata kuliah
        $response->assertSee('Struktur Data &amp; Algoritma', false);
        $response->assertSee('IF204-A');

        // 3. Rincian komponen nilai dan kartu terpadu di sisi kanan
        $response->assertSee('Rincian Komponen Nilai:');
        $response->assertSee('Tugas 1 Linked List');
        $response->assertSee('80.0');
        $response->assertSee('Ujian Tengah Semester');
        $response->assertSee('90.0');
        $response->assertSee('Total Nilai Akhir');

        // 4. Tab navigasi terpadu
        $response->assertSee('Transkrip Nilai (KHS)');
        $response->assertSee('Capaian Pembelajaran OBE');

        // 5. Cek halaman Capaian Pembelajaran OBE juga aktif dan terhubung
        $obeResponse = $this->actingAs($student)->get(route('mahasiswa.obe.progress'));
        $obeResponse->assertOk();
        $obeResponse->assertSee('Transkrip Nilai (KHS)');
        $obeResponse->assertSee('Capaian Pembelajaran OBE');
        $obeResponse->assertSee('Struktur Data &amp; Algoritma', false);
    }
}
