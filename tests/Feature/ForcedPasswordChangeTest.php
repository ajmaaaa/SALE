<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_blocks_all_web_features_until_it_is_changed(): void
    {
        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => 'TemporaryPass123!',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->get(route('mahasiswa.dashboard'))
            ->assertRedirect(route('password.change'));
        $this->get(route('mahasiswa.nilai'))
            ->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk();

        $this->post(route('password.change.update'), [
            'password' => 'too-short',
            'password_confirmation' => 'too-short',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.change.update'), [
            'password' => 'PermanentPass456!',
            'password_confirmation' => 'PermanentPass456!',
        ])->assertRedirect(route('mahasiswa.dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('PermanentPass456!', $user->password));
        $this->get(route('mahasiswa.nilai'))->assertOk();
    }

    public function test_user_with_temporary_password_can_still_log_out(): void
    {
        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
