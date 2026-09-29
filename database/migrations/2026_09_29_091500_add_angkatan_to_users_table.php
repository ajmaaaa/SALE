<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('angkatan')->nullable()->after('nim_nidn');
        });

        // Backfill angkatan for existing accounts
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $angkatan = null;
            $nim = trim((string) $user->nim_nidn);

            if (preg_match('/^(20\d{2})/', $nim, $matches)) {
                $angkatan = (int) $matches[1];
            } elseif (preg_match('/^(\d{2})/', $nim, $matches)) {
                $twoDigits = (int) $matches[1];
                if ($twoDigits >= 18 && $twoDigits <= 35) {
                    $angkatan = 2000 + $twoDigits;
                }
            }

            if (! $angkatan && ! empty($user->created_at)) {
                $angkatan = (int) date('Y', strtotime($user->created_at));
            }

            if ($angkatan) {
                DB::table('users')->where('id', $user->id)->update([
                    'angkatan' => $angkatan,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('angkatan');
        });
    }
};
