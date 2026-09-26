<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.demo_mode' => false]);
    }

    public function test_production_mode_blocks_guests_and_cross_role_access(): void
    {
        $this->get('/mahasiswa/dashboard')->assertRedirect(route('login'));
        $this->get('/dosen/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/admin-prodi/dashboard')->assertRedirect(route('login'));

        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $student = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($student);

        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/dosen/dashboard')->assertForbidden();
        $this->get('/admin-prodi/dashboard')->assertForbidden();
    }

    public function test_production_mode_disables_personas_and_requires_database_password(): void
    {
        $this->get('/switch-role/admin')->assertMethodNotAllowed();
        $this->get('/logout')->assertStatus(405);

        $this->post('/login', ['persona_id' => 1])
            ->assertSessionHasErrors(['login_id', 'password']);
        $this->assertGuest();

        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'secure@example.test',
            'nim_nidn' => '23100001',
            'password' => Hash::make('correct-password'),
        ]);

        $this->post('/login', ['login_id' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('login_id');
        $this->assertGuest();

        $this->post('/login', ['login_id' => $user->nim_nidn, 'password' => 'correct-password'])
            ->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_production_database_seeder_only_creates_fixed_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('prodis', 0);
    }
}
