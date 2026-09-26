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

    public function test_demo_seeder_repairs_an_old_admin_prodi_role(): void
    {
        $adminProdi = User::where('email', 'adminprodi@example.test')->firstOrFail();
        $adminProdi->update(['role_id' => Role::where('name', Role::MAHASISWA)->value('id')]);

        $this->seed(DosenAccountSeeder::class);

        $this->assertSame(
            Role::where('name', Role::ADMIN_PRODI)->value('id'),
            $adminProdi->fresh()->role_id
        );
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

    public function test_all_four_roles_can_switch_smoothly(): void
    {
        $roles = ['mahasiswa', 'dosen', 'admin_prodi', 'admin'];

        foreach ($roles as $role) {
            $response = $this->post("/switch-role/{$role}");
            $response->assertRedirect();
            $this->assertAuthenticated();
            $this->assertEquals($role, session('auth_user.role'));
        }
    }

    public function test_demo_user_cannot_enter_another_role_area_by_changing_the_path(): void
    {
        $this->post('/login', [
            'login_id' => '231011401234',
            'password' => 'password',
        ])->assertRedirect('/mahasiswa/dashboard');

        $this->get('/mahasiswa/dashboard')->assertOk();
        $this->get('/dosen/dashboard')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin-prodi/dashboard')->assertForbidden();
        $this->get('/switch-role/admin')->assertMethodNotAllowed();
    }

    public function test_demo_guest_cannot_open_role_paths_directly(): void
    {
        foreach ([
            '/mahasiswa/dashboard',
            '/dosen/dashboard',
            '/admin/dashboard',
            '/admin-prodi/dashboard',
        ] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_login_page_renders_password_visibility_toggle(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('id="toggle-password"', false);
        $response->assertSee('id="eye-icon"', false);
        $response->assertSee('id="eye-off-icon"', false);
    }
}
