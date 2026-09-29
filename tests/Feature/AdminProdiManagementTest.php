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
        $this->adminProdi->update(['prodi_id' => $this->prodi->id]);

        $this->dosenKetua = User::where('email', 'budi@example.test')->first() ?? User::create([
            'name' => 'Budi Santoso, M.Kom.',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '198501012010121001',
        ]);
        $this->dosenKetua->update(['prodi_id' => $this->prodi->id]);

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
        $this->mahasiswa->update(['prodi_id' => $this->prodi->id]);
    }

    /**
     * Test Requirement 1: CRUD Prodi
     */
    public function test_global_admin_can_crud_prodi(): void
    {
        $globalAdmin = User::factory()->create([
            'role_id' => Role::where('name', Role::ADMIN)->value('id'),
            'prodi_id' => null,
        ]);
        $this->actingAs($globalAdmin);

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
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test');
        file_put_contents($tempFile, $response->streamedContent());
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFile)->getActiveSheet();
        $this->assertSame('NIDN_NIP', $sheet->getCell('A1')->getValue());
        @unlink($tempFile);

        $response = $this->get(route('admin-prodi.users.template', 'mahasiswa'));
        $response->assertStatus(200);
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test');
        file_put_contents($tempFile, $response->streamedContent());
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFile)->getActiveSheet();
        $this->assertSame('NIM', $sheet->getCell('A1')->getValue());
        @unlink($tempFile);

        // 2. Input Manual Mahasiswa
        $response = $this->post(route('admin-prodi.users.store'), [
            'name' => 'Fajar Santoso',
            'email' => 'fajar@student.test',
            'nim_nidn' => '231011409999',
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'password' => 'secret123456',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'fajar@student.test', 'nim_nidn' => '231011409999']);

        // 3. Impor CSV Massal Mahasiswa
        $csvContent = "NIM,Nama Mahasiswa,Email Mahasiswa,Password\n"
            ."231011405001,Rina Kurnia,rina@student.test,password1234\n"
            ."231011405002,Dimas Anggara,dimas@student.test,\n";

        $file = UploadedFile::fake()->createWithContent('import_students.csv', $csvContent);

        $response = $this->post(route('admin-prodi.users.import'), [
            'prodi_id' => $this->prodi->id,
            'role_type' => 'mahasiswa',
            'file' => $file,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'rina@student.test']);
        $this->assertDatabaseHas('users', ['email' => 'dimas@student.test']);
        $this->assertDatabaseHas('users', ['email' => 'rina@student.test', 'must_change_password' => true]);
        $temporaryCredentials = $response->getSession()->get('temporary_credentials');
        $this->assertCount(1, $temporaryCredentials);
        $this->assertSame('dimas@student.test', $temporaryCredentials[0]['email']);
        $this->assertTrue(Hash::check(
            $temporaryCredentials[0]['password'],
            User::where('email', 'dimas@student.test')->firstOrFail()->password
        ));

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
        $rina->update(['must_change_password' => false]);
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

        // 3. Ekspor Laporan Excel (.xlsx)
        $response = $this->get(route('admin-prodi.laporan.export', [
            'prodi_id' => $this->prodi->id,
            'semester_id' => $this->semester->id,
        ]));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('laporan-prodi-', (string) $response->headers->get('content-disposition'));
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test');
        file_put_contents($tempFile, $response->streamedContent());
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();
        $this->assertSame('LAPORAN AKADEMIK & CAPAIAN PROGRAM STUDI', $sheet->getCell('A1')->getValue());
        $this->assertSame('RINGKASAN METRIK SEMESTER', $sheet->getCell('A6')->getValue());
        $this->assertStringContainsString('Teknik Informatika', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('IF204', $sheet->getCell('B17')->getValue());
        @unlink($tempFile);
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

        $response = $this->post(route('admin-prodi.users.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $created = User::where('email', $payload['email'])->firstOrFail();
        $notice = (string) $response->getSession()->get('notice');
        $this->assertMatchesRegularExpression('/Password sementara: (\S{16}) /', $notice);
        preg_match('/Password sementara: (\S{16}) /', $notice, $matches);
        $this->assertTrue(Hash::check($matches[1], $created->password));
        $this->assertFalse(Hash::check('password123', $created->password));
        $this->assertTrue($created->must_change_password);

        $this->post(route('admin-prodi.users.store'), array_merge($payload, [
            'email' => 'invalid.password@student.test',
            'nim_nidn' => '231011409098',
            'password' => '123',
        ]))->assertSessionHasErrors('password');

        $created->update(['must_change_password' => false]);
        $updatePayload = [
            'name' => $created->name,
            'email' => $created->email,
            'nim_nidn' => $created->nim_nidn,
            'prodi_id' => $created->prodi_id,
        ];

        $this->put(route('admin-prodi.users.update', $created), array_merge($updatePayload, [
            'password' => 'short12',
        ]))->assertSessionHasErrors('password');

        $this->put(route('admin-prodi.users.update', $created), array_merge($updatePayload, [
            'password' => 'Resetpass1234',
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $this->assertTrue(Hash::check('Resetpass1234', $created->fresh()->password));
        $this->assertTrue($created->fresh()->must_change_password);
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

    public function test_admin_prodi_cannot_access_or_mutate_foreign_prodi_data(): void
    {
        $foreignProdi = Prodi::create(['code' => 'SI', 'name' => 'Sistem Informasi']);
        $foreignMk = MataKuliah::create([
            'prodi_id' => $foreignProdi->id,
            'code' => 'SI101',
            'name' => 'Pengantar Sistem Informasi',
            'sks' => 3,
        ]);
        $foreignSection = ClassSection::create([
            'mata_kuliah_id' => $foreignMk->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'A',
            'capacity' => 30,
        ]);
        $foreignCpl = Cpl::create([
            'prodi_id' => $foreignProdi->id,
            'code' => 'CPL-SI',
            'description' => 'CPL prodi lain',
        ]);
        $foreignStudent = User::factory()->create([
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'prodi_id' => $foreignProdi->id,
            'nim_nidn' => 'SI-STUDENT-01',
        ]);

        $this->actingAs($this->adminProdi);

        $this->get(route('admin-prodi.dashboard'))
            ->assertOk()
            ->assertDontSee('Pengantar Sistem Informasi');
        $this->get(route('admin-prodi.users.index', ['prodi_id' => $foreignProdi->id]))->assertForbidden();
        $this->get(route('admin-prodi.laporan.index', ['prodi_id' => $foreignProdi->id]))->assertForbidden();
        $this->post(route('admin-prodi.prodi.store'), [
            'code' => 'NEW',
            'name' => 'Prodi Baru Tanpa Izin',
        ])->assertForbidden();
        $this->put(route('admin-prodi.prodi.update', $foreignProdi), [
            'code' => 'SI',
            'name' => 'Diambil Alih',
        ])->assertForbidden();
        $this->put(route('admin-prodi.users.update', $foreignStudent), [
            'name' => 'Diambil Alih',
            'email' => $foreignStudent->email,
            'nim_nidn' => $foreignStudent->nim_nidn,
            'prodi_id' => $foreignProdi->id,
        ])->assertForbidden();
        $this->put(route('admin-prodi.users.update', $this->mahasiswa), [
            'name' => $this->mahasiswa->name,
            'email' => $this->mahasiswa->email,
            'nim_nidn' => $this->mahasiswa->nim_nidn,
            'prodi_id' => $foreignProdi->id,
        ])->assertForbidden();
        $this->get(route('admin-prodi.akademik.kelas.qr', $foreignSection))->assertForbidden();

        $this->post(route('admin-prodi.kurikulum.cpmk.store'), [
            'mata_kuliah_id' => MataKuliah::create([
                'prodi_id' => $this->prodi->id,
                'code' => 'IF999',
                'name' => 'Mata Kuliah Lokal',
                'sks' => 3,
            ])->id,
            'code' => 'CPMK-X',
            'description' => 'Tidak boleh terhubung lintas prodi',
            'threshold' => 60,
            'cpl_ids' => [$foreignCpl->id],
            'weights' => [$foreignCpl->id => 100],
        ])->assertForbidden();

        $this->assertDatabaseHas('prodis', ['id' => $foreignProdi->id, 'name' => 'Sistem Informasi']);
        $this->assertDatabaseMissing('prodis', ['code' => 'NEW']);
        $this->assertDatabaseHas('users', ['id' => $foreignStudent->id, 'name' => $foreignStudent->name]);
        $this->assertDatabaseHas('users', ['id' => $this->mahasiswa->id, 'prodi_id' => $this->prodi->id]);
        $this->assertDatabaseMissing('cpmks', ['code' => 'CPMK-X']);
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

    public function test_all_added_prodis_are_integrated_and_visible_in_admin_prodi_dashboard_and_modules(): void
    {
        $newProdi = Prodi::create([
            'code' => 'TE',
            'name' => 'Teknik Elektro',
        ]);

        $globalAdmin = User::factory()->create([
            'role_id' => Role::where('name', Role::ADMIN)->value('id'),
            'prodi_id' => null,
        ]);

        $this->actingAs($globalAdmin);

        // 1. Dashboard Admin Prodi displays newly added Prodi in stat and table
        $dashResponse = $this->get(route('admin-prodi.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Program Studi Terdaftar');
        $dashResponse->assertSee('Teknik Elektro');
        $dashResponse->assertSee('TE');

        // 2. Admin Sistem Pengguna allows assigning user to TE
        $createLecturerResponse = $this->post(route('admin.users.store'), [
            'name' => 'Dosen Elektro Baru',
            'email' => 'dosen.te@example.test',
            'number' => 'NIDN123456',
            'roles' => ['dosen'],
            'status' => 'aktif',
            'prodi_id' => $newProdi->id,
        ]);
        $createLecturerResponse->assertRedirect(route('admin.page', 'pengguna'));
        $this->assertDatabaseHas('users', [
            'email' => 'dosen.te@example.test',
            'prodi_id' => $newProdi->id,
        ]);

        // 3. Unconstrained Admin Prodi can view and manage both prodis
        $institutionalAdminProdi = User::factory()->create([
            'role_id' => Role::where('name', Role::ADMIN_PRODI)->value('id'),
            'prodi_id' => null,
            'managing_prodi_id' => null,
        ]);
        $this->actingAs($institutionalAdminProdi);

        $instDashResponse = $this->get(route('admin-prodi.dashboard'));
        $instDashResponse->assertOk();
        $instDashResponse->assertSee('Teknik Elektro');
        $instDashResponse->assertSee('Teknik Informatika');

        $kurikulumResponse = $this->get(route('admin-prodi.kurikulum.index', ['prodi_id' => $newProdi->id]));
        $kurikulumResponse->assertOk();
        $kurikulumResponse->assertSee('Teknik Elektro');

        $mkResponse = $this->get(route('admin-prodi.akademik.matakuliah', ['prodi_id' => $newProdi->id]));
        $mkResponse->assertOk();
        $mkResponse->assertSee('Teknik Elektro');

        $kelasResponse = $this->get(route('admin-prodi.akademik.kelas', ['prodi_id' => $newProdi->id]));
        $kelasResponse->assertOk();
        $kelasResponse->assertSee('Teknik Elektro');
    }
}
