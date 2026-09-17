<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Many-to-many: one assessment can measure several CPMK, and one CPMK
     * can be measured by several assessments. `weight` is this assessment's
     * contribution to that specific CPMK and should be normalized to 100%
     * per CPMK at the application layer.
     */
    public function up(): void
    {
        Schema::create('assessment_cpmk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
            $table->decimal('weight', 5, 2); // contribution of this assessment to this CPMK (%)
            $table->timestamps();

            $table->unique(['assessment_id', 'cpmk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_cpmk');
    }
};
