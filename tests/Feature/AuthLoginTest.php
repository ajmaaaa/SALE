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
        $this->assertTrue(auth()->user()->hasRole(Role::MAHASISWA));
    }

    public function test_can_login_as_mahasiswa_with_nim(): void
    {
        $response = $this->post('/login', [
            'login_id' => '231011401234',
            'password' => 'password',
        ]);

        $response->assertRedirect('/mahasiswa/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(Role::MAHASISWA));
    }

    public function test_unregistered_alias_email_cannot_login(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'ahmad@example.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['login_id', 'password']);
        $this->assertGuest();
    }

    public function test_can_login_as_dosen(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'budi@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dosen/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(Role::DOSEN));
    }

    public function test_can_login_as_admin_prodi(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'adminprodi@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin-prodi/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(Role::ADMIN_PRODI));
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

    public function test_multi_role_account_uses_persisted_database_roles(): void
    {
        $user = User::where('email', 'budi@example.test')->firstOrFail();
        $adminProdiRole = Role::where('name', Role::ADMIN_PRODI)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$adminProdiRole->id]);

        $response = $this->post('/login', [
            'login_id' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dosen/dashboard');
        $this->assertTrue(auth()->user()->hasRole(Role::DOSEN));
        $this->assertTrue(auth()->user()->hasRole(Role::ADMIN_PRODI));
    }

    public function test_can_login_as_admin(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'admin@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(Role::ADMIN));
    }

    public function test_role_switch_endpoint_is_removed(): void
    {
        $this->post('/switch-role/dosen')->assertNotFound();
        $this->assertGuest();
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
        $this->get('/switch-role/admin')->assertNotFound();
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

    public function test_session_expires_after_configured_timeout_and_redirects_on_reload_or_path_access(): void
    {
        \App\Models\SystemSetting::updateOrCreate(['key' => 'session_lifetime'], ['value' => '5']);

        $this->post('/login', [
            'login_id' => 'admin@example.test',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticated();

        // Akses langsung saat sesi masih aktif berhasil
        $this->get('/admin/dashboard')->assertOk();

        // Simulasikan pengguna tidak aktif selama 6 menit (melebihi limit 5 menit)
        session(['last_user_activity' => time() - 360]);

        // Ketika pengguna reload, pindah tab, atau copy path URL ke tab baru
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('notice', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
        $this->assertGuest();
    }

    public function test_background_poll_does_not_extend_user_inactivity_timer_and_returns_401_when_expired(): void
    {
        \App\Models\SystemSetting::updateOrCreate(['key' => 'session_lifetime'], ['value' => '5']);

        $this->post('/login', [
            'login_id' => 'admin@example.test',
            'password' => 'password',
        ]);

        $initialActivity = time() - 100;
        session(['last_user_activity' => $initialActivity]);

        // Background poll live-status tanpa X-User-Activity tidak boleh memperbarui last_user_activity
        $pollResponse = $this->getJson(route('live-status'));
        $pollResponse->assertOk();
        $this->assertSame($initialActivity, session('last_user_activity'));

        // Jika telah melebihi batas waktu (misal 6 menit), background poll menghasilkan 401 dan logout
        session(['last_user_activity' => time() - 360]);

        $expiredPoll = $this->getJson(route('live-status'));
        $expiredPoll->assertStatus(401)
            ->assertJsonPath('session_expired', true);
        $this->assertGuest();
    }

    public function test_wrong_password_five_times_locks_account_for_five_hours_without_deactivating_user(): void
    {
        $user = User::where('email', 'budi@example.test')->firstOrFail();
        $this->assertTrue($user->is_active);

        // Percobaan salah 1 sampai 4
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->post('/login', [
                'login_id' => 'budi@example.test',
                'password' => 'wrongpass' . $i,
            ]);
            $res->assertSessionHasErrors(['login_id', 'password']);
            $this->assertGuest();
        }

        // Percobaan salah ke-5
        $res5 = $this->post('/login', [
            'login_id' => 'budi@example.test',
            'password' => 'wrongpass5',
        ]);
        $res5->assertSessionHasErrors(['login_id', 'password']);
        $this->assertGuest();
        $errorMsg = session('errors')->get('login_id')[0];
        $this->assertStringContainsString('lebih dari 5 kali', $errorMsg);
        $this->assertStringContainsString('admin prodi', $errorMsg);

        // Akun tetap aktif di DB (tidak diblokir permanen / ganti password admin)
        $this->assertTrue($user->fresh()->is_active);

        // Percobaan ke-6 terkunci meskipun mencoba dengan password benar
        $res6 = $this->post('/login', [
            'login_id' => $user->nim_nidn ?: 'budi@example.test',
            'password' => 'password',
        ]);
        $res6->assertSessionHasErrors(['login_id', 'password']);
        $this->assertGuest();

        // Majukan waktu 5 jam (18.001 detik)
        $this->travel(18001)->seconds();

        // Setelah 5 jam, pengguna dapat mencoba login kembali dengan password benar
        $resUnlock = $this->post('/login', [
            'login_id' => 'budi@example.test',
            'password' => 'password',
        ]);
        $resUnlock->assertRedirect('/dosen/dashboard');
        $this->assertAuthenticated();
    }
}
