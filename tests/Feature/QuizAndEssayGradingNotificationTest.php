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
use App\Models\Submission;
use App\Models\User;
use App\Services\DatabaseNotificationService;
use App\Support\QuizQuestion;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAndEssayGradingNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $student;
    private ClassSection $section;
    private Cpmk $cpmk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mhsRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $this->dosen = User::create([
            'name' => 'Dosen Pengampu M.Kom',
            'email' => 'dosen@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '198801012020121001',
        ]);

        $this->student = User::create([
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '20260099',
        ]);

        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknik Informatika']);
        $course = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'TI-301',
            'name' => 'Rekayasa Perangkat Lunak',
            'sks' => 3,
        ]);
        $semester = Semester::create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);
        $this->section = ClassSection::create([
            'mata_kuliah_id' => $course->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'RPL2026',
        ]);
        $this->section->students()->attach($this->student->id);

        $this->cpmk = Cpmk::create([
            'mata_kuliah_id' => $course->id,
            'code' => 'CPMK-01',
            'description' => 'Menerapkan konsep arsitektur dan pengujian perangkat lunak.',
            'threshold' => 65,
        ]);
    }

    public function test_quiz_with_essay_updates_score_and_sends_notification_when_graded(): void
    {
        $choice = QuizQuestion::canonicalizeQuestion([
            'id' => 'q-choice-1',
            'type' => 'pilihan',
            'prompt' => 'Apa itu unit test?',
            'options' => "Pengujian unit mandiri\nPengujian sistem global",
            'correct_answer' => 'A',
            'points' => 50,
            'cpmk' => 'CPMK-01',
        ]);

        $essay = QuizQuestion::canonicalizeQuestion([
            'id' => 'q-essay-1',
            'type' => 'uraian',
            'prompt' => 'Jelaskan perbedaan white box dan black box testing!',
            'points' => 50,
            'cpmk' => 'CPMK-01',
        ]);

        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-01',
            'name' => 'Kuis Pengujian Software',
            'type' => 'kuis',
            'final_weight' => 15,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => [
                'type' => 'kuis',
                'title' => 'Kuis Pengujian Software',
                'questions' => [$choice, $essay],
                'points' => 100,
            ],
        ]);
        $quiz->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        // 1. Student submits quiz
        $submitRes = $this->actingAs($this->student)->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                'q-choice-1' => [
                    'question_id' => 'q-choice-1',
                    'option_ids' => $choice['answer_key']['option_ids'],
                ],
                'q-essay-1' => [
                    'question_id' => 'q-essay-1',
                    'text' => 'White box menguji struktur internal kode, sedangkan black box menguji fungsionalitas eksternal tanpa melihat kode.',
                ],
            ],
        ]);
        $submitRes->assertRedirect(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));

        // Check submission state: essay is pending, quiz score is not yet final/published
        $submission = Submission::where('assessment_id', $quiz->id)->firstOrFail();
        $choiceAnswer = $submission->answers()->where('question_id', 'q-choice-1')->firstOrFail();
        $essayAnswer = $submission->answers()->where('question_id', 'q-essay-1')->firstOrFail();

        $this->assertSame('50.00', $choiceAnswer->earned_score);
        $this->assertNull($essayAnswer->earned_score);
        $this->assertSame('manual_pending', $essayAnswer->grading_status);

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'score' => null,
            'status' => StudentAssessmentScore::STATUS_PENDING,
        ]);

        // Student item view shows pending essay note
        $itemRes = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $itemRes->assertOk();
        $itemRes->assertSee('Menunggu penilaian esai');

        // Notification before lecturer grades is "Jawaban Terkirim"
        $notifService = app(DatabaseNotificationService::class);
        $notifsBefore = $notifService->forUser($this->student, 'mahasiswa');
        $quizNotifBefore = collect($notifsBefore)->firstWhere('id', "submit_{$quiz->id}");
        $this->assertNotNull($quizNotifBefore);
        $this->assertStringContainsString('Jawaban Terkirim', $quizNotifBefore['title']);

        // 2. Lecturer grades the essay question
        $gradeRes = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.essay_scores', [$this->section->id, $quiz->id, $this->student->id]),
            [
                'scores' => [
                    $essayAnswer->id => 45,
                ],
            ]
        );
        $gradeRes->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $quiz->id]));
        $gradeRes->assertSessionHasNoErrors();

        // Check that essay earned_score is updated
        $this->assertSame('45.00', $essayAnswer->fresh()->earned_score);
        $this->assertSame('manual_graded', $essayAnswer->fresh()->grading_status);

        // Check that quiz total score is recalculated: 50 + 45 = 95
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 95,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);

        // 3. Student receives "Nilai Quiz Diperbarui" notification
        $notifsAfter = $notifService->forUser($this->student, 'mahasiswa');
        $gradeNotif = collect($notifsAfter)->firstWhere('id', "grade_{$quiz->id}");
        $this->assertNotNull($gradeNotif);
        $this->assertStringContainsString('Nilai Quiz Diperbarui', $gradeNotif['title']);
        $this->assertStringContainsString('95/100', $gradeNotif['message']);
        $this->assertFalse($gradeNotif['is_read']);

        // 4. Student views course item: score is now visible
        $itemResAfter = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $itemResAfter->assertOk();
        $itemResAfter->assertSee('95/100 Poin');
        $itemResAfter->assertDontSee('Menunggu penilaian esai');

        // 5. Student views quiz room: score is 95 and essay shows graded
        $quizRoomRes = $this->actingAs($this->student)->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $quizRoomRes->assertOk();
        $quizRoomRes->assertSee('95');
        $quizRoomRes->assertSee('Jawaban esai telah dinilai oleh dosen pengampu');
    }

    public function test_assignment_task_grading_sends_notification(): void
    {
        $task = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas Desain UML',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(2),
            'learning_payload' => [
                'type' => 'tugas',
                'title' => 'Tugas Desain UML',
                'body' => 'Buat diagram class dan sequence.',
                'points' => 100,
            ],
        ]);
        $task->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        // Student submits task
        $this->actingAs($this->student)->post(route('mahasiswa.course.submit', [$this->section->id, $task->id]), [
            'answer' => 'Berikut adalah tautan dokumen desain UML.',
        ])->assertRedirect();

        // Lecturer grades task via storeStudentTaskScore
        $gradeRes = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.score', [$this->section->id, $task->id, $this->student->id]),
            [
                'cpmk_scores' => [
                    $this->cpmk->id => 88,
                ],
            ]
        );
        $gradeRes->assertSessionHasNoErrors();

        // Check that StudentAssessmentScore is saved with score 88
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $task->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 88,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);

        // Student receives "Nilai Tugas Diperbarui" notification
        $notifService = app(DatabaseNotificationService::class);
        $notifs = $notifService->forUser($this->student, 'mahasiswa');
        $taskNotif = collect($notifs)->firstWhere('id', "grade_{$task->id}");

        $this->assertNotNull($taskNotif);
        $this->assertStringContainsString('Nilai Tugas Diperbarui', $taskNotif['title']);
        $this->assertStringContainsString('88/100', $taskNotif['message']);
        $this->assertFalse($taskNotif['is_read']);
    }

    public function test_read_notification_becomes_unread_when_grade_is_updated(): void
    {
        $task = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-02',
            'name' => 'Tugas Pengujian API',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(2),
            'learning_payload' => [
                'type' => 'tugas',
                'title' => 'Tugas Pengujian API',
                'points' => 100,
            ],
        ]);
        $task->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        // Lecturer grades initially
        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.score', [$this->section->id, $task->id, $this->student->id]),
            ['cpmk_scores' => [$this->cpmk->id => 75]]
        );

        $notifService = app(DatabaseNotificationService::class);

        // Student marks notification as read
        $notifService->markRead($this->student, ["grade_{$task->id}"]);
        $notifs = $notifService->forUser($this->student, 'mahasiswa');
        $notif = collect($notifs)->firstWhere('id', "grade_{$task->id}");
        $this->assertTrue($notif['is_read']);

        // Time passes, lecturer updates the score to 92
        $this->travel(10)->minutes();
        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.student.score', [$this->section->id, $task->id, $this->student->id]),
            ['cpmk_scores' => [$this->cpmk->id => 92]]
        );

        // Score is now 92
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $task->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 92,
        ]);

        // Student's notification is now unread again and shows updated score 92
        $notifsAfterUpdate = $notifService->forUser($this->student, 'mahasiswa');
        $updatedNotif = collect($notifsAfterUpdate)->firstWhere('id', "grade_{$task->id}");
        $this->assertNotNull($updatedNotif);
        $this->assertFalse($updatedNotif['is_read']);
        $this->assertStringContainsString('92/100', $updatedNotif['message']);
    }
}
