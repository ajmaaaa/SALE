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
use App\Models\User;
use App\Support\AcademicPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanEssayGradingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private ClassSection $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableRoleGateForPreviewBehavior();

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

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
    }

    public function test_assessment_grading_dashboard_renders_pending_and_results_tabs(): void
    {
        $response = $this->get(route('dosen.item.penilaian', [1, 3]));

        $response->assertOk()
            ->assertSee('Perlu Dinilai')
            ->assertSee('Hasil Nilai')
            ->assertSee('Fajar Ramadhan')
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Nilai')
            ->assertSee('Formula Perhitungan', false)
            ->assertDontSee('ai-slop')
            ->assertDontSee('emoji');
    }

    public function test_two_column_essay_evaluation_view_renders_clean_reading_and_scoring_panels(): void
    {
        $response = $this->get(route('dosen.item.penilaian.esai', [
            'course' => 1,
            'item' => 3,
            'student' => 5, // Fajar Ramadhan
        ]));

        $response->assertOk()
            ->assertSee('Fajar Ramadhan')
            ->assertSee('SOAL')
            ->assertSee('ESAI')
            ->assertSee('JAWABAN MAHASISWA')
            ->assertSee('PENILAIAN')
            ->assertSee('Skor')
            ->assertSee('Porsi soal di CPMK')
            ->assertSee('Simpan & Selesai', false)
            ->assertDontSee('<textarea name="answer"', false); // Reading container must NOT be a textarea
    }

    public function test_essay_grading_validation_blocks_score_exceeding_max_points(): void
    {
        $evaluation = AcademicPreview::assessmentEvaluation(1, 3);
        $questions = $evaluation['questions'];
        $firstEssayIdx = null;
        foreach ($questions as $idx => $q) {
            if ($q['is_essay']) {
                $firstEssayIdx = $idx;
                break;
            }
        }

        $this->assertNotNull($firstEssayIdx);
        $maxPoints = $questions[$firstEssayIdx]['points'];

        $response = $this->post(route('dosen.item.penilaian.esai.save', [
            'course' => 1,
            'item' => 3,
            'student' => 5,
            'questionIndex' => $firstEssayIdx,
        ]), [
            'score' => $maxPoints + 50,
        ]);

        $response->assertSessionHasErrors('score');
    }

    public function test_saving_essay_score_updates_session_and_advances(): void
    {
        $evaluation = AcademicPreview::assessmentEvaluation(1, 3);
        $questions = $evaluation['questions'];
        $firstEssayIdx = null;
        foreach ($questions as $idx => $q) {
            if ($q['is_essay']) {
                $firstEssayIdx = $idx;
                break;
            }
        }

        $this->assertNotNull($firstEssayIdx);

        $response = $this->post(route('dosen.item.penilaian.esai.save', [
            'course' => 1,
            'item' => 3,
            'student' => 5, // Fajar Ramadhan
            'questionIndex' => $firstEssayIdx,
        ]), [
            'score' => 18,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(18.0, session("academic.item_grades.3.5.points.{$firstEssayIdx}"));
    }

    public function test_formula_calculation_obe_from_desain_1_matches_exact_example(): void
    {
        // Worked example from desain (1).md Section 10:
        // Kuis 1, 5 soal, 2 CPMK:
        // Q1 (PG, CPMK 1, 5 pts): score 5 -> nilai = 33.33
        // Q2 (PGK, CPMK 1, 15 pts): score 10 -> nilai = 22.22
        // Q3 (Esai, CPMK 1, 20 pts): score 15 -> nilai = 25.00
        // Q4 (B/S, CPMK 2, 20 pts): score 0 -> nilai = 0.00
        // Q5 (Jodohkan, CPMK 2, 40 pts): score 32 -> nilai = 40.00
        //
        // Expected:
        // CPMK 1 = 33.33 + 22.22 + 25 = 80.56
        // CPMK 2 = 0 + 40 = 40.00
        // Nilai Asesmen = (80.56 * 0.6) + (40.00 * 0.4) = 48.33 + 16 = 64.33
        session(['learning.items.99' => [
            'id' => 99,
            'course' => 1,
            'title' => 'Kuis 1 - Evaluasi OBE',
            'module' => 'Kuis',
            'type' => 'kuis',
            'questions' => [
                ['id' => 1, 'type' => 'pilihan', 'cpmk' => 'CPMK-01', 'points' => 5],
                ['id' => 2, 'type' => 'kompleks', 'cpmk' => 'CPMK-01', 'points' => 15],
                ['id' => 3, 'type' => 'uraian', 'cpmk' => 'CPMK-01', 'points' => 20],
                ['id' => 4, 'type' => 'benar_salah', 'cpmk' => 'CPMK-02', 'points' => 20],
                ['id' => 5, 'type' => 'mencocokkan', 'cpmk' => 'CPMK-02', 'points' => 40],
            ],
        ]]);

        session([
            'learning.submissions.99.1' => [
                'student_number' => '231011401234',
                'question_answers' => [
                    0 => ['choices' => ['Benar']],
                    1 => ['choices' => ['Benar', 'Benar']],
                    2 => ['text' => 'Stack adalah struktur LIFO...'],
                    3 => ['choices' => ['Salah']],
                    4 => ['pairs' => ['A' => 'B']],
                ],
            ],
            'academic.item_grades.99.1.points' => [
                0 => 5,
                1 => 10,
                2 => 15,
                3 => 0,
                4 => 32,
            ],
        ]);

        $eval = AcademicPreview::assessmentEvaluation(1, 99);
        $results = $eval['results'];

        $ahmadResult = collect($results)->firstWhere('student.id', 1);
        $this->assertNotNull($ahmadResult);

        $cpmkBreakdown = $ahmadResult['cpmk_breakdown'];

        // CPMK 1 check
        $this->assertEquals(80.56, $cpmkBreakdown['CPMK-01']['score']);
        // CPMK 2 check
        $this->assertEquals(40.0, $cpmkBreakdown['CPMK-02']['score']);
        // Overall assessment check
        $this->assertEquals(64.33, $ahmadResult['nilai_asesmen']);
    }

    public function test_navigation_header_starts_with_asesmen_without_matriks_penilaian(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen', $this->section->id));

        $response->assertOk()
            ->assertSee('1. Asesmen')
            ->assertSee('2. Rekap CPMK')
            ->assertSee('3. Rekap CPL')
            ->assertDontSee('1. Matriks Penilaian');
    }
}
