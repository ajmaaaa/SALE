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
            if (! Schema::hasColumn('cpmk_mata_kuliah', 'cpl_id')) {
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

        DB::table('cpmk_mata_kuliah')
            ->whereNull('cpl_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $cplByCpmk = DB::table('cpl_cpmk')
                    ->whereIn('cpmk_id', $rows->pluck('cpmk_id')->unique())
                    ->selectRaw('cpmk_id, MIN(cpl_id) AS cpl_id')
                    ->groupBy('cpmk_id')
                    ->pluck('cpl_id', 'cpmk_id');

                foreach ($rows as $row) {
                    $cplId = $cplByCpmk->get($row->cpmk_id);
                    if ($cplId !== null) {
                        DB::table('cpmk_mata_kuliah')->where('id', $row->id)->update(['cpl_id' => $cplId]);
                    }
                }
            });
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
