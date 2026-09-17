<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores a student's score for a specific CPMK measured by an assessment.
     * For example, in a UAS measuring CPMK-01, CPMK-02, and CPMK-03,
     * this stores the score for each individual CPMK.
     */
    public function up(): void
    {
        Schema::create('student_assessment_cpmk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'cpmk_id', 'mahasiswa_id'], 'stud_asmt_cpmk_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessment_cpmk_scores');
    }
};
