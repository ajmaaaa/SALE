<?php

namespace Tests\Feature;

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
