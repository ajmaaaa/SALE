<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use Illuminate\Database\Seeder;

/**
 * Seeds an example OBE dataset matching the scenario in the project
 * requirement: 3 CPL, 5 CPMK, and a handful of assessments — including
 * assessments that measure more than one CPMK (Tugas 1, PBL) — so the
 * matrix/rekap/calculation pages have real, verifiable data to work
 * against instead of an empty state.
 *
 * Applied to IF204 (Struktur Data dan Algoritma), the mata kuliah backing
 * class sections IF204-A and IF204-B seeded by AcademicDemoSeeder.
 */
class ObeExampleSeeder extends Seeder
{
    public function run(): void
    {
        $mataKuliah = MataKuliah::where('code', 'IF204')->first();

        if (! $mataKuliah) {
            $this->command?->warn('Mata kuliah IF204 belum ada. Jalankan AcademicDemoSeeder terlebih dahulu.');

            return;
        }

        $prodi = $mataKuliah->prodi;

        // --- CPL (level prodi) ---
        $cplData = [
            ['code' => 'CPL-01', 'description' => 'Mampu menganalisis masalah dan menyusun solusi secara sistematis.'],
            ['code' => 'CPL-02', 'description' => 'Mampu menerapkan pengetahuan komputasi dalam praktik.'],
            ['code' => 'CPL-03', 'description' => 'Mampu bekerja secara kolaboratif dan mengomunikasikan hasil kerja teknis.'],
        ];

        $cpls = collect($cplData)->mapWithKeys(function ($data) use ($prodi) {
            $cpl = Cpl::updateOrCreate(
                ['prodi_id' => $prodi->id, 'code' => $data['code']],
                ['description' => $data['description']]
            );

            return [$data['code'] => $cpl];
        });

        // --- CPMK (level mata kuliah) ---
        $cpmkData = [
            ['code' => 'CPMK-01', 'description' => 'Menganalisis kompleksitas algoritma dan struktur data dasar.'],
            ['code' => 'CPMK-02', 'description' => 'Menerapkan struktur data linear dan non-linear untuk menyelesaikan masalah.'],
            ['code' => 'CPMK-03', 'description' => 'Merancang solusi algoritmik untuk permasalahan terapan (proyek/PBL).'],
            ['code' => 'CPMK-04', 'description' => 'Mengevaluasi dan membandingkan efisiensi beberapa pendekatan algoritma.'],
            ['code' => 'CPMK-05', 'description' => 'Mengomunikasikan hasil analisis algoritma secara tertulis dan lisan.'],
        ];

        $cpmks = collect($cpmkData)->mapWithKeys(function ($data) use ($mataKuliah) {
            $cpmk = Cpmk::updateOrCreate(
                ['mata_kuliah_id' => $mataKuliah->id, 'code' => $data['code']],
                ['description' => $data['description']]
            );

            return [$data['code'] => $cpmk];
        });

        // --- CPL <-> CPMK mapping (many-to-many, weight = CPMK's
        // contribution to that CPL, normalized to 100% per CPL) ---
        $cplCpmkMap = [
            'CPL-01' => ['CPMK-01' => 60, 'CPMK-04' => 40],
            'CPL-02' => ['CPMK-02' => 50, 'CPMK-03' => 50],
            'CPL-03' => ['CPMK-03' => 40, 'CPMK-05' => 60],
        ];

        foreach ($cplCpmkMap as $cplCode => $contributions) {
            foreach ($contributions as $cpmkCode => $weight) {
                $cpls[$cplCode]->cpmks()->syncWithoutDetaching([
                    $cpmks[$cpmkCode]->id => ['weight' => $weight],
                ]);
            }
        }

        // --- Assessments, seeded per class section so each section can
        // have its own instruments (a parallel class could differ). ---
        $sections = ClassSection::where('mata_kuliah_id', $mataKuliah->id)->get();

        $assessmentData = [
            ['code' => 'TGS-01', 'name' => 'Tugas 1', 'type' => 'tugas', 'final_weight' => 10, 'cpmk' => ['CPMK-01' => 60, 'CPMK-02' => 40]],
            ['code' => 'TGS-02', 'name' => 'Tugas 2', 'type' => 'tugas', 'final_weight' => 10, 'cpmk' => ['CPMK-02' => 100]],
            ['code' => 'KUIS-01', 'name' => 'Kuis 1', 'type' => 'kuis', 'final_weight' => 10, 'cpmk' => ['CPMK-01' => 100]],
            ['code' => 'PBL-01', 'name' => 'PBL 1', 'type' => 'pbl', 'final_weight' => 20, 'cpmk' => ['CPMK-02' => 30, 'CPMK-03' => 70]],
            ['code' => 'UTS', 'name' => 'UTS', 'type' => 'uts', 'final_weight' => 20, 'cpmk' => ['CPMK-01' => 50, 'CPMK-04' => 50]],
            ['code' => 'UAS', 'name' => 'UAS', 'type' => 'uas', 'final_weight' => 30, 'cpmk' => ['CPMK-03' => 30, 'CPMK-04' => 30, 'CPMK-05' => 40]],
        ];

        foreach ($sections as $section) {
            foreach ($assessmentData as $data) {
                $assessment = Assessment::updateOrCreate(
                    ['class_section_id' => $section->id, 'code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'type' => $data['type'],
                        'final_weight' => $data['final_weight'],
                        'status' => 'published',
                    ]
                );

                foreach ($data['cpmk'] as $cpmkCode => $weight) {
                    $assessment->cpmks()->syncWithoutDetaching([
                        $cpmks[$cpmkCode]->id => ['weight' => $weight],
                    ]);
                }
            }
        }

        $this->command?->info('Data contoh OBE dibuat: 3 CPL, 5 CPMK, 6 asesmen per kelas IF204.');
    }
}
