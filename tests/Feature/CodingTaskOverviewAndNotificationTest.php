<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use App\Services\DatabaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodingTaskOverviewAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $mahasiswa;
    private ClassSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::firstOrCreate(['name' => Role::DOSEN], ['label' => 'Dosen']);
        $mahasiswaRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);

        $this->dosen = User::factory()->create([
            'role_id' => $dosenRole->id,
            'nim_nidn' => '198001012005011001',
        ]);
        $this->mahasiswa = User::factory()->create([
            'role_id' => $mahasiswaRole->id,
            'nim_nidn' => '220101001',
        ]);

        $prodi = Prodi::create(['name' => 'Informatika', 'code' => 'IF']);
        $semester = Semester::create(['code' => '20261', 'name' => 'Ganjil 2026/2027', 'academic_year' => '2026/2027', 'is_active' => true]);
        $matkul = MataKuliah::create([
            'name' => 'Pemrograman Web & Lanjut',
            'code' => 'IF204',
            'sks' => 3,
            'prodi_id' => $prodi->id,
            'semester_paket' => 2,
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'IF204-A',
        ]);

        $this->section->students()->attach($this->mahasiswa->id);
    }

    public function test_student_sees_coding_task_overview_card_and_no_textarea_questions(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'CODING-1',
            'name' => 'Tugas Coding Algoritma',
            'type' => 'tugas',
            'description' => 'Kerjakan implementasi algoritma berikut.',
            'learning_payload' => [
                'task_mode' => 'coding',
                'question_type' => 'coding',
                'language' => 'python',
                'points' => 100,
                'coding_steps' => [
                    ['title' => 'Langkah 1: Setup', 'body' => 'Buat struktur class Node', 'points' => 50],
                    ['title' => 'Langkah 2: Insert', 'body' => 'Implementasi fungsi insert', 'points' => 50],
                ],
                'questions' => [
                    ['id' => 'q1', 'type' => 'coding', 'prompt' => 'Soal 1 Coding', 'points' => 50],
                    ['id' => 'q2', 'type' => 'coding', 'prompt' => 'Soal 2 Coding', 'points' => 50],
                ],
            ],
            'final_weight' => 10,
            'status' => Assessment::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->mahasiswa)
            ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));

        $response->assertOk();
        $response->assertSee('Informasi Tugas Pemrograman &amp; Penilaian', false);
        $response->assertSee('Mulai Kerjakan Tugas Koding');
        $response->assertSee('butir instruksi / soal');
        $response->assertSee('Total <strong>100 poin</strong>', false);
        $response->assertSee('Status: Belum diserahkan');

        // Textarea question sheet must NOT be present
        $response->assertDontSee('Lembar Jawaban Soal');
        $response->assertDontSee('Tulis jawaban di sini...');
        // Manual file upload form must NOT be present
        $response->assertDontSee('Tambah atau buat');
    }

    public function test_submitted_coding_task_shows_submitted_status_and_open_editor_button(): void
    {
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'CODING-2',
            'name' => 'Tugas Coding Python II',
            'type' => 'coding',
            'description' => 'Kerjakan tugas python 2.',
            'learning_payload' => [
                'task_mode' => 'coding',
                'question_type' => 'coding',
                'points' => 100,
            ],
            'final_weight' => 10,
            'status' => Assessment::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        Submission::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'user_id' => $this->mahasiswa->id,
            'student_number' => $this->mahasiswa->nim_nidn,
            'answer' => json_encode([['name' => 'untitled.py', 'code' => 'print("hello")']]),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->mahasiswa)
            ->get(route('mahasiswa.course.item', [$this->section->id, $assessment->id]));

        $response->assertOk();
        $response->assertSee('Sudah diserahkan');
        $response->assertSee('Menunggu penilaian dosen');
        $response->assertSee('Buka Editor Kode / Jawaban');
    }

    public function test_new_materi_pengumuman_and_coding_task_appear_in_notifications(): void
    {
        $materi = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MAT-1',
            'name' => 'Pengenalan Framework Laravel',
            'type' => 'materi',
            'description' => 'Pelajari konsep MVC.',
            'learning_payload' => ['material_mode' => 'regular'],
            'final_weight' => 0,
            'status' => Assessment::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $pengumuman = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'ANN-1',
            'name' => 'Jadwal Kuliah Pengganti',
            'type' => 'pengumuman',
            'description' => 'Kuliah pengganti dilaksanakan hari Sabtu.',
            'learning_payload' => [],
            'final_weight' => 0,
            'status' => Assessment::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $codingTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'COD-1',
            'name' => 'Praktikum Sorting Python',
            'type' => 'coding',
            'description' => 'Implementasi bubble sort.',
            'learning_payload' => ['task_mode' => 'coding'],
            'final_weight' => 10,
            'status' => Assessment::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $service = app(DatabaseNotificationService::class);
        $notifs = $service->forUser($this->mahasiswa, 'mahasiswa');

        $titles = array_column($notifs, 'title');
        $actions = array_column($notifs, 'action_label');
        $links = array_column($notifs, 'link');

        $this->assertTrue(collect($titles)->contains(fn ($t) => str_contains($t, 'Materi Baru: Pengenalan Framework Laravel')));
        $this->assertTrue(collect($titles)->contains(fn ($t) => str_contains($t, 'Pengumuman: Jadwal Kuliah Pengganti')));
        $this->assertTrue(collect($titles)->contains(fn ($t) => str_contains($t, 'Tugas Pemrograman Tersedia: Praktikum Sorting Python')));

        // Check action buttons
        $this->assertTrue(in_array('Pelajari Materi', $actions, true));
        $this->assertTrue(in_array('Lihat Pengumuman', $actions, true));
        $this->assertTrue(in_array('Mulai Kerjakan Tugas Koding', $actions, true));

        // Links must direct to item overview (not directly workbench)
        $this->assertTrue(collect($links)->contains(fn ($l) => str_contains($l, "/course/{$this->section->id}/item/{$codingTask->id}")));
    }
}
