<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('class_section_dosen_anggota')) {
            Schema::create('class_section_dosen_anggota', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
                $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['class_section_id', 'dosen_id']);
            });
        }

        // Backfill existing dosen_pendamping_id into class_section_dosen_anggota
        if (Schema::hasTable('class_sections') && Schema::hasColumn('class_sections', 'dosen_pendamping_id')) {
            $existing = DB::table('class_sections')
                ->whereNotNull('dosen_pendamping_id')
                ->get(['id', 'dosen_pendamping_id', 'created_at', 'updated_at']);

            foreach ($existing as $section) {
                DB::table('class_section_dosen_anggota')->insertOrIgnore([
                    'class_section_id' => $section->id,
                    'dosen_id' => $section->dosen_pendamping_id,
                    'created_at' => $section->created_at ?? now(),
                    'updated_at' => $section->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_section_dosen_anggota');
    }
};
