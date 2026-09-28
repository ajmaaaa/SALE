<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessments') && ! Schema::hasColumn('assessments', 'published_at')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->timestamp('published_at')->nullable()->after('due_at');
            });

            DB::table('assessments')
                ->where('status', 'published')
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'published_at')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropColumn('published_at');
            });
        }
    }
};
