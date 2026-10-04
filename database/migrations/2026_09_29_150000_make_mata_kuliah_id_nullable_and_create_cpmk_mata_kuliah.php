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
            if (! Schema::hasColumn('cpmks', 'prodi_id')) {
                $table->foreignId('prodi_id')->nullable()->after('id')->constrained('prodis')->cascadeOnDelete();
            }
            $table->foreignId('mata_kuliah_id')->nullable()->change();
        });

        // Backfill prodi_id from mata_kuliahs if available
        if (Schema::hasColumn('cpmks', 'prodi_id')) {
            DB::table('cpmks')
                ->whereNull('prodi_id')
                ->whereNotNull('mata_kuliah_id')
                ->orderBy('id')
                ->chunkById(500, function ($cpmks): void {
                    $prodiByCourse = DB::table('mata_kuliahs')
                        ->whereIn('id', $cpmks->pluck('mata_kuliah_id')->filter()->unique())
                        ->pluck('prodi_id', 'id');

                    foreach ($cpmks as $cpmk) {
                        $prodiId = $prodiByCourse->get($cpmk->mata_kuliah_id);
                        if ($prodiId !== null) {
                            DB::table('cpmks')->where('id', $cpmk->id)->update(['prodi_id' => $prodiId]);
                        }
                    }
                });
        }

        // 2. Create pivot table cpmk_mata_kuliah
        if (! Schema::hasTable('cpmk_mata_kuliah')) {
            Schema::create('cpmk_mata_kuliah', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
                $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['mata_kuliah_id', 'cpmk_id']);
            });

            // Populate existing associations portably across MySQL and SQLite.
            DB::table('cpmks')
                ->whereNotNull('mata_kuliah_id')
                ->orderBy('id')
                ->chunkById(500, function ($cpmks): void {
                    $timestamp = now();
                    $rows = $cpmks->map(fn ($cpmk) => [
                        'mata_kuliah_id' => $cpmk->mata_kuliah_id,
                        'cpmk_id' => $cpmk->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])->all();

                    DB::table('cpmk_mata_kuliah')->insertOrIgnore($rows);
                });
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
