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

/**
 * Verifies the OBE formulas in ObeCalculationService against a small,
 * hand-verifiable dataset mirroring the requirement's example scenario
 * (assessments that measure more than one CPMK, a CPMK contributing to
 * more than one CPL, and a missing/ungraded score that must not be
 * treated as 0).
 */
class ObeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ObeCalculationService $service;

    private ClassSection $section;

    private User $student;

    private Cpmk $cpmk1;

    private Cpmk $cpmk2;

    private Cpl $cpl1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ObeCalculationService;

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data']);

        $dosen = User::create(['name' => 'Dosen Uji', 'email' => 'dosen-uji@test.local', 'password' => 'x', 'role_id' => $dosenRole->id]);
        $this->student = User::create(['name' => 'Mahasiswa Uji', 'email' => 'mhs-uji@test.local', 'password' => 'x', 'role_id' => $mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
        ]);

        $this->cpmk1 = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-01', 'description' => '...', 'threshold' => 65]);
        $this->cpmk2 = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-02', 'description' => '...', 'threshold' => 65]);

        $this->cpl1 = Cpl::create(['prodi_id' => $prodi->id, 'code' => 'CPL-01', 'description' => '...']);
        $this->cpl1->cpmks()->attach($this->cpmk1->id, ['weight' => 60]);
        $this->cpl1->cpmks()->attach($this->cpmk2->id, ['weight' => 40]);
    }

    /**
     * CPMK Score = (Tugas 1 x 30%) + (Tugas 2 x 20%) + (PBL 1 x 50%)
     * exactly as given in the requirement's own worked example.
     */
    public function test_cpmk_score_matches_manual_calculation(): void
    {
        $tugas1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-01', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 30]);
        $tugas2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-02', 'name' => 'Tugas 2', 'type' => 'tugas', 'final_weight' => 20]);
        $pbl1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'PBL-01', 'name' => 'PBL 1', 'type' => 'pbl', 'final_weight' => 50]);

        $this->cpmk1->assessments()->attach($tugas1->id, ['weight' => 30]);
        $this->cpmk1->assessments()->attach($tugas2->id, ['weight' => 20]);
        $this->cpmk1->assessments()->attach($pbl1->id, ['weight' => 50]);

        StudentAssessmentScore::create(['assessment_id' => $tugas1->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $tugas2->id, 'mahasiswa_id' => $this->student->id, 'score' => 90]);
        StudentAssessmentScore::create(['assessment_id' => $pbl1->id, 'mahasiswa_id' => $this->student->id, 'score' => 70]);

        // (80*0.3) + (90*0.2) + (70*0.5) = 24 + 18 + 35 = 77
        $this->assertEquals(77.0, $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id));
    }

    /**
     * A CPMK measured by assessments where one is not yet graded must
     * re-normalize against only the graded weight, never treat the
     * missing score as 0.
     */
    public function test_ungraded_assessment_is_excluded_not_zero(): void
    {
        $tugas1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-01', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 50]);
        $tugas2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-02', 'name' => 'Tugas 2', 'type' => 'tugas', 'final_weight' => 50]);

        $this->cpmk1->assessments()->attach($tugas1->id, ['weight' => 50]);
        $this->cpmk1->assessments()->attach($tugas2->id, ['weight' => 50]);

        StudentAssessmentScore::create(['assessment_id' => $tugas1->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        // Tugas 2 intentionally has no score row at all (not graded yet).

        // If Tugas 2 were wrongly treated as 0, result would be 40.
        // Correct: re-normalized to just Tugas 1's weight -> 80.
        $this->assertEquals(80.0, $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id));
    }

    /**
     * If NO assessment measuring a CPMK has been graded yet, the CPMK
     * score must be null, not 0.
     */
    public function test_cpmk_score_is_null_when_nothing_graded(): void
    {
        $tugas1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-01', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 100]);
        $this->cpmk1->assessments()->attach($tugas1->id, ['weight' => 100]);

        $this->assertNull($this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id));
    }

    /**
     * CPL Score = (CPMK 1 x 60%) + (CPMK 2 x 40%) as in the requirement's
     * own worked example.
     */
    public function test_cpl_score_matches_manual_calculation(): void
    {
        $a1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A1', 'name' => 'A1', 'type' => 'tugas', 'final_weight' => 50]);
        $a2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A2', 'name' => 'A2', 'type' => 'tugas', 'final_weight' => 50]);

        $this->cpmk1->assessments()->attach($a1->id, ['weight' => 100]);
        $this->cpmk2->assessments()->attach($a2->id, ['weight' => 100]);

        StudentAssessmentScore::create(['assessment_id' => $a1->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]); // CPMK-01 = 80
        StudentAssessmentScore::create(['assessment_id' => $a2->id, 'mahasiswa_id' => $this->student->id, 'score' => 70]); // CPMK-02 = 70

        // (80*0.6) + (70*0.4) = 48 + 28 = 76
        $this->assertEquals(76.0, $this->service->cplScore($this->cpl1->fresh(), $this->student->id));
    }

    /**
     * Final course grade must follow its own final_weight-based formula
     * and must NOT equal a simple average of CPL scores (explicit
     * requirement: these are two distinct calculation paths).
     */
    public function test_final_score_uses_final_weight_not_cpl_average(): void
    {
        $a1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A1', 'name' => 'A1', 'type' => 'tugas', 'final_weight' => 30]);
        $a2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A2', 'name' => 'A2', 'type' => 'uas', 'final_weight' => 70]);

        StudentAssessmentScore::create(['assessment_id' => $a1->id, 'mahasiswa_id' => $this->student->id, 'score' => 60]);
        StudentAssessmentScore::create(['assessment_id' => $a2->id, 'mahasiswa_id' => $this->student->id, 'score' => 90]);

        $result = $this->service->finalScore($this->section->fresh(), $this->student->id);

        // (60*0.3) + (90*0.7) = 18 + 63 = 81, fully covered (100%).
        $this->assertEquals(81.0, $result['score']);
        $this->assertEquals(100.0, $result['coverage']);
    }

    /**
     * When only part of the final_weight has been graded, the score is
     * a re-normalized provisional figure and coverage reports how much
     * of the total weight that represents — never presented as if the
     * course were fully graded.
     */
    public function test_final_score_reports_partial_coverage(): void
    {
        $a1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A1', 'name' => 'A1', 'type' => 'tugas', 'final_weight' => 30]);
        $a2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A2', 'name' => 'A2', 'type' => 'uas', 'final_weight' => 70]);

        StudentAssessmentScore::create(['assessment_id' => $a1->id, 'mahasiswa_id' => $this->student->id, 'score' => 60]);
        // A2 (UAS) not graded yet.

        $result = $this->service->finalScore($this->section->fresh(), $this->student->id);

        $this->assertEquals(60.0, $result['score']); // re-normalized to the only graded weight
        $this->assertEquals(30.0, $result['coverage']); // only 30/100 of final_weight is backed by a grade
    }

    public function test_cpmk_achievement_respects_threshold_and_null(): void
    {
        $this->cpmk1->threshold = 70;

        $this->assertTrue($this->service->cpmkAchieved($this->cpmk1, 75));
        $this->assertFalse($this->service->cpmkAchieved($this->cpmk1, 65));
        $this->assertNull($this->service->cpmkAchieved($this->cpmk1, null));
    }

    public function test_cpmk_score_details_reports_coverage_and_metadata(): void
    {
        $t1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'T1', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 40]);
        $t2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'T2', 'name' => 'Tugas 2', 'type' => 'tugas', 'final_weight' => 60]);

        $this->cpmk1->assessments()->attach($t1->id, ['weight' => 100]);
        $this->cpmk1->assessments()->attach($t2->id, ['weight' => 100]);

        StudentAssessmentScore::create(['assessment_id' => $t1->id, 'mahasiswa_id' => $this->student->id, 'score' => 85]);
        // T2 not graded yet

        $details = $this->service->cpmkScoreDetails($this->cpmk1->fresh(), $this->student->id, $this->section->id);

        $this->assertEquals(85.0, $details['score']);
        $this->assertEquals(40.0, $details['coverage']); // 40 / 100
        $this->assertEquals(40.0, $details['weight_graded']);
        $this->assertEquals(100.0, $details['weight_total']);
        $this->assertFalse($details['is_complete']);

        // When T2 is also graded
        StudentAssessmentScore::create(['assessment_id' => $t2->id, 'mahasiswa_id' => $this->student->id, 'score' => 95]);
        $completeDetails = $this->service->cpmkScoreDetails($this->cpmk1->fresh(), $this->student->id, $this->section->id);

        $this->assertEquals(91.0, $completeDetails['score']); // (85*40 + 95*60) / 100 = (3400 + 5700) / 100 = 91
        $this->assertEquals(100.0, $completeDetails['coverage']);
        $this->assertTrue($completeDetails['is_complete']);
    }

    public function test_cpl_score_details_reports_coverage_and_metadata(): void
    {
        // cpl1 has cpmk1 (60%) and cpmk2 (40%)
        $t1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'T1', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 50]);
        $this->cpmk1->assessments()->attach($t1->id, ['weight' => 100]);
        StudentAssessmentScore::create(['assessment_id' => $t1->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);

        // cpmk2 has no grades yet
        $details = $this->service->cplScoreDetails($this->cpl1->fresh(), $this->student->id, $this->section->id);

        $this->assertEquals(80.0, $details['score']);
        $this->assertEquals(60.0, $details['coverage']); // cpmk1 weight is 60 of 100
        $this->assertEquals(1, $details['cpmks_graded']);
        $this->assertEquals(2, $details['cpmks_total']);
        $this->assertFalse($details['is_complete']);
    }
    /**
     * Poin 3: Dukungan banyak CPMK pada satu asesmen dan dihitung secara independen.
     * Contoh:
     * - Tugas 1 -> CPMK1 = 100%
     * - Kuis 1  -> CPMK1 = 100%
     * - UTS     -> CPMK1 = 60%, CPMK2 = 40%
     * - UAS     -> CPMK2 = 70%, CPMK3 = 30%
     */
    public function test_multi_cpmk_assessment_is_calculated_independently_with_assessment_cpmk_weights(): void
    {
        $cpmk3 = Cpmk::create(['mata_kuliah_id' => $this->cpmk1->mata_kuliah_id, 'code' => 'CPMK-03', 'description' => '...']);

        $tugas1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-1', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 10]);
        $kuis1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'KUS-1', 'name' => 'Kuis 1', 'type' => 'quiz', 'final_weight' => 10]);
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UTS-1', 'name' => 'UTS', 'type' => 'uts', 'final_weight' => 30]);
        $uas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UAS-1', 'name' => 'UAS', 'type' => 'uas', 'final_weight' => 50]);

        $tugas1->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);
        $kuis1->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);

        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 60]);
        $uts->cpmks()->attach($this->cpmk2->id, ['weight' => 40]);

        $uas->cpmks()->attach($this->cpmk2->id, ['weight' => 70]);
        $uas->cpmks()->attach($cpmk3->id, ['weight' => 30]);

        // Nilai Tugas 1 = 80, Kuis 1 = 90
        StudentAssessmentScore::create(['assessment_id' => $tugas1->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $kuis1->id, 'mahasiswa_id' => $this->student->id, 'score' => 90]);

        // Nilai UTS: CPMK1 dapat 60/60 (100%), CPMK2 dapat 20/40 (50%) -> Total UTS = 80
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uts->id, 'cpmk_id' => $this->cpmk1->id, 'mahasiswa_id' => $this->student->id, 'score' => 60]);
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uts->id, 'cpmk_id' => $this->cpmk2->id, 'mahasiswa_id' => $this->student->id, 'score' => 20]);
        StudentAssessmentScore::create(['assessment_id' => $uts->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);

        // Nilai UAS: CPMK2 dapat 70/70 (100%), CPMK3 dapat 15/30 (50%) -> Total UAS = 85
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uas->id, 'cpmk_id' => $this->cpmk2->id, 'mahasiswa_id' => $this->student->id, 'score' => 70]);
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uas->id, 'cpmk_id' => $cpmk3->id, 'mahasiswa_id' => $this->student->id, 'score' => 15]);
        StudentAssessmentScore::create(['assessment_id' => $uas->id, 'mahasiswa_id' => $this->student->id, 'score' => 85]);

        // CPMK1: Tugas1(10, 80), Kuis1(10, 90), UTS(18, 100) -> (800 + 900 + 1800) / 38 = 92.11
        $scoreCpmk1 = $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id);
        $this->assertEquals(92.11, $scoreCpmk1);

        // CPMK2: UTS(12, 50), UAS(35, 100) -> (600 + 3500) / 47 = 87.23
        $scoreCpmk2 = $this->service->cpmkScore($this->cpmk2->fresh(), $this->student->id);
        $this->assertEquals(87.23, $scoreCpmk2);

        // CPMK3 = (50*15) / 15 = 50.0
        $scoreCpmk3 = $this->service->cpmkScore($cpmk3->fresh(), $this->student->id);
        $this->assertEquals(50.0, $scoreCpmk3);
    }

    /**
     * Kasus khusus: Hanya 1 CPMK dan 1 CPL menggunakan algoritma umum yang sama.
     * CPL1 -> CPMK031 (100%)
     * Aktivitas = 15%, PBL = 35%, Tugas = 10%, UTS = 20%, UAS = 20%
     * Semua asesmen mengukur CPMK031 dengan bobot 100%.
     */
    public function test_special_case_single_cpmk_and_single_cpl_uses_universal_formula(): void
    {
        $cpmk031 = Cpmk::create(['mata_kuliah_id' => $this->section->mata_kuliah_id, 'code' => 'CPMK031', 'description' => 'Tunggal', 'threshold' => 65]);
        $cplSingle = Cpl::create(['prodi_id' => $this->cpl1->prodi_id, 'code' => 'CPL-TUNGGAL', 'description' => 'Tunggal']);
        $cplSingle->cpmks()->attach($cpmk031->id, ['weight' => 100]);

        $akt = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'AKT', 'name' => 'Aktivitas', 'type' => 'aktivitas', 'final_weight' => 15]);
        $pbl = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'PBL', 'name' => 'PBL', 'type' => 'pbl', 'final_weight' => 35]);
        $tgs = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS', 'name' => 'Tugas', 'type' => 'tugas', 'final_weight' => 10]);
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UTS', 'name' => 'UTS', 'type' => 'uts', 'final_weight' => 20]);
        $uas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UAS', 'name' => 'UAS', 'type' => 'uas', 'final_weight' => 20]);

        $akt->cpmks()->attach($cpmk031->id, ['weight' => 100]);
        $pbl->cpmks()->attach($cpmk031->id, ['weight' => 100]);
        $tgs->cpmks()->attach($cpmk031->id, ['weight' => 100]);
        $uts->cpmks()->attach($cpmk031->id, ['weight' => 100]);
        $uas->cpmks()->attach($cpmk031->id, ['weight' => 100]);

        StudentAssessmentScore::create(['assessment_id' => $akt->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $pbl->id, 'mahasiswa_id' => $this->student->id, 'score' => 90]);
        StudentAssessmentScore::create(['assessment_id' => $tgs->id, 'mahasiswa_id' => $this->student->id, 'score' => 70]);
        StudentAssessmentScore::create(['assessment_id' => $uts->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $uas->id, 'mahasiswa_id' => $this->student->id, 'score' => 85]);

        // Capaian CPMK dihitung dengan bobot efektif masing-masing asesmen:
        // (80*15 + 90*35 + 70*10 + 80*20 + 85*20) / 100 = 83.5
        $cpmkScore = $this->service->cpmkScore($cpmk031->fresh(), $this->student->id);
        $this->assertEquals(83.5, $cpmkScore);

        // Capaian CPL = CPMK031 = 83.5
        $cplScore = $this->service->cplScore($cplSingle->fresh(), $this->student->id);
        $this->assertEquals(83.5, $cplScore);

        // Nilai Akhir MK murni dari final_weight = (80*15 + 90*35 + 70*10 + 80*20 + 85*20) / 100
        // = (1200 + 3150 + 700 + 1600 + 1700) / 100 = 8350 / 100 = 83.5
        $final = $this->service->finalScore($this->section->fresh(), $this->student->id);
        $this->assertEquals(83.5, $final['score']);
        $this->assertEquals(100.0, $final['coverage']);
    }

    /**
     * Poin 6: Fallback aman. Jika asesmen multi-CPMK dinilai per-CPMK dan salah satu CPMK
     * belum dinilai, sistem TIDAK BOLEH fallback ke nilai total asesmen.
     */
    public function test_fallback_does_not_leak_general_score_when_partially_graded_on_cpmks(): void
    {
        $uts = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UTS-FB', 'name' => 'UTS Fallback', 'type' => 'uts', 'final_weight' => 50]);
        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 60]);
        $uts->cpmks()->attach($this->cpmk2->id, ['weight' => 40]);

        // Dosen baru menilai CPMK1 (60 poin penuh)
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uts->id, 'cpmk_id' => $this->cpmk1->id, 'mahasiswa_id' => $this->student->id, 'score' => 60]);
        // CPMK2 belum dinilai sama sekali
        StudentAssessmentScore::create(['assessment_id' => $uts->id, 'mahasiswa_id' => $this->student->id, 'score' => 60]);

        // CPMK1 harus 100%
        $this->assertEquals(100.0, $this->service->studentScoreForAssessmentCpmk($uts, $this->cpmk1, $this->student->id));

        // CPMK2 HARUS null (belum dinilai), tidak boleh mengambil skor 60!
        $this->assertNull($this->service->studentScoreForAssessmentCpmk($uts, $this->cpmk2, $this->student->id));
        $this->assertNull($this->service->cpmkScore($this->cpmk2->fresh(), $this->student->id));
    }

    /**
     * Poin 7 & 8: Attainment biner (Tercapai / Tidak Tercapai) dan coverage CPMK/CPL.
     */
    public function test_attainment_and_coverage_metrics(): void
    {
        $tgs = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'TGS-COV', 'name' => 'Tugas Cov', 'type' => 'tugas', 'final_weight' => 40]);
        $uas = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'UAS-COV', 'name' => 'UAS Cov', 'type' => 'uas', 'final_weight' => 60]);

        $this->cpmk1->assessments()->attach($tgs->id, ['weight' => 50]);
        $this->cpmk1->assessments()->attach($uas->id, ['weight' => 50]);

        // Baru menilai Tugas (bobot efektif 40 dari total 100) -> coverage = 40.0%
        StudentAssessmentScore::create(['assessment_id' => $tgs->id, 'mahasiswa_id' => $this->student->id, 'score' => 70]);

        $this->assertEquals(40.0, $this->service->cpmkCoverage($this->cpmk1->fresh(), $this->student->id));
        $this->assertEquals(70.0, $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id));

        // Threshold 65: 70 >= 65 -> Tercapai
        $this->assertTrue($this->service->cpmkAchieved($this->cpmk1, 70.0));
        $this->assertSame('Tercapai', $this->service->attainmentStatus(true));
        $this->assertSame('Tidak Tercapai', $this->service->attainmentStatus(false));
        $this->assertNull($this->service->attainmentStatus(null));

        $this->assertTrue($this->service->cplAchieved($this->cpl1, 70.0));
        $this->assertFalse($this->service->cplAchieved($this->cpl1, 60.0));
    }

    /**
     * Uji fallback terisolasi per-mahasiswa: dua mahasiswa pada asesmen yang sama,
     * tetapi kondisi per-CPMK berbeda, memastikan data mahasiswa A tidak bocor ke mahasiswa B.
     */
    public function test_two_students_on_same_assessment_have_isolated_cpmk_fallback(): void
    {
        $roleMahasiswa = Role::where('name', Role::MAHASISWA)->first();
        $studentB = User::create(['name' => 'Mahasiswa B', 'email' => 'mhs-b@test.local', 'password' => 'x', 'role_id' => $roleMahasiswa->id]);

        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-ISO',
            'name' => 'UTS Isolasi',
            'type' => 'uts',
            'final_weight' => 40,
        ]);
        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 60]); // maks 60
        $uts->cpmks()->attach($this->cpmk2->id, ['weight' => 40]); // maks 40

        // Mahasiswa A (this->student): sudah dinilai CPMK1 (60 poin = 100%), CPMK2 belum dinilai
        \App\Models\StudentAssessmentCpmkScore::create([
            'assessment_id' => $uts->id,
            'cpmk_id' => $this->cpmk1->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 60,
        ]);
        StudentAssessmentScore::create([
            'assessment_id' => $uts->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 60,
        ]);

        // Mahasiswa B: tidak memiliki nilai per-CPMK, hanya memiliki nilai asesmen umum = 80
        StudentAssessmentScore::create([
            'assessment_id' => $uts->id,
            'mahasiswa_id' => $studentB->id,
            'score' => 80,
        ]);

        // Verifikasi Mahasiswa A:
        // CPMK1 = 100%
        $this->assertEquals(100.0, $this->service->studentScoreForAssessmentCpmk($uts, $this->cpmk1, $this->student->id));
        // CPMK2 HARUS null (belum dinilai), tidak boleh mengambil skor umum 60
        $this->assertNull($this->service->studentScoreForAssessmentCpmk($uts, $this->cpmk2, $this->student->id));

        // Verifikasi Mahasiswa B:
        // CPMK1 dan CPMK2 harus fallback ke nilai umum 80 karena Mahasiswa B belum memiliki baris per-CPMK,
        // dan status penilaian Mahasiswa A TIDAK BOLEH memengaruhi Mahasiswa B!
        $this->assertEquals(80.0, $this->service->studentScoreForAssessmentCpmk($uts, $this->cpmk1, $studentB->id));
        $this->assertEquals(80.0, $this->service->studentScoreForAssessmentCpmk($uts, $this->cpmk2, $studentB->id));
    }

    /**
     * Uji finalScore() pada assessment multi-CPMK:
     * 1. Sumber utama adalah student_assessment_scores.score
     * 2. Fallback per-CPMK hanya jika berupa poin kontribusi valid
     * 3. Menolak fallback penjumlahan persentase 0-100 mentah
     */
    public function test_final_score_multi_cpmk_assessment_scenarios(): void
    {
        $roleMahasiswa = Role::where('name', Role::MAHASISWA)->first();
        $studentValidFallback = User::create(['name' => 'Mhs Valid', 'email' => 'mhs-valid@test.local', 'password' => 'x', 'role_id' => $roleMahasiswa->id]);
        $studentRawPercentage = User::create(['name' => 'Mhs Raw', 'email' => 'mhs-raw@test.local', 'password' => 'x', 'role_id' => $roleMahasiswa->id]);

        $tugas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-FS',
            'name' => 'Tugas Final',
            'type' => 'tugas',
            'final_weight' => 30,
        ]);
        $tugas->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);

        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS-FS',
            'name' => 'UAS Final',
            'type' => 'uas',
            'final_weight' => 70,
        ]);
        $uas->cpmks()->attach($this->cpmk1->id, ['weight' => 60]); // maks 60 poin
        $uas->cpmks()->attach($this->cpmk2->id, ['weight' => 40]); // maks 40 poin

        // Kasus 1: Sumber utama student_assessment_scores
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $uas->id, 'mahasiswa_id' => $this->student->id, 'score' => 85]);

        $res1 = $this->service->finalScore($this->section->fresh(), $this->student->id);
        // (80*30 + 85*70) / 100 = (2400 + 5950) / 100 = 83.5
        $this->assertEquals(83.5, $res1['score']);
        $this->assertEquals(100.0, $res1['coverage']);

        // Kasus 2: Mahasiswa Valid Fallback (student_assessment_scores tidak ada, tapi poin per-CPMK valid)
        // Tugas = 80
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $studentValidFallback->id, 'score' => 80]);
        // UAS per-CPMK: 55/60 dan 35/40 (total 90)
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uas->id, 'cpmk_id' => $this->cpmk1->id, 'mahasiswa_id' => $studentValidFallback->id, 'score' => 55]);
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uas->id, 'cpmk_id' => $this->cpmk2->id, 'mahasiswa_id' => $studentValidFallback->id, 'score' => 35]);

        $res2 = $this->service->finalScore($this->section->fresh(), $studentValidFallback->id);
        // UAS dihitung sebagai 55 + 35 = 90
        // (80*30 + 90*70) / 100 = (2400 + 6300) / 100 = 87.0
        $this->assertEquals(87.0, $res2['score']);
        $this->assertEquals(100.0, $res2['coverage']);

        // Kasus 3: Mahasiswa dengan nilai 0-100 mentah di per-CPMK (CPMK1: 80, CPMK2: 70)
        // Nilai 80 > maxScore (60), sehingga tidak boleh dijumlahkan 80+70=150 lalu dicap ke 100!
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $studentRawPercentage->id, 'score' => 80]);
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uas->id, 'cpmk_id' => $this->cpmk1->id, 'mahasiswa_id' => $studentRawPercentage->id, 'score' => 80]);
        \App\Models\StudentAssessmentCpmkScore::create(['assessment_id' => $uas->id, 'cpmk_id' => $this->cpmk2->id, 'mahasiswa_id' => $studentRawPercentage->id, 'score' => 70]);

        $res3 = $this->service->finalScore($this->section->fresh(), $studentRawPercentage->id);
        // UAS tidak dihitung (null), hanya Tugas yang dinilai (score 80, coverage 30%)
        $this->assertEquals(80.0, $res3['score']);
        $this->assertEquals(30.0, $res3['coverage']);
    }

    /**
     * Uji multi-CPL dengan bobot berbeda pada CPMK yang sama.
     */
    public function test_multi_cpl_with_different_weights(): void
    {
        $prodi = $this->cpl1->prodi;
        $cpl2 = Cpl::create(['prodi_id' => $prodi->id, 'code' => 'CPL-02', 'description' => 'CPL Kedua']);

        // CPL1: CPMK1 weight 70, CPMK2 weight 30
        $this->cpl1->cpmks()->sync([
            $this->cpmk1->id => ['weight' => 70],
            $this->cpmk2->id => ['weight' => 30],
        ]);

        // CPL2: CPMK1 weight 25, CPMK2 weight 75
        $cpl2->cpmks()->sync([
            $this->cpmk1->id => ['weight' => 25],
            $this->cpmk2->id => ['weight' => 75],
        ]);

        $asmt1 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A1-CPL', 'name' => 'A1', 'type' => 'tugas', 'final_weight' => 50]);
        $asmt2 = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'A2-CPL', 'name' => 'A2', 'type' => 'tugas', 'final_weight' => 50]);

        $this->cpmk1->assessments()->attach($asmt1->id, ['weight' => 100]);
        $this->cpmk2->assessments()->attach($asmt2->id, ['weight' => 100]);

        // CPMK1 score = 80, CPMK2 score = 60
        StudentAssessmentScore::create(['assessment_id' => $asmt1->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);
        StudentAssessmentScore::create(['assessment_id' => $asmt2->id, 'mahasiswa_id' => $this->student->id, 'score' => 60]);

        $scoreCpmk1 = $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id);
        $scoreCpmk2 = $this->service->cpmkScore($this->cpmk2->fresh(), $this->student->id);
        $this->assertEquals(80.0, $scoreCpmk1);
        $this->assertEquals(60.0, $scoreCpmk2);

        // CPL1 = (80 * 70 + 60 * 30) / (70 + 30) = (5600 + 1800) / 100 = 74.0
        $scoreCpl1 = $this->service->cplScore($this->cpl1->fresh(), $this->student->id);
        $this->assertEquals(74.0, $scoreCpl1);

        // CPL2 = (80 * 25 + 60 * 75) / (25 + 75) = (2000 + 4500) / 100 = 65.0
        $scoreCpl2 = $this->service->cplScore($cpl2->fresh(), $this->student->id);
        $this->assertEquals(65.0, $scoreCpl2);
    }

    /**
     * Uji nilai kosong tetap NULL (bukan 0), dan nilai 0 tetap 0 (bukan NULL).
     */
    public function test_empty_values_remain_null_and_zero_is_preserved(): void
    {
        $asmt = Assessment::create(['class_section_id' => $this->section->id, 'code' => 'EMPTY-ASMT', 'name' => 'Empty', 'type' => 'tugas', 'final_weight' => 100]);
        $this->cpmk1->assessments()->attach($asmt->id, ['weight' => 100]);

        // 1. Belum ada nilai sama sekali: harus NULL
        $this->assertNull($this->service->studentScoreForAssessmentCpmk($asmt, $this->cpmk1, $this->student->id));
        $this->assertNull($this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id));
        $this->assertNull($this->service->cplScore($this->cpl1->fresh(), $this->student->id));
        $finalEmpty = $this->service->finalScore($this->section->fresh(), $this->student->id);
        $this->assertNull($finalEmpty['score']);
        $this->assertEquals(0.0, $finalEmpty['coverage']);

        // 2. Mahasiswa mendapat skor 0 murni: harus 0.0, BUKAN NULL
        StudentAssessmentScore::create(['assessment_id' => $asmt->id, 'mahasiswa_id' => $this->student->id, 'score' => 0]);

        $this->assertSame(0.0, (float) $this->service->studentScoreForAssessmentCpmk($asmt, $this->cpmk1, $this->student->id));
        $this->assertSame(0.0, (float) $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id));
        $this->assertSame(0.0, (float) $this->service->cplScore($this->cpl1->fresh(), $this->student->id));
        $finalZero = $this->service->finalScore($this->section->fresh(), $this->student->id);
        $this->assertSame(0.0, (float) $finalZero['score']);
        $this->assertEquals(100.0, $finalZero['coverage']);
    }
}
