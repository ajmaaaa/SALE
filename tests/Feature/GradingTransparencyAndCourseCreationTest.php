<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Semester;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\User;
use App\Support\AcademicPreview;
use App\Support\QuizQuestion;
use Database\Seeders\AcademicDemoSeeder;
use Database\Seeders\DemoLearningContentSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GradingTransparencyAndCourseCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $student;

    private ClassSection $section;

    private Assessment $gradedTask;

    private Assessment $ungradedTask;

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

        $this->gradedTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1: Binary Search Tree',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(5),
        ]);

        $this->ungradedTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-02',
            'name' => 'Tugas 2: Graph Traversal',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDays(10),
        ]);

        StudentAssessmentScore::create([
            'assessment_id' => $this->gradedTask->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 88.50,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
            'feedback' => 'Implementasi traversal sudah sangat baik dan efisien.',
            'graded_by' => $this->dosen->id,
            'graded_at' => now(),
            'published_at' => now(),
        ]);
    }

    public function test_student_assignment_page_displays_scores_and_proper_status(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index'));

        $response->assertOk();
        $response->assertSee('Tugas 1: Binary Search Tree');
        $response->assertSee('89/100');
        $response->assertDontSee('Sudah dinilai (89/100)');
        $response->assertDontSee('Implementasi traversal sudah sangat baik');
        $response->assertSee('Tugas 2: Graph Traversal');
        $response->assertSee('Belum dikumpulkan');
        $response->assertSee('Tenggat');
    }

    public function test_official_grading_route_rejects_unassigned_lecturer(): void
    {
        $otherDosen = User::create([
            'name' => 'Dosen Kelas Lain',
            'email' => 'dosen.lain@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'nim_nidn' => '198501012010121099',
        ]);
        $unenrolledStudent = User::create([
            'name' => 'Mahasiswa Tidak Terdaftar',
            'email' => 'tidak.terdaftar@student.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'nim_nidn' => '231011409999',
        ]);
        $url = route('dosen.penilaian.asesmen.nilai', [$this->section, $this->ungradedTask]);
        $this->actingAs($otherDosen)->get($url)->assertForbidden();

        $this->assertDatabaseMissing('student_assessment_scores', [
            'assessment_id' => $this->ungradedTask->id,
            'mahasiswa_id' => $unenrolledStudent->id,
        ]);
    }

    public function test_student_assignment_tab_nilai_only_shows_graded_items(): void
    {
        StudentAssessmentScore::create([
            'assessment_id' => $this->ungradedTask->id,
            'mahasiswa_id' => $this->student->id,
            'score' => null,
            'status' => StudentAssessmentScore::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index', ['tab' => 'nilai']));

        $response->assertOk();
        $response->assertSee('Tugas 1: Binary Search Tree');
        $response->assertSee('89/100');
        $response->assertDontSee('Sudah dinilai (89/100)');
        $response->assertDontSee('Tugas 2: Graph Traversal');
    }

    public function test_student_item_detail_displays_grade_in_status_without_lecturer_feedback(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [
            $this->section->id,
            $this->gradedTask->id,
        ]));

        $response->assertOk();
        $response->assertSee('89/100');
        $response->assertSee('Sudah dinilai');
        $response->assertDontSee('Catatan Dosen');
        $response->assertDontSee('Implementasi traversal sudah sangat baik dan efisien.');
    }

    public function test_graded_quiz_shows_only_automatic_score_status_without_lecturer_note(): void
    {
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-01',
            'name' => 'Kuis Otomatis Struktur Data',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
        ]);
        StudentAssessmentScore::create([
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 92,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
            'feedback' => 'Catatan ini tidak perlu ditampilkan untuk kuis otomatis.',
            'graded_by' => $this->dosen->id,
            'graded_at' => now(),
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [
            $this->section->id,
            $quiz->id,
        ]));

        $response->assertOk();
        $response->assertSee('92/100');
        $response->assertDontSee('Sudah dinilai');
        $response->assertDontSee('Catatan Dosen');
        $response->assertDontSee('Catatan ini tidak perlu ditampilkan');
    }

    public function test_lecturer_can_create_course_with_video_and_view_it_without_error(): void
    {
        Storage::fake('local');
        $videoFile = UploadedFile::fake()->create('intro.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->dosen)->post(route('dosen.course.store'), [
            'code' => 'IF301',
            'title' => 'Pemrograman Web Lanjut',
            'lecturer' => $this->dosen->name,
            'description' => 'Mata kuliah pengembangan web modern berbasis arsitektur komponen.',
            'video_file' => $videoFile,
        ]);

        $newSection = ClassSection::whereHas('mataKuliah', fn ($q) => $q->where('code', 'IF301'))->first();
        $this->assertNotNull($newSection);
        $this->assertSame($this->dosen->id, $newSection->dosen_id);

        $response->assertRedirect(route('dosen.course.show', $newSection->id));

        $viewResponse = $this->actingAs($this->dosen)->get(route('dosen.course.show', $newSection->id));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Pemrograman Web Lanjut');
    }

    public function test_database_demo_courses_keep_pushed_youtube_and_mp4_examples(): void
    {
        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MATERI-YOUTUBE',
            'name' => 'Video Pengantar',
            'type' => 'materi',
            'final_weight' => 0,
            'learning_payload' => [
                'pin_video' => true,
                'video' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'video_type' => 'url',
                'video_title' => 'Video Pengantar: Struktur Data dan Algoritma (YouTube)',
            ],
            'status' => 'published',
        ]);

        $youtubeCourse = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section));
        $youtubeCourse->assertOk()
            ->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false);

        $secondCourse = MataKuliah::create([
            'prodi_id' => $this->section->mataKuliah->prodi_id,
            'code' => 'IF218',
            'name' => 'Interaksi Manusia dan Komputer',
            'sks' => 3,
        ]);
        $secondSection = ClassSection::create([
            'mata_kuliah_id' => $secondCourse->id,
            'semester_id' => $this->section->semester_id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TESTIF02',
        ]);
        $secondSection->students()->attach($this->student->id);

        Assessment::create([
            'class_section_id' => $secondSection->id,
            'code' => 'MATERI-MP4',
            'name' => 'Video Materi Perkuliahan (.mp4)',
            'type' => 'materi',
            'final_weight' => 0,
            'learning_payload' => [
                'pin_video' => true,
                'video' => '00000000-0000-4000-8000-000000000001',
                'video_type' => 'file',
                'video_title' => 'Video Materi Perkuliahan (.mp4)',
            ],
            'status' => 'published',
        ]);

        $mp4Course = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $secondSection));
        $mp4Course->assertOk()
            ->assertSee('<video', false)
            ->assertSee('video-pembelajaran-kuliah.mp4');

        // Course baru tanpa materi TIDAK boleh memiliki video
        $emptyCourse = MataKuliah::create([
            'prodi_id' => $this->section->mataKuliah->prodi_id,
            'code' => 'IF300',
            'name' => 'Mata Kuliah Baru Tanpa Materi',
            'sks' => 3,
        ]);
        $emptySection = ClassSection::create([
            'mata_kuliah_id' => $emptyCourse->id,
            'semester_id' => $this->section->semester_id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'TESTEMPTY',
        ]);
        $emptySection->students()->attach($this->student->id);
        $emptyView = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $emptySection));
        $emptyView->assertOk()
            ->assertDontSee('id="course-video-card"', false);
    }

    public function test_forum_discussion_renders_without_missing_lecturer_key_error(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.discussion.index'));

        $response->assertOk();
        $response->assertSee('Forum Diskusi Perkuliahan');
        $response->assertSee('Struktur Data dan Algoritma');
        $response->assertSee('Pengampu: Dr. Budi Santoso, M.Kom.');
    }

    public function test_non_database_preview_item_is_not_exposed(): void
    {
        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, 999999]))
            ->assertNotFound();
    }

    public function test_database_material_and_late_task_render_generated_pdf_and_image_files(): void
    {
        $this->seed(DemoLearningContentSeeder::class);

        $material = Assessment::where('class_section_id', $this->section->id)
            ->where('code', 'MATERI-DEMO')
            ->firstOrFail();
        $task = Assessment::where('class_section_id', $this->section->id)
            ->where('code', 'TGS-LAMPIRAN')
            ->firstOrFail();

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, $material->id]))
            ->assertOk()
            ->assertSee('Modul-01-Pengantar-Struktur-Data.pdf');

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, $task->id]))
            ->assertOk()
            ->assertSee('Panduan-Evaluasi-Usability-Partisipan.pdf')
            ->assertSee('Lembar observasi dan wireframe pendukung tugas');

        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignment.index'))
            ->assertOk()
            ->assertSee('Tugas Evaluasi Struktur Data dengan Lampiran')
            ->assertSee('Terlambat');
    }

    public function test_demo_database_adds_five_courses_in_admin_prodi_only(): void
    {
        $this->seed(AcademicDemoSeeder::class);

        $newCodes = ['IF218', 'IF221', 'IF240', 'IF250', 'IF260'];
        $this->assertSame(5, MataKuliah::whereIn('code', $newCodes)->count());

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.index'))
            ->assertOk()
            ->assertDontSee('IF260-A')
            ->assertDontSee('Pemrograman Web');

        $this->actingAs($this->dosen)
            ->get(route('dosen.course.index'))
            ->assertOk()
            ->assertDontSee('IF260-A')
            ->assertDontSee('Pemrograman Web');

        $adminProdi = User::where('email', 'adminprodi@example.test')->first();
        if ($adminProdi) {
            $this->actingAs($adminProdi)
                ->get(route('admin-prodi.akademik.matakuliah'))
                ->assertOk()
                ->assertSee('IF260')
                ->assertSee('Pemrograman Web');
        }
    }

    public function test_notifications_page_renders_all_tasks_and_allows_marking_read(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));

        $response->assertOk();
        $response->assertSee('Notifikasi Pembelajaran');
        $response->assertSee('Semua');
        $response->assertSee('Tugas & Kuis');
        $response->assertSee('bg-brand-dark font-semibold text-white', false);
        $response->assertSee('notifikasi belum dibaca', false);

        $readResponse = $this->actingAs($this->student)->post(route('mahasiswa.notifications.read', 'pending_1'));
        $readResponse->assertRedirect();
        $this->assertContains('pending_1', session('learning.read_notifications', []));

        $allReadResponse = $this->actingAs($this->student)->post(route('mahasiswa.notifications.read', 'all'));
        $allReadResponse->assertRedirect();
        $this->assertContains('all', session('learning.read_notifications', []));
    }

    public function test_submitted_assignment_cannot_unsubmit_when_deadline_passed_and_late_disallowed(): void
    {
        $this->ungradedTask->update([
            'due_at' => now()->subDay(),
            'allow_late' => false,
        ]);
        $this->actingAs($this->student);
        Submission::create([
            'assessment_id' => $this->ungradedTask->id,
            'user_id' => $this->student->id,
            'mahasiswa_id' => $this->student->id,
            'status' => 'submitted',
            'submitted_at' => now()->subHours(2),
        ]);

        $page = $this->get(route('mahasiswa.course.item', [$this->section->id, $this->ungradedTask->id]));
        $page->assertOk()
            ->assertSee('Sudah Diserahkan')
            ->assertDontSee('onclick="cancelSubmissionConfirm()"', false)
            ->assertDontSee('id="cancel-submission-form"', false);

        $cancel = $this->post(route('mahasiswa.course.submission.cancel', [$this->section->id, $this->ungradedTask->id]));
        $cancel->assertSessionHas('notice');
    }

    public function test_submitted_assignment_can_unsubmit_when_allowed(): void
    {
        $this->actingAs($this->student);
        $this->post(route('mahasiswa.course.submit', [$this->section->id, $this->ungradedTask->id]), [
            'answer' => 'Jawaban saya yang sudah diserahkan.',
        ])->assertRedirect();

        $page = $this->get(route('mahasiswa.course.item', [$this->section->id, $this->ungradedTask->id]));
        $page->assertOk()
            ->assertSee('Batalkan Serahkan');

        $cancel = $this->post(route('mahasiswa.course.submission.cancel', [$this->section->id, $this->ungradedTask->id]));
        $cancel->assertRedirect();
        $this->assertDatabaseMissing('submissions', [
            'assessment_id' => $this->ungradedTask->id,
            'mahasiswa_id' => $this->student->id,
        ]);
    }

    public function test_submission_attachment_is_private_from_other_students_in_same_class(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('submissions/private-answer.pdf', '%PDF private answer');

        $submission = Submission::create([
            'assessment_id' => $this->ungradedTask->id,
            'user_id' => $this->student->id,
            'mahasiswa_id' => $this->student->id,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $attachment = Attachment::create([
            'uuid' => '10000000-0000-4000-8000-000000000001',
            'user_id' => $this->student->id,
            'class_section_id' => $this->section->id,
            'assessment_id' => $this->ungradedTask->id,
            'submission_id' => $submission->id,
            'path' => 'submissions/private-answer.pdf',
            'name' => 'private-answer.pdf',
            'mime' => 'application/pdf',
        ]);
        $otherStudent = User::factory()->create([
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
        ]);
        $otherDosen = User::factory()->create([
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
        ]);
        $this->section->students()->attach($otherStudent->id);

        $this->actingAs($otherStudent)->get(route('preview.file', $attachment->uuid))->assertForbidden();
        $this->actingAs($otherDosen)->get(route('preview.file', $attachment->uuid))->assertForbidden();
        $this->actingAs($this->student)->get(route('preview.file', $attachment->uuid))->assertOk();
        $this->actingAs($this->dosen)->get(route('preview.file', $attachment->uuid))->assertOk();
    }

    public function test_graded_assignment_cannot_be_submitted_again(): void
    {
        $submittedAt = now()->subDay();
        $submission = Submission::create([
            'assessment_id' => $this->gradedTask->id,
            'user_id' => $this->student->id,
            'mahasiswa_id' => $this->student->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'pending',
            'submitted_at' => $submittedAt,
            'answer' => 'Jawaban asli yang sudah dinilai.',
        ]);

        $response = $this->actingAs($this->student)->post(route('mahasiswa.course.submit', [
            $this->section->id,
            $this->gradedTask->id,
        ]), [
            'answer' => 'Jawaban pengganti setelah dinilai.',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('submission');

        $this->assertSame('Jawaban asli yang sudah dinilai.', $submission->fresh()->answer);
        $this->assertSame(1, $submission->fresh()->attempt);
        $this->assertSame(1, $submission->fresh()->version);

        $score = StudentAssessmentScore::where('assessment_id', $this->gradedTask->id)
            ->where('mahasiswa_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame('88.50', $score->score);
        $this->assertNotNull($score->graded_at);
    }

    public function test_draft_and_closed_assessments_cannot_be_opened_or_submitted(): void
    {
        foreach (['draft', 'closed'] as $status) {
            $assessment = Assessment::create([
                'class_section_id' => $this->section->id,
                'code' => 'LOCKED-'.strtoupper($status),
                'name' => 'Asesmen '.ucfirst($status),
                'type' => 'tugas',
                'final_weight' => 10,
                'status' => $status,
                'due_at' => now()->addDay(),
            ]);

            $this->actingAs($this->student)
                ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]))
                ->assertForbidden();

            $this->get(route('mahasiswa.quiz.room', [$this->section->id, $assessment->id]))
                ->assertForbidden();

            $this->post(route('mahasiswa.course.submit', [$this->section->id, $assessment->id]), [
                'answer' => 'Jawaban yang harus ditolak.',
            ])->assertForbidden();

            $this->assertDatabaseMissing('submissions', [
                'assessment_id' => $assessment->id,
                'mahasiswa_id' => $this->student->id,
            ]);
            $this->assertDatabaseMissing('student_assessment_scores', [
                'assessment_id' => $assessment->id,
                'mahasiswa_id' => $this->student->id,
            ]);
        }
    }

    public function test_timed_quiz_submission_is_rejected_and_recorded_after_server_deadline(): void
    {
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TIMED-QUIZ',
            'name' => 'Kuis dengan Deadline Server',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => [
                'duration_enabled' => true,
                'duration_minutes' => 1,
                'questions' => [[
                    'type' => 'mencocokkan',
                    'prompt' => 'Pasangkan istilah berikut.',
                    'options' => "TERM_A = ANSWER_A\nTERM_B = ANSWER_B",
                    'points' => 100,
                    'cpmk' => 'CPMK-01',
                ]],
            ],
        ]);

        $room = $this->actingAs($this->student)
            ->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]))
            ->assertOk();
        $room->assertSee('data-deadline=', false);

        $attempt = AssessmentAttempt::where('assessment_id', $quiz->id)
            ->where('mahasiswa_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame(1, $attempt->attempt);
        $this->assertSame(60.0, $attempt->started_at->diffInSeconds($attempt->deadline_at));

        $this->travel(2)->minutes();

        $this->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
            'from_quiz_room' => 1,
        ])->assertRedirect()->assertSessionHasErrors('submission');

        $attempt->refresh();
        $this->assertSame(AssessmentAttempt::STATUS_REJECTED, $attempt->status);
        $this->assertNotNull($attempt->rejected_at);
        $this->assertStringContainsString('melewati deadline attempt', $attempt->rejection_reason);
        $this->assertDatabaseMissing('submissions', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
        ]);
        $this->assertDatabaseMissing('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
        ]);
    }

    public function test_timed_quiz_requires_a_server_attempt_and_marks_timely_submission(): void
    {
        $payload = [
            'duration_enabled' => true,
            'duration_minutes' => 5,
            'questions' => [[
                'type' => 'mencocokkan',
                'prompt' => 'Pasangkan istilah berikut.',
                'options' => "TERM_A = ANSWER_A\nTERM_B = ANSWER_B",
                'points' => 100,
                'cpmk' => 'CPMK-01',
            ]],
        ];
        $directQuiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'DIRECT-TIMED-QUIZ',
            'name' => 'Kuis Tanpa Attempt',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => $payload,
        ]);

        $this->actingAs($this->student)->post(route('mahasiswa.course.submit', [
            $this->section->id,
            $directQuiz->id,
        ]), [
            'from_quiz_room' => 1,
        ])->assertRedirect()->assertSessionHasErrors('submission');
        $this->assertDatabaseMissing('submissions', ['assessment_id' => $directQuiz->id]);

        $timelyQuiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TIMELY-TIMED-QUIZ',
            'name' => 'Kuis Tepat Waktu',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => $payload,
        ]);

        $this->get(route('mahasiswa.quiz.room', [$this->section->id, $timelyQuiz->id]))->assertOk();
        $this->post(route('mahasiswa.course.submit', [$this->section->id, $timelyQuiz->id]), [
            'from_quiz_room' => 1,
        ])->assertRedirect(route('mahasiswa.quiz.room', [$this->section->id, $timelyQuiz->id]))
            ->assertSessionHasNoErrors();

        $attempt = AssessmentAttempt::where('assessment_id', $timelyQuiz->id)
            ->where('mahasiswa_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame(AssessmentAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertNull($attempt->rejection_reason);
    }

    public function test_quiz_grading_uses_canonical_question_and_option_ids(): void
    {
        $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'kuis',
            'title' => 'Kuis Kunci Kanonik',
            'module' => 'Kunci Kanonik',
            'body' => 'Pilih jawaban yang benar.',
            'question_type' => 'pilihan',
            'cpmk' => 'CPMK-01',
            'formats' => ['text'],
            'options' => "Opsi pertama\nOpsi kedua",
            'questions' => [[
                'type' => 'pilihan',
                'prompt' => 'Pilih opsi kedua.',
                'points' => 100,
                'cpmk' => 'CPMK-01',
                'options' => "Opsi pertama\nOpsi kedua",
                'correct_answer' => 'B',
            ]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $storedQuestion = Assessment::where('name', 'Kuis Kunci Kanonik')->firstOrFail()->learning_payload['questions'][0];
        $this->assertArrayHasKey('answer_key', $storedQuestion);
        $this->assertArrayHasKey('option_items', $storedQuestion);
        $this->assertArrayNotHasKey('correct_answer', $storedQuestion);
        $this->assertSame([$storedQuestion['option_items'][1]['id']], $storedQuestion['answer_key']['option_ids']);

        $choice = QuizQuestion::canonicalizeQuestion([
            'type' => 'pilihan',
            'prompt' => 'Pilih jawaban kedua.',
            'options' => "Jawaban pertama\nJawaban kedua",
            'correct_answer' => 'B',
            'points' => 100,
        ]);
        $this->assertSame(100.0, QuizQuestion::evaluate($choice, [
            'option_ids' => [$choice['option_items'][1]['id']],
        ]));

        $boolean = QuizQuestion::canonicalizeQuestion([
            'type' => 'benar_salah',
            'prompt' => 'Pernyataan salah.',
            'boolean_answer' => 'Salah',
            'points' => 100,
        ]);
        $this->assertSame(100.0, QuizQuestion::evaluate($boolean, [
            'option_ids' => [$boolean['option_items'][1]['id']],
        ]));

        $this->assertSame(0.0, AcademicPreview::evaluateAutoQuestion([
            'type' => 'mencocokkan',
            'points' => 100,
            'options' => 'Istilah = Jawaban benar',
        ], ['matching' => ['Jawaban salah']]));
        $this->assertSame(100.0, AcademicPreview::evaluateAutoQuestion([
            'type' => 'pilihan',
            'points' => 100,
            'options' => "Jawaban pertama\nJawaban kedua",
            'correct_answer' => 'B',
        ], ['choices' => ['B']]));

        $questions = array_map(fn (int $index) => [
            'type' => 'pilihan',
            'prompt' => 'Pertanyaan '.$index,
            'options' => implode("\n", array_map(fn (int $option) => 'Jawaban '.$option, range(0, 7))),
            'correct_answer' => 'Jawaban '.$index,
            'points' => 100,
            'cpmk' => 'CPMK-01',
        ], range(0, 7));
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'CANONICAL-QUIZ',
            'name' => 'Kuis ID Stabil',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => [
                'randomize_questions' => true,
                'questions' => $questions,
            ],
        ]);

        $shownQuestions = $this->actingAs($this->student)
            ->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]))
            ->assertOk()
            ->viewData('item')['questions'];
        $this->assertNotSame(array_column($questions, 'prompt'), array_column($shownQuestions, 'prompt'));

        $answers = [];
        foreach ($shownQuestions as $question) {
            $answers[(string) $question['id']] = [
                'question_id' => (string) $question['id'],
                'option_ids' => $question['answer_key']['option_ids'],
            ];
        }

        $this->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
            'from_quiz_room' => 1,
            'question_answers' => $answers,
        ])->assertRedirect(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 100,
        ]);
        $submission = Submission::where('assessment_id', $quiz->id)->firstOrFail();
        $this->assertSame(array_keys($answers), array_keys($submission->question_answers));
        $this->assertDatabaseCount('submission_answers', 8);
        $this->assertDatabaseMissing('submission_answers', ['question_id' => null]);
    }

    public function test_quiz_submission_rolls_back_when_cpmk_breakdown_fails(): void
    {
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $this->section->mata_kuliah_id,
            'code' => 'CPMK-ROLLBACK',
            'description' => 'CPMK untuk menguji transaksi penilaian kuis.',
            'threshold' => 65,
        ]);
        $question = QuizQuestion::canonicalizeQuestion([
            'type' => 'pilihan',
            'prompt' => 'Pilih jawaban yang benar.',
            'options' => "Salah\nBenar",
            'correct_answer' => 'B',
            'points' => 100,
            'cpmk' => $cpmk->code,
        ]);
        $quiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'QUIZ-ROLLBACK',
            'name' => 'Kuis Rollback CPMK',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => [
                'questions' => [$question],
            ],
        ]);
        $quiz->cpmks()->attach($cpmk->id, ['weight' => 100]);

        StudentAssessmentCpmkScore::creating(function () {
            throw new \RuntimeException('forced CPMK breakdown failure');
        });

        try {
            $response = $this->actingAs($this->student)->post(route('mahasiswa.course.submit', [
                $this->section->id,
                $quiz->id,
            ]), [
                'from_quiz_room' => 1,
                'question_answers' => [
                    $question['id'] => [
                        'question_id' => $question['id'],
                        'option_ids' => $question['answer_key']['option_ids'],
                    ],
                ],
            ]);
        } finally {
            StudentAssessmentCpmkScore::flushEventListeners();
        }

        $response->assertStatus(500);

        $this->assertDatabaseMissing('submissions', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
        ]);
        $this->assertDatabaseMissing('submission_answers', [
            'question_id' => $question['id'],
        ]);
        $this->assertDatabaseMissing('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
        ]);
        $this->assertDatabaseMissing('student_assessment_cpmk_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->student->id,
        ]);
        $this->assertNull(session("learning.submissions.{$quiz->id}"));
        $this->assertNull(session("learning.grades.{$quiz->id}"));
    }

    public function test_partial_cpmk_score_stays_hidden_until_all_components_are_published(): void
    {
        $first = Cpmk::create([
            'mata_kuliah_id' => $this->section->mata_kuliah_id,
            'code' => 'CPMK-PARTIAL-1',
            'description' => 'Komponen pertama.',
        ]);
        $second = Cpmk::create([
            'mata_kuliah_id' => $this->section->mata_kuliah_id,
            'code' => 'CPMK-PARTIAL-2',
            'description' => 'Komponen kedua.',
        ]);
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MULTI-CPMK',
            'name' => 'Asesmen Multi CPMK',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
            'due_at' => now()->addDay(),
        ]);
        $assessment->cpmks()->attach([
            $first->id => ['weight' => 50],
            $second->id => ['weight' => 50],
        ]);

        $this->actingAs($this->dosen)->post(route('dosen.penilaian.asesmen.nilai.store', [
            $this->section->id,
            $assessment->id,
        ]), [
            'intent' => 'publish',
            'cpmk_scores' => [
                $this->student->id => [$first->id => 40, $second->id => null],
            ],
        ])->assertSessionHasNoErrors();

        $score = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->where('mahasiswa_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame(StudentAssessmentScore::STATUS_PARTIAL, $score->status);
        $this->assertNull($score->score);
        $this->assertNull($score->graded_at);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignment.index', ['tab' => 'nilai']))
            ->assertDontSee('Asesmen Multi CPMK');

        $this->actingAs($this->dosen)->post(route('dosen.penilaian.asesmen.nilai.store', [
            $this->section->id,
            $assessment->id,
        ]), [
            'intent' => 'publish',
            'cpmk_scores' => [
                $this->student->id => [$first->id => 40, $second->id => 45],
            ],
        ])->assertSessionHasNoErrors();

        $score->refresh();
        $this->assertSame(StudentAssessmentScore::STATUS_PUBLISHED, $score->status);
        $this->assertSame('85.00', $score->score);
        $this->assertNotNull($score->graded_at);
        $this->assertNotNull($score->published_at);
    }

    public function test_direct_score_synchronizes_cpmk_breakdown_and_rubric_respects_criterion_maximum(): void
    {
        $first = Cpmk::create([
            'mata_kuliah_id' => $this->section->mata_kuliah_id,
            'code' => 'CPMK-SYNC-1',
            'description' => 'Komponen sinkron pertama.',
        ]);
        $second = Cpmk::create([
            'mata_kuliah_id' => $this->section->mata_kuliah_id,
            'code' => 'CPMK-SYNC-2',
            'description' => 'Komponen sinkron kedua.',
        ]);
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'DIRECT-SYNC',
            'name' => 'Asesmen Sinkronisasi',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
            'due_at' => now()->addDay(),
        ]);
        $assessment->cpmks()->attach([
            $first->id => ['weight' => 60],
            $second->id => ['weight' => 40],
        ]);

        $this->actingAs($this->dosen)->post(route('dosen.penilaian.asesmen.nilai.store', [
            $this->section->id,
            $assessment->id,
        ]), [
            'intent' => 'save',
            'scores' => [$this->student->id => 80],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 80,
            'status' => StudentAssessmentScore::STATUS_FINAL,
        ]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $assessment->id,
            'cpmk_id' => $first->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 48,
        ]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $assessment->id,
            'cpmk_id' => $second->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 32,
        ]);
        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignment.index', ['tab' => 'nilai']))
            ->assertDontSee('Asesmen Sinkronisasi');

        $this->actingAs($this->dosen)->post(route('dosen.penilaian.asesmen.nilai.store', [
            $this->section->id,
            $assessment->id,
        ]), [
            'intent' => 'publish',
            'scores' => [$this->student->id => 80],
        ])->assertSessionHasNoErrors();
        $this->assertSame(
            StudentAssessmentScore::STATUS_PUBLISHED,
            StudentAssessmentScore::where('assessment_id', $assessment->id)
                ->where('mahasiswa_id', $this->student->id)
                ->value('status')
        );

        $assessment->update(['uses_rubric' => true]);
        $rubric = Rubric::create(['assessment_id' => $assessment->id, 'name' => 'Rubrik batas skor']);
        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Ketepatan',
            'weight' => 100,
            'max_score' => 10,
            'order' => 1,
        ]);

        $this->post(route('dosen.penilaian.asesmen.nilai.store', [
            $this->section->id,
            $assessment->id,
        ]), [
            'rubric_scores' => [
                $this->student->id => [$criterion->id => 100],
            ],
        ])->assertSessionHasErrors("rubric_scores.{$this->student->id}.{$criterion->id}");

        $this->assertDatabaseMissing('student_rubric_scores', [
            'rubric_criterion_id' => $criterion->id,
            'mahasiswa_id' => $this->student->id,
        ]);
    }

    public function test_quiz_room_completed_view_shows_answer_correctness_and_keys(): void
    {
        $this->actingAs($this->student);
        // Submit quiz 5 (Kuis Evaluasi Model)
        $this->post(route('mahasiswa.course.submit', [3, 5]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                // Use question IDs as keys (as the real quiz room form does) to avoid
                // index-vs-ID collision in QuizQuestion::normalizeAnswers().
                1 => ['question_id' => '1', 'choices' => ['F1-Score dan ROC-AUC']], // Correct (+25)
                2 => ['question_id' => '2', 'boolean_choice' => 'Benar'],            // Wrong (Correct is Salah)
                3 => ['question_id' => '3', 'matching' => [0 => 'Salah Pasangan']], // Wrong
                4 => ['question_id' => '4', 'text' => 'Trade off precision dan recall...'], // Uraian
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [3, 5]));

        $quizRoom = $this->get(route('mahasiswa.quiz.room', [3, 5]));
        $quizRoom->assertOk()
            ->assertSee('Nilai Perolehan Kuis:')
            ->assertSee('Kuis Terkunci (Telah Selesai)')
            ->assertDontSee('Lembar Jawaban Terkumpul')
            ->assertDontSee('Kuis Telah Berhasil Dikumpulkan!')
            ->assertSee('Hasil Pemeriksaan Lembar Jawaban')
            ->assertSee('Benar (+25 Poin)')
            ->assertSee('Salah (0 Poin)')
            ->assertSee('✓ Kunci Jawaban Benar')
            ->assertSee('Jawaban Anda (Benar)')
            ->assertSee('Jawaban Anda (Salah)');
    }

    public function test_database_quiz_loads_all_six_question_variations_and_renders_clean_evaluation(): void
    {
        $this->seed(DemoLearningContentSeeder::class);

        $quiz = Assessment::where('class_section_id', $this->section->id)
            ->where('code', 'KUIS-01')
            ->firstOrFail();

        $this->actingAs($this->student);

        // 1. Ruang Ujian CBT memuat 6 variasi soal
        $response = $this->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $response->assertOk()
            ->assertSee('Daftar Soal')
            ->assertSee('Pilihan Ganda')
            ->assertSee('Pilihan Ganda Kompleks')
            ->assertSee('Benar / Salah')
            ->assertSee('Mencocokkan Pasangan')
            ->assertSee('Pemrograman')
            ->assertSee('Uraian / Esai');

        // 2. Submit jawaban kuis
        $this->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                0 => ['choices' => ['Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan']],
                1 => ['choices' => [
                    'Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)',
                    'Kompleksitas pencarian rata-rata pada balanced BST adalah O(log n)',
                ]],
                2 => ['boolean_choice' => 'Benar'],
                3 => ['matching' => [0 => 'Pre-order: Kunjungan Akar → Kiri → Kanan']],
                4 => ['text' => 'def insert(self, val): pass'],
                5 => ['text' => 'Pohon seimbang menjaga tinggi tetap O(log n).'],
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));

        // 3. Layar evaluasi purna pengumpulan menampilkan ke-6 soal tanpa pill AI pudar
        $review = $this->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $review->assertOk()
            ->assertSee('Nilai Perolehan Kuis:')
            ->assertSee('Kuis Terkunci (Telah Selesai)')
            ->assertDontSee('Lembar Jawaban Terkumpul')
            ->assertDontSee('Kuis Telah Berhasil Dikumpulkan!')
            ->assertSee('Hasil Pemeriksaan Lembar Jawaban')
            ->assertSee('6 Butir Soal')
            ->assertSee('Benar (+15 Poin)')
            ->assertSee('Praktikum Coding')
            ->assertSee('Uraian / Essay')
            ->assertDontSee('bg-emerald-100/70 border-emerald-200');
    }

    public function test_course_card_shows_red_deadline_for_lecturer_and_student(): void
    {
        $this->actingAs($this->student);
        $studentCourses = $this->get(route('mahasiswa.course.index'));
        $studentCourses->assertOk()
            ->assertSee('Tenggat:')
            ->assertSee('text-rose-600', false);

        $this->actingAs($this->dosen);
        $dosenCourses = $this->get(route('dosen.course.index'));
        $dosenCourses->assertOk()
            ->assertSee('Tenggat:')
            ->assertSee('text-rose-600', false)
            ->assertSee('QR');
    }

    public function test_lecturer_item_form_has_no_cpmk_summary_and_has_clean_total_points_badge(): void
    {
        $this->actingAs($this->dosen);
        $form = $this->get(route('dosen.item.create', $this->section->id));
        $form->assertOk()
            ->assertDontSee('data-toggle-cpmk-summary', false)
            ->assertDontSee('Ringkasan CPMK')
            ->assertDontSee('data-cpmk-summary-panel', false)
            ->assertSee('data-question-empty-state', false)
            ->assertSee('data-total-points-badge', false)
            ->assertDontSee('bg-slate-50 text-slate-700 border-line/80" data-total-points-badge', false);
    }

    public function test_question_builder_submits_only_one_options_field_per_question(): void
    {
        $this->actingAs($this->dosen);
        $html = $this->get(route('dosen.item.create', $this->section->id))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'data-q-field="options"'));
    }

    public function test_five_question_quiz_accepts_visible_choice_values_and_normalizes_line_endings(): void
    {
        $response = $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'kuis',
            'title' => 'Kuis Lima Soal',
            'module' => 'Kuis Lima Soal',
            'body' => 'Jawab seluruh soal berikut.',
            'question_type' => 'uraian',
            'cpmk' => 'CPMK-01',
            'formats' => ['text'],
            'duration_mode' => 'disabled',
            'questions' => [
                [
                    'type' => 'pilihan',
                    'prompt' => 'Soal pilihan pertama.',
                    'points' => 20,
                    'cpmk' => 'CPMK-01',
                    'options' => "Pilihan A\nPilihan B\nPilihan B",
                    'correct_answer' => 'A',
                ],
                [
                    'type' => 'pilihan',
                    'prompt' => 'Soal pilihan kedua.',
                    'points' => 20,
                    'cpmk' => 'CPMK-01',
                    'options' => "Jawaban satu\r\nJawaban dua\r\nJawaban tiga",
                    'correct_answer' => 'B',
                ],
                [
                    'type' => 'uraian',
                    'prompt' => 'Jelaskan konsep struktur data.',
                    'points' => 20,
                    'cpmk' => 'CPMK-01',
                    'essay_guide' => 'Jawaban menjelaskan konsep dengan benar.',
                ],
                [
                    'type' => 'benar_salah',
                    'prompt' => 'Queue menggunakan prinsip FIFO.',
                    'points' => 20,
                    'cpmk' => 'CPMK-01',
                    'boolean_answer' => 'Benar',
                ],
                [
                    'type' => 'pilihan',
                    'prompt' => 'Soal pilihan kelima.',
                    'points' => 20,
                    'cpmk' => 'CPMK-01',
                    'options' => "Opsi pertama\nOpsi kedua",
                    'correct_answer' => 'B',
                ],
            ],
        ]);

        $response->assertRedirect(route('dosen.course.show', $this->section->id))
            ->assertSessionHasNoErrors();

        $quiz = Assessment::where('name', 'Kuis Lima Soal')->firstOrFail();
        $questions = $quiz->learning_payload['questions'];

        $this->assertCount(5, $questions);
        $this->assertSame(['Pilihan A', 'Pilihan B'], array_column($questions[0]['option_items'], 'text'));
        $this->assertSame(['Jawaban satu', 'Jawaban dua', 'Jawaban tiga'], array_column($questions[1]['option_items'], 'text'));
        $this->assertSame(100, array_sum(array_column($questions, 'points')));
    }

    public function test_database_coding_content_uses_course_aware_route_without_404(): void
    {
        $coding = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'CODING-01',
            'name' => 'Praktikum Struktur Data',
            'type' => 'coding',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'body' => 'Implementasikan struktur data sesuai instruksi.',
                'question_type' => 'coding',
                'language' => 'python',
            ],
        ]);

        $url = route('course.assignment.code', [$this->section->id, $coding->id]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.show', $this->section->id))
            ->assertOk()
            ->assertSee($url, false);

        $this->get($url)
            ->assertOk()
            ->assertSee('Praktikum Struktur Data');
    }

    public function test_database_identity_cannot_be_overwritten_by_preview_ids_in_payload(): void
    {
        $material = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MATERI-ID',
            'name' => 'Video Materi Internal',
            'type' => 'materi',
            'final_weight' => 0,
            'status' => 'published',
            'learning_payload' => [
                'id' => 9999,
                'course' => 9999,
                'body' => 'Materi dengan identitas payload lama.',
                'link' => 'https://youtu.be/O3wDmnDU4E8?si=example',
            ],
        ]);

        $itemUrl = route('mahasiswa.course.item', [$this->section->id, $material->id]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.show', $this->section->id))
            ->assertOk()
            ->assertSee($itemUrl, false)
            ->assertDontSee('/item/9999', false);

        $this->get($itemUrl)
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/O3wDmnDU4E8', false)
            ->assertSee('origin=', false)
            ->assertSee('widget_referrer=', false)
            ->assertSee('referrerpolicy="origin"', false)
            ->assertDontSee('href="https://youtu.be/O3wDmnDU4E8', false);
    }

    public function test_external_link_attachments_open_in_the_internal_previewer(): void
    {
        $material = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MATERI-LINK',
            'name' => 'Referensi Eksternal',
            'type' => 'materi',
            'final_weight' => 0,
            'status' => 'published',
            'learning_payload' => [
                'body' => 'Baca referensi berikut.',
                'link' => 'https://example.com/reference',
            ],
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.item', [$this->section->id, $material->id]))
            ->assertOk()
            ->assertSee('Pratinjau Tautan')
            ->assertSee('openAttachmentPreview(event', false)
            ->assertDontSee('href="https://example.com/reference" target="_blank"', false);
    }

    public function test_unpublished_assessments_are_not_listed_for_students(): void
    {
        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'DRAFT-HIDDEN',
            'name' => 'Materi yang Belum Terbit',
            'type' => 'materi',
            'final_weight' => 0,
            'status' => 'draft',
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.course.show', $this->section->id))
            ->assertOk()
            ->assertDontSee('Materi yang Belum Terbit');

        $this->get(route('mahasiswa.assignment.index'))
            ->assertOk()
            ->assertDontSee('Materi yang Belum Terbit');
    }

    public function test_matching_question_builder_has_three_clean_formats_and_randomized_answers(): void
    {
        $this->actingAs($this->dosen);
        $form = $this->get(route('dosen.item.create', $this->section->id));
        $form->assertOk()
            ->assertSee('Format Pasangan Menjodohkan:')
            ->assertSee('Teks ↔ Teks')
            ->assertDontSee('Gambar ↔ Teks')
            ->assertSee('Teks ↔ Gambar')
            ->assertSee('Gambar ↔ Gambar')
            ->assertSee('data-pair-mode', false);

        $coursePage = $this->get(route('dosen.course.show', $this->section->id));
        $coursePage->assertOk()
            ->assertSee('lg:sticky lg:top-20 z-20 self-start w-full', false)
            ->assertSee('id="diskusi-kelas"', false);
    }

    public function test_lecturer_can_pin_photo_to_course_and_student_sees_it(): void
    {
        Storage::fake('local');
        $imageFile = UploadedFile::fake()->image('diagram-arsitektur.png', 800, 600);

        $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'materi',
            'title' => 'Diagram Arsitektur Sistem',
            'module' => 'Minggu 1 · Pengantar',
            'body' => 'Pelajari diagram arsitektur sistem berikut.',
            'question_type' => 'uraian',
            'cpmk' => 'CPMK-01',
            'formats' => ['file', 'image'],
            'pin_video' => '1',
            'attachments' => [$imageFile],
        ])->assertRedirect(route('dosen.course.show', $this->section->id));

        $coursePage = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $coursePage->assertOk()
            ->assertSee('id="course-video-card"', false)
            ->assertSee('Media Foto Utama')
            ->assertSee('diagram-arsitektur.png');
    }

    public function test_pinning_new_media_or_photo_automatically_unpins_older_media(): void
    {
        Storage::fake('local');

        // 1. Dosen membuat materi pertama dengan pin video link
        $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'materi',
            'title' => 'Materi Video Pertama',
            'module' => 'Minggu 1',
            'body' => 'Tonton video ini.',
            'question_type' => 'uraian',
            'cpmk' => 'CPMK-01',
            'formats' => ['file', 'link'],
            'link' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'pin_video' => '1',
        ])->assertRedirect(route('dosen.course.show', $this->section->id));

        $materi1 = Assessment::where('name', 'Materi Video Pertama')->firstOrFail();
        $this->assertTrue($materi1->learning_payload['pin_video']);

        // Halaman kelas menampilkan video dari materi 1
        $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id))
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false);

        // 2. Dosen membuat materi kedua dengan pin foto
        $photoFile = UploadedFile::fake()->image('banner-materi-kedua.jpg', 600, 400);
        $this->actingAs($this->dosen)->post(route('dosen.item.store', $this->section->id), [
            'type' => 'materi',
            'title' => 'Materi Foto Kedua',
            'module' => 'Minggu 2',
            'body' => 'Perhatikan gambar ini.',
            'question_type' => 'uraian',
            'cpmk' => 'CPMK-01',
            'formats' => ['file', 'image'],
            'pin_video' => '1',
            'attachments' => [$photoFile],
        ])->assertRedirect(route('dosen.course.show', $this->section->id));

        $materi2 = Assessment::where('name', 'Materi Foto Kedua')->firstOrFail();
        $this->assertTrue($materi2->learning_payload['pin_video']);

        // Materi 1 otomatis ter-unpin
        $materi1->refresh();
        $this->assertFalse($materi1->learning_payload['pin_video']);

        // Halaman kelas sekarang menampilkan foto materi 2, bukan lagi video materi 1
        $coursePage2 = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $coursePage2->assertOk()
            ->assertSee('banner-materi-kedua.jpg')
            ->assertDontSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false);

        // 3. Dosen mengedit materi 1 dan mencentang kembali pin media
        $this->actingAs($this->dosen)->put(route('dosen.item.update', [$this->section->id, $materi1->id]), [
            'type' => 'materi',
            'title' => 'Materi Video Pertama',
            'module' => 'Minggu 1',
            'body' => 'Tonton video ini kembali.',
            'question_type' => 'uraian',
            'cpmk' => 'CPMK-01',
            'formats' => ['file', 'link'],
            'link' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'pin_video' => '1',
        ])->assertRedirect(route('dosen.course.item', [$this->section->id, $materi1->id]));

        $materi1->refresh();
        $materi2->refresh();

        // Materi 1 kembali ter-pin, Materi 2 otomatis ter-unpin
        $this->assertTrue($materi1->learning_payload['pin_video']);
        $this->assertFalse($materi2->learning_payload['pin_video']);

        // Halaman kelas sekarang kembali menampilkan video dari materi 1
        $coursePage3 = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $coursePage3->assertOk()
            ->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false)
            ->assertDontSee('banner-materi-kedua.jpg');
    }

    public function test_matching_choices_differ_between_students(): void
    {
        $task = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-MATCH-TEST',
            'name' => 'Latihan Menjodohkan Beragam',
            'type' => 'tugas',
            'final_weight' => 10,
            'learning_payload' => [
                'module' => 'Minggu 2',
                'body' => 'Jodohkan istilah berikut.',
                'questions' => [[
                    'type' => 'mencocokkan',
                    'prompt' => 'Jodohkan istilah struktur data dengan definisinya:',
                    'points' => 30,
                    'options' => "Stack = LIFO (Last In First Out)\nQueue = FIFO (First In First Out)\nTree = Struktur Data Hierarkis Non-Linear\nGraph = Kumpulan Simpul dan Sisi\nArray = Elemen Kontigu Berindeks",
                ]],
            ],
            'status' => 'published',
        ]);

        $secondStudent = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@student.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::MAHASISWA)->first()->id,
            'nim_nidn' => '231011409999',
        ]);
        $this->section->students()->attach($secondStudent->id);

        $resp1 = $this->actingAs($this->student)->get(route('mahasiswa.course.item', [$this->section->id, $task->id]));
        $resp1->assertOk();
        $html1 = $resp1->getContent();
        $resp2 = $this->actingAs($secondStudent)->get(route('mahasiswa.course.item', [$this->section->id, $task->id]));
        $resp2->assertOk();
        $html2 = $resp2->getContent();

        // Extract option order in dropdown for both students
        preg_match_all('/<option\s+value="([^"]+)"[^>]*>/i', $html1, $matches1);
        preg_match_all('/<option\s+value="([^"]+)"[^>]*>/i', $html2, $matches2);

        $this->assertNotEmpty($matches1[1]);
        $this->assertNotEmpty($matches2[1]);
        // The first non-empty option should not just be the un-shuffled first premise
        $this->assertNotEquals($matches1[1], $matches2[1]);
    }
}
