<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ObeCalculationService;
use App\Services\ObeExcelExportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private ObeCalculationService $obe,
        private ObeExcelExportService $excelExport
    ) {}

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
     * Export Rekap Nilai & CPMK ke Excel (.xlsx).
     */
    public function rekapKeseluruhan(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        return $this->excelExport->exportKeseluruhanExcel($section);
    }

    /**
     * Export Rekap CPMK ke Excel (.xlsx) dengan kop dokumen resmi & rincian nilai per komponen asesmen.
     */
    public function rekapCpmk(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpmkId = request('cpmk_id') ? (int) request('cpmk_id') : null;

        return $this->excelExport->exportCpmkExcel($section, $cpmkId);
    }

    /**
     * Export Rekap CPL ke Excel (.xlsx).
     */
    public function rekapCpl(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        return $this->excelExport->exportCplExcel($section);
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

        $prodi = $section->mataKuliah?->prodi;
        $dosenKaprodi = $prodi ? User::where('prodi_id', $prodi->id)
            ->whereHas('role', fn ($q) => $q->where('name', Role::DOSEN))
            ->first() : null;

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
            'dosenKaprodi' => $dosenKaprodi,
        ]);
    }

    /**
     * Export Nilai per Assessment ke Excel (.xlsx).
     */
    public function rekapNilaiAssessment(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        return $this->excelExport->exportNilaiAssessmentExcel($section);
    }

    /**
     * Export Rekap Nilai Akhir & CPMK khusus Excel (.xlsx) dengan Kop Surat & Format Berwarna.
     */
    public function rekapKeseluruhanExcel(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        return $this->excelExport->exportKeseluruhanExcel($section);
    }

    /**
     * Export Rekap CPMK khusus Excel (.xlsx) persis Rekap_OBE_CPMK101 dengan Kop Surat & Format Berwarna.
     */
    public function rekapCpmkExcel(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);
        $cpmkId = request('cpmk_id') ? (int) request('cpmk_id') : null;

        return $this->excelExport->exportCpmkExcel($section, $cpmkId);
    }

    /**
     * Export Rekap CPL khusus Excel (.xlsx) dengan Kop Surat & Format Berwarna.
     */
    public function rekapCplExcel(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        return $this->excelExport->exportCplExcel($section);
    }

    /**
     * Export Nilai per Asesmen khusus Excel (.xlsx) dengan Kop Surat & Format Berwarna.
     */
    public function rekapNilaiAssessmentExcel(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        return $this->excelExport->exportNilaiAssessmentExcel($section);
    }

    // ── Helpers ──

    private function cpmksFor(ClassSection $section)
    {
        return Cpmk::forMataKuliah($section->mata_kuliah_id)->orderBy('code')->get();
    }

    private function cplsFor(ClassSection $section)
    {
        return $section->mataKuliah?->contextualCpls() ?? collect();
    }

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount(['assessments' => fn ($q) => $q->whereNotIn('type', ['materi', 'pengumuman', 'lainnya'])])->load(['mataKuliah', 'semester', 'dosen']);

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
        Gate::authorize('manage', $section);
    }

    private function sanitizeCsv(mixed $value): string
    {
        $str = (string) $value;
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$str;
        }

        return $str;
    }

    /**
     * Tulis kop informasi dokumen resmi pada awal file CSV.
     */
    private function writeDocumentHeader($handle, ClassSection $section, string $title, int $studentCount, array $extraMeta = []): void
    {
        $section->loadMissing(['mataKuliah.prodi', 'semester', 'dosen']);
        $mk = $section->mataKuliah;
        $mkLabel = $mk ? ($mk->code.' - '.$mk->name.($mk->sks ? " ({$mk->sks} SKS)" : '')) : '-';
        $classCode = $section->section_code ?: ($section->name ?: 'A');
        $semesterName = $section->semester?->name ?? 'Semester Aktif';
        $dosenName = $section->dosen?->name ?? '-';
        if ($section->dosen?->nim_nidn) {
            $dosenName .= ' (NIP/NIDN: '.$section->dosen->nim_nidn.')';
        }

        fputcsv($handle, [mb_strtoupper($title, 'UTF-8')], ';');
        $appN = SystemSetting::appName();
        $instN = SystemSetting::valueFor('institution', '');
        $csvHeader = $instN ? mb_strtoupper($instN, 'UTF-8').' - '.mb_strtoupper($appN, 'UTF-8') : mb_strtoupper($appN, 'UTF-8');
        fputcsv($handle, [$csvHeader], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ['Mata Kuliah', $mkLabel], ';');
        if ($mk?->prodi?->name) {
            fputcsv($handle, ['Program Studi', $mk->prodi->name], ';');
        }
        fputcsv($handle, ['Kelas / Sesi', $classCode], ';');
        fputcsv($handle, ['Semester', $semesterName], ';');
        fputcsv($handle, ['Dosen Pengampu', $dosenName], ';');
        fputcsv($handle, ['Jumlah Mahasiswa', $studentCount.' Orang'], ';');
        foreach ($extraMeta as $label => $val) {
            fputcsv($handle, [$label, $val], ';');
        }
        fputcsv($handle, ['Tanggal Ekspor', now()->locale('id')->translatedFormat('d F Y, H:i').' WIB'], ';');
        fputcsv($handle, [], ';');
    }
}
