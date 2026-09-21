<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DosenAccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DosenAccountSeeder::class);
    }

    public function test_can_login_as_mahasiswa_with_student_email(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'ahmad.maulana@student.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/mahasiswa/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('mahasiswa', session('auth_user.role'));
    }

    public function test_can_login_as_mahasiswa_with_nim(): void
    {
        $response = $this->post('/login', [
            'login_id' => '231011401234',
            'password' => 'password',
        ]);

        $response->assertRedirect('/mahasiswa/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('mahasiswa', session('auth_user.role'));
    }

    public function test_can_login_as_mahasiswa_with_alias_email(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'ahmad@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/mahasiswa/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('mahasiswa', session('auth_user.role'));
    }

    public function test_can_login_as_dosen(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'budi@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dosen/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('dosen', session('auth_user.role'));
    }

    public function test_can_login_as_admin_prodi(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'adminprodi@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin-prodi/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('admin_prodi', session('auth_user.role'));
    }

    public function test_multi_role_preview_account_uses_selected_admin_prodi_role(): void
    {
        $this->withSession(['admin.users' => [
            10 => [
                'id' => 10,
                'name' => 'Petugas Prodi',
                'email' => 'petugas@example.test',
                'number' => 'PTG001',
                'role' => 'mahasiswa',
                'roles' => ['mahasiswa', 'admin_prodi'],
                'status' => 'aktif',
            ],
        ]]);

        $response = $this->post('/login', [
            'login_id' => 'petugas@example.test',
            'role' => 'admin_prodi',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin-prodi/dashboard');
        $this->assertEquals('admin_prodi', session('auth_user.role'));
    }

    public function test_can_login_as_kaprodi(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'kaprodi@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/kaprodi/monitoring/cpmk');
        $this->assertAuthenticated();
        $this->assertEquals('kaprodi', session('auth_user.role'));
    }

    public function test_can_login_as_admin(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'admin@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('admin', session('auth_user.role'));
    }

    public function test_all_five_roles_can_switch_smoothly(): void
    {
        $roles = ['mahasiswa', 'dosen', 'admin_prodi', 'kaprodi', 'admin'];

        foreach ($roles as $role) {
            $response = $this->get("/switch-role/{$role}");
            $response->assertRedirect();
            $this->assertAuthenticated();
            $this->assertEquals($role, session('auth_user.role'));
        }
    }
}
