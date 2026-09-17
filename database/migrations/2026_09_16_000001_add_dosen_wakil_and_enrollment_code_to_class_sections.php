<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->foreignId('dosen_pendamping_id')->nullable()->after('dosen_id')->constrained('users')->nullOnDelete();
            $table->string('enrollment_code', 32)->nullable()->unique()->after('capacity');
        });

        // Populate existing class sections with a unique enrollment code
        $sections = DB::table('class_sections')->get();
        foreach ($sections as $section) {
            DB::table('class_sections')
                ->where('id', $section->id)
                ->update([
                    'enrollment_code' => strtoupper(Str::random(8)),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->dropForeign(['dosen_pendamping_id']);
            $table->dropColumn(['dosen_pendamping_id', 'enrollment_code']);
        });
    }
};
