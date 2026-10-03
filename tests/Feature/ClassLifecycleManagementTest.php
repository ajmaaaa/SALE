<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassEnrollmentAppeal;
use App\Models\ClassSection;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\User;
use App\Services\ClassEnrollmentService;
use App\Services\DatabaseNotificationService;
use App\Services\ObeExcelExportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassLifecycleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminProdi;
    private User $dosen;
    private User $mahasiswa1;
    private User $mahasiswa2;
    private Prodi $prodi;
    private Semester $semester;
    private MataKuliah $mataKuliah;
    private ClassSection $section;
    private ClassEnrollmentService $enrollmentService;
    private DatabaseNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminProdiRole = Role::where('name', Role::ADMIN_PRODI)->firstOrFail();
        $dosenRole = Role::where('name', Role::DOSEN)->firstOrFail();
        $mahasiswaRole = Role::where('name', Role::MAHASISWA)->firstOrFail();

        $this->prodi = Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
        ]);

        $this->semester = Semester::create([
            'code' => '2026-1',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);

        $this->mataKuliah = MataKuliah::create([
            'prodi_id' => $this->prodi->id,
            'code' => 'IF201',
            'name' => 'Struktur Data & Algoritma',
            'sks' => 3,
        ]);

        $this->adminProdi = User::create([
            'name' => 'Kaprodi IF',
            'email' => 'kaprodi.if@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminProdiRole->id,
            'prodi_id' => $this->prodi->id,
            'managing_prodi_id' => $this->prodi->id,
            'nim_nidn' => 'AP001',
        ]);
        $this->adminProdi->roles()->syncWithoutDetaching([$adminProdiRole->id]);

        $this->dosen = User::create([
            'name' => 'Dr. Budi Dosen',
            'email' => 'budi.dosen@test.com',
            'password' => Hash::make('password'),
            'role_id' => $dosenRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => 'DS001',
        ]);
        $this->dosen->roles()->syncWithoutDetaching([$dosenRole->id]);

        $this->mahasiswa1 = User::create([
            'name' => 'Mahasiswa Satu',
            'email' => 'mhs1@test.com',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '230001',
        ]);
        $this->mahasiswa1->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        $this->mahasiswa2 = User::create([
            'name' => 'Mahasiswa Dua',
            'email' => 'mhs2@test.com',
            'password' => Hash::make('password'),
            'role_id' => $mahasiswaRole->id,
            'prodi_id' => $this->prodi->id,
            'nim_nidn' => '230002',
        ]);
        $this->mahasiswa2->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        $this->section = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'IF-01',
            'enrollment_code' => 'ABCDEF12',
            'capacity' => 40,
            'dosen_id' => $this->dosen->id,
        ]);

        $this->enrollmentService = app(ClassEnrollmentService::class);
        $this->notificationService = app(DatabaseNotificationService::class);
    }

    /**
     * KONDISI 1.1 & KONDISI 2.1: Join normal dan Leave tanpa riwayat nilai (Hard Delete).
     */
    public function test_student_can_join_class_and_leave_without_grades_is_hard_deleted(): void
    {
        $this->actingAs($this->mahasiswa1);

        $response = $this->post(route('mahasiswa.join-kelas.direct'), [
            'code' => 'ABCDEF12',
        ]);

        $response->assertRedirect(route('mahasiswa.course.show', $this->section->id));
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
            'kick_count' => 0,
        ]);

        // Leave tanpa nilai
        $leaveResponse = $this->post(route('mahasiswa.course.leave', $this->section->id));
        $leaveResponse->assertRedirect(route('mahasiswa.course.index'));

        // Baris pivot harus terhapus bersih (Hard Delete)
        $this->assertDatabaseMissing('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
        ]);
    }

    /**
     * KONDISI 1.2 & KONDISI 2.2: Leave dengan nilai (Soft Drop) dan Re-Join memulihkan nilai.
     */
    public function test_student_leave_with_academic_records_is_soft_dropped_and_rejoin_restores_records(): void
    {
        // Daftarkan mahasiswa
        $this->enrollmentService->join($this->section, $this->mahasiswa1);

        // Buat assessment & submisi
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'T1',
            'name' => 'Tugas 1 Analisis Kompleksitas',
            'type' => 'tugas',
            'final_weight' => 20.0,
            'status' => Assessment::STATUS_PUBLISHED,
            'max_score' => 100,
        ]);

        Submission::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'user_id' => $this->mahasiswa1->id,
            'content' => 'Jawaban tugas...',
            'submitted_at' => now(),
        ]);

        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'score' => 88.5,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->mahasiswa1);

        // Leave kelas
        $this->post(route('mahasiswa.course.leave', $this->section->id));

        // Status harus dropped_self (Soft Drop)
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'dropped_self',
        ]);
        $this->assertNotNull(DB::table('class_section_student')->where('mahasiswa_id', $this->mahasiswa1->id)->value('dropped_at'));

        // Mahasiswa re-join via kode
        $rejoinResponse = $this->post(route('mahasiswa.join-kelas.direct'), [
            'code' => 'ABCDEF12',
        ]);
        $rejoinResponse->assertRedirect(route('mahasiswa.course.show', $this->section->id));

        // Status kembali enrolled, dropped_at null
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
            'dropped_at' => null,
        ]);
    }

    /**
     * KONDISI 1.3 & KONDISI 3: Dosen kick mahasiswa (1x), mahasiswa re-join & notifikasi dosen.
     */
    public function test_lecturer_can_kick_student_first_time_and_student_can_rejoin_notifying_lecturer(): void
    {
        $this->enrollmentService->join($this->section, $this->mahasiswa1);

        // Dosen kick mahasiswa 1
        $this->actingAs($this->dosen);
        $kickResponse = $this->post(route('dosen.course.students.kick', [$this->section->id, $this->mahasiswa1->id]), [
            'reason' => 'Salah kelas paralel',
        ]);
        $kickResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'kicked',
            'kick_count' => 1,
            'kick_reason' => 'Salah kelas paralel',
            'kicked_by' => $this->dosen->id,
        ]);

        // Cek notifikasi terkirim ke mahasiswa
        $mhsNotifs = $this->notificationService->forUser($this->mahasiswa1);
        $kickedNotif = collect($mhsNotifs)->firstWhere('category', 'sistem');
        $this->assertNotNull($kickedNotif);
        $this->assertStringContainsString('Dikeluarkan dari Kelas', $kickedNotif['title']);
        $this->assertStringContainsString('Salah kelas paralel', $kickedNotif['message']);

        // Mahasiswa re-join (kick 1 diarahkan ke halaman konfirmasi untuk melihat alasan dosen terlebih dahulu)
        $this->actingAs($this->mahasiswa1);
        $directResponse = $this->post(route('mahasiswa.join-kelas.direct'), [
            'code' => 'ABCDEF12',
        ]);
        $directResponse->assertRedirect(route('mahasiswa.join-kelas', 'ABCDEF12'));

        $confirmPage = $this->get(route('mahasiswa.join-kelas', 'ABCDEF12'));
        $confirmPage->assertOk();
        $confirmPage->assertSee('Salah kelas paralel');
        $confirmPage->assertSee('Daftar Ulang Kelas');
        $confirmPage->assertSee('Batalkan / Bukan Kelas Saya');

        // Mahasiswa menekan tombol Daftar Ulang Kelas
        $rejoinResponse = $this->post(route('mahasiswa.join-kelas.post', 'ABCDEF12'));
        $rejoinResponse->assertRedirect(route('mahasiswa.course.show', $this->section->id));

        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
            'kick_count' => 1,
        ]);

        // Cek notifikasi ke Dosen bahwa mahasiswa re-join
        $dosenNotifs = $this->notificationService->forUser($this->dosen, 'dosen');
        $rejoinNotif = collect($dosenNotifs)->first(fn ($n) => str_starts_with($n['id'], 'rejoin_'));
        $this->assertNotNull($rejoinNotif);
        $this->assertStringContainsString('Peserta Masuk Kembali', $rejoinNotif['title']);
        $this->assertStringContainsString($this->mahasiswa1->name, $rejoinNotif['message']);
    }

    /**
     * KONDISI 1.3 & 5.1: Kick 2x mengunci re-join, memunculkan layar blokir / opsi banding.
     */
    public function test_lecturer_second_kick_blocks_student_from_rejoining(): void
    {
        // Join, kick 1x, rejoin
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick pertama');
        $this->enrollmentService->join($this->section, $this->mahasiswa1);

        // Kick kedua kalinya
        $this->actingAs($this->dosen);
        $this->post(route('dosen.course.students.kick', [$this->section->id, $this->mahasiswa1->id]), [
            'reason' => 'Batal KRS resmi semester ini',
        ]);

        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'kicked',
            'kick_count' => 2,
        ]);

        // Mahasiswa mencoba join via kode -> diarahkan ke konfirmasi / blocked view
        $this->actingAs($this->mahasiswa1);
        $directResponse = $this->post(route('mahasiswa.join-kelas.direct'), [
            'code' => 'ABCDEF12',
        ]);
        $directResponse->assertRedirect(route('mahasiswa.join-kelas', 'ABCDEF12'));

        $confirmPage = $this->get(route('mahasiswa.join-kelas', 'ABCDEF12'));
        $confirmPage->assertOk();
        $confirmPage->assertSee('Akses Bergabung Dibatasi');
        $confirmPage->assertSee('Ajukan Verifikasi Peserta');
    }

    /**
     * KONDISI 5.2 & 5.3: Mahasiswa mengajukan banding dengan KRS, masuk ke antrean Admin Prodi.
     */
    public function test_blocked_student_can_submit_appeal_and_admin_prodi_sees_it_in_queue_and_receives_notification(): void
    {
        Storage::fake('local');

        // Setup: kick 2x
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick 1');
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick 2');

        $this->actingAs($this->mahasiswa1);

        $file = UploadedFile::fake()->create('krs_resmi.pdf', 500, 'application/pdf');

        $appealResponse = $this->post(route('mahasiswa.course.appeal', $this->section->id), [
            'student_notes' => 'Saya adalah mahasiswa resmi kelas paralel ini sesuai dengan KRS terlampir.',
            'attachment' => $file,
        ]);

        $appealResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_enrollment_appeals', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'pending',
            'student_notes' => 'Saya adalah mahasiswa resmi kelas paralel ini sesuai dengan KRS terlampir.',
        ]);

        $appeal = ClassEnrollmentAppeal::firstOrFail();
        $this->assertNotNull($appeal->attachment_path);
        Storage::disk('local')->assertExists($appeal->attachment_path);

        // Notifikasi ke Admin Prodi
        $adminNotifs = $this->notificationService->forUser($this->adminProdi, Role::ADMIN_PRODI);
        $pendingNotif = collect($adminNotifs)->first(fn ($n) => str_starts_with($n['id'], 'appeal_pending_'));
        $this->assertNotNull($pendingNotif);
        $this->assertStringContainsString('Permohonan Verifikasi Peserta', $pendingNotif['title']);
        $this->assertStringContainsString($this->mahasiswa1->name, $pendingNotif['message']);

        // Admin Prodi melihat di antrean
        $this->actingAs($this->adminProdi);
        $adminView = $this->get(route('admin-prodi.akademik.verifikasi-peserta', ['prodi_id' => $this->prodi->id]));
        $adminView->assertOk();
        $adminView->assertSee($this->mahasiswa1->name);
        $adminView->assertSee($this->section->display_code);
        $adminView->assertSee('Lihat KRS');

        // Admin Prodi bisa mengunduh / melihat berkas attachment KRS
        $attachView = $this->get(route('admin-prodi.akademik.verifikasi-peserta.attachment', $appeal->id));
        $attachView->assertOk();
    }

    /**
     * KONDISI 5.4: Admin Prodi menyetujui banding -> mahasiswa aktif kembali dan terkunci (is_locked = true).
     */
    public function test_admin_prodi_can_approve_appeal_locking_student_from_further_lecturer_kicks(): void
    {
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick 1');
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick 2');

        $appeal = ClassEnrollmentAppeal::create([
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'pending',
            'student_notes' => 'KRS saya valid dan terverifikasi oleh BAAK kampus.',
        ]);

        $this->actingAs($this->adminProdi);
        $approveResponse = $this->post(route('admin-prodi.akademik.verifikasi-peserta.approve', $appeal->id), [
            'admin_notes' => 'Disetujui berdasarkan verifikasi SIAKAD BAAK.',
        ]);

        $approveResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_enrollment_appeals', [
            'id' => $appeal->id,
            'status' => 'approved',
            'reviewed_by' => $this->adminProdi->id,
        ]);

        // Pivot mahasiswa aktif dan is_locked = true
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
            'is_locked' => 1,
        ]);

        // Dosen dilarang menge-kick mahasiswa ini lagi sepihak
        $this->actingAs($this->dosen);
        $kickAttempt = $this->post(route('dosen.course.students.kick', [$this->section->id, $this->mahasiswa1->id]), [
            'reason' => 'Mau kick lagi',
        ]);
        $kickAttempt->assertSessionHasErrors('kick');

        // Status tetap enrolled & is_locked
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
            'is_locked' => 1,
        ]);

        // Mahasiswa menerima notifikasi banding disetujui
        $mhsNotifs = $this->notificationService->forUser($this->mahasiswa1);
        $approvedNotif = collect($mhsNotifs)->first(fn ($n) => str_starts_with($n['id'], 'appeal_approved_'));
        $this->assertNotNull($approvedNotif);
        $this->assertStringContainsString('Verifikasi Disetujui', $approvedNotif['title']);
    }

    /**
     * KONDISI 5.4: Admin Prodi menolak banding dengan catatan.
     */
    public function test_admin_prodi_can_reject_appeal_with_notes(): void
    {
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick 1');
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Kick 2');

        $appeal = ClassEnrollmentAppeal::create([
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'pending',
            'student_notes' => 'KRS saya valid dan terverifikasi oleh BAAK kampus.',
        ]);

        $this->actingAs($this->adminProdi);
        $rejectResponse = $this->post(route('admin-prodi.akademik.verifikasi-peserta.reject', $appeal->id), [
            'admin_notes' => 'Berkas KRS yang dilampirkan tidak sesuai untuk semester ini.',
        ]);

        $rejectResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_enrollment_appeals', [
            'id' => $appeal->id,
            'status' => 'rejected',
            'admin_notes' => 'Berkas KRS yang dilampirkan tidak sesuai untuk semester ini.',
        ]);

        // Mahasiswa tetap berstatus kicked
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'kicked',
        ]);

        // Mahasiswa menerima notifikasi penolakan
        $mhsNotifs = $this->notificationService->forUser($this->mahasiswa1);
        $rejectedNotif = collect($mhsNotifs)->first(fn ($n) => str_starts_with($n['id'], 'appeal_rejected_'));
        $this->assertNotNull($rejectedNotif);
        $this->assertStringContainsString('Verifikasi Ditolak', $rejectedNotif['title']);
        $this->assertStringContainsString('Berkas KRS yang dilampirkan tidak sesuai', $rejectedNotif['message']);
    }

    /**
     * KONDISI 7: Proteksi penghapusan kelas (kelas bernilai dilarang dihapus).
     */
    public function test_admin_prodi_cannot_delete_class_with_academic_records_must_archive(): void
    {
        // Daftarkan mahasiswa & beri nilai
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'T1',
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'final_weight' => 20.0,
            'status' => Assessment::STATUS_PUBLISHED,
        ]);
        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'score' => 90,
        ]);

        $this->actingAs($this->adminProdi);

        // Hapus kelas harus ditolak
        $deleteResponse = $this->delete(route('admin-prodi.akademik.kelas.destroy', $this->section->id));
        $deleteResponse->assertSessionHasErrors('kelas');

        $this->assertDatabaseHas('class_sections', [
            'id' => $this->section->id,
        ]);

        // Buat kelas kosong lain tanpa peserta & nilai -> boleh dihapus
        $emptySection = ClassSection::create([
            'mata_kuliah_id' => $this->mataKuliah->id,
            'semester_id' => $this->semester->id,
            'section_code' => 'IF-02',
            'enrollment_code' => 'XYZ98765',
        ]);

        $deleteEmpty = $this->delete(route('admin-prodi.akademik.kelas.destroy', $emptySection->id));
        $deleteEmpty->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('class_sections', ['id' => $emptySection->id]);
    }

    /**
     * KONDISI 6: Pengarsipan manual, unarchive, dan pengarsipan massal semester.
     */
    public function test_admin_prodi_can_archive_and_unarchive_class_and_archive_semester(): void
    {
        $this->actingAs($this->adminProdi);

        // Manual archive
        $this->post(route('admin-prodi.akademik.kelas.archive', $this->section->id));
        $this->section->refresh();
        $this->assertTrue($this->section->isArchived());
        $this->assertEquals($this->adminProdi->id, $this->section->archived_by);

        // Manual unarchive
        $this->post(route('admin-prodi.akademik.kelas.unarchive', $this->section->id));
        $this->section->refresh();
        $this->assertFalse($this->section->isArchived());
        $this->assertNull($this->section->archived_at);

        // Mass archive semester
        $this->post(route('admin-prodi.akademik.semester.archive-classes', $this->semester->id), [
            'prodi_id' => $this->prodi->id,
        ]);
        $this->section->refresh();
        $this->assertTrue($this->section->isArchived());
    }

    /**
     * KONDISI 6.3: Mode read-only pada kelas yang diarsipkan.
     */
    public function test_archived_class_blocks_submissions_and_grade_changes(): void
    {
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'TA',
            'name' => 'Tugas Akhir',
            'type' => 'tugas',
            'final_weight' => 20.0,
            'status' => Assessment::STATUS_PUBLISHED,
        ]);

        // Arsipkan kelas
        $this->section->forceFill(['archived_at' => now(), 'archived_by' => $this->adminProdi->id])->save();

        // Mahasiswa submit ditolak (403)
        $this->actingAs($this->mahasiswa1);
        $submitResponse = $this->post(route('mahasiswa.course.submit', [$this->section->id, $assessment->id]), [
            'content' => 'Submisi terlambat',
        ]);
        $submitResponse->assertForbidden();

        // Dosen tambah konten ditolak (403)
        $this->actingAs($this->dosen);
        $createContent = $this->get(route('dosen.item.create', $this->section->id));
        $createContent->assertForbidden();

        // Mahasiswa leave kelas arsip ditolak
        $this->actingAs($this->mahasiswa1);
        $leaveResponse = $this->post(route('mahasiswa.course.leave', $this->section->id));
        $leaveResponse->assertSessionHasErrors('leave');
    }

    /**
     * KONDISI 5.6: Pemisahan data OBE & Sheet 'Riwayat Peserta Non-Aktif' di Export Excel.
     */
    public function test_obe_rekap_and_excel_export_includes_inactive_students_sheet(): void
    {
        // Mahasiswa 1 aktif
        $this->enrollmentService->join($this->section, $this->mahasiswa1);

        // Mahasiswa 2 ikut kelas, dapat nilai, lalu keluar (dropped_self)
        $this->enrollmentService->join($this->section, $this->mahasiswa2);

        $assessment = Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'K1',
            'name' => 'Kuis 1',
            'type' => 'kuis',
            'final_weight' => 20.0,
            'status' => Assessment::STATUS_PUBLISHED,
        ]);

        StudentAssessmentScore::create([
            'assessment_id' => $assessment->id,
            'mahasiswa_id' => $this->mahasiswa2->id,
            'score' => 75.0,
            'status' => StudentAssessmentScore::STATUS_PUBLISHED,
        ]);

        $this->enrollmentService->leave($this->section, $this->mahasiswa2);

        // Relasi students() hanya memuat Mahasiswa 1
        $activeStudents = $this->section->students()->get();
        $this->assertTrue($activeStudents->contains('id', $this->mahasiswa1->id));
        $this->assertFalse($activeStudents->contains('id', $this->mahasiswa2->id));

        // inactiveStudentsWithRecords memuat Mahasiswa 2
        $inactiveWithRecords = $this->enrollmentService->inactiveStudentsWithRecords($this->section);
        $this->assertEquals(1, $inactiveWithRecords->count());
        $this->assertEquals($this->mahasiswa2->id, $inactiveWithRecords->first()['student']->id);

        // Test export Excel memuat sheet "Riwayat Peserta Non-Aktif"
        $excelService = app(ObeExcelExportService::class);
        $response = $excelService->exportCpmkExcel($this->section);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $tempFile = tempnam(sys_get_temp_dir(), 'excel_test');
        file_put_contents($tempFile, $content);

        $loadedSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFile);
        $sheetNames = $loadedSpreadsheet->getSheetNames();
        $this->assertContains('Riwayat Peserta Non-Aktif', $sheetNames);

        $inactiveSheet = $loadedSpreadsheet->getSheetByName('Riwayat Peserta Non-Aktif');
        $this->assertNotNull($inactiveSheet);

        $found = false;
        for ($r = 1; $r <= 25; $r++) {
            if ($inactiveSheet->getCell("C{$r}")->getValue() === $this->mahasiswa2->name) {
                $this->assertEquals('Keluar Sendiri', (string) $inactiveSheet->getCell("D{$r}")->getValue());
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Mahasiswa {$this->mahasiswa2->name} should be listed in column C of Riwayat Peserta Non-Aktif sheet.");

        @unlink($tempFile);
    }

    /**
     * Verifikasi tampilan course dosen ketika ada mahasiswa terdaftar (tidak ada error Undefined array key "id").
     */
    public function test_lecturer_can_view_course_page_with_enrolled_students_without_undefined_id_error(): void
    {
        $this->enrollmentService->join($this->section, $this->mahasiswa1);

        $this->actingAs($this->dosen);
        $response = $this->get(route('dosen.course.show', $this->section->id));

        $response->assertOk();
        $response->assertSee($this->mahasiswa1->name);
        $response->assertSee('Keluarkan');
        $response->assertSee("kick-modal-{$this->mahasiswa1->id}");
    }

    /**
     * Verifikasi skenario pengguna: Mahasiswa keluar kelas, lalu Dosen membuka halaman course.
     */
    public function test_lecturer_views_course_after_student_leaves(): void
    {
        // Mahasiswa 1 dan 2 masuk
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->join($this->section, $this->mahasiswa2);

        // Mahasiswa 1 keluar kelas
        $this->actingAs($this->mahasiswa1);
        $this->post(route('mahasiswa.course.leave', $this->section->id));

        // Dosen membuka halaman course
        $this->actingAs($this->dosen);
        $response = $this->get(route('dosen.course.show', $this->section->id));

        $response->assertOk();
        $response->assertDontSee("kick-modal-{$this->mahasiswa1->id}");
        $response->assertSee("kick-modal-{$this->mahasiswa2->id}");
        $response->assertSee($this->mahasiswa2->name);
    }

    /**
     * Verifikasi penyesuaian: Menekan tombol "Ya, Daftarkan Saya" langsung mengarahkan ke halaman course
     * tanpa perantara card pendaftaran berhasil.
     */
    public function test_student_join_via_confirm_button_redirects_directly_to_course_page(): void
    {
        $this->actingAs($this->mahasiswa1);

        $response = $this->post(route('mahasiswa.join-kelas.post', $this->section->enrollment_code));

        $response->assertRedirect(route('mahasiswa.course.show', $this->section->id));
        $response->assertSessionHas('notice');

        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
        ]);
    }

    /**
     * Verifikasi penyesuaian: Notifikasi pengeluaran mahasiswa mengarahkan ke card konfirmasi pendaftaran ulang
     * (bukan course index), menampilkan alasan pengeluaran dosen, dan tombol "Daftar Ulang Kelas" langsung memasukkan ke kelas.
     */
    public function test_kicked_notification_links_to_join_kelas_card_and_allows_rejoining(): void
    {
        // 1. Dosen mengeluarkan mahasiswa dengan alasan
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->kick($this->section, $this->mahasiswa1, $this->dosen, 'Tidak sesuai RPS kelas A.');

        // 2. Cek notifikasi untuk mahasiswa
        $notifService = app(\App\Services\DatabaseNotificationService::class);
        $notifications = $notifService->forUser($this->mahasiswa1, 'mahasiswa');

        $kickedNotif = collect($notifications)->first(fn ($n) => str_starts_with($n['id'], "kicked_{$this->section->id}_"));
        $this->assertNotNull($kickedNotif);
        $this->assertEquals(route('mahasiswa.join-kelas', $this->section->enrollment_code, false), $kickedNotif['link']);
        $this->assertEquals('Daftar Ulang', $kickedNotif['action_label']);
        $this->assertStringContainsString('Tidak sesuai RPS kelas A.', $kickedNotif['message']);

        // 3. Mahasiswa mengakses URL dari notifikasi tersebut
        $this->actingAs($this->mahasiswa1);
        $response = $this->get($kickedNotif['link']);
        $response->assertOk();
        $response->assertSee('Daftar Ulang Kelas');
        $response->assertSee('Riwayat Pengeluaran dari Kelas');
        $response->assertSee('Tidak sesuai RPS kelas A.');
        $response->assertSee('Batalkan / Bukan Kelas Saya');

        // 4. Mahasiswa menekan tombol "Daftar Ulang Kelas"
        $joinResponse = $this->post(route('mahasiswa.join-kelas.post', $this->section->enrollment_code));
        $joinResponse->assertRedirect(route('mahasiswa.course.show', $this->section->id));
        $joinResponse->assertSessionHas('notice');

        // Pastikan kembali aktif
        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
        ]);
    }

    /**
     * Verifikasi penyesuaian: Tautan kelas yang dibagikan dapat dibuka sebelum login.
     * Pengguna belum login diarahkan ke login, setelah login otomatis kembali ke halaman kelas tersebut dan bisa bergabung.
     */
    public function test_unauthenticated_user_accessing_shared_class_link_redirects_to_login_and_then_to_class_card(): void
    {
        $joinUrl = route('mahasiswa.join-kelas', $this->section->enrollment_code);

        // 1. Buka tautan kelas tanpa login
        $guestResponse = $this->get($joinUrl);
        $guestResponse->assertRedirect(route('login'));
        $this->assertEquals($joinUrl, session('url.intended'));

        // 2. Lakukan login sebagai mahasiswa
        $loginResponse = $this->post(route('login.post'), [
            'login_id' => $this->mahasiswa1->email,
            'password' => 'password',
        ]);

        // Harus diarahkan kembali ke URL intended tautan kelas
        $loginResponse->assertRedirect($joinUrl);

        // 3. Buka halaman setelah login -> card konfirmasi pendaftaran tampil
        $cardResponse = $this->get($joinUrl);
        $cardResponse->assertOk();
        $cardResponse->assertSee('Konfirmasi Pendaftaran Kelas');
        $cardResponse->assertSee($this->section->display_code);
        $cardResponse->assertSee('Ya, Daftarkan Saya');

        // 4. Tekan tombol daftarkan diri -> langsung masuk ke course
        $joinResponse = $this->post(route('mahasiswa.join-kelas.post', $this->section->enrollment_code));
        $joinResponse->assertRedirect(route('mahasiswa.course.show', $this->section->id));

        $this->assertDatabaseHas('class_section_student', [
            'class_section_id' => $this->section->id,
            'mahasiswa_id' => $this->mahasiswa1->id,
            'status' => 'enrolled',
        ]);
    }

    /**
     * Verifikasi penyesuaian: Kelas diarsipkan menyembunyikan tombol tambah konten dosen,
     * membuka quiz-room dalam mode read-only untuk mahasiswa (tanpa 403),
     * dan mengizinkan mahasiswa yang sudah mengerjakan kuis untuk melihat jawaban/nilai.
     */
    public function test_archived_class_quiz_room_is_read_only_and_add_content_button_is_hidden(): void
    {
        $this->enrollmentService->join($this->section, $this->mahasiswa1);
        $this->enrollmentService->join($this->section, $this->mahasiswa2);

        $quiz = \App\Models\Assessment::create([
            'class_section_id' => $this->section->id,
            'code' => 'KUIS-01',
            'name' => 'Kuis Tengah Semester',
            'title' => 'Kuis Tengah Semester',
            'type' => 'kuis',
            'task_mode' => 'regular',
            'final_weight' => 20,
            'status' => 'published',
            'duration_enabled' => true,
            'duration_minutes' => 60,
            'points' => 100,
            'learning_payload' => [
                'task_mode' => 'regular',
                'duration_enabled' => true,
                'duration_minutes' => 60,
                'points' => 100,
                'questions' => [
                    [
                        'id' => 101,
                        'type' => 'pilihan',
                        'prompt' => 'Apa kompleksitas pencarian binary search?',
                        'points' => 50,
                        'option_items' => [
                            ['id' => 'opt-1', 'text' => 'O(1)'],
                            ['id' => 'opt-2', 'text' => 'O(log n)'],
                        ],
                    ],
                    [
                        'id' => 102,
                        'type' => 'uraian',
                        'prompt' => 'Jelaskan perbedaan stack dan queue!',
                        'points' => 50,
                    ],
                ],
            ],
            'questions' => [
                [
                    'id' => 101,
                    'type' => 'pilihan',
                    'prompt' => 'Apa kompleksitas pencarian binary search?',
                    'points' => 50,
                    'option_items' => [
                        ['id' => 'opt-1', 'text' => 'O(1)'],
                        ['id' => 'opt-2', 'text' => 'O(log n)'],
                    ],
                ],
                [
                    'id' => 102,
                    'type' => 'uraian',
                    'prompt' => 'Jelaskan perbedaan stack dan queue!',
                    'points' => 50,
                ],
            ],
        ]);

        // 1. Dosen pada kelas aktif melihat tombol tambah konten
        $this->actingAs($this->dosen);
        $dosenResponse = $this->get(route('dosen.course.item', [$this->section->id, $quiz->id]));
        $dosenResponse->assertOk();
        $dosenResponse->assertSee('+ Tambah Konten / Soal Baru');

        // 2. Arsipkan kelas
        $this->section->update(['archived_at' => now()]);

        // 3. Dosen pada kelas arsip TIDAK melihat tombol tambah konten
        $dosenArchived = $this->get(route('dosen.course.item', [$this->section->id, $quiz->id]));
        $dosenArchived->assertOk();
        $dosenArchived->assertDontSee('+ Tambah Konten / Soal Baru');

        // 4. Mahasiswa melihat halaman item: soal tidak di-dump di item page, melainkan ada tombol Buka Lembar Kuis
        $this->actingAs($this->mahasiswa1);
        $mhsItemResponse = $this->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $mhsItemResponse->assertOk();
        $mhsItemResponse->assertSee('Buka Lembar Kuis (Read-Only)');
        $mhsItemResponse->assertDontSee('Pilihan satu opsi jawaban yang paling tepat:');

        // 5. Mahasiswa membuka quiz-room: tidak 403, melainkan status 200 read-only
        $quizRoomResponse = $this->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $quizRoomResponse->assertOk();
        $quizRoomResponse->assertSee('Kelas telah diarsipkan. Lembar kuis ini dibuka dalam mode hanya-baca');
        $quizRoomResponse->assertSee('Pengerjaan Ditutup');
        $quizRoomResponse->assertSee('Apa kompleksitas pencarian binary search?');

        // 6. Mahasiswa mencoba submit kuis: ditolak backend 403
        $submitResponse = $this->post(route('mahasiswa.course.submit', [$this->section->id, $quiz->id]), [
            'from_quiz_room' => 1,
            'question_answers' => [
                101 => ['option_ids' => ['opt-2']],
            ],
        ]);
        $submitResponse->assertStatus(403);

        // 7. Mahasiswa yang sudah mengerjakan kuis sebelumnya dapat melihat lembar jawaban & nilainya di quiz-room tanpa 403
        \App\Models\Submission::create([
            'assessment_id' => $quiz->id,
            'user_id' => $this->mahasiswa2->id,
            'mahasiswa_id' => $this->mahasiswa2->id,
            'status' => 'graded',
            'submitted_at' => now()->subDay(),
            'question_answers' => [
                101 => ['question_id' => 101, 'option_ids' => ['opt-2']],
                102 => ['question_id' => 102, 'text' => 'Stack LIFO, Queue FIFO.'],
            ],
        ]);
        \App\Models\StudentAssessmentScore::create([
            'class_section_id' => $this->section->id,
            'assessment_id' => $quiz->id,
            'mahasiswa_id' => $this->mahasiswa2->id,
            'score' => 95,
            'status' => 'published',
        ]);

        $this->actingAs($this->mahasiswa2);
        $completedItemResponse = $this->get(route('mahasiswa.course.item', [$this->section->id, $quiz->id]));
        $completedItemResponse->assertOk();
        $completedItemResponse->assertSee('Lihat Jawaban');

        $completedQuizRoomResponse = $this->get(route('mahasiswa.quiz.room', [$this->section->id, $quiz->id]));
        $completedQuizRoomResponse->assertOk();
        $completedQuizRoomResponse->assertSee('Nilai Perolehan Kuis:');
    }

    public function test_class_capacity_limit_blocks_joining_when_full(): void
    {
        // Atur kapasitas seksi menjadi 2 dan daftarkan 2 mahasiswa
        $this->section->update(['capacity' => 2]);
        $this->section->students()->attach($this->mahasiswa1->id, ['status' => 'enrolled']);
        $this->section->students()->attach($this->mahasiswa2->id, ['status' => 'enrolled']);

        $this->assertEquals(2, $this->section->students()->count());

        // Mahasiswa ketiga mencoba join kelas
        $mhsRole = \App\Models\Role::firstOrCreate(['name' => \App\Models\Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $newStudent = User::factory()->create([
            'role_id' => $mhsRole->id,
            'name' => 'Mahasiswa Ketiga',
            'email' => 'mhs3@student.test',
        ]);

        $this->actingAs($newStudent);

        // 1. Pada halaman konfirmasi, tombol pendaftaran tidak muncul dan menampilkan peringatan kapasitas penuh
        $confirmResponse = $this->get(route('mahasiswa.join-kelas', $this->section->enrollment_code));
        $confirmResponse->assertOk();
        $confirmResponse->assertSee('Kapasitas Kelas Telah Penuh');
        $confirmResponse->assertDontSee('Ya, Daftarkan Saya');

        // 2. Submit form join-kelas ditolak dengan status kapasitas penuh
        $joinResponse = $this->post(route('mahasiswa.join-kelas.post', $this->section->enrollment_code));
        $joinResponse->assertSee('Kapasitas kelas telah penuh');
        $this->assertFalse($this->section->students()->where('users.id', $newStudent->id)->exists());
    }
}


