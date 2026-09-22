<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutRoleDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_displays_clean_role_label_without_json_dump(): void
    {
        $dosenRole = Role::create(['name' => Role::DOSEN, 'label' => 'Dosen']);
        $dosen = User::create([
            'name' => 'Budi Santoso, M.Kom.',
            'email' => 'budi@example.test',
            'nim_nidn' => '198501012010121001',
            'password' => 'secret',
            'role_id' => $dosenRole->id,
        ]);

        $response = $this->actingAs($dosen)->get(route('dosen.penilaian.index'));

        $response->assertOk();
        // Pastikan tidak ada raw json role di render
        $response->assertDontSee('{"id":');
        $response->assertSee('Beralih Peran (Dosen & Kaprodi)');
        $response->assertDontSee('Mahasiswa (Ahmad Maulana)');

        // Mahasiswa tidak melihat opsi switch role sama sekali
        $mhsRole = Role::firstOrCreate(['name' => Role::MAHASISWA], ['label' => 'Mahasiswa']);
        $mhs = User::create([
            'name' => 'Ahmad Maulana',
            'email' => 'ahmad@example.test',
            'nim_nidn' => '231011401234',
            'password' => 'secret',
            'role_id' => $mhsRole->id,
        ]);
        $mhsResponse = $this->actingAs($mhs)->get(route('mahasiswa.dashboard'));
        $mhsResponse->assertOk();
        $mhsResponse->assertDontSee('Beralih Peran');
    }

    public function test_can_switch_to_all_5_roles(): void
    {
        // 1. Switch to Kaprodi
        $this->get(route('switch-role', 'kaprodi'))
            ->assertRedirect(route('kaprodi.monitoring.cpmk'));

        // 2. Switch to Admin Prodi
        $this->get(route('switch-role', 'admin_prodi'))
            ->assertRedirect(route('admin-prodi.dashboard'));

        // 3. Switch to Admin Sistem
        $this->get(route('switch-role', 'admin'))
            ->assertRedirect(route('admin.page', 'dashboard'));

        // 4. Switch to Dosen
        $this->get(route('switch-role', 'dosen'))
            ->assertRedirect(route('dosen.dashboard'));

        // 5. Switch to Mahasiswa
        $this->get(route('switch-role', 'mahasiswa'))
            ->assertRedirect(route('mahasiswa.dashboard'));
    }
}
