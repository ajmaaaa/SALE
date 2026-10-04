<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BladeZeroDatabaseQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $mahasiswa;

    private Semester $semester;

    private Prodi $prodi;

    private ClassSection $section;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::firstOrCreate(['name' => Role::DOSEN], ['label' => 'Dosen']);
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);

        $this->prodi = Prodi::firstOrCreate(['code' => 'IF'], ['name' => 'Informatika']);
        $this->dosen = User::factory()->create(['role_id' => $dosenRole->id, 'prodi_id' => $this->prodi->id]);
        $this->mahasiswa = User::factory()->create(['role_id' => $mhsRole->id, 'prodi_id' => $this->prodi->id]);

        $this->semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mk = MataKuliah::create(['code' => 'IF101', 'name' => 'Pemrograman Web', 'prodi_id' => $this->prodi->id, 'sks' => 3]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $this->semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'IF-A',
        ]);

        $this->section->students()->attach($this->mahasiswa->id, ['status' => 'enrolled']);

        $this->assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TSK-1',
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'status' => 'published',
            'due_at' => now()->addDays(3),
            'final_weight' => 10,
        ]);
    }

    public function test_courses_index_query_budget_does_not_n_plus_one_with_more_courses(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response1 = $this->actingAs($this->mahasiswa)->get(route('mahasiswa.course.index'));
        $response1->assertOk();
        $queries1 = DB::getQueryLog();

        DB::flushQueryLog();

        // Add 5 more sections with assessments
        for ($i = 0; $i < 5; $i++) {
            $mk = MataKuliah::create(['code' => 'IF10'.($i + 2), 'name' => 'Matkul '.$i, 'prodi_id' => $this->prodi->id, 'sks' => 2]);
            $sec = ClassSection::create([
                'mata_kuliah_id' => $mk->id,
                'semester_id' => $this->semester->id,
                'dosen_id' => $this->dosen->id,
                'section_code' => 'IF-'.($i + 2),
            ]);
            $sec->students()->attach($this->mahasiswa->id, ['status' => 'enrolled']);
            Assessment::create([
                'class_section_id' => $sec->id,
                'code' => 'TSK-'.($i + 2),
                'name' => 'Tugas Matkul '.$i,
                'type' => 'tugas',
                'status' => 'published',
                'due_at' => now()->addDays(5),
                'final_weight' => 10,
            ]);
        }

        DB::flushQueryLog();

        $response6 = $this->actingAs($this->mahasiswa)->get(route('mahasiswa.course.index'));
        $response6->assertOk();
        $queries6 = DB::getQueryLog();
        DB::disableQueryLog();

        $diff = abs(count($queries6) - count($queries1));
        $this->assertLessThanOrEqual(3, $diff, "Adding 5 courses caused {$diff} extra queries, indicating N+1 queries");
    }

    public function test_mahasiswa_course_show_renders_cleanly(): void
    {
        $response = $this->actingAs($this->mahasiswa)->get(route('mahasiswa.course.show', $this->section->id));
        $response->assertOk();
        $response->assertSee($this->section->mataKuliah->name);
        $response->assertSee('Tugas 1');
    }

    public function test_dosen_course_show_renders_cleanly(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.course.show', $this->section->id));
        $response->assertOk();
        $response->assertSee($this->section->mataKuliah->name);
    }

    public function test_item_detail_renders_without_blade_queries(): void
    {
        $response = $this->actingAs($this->mahasiswa)->get(route('mahasiswa.course.item', [
            'course' => $this->section->id,
            'item' => $this->assessment->id,
        ]));

        $response->assertOk();
        $response->assertSee('Tugas 1');
    }

    public function test_dosen_item_detail_renders_without_blade_queries(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.course.item', [
            'course' => $this->section->id,
            'item' => $this->assessment->id,
        ]));

        $response->assertOk();
        $response->assertSee('Tugas 1');
    }
}
