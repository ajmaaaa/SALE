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
use App\Models\SubmissionAnswer;
use App\Models\User;
use App\Support\QuizQuestion;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MixedQuizAndFinalUiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mixed_quiz_keeps_automatic_score_and_combines_it_with_manual_essay_score(): void
    {
        [$lecturer, $student, $section, $course] = $this->academicContext();
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $course->id,
            'code' => 'CPMK-01',
            'description' => 'Menerapkan konsep pengujian.',
            'threshold' => 65,
        ]);

        $choice = QuizQuestion::canonicalizeQuestion([
            'id' => 'objective-1',
            'type' => 'pilihan',
            'prompt' => 'Pilih jawaban benar.',
            'options' => "Salah\nBenar",
            'correct_answer' => 'B',
            'points' => 50,
            'cpmk' => 'CPMK-01',
        ]);
        $essay = QuizQuestion::canonicalizeQuestion([
            'id' => 'essay-1',
            'type' => 'uraian',
            'prompt' => 'Jelaskan alasan Anda.',
            'points' => 50,
            'cpmk' => 'CPMK-01',
        ]);

        $quiz = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'KUIS-CAMPURAN',
            'name' => 'Kuis Campuran',
            'type' => 'kuis',
            'final_weight' => 20,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => ['questions' => [$choice, $essay]],
        ]);
        $quiz->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $this->actingAs($student)->post(route('mahasiswa.course.submit', [$section, $quiz]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                'objective-1' => [
                    'question_id' => 'objective-1',
                    'option_ids' => $choice['answer_key']['option_ids'],
                ],
                'essay-1' => [
                    'question_id' => 'essay-1',
                    'text' => 'Jawaban esai mahasiswa.',
                ],
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [$section, $quiz]))
            ->assertSessionHasNoErrors();

        $submission = Submission::where('assessment_id', $quiz->id)->firstOrFail();
        $objectiveAnswer = $submission->answers()->where('question_id', 'objective-1')->firstOrFail();
        $essayAnswer = $submission->answers()->where('question_id', 'essay-1')->firstOrFail();

        $this->assertSame('50.00', $objectiveAnswer->earned_score);
        $this->assertSame('automatic', $objectiveAnswer->grading_status);
        $this->assertNull($essayAnswer->earned_score);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $student->id,
            'score' => null,
            'status' => StudentAssessmentScore::STATUS_PENDING,
        ]);

        $this->actingAs($lecturer)->post(route('dosen.penilaian.asesmen.answer.score', [$section, $quiz, $essayAnswer]), [
            'score' => 40,
        ])->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$section, $quiz]))
            ->assertSessionHasNoErrors();

        $this->assertSame('50.00', $objectiveAnswer->fresh()->earned_score);
        $this->assertSame('40.00', $essayAnswer->fresh()->earned_score);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $student->id,
            'score' => 90,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);
    }

    public function test_final_rekap_and_lecturer_quiz_preview_use_the_requested_flows(): void
    {
        [$lecturer, , $section] = $this->academicContext();
        $question = QuizQuestion::canonicalizeQuestion([
            'id' => 'preview-1',
            'type' => 'pilihan',
            'prompt' => 'Soal pratinjau.',
            'options' => "Salah\nBenar",
            'correct_answer' => 'B',
            'points' => 100,
        ]);
        $quiz = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'PREVIEW',
            'name' => 'Kuis Pratinjau',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => ['questions' => [$question]],
        ]);

        $this->actingAs($lecturer)
            ->get(route('dosen.rekap.index'))
            ->assertOk()
            ->assertSee('Rekap Nilai')
            ->assertDontSee('Rekap CPL')
            ->assertDontSee('Rekap CPMK');

        $this->get(route('dosen.course.quiz.preview', [$section, $quiz]))
            ->assertOk()
            ->assertSee('Pratinjau Kunci Jawaban')
            ->assertSee('Soal pratinjau.')
            ->assertSee('Benar (+100 Poin)');
    }

    public function test_profile_photo_url_is_relative_to_the_host_used_by_the_browser(): void
    {
        [$lecturer] = $this->academicContext();
        $lecturer->forceFill(['profile_photo_path' => 'profile-photos/1/photo.png'])->save();

        $this->assertSame('/storage/profile-photos/1/photo.png', $lecturer->fresh()->profile_photo_url);
    }

    public function test_lecturer_notification_marked_read_on_input_nilai_open_and_disables_cache(): void
    {
        [$lecturer, $student, $section] = $this->academicContext();
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TUGAS-1',
            'name' => 'Tugas Algoritma',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
        ]);
        $submission = Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'mahasiswa_id' => $student->id,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // 1. Initial notification page should show unread notification with anti-cache headers
        $notifPage = $this->actingAs($lecturer)->get(route('dosen.notifications'));
        $notifPage->assertOk();
        $this->assertStringContainsString('no-store', (string) $notifPage->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $notifPage->headers->get('Cache-Control'));
        $notifPage->assertSee('Buka Input Nilai')
            ->assertSee('opacity-100');

        // 2. Click "Buka Input Nilai" (GET request with target)
        $target = route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id], false);
        $readResponse = $this->get(route('dosen.notifications.read', [
            'id' => "submission_{$submission->id}",
            'target' => $target,
        ]));
        $readResponse->assertRedirect($target);

        // 3. Revisiting notifications (as happens on browser back) must show notification as read
        $notifPageAfter = $this->get(route('dosen.notifications'));
        $notifPageAfter->assertOk()
            ->assertSee('opacity-60')
            ->assertDontSee('hover:opacity-100');
    }

    public function test_input_nilai_table_has_no_bobot_or_score_ranges_and_clean_buttons(): void
    {
        [$lecturer, $student, $section, $course] = $this->academicContext();
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $course->id,
            'code' => 'CPMK-011',
            'description' => 'Mampu mengimplementasikan binary search tree.',
            'threshold' => 70,
        ]);
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TUGAS-01',
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'cpmk_map' => [
                    $cpmk->id => 10,
                ],
            ],
        ]);
        $assessment->cpmks()->attach($cpmk->id, ['weight' => 10]);

        $response = $this->actingAs($lecturer)->get(route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]));
        $response->assertOk()
            ->assertSee('CPMK-011')
            ->assertDontSee('Bobot: 10%')
            ->assertDontSee('Skor 0')
            ->assertDontSee('Skala 0')
            ->assertSee('Lihat Jawaban')
            ->assertDontSee('bg-brand/5')
            ->assertDontSee('>Kembali<', false);

        $html = $response->getContent();
        $buttonPos = strpos($html, 'id="btn-simpan-nilai"');
        $tablePos = strpos($html, 'id="table-input-nilai"');
        $this->assertNotFalse($buttonPos);
        $this->assertNotFalse($tablePos);
        $this->assertTrue($buttonPos < $tablePos, 'Tombol simpan draft harus berada di atas tabel.');

        // Check clear button does not have ai slop sky-100 color
        $notifPage = $this->get(route('dosen.notifications'));
        $notifPage->assertOk()
            ->assertDontSee('bg-sky-100')
            ->assertDontSee('text-sky-900');
    }

    public function test_published_content_records_and_displays_publish_time(): void
    {
        [$lecturer, $student, $section] = $this->academicContext();

        // 1. Create content with status published
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'MAT-01',
            'name' => 'Materi Pertemuan 1',
            'type' => 'materi',
            'final_weight' => 0,
            'status' => 'published',
            'learning_payload' => [
                'module' => 'Pengantar Perkuliahan',
                'body' => 'Pengenalan konsep dasar.',
            ],
        ]);

        // Verification: published_at was automatically recorded
        $this->assertNotNull($assessment->fresh()->published_at);
        $expectedDate = $assessment->fresh()->published_at->translatedFormat('d M Y, H:i');

        // 2. Inside course view, publish time is displayed
        $coursePage = $this->actingAs($lecturer)->get(route('dosen.course.show', $section->id));
        $coursePage->assertOk()
            ->assertSee('Diterbitkan '.$expectedDate);

        // 3. Inside item detail view, publish time is displayed
        $itemPage = $this->get(route('dosen.course.item', [$section->id, $assessment->id]));
        $itemPage->assertOk()
            ->assertSee('Diterbitkan '.$expectedDate);

        // 4. Student enrolled in class also sees publish time
        $studentPage = $this->actingAs($student)->get(route('mahasiswa.course.show', $section->id));
        $studentPage->assertOk()
            ->assertSee('Diterbitkan '.$expectedDate);
    }

    public function test_score_columns_are_not_editable_in_table_and_graded_via_jawaban_for_tipe_soal_and_tugas(): void
    {
        [$lecturer, $student, $section, $course] = $this->academicContext();
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $course->id,
            'code' => 'CPMK-TEST',
            'description' => 'Tes penilaian via jawaban',
            'threshold' => 70,
        ]);

        // 1. Kasus Tugas:
        $tugas = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas Mandiri',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
            'learning_payload' => [
                'module' => 'Modul 1',
            ],
        ]);
        $tugas->cpmks()->attach($cpmk->id, ['weight' => 20]);

        $tugasPage = $this->actingAs($lecturer)->get(route('dosen.penilaian.asesmen.nilai', [$section->id, $tugas->id]));
        $tugasPage->assertOk();
        // Kolom nilai pada tabel tidak boleh berisi input cpmk-input untuk diisi langsung
        $tugasPage->assertDontSee('cpmk-input');
        $tugasPage->assertSee('Lihat Jawaban');

        // Nilai diisi melalui bagian jawaban (submit ke route student score)
        $gradeResponse = $this->actingAs($lecturer)->post(
            route('dosen.penilaian.asesmen.student.score', [$section->id, $tugas->id, $student->id]),
            [
                'cpmk_scores' => [
                    $cpmk->id => 88.5,
                ],
            ]
        );
        $gradeResponse->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$section->id, $tugas->id]));

        // Refresh halaman: nilai 88.5 tampil di tabel (bukan sebagai input cpmk-input)
        $tugasPageAfter = $this->get(route('dosen.penilaian.asesmen.nilai', [$section->id, $tugas->id]));
        $tugasPageAfter->assertOk()
            ->assertSee('88.5')
            ->assertDontSee('cpmk-input');

        // 2. Kasus Tipe Soal (Kuis):
        $quiz = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'KUIS-01',
            'name' => 'Kuis Pertemuan 1',
            'type' => 'kuis',
            'final_weight' => 15,
            'status' => 'published',
            'learning_payload' => [
                'questions' => [
                    [
                        'id' => 'q1',
                        'type' => 'uraian',
                        'prompt' => 'Jelaskan konsep OOP.',
                        'points' => 100,
                        'cpmk' => 'CPMK-TEST',
                    ],
                ],
            ],
        ]);
        $quiz->cpmks()->attach($cpmk->id, ['weight' => 15]);

        $quizPage = $this->get(route('dosen.penilaian.asesmen.nilai', [$section->id, $quiz->id]));
        $quizPage->assertOk();
        // Kolom nilai kuis juga tidak boleh ada input cpmk-input di tabel
        $quizPage->assertDontSee('cpmk-input');
        $quizPage->assertSee('Lihat Jawaban');
    }

    public function test_login_and_password_change_headings_are_center_aligned(): void
    {
        // 1. Login page header
        $loginPage = $this->get(route('login'));
        $loginPage->assertOk()
            ->assertSee('mb-7 text-center')
            ->assertSee('MASUK KE SALE')
            ->assertSee('font-bold')
            ->assertDontSee('Gunakan email atau nomor induk yang terdaftar.');

        // 2. First-time password change page header
        $this->seed(RoleSeeder::class);
        $role = Role::where('name', Role::MAHASISWA)->firstOrFail();
        $user = User::create([
            'name' => 'User Ganti Sandi',
            'email' => 'gantisandi@test.local',
            'password' => 'password',
            'role_id' => $role->id,
            'nim_nidn' => 'M999',
            'must_change_password' => true,
        ]);

        $pwPage = $this->actingAs($user)->get(route('password.change'));
        $pwPage->assertOk()
            ->assertSee('text-center')
            ->assertSee('Buat password baru')
            ->assertSee('mx-auto flex h-10 w-10');
    }

    public function test_admin_can_download_user_excel_template_and_import_excel_file(): void
    {
        $this->seed(RoleSeeder::class);
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-utama@test.local',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'nim_nidn' => 'ADM001',
        ]);
        $admin->roles()->sync([$adminRole->id]);

        $this->actingAs($admin);

        // 1. Check user management page renders template button and file input
        $response = $this->get('/admin/pengguna');
        $response->assertOk()
            ->assertSee('Template Excel')
            ->assertSee('Unduh Template Excel (.xlsx)')
            ->assertSee('name="file"', false);

        // 2. Download template
        $templateResponse = $this->get(route('admin.users.template'));
        $templateResponse->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=template-import-pengguna.xlsx');

        // 3. Test Excel file bulk upload
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['NIM / NIDN', 'Nama Lengkap', 'Email', 'Peran', 'Status'],
            ['231011409991', 'Budi Excel', 'budi.excel@student.test', 'mahasiswa', 'aktif'],
            ['DSN9992', 'Dosen Excel', 'dosen.excel@campus.test', 'dosen', 'aktif'],
        ]);
        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'users_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $postResponse = $this->post(route('admin.users.bulk'), [
            'file' => $uploadedFile,
        ]);

        $postResponse->assertRedirect('/admin/pengguna')
            ->assertSessionHas('notice');

        $this->assertDatabaseHas('users', [
            'nim_nidn' => '231011409991',
            'email' => 'budi.excel@student.test',
            'name' => 'Budi Excel',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'nim_nidn' => 'DSN9992',
            'email' => 'dosen.excel@campus.test',
            'name' => 'Dosen Excel',
            'is_active' => true,
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    public function test_quiz_grading_view_omits_automatically_graded_questions_from_answer_modal(): void
    {
        [$lecturer, $student, $section, $course] = $this->academicContext();
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $course->id,
            'code' => 'CPMK-MIXED',
            'description' => 'Tes soal campuran',
            'threshold' => 60,
        ]);

        $mcQuestion = QuizQuestion::canonicalizeQuestion([
            'id' => 'q-auto-1',
            'type' => 'pilihan',
            'prompt' => 'Pertanyaan Pilihan Ganda Rahasia Otomatis',
            'options' => "A\nB",
            'correct_answer' => 'A',
            'points' => 40,
            'cpmk' => 'CPMK-MIXED',
        ]);
        $essayQuestion = QuizQuestion::canonicalizeQuestion([
            'id' => 'q-essay-1',
            'type' => 'uraian',
            'prompt' => 'Pertanyaan Esai Terbuka Manual',
            'points' => 60,
            'cpmk' => 'CPMK-MIXED',
        ]);

        $quiz = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'KUIS-OMIT-AUTO',
            'name' => 'Kuis Campuran Omit Auto',
            'type' => 'kuis',
            'final_weight' => 20,
            'status' => 'published',
            'due_at' => now()->addDay(),
            'learning_payload' => ['questions' => [$mcQuestion, $essayQuestion]],
        ]);
        $quiz->cpmks()->attach($cpmk->id, ['weight' => 100]);

        // Student submits
        $this->actingAs($student)->post(route('mahasiswa.course.submit', [$section, $quiz]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                'q-auto-1' => [
                    'question_id' => 'q-auto-1',
                    'option_ids' => $mcQuestion['answer_key']['option_ids'],
                ],
                'q-essay-1' => [
                    'question_id' => 'q-essay-1',
                    'text' => 'Teks jawaban esai mahasiswa.',
                ],
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [$section, $quiz]));

        // Lecturer views input nilai page
        $res = $this->actingAs($lecturer)->get(route('dosen.penilaian.asesmen.nilai', [$section, $quiz]));
        $res->assertOk();

        $studentEssayData = $res->viewData('studentEssayData');
        $studentData = $studentEssayData[$student->id];

        // Ensure questions array for the student only has the essay question, not the auto-graded question
        $this->assertCount(1, $studentData['questions']);
        $this->assertSame('q-essay-1', $studentData['questions'][0]['prompt'] === 'Pertanyaan Esai Terbuka Manual' ? 'q-essay-1' : null);
        $this->assertTrue($studentData['questions'][0]['is_essay']);

        // The page must contain the essay prompt but NOT the automatic multiple-choice prompt in the answer data
        $res->assertSee('Pertanyaan Esai Terbuka Manual');
        $res->assertDontSee('Pertanyaan Pilihan Ganda Rahasia Otomatis');

        // Test 100% objective quiz:
        $pureObjQuiz = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'KUIS-PURE-OBJ',
            'name' => 'Kuis 100 Persen Objektif',
            'type' => 'kuis',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => ['questions' => [$mcQuestion]],
        ]);
        $pureObjQuiz->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $pureRes = $this->get(route('dosen.penilaian.asesmen.nilai', [$section, $pureObjQuiz]));
        $pureRes->assertOk();
        $this->assertFalse($pureRes->viewData('hasEssayQuestions'));
        $pureRes->assertSee('Seluruh butir soal dinilai secara otomatis oleh sistem.');
    }

    public function test_lecturer_can_save_all_essay_scores_in_one_action(): void
    {
        [$lecturer, $student, $section] = $this->academicContext();
        $cpmk = Cpmk::create([
            'mata_kuliah_id' => $section->mata_kuliah_id,
            'code' => 'CPMK-ESSAY',
            'description' => 'CPMK Essay Test',
            'threshold' => 65,
        ]);

        $essay1 = QuizQuestion::canonicalizeQuestion([
            'id' => 'essay-q1',
            'type' => 'uraian',
            'prompt' => 'Jelaskan konsep OOP 1.',
            'points' => 50,
            'cpmk' => 'CPMK-ESSAY',
        ]);
        $essay2 = QuizQuestion::canonicalizeQuestion([
            'id' => 'essay-q2',
            'type' => 'uraian',
            'prompt' => 'Jelaskan konsep OOP 2.',
            'points' => 50,
            'cpmk' => 'CPMK-ESSAY',
        ]);

        $quiz = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'KUIS-ESSAY-BATCH',
            'name' => 'Kuis Dua Esai',
            'type' => 'kuis',
            'final_weight' => 20,
            'status' => 'published',
            'learning_payload' => ['questions' => [$essay1, $essay2]],
        ]);
        $quiz->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $this->actingAs($student)->post(route('mahasiswa.course.submit', [$section, $quiz]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                'essay-q1' => [
                    'question_id' => 'essay-q1',
                    'text' => 'Jawaban esai pertama.',
                ],
                'essay-q2' => [
                    'question_id' => 'essay-q2',
                    'text' => 'Jawaban esai kedua.',
                ],
            ],
        ])->assertRedirect(route('mahasiswa.quiz.room', [$section, $quiz]));

        $submission = Submission::where('assessment_id', $quiz->id)->firstOrFail();
        $ans1 = $submission->answers()->where('question_id', 'essay-q1')->firstOrFail();
        $ans2 = $submission->answers()->where('question_id', 'essay-q2')->firstOrFail();

        // 1. Check UI contains single "Simpan Nilai Esai" button and no individual "Simpan skor" buttons
        $res = $this->actingAs($lecturer)->get(route('dosen.penilaian.asesmen.nilai', [$section, $quiz]));
        $res->assertOk();
        $res->assertSee('Simpan Nilai Esai');
        $res->assertDontSee('Simpan skor');

        // 2. Test validation failure when score exceeds max
        $this->actingAs($lecturer)->post(route('dosen.penilaian.asesmen.student.essay_scores', [$section, $quiz, $student]), [
            'scores' => [
                $ans1->id => 60, // max is 50
                $ans2->id => 40,
            ],
        ])->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$section, $quiz]))
          ->assertSessionHasErrors(['scores']);

        // 3. Test successful batch save of all essay scores at once
        $this->actingAs($lecturer)->post(route('dosen.penilaian.asesmen.student.essay_scores', [$section, $quiz, $student]), [
            'scores' => [
                $ans1->id => 45,
                $ans2->id => 35,
            ],
        ])->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$section, $quiz]))
          ->assertSessionHasNoErrors()
          ->assertSessionHas('notice');

        // Verify both answers were updated
        $this->assertSame('45.00', $ans1->fresh()->earned_score);
        $this->assertSame('35.00', $ans2->fresh()->earned_score);
        $this->assertSame('manual_graded', $ans1->fresh()->grading_status);
        $this->assertSame('manual_graded', $ans2->fresh()->grading_status);

        // Verify overall quiz score was calculated and published (45 + 35 = 80)
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $student->id,
            'score' => 80,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);
    }

    private function academicContext(): array
    {
        $this->seed(RoleSeeder::class);
        $lecturerRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $studentRole = Role::where('name', Role::MAHASISWA)->firstOrFail();
        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $course = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data', 'sks' => 3]);
        $lecturer = User::create([
            'name' => 'Dosen Uji',
            'email' => 'dosen-final@test.local',
            'password' => 'password',
            'role_id' => $lecturerRole->id,
            'nim_nidn' => 'D001',
        ]);
        $student = User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'mahasiswa-final@test.local',
            'password' => 'password',
            'role_id' => $studentRole->id,
            'nim_nidn' => 'M001',
        ]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $course->id,
            'semester_id' => $semester->id,
            'dosen_id' => $lecturer->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'FINAL001',
        ]);
        $section->students()->attach($student->id);

        return [$lecturer, $student, $section, $course];
    }
}
