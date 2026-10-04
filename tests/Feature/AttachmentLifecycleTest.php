<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttachmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $student;

    private ClassSection $section;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $studentRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknologi Informasi']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026', 'is_active' => true]);
        $mk = MataKuliah::create(['code' => 'TI101', 'name' => 'Algoritma', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $this->dosen = User::factory()->create(['role_id' => $dosenRole->id, 'prodi_id' => $prodi->id]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'prodi_id' => $prodi->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'TI-A',
        ]);
        $this->section->students()->attach($this->student->id, ['status' => 'enrolled']);

        $this->assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'ASM-01',
            'name' => 'Tugas Praktikum',
            'type' => 'tugas',
            'final_weight' => 10,
        ]);
    }

    public function test_deleting_assessment_removes_physical_attachment_after_commit(): void
    {
        $filePath = 'learning-preview/assessment_doc.pdf';
        Storage::disk('local')->put($filePath, 'dummy assessment content');

        $attachment = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->dosen->id,
            'class_section_id' => $this->section->id,
            'assessment_id' => $this->assessment->id,
            'path' => $filePath,
            'name' => 'assessment_doc.pdf',
            'mime' => 'application/pdf',
            'size' => 24,
        ]);

        $this->assertTrue(Storage::disk('local')->exists($filePath));

        // Call destroyItem
        $this->actingAs($this->dosen)
            ->delete(route('dosen.item.destroy', [$this->section->id, $this->assessment->id]))
            ->assertRedirect(route('dosen.course.show', $this->section->id));

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }

    public function test_shared_reference_protection_prevents_file_deletion_if_still_referenced(): void
    {
        $sharedPath = 'learning-preview/shared_module.pdf';
        Storage::disk('local')->put($sharedPath, 'shared content');

        // Attachment 1 attached to assessment
        $att1 = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->dosen->id,
            'class_section_id' => $this->section->id,
            'assessment_id' => $this->assessment->id,
            'path' => $sharedPath,
            'name' => 'shared_module.pdf',
            'mime' => 'application/pdf',
            'size' => 14,
        ]);

        // Attachment 2 attached to another record referencing same file
        $att2 = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->dosen->id,
            'class_section_id' => $this->section->id,
            'path' => $sharedPath,
            'name' => 'shared_module_copy.pdf',
            'mime' => 'application/pdf',
            'size' => 14,
        ]);

        // Destroy assessment
        $this->actingAs($this->dosen)
            ->delete(route('dosen.item.destroy', [$this->section->id, $this->assessment->id]));

        // att1 is gone, but att2 remains, so physical file MUST NOT be deleted
        $this->assertDatabaseMissing('attachments', ['id' => $att1->id]);
        $this->assertDatabaseHas('attachments', ['id' => $att2->id]);
        $this->assertTrue(Storage::disk('local')->exists($sharedPath));
    }

    public function test_cancelling_submission_removes_physical_attachment_after_commit(): void
    {
        $submission = Submission::create([
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
            'mahasiswa_id' => $this->student->id,
            'submitted_at' => now(),
        ]);

        $subFile = 'learning-preview/student_answer.zip';
        Storage::disk('local')->put($subFile, 'zip content');

        $attachment = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'class_section_id' => $this->section->id,
            'assessment_id' => $this->assessment->id,
            'submission_id' => $submission->id,
            'path' => $subFile,
            'name' => 'student_answer.zip',
            'mime' => 'application/zip',
            'size' => 11,
        ]);

        $this->assertTrue(Storage::disk('local')->exists($subFile));

        $this->actingAs($this->student)
            ->post(route('mahasiswa.course.submission.cancel', [$this->section->id, $this->assessment->id]))
            ->assertRedirect(route('mahasiswa.course.item', [$this->section->id, $this->assessment->id]));

        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        $this->assertFalse(Storage::disk('local')->exists($subFile));
    }

    public function test_scan_attachments_command_detects_orphans_and_missing_files(): void
    {
        // 1. Valid registered file
        $validFile = 'learning-preview/valid_file.pdf';
        Storage::disk('local')->put($validFile, 'valid content');
        Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->dosen->id,
            'class_section_id' => $this->section->id,
            'path' => $validFile,
            'name' => 'valid_file.pdf',
            'mime' => 'application/pdf',
            'size' => 10,
        ]);

        // 2. Orphan file
        $orphanFile = 'learning-preview/orphan_old.pdf';
        Storage::disk('local')->put($orphanFile, 'orphan content');
        touch(Storage::disk('local')->path($orphanFile), now()->subHours(48)->timestamp);

        // Dry run scan
        $this->artisan('sale:scan-attachments', ['--dry-run' => true, '--grace-hours' => 24])
            ->assertExitCode(0);

        // File should still exist in dry-run
        $this->assertTrue(Storage::disk('local')->exists($orphanFile));

        // Active cleanup scan
        $this->artisan('sale:scan-attachments', ['--delete-orphans' => true, '--grace-hours' => 24])
            ->assertExitCode(0);

        // Orphan file deleted, valid file preserved
        $this->assertFalse(Storage::disk('local')->exists($orphanFile));
        $this->assertTrue(Storage::disk('local')->exists($validFile));
    }

    public function test_scan_command_cleans_stale_pending_uploads_but_keeps_fresh_pending_uploads(): void
    {
        $stalePath = 'learning-preview/stale-pending.pdf';
        $freshPath = 'learning-preview/fresh-pending.pdf';
        Storage::disk('local')->put($stalePath, 'stale');
        Storage::disk('local')->put($freshPath, 'fresh');

        $stale = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'path' => $stalePath,
            'name' => 'stale-pending.pdf',
            'mime' => 'application/pdf',
            'size' => 5,
            'status' => Attachment::STATUS_PENDING,
        ]);
        $stale->forceFill(['created_at' => now()->subHours(48)])->saveQuietly();

        $fresh = Attachment::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'path' => $freshPath,
            'name' => 'fresh-pending.pdf',
            'mime' => 'application/pdf',
            'size' => 5,
            'status' => Attachment::STATUS_PENDING,
        ]);

        $this->artisan('sale:scan-attachments', ['--delete-orphans' => true, '--grace-hours' => 24])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('attachments', ['id' => $stale->id]);
        $this->assertFalse(Storage::disk('local')->exists($stalePath));
        $this->assertDatabaseHas('attachments', ['id' => $fresh->id]);
        $this->assertTrue(Storage::disk('local')->exists($freshPath));
    }
}
