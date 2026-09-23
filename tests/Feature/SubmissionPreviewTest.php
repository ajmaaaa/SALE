<?php

namespace Tests\Feature;

use App\Support\SubmissionPreview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionPreviewTest extends TestCase
{
    public function test_uploaded_files_are_scoped_to_student_course_and_component(): void
    {
        Storage::fake('local');
        $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Andi Pratama', 'number' => '2024081001', 'role' => 'mahasiswa', 'email' => 'andi@example.test']])
            ->post('/mahasiswa/course/2/item/4/submission', [
                'files' => [UploadedFile::fake()->create('laporan.pdf', 10, 'application/pdf')],
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('2024081001', session('learning.submissions.4.student_number'));
        $files = SubmissionPreview::files(2, 'tugas', '2024081001');
        $this->assertCount(1, $files);
        $this->assertSame('laporan.pdf', $files[0]['name']);
        $this->assertSame([], SubmissionPreview::files(1, 'tugas', '2024081001'));
        $this->assertSame([], SubmissionPreview::files(2, 'uas', '2024081001'));
        $this->assertSame([], SubmissionPreview::files(2, 'tugas', '2024081002'));

        session()->forget('learning.submissions.4.student_number');
        $this->assertSame([], SubmissionPreview::files(2, 'tugas', '2024081001'));
    }

    public function test_pdf_preview_download_and_session_isolation(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('learning-preview/document.pdf', '%PDF-1.4 test');
        $id = '00000000-0000-4000-8000-000000000001';
        $this->withSession(["learning.files.$id" => [
            'path' => 'learning-preview/document.pdf', 'name' => 'document.pdf', 'mime' => 'application/pdf',
        ]]);
        $this->get(route('preview.file', ['file' => $id, 'inline' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Disposition', 'inline; filename=document.pdf');
        $this->get(route('preview.file', ['file' => $id, 'download' => 1]))->assertDownload('document.pdf');
        session()->forget('learning.files');
        $this->get(route('preview.file', ['file' => $id, 'inline' => 1]))->assertNotFound();
    }

    public function test_unsupported_formats_are_never_served_inline(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('learning-preview/document.html', '<script>alert(1)</script>');
        $id = '00000000-0000-4000-8000-000000000002';
        $this->withSession(["learning.files.$id" => [
            'path' => 'learning-preview/document.html', 'name' => 'document.html', 'mime' => 'text/html',
        ]]);
        $this->get(route('preview.file', ['file' => $id, 'inline' => 1]))
            ->assertDownload('document.html')->assertHeader('X-Frame-Options', 'DENY');
    }
}
