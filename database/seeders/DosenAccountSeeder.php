<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DosenAccountSeeder extends Seeder
{
    public function run(): void
    {
        $dosenRole = Role::where('name', Role::DOSEN)->first();
        $kaprodiRole = Role::where('name', Role::KAPRODI)->first();
        $adminProdiRole = Role::where('name', Role::ADMIN_PRODI)->first();
        $adminRole = Role::where('name', Role::ADMIN)->first();
        $mahasiswaRole = Role::where('name', Role::MAHASISWA)->first();

        User::updateOrCreate(
            ['email' => 'ahmad.maulana@student.test'],
            [
                'name' => 'Ahmad Maulana',
                'password' => Hash::make('password'),
                'role_id' => $mahasiswaRole?->id,
                'nim_nidn' => '231011401234',
            ]
        );

        User::updateOrCreate(
            ['email' => 'budi@example.test'],
            [
                'name' => 'Budi Santoso, M.Kom.',
                'password' => Hash::make('password'),
                'role_id' => $dosenRole?->id,
                'nim_nidn' => '198501012010121001',
            ]
        );

        User::updateOrCreate(
            ['email' => 'kaprodi@example.test'],
            [
                'name' => 'Dr. H. Kaprodi, M.T.',
                'password' => Hash::make('password'),
                'role_id' => $kaprodiRole?->id,
                'nim_nidn' => '197501012000031001',
            ]
        );

        User::updateOrCreate(
            ['email' => 'adminprodi@example.test'],
            [
                'name' => 'Admin Prodi TI',
                'password' => Hash::make('password'),
                'role_id' => $adminProdiRole?->id,
                'nim_nidn' => 'AP001',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Admin Sistem Akademik',
                'password' => Hash::make('password'),
                'role_id' => $adminRole?->id,
                'nim_nidn' => 'ADM001',
            ]
        );
    }
}