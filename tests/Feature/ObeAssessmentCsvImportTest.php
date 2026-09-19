<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ObeAssessmentCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $student1;
    private User $student2;
    private ClassSection $section;
    private Cpmk $cpmk1;
    private Cpmk $cpmk2;
    private Assessment $multiAssessment;
    private Assessment $singleAssessment;
    private ObeCalculationService $obe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obe = app(ObeCalculationService::class);

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026', 'is_active' => true]);
        $mk = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF201', 'name' => 'Struktur Data']);

        $this->dosen = User::create(['name' => 'Dosen Test', 'email' => 'dosen@test.local', 'password' => 'x', 'role_id' => $dosenRole->id]);
        $this->student1 = User::create(['name' => 'Ahmad Santoso', 'nim_nidn' => '2026001', 'email' => 'ahmad@test.local', 'password' => 'x', 'role_id' => $mahasiswaRole->id]);
        $this->student2 = User::create(['name' => 'Budi Pratama', 'nim_nidn' => '2026002', 'email' => 'budi@test.local', 'password' => 'x', 'role_id' => $mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mk->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
        ]);

        $this->section->students()->attach([$this->student1->id, $this->student2->id]);

        $this->cpmk1 = Cpmk::create(['mata_kuliah_id' => $mk->id, 'code' => 'CPMK-01', 'description' => 'Desc 1', 'threshold' => 65]);
        $this->cpmk2 = Cpmk::create(['mata_kuliah_id' => $mk->id, 'code' => 'CPMK-02', 'description' => 'Desc 2', 'threshold' => 65]);

        // Multi-CPMK: CPMK-01 (60%), CPMK-02 (40%)
        $this->multiAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-MULTI',
            'name' => 'UTS Multi CPMK',
            'type' => 'uts',
            'final_weight' => 50,
        ]);
        $this->multiAssessment->cpmks()->attach($this->cpmk1->id, ['weight' => 60]);
        $this->multiAssessment->cpmks()->attach($this->cpmk2->id, ['weight' => 40]);

        // Single-CPMK: CPMK-01 (100%)
        $this->singleAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-SINGLE',
            'name' => 'Tugas Single CPMK',
            'type' => 'tugas',
            'final_weight' => 20,
        ]);
        $this->singleAssessment->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);
    }

    private function makeCsvFile(string $content, string $filename = 'test.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    /**
     * Uji CSV multi-CPMK 60/40 berhasil:
     * - Nilai disimpan sebagai poin proporsional di StudentAssessmentCpmkScore
     * - Total 60 + 40 = 100.0
     * - Total 30 + 20 = 50.0
     */
    public function test_csv_multi_cpmk_60_40_successful_import_and_saved_as_proportional_points(): void
    {
        $csvContent = "NIM;Nama;CPMK-01;CPMK-02\n"
            . "2026001;Ahmad Santoso;60;40\n"
            . "2026002;Budi Pratama;30;20\n";

        $file = $this->makeCsvFile($csvContent);

        // Phase 1: Upload & Preview
        $res = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->multiAssessment->id]),
            ['file' => $file]
        );

        $res->assertRedirect(route('dosen.penilaian.asesmen.nilai.import', [$this->section->id, $this->multiAssessment->id]));
        $this->assertTrue(session('import_preview_ready'));
        $preview = session('import_preview');
        $this->assertSame('cpmk', $preview['mode']);
        $this->assertCount(2, $preview['rows']);

        // Phase 2: Confirm & Save
        $resConfirm = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->multiAssessment->id]),
            ['confirm' => 1]
        );

        $resConfirm->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->multiAssessment->id]));

        // Verifikasi StudentAssessmentCpmkScore (poin proporsional)
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $this->multiAssessment->id,
            'cpmk_id' => $this->cpmk1->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 60.00,
        ]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $this->multiAssessment->id,
            'cpmk_id' => $this->cpmk2->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 40.00,
        ]);

        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $this->multiAssessment->id,
            'cpmk_id' => $this->cpmk1->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 30.00,
        ]);
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $this->multiAssessment->id,
            'cpmk_id' => $this->cpmk2->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 20.00,
        ]);

        // Verifikasi StudentAssessmentScore (Total penjumlahan poin)
        // Student 1: 60 + 40 = 100.0
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->multiAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 100.00,
        ]);

        // Student 2: 30 + 20 = 50.0
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->multiAssessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 50.00,
        ]);

        // Verifikasi normalisasi di ObeCalculationService
        $this->assertEquals(100.0, $this->obe->cpmkScore($this->cpmk1->fresh(), $this->student1->id, $this->section->id));
        $this->assertEquals(100.0, $this->obe->cpmkScore($this->cpmk2->fresh(), $this->student1->id, $this->section->id));

        $this->assertEquals(50.0, $this->obe->cpmkScore($this->cpmk1->fresh(), $this->student2->id, $this->section->id));
        $this->assertEquals(50.0, $this->obe->cpmkScore($this->cpmk2->fresh(), $this->student2->id, $this->section->id));
    }

    /**
     * Uji nilai melebihi batas proporsional ditolak dengan error yang jelas.
     * CPMK-02 maksimal 40, jika diinput 50 atau 100 harus ditolak.
     */
    public function test_csv_multi_cpmk_rejects_scores_exceeding_maximum(): void
    {
        // Ahmad CPMK-02 diisi 50 (melebihi 40), Budi CPMK-01 diisi 70 (melebihi 60)
        $csvContent = "NIM;Nama;CPMK-01;CPMK-02\n"
            . "2026001;Ahmad Santoso;60;50\n"
            . "2026002;Budi Pratama;70;40\n";

        $file = $this->makeCsvFile($csvContent);

        $res = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->multiAssessment->id]),
            ['file' => $file]
        );

        $res->assertSessionHas('import_errors');
        $errors = session('import_errors');

        $this->assertTrue(collect($errors)->contains(fn ($msg) => str_contains($msg, 'Baris 2') && str_contains($msg, 'CPMK-02') && str_contains($msg, 'melebihi batas maksimal 40')));
        $this->assertTrue(collect($errors)->contains(fn ($msg) => str_contains($msg, 'Baris 3') && str_contains($msg, 'CPMK-01') && str_contains($msg, 'melebihi batas maksimal 60')));
    }

    /**
     * Uji nilai kosong pada CSV multi-CPMK tetap NULL (bukan 0).
     */
    public function test_csv_multi_cpmk_empty_values_become_null(): void
    {
        // Ahmad: CPMK-01 diisi 30, CPMK-02 kosong
        // Budi: kedua CPMK kosong
        $csvContent = "NIM;Nama;CPMK-01;CPMK-02\n"
            . "2026001;Ahmad Santoso;30;\n"
            . "2026002;Budi Pratama;;\n";

        $file = $this->makeCsvFile($csvContent);

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->multiAssessment->id]),
            ['file' => $file]
        );

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->multiAssessment->id]),
            ['confirm' => 1]
        );

        // Ahmad: CPMK-01 = 30, CPMK-02 = null
        $scoreAhmadCpmk1 = StudentAssessmentCpmkScore::where('assessment_id', $this->multiAssessment->id)
            ->where('cpmk_id', $this->cpmk1->id)
            ->where('mahasiswa_id', $this->student1->id)
            ->value('score');
        $this->assertEquals(30.0, (float) $scoreAhmadCpmk1);

        $scoreAhmadCpmk2 = StudentAssessmentCpmkScore::where('assessment_id', $this->multiAssessment->id)
            ->where('cpmk_id', $this->cpmk2->id)
            ->where('mahasiswa_id', $this->student1->id)
            ->value('score');
        $this->assertNull($scoreAhmadCpmk2);

        // Budi: CPMK-01 = null, CPMK-02 = null
        $scoreBudiCpmk1 = StudentAssessmentCpmkScore::where('assessment_id', $this->multiAssessment->id)
            ->where('cpmk_id', $this->cpmk1->id)
            ->where('mahasiswa_id', $this->student2->id)
            ->value('score');
        $this->assertNull($scoreBudiCpmk1);

        $scoreBudiTotal = StudentAssessmentScore::where('assessment_id', $this->multiAssessment->id)
            ->where('mahasiswa_id', $this->student2->id)
            ->value('score');
        $this->assertNull($scoreBudiTotal);

        // Fallback protection: CPMK-02 Ahmad tetap null (tidak mengambil 30.0)
        $this->assertNull($this->obe->studentScoreForAssessmentCpmk($this->multiAssessment, $this->cpmk2, $this->student1->id));
    }

    /**
     * Uji kolom CPMK yang tidak ada pada assessment ditolak dengan pesan error yang jelas.
     */
    public function test_csv_rejects_cpmk_not_attached_to_assessment(): void
    {
        $csvContent = "NIM;Nama;CPMK-UNKNOWN;CPMK-02\n"
            . "2026001;Ahmad Santoso;60;40\n";

        $file = $this->makeCsvFile($csvContent);

        $res = $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->multiAssessment->id]),
            ['file' => $file]
        );

        $res->assertSessionHasErrors('file');
        $errorMsg = session('errors')->first('file');
        $this->assertStringContainsString("CPMK-UNKNOWN", $errorMsg);
        $this->assertStringContainsString("bukan CPMK yang diukur oleh asesmen ini", $errorMsg);
    }

    /**
     * Uji CSV single CPMK dengan header kode CPMK berhasil.
     */
    public function test_csv_single_cpmk_with_cpmk_header_success(): void
    {
        $csvContent = "NIM;Nama;CPMK-01\n"
            . "2026001;Ahmad Santoso;85\n"
            . "2026002;Budi Pratama;75\n";

        $file = $this->makeCsvFile($csvContent);

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->singleAssessment->id]),
            ['file' => $file]
        );

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->singleAssessment->id]),
            ['confirm' => 1]
        );

        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $this->singleAssessment->id,
            'cpmk_id' => $this->cpmk1->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 85.00,
        ]);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->singleAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 85.00,
        ]);
    }

    /**
     * Uji CSV format legacy (NIM;Nama;Nilai;Feedback) tetap berhasil.
     */
    public function test_csv_legacy_format_with_nilai_and_feedback_success(): void
    {
        $csvContent = "NIM;Nama;Nilai;Feedback\n"
            . "2026001;Ahmad Santoso;90;Kerja bagus\n"
            . "2026002;Budi Pratama;75;Tingkatkan lagi\n";

        $file = $this->makeCsvFile($csvContent);

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->singleAssessment->id]),
            ['file' => $file]
        );

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->singleAssessment->id]),
            ['confirm' => 1]
        );

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->singleAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 90.00,
            'feedback' => 'Kerja bagus',
        ]);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->singleAssessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 75.00,
            'feedback' => 'Tingkatkan lagi',
        ]);

        // Karena single assessment punya 1 CPMK, tersinkronkan juga ke CPMK score
        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $this->singleAssessment->id,
            'cpmk_id' => $this->cpmk1->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 90.00,
        ]);
    }

    /**
     * Uji CSV format legacy tanpa feedback (NIM;Nama;Nilai) tetap berhasil.
     */
    public function test_csv_legacy_format_with_nim_nama_nilai_only_success(): void
    {
        $csvContent = "NIM;Nama;Nilai\n"
            . "2026001;Ahmad Santoso;80\n"
            . "2026002;Budi Pratama;70\n";

        $file = $this->makeCsvFile($csvContent);

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->singleAssessment->id]),
            ['file' => $file]
        );

        $this->actingAs($this->dosen)->post(
            route('dosen.penilaian.asesmen.nilai.import.process', [$this->section->id, $this->singleAssessment->id]),
            ['confirm' => 1]
        );

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->singleAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 80.00,
        ]);
    }
}
