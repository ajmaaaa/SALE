<?php

namespace Tests\Feature;

use App\Models\Assessment;
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

class DosenCourseItemActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_sees_kelola_materi_and_edit_delete_icons_on_course_page(): void
    {
        $this->seed(RoleSeeder::class);
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['name' => 'Ganjil 2026/2027', 'code' => '20261', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'IF101', 'name' => 'Algoritma Pemrograman', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $dosen = User::create([
            'name' => 'Dr. Dosen',
            'email' => 'dosen@test.local',
            'nim_nidn' => '12345678',
            'password' => Hash::make('secret'),
            'role_id' => $dosenRoleId,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $student = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mhs@test.local',
            'nim_nidn' => '98765432',
            'password' => Hash::make('secret'),
            'role_id' => $mhsRoleId,
        ]);
        $student->roles()->sync([$mhsRoleId]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);
        $section->students()->attach($student->id);

        $materi = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'MAT-01',
            'name' => 'Materi Pengenalan Python',
            'type' => 'materi',
            'final_weight' => 0,
            'status' => 'published',
            'learning_payload' => [
                'module' => 'Modul 1: Dasar Python',
                'body' => 'Pengenalan sintaks dasar python.',
            ],
        ]);

        $tugas = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1 Variabel',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'learning_payload' => [
                'module' => 'Modul 1: Dasar Python',
                'body' => 'Kerjakan latihan variabel.',
            ],
        ]);

        // Dosen view
        $dosenResponse = $this->actingAs($dosen)->get(route('dosen.course.show', $section->id));
        $dosenResponse->assertOk();
        $dosenResponse->assertDontSee('Kelola Materi');
        $dosenResponse->assertSee(route('dosen.item.edit', [$section->id, $materi->id]));
        $dosenResponse->assertSee(route('dosen.item.destroy', [$section->id, $materi->id]));
        $dosenResponse->assertSee(route('dosen.item.edit', [$section->id, $tugas->id]));
        $dosenResponse->assertSee(route('dosen.item.destroy', [$section->id, $tugas->id]));

        // Mahasiswa view
        $mhsResponse = $this->actingAs($student)->get(route('mahasiswa.course.show', $section->id));
        $mhsResponse->assertOk();
        $mhsResponse->assertSee('Buka Materi');
        $mhsResponse->assertDontSee(route('dosen.item.edit', [$section->id, $materi->id]));
        $mhsResponse->assertDontSee(route('dosen.item.destroy', [$section->id, $materi->id]));

        // Item detail view - Dosen
        $dosenItemResponse = $this->actingAs($dosen)->get(route('dosen.course.item', [$section->id, $materi->id]));
        $dosenItemResponse->assertOk();
        $dosenItemResponse->assertSee(route('dosen.item.edit', [$section->id, $materi->id]));
        $dosenItemResponse->assertSee(route('dosen.item.destroy', [$section->id, $materi->id]));

        // Item detail view - Mahasiswa
        $mhsItemResponse = $this->actingAs($student)->get(route('mahasiswa.course.item', [$section->id, $materi->id]));
        $mhsItemResponse->assertOk();
        $mhsItemResponse->assertDontSee(route('dosen.item.edit', [$section->id, $materi->id]));
        $mhsItemResponse->assertDontSee(route('dosen.item.destroy', [$section->id, $materi->id]));
    }
}
