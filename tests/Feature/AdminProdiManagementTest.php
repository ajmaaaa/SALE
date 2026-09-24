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
use Database\Seeders\DosenAccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProdiManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminProdi;

    private User $dosenKetua;

    private User $dosenWakil;

    private User $mahasiswa;

    private Prodi $prodi;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(DosenAccountSeeder::class);

        $adminProdiRole = Role::where('name', Role::ADMIN_PRODI)->first();
        $dosenRole = Role::where('name', Role::DOSEN)->first();
        $mahasiswaRole = Role::where('name', Role::MAHASISWA)->first();

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Teknik Informatika',
        ]);

        $this->semester = Semester::create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);

        $this->adminProdi = User::where('email', 'adminprodi@example.test')->first() ?? User::create([
            'name' => 'Admin Prodi TI',
            'email' => 'adminprodi@example.test',
            'password' => Hash::make('password'),
            'role_id' => $adminProdiRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => 'AP001',
        ]);

        $this->dosenKetua = User::where('email', 'budi@example.test')->first() ?? User::create([
            'name' => 'Budi Santoso, M.Kom.',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '198501012010121001',
        ]);

        $this->dosenWakil = User::create([
            'name' => 'Hendra Wijaya, S.Kom., M.Cs.',
            'email' => 'hendra@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '199002022018011002',
        ]);

        $this->mahasiswa = User::where('nim_nidn', '231011401234')->first() ?? User::create([
            'name' => 'Ahmad Maulana',
            'email' => 'ahmad.maulana@student.test',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '231011401234',
        ]);
    }

    /**
     * Test Requirement 1: CRUD Prodi
     */
    public function test_admin_prodi_can_crud_prodi(): void
    {
        $this->actingAs($this->adminProdi);

        // Index redirects to dashboard
        $response = $this->get(route('admin-prodi.prodi.index'));
        $response->assertRedirect(route('admin-prodi.dashboard'));

        // Store
        $response = $this->post(route('admin-prodi.prodi.store'), [
            'code' => 'SI',
            'name' => 'Sistem Informasi',
        ]);
        $response->assertRedirect(route('admin-prodi.dashboard'));
        $this->assertDatabaseHas('prodis', ['code' => 'SI', 'name' => 'Sistem Informasi']);

        $si = Prodi::where('code', 'SI')->first();

        // Update
        $response = $this->put(route('admin-prodi.prodi.update', $si->id), [
            'code' => 'SI',
            'name' => 'Sistem Informasi Bisnis',
        ]);
        $response->assertRedirect(route('admin-prodi.dashboard'));
        $this->assertDatabaseHas('prodis', ['code' => 'SI', 'name' => 'Sistem Informasi Bisnis']);

        // Destroy
        $response = $this->delete(route('admin-prodi.prodi.destroy', $si->id));
        $response->assertRedirect(route('admin-prodi.dashboard'));
        $this->assertDatabaseMissing('prodis', ['id' => $si->id]);
    }

    /**
     * Test Requirement 2: Menetapkan CPL & CPMK secara terpusat
     */
    public function test_admin_prodi_can_manage_cpl_and_cpmk_curriculum(): void
    {
        $this->actingAs($this->adminProdi);

        // 1. Tambah CPL
        $response = $this->post(route('admin-prodi.kurikulum.cpl.store'), [
            'prodi_id' => $this->prodi->id,
            'code' => 'CPL-01',
            'description' => 'Mampu menerapkan pemikiran logis dan komputasional.',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('cpls', ['code' => 'CPL-01', 'prodi_id' => $this->prodi->id]);
        $cpl = Cpl::where('code', 'CPL-01')->first();

        // 2. Tambah Mata Kuliah
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF204',
            'name' => 'Struktur Data dan Algoritma',
            'sks' => 3,
        ]);

        // 3. Tambah CPMK
        $response = $this->post(route('admin-prodi.kurikulum.cpmk.store'), [
            'mata_kuliah_id' => $mk->id,
            'code' => 'CPMK-01',
            'description' => 'Mampu mengimplementasikan binary search tree.',
            'threshold' => 65,
            'cpl_ids' => [$cpl->id],
            'weights' => [$cpl->id => 100],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('cpmks', ['code' => 'CPMK-01', 'mata_kuliah_id' => $mk->id]);
        $cpmk = Cpmk::where('code', 'CPMK-01')->first();
        $this->assertTrue($cpmk->cpls->contains($cpl->id));

        // 4. Update Matriks Pemetaan
        $response = $this->post(route('admin-prodi.kurikulum.mapping.update'), [
            'prodi_id' => $this->prodi->id,
            'matrix' => [
                $cpmk->id => [
                    $cpl->id => 85,
                ],
            ],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('cpl_cpmk', [
            'cpl_id' => $cpl->id,
            'cpmk_id' => $cpmk->id,
            'weight' => 85,
        ]);
    }

    /**
     * Test Requirement 3: Membuat Mata Kuliah & Kelas dengan Dosen Ketua dan Dosen Wakil
     */
    public function test_admin_prodi_can_manage_matakuliah_and_class_with_ketua_and_wakil(): void
    {
        $this->actingAs($this->adminProdi);

        // Store Mata Kuliah
        $response = $this->post(route('admin-prodi.akademik.matakuliah.store'), [
            'prodi_id' => $this->prodi->id,
            'code' => 'IF301',
            'name' => 'Pemrograman Web Lanjut',
            'sks' => 3,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('mata_kuliahs', ['code' => 'IF301']);
        $mk = MataKuliah::where('code', 'IF301')->first();

        // Store Kelas dengan Dosen Ketua & Dosen Wakil
        $response = $this->post(route('admin-prodi.akademik.kelas.store'), [
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'A',
            'capacity' => 45,
            'dosen_id' => $this->dosenKetua->id,
            'dosen_pendamping_id' => $this->dosenWakil->id,
        ]);
        $response->assertRedirect();

        $section = ClassSection::where('mata_kuliah_id', $mk->id)->first();
        $this->assertNotNull($section);
        $this->assertSame('A', $section->section_code);
        $this->assertSame($this->dosenKetua->id, $section->dosen_id);
        $this->assertSame($this->dosenWakil->id, $section->dosen_pendamping_id);
        $this->assertNotEmpty($section->enrollment_code);

        // Verifikasi Dosen Wakil dapat melihat kelas di daftar kelasnya
        $this->actingAs($this->dosenWakil);
        $response = $this->get(route('dosen.penilaian.index'));
        $response->assertStatus(200);
        $response->assertSee('IF301-A');
        $response->assertSee('Dosen Wakil');

        // Verifikasi SVG QR & Barcode endpoint
        $this->actingAs($this->adminProdi);
        $qrResp = $this->get(route('admin-prodi.akademik.kelas.qr', $section->id));
        $qrResp->assertStatus(200);
        $qrResp->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $qrResp->getContent());

        $barResp = $this->get(route('admin-prodi.akademik.kelas.barcode', $section->id));
        $barResp->assertStatus(200);
        $barResp->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $barResp->getContent());
    }

    /**
     * Test Requirement 4: Input Dosen & Mahasiswa manual & impor Excel, join kelas via link/barcode
     */
    public function test_admin_prodi_user_input_excel_import_and_student_join_class(): void
    {
        $this->actingAs($this->adminProdi);

        // 1. Download template Excel Dosen & Mahasiswa
        $response = $this->get(route('admin-prodi.users.template', 'dosen'));
        $response->assertStatus(200);
        $this->assertStringContainsString('NIDN_NIP', $response->streamedContent());

        $response = $this->get(route('admin-prodi.users.template', 'mahasiswa'));
        $response->assertStatus(200);
        $this->assertStringContainsString('NIM', $response->streamedContent());

        // 2. Input Manual Mahasiswa
        $response = $this->post(route('admin-prodi.users.store'), [
            'name' => 'Fajar Santoso',
            'email' => 'fajar@student.test',
            'nim_nidn' => '231011409999',
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'password' => 'secret123',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'fajar@student.test', 'nim_nidn' => '231011409999']);

        // 3. Impor CSV Massal Mahasiswa
        $csvContent = "NIM,Nama Mahasiswa,Email Mahasiswa,Password\n"
            ."231011405001,Rina Kurnia,rina@student.test,password123\n"
            ."231011405002,Dimas Anggara,dimas@student.test,password123\n";

        $file = UploadedFile::fake()->createWithContent('import_students.csv', $csvContent);

        $response = $this->post(route('admin-prodi.users.import'), [
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'file' => $file,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'rina@student.test']);
        $this->assertDatabaseHas('users', ['email' => 'dimas@student.test']);

        // 4. Mahasiswa join kelas via Link / Barcode (`enrollment_code`)
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF201',
            'name' => 'Algoritma Pemrograman',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosenKetua->id,
            'section_code' => 'A',
            'capacity' => 50,
            'enrollment_code' => 'ALGO201A',
        ]);

        // Login sebagai mahasiswa baru yang diimpor
        $rina = User::where('email', 'rina@student.test')->first();
        $this->actingAs($rina);

        // GET hanya menampilkan konfirmasi; mutasi dilakukan melalui POST + CSRF.
        $response = $this->get(route('mahasiswa.join-kelas', 'ALGO201A'));
        $response->assertStatus(200);
        $response->assertSee('Konfirmasi Pendaftaran Kelas');
        $this->assertFalse($section->students()->where('users.id', $rina->id)->exists());

        $response = $this->post(route('mahasiswa.join-kelas.post', 'ALGO201A'));
        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Berhasil');
        $response->assertSee('IF201-A');

        // Pastikan mahasiswa sudah terdaftar di database pivot
        $this->assertTrue($section->students()->where('users.id', $rina->id)->exists());

        // Jika membuka link lagi, menampilkan feedback 'Sudah Terdaftar'
        $response = $this->get(route('mahasiswa.join-kelas', 'ALGO201A'));
        $response->assertStatus(200);
        $response->assertSee('Anda Sudah Terdaftar');
    }

    /**
     * Test Requirement 5: Laporan per prodi per semester dan ekspor CSV
     */
    public function test_admin_prodi_can_view_and_export_semester_report(): void
    {
        $this->actingAs($this->adminProdi);

        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF204',
            'name' => 'Struktur Data',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosenKetua->id,
            'dosen_pendamping_id' => $this->dosenWakil->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'IF204A12',
        ]);

        $section->students()->attach($this->mahasiswa->id);

        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1 BST',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
        ]);

        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'score' => 88.5,
            'graded_by' => $this->dosenKetua->id,
        ]);

        // 1. Tampilkan Laporan
        $response = $this->get(route('admin-prodi.laporan.index', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Laporan Akademik &amp; Capaian Nilai Prodi', false);
        $response->assertSee('88.50');

        // 2. Cetak Dokumen PDF / Print view
        $response = $this->get(route('admin-prodi.laporan.print', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('LAPORAN AKADEMIK &amp; KELAS PERKULIAHAN PROGRAM STUDI', false);

        // 3. Ekspor Laporan CSV
        $response = $this->get(route('admin-prodi.laporan.export', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $response->assertStatus(200);
        $csvOutput = $response->streamedContent();
        $this->assertStringContainsString('RINGKASAN METRIK SEMESTER', $csvOutput);
        $this->assertStringContainsString('Teknik Informatika', $csvOutput);
        $this->assertStringContainsString('IF204', $csvOutput);
    }

    /**
     * Test Otorisasi: Mahasiswa tidak boleh mengakses ruang admin prodi
     */
    public function test_admin_prodi_may_leave_password_blank_but_invalid_supplied_password_is_rejected(): void
    {
        $this->actingAs($this->adminProdi);

        $payload = [
            'role_type' => 'mahasiswa',
            'name' => 'Mahasiswa Tanpa Password Manual',
            'email' => 'default.password@student.test',
            'nim_nidn' => '231011409099',
            'prodi_id' => $this->prodi->id,
        ];

        $this->post(route('admin-prodi.users.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $created = User::where('email', $payload['email'])->firstOrFail();
        $this->assertTrue(Hash::check('password123', $created->password));

        $this->post(route('admin-prodi.users.store'), array_merge($payload, [
            'email' => 'invalid.password@student.test',
            'nim_nidn' => '231011409098',
            'password' => '123',
        ]))->assertSessionHasErrors('password');
    }

    public function test_unauthorized_user_is_blocked_from_admin_prodi(): void
    {
        $this->actingAs($this->mahasiswa);

        $response = $this->get(route('admin-prodi.dashboard'));
        $response->assertStatus(403);
    }

    public function test_admin_prodi_cannot_update_or_delete_privileged_accounts(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'prodi_id' => $this->prodi->id,
        ]);

        $this->actingAs($this->adminProdi);

        $payload = [
            'name' => 'Compromised Admin',
            'email' => 'compromised@example.test',
            'nim_nidn' => 'ADMIN-CHANGED',
            'prodi_id' => $this->prodi->id,
        ];

        $this->put(route('admin-prodi.users.update', $admin), $payload)->assertNotFound();
        $this->delete(route('admin-prodi.users.destroy', $admin))->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'email' => $admin->email,
            'role_id' => $adminRole->id,
        ]);
    }

    /**
     * Test QR Code & Barcode kelas dapat diakses oleh Dosen / umum tanpa 403
     */
    public function test_class_qr_code_and_barcode_are_accessible(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF259',
            'name' => 'Data Sains',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'C',
            'dosen_id' => $this->dosenKetua->id,
            'dosen_pendamping_id' => $this->dosenWakil->id,
            'capacity' => 40,
        ]);

        // 1. Dosen mengakses QR Code kelas (tidak boleh 403 Forbidden)
        $this->actingAs($this->dosenKetua);
        $qrResponse = $this->get(route('kelas.qr', $section->id));
        $qrResponse->assertStatus(200);
        $qrResponse->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $qrResponse->getContent());

        // 2. Akses Barcode kelas
        $barcodeResponse = $this->get(route('kelas.barcode', $section->id));
        $barcodeResponse->assertStatus(200);
        $barcodeResponse->assertHeader('Content-Type', 'image/svg+xml');
    }

    /**
     * Test Mahasiswa setelah join kelas langsung melihat kelas tersebut di Dashboard & Course
     */
    public function test_mahasiswa_sees_enrolled_class_on_dashboard_and_course_after_joining(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF310',
            'name' => 'Pemrograman Mobile Lanjut',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'A',
            'dosen_id' => $this->dosenKetua->id,
            'capacity' => 45,
        ]);

        // Mahasiswa belum terdaftar, belum melihat di dashboard
        $this->actingAs($this->mahasiswa);
        $dashBefore = $this->get(route('mahasiswa.dashboard'));
        $dashBefore->assertStatus(200);
        $dashBefore->assertDontSee('Pemrograman Mobile Lanjut');

        // Mahasiswa join kelas via kode
        $joinResponse = $this->post(route('mahasiswa.join-kelas.post', $section->enrollment_code));
        $joinResponse->assertStatus(200);
        $joinResponse->assertSee('Pendaftaran Berhasil');

        // Setelah join, kelas tersebut langsung muncul di Dashboard Mahasiswa
        $dashAfter = $this->get(route('mahasiswa.dashboard'));
        $dashAfter->assertStatus(200);
        $dashAfter->assertSee('Pemrograman Mobile Lanjut');
        $dashAfter->assertSee($section->display_code);

        // Juga muncul di halaman Course Mahasiswa
        $courseAfter = $this->get(route('mahasiswa.course.index'));
        $courseAfter->assertStatus(200);
        $courseAfter->assertSee('Pemrograman Mobile Lanjut');
    }
}
