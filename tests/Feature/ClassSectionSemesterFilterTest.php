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
use Tests\TestCase;

class ClassSectionSemesterFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_penilaian_and_rekap_support_semester_filter_and_search_query(): void
    {
        $this->seed(RoleSeeder::class);

        $lecturer = User::factory()->create();
        $lecturer->roles()->sync([Role::where('name', Role::DOSEN)->first()->id]);

        $prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
            'slug' => 'informatika',
        ]);

        $semesterGanjil = Semester::create([
            'code' => '20241',
            'name' => 'Semester Ganjil 2024/2025',
            'academic_year' => '2024/2025',
            'term' => 1,
            'is_active' => true,
        ]);

        $semesterGenap = Semester::create([
            'code' => '20242',
            'name' => 'Semester Genap 2024/2025',
            'academic_year' => '2024/2025',
            'term' => 2,
            'is_active' => false,
        ]);

        $mk1 = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF101',
            'name' => 'Algoritma Pemrograman',
            'sks' => 3,
        ]);

        $mk2 = MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF202',
            'name' => 'Sistem Basis Data',
            'sks' => 3,
        ]);

        $sectionGanjil = ClassSection::create([
            'mata_kuliah_id' => $mk1->id,
            'semester_id' => $semesterGanjil->id,
            'dosen_id' => $lecturer->id,
            'section_code' => 'A',
            'capacity' => 30,
        ]);

        $sectionGenap = ClassSection::create([
            'mata_kuliah_id' => $mk2->id,
            'semester_id' => $semesterGenap->id,
            'dosen_id' => $lecturer->id,
            'section_code' => 'B',
            'capacity' => 30,
        ]);

        // 1. Without filter: sees both classes on Penilaian and Rekap
        $this->actingAs($lecturer)
            ->get(route('dosen.penilaian.index'))
            ->assertOk()
            ->assertSee('Semua Semester')
            ->assertSee('Algoritma Pemrograman')
            ->assertSee('Sistem Basis Data');

        $this->actingAs($lecturer)
            ->get(route('dosen.rekap.index'))
            ->assertOk()
            ->assertSee('Semua Semester')
            ->assertSee('Algoritma Pemrograman')
            ->assertSee('Sistem Basis Data');

        // 2. Filter by Semester Ganjil: only sees Algoritma Pemrograman
        $this->actingAs($lecturer)
            ->get(route('dosen.penilaian.index', ['semester' => $semesterGanjil->id]))
            ->assertOk()
            ->assertSee('Algoritma Pemrograman')
            ->assertDontSee('Sistem Basis Data');

        $this->actingAs($lecturer)
            ->get(route('dosen.rekap.index', ['semester' => $semesterGanjil->id]))
            ->assertOk()
            ->assertSee('Algoritma Pemrograman')
            ->assertDontSee('Sistem Basis Data');

        // 3. Filter by Semester Genap: only sees Sistem Basis Data
        $this->actingAs($lecturer)
            ->get(route('dosen.penilaian.index', ['semester' => $semesterGenap->id]))
            ->assertOk()
            ->assertSee('Sistem Basis Data')
            ->assertDontSee('Algoritma Pemrograman');

        $this->actingAs($lecturer)
            ->get(route('dosen.rekap.index', ['semester' => $semesterGenap->id]))
            ->assertOk()
            ->assertSee('Sistem Basis Data')
            ->assertDontSee('Algoritma Pemrograman');

        // 4. Search query filter
        $this->actingAs($lecturer)
            ->get(route('dosen.penilaian.index', ['q' => 'Basis Data']))
            ->assertOk()
            ->assertSee('Sistem Basis Data')
            ->assertDontSee('Algoritma Pemrograman');
    }
}
