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

class GetCourseSideEffectFreeTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $dosen;

    private ClassSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $studentRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknologi Informasi']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026', 'is_active' => true]);
        $mk = MataKuliah::create(['code' => 'TI101', 'name' => 'Algoritma', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $this->dosen = User::factory()->create(['role_id' => $dosenRole->id, 'prodi_id' => $prodi->id]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'prodi_id' => $prodi->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'TI-A',
        ]);

        $this->section->students()->attach($this->student->id, ['status' => 'enrolled']);
    }

    public function test_get_course_does_not_mutate_database_state(): void
    {
        // Setup two materials both having pin_video = true in DB
        $materi1 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MAT-1',
            'name' => 'Materi 1',
            'type' => 'materi',
            'final_weight' => 0,
            'learning_payload' => ['pin_video' => true, 'video' => 'https://youtu.be/test1'],
        ]);

        $materi2 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'MAT-2',
            'name' => 'Materi 2',
            'type' => 'materi',
            'final_weight' => 0,
            'learning_payload' => ['pin_video' => true, 'video' => 'https://youtu.be/test2'],
        ]);

        // Capture DB snapshot of assessments
        $initialState = DB::table('assessments')->select('id', 'learning_payload', 'updated_at')->get()->toArray();

        // Perform GET course as student
        $res1 = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $res1->assertOk();

        // Perform second GET course as student
        $res2 = $this->actingAs($this->student)->get(route('mahasiswa.course.show', $this->section->id));
        $res2->assertOk();

        // DB state must be identical before and after GET requests
        $afterState = DB::table('assessments')->select('id', 'learning_payload', 'updated_at')->get()->toArray();
        $this->assertEquals($initialState, $afterState);
    }
}
