<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_api_calls') && ! Schema::hasColumn('ai_api_calls', 'thread_id')) {
            Schema::table('ai_api_calls', function (Blueprint $table) {
                $table->foreignId('thread_id')->nullable()->after('task_id')->constrained('ai_threads')->nullOnDelete();
                $table->index(['thread_id', 'stage']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_api_calls') && Schema::hasColumn('ai_api_calls', 'thread_id')) {
            Schema::table('ai_api_calls', function (Blueprint $table) {
                $table->dropForeign(['thread_id']);
                $table->dropIndex(['thread_id', 'stage']);
                $table->dropColumn('thread_id');
            });
        }
    }
};
