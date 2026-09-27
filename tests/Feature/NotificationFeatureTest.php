<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $mhsRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $this->student = User::create([
            'name' => 'Ahmad Mahasiswa',
            'email' => 'student@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '20260001',
        ]);
    }

    public function test_notification_page_displays_categories_hapus_semua_and_date_separation(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));

        $response->assertOk();
        // Check 5 categories
        $response->assertSee('Semua');
        $response->assertSee('Tugas & Kuis');
        $response->assertSee('Nilai');
        $response->assertSee('Sistem');
        $response->assertSee('Diskusi');

        // Check Hapus Semua button
        $response->assertSee('Hapus Semua');

        // Check date separation header (e.g. Hari Ini)
        $response->assertSee('Hari Ini');

        // Check relative time format (e.g. menit lalu or jam, not "Aktif")
        $response->assertSee('menit lalu');
    }

    public function test_system_category_filter_works(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.notifications', ['category' => 'sistem']));

        $response->assertOk();
        $response->assertSee('Asisten Lumina AI');
        $response->assertSee('Sinkronisasi Kurikulum OBE');
    }

    public function test_hapus_semua_clears_notifications(): void
    {
        $response = $this->actingAs($this->student)->post(route('mahasiswa.notifications.clear'));
        $response->assertRedirect();

        $followUp = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));
        $followUp->assertOk();
        $followUp->assertSee('Tidak ada notifikasi untuk kategori ini.');
    }

    public function test_individual_notification_can_be_deleted(): void
    {
        $response = $this->actingAs($this->student)->post(route('mahasiswa.notifications.delete', 'system_ai_ready'));
        $response->assertRedirect();

        $followUp = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));
        $followUp->assertOk();
        $followUp->assertDontSee('Asisten Lumina AI & Lab Interaktif Siap Digunakan');
    }

    public function test_read_notification_has_faded_styling(): void
    {
        // Mark all as read
        $this->actingAs($this->student)->post(route('mahasiswa.notifications.read', 'all'));

        $response = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));
        $response->assertOk();
        $response->assertSee('opacity-60', false);
    }

    public function test_notification_target_only_redirects_to_the_application_origin(): void
    {
        $internalPath = '/mahasiswa/dashboard?from=notification#latest';
        $this->actingAs($this->student)
            ->get(route('mahasiswa.notifications.read', ['id' => 'internal', 'target' => $internalPath]))
            ->assertRedirect($internalPath);

        $sameOrigin = url('/mahasiswa/dashboard?from=notification');
        $this->get(route('mahasiswa.notifications.read', ['id' => 'same-origin', 'target' => $sameOrigin]))
            ->assertRedirect($sameOrigin);

        $maliciousTargets = [
            url('/').'.attacker.example/audit',
            '//attacker.example/audit',
            '/\\attacker.example/audit',
            '/%255C%255Cattacker.example/audit',
            'https://'.parse_url((string) config('app.url'), PHP_URL_HOST).'@attacker.example/audit',
        ];

        foreach ($maliciousTargets as $index => $target) {
            $this->from('https://attacker.example/referrer')
                ->get(route('mahasiswa.notifications.read', [
                    'id' => "malicious-{$index}",
                    'target' => $target,
                ]))
                ->assertRedirect(route('mahasiswa.notifications'));
        }
    }
}
