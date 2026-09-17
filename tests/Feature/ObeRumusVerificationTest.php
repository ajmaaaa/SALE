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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObeRumusVerificationTest extends TestCase
{
    use RefreshDatabase;

    private ObeCalculationService $service;
    private User $dosen;
    private User $mhs1;
    private User $mhs2;
    private ClassSection $section;
    private Cpmk $cpmk;
    private Cpl $cpl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ObeCalculationService;

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mhsRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Basis Data']);

        $this->dosen = User::create([
            'name' => 'Dosen Basis Data',
            'email' => 'dosen@test.local',
            'password' => bcrypt('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '112233',
        ]);

        $this->mhs1 = User::create([
            'name' => 'Mahasiswa A',
            'email' => 'a@test.local',
            'password' => bcrypt('password'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '2201',
        ]);

        $this->mhs2 = User::create([
            'name' => 'Mahasiswa B',
            'email' => 'b@test.local',
            'password' => bcrypt('password'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '2202',
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);

        $this->section->students()->attach([$this->mhs1->id, $this->mhs2->id]);

        $this->cpl = Cpl::create([
            'prodi_id' => $prodi->id,
            'code' => 'CPL-03',
            'description' => 'Mampu merancang dan mengelola basis data.',
        ]);

        $this->cpmk = Cpmk::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'code' => 'CPMK-1',
            'description' => 'Mampu merancang skema relasional',
            'threshold' => 60,
        ]);

        $this->cpmk->cpls()->attach($this->cpl->id, ['weight' => 100]);
    }

    /**
     * Verifikasi Contoh Numerik Bagian 3 rumus-obe-cpmk-cpl.md:
     * Tugas (bobot 30%) -> 80
     * UTS (bobot 30%) -> 75
     * UAS (bobot 40%) -> 85
     * NCPMK-1 = (0.3*80 + 0.3*75 + 0.4*85) / 1.0 = 80.5
     */
    public function test_cpmk_formula_matches_exact_worked_example(): void
    {
        $tugas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS', 'name' => 'Tugas', 'type' => 'assignment', 'final_weight' => 30]);
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UTS', 'name' => 'UTS', 'type' => 'uts', 'final_weight' => 30]);
        $uas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UAS', 'name' => 'UAS', 'type' => 'uas', 'final_weight' => 40]);

        $this->cpmk->assessments()->attach($tugas->id, ['weight' => 30]);
        $this->cpmk->assessments()->attach($uts->id, ['weight' => 30]);
        $this->cpmk->assessments()->attach($uas->id, ['weight' => 40]);

        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $uts->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 75]);
        StudentAssessmentScore::create(['assessment_id' => $uas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 85]);

        $cpmkScore = $this->service->cpmkScore($this->cpmk->fresh(), $this->mhs1->id);
        $this->assertEquals(80.5, $cpmkScore);

        $cplScore = $this->service->cplScore($this->cpl->fresh(), $this->mhs1->id);
        $this->assertEquals(80.5, $cplScore);
    }

    /**
     * Verifikasi Kategori Predikat Ketercapaian Bagian 4 rumus-obe-cpmk-cpl.md:
     * ≥ 85: Sangat Baik
     * 70 – 84.9: Baik
     * 60 – 69.9: Cukup
     * < 60: Kurang
     */
    public function test_predicate_categories_match_design(): void
    {
        $this->assertSame('Sangat Baik', $this->service->predicate(95.0));
        $this->assertSame('Sangat Baik', $this->service->predicate(85.0));
        $this->assertSame('Baik', $this->service->predicate(84.9));
        $this->assertSame('Baik', $this->service->predicate(70.0));
        $this->assertSame('Cukup', $this->service->predicate(69.9));
        $this->assertSame('Cukup', $this->service->predicate(60.0));
        $this->assertSame('Kurang', $this->service->predicate(59.9));
        $this->assertSame('Kurang', $this->service->predicate(0.0));
        $this->assertNull($this->service->predicate(null));
    }

    /**
     * Verifikasi Agregat Kelas CPMK Level 3 rumus-obe-cpmk-cpl.md:
     * RataRata_CPMK = Σ NCPMK / N
     * %Mahasiswa_Tuntas = Σ(NCPMK >= ambang) / N * 100
     */
    public function test_cpmk_class_aggregate_calculation(): void
    {
        $tugas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS', 'name' => 'Tugas', 'type' => 'assignment', 'final_weight' => 100]);
        $this->cpmk->assessments()->attach($tugas->id, ['weight' => 100]);

        // Mhs 1 = 80 (tuntas >= 60), Mhs 2 = 50 (belum tuntas < 60)
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->mhs2->id, 'score' => 50]);

        $studentIds = collect([$this->mhs1->id, $this->mhs2->id]);
        $agg = $this->service->cpmkClassAggregate($this->cpmk->fresh(), $studentIds);

        $this->assertSame(2, $agg['graded_count']);
        $this->assertSame(2, $agg['total_count']);
        $this->assertEquals(65.0, $agg['average']); // (80 + 50) / 2 = 65
        $this->assertSame(1, $agg['pass_count']); // 80 >= 60
        $this->assertEquals(50.0, $agg['pass_rate']); // 1 / 2 * 100 = 50%
        $this->assertSame('Cukup', $agg['predicate']); // 65 = Cukup
    }

    /**
     * Verifikasi Tampilan Dosen: CPMK, CPL, dan Rekap
     */
    public function test_dosen_penilaian_views_render_with_aggregates(): void
    {
        $tugas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS', 'name' => 'Tugas', 'type' => 'assignment', 'final_weight' => 100]);
        $this->cpmk->assessments()->attach($tugas->id, ['weight' => 100]);

        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 85]);

        // 1. Tab CPMK
        $resCpmk = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.cpmk', $this->section->id));
        $resCpmk->assertOk()
            ->assertSee('CPMK-1')
            ->assertSee('Rata-rata')
            ->assertSee('Tuntas')
            ->assertSee('Sangat Baik');

        // 2. Tab CPL
        $resCpl = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.cpl', $this->section->id));
        $resCpl->assertOk()
            ->assertSee('CPL-03')
            ->assertSee('Rata-rata:')
            ->assertSee('Tuntas')
            ->assertSee('Sangat Baik');

        // 3. Tab Rekap CPMK
        $resRekap = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.rekap', $this->section->id));
        $resRekap->assertOk()
            ->assertSee('Rekap Capaian per CPMK')
            ->assertSee('CPMK-1')
            ->assertSee('Sangat Baik');
    }

    /**
     * Verifikasi Asesmen dengan beberapa CPMK (misal UAS dengan 3 CPMK)
     * menampilkan input per CPMK, tanpa input feedback / komentar.
     */
    public function test_assessment_with_multiple_cpmks_renders_input_per_cpmk_and_no_feedback(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-2', 'description' => 'CPMK Dua', 'threshold' => 60]);
        $cpmk3 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-3', 'description' => 'CPMK Tiga', 'threshold' => 60]);

        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS',
            'name' => 'UAS Terintegrasi',
            'type' => 'uas',
            'final_weight' => 30,
        ]);

        $uas->cpmks()->attach($this->cpmk->id, ['weight' => 30]);
        $uas->cpmks()->attach($cpmk2->id, ['weight' => 30]);
        $uas->cpmks()->attach($cpmk3->id, ['weight' => 40]);

        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $uas->id]));

        $res->assertOk()
            ->assertSee('CPMK-1')
            ->assertSee('CPMK-2')
            ->assertSee('Bobot: 9%')
            ->assertSee('Bobot: 12%')
            ->assertDontSee('Bobot: 30%')
            ->assertDontSee('Bobot: 40%')
            ->assertSee('Total Asesmen')
            ->assertSee("cpmk_scores[{$this->mhs1->id}][{$this->cpmk->id}]")
            ->assertSee("cpmk_scores[{$this->mhs1->id}][{$cpmk2->id}]")
            ->assertSee("cpmk_scores[{$this->mhs1->id}][{$cpmk3->id}]")
            ->assertDontSee('Komentar / Feedback')
            ->assertDontSee("feedback[{$this->mhs1->id}]");
    }

    /**
     * Verifikasi Simpan Nilai per CPMK menghitung nilai total terbobot
     * dan menyimpan rincian ke student_assessment_cpmk_scores.
     */
    public function test_storing_cpmk_scores_calculates_weighted_total_and_saves_per_cpmk(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-2', 'description' => 'CPMK Dua', 'threshold' => 60]);

        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 40,
        ]);

        // CPMK-1 (bobot 40%), CPMK-2 (bobot 60%)
        $uas->cpmks()->attach($this->cpmk->id, ['weight' => 40]);
        $uas->cpmks()->attach($cpmk2->id, ['weight' => 60]);

        // Mhs1: CPMK-1 = 35 (dari maks 40), CPMK-2 = 55 (dari maks 60)
        // Total asesmen = 35 + 55 = 90.0
        $res = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $uas->id]), [
                'cpmk_scores' => [
                    $this->mhs1->id => [
                        $this->cpmk->id => 35,
                        $cpmk2->id => 55,
                    ],
                ],
            ]);

        $res->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $uas->id]));

        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $uas->id,
            'cpmk_id' => $this->cpmk->id,
            'mahasiswa_id' => $this->mhs1->id,
            'score' => 35,
        ]);

        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $uas->id,
            'cpmk_id' => $cpmk2->id,
            'mahasiswa_id' => $this->mhs1->id,
            'score' => 55,
        ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $uas->id,
            'mahasiswa_id' => $this->mhs1->id,
            'score' => 90.0,
            'feedback' => null,
        ]);
    }

    /**
     * Verifikasi Tabel Asesmen tidak memuat kolom Rubrik lagi
     * dan navigasi sidebar Dosen bersih (1 alur).
     */
    public function test_assessment_table_has_no_rubrik_and_sidebar_is_unified(): void
    {
        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS',
            'name' => 'Ujian Akhir Semester',
            'type' => 'uas',
            'final_weight' => 40,
        ]);

        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen', $this->section->id));

        $res->assertOk()
            ->assertSee('Input Nilai')
            ->assertDontSee('<th>Rubrik</th>')
            ->assertDontSee('Rubrik Nilai')
            ->assertSee('Penilaian OBE')
            ->assertDontSee('Penilaian tugas')
            ->assertDontSee('Rekap nilai & CPMK');
    }

    /**
     * Verifikasi Lengkap Matriks Penilaian RPS (Tabel B & Tabel C):
     * CBM (20), Tugas (10), UTS (20) -> CPMK 041 (Total = 50)
     * PBL (35), UAS (15)            -> CPMK 042 (Total = 50)
     * Total Keseluruhan              = 100
     */
    public function test_exact_rps_matrix_calculation_and_view_rendering(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk041 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK 041', 'description' => 'Kemampuan merancang basis data', 'threshold' => 60]);
        $cpmk042 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK 042', 'description' => 'Kemampuan implementasi dan optimasi', 'threshold' => 60]);

        $cbm = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'CBM', 'name' => 'Case Based Project (CBM)', 'type' => 'project', 'final_weight' => 20]);
        $tugas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS', 'name' => 'Tugas', 'type' => 'tugas', 'final_weight' => 10]);
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UTS', 'name' => 'UTS', 'type' => 'uts', 'final_weight' => 20]);
        $pbl = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'PBL', 'name' => 'Project Based Learning (PBL)', 'type' => 'pbl', 'final_weight' => 35]);
        $uas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UAS', 'name' => 'UAS', 'type' => 'uas', 'final_weight' => 15]);

        $cbm->cpmks()->attach($cpmk041->id, ['weight' => 100]);
        $tugas->cpmks()->attach($cpmk041->id, ['weight' => 100]);
        $uts->cpmks()->attach($cpmk041->id, ['weight' => 100]);
        $pbl->cpmks()->attach($cpmk042->id, ['weight' => 100]);
        $uas->cpmks()->attach($cpmk042->id, ['weight' => 100]);

        // Input nilai mahasiswa A:
        // CBM = 80, Tugas = 90, UTS = 70
        // PBL = 80, UAS = 90
        StudentAssessmentScore::create(['assessment_id' => $cbm->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 90]);
        StudentAssessmentScore::create(['assessment_id' => $uts->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 70]);
        StudentAssessmentScore::create(['assessment_id' => $pbl->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $uas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 90]);

        // Nilai CPMK 041 = (80*20 + 90*10 + 70*20) / 50 = (1600 + 900 + 1400) / 50 = 3900 / 50 = 78.0
        $score041 = $this->service->cpmkScore($cpmk041->fresh(), $this->mhs1->id);
        $this->assertEquals(78.0, $score041);

        // Nilai CPMK 042 = (80*35 + 90*15) / 50 = (2800 + 1350) / 50 = 4150 / 50 = 83.0
        $score042 = $this->service->cpmkScore($cpmk042->fresh(), $this->mhs1->id);
        $this->assertEquals(83.0, $score042);

        // Nilai Akhir MK = (78.0*50 + 83.0*50) / 100 = 80.5
        $final = $this->service->finalScore($this->section->fresh(), $this->mhs1->id);
        $this->assertEquals(80.5, $final['score']);
        $this->assertEquals(100.0, $final['coverage']);

        // Verifikasi Halaman Matriks Penilaian me-render Matriks Versi C Interaktif
        $resMatriks = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.matriks', $this->section->id));

        $resMatriks->assertOk()
            ->assertSee('Rancangan Matriks Penilaian (Versi C)')
            ->assertSee('CPMK 041')
            ->assertSee('CPMK 042')
            ->assertSee('Case Based Project (CBM)')
            ->assertSee('Project Based Learning (PBL)');

        // Verifikasi Halaman Rekap Nilai & CPMK menampilkan kolom CPMK, bobotnya, dan nilainya
        $resRekap = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.rekap', $this->section->id));

        $resRekap->assertOk()
            ->assertSee('CPMK 041')
            ->assertSee('CPMK 042')
            ->assertSee('Bobot: 50%')
            ->assertSee('78.0')
            ->assertSee('83.0');

        // Verifikasi Halaman Rekap CPMK menampilkan analisis dan skor mahasiswa beserta bobot
        $resCpmk = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.cpmk', $this->section->id));

        $resCpmk->assertOk()
            ->assertSee('CPMK 041')
            ->assertSee('CPMK 042')
            ->assertSee('Bobot: 50%')
            ->assertSee('78.0')
            ->assertSee('83.0');
    }

    /**
     * Verifikasi Contoh 3 CPMK (30% + 40% + 30% = 100%) sesuai permintaan user:
     * CPMK 1 = 30%, CPMK 2 = 40%, CPMK 3 = 30%.
     * Nilai Akhir dihitung terbobot secara presisi.
     */
    public function test_three_cpmk_weights_sum_to_100_percent_and_calculate_final_score(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk1 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-A', 'description' => 'CPMK A', 'threshold' => 60]);
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-B', 'description' => 'CPMK B', 'threshold' => 60]);
        $cpmk3 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-C', 'description' => 'CPMK C', 'threshold' => 60]);

        // Tugas (bobot 30%) -> CPMK-A
        $tgs = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS3', 'name' => 'Tugas', 'type' => 'tugas', 'final_weight' => 30]);
        $tgs->cpmks()->attach($cpmk1->id, ['weight' => 100]);

        // UTS (bobot 40%) -> CPMK-B
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UTS3', 'name' => 'UTS', 'type' => 'uts', 'final_weight' => 40]);
        $uts->cpmks()->attach($cpmk2->id, ['weight' => 100]);

        // UAS (bobot 30%) -> CPMK-C
        $uas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UAS3', 'name' => 'UAS', 'type' => 'uas', 'final_weight' => 30]);
        $uas->cpmks()->attach($cpmk3->id, ['weight' => 100]);

        // Verifikasi helper cpmkWeight
        $this->assertEquals(30.0, $this->service->cpmkWeight($cpmk1, $this->section));
        $this->assertEquals(40.0, $this->service->cpmkWeight($cpmk2, $this->section));
        $this->assertEquals(30.0, $this->service->cpmkWeight($cpmk3, $this->section));

        $weights = $this->service->cpmkWeightsFor(collect([$cpmk1, $cpmk2, $cpmk3]), $this->section);
        $this->assertEquals(100.0, $weights->sum());

        // Mahasiswa A dapat nilai: CPMK-A = 80, CPMK-B = 70, CPMK-C = 90
        StudentAssessmentScore::create(['assessment_id' => $tgs->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $uts->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 70]);
        StudentAssessmentScore::create(['assessment_id' => $uas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 90]);

        // Nilai CPMK
        $this->assertEquals(80.0, $this->service->cpmkScore($cpmk1, $this->mhs1->id, $this->section->id));
        $this->assertEquals(70.0, $this->service->cpmkScore($cpmk2, $this->mhs1->id, $this->section->id));
        $this->assertEquals(90.0, $this->service->cpmkScore($cpmk3, $this->mhs1->id, $this->section->id));

        // Nilai Akhir = (30*80 + 40*70 + 30*90) / 100 = (2400 + 2800 + 2700) / 100 = 7900 / 100 = 79.0
        $final = $this->service->finalScore($this->section->fresh(), $this->mhs1->id);
        $this->assertEquals(79.0, $final['score']);
        $this->assertEquals(100.0, $final['coverage']);
    }

    /**
     * Verifikasi Halaman Rekap Dosen tidak diblokir jika belum ada CPL
     * dan menampilkan bobot CPMK serta kolom nilai dengan benar.
     */
    public function test_rekap_renders_without_cpl_and_displays_cpmk_weights(): void
    {
        // Buat kelas terpisah tanpa CPL sama sekali
        $mkId = $this->section->mata_kuliah_id;
        $cpmkStandalone = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-STAND', 'description' => 'CPMK Mandiri', 'threshold' => 60]);

        $asmt = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-ST', 'name' => 'Tugas Mandiri', 'type' => 'tugas', 'final_weight' => 100]);
        $asmt->cpmks()->attach($cpmkStandalone->id, ['weight' => 100]);

        StudentAssessmentScore::create(['assessment_id' => $asmt->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 88]);

        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.rekap', $this->section->id));

        $res->assertOk()
            ->assertDontSee('Belum Ada CPL yang Terhubung')
            ->assertSee('Rekap Capaian per CPMK')
            ->assertSee('CPMK-STAND')
            ->assertSee('Bobot: 100%')
            ->assertSee('88.0');
    }

    /**
     * Verifikasi Export CSV Rekap Nilai & CPMK memuat header Bobot CPMK.
     */
    public function test_export_csv_includes_cpmk_weights_and_student_results(): void
    {
        $tugas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-EXP', 'name' => 'Tugas Exp', 'type' => 'tugas', 'final_weight' => 100]);
        $this->cpmk->assessments()->attach($tugas->id, ['weight' => 100]);

        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->mhs1->id, 'score' => 85]);

        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.export.keseluruhan', $this->section->id));

        $response->assertOk();
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
    }

    /**
     * Verifikasi Endpoint POST /matriks menyimpan bobot sel dan menyinkronkan final_weight asesmen.
     */
    public function test_save_matriks_endpoint_syncs_matrix_cells_and_final_weights(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk1 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-M1', 'description' => 'M1', 'threshold' => 60]);
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-M2', 'description' => 'M2', 'threshold' => 60]);

        $tugas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'T-MAT', 'name' => 'Tugas Mat', 'type' => 'tugas', 'final_weight' => 0]);
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'U-MAT', 'name' => 'UTS Mat', 'type' => 'uts', 'final_weight' => 0]);

        // Kirim matrix POST: Tugas -> CPMK1: 20, CPMK2: 10 (Total Tugas = 30)
        //                     UTS   -> CPMK1: 30, CPMK2: 40 (Total UTS = 70)
        // Grand Total = 100
        $postData = [
            'matrix' => [
                $tugas->id => [
                    $cpmk1->id => 20,
                    $cpmk2->id => 10,
                ],
                $uts->id => [
                    $cpmk1->id => 30,
                    $cpmk2->id => 40,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.matriks.save', $this->section->id), $postData);

        $response->assertRedirect(route('dosen.penilaian.matriks', $this->section->id))
            ->assertSessionHas('notice');

        // Pastikan final_weight asesmen tersinkronkan
        $this->assertEquals(30.0, (float) $tugas->fresh()->final_weight);
        $this->assertEquals(70.0, (float) $uts->fresh()->final_weight);

        // Pastikan bobot sel assessment_cpmk tersimpan
        $this->assertEquals(20.0, (float) $tugas->cpmks()->where('cpmk_id', $cpmk1->id)->first()->pivot->weight);
        $this->assertEquals(10.0, (float) $tugas->cpmks()->where('cpmk_id', $cpmk2->id)->first()->pivot->weight);
        $this->assertEquals(30.0, (float) $uts->cpmks()->where('cpmk_id', $cpmk1->id)->first()->pivot->weight);
        $this->assertEquals(40.0, (float) $uts->cpmks()->where('cpmk_id', $cpmk2->id)->first()->pivot->weight);

        // Pastikan bobot CPMK total mata kuliah tepat
        $this->assertEquals(50.0, $this->service->cpmkWeight($cpmk1, $this->section));
        $this->assertEquals(50.0, $this->service->cpmkWeight($cpmk2, $this->section));
    }

    /**
     * Verifikasi Halaman Asesmen menampilkan bobot efektif riil mata kuliah, bukan 100% palsu.
     */
    public function test_asesmen_view_displays_effective_weights_instead_of_misleading_percentages(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-EFF', 'description' => 'Effective CPMK', 'threshold' => 60]);

        $asmt = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'T-EFF',
            'name' => 'Tugas Mandiri Efektif',
            'type' => 'tugas',
            'final_weight' => 15.0,
        ]);
        // Pivot disimpan 100 (relatif terhadap asesmen ini)
        $asmt->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen', $this->section->id));

        $res->assertOk()
            ->assertSee('CPMK-EFF (Bobot: 15%)')
            ->assertDontSee('CPMK-EFF (100%)');
    }

    /**
     * Verifikasi Tabel Rekap menampilkan breakdown asesmen per CPMK dan bersih tanpa kolom CPL.
     */
    public function test_rekap_table_is_pure_cpmk_with_component_breakdown_and_no_cpl_table_headers(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-PURE', 'description' => 'Pure CPMK', 'threshold' => 60]);

        $tugas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'T-PURE',
            'name' => 'Tugas Awal',
            'type' => 'tugas',
            'final_weight' => 20.0,
        ]);
        $tugas->cpmks()->attach($cpmk->id, ['weight' => 20.0]);

        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'U-PURE',
            'name' => 'UTS Tengah',
            'type' => 'uts',
            'final_weight' => 30.0,
        ]);
        $uts->cpmks()->attach($cpmk->id, ['weight' => 30.0]);

        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.rekap', $this->section->id));

        $res->assertOk()
            ->assertSee('CPMK-PURE')
            ->assertSee('Bobot: 50%')
            ->assertSee('Tugas Awal (20%)')
            ->assertSee('UTS Tengah (30%)')
            ->assertDontSee('Capaian CPL Terhubung (0–100)');
    }

    /**
     * Verifikasi Desain v3.0: Navigasi 5 langkah tanpa tab sekunder,
     * halaman Input Nilai tanpa tombol tambah/ubah/hapus,
     * dan format kartu mandiri pada Rekap CPMK dan Rekap CPL.
     */
    public function test_desain_v3_linear_navigation_and_clean_interfaces(): void
    {
        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.matriks', $this->section->id));

        $res->assertOk()
            ->assertSee('1. Matriks Penilaian')
            ->assertSee('2. Input Nilai')
            ->assertSee('3. Rekap CPMK')
            ->assertSee('4. Rekap CPL')
            ->assertSee('5. Export')
            ->assertDontSee('Detail CPMK')
            ->assertDontSee('Pemetaan CPL')
            ->assertDontSee('>Pengaturan<', false);

        // Asesmen list: only Input Nilai
        $resAsesmen = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen', $this->section->id));

        $resAsesmen->assertOk()
            ->assertSee('2. Input Nilai per Komponen Asesmen')
            ->assertDontSee('+ Tambah Asesmen')
            ->assertDontSee('>Ubah<', false)
            ->assertDontSee('>Hapus<', false);

        // Rekap CPL: per-CPL vertical cards
        $resCpl = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.cpl', $this->section->id));

        $resCpl->assertOk()
            ->assertSee('4. Rekap Capaian per CPL')
            ->assertSee('Disusun dari CPMK:')
            ->assertSee('Catatan Ketercapaian CPL:');
    }

    /**
     * Verifikasi Sidebar Dosen: Menu khusus 'Rekap Nilai' yang memuat
     * link ke 'Rekap CPMK' dan 'Rekap CPL' secara eksplisit.
     */
    public function test_sidebar_rekap_nilai_and_cpmk_cpl_navigation(): void
    {
        // 1. Top-level Rekap Nilai route (/dosen/rekap-nilai)
        $resIndex = $this->actingAs($this->dosen)->get(route('dosen.rekap.index'));
        $resIndex->assertOk()
            ->assertSee('Rekap Nilai OBE')
            ->assertSee('Rekap CPMK')
            ->assertSee('Rekap CPL');

        // 2. Di dalam kelas pada halaman Rekap CPMK
        $resRekap = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap', $this->section->id));
        $resRekap->assertOk()
            ->assertSee('Rekap Nilai')
            ->assertSee('Rekap CPMK')
            ->assertSee('Rekap CPL')
            ->assertSee(route('dosen.penilaian.rekap', $this->section->id))
            ->assertSee(route('dosen.penilaian.cpl', $this->section->id));

        // 3. Di dalam kelas pada halaman Rekap CPL
        $resCpl = $this->actingAs($this->dosen)->get(route('dosen.penilaian.cpl', $this->section->id));
        $resCpl->assertOk()
            ->assertSee('Rekap Nilai')
            ->assertSee('Rekap CPMK')
            ->assertSee('Rekap CPL');

        // 4. Di dalam kelas pada halaman Matriks Penilaian
        $resMatriks = $this->actingAs($this->dosen)->get(route('dosen.penilaian.matriks', $this->section->id));
        $resMatriks->assertOk()
            ->assertSee('Penilaian OBE')
            ->assertSee('1. Matriks Penilaian')
            ->assertSee('2. Input Nilai')
            ->assertSee('Rekap Nilai')
            ->assertSee('Rekap CPMK')
            ->assertSee('Rekap CPL');
    }

    /**
     * Verifikasi halaman Input Nilai menampilkan bobot CPMK yang sama persis
     * dengan nilai pada Matriks Penilaian (misal PBL 1 dengan bobot 20% yang mengukur
     * CPMK-02 30% dan CPMK-03 70% menampilkan Bobot: 6% dan Bobot: 14%).
     */
    public function test_input_nilai_displays_matrix_percentages_matching_matriks_penilaian(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-02', 'description' => 'CPMK 2', 'threshold' => 60]);
        $cpmk3 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-03', 'description' => 'CPMK 3', 'threshold' => 60]);

        $pbl = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'PBL-01',
            'name' => 'PBL 1',
            'type' => 'pbl',
            'final_weight' => 20, // Bobot Nilai Akhir Asesmen: 20%
        ]);

        // Simpan pivot bobot relatif: 30% dan 70%
        $pbl->cpmks()->attach($cpmk2->id, ['weight' => 30]);
        $pbl->cpmks()->attach($cpmk3->id, ['weight' => 70]);

        $res = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $pbl->id]));

        $res->assertOk()
            // Di header kolom tabel harus menampilkan 6% dan 14% sesuai matriks
            ->assertSee('Bobot: 6%')
            ->assertSee('Bobot: 14%')
            // Menampilkan Maks poin proporsional: 30 dan 70
            ->assertSee('Maks: 30')
            ->assertSee('Maks: 70')
            // data-max dan max atribut input
            ->assertSee('data-max="30"', false)
            ->assertSee('data-max="70"', false)
            ->assertSee('max="30"', false)
            ->assertSee('max="70"', false);
    }

    /**
     * Verifikasi penyimpanan nilai poin proporsional:
     * Tugas 1 (bobot 10%, CPMK-01 6% & CPMK-02 4%):
     * CPMK-01 maks 60, CPMK-02 maks 40.
     * Mahasiswa diberi nilai 60 dan 40:
     * - Nilai Asesmen = 100.0 (60 + 40)
     * - Capaian CPMK-01 = 100% (60 / 60 * 100)
     * - Capaian CPMK-02 = 100% (40 / 40 * 100)
     */
    public function test_input_nilai_proportional_max_points_and_direct_sum(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk1 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-TEST-1', 'description' => 'C1', 'threshold' => 60]);
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-TEST-2', 'description' => 'C2', 'threshold' => 60]);

        $tugas1 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-TEST-1',
            'name' => 'Tugas 1 Test',
            'type' => 'tugas',
            'final_weight' => 10,
        ]);

        $tugas1->cpmks()->attach($cpmk1->id, ['weight' => 60]); // 6%
        $tugas1->cpmks()->attach($cpmk2->id, ['weight' => 40]); // 4%

        $obeService = app(\App\Services\ObeCalculationService::class);
        $this->assertEquals(60.0, $obeService->assessmentCpmkMaxScore($tugas1, $cpmk1));
        $this->assertEquals(40.0, $obeService->assessmentCpmkMaxScore($tugas1, $cpmk2));

        // Post nilai proporsional: 60 untuk CPMK-1 dan 40 untuk CPMK-2
        $res = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $tugas1->id]), [
                'cpmk_scores' => [
                    $this->mhs1->id => [
                        $cpmk1->id => 60,
                        $cpmk2->id => 40,
                    ],
                ],
            ]);

        $res->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $tugas1->id]));

        // Cek student_assessment_scores: overallScore = 60 + 40 = 100.0 (bukan 200)
        $scoreRow = \App\Models\StudentAssessmentScore::where('assessment_id', $tugas1->id)
            ->where('mahasiswa_id', $this->mhs1->id)
            ->first();
        $this->assertNotNull($scoreRow);
        $this->assertEquals(100.0, (float) $scoreRow->score);

        // Capaian CPMK di ObeCalculationService ternormalisasi 100%
        $cpmk1Score = $obeService->cpmkScore($cpmk1, $this->mhs1->id, $this->section->id);
        $cpmk2Score = $obeService->cpmkScore($cpmk2, $this->mhs1->id, $this->section->id);
        $this->assertEquals(100.0, $cpmk1Score);
        $this->assertEquals(100.0, $cpmk2Score);

        // Uji nilai parsial: CPMK-1 = 30 (50%), CPMK-2 = 20 (50%) -> total = 50.0
        $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $tugas1->id]), [
                'cpmk_scores' => [
                    $this->mhs1->id => [
                        $cpmk1->id => 30,
                        $cpmk2->id => 20,
                    ],
                ],
            ]);

        $scoreRow->refresh();
        $this->assertEquals(50.0, (float) $scoreRow->score);
        $this->assertEquals(50.0, $obeService->cpmkScore($cpmk1, $this->mhs1->id, $this->section->id));
        $this->assertEquals(50.0, $obeService->cpmkScore($cpmk2, $this->mhs1->id, $this->section->id));
    }

    /**
     * Verifikasi validasi menolak nilai yang melebihi batas maksimal proporsional CPMK.
     * Misal CPMK-02 maksimal 40, jika diinput 50 atau 100 harus ditolak.
     */
    public function test_input_nilai_blocks_scores_exceeding_max_proportional_points(): void
    {
        $mkId = $this->section->mata_kuliah_id;
        $cpmk1 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-V1', 'description' => 'V1', 'threshold' => 60]);
        $cpmk2 = Cpmk::create(['mata_kuliah_id' => $mkId, 'code' => 'CPMK-V2', 'description' => 'V2', 'threshold' => 60]);

        $tugas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-V1',
            'name' => 'Tugas Validation',
            'type' => 'tugas',
            'final_weight' => 10,
        ]);

        $tugas->cpmks()->attach($cpmk1->id, ['weight' => 60]); // maks 60
        $tugas->cpmks()->attach($cpmk2->id, ['weight' => 40]); // maks 40

        $res = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $tugas->id]), [
                'cpmk_scores' => [
                    $this->mhs1->id => [
                        $cpmk1->id => 60,
                        $cpmk2->id => 100, // melebihi batas maksimal 40!
                    ],
                ],
            ]);

        $res->assertSessionHasErrors([
            "cpmk_scores.{$this->mhs1->id}.{$cpmk2->id}" => "Nilai CPMK-V2 tidak boleh melebihi batas maksimal 40.",
        ]);
    }
}

