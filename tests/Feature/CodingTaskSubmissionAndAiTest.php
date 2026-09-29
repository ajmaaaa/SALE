<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodingTaskSubmissionAndAiTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $mahasiswa;
    private ClassSection $section;
    private MataKuliah $mataKuliah;
    private Cpmk $cpmk;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::firstOrCreate(['name' => Role::DOSEN], ['label' => 'Dosen']);
        $mahasiswaRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);

        $this->dosen = User::factory()->create([
            'role_id' => $dosenRole->id,
            'email' => 'dosen.coding@example.test',
        ]);

        $this->mahasiswa = User::factory()->create([
            'role_id' => $mahasiswaRole->id,
            'nim_nidn' => '20260001',
            'email' => 'mhs.coding@example.test',
        ]);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $this->mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF-CODE',
            'name' => 'Pemrograman Lanjut',
            'sks' => 3,
            'semester_paket' => 2,
        ]);

        $this->cpmk = Cpmk::create([
            'prodi_id' => $prodi->id,
            'mata_kuliah_id' => $this->mataKuliah->id,
            'code' => 'CPMK-01',
            'description' => 'Mampu menulis kode program terstruktur',
        ]);
        $this->mataKuliah->cpmks()->attach([$this->cpmk->id]);

        $semester = Semester::create([
            'code' => '20261',
            'name' => 'Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'term' => 1,
            'is_active' => true,
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 30,
            'enrollment_code' => 'CODE2026',
        ]);

        $this->section->students()->attach($this->mahasiswa->id);
    }

    public function test_lecturer_can_set_ai_enabled_in_content_setting(): void
    {
        // 1. Dosen creates coding task with AI enabled (ai_enabled = 1)
        $res = $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'title' => 'Tugas Binary Search Tree',
            'module' => 'Modul 4: Tree',
            'body' => 'Implementasikan struktur data BST dalam bahasa Python.',
            'ai_enabled' => '1',
            'formats' => ['text'],
            'cpmk' => $this->cpmk->code,
            'code_language' => 'python',
            'coding_steps' => [
                [
                    'title' => 'Langkah 1: Struktur Node',
                    'cpmk' => $this->cpmk->code,
                    'cpmk_id' => $this->cpmk->id,
                    'points' => 100,
                    'body' => 'Buat class Node.',
                    'code' => 'class Node:\n    pass',
                ],
            ],
        ]);

        $res->assertSessionHasNoErrors();
        $assessment = Assessment::latest('id')->first();
        $this->assertNotNull($assessment);
        $this->assertTrue((bool) ($assessment->learning_payload['ai_enabled'] ?? false));

        // 2. Dosen updates task with AI disabled (ai_enabled = 0)
        $updateRes = $this->actingAs($this->dosen)->put(route('dosen.item.update', [$this->section->id, $assessment->id]), [
            'type' => 'tugas',
            'task_mode' => 'coding',
            'title' => 'Tugas Binary Search Tree (Tanpa AI)',
            'module' => 'Modul 4: Tree',
            'body' => 'Implementasikan struktur data BST tanpa bantuan AI.',
            'ai_enabled' => '0',
            'formats' => ['text'],
            'cpmk' => $this->cpmk->code,
            'code_language' => 'python',
            'coding_steps' => [
                [
                    'title' => 'Langkah 1: Struktur Node',
                    'cpmk' => $this->cpmk->code,
                    'cpmk_id' => $this->cpmk->id,
                    'points' => 100,
                    'body' => 'Buat class Node mandiri.',
                    'code' => 'class Node:\n    pass',
                ],
            ],
        ]);

        $updateRes->assertSessionHasNoErrors();
        $assessment->refresh();
        $this->assertFalse((bool) ($assessment->learning_payload['ai_enabled'] ?? true));
    }

    public function test_student_can_submit_code_without_answering_all_questions_validation_error(): void
    {
        // Create coding assessment
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-SORT',
            'name' => 'Praktikum Sorting',
            'final_weight' => 10,
            'status' => 'published',
            'type' => 'tugas',
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'coding',
                'title' => 'Praktikum Sorting',
                'module' => 'Modul 3',
                'body' => 'Tulis algoritma QuickSort.',
                'question_type' => 'coding',
                'formats' => ['text'],
                'questions' => [
                    [
                        'id' => 1,
                        'type' => 'uraian',
                        'prompt' => 'Langkah 1: Implementasi QuickSort',
                        'points' => 100,
                        'cpmk' => 'CPMK-01',
                    ],
                ],
                'material_steps' => [
                    [
                        'title' => 'Langkah 1: Implementasi QuickSort',
                        'cpmk' => 'CPMK-01',
                        'body' => 'Tulis algoritma QuickSort.',
                        'code' => 'def quicksort(arr): return arr',
                    ],
                ],
            ],
        ]);

        $codeAnswer = json_encode([
            ['name' => 'main.py', 'code' => 'def quicksort(arr):\n    if len(arr) <= 1: return arr\n    pivot = arr[0]\n    return quicksort([x for x in arr[1:] if x < pivot]) + [pivot] + quicksort([x for x in arr[1:] if x >= pivot])'],
        ]);

        // Student submits code
        $submitRes = $this->actingAs($this->mahasiswa)->post(route('mahasiswa.course.submit', [$this->section->id, $assessment->id]), [
            'answer' => $codeAnswer,
        ]);

        $submitRes->assertSessionHasNoErrors();
        $submitRes->assertSessionMissing('errors');

        $submission = Submission::where('assessment_id', $assessment->id)->where('user_id', $this->mahasiswa->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame($codeAnswer, $submission->answer);
    }

    public function test_coding_workbench_view_has_no_mode_tinjau_and_only_two_nav_buttons(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-PY',
            'name' => 'Tugas Python',
            'final_weight' => 10,
            'status' => 'published',
            'type' => 'tugas',
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'coding',
                'title' => 'Tugas Python',
                'module' => 'Modul 1',
                'body' => 'Kerjakan tugas python.',
                'question_type' => 'coding',
                'ai_enabled' => false,
                'material_steps' => [
                    [
                        'title' => 'Bagian 1',
                        'cpmk' => 'CPMK-01',
                        'body' => 'Instruksi bagian 1',
                        'code' => 'print("hello")',
                    ],
                    [
                        'title' => 'Bagian 2',
                        'cpmk' => 'CPMK-01',
                        'body' => 'Instruksi bagian 2',
                        'code' => 'print("world")',
                    ],
                ],
            ],
        ]);

        // 1. Dosen view does NOT see "Mode Tinjau Dosen" badge
        $dosenRes = $this->actingAs($this->dosen)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $dosenRes->assertOk();
        $dosenRes->assertDontSee('Mode Tinjau Dosen');

        // 2. Buttons: "Sebelumnya" and "Selanjutnya" exist, and submit is hidden until last step
        $studentRes = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $studentRes->assertOk();
        $studentRes->assertDontSee('Mode Tinjau Dosen');
        $studentRes->assertSee('Sebelumnya');
        $studentRes->assertSee('Selanjutnya');
        $studentRes->assertSee('Serahkan');
        $studentRes->assertDontSee('Kumpulkan Kode');

        // 3. AI Asisten is disabled so panel-ai is not rendered
        $studentRes->assertDontSee('id="panel-ai"', false);
        $studentRes->assertDontSee('Tanyakan Baris');

        // 4. Now test with AI enabled
        $assessment->update([
            'learning_payload' => array_merge($assessment->learning_payload, ['ai_enabled' => true]),
        ]);
        $withAiRes = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $withAiRes->assertOk();
        $withAiRes->assertSee('id="panel-ai"', false);
        $withAiRes->assertSee('AI Asisten');
        $withAiRes->assertSee('Tanyakan Baris');
    }

    public function test_lecturer_can_view_student_coding_answer_and_grade_in_penilaian(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-BS',
            'name' => 'Tugas Binary Search',
            'final_weight' => 10,
            'status' => 'published',
            'type' => 'tugas',
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'coding',
                'title' => 'Tugas Binary Search',
                'module' => 'Modul 2',
                'body' => 'Buat fungsi binary search.',
                'question_type' => 'coding',
                'questions' => [
                    [
                        'id' => 1,
                        'type' => 'uraian',
                        'prompt' => 'Fungsi binary search',
                        'points' => 100,
                        'cpmk' => 'CPMK-01',
                    ],
                ],
            ],
        ]);
        $assessment->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        $submittedCode = json_encode([
            ['name' => 'solution.py', 'code' => 'def binary_search(arr, target):\n    low, high = 0, len(arr) - 1\n    return -1'],
        ]);

        Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->mahasiswa->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'pending',
            'submitted_at' => now(),
            'answer' => $submittedCode,
        ]);

        // Lecturer opens assessment grade page
        $res = $this->actingAs($this->dosen)->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));
        $res->assertOk();

        // Check that studentEssayData contains is_coding = true, is_assignment = true, is_tipe_soal = false
        $res->assertSee('"is_coding":true', false);
        $res->assertSee('"is_assignment":true', false);
        $res->assertSee('"is_tipe_soal":false', false);
        $res->assertSee('solution.py');

        // Lecturer saves score via CPMK input
        $scoreRes = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.score', [$this->section->id, $assessment->id, $this->mahasiswa->id]),
            ['cpmk_scores' => [$this->cpmk->id => 95]]
        );

        $scoreRes->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'cpmk_id' => $this->cpmk->id,
            'score' => 95,
        ]);
    }
}
