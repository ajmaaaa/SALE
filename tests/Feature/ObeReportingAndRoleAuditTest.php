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
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ObeReportingAndRoleAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;

    private User $otherDosen;

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
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data']);

        $this->dosen = User::create(['name' => 'Dosen Pengampu', 'email' => 'dosen@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->otherDosen = User::create(['name' => 'Dosen Lain', 'email' => 'other@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);

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
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
            'graded_at' => now(),
            'published_at' => now(),
        ]);
        StudentAssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 55,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
            'graded_at' => now(),
            'published_at' => now(),
        ]);
    }

    public function test_dosen_can_export_rekap_csv(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.rekap.export', $this->section->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_dosen_can_export_cpmk_csv(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.cpmk.export', $this->section->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_dosen_can_export_cpl_csv(): void
    {
        $response = $this->actingAs($this->dosen)->get(route('dosen.penilaian.cpl.export', $this->section->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_xlsx_export_sanitizes_student_name_against_formula_injection(): void
    {
        $this->student1->update(['name' => '=1+1', 'nim_nidn' => '+62812345']);
        $this->student2->update(['name' => "@SUM(A1:A2)\tTest", 'nim_nidn' => '-999']);

        $endpoints = [
            route('dosen.penilaian.rekap.export.excel', $this->section->id),
            route('dosen.penilaian.cpmk.export.excel', $this->section->id),
            route('dosen.penilaian.cpl.export.excel', $this->section->id),
            route('dosen.penilaian.export.nilai.excel', $this->section->id),
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($this->dosen)->get($url);
            $response->assertOk();

            $path = tempnam(sys_get_temp_dir(), 'test-xlsx-');
            try {
                file_put_contents($path, $response->streamedContent());
                $zip = new \ZipArchive;
                $this->assertTrue($zip->open($path));

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (str_starts_with($filename, 'xl/worksheets/')) {
                        $sheetXml = $zip->getFromIndex($i);
                        $this->assertStringNotContainsString('<f>1+1</f>', $sheetXml);
                        $this->assertStringNotContainsString('<f>=1+1</f>', $sheetXml);
                        $this->assertStringNotContainsString('<f>SUM', $sheetXml);
                    }
                }

                $sharedStrings = $zip->getFromName('xl/sharedStrings.xml') ?: '';
                $zip->close();

                $this->assertTrue(
                    str_contains($sharedStrings, "'=1+1") || str_contains($sheetXml, "'=1+1")
                );
            } finally {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
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

    public function test_assistant_lecturer_uses_the_same_class_management_policy_as_primary_lecturer(): void
    {
        $outsider = User::create([
            'name' => 'Dosen Luar',
            'email' => 'outsider@test.local',
            'password' => 'secret',
            'role_id' => $this->otherDosen->role_id,
        ]);
        $this->section->update(['dosen_pendamping_id' => $this->otherDosen->id]);
        $this->section->refresh();

        $this->assertTrue(Gate::forUser($this->dosen)->allows('manage', $this->section));
        $this->assertTrue(Gate::forUser($this->otherDosen)->allows('manage', $this->section));
        $this->assertFalse(Gate::forUser($outsider)->allows('manage', $this->section));

        $this->actingAs($this->otherDosen)
            ->get(route('dosen.penilaian.dashboard', $this->section))
            ->assertRedirect(route('dosen.penilaian.asesmen', $this->section));
        $this->get(route('dosen.penilaian.asesmen.edit', [$this->section, $this->assessment]))
            ->assertOk();
        $this->get(route('dosen.penilaian.asesmen.nilai', [$this->section, $this->assessment]))
            ->assertOk();
        $this->get(route('dosen.penilaian.asesmen.rubrik', [$this->section, $this->assessment]))
            ->assertOk();
        $this->get(route('dosen.penilaian.rekap.export', $this->section))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(route('dosen.penilaian.asesmen.rubrik', [$this->section, $this->assessment]))
            ->assertForbidden();
        $this->get(route('dosen.penilaian.rekap.export', $this->section))
            ->assertForbidden();
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

    public function test_mahasiswa_cannot_access_dosen_pages(): void
    {
        // Mahasiswa attempts to access Dosen penilaian
        $resDosen = $this->actingAs($this->student1)->get(route('dosen.penilaian.rekap', $this->section->id));
        $resDosen->assertStatus(403);

        // Mahasiswa attempts to export Dosen rekap
        $resExport = $this->actingAs($this->student1)->get(route('dosen.penilaian.rekap.export', $this->section->id));
        $resExport->assertStatus(403);
    }
}
