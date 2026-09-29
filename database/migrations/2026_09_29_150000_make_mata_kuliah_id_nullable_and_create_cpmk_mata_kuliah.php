<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add prodi_id to cpmks and make mata_kuliah_id nullable
        Schema::table('cpmks', function (Blueprint $table) {
            if (!Schema::hasColumn('cpmks', 'prodi_id')) {
                $table->foreignId('prodi_id')->nullable()->after('id')->constrained('prodis')->cascadeOnDelete();
            }
            $table->foreignId('mata_kuliah_id')->nullable()->change();
        });

        // Backfill prodi_id from mata_kuliahs if available
        if (Schema::hasColumn('cpmks', 'prodi_id')) {
            DB::statement("UPDATE cpmks JOIN mata_kuliahs ON mata_kuliahs.id = cpmks.mata_kuliah_id SET cpmks.prodi_id = mata_kuliahs.prodi_id WHERE cpmks.prodi_id IS NULL");
        }

        // 2. Create pivot table cpmk_mata_kuliah
        if (!Schema::hasTable('cpmk_mata_kuliah')) {
            Schema::create('cpmk_mata_kuliah', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
                $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['mata_kuliah_id', 'cpmk_id']);
            });

            // Populate existing associations from cpmks into cpmk_mata_kuliah
            DB::statement("INSERT IGNORE INTO cpmk_mata_kuliah (mata_kuliah_id, cpmk_id, created_at, updated_at) SELECT mata_kuliah_id, id, NOW(), NOW() FROM cpmks WHERE mata_kuliah_id IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cpmk_mata_kuliah');

        Schema::table('cpmks', function (Blueprint $table) {
            if (Schema::hasColumn('cpmks', 'prodi_id')) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            }
            $table->foreignId('mata_kuliah_id')->nullable(false)->change();
        });
    }
};
