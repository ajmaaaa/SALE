<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\StudentRubricScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExcelImportAndPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $otherDosen;
    private User $student1;
    private User $student2;
    private ClassSection $section;
    private Assessment $directAssessment;
    private Assessment $rubricAssessment;
    private RubricCriterion $crit1;
    private RubricCriterion $crit2;

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

        $cpmk = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-01', 'description' => 'Konsep Dasar']);

        // Direct assessment (Non-rubrik)
        $this->directAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'Ujian Tengah Semester',
            'type' => 'uts',
            'final_weight' => 20,
            'status' => 'published',
            'uses_rubric' => false,
        ]);
        $this->directAssessment->cpmks()->attach($cpmk->id, ['weight' => 100]);

        // Rubric assessment
        $this->rubricAssessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas 1 Pemrograman',
            'type' => 'tugas',
            'final_weight' => 15,
            'status' => 'published',
            'uses_rubric' => true,
        ]);
        $this->rubricAssessment->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $rubric = Rubric::create(['assessment_id' => $this->rubricAssessment->id, 'name' => 'Rubrik Tugas 1']);
        $this->crit1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Pemahaman Algoritma',
            'weight' => 40,
            'max_score' => 100,
            'order' => 1,
        ]);
        $this->crit2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Implementasi Kode',
            'weight' => 60,
            'max_score' => 100,
            'order' => 2,
        ]);
    }

    public function test_dosen_can_upload_csv_for_direct_assessment_and_sees_preview_with_valid_data(): void
    {
        $csvContent = "NIM,Nama Mahasiswa,Nilai (0-100),Catatan\n"
                    . "2024081001,Budi Santoso,85.5,Baik sekali\n"
                    . "2024081002,Siti Rahma,78.0,Cukup memuaskan\n";

        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.upload', [$this->section->id, $this->directAssessment->id]), [
                'file' => $file,
            ]);

        $response->assertOk()
            ->assertViewIs('dosen.penilaian.import-preview')
            ->assertSee('Preview & Validasi Impor')
            ->assertSee('2024081001')
            ->assertSee('Budi Santoso')
            ->assertSee('85.5')
            ->assertSee('2024081002')
            ->assertSee('78');

        $this->assertNotNull(session("import_preview_{$this->directAssessment->id}"));
    }

    public function test_preview_correctly_detects_and_displays_invalid_rows_and_errors(): void
    {
        // Baris 1: Valid
        // Baris 2: NIM tidak terdaftar
        // Baris 3: Skor melebihi batas 100
        $csvContent = "NIM,Nama Mahasiswa,Nilai (0-100),Catatan\n"
                    . "2024081001,Budi Santoso,80,Bagus\n"
                    . "9999999999,Orang Luar,90,Tidak ada\n"
                    . "2024081002,Siti Rahma,150,Kelebihan\n";

        $file = UploadedFile::fake()->createWithContent('nilai.csv', $csvContent);

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.upload', [$this->section->id, $this->directAssessment->id]), [
                'file' => $file,
            ]);

        $response->assertOk()
            ->assertSee('Preview & Validasi Impor')
            ->assertSee('Data Bermasalah (Ditolak)')
            ->assertSee('tidak terdaftar pada kelas ini')
            ->assertSee('Nilai harus antara 0 dan 100')
            ->assertSee('Perhatian Integritas Data OBE');

        $sessionData = session("import_preview_{$this->directAssessment->id}");
        $this->assertCount(1, $sessionData['valid_rows']);
        $this->assertCount(2, $sessionData['invalid_rows']);
    }

    public function test_cancel_import_clears_session_and_makes_no_changes_to_database(): void
    {
        $csvContent = "NIM,Nama Mahasiswa,Nilai (0-100),Catatan\n"
                    . "2024081001,Budi Santoso,85,Bagus\n";
        $file = UploadedFile::fake()->createWithContent('nilai.csv', $csvContent);

        // 1. Upload file
        $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.upload', [$this->section->id, $this->directAssessment->id]), [
                'file' => $file,
            ]);

        $this->assertNotNull(session("import_preview_{$this->directAssessment->id}"));

        // 2. Batalkan impor
        $cancelResponse = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen.import.cancel', [$this->section->id, $this->directAssessment->id]));

        $cancelResponse->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->directAssessment->id]))
            ->assertSessionHas('notice', 'Impor nilai dibatalkan. Tidak ada data yang disimpan.');

        $this->assertNull(session("import_preview_{$this->directAssessment->id}"));
        $this->assertDatabaseMissing('student_assessment_scores', [
            'assessment_id' => $this->directAssessment->id,
            'mahasiswa_id' => $this->student1->id,
        ]);
    }

    public function test_confirm_import_saves_only_valid_rows_and_skips_invalid_rows(): void
    {
        $csvContent = "NIM,Nama Mahasiswa,Nilai (0-100),Catatan\n"
                    . "2024081001,Budi Santoso,82.5,Catatan valid\n"
                    . "9999999999,Hantu,100,Tidak terdaftar\n";

        $file = UploadedFile::fake()->createWithContent('nilai.csv', $csvContent);

        // 1. Upload
        $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.upload', [$this->section->id, $this->directAssessment->id]), [
                'file' => $file,
            ]);

        // 2. Confirm
        $confirmResponse = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.confirm', [$this->section->id, $this->directAssessment->id]));

        $confirmResponse->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->directAssessment->id]))
            ->assertSessionHas('notice', 'Berhasil mengimpor dan menyimpan nilai untuk 1 mahasiswa.');

        // Verify valid row is committed to DB
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->directAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 82.50,
            'feedback' => 'Catatan valid',
        ]);

        // Session cleared
        $this->assertNull(session("import_preview_{$this->directAssessment->id}"));
    }

    public function test_dosen_can_upload_and_import_rubric_csv_with_automatic_assessment_score_calculation(): void
    {
        // Crit 1 = 40%, Crit 2 = 60%
        // Student 1: Crit 1 = 80, Crit 2 = 90 -> Nilai Akhir = 32 + 54 = 86.00
        // Student 2: Crit 1 = 70, Crit 2 = 60 -> Nilai Akhir = 28 + 36 = 64.00
        $csvContent = "NIM,Nama Mahasiswa,Pemahaman Algoritma (40%),Implementasi Kode (60%),Catatan\n"
                    . "2024081001,Budi Santoso,80,90,Mantap\n"
                    . "2024081002,Siti Rahma,70,60,Cukup\n";

        $file = UploadedFile::fake()->createWithContent('rubrik.csv', $csvContent);

        // Upload & Preview
        $preview = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.upload', [$this->section->id, $this->rubricAssessment->id]), [
                'file' => $file,
            ]);

        $preview->assertOk()
            ->assertSee('86')
            ->assertSee('64');

        // Confirm
        $confirm = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.import.confirm', [$this->section->id, $this->rubricAssessment->id]));

        $confirm->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $this->rubricAssessment->id]));

        // Check rubric criteria scores
        $this->assertDatabaseHas('student_rubric_scores', [
            'rubric_criterion_id' => $this->crit1->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 80.00,
        ]);
        $this->assertDatabaseHas('student_rubric_scores', [
            'rubric_criterion_id' => $this->crit2->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 90.00,
        ]);

        // Check calculated assessment score in student_assessment_scores
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->rubricAssessment->id,
            'mahasiswa_id' => $this->student1->id,
            'score' => 86.00,
            'feedback' => 'Mantap',
        ]);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_id' => $this->rubricAssessment->id,
            'mahasiswa_id' => $this->student2->id,
            'score' => 64.00,
            'feedback' => 'Cukup',
        ]);
    }

    public function test_unauthorized_dosen_cannot_upload_or_confirm_import(): void
    {
        $csvContent = "NIM,Nama Mahasiswa,Nilai (0-100),Catatan\n2024081001,Budi,80,Ok\n";
        $file = UploadedFile::fake()->createWithContent('nilai.csv', $csvContent);

        $upload = $this->actingAs($this->otherDosen)
            ->post(route('dosen.penilaian.asesmen.import.upload', [$this->section->id, $this->directAssessment->id]), [
                'file' => $file,
            ]);
        $upload->assertStatus(403);

        $confirm = $this->actingAs($this->otherDosen)
            ->post(route('dosen.penilaian.asesmen.import.confirm', [$this->section->id, $this->directAssessment->id]));
        $confirm->assertStatus(403);
    }
}
