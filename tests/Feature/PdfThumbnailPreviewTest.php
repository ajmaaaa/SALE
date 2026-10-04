<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfThumbnailPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $this->admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-pdf-test@test.local',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'nim_nidn' => 'ADM_PDF',
            'is_active' => true,
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        Attachment::create([
            'uuid' => '00000000-0000-4000-8000-000000000002',
            'user_id' => $this->admin->id,
            'path' => 'testing/sample-pdf.pdf',
            'name' => 'sample-pdf.pdf',
            'mime' => 'application/pdf',
            'size' => 200,
        ]);
        Attachment::create([
            'uuid' => '00000000-0000-4000-8000-000000000003',
            'user_id' => $this->admin->id,
            'path' => 'testing/sample-img.png',
            'name' => 'sample-img.png',
            'mime' => 'image/png',
            'size' => 100,
        ]);

        Storage::disk('local')->put('testing/sample-pdf.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 100 100]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF\n");
        Storage::disk('local')->put('testing/sample-img.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    }

    public function test_pdf_thumbnail_is_generated_and_cached_on_server(): void
    {
        $id = '00000000-0000-4000-8000-000000000002';
        $response = $this->actingAs($this->admin)->get(route('preview.file', ['file' => $id, 'thumbnail' => 1]));

        $response->assertOk();
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=604800', (string) $response->headers->get('Cache-Control'));
    }

    public function test_image_file_returns_image_directly_when_thumbnail_requested(): void
    {
        $id = '00000000-0000-4000-8000-000000000003';
        $response = $this->actingAs($this->admin)->get(route('preview.file', ['file' => $id, 'thumbnail' => 1]));

        $response->assertOk();
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
    }

    public function test_pdf_inline_preview_has_caching_headers(): void
    {
        $id = '00000000-0000-4000-8000-000000000002';
        $response = $this->actingAs($this->admin)->get(route('preview.file', ['file' => $id, 'inline' => 1]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=86400', (string) $response->headers->get('Cache-Control'));
    }
}
