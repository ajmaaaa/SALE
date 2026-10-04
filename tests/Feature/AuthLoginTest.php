<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemSetting;
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
        SystemSetting::updateOrCreate(['key' => 'session_lifetime'], ['value' => '5']);

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
        SystemSetting::updateOrCreate(['key' => 'session_lifetime'], ['value' => '5']);

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

    public function test_failed_login_is_rate_limited_briefly_per_identifier_and_ip_without_locking_the_account_globally(): void
    {
        $user = User::where('email', 'budi@example.test')->firstOrFail();
        $this->assertTrue($user->is_active);

        for ($i = 1; $i <= 5; $i++) {
            $res = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->post('/login', [
                'login_id' => 'budi@example.test',
                'password' => 'wrongpass'.$i,
            ]);
            $res->assertSessionHasErrors(['login_id', 'password']);
            $this->assertGuest();
        }

        $blocked = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->post('/login', [
            'login_id' => 'budi@example.test',
            'password' => 'password',
        ]);
        $blocked->assertSessionHasErrors(['login_id', 'password']);
        $this->assertGuest();
        $this->assertTrue($user->fresh()->is_active);

        // Percobaan penyerang dari satu IP tidak mengunci akun korban secara global.
        $otherIp = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->post('/login', [
            'login_id' => 'budi@example.test',
            'password' => 'password',
        ]);
        $otherIp->assertRedirect('/dosen/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_failures_use_the_same_generic_message(): void
    {
        $inactive = User::where('email', 'budi@example.test')->firstOrFail();
        $inactive->update(['is_active' => false]);

        $unknown = $this->post('/login', [
            'login_id' => 'unknown@example.test',
            'password' => 'wrong-password',
        ]);
        $unknownMessage = $unknown->getSession()->get('errors')->first('login_id');

        $inactiveResponse = $this->post('/login', [
            'login_id' => $inactive->email,
            'password' => 'password',
        ]);
        $inactiveMessage = $inactiveResponse->getSession()->get('errors')->first('login_id');

        $wrongPassword = $this->post('/login', [
            'login_id' => 'admin@example.test',
            'password' => 'wrong-password',
        ]);
        $wrongMessage = $wrongPassword->getSession()->get('errors')->first('login_id');

        $this->assertSame($unknownMessage, $inactiveMessage);
        $this->assertSame($unknownMessage, $wrongMessage);
        $this->assertStringNotContainsString('tersisa', $unknownMessage);
        $this->assertStringNotContainsString('dinonaktifkan', $unknownMessage);
    }
}
