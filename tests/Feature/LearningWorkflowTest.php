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
        $this->get('/dosen/penilaian')->assertSee('Hasil evaluasi: navigasi sulit ditemukan.');
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
}
