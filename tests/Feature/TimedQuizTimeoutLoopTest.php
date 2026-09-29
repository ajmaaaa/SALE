<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimedQuizTimeoutLoopTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $dosen;
    private ClassSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mhsRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data dan Algoritma', 'sks' => 3]);

        $this->dosen = User::create([
            'name' => 'Dr. Budi Santoso, M.Kom.',
            'email' => 'budi@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '198501012010121001',
        ]);

        $this->student = User::create([
            'name' => 'Ahmad Maulana',
            'email' => 'ahmad.maulana@student.test',
            'password' => Hash::make('password'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '231011401234',
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TESTIF01',
        ]);

        $this->section->students()->attach($this->student->id);
    }

    public function test_expired_or_rejected_attempt_shows_timeout_screen_without_countdown_or_loop(): void
    {
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-EXPIRED',
            'name' => 'Kuis Expired Test',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(2),
            'learning_payload' => [
                'duration_enabled' => true,
                'duration_minutes' => 2,
                'questions' => [[
                    'id' => 'q_test_1',
                    'type' => 'pilihan',
                    'prompt' => 'Apa itu testing?',
                    'points' => 100,
                    'options' => "A\nB\nC\nD",
                    'option_items' => [
                        ['id' => 'opt_1', 'text' => 'A'],
                        ['id' => 'opt_2', 'text' => 'B'],
                    ],
                    'answer_key' => [
                        'option_ids' => ['opt_1'],
                        'matches' => [],
                    ],
                ]],
            ],
        ]);

        // Create an attempt that is already rejected (e.g. timeout)
        AssessmentAttempt::create([
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'attempt' => 1,
            'status' => AssessmentAttempt::STATUS_REJECTED,
            'started_at' => now()->subMinutes(10),
            'deadline_at' => now()->subMinutes(8),
            'rejected_at' => now()->subMinutes(8),
            'rejection_reason' => 'Submission ditolak karena melewati deadline attempt.',
        ]);

        // Student visits the quiz room
        $response = $this->actingAs($this->student)
            ->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));

        $response->assertOk();
        $response->assertSee('Waktu Kuis Telah Berakhir');
        $response->assertSee('Submission ditolak karena melewati deadline attempt.');
        $response->assertSee('Kembali ke Course');
        // Must NOT render active countdown or exam form
        $response->assertDontSee('id="quiz-countdown"', false);
        $response->assertDontSee('id="exam-sheet-form"', false);

        // Reloading the page also stays on the timeout screen without loops
        $reloadResponse = $this->actingAs($this->student)
            ->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $reloadResponse->assertOk();
        $reloadResponse->assertSee('Waktu Kuis Telah Berakhir');
        $reloadResponse->assertDontSee('id="quiz-countdown"', false);

        // Course item page should also show 'Kuis Ditutup'
        $itemResponse = $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $itemResponse->assertOk();
        $itemResponse->assertSee('Kuis Ditutup');
        $itemResponse->assertDontSee('Mulai Kerjakan Kuis');
    }

    public function test_in_progress_attempt_past_grace_period_is_marked_rejected_on_access(): void
    {
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-INPROGRESS-EXPIRED',
            'name' => 'Kuis In Progress Expired',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(2),
            'learning_payload' => [
                'duration_enabled' => true,
                'duration_minutes' => 2,
                'questions' => [[
                    'id' => 'q_test_1',
                    'type' => 'pilihan',
                    'prompt' => 'Apa itu testing?',
                    'points' => 100,
                    'options' => "A\nB",
                    'option_items' => [
                        ['id' => 'opt_1', 'text' => 'A'],
                        ['id' => 'opt_2', 'text' => 'B'],
                    ],
                    'answer_key' => [
                        'option_ids' => ['opt_1'],
                        'matches' => [],
                    ],
                ]],
            ],
        ]);

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'attempt' => 1,
            'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
            'started_at' => now()->subMinutes(5),
            'deadline_at' => now()->subMinutes(3), // 3 minutes ago (> 30s grace)
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));

        $response->assertOk();
        $response->assertSee('Waktu Kuis Telah Berakhir');
        $response->assertDontSee('id="quiz-countdown"', false);

        $attempt->refresh();
        $this->assertSame(AssessmentAttempt::STATUS_REJECTED, $attempt->status);
    }

    public function test_auto_submission_within_grace_period_is_accepted_and_scored(): void
    {
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-GRACE',
            'name' => 'Kuis Grace Period Test',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(2),
            'learning_payload' => [
                'duration_enabled' => true,
                'duration_minutes' => 1,
                'questions' => [[
                    'id' => 'q_test_1',
                    'type' => 'pilihan',
                    'prompt' => 'Apa warna langit?',
                    'points' => 100,
                    'options' => "Biru\nMerah",
                    'option_items' => [
                        ['id' => 'opt_blue', 'text' => 'Biru'],
                        ['id' => 'opt_red', 'text' => 'Merah'],
                    ],
                    'answer_key' => [
                        'option_ids' => ['opt_blue'],
                        'matches' => [],
                    ],
                ]],
            ],
        ]);

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'attempt' => 1,
            'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
            'started_at' => now()->subSeconds(65),
            'deadline_at' => now()->subSeconds(5), // 5 seconds past deadline (< 30s grace)
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
                'from_quiz_room' => 1,
                'question_answers' => [
                    'q_test_1' => [
                        'option_ids' => ['opt_blue'],
                    ],
                ],
            ]);

        $response->assertRedirect(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $response->assertSessionHasNoErrors();

        $attempt->refresh();
        $this->assertSame(AssessmentAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);

        $this->assertDatabaseHas('submissions', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
        ]);

        // Visiting quiz room now shows completed results
        $roomResponse = $this->actingAs($this->student)
            ->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $roomResponse->assertOk();
        $roomResponse->assertSee('Hasil Pemeriksaan Lembar Jawaban');
        $roomResponse->assertDontSee('id="quiz-countdown"', false);
    }
}
