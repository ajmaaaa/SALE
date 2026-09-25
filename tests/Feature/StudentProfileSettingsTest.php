<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $this->student = User::create([
            'name' => 'Ahmad Maulana',
            'email' => 'ahmad-profile@test.local',
            'nim_nidn' => '231011401234',
            'password' => 'Currentpass123!',
            'role_id' => $role->id,
        ]);
    }

    public function test_profile_reads_identity_and_preferences_from_the_database(): void
    {
        $this->student->forceFill(['notification_preferences' => [
            'announcement' => false,
            'deadline' => true,
            'grade' => false,
            'forum' => true,
        ]])->save();

        $response = $this->actingAs($this->student)
            ->get(route('mahasiswa.profile.index'))
            ->assertOk()
            ->assertSee('Ahmad Maulana')
            ->assertSee('231011401234')
            ->assertSee('ahmad-profile@test.local');

        $this->assertMatchesRegularExpression('/id="deadline"[^>]*\schecked(?:\s|>)/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/id="announcement"[^>]*\schecked(?:\s|>)/', $response->getContent());
    }

    public function test_student_can_update_password_after_current_password_check(): void
    {
        $this->actingAs($this->student)
            ->put(route('mahasiswa.profile.password'), [
                'current_password' => 'Currentpass123!',
                'new_password' => 'Updatedpass456!',
                'new_password_confirmation' => 'Updatedpass456!',
            ])
            ->assertRedirect(route('mahasiswa.profile.index').'#keamanan')
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('Updatedpass456!', $this->student->fresh()->password));
    }

    public function test_preferences_save_unchecked_values_without_removing_other_preferences(): void
    {
        $this->student->forceFill(['notification_preferences' => ['notif_forum' => true]])->save();

        $this->actingAs($this->student)
            ->put(route('mahasiswa.profile.notifications'), [
                'preferences' => [
                    'deadline' => '1',
                    'forum' => '1',
                ],
            ])
            ->assertRedirect(route('mahasiswa.profile.index').'#notifikasi')
            ->assertSessionHas('status', 'notification-preferences-updated');

        $this->assertSame([
            'notif_forum' => true,
            'announcement' => false,
            'deadline' => true,
            'grade' => false,
            'forum' => true,
        ], $this->student->fresh()->notification_preferences);
    }

    public function test_profile_photo_can_be_uploaded_once(): void
    {
        Storage::fake('public');

        $this->actingAs($this->student)
            ->post(route('mahasiswa.profile.photo'), [
                'photo' => UploadedFile::fake()->image('avatar.png'),
            ])
            ->assertRedirect(route('mahasiswa.profile.index').'#profil')
            ->assertSessionHas('status', 'profile-photo-uploaded');

        $storedPath = $this->student->fresh()->profile_photo_path;
        $this->assertNotEmpty($storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $this->actingAs($this->student->fresh())
            ->from(route('mahasiswa.profile.index').'#profil')
            ->post(route('mahasiswa.profile.photo'), [
                'photo' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertSame($storedPath, $this->student->fresh()->profile_photo_path);
    }
}