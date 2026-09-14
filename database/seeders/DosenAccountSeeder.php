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

        User::updateOrCreate(
            ['email' => 'budi@example.test'],
            [
                'name' => 'Budi Santoso, M.Kom.',
                'password' => Hash::make('password'),
                'role_id' => $dosenRole?->id,
                'nim_nidn' => '198501012010121001',
            ]
        );
    }
}