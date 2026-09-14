<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the 5 fixed roles used by SALE. This list is intentionally
     * closed — the requirement is explicit that no role beyond these 5
     * should be introduced without instruction.
     */
    public function run(): void
    {
        $roles = [
            ['name' => Role::ADMIN, 'label' => 'Admin'],
            ['name' => Role::ADMIN_PRODI, 'label' => 'Admin Prodi'],
            ['name' => Role::KAPRODI, 'label' => 'Kaprodi'],
            ['name' => Role::DOSEN, 'label' => 'Dosen'],
            ['name' => Role::MAHASISWA, 'label' => 'Mahasiswa'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
