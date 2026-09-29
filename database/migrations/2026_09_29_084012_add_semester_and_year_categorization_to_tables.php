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
        Schema::table('semesters', function (Blueprint $table) {
            $table->string('academic_year', 20)->nullable()->after('name');
            $table->unsignedTinyInteger('term')->nullable()->after('academic_year'); // 1 = Ganjil, 2 = Genap, 3 = Pendek
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester_paket')->nullable()->after('sks'); // 1 s/d 8
        });

        // Backfill existing semesters data
        $semesters = \Illuminate\Support\Facades\DB::table('semesters')->get();
        foreach ($semesters as $sem) {
            $term = null;
            $academicYear = null;

            if (preg_match('/ganjil/i', (string) $sem->name)) {
                $term = 1;
            } elseif (preg_match('/genap/i', (string) $sem->name)) {
                $term = 2;
            } elseif (preg_match('/pendek|antara/i', (string) $sem->name)) {
                $term = 3;
            } elseif (str_ends_with((string) $sem->code, '-1')) {
                $term = 1;
            } elseif (str_ends_with((string) $sem->code, '-2')) {
                $term = 2;
            }

            if (preg_match('/(\d{4}\/\d{4})/', (string) $sem->name, $matches)) {
                $academicYear = $matches[1];
            } elseif (preg_match('/^(\d{4})/', (string) $sem->code, $matches)) {
                $y = (int) $matches[1];
                $academicYear = $y . '/' . ($y + 1);
            }

            \Illuminate\Support\Facades\DB::table('semesters')
                ->where('id', $sem->id)
                ->update([
                    'term' => $term,
                    'academic_year' => $academicYear,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->dropColumn('semester_paket');
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'term']);
        });
    }
};
