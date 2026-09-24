<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\CourseDiscussion;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DosenCourseEnrollmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_course_uses_database_discussion_and_rejects_locked_late_submission(): void
    {
        $this->seed(RoleSeeder::class);
        $prodi = Prodi::create(['code' => 'BD', 'name' => 'Basis Data']);
        $semester = Semester::create(['code' => '2026-3', 'name' => 'Semester Uji Database', 'is_active' => true]);
        $mataKuliah = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'BD401',
            'name' => 'Integrasi Database',
            'sks' => 4,
        ]);
        $dosen = User::create([
            'name' => 'Dosen Database',
            'email' => 'forum.dosen@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'prodi_id' => $prodi->id,
            'nim_nidn' => 'DBD001',
        ]);
        $student = User::create([
            'name' => 'Mahasiswa Database',
            'email' => 'forum.student@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::MAHASISWA)->value('id'),
            'prodi_id' => $prodi->id,
            'nim_nidn' => 'DBM001',
        ]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
        $section->students()->attach($student->id);
        $assessment = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-DB-01',
            'name' => 'Tugas Database Terkunci',
            'type' => 'tugas',
            'description' => 'Tugas ini berasal dari database.',
            'final_weight' => 10,
            'uses_rubric' => false,
            'status' => Assessment::STATUS_PUBLISHED,
            'due_at' => now()->subHour(),
            'allow_late' => false,
        ]);

        $this->actingAs($dosen)
            ->postJson(route('dosen.course.discuss.class', $section), ['message' => 'Pesan forum tersimpan permanen.'])
            ->assertOk()
            ->assertJsonPath('message.message', 'Pesan forum tersimpan permanen.');

        $this->assertDatabaseHas('course_discussions', [
            'class_section_id' => $section->id,
            'user_id' => $dosen->id,
            'message' => 'Pesan forum tersimpan permanen.',
        ]);
        $this->assertSame(1, CourseDiscussion::whereBelongsTo($section)->count());

        $this->actingAs($student)
            ->get(route('mahasiswa.course.show', $section))
            ->assertOk()
            ->assertSee('Tugas Database Terkunci')
            ->assertSee('Pesan forum tersimpan permanen.')
            ->assertSee('4 SKS')
            ->assertSee('Semester Uji Database')
            ->assertDontSee('Praktikum Binary Tree');

        $this->post(route('mahasiswa.course.submit', [$section, $assessment]), [
            'answer' => 'Jawaban yang sudah terlambat.',
        ])->assertSessionHasErrors('answer');
    }

    public function test_dosen_join_by_code_is_persisted_and_visible_in_both_class_lists(): void
    {
        $this->seed(RoleSeeder::class);
        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF401', 'name' => 'Integrasi Sistem', 'sks' => 3]);
        $ketua = User::create([
            'name' => 'Dosen Ketua',
            'email' => 'ketua@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => 'DSN001',
        ]);
        $joiningDosen = User::create([
            'name' => 'Dosen Pendamping',
            'email' => 'pendamping@example.test',
            'password' => Hash::make('custom-password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => 'DSN002',
        ]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $ketua->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'JOINIF01',
        ]);

        $this->actingAs($joiningDosen);

        $this->get(route('mahasiswa.join-kelas', $section->enrollment_code))
            ->assertOk()
            ->assertSee('Tambahkan Saya');

        $this->post(route('mahasiswa.join-kelas.post', $section->enrollment_code))
            ->assertOk()
            ->assertSee('berhasil bergabung sebagai Dosen Pendamping');

        $this->assertSame($joiningDosen->id, $section->fresh()->dosen_pendamping_id);

        $this->get(route('dosen.course.index'))
            ->assertOk()
            ->assertSee('Integrasi Sistem')
            ->assertSee('IF401-A');

        $this->get(route('dosen.penilaian.index'))
            ->assertOk()
            ->assertSee('Integrasi Sistem')
            ->assertSee('Dosen Wakil')
            ->assertSee('Gabung Kelas Perkuliahan')
            ->assertSee('Contoh: A7K9M2QX');
    }

    public function test_database_only_account_can_login_with_its_hashed_password(): void
    {
        $this->seed(RoleSeeder::class);
        $dosen = User::create([
            'name' => 'Dosen Database',
            'email' => 'database.lecturer@example.test',
            'password' => Hash::make('custom-password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'nim_nidn' => 'DSN900',
        ]);

        $this->post(route('login.post'), [
            'login_id' => strtoupper($dosen->email),
            'password' => 'custom-password',
        ])->assertRedirect(route('dosen.dashboard'));

        $this->assertAuthenticatedAs($dosen);
        $this->assertSame('dosen', session('auth_user.role'));

        $this->post(route('logout'));
        $this->post(route('login.post'), [
            'login_id' => $dosen->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_dosen_course_page_offers_both_join_code_and_course_creation(): void
    {
        $this->seed(RoleSeeder::class);
        $dosen = User::create([
            'name' => 'Dosen Tanpa Kelas',
            'email' => 'empty@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'nim_nidn' => 'DSN901',
        ]);

        $this->actingAs($dosen)
            ->get(route('dosen.course.index'))
            ->assertOk()
            ->assertSee('Gabung Kelas')
            ->assertSee('Contoh: A7K9M2QX')
            ->assertSee('kode acak 8 karakter')
            ->assertDontSee('Tambah course');

        $this->get(route('dosen.course.create'))->assertOk();

        $postResponse = $this->post(route('dosen.course.store'), [
            'code' => 'IF999',
            'title' => 'Kecerdasan Buatan',
            'description' => 'Deskripsi mata kuliah kecerdasan buatan',
            'lecturer' => 'Dosen Tanpa Kelas',
        ]);

        $section = ClassSection::whereHas('mataKuliah', fn ($q) => $q->where('code', 'IF999'))->first();
        $this->assertNotNull($section);
        $this->assertSame($dosen->id, $section->dosen_id);
        $postResponse->assertRedirect(route('dosen.course.show', $section->id));
    }

    public function test_enrollment_code_is_random_unique_and_uses_the_documented_format(): void
    {
        $this->seed(RoleSeeder::class);
        $prodi = Prodi::create(['code' => 'SI', 'name' => 'Sistem Informasi']);
        $semester = Semester::create(['code' => '2026-2', 'name' => 'Genap 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'SI101', 'name' => 'Pengantar SI', 'sks' => 3]);
        $dosen = User::create([
            'name' => 'Dosen Pengampu',
            'email' => 'pengampu@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'nim_nidn' => 'DSN902',
        ]);

        $first = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
        $second = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'B',
            'capacity' => 40,
        ]);

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $first->enrollment_code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $second->enrollment_code);
        $this->assertNotSame($first->enrollment_code, $second->enrollment_code);
    }

    public function test_invalid_code_returns_to_course_list_and_reopens_join_dialog(): void
    {
        $this->seed(RoleSeeder::class);
        $dosen = User::create([
            'name' => 'Dosen Penguji',
            'email' => 'uji@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', Role::DOSEN)->value('id'),
            'nim_nidn' => 'DSN903',
        ]);

        $this->actingAs($dosen)
            ->get(route('mahasiswa.join-kelas', 'SALAH123'))
            ->assertRedirect(route('dosen.course.index'))
            ->assertSessionHas('join_error', 'Kelas tidak ditemukan. Periksa kembali kode kelas yang dimasukkan.');

        $this->get(route('dosen.course.index'))
            ->assertOk()
            ->assertSee('Kelas tidak ditemukan. Periksa kembali kode kelas yang dimasukkan.')
            ->assertSee('showModal()', false);
    }

    public function test_empty_class_is_only_visible_after_each_user_joins(): void
    {
        $this->seed(RoleSeeder::class);
        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $studentRole = Role::where('name', Role::MAHASISWA)->firstOrFail();
        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknologi Informasi']);
        $semester = Semester::create(['code' => '2027-1', 'name' => 'Ganjil 2027/2028', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'TI101', 'name' => 'Kelas Kosong', 'sks' => 3]);
        $dosen = User::create([
            'name' => 'Dosen Baru',
            'email' => 'baru@example.test',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => 'DSN904',
        ]);
        $student = User::create([
            'name' => 'Mahasiswa Baru',
            'email' => 'mahasiswa.baru@example.test',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'nim_nidn' => 'MHS904',
        ]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => null,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'EMPTY001',
        ]);

        $this->actingAs($dosen)
            ->get(route('dosen.course.index'))
            ->assertOk()
            ->assertDontSee('Kelas Kosong')
            ->assertSee('Belum ada kelas.')
            ->assertDontSee('Kelas tidak ditemukan.');

        $this->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertDontSee('Kelas Kosong')
            ->assertSee('Belum ada kelas.');

        $this->post(route('mahasiswa.join-kelas.post', $section->enrollment_code))
            ->assertOk()
            ->assertSee('berhasil bergabung sebagai Dosen Ketua');

        $this->assertSame($dosen->id, $section->fresh()->dosen_id);
        $this->get(route('dosen.dashboard'))->assertOk()->assertSee('Kelas Kosong');

        $this->actingAs($student)
            ->get(route('mahasiswa.course.index'))
            ->assertOk()
            ->assertDontSee('Kelas Kosong')
            ->assertSee('Belum ada kelas.')
            ->assertDontSee('Kelas tidak ditemukan.');

        $this->get(route('mahasiswa.course.index', ['q' => 'KODE-SALAH']))
            ->assertOk()
            ->assertSee('Kelas tidak ditemukan.')
            ->assertDontSee('Belum ada kelas.');

        $this->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertDontSee('Kelas Kosong')
            ->assertSee('Belum ada kelas.');

        $this->post(route('mahasiswa.join-kelas.post', $section->enrollment_code))->assertOk();
        $this->get(route('mahasiswa.course.index'))
            ->assertOk()
            ->assertSee('Kelas Kosong');
        $this->get(route('mahasiswa.dashboard'))->assertOk()->assertSee('Kelas Kosong');
    }

    public function test_direct_join_from_popup_modal_enrolls_and_redirects_to_course(): void
    {
        $this->seed(RoleSeeder::class);
        $studentRole = Role::where('name', Role::MAHASISWA)->firstOrFail();
        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF801', 'name' => 'Pemrograman Web', 'sks' => 3]);
        $student = User::create([
            'name' => 'Mahasiswa PopUp',
            'email' => 'mhs.popup@example.test',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'nim_nidn' => 'MHS801',
        ]);
        $section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'section_code' => 'A',
            'capacity' => 40,
            'enrollment_code' => 'POPUP001',
        ]);

        $this->actingAs($student);

        // Invalid code returns back with join_error
        $invalidResponse = $this->from(route('mahasiswa.course.index'))
            ->post(route('mahasiswa.join-kelas.direct'), ['code' => 'WRONGCODE']);
        $invalidResponse->assertRedirect(route('mahasiswa.course.index'))
            ->assertSessionHas('join_error');

        // Valid code directly enrolls and redirects to course show
        $validResponse = $this->from(route('mahasiswa.course.index'))
            ->post(route('mahasiswa.join-kelas.direct'), ['code' => 'POPUP001']);
        $validResponse->assertRedirect(route('mahasiswa.course.show', $section->id))
            ->assertSessionHas('notice');

        $this->assertTrue($section->students()->where('users.id', $student->id)->exists());
    }
}
