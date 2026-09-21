<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['admin', 'admin_prodi', 'kaprodi', 'dosen', 'mahasiswa'])
            ->pluck('id', 'name');

        $demoRoles = [
            'ahmad.maulana@student.test' => 'mahasiswa',
            'budi@example.test' => 'dosen',
            'kaprodi@example.test' => 'kaprodi',
            'adminprodi@example.test' => 'admin_prodi',
            'admin@example.test' => 'admin',
        ];

        foreach ($demoRoles as $email => $role) {
            if (isset($roleIds[$role])) {
                DB::table('users')->where('email', $email)->update(['role_id' => $roleIds[$role]]);
            }
        }
    }

    public function down(): void
    {
        // Role repair is data correction and is intentionally not reversed.
    }
};
