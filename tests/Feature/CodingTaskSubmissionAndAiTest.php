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
        $this->assertEquals(1, (int) \Illuminate\Support\Facades\DB::table('ai_tasks')->where('id', $assessment->id)->value('enabled'));

        // Student views task with AI enabled
        $viewRes = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $viewRes->assertOk();
        $viewRes->assertDontSee('AI Aktif');
        $viewRes->assertDontSee('Interaktif');
        $viewRes->assertSee('id="panel-ai"', false);

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
        $this->assertEquals(0, (int) \Illuminate\Support\Facades\DB::table('ai_tasks')->where('id', $assessment->id)->value('enabled'));

        // Student views task with AI disabled: no panel-ai, no status badge
        $viewResDisabled = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $viewResDisabled->assertOk();
        $viewResDisabled->assertDontSee('AI Dinonaktifkan');
        $viewResDisabled->assertDontSee('id="panel-ai"', false);

        // 3. Dosen creates programming material with multiple steps and AI disabled
        $matRes = $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'materi',
            'material_mode' => 'coding',
            'title' => 'Materi Praktikum Algoritma',
            'module' => 'Modul 1: Pengenalan',
            'body' => 'Pelajari konsep dasar pemrograman.',
            'ai_enabled' => '0',
            'cpmk' => $this->cpmk->code,
            'code_language' => 'python',
            'coding_steps' => [
                [
                    'title' => 'Tahap 1: Sintaks Dasar',
                    'body' => 'Pahami print dan variabel.',
                ],
                [
                    'title' => 'Tahap 2: Pengulangan Loop',
                    'body' => 'Pahami for dan while loop.',
                ],
            ],
        ]);

        $matRes->assertSessionHasNoErrors();
        $matAssessment = Assessment::latest('id')->first();
        $this->assertNotNull($matAssessment);
        $this->assertEquals('materi', $matAssessment->type);
        $this->assertFalse((bool) ($matAssessment->learning_payload['ai_enabled'] ?? true));
        $this->assertEquals(0, (int) \Illuminate\Support\Facades\DB::table('ai_tasks')->where('id', $matAssessment->id)->value('enabled'));

        // Student views programming material: shows 'Materi Pemrograman', no CPMK badge, no submit button/modal, and no AI/Interaktif badges
        $matView = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $matAssessment->id]));
        $matView->assertOk();
        $matView->assertSee('Materi Pemrograman');
        $matView->assertSee('Kembali ke Kelas');
        $matView->assertDontSee('id="panel-step-cpmk"', false);
        $matView->assertDontSee('id="btn-submit-code-trigger"', false);
        $matView->assertDontSee('id="coding-submit-confirm-modal"', false);
        $matView->assertDontSee('AI Dinonaktifkan');
        $matView->assertDontSee('Interaktif');
        $matView->assertDontSee('id="panel-ai"', false);

        // Lecturer views programming material: displays material view without redirecting to penilaian and no grading panel
        $dosenMatView = $this->actingAs($this->dosen)->get(route('course.assignment.code', [$this->section->id, $matAssessment->id]));
        $dosenMatView->assertOk();
        $dosenMatView->assertDontSee('id="panel-grading"', false);
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

        // 1. Dosen view cannot take task; redirected to penilaian page
        $dosenRes = $this->actingAs($this->dosen)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $dosenRes->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));

        // Dosen reviewing student with ?student=
        $dosenReviewRes = $this->actingAs($this->dosen)->get(route('course.assignment.code', [$this->section->id, $assessment->id]) . '?student=' . $this->mahasiswa->id);
        $dosenReviewRes->assertDontSee('Meninjau:');
        $dosenReviewRes->assertSee($this->mahasiswa->name);
        $dosenReviewRes->assertSee('Kembali ke Penilaian');
        $dosenReviewRes->assertDontSee('id="panel-ai"', false);
        $dosenReviewRes->assertDontSee('Serahkan');
        $dosenReviewRes->assertDontSee('Simpan &amp; Selesai', false);
        $dosenReviewRes->assertDontSee('Simpan Nilai');
        $dosenReviewRes->assertSee('id="btn-step-next"', false);
        $dosenReviewRes->assertSee('name="return_to"', false);
        $dosenReviewRes->assertSee('data-read-only="1"', false);

        // 2. Buttons: "Sebelumnya" and "Selanjutnya" exist, and submit is hidden until last step
        $studentRes = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $studentRes->assertOk();
        $studentRes->assertDontSee('Mode Tinjau Dosen');
        $studentRes->assertSee('Sebelumnya');
        $studentRes->assertSee('Selanjutnya');
        $studentRes->assertSee('Serahkan');
        $studentRes->assertDontSee('Kumpulkan Kode');

        // 3. Mobile responsiveness tabs and panels exist for Android/mobile
        $studentRes->assertSee('id="mobile-workbench-tabs"', false);
        $studentRes->assertSee('data-mobile-tab="editor"', false);
        $studentRes->assertSee('data-mobile-tab="question"', false);
        $studentRes->assertSee('mobile-panel-active', false);
        $studentRes->assertSee('data-terminal-fullscreen-toggle', false);

        // Verify order: Soal (question) is before Editor Kode (editor)
        $content = $studentRes->getContent();
        $qPos = strpos($content, 'data-mobile-tab="question"');
        $ePos = strpos($content, 'data-mobile-tab="editor"');
        $this->assertTrue($qPos !== false && $ePos !== false && $qPos < $ePos, 'Soal tab must be to the left of Editor tab');

        // 4. AI Asisten is disabled so panel-ai is not rendered
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

    public function test_lecturer_can_grade_multi_question_coding_task_per_step_and_syncs_to_cpmk(): void
    {
        // Create 2 CPMKs for the course
        $cpmk2 = Cpmk::create([
            'prodi_id' => $this->cpmk->prodi_id,
            'mata_kuliah_id' => $this->mataKuliah->id,
            'code' => 'CPMK-02',
            'description' => 'Mampu mengimplementasikan algoritma pencarian',
        ]);
        $this->mataKuliah->cpmks()->attach([$cpmk2->id]);

        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'type' => 'tugas',
            'code' => 'TGS-MULTI-CODING',
            'name' => 'Tugas Praktikum 2 Soal',
            'final_weight' => 20,
            'status' => 'published',
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'coding',
                'title' => 'Tugas Praktikum 2 Soal',
                'module' => 'Modul 3',
                'body' => 'Selesaikan 2 soal pemrograman berikut.',
                'coding_steps' => [
                    [
                        'title' => 'Soal 1: Fungsi Factorial',
                        'cpmk' => 'CPMK-01',
                        'points' => 50,
                        'body' => 'Buat fungsi factorial',
                        'code' => 'def factorial(n): pass',
                    ],
                    [
                        'title' => 'Soal 2: Fungsi Fibonacci',
                        'cpmk' => 'CPMK-02',
                        'points' => 50,
                        'body' => 'Buat fungsi fibonacci',
                        'code' => 'def fibonacci(n): pass',
                    ],
                ],
            ],
        ]);
        $assessment->cpmks()->attach([
            $this->cpmk->id => ['weight' => 50],
            $cpmk2->id => ['weight' => 50],
        ]);

        $submittedCode = json_encode([
            [
                'step' => 1,
                'name' => 'factorial.py',
                'code' => "def factorial(n):\n    return 1 if n <= 1 else n * factorial(n - 1)",
            ],
            [
                'step' => 2,
                'name' => 'fibonacci.py',
                'code' => "def fibonacci(n):\n    return n if n <= 1 else fibonacci(n - 1) + fibonacci(n - 2)",
            ],
        ]);

        $submission = Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->mahasiswa->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'pending',
            'submitted_at' => now(),
            'answer' => $submittedCode,
        ]);

        // Dosen opens grading page
        $res = $this->actingAs($this->dosen)->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));
        $res->assertOk();
        $res->assertSee('Buka di Editor Kode');
        $res->assertSee('Penilaian Tugas Coding (Per Butir Soal)');

        // Check created submission answers
        $answers = \App\Models\SubmissionAnswer::where('submission_id', $submission->id)->get();
        $this->assertCount(2, $answers);

        $ans1 = $answers->firstWhere('question_id', '1');
        $ans2 = $answers->firstWhere('question_id', '2');
        $this->assertNotNull($ans1);
        $this->assertNotNull($ans2);
        $this->assertEquals(50, $ans1->max_score);
        $this->assertEquals(50, $ans2->max_score);

        // Dosen grades Soal 1 = 45 / 50 and Soal 2 = 50 / 50
        $gradeRes = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.coding_scores', [$this->section->id, $assessment->id, $this->mahasiswa->id]),
            [
                'scores' => [
                    $ans1->id => 45,
                    $ans2->id => 50,
                ],
            ]
        );

        $gradeRes->assertSessionHasNoErrors();
        $gradeRes->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));

        // Verify earned_scores in submission_answers
        $this->assertDatabaseHas('submission_answers', [
            'id' => $ans1->id,
            'earned_score' => 45,
        ]);
        $this->assertDatabaseHas('submission_answers', [
            'id' => $ans2->id,
            'earned_score' => 50,
        ]);

        // Verify CPMK scores:
        // Soal 1 (45/50) = 90% of CPMK-01 (max 50) -> 45
        // Soal 2 (50/50) = 100% of CPMK-02 (max 50) -> 50
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'cpmk_id' => $this->cpmk->id,
            'score' => 45,
        ]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'cpmk_id' => $cpmk2->id,
            'score' => 50,
        ]);

        // Total score = 45 + 50 = 95
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'score' => 95,
        ]);
    }

    public function test_submitted_or_graded_coding_task_is_strictly_read_only_with_no_ai_and_faded_disabled_submit_button(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-LOCK-TEST',
            'name' => 'Tugas Coding Terkunci',
            'type' => 'coding',
            'final_weight' => 15,
            'status' => 'published',
            'learning_payload' => [
                'task_mode' => 'coding',
                'question_type' => 'coding',
                'ai_enabled' => true,
                'points' => 100,
                'coding_steps' => [
                    [
                        'title' => 'Soal 1: Binary Tree',
                        'cpmk' => 'CPMK-01',
                        'points' => 100,
                        'code' => 'class Node: pass',
                    ],
                ],
            ],
        ]);

        // Student submits task
        Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->mahasiswa->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'submitted',
            'submitted_at' => now(),
            'answer' => json_encode([['name' => 'untitled.py', 'code' => 'print("done")']]),
        ]);

        $res = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $assessment->id]));
        $res->assertOk();

        // 1. Editor must be strictly read-only
        $res->assertSee('data-read-only="1"', false);
        $res->assertSee('data-is-submitted="1"', false);
        $res->assertSee('Mode Baca Saja (Tugas Telah Diserahkan - Terkunci)');

        // 2. AI panel must be completely hidden/removed
        $res->assertDontSee('id="panel-ai"', false);
        $res->assertDontSee('Tanyakan Baris');

        // 3. Submit button must be disabled, faded (opacity-40), and cannot be clicked (no form / trigger)
        $res->assertSee('id="status-submitted-badge"', false);
        $res->assertSee('Sudah Diserahkan');
        $res->assertSee('opacity-40');
        $res->assertDontSee('id="btn-submit-code-trigger"', false);
        $res->assertDontSee('id="form-code-submit"', false);
        $res->assertDontSee('id="coding-submit-confirm-modal"', false);

        // 4. Also test when student is graded: remains strictly locked even without active submission submitted_at
        $gradedAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-GRADED-TEST',
            'name' => 'Tugas Coding Sudah Dinilai',
            'type' => 'coding',
            'final_weight' => 15,
            'status' => 'published',
            'learning_payload' => [
                'task_mode' => 'coding',
                'question_type' => 'coding',
                'ai_enabled' => true,
                'points' => 100,
            ],
        ]);

        \App\Models\StudentAssessmentScore::create([
            'assessment_id' => $gradedAssessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'score' => 88.5,
            'status' => \App\Models\StudentAssessmentScore::STATUS_PUBLISHED,
        ]);

        $gradedRes = $this->actingAs($this->mahasiswa)->get(route('course.assignment.code', [$this->section->id, $gradedAssessment->id]));
        $gradedRes->assertOk();
        $gradedRes->assertSee('data-read-only="1"', false);
        $gradedRes->assertDontSee('id="panel-ai"', false);
        $gradedRes->assertSee('id="status-submitted-badge"', false);
        $gradedRes->assertDontSee('id="btn-submit-code-trigger"', false);
    }

    public function test_lecturer_review_coding_submission_displays_student_code_and_completion_time_without_meninjau_badge_or_center_timer(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-REVIEW-TEST',
            'name' => 'Tugas Review Koding',
            'final_weight' => 15,
            'status' => 'published',
            'type' => 'tugas',
            'learning_payload' => [
                'type' => 'tugas',
                'task_mode' => 'coding',
                'title' => 'Tugas Review Koding',
                'module' => 'Modul 5',
                'body' => 'Kerjakan tugas BST.',
                'question_type' => 'coding',
                'duration_enabled' => true,
                'duration_minutes' => 60,
                'coding_steps' => [
                    [
                        'title' => 'Bagian 1: Inisialisasi',
                        'cpmk' => 'CPMK-01',
                        'points' => 100,
                        'code' => 'class Node: pass',
                    ],
                ],
            ],
        ]);
        $assessment->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        // Create attempt: started 40 minutes before submission
        $startedAt = now()->subMinutes(40);
        $submittedAt = now();

        \App\Models\AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'attempt' => 1,
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'deadline_at' => $startedAt->copy()->addMinutes(60),
            'status' => \App\Models\AssessmentAttempt::STATUS_SUBMITTED,
        ]);

        $studentCustomCode = 'def my_custom_solution(): return 42';
        $submission = Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->mahasiswa->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'pending',
            'submitted_at' => $submittedAt,
            'answer' => json_encode([
                [
                    'name' => 'custom_bst.py',
                    'code' => $studentCustomCode,
                    'step' => 1,
                ],
            ]),
        ]);

        // Lecturer opens the student coding review page
        $res = $this->actingAs($this->dosen)->get(route('course.assignment.code', [$this->section->id, $assessment->id]) . '?student=' . $this->mahasiswa->id);
        $res->assertOk();

        // 1. Must NOT see 'Meninjau:' badge
        $res->assertDontSee('Meninjau:');

        // 2. Must NOT see 'Timer: 60 mnt' in center
        $res->assertDontSee('Timer: 60 mnt');

        // 3. Must see 'Daftar Bagian' in center
        $res->assertSee('Daftar Bagian');

        // 4. Must see student's submitted code in the payload
        $res->assertSee($studentCustomCode);
        $res->assertSee('custom_bst.py');

        // 5. Must see completion time in right panel: '40/60 menit'
        $res->assertSee('Waktu Selesai:');
        $res->assertSee('40/60 menit');
    }
}
