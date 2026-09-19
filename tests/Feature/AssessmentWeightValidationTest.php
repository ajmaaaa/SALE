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
use App\Models\StudentRubricScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentWeightValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $student;
    private ClassSection $section;
    private Cpmk $cpmk1;
    private Cpmk $cpmk2;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'IF', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'IF301', 'name' => 'Rekayasa Perangkat Lunak']);

        $this->dosen = User::create([
            'name' => 'Dosen Pengampu',
            'email' => 'dosen.rpl@test.local',
            'password' => bcrypt('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '998877',
        ]);

        $this->student = User::create([
            'name' => 'Mahasiswa RPL',
            'email' => 'mhs.rpl@test.local',
            'password' => bcrypt('password'),
            'role_id' => $mahasiswaRole->id,
            'nim_nidn' => '230101',
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 30,
        ]);

        $this->section->students()->attach($this->student->id);

        $this->cpmk1 = Cpmk::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'code' => 'CPMK-1',
            'description' => 'Mampu menyusun kebutuhan dan analisis sistem',
            'threshold' => 60,
        ]);

        $this->cpmk2 = Cpmk::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'code' => 'CPMK-2',
            'description' => 'Mampu merancang dan mengimplementasikan proyek perangkat lunak',
            'threshold' => 60,
        ]);
    }

    public function test_can_create_assessment_when_total_weight_within_100(): void
    {
        // Asesmen 1: Bobot 40% (Total kelas baru 40%)
        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.store', $this->section->id), [
                'code' => 'UTS-01',
                'name' => 'Ujian Tengah Semester',
                'type' => 'uts',
                'final_weight' => 40,
                'status' => 'published',
                'cpmk_selected' => [$this->cpmk1->id => '1'],
                'cpmk' => [$this->cpmk1->id => 100],
            ]);

        $response->assertRedirect(route('dosen.penilaian.asesmen', $this->section->id));
        $response->assertSessionHas('notice');
        $this->assertDatabaseHas('assessments', [
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'final_weight' => 40,
        ]);
    }

    public function test_cannot_create_assessment_exceeding_100_percent_total_weight(): void
    {
        // Existing assessment = 70%
        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 70,
            'status' => 'published',
        ]);

        // Attempting to add an assessment with 35% (70 + 35 = 105 > 100%)
        $response = $this->actingAs($this->dosen)
            ->from(route('dosen.penilaian.asesmen.create', $this->section->id))
            ->post(route('dosen.penilaian.asesmen.store', $this->section->id), [
                'code' => 'UAS-01',
                'name' => 'UAS',
                'type' => 'uas',
                'final_weight' => 35,
                'status' => 'published',
                'cpmk_selected' => [$this->cpmk2->id => '1'],
                'cpmk' => [$this->cpmk2->id => 100],
            ]);

        $response->assertRedirect(route('dosen.penilaian.asesmen.create', $this->section->id));
        $response->assertSessionHasErrors('final_weight');
        $this->assertDatabaseMissing('assessments', [
            'code' => 'UAS-01',
        ]);
    }

    public function test_cannot_update_assessment_exceeding_100_percent_total_weight(): void
    {
        // Assessment 1 = 60%
        $asmt1 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 60,
            'status' => 'published',
        ]);

        // Assessment 2 = 30% (Total = 90%)
        $asmt2 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'PRJ-01',
            'name' => 'Proyek',
            'type' => 'proyek',
            'final_weight' => 30,
            'status' => 'published',
        ]);

        // Attempt to update asmt2 weight from 30% to 50% (60 + 50 = 110 > 100%)
        $response = $this->actingAs($this->dosen)
            ->from(route('dosen.penilaian.asesmen.edit', [$this->section->id, $asmt2->id]))
            ->put(route('dosen.penilaian.asesmen.update', [$this->section->id, $asmt2->id]), [
                'code' => 'PRJ-01',
                'name' => 'Proyek Pengembangan',
                'type' => 'proyek',
                'final_weight' => 50,
                'status' => 'published',
                'cpmk_selected' => [$this->cpmk2->id => '1'],
                'cpmk' => [$this->cpmk2->id => 100],
            ]);

        $response->assertRedirect(route('dosen.penilaian.asesmen.edit', [$this->section->id, $asmt2->id]));
        $response->assertSessionHasErrors('final_weight');
        $this->assertEquals(30, $asmt2->fresh()->final_weight);
    }

    public function test_can_update_assessment_to_reach_exactly_100_percent(): void
    {
        // Assessment 1 = 60%
        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 60,
            'status' => 'published',
        ]);

        // Assessment 2 = 30%
        $asmt2 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'PRJ-01',
            'name' => 'Proyek',
            'type' => 'proyek',
            'final_weight' => 30,
            'status' => 'published',
        ]);

        // Update asmt2 to 40% (60 + 40 = 100%)
        $response = $this->actingAs($this->dosen)
            ->put(route('dosen.penilaian.asesmen.update', [$this->section->id, $asmt2->id]), [
                'code' => 'PRJ-01',
                'name' => 'Proyek Lengkap',
                'type' => 'proyek',
                'final_weight' => 40,
                'status' => 'published',
                'cpmk_selected' => [$this->cpmk2->id => '1'],
                'cpmk' => [$this->cpmk2->id => 100],
            ]);

        $response->assertRedirect(route('dosen.penilaian.asesmen', $this->section->id));
        $response->assertSessionHas('notice');
        $this->assertEquals(40, $asmt2->fresh()->final_weight);
        $this->assertEquals(100, $this->section->assessments()->sum('final_weight'));
    }

    public function test_project_rubric_with_3_sub_instruments_calculates_correctly(): void
    {
        // Asesmen Proyek: Bobot 30% dari MK
        $project = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'PRJ-01',
            'name' => 'Proyek Capstone',
            'type' => 'proyek',
            'final_weight' => 30,
            'status' => 'published',
            'uses_rubric' => true,
        ]);
        $project->cpmks()->attach($this->cpmk2->id, ['weight' => 100]);

        $rubric = Rubric::create([
            'assessment_id' => $project->id,
            'name' => 'Rubrik Penilaian Proyek',
        ]);

        // Sub-instrumen 1: Progress (20%)
        $critProgress = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kemajuan Proyek & Asistensi (Progress)',
            'weight' => 20,
            'max_score' => 100,
            'order' => 1,
        ]);

        // Sub-instrumen 2: Presentasi (30%)
        $critPresentasi = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Presentasi & Tanya Jawab',
            'weight' => 30,
            'max_score' => 100,
            'order' => 2,
        ]);

        // Sub-instrumen 3: Hasil (50%)
        $critHasil = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Hasil Produk Sistem & Laporan Akhir',
            'weight' => 50,
            'max_score' => 100,
            'order' => 3,
        ]);

        // Nilai mahasiswa:
        // Progress = 90 (20%) -> kontribusi: 18.0
        // Presentasi = 80 (30%) -> kontribusi: 24.0
        // Hasil = 86 (50%) -> kontribusi: 43.0
        // Total Nilai Asesmen Proyek = 18 + 24 + 43 = 85.0
        StudentRubricScore::create([
            'rubric_criterion_id' => $critProgress->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 90,
        ]);
        StudentRubricScore::create([
            'rubric_criterion_id' => $critPresentasi->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 80,
        ]);
        StudentRubricScore::create([
            'rubric_criterion_id' => $critHasil->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 86,
        ]);

        $obeService = new ObeCalculationService();

        // Hitung nilai rubrik
        $calculatedScore = $obeService->assessmentScoreFromRubric($project, $this->student->id);
        $this->assertEquals(85.0, $calculatedScore);

        // Sinkronkan ke student_assessment_scores
        $obeService->syncRubricToAssessmentScore($project, $this->student->id, $this->dosen->id);

        $savedScore = $obeService->assessmentScore($project->id, $this->student->id);
        $this->assertEquals(85.0, $savedScore);

        // Nilai CPMK 2 untuk mahasiswa ini dari asesmen proyek
        $cpmkScore = $obeService->cpmkScore($this->cpmk2, $this->student->id);
        $this->assertEquals(85.0, $cpmkScore);
    }

    public function test_cannot_save_matrix_when_grand_total_is_not_100_percent(): void
    {
        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 50,
            'status' => 'published',
        ]);
        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS-01',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 50,
            'status' => 'published',
        ]);

        // Kirim bobot matriks yang totalnya 100.6% (UTS=50.6%, UAS=50%)
        $postData = [
            'matrix' => [
                $uts->id => [
                    $this->cpmk1->id => 30.6,
                    $this->cpmk2->id => 20.0,
                ],
                $uas->id => [
                    $this->cpmk1->id => 20.0,
                    $this->cpmk2->id => 30.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->from(route('dosen.penilaian.matriks', $this->section->id))
            ->post(route('dosen.penilaian.matriks.save', $this->section->id), $postData);

        $response->assertRedirect(route('dosen.penilaian.matriks', $this->section->id));
        $response->assertSessionHasErrors('matrix');

        // Pastikan final_weight tidak berubah di database (masih 50, bukan 50.6)
        $this->assertEquals(50.0, (float) $uts->fresh()->final_weight);
    }

    public function test_can_save_matrix_when_grand_total_is_exactly_100_percent(): void
    {
        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 0,
            'status' => 'published',
        ]);
        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS-01',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 0,
            'status' => 'published',
        ]);

        // Kirim bobot matriks tepat 100% (UTS=40%, UAS=60%)
        $postData = [
            'matrix' => [
                $uts->id => [
                    $this->cpmk1->id => 40.0,
                ],
                $uas->id => [
                    $this->cpmk2->id => 60.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.matriks.save', $this->section->id), $postData);

        $response->assertRedirect(route('dosen.penilaian.matriks', $this->section->id));
        $response->assertSessionHas('notice');

        // Pastikan final_weight tersimpan dengan benar
        $this->assertEquals(40.0, (float) $uts->fresh()->final_weight);
        $this->assertEquals(60.0, (float) $uas->fresh()->final_weight);
    }

    public function test_matriks_page_displays_clean_over_100_status_without_floating_artifacts_and_disables_button(): void
    {
        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 50.6,
            'status' => 'published',
        ]);
        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 50.6]);

        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS-01',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 50.0,
            'status' => 'published',
        ]);
        $uas->cpmks()->attach($this->cpmk2->id, ['weight' => 50.0]);

        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.matriks', $this->section->id));

        $response->assertOk();
        // Memastikan tidak ada artefak floating point precision
        $response->assertDontSee('-0.59999999999999%');
        // Memastikan status melebihi 100% dan selisih diformat bersih
        $response->assertSee('Melebihi 100%');
        $response->assertSee('Kelebihan: +0.6%');
        // Memastikan tombol Lanjut ke Input Nilai dinonaktifkan (disabled)
        $response->assertSee('id="btn-lanjut-nilai"', false);
        $response->assertSee('disabled', false);
    }

    public function test_matriks_page_enables_lanjut_button_when_matrix_is_exactly_100_percent(): void
    {
        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 40.0,
            'status' => 'published',
        ]);
        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 40.0]);

        $uas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS-01',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 60.0,
            'status' => 'published',
        ]);
        $uas->cpmks()->attach($this->cpmk2->id, ['weight' => 60.0]);

        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.matriks', $this->section->id));

        $response->assertOk();
        $response->assertSee('Valid (Tepat 100%)');
        $response->assertSee('Siap input nilai');
        // Memastikan tombol aktif sebagai link ke halaman asesmen
        $response->assertSee(route('dosen.penilaian.asesmen', $this->section->id));
    }

    public function test_asesmen_page_locks_input_nilai_buttons_when_total_weight_is_not_100_percent(): void
    {
        // Total baru 50%
        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 50.0,
            'status' => 'published',
        ]);
        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 50.0]);

        $response = $this->actingAs($this->dosen)
            ->get(route('dosen.penilaian.asesmen', $this->section->id));

        $response->assertOk();
        $response->assertSee('Bobot Matriks Belum Lengkap');
        // Tombol input nilai dikunci (disabled)
        $response->assertSee('disabled', false);
        $response->assertDontSee(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $uts->id]));

        $this->assertFalse($this->section->fresh()->isMatrixValid());
    }

    public function test_class_section_is_matrix_valid_helper(): void
    {
        $this->assertFalse($this->section->isMatrixValid());

        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS-01',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 45.0,
            'status' => 'published',
        ]);

        $this->assertFalse($this->section->fresh()->isMatrixValid());

        Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS-01',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 55.0,
            'status' => 'published',
        ]);

        $this->assertTrue($this->section->fresh()->isMatrixValid());
        $this->assertEquals(100.0, $this->section->fresh()->total_assessment_weight);
    }
}

