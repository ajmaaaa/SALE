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
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAssignmentAndCourseDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $dosen;

    private ClassSection $section;

    private Assessment $overdueTask;

    private Assessment $submittedTask;

    private Assessment $upcomingQuiz;

    private Carbon $pastDueDate;

    private Carbon $submissionDate;

    private Carbon $upcomingDueDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'IF-UI', 'name' => 'Informatika UI']);
        $semester = Semester::create(['name' => 'Ganjil 2026/2027', 'code' => '20261UI', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'IF201', 'name' => 'Pemrograman Web Lanjut', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $this->dosen = User::create([
            'name' => 'Dosen Pengampu Web',
            'email' => 'dosen.web@test.local',
            'nim_nidn' => '10203040',
            'password' => Hash::make('secret'),
            'role_id' => $dosenRoleId,
        ]);
        $this->dosen->roles()->sync([$dosenRoleId]);

        $this->student = User::create([
            'name' => 'Mahasiswa Web Cerdas',
            'email' => 'mhs.web@test.local',
            'nim_nidn' => '20230001',
            'password' => Hash::make('secret'),
            'role_id' => $mhsRoleId,
        ]);
        $this->student->roles()->sync([$mhsRoleId]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 30,
        ]);
        $this->section->students()->attach($this->student->id);

        $this->pastDueDate = Carbon::now()->subDays(3);
        $this->overdueTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-OVERDUE',
            'name' => 'Tugas Laravel Blade dan UI',
            'type' => 'tugas',
            'final_weight' => 15,
            'status' => 'published',
            'due_at' => $this->pastDueDate,
            'learning_payload' => [
                'due' => $this->pastDueDate->format('Y-m-d\TH:i'),
                'points' => 100,
            ],
        ]);
        $this->overdueTask->forceFill([
            'created_at' => Carbon::now()->subDays(10),
            'published_at' => Carbon::now()->subDays(10),
        ])->saveQuietly();

        $this->submissionDate = Carbon::now()->subDay();
        $this->submittedTask = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-SUBMITTED',
            'name' => 'Tugas REST API Controller',
            'type' => 'coding',
            'final_weight' => 20,
            'status' => 'published',
            'due_at' => Carbon::now()->addDays(5),
            'learning_payload' => [
                'due' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
                'points' => 100,
            ],
        ]);

        Submission::create([
            'assessment_id' => $this->submittedTask->id,
            'user_id' => $this->student->id,
            'mahasiswa_id' => $this->student->id,
            'attempt' => 1,
            'version' => 1,
            'status' => 'submitted',
            'submitted_at' => $this->submissionDate,
        ]);

        $this->upcomingDueDate = Carbon::now()->addDays(2);
        $this->upcomingQuiz = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'QUIZ-UPCOMING',
            'name' => 'Kuis Tengah Semester Teori',
            'type' => 'kuis',
            'final_weight' => 25,
            'status' => 'published',
            'due_at' => $this->upcomingDueDate,
            'learning_payload' => [
                'due' => $this->upcomingDueDate->format('Y-m-d\TH:i'),
                'points' => 100,
            ],
        ]);
        $this->upcomingQuiz->forceFill([
            'created_at' => Carbon::now()->subHours(2),
            'published_at' => Carbon::now()->subHours(2),
        ])->saveQuietly();
    }

    public function test_course_view_shows_deadline_date_instead_of_duplicate_terlambat(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $response->assertOk();

        // 1. Tugas yang terlambat: Di samping tanggal diterbitkan tampil 'Tenggat ...', BUKAN 'Terlambat'
        $response->assertSee('Tenggat '.$this->pastDueDate->translatedFormat('d M Y, H:i'));

        // Badge aksi di kanan untuk tugas terlambat menampilkan 'Terlambat'
        $response->assertSee('Terlambat');

        // 2. Tugas yang sudah diserahkan: Menampilkan 'Dikerjakan ...'
        $response->assertSee('Dikerjakan '.$this->submissionDate->translatedFormat('d M Y, H:i'));
        $response->assertSee('Sudah dikerjakan');

        // Pastikan kata 'Terlambat' muncul tepat satu kali untuk tugas yang lewat tenggat
        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'Terlambat'), 'Kata Terlambat tidak boleh duplikat (hanya muncul pada badge status).');
    }

    public function test_dosen_course_view_does_not_display_terlambat(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.course.show', $this->section->id));
        $response->assertOk();

        // Dosen tidak melihat kata 'Terlambat'
        $response->assertDontSee('Terlambat');
        // Tetapi dosen melihat tenggat waktu
        $response->assertSee('Tenggat '.$this->pastDueDate->translatedFormat('d M Y, H:i'));
    }

    public function test_course_card_does_not_show_terlambat(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.course.index'));
        $response->assertOk();

        // Pada card course, kata 'Terlambat' tidak boleh tampil
        $response->assertDontSee('Terlambat');
    }

    public function test_topbar_profile_header_displays_name_role_and_dropdown(): void
    {
        $response = $this->actingAs($this->student)->get(route('mahasiswa.dashboard'));
        $response->assertOk();

        // Menampilkan nama mahasiswa secara jelas di header
        $response->assertSee('Mahasiswa Web Cerdas');
        $response->assertSee('20230001');

        // Memiliki dropdown details di area profil
        $response->assertSee('<details class="group relative">', false);

        // Memiliki form logout
        $response->assertSee(route('logout'));
    }

    public function test_assignment_page_supports_search_and_sorting_terbaru_vs_terdekat(): void
    {
        // 1. Sort terdekat (default): Kuis dengan tenggat mendekati (2 hari lagi) muncul sebelum tugas overdue
        $responseTerdekat = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index', ['sort' => 'terdekat']));
        $responseTerdekat->assertOk();
        $htmlTerdekat = $responseTerdekat->getContent();

        $posQuiz = strpos($htmlTerdekat, 'Kuis Tengah Semester Teori');
        $posOverdue = strpos($htmlTerdekat, 'Tugas Laravel Blade dan UI');
        $this->assertNotFalse($posQuiz);
        $this->assertNotFalse($posOverdue);
        $this->assertTrue($posQuiz < $posOverdue, 'Pada sort terdekat, kuis upcoming harus di atas tugas overdue');

        // 2. Sort terbaru: Item yang dibuat paling baru (kuis baru dibuat 2 jam lalu) harus di atas yang dibuat 10 hari lalu
        $responseTerbaru = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index', ['sort' => 'terbaru']));
        $responseTerbaru->assertOk();
        $htmlTerbaru = $responseTerbaru->getContent();

        $posQuizNewest = strpos($htmlTerbaru, 'Kuis Tengah Semester Teori');
        $posOverdueNewest = strpos($htmlTerbaru, 'Tugas Laravel Blade dan UI');
        $this->assertTrue($posQuizNewest < $posOverdueNewest, 'Pada sort terbaru, item yang baru dibuat berada di atas');

        // 3. Search query: hanya menampilkan item yang cocok
        $responseSearch = $this->actingAs($this->student)->get(route('mahasiswa.assignment.index', ['q' => 'Laravel Blade']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Tugas Laravel Blade dan UI');
        $responseSearch->assertDontSee('Kuis Tengah Semester Teori');

        // 4. Verifikasi bahwa tanggal dikerjakan tampil untuk tugas yang sudah diserahkan di halaman assignment
        $responseTerdekat->assertSee('Dikerjakan '.$this->submissionDate->translatedFormat('d M Y, H:i'));
    }
}
