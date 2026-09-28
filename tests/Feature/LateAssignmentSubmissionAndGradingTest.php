<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LateAssignmentSubmissionAndGradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_assignment_submission_and_lecturer_review_workflow(): void
    {
        Storage::fake('local');
        $this->seed(RoleSeeder::class);

        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['name' => 'Gasal 2026/2027', 'code' => '20261', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'TI201', 'name' => 'Pemrograman Berorientasi Objek', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $dosen = User::create([
            'name' => 'Bpk. Hendra S.Kom., M.T.',
            'email' => 'hendra@univ.ac.id',
            'nim_nidn' => '0412345678',
            'password' => Hash::make('password123'),
            'role_id' => $dosenRoleId,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $student = User::create([
            'name' => 'Ahmad Santoso',
            'email' => 'ahmad@mhs.univ.ac.id',
            'nim_nidn' => '202410001',
            'password' => Hash::make('password123'),
            'role_id' => $mhsRoleId,
        ]);
        $student->roles()->sync([$mhsRoleId]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'PBO-A',
            'capacity' => 35,
        ]);
        $section->students()->attach($student->id);

        $pastDueDate = now()->subDays(2)->format('Y-m-d H:i:s');
        $tugas = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-PBO-01',
            'name' => 'Tugas 1: Class Diagram & Inheritance',
            'type' => 'tugas',
            'final_weight' => 15,
            'status' => 'published',
            'due_at' => $pastDueDate,
            'allow_late' => true,
            'learning_payload' => [
                'module' => 'Modul 2: Konsep OOP',
                'body' => 'Buat class diagram dan implementasikan dalam kode bahasa pemrograman.',
                'due' => $pastDueDate,
                'allow_late' => true,
                'points' => 100,
                'formats' => ['file', 'image', 'link', 'text'],
            ],
        ]);

        // 1. Student opens assignment page after deadline has passed
        $studentItemView = $this->actingAs($student)->get(route('mahasiswa.course.item', [$section->id, $tugas->id]));
        $studentItemView->assertOk();
        $studentItemView->assertSee('Terlambat');
        $studentItemView->assertSee('Belum diserahkan');
        $studentItemView->assertSee('Kumpulkan Tugas (Terlambat)');

        // 2. Student submits assignment late with file, text, and link
        $uploadedFile = UploadedFile::fake()->create('laporan_tugas_ahmad.pdf', 500, 'application/pdf');
        $submitResponse = $this->actingAs($student)->post(route('mahasiswa.course.submit', [$section->id, $tugas->id]), [
            'answer' => 'Berikut jawaban dan dokumentasi source code saya.',
            'link' => 'https://github.com/ahmadsantoso/pbo-inheritance',
            'files' => [$uploadedFile],
        ]);

        $submitResponse->assertSessionHasNoErrors();
        $submitResponse->assertRedirect(route('mahasiswa.course.item', [$section->id, $tugas->id]));

        // Check database records
        $submission = Submission::where('assessment_id', $tugas->id)
            ->where('mahasiswa_id', $student->id)
            ->first();
        $this->assertNotNull($submission);
        $this->assertNotNull($submission->submitted_at);
        $this->assertEquals('Berikut jawaban dan dokumentasi source code saya.', $submission->answer);

        $attachment = Attachment::where('submission_id', $submission->id)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('laporan_tugas_ahmad.pdf', $attachment->name);

        // 3. Student views item page again: shows Diserahkan and Terlambat badge
        $afterSubmitView = $this->actingAs($student)->get(route('mahasiswa.course.item', [$section->id, $tugas->id]));
        $afterSubmitView->assertOk();
        $afterSubmitView->assertSee('Diserahkan');
        $afterSubmitView->assertSee('Terlambat');
        $afterSubmitView->assertSee('laporan_tugas_ahmad.pdf');

        // 4. Lecturer views assignment item page: shows submission stats including late count
        $lecturerItemView = $this->actingAs($dosen)->get(route('dosen.course.item', [$section->id, $tugas->id]));
        $lecturerItemView->assertOk();
        $lecturerItemView->assertSee('Pengumpulan Mahasiswa');
        $lecturerItemView->assertSee('1 / 1');
        $lecturerItemView->assertSee('Dikumpulkan terlambat:');
        $lecturerItemView->assertSee('1 mahasiswa');
        $lecturerItemView->assertSee('Lihat &amp; Nilai Mahasiswa', false);

        // 5. Lecturer opens input nilai page: shows student with Terlambat badge
        $inputNilaiView = $this->actingAs($dosen)->get(route('dosen.penilaian.asesmen.nilai', [$section->id, $tugas->id]));
        $inputNilaiView->assertOk();
        $inputNilaiView->assertSee('Ahmad Santoso');
        $inputNilaiView->assertSee('Lihat Jawaban');
        $inputNilaiView->assertSee('Terlambat');

        // 6. Lecturer opens/reads the student's submitted file
        $fileResponse = $this->actingAs($dosen)->get(route('preview.file', $attachment->uuid));
        $fileResponse->assertOk();
        $fileResponse->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_assignment_with_allow_late_false_still_allows_student_to_submit_and_lecturer_to_grade(): void
    {
        Storage::fake('local');
        $this->seed(RoleSeeder::class);

        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'SI', 'name' => 'Sistem Informasi']);
        $semester = Semester::create(['name' => 'Gasal 2026/2027', 'code' => '20261', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'SI102', 'name' => 'Basis Data', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $dosen = User::create([
            'name' => 'Dr. Rina',
            'email' => 'rina@univ.ac.id',
            'nim_nidn' => '0487654321',
            'password' => Hash::make('password123'),
            'role_id' => $dosenRoleId,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $student = User::create([
            'name' => 'Budi Pratama',
            'email' => 'budi@mhs.univ.ac.id',
            'nim_nidn' => '202420002',
            'password' => Hash::make('password123'),
            'role_id' => $mhsRoleId,
        ]);
        $student->roles()->sync([$mhsRoleId]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'BD-B',
            'capacity' => 30,
        ]);
        $section->students()->attach($student->id);

        $pastDueDate = now()->subDays(3)->format('Y-m-d H:i:s');
        $tugas = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-BD-01',
            'name' => 'Tugas ERD',
            'type' => 'tugas',
            'final_weight' => 10,
            'status' => 'published',
            'due_at' => $pastDueDate,
            'allow_late' => false,
            'learning_payload' => [
                'module' => 'Modul 3: Desain Database',
                'body' => 'Rancang ERD untuk sistem perpustakaan.',
                'due' => $pastDueDate,
                'allow_late' => false,
                'points' => 100,
                'formats' => ['file', 'image', 'link', 'text'],
            ],
        ]);

        // Student still sees Kumpulkan Tugas (Terlambat) and can submit
        $view = $this->actingAs($student)->get(route('mahasiswa.course.item', [$section->id, $tugas->id]));
        $view->assertOk();
        $view->assertSee('Kumpulkan Tugas (Terlambat)');

        $imageFile = UploadedFile::fake()->image('erd_diagram.png');
        $submit = $this->actingAs($student)->post(route('mahasiswa.course.submit', [$section->id, $tugas->id]), [
            'answer' => 'Berikut diagram ERD perpustakaan saya.',
            'files' => [$imageFile],
        ]);
        $submit->assertSessionHasNoErrors();
        $submit->assertRedirect(route('mahasiswa.course.item', [$section->id, $tugas->id]));

        // Submission created successfully
        $sub = Submission::where('assessment_id', $tugas->id)->where('mahasiswa_id', $student->id)->first();
        $this->assertNotNull($sub);

        // Lecturer can view and download image file
        $att = Attachment::where('submission_id', $sub->id)->first();
        $this->assertNotNull($att);

        $fileResp = $this->actingAs($dosen)->get(route('preview.file', $att->uuid));
        $fileResp->assertOk();
    }

    public function test_rekap_nilai_table_has_solid_sticky_columns_and_proper_structure(): void
    {
        $this->seed(RoleSeeder::class);

        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mhsRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $prodi = Prodi::create(['code' => 'TI', 'name' => 'Teknik Informatika']);
        $semester = Semester::create(['name' => 'Gasal 2026/2027', 'code' => '20261', 'is_active' => true]);
        $matkul = MataKuliah::create(['code' => 'TI301', 'name' => 'Basis Data Lanjut', 'prodi_id' => $prodi->id, 'sks' => 3]);

        $dosen = User::create([
            'name' => 'Bpk. Dosen Rekap',
            'email' => 'dosenrekap@univ.ac.id',
            'nim_nidn' => '0499887766',
            'password' => Hash::make('password123'),
            'role_id' => $dosenRoleId,
        ]);
        $dosen->roles()->sync([$dosenRoleId]);

        $student = User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@mhs.univ.ac.id',
            'nim_nidn' => '202430003',
            'password' => Hash::make('password123'),
            'role_id' => $mhsRoleId,
        ]);
        $student->roles()->sync([$mhsRoleId]);

        $section = ClassSection::create([
            'mata_kuliah_id' => $matkul->id,
            'semester_id' => $semester->id,
            'dosen_id' => $dosen->id,
            'section_code' => 'BDL-A',
            'capacity' => 30,
        ]);
        $section->students()->attach($student->id);

        $cpmk = \App\Models\Cpmk::create([
            'mata_kuliah_id' => $matkul->id,
            'code' => 'CPMK-1',
            'description' => 'Mampu mendesain skema relasional',
        ]);

        $asmt = Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TGS-01',
            'name' => 'Tugas Normalisasi',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
        ]);
        $asmt->cpmks()->attach($cpmk->id, ['weight' => 100]);

        $res = $this->actingAs($dosen)->get(route('dosen.penilaian.rekap', $section->id));
        $res->assertOk();

        // Verify sticky columns are present and use solid backgrounds
        $res->assertSee('sticky left-0', false);
        $res->assertSee('sticky left-[48px]', false);
        $res->assertSee('sticky left-[178px]', false);
        $res->assertSee('Nama Mahasiswa');
        $res->assertSee('Siti Nurhaliza');

        // Verify transparent background bugs are gone
        $res->assertDontSee('bg-amber-50/30');
        $res->assertDontSee('group-hover:bg-canvas/40');
    }
}
