<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArchivedCourseDashboardAndGradingMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $mahasiswa;
    private Prodi $prodi;
    private Semester $semester;
    private ClassSection $activeSection;
    private ClassSection $archivedSection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mahasiswaRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
        ]);

        $this->semester = Semester::create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);

        $mk1 = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF101',
            'name' => 'Pemrograman Web Aktif',
            'sks' => 3,
        ]);

        $mk2 = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF102',
            'name' => 'Algoritma Lampau Diarsipkan',
            'sks' => 3,
        ]);

        $this->dosen = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => 'DS100',
        ]);
        $this->dosen->roles()->syncWithoutDetaching([$dosenRole->id]);

        $this->mahasiswa = User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti@test.com',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '2024001',
        ]);
        $this->mahasiswa->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        // 1. Active class
        $this->activeSection = ClassSection::create([
            'mata_kuliah_id' => $mk1->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);

        // 2. Archived class
        $this->archivedSection = ClassSection::create([
            'mata_kuliah_id' => $mk2->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'B',
            'capacity' => 40,
            'archived_at' => now(),
            'archived_by' => $this->dosen->id,
        ]);

        // Enroll mahasiswa in both classes
        $this->activeSection->students()->attach($this->mahasiswa->id, ['status' => 'enrolled']);
        $this->archivedSection->students()->attach($this->mahasiswa->id, ['status' => 'enrolled']);
    }

    public function test_archived_course_does_not_appear_on_mahasiswa_dashboard(): void
    {
        $response = $this->actingAs($this->mahasiswa)->get(route('mahasiswa.dashboard'));
        $response->assertOk();

        // Active course appears, archived course does not appear on dashboard
        $response->assertSee('Pemrograman Web Aktif');
        $response->assertDontSee('Algoritma Lampau Diarsipkan');
    }

    public function test_archived_course_does_not_appear_on_dosen_dashboard(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.dashboard'));
        $response->assertOk();

        // Active course appears, archived course does not appear on dashboard
        $response->assertSee('Pemrograman Web Aktif');
        $response->assertDontSee('Algoritma Lampau Diarsipkan');
    }

    public function test_dosen_penilaian_separates_active_and_archived_classes_into_tabs(): void
    {
        // 1. Default Tab (Kelas Aktif): shows active class only
        $responseActive = $this->actingAs($this->dosen)->get(route('dosen.penilaian.index'));
        $responseActive->assertOk();
        $responseActive->assertSee('Pemrograman Web Aktif');
        $responseActive->assertDontSee('Algoritma Lampau Diarsipkan');
        $responseActive->assertSee('Kelas Aktif');
        $responseActive->assertSee('Arsip Kelas');

        // Check badge counts
        $responseActive->assertSee('tab=active', false);
        $responseActive->assertSee('tab=archived', false);

        // 2. Tab Arsip Kelas: shows archived class only
        $responseArchived = $this->actingAs($this->dosen)->get(route('dosen.penilaian.index', ['tab' => 'archived']));
        $responseArchived->assertOk();
        $responseArchived->assertDontSee('Pemrograman Web Aktif');
        $responseArchived->assertSee('Algoritma Lampau Diarsipkan');
        // Yellow Arsip label is not displayed on the cards
        $responseArchived->assertDontSee('border-amber-200', false);
    }

    public function test_dosen_rekap_separates_active_and_archived_classes_into_tabs(): void
    {
        // 1. Default Tab (Kelas Aktif): shows active class only
        $responseActive = $this->actingAs($this->dosen)->get(route('dosen.rekap.index'));
        $responseActive->assertOk();
        $responseActive->assertSee('Pemrograman Web Aktif');
        $responseActive->assertDontSee('Algoritma Lampau Diarsipkan');
        $responseActive->assertSee('Kelas Aktif');
        $responseActive->assertSee('Arsip Kelas');

        // 2. Tab Arsip Kelas: shows archived class only
        $responseArchived = $this->actingAs($this->dosen)->get(route('dosen.rekap.index', ['tab' => 'archived']));
        $responseArchived->assertOk();
        $responseArchived->assertDontSee('Pemrograman Web Aktif');
        $responseArchived->assertSee('Algoritma Lampau Diarsipkan');
        // Yellow Arsip label is not displayed on the cards
        $responseArchived->assertDontSee('border-amber-200', false);
    }
}
