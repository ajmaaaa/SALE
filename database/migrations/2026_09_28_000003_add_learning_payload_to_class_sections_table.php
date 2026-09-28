<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('class_sections', 'learning_payload')) {
            Schema::table('class_sections', function (Blueprint $table) {
                $table->json('learning_payload')->nullable()->after('enrollment_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_sections', 'learning_payload')) {
            Schema::table('class_sections', function (Blueprint $table) {
                $table->dropColumn('learning_payload');
            });
        }
    }
};
