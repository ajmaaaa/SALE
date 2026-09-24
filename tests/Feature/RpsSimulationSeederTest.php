<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Rubric;
use App\Models\User;
use Database\Seeders\RpsSimulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RpsSimulationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_rps_simulation_seeds_expected_database_relations_idempotently(): void
    {
        $this->seed(RpsSimulationSeeder::class);
        $this->seed(RpsSimulationSeeder::class);

        $section = ClassSection::where('enrollment_code', 'DIST305')->firstOrFail();
        $mataKuliah = MataKuliah::where('code', 'IF305')->firstOrFail();
        $cpl = Cpl::where('code', 'CPL05')->firstOrFail();
        $cpmks = Cpmk::whereIn('code', ['CPMK051', 'CPMK052'])->get()->keyBy('code');

        $this->assertSame('Pengembangan Aplikasi Terdistribusi', $mataKuliah->name);
        $this->assertSame(3, $section->students()->count());
        $this->assertSame(1, User::where('email', 'dosen.rps@sale.ac.id')->count());
        $this->assertSame(1, User::where('email', 'aditya@student.sale.ac.id')->count());
        $this->assertSame(2, $cpmks->count());

        $this->assertEquals(41, $cpl->cpmks()->where('code', 'CPMK051')->first()->pivot->weight);
        $this->assertEquals(59, $cpl->cpmks()->where('code', 'CPMK052')->first()->pivot->weight);

        $assessments = Assessment::where('class_section_id', $section->id)->get()->keyBy('code');
        $this->assertSame(4, $assessments->count());
        $this->assertEquals(100, $assessments->sum('final_weight'));
        $this->assertEquals(70, $assessments['CASE']->cpmks()->where('code', 'CPMK051')->first()->pivot->weight);
        $this->assertEquals(30, $assessments['CASE']->cpmks()->where('code', 'CPMK052')->first()->pivot->weight);
        $this->assertTrue($assessments['PJBL']->uses_rubric);

        $rubric = Rubric::where('assessment_id', $assessments['PJBL']->id)->firstOrFail();
        $this->assertEquals(100, $rubric->criteria()->sum('weight'));
        $this->assertSame(1, Assessment::where('class_section_id', $section->id)->where('code', 'PJBL')->count());
    }

    public function test_simulation_accounts_can_login_through_the_portal(): void
    {
        $this->seed(RpsSimulationSeeder::class);

        $studentResponse = $this->post('/login', [
            'login_id' => 'aditya@student.sale.ac.id',
            'password' => 'password123',
            'role' => 'mahasiswa',
        ]);

        $studentResponse->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'aditya@student.sale.ac.id')->firstOrFail());

        $this->post('/logout');

        $lecturerResponse = $this->post('/login', [
            'login_id' => 'dosen.rps@sale.ac.id',
            'password' => 'password123',
            'role' => 'dosen',
        ]);

        $lecturerResponse->assertRedirect(route('dosen.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'dosen.rps@sale.ac.id')->firstOrFail());
    }

    public function test_student_sees_simulation_class_in_dashboard_course_and_obe_pages(): void
    {
        $this->seed(RpsSimulationSeeder::class);
        $student = User::where('email', 'aditya@student.sale.ac.id')->firstOrFail();

        $this->actingAs($student)->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('IF305-A')
            ->assertSee('Pengembangan Aplikasi Terdistribusi');

        $this->actingAs($student)->get(route('mahasiswa.course.index'))
            ->assertOk()
            ->assertSee('IF305-A')
            ->assertSee('Pengembangan Aplikasi Terdistribusi');

        $this->actingAs($student)->get(route('mahasiswa.obe.progress'))
            ->assertOk()
            ->assertSee('CPMK051')
            ->assertSee('CPMK052')
            ->assertSee('CPL05');
    }
}
