<?php

namespace Tests\Feature;

use App\Support\AdminPreview;
use Tests\TestCase;

class AcademicAndAuthEnhancementTest extends TestCase
{
    public function test_login_page_renders_official_portal(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('Masuk ke Portal');
        $response->assertSee('Email Institusi atau NIM / NIDN');
        $response->assertSee('Ahmad Maulana');
    }

    public function test_login_with_nim_and_email_credentials(): void
    {
        $res1 = $this->post('/login', ['login_id' => '231011401234', 'password' => 'secret']);
        $res1->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertEquals(1, session('auth_user.id'));

        $res2 = $this->post('/login', ['login_id' => 'budi@example.test', 'password' => 'secret']);
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
        $switchResponse = $this->get('/switch-role/admin');
        $switchResponse->assertRedirect(route('admin.page', 'dashboard'));
        $this->assertEquals('admin', session('auth_user.role'));

        // Logout
        $logoutResponse = $this->get('/logout');
        $logoutResponse->assertRedirect(route('login'));
        $this->assertNull(session('auth_user'));
    }

    public function test_admin_users_table_shows_nim_column_and_supports_bulk_import(): void
    {
        $response = $this->get('/admin/pengguna');
        $response->assertOk();
        $response->assertSee('NIM / NIDN / NIP');
        $response->assertSee('231011401234');

        // Test bulk import
        $bulkInput = "231011409001, Rani Kartika, rani@example.test, mahasiswa, aktif\n" .
                     "231011409002, Doni Saputra, doni@example.test, mahasiswa, aktif";

        $postResponse = $this->post('/admin/pengguna/bulk', [
            'raw_users' => $bulkInput,
        ]);

        $postResponse->assertRedirect('/admin/pengguna');
        $this->assertDatabaseHasOrSessionUsers('231011409001', 'Rani Kartika');
        $this->assertDatabaseHasOrSessionUsers('231011409002', 'Doni Saputra');
    }

    public function test_lecturer_academic_settings_requires_100_percent_weight(): void
    {
        $payload = [
            'cpl' => [
                ['code' => 'CPL-01', 'description' => 'Mampu menganalisis masalah'],
            ],
            'cpmk' => [
                ['code' => 'CPMK-01', 'cpl' => 'CPL-01', 'description' => 'Konsep dasar algoritma'],
            ],
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'weight' => 50],
                ['code' => 'uas', 'name' => 'UAS', 'weight' => 40], // total 90%, invalid!
            ],
        ];

        $failResponse = $this->post(route('dosen.academic.save', 1), $payload);
        $failResponse->assertSessionHasErrors('components');

        // Valid with 100% total
        $payload['components'][] = ['code' => 'uts', 'name' => 'UTS', 'weight' => 10];
        $successResponse = $this->post(route('dosen.academic.save', 1), $payload);
        $successResponse->assertSessionHasNoErrors();
    }

    public function test_lecturer_can_bulk_import_student_scores(): void
    {
        $bulkScores = "231011401234, 90, 85, 95, 88, 92, 100";

        $response = $this->post(route('dosen.scores.bulk', 1), [
            'raw_scores' => $bulkScores,
        ]);

        $response->assertSessionHasNoErrors();
        $studentScores = session('academic.scores.1.1');
        $this->assertNotEmpty($studentScores);
        $this->assertEquals(90, $studentScores['tugas'] ?? null);
    }

    public function test_student_grades_page_renders_transparent_components(): void
    {
        $response = $this->get(route('mahasiswa.nilai'));
        $response->assertOk();
        $response->assertSee('Transkrip Nilai');
        $response->assertSee('Rincian Komponen Nilai');
        $response->assertSee('Capaian CPMK');
    }

    private function assertDatabaseHasOrSessionUsers(string $number, string $name): void
    {
        $users = AdminPreview::users();
        $found = collect($users)->first(fn ($u) => $u['number'] === $number && $u['name'] === $name);
        $this->assertNotNull($found, "User with number {$number} and name {$name} not found in session.");
    }
}
