<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt')->default(1);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 50)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->text('answer')->nullable();
            $table->string('link', 2000)->nullable();
            $table->json('question_answers')->nullable();
            $table->json('file_ids')->nullable();
            $table->string('student_number', 50)->nullable();
            $table->timestamps();

            $table->index(['assessment_id', 'user_id']);
            $table->index(['assessment_id', 'mahasiswa_id']);
        });

        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->integer('question_index')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->text('answer_text')->nullable();
            $table->string('link', 2000)->nullable();
            $table->json('choices')->nullable();
            $table->string('boolean_choice', 20)->nullable();
            $table->json('matching')->nullable();
            $table->timestamps();

            $table->index(['submission_id', 'version']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained('assessments')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            $table->string('path', 500);
            $table->string('name', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['uuid']);
            $table->index(['class_section_id']);
            $table->index(['assessment_id']);
            $table->index(['submission_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('submission_answers');
        Schema::dropIfExists('submissions');
    }
};
