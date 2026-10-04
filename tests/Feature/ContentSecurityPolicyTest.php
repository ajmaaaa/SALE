<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Models\Attachment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $studentRole = Role::where('name', Role::MAHASISWA)->firstOrFail();
        $this->user = User::create([
            'name' => 'Student CSP Test',
            'email' => 'student-csp@test.local',
            'password' => 'password',
            'role_id' => $studentRole->id,
            'nim_nidn' => 'CSP_001',
            'is_active' => true,
        ]);
        $this->user->roles()->sync([$studentRole->id]);
    }

    public function test_csp_header_contains_all_required_directives(): void
    {
        $response = $this->actingAs($this->user)->get(route('mahasiswa.dashboard'));
        $response->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp);

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+' blob:/", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("script-src-attr 'unsafe-inline'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https:", $csp);
        $this->assertStringContainsString("img-src 'self' data: blob: https:", $csp);
        $this->assertStringContainsString("font-src 'self' data: https:", $csp);
        $this->assertStringContainsString("connect-src 'self' blob:", $csp);
        $this->assertStringNotContainsString("connect-src 'self' ws: wss: https:", $csp);
        $this->assertStringContainsString("worker-src 'self' blob:", $csp);
        $this->assertStringContainsString("media-src 'self' blob: https:", $csp);
        $this->assertStringContainsString("frame-src 'self' blob: data: https://www.youtube-nocookie.com https://www.youtube.com", $csp);

        // unsafe-eval must not be used
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);

        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringContainsString('<script nonce="'.($matches[1] ?? '').'"', (string) $response->getContent());
    }

    public function test_pdf_preview_allows_self_frame_ancestors(): void
    {
        Storage::fake('local');
        $uuid = '00000000-0000-4000-8000-000000000099';
        $path = 'testing/preview-doc.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 100 100]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF\n");

        Attachment::create([
            'uuid' => $uuid,
            'user_id' => $this->user->id,
            'path' => $path,
            'name' => 'preview-doc.pdf',
            'mime' => 'application/pdf',
            'size' => 200,
        ]);

        $response = $this->actingAs($this->user)->get(route('preview.file', ['file' => $uuid, 'inline' => 1]));
        $response->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_csp_report_only_mode_when_configured(): void
    {
        config(['security.csp_report_only' => true]);

        try {
            $response = $this->actingAs($this->user)->get(route('mahasiswa.dashboard'));
            $response->assertOk();

            $this->assertNull($response->headers->get('Content-Security-Policy'));
            $reportOnly = (string) $response->headers->get('Content-Security-Policy-Report-Only');
            $this->assertNotEmpty($reportOnly);
            $this->assertStringContainsString("default-src 'self'", $reportOnly);
        } finally {
            config(['security.csp_report_only' => false]);
        }
    }

    public function test_security_middleware_does_not_grant_nonce_to_untrusted_response_markup(): void
    {
        $request = Request::create('/untrusted-csp-test', 'GET');
        $response = app(SecurityHeaders::class)->handle(
            $request,
            fn () => response('<html><body><script id="injected">alert(1)</script></body></html>')
        );

        $this->assertStringContainsString('<script id="injected">', (string) $response->getContent());
        $this->assertStringNotContainsString('<script nonce=', (string) $response->getContent());
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[^']+' blob:/", (string) $response->headers->get('Content-Security-Policy'));
    }
}
