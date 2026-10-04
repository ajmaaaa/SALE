<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexibleTaskModeAndLainnyaTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $mahasiswa;

    private ClassSection $section;

    private MataKuliah $mataKuliah;

    private Cpmk $cpmk1;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::firstOrCreate(
            ['name' => Role::DOSEN],
            ['label' => 'Dosen']
        );
        $mhsRole = Role::firstOrCreate(
            ['name' => Role::MAHASISWA],
            ['label' => 'Mahasiswa']
        );

        $this->dosen = User::factory()->create([
            'role_id' => $dosenRole->id,
            'email' => 'dosen.test@example.com',
        ]);
        $this->mahasiswa = User::factory()->create([
            'role_id' => $mhsRole->id,
            'email' => 'mhs.test@example.com',
        ]);

        $prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
        ]);

        $semester = Semester::create([
            'code' => '20261',
            'name' => '2026/2027 Ganjil',
            'term' => 1,
            'is_active' => true,
        ]);

        $this->mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF101',
            'name' => 'Algoritma & Pemrograman',
            'sks' => 3,
            'semester_paket' => 1,
        ]);

        $this->cpmk1 = Cpmk::create([
            'prodi_id' => $prodi->id,
            'mata_kuliah_id' => $this->mataKuliah->id,
            'code' => 'CPMK-1',
            'description' => 'Memahami logika dasar pemrograman',
        ]);
        $this->mataKuliah->cpmks()->attach($this->cpmk1->id);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'IF101-A',
            'enrollment_code' => 'ENROLL123',
        ]);

        $this->section->students()->attach($this->mahasiswa->id);
    }

    public function test_lecturer_can_create_uts_in_regular_assignment_mode(): void
    {
        $payload = [
            'type' => 'uts',
            'task_mode' => 'regular',
            'title' => 'UTS Praktik Berkas',
            'module' => 'Modul UTS',
            'body' => 'Unggah laporan studi kasus dan diagram perancangan sistem.',
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            'due' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'allow_late' => '1',
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.item.store', $this->section->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'UTS Praktik Berkas')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals('uts', $assessment->type);
        $this->assertEquals('regular', $assessment->learning_payload['task_mode'] ?? null);
        $this->assertEquals(10, $assessment->final_weight);
        $this->assertTrue((bool) $assessment->allow_late);

        // Student views UTS in regular mode: should see standard submission panel, not quiz room
        $studentView = $this->actingAs($this->mahasiswa)
            ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));

        $studentView->assertOk();
        $studentView->assertSee('Tugas Anda');
        $studentView->assertSee('Kumpulkan Tugas');
        $studentView->assertDontSee('Mulai Kerjakan Kuis');
    }

    public function test_lecturer_can_create_uts_in_cbt_quiz_mode(): void
    {
        $payload = [
            'type' => 'uts',
            'task_mode' => 'quiz',
            'title' => 'UTS Teori CBT',
            'module' => 'Modul UTS CBT',
            'body' => 'Kerjakan butir soal pilihan ganda di ruang ujian.',
            'duration_mode' => 'enabled',
            'duration_minutes' => 90,
            'questions' => [
                [
                    'type' => 'pilihan',
                    'prompt' => 'Apa itu Big-O notation?',
                    'options' => "Kompleksitas waktu\nBahasa pemrograman\nHardware komputer",
                    'correct_answer' => 'Kompleksitas waktu',
                    'points' => 100,
                    'cpmk' => 'CPMK-1',
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.item.store', $this->section->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'UTS Teori CBT')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals('uts', $assessment->type);
        $this->assertEquals('quiz', $assessment->learning_payload['task_mode'] ?? null);
        $this->assertNotEmpty($assessment->learning_payload['questions'] ?? []);

        // Student views UTS in quiz mode: should see quiz room button
        $studentView = $this->actingAs($this->mahasiswa)
            ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));

        $studentView->assertOk();
        $studentView->assertSee('Mulai Kerjakan Kuis');
    }

    public function test_lecturer_can_create_item_with_type_lainnya(): void
    {
        $payload = [
            'type' => 'lainnya',
            'title' => 'Formulir Pendataan Kelompok',
            'module' => 'Administrasi Kelas',
            'body' => 'Silakan kumpulkan daftar anggota kelompok dan link repositori Github.',
            'due' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'allow_late' => '1',
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.item.store', $this->section->id), $payload);

        $response->assertRedirect();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'Formulir Pendataan Kelompok')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals('lainnya', $assessment->type);
        $this->assertEquals(0, (float) $assessment->final_weight);

        // Assure "lainnya" is excluded from gradable assessments and CPMK pivot
        $gradables = $this->section->gradableAssessments()->get();
        $this->assertFalse($gradables->contains('id', $assessment->id));
        $this->assertEmpty($assessment->cpmks);

        // Student views "lainnya": should see "Pengumpulan Anda" and "Kirimkan", no points
        $studentView = $this->actingAs($this->mahasiswa)
            ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));

        $studentView->assertOk();
        $studentView->assertSee('Pengumpulan Anda');
        $studentView->assertSee('Kirimkan');
        $studentView->assertDontSee('Total Bobot:');

        // Lecturer views "lainnya": should see "Lihat Pengumpulan Mahasiswa"
        $lecturerView = $this->actingAs($this->dosen)
            ->get(route('dosen.course.item', [$this->section->id, $assessment->id]));

        $lecturerView->assertOk();
        $lecturerView->assertSee('Lihat Pengumpulan Mahasiswa');
        $lecturerView->assertDontSee('Total Bobot:');
    }

    public function test_lecturer_can_create_uas_in_coding_mode(): void
    {
        $payload = [
            'type' => 'uas',
            'task_mode' => 'coding',
            'title' => 'UAS Praktikum Pemrograman',
            'module' => 'Modul UAS Coding',
            'body' => 'Implementasikan algoritma Dijkstra menggunakan Python.',
            'coding_steps' => [
                [
                    'title' => 'Implementasi Graph dan Dijkstra',
                    'body' => 'Buat fungsi dijkstra(graph, start) yang mengembalikan jarak terpendek.',
                    'cpmk' => 'CPMK-1',
                    'points' => 100,
                ],
            ],
            'due' => now()->addDays(10)->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.item.store', $this->section->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'UAS Praktikum Pemrograman')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals('uas', $assessment->type);
        $this->assertEquals('coding', $assessment->learning_payload['task_mode'] ?? null);

        // Student views UAS in coding mode: should see editor button
        $studentView = $this->actingAs($this->mahasiswa)
            ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));

        $studentView->assertOk();
        $studentView->assertSee('Mulai Kerjakan Tugas Koding');
    }

    public function test_input_nilai_page_loads_for_regular_uts_with_task_submission_modal(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-1',
            'name' => 'UTS Analisis Algoritma',
            'type' => 'uts',
            'description' => 'Kerjakan analisis kompleksitas.',
            'learning_payload' => [
                'type' => 'uts',
                'task_mode' => 'regular',
                'title' => 'UTS Analisis Algoritma',
                'module' => 'Modul UTS',
                'body' => 'Kerjakan analisis kompleksitas.',
                'points' => 100,
            ],
            'final_weight' => 20,
            'status' => Assessment::STATUS_PUBLISHED,
        ]);

        $this->section->gradableAssessments();

        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));

        $response->assertOk();
        $response->assertSee('Input Nilai');
        $response->assertSee('UTS Analisis Algoritma');
    }

    public function test_lecturer_can_create_pbl_and_student_submission_creates_pending_score(): void
    {
        $payload = [
            'type' => 'pbl',
            'task_mode' => 'regular',
            'title' => 'Proyek Capstone Web E-Commerce',
            'module' => 'PBL Tahap 1',
            'body' => 'Rancang arsitektur sistem dan kumpulkan laporan dokumen desain serta tautan prototipe.',
            'manual_cpmk_weights' => [
                'CPMK-1' => 100,
            ],
            'due' => now()->addDays(14)->format('Y-m-d\TH:i'),
            'allow_late' => '1',
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.item.store', $this->section->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'Proyek Capstone Web E-Commerce')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals('pbl', $assessment->type);
        $this->assertEquals('pbl', $assessment->learning_payload['component'] ?? null);

        // Verify PBL is included in gradable assessments
        $gradables = $this->section->gradableAssessments()->pluck('id')->all();
        $this->assertContains($assessment->id, $gradables);

        // Student submits assignment
        $submitResponse = $this->actingAs($this->mahasiswa)
            ->post(route('mahasiswa.course.submit', [$this->section->id, $assessment->id]), [
                'answer' => 'Berikut adalah tautan repositori proyek kami: https://github.com/mahasiswa/pbl-project',
                'link' => 'https://github.com/mahasiswa/pbl-project',
            ]);

        $submitResponse->assertSessionHasNoErrors();

        // Verify pending score record exists (score = null, meaning waiting for lecturer grading)
        $scoreRecord = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->where('mahasiswa_id', $this->mahasiswa->id)
            ->first();

        $this->assertNotNull($scoreRecord);
        $this->assertNull($scoreRecord->score);

        // Lecturer can view Input Nilai and sees student submission
        $nilaiResponse = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));

        $nilaiResponse->assertOk();
        $nilaiResponse->assertSee('Proyek Capstone Web E-Commerce');
    }

    public function test_quiz_assessment_isolates_only_essay_questions_for_manual_grading_in_input_nilai(): void
    {
        $payload = [
            'type' => 'kuis',
            'task_mode' => 'quiz',
            'title' => 'Kuis Logika & Algoritma',
            'module' => 'Modul Kuis 1',
            'body' => 'Kerjakan kuis berikut dengan teliti.',
            'question_type' => 'uraian',
            'questions' => [
                [
                    'type' => 'pilihan',
                    'prompt' => 'Apa kompleksitas pencarian binary search?',
                    'cpmk' => 'CPMK-1',
                    'points' => 50,
                    'options' => "O(1)\nO(n)\nO(log n)\nO(n^2)",
                    'correct_answer' => 'C',
                ],
                [
                    'type' => 'uraian',
                    'prompt' => 'Jelaskan perbedaan mendasar antara Stack dan Queue!',
                    'cpmk' => 'CPMK-1',
                    'points' => 50,
                    'essay_guide' => 'Stack bersifat LIFO sedangkan Queue bersifat FIFO.',
                ],
            ],
            'points' => 100,
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.item.store', $this->section->id), $payload);

        $response->assertSessionHasNoErrors();

        $assessment = Assessment::where('class_section_id', $this->section->id)
            ->where('name', 'Kuis Logika & Algoritma')
            ->first();

        $this->assertNotNull($assessment);

        // Access Input Nilai page as lecturer
        $nilaiView = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $assessment->id]));

        $nilaiView->assertOk();

        // In view data, verify that only the essay question is flagged for manual grading
        $this->assertTrue($nilaiView->viewData('hasEssayQuestions'));
        $this->assertTrue($nilaiView->viewData('isTipeSoal'));

        $studentData = $nilaiView->viewData('studentEssayData')[$this->mahasiswa->id] ?? null;
        $this->assertNotNull($studentData);
        $this->assertTrue($studentData['is_tipe_soal']);

        $essayList = collect($studentData['questions'])->filter(fn ($q) => ! empty($q['is_essay']));
        $this->assertCount(1, $essayList);
        $this->assertEquals('Jelaskan perbedaan mendasar antara Stack dan Queue!', $essayList->first()['prompt']);
    }

    public function test_create_item_form_has_unified_time_settings_and_no_duplicate_due_inputs(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.item.create', $this->section->id));

        $response->assertOk();
        $response->assertSee('data-main-category', false);
        $response->assertSee('data-assessment-type', false);
        $response->assertSee('data-unified-time-settings', false);
        $response->assertDontSee('data-task-mode-info', false);

        // Pastikan hanya ada tepat SATU input name="due" di seluruh form
        $content = $response->getContent();
        $dueMatches = preg_match_all('/name=["\']due["\']/', $content);
        $this->assertEquals(1, $dueMatches, 'Harus hanya ada tepat 1 input name="due" di form tambah konten');

        // Pastikan tidak ada id="task_due" atau id="quiz_due" yang lama
        $this->assertStringNotContainsString('id="task_due"', $content);
        $this->assertStringNotContainsString('id="quiz_due"', $content);
    }

    public function test_create_item_form_isolates_features_per_category_on_initial_render(): void
    {
        // 1. Pengumuman
        $resPengumuman = $this->actingAs($this->dosen)->get(route('dosen.item.create', [$this->section->id, 'type' => 'pengumuman']));
        $resPengumuman->assertOk();
        $this->assertMatchesRegularExpression('/<div\s+data-sub-category-wrapper\s+hidden/', $resPengumuman->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-task-mode-container[^>]*hidden/', $resPengumuman->getContent());
        $this->assertMatchesRegularExpression('/<section\s+data-unified-time-settings[^>]*hidden/', $resPengumuman->getContent());

        // 2. Materi
        $resMateri = $this->actingAs($this->dosen)->get(route('dosen.item.create', [$this->section->id, 'type' => 'materi']));
        $resMateri->assertOk();
        $this->assertMatchesRegularExpression('/<div\s+data-sub-category-wrapper\s+hidden/', $resMateri->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-task-mode-container[^>]*hidden/', $resMateri->getContent());
        $this->assertMatchesRegularExpression('/<section\s+data-unified-time-settings[^>]*hidden/', $resMateri->getContent());
        $this->assertDoesNotMatchRegularExpression('/<fieldset\s+data-material-mode-settings\s+hidden/', $resMateri->getContent());

        // 3. Kuis
        $resKuis = $this->actingAs($this->dosen)->get(route('dosen.item.create', [$this->section->id, 'type' => 'kuis']));
        $resKuis->assertOk();
        $this->assertDoesNotMatchRegularExpression('/<div\s+data-sub-category-wrapper\s+hidden/', $resKuis->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-task-mode-container[^>]*hidden/', $resKuis->getContent());
        $this->assertDoesNotMatchRegularExpression('/<section\s+data-unified-time-settings[^>]*hidden/', $resKuis->getContent());
        $this->assertDoesNotMatchRegularExpression('/<div\s+data-timer-setting-container[^>]*hidden/', $resKuis->getContent());

        // 4. PBL
        $resPbl = $this->actingAs($this->dosen)->get(route('dosen.item.create', [$this->section->id, 'type' => 'pbl']));
        $resPbl->assertOk();
        $this->assertDoesNotMatchRegularExpression('/<div\s+data-sub-category-wrapper\s+hidden/', $resPbl->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-task-mode-container[^>]*hidden/', $resPbl->getContent());
        $this->assertDoesNotMatchRegularExpression('/<section\s+data-unified-time-settings[^>]*hidden/', $resPbl->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-timer-setting-container[^>]*hidden/', $resPbl->getContent());
        $this->assertDoesNotMatchRegularExpression('/<fieldset\s+data-manual-cpmk-settings[^>]*hidden/', $resPbl->getContent());

        // 5. Tugas
        $resTugas = $this->actingAs($this->dosen)->get(route('dosen.item.create', [$this->section->id, 'type' => 'tugas']));
        $resTugas->assertOk();
        $this->assertDoesNotMatchRegularExpression('/<div\s+data-sub-category-wrapper\s+hidden/', $resTugas->getContent());
        $this->assertDoesNotMatchRegularExpression('/<div\s+data-task-mode-container[^>]*hidden/', $resTugas->getContent());
        $this->assertDoesNotMatchRegularExpression('/<section\s+data-unified-time-settings[^>]*hidden/', $resTugas->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-timer-setting-container[^>]*hidden/', $resTugas->getContent());
        $this->assertDoesNotMatchRegularExpression('/<fieldset\s+data-manual-cpmk-settings[^>]*hidden/', $resTugas->getContent());

        // 6. Lainnya
        $resLainnya = $this->actingAs($this->dosen)->get(route('dosen.item.create', [$this->section->id, 'type' => 'lainnya']));
        $resLainnya->assertOk();
        $this->assertMatchesRegularExpression('/<div\s+data-sub-category-wrapper\s+hidden/', $resLainnya->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-task-mode-container[^>]*hidden/', $resLainnya->getContent());
        $this->assertDoesNotMatchRegularExpression('/<section\s+data-unified-time-settings[^>]*hidden/', $resLainnya->getContent());
        $this->assertMatchesRegularExpression('/<div\s+data-timer-setting-container[^>]*hidden/', $resLainnya->getContent());
        $this->assertMatchesRegularExpression('/<fieldset\s+data-manual-cpmk-settings[^>]*hidden/', $resLainnya->getContent());
    }
}
