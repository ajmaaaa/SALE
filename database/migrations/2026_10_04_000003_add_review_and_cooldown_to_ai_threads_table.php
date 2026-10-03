<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_threads', function (Blueprint $table) {
            $table->boolean('flagged_for_review')->default(false)->after('cleared_at');
            $table->timestamp('last_blocked_at')->nullable()->after('flagged_for_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_threads', function (Blueprint $table) {
            $table->dropColumn(['flagged_for_review', 'last_blocked_at']);
        });
    }
};
