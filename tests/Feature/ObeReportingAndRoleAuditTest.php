<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObeReportingAndRoleAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $otherDosen;
    private User $kaprodi;
    private User $student1;
    private User $student2;
    private ClassSection $section;
    private Assessment $assessment;
    private Cpmk $cpmk;
    private Cpl $cpl;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $kaprodiRole = Role::create(['name' => Role::KAPRODI, 'label' => 'Kaprodi']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data']);

        $this->dosen = User::create(['name' => 'Dosen Pengampu', 'email' => 'dosen@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->otherDosen = User::create(['name' => 'Dosen Lain', 'email' => 'other@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->kaprodi = User::create(['name' => 'Kaprodi IF', 'email' => 'kaprodi@test.local', 'password' => 'secret', 'role_id' => $kaprodiRole->id]);

        $this->student1 = User::create(['name' => 'Budi Santoso', 'email' => 'budi@test.local', 'nim_nidn' => '2024081001', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);
        $this->student2 = User::create(['name' => 'Siti Rahma', 'email' => 'siti@test.local', 'nim_nidn' => '2024081002', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
        ]);

        $this->section->students()->attach([$this->student1->id, $this->student2->id]);

        // CPL & CPMK Setup
        $this->cpl = Cpl::create(['prodi_id' => $prodi->id, 'code' => 'CPL-01', 'description' => 'Mampu merancang struktur data efisien']);
        $this->cpmk = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-01', 'description' => 'Konsep Dasar Algoritma', 'threshold' => 65]);
        $this->cpl->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        // Assessment
        $this->assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'Ujian Tengah Semester',
            'type' => 'uts',
            'final_weight' => 40,
            'status' => 'published',
            'uses_rubric' => false,
        ]);
        $this->assessment->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        // Scores: Budi = 85 (achieved), Siti = 55 (not achieved)
        StudentAssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 85,
        ]);
        StudentAssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 55,
        ]);
    }

    public function test_dosen_can_export_rekap_csv(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap.export', $this->section->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('rekap-nilai-IF204-A', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        // Cek UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Budi Santoso', $content);
        $this->assertStringContainsString('Siti Rahma', $content);
        $this->assertStringContainsString('2024081001', $content);
        $this->assertStringContainsString('Final', $content);
        $this->assertStringContainsString('85.00', $content);
        $this->assertStringContainsString('55.00', $content);
    }

    public function test_dosen_can_export_cpmk_csv(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.cpmk.export', $this->section->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('rekap-cpmk-IF204-A', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('CPMK-01', $content);
        $this->assertStringContainsString('85.0', $content);
        $this->assertStringContainsString('55.0', $content);
    }

    public function test_dosen_can_export_cpl_csv(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.cpl.export', $this->section->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('rekap-cpl-IF204-A', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('CPL-01', $content);
        $this->assertStringContainsString('Tercapai', $content);
        $this->assertStringContainsString('Belum Tercapai', $content);
    }

    public function test_dosen_can_view_printable_rekap(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap.print', $this->section->id));

        $response->assertOk();
        $response->assertSee('Laporan Rekap Nilai', false);
        $response->assertSee('IF204', false);
        $response->assertSee('Struktur Data', false);
        $response->assertSee('Budi Santoso');
        $response->assertSee('Siti Rahma');
        $response->assertSee('@media print', false);
    }

    public function test_unauthorized_dosen_cannot_access_or_export_other_class(): void
    {
        // Other dosen attempts to export
        $response = $this->actingAs($this->otherDosen)->get(route('dosen.penilaian.rekap.export', $this->section->id));
        $response->assertStatus(403);

        // Other dosen attempts to view printable rekap
        $responsePrint = $this->actingAs($this->otherDosen)->get(route('dosen.penilaian.rekap.print', $this->section->id));
        $responsePrint->assertStatus(403);
    }

    public function test_kaprodi_can_monitor_cpmk_and_cpl(): void
    {
        $resCpmk = $this->actingAs($this->kaprodi)->get(route('kaprodi.monitoring.cpmk'));
        $resCpmk->assertOk();
        $resCpmk->assertSee('Monitoring Capaian CPMK Program Studi');
        $resCpmk->assertSee('CPMK-01');

        $resCpl = $this->actingAs($this->kaprodi)->get(route('kaprodi.monitoring.cpl'));
        $resCpl->assertOk();
        $resCpl->assertSee('Monitoring Capaian CPL Program Studi');
        $resCpl->assertSee('CPL-01');
    }

    public function test_kaprodi_cannot_mutate_dosen_assessments_or_grades(): void
    {
        // Kaprodi attempts to post a grade directly
        $response = $this->actingAs($this->kaprodi)->post(route('dosen.penilaian.asesmen.nilai.store', [
            'section' => $this->section->id,
            'assessment' => $this->assessment->id,
        ]), [
            'scores' => [$this->student1->id => 100],
        ]);

        // Dosen middleware restricts /penilaian-kelas to dosen pengampu only
        $response->assertStatus(403);
    }

    public function test_mahasiswa_can_view_own_obe_progress(): void
    {
        $response = $this->actingAs($this->student1)->get(route('mahasiswa.obe.progress'));

        $response->assertOk();
        $response->assertSee('Capaian Pembelajaran OBE &amp; Nilai Mandiri', false);
        $response->assertSee('Budi Santoso');
        $response->assertSee('IF204');
        $response->assertSee('CPMK-01');
        $response->assertSee('85.0');
        $response->assertSee('Tercapai');
    }

    public function test_mahasiswa_cannot_access_dosen_or_kaprodi_pages(): void
    {
        // Mahasiswa attempts to access Kaprodi monitoring
        $resKaprodi = $this->actingAs($this->student1)->get(route('kaprodi.monitoring.cpmk'));
        $resKaprodi->assertStatus(403);

        // Mahasiswa attempts to access Dosen penilaian
        $resDosen = $this->actingAs($this->student1)->get(route('dosen.penilaian.rekap', $this->section->id));
        $resDosen->assertStatus(403);

        // Mahasiswa attempts to export Dosen rekap
        $resExport = $this->actingAs($this->student1)->get(route('dosen.penilaian.rekap.export', $this->section->id));
        $resExport->assertStatus(403);
    }
}
