<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KaprodiMonitoringController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Dashboard Monitoring Capaian CPMK Tingkat Prodi (Step 18 & Step 20).
     */
    public function cpmk(Request $request): View
    {
        $this->authorizeKaprodi();

        $sections = ClassSection::with(['mataKuliah', 'semester', 'dosen'])
            ->whereHas('semester', fn ($q) => $q->where('is_active', true))
            ->get();

        $selectedSectionId = $request->integer('section_id', $sections->first()?->id ?? 0);
        $activeSection = $sections->firstWhere('id', $selectedSectionId);

        $cpmkStats = collect();
        if ($activeSection) {
            $cpmks = Cpmk::where('mata_kuliah_id', $activeSection->mata_kuliah_id)->get();
            $students = $activeSection->students;

            foreach ($cpmks as $cpmk) {
                $scores = $students->map(fn ($s) => $this->obe->cpmkScore($cpmk, $s->id))->filter(fn ($s) => $s !== null);
                $gradedCount = $scores->count();
                $achievedCount = $scores->filter(fn ($s) => $s >= (float)$cpmk->threshold)->count();
                $avgScore = $gradedCount > 0 ? round($scores->average(), 1) : null;
                $percentAchieved = $gradedCount > 0 ? round(($achievedCount / $gradedCount) * 100, 1) : 0;

                $cpmkStats->push([
                    'cpmk' => $cpmk,
                    'graded_count' => $gradedCount,
                    'total_students' => $students->count(),
                    'achieved_count' => $achievedCount,
                    'average_score' => $avgScore,
                    'percent_achieved' => $percentAchieved,
                ]);
            }
        }

        return view('kaprodi.monitoring-cpmk', [
            'sections' => $sections,
            'activeSection' => $activeSection,
            'cpmkStats' => $cpmkStats,
        ]);
    }

    /**
     * Dashboard Monitoring Capaian CPL Tingkat Prodi (Step 19 & Step 20).
     */
    public function cpl(Request $request): View
    {
        $this->authorizeKaprodi();

        $cpls = Cpl::with('cpmks')->orderBy('code')->get();
        $sections = ClassSection::with(['mataKuliah', 'dosen'])->get();

        $cplStats = $cpls->map(function ($cpl) use ($sections) {
            $allScores = collect();

            foreach ($sections as $section) {
                foreach ($section->students as $student) {
                    $score = $this->obe->cplScore($cpl, $student->id);
                    if ($score !== null) {
                        $allScores->push($score);
                    }
                }
            }

            $count = $allScores->count();
            $avg = $count > 0 ? round($allScores->average(), 1) : null;
            $achieved = $allScores->filter(fn ($s) => $s >= 65)->count();
            $percent = $count > 0 ? round(($achieved / $count) * 100, 1) : 0;

            return [
                'cpl' => $cpl,
                'count' => $count,
                'average' => $avg,
                'achieved_count' => $achieved,
                'percent_achieved' => $percent,
            ];
        });

        return view('kaprodi.monitoring-cpl', [
            'cplStats' => $cplStats,
            'sectionsCount' => $sections->count(),
        ]);
    }

    /**
     * Halaman Utama Pusat Export Rekap Nilai OBE (CPMK & CPL) Kaprodi.
     */
    public function exportIndex(Request $request): View
    {
        $this->authorizeKaprodi();

        $sections = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])
            ->whereHas('semester', fn ($q) => $q->where('is_active', true))
            ->orderBy('mata_kuliah_id')
            ->orderBy('section_code')
            ->get();

        $selectedSectionId = $request->integer('section_id', $sections->first()?->id ?? 0);
        $selectedSection = $sections->firstWhere('id', $selectedSectionId);

        $cpls = Cpl::orderBy('code')->get();

        return view('kaprodi.export', [
            'sections' => $sections,
            'selectedSection' => $selectedSection,
            'cpls' => $cpls,
        ]);
    }

    /**
     * Download CSV Rekapitulasi Nilai & Capaian CPMK (Per-Kelas / Mata Kuliah).
     */
    public function exportCpmk(Request $request): StreamedResponse
    {
        $this->authorizeKaprodi();

        $sectionId = $request->integer('section_id');
        $section = ClassSection::with(['mataKuliah', 'semester', 'dosen'])->findOrFail($sectionId);

        $cpmks = Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();

        $filename = 'rekap_cpmk_' . $section->mataKuliah->code . '_' . $section->section_code . '_' . date('Ymd') . '.csv';

        return response()->streamDownload(function () use ($students, $cpmks, $cpmkWeights, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM untuk Microsoft Excel

            // Header baris CSV
            $header = ['No', 'NIM', 'Nama Mahasiswa', 'Kelas', 'Mata Kuliah'];
            foreach ($cpmks as $cpmk) {
                $w = $cpmkWeights[$cpmk->id] ?? 0;
                $header[] = "{$cpmk->code} (" . rtrim(rtrim(number_format($w, 1), '0'), '.') . '%)';
            }
            $header = array_merge($header, ['Nilai Akhir', 'Grade', 'Predikat Mutu', 'Coverage (%)']);
            fputcsv($handle, $header, ';');

            // Data baris per mahasiswa
            foreach ($students as $i => $student) {
                $cpmkScores = $this->obe->cpmkScoresFor($cpmks, $student->id, $section->id);
                $final = $this->obe->finalScore($section, $student->id);
                $grade = $this->gradeLetter($final['score']);
                $predicate = $this->obe->predicate($final['score']);

                $row = [
                    $i + 1,
                    $student->nim_nidn ?? '',
                    $student->name,
                    $section->section_code,
                    $section->mataKuliah->name,
                ];

                foreach ($cpmks as $cpmk) {
                    $row[] = $cpmkScores[$cpmk->id] !== null ? number_format($cpmkScores[$cpmk->id], 2) : '';
                }

                $row[] = $final['score'] !== null ? number_format($final['score'], 2) : '';
                $row[] = $grade ?? '';
                $row[] = $predicate ?? '';
                $row[] = $final['coverage'] . '%';

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Download CSV Rekapitulasi Capaian CPL (Tingkat Program Studi / Per-Kelas).
     */
    public function exportCpl(Request $request): StreamedResponse
    {
        $this->authorizeKaprodi();

        $cpls = Cpl::orderBy('code')->get();
        $sectionId = $request->integer('section_id', 0);

        if ($sectionId > 0) {
            $section = ClassSection::with(['mataKuliah', 'dosen', 'semester'])->findOrFail($sectionId);
            $sections = collect([$section]);
            $filename = 'rekap_cpl_' . $section->mataKuliah->code . '_' . $section->section_code . '_' . date('Ymd') . '.csv';
        } else {
            $sections = ClassSection::with(['mataKuliah', 'dosen', 'semester'])
                ->whereHas('semester', fn ($q) => $q->where('is_active', true))
                ->get();
            $filename = 'rekap_cpl_prodi_seluruh_kelas_' . date('Ymd') . '.csv';
        }

        return response()->streamDownload(function () use ($sections, $cpls) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM untuk Microsoft Excel

            $header = ['No', 'NIM', 'Nama Mahasiswa', 'Kelas / Mata Kuliah'];
            foreach ($cpls as $cpl) {
                $header[] = $cpl->code;
            }
            $header[] = 'Rata-rata CPL';
            $header[] = 'Status Ketercapaian';
            fputcsv($handle, $header, ';');

            $rowNumber = 1;
            $processedKeys = [];

            foreach ($sections as $sec) {
                foreach ($sec->students as $student) {
                    $key = $sec->id . '_' . $student->id;
                    if (isset($processedKeys[$key])) {
                        continue;
                    }
                    $processedKeys[$key] = true;

                    $cplScores = $this->obe->cplScoresFor($cpls, $student->id);
                    $numericScores = collect($cplScores)->filter(fn ($s) => $s !== null);
                    $avgScore = $numericScores->isNotEmpty() ? round($numericScores->average(), 2) : null;
                    $status = $avgScore !== null ? ($avgScore >= 65 ? 'Tercapai (>=65)' : 'Belum Tercapai (<65)') : 'Belum Dinilai';

                    $row = [
                        $rowNumber++,
                        $student->nim_nidn ?? '',
                        $student->name,
                        $sec->mataKuliah->code . ' (' . $sec->section_code . ') - ' . $sec->mataKuliah->name,
                    ];

                    foreach ($cpls as $cpl) {
                        $row[] = $cplScores[$cpl->id] !== null ? number_format($cplScores[$cpl->id], 2) : '';
                    }

                    $row[] = $avgScore !== null ? number_format($avgScore, 2) : '';
                    $row[] = $status;

                    fputcsv($handle, $row, ';');
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function gradeLetter(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 85 => 'A',
            $score >= 80 => 'AB',
            $score >= 75 => 'B',
            $score >= 70 => 'BC',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }

    private function authorizeKaprodi(): void
    {
        $user = Auth::guard('web')->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $isKaprodi = ($user && $user->role && $user->role->name === Role::KAPRODI)
            || (is_array(session('auth_user')) && (session('auth_user')['role'] ?? '') === Role::KAPRODI);

        abort_unless($isKaprodi, 403, 'Akses khusus Kaprodi (Monitoring & Evaluasi OBE).');
    }
}
