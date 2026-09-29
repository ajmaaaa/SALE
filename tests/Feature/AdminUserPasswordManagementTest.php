<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $adminRoleId = Role::where('name', Role::ADMIN)->value('id');
        $this->admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.local',
            'nim_nidn' => 'ADM999',
            'password' => Hash::make('adminsecret'),
            'role_id' => $adminRoleId,
            'is_active' => true,
        ]);
        $this->admin->roles()->sync([$adminRoleId]);

        $this->prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        Semester::create(['name' => 'Ganjil 2026/2027', 'code' => '20261', 'is_active' => true, 'academic_year' => '2026/2027']);
    }

    public function test_admin_system_can_create_user_with_manual_password(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@test.local',
            'number' => '198501012010121001',
            'role' => 'dosen',
            'status' => 'aktif',
            'prodi_id' => $this->prodi->id,
            'password' => 'ManualPass123',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'budi@test.local')->firstOrFail();
        $this->assertTrue(Hash::check('ManualPass123', $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_admin_system_creates_auto_password_when_password_blank(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Citra Lestari',
            'email' => 'citra@student.test',
            'number' => '231011409876',
            'role' => 'mahasiswa',
            'status' => 'aktif',
            'prodi_id' => $this->prodi->id,
            'password' => '',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'citra@student.test')->firstOrFail();
        $notice = (string) session('notice');
        $this->assertStringContainsString('Password sementara:', $notice);
        preg_match('/Password sementara: (\S{16})/', $notice, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertTrue(Hash::check($matches[1], $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_admin_system_cannot_create_or_update_with_password_shorter_than_8_chars(): void
    {
        // 1. Create with password < 8
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Pendek',
            'email' => 'pendek@test.local',
            'number' => '231011401111',
            'role' => 'mahasiswa',
            'status' => 'aktif',
            'prodi_id' => $this->prodi->id,
            'password' => 'short12',
        ]);
        $response->assertSessionHasErrors('password');

        // 2. Update with password < 8
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');
        $existing = User::create([
            'name' => 'Mahasiswa Lama',
            'email' => 'mhs.lama@test.local',
            'nim_nidn' => '231011402222',
            'password' => Hash::make('passwordAwal123'),
            'role_id' => $mhsRoleId,
            'is_active' => true,
        ]);
        $existing->roles()->sync([$mhsRoleId]);

        $updateResponse = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'id' => $existing->id,
            'name' => $existing->name,
            'email' => $existing->email,
            'number' => $existing->nim_nidn,
            'role' => 'mahasiswa',
            'status' => 'aktif',
            'prodi_id' => $this->prodi->id,
            'password' => '123',
        ]);
        $updateResponse->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('passwordAwal123', $existing->fresh()->password));
    }

    public function test_admin_system_can_change_user_password_like_admin_prodi(): void
    {
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $dosen = User::create([
            'name' => 'Dosen Target Ganti Password',
            'email' => 'target.dosen@test.local',
            'nim_nidn' => '198701012012121002',
            'password' => Hash::make('LamaBanget123'),
            'role_id' => $dosenRoleId,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        // Admin Sistem melakukan ganti password
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'id' => $dosen->id,
            'name' => $dosen->name,
            'email' => $dosen->email,
            'number' => $dosen->nim_nidn,
            'role' => 'dosen',
            'status' => 'aktif',
            'prodi_id' => $this->prodi->id,
            'password' => 'PasswordBaru2026!',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHasNoErrors();

        $freshDosen = $dosen->fresh();
        $this->assertTrue(Hash::check('PasswordBaru2026!', $freshDosen->password), 'Password baru harus berhasil di-hash dan disimpan');
        $this->assertFalse(Hash::check('LamaBanget123', $freshDosen->password), 'Password lama tidak boleh aktif lagi');
        $this->assertTrue($freshDosen->must_change_password, 'must_change_password harus diset true');
    }

    public function test_admin_system_leaves_password_unchanged_when_blank_on_update(): void
    {
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $dosen = User::create([
            'name' => 'Dosen Password Tetap',
            'email' => 'tetap.dosen@test.local',
            'nim_nidn' => '198701012012121003',
            'password' => Hash::make('PasswordAman123'),
            'role_id' => $dosenRoleId,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'id' => $dosen->id,
            'name' => 'Dosen Password Tetap (Updated Name)',
            'email' => $dosen->email,
            'number' => $dosen->nim_nidn,
            'role' => 'dosen',
            'status' => 'aktif',
            'prodi_id' => $this->prodi->id,
            'password' => '', // kosong
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHasNoErrors();

        $freshDosen = $dosen->fresh();
        $this->assertSame('Dosen Password Tetap (Updated Name)', $freshDosen->name);
        $this->assertTrue(Hash::check('PasswordAman123', $freshDosen->password), 'Password tidak boleh berubah jika dikosongkan');
        $this->assertFalse($freshDosen->must_change_password);
    }
}
