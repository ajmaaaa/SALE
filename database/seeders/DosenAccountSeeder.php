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

        // S08: Gunakan firstOrCreate (bukan updateOrCreate) agar seeder tidak pernah
        // menimpa password atau data user yang sudah ada di database produksi.
        // Kolom selain 'email' hanya diisi saat user pertama kali dibuat.

        $repairDemoRole = static function (string $email, ?int $roleId): void {
            if (! $roleId) {
                return;
            }

            $user = User::where('email', $email)->first();
            if ($user && $user->role_id !== $roleId) {
                $user->update(['role_id' => $roleId]);
            }
        };

        User::firstOrCreate(
            ['email' => 'ahmad.maulana@student.test'],
            [
                'name' => 'Ahmad Maulana',
                'password' => Hash::make('password'),
                'role_id' => $mahasiswaRole?->id,
                'nim_nidn' => '231011401234',
            ]
        );

        // Repair role_id when an older local database was seeded before the
        // five demo roles were finalized. Passwords and other user data stay intact.
        $repairDemoRole('ahmad.maulana@student.test', $mahasiswaRole?->id);
        $repairDemoRole('budi@example.test', $dosenRole?->id);
        $repairDemoRole('kaprodi@example.test', $kaprodiRole?->id);
        $repairDemoRole('adminprodi@example.test', $adminProdiRole?->id);
        $repairDemoRole('admin@example.test', $adminRole?->id);

        User::firstOrCreate(
            ['email' => 'budi@example.test'],
            [
                'name' => 'Budi Santoso, M.Kom.',
                'password' => Hash::make('password'),
                'role_id' => $dosenRole?->id,
                'nim_nidn' => '198501012010121001',
            ]
        );

        User::firstOrCreate(
            ['email' => 'kaprodi@example.test'],
            [
                'name' => 'Budi Santoso, M.Kom.',
                'password' => Hash::make('password'),
                'role_id' => $kaprodiRole?->id,
                'nim_nidn' => '197501012000031001',
            ]
        );

        User::firstOrCreate(
            ['email' => 'adminprodi@example.test'],
            [
                'name' => 'Admin Prodi TI',
                'password' => Hash::make('password'),
                'role_id' => $adminProdiRole?->id,
                'nim_nidn' => 'AP001',
            ]
        );

        User::firstOrCreate(
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
