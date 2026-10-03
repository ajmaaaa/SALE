<?php

namespace Tests\Feature;

use App\Http\Controllers\Dosen\PenilaianController;
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
use Tests\TestCase;

class MataKuliahCpmkWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $adminProdi;
    private User $dosen;
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

        $this->prodi = Prodi::firstOrCreate(
            ['code' => 'IF'],
            ['name' => 'Teknik Informatika']
        );

        $this->semester = Semester::firstOrCreate(
            ['code' => '2026-1'],
            ['name' => 'Ganjil 2026/2027', 'is_active' => true]
        );

        $this->adminProdi = User::create([
            'name' => 'Admin Prodi TI',
            'email' => 'adminprodi_test@example.test',
            'password' => 'password',
            'role_id' => $adminProdiRole->id,
            'prodi_id' => $this->prodi->id,
            'managing_prodi_id' => $this->prodi->id,
            'nim_nidn' => 'AP001_TEST',
        ]);

        $this->dosen = User::create([
            'name' => 'Dosen Test, M.Kom.',
            'email' => 'dosentest@example.test',
            'password' => 'password',
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '198701012015011001',
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mhs_test@example.test',
            'password' => 'password',
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '230101001',
        ]);
    }

    /**
     * Uji alur user:
     * 1. Awalnya MK diset CPMK 1
     * 2. Kemudian diedit menjadi CPMK 1 & 2
     * 3. Kemudian diedit lagi menjadi CPMK 1, 2, & 3
     * Semua perubahan harus tersimpan konsisten.
     */
    public function test_cpmk_incremental_update_workflow(): void
    {
        $this->actingAs($this->adminProdi);

        $cpl = Cpl::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPL-01',
            'description' => 'Kemampuan analitis.',
        ]);

        $cpmk1 = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-01',
            'description' => 'CPMK Pertama',
            'threshold' => 65,
        ]);
        $cpmk1->cpls()->attach($cpl->id, ['weight' => 100]);

        $cpmk2 = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-02',
            'description' => 'CPMK Kedua',
            'threshold' => 70,
        ]);
        $cpmk2->cpls()->attach($cpl->id, ['weight' => 100]);

        $cpmk3 = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-03',
            'description' => 'CPMK Ketiga',
            'threshold' => 75,
        ]);
        $cpmk3->cpls()->attach($cpl->id, ['weight' => 100]);

        // 1. Simpan awal dengan hanya CPMK-01
        $storeResponse = $this->post(route('admin-prodi.akademik.matakuliah.store'), [
            'prodi_id' => $this->prodi->id,
            'code' => 'IF301',
            'name' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
            'semester_paket' => 3,
            'cpmk_ids' => [$cpmk1->id],
        ]);
        $storeResponse->assertRedirect();

        $mk = MataKuliah::where('code', 'IF301')->first();
        $this->assertNotNull($mk);
        $this->assertEquals(1, $mk->cpmks()->count());
        $this->assertTrue($mk->cpmks->contains($cpmk1->id));
        $this->assertFalse($mk->cpmks->contains($cpmk2->id));
        $this->assertFalse($mk->cpmks->contains($cpmk3->id));

        // 2. Edit menjadi CPMK 1 dan CPMK 2
        $updateResponse1 = $this->put(route('admin-prodi.akademik.matakuliah.update', $mk->id), [
            'code' => 'IF301',
            'name' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
            'semester_paket' => 3,
            'cpmk_ids' => [$cpmk1->id, $cpmk2->id],
        ]);
        $updateResponse1->assertRedirect();

        $mk->refresh();
        $this->assertEquals(2, $mk->cpmks()->count());
        $this->assertTrue($mk->cpmks->contains($cpmk1->id));
        $this->assertTrue($mk->cpmks->contains($cpmk2->id));
        $this->assertFalse($mk->cpmks->contains($cpmk3->id));

        // 3. Edit lagi menjadi CPMK 1, 2, dan 3
        $updateResponse2 = $this->put(route('admin-prodi.akademik.matakuliah.update', $mk->id), [
            'code' => 'IF301',
            'name' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
            'semester_paket' => 3,
            'cpmk_ids' => [$cpmk1->id, $cpmk2->id, $cpmk3->id],
        ]);
        $updateResponse2->assertRedirect();

        $mk->refresh();
        $this->assertEquals(3, $mk->cpmks()->count());
        $this->assertTrue($mk->cpmks->contains($cpmk1->id));
        $this->assertTrue($mk->cpmks->contains($cpmk2->id));
        $this->assertTrue($mk->cpmks->contains($cpmk3->id));
    }

    /**
     * CPMK yang belum/tidak memiliki mapping ke CPL (unmapped) tetap bisa
     * dipilih dan tersimpan pada mata kuliah, tidak hilang silently.
     */
    public function test_unmapped_cpmk_persists_properly(): void
    {
        $this->actingAs($this->adminProdi);

        // CPMK tanpa CPL terhubung
        $unmappedCpmk = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-STANDALONE',
            'description' => 'CPMK Mandiri tanpa CPL',
            'threshold' => 60,
        ]);

        $response = $this->post(route('admin-prodi.akademik.matakuliah.store'), [
            'prodi_id' => $this->prodi->id,
            'code' => 'IF302',
            'name' => 'Topik Khusus',
            'sks' => 2,
            'semester_paket' => 5,
            'cpmk_ids' => [$unmappedCpmk->id],
        ]);
        $response->assertRedirect();

        $mk = MataKuliah::where('code', 'IF302')->first();
        $this->assertNotNull($mk);
        $this->assertEquals(1, $mk->cpmks()->count());
        $this->assertTrue($mk->cpmks->contains($unmappedCpmk->id));

        // Verifikasi pada tampilan index matakuliah terhitung
        $indexResponse = $this->get(route('admin-prodi.akademik.matakuliah', ['prodi_id' => $this->prodi->id]));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('CPMK-STANDALONE');
    }

    /**
     * CPMK mata kuliah terintegrasi penuh ke PenilaianController dosen,
     * pembuatan asesmen, dan kalkulasi nilai mahasiswa.
     */
    public function test_cpmk_integrates_with_dosen_penilaian_and_student_grading(): void
    {
        $cpl = Cpl::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPL-02',
            'description' => 'Kemampuan implementasi sistem.',
        ]);

        $cpmk1 = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-10',
            'description' => 'Perancangan Database',
            'threshold' => 70,
        ]);
        $cpmk1->cpls()->attach($cpl->id, ['weight' => 50]);

        $cpmk2 = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-11',
            'description' => 'Optimasi Query',
            'threshold' => 70,
        ]);
        $cpmk2->cpls()->attach($cpl->id, ['weight' => 50]);

        $mk = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF303',
            'name' => 'Basis Data Lanjut',
            'sks' => 3,
            'semester_paket' => 3,
        ]);
        $mk->cpmks()->sync([$cpmk1->id, $cpmk2->id]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'BDL-01',
            'enrollment_code' => 'BDL01',
            'max_students' => 40,
        ]);
        $section->students()->attach($this->mahasiswa->id);

        // 1. Dosen / Sistem mengambil CPMK untuk mata kuliah kelas
        $cpmksForDosen = Cpmk::forMataKuliah($section->mata_kuliah_id)->get();

        $this->assertCount(2, $cpmksForDosen);
        $this->assertTrue($cpmksForDosen->contains('id', $cpmk1->id));
        $this->assertTrue($cpmksForDosen->contains('id', $cpmk2->id));

        // 2. Buat asesmen dosen dengan bobot CPMK
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS1',
            'name' => 'Tugas 1 Database Design',
            'type' => 'assignment',
            'final_weight' => 20,
        ]);
        $assessment->cpmks()->sync([
            $cpmk1->id => ['weight' => 60],
            $cpmk2->id => ['weight' => 40],
        ]);

        // 3. Simpan nilai mahasiswa
        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'score' => 85,
        ]);

        // 4. Verifikasi dosen melihat nilai dan rekap CPMK pada halaman rekap penilaian
        $this->actingAs($this->dosen);
        $response = $this->get(route('dosen.penilaian.rekap', $section->id));
        $response->assertStatus(200);
        $response->assertSee('CPMK-10');
        $response->assertSee('CPMK-11');
    }

    /**
     * Opsi 3: Contextual / Independent Mapping per Mata Kuliah.
     * Menguji bahwa jika CPMK 1 terhubung ke CPL 1 dan CPL 2 di master kurikulum,
     * tetapi admin prodi hanya memilih CPMK 1 di bawah CPL 1 untuk Mata Kuliah X,
     * maka:
     * - Mata Kuliah X hanya terhubung ke CPL 1 (CPL 2 tidak ikut terdeteksi).
     * - Penilaian / Asesmen Dosen hanya mendeteksi CPL 1.
     * - Nilai mahasiswa untuk CPMK 1 hanya mengalir ke CPL 1, bukan CPL 2.
     * - Jika Mata Kuliah X diedit untuk memilih CPMK 1 di bawah CPL 1 dan CPL 2,
     *   maka kedua CPL terdeteksi.
     */
    public function test_option_3_contextual_cpmk_cpl_selection_isolates_parent_cpl(): void
    {
        $this->actingAs($this->adminProdi);

        // 1. Setup master kurikulum: CPL-01 dan CPL-02
        $cpl1 = Cpl::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPL-01',
            'description' => 'Kemampuan analitis rekayasa.',
        ]);
        $cpl2 = Cpl::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPL-02',
            'description' => 'Penguasaan pemrograman dan teknologi.',
        ]);

        // CPMK-01 terhubung ke CPL-01 dan CPL-02 di master kurikulum
        $cpmk1 = Cpmk::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'CPMK-01',
            'description' => 'Mampu merancang arsitektur sistem.',
            'threshold' => 65,
        ]);
        $cpmk1->cpls()->attach([
            $cpl1->id => ['weight' => 50],
            $cpl2->id => ['weight' => 50],
        ]);

        // 2. Simpan Mata Kuliah dengan hanya memilih CPMK-01 di bawah CPL-01 (format cpmk_cpl_pairs)
        $response = $this->post(route('admin-prodi.akademik.matakuliah.store'), [
            'prodi_id' => $this->prodi->id,
            'code' => 'IF401',
            'name' => 'Arsitektur Perangkat Lunak',
            'sks' => 3,
            'semester_paket' => 4,
            'cpmk_cpl_pairs' => ["{$cpmk1->id}_{$cpl1->id}"],
        ]);
        $response->assertRedirect();

        $mk = MataKuliah::where('code', 'IF401')->first();
        $this->assertNotNull($mk);

        // Verifikasi hubungan kontekstual pada Mata Kuliah
        $this->assertEquals(["{$cpmk1->id}_{$cpl1->id}"], $mk->cpmk_cpl_pairs);
        $contextualCpls = $mk->contextualCpls();
        $this->assertCount(1, $contextualCpls);
        $this->assertTrue($contextualCpls->contains('id', $cpl1->id));
        $this->assertFalse($contextualCpls->contains('id', $cpl2->id), 'CPL-02 tidak boleh terdeteksi karena tidak dipilih pada mata kuliah ini');

        // 3. Verifikasi pada Kelas dan Dosen Penilaian
        $section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'APL-01',
            'enrollment_code' => 'APL01',
            'max_students' => 40,
        ]);
        $section->students()->attach($this->mahasiswa->id);

        $this->actingAs($this->dosen);
        $penilaianResponse = $this->get(route('dosen.penilaian.cpl', $section->id));
        $penilaianResponse->assertStatus(200);
        $penilaianResponse->assertSee('CPL-01');
        $penilaianResponse->assertDontSee('CPL-02');

        // 4. Verifikasi perhitungan OBE: Nilai hanya mengalir ke CPL-01
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-APL',
            'name' => 'Tugas Desain Arsitektur',
            'type' => 'assignment',
            'final_weight' => 100,
        ]);
        $assessment->cpmks()->sync([$cpmk1->id => ['weight' => 100]]);

        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'score' => 90,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);

        $obeService = app(\App\Services\ObeCalculationService::class);
        // Nilai CPL-01 harus terhitung 90
        $cpl1Score = $obeService->cplScore($cpl1, $this->mahasiswa->id, $section->id);
        $this->assertEquals(90.0, $cpl1Score);

        // Nilai CPL-02 pada section ini harus null (karena tidak diampu di section ini)
        $cpl2Score = $obeService->cplScore($cpl2, $this->mahasiswa->id, $section->id);
        $this->assertNull($cpl2Score, 'Skor CPL-02 harus null karena tidak diampu di kelas ini');

        // 5. Ubah Mata Kuliah untuk memilih CPMK-01 pada CPL-01 DAN CPL-02
        $this->actingAs($this->adminProdi);
        $updateResponse = $this->put(route('admin-prodi.akademik.matakuliah.update', $mk->id), [
            'code' => 'IF401',
            'name' => 'Arsitektur Perangkat Lunak',
            'sks' => 3,
            'semester_paket' => 4,
            'cpmk_cpl_pairs' => ["{$cpmk1->id}_{$cpl1->id}", "{$cpmk1->id}_{$cpl2->id}"],
        ]);
        $updateResponse->assertRedirect();

        $mk->refresh();
        $this->assertCount(2, $mk->contextualCpls());
        $this->assertTrue($mk->contextualCpls()->contains('id', $cpl1->id));
        $this->assertTrue($mk->contextualCpls()->contains('id', $cpl2->id));

        // Sekarang CPL-02 juga terhitung untuk kelas ini
        $cpl2ScoreAfter = $obeService->cplScore($cpl2, $this->mahasiswa->id, $section->id);
        $this->assertEquals(90.0, $cpl2ScoreAfter);
    }
}
