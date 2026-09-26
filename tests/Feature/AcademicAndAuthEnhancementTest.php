<?php

namespace Tests\Feature;

use App\Support\AdminPreview;
use Tests\TestCase;

class AcademicAndAuthEnhancementTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('Masuk');
        $response->assertSee('Email atau NIM / NIDN');
    }

    public function test_login_with_nim_and_email_credentials(): void
    {
        $res1 = $this->post('/login', ['login_id' => '231011401234', 'password' => 'password']);
        $res1->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertEquals(1, session('auth_user.id'));

        $res2 = $this->post('/login', ['login_id' => 'budi@example.test', 'password' => 'password']);
        $res2->assertRedirect(route('dosen.dashboard'));
        $this->assertEquals(2, session('auth_user.id'));
    }

    public function test_one_click_persona_login_and_logout(): void
    {
        // Login as Dosen
        $loginResponse = $this->post('/login', ['persona_id' => 2]);
        $loginResponse->assertRedirect(route('dosen.dashboard'));
        $this->assertEquals(2, session('auth_user.id'));
        $this->assertEquals('dosen', session('auth_user.role'));

        // Switch role to admin
        $switchResponse = $this->post('/switch-role/admin');
        $switchResponse->assertRedirect(route('admin.page', 'dashboard'));
        $this->assertEquals('admin', session('auth_user.role'));

        // Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect(route('login'));
        $this->assertNull(session('auth_user'));
    }

    public function test_admin_users_table_shows_nim_column_and_supports_bulk_import(): void
    {
        $this->post('/switch-role/admin')->assertRedirect();

        $response = $this->get('/admin/pengguna');
        $response->assertOk();
        $response->assertSee('NIM / NIDN / NIP');
        $response->assertSee('231011401234');

        // Test bulk import
        $bulkInput = "231011409001, Rani Kartika, rani@example.test, mahasiswa, aktif\n".
                     '231011409002, Doni Saputra, doni@example.test, mahasiswa, aktif';

        $postResponse = $this->post('/admin/pengguna/bulk', [
            'raw_users' => $bulkInput,
        ]);

        $postResponse->assertRedirect('/admin/pengguna');
        $this->assertDatabaseHasOrSessionUsers('231011409001', 'Rani Kartika');
        $this->assertDatabaseHasOrSessionUsers('231011409002', 'Doni Saputra');
    }

    public function test_lecturer_academic_settings_url_is_inaccessible(): void
    {
        $this->post('/switch-role/dosen')->assertRedirect();

        $response = $this->get('/dosen/course/1/akademik');
        $response->assertNotFound();
    }

    public function test_lecturer_gradebook_url_is_inaccessible(): void
    {
        $this->post('/switch-role/dosen')->assertRedirect();

        $response = $this->get('/dosen/gradebook');
        $response->assertNotFound();
    }

    public function test_student_grades_page_renders_transparent_components(): void
    {
        $this->post('/switch-role/mahasiswa')->assertRedirect();

        $response = $this->get(route('mahasiswa.nilai'));
        $response->assertOk();
        $response->assertSee('Transkrip Nilai');
        $response->assertSee('Rincian Komponen Nilai');
        $response->assertDontSee('Capaian CPMK');
    }

    private function assertDatabaseHasOrSessionUsers(string $number, string $name): void
    {
        $users = AdminPreview::users();
        $found = collect($users)->first(fn ($u) => $u['number'] === $number && $u['name'] === $name);
        $this->assertNotNull($found, "User with number {$number} and name {$name} not found in session.");
    }
}
