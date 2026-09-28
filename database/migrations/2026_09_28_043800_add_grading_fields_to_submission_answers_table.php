<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            $table->decimal('earned_score', 8, 2)->nullable()->after('matching');
            $table->decimal('max_score', 8, 2)->nullable()->after('earned_score');
            $table->string('grading_status', 30)->default('pending')->after('max_score');
            $table->foreignId('graded_by_id')->nullable()->after('grading_status')->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by_id');
            $table->dropColumn(['earned_score', 'max_score', 'grading_status', 'graded_at']);
        });
    }
};
