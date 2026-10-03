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
        Schema::table('cpmk_mata_kuliah', function (Blueprint $table) {
            if (!Schema::hasColumn('cpmk_mata_kuliah', 'cpl_id')) {
                $table->foreignId('cpl_id')
                    ->nullable()
                    ->after('cpmk_id')
                    ->constrained('cpls')
                    ->nullOnDelete();
            }
            $table->index('mata_kuliah_id', 'cpmk_mk_idx');
        });

        Schema::table('cpmk_mata_kuliah', function (Blueprint $table) {
            $table->dropUnique('cpmk_mata_kuliah_mata_kuliah_id_cpmk_id_unique');
            $table->unique(['mata_kuliah_id', 'cpmk_id', 'cpl_id'], 'cpmk_mk_cpl_unique');
        });

        // Correlated subquery works across MySQL, MariaDB, and SQLite
        DB::statement("
            UPDATE cpmk_mata_kuliah
            SET cpl_id = (
                SELECT MIN(cpl_id)
                FROM cpl_cpmk
                WHERE cpl_cpmk.cpmk_id = cpmk_mata_kuliah.cpmk_id
            )
            WHERE cpl_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cpmk_mata_kuliah', function (Blueprint $table) {
            $table->dropUnique('cpmk_mk_cpl_unique');
            $table->unique(['mata_kuliah_id', 'cpmk_id'], 'cpmk_mata_kuliah_mata_kuliah_id_cpmk_id_unique');
            $table->dropIndex('cpmk_mk_idx');
            $table->dropForeign(['cpl_id']);
            $table->dropColumn('cpl_id');
        });
    }
};
