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
            $table->timestamp('cleared_at')->nullable()->after('last_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_threads', function (Blueprint $table) {
            $table->dropColumn('cleared_at');
        });
    }
};
