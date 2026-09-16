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
use App\Models\StudentAssessmentScore;
use App\Models\StudentRubricScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputNilaiAndTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $otherDosen;
    private User $student1;
    private User $student2;
    private User $unenrolledStudent;
    private ClassSection $section;
    private Assessment $directAssessment;
    private Assessment $rubricAssessment;
    private RubricCriterion $crit1;
    private RubricCriterion $crit2;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data']);

        $this->dosen = User::create(['name' => 'Dosen Utama', 'email' => 'dosen@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->otherDosen = User::create(['name' => 'Dosen Lain', 'email' => 'other@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);

        $this->student1 = User::create(['name' => 'Andi Santoso', 'email' => 'andi@test.local', 'nim_nidn' => '2024081001', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);
        $this->student2 = User::create(['name' => 'Budi Pratama', 'email' => 'budi@test.local', 'nim_nidn' => '2024081002', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);
        $this->unenrolledStudent = User::create(['name' => 'Citra Dewi', 'email' => 'citra@test.local', 'nim_nidn' => '2024081099', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
        ]);

        $this->section->students()->attach([$this->student1->id, $this->student2->id]);

        $cpmk = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-01', 'description' => 'Konsep Dasar']);

        // Asesmen 1: Non-rubrik (Nilai Langsung)
        $this->directAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'Ujian Tengah Semester',
            'type' => 'uts',
            'final_weight' => 25,
            'status' => 'published',
            'uses_rubric' => false,
        ]);
        $this->directAssessment->cpmks()->attach($cpmk->id, ['weight' => 100]);

        // Asesmen 2: Dengan Rubrik
        $this->rubricAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1 Pemrograman',
            'type' => 'tugas',
            'final_weight' => 15,
            'status' => 'published',
            'uses_rubric' => true,
        ]);
        $this->rubricAssessment->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $rubric = Rubric::create(['assessment_id' => $this->rubricAssessment->id, 'name' => 'Rubrik Tugas 1']);
        $this->crit1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Pemahaman Algoritma',
            'weight' => 40,
            'max_score' => 100,
            'order' => 1,
        ]);
        $this->crit2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Implementasi Kode',
            'weight' => 60,
            'max_score' => 100,
            'order' => 2,
        ]);
    }

    public function test_dosen_can_view_input_nilai_page_for_non_rubric_assessment(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->directAssessment->id]));

        $response->assertOk()
            ->assertSee('Input Nilai: Ujian Tengah Semester')
            ->assertSee('Nilai Langsung (0-100)')
            ->assertSee('Andi Santoso')
            ->assertSee('Budi Pratama')
            ->assertSee('Unduh Template Excel/CSV');
    }

    public function test_dosen_can_view_input_nilai_page_for_rubric_assessment(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->rubricAssessment->id]));

        $response->assertOk()
            ->assertSee('Input Nilai: Tugas 1 Pemrograman')
            ->assertSee('Rubrik (2 Kriteria)')
            ->assertSee('Pemahaman Algoritma')
            ->assertSee('Implementasi Kode')
            ->assertSee('Andi Santoso');
    }

    public function test_unauthorized_dosen_cannot_access_or_save_grades(): void
    {
        // Akses view
        $response = $this->actingAs($this->otherDosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->directAssessment->id]));
        $response->assertStatus(403);

        // Submit nilai
        $submit = $this->actingAs($this->otherDosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $this->directAssessment->id]), [
                'scores' => [$this->student1->id => 85],
            ]);
        $submit->assertStatus(403);
    }

    public function test_dosen_can_save_direct_scores_for_non_rubric_assessment(): void
    {
        $payload = [
            'scores' => [
                $this->student1->id => 85.5,
                $this->student2->id => 72.0,
            ],
            'feedback' => [
                $this->student1->id => 'Sangat baik',
                $this->student2->id => 'Tingkatkan pemahaman',
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $this->directAssessment->id]), $payload);

        $response->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->directAssessment->id]))
            ->assertSessionHas('notice');

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->directAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 85.50,
            'feedback' => 'Sangat baik',
        ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->directAssessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 72.00,
            'feedback' => 'Tingkatkan pemahaman',
        ]);
    }

    public function test_dosen_can_save_rubric_criteria_scores_and_calculates_assessment_score(): void
    {
        // Student 1: Crit 1 (weight 40%) = 80, Crit 2 (weight 60%) = 90
        // Expected Assessment Score = (80 * 0.4) + (90 * 0.6) = 32 + 54 = 86.00
        $payload = [
            'rubric_scores' => [
                $this->student1->id => [
                    $this->crit1->id => 80,
                    $this->crit2->id => 90,
                ],
                $this->student2->id => [
                    $this->crit1->id => 70,
                    $this->crit2->id => 60,
                ],
            ],
            'feedback' => [
                $this->student1->id => 'Bagus sekali',
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $this->rubricAssessment->id]), $payload);

        $response->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->rubricAssessment->id]))
            ->assertSessionHas('notice');

        // Check criteria scores
        $this->assertDatabaseHas('student_rubric_scores', [
            'rubric_criterion_id' => $this->crit1->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 80.00,
        ]);
        $this->assertDatabaseHas('student_rubric_scores', [
            'rubric_criterion_id' => $this->crit2->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 90.00,
        ]);

        // Check calculated assessment score in student_assessment_scores
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->rubricAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 86.00,
            'feedback' => 'Bagus sekali',
        ]);

        // Student 2: (70 * 0.4) + (60 * 0.6) = 28 + 36 = 64.00
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->rubricAssessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 64.00,
        ]);
    }

    public function test_empty_score_is_preserved_as_null_not_zero(): void
    {
        $payload = [
            'scores' => [
                $this->student1->id => '', // Kosong = belum dinilai
                $this->student2->id => null,
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $this->directAssessment->id]), $payload);

        $response->assertRedirect();

        $scoreRecord = StudentAssessmentScore::where('assessment_id', $this->directAssessment->id)
            ->where('mahasiswa_id', $this->student1->id)
            ->first();

        $this->assertNotNull($scoreRecord);
        $this->assertNull($scoreRecord->score);
    }

    public function test_validation_rejects_score_out_of_range(): void
    {
        $payload = [
            'scores' => [
                $this->student1->id => 105, // > 100
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $this->directAssessment->id]), $payload);

        $response->assertSessionHasErrors('scores');
    }

    public function test_validation_rejects_unenrolled_student(): void
    {
        $payload = [
            'scores' => [
                $this->unenrolledStudent->id => 80,
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $this->directAssessment->id]), $payload);

        $response->assertSessionHasErrors('scores');
    }

    public function test_dosen_can_download_template_csv_for_non_rubric_assessment(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.template', [$this->section->id, $this->directAssessment->id]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        // Stream output
        $content = $response->streamedContent();

        $this->assertStringContainsString('NIM', $content);
        $this->assertStringContainsString('Nama Mahasiswa', $content);
        $this->assertStringContainsString('Nilai (0-100)', $content);
        $this->assertStringContainsString('Catatan', $content);

        // Contains student records
        $this->assertStringContainsString('2024081001', $content);
        $this->assertStringContainsString('Andi Santoso', $content);
        $this->assertStringContainsString('2024081002', $content);
        $this->assertStringContainsString('Budi Pratama', $content);
    }

    public function test_dosen_can_download_template_csv_for_rubric_assessment(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.template', [$this->section->id, $this->rubricAssessment->id]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();

        $this->assertStringContainsString('NIM', $content);
        $this->assertStringContainsString('Nama Mahasiswa', $content);
        $this->assertStringContainsString('Pemahaman Algoritma (40%)', $content);
        $this->assertStringContainsString('Implementasi Kode (60%)', $content);
        $this->assertStringContainsString('Catatan', $content);
        $this->assertStringContainsString('Andi Santoso', $content);
    }
}
