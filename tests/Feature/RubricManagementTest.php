<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Semester;
use App\Models\StudentRubricScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $otherDosen;
    private User $student;
    private ClassSection $section;
    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data']);

        $this->dosen = User::create(['name' => 'Dosen Utama', 'email' => 'dosen-utama@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->otherDosen = User::create(['name' => 'Dosen Lain', 'email' => 'dosen-lain@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->student = User::create(['name' => 'Mahasiswa Uji', 'email' => 'mhs@test.local', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
        ]);

        $this->section->students()->attach($this->student->id);

        $cpmk = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-01', 'description' => 'Konsep Dasar']);

        $this->assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'final_weight' => 15,
            'status' => 'published',
            'uses_rubric' => false,
        ]);

        $this->assessment->cpmks()->attach($cpmk->id, ['weight' => 100]);
    }

    public function test_dosen_can_view_assessment_detail_page(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.show', [$this->section->id, $this->assessment->id]));

        $response->assertOk()
            ->assertSee('Tugas 1')
            ->assertSee('TGS-01')
            ->assertSee('Rubrik Penilaian')
            ->assertSee('Rubrik Penilaian Belum Diaktifkan');
    }

    public function test_unauthorized_dosen_cannot_access_assessment_detail_or_manage_rubric(): void
    {
        // Other dosen attempting to view
        $response = $this->actingAs($this->otherDosen)
            ->get(route('dosen.penilaian.asesmen.show', [$this->section->id, $this->assessment->id]));
        $response->assertStatus(403);

        // Other dosen attempting to update rubric
        $updateResponse = $this->actingAs($this->otherDosen)
            ->put(route('dosen.penilaian.asesmen.rubrik.update', [$this->section->id, $this->assessment->id]), [
                'uses_rubric' => 1,
                'enable_only' => 1,
            ]);
        $updateResponse->assertStatus(403);
    }

    public function test_dosen_can_activate_rubric_with_empty_state(): void
    {
        $response = $this->actingAs($this->dosen)
            ->put(route('dosen.penilaian.asesmen.rubrik.update', [$this->section->id, $this->assessment->id]), [
                'uses_rubric' => 1,
                'enable_only' => 1,
            ]);

        $response->assertRedirect(route('dosen.penilaian.asesmen.show', [$this->section->id, $this->assessment->id]));
        $this->assertDatabaseHas('assessments', ['id' => $this->assessment->id, 'uses_rubric' => true]);
        $this->assertDatabaseHas('rubrics', ['assessment_id' => $this->assessment->id]);

        // Follow redirect to verify empty state is visible
        $detailResponse = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.show', [$this->section->id, $this->assessment->id]));
        $detailResponse->assertSee('Belum Ada Kriteria Penilaian')
            ->assertSee('Tambah Kriteria Pertama');
    }

    public function test_dosen_can_save_valid_criteria_with_total_weight_100(): void
    {
        $payload = [
            'uses_rubric' => 1,
            'rubric_name' => 'Rubrik Tugas 1 Pemrograman',
            'criteria' => [
                [
                    'name' => 'Pemahaman Algoritma',
                    'description' => 'Menganalisis kompleksitas waktu dan ruang',
                    'weight' => 40,
                    'max_score' => 100,
                    'order' => 1,
                ],
                [
                    'name' => 'Implementasi Kode',
                    'description' => 'Kerapian dan ketepatan sintaks',
                    'weight' => 60,
                    'max_score' => 100,
                    'order' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->put(route('dosen.penilaian.asesmen.rubrik.update', [$this->section->id, $this->assessment->id]), $payload);

        $response->assertRedirect(route('dosen.penilaian.asesmen.show', [$this->section->id, $this->assessment->id]))
            ->assertSessionHas('notice');

        $this->assertDatabaseHas('assessments', ['id' => $this->assessment->id, 'uses_rubric' => true]);
        $this->assertDatabaseHas('rubrics', ['assessment_id' => $this->assessment->id, 'name' => 'Rubrik Tugas 1 Pemrograman']);
        $this->assertDatabaseHas('rubric_criteria', ['name' => 'Pemahaman Algoritma', 'weight' => 40, 'order' => 1]);
        $this->assertDatabaseHas('rubric_criteria', ['name' => 'Implementasi Kode', 'weight' => 60, 'order' => 2]);
    }

    public function test_rubric_validation_fails_if_total_weight_is_not_100(): void
    {
        $payload = [
            'uses_rubric' => 1,
            'criteria' => [
                ['name' => 'Kriteria 1', 'weight' => 50, 'max_score' => 100],
                ['name' => 'Kriteria 2', 'weight' => 40, 'max_score' => 100], // total = 90%
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->put(route('dosen.penilaian.asesmen.rubrik.update', [$this->section->id, $this->assessment->id]), $payload);

        $response->assertSessionHasErrors('criteria');
    }

    public function test_rubric_validation_fails_if_criterion_name_is_duplicate(): void
    {
        $payload = [
            'uses_rubric' => 1,
            'criteria' => [
                ['name' => 'Analisis', 'weight' => 50, 'max_score' => 100],
                ['name' => 'Analisis', 'weight' => 50, 'max_score' => 100],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->put(route('dosen.penilaian.asesmen.rubrik.update', [$this->section->id, $this->assessment->id]), $payload);

        $response->assertSessionHasErrors('criteria');
    }

    public function test_rubric_validation_fails_if_criterion_weight_is_zero_or_negative(): void
    {
        $payload = [
            'uses_rubric' => 1,
            'criteria' => [
                ['name' => 'Kriteria 1', 'weight' => 0, 'max_score' => 100],
                ['name' => 'Kriteria 2', 'weight' => 100, 'max_score' => 100],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->put(route('dosen.penilaian.asesmen.rubrik.update', [$this->section->id, $this->assessment->id]), $payload);

        $response->assertSessionHasErrors('criteria.0.weight');
    }

    public function test_dosen_can_deactivate_rubric(): void
    {
        $this->assessment->update(['uses_rubric' => true]);

        $response = $this->actingAs($this->dosen)
            ->delete(route('dosen.penilaian.asesmen.rubrik.destroy', [$this->section->id, $this->assessment->id]));

        $response->assertRedirect(route('dosen.penilaian.asesmen.show', [$this->section->id, $this->assessment->id]));
        $this->assertDatabaseHas('assessments', ['id' => $this->assessment->id, 'uses_rubric' => false]);
    }

    public function test_obe_calculation_service_calculates_assessment_score_from_rubric(): void
    {
        $this->assessment->update(['uses_rubric' => true]);
        $rubric = Rubric::create(['assessment_id' => $this->assessment->id, 'name' => 'Rubrik TGS-01']);

        $crit1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Analisis',
            'weight' => 40,
            'max_score' => 100,
            'order' => 1,
        ]);

        $crit2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Implementasi',
            'weight' => 60,
            'max_score' => 100,
            'order' => 2,
        ]);

        // Student scores: Analisis = 80 (weight 40%), Implementasi = 90 (weight 60%)
        // Nilai = (80 * 0.4) + (90 * 0.6) = 32 + 54 = 86.0
        StudentRubricScore::create([
            'rubric_criterion_id' => $crit1->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 80,
        ]);

        StudentRubricScore::create([
            'rubric_criterion_id' => $crit2->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 90,
        ]);

        $obeService = new ObeCalculationService();
        $calculatedScore = $obeService->assessmentScore($this->assessment->id, $this->student->id);

        $this->assertEquals(86.0, $calculatedScore);
    }
}
