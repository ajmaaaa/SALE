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
    }

    public function test_notification_preferences_persist_checked_and_unchecked_values(): void
    {
        $this->actingAs($this->lecturer)
            ->put(route('dosen.profile.notifications'), [
                'preferences' => [
                    'notif_deadline' => '1',
                    'notif_forum' => '1',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'notification-preferences-updated');

        $this->assertSame([
            'notif_submission' => false,
            'notif_deadline' => true,
            'notif_forum' => true,
            'notif_rekap' => false,
        ], $this->lecturer->fresh()->notification_preferences);
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
}