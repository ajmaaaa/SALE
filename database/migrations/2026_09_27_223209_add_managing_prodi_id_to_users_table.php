<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'managing_prodi_id')) {
                $table->foreignId('managing_prodi_id')
                    ->nullable()
                    ->after('prodi_id')
                    ->constrained('prodis')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'managing_prodi_id')) {
                $table->dropConstrainedForeignId('managing_prodi_id');
            }
        });
    }
};
