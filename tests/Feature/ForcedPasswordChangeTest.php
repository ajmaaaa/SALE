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
            'password' => 'short7',
            'password_confirmation' => 'short7',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.change.update'), [
            'password' => 'Pass1234',
            'password_confirmation' => 'Pass1234',
        ])->assertRedirect(route('mahasiswa.dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('Pass1234', $user->password));
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

    public function test_user_with_temporary_password_can_open_login_and_replace_the_account(): void
    {
        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'must_change_password' => true,
        ]);
        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $dosen = User::factory()->create([
            'email' => 'budi@example.test',
            'role_id' => $dosenRole->id,
            'password' => 'password',
            'must_change_password' => false,
        ]);

        $this->actingAs($user)->get(route('login'))->assertOk();

        $this->post(route('login.post'), [
            'login_id' => 'budi@example.test',
            'password' => 'password',
        ])->assertRedirect(route('dosen.dashboard'));

        $this->assertAuthenticatedAs($dosen);
    }

    public function test_password_change_page_uses_sale_layout_and_eight_character_rule(): void
    {
        $role = Role::create(['name' => Role::MAHASISWA, 'label' => 'Mahasiswa']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->get(route('password.change'))
            ->assertOk()
            ->assertSee('Smart Academic Learning Ecosystem')
            ->assertSee('minlength="8"', false)
            ->assertDontSee('Minimal 12 karakter');
    }
}
