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
        Schema::table('rubric_criteria', function (Blueprint $table) {
            $table->unsignedSmallInteger('order')->default(0)->after('max_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rubric_criteria', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
