<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LaporanProdiSemesterMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminProdi;

    private Prodi $prodi;

    private Semester $semester;

    private User $dosen1;

    private User $dosen2;

    private User $dosen3;

    private User $dosenIdle;

    private User $mhs1;

    private User $mhs2;

    private User $mhs3;

    private User $mhsIdle;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdminProdi = Role::firstOrCreate(['name' => Role::ADMIN_PRODI], ['label' => 'Admin Prodi']);
        $roleDosen = Role::firstOrCreate(['name' => Role::DOSEN], ['label' => 'Dosen']);
        $roleMhs = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Teknik Informatika',
            'jenjang' => 'S1',
        ]);

        $this->semester = Semester::create([
            'name' => 'Ganjil 2026/2027',
            'code' => '20261',
            'is_active' => true,
            'academic_year_start' => 2026,
            'academic_year_end' => 2027,
        ]);

        $this->adminProdi = User::create([
            'name' => 'Admin Prodi IF',
            'email' => 'admin.if@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleAdminProdi->id,
            'managing_prodi_id' => $this->prodi->id,
            'prodi_id' => $this->prodi->id,
        ]);

        // Dosen 1: Ketua in Class A & Class B
        $this->dosen1 = User::create([
            'name' => 'Dosen Satu',
            'email' => 'dosen1@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleDosen->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '001001',
        ]);

        // Dosen 2: Anggota in Class A, Ketua in Class C
        $this->dosen2 = User::create([
            'name' => 'Dosen Dua',
            'email' => 'dosen2@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleDosen->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '001002',
        ]);

        // Dosen 3: Anggota in Class B & Class C
        $this->dosen3 = User::create([
            'name' => 'Dosen Tiga',
            'email' => 'dosen3@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleDosen->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '001003',
        ]);

        // Dosen Idle: Homebase in prodi, but teaches NO classes
        $this->dosenIdle = User::create([
            'name' => 'Dosen Idle',
            'email' => 'dosen.idle@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleDosen->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '001004',
        ]);

        // Mahasiswa 1: takes Class A & Class B (2 classes)
        $this->mhs1 = User::create([
            'name' => 'Mahasiswa Satu',
            'email' => 'mhs1@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleMhs->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '261001',
            'angkatan' => 2026,
        ]);

        // Mahasiswa 2: takes Class A, Class B, and Class C (3 classes)
        $this->mhs2 = User::create([
            'name' => 'Mahasiswa Dua',
            'email' => 'mhs2@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleMhs->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '261002',
            'angkatan' => 2026,
        ]);

        // Mahasiswa 3: takes Class C only
        $this->mhs3 = User::create([
            'name' => 'Mahasiswa Tiga',
            'email' => 'mhs3@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleMhs->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '261003',
            'angkatan' => 2026,
        ]);

        // Mahasiswa Idle: Belongs to prodi, but enrolled in 0 classes this semester
        $this->mhsIdle = User::create([
            'name' => 'Mahasiswa Idle',
            'email' => 'mhs.idle@test.local',
            'password' => bcrypt('password'),
            'role_id' => $roleMhs->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '251004',
            'angkatan' => 2025,
        ]);
    }

    public function test_unique_dosen_and_mahasiswa_counts_across_web_pdf_and_excel(): void
    {
        $this->actingAs($this->adminProdi);

        $mk1 = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF101',
            'name' => 'Algoritma Pemrograman',
            'sks' => 3,
        ]);

        $mk2 = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF102',
            'name' => 'Basis Data',
            'sks' => 3,
        ]);

        // Class A: Dosen Ketua = Dosen 1, Dosen Anggota = Dosen 2
        $classA = ClassSection::create([
            'mata_kuliah_id' => $mk1->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'IF101A01',
        ]);
        $classA->dosenAnggota()->attach($this->dosen2->id);
        $classA->students()->attach([$this->mhs1->id, $this->mhs2->id]); // 2 students

        // Class B: Dosen Ketua = Dosen 1, Dosen Anggota = Dosen 3
        $classB = ClassSection::create([
            'mata_kuliah_id' => $mk1->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'section_code' => 'B',
            'capacity' => 40,
            'enrollment_code' => 'IF101B01',
        ]);
        $classB->dosenAnggota()->attach($this->dosen3->id);
        $classB->students()->attach([$this->mhs1->id, $this->mhs2->id]); // 2 students (same as Class A)

        // Class C: Dosen Ketua = Dosen 2, Dosen Anggota = Dosen 3
        $classC = ClassSection::create([
            'mata_kuliah_id' => $mk2->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen2->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'IF102A01',
        ]);
        $classC->dosenAnggota()->attach($this->dosen3->id);
        $classC->students()->attach([$this->mhs2->id, $this->mhs3->id]); // 2 students

        // Expected Unique Metrics:
        // - Classes: 3
        // - Teaching Lecturers (Unique): Dosen 1, Dosen 2, Dosen 3 => 3 orang (Dosen Idle excluded)
        // - Enrolled Students (Unique): Mhs 1, Mhs 2, Mhs 3 => 3 orang (Mhs Idle excluded, duplicates across A,B,C eliminated)

        // 1. WEB VIEW VERIFICATION
        $webResponse = $this->get(route('admin-prodi.laporan.index', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $webResponse->assertOk();
        $viewData = $webResponse->viewData('metrics');
        $this->assertSame(3, $viewData['total_dosen'], 'Jumlah Dosen pada web harus 3 orang unik.');
        $this->assertSame(3, $viewData['total_mahasiswa'], 'Jumlah Mahasiswa pada web harus 3 orang unik.');
        $this->assertSame(3, $viewData['total_kelas']);

        // 2. PDF PRINT VIEW VERIFICATION
        $printResponse = $this->get(route('admin-prodi.laporan.print', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $printResponse->assertOk();
        $printData = $printResponse->viewData('metrics');
        $this->assertSame(3, $printData['total_dosen'], 'Jumlah Dosen pada PDF harus 3 orang unik.');
        $this->assertSame(3, $printData['total_mahasiswa'], 'Jumlah Mahasiswa pada PDF harus 3 orang unik.');
        $printResponse->assertSee('<span class="val">3</span>', false);

        // 3. EXCEL EXPORT VERIFICATION
        $excelResponse = $this->get(route('admin-prodi.laporan.export', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $excelResponse->assertOk();
        $tempFile = tempnam(sys_get_temp_dir(), 'test_laporan_excel');
        file_put_contents($tempFile, $excelResponse->streamedContent());

        $spreadsheet = IOFactory::load($tempFile);

        // Sheet 1: Ringkasan Metrik
        $sheetMetrics = $spreadsheet->getSheet(0);
        $this->assertSame('Ringkasan Metrik', $sheetMetrics->getTitle());
        $this->assertSame('Total Dosen Pengampu', $sheetMetrics->getCell('B7')->getValue());
        $this->assertSame('3 Orang', $sheetMetrics->getCell('C7')->getValue(), 'Dosen pengampu di Excel harus 3 Orang.');
        $this->assertSame('Total Mahasiswa Terdaftar (Aktif)', $sheetMetrics->getCell('B8')->getValue());
        $this->assertSame('3 Orang', $sheetMetrics->getCell('C8')->getValue(), 'Mahasiswa terdaftar aktif di Excel harus 3 Orang.');
        $this->assertSame('Total Kelas Perkuliahan Aktif', $sheetMetrics->getCell('B9')->getValue());
        $this->assertSame('3 Kelas', $sheetMetrics->getCell('C9')->getValue());

        // Sheet 2: Rincian Kelas
        $sheetClasses = $spreadsheet->getSheet(1);
        $this->assertSame('Rincian Kelas', $sheetClasses->getTitle());
        $this->assertSame('RATA-RATA NILAI MAHASISWA', $sheetClasses->getCell('A10')->getValue());

        @unlink($tempFile);
    }
}
