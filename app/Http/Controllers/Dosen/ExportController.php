<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman export — pilih jenis rekap yang akan diexport.
     */
    public function index(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        return view('dosen.penilaian.export', [
            'section' => $this->withHeaderCounts($section),
        ]);
    }

    /**
     * Export Rekap Nilai & CPMK ke CSV (termasuk Bobot CPMK dan Nilai Akhir).
     */
    public function rekapKeseluruhan(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();

        $sectionSuffix = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'rekap-nilai-' . $section->mataKuliah->code . '-' . $sectionSuffix . '.csv';

        return response()->streamDownload(function () use ($students, $cpmks, $cpmkWeights, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            $header = ['No', 'NIM', 'Nama'];
            foreach ($cpmks as $cpmk) {
                $w = $cpmkWeights[$cpmk->id] ?? 0;
                $header[] = "{$cpmk->code} (" . rtrim(rtrim(number_format($w, 1), '0'), '.') . '%)';
            }
            $header = array_merge($header, ['Nilai Akhir', 'Grade', 'Predikat', 'Coverage (%)', 'Status']);
            fputcsv($handle, $header, ';');

            // Data rows
            foreach ($students as $i => $student) {
                $cpmkScores = $this->obe->cpmkScoresFor($cpmks, $student->id, $section->id);
                $final = $this->obe->finalScore($section, $student->id);
                $grade = $this->gradeLetter($final['score']);
                $predicate = $this->obe->predicate($final['score']);
                $status = $final['coverage'] < 100 ? 'Provisional' : ($final['score'] !== null ? 'Final' : 'Belum dinilai');

                $row = [$i + 1, $student->nim_nidn ?? '', $student->name];
                foreach ($cpmks as $cpmk) {
                    $row[] = $cpmkScores[$cpmk->id] !== null ? number_format($cpmkScores[$cpmk->id], 2) : '';
                }
                $row[] = $final['score'] !== null ? number_format($final['score'], 2) : '';
                $row[] = $grade ?? '';
                $row[] = $predicate ?? '';
                $row[] = $final['coverage'];
                $row[] = $status;
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export Rekap CPMK ke CSV (dengan bobot penilaian).
     */
    public function rekapCpmk(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();

        $sectionSuffix = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'rekap-cpmk-' . $section->mataKuliah->code . '-' . $sectionSuffix . '.csv';

        return response()->streamDownload(function () use ($students, $cpmks, $cpmkWeights, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $header = ['No', 'NIM', 'Nama'];
            foreach ($cpmks as $cpmk) {
                $w = $cpmkWeights[$cpmk->id] ?? 0;
                $header[] = "{$cpmk->code} (" . rtrim(rtrim(number_format($w, 1), '0'), '.') . '%)';
            }
            fputcsv($handle, $header, ';');

            foreach ($students as $i => $student) {
                $scores = $this->obe->cpmkScoresFor($cpmks, $student->id, $section->id);

                $row = [$i + 1, $student->nim_nidn ?? '', $student->name];
                foreach ($cpmks as $cpmk) {
                    $row[] = $scores[$cpmk->id] !== null ? number_format($scores[$cpmk->id], 2) : '';
                }
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export Rekap CPL ke CSV.
     */
    public function rekapCpl(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();

        $sectionSuffix = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'rekap-cpl-' . $section->mataKuliah->code . '-' . $sectionSuffix . '.csv';

        return response()->streamDownload(function () use ($students, $cpls, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $header = ['No', 'NIM', 'Nama'];
            foreach ($cpls as $cpl) {
                $header[] = $cpl->code;
                $header[] = 'Status ' . $cpl->code;
            }
            fputcsv($handle, $header, ';');

            foreach ($students as $i => $student) {
                $scores = $this->obe->cplScoresFor($cpls, $student->id, $section->id);

                $row = [$i + 1, $student->nim_nidn ?? '', $student->name];
                foreach ($cpls as $cpl) {
                    $score = $scores[$cpl->id] ?? null;
                    $row[] = $score !== null ? number_format($score, 2) : '';
                    $row[] = $score !== null ? ($score >= (float)($cpl->target_score ?? 65) ? 'Tercapai' : 'Belum Tercapai') : 'Belum Dinilai';
                }
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Cetak Laporan Rekap Nilai format printable.
     */
    public function printRekap(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $cpmks = $this->cpmksFor($section);
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();

        $rows = collect();
        foreach ($students as $student) {
            $cplScores = $this->obe->cplScoresFor($cpls, $student->id, $section->id);
            $cpmkScores = $this->obe->cpmkScoresFor($cpmks, $student->id, $section->id);
            $final = $this->obe->finalScore($section, $student->id);
            $rows->push([
                'student' => $student,
                'cpl_scores' => $cplScores,
                'cpmk_scores' => $cpmkScores,
                'final_score' => $final['score'],
                'grade' => $this->gradeLetter($final['score']),
                'predicate' => $this->obe->predicate($final['score']),
                'coverage' => $final['coverage'],
            ]);
        }

        $classAverage = $rows->pluck('final_score')->filter(fn ($s) => $s !== null)->average();

        return view('dosen.penilaian.cetak-rekap', [
            'section' => $section,
            'title' => 'Laporan Rekap Nilai',
            'cpls' => $cpls,
            'cpmks' => $cpmks,
            'cpmkWeights' => $cpmkWeights,
            'students' => $students,
            'rows' => $rows,
            'studentRows' => $rows,
            'classAverage' => $classAverage,
        ]);
    }

    /**
     * Export Nilai per Assessment ke CSV.
     */
    public function rekapNilaiAssessment(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $assessments = $section->assessments()->orderBy('code')->get();
        $students = $section->students()->orderBy('name')->get();

        $filename = 'rekap_nilai_asesmen_' . $section->mataKuliah->code . '_' . $section->section_code . '.csv';

        return response()->streamDownload(function () use ($students, $assessments) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $header = ['No', 'NIM', 'Nama'];
            foreach ($assessments as $assessment) {
                $header[] = $assessment->code . ' (' . $assessment->final_weight . '%)';
            }
            fputcsv($handle, $header, ';');

            foreach ($students as $i => $student) {
                $row = [$i + 1, $student->nim_nidn ?? '', $student->name];
                foreach ($assessments as $assessment) {
                    $score = $this->obe->assessmentScore($assessment->id, $student->id);
                    $row[] = $score !== null ? number_format($score, 2) : '';
                }
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Helpers ──

    private function cpmksFor(ClassSection $section)
    {
        return Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
    }

    private function cplsFor(ClassSection $section)
    {
        $cpmkIds = $this->cpmksFor($section)->pluck('id');

        return Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))
            ->with(['cpmks' => fn ($q) => $q->whereIn('cpmks.id', $cpmkIds)])
            ->orderBy('code')
            ->get();
    }

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);

        return $section;
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

    private function authorizeOwnership(ClassSection $section): void
    {
        $currentUserId = Auth::guard('web')->id();

        if (! $currentUserId && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
            $currentUserId = $user?->hasRole(\App\Models\Role::DOSEN) ? $user->id : null;
        }

        abort_unless($currentUserId && $section->dosen_id === $currentUserId, 403, 'Anda tidak memiliki akses ke kelas ini.');
    }
}
