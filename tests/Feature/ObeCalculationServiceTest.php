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
}
