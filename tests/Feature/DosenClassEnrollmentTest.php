<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosenClassEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private Prodi $prodi;
    private Semester $semester;
    private User $dosen1;
    private User $dosen2;
    private User $dosen3;
    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $this->semester = Semester::create(['code' => '20261', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);

        $dosenRole = Role::firstOrCreate(['name' => Role::DOSEN], ['label' => 'Dosen']);
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);

        $this->dosen1 = User::create([
            'name' => 'Dosen Utama, M.Kom.',
            'email' => 'dosen1@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '11111111',
        ]);

        $this->dosen2 = User::create([
            'name' => 'Dosen Kedua, M.T.',
            'email' => 'dosen2@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '22222222',
        ]);

        $this->dosen3 = User::create([
            'name' => 'Dosen Ketiga, Ph.D.',
            'email' => 'dosen3@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '33333333',
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '44444444',
        ]);
    }

    public function test_dosen_ketua_enters_class_via_enrollment_code(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF101',
            'name' => 'Dasar Pemrograman',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'DS101A',
        ]);

        $this->actingAs($this->dosen1);
        $response = $this->get(route('mahasiswa.join-kelas', 'DS101A'));

        $response->assertStatus(200);
        $response->assertSee('Sudah Terdaftar');
        $response->assertSee('Dosen Ketua');
        $response->assertSee(route('dosen.penilaian.matriks', $section->id));
        $response->assertSee(route('dosen.course.show', $section->id));
    }

    public function test_dosen_can_join_as_pendamping_when_ketua_already_assigned(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF202',
            'name' => 'Struktur Data',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'dosen_pendamping_id' => null,
            'section_code' => 'B',
            'capacity' => 40,
            'enrollment_code' => 'SD202B',
        ]);

        $this->actingAs($this->dosen2);
        $response = $this->get(route('mahasiswa.join-kelas', 'SD202B'));

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Berhasil');
        $response->assertSee('Dosen Pendamping');
        $response->assertSee(route('dosen.penilaian.matriks', $section->id));

        $section->refresh();
        $this->assertSame($this->dosen2->id, $section->dosen_pendamping_id);
    }

    public function test_already_enrolled_dosen_sees_already_enrolled_and_action_buttons(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF303',
            'name' => 'Basis Data Lanjut',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'dosen_pendamping_id' => $this->dosen2->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'BD303A',
        ]);

        // Dosen 1 (Ketua) mengakses tautan
        $this->actingAs($this->dosen1);
        $response1 = $this->get(route('mahasiswa.join-kelas', 'BD303A'));
        $response1->assertStatus(200);
        $response1->assertSee('Sudah Terdaftar');
        $response1->assertSee('Dosen Ketua (Koordinator)');
        $response1->assertSee(route('dosen.penilaian.matriks', $section->id));
        $response1->assertSee(route('dosen.penilaian.index'));

        // Dosen 2 (Pendamping) mengakses tautan
        $this->actingAs($this->dosen2);
        $response2 = $this->get(route('mahasiswa.join-kelas', 'BD303A'));
        $response2->assertStatus(200);
        $response2->assertSee('Sudah Terdaftar');
        $response2->assertSee('Dosen Wakil (Pendamping)');
        $response2->assertSee(route('dosen.penilaian.matriks', $section->id));
    }

    public function test_third_dosen_is_notified_when_both_lecturer_slots_are_full(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF404',
            'name' => 'Kecerdasan Buatan',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'dosen_pendamping_id' => $this->dosen2->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'AI404A',
        ]);

        $this->actingAs($this->dosen3);
        $response = $this->get(route('mahasiswa.join-kelas', 'AI404A'));

        $response->assertStatus(200);
        $response->assertSee('Kapasitas Kelas Terpenuhi');
        $response->assertSee('telah memiliki Dosen Ketua');
    }

    public function test_dosen_penilaian_index_has_quick_join_form_and_scan_qr(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF505',
            'name' => 'Jaringan Komputer',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'dosen_pendamping_id' => $this->dosen2->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'JK505A',
        ]);

        // Dosen 1 melihat form masuk kelas, tombol scan QR, info dosen ketua & wakil, serta tombol masuk kelas
        $this->actingAs($this->dosen1);
        $response = $this->get(route('dosen.penilaian.index'));

        $response->assertStatus(200);
        $response->assertSee('dosen_enroll_code_input');
        $response->assertSee('Masuk Kelas');
        $response->assertSee('Scan QR');
        $response->assertSee('Dosen Ketua');
        $response->assertSee('Dosen Wakil');
        $response->assertSee('Bagikan QR');
        $response->assertSee(route('dosen.course.show', $section->id));
        $response->assertSee(route('dosen.penilaian.matriks', $section->id));
    }

    public function test_dosen_pendamping_has_full_access_to_assessment_matrix(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF606',
            'name' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'dosen_pendamping_id' => $this->dosen2->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'RPL606A',
        ]);

        // Dosen Pendamping dapat mengakses matriks tanpa 403
        $this->actingAs($this->dosen2);
        $response = $this->get(route('dosen.penilaian.matriks', $section->id));
        $response->assertStatus(200);
    }

    public function test_mahasiswa_enrollment_still_works_as_expected(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF707',
            'name' => 'Sistem Operasi',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'OS707A',
        ]);

        $this->actingAs($this->mahasiswa);
        $response = $this->get(route('mahasiswa.join-kelas', 'OS707A'));

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Berhasil');
        $this->assertTrue($section->students()->where('users.id', $this->mahasiswa->id)->exists());
    }

    public function test_dosen_dashboard_and_course_views_reflect_authenticated_dosen(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF808',
            'name' => 'Kecerdasan Buatan Lanjutan',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'section_code' => 'A',
            'capacity' => 35,
            'enrollment_code' => 'AI808A',
        ]);

        // Dashboard Dosen
        $this->actingAs($this->dosen1);
        $responseDash = $this->get(route('dosen.dashboard'));
        $responseDash->assertStatus(200);
        $responseDash->assertSee('Selamat datang, Dosen Utama, M.Kom.');
        $responseDash->assertDontSee('Selamat datang, Budi Santoso');
        $responseDash->assertSee('Kecerdasan Buatan Lanjutan');
        $responseDash->assertSee('1 <span class="text-xs font-normal text-muted">Matkul Aktif</span>', false);

        // Course List Dosen
        $responseCourses = $this->get(route('dosen.course.index'));
        $responseCourses->assertStatus(200);
        $responseCourses->assertSee('Kecerdasan Buatan Lanjutan');
        $responseCourses->assertDontSee('Struktur Data dan Algoritma'); // Mock course 1 should not appear

        // Course Detail (/dosen/course/{id})
        $responseDetail = $this->get(route('dosen.course.show', $section->id));
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee('Kecerdasan Buatan Lanjutan');
        $responseDetail->assertSee('Dosen Utama, M.Kom.');
    }

    public function test_dosen_with_no_classes_sees_clean_empty_state(): void
    {
        // Login as dosen3 with 0 classes
        $this->actingAs($this->dosen3);

        $responseDash = $this->get(route('dosen.dashboard'));
        $responseDash->assertStatus(200);
        $responseDash->assertSee('Selamat datang, Dosen Ketiga, Ph.D.');
        $responseDash->assertSee('Belum Ada Kelas yang Diampu');
        $responseDash->assertSee('0 <span class="text-xs font-normal text-muted">Matkul Aktif</span>', false);

        $responseCourses = $this->get(route('dosen.course.index'));
        $responseCourses->assertStatus(200);
        $responseCourses->assertSee('Belum Ada Course / Kelas');
        $responseCourses->assertDontSee('Struktur Data dan Algoritma');
    }

    public function test_dosen_can_view_course_item_for_database_assessment(): void
    {
        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF909',
            'name' => 'Pemrograman Web Lanjut',
            'sks' => 3,
        ]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen1->id,
            'section_code' => 'A',
            'capacity' => 35,
            'enrollment_code' => 'PW909A',
        ]);

        $asm = \App\Models\Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'T1',
            'name' => 'Tugas Proyek Web 1',
            'type' => 'tugas',
            'final_weight' => 20,
        ]);

        $this->actingAs($this->dosen1);
        $response = $this->get(route('dosen.course.item', [$section->id, $asm->id]));
        $response->assertStatus(200);
        $response->assertSee('Tugas Proyek Web 1');
        $response->assertSee('Input &amp; Kelola Nilai Asesmen', false);
    }
}
