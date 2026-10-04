<?php

namespace Tests\Audit;

use App\Events\MessageDeleted;
use App\Events\MessagePinned;
use App\Events\MessageSent;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Message;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Semester;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Support\AcademicPreview;
use App\Support\LearningPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Audit evidence: passing assertions confirm the current defect, NOT safe behavior.
 * Run only with DB_CONNECTION=sqlite DB_DATABASE=:memory: (no real data).
 * Demo mode is disabled; normal role middleware remains enabled.
 * Laravel's test harness bypasses CSRF, so these are not CSRF tests.
 */
class AuditReproductionTest extends TestCase
{
    use RefreshDatabase;

    private User $lecturer;

    private User $student;

    private User $outsider;

    private ClassSection $section;

    private Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['app.demo_mode' => false, 'ai.enabled' => false, 'ai.key' => null]);
        Event::fake([MessageSent::class, MessagePinned::class, MessageDeleted::class]);
        foreach (['dosen', 'mahasiswa', 'admin_prodi', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role], ['label' => $role]);
        }
        $this->prodi = Prodi::create(['code' => 'AUDIT', 'name' => 'Audit Prodi']);
        $this->lecturer = $this->user('dosen');
        $this->student = $this->user('mahasiswa');
        $this->outsider = $this->user('dosen');
        $mk = MataKuliah::create(['prodi_id' => $this->prodi->id, 'code' => 'AUDIT-MK', 'name' => 'Audit Course', 'sks' => 3]);
        $semester = Semester::create(['code' => 'AUDIT-SEM', 'name' => 'Audit Semester', 'is_active' => true]);
        $this->section = ClassSection::create(['mata_kuliah_id' => $mk->id, 'semester_id' => $semester->id, 'dosen_id' => $this->lecturer->id, 'section_code' => 'A', 'capacity' => 30]);
        $this->section->students()->attach($this->student);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $role)->value('id'), 'prodi_id' => $this->prodi->id]);
    }

    private function assessment(array $attributes = []): Assessment
    {
        return Assessment::create(array_merge(['class_section_id' => $this->section->id, 'code' => 'AUDIT-'.(Assessment::count() + 1), 'name' => 'Audit Assessment', 'type' => 'tugas', 'final_weight' => 10, 'status' => 'published', 'allow_late' => true], $attributes));
    }

    private function submitUrl(Assessment $assessment): string
    {
        return "/mahasiswa/course/{$this->section->id}/item/{$assessment->id}/submission";
    }

    private function question(string $type = 'pilihan'): array
    {
        return ['type' => $type, 'prompt' => 'Audit question', 'options' => "A\nB", 'correct_answer' => 'A', 'points' => 100, 'cpmk' => 'CPMK-01'];
    }

    private function contentPayload(): array
    {
        return ['title' => 'Audit New Content', 'module' => 'Audit Module', 'type' => 'tugas', 'body' => 'Audit instructions', 'question_type' => 'uraian', 'formats' => ['text'], 'cpmk' => 'CPMK-01'];
    }

    public function test_a01_guest_can_read_private_chat_and_impersonate_first_user(): void
    {
        $room = Room::forCourse($this->section->id);
        Message::create(['room_id' => $room->id, 'user_id' => $this->student->id, 'content' => 'AUDIT PRIVATE MESSAGE']);
        $this->getJson("/chat/course/{$this->section->id}/messages")->assertOk()->assertSee('AUDIT PRIVATE MESSAGE');
        $this->assertGuest();
        $this->postJson("/chat/course/{$this->section->id}/messages", ['content' => 'AUDIT IMPERSONATION'])->assertOk();
        $this->assertDatabaseHas('messages', ['content' => 'AUDIT IMPERSONATION', 'user_id' => $this->lecturer->id]);
    }

    public function test_a02_unenrolled_student_can_join_room_by_reading_it(): void
    {
        $other = $this->user('mahasiswa');
        $this->actingAs($other)->getJson("/chat/course/{$this->section->id}/messages")->assertOk();
        $this->assertDatabaseHas('room_members', ['user_id' => $other->id]);
        $this->assertFalse($this->section->students()->whereKey($other->id)->exists());
    }

    public function test_a03_unassigned_lecturer_can_moderate_messages(): void
    {
        $room = Room::forCourse($this->section->id);
        $message = Message::create(['room_id' => $room->id, 'user_id' => $this->student->id, 'content' => 'AUDIT']);
        $this->actingAs($this->outsider)->postJson("/chat/messages/{$message->id}/pin")->assertOk();
        $this->deleteJson("/chat/messages/{$message->id}")->assertOk();
        $this->assertSoftDeleted($message);
    }

    public function test_a04_demo_ai_account_is_created_when_demo_mode_is_disabled(): void
    {
        $this->post('/ai/login', ['email' => 'demo.ai@sale.test', 'password' => 'password123456', 'assignment' => 1])->assertRedirect();
        $demo = User::where('email', 'demo.ai@sale.test')->firstOrFail();
        $this->assertAuthenticatedAs($demo);
        $this->assertDatabaseHas('ai_access', ['user_id' => $demo->id, 'task_id' => 1]);
    }

    public function test_a05_admin_prodi_can_reset_foreign_user_password(): void
    {
        $admin = $this->user('admin_prodi');
        $foreign = Prodi::create(['code' => 'FOREIGN', 'name' => 'Foreign Prodi']);
        $this->student->update(['prodi_id' => $foreign->id, 'nim_nidn' => 'AUDIT-STUDENT']);
        $this->actingAs($admin)->put("/admin-prodi/pengguna/{$this->student->id}", [
            'name' => 'Audit Changed', 'email' => $this->student->email, 'nim_nidn' => 'AUDIT-STUDENT', 'prodi_id' => $foreign->id, 'password' => 'AuditChangedPassword123',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(Hash::check('AuditChangedPassword123', $this->student->fresh()->password));
    }

    public function test_a06_new_account_uses_shared_default_password(): void
    {
        $this->actingAs($this->user('admin_prodi'))->post('/admin-prodi/pengguna', [
            'role_type' => 'mahasiswa', 'name' => 'Audit New', 'email' => 'audit-new@example.test', 'nim_nidn' => 'AUDIT-NEW', 'prodi_id' => $this->prodi->id,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(Hash::check('password123', User::where('email', 'audit-new@example.test')->firstOrFail()->password));
    }

    public function test_a07_private_attachment_is_readable_without_enrollment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('audit/private.txt', 'AUDIT PRIVATE ATTACHMENT');
        $uuid = '12345678-1234-4234-8234-123456789012';
        $this->assessment(['learning_payload' => ['file_meta' => [$uuid => ['path' => 'audit/private.txt', 'name' => 'private.txt', 'mime' => 'text/plain']]]]);
        $other = $this->user('mahasiswa');
        $this->actingAs($other)->get('/preview/files/'.$uuid)->assertOk()->assertDownload('private.txt');
    }

    public function test_a08_get_course_assigns_unrelated_lecturer_as_owner(): void
    {
        $this->section->update(['dosen_id' => null]);
        $this->actingAs($this->outsider)->get('/dosen/course/'.$this->section->id)->assertOk();
        $this->assertSame($this->outsider->id, $this->section->fresh()->dosen_id);
    }

    public function test_a09_unassigned_lecturer_can_create_class_content(): void
    {
        $this->actingAs($this->outsider)->post('/dosen/course/'.$this->section->id.'/items', $this->contentPayload())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('assessments', ['class_section_id' => $this->section->id, 'name' => 'Audit New Content']);
    }

    public function test_a10_unassigned_lecturer_can_create_grade_for_unenrolled_user(): void
    {
        $assessment = $this->assessment();
        // Preview item 1 is course 1's coding task; the database ID collides.
        $this->assertSame(1, $assessment->id);
        $this->actingAs($this->outsider)->post('/dosen/course/1/item/1/penilaian-tugas/'.$this->outsider->id, ['skor' => 99])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'mahasiswa_id' => $this->outsider->id, 'score' => 99]);
    }

    public function test_a11_quiz_submission_errors_after_overall_score_is_saved(): void
    {
        $cpmk = Cpmk::create(['mata_kuliah_id' => $this->section->mata_kuliah_id, 'code' => 'CPMK-01', 'description' => 'Audit', 'threshold' => 60]);
        $assessment = $this->assessment(['type' => 'kuis', 'learning_payload' => ['questions' => [$this->question()]]]);
        $assessment->cpmks()->attach($cpmk->id, ['weight' => 100]);
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->student)->post($this->submitUrl($assessment), ['from_quiz_room' => 1, 'question_answers' => [['choices' => ['A']]]]);
            $this->fail('Expected missing model import');
        } catch (\Error $error) {
            $this->assertStringContainsString('App\\Http\\Controllers\\StudentAssessmentCpmkScore', $error->getMessage());
        }
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => 100]);
        $this->assertDatabaseCount('student_assessment_cpmk_scores', 0);
    }

    public function test_a12_essay_pending_grade_is_already_visible_in_grade_tab(): void
    {
        $assessment = $this->assessment(['type' => 'kuis', 'name' => 'AUDIT PENDING ESSAY', 'learning_payload' => ['questions' => [$this->question('uraian')]]]);
        $this->actingAs($this->student)->post($this->submitUrl($assessment), ['from_quiz_room' => 1, 'question_answers' => [['text' => 'Audit answer']]])->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => null]);
        $this->get('/mahasiswa/assignment?tab=nilai')->assertOk()->assertViewHas('items', fn ($items) => isset($items[$assessment->id]));
    }

    public function test_a13_student_can_overwrite_already_graded_submission(): void
    {
        $assessment = $this->assessment();
        StudentAssessmentScore::create(['assessment_id' => $assessment->id, 'mahasiswa_id' => $this->student->id, 'score' => 88, 'graded_by' => $this->lecturer->id, 'graded_at' => now()]);
        $this->actingAs($this->student)->post($this->submitUrl($assessment), ['answer' => 'Replacement answer'])->assertRedirect();
        $score = StudentAssessmentScore::firstOrFail();
        $this->assertNull($score->score);
        $this->assertNotNull($score->graded_at);
    }

    public function test_a14_closed_assessment_still_accepts_submission(): void
    {
        $assessment = $this->assessment(['status' => 'closed']);
        $this->actingAs($this->student)->post($this->submitUrl($assessment), ['answer' => 'Submitted to closed assessment'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'mahasiswa_id' => $this->student->id]);
    }

    public function test_a15_new_content_keeps_preview_id_in_database_payload(): void
    {
        $this->actingAs($this->lecturer)->post('/dosen/course/'.$this->section->id.'/items', $this->contentPayload())->assertSessionHasNoErrors()->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertNotSame($assessment->id, $assessment->learning_payload['id']);
        $this->assertSame($assessment->learning_payload['id'], LearningPreview::databaseAssessment($assessment)['id']);
        $this->flushSession();
        $this->get('/dosen/course/'.$this->section->id.'/item/'.$assessment->id.'/penilaian-tugas')->assertOk()
            ->assertViewHas('item', fn ($item) => $item['title'] === 'Praktikum Binary Tree');
    }

    public function test_a16_real_quiz_without_questions_gets_sample_bst_questions(): void
    {
        $assessment = $this->assessment(['type' => 'kuis']);
        $this->assertNull($assessment->learning_payload);
        $this->assertSame(LearningPreview::defaultQuizQuestions(), LearningPreview::databaseAssessment($assessment)['questions']);
    }

    public function test_a17_submission_answer_disappears_when_session_is_reset(): void
    {
        $assessment = $this->assessment();
        $this->actingAs($this->student)->post($this->submitUrl($assessment), ['answer' => 'AUDIT STUDENT WORK'])->assertRedirect();
        $this->assertSame('AUDIT STUDENT WORK', session('learning.submissions.'.$assessment->id.'.answer'));
        $this->flushSession();
        $this->actingAs($this->lecturer);
        $this->assertNull(session('learning.submissions.'.$assessment->id));
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => null]);
    }

    public function test_a18_partial_cpmk_input_already_publishes_overall_grade(): void
    {
        $assessment = $this->assessment();
        $a = Cpmk::create(['mata_kuliah_id' => $this->section->mata_kuliah_id, 'code' => 'A', 'description' => 'Audit A', 'threshold' => 60]);
        $b = Cpmk::create(['mata_kuliah_id' => $this->section->mata_kuliah_id, 'code' => 'B', 'description' => 'Audit B', 'threshold' => 60]);
        $assessment->cpmks()->attach([$a->id => ['weight' => 50], $b->id => ['weight' => 50]]);
        $this->actingAs($this->lecturer)->post("/dosen/penilaian-kelas/{$this->section->id}/asesmen/{$assessment->id}/nilai", ['cpmk_scores' => [$this->student->id => [$a->id => 40, $b->id => null]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['score' => 40, 'assessment_id' => $assessment->id]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', ['cpmk_id' => $b->id, 'score' => null]);
    }

    public function test_a19_new_student_without_enrollment_sees_sample_assignments(): void
    {
        $other = $this->user('mahasiswa');
        $this->actingAs($other)->get('/mahasiswa/assignment')->assertOk()->assertSee('Kuis Evaluasi Model');
    }

    public function test_a20_notification_target_allows_external_host_with_same_prefix(): void
    {
        $target = url('/').'.attacker.example/audit';
        $this->actingAs($this->student)->get('/mahasiswa/notifikasi/audit/read?'.http_build_query(['target' => $target]))->assertRedirect($target);
    }

    public function test_a21_database_material_is_listed_as_assignment(): void
    {
        $assessment = $this->assessment(['type' => 'materi', 'name' => 'AUDIT MATERIAL']);
        $this->actingAs($this->student)->get('/mahasiswa/assignment')->assertOk()->assertViewHas('items', fn ($items) => ($items[$assessment->id]['type'] ?? '') === 'tugas');
    }

    public function test_a22_server_accepts_submission_after_quiz_duration(): void
    {
        $assessment = $this->assessment(['type' => 'kuis', 'learning_payload' => ['duration_enabled' => true, 'duration_minutes' => 1, 'questions' => [['type' => 'mencocokkan', 'prompt' => 'Audit match', 'options' => "TERM_A = ANSWER_A\nTERM_B = ANSWER_B", 'points' => 100, 'cpmk' => 'CPMK-01']]]]);
        $this->actingAs($this->student)->get("/mahasiswa/course/{$this->section->id}/item/{$assessment->id}/quiz")->assertOk()->assertSee('ANSWER_A');
        $this->travel(2)->minutes();
        $this->post($this->submitUrl($assessment), ['from_quiz_room' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => 0]);
    }

    public function test_a23_existing_direct_grade_and_cpmk_grade_can_disagree(): void
    {
        $assessment = $this->assessment();
        $cpmk = Cpmk::create(['mata_kuliah_id' => $this->section->mata_kuliah_id, 'code' => 'A', 'description' => 'Audit A', 'threshold' => 60]);
        $assessment->cpmks()->attach($cpmk->id, ['weight' => 100]);
        StudentAssessmentCpmkScore::create(['assessment_id' => $assessment->id, 'mahasiswa_id' => $this->student->id, 'cpmk_id' => $cpmk->id, 'score' => 20]);
        $this->actingAs($this->lecturer)->post("/dosen/penilaian-kelas/{$this->section->id}/asesmen/{$assessment->id}/nilai", ['scores' => [$this->student->id => 90]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['score' => 90]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', ['score' => 20]);
    }

    public function test_a24_question_builder_letter_key_is_not_equal_to_answer_text(): void
    {
        $q = array_merge($this->question(), ['options' => "First answer\nSecond answer", 'correct_answer' => 'B']);
        $assessment = $this->assessment(['type' => 'kuis', 'learning_payload' => ['questions' => [$q]]]);
        $this->actingAs($this->student)->post($this->submitUrl($assessment), ['from_quiz_room' => 1, 'question_answers' => [['choices' => ['Second answer']]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => 0]);
    }

    public function test_a25_teacher_boolean_key_is_ignored_by_submission_grader(): void
    {
        $q = array_merge($this->question('benar_salah'), ['correct_answer' => null, 'boolean_answer' => 'Salah']);
        $assessment = $this->assessment(['type' => 'kuis', 'learning_payload' => ['questions' => [$q]]]);
        $this->actingAs($this->student)->post($this->submitUrl($assessment), ['from_quiz_room' => 1, 'question_answers' => [['boolean_choice' => 'Salah']]])->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => 0]);
    }

    public function test_a26_essay_review_grader_accepts_wrong_matches_and_ignores_keys(): void
    {
        $this->assertSame(100.0, AcademicPreview::evaluateAutoQuestion(['type' => 'mencocokkan', 'points' => 100, 'options' => 'Term = Correct'], ['matching' => ['Wrong']]));
        $this->assertSame(0.0, AcademicPreview::evaluateAutoQuestion(array_merge($this->question(), ['correct_answer' => 'B']), ['choices' => ['B']]));
    }

    public function test_a27_randomized_questions_are_graded_against_original_indexes(): void
    {
        $options = implode("\n", array_map(fn ($i) => 'Answer '.$i, range(0, 7)));
        $questions = array_map(fn ($i) => array_merge($this->question(), ['prompt' => 'Question '.$i, 'options' => $options, 'correct_answer' => 'Answer '.$i]), range(0, 7));
        $assessment = $this->assessment(['type' => 'kuis', 'learning_payload' => ['randomize_questions' => true, 'questions' => $questions]]);
        $response = $this->actingAs($this->student)->get("/mahasiswa/course/{$this->section->id}/item/{$assessment->id}/quiz")->assertOk();
        $shown = $response->viewData('item')['questions'];
        $this->assertNotSame($questions, $shown);
        $answers = array_map(fn ($q) => ['choices' => [$q['correct_answer']]], $shown);
        $this->post($this->submitUrl($assessment), ['from_quiz_room' => 1, 'question_answers' => $answers])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertLessThan(100, (float) StudentAssessmentScore::firstOrFail()->score);
    }

    public function test_a28_student_obe_progress_uses_old_section_for_repeated_course(): void
    {
        $cpmk = Cpmk::create(['mata_kuliah_id' => $this->section->mata_kuliah_id, 'code' => 'A', 'description' => 'Audit A', 'threshold' => 60]);
        $old = $this->assessment(['final_weight' => 100]);
        $old->cpmks()->attach($cpmk->id, ['weight' => 100]);
        StudentAssessmentScore::create(['assessment_id' => $old->id, 'mahasiswa_id' => $this->student->id, 'score' => 20]);
        $newSection = $this->section->replicate();
        $newSection->section_code = 'B';
        $newSection->enrollment_code = null;
        $newSection->save();
        $newSection->students()->attach($this->student);
        $new = $this->assessment(['class_section_id' => $newSection->id, 'final_weight' => 100]);
        $new->cpmks()->attach($cpmk->id, ['weight' => 100]);
        StudentAssessmentScore::create(['assessment_id' => $new->id, 'mahasiswa_id' => $this->student->id, 'score' => 90]);
        $response = $this->actingAs($this->student)->get('/mahasiswa/capaian-obe')->assertOk();
        $row = $response->viewData('courseProgress')->first(fn ($row) => $row['section']->id === $newSection->id);
        $this->assertSame(20.0, $row['cpmks']->first()['score']);
        $this->assertSame(90.0, $row['final_score']);
    }

    public function test_a29_assistant_can_edit_assessment_but_cannot_open_rubric(): void
    {
        $assessment = $this->assessment();
        $this->section->update(['dosen_pendamping_id' => $this->outsider->id]);
        $this->actingAs($this->outsider)->get("/dosen/penilaian-kelas/{$this->section->id}/asesmen/{$assessment->id}/ubah")->assertOk();
        $this->get("/dosen/penilaian-kelas/{$this->section->id}/asesmen/{$assessment->id}/rubrik")->assertForbidden();
    }

    public function test_a30_valid_scoped_grade_flow_and_cross_class_gate_are_present(): void
    {
        $assessment = $this->assessment();
        $url = "/dosen/penilaian-kelas/{$this->section->id}/asesmen/{$assessment->id}/nilai";
        $this->actingAs($this->outsider)->post($url, ['scores' => [$this->student->id => 90]])->assertForbidden();
        $this->actingAs($this->lecturer)->post($url, ['scores' => [$this->student->id => 90]])->assertRedirect()->assertSessionHasNoErrors();
        $this->flushSession();
        $this->actingAs($this->student)->get('/mahasiswa/assignment?tab=nilai')->assertOk()->assertViewHas('items', fn ($items) => isset($items[$assessment->id]));
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'mahasiswa_id' => $this->student->id, 'score' => 90]);
    }

    public function test_a31_excel_export_interprets_student_name_as_formula(): void
    {
        $this->student->update(['name' => '=1+1']);
        $response = $this->actingAs($this->lecturer)->get("/dosen/penilaian-kelas/{$this->section->id}/rekap/export/excel")->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'sale-audit-xlsx-');
        try {
            file_put_contents($path, $response->streamedContent());
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            $this->assertStringContainsString('<f>1+1</f>', $xml);
        } finally {
            unlink($path);
        }
    }

    public function test_a32_content_creation_can_push_assessment_weights_over_100(): void
    {
        $this->assessment(['final_weight' => 100]);
        $this->actingAs($this->lecturer)->post('/dosen/course/'.$this->section->id.'/items', $this->contentPayload())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(110.0, (float) $this->section->assessments()->sum('final_weight'));
    }

    public function test_a33_rubric_input_through_grade_route_can_exceed_criterion_max(): void
    {
        $assessment = $this->assessment(['uses_rubric' => true]);
        $rubric = Rubric::create(['assessment_id' => $assessment->id, 'name' => 'Audit Rubric']);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Audit Criterion', 'weight' => 100, 'max_score' => 10]);
        $this->actingAs($this->lecturer)->post("/dosen/penilaian-kelas/{$this->section->id}/asesmen/{$assessment->id}/nilai", ['rubric_scores' => [$this->student->id => [$criterion->id => 100]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', ['assessment_id' => $assessment->id, 'score' => 1000]);
    }
}
