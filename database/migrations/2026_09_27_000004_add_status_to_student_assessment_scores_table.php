<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_assessment_scores', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('score')->index();
            $table->timestamp('published_at')->nullable()->after('graded_at');
        });

        DB::table('student_assessment_scores')
            ->whereNotNull('score')
            ->whereNotNull('graded_at')
            ->update([
                'status' => 'published',
                'published_at' => DB::raw('graded_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('student_assessment_scores', function (Blueprint $table) {
            $table->dropColumn(['status', 'published_at']);
        });
    }
};
