<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These columns are additive only. Existing `users` rows and the
     * session-based persona-switcher (AdminPreview::users()) keep working
     * untouched — `role_id` is nullable so it does not force a migration
     * of preview/demo accounts. It is populated only for users created
     * through the new real authentication flow (Dosen for now).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')
                ->constrained('roles')->nullOnDelete();
            $table->foreignId('prodi_id')->nullable()->after('role_id')
                ->constrained('prodis')->nullOnDelete();
            $table->string('nim_nidn')->nullable()->unique()->after('prodi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('prodi_id');
            $table->dropColumn('nim_nidn');
        });
    }
};
