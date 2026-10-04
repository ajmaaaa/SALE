<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Services\DatabaseNotificationService;
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

        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknik Informatika']);
        $mk = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'TI-101', 'name' => 'Pemrograman Web', 'sks' => 3]);
        $sem = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026', 'is_active' => true]);
        $section = ClassSection::create(['mata_kuliah_id' => $mk->id, 'semester_id' => $sem->id, 'section_code' => 'A', 'enrollment_code' => 'TI-A-2026']);
        $section->students()->attach($this->student->id);
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'name' => 'Tugas 1 Pemrograman',
            'code' => 'TUGAS-1',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => Assessment::STATUS_PUBLISHED,
            'max_score' => 100,
        ]);
        $assessment->published_at = now()->subMinutes(5);
        $assessment->created_at = now()->subMinutes(5);
        $assessment->saveQuietly();
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
        $response->assertDontSee('Asisten Lumina AI');
        $response->assertDontSee('Sinkronisasi Kurikulum OBE');
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
        $notifService = app(DatabaseNotificationService::class);
        $firstNotif = collect($notifService->forUser($this->student))->first();
        $this->assertNotNull($firstNotif);

        $response = $this->actingAs($this->student)->post(route('mahasiswa.notifications.delete', $firstNotif['id']));
        $response->assertRedirect();

        $followUp = $this->actingAs($this->student)->get(route('mahasiswa.notifications'));
        $followUp->assertOk();
        $followUp->assertDontSee($firstNotif['title']);
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
            ->post(route('mahasiswa.notifications.read', ['id' => 'internal']), ['target' => $internalPath])
            ->assertRedirect($internalPath);

        $sameOrigin = url('/mahasiswa/dashboard?from=notification');
        $this->post(route('mahasiswa.notifications.read', ['id' => 'same-origin']), ['target' => $sameOrigin])
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
                ->post(route('mahasiswa.notifications.read', [
                    'id' => "malicious-{$index}",
                ]), ['target' => $target])
                ->assertRedirect(route('mahasiswa.notifications'));
        }
    }

    public function test_get_request_to_mark_read_endpoint_returns_405_method_not_allowed(): void
    {
        $this->actingAs($this->student)
            ->get(route('mahasiswa.notifications.read', 'any_id'))
            ->assertStatus(405);
    }
}
