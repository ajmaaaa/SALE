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

class KaprodiExportTest extends TestCase
{
    use RefreshDatabase;

    private User $kaprodi;
    private User $mahasiswa;
    private ClassSection $section;
    private Cpmk $cpmk;
    private Cpl $cpl;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $kaprodiRole = Role::create(['name' => Role::KAPRODI, 'label' => 'Kaprodi']);
        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF204', 'name' => 'Struktur Data']);

        $dosen = User::create(['name' => 'Dosen IF', 'email' => 'dosen@test.local', 'password' => 'secret', 'role_id' => $dosenRole->id]);
        $this->kaprodi = User::create(['name' => 'Kaprodi IF', 'email' => 'kaprodi@test.local', 'password' => 'secret', 'role_id' => $kaprodiRole->id]);
        $this->mahasiswa = User::create(['name' => 'Budi Mhs', 'email' => 'mhs@test.local', 'nim_nidn' => '2024081001', 'password' => 'secret', 'role_id' => $mahasiswaRole->id]);
        $this->student = $this->mahasiswa;

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'A',
        ]);
        $this->section->students()->attach($this->student->id);

        $this->cpl = Cpl::create(['prodi_id' => $prodi->id, 'code' => 'CPL-01', 'description' => 'Mampu merancang struktur data']);
        $this->cpmk = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-01', 'description' => 'Konsep Dasar Algoritma', 'threshold' => 65]);
        $this->cpl->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'final_weight' => 100,
            'status' => 'published',
            'uses_rubric' => false,
        ]);
        $assessment->cpmks()->attach($this->cpmk->id, ['weight' => 100]);

        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 85,
        ]);
    }

    public function test_monitoring_pages_do_not_contain_redundant_header_buttons(): void
    {
        // On CPMK monitoring page: "Lihat Monitoring CPL" button is removed, sidebar link to export exists
        $responseCpmk = $this->actingAs($this->kaprodi)->get(route('kaprodi.monitoring.cpmk'));
        $responseCpmk->assertOk();
        $responseCpmk->assertDontSee('Lihat Monitoring CPL');
        $responseCpmk->assertSee('Export Rekap Nilai');

        // On CPL monitoring page: "Kembali ke Monitoring CPMK" button is removed, sidebar link to export exists
        $responseCpl = $this->actingAs($this->kaprodi)->get(route('kaprodi.monitoring.cpl'));
        $responseCpl->assertOk();
        $responseCpl->assertDontSee('Kembali ke Monitoring CPMK');
        $responseCpl->assertSee('Export Rekap Nilai');
    }

    public function test_kaprodi_can_access_export_index(): void
    {
        $response = $this->actingAs($this->kaprodi)->get(route('kaprodi.export.index'));
        $response->assertOk();
        $response->assertSee('Pusat Ekspor Rekapitulasi Nilai');
        $response->assertSee('Rekap Nilai &amp; Capaian CPMK', false);
        $response->assertSee('Rekapitulasi Capaian CPL');
        $response->assertSee('Download Rekap CPMK (CSV)');
        $response->assertSee('Download Rekap CPL (CSV)');
    }

    public function test_kaprodi_can_export_cpmk_csv(): void
    {
        $response = $this->actingAs($this->kaprodi)->get(route('kaprodi.export.cpmk', [
            'section_id' => $this->section->id,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('rekap_cpmk_IF204_A', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content); // UTF-8 BOM
        $this->assertStringContainsString('CPMK-01', $content);
        $this->assertStringContainsString('Budi Mhs', $content);
        $this->assertStringContainsString('2024081001', $content);
        $this->assertStringContainsString('85.00', $content);
    }

    public function test_kaprodi_can_export_cpl_csv(): void
    {
        // All classes (prodi-wide)
        $responseAll = $this->actingAs($this->kaprodi)->get(route('kaprodi.export.cpl', ['section_id' => 0]));
        $responseAll->assertOk();
        $this->assertStringContainsString('rekap_cpl_prodi_seluruh_kelas', $responseAll->headers->get('content-disposition'));

        $contentAll = $responseAll->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contentAll); // UTF-8 BOM
        $this->assertStringContainsString('CPL-01', $contentAll);
        $this->assertStringContainsString('Budi Mhs', $contentAll);

        // Per section
        $responseSec = $this->actingAs($this->kaprodi)->get(route('kaprodi.export.cpl', ['section_id' => $this->section->id]));
        $responseSec->assertOk();
        $this->assertStringContainsString('rekap_cpl_IF204_A', $responseSec->headers->get('content-disposition'));
        $contentSec = $responseSec->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contentSec);
        $this->assertStringContainsString('CPL-01', $contentSec);
    }

    public function test_non_kaprodi_cannot_access_export(): void
    {
        $response = $this->actingAs($this->mahasiswa)->get(route('kaprodi.export.index'));
        $response->assertStatus(403);
    }
}
