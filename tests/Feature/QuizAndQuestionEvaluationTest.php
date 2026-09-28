<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\Course;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use App\Support\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAndQuestionEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_question_types_canonicalization_and_evaluation(): void
    {
        // 1. Pilihan Ganda (Single Choice)
        $qPilihan = QuizQuestion::canonicalizeQuestion([
            'type' => 'pilihan',
            'prompt' => 'Apa ibukota Indonesia?',
            'options' => "Jakarta\nBandung\nSurabaya\nMedan",
            'correct_answer' => 'A',
            'points' => 100,
        ]);
        $this->assertCount(4, $qPilihan['option_items']);
        $this->assertCount(1, $qPilihan['answer_key']['option_ids']);
        $correctOptId = $qPilihan['option_items'][0]['id'];
        $wrongOptId = $qPilihan['option_items'][1]['id'];

        $this->assertSame(100.0, QuizQuestion::evaluate($qPilihan, ['option_ids' => [$correctOptId]]));
        $this->assertSame(0.0, QuizQuestion::evaluate($qPilihan, ['option_ids' => [$wrongOptId]]));

        // 2. Benar / Salah (Boolean)
        $qBoolean = QuizQuestion::canonicalizeQuestion([
            'type' => 'benar_salah',
            'prompt' => 'Laravel adalah framework PHP.',
            'boolean_answer' => 'Benar',
            'points' => 100,
        ]);
        $this->assertCount(2, $qBoolean['option_items']);
        $trueOptId = $qBoolean['option_items'][0]['id'];
        $falseOptId = $qBoolean['option_items'][1]['id'];

        $this->assertSame(100.0, QuizQuestion::evaluate($qBoolean, ['option_ids' => [$trueOptId]]));
        $this->assertSame(0.0, QuizQuestion::evaluate($qBoolean, ['option_ids' => [$falseOptId]]));

        // 3. Pilihan Ganda Kompleks (Multiple Choice)
        $qKompleks = QuizQuestion::canonicalizeQuestion([
            'type' => 'kompleks',
            'prompt' => 'Pilih bilangan genap:',
            'options' => "2\n3\n4\n5",
            'correct_answer' => 'A, C',
            'score_mode' => 'parsial',
            'points' => 100,
        ]);
        $this->assertCount(4, $qKompleks['option_items']);
        $this->assertCount(2, $qKompleks['answer_key']['option_ids']);
        $optA = $qKompleks['option_items'][0]['id'];
        $optB = $qKompleks['option_items'][1]['id'];
        $optC = $qKompleks['option_items'][2]['id'];

        // Full score: user picked A and C
        $this->assertSame(100.0, QuizQuestion::evaluate($qKompleks, ['option_ids' => [$optA, $optC]]));
        // Partial score: user picked only A
        $this->assertSame(50.0, QuizQuestion::evaluate($qKompleks, ['option_ids' => [$optA]]));
        // Wrong picked: user picked A and wrong option B -> net correct = 1 - 1 = 0
        $this->assertSame(0.0, QuizQuestion::evaluate($qKompleks, ['option_ids' => [$optA, $optB]]));

        // 4. Mencocokkan (Matching)
        $qMatching = QuizQuestion::canonicalizeQuestion([
            'type' => 'mencocokkan',
            'prompt' => 'Pasangkan data structure berikut:',
            'options' => "Stack = LIFO\nQueue = FIFO",
            'points' => 100,
        ]);
        $this->assertCount(2, $qMatching['matching_items']);
        $correctMatches = $qMatching['answer_key']['matches'];
        $this->assertSame(100.0, QuizQuestion::evaluate($qMatching, ['matches' => $correctMatches]));

        // Partial matching
        $firstKey = array_key_first($correctMatches);
        $this->assertSame(50.0, QuizQuestion::evaluate($qMatching, ['matches' => [$firstKey => $correctMatches[$firstKey]]]));
    }

    public function test_rekap_cpmk_syncs_default_weight_without_sql_error(): void
    {
        $prodi = Prodi::create(['name' => 'Informatika', 'code' => 'IF']);
        $mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF101',
            'name' => 'Algoritma',
            'sks' => 3,
            'semester' => 1,
        ]);
        $lecturerRole = \App\Models\Role::firstOrCreate(['name' => \App\Models\Role::DOSEN], ['label' => 'Dosen']);
        $dosen = User::create([
            'name' => 'Dosen Uji',
            'email' => 'dosen-uji@test.local',
            'password' => 'password',
            'role_id' => $lecturerRole->id,
            'nim_nidn' => 'D001',
        ]);

        $semester = \App\Models\Semester::firstOrCreate(['code' => '2026-1'], ['name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'code' => 'CPMK-1',
            'description' => 'Mampu menganalisis algoritma',
        ]);
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'KUIS-01',
            'name' => 'Kuis 1',
            'type' => 'kuis',
            'final_weight' => 20,
        ]);

        // Access rekap endpoint as lecturer
        $response = $this->actingAs($dosen)->get(route('dosen.penilaian.rekap', $section->id));
        $response->assertOk();

        // Ensure pivot assessment_cpmk has default weight 100
        $this->assertDatabaseHas('assessment_cpmk', [
            'assessment_id' => $assessment->id,
            'cpmk_id' => $cpmk->id,
            'weight' => 100.0,
        ]);
    }

    public function test_course_enrolled_members_modal_does_not_have_flex_on_dialog_tag(): void
    {
        $prodi = Prodi::create(['name' => 'Informatika', 'code' => 'IF']);
        $mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF102',
            'name' => 'Basis Data',
            'sks' => 3,
            'semester' => 2,
        ]);
        $lecturerRole = \App\Models\Role::firstOrCreate(['name' => \App\Models\Role::DOSEN], ['label' => 'Dosen']);
        $dosen = User::create([
            'name' => 'Dosen Basis Data',
            'email' => 'dosen-bd@test.local',
            'password' => 'password',
            'role_id' => $lecturerRole->id,
            'nim_nidn' => 'D002',
        ]);
        $semester = \App\Models\Semester::firstOrCreate(['code' => '2026-1'], ['name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.course.show', $section->id));
        $response->assertOk();

        // Ensure dialog element does not contain class "flex flex-col" directly on the dialog
        $response->assertDontSee('<dialog id="enrolled-students-modal" class="fixed inset-0 m-auto flex flex-col', false);
        // Ensure inner container uses flex instead
        $response->assertSee('<dialog id="enrolled-students-modal"', false);
        $response->assertSee('<div class="flex flex-col max-h-[85vh]">', false);
        // Ensure no redundant bottom close button
        $response->assertDontSee('class="button-secondary text-xs py-1.5 px-4 cursor-pointer">Tutup</button>', false);
    }
}
