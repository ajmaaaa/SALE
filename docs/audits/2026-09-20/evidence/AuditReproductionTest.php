<?php

// Diagnostic evidence: passing tests CONFIRM existing defects, not safe behavior.
// Run explicitly with the project's testing config; never use a live database.

namespace Tests\Audit;

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
use App\Support\LearningPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditReproductionTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $r = Role::firstOrCreate(['name' => $role], ['label' => $role]);

        return User::factory()->create(['role_id' => $r->id, 'password' => Hash::make('unique-audit-password'), 'nim_nidn' => 'AUD'.User::count()]);
    }

    private function section(User $dosen, ?User $assistant = null): ClassSection
    {
        $p = Prodi::firstOrCreate(['code' => 'AUD'], ['name' => 'Audit Prodi']);
        $s = Semester::firstOrCreate(['code' => 'AUD'], ['name' => 'Audit Semester', 'is_active' => true]);
        $mk = MataKuliah::firstOrCreate(['code' => 'AUD101', 'prodi_id' => $p->id], ['name' => 'Audit Database Course']);

        return ClassSection::create(['mata_kuliah_id' => $mk->id, 'semester_id' => $s->id, 'dosen_id' => $dosen->id, 'dosen_pendamping_id' => $assistant?->id, 'section_code' => 'A'.ClassSection::count()]);
    }

    public function test_guest_is_automatically_authenticated_as_database_admin(): void
    {
        $admin = $this->account(Role::ADMIN_PRODI);
        $this->assertGuest();
        $this->get('/admin-prodi/dashboard')->assertOk();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_public_role_switch_authenticates_real_account_and_exposes_ai_history(): void
    {
        $dosen = $this->account(Role::DOSEN);
        DB::table('ai_tasks')->insert(['id' => 1, 'title' => 'Audit', 'body' => 'Audit', 'enabled' => true]);
        DB::table('ai_access')->insert(['user_id' => $dosen->id, 'task_id' => 1]);
        DB::table('ai_turns')->insert(['user_id' => $dosen->id, 'task_id' => 1, 'question' => 'Private audit question', 'code' => '', 'answer' => 'Private answer', 'status' => 'answered']);
        $this->get('/switch-role/dosen')->assertRedirect();
        $this->assertAuthenticatedAs($dosen);
        $this->getJson('/ai/tasks/1')->assertOk()->assertJsonPath('history.0.question', 'Private audit question');
    }

    public function test_database_user_can_login_without_their_password(): void
    {
        $user = $this->account(Role::MAHASISWA);
        $this->post('/login', ['login_id' => $user->email, 'password' => ''])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_prodi_can_reset_another_programs_system_admin_password(): void
    {
        $actor = $this->account(Role::ADMIN_PRODI);
        $target = $this->account(Role::ADMIN);
        $p = Prodi::create(['code' => 'OTHER', 'name' => 'Other Program']);
        $target->update(['prodi_id' => $p->id]);
        $this->actingAs($actor)->put('/admin-prodi/pengguna/'.$target->id, [
            'name' => $target->name, 'email' => $target->email, 'nim_nidn' => $target->nim_nidn,
            'prodi_id' => $p->id, 'password' => 'audit-replacement-password',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('audit-replacement-password', $target->fresh()->password));
    }

    public function test_guest_can_read_enrollment_code_from_sequential_class_id(): void
    {
        $s = $this->section($this->account(Role::DOSEN));
        $this->get('/kelas/'.$s->id.'/barcode')->assertOk()->assertSee($s->enrollment_code);
        $this->assertGuest();
    }

    public function test_database_course_card_opens_unrelated_preview_course(): void
    {
        $s = $this->section($this->account(Role::DOSEN));
        $student = $this->account(Role::MAHASISWA);
        $s->students()->attach($student);
        $this->actingAs($student)->get('/mahasiswa/course')->assertOk()->assertSee('Audit Database Course');
        $this->get('/mahasiswa/course/'.$s->id)->assertOk()->assertViewHas('course', fn ($c) => $c['title'] === 'Struktur Data dan Algoritma');
    }

    public function test_assistant_sees_class_but_cannot_open_its_assessment_dashboard(): void
    {
        $primary = $this->account(Role::DOSEN);
        $assistant = $this->account(Role::DOSEN);
        $s = $this->section($primary, $assistant);
        $this->actingAs($assistant)->get('/dosen/penilaian-kelas')->assertOk()->assertSee('Audit Database Course');
        $this->get('/dosen/penilaian-kelas/'.$s->id)->assertForbidden();
    }

    public function test_quiz_submission_can_be_replaced_after_ui_says_locked(): void
    {
        $url = '/mahasiswa/course/3/item/5/submission';
        $this->post($url, ['from_quiz_room' => 1, 'question_answers' => [3 => ['text' => 'first']]])->assertSessionHasNoErrors();
        $this->get('/mahasiswa/course/3/item/5/quiz')->assertOk()->assertSee('Kuis Terkunci');
        $this->post($url, ['from_quiz_room' => 1, 'question_answers' => [3 => ['text' => 'replacement']]])->assertSessionHasNoErrors();
        $this->assertSame('replacement', session('learning.submissions.5.question_answers.3.text'));
    }

    public function test_randomized_quiz_validates_answers_against_original_position(): void
    {
        $items = LearningPreview::items();
        $items[5]['randomize_questions'] = true;
        $items[5]['questions'] = [
            ['id' => 11, 'type' => 'pilihan', 'prompt' => 'Question A', 'options' => "A1\nA2", 'points' => 50, 'cpmk' => 'CPMK-01', 'cpl' => 'CPL-01'],
            ['id' => 12, 'type' => 'pilihan', 'prompt' => 'Question B', 'options' => "B1\nB2", 'points' => 50, 'cpmk' => 'CPMK-01', 'cpl' => 'CPL-01'],
        ];
        $this->withSession(['learning.items' => $items, 'learning.quiz_order.5.1' => [1, 0]])
            ->get('/mahasiswa/course/3/item/5/quiz')->assertViewHas('item', fn ($i) => $i['questions'][0]['id'] === 12);
        $this->post('/mahasiswa/course/3/item/5/submission', ['from_quiz_room' => 1, 'question_answers' => [
            ['choices' => ['B1']], ['choices' => ['A1']],
        ]])->assertSessionHasErrors('question_answers');
    }

    public function test_grade_for_another_student_is_saved_under_student_one(): void
    {
        $this->withSession(['learning.submissions.4' => ['student_number' => 'OTHER-STUDENT', 'answer' => 'answer']])
            ->post('/dosen/penilaian/4', ['points' => [80]])->assertSessionHasNoErrors();
        $this->assertEquals([80], session('academic.item_grades.4.1.points'));
    }

    public function test_cpmk_without_section_uses_first_enrollment_even_for_retake(): void
    {
        $teacher = $this->account(Role::DOSEN);
        $student = $this->account(Role::MAHASISWA);
        $first = $this->section($teacher);
        $second = $this->section($teacher);
        $cpmk = Cpmk::create(['mata_kuliah_id' => $first->mata_kuliah_id, 'code' => 'AUD-01', 'description' => 'Audit', 'threshold' => 65]);
        foreach ([[$first, 20], [$second, 90]] as [$section, $score]) {
            $section->students()->attach($student);
            $assessment = Assessment::create(['class_section_id' => $section->id, 'code' => 'T1', 'name' => 'Test', 'type' => 'tugas', 'final_weight' => 100]);
            $assessment->cpmks()->attach($cpmk, ['weight' => 100]);
            StudentAssessmentScore::create(['assessment_id' => $assessment->id, 'mahasiswa_id' => $student->id, 'score' => $score]);
        }
        $obe = app(ObeCalculationService::class);
        $this->assertEquals(20, $obe->cpmkScore($cpmk, $student->id));
        $this->assertEquals(90, $obe->cpmkScore($cpmk, $student->id, $second->id));
    }

    public function test_admin_can_assign_student_as_lecturer(): void
    {
        $admin = $this->account(Role::ADMIN_PRODI);
        $s = $this->section($this->account(Role::DOSEN));
        $student = $this->account(Role::MAHASISWA);
        $this->actingAs($admin)->post('/admin-prodi/akademik/kelas', [
            'mata_kuliah_id' => $s->mata_kuliah_id, 'semester_id' => $s->semester_id,
            'section_code' => 'BADROLE', 'dosen_id' => $student->id,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_sections', ['section_code' => 'BADROLE', 'dosen_id' => $student->id]);
    }

    public function test_partial_cpmk_score_is_counted_as_full_coverage(): void
    {
        $teacher = $this->account(Role::DOSEN);
        $student = $this->account(Role::MAHASISWA);
        $s = $this->section($teacher);
        $s->students()->attach($student);
        $a = Assessment::create(['class_section_id' => $s->id, 'code' => 'T1', 'name' => 'Test', 'type' => 'tugas', 'final_weight' => 100]);
        $c1 = Cpmk::create(['mata_kuliah_id' => $s->mata_kuliah_id, 'code' => 'C1', 'description' => 'First', 'threshold' => 65]);
        $c2 = Cpmk::create(['mata_kuliah_id' => $s->mata_kuliah_id, 'code' => 'C2', 'description' => 'Second', 'threshold' => 65]);
        $a->cpmks()->attach([$c1->id => ['weight' => 50], $c2->id => ['weight' => 50]]);
        $this->actingAs($teacher)->post('/dosen/penilaian-kelas/'.$s->id.'/asesmen/'.$a->id.'/nilai', [
            'cpmk_scores' => [$student->id => [$c1->id => 40, $c2->id => null]],
        ])->assertSessionHasNoErrors();
        $result = app(ObeCalculationService::class)->finalScore($s, $student->id);
        $this->assertEquals(100, $result['coverage']);
        $this->assertEquals(60, $result['score']);
        $this->assertEquals(40, app(ObeCalculationService::class)->cpmkScore($c2, $student->id, $s->id));
    }

    public function test_removing_scored_rubric_criterion_silently_leaves_weight_above_100(): void
    {
        $teacher = $this->account(Role::DOSEN);
        $student = $this->account(Role::MAHASISWA);
        $s = $this->section($teacher);
        $a = Assessment::create(['class_section_id' => $s->id, 'code' => 'T1', 'name' => 'Test', 'type' => 'tugas', 'final_weight' => 100]);
        $rubric = Rubric::create(['assessment_id' => $a->id, 'name' => 'Audit']);
        $old = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Old', 'weight' => 100, 'max_score' => 100]);
        StudentRubricScore::create(['rubric_criterion_id' => $old->id, 'mahasiswa_id' => $student->id, 'score' => 80]);
        $this->actingAs($teacher)->post('/dosen/penilaian-kelas/'.$s->id.'/asesmen/'.$a->id.'/rubrik', [
            'rubric_name' => 'Revised', 'criteria' => [['name' => 'New', 'weight' => 100, 'max_score' => 100]],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(200, $rubric->criteria()->sum('weight'));
    }

    public function test_csv_export_contains_unescaped_formula_cell(): void
    {
        $teacher = $this->account(Role::DOSEN);
        $student = $this->account(Role::MAHASISWA);
        $student->update(['name' => '=1+1']);
        $s = $this->section($teacher);
        $s->students()->attach($student);
        $response = $this->actingAs($teacher)->get('/dosen/penilaian-kelas/'.$s->id.'/rekap/export')->assertOk();
        $this->assertStringContainsString(';=1+1;', $response->streamedContent());
    }

    public function test_logout_keeps_previous_personas_submission_in_session(): void
    {
        $this->withSession(['auth_user' => ['id' => 1], 'learning.submissions.4' => ['answer' => 'previous users answer']])
            ->get('/logout')->assertRedirect();
        $this->assertSame('previous users answer', session('learning.submissions.4.answer'));
    }

    // F15: Dosen dapat masuk ke ruang ujian CBT mahasiswa (seharusnya 403 atau read-only)
    // F15: Dosen dapat masuk ke ruang ujian CBT mahasiswa (seharusnya 403 atau read-only)
    public function test_lecturer_can_open_dedicated_quiz_item_page_with_start_button(): void
    {
        // item.blade.php tidak membungkus "Mulai Kerjakan Kuis" dengan @if(!$isLecturer)
        // Dosen melihat tombol yang membawa ke quiz-room persis seperti mahasiswa
        $this->withSession(['auth_user' => ['id' => 2, 'name' => 'Dr. Budi', 'role' => 'dosen']]);
        $response = $this->get('/mahasiswa/course/3/item/5'); // item type=kuis
        $response->assertOk();
        // Dosen seharusnya TIDAK melihat tombol ini, tapi saat ini melihatnya:
        $response->assertSee('Mulai Kerjakan Kuis');
        // Dosen seharusnya melihat panel manajemen, bukan form ujian:
        $response->assertDontSee('Kumpulkan Tugas'); // OK: ini sudah disembunyikan untuk dosen
    }

    public function test_lecturer_can_access_quiz_room_cbt_page_with_full_student_ui(): void
    {
        // LearningController::quizRoom() tidak memiliki pengecekan role
        // quiz-room.blade.php tidak memiliki kondisi isLecturer sama sekali
        // Dosen mendapat tampilan ujian penuh persis seperti mahasiswa
        $this->withSession(['auth_user' => ['id' => 2, 'name' => 'Dr. Budi', 'role' => 'dosen']]);
        $response = $this->get('/mahasiswa/course/3/item/5/quiz');
        $response->assertOk();
        // Seharusnya 403, tapi saat ini 200 dengan soal ujian:
        $response->assertSee('Kumpulkan Kuis');
        $response->assertSee('Ujian Selesai &amp; Tidak Dapat Diulang', false);
        // Dosen bahkan bisa submit jawaban karena POST /mahasiswa/course/{c}/item/{i}/submission juga tanpa guard role
    }

    // S10: Route group admin/* tidak memiliki middleware auth
    public function test_admin_routes_accessible_without_authentication(): void
    {
        $this->assertGuest();
        $this->get('/admin/pengguna')->assertOk()->assertSee('Ahmad Maulana');
        $this->post('/admin/akademik/reset')->assertRedirect('/admin/akademik');
    }

    // S11: KaprodiMonitoringController::authorizeKaprodi() meng-autologin guest menjadi akun kaprodi database
    public function test_kaprodi_monitoring_auto_authenticates_guest_as_kaprodi(): void
    {
        $kaprodi = $this->account(Role::KAPRODI);
        $this->assertGuest();
        $this->get('/kaprodi/monitoring/cpl')->assertOk();
        $this->assertAuthenticatedAs($kaprodi);
    }

    // F16: File metadata disimpan hanya pada session pengunggah sehingga browser lain (dosen) mendapat 404
    public function test_uploaded_file_inaccessible_across_different_browser_sessions(): void
    {
        Storage::fake('local');
        $uuid = (string) Str::uuid();
        Storage::disk('local')->put("learning/{$uuid}", 'sample student answer content');

        // Sesi mahasiswa pengunggah
        $studentSession = [
            'learning.files.'.$uuid => [
                'disk' => 'local',
                'path' => "learning/{$uuid}",
                'name' => 'tugas.pdf',
                'mime' => 'application/pdf',
            ],
        ];

        // Mahasiswa pengunggah bisa mengakses file
        $this->withSession($studentSession)->get('/preview/files/'.$uuid.'?download=1')->assertOk();

        // Sesi lain (dosen yang memeriksa) tidak memiliki metadata di sessionnya -> 404!
        $this->flushSession();
        $this->get('/preview/files/'.$uuid.'?download=1')->assertNotFound();
    }
}
