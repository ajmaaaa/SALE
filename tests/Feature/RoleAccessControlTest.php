<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    public function test_guest_is_redirected_to_login_on_protected_routes(): void
    {
        $protectedRoutes = [
            '/dosen/dashboard',
            '/dosen/course',
            '/dosen/penilaian-kelas',
            '/admin/dashboard',
            '/admin/pengguna',
            '/kaprodi/monitoring/cpmk',
            '/kaprodi/monitoring/cpl',
            '/mahasiswa/dashboard',
            '/mahasiswa/course',
            '/admin-prodi/dashboard',
        ];

        foreach ($protectedRoutes as $uri) {
            $response = $this->get($uri);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_mahasiswa_cannot_access_other_roles(): void
    {
        $session = ['auth_user' => ['id' => 1, 'role' => 'mahasiswa', 'name' => 'Ahmad', 'email' => 'ahmad@example.test']];

        $forbiddenRoutes = [
            '/dosen/dashboard',
            '/dosen/course',
            '/dosen/penilaian-kelas',
            '/admin/dashboard',
            '/admin/pengguna',
            '/kaprodi/monitoring/cpmk',
            '/admin-prodi/dashboard',
        ];

        foreach ($forbiddenRoutes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(403);
        }

        // Allowed route
        $okResponse = $this->withSession($session)->get('/mahasiswa/dashboard');
        $okResponse->assertOk();
    }

    public function test_dosen_cannot_access_other_roles(): void
    {
        $session = ['auth_user' => ['id' => 2, 'role' => 'dosen', 'name' => 'Budi Santoso', 'email' => 'budi@example.test']];

        $forbiddenRoutes = [
            '/mahasiswa/dashboard',
            '/mahasiswa/course',
            '/admin/dashboard',
            '/admin/pengguna',
            '/kaprodi/monitoring/cpmk',
            '/admin-prodi/dashboard',
        ];

        foreach ($forbiddenRoutes as $uri) {
            $response = $this->withSession($session)->get($uri);
            $response->assertStatus(403);
        }

        // Allowed route
        $okResponse = $this->withSession($session)->get('/dosen/dashboard');
        $okResponse->assertOk();
    }

    public function test_deleted_routes_return_404(): void
    {
        $dosenSession = ['auth_user' => ['id' => 2, 'role' => 'dosen', 'name' => 'Budi', 'email' => 'budi@example.test']];

        // 1. /dosen/course/1/akademik
        $res1 = $this->withSession($dosenSession)->get('/dosen/course/1/akademik');
        $res1->assertNotFound();

        // 2. /dosen/penilaian?room=1&course=1&type=uts
        $res2 = $this->withSession($dosenSession)->get('/dosen/penilaian?room=1&course=1&type=uts');
        $res2->assertNotFound();

        // 3. /dosen/course/create
        $res3 = $this->withSession($dosenSession)->get('/dosen/course/create');
        $res3->assertNotFound();
    }

    public function test_dosen_course_page_does_not_have_tambah_course_button(): void
    {
        $dosenSession = ['auth_user' => ['id' => 2, 'role' => 'dosen', 'name' => 'Budi', 'email' => 'budi@example.test']];

        $response = $this->withSession($dosenSession)->get('/dosen/course');
        $response->assertOk();
        $response->assertDontSee('+ Tambah course');
        $response->assertDontSee('Tambah course');
    }
}
