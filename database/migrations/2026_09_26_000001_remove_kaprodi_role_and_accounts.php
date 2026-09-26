<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasTable('roles')) {
            $kaprodiRole = DB::table('roles')->where('name', 'kaprodi')->first();
            if ($kaprodiRole) {
                DB::table('users')->where('role_id', $kaprodiRole->id)->delete();
                DB::table('roles')->where('id', $kaprodiRole->id)->delete();
            }
            DB::table('users')->where('email', 'kaprodi@example.test')->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->insertOrIgnore([
                'name' => 'kaprodi',
                'label' => 'Kaprodi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
