<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Role;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private ObeCalculationService $obe,
        private \App\Services\ObeExcelExportService $excelExport
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
     * Export Rekap Nilai & CPMK ke Excel (.xlsx) atau CSV.
     */
    public function rekapKeseluruhan(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        if (request('format') === 'xlsx' || request('format') === 'excel' || request()->boolean('excel')) {
            return $this->excelExport->exportKeseluruhanExcel($section);
        }

        $cpmks = $this->cpmksFor($section);
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();

        $sectionSuffix = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'rekap-nilai-'.$section->mataKuliah->code.'-'.$sectionSuffix.'.csv';

        return response()->streamDownload(function () use ($students, $cpmks, $cpmkWeights, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // ── Kop Informasi Dokumen ──
            $this->writeDocumentHeader($handle, $section, 'Rekapitulasi Nilai Akhir & Capaian CPMK', $students->count());

            // Header row
            $header = ['No', 'NIM', 'Nama Mahasiswa'];
            foreach ($cpmks as $cpmk) {
                $w = $cpmkWeights[$cpmk->id] ?? 0;
                $header[] = "{$cpmk->code} (".rtrim(rtrim(number_format($w, 1), '0'), '.').'%)';
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

                $row = [$i + 1, $this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];
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
     * Export Rekap CPMK ke Excel (.xlsx) atau CSV (dengan kop dokumen resmi & rincian nilai per komponen asesmen).
     */
    public function rekapCpmk(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        if (request('format') === 'xlsx' || request('format') === 'excel' || request()->boolean('excel')) {
            $cpmkId = request('cpmk_id') ? (int) request('cpmk_id') : null;
            return $this->excelExport->exportCpmkExcel($section, $cpmkId);
        }

        $cpmks = $this->cpmksFor($section);
        $students = $section->students()->orderBy('name')->get();
        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();
        $assessmentIds = $assessments->pluck('id');

        $rawAssessmentScores = \App\Models\StudentAssessmentScore::whereIn('assessment_id', $assessmentIds)
            ->get()
            ->groupBy(fn ($r) => $r->assessment_id.'_'.$r->mahasiswa_id);

        $rawCpmkScores = \App\Models\StudentAssessmentCpmkScore::whereIn('assessment_id', $assessmentIds)
            ->get()
            ->groupBy(fn ($r) => $r->assessment_id.'_'.$r->cpmk_id.'_'.$r->mahasiswa_id);

        $columns = [];
        foreach ($assessments as $asmt) {
            $cpmkCols = [];
            $totalPivot = (float) $asmt->cpmks->sum(fn ($c) => (float) ($c->pivot?->weight ?: 0));
            if ($totalPivot <= 0) {
                $totalPivot = (float) max(1, $asmt->cpmks->count());
            }

            foreach ($cpmks as $cpmk) {
                $pivot = $asmt->cpmks->firstWhere('id', $cpmk->id);
                if ($pivot && (float) ($pivot->pivot?->weight ?: 0) > 0) {
                    $pivotWeight = (float) $pivot->pivot->weight;
                    $weightWithinAsmt = round(($pivotWeight / $totalPivot) * 100, 1);
                    $cpmkCols[] = [
                        'cpmk' => $cpmk,
                        'weight' => $weightWithinAsmt,
                        'weight_fmt' => rtrim(rtrim(number_format($weightWithinAsmt, 1), '0'), '.'),
                    ];
                }
            }
            if (count($cpmkCols) > 0) {
                $columns[] = [
                    'assessment' => $asmt,
                    'cpmk_cols' => $cpmkCols,
                ];
            }
        }

        $sectionSuffix = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'rekap-cpmk-'.$section->mataKuliah->code.'-'.$sectionSuffix.'.csv';

        return response()->streamDownload(function () use (
            $students,
            $cpmks,
            $columns,
            $rawAssessmentScores,
            $rawCpmkScores,
            $section
        ) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // ── Kop Informasi Dokumen ──
            $this->writeDocumentHeader($handle, $section, 'Rekapitulasi Penilaian Capaian CPMK Mahasiswa', $students->count());

            if (! empty($columns)) {
                $header = ['No', 'NIM', 'Nama Mahasiswa'];
                foreach ($columns as $col) {
                    foreach ($col['cpmk_cols'] as $cc) {
                        $header[] = "{$col['assessment']->name} - {$cc['cpmk']->code} ({$cc['weight_fmt']}%)";
                    }
                    $header[] = "{$col['assessment']->name} - Total (100)";
                }
                fputcsv($handle, $header, ';');

                foreach ($students as $i => $student) {
                    $row = [$i + 1, $this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];

                    foreach ($columns as $col) {
                        $asmtId = $col['assessment']->id;
                        $asmtScoreKey = $asmtId.'_'.$student->id;
                        $asmtRow = $rawAssessmentScores->get($asmtScoreKey)?->first();

                        $asmtHasPending = false;
                        $allSubCellsGraded = true;
                        $sumCellScores = 0.0;
                        $hasAnyCellScore = false;

                        foreach ($col['cpmk_cols'] as $cc) {
                            $cpmk = $cc['cpmk'];
                            $cpmkKey = $asmtId.'_'.$cpmk->id.'_'.$student->id;
                            $cpmkSpecific = $rawCpmkScores->get($cpmkKey)?->first();
                            $maxScore = (float) $cc['weight'];

                            if ($cpmkSpecific !== null && $cpmkSpecific->score !== null) {
                                $rawVal = (float) $cpmkSpecific->score;
                                if (count($col['cpmk_cols']) > 1 && $rawVal > ($maxScore + 0.01) && $maxScore > 0) {
                                    $cellScore = round(($rawVal * $maxScore) / 100, 1);
                                } else {
                                    $cellScore = round($rawVal, 1);
                                }
                                $row[] = number_format($cellScore, 2);
                                $sumCellScores += $cellScore;
                                $hasAnyCellScore = true;
                            } elseif ($asmtRow === null) {
                                $row[] = '';
                                $allSubCellsGraded = false;
                            } elseif ($asmtRow->score !== null) {
                                $cellScore = round(((float) $asmtRow->score * $maxScore) / 100, 1);
                                $row[] = number_format($cellScore, 2);
                                $sumCellScores += $cellScore;
                                $hasAnyCellScore = true;
                            } else {
                                $row[] = '';
                                $asmtHasPending = true;
                                $allSubCellsGraded = false;
                            }
                        }

                        // Total Asesmen (Skala 100)
                        if ($asmtHasPending || ($asmtRow !== null && $asmtRow->score === null)) {
                            $row[] = '';
                        } elseif ($asmtRow !== null && $asmtRow->score !== null) {
                            $row[] = number_format((float) $asmtRow->score, 2);
                        } elseif ($allSubCellsGraded && $hasAnyCellScore) {
                            $row[] = number_format($sumCellScores, 2);
                        } else {
                            $row[] = '';
                        }
                    }

                    fputcsv($handle, $row, ';');
                }
            } else {
                $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
                $header = ['No', 'NIM', 'Nama Mahasiswa'];
                foreach ($cpmks as $cpmk) {
                    $w = $cpmkWeights[$cpmk->id] ?? 0;
                    $header[] = "{$cpmk->code} (".rtrim(rtrim(number_format($w, 1), '0'), '.').'%)';
                }
                fputcsv($handle, $header, ';');

                foreach ($students as $i => $student) {
                    $scores = $this->obe->cpmkScoresFor($cpmks, $student->id, $section->id);
                    $row = [$i + 1, $this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];
                    foreach ($cpmks as $cpmk) {
                        $row[] = $scores[$cpmk->id] !== null ? number_format($scores[$cpmk->id], 2) : '';
                    }
                    fputcsv($handle, $row, ';');
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export Rekap CPL ke Excel (.xlsx) atau CSV.
     */
    public function rekapCpl(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        if (request('format') === 'xlsx' || request('format') === 'excel' || request()->boolean('excel')) {
            return $this->excelExport->exportCplExcel($section);
        }

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();

        $sectionSuffix = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'rekap-cpl-'.$section->mataKuliah->code.'-'.$sectionSuffix.'.csv';

        return response()->streamDownload(function () use ($students, $cpls, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // ── Kop Informasi Dokumen ──
            $this->writeDocumentHeader($handle, $section, 'Rekapitulasi Capaian Pembelajaran Lulusan (CPL)', $students->count());

            $header = ['No', 'NIM', 'Nama Mahasiswa'];
            foreach ($cpls as $cpl) {
                $header[] = $cpl->code;
                $header[] = 'Status '.$cpl->code;
            }
            fputcsv($handle, $header, ';');

            foreach ($students as $i => $student) {
                $scores = $this->obe->cplScoresFor($cpls, $student->id, $section->id);

                $row = [$i + 1, $this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];
                foreach ($cpls as $cpl) {
                    $score = $scores[$cpl->id] ?? null;
                    $row[] = $score !== null ? number_format($score, 2) : '';
                    $row[] = $score !== null ? ($score >= (float) ($cpl->target_score ?? 65) ? 'Tercapai' : 'Belum Tercapai') : 'Belum Dinilai';
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
     * Export Nilai per Assessment ke Excel (.xlsx) atau CSV.
     */
    public function rekapNilaiAssessment(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        if (request('format') === 'xlsx' || request('format') === 'excel' || request()->boolean('excel')) {
            return $this->excelExport->exportNilaiAssessmentExcel($section);
        }

        $assessments = $section->assessments()->orderBy('code')->get();
        $students = $section->students()->orderBy('name')->get();

        $filename = 'rekap_nilai_asesmen_'.$section->mataKuliah->code.'_'.$section->section_code.'.csv';

        return response()->streamDownload(function () use ($students, $assessments, $section) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // ── Kop Informasi Dokumen ──
            $this->writeDocumentHeader($handle, $section, 'Rekapitulasi Nilai per Komponen Asesmen', $students->count());

            $header = ['No', 'NIM', 'Nama Mahasiswa'];
            foreach ($assessments as $assessment) {
                $header[] = $assessment->code.' ('.$assessment->final_weight.'%)';
            }
            fputcsv($handle, $header, ';');

            foreach ($students as $i => $student) {
                $row = [$i + 1, $this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];
                foreach ($assessments as $assessment) {
                    $score = $this->obe->assessmentScore($assessment->id, $student->id);
                    $row[] = $score !== null ? number_format($score, 2) : '';
                }
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
            $currentUserId = $user?->hasRole(Role::DOSEN) ? $user->id : null;
        }

        abort_unless(
            $currentUserId && in_array($currentUserId, [$section->dosen_id, $section->dosen_pendamping_id], true),
            403,
            'Anda tidak memiliki akses ke kelas ini.'
        );
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
        $mkLabel = $mk ? ($mk->code.' - '.$mk->name.($mk->sks ? " ({$mk->sks} SKS)" : '')) : '—';
        $classCode = $section->section_code ?: ($section->name ?: 'A');
        $semesterName = $section->semester?->name ?? 'Semester Aktif';
        $dosenName = $section->dosen?->name ?? '—';
        if ($section->dosen?->nim_nidn) {
            $dosenName .= ' (NIP/NIDN: '.$section->dosen->nim_nidn.')';
        }

        fputcsv($handle, [mb_strtoupper($title, 'UTF-8')], ';');
        fputcsv($handle, ['SISTEM INFORMASI AKADEMIK & OBE (SALE)'], ';');
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
