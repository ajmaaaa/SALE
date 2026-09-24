<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->foreignId('dosen_id')->nullable()->change();
        });

        $demoSections = DB::table('class_sections')
            ->join('mata_kuliahs', 'mata_kuliahs.id', '=', 'class_sections.mata_kuliah_id')
            ->join('semesters', 'semesters.id', '=', 'class_sections.semester_id')
            ->whereIn('mata_kuliahs.code', ['IF204', 'IF230'])
            ->where('semesters.code', '2026-1')
            ->pluck('class_sections.id');

        $demoUsers = DB::table('users')
            ->whereIn('email', [
                'ahmad.maulana@student.test',
                'siti.nurhaliza@student.test',
                'rizky.pratama@student.test',
                'dewi.anggraini@student.test',
                'fajar.ramadhan@student.test',
            ])
            ->pluck('id');

        if ($demoSections->isNotEmpty() && $demoUsers->isNotEmpty()) {
            DB::table('class_section_student')
                ->whereIn('class_section_id', $demoSections)
                ->whereIn('mahasiswa_id', $demoUsers)
                ->delete();
        }

        $demoLecturerId = DB::table('users')->where('email', 'budi@example.test')->value('id');
        if ($demoLecturerId && $demoSections->isNotEmpty()) {
            DB::table('class_sections')
                ->whereIn('id', $demoSections)
                ->where('dosen_id', $demoLecturerId)
                ->update(['dosen_id' => null]);

            DB::table('class_sections')
                ->whereIn('id', $demoSections)
                ->where('dosen_pendamping_id', $demoLecturerId)
                ->update(['dosen_pendamping_id' => null]);
        }
    }

    public function down(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->foreignId('dosen_id')->nullable(false)->change();
        });
    }
};
