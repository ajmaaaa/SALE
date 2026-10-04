<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentCohortAndSemesterProgressionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminProdi;

    private Prodi $prodi;

    private Semester $sem1;

    private Semester $sem2;

    private Semester $sem5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Teknik Informatika',
        ]);

        $this->sem1 = Semester::create([
            'code' => '20241',
            'name' => 'Semester Ganjil 2024/2025',
            'academic_year' => '2024/2025',
            'term' => 1,
            'is_active' => false,
        ]);

        $this->sem2 = Semester::create([
            'code' => '20242',
            'name' => 'Semester Genap 2024/2025',
            'academic_year' => '2024/2025',
            'term' => 2,
            'is_active' => false,
        ]);

        $this->sem5 = Semester::create([
            'code' => '20261',
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'term' => 1,
            'is_active' => true,
        ]);

        $adminProdiRole = Role::where('name', Role::ADMIN_PRODI)->first();
        $this->adminProdi = User::create([
            'name' => 'Admin Prodi IF',
            'email' => 'admin.if@example.test',
            'password' => bcrypt('password123'),
            'role_id' => $adminProdiRole->id,
            'prodi_id' => $this->prodi->id,
            'managing_prodi_id' => $this->prodi->id,
            'is_active' => true,
        ]);
    }

    public function test_dynamic_semester_progression_calculation(): void
    {
        $mahasiswa2024 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi24@student.test',
            'nim_nidn' => '2024101001',
            'angkatan' => 2024,
            'prodi_id' => $this->prodi->id,
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Sem 1: Ganjil 2024/2025 -> (2024 - 2024)*2 + 1 = 1
        $this->assertSame(1, $mahasiswa2024->semesterTempuhAt($this->sem1));

        // Sem 2: Genap 2024/2025 -> (2024 - 2024)*2 + 2 = 2
        $this->assertSame(2, $mahasiswa2024->semesterTempuhAt($this->sem2));

        // Sem 5: Ganjil 2026/2027 -> (2026 - 2024)*2 + 1 = 5
        $this->assertSame(5, $mahasiswa2024->semesterTempuhAt($this->sem5));

        // Senior student beyond semester 8
        $mahasiswa2020 = User::create([
            'name' => 'Senior Sutrisno',
            'email' => 'senior20@student.test',
            'nim_nidn' => '2020101001',
            'angkatan' => 2020,
            'prodi_id' => $this->prodi->id,
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // In 2026/2027 Ganjil: (2026 - 2020)*2 + 1 = 13 (semester 13!)
        $this->assertSame(13, $mahasiswa2020->semesterTempuhAt($this->sem5));
    }

    public function test_admin_prodi_can_create_and_update_student_with_angkatan(): void
    {
        $response = $this->actingAs($this->adminProdi)->post(route('admin-prodi.users.store'), [
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'nim_nidn' => '24010199',
            'name' => 'Citra Lestari',
            'email' => 'citra@student.test',
            'angkatan' => 2024,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'citra@student.test',
            'nim_nidn' => '24010199',
            'angkatan' => 2024,
        ]);

        $student = User::where('email', 'citra@student.test')->first();

        // Update angkatan
        $updateResponse = $this->actingAs($this->adminProdi)->put(route('admin-prodi.users.update', $student->id), [
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '24010199',
            'name' => 'Citra Lestari S',
            'email' => 'citra@student.test',
            'angkatan' => 2023,
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Citra Lestari S',
            'angkatan' => 2023,
        ]);
    }

    public function test_admin_prodi_auto_detects_angkatan_when_omitted(): void
    {
        $response = $this->actingAs($this->adminProdi)->post(route('admin-prodi.users.store'), [
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'nim_nidn' => '2025110099',
            'name' => 'Dedi Firmansyah',
            'email' => 'dedi@student.test',
            // angkatan omitted
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'dedi@student.test',
            'angkatan' => 2025,
        ]);
    }

    public function test_admin_prodi_can_import_excel_with_angkatan_column(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['NIM', 'Nama Mahasiswa', 'Email', 'Password', 'Tahun Masuk (Angkatan)'],
            ['20220101', 'Eka Ramadhani', 'eka@student.test', 'secret1234', '2022'],
            ['20240102', 'Fajar Pratama', 'fajar@student.test', '', '2024'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'import_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'students_cohort.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminProdi)->post(route('admin-prodi.users.import'), [
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'eka@student.test',
            'angkatan' => 2022,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'fajar@student.test',
            'angkatan' => 2024,
        ]);

        @unlink($tempPath);
    }

    public function test_khs_dropdown_contains_dynamically_computed_semesters(): void
    {
        $student = User::create([
            'name' => 'Gilang Ramadhan',
            'email' => 'gilang@student.test',
            'nim_nidn' => '2024101088',
            'angkatan' => 2024,
            'prodi_id' => $this->prodi->id,
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.nilai'));
        $response->assertOk();

        // Check dynamically computed dropdown options in HTML
        $response->assertSee('Ganjil 2024/2025');
        $response->assertSee('Genap 2024/2025');
        $response->assertSee('Ganjil 2026/2027 - Semester Aktif');
        $response->assertDontSee('Semester 1 (Ganjil');
        $response->assertDontSee('Semester 5 (Ganjil');
    }
}
