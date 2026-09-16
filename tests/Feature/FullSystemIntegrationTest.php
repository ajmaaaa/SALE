<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Semester;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kaprodi;
    private User $dosen;
    private User $unassignedDosen;
    private User $mahasiswaA;
    private User $mahasiswaB;
    private Prodi $prodi;
    private Semester $semester;
    private MataKuliah $mataKuliah;
    private ClassSection $section;
    private Cpl $cpl1;
    private Cpl $cpl2;
    private Cpmk $cpmk1;
    private Cpmk $cpmk2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles Setup
        $adminRole = Role::create(['name' => Role::ADMIN, 'label' => 'Admin Sistem']);
        $kaprodiRole = Role::create(['name' => Role::KAPRODI, 'label' => 'Kaprodi']);
        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        // 2. Users Setup
        $this->admin = User::create(['name' => 'Admin Utama', 'email' => 'admin@sale.local', 'password' => 'secret', 'role_id' => $adminRole->id]);
        $this->kaprodi = User::create(['name' => 'Kaprodi Teknik', 'email' => 'kaprodi@sale.local', 'password' => 'secret', 'role_id' => $kaprodiRole->id]);
        $this->dosen = User::create(['name' => 'Dr. Dosen Pengampu', 'email' => 'dosen@sale.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->unassignedDosen = User::create(['name' => 'Dosen Luar', 'email' => 'luar@sale.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->mahasiswaA = User::create(['name' => 'Mahasiswa Alpha', 'email' => 'alpha@sale.local', 'nim_nidn' => '2026001', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);
        $this->mahasiswaB = User::create(['name' => 'Mahasiswa Beta', 'email' => 'beta@sale.local', 'nim_nidn' => '2026002', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);

        // 3. Academic Structure
        $this->prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknik Informatika']);
        $this->semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $this->mataKuliah = MataKuliah::create(['prodi_id' => $this->prodi->id, 'code' => 'TI301', 'name' => 'Rekayasa Perangkat Lunak']);

        // 4. Class Section & Enrolled Students
        $this->section = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
        ]);
        $this->section->students()->attach([$this->mahasiswaA->id, $this->mahasiswaB->id]);

        // 5. CPL & CPMK Mappings
        $this->cpl1 = Cpl::create(['prodi_id' => $this->prodi->id, 'code' => 'CPL-01', 'description' => 'Mampu menganalisis kebutuhan sistem']);
        $this->cpl2 = Cpl::create(['prodi_id' => $this->prodi->id, 'code' => 'CPL-02', 'description' => 'Mampu mengimplementasikan perangkat lunak']);

        $this->cpmk1 = Cpmk::create(['mata_kuliah_id' => $this->mataKuliah->id, 'code' => 'CPMK-1', 'description' => 'Analisis Kebutuhan', 'threshold' => 65]);
        $this->cpmk2 = Cpmk::create(['mata_kuliah_id' => $this->mataKuliah->id, 'code' => 'CPMK-2', 'description' => 'Implementasi Arsitektur', 'threshold' => 65]);

        $this->cpl1->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);
        $this->cpl2->cpmks()->attach($this->cpmk2->id, ['weight' => 100]);
    }

    public function test_complete_end_to_end_obe_lifecycle_and_role_audit(): void
    {
        // -------------------------------------------------------------
        // STEP A: Dosen creates Assessment 1 (Non-rubrik, UTS)
        // -------------------------------------------------------------
        $assessmentUts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS',
            'name' => 'Ujian Tengah Semester',
            'type' => 'uts',
            'final_weight' => 50,
            'status' => 'published',
            'uses_rubric' => false,
        ]);
        $assessmentUts->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);

        // -------------------------------------------------------------
        // STEP B: Dosen creates Assessment 2 (Uses Rubrik, Tugas Akhir)
        // -------------------------------------------------------------
        $assessmentProject = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-AKHIR',
            'name' => 'Proyek Perangkat Lunak',
            'type' => 'proyek',
            'final_weight' => 50,
            'status' => 'published',
            'uses_rubric' => true,
        ]);
        $assessmentProject->cpmks()->attach($this->cpmk2->id, ['weight' => 100]);

        // Dosen sets up Rubric with 2 criteria totaling 100% weight
        $rubric = Rubric::create([
            'assessment_id' => $assessmentProject->id,
            'name' => 'Rubrik Proyek Akhir',
            'scale_min' => 0,
            'scale_max' => 100,
        ]);
        $criterionA = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Desain Arsitektur',
            'weight' => 40,
            'order' => 1,
        ]);
        $criterionB = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kualitas Kode & Testing',
            'weight' => 60,
            'order' => 2,
        ]);

        // -------------------------------------------------------------
        // STEP C: Dosen inputs grades
        // -------------------------------------------------------------
        // UTS direct scores: Mahasiswa A = 80, Mahasiswa B = 50
        $this->actingAs($this->dosen)->post(route('dosen.penilaian.asesmen.nilai.store', [
            'section' => $this->section->id,
            'assessment' => $assessmentUts->id,
        ]), [
            'scores' => [
                $this->mahasiswaA->id => 80,
                $this->mahasiswaB->id => 50,
            ],
            'feedback' => [
                $this->mahasiswaA->id => 'Bagus sekali',
                $this->mahasiswaB->id => 'Perlu belajar lagi',
            ],
        ])->assertRedirect();

        // Project Rubric scores:
        // Mahasiswa A: critA = 90, critB = 85 -> Rubric score = 0.40*90 + 0.60*85 = 36 + 51 = 87.0
        // Mahasiswa B: critA = 60, critB = 70 -> Rubric score = 0.40*60 + 0.60*70 = 24 + 42 = 66.0
        $this->actingAs($this->dosen)->post(route('dosen.penilaian.asesmen.nilai.store', [
            'section' => $this->section->id,
            'assessment' => $assessmentProject->id,
        ]), [
            'rubric_scores' => [
                $this->mahasiswaA->id => [
                    $criterionA->id => 90,
                    $criterionB->id => 85,
                ],
                $this->mahasiswaB->id => [
                    $criterionA->id => 60,
                    $criterionB->id => 70,
                ],
            ],
        ])->assertRedirect();

        // -------------------------------------------------------------
        // STEP D: Verify Calculation Service (Single Source of Truth)
        // -------------------------------------------------------------
        $obe = app(ObeCalculationService::class);

        // Rubric scores
        $this->assertEquals(87.0, $obe->rubricScore($rubric, $this->mahasiswaA->id));
        $this->assertEquals(66.0, $obe->rubricScore($rubric, $this->mahasiswaB->id));

        // Assessment scores
        $this->assertEquals(80.0, $obe->assessmentScore($assessmentUts->id, $this->mahasiswaA->id));
        $this->assertEquals(87.0, $obe->assessmentScore($assessmentProject->id, $this->mahasiswaA->id));

        // CPMK Scores
        $this->assertEquals(80.0, $obe->cpmkScore($this->cpmk1, $this->mahasiswaA->id));
        $this->assertEquals(87.0, $obe->cpmkScore($this->cpmk2, $this->mahasiswaA->id));
        $this->assertEquals(50.0, $obe->cpmkScore($this->cpmk1, $this->mahasiswaB->id));
        $this->assertEquals(66.0, $obe->cpmkScore($this->cpmk2, $this->mahasiswaB->id));

        // CPL Scores
        $this->assertEquals(80.0, $obe->cplScore($this->cpl1, $this->mahasiswaA->id));
        $this->assertEquals(87.0, $obe->cplScore($this->cpl2, $this->mahasiswaA->id));
        $this->assertEquals(50.0, $obe->cplScore($this->cpl1, $this->mahasiswaB->id));
        $this->assertEquals(66.0, $obe->cplScore($this->cpl2, $this->mahasiswaB->id));

        // Final scores:
        // Mahasiswa A = 0.50*80 + 0.50*87 = 83.5 (Grade AB)
        // Mahasiswa B = 0.50*50 + 0.50*66 = 58.0 (Grade D)
        $finalA = $obe->finalScore($this->section, $this->mahasiswaA->id);
        $finalB = $obe->finalScore($this->section, $this->mahasiswaB->id);
        $this->assertEquals(83.5, $finalA['score']);
        $this->assertEquals(100.0, $finalA['coverage']);
        $this->assertEquals(58.0, $finalB['score']);
        $this->assertEquals(100.0, $finalB['coverage']);

        // -------------------------------------------------------------
        // STEP E: Dosen Views and Exports
        // -------------------------------------------------------------
        // Rekap table
        $resRekap = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap', $this->section->id));
        $resRekap->assertOk();
        $resRekap->assertSee('Mahasiswa Alpha');
        $resRekap->assertSee('83.5');
        $resRekap->assertSee('AB');

        // CSV Export
        $resCsv = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap.export', $this->section->id));
        $resCsv->assertOk();
        $this->assertStringContainsString('Mahasiswa Alpha', $resCsv->streamedContent());

        // Printable PDF
        $resPrint = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap.print', $this->section->id));
        $resPrint->assertOk();
        $resPrint->assertSee('TI301');

        // -------------------------------------------------------------
        // STEP F: Kaprodi Monitoring
        // -------------------------------------------------------------
        $resKaprodiCpmk = $this->actingAs($this->kaprodi)->get(route('kaprodi.monitoring.cpmk', ['section_id' => $this->section->id]));
        $resKaprodiCpmk->assertOk();
        $resKaprodiCpmk->assertSee('CPMK-1');
        $resKaprodiCpmk->assertSee('CPMK-2');

        $resKaprodiCpl = $this->actingAs($this->kaprodi)->get(route('kaprodi.monitoring.cpl'));
        $resKaprodiCpl->assertOk();
        $resKaprodiCpl->assertSee('CPL-01');
        $resKaprodiCpl->assertSee('CPL-02');

        // -------------------------------------------------------------
        // STEP G: Mahasiswa Self-Service Transcripts
        // -------------------------------------------------------------
        $resMhsA = $this->actingAs($this->mahasiswaA)->get(route('mahasiswa.obe.progress'));
        $resMhsA->assertOk();
        $resMhsA->assertSee('TI301');
        $resMhsA->assertSee('83.5');
        $resMhsA->assertSee('Mahasiswa Alpha');
        $resMhsA->assertDontSee('Mahasiswa Beta'); // Must NOT see other students' grades!

        // -------------------------------------------------------------
        // STEP H: Strict Security & Role Boundary Enforcement
        // -------------------------------------------------------------
        // 1. Unassigned Dosen is blocked from accessing this section
        $this->actingAs($this->unassignedDosen)
            ->get(route('dosen.penilaian.rekap', $this->section->id))
            ->assertStatus(403);

        // 2. Kaprodi is blocked from modifying grades or assessments
        $this->actingAs($this->kaprodi)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [
                'section' => $this->section->id,
                'assessment' => $assessmentUts->id,
            ]), ['scores' => [$this->mahasiswaA->id => 100]])
            ->assertStatus(403);

        // 3. Mahasiswa is blocked from modifying grades or accessing kaprodi monitoring
        $this->actingAs($this->mahasiswaA)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [
                'section' => $this->section->id,
                'assessment' => $assessmentUts->id,
            ]), ['scores' => [$this->mahasiswaA->id => 100]])
            ->assertStatus(403);

        $this->actingAs($this->mahasiswaA)
            ->get(route('kaprodi.monitoring.cpmk'))
            ->assertStatus(403);
    }
}
