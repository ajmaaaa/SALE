<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LecturerProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $lecturer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $this->lecturer = User::create([
            'name' => 'Dosen Uji',
            'email' => 'dosen-profile@test.local',
            'password' => 'Currentpass123!',
            'role_id' => $role->id,
        ]);
    }

    public function test_profile_displays_database_identity_and_saved_preferences(): void
    {
        $this->lecturer->forceFill(['notification_preferences' => [
            'notif_submission' => false,
            'notif_deadline' => true,
            'notif_forum' => true,
            'notif_rekap' => false,
        ]])->save();

        $response = $this->actingAs($this->lecturer)
            ->get(route('dosen.profile.index'))
            ->assertOk()
            ->assertSee('Dosen Uji')
            ->assertSee('dosen-profile@test.local');

        $this->assertMatchesRegularExpression('/id="notif_deadline"[^>]*\schecked(?:\s|>)/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/id="notif_submission"[^>]*\schecked(?:\s|>)/', $response->getContent());
    }

    public function test_lecturer_can_update_password_after_current_password_check(): void
    {
        $this->actingAs($this->lecturer)
            ->put(route('dosen.profile.password'), [
                'current_password' => 'Currentpass123!',
                'new_password' => 'Updatedpass456!',
                'new_password_confirmation' => 'Updatedpass456!',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('Updatedpass456!', $this->lecturer->fresh()->password));

        // Verifikasi login dengan password baru berhasil
        $this->post('/logout');
        $this->assertGuest();

        // Login dengan password lama harus ditolak
        $failLogin = $this->post('/login', [
            'login_id' => $this->lecturer->email,
            'password' => 'Currentpass123!',
        ]);
        $failLogin->assertSessionHasErrors(['login_id', 'password']);
        $this->assertGuest();

        // Login dengan password baru harus sukses
        $successLogin = $this->post('/login', [
            'login_id' => $this->lecturer->email,
            'password' => 'Updatedpass456!',
        ]);
        $successLogin->assertRedirect('/dosen/dashboard');
        $this->assertAuthenticatedAs($this->lecturer);
    }

    public function test_lecturer_password_update_fails_with_wrong_current_password_or_mismatched_confirmation(): void
    {
        // 1. Password saat ini salah
        $resWrong = $this->actingAs($this->lecturer)
            ->put(route('dosen.profile.password'), [
                'current_password' => 'Wrongpass999!',
                'new_password' => 'Newpass123!',
                'new_password_confirmation' => 'Newpass123!',
            ]);
        $resWrong->assertSessionHasErrors('current_password');

        // 2. Konfirmasi password tidak cocok
        $resMismatch = $this->actingAs($this->lecturer)
            ->put(route('dosen.profile.password'), [
                'current_password' => 'Currentpass123!',
                'new_password' => 'Newpass123!',
                'new_password_confirmation' => 'Differentpass123!',
            ]);
        $resMismatch->assertSessionHasErrors('new_password');
    }

    public function test_notification_preferences_persist_checked_and_unchecked_values(): void
    {
        $response = $this->actingAs($this->lecturer)
            ->put(route('dosen.profile.notifications'), [
                'preferences' => [
                    'notif_deadline' => '1',
                    'notif_forum' => '1',
                ],
            ]);

        $response->assertRedirect()
            ->assertSessionHas('status', 'notification-preferences-updated')
            ->assertSessionHas('notice', 'Preferensi notifikasi berhasil disimpan.');

        $this->assertSame([
            'notif_submission' => false,
            'notif_deadline' => true,
            'notif_forum' => true,
            'notif_rekap' => false,
        ], $this->lecturer->fresh()->notification_preferences);

        $view = $this->actingAs($this->lecturer)->get(route('dosen.profile.index'));
        $view->assertSee('id="toast-notice"', false);
        $view->assertSee('Preferensi notifikasi berhasil disimpan.');
    }

    public function test_non_dosen_cannot_update_lecturer_settings(): void
    {
        $studentRole = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $student = User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'mahasiswa-profile@test.local',
            'password' => 'Currentpass123!',
            'role_id' => $studentRole->id,
        ]);

        $this->actingAs($student)
            ->put(route('dosen.profile.notifications'), ['preferences' => ['notif_forum' => '1']])
            ->assertForbidden();
    }

    public function test_lecturer_profile_displays_active_semester_and_total_classes_from_database(): void
    {
        $semester = \App\Models\Semester::create([
            'code' => '2026-GANJIL',
            'name' => 'Ganjil 2026/2027',
            'is_active' => true,
        ]);

        $prodi = \App\Models\Prodi::create([
            'code' => 'IF',
            'name' => 'Informatika',
        ]);

        $mataKuliah = \App\Models\MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'IF202',
            'name' => 'Basis Data',
            'sks' => 3,
        ]);

        $otherLecturer = User::create([
            'name' => 'Dosen Utama',
            'email' => 'dosen-utama@test.local',
            'password' => 'Pass123!',
            'role_id' => Role::where('name', Role::DOSEN)->firstOrFail()->id,
        ]);

        // Class where lecturer is primary
        \App\Models\ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->lecturer->id,
            'section_code' => 'A',
            'capacity' => 30,
        ]);

        // Class where lecturer is co-lecturer (dosen pendamping)
        \App\Models\ClassSection::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_id' => $semester->id,
            'dosen_id' => $otherLecturer->id,
            'dosen_pendamping_id' => $this->lecturer->id,
            'section_code' => 'B',
            'capacity' => 30,
        ]);

        $response = $this->actingAs($this->lecturer)
            ->get(route('dosen.profile.index'));

        $response->assertOk();
        $response->assertSee('Ganjil 2026/2027');
        // Total classes should be 2 (1 primary + 1 co-teaching)
        $response->assertSee('2 Kelas');
    }

    public function test_support_admin_email_is_placed_in_sidebar_and_not_in_profile_content(): void
    {
        \App\Models\SystemSetting::updateOrCreate(['key' => 'support'], ['value' => 'admin.support@univ.ac.id']);

        $response = $this->actingAs($this->lecturer)
            ->get(route('dosen.profile.index'));

        $response->assertOk();
        // Support email is in the sidebar
        $response->assertSee('admin.support@univ.ac.id');
        $response->assertSee('mailto:admin.support@univ.ac.id', false);

        // Verify it is removed from the profile information card section
        $content = $response->getContent();
        $this->assertStringNotContainsString('Bantuan &amp; Narahubung:', $content);
    }

    public function test_lecturer_notification_preferences_filter_notifications_returned_by_service(): void
    {
        $prodi = \App\Models\Prodi::create([
            'code' => 'TI',
            'name' => 'Teknik Informatika',
            'slug' => 'teknik-informatika-dosen',
        ]);
        $course = \App\Models\MataKuliah::create([
            'prodi_id' => $prodi->id,
            'code' => 'TI201',
            'name' => 'Struktur Data',
            'sks' => 3,
            'semester' => 2,
        ]);
        $semester = \App\Models\Semester::create([
            'code' => '20262',
            'name' => '2026/2027 Genap',
            'academic_year' => '2026/2027',
            'term' => 2,
            'is_active' => true,
        ]);
        $section = \App\Models\ClassSection::create([
            'mata_kuliah_id' => $course->id,
            'semester_id' => $semester->id,
            'dosen_id' => $this->lecturer->id,
            'section_code' => 'A',
            'capacity' => 40,
        ]);

        $assessment = \App\Models\Assessment::create([
            'class_section_id' => $section->id,
            'code' => 'TUGAS-2',
            'name' => 'Tugas 2',
            'type' => 'tugas',
            'final_weight' => 20,
            'status' => 'published',
            'due_at' => now()->addDays(3),
        ]);

        $studentRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $student = User::create([
            'name' => 'Siswa Pengumpul',
            'email' => 'siswa-pengumpul@test.local',
            'password' => 'Pass12345!',
            'role_id' => $studentRole->id,
        ]);

        $submission = \App\Models\Submission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'mahasiswa_id' => $student->id,
            'attempt' => 1,
            'status' => 'submitted',
            'submitted_at' => now(),
            'answer' => 'Jawaban mahasiswa.',
        ]);

        $service = app(\App\Services\DatabaseNotificationService::class);

        // By default (no preferences or notif_submission: true), submission notification is visible
        $this->lecturer->forceFill(['notification_preferences' => null])->save();
        $notifs = $service->forUser($this->lecturer, 'dosen');
        $this->assertTrue(collect($notifs)->contains('id', "submission_{$submission->id}"));

        // When notif_submission is muted, submission notification is filtered out
        $this->lecturer->forceFill(['notification_preferences' => [
            'notif_submission' => false,
            'notif_deadline' => true,
            'notif_forum' => true,
            'notif_rekap' => true,
        ]])->save();
        $notifsFiltered = $service->forUser($this->lecturer, 'dosen');
        $this->assertFalse(collect($notifsFiltered)->contains('id', "submission_{$submission->id}"));

        // Verify on HTTP notification page: submission is not shown when muted
        $this->actingAs($this->lecturer)
            ->get(route('dosen.notifications'))
            ->assertOk()
            ->assertDontSee('Siswa Pengumpul');

        // When notif_submission is re-enabled, submission appears on notification page
        $this->lecturer->forceFill(['notification_preferences' => [
            'notif_submission' => true,
            'notif_deadline' => true,
            'notif_forum' => true,
            'notif_rekap' => true,
        ]])->save();
        $this->actingAs($this->lecturer)
            ->get(route('dosen.notifications'))
            ->assertOk()
            ->assertSee('Siswa Pengumpul');
    }
}