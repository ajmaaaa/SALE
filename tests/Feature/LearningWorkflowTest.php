<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningWorkflowTest extends TestCase
{
    public function test_courses_have_distinct_content_and_items_cannot_cross_courses(): void
    {
        $this->get('/mahasiswa/course/2')->assertOk()->assertSee('Laporan Evaluasi Usability')->assertDontSee('Praktikum Binary Tree');
        $this->get('/mahasiswa/course/2/item/1')->assertNotFound();
        $this->get('/mahasiswa/grade')->assertRedirect('/mahasiswa/assignment?tab=nilai');
        $this->get('/mahasiswa/assignment?course=2')->assertSee('Laporan Evaluasi Usability')->assertDontSee('Praktikum Binary Tree');
    }

    public function test_lecturer_can_create_course_and_material_with_session_scoped_file(): void
    {
        Storage::fake('local');
        $this->post('/dosen/course', ['code' => 'IF300', 'title' => 'Course baru', 'description' => 'Deskripsi kelas', 'lecturer' => 'Dosen contoh'])->assertSessionHasNoErrors()->assertRedirect('/dosen/course/5');
        $this->post('/dosen/course/5/items', ['type' => 'materi', 'title' => 'Materi baru', 'module' => 'Minggu 1', 'body' => 'Baca lampiran berikut.', 'question_type' => 'uraian', 'cpmk' => 'Memahami konsep.', 'attachments' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/pdf')]])->assertSessionHasNoErrors()->assertRedirect('/dosen/course/5');
        $this->get('/mahasiswa/course/5/item/7')->assertOk()->assertSee('materi.pdf');
        $files = session('learning.files');
        $id = array_key_first($files);
        $this->get('/preview/files/'.$id)->assertOk();
        session()->forget('learning.files');
        $this->get('/preview/files/'.$id)->assertNotFound();
    }

    public function test_discussion_is_scoped_and_user_text_is_escaped(): void
    {
        $this->post('/mahasiswa/course/1/item/3/discussion', ['message' => '<script>alert(1)</script>'])->assertRedirect();
        $this->get('/mahasiswa/course/1/item/3')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/mahasiswa/course/1/item/2')->assertDontSee('alert(1)');
    }

    public function test_submission_requires_content_and_is_visible_for_review(): void
    {
        $this->post('/mahasiswa/course/2/item/4/submission', [])->assertSessionHasErrors('answer');
        $this->post('/mahasiswa/course/2/item/4/submission', ['link' => 'javascript:alert(1)'])->assertSessionHasErrors('link');
        $this->post('/mahasiswa/course/2/item/4/submission', ['answer' => 'Hasil evaluasi: navigasi sulit ditemukan.'])->assertRedirect('/mahasiswa/course/2/item/4');
        $this->get('/mahasiswa/course/2/item/4')->assertSee('Sudah dikumpulkan');
        $this->get('/dosen/penilaian')->assertSee('Kelas yang Saya Ajar')->assertSee('Buka Ruang Penilaian');
        $this->get('/dosen/penilaian?room=1&course=2&type=uts')->assertOk()->assertSee('Ujian Tengah Semester (UTS)');
        $this->post('/mahasiswa/course/1/item/2/submission', ['answer' => 'x'])->assertNotFound();
    }

    public function test_new_coding_tasks_get_their_own_editor_template(): void
    {
        $this->post('/dosen/course/1/items', ['type' => 'coding', 'title' => 'Kode baru', 'module' => 'Minggu 4', 'body' => 'Buat fungsi.', 'question_type' => 'coding', 'cpmk' => 'Membuat fungsi.', 'formats' => ['text']])->assertRedirect();
        $this->get('/mahasiswa/assignment/7/code')->assertOk()->assertSee('Tulis jawaban Python kamu di sini')->assertDontSee('class Node:');
        $this->get('/mahasiswa/assignment/1/code')->assertSee('class Node:')->assertDontSee('@else');
    }

    public function test_resubmission_preserves_files_until_explicitly_removed(): void
    {
        Storage::fake('local');
        $this->post('/mahasiswa/course/2/item/4/submission', ['files' => [UploadedFile::fake()->create('jawaban.pdf', 10, 'application/pdf')]])->assertRedirect();
        $files = session('learning.submissions.4.files');
        $this->post('/mahasiswa/course/2/item/4/submission', ['answer' => 'Catatan tambahan'])->assertRedirect();
        $this->assertSame($files, session('learning.submissions.4.files'));
        $this->post('/mahasiswa/course/2/item/4/submission', ['keep_files' => ['00000000-0000-4000-8000-000000000000']])->assertUnprocessable();
        $this->post('/mahasiswa/course/2/item/4/submission', ['replace_files' => 1, 'answer' => 'Jawaban revisi'])->assertRedirect();
        $this->assertSame([], session('learning.submissions.4.files'));
    }

    public function test_numeric_zero_is_a_valid_choice_and_duplicate_choices_are_rejected(): void
    {
        $base = ['type' => 'kuis', 'title' => 'Angka', 'module' => 'Minggu 1', 'body' => 'Pilih angka.', 'question_type' => 'pilihan', 'cpmk' => 'Konsep angka.', 'formats' => ['text']];
        $this->post('/dosen/course/1/items', $base + ['options' => "0\n1"])->assertRedirect();
        $this->get('/mahasiswa/course/1/item/7')->assertSee('value="0"', false);
        $this->post('/mahasiswa/course/1/item/7/submission', ['choices' => ['0']])->assertRedirect();
        $this->post('/dosen/course/1/items', $base + ['options' => "Sama\nSama"])->assertSessionHasErrors('options');
    }

    public function test_upload_and_choice_validation(): void
    {
        Storage::fake('local');
        $this->post('/dosen/course', ['code' => 'IF500', 'title' => 'Test', 'description' => 'Test', 'lecturer' => 'Test', 'cover' => UploadedFile::fake()->create('bad.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('cover');
        $base = ['type' => 'kuis', 'title' => 'Kuis AKM', 'module' => 'Minggu 1', 'body' => 'Pilih jawaban.', 'question_type' => 'kompleks', 'cpmk' => 'Konsep', 'formats' => ['text']];
        $this->post('/dosen/course/1/items', $base + ['options' => 'Satu'])->assertSessionHasErrors('options');
        $this->post('/dosen/course/1/items', $base + ['options' => "Pertama\nKedua"])->assertRedirect('/dosen/course/1');
        $this->post('/mahasiswa/course/1/item/7/submission', ['choices' => ['Tidak ada']])->assertUnprocessable();
        $this->post('/mahasiswa/course/1/item/7/submission', ['choices' => ['Pertama', 'Kedua']])->assertRedirect('/mahasiswa/course/1/item/7');
        $this->post('/mahasiswa/course/1/item/7/submission', ['files' => [UploadedFile::fake()->create('answer.pdf', 10, 'application/pdf')]])->assertUnprocessable();
    }

    public function test_matching_and_boolean_question_types(): void
    {
        // 1. Create a task with mixed questions including benar_salah and mencocokkan
        $this->post('/dosen/course/1/items', [
            'type' => 'tugas',
            'title' => 'Tugas Struktur Data Lanjutan',
            'module' => 'Minggu 5',
            'body' => 'Kerjakan soal-soal berikut.',
            'question_type' => 'uraian',
            'cpmk' => 'Memahami konsep.',
            'formats' => ['text'],
            'questions' => [
                [
                    'type' => 'benar_salah',
                    'prompt' => 'Binary Search Tree selalu seimbang secara alami.',
                    'points' => 20,
                    'cpmk' => 'CPMK-01',
                    'options' => null,
                ],
                [
                    'type' => 'mencocokkan',
                    'prompt' => 'Pasangkan istilah struktur data dengan karakteristiknya.',
                    'points' => 30,
                    'cpmk' => 'CPMK-02',
                    'options' => "Stack = LIFO (Last In First Out)\nQueue = FIFO (First In First Out)",
                ],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $itemId = max(array_keys(session('learning.items')));

        // 2. Student views the item
        $response = $this->get("/mahasiswa/course/1/item/{$itemId}");
        $response->assertSee('Salah');
        $response->assertSee('Stack');
        $response->assertSee('LIFO (Last In First Out)');

        // 3. Student submits answers
        $this->post("/mahasiswa/course/1/item/{$itemId}/submission", [
            'question_answers' => [
                ['boolean_choice' => 'Salah'],
                ['matching' => ['0' => 'LIFO (Last In First Out)', '1' => 'FIFO (First In First Out)']],
            ],
        ])->assertRedirect("/mahasiswa/course/1/item/{$itemId}");

        // 4. Discussion index navigation
        $discResponse = $this->get('/mahasiswa/discussion');
        $discResponse->assertOk();
        $discResponse->assertDontSee('Buka Diskusi →');
        $discResponse->assertDontSee('<th>Aksi</th>', false);
    }

    public function test_late_submission_policy_is_enforced(): void
    {
        // 1. Disallow late submission
        $this->post('/dosen/course/1/items', [
            'type' => 'tugas',
            'title' => 'Tugas Ketat Waktu',
            'module' => 'Minggu 6',
            'body' => 'Kumpulkan sebelum tenggat.',
            'due' => '2020-01-01T23:59',
            'allow_late' => '0',
            'question_type' => 'uraian',
            'cpmk' => 'Memahami tenggat.',
            'formats' => ['text'],
        ])->assertRedirect();

        $lockedId = max(array_keys(session('learning.items')));
        $this->get("/mahasiswa/course/1/item/{$lockedId}")
            ->assertSee('Pengumpulan Ditutup')
            ->assertSee('Terlambat');

        // Attempt submission should fail
        $this->post("/mahasiswa/course/1/item/{$lockedId}/submission", [
            'answer' => 'Jawaban terlambat saya.',
        ])->assertSessionHasErrors('answer');

        // 2. Allow late submission
        $this->post('/dosen/course/1/items', [
            'type' => 'tugas',
            'title' => 'Tugas Fleksibel',
            'module' => 'Minggu 6',
            'body' => 'Boleh terlambat.',
            'due' => '2020-01-01T23:59',
            'allow_late' => '1',
            'question_type' => 'uraian',
            'cpmk' => 'Memahami fleksibilitas.',
            'formats' => ['text'],
        ])->assertRedirect();

        $flexibleId = max(array_keys(session('learning.items')));
        $this->post("/mahasiswa/course/1/item/{$flexibleId}/submission", [
            'answer' => 'Jawaban fleksibel terlambat.',
        ])->assertSessionHasNoErrors()->assertRedirect("/mahasiswa/course/1/item/{$flexibleId}");
    }

    public function test_grades_and_discussions_ui(): void
    {
        $gradesResponse = $this->get(route('mahasiswa.nilai'));
        $gradesResponse->assertOk()
            ->assertDontSee('Total Beban SKS Semester Ini')
            ->assertSee('Daftar Mata Kuliah Semester');

        $assignmentsResponse = $this->get('/mahasiswa/assignment');
        $assignmentsResponse->assertOk()
            ->assertSee('Belum dikumpulkan')
            ->assertSee('text-rose-600', false)
            ->assertDontSee('bg-rose-100', false);

        $discResponse = $this->get('/mahasiswa/discussion');
        $discResponse->assertOk()
            ->assertSee('bg-blue-600', false)
            ->assertDontSee('bg-brand-soft text-brand', false);
    }

    public function test_lecturer_view_and_quiz_action(): void
    {
        // 1. Dosen view has no student submission and shows management panel
        session(['auth_user' => ['id' => 2, 'name' => 'Dr. Budi Santoso', 'role' => 'dosen']]);
        $lecturerView = $this->get('/mahasiswa/course/1/item/1');
        $lecturerView->assertOk()
            ->assertSee('Pengelolaan Pengampu')
            ->assertSee('Lihat &amp; Nilai Jawaban Mahasiswa', false)
            ->assertDontSee('+ Tambah atau buat')
            ->assertDontSee('Kumpulkan Tugas');

        // 2. Student viewing quiz sees 'Kerjakan Kuis'
        session(['auth_user' => ['id' => 1, 'name' => 'Ahmad Maulana', 'role' => 'mahasiswa']]);
        $quizView = $this->get('/mahasiswa/course/3/item/5');
        $quizView->assertOk()
            ->assertSee('Kerjakan Kuis')
            ->assertDontSee('Kumpulkan Tugas');
    }

    public function test_code_workbench_linux_layout(): void
    {
        $codeView = $this->get('/mahasiswa/assignment/1/code');
        $codeView->assertOk()
            ->assertSee('Output Python')
            ->assertSee('data-stop-code', false)
            ->assertSee('data-runtime-url', false)
            ->assertSee('Petunjuk Pengerjaan')
            ->assertSee('Lumina AI');
    }

    public function test_quiz_room_cbt_and_duration_settings(): void
    {
        // 1. Dosen creates quiz with duration setting
        session(['auth_user' => ['id' => 2, 'name' => 'Dr. Budi Santoso', 'role' => 'dosen']]);
        $this->post('/dosen/course/1/items', [
            'type' => 'kuis',
            'title' => 'Kuis Algoritma Pemrograman',
            'module' => 'Minggu 7',
            'body' => 'Kerjakan kuis berikut secara mandiri.',
            'question_type' => 'uraian',
            'cpmk' => 'Pemahaman Algoritma',
            'formats' => ['text'],
            'duration_mode' => 'enabled',
            'duration_minutes' => 45,
            'questions' => [
                [
                    'type' => 'mencocokkan',
                    'prompt' => 'Pasangkan konsep berikut.',
                    'points' => 50,
                    'cpmk' => 'CPMK-01',
                    'options' => "QuickSort = O(n log n)\nBubbleSort = O(n^2)",
                ],
                [
                    'type' => 'coding',
                    'prompt' => 'Tuliskan fungsi pencarian binary search.',
                    'points' => 50,
                    'cpmk' => 'CPMK-02',
                    'options' => 'def binary_search(): pass',
                ],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect('/dosen/course/1');

        $quizId = max(array_keys(session('learning.items')));
        $savedItem = session('learning.items')[$quizId];
        $this->assertTrue($savedItem['duration_enabled']);
        $this->assertEquals(45, $savedItem['duration_minutes']);

        // 2. Student enters the dedicated Quiz Room
        session(['auth_user' => ['id' => 1, 'name' => 'Ahmad Maulana', 'role' => 'mahasiswa']]);
        $room = $this->get("/mahasiswa/course/1/item/{$quizId}/quiz");
        $room->assertOk()
            ->assertSee('Kuis Algoritma Pemrograman')
            ->assertSee('Sebelumnya')
            ->assertSee('Selanjutnya')
            ->assertSee('Kumpulkan Kuis')
            ->assertSee('QuickSort')
            ->assertSee('sale@sandbox')
            ->assertDontSee('Kumpulkan Tugas')
            ->assertDontSee('+ Tambah atau buat');

        // 3. Student submits quiz answers
        $this->post("/mahasiswa/course/1/item/{$quizId}/submission", [
            'question_answers' => [
                0 => ['matching' => [0 => 'O(n log n)', 1 => 'O(n^2)']],
                1 => ['text' => 'def binary_search(arr, x): return 0'],
            ],
        ])->assertRedirect("/mahasiswa/course/1/item/{$quizId}");

        $submission = session("learning.submissions.{$quizId}");
        $this->assertNotNull($submission);
        $this->assertEquals('O(n log n)', $submission['question_answers'][0]['matching'][0]);
    }
}
