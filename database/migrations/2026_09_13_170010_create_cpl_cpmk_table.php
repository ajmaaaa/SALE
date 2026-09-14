<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Many-to-many: one CPMK can contribute to several CPL, and one CPL
     * can be built from several CPMK. `weight` is the CPMK's contribution
     * to that specific CPL and should be normalized to 100% per CPL at
     * the application layer.
     */
    public function up(): void
    {
        Schema::create('cpl_cpmk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete();
            $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
            $table->decimal('weight', 5, 2); // contribution of this CPMK to this CPL (%)
            $table->timestamps();

            $table->unique(['cpl_id', 'cpmk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpl_cpmk');
    }
};
