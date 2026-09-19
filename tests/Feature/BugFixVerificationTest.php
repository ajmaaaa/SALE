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
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugFixVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $student;
    private ClassSection $section;
    private Cpmk $cpmk1;
    private Cpmk $cpmk2;
    private Cpmk $cpmk3;
    private Cpl $cpl;
    private ObeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $mahasiswaRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);

        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['code' => '2026-1', 'name' => 'Ganjil 2026/2027', 'is_active' => true]);
        $mataKuliah = MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'TI101', 'name' => 'Algoritma']);

        $this->dosen = User::create([
            'name' => 'Dosen Pengampu',
            'email' => 'dosen@test.local',
            'password' => bcrypt('password'),
            'role_id' => $dosenRole->id,
            'nim_nidn' => '112233',
        ]);

        $this->student = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mhs@test.local',
            'password' => bcrypt('password'),
            'role_id' => $mahasiswaRole->id,
            'nim_nidn' => '26001',
        ]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->dosen->id,
            'section_code' => 'A',
            'capacity' => 30,
        ]);

        $this->section->students()->attach($this->student->id);

        $this->cpmk1 = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-1', 'description' => 'CPMK 1', 'threshold' => 60]);
        $this->cpmk2 = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-2', 'description' => 'CPMK 2', 'threshold' => 60]);
        $this->cpmk3 = Cpmk::create(['mata_kuliah_id' => $mataKuliah->id, 'code' => 'CPMK-3', 'description' => 'CPMK 3', 'threshold' => 60]);

        $this->cpl = Cpl::create(['prodi_id' => $prodi->id, 'code' => 'CPL-1', 'description' => 'CPL 1']);
        $this->cpl->cpmks()->attach($this->cpmk1->id, ['weight' => 50]);
        $this->cpl->cpmks()->attach($this->cpmk2->id, ['weight' => 50]);

        $this->service = new ObeCalculationService();
    }

    /**
     * Verifikasi BUG #2:
     * assessmentCpmkEffectiveWeight() menggunakan formula proporsional matematis tunggal
     * effWeight = finalWeight * (pivotWeight / totalPivot)
     * tanpa 4 cabang heuristik tebakan.
     */
    public function test_bug_2_effective_weight_single_proportional_formula(): void
    {
        // Kasus 1: Asesmen mengukur 2 CPMK (bobot lokal 70% dan 30%, bobot final asesmen = 20%)
        $asmt1 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 20,
            'status' => 'published',
        ]);
        $asmt1->cpmks()->attach($this->cpmk1->id, ['weight' => 70]);
        $asmt1->cpmks()->attach($this->cpmk2->id, ['weight' => 30]);

        $this->assertEquals(14.0, $this->service->assessmentCpmkEffectiveWeight($asmt1->fresh(), $this->cpmk1)); // 20 * (70/100) = 14
        $this->assertEquals(6.0, $this->service->assessmentCpmkEffectiveWeight($asmt1->fresh(), $this->cpmk2));  // 20 * (30/100) = 6

        // Kasus 2: Asesmen dari matriks (bobot pivot langsung 14% dan 6%, final_weight = 20%)
        $asmt2 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UAS',
            'name' => 'UAS',
            'type' => 'uas',
            'final_weight' => 20,
            'status' => 'published',
        ]);
        $asmt2->cpmks()->attach($this->cpmk1->id, ['weight' => 14]);
        $asmt2->cpmks()->attach($this->cpmk2->id, ['weight' => 6]);

        $this->assertEquals(14.0, $this->service->assessmentCpmkEffectiveWeight($asmt2->fresh(), $this->cpmk1)); // 20 * (14/20) = 14
        $this->assertEquals(6.0, $this->service->assessmentCpmkEffectiveWeight($asmt2->fresh(), $this->cpmk2));  // 20 * (6/20) = 6

        // Kasus 3: Asesmen 1 CPMK (pivot 100%, final_weight 30%)
        $asmt3 = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'PRJ',
            'name' => 'Proyek',
            'type' => 'proyek',
            'final_weight' => 30,
            'status' => 'published',
        ]);
        $asmt3->cpmks()->attach($this->cpmk3->id, ['weight' => 100]);

        $this->assertEquals(30.0, $this->service->assessmentCpmkEffectiveWeight($asmt3->fresh(), $this->cpmk3)); // 30 * (100/100) = 30
    }

    /**
     * Verifikasi BUG #3:
     * Dosen dapat menginput nilai dengan pecahan desimal (seperti 33.3)
     * saat asesmen mengukur 3 CPMK terbagi rata, tanpa ditolak oleh validasi (int) cast.
     */
    public function test_bug_3_decimal_max_score_validation_allows_valid_fractional_points(): void
    {
        // Asesmen 30% terbagi rata ke 3 CPMK (CPMK 1 = 10%, CPMK 2 = 10%, CPMK 3 = 10%)
        // Max score per CPMK = (10/30)*100 = 33.3 poin
        $asmt = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TGS-33',
            'name' => 'Tugas 3 CPMK',
            'type' => 'tugas',
            'final_weight' => 30,
            'status' => 'published',
        ]);
        $asmt->cpmks()->attach($this->cpmk1->id, ['weight' => 10]);
        $asmt->cpmks()->attach($this->cpmk2->id, ['weight' => 10]);
        $asmt->cpmks()->attach($this->cpmk3->id, ['weight' => 10]);

        $maxScoreCpmk1 = $this->service->assessmentCpmkMaxScore($asmt->fresh(), $this->cpmk1);
        $this->assertEquals(33.3, $maxScoreCpmk1);

        // Input nilai 33.3 (sebelum fix, ditolak oleh Laravel karena (int) cast membatasi 33.05)
        $payload = [
            'cpmk_scores' => [
                $this->student->id => [
                    $this->cpmk1->id => 33.3,
                    $this->cpmk2->id => 30.0,
                    $this->cpmk3->id => 32.5,
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('dosen.penilaian.asesmen.nilai.store', [$this->section->id, $asmt->id]), $payload);

        $response->assertRedirect(route('dosen.penilaian.asesmen.nilai', [$this->section->id, $asmt->id]));
        $response->assertSessionHas('notice');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_assessment_cpmk_scores', [
            'assessment_id' => $asmt->id,
            'cpmk_id' => $this->cpmk1->id,
            'mahasiswa_id' => $this->student->id,
            'score' => 33.3,
        ]);
    }

    /**
     * Verifikasi GAP #4:
     * cpmkScoreDetails() dan cplScoreDetails() menyediakan metadata coverage dan status complete.
     */
    public function test_gap_4_coverage_metadata_for_cpmk_and_cpl(): void
    {
        // CPMK 1 diukur oleh Tugas (20%) dan UTS (30%) -> Total 50%
        $tugas = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'T1',
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
        ]);
        $tugas->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);

        $uts = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'UTS1',
            'name' => 'UTS',
            'type' => 'uts',
            'final_weight' => 30,
            'status' => 'published',
        ]);
        $uts->cpmks()->attach($this->cpmk1->id, ['weight' => 100]);

        // Baru Tugas yang dinilai (nilai 80)
        StudentAssessmentScore::create(['assessment_id' => $tugas->id, 'mahasiswa_id' => $this->student->id, 'score' => 80]);

        $details = $this->service->cpmkScoreDetails($this->cpmk1->fresh(), $this->student->id, $this->section->id);
        $this->assertEquals(80.0, $details['score']);
        $this->assertEquals(40.0, $details['coverage']); // 20 / 50 = 40%
        $this->assertEquals(20.0, $details['weight_graded']);
        $this->assertEquals(50.0, $details['weight_total']);
        $this->assertFalse($details['is_complete']);

        // Backward compatibility: cpmkScore() tetap mengembalikan angka polos 80.0
        $this->assertEquals(80.0, $this->service->cpmkScore($this->cpmk1->fresh(), $this->student->id, $this->section->id));

        // Verifikasi CPL Coverage
        // CPL-1 dibentuk dari CPMK-1 (50%) dan CPMK-2 (50%)
        // CPMK-2 belum dinilai sama sekali
        $cplDetails = $this->service->cplScoreDetails($this->cpl->fresh(), $this->student->id, $this->section->id);
        $this->assertEquals(80.0, $cplDetails['score']);
        $this->assertEquals(50.0, $cplDetails['coverage']); // 50 dari 100 bobot CPL
        $this->assertEquals(1, $cplDetails['cpmks_graded']);
        $this->assertEquals(2, $cplDetails['cpmks_total']);
        $this->assertFalse($cplDetails['is_complete']);

        // Backward compatibility: cplScore() tetap mengembalikan angka polos 80.0
        $this->assertEquals(80.0, $this->service->cplScore($this->cpl->fresh(), $this->student->id, $this->section->id));
    }
}
