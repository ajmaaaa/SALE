<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `type` is a free-form string (not a DB enum) so lecturers can
     * introduce new assessment types without a schema migration.
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->string('code'); // 'TGS-01'
            $table->string('name'); // 'Tugas 1'
            $table->string('type'); // tugas, kuis, pbl, uts, uas, proyek, partisipasi, ...
            $table->text('description')->nullable();
            $table->decimal('final_weight', 5, 2); // contribution to final course grade (%)
            $table->boolean('uses_rubric')->default(false);
            $table->string('status')->default('draft'); // draft, published, closed
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->unique(['class_section_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
