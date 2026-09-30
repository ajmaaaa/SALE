<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Message;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationAndMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $student;
    private User $admin;
    private ClassSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mhsRole = Role::where('name', Role::MAHASISWA)->firstOrFail();
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();

        $this->dosen = User::create([
            'name' => 'Dr. Dosen Pengampu',
            'email' => 'dosen@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '198701012020121001',
        ]);

        $this->student = User::create([
            'name' => 'Budi Santoso',
            'email' => 'student@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $mhsRole->id,
            'nim_nidn' => '20260010',
        ]);

        $this->admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret'),
            'role_id' => $adminRole->id,
        ]);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $mk = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF-202', 'name' => 'Struktur Data', 'sks' => 3]);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026', 'is_active' => true]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'enrollment_code' => 'IF2026A',
        ]);
        $this->section->students()->attach($this->student->id);
    }

    public function test_live_status_endpoint_returns_json_counts_for_student(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'name' => 'Tugas Binary Tree',
            'code' => 'TGS-BT',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => Assessment::STATUS_PUBLISHED,
            'max_score' => 100,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson(route('live-status'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'role',
            'unread_notif_count',
            'category_counts',
            'forum_unread_count',
            'pending_task_count',
            'pending_grading_count',
            'course_discussion_counts',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertGreaterThanOrEqual(1, $data['unread_notif_count']);
        $this->assertGreaterThanOrEqual(1, $data['pending_task_count']);
        $this->assertEquals(0, $data['pending_grading_count']);
    }

    public function test_live_status_endpoint_returns_json_counts_for_dosen(): void
    {
        $response = $this->actingAs($this->dosen)
            ->getJson(route('live-status'));

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('unread_notif_count', $data);
        $this->assertArrayHasKey('pending_grading_count', $data);
    }

    public function test_mahasiswa_notifications_page_supports_ajax_and_returns_html_partial_with_fingerprint(): void
    {
        Assessment::create([
            'class_section_id' => $this->section->id,
            'name' => 'Tugas Graph Traversal',
            'code' => 'TGS-GRAPH',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => Assessment::STATUS_PUBLISHED,
            'max_score' => 100,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('mahasiswa.notifications'), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'unread_count',
            'category_counts',
            'has_unread',
            'total_count',
            'fingerprint',
            'html',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('Tugas Graph Traversal', $data['html']);
        $this->assertNotEmpty($data['fingerprint']);
    }

    public function test_dosen_notifications_page_supports_ajax_and_returns_html_partial(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.notifications'), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('fingerprint', $data);
        $this->assertArrayHasKey('html', $data);
    }

    public function test_admin_monitoring_page_supports_ajax_json_metrics(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.page', 'monitoring'), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'storageMetrics' => [
                'used_formatted',
                'total_formatted',
                'free_formatted',
                'app_formatted',
                'percent',
            ],
            'aiMetrics' => [
                'requests',
                'input_tokens',
                'output_tokens',
                'total_tokens',
            ],
        ]);
    }
}
