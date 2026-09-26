<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ObeExcelExportService
{
    // ── Palet Warna Standar OBE, File Referensi Rekap_OBE_CPMK101 & Desain Eksekutif ──
    private const COLOR_BANNER_GREEN     = 'FF92D050'; // Fresh Olive-Green banner (#92D050 - identik file contoh)
    private const COLOR_ACCENT_YELLOW    = 'FFFFFF00'; // Canary Yellow strip (#FFFF00 - garis aksen kuning)
    private const COLOR_HIGHLIGHT_YELLOW = 'FFFFFF00'; // Canary Yellow highlight (#FFFF00 - identik file contoh)
    private const COLOR_BANNER_ORANGE    = 'FFF79646'; // Warm Tangerine Orange (#F79646 - identik file contoh)
    private const COLOR_HEADER_BLACK     = 'FF000000'; // Pure Black text for official header title
    private const COLOR_DARK_CHARCOAL    = 'FF1E293B'; // Slate-800 for dark accents/KPI banner
    private const COLOR_TABLE_HEADER_BG  = 'FFF1F5F9'; // Slate-100 crisp table header
    private const COLOR_ZEBRA_BG         = 'FFF8FAFC'; // Slate-50 zebra stripe
    private const COLOR_CAPAIAN_BG       = 'FFFEF9C3'; // Soft Warm Pastel Yellow for Nilai Capaian highlight
    private const COLOR_CAPAIAN_TEXT     = 'FF713F12'; // Warm Dark Amber/Brown for Nilai Capaian text
    private const COLOR_PASS_BG          = 'FFDCFCE7'; // Soft Mint Green badge (Lulus)
    private const COLOR_PASS_TEXT        = 'FF15803D'; // Dark Emerald text (Lulus)
    private const COLOR_FAIL_BG          = 'FFFEE2E2'; // Soft Rose Red badge (Belum Lulus)
    private const COLOR_FAIL_TEXT        = 'FFB91C1C'; // Dark Crimson text (Belum Lulus)
    private const COLOR_GRADE_A_BG       = 'FFDCFCE7'; // Mint badge for Grade A
    private const COLOR_GRADE_A_TEXT     = 'FF15803D';
    private const COLOR_GRADE_B_BG       = 'FFFEF3C7'; // Soft Amber badge for Grade B/AB
    private const COLOR_GRADE_B_TEXT     = 'FFB45309';
    private const COLOR_GRADE_C_BG       = 'FFFFEDD5'; // Soft Orange badge for Grade C/BC
    private const COLOR_GRADE_C_TEXT     = 'FFC2410C';
    private const COLOR_INFO_BOX_BG      = 'FFF8FAFC'; // Slate-50 info card background
    private const COLOR_BORDER_GRAY      = 'FFCBD5E1'; // Slate-300 clean border
    private const COLOR_BORDER_DARK      = 'FF94A3B8'; // Slate-400 accent border

    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Export Rekap CPMK ke file Excel (.xlsx) dengan Kop Surat & Palet Warna Mewah.
     * Jika $cpmkId diberikan, ekspor sheet khusus untuk CPMK tersebut.
     * Jika tidak, buat Sheet Matriks Asesmen + Sheet per-CPMK (CPMK-01, CPMK-02, dst).
     */
    public function exportCpmkExcel(ClassSection $section, ?int $cpmkId = null): StreamedResponse
    {
        $section->loadMissing(['mataKuliah.prodi', 'semester', 'dosen']);
        $cpmks = Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();
        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        if ($cpmkId !== null) {
            $targetCpmk = $cpmks->firstWhere('id', $cpmkId) ?? $cpmks->first();
            if ($targetCpmk) {
                $sheet = $spreadsheet->createSheet();
                $sheet->getTabColor()->setARGB(self::COLOR_BANNER_GREEN);
                $this->buildCpmkSheet($sheet, $section, $targetCpmk, $students, $cpmkWeights);
                $filename = 'Rekap_OBE_' . $targetCpmk->code . '_' . date('Y-m-d') . '.xlsx';
            } else {
                $targetCpmk = $cpmks->first();
                $sheet = $spreadsheet->createSheet();
                $sheet->getTabColor()->setARGB(self::COLOR_BANNER_GREEN);
                $this->buildCpmkSheet($sheet, $section, $targetCpmk, $students, $cpmkWeights);
                $filename = 'Rekap_OBE_CPMK_' . ($section->mataKuliah?->code ?? 'MK') . '_' . date('Y-m-d') . '.xlsx';
            }
        } else {
            // Sheet 1: Matriks Asesmen & CPMK (Ringkasan Lengkap Kelas)
            $matrixSheet = $spreadsheet->createSheet();
            $matrixSheet->setTitle('Matriks Asesmen & CPMK');
            $matrixSheet->getTabColor()->setARGB(self::COLOR_DARK_CHARCOAL);
            $this->buildMatrixSheet($matrixSheet, $section, $cpmks, $assessments, $students);

            // Sheet 2+: Sheet masing-masing CPMK (persis desain Rekap_OBE_CPMK101)
            foreach ($cpmks as $cpmk) {
                $cpmkSheet = $spreadsheet->createSheet();
                $cpmkSheet->getTabColor()->setARGB(self::COLOR_BANNER_GREEN);
                $this->buildCpmkSheet($cpmkSheet, $section, $cpmk, $students, $cpmkWeights);
            }

            $spreadsheet->setActiveSheetIndex(0);
            $mkCode = $section->mataKuliah?->code ?? 'MK';
            $classCode = $section->section_code ?: ($section->name ?: 'A');
            $filename = 'Rekap_OBE_CPMK_' . $mkCode . '_' . $classCode . '_' . date('Y-m-d') . '.xlsx';
        }

        return $this->downloadSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Export Rekap Nilai Akhir & Seluruh CPMK ke Excel (.xlsx).
     */
    public function exportKeseluruhanExcel(ClassSection $section): StreamedResponse
    {
        $section->loadMissing(['mataKuliah.prodi', 'semester', 'dosen']);
        $cpmks = Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
        $cpmkWeights = $this->obe->cpmkWeightsFor($cpmks, $section);
        $students = $section->students()->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai & CPMK');
        $sheet->getTabColor()->setARGB('FF10B981'); // Emerald
        $sheet->setShowGridlines(true);

        $numCols = 3 + $cpmks->count() + 5;
        $lastColLetter = Coordinate::stringFromColumnIndex($numCols);

        // 1. Kop Surat Resmi
        $currRow = $this->applyKopSurat($sheet, $section, 'Rekapitulasi Nilai Akhir & Capaian CPMK Mahasiswa', $numCols);

        // 2. Banner Hijau: Pembobotan CPMK
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'PEMBOBOTAN CAPAIAN PEMBELAJARAN MATA KULIAH (CPMK)');
        $this->styleBannerGreen($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;
        $this->styleAccentStrip($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");

        $currRow++;
        // Header Bobot CPMK
        $sheet->setCellValue("A{$currRow}", 'Kode CPMK');
        $sheet->mergeCells("B{$currRow}:E{$currRow}");
        $sheet->setCellValue("B{$currRow}", 'Deskripsi Capaian Pembelajaran');
        $sheet->setCellValue("F{$currRow}", 'Ambang Minimum');
        $sheet->setCellValue("{$lastColLetter}{$currRow}", 'Bobot Akhir (%)');
        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(22);

        foreach ($cpmks as $idx => $cpmk) {
            $currRow++;
            $w = $cpmkWeights[$cpmk->id] ?? 0;
            $rowBg = ($idx % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $cpmk->code);
            $sheet->mergeCells("B{$currRow}:E{$currRow}");
            $sheet->setCellValue("B{$currRow}", $cpmk->description ?? $cpmk->name ?? '—');
            $sheet->setCellValue("F{$currRow}", '≥ ' . (int) ($cpmk->threshold ?: 60));
            $sheet->setCellValue("{$lastColLetter}{$currRow}", rtrim(rtrim(number_format($w, 1), '0'), '.') . '%');

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("F{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HIGHLIGHT_YELLOW);

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getStyle("A{$currRow}:F{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getRowDimension($currRow)->setRowHeight(20);
        }

        $currRow++;
        $sheet->getRowDimension($currRow)->setRowHeight(10); // Blank spacer
        $currRow++;

        // 3. Banner Oranye: Data Hasil Evaluasi Mahasiswa
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'DATA HASIL EVALUASI & CAPAIAN MAHASISWA');
        $this->styleBannerOrange($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;

        // Table Header
        $sheet->setCellValue("A{$currRow}", 'No');
        $sheet->setCellValue("B{$currRow}", 'NIM');
        $sheet->setCellValue("C{$currRow}", 'Nama Mahasiswa');

        $colIdx = 4;
        foreach ($cpmks as $cpmk) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $w = $cpmkWeights[$cpmk->id] ?? 0;
            $sheet->setCellValue("{$colLetter}{$currRow}", $cpmk->code . "\n(" . rtrim(rtrim(number_format($w, 1), '0'), '.') . '%)');
            $colIdx++;
        }

        $finalScoreCol = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$finalScoreCol}{$currRow}", "Nilai Akhir\n(0-100)");
        $colIdx++;

        $gradeCol = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$gradeCol}{$currRow}", 'Grade');
        $colIdx++;

        $predCol = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$predCol}{$currRow}", 'Predikat');
        $colIdx++;

        $covCol = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$covCol}{$currRow}", "Coverage\n(%)");
        $colIdx++;

        $statCol = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$statCol}{$currRow}", 'Status');

        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(32);

        // Data Rows
        $finalScores = [];
        $passCount = 0;
        $cpmkScoreSums = [];
        $cpmkScoreCounts = [];

        foreach ($students as $i => $student) {
            $currRow++;
            $cpmkScores = $this->obe->cpmkScoresFor($cpmks, $student->id, $section->id);
            $final = $this->obe->finalScore($section, $student->id);
            $grade = $this->gradeLetter($final['score']);
            $predicate = $this->obe->predicate($final['score']);
            $status = $final['coverage'] < 100 ? 'Provisional' : ($final['score'] !== null ? 'Final' : 'Belum Dinilai');

            if ($final['score'] !== null) {
                $finalScores[] = $final['score'];
                if ($final['score'] >= 55.0) {
                    $passCount++;
                }
            }

            $rowBg = ($i % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $i + 1);
            $sheet->setCellValueExplicit("B{$currRow}", (string) ($student->nim_nidn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $student->name);

            $colIdx = 4;
            foreach ($cpmks as $cpmk) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $sc = $cpmkScores[$cpmk->id] ?? null;
                $sheet->setCellValue("{$colLetter}{$currRow}", $sc !== null ? number_format($sc, 1) : '—');
                $sheet->getStyle("{$colLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($sc !== null) {
                    $cpmkScoreSums[$cpmk->id] = ($cpmkScoreSums[$cpmk->id] ?? 0.0) + $sc;
                    $cpmkScoreCounts[$cpmk->id] = ($cpmkScoreCounts[$cpmk->id] ?? 0) + 1;
                }
                $colIdx++;
            }

            // Nilai Akhir (Highlight Kuning Lembut)
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}{$currRow}", $final['score'] !== null ? number_format($final['score'], 2) : '—');
            $sheet->getStyle("{$colLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colLetter}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_CAPAIAN_TEXT);
            $sheet->getStyle("{$colLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPAIAN_BG);
            $colIdx++;

            // Grade Badge
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}{$currRow}", $grade ?? '—');
            $sheet->getStyle("{$colLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colLetter}{$currRow}")->getFont()->setBold(true);
            if ($grade !== null) {
                $gradeStyle = $this->gradeBadgeColors($grade);
                $sheet->getStyle("{$colLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($gradeStyle['bg']);
                $sheet->getStyle("{$colLetter}{$currRow}")->getFont()->getColor()->setARGB($gradeStyle['text']);
            }
            $colIdx++;

            // Predikat
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}{$currRow}", $predicate ?? '—');
            $sheet->getStyle("{$colLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $colIdx++;

            // Coverage
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}{$currRow}", $final['coverage'] . '%');
            $sheet->getStyle("{$colLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $colIdx++;

            // Status
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}{$currRow}", $status);
            $sheet->getStyle("{$colLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if ($status === 'Final') {
                $sheet->getStyle("{$colLetter}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_PASS_TEXT);
            } elseif ($status === 'Provisional') {
                $sheet->getStyle("{$colLetter}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB('FFD97706'); // Amber
            }

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10.5);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("A{$currRow}:C{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getRowDimension($currRow)->setRowHeight(22);
        }

        // Summary row
        if (! empty($finalScores)) {
            $currRow++;
            $avgScore = round(array_sum($finalScores) / count($finalScores), 2);
            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("A{$currRow}", 'RATA-RATA KELAS');
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
            $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);

            // CPMK averages
            $cIdx = 4;
            foreach ($cpmks as $cpmk) {
                $cLet = Coordinate::stringFromColumnIndex($cIdx);
                $cnt = $cpmkScoreCounts[$cpmk->id] ?? 0;
                $sum = $cpmkScoreSums[$cpmk->id] ?? 0.0;
                $cAvg = $cnt > 0 ? round($sum / $cnt, 1) : null;
                $sheet->setCellValue("{$cLet}{$currRow}", $cAvg !== null ? number_format($cAvg, 1) : '—');
                $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true);
                $cIdx++;
            }

            $finalScoreColLetter = Coordinate::stringFromColumnIndex(4 + $cpmks->count());
            $sheet->setCellValue("{$finalScoreColLetter}{$currRow}", number_format($avgScore, 2));
            $sheet->getStyle("{$finalScoreColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$finalScoreColLetter}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_CAPAIAN_TEXT);
            $sheet->getStyle("{$finalScoreColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPAIAN_BG);

            $avgGradeLetter = Coordinate::stringFromColumnIndex(4 + $cpmks->count() + 1);
            $avgGrade = $this->gradeLetter($avgScore) ?? '—';
            $sheet->setCellValue("{$avgGradeLetter}{$currRow}", $avgGrade);
            $sheet->getStyle("{$avgGradeLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$avgGradeLetter}{$currRow}")->getFont()->setBold(true);

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->doubleBottomBorderArray());
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_TABLE_HEADER_BG);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
            $sheet->getRowDimension($currRow)->setRowHeight(24);
        }

        // Executive KPI Dashboard Card
        $totalStudents = count($students);
        $passRate = $totalStudents > 0 ? round(($passCount / $totalStudents) * 100, 1) : 0.0;
        $maxScore = ! empty($finalScores) ? max($finalScores) : null;
        $minScore = ! empty($finalScores) ? min($finalScores) : null;
        $currRow = $this->appendKpiSummaryCard(
            $sheet,
            $currRow,
            $numCols,
            $totalStudents,
            $passCount,
            $passRate,
            ! empty($finalScores) ? $avgScore : null,
            $maxScore,
            $minScore,
            55.0,
            'NILAI AKHIR'
        );

        $this->appendSignBlock($sheet, $section, $currRow, $numCols);

        // Column widths explicit
        $widths = [
            'A' => 6.0,
            'B' => 18.0,
            'C' => 34.0,
        ];
        $cIdx = 4;
        foreach ($cpmks as $cpmk) {
            $widths[Coordinate::stringFromColumnIndex($cIdx)] = 13.5;
            $cIdx++;
        }
        $widths[Coordinate::stringFromColumnIndex($cIdx++)] = 15.0; // Nilai Akhir
        $widths[Coordinate::stringFromColumnIndex($cIdx++)] = 10.0; // Grade
        $widths[Coordinate::stringFromColumnIndex($cIdx++)] = 14.0; // Predikat
        $widths[Coordinate::stringFromColumnIndex($cIdx++)] = 12.0; // Coverage
        $widths[Coordinate::stringFromColumnIndex($cIdx++)] = 16.0; // Status

        $this->applyExplicitColumnWidths($sheet, $widths);
        $this->setupPageAndPrint($sheet);

        $mkCode = $section->mataKuliah?->code ?? 'MK';
        $classCode = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'Rekap_OBE_Nilai_Akhir_' . $mkCode . '_' . $classCode . '_' . date('Y-m-d') . '.xlsx';

        return $this->downloadSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Export Rekap CPL ke Excel (.xlsx) dengan Kop Surat & Palet Warna.
     */
    public function exportCplExcel(ClassSection $section): StreamedResponse
    {
        $section->loadMissing(['mataKuliah.prodi', 'semester', 'dosen']);
        $cpmkIds = Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->pluck('id');
        $cpls = Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))->orderBy('code')->get();
        $students = $section->students()->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Capaian CPL');
        $sheet->getTabColor()->setARGB('FFF59E0B'); // Amber/Gold
        $sheet->setShowGridlines(true);

        $numCols = 3 + ($cpls->count() * 2);
        $lastColLetter = Coordinate::stringFromColumnIndex($numCols);

        $currRow = $this->applyKopSurat($sheet, $section, 'Rekapitulasi Capaian Pembelajaran Lulusan (CPL)', $numCols);

        // Banner Hijau: Informasi CPL
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'CAPAIAN PEMBELAJARAN LULUSAN (CPL) YANG DIBEBANKAN');
        $this->styleBannerGreen($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;
        $this->styleAccentStrip($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");

        $currRow++;
        $sheet->setCellValue("A{$currRow}", 'Kode CPL');
        $sheet->mergeCells("B{$currRow}:E{$currRow}");
        $sheet->setCellValue("B{$currRow}", 'Deskripsi Capaian Pembelajaran Lulusan');
        $sheet->setCellValue("{$lastColLetter}{$currRow}", 'Target Minimum (0-100)');
        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(22);

        foreach ($cpls as $idx => $cpl) {
            $currRow++;
            $rowBg = ($idx % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $cpl->code);
            $sheet->mergeCells("B{$currRow}:E{$currRow}");
            $sheet->setCellValue("B{$currRow}", $cpl->description ?? $cpl->name ?? '—');
            $sheet->setCellValue("{$lastColLetter}{$currRow}", (int) ($cpl->target_score ?? 65));

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HIGHLIGHT_YELLOW);

            $sheet->getStyle("A{$currRow}:E{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getRowDimension($currRow)->setRowHeight(20);
        }

        $currRow++;
        $sheet->getRowDimension($currRow)->setRowHeight(10); // Spacer
        $currRow++;

        // Banner Oranye: Data Capaian CPL Mahasiswa
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'DATA CAPAIAN CPL MAHASISWA');
        $this->styleBannerOrange($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;

        // Table Header
        $sheet->setCellValue("A{$currRow}", 'No');
        $sheet->setCellValue("B{$currRow}", 'NIM');
        $sheet->setCellValue("C{$currRow}", 'Nama Mahasiswa');

        $colIdx = 4;
        foreach ($cpls as $cpl) {
            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$cLet}{$currRow}", $cpl->code . "\n(Skor)");
            $colIdx++;
            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$cLet}{$currRow}", 'Status ' . $cpl->code);
            $colIdx++;
        }

        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(32);

        // Data Rows
        $cplPassCounts = [];
        $cplTotalScores = [];
        $cplScoreCounts = [];

        foreach ($students as $i => $student) {
            $currRow++;
            $scores = $this->obe->cplScoresFor($cpls, $student->id, $section->id);
            $rowBg = ($i % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $i + 1);
            $sheet->setCellValueExplicit("B{$currRow}", (string) ($student->nim_nidn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $student->name);

            $colIdx = 4;
            foreach ($cpls as $cpl) {
                $sc = $scores[$cpl->id] ?? null;
                $target = (float) ($cpl->target_score ?? 65);
                $isAchieved = $sc !== null && $sc >= $target;

                $cLet = Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue("{$cLet}{$currRow}", $sc !== null ? number_format($sc, 1) : '—');
                $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_CAPAIAN_TEXT);
                $sheet->getStyle("{$cLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPAIAN_BG);

                if ($sc !== null) {
                    $cplTotalScores[$cpl->id] = ($cplTotalScores[$cpl->id] ?? 0.0) + $sc;
                    $cplScoreCounts[$cpl->id] = ($cplScoreCounts[$cpl->id] ?? 0) + 1;
                }
                $colIdx++;

                $cLet = Coordinate::stringFromColumnIndex($colIdx);
                $statusText = $sc !== null ? ($isAchieved ? 'Tercapai' : 'Belum Tercapai') : 'Belum Dinilai';
                $sheet->setCellValue("{$cLet}{$currRow}", $statusText);
                $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($sc !== null) {
                    if ($isAchieved) {
                        $sheet->getStyle("{$cLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PASS_BG);
                        $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_PASS_TEXT);
                        $cplPassCounts[$cpl->id] = ($cplPassCounts[$cpl->id] ?? 0) + 1;
                    } else {
                        $sheet->getStyle("{$cLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_FAIL_BG);
                        $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_FAIL_TEXT);
                    }
                } else {
                    $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setItalic(true)->getColor()->setARGB('FF94A3B8');
                }
                $colIdx++;
            }

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10.5);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("A{$currRow}:C{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getRowDimension($currRow)->setRowHeight(22);
        }

        // Summary row
        $currRow++;
        $sheet->mergeCells("A{$currRow}:C{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'PERSENTASE KETUNTASAN CPL KELAS');
        $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
        $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);

        $colIdx = 4;
        $totalStd = max(1, count($students));
        foreach ($cpls as $cpl) {
            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $cCnt = $cplScoreCounts[$cpl->id] ?? 0;
            $cSum = $cplTotalScores[$cpl->id] ?? 0.0;
            $cAvg = $cCnt > 0 ? round($cSum / $cCnt, 1) : null;
            $sheet->setCellValue("{$cLet}{$currRow}", $cAvg !== null ? number_format($cAvg, 1) : '—');
            $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true);
            $colIdx++;

            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $pass = $cplPassCounts[$cpl->id] ?? 0;
            $rate = round(($pass / $totalStd) * 100, 1);
            $sheet->setCellValue("{$cLet}{$currRow}", "{$rate}% ({$pass}/{$totalStd})");
            $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_PASS_TEXT);
            $sheet->getStyle("{$cLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PASS_BG);
            $colIdx++;
        }

        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->doubleBottomBorderArray());
        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
        $sheet->getRowDimension($currRow)->setRowHeight(24);

        $this->appendSignBlock($sheet, $section, $currRow, $numCols);

        $widths = [
            'A' => 6.0,
            'B' => 18.0,
            'C' => 34.0,
        ];
        $colIdx = 4;
        foreach ($cpls as $cpl) {
            $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 13.0; // Skor
            $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 18.0; // Status
        }
        $this->applyExplicitColumnWidths($sheet, $widths);
        $this->setupPageAndPrint($sheet);

        $mkCode = $section->mataKuliah?->code ?? 'MK';
        $classCode = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'Rekap_OBE_CPL_' . $mkCode . '_' . $classCode . '_' . date('Y-m-d') . '.xlsx';

        return $this->downloadSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Export Nilai per Asesmen ke Excel (.xlsx).
     */
    public function exportNilaiAssessmentExcel(ClassSection $section): StreamedResponse
    {
        $section->loadMissing(['mataKuliah.prodi', 'semester', 'dosen']);
        $assessments = $section->assessments()->orderBy('code')->get();
        $students = $section->students()->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai per Asesmen');
        $sheet->getTabColor()->setARGB('FFF97316'); // Orange
        $sheet->setShowGridlines(true);

        $numCols = 3 + $assessments->count();
        $lastColLetter = Coordinate::stringFromColumnIndex($numCols);

        $currRow = $this->applyKopSurat($sheet, $section, 'Rekapitulasi Nilai per Komponen Asesmen', $numCols);

        // Banner Hijau: Komponen Asesmen
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'KOMPONEN ASESMEN & BOBOT PENILAIAN');
        $this->styleBannerGreen($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;
        $this->styleAccentStrip($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");

        $currRow++;
        $sheet->setCellValue("A{$currRow}", 'Kode Asesmen');
        $sheet->mergeCells("B{$currRow}:D{$currRow}");
        $sheet->setCellValue("B{$currRow}", 'Nama Instrumen Asesmen');
        $sheet->setCellValue("E{$currRow}", 'Jenis Evaluasi');
        $sheet->setCellValue("{$lastColLetter}{$currRow}", 'Bobot Nilai (%)');
        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(22);

        foreach ($assessments as $idx => $asmt) {
            $currRow++;
            $rowBg = ($idx % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $asmt->code ?: $asmt->name);
            $sheet->mergeCells("B{$currRow}:D{$currRow}");
            $sheet->setCellValue("B{$currRow}", $asmt->name);
            $sheet->setCellValue("E{$currRow}", ucfirst($asmt->type ?? 'Asesmen'));
            $sheet->setCellValue("{$lastColLetter}{$currRow}", rtrim(rtrim(number_format((float) $asmt->final_weight, 1), '0'), '.') . '%');

            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("E{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HIGHLIGHT_YELLOW);

            $sheet->getStyle("A{$currRow}:E{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getRowDimension($currRow)->setRowHeight(20);
        }

        $currRow++;
        $sheet->getRowDimension($currRow)->setRowHeight(10); // Spacer
        $currRow++;

        // Banner Oranye: Daftar Nilai Mahasiswa
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'DAFTAR NILAI KOMPONEN ASESMEN MAHASISWA');
        $this->styleBannerOrange($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;

        // Table Header
        $sheet->setCellValue("A{$currRow}", 'No');
        $sheet->setCellValue("B{$currRow}", 'NIM');
        $sheet->setCellValue("C{$currRow}", 'Nama Mahasiswa');

        $colIdx = 4;
        foreach ($assessments as $asmt) {
            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $w = rtrim(rtrim(number_format((float) $asmt->final_weight, 1), '0'), '.');
            $sheet->setCellValue("{$cLet}{$currRow}", $asmt->name . "\n(" . $w . '%)');
            $colIdx++;
        }

        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(32);

        // Data Rows
        $asmtScoreSums = [];
        $asmtScoreCounts = [];

        foreach ($students as $i => $student) {
            $currRow++;
            $rowBg = ($i % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $i + 1);
            $sheet->setCellValueExplicit("B{$currRow}", (string) ($student->nim_nidn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $student->name);

            $colIdx = 4;
            foreach ($assessments as $asmt) {
                $cLet = Coordinate::stringFromColumnIndex($colIdx);
                $sc = $this->obe->assessmentScore($asmt->id, $student->id);
                $sheet->setCellValue("{$cLet}{$currRow}", $sc !== null ? number_format($sc, 1) : '—');
                $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($sc !== null) {
                    $asmtScoreSums[$asmt->id] = ($asmtScoreSums[$asmt->id] ?? 0.0) + $sc;
                    $asmtScoreCounts[$asmt->id] = ($asmtScoreCounts[$asmt->id] ?? 0) + 1;
                }
                $colIdx++;
            }

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10.5);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("A{$currRow}:C{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getRowDimension($currRow)->setRowHeight(22);
        }

        // Summary row
        $currRow++;
        $sheet->mergeCells("A{$currRow}:C{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'RATA-RATA KELAS');
        $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
        $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);

        $colIdx = 4;
        foreach ($assessments as $asmt) {
            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $cnt = $asmtScoreCounts[$asmt->id] ?? 0;
            $sum = $asmtScoreSums[$asmt->id] ?? 0.0;
            $avg = $cnt > 0 ? round($sum / $cnt, 1) : null;
            $sheet->setCellValue("{$cLet}{$currRow}", $avg !== null ? number_format($avg, 1) : '—');
            $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true);
            $colIdx++;
        }

        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->doubleBottomBorderArray());
        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
        $sheet->getRowDimension($currRow)->setRowHeight(24);

        $this->appendSignBlock($sheet, $section, $currRow, $numCols);

        $widths = [
            'A' => 6.0,
            'B' => 18.0,
            'C' => 34.0,
        ];
        $colIdx = 4;
        foreach ($assessments as $asmt) {
            $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 16.0;
        }
        $this->applyExplicitColumnWidths($sheet, $widths);
        $this->setupPageAndPrint($sheet);

        $mkCode = $section->mataKuliah?->code ?? 'MK';
        $classCode = $section->section_code ?: ($section->name ?: 'A');
        $filename = 'Rekap_OBE_Asesmen_' . $mkCode . '_' . $classCode . '_' . date('Y-m-d') . '.xlsx';

        return $this->downloadSpreadsheet($spreadsheet, $filename);
    }

    /**
     * Membangun satu Worksheet CPMK persis dengan tata letak & palet Rekap_OBE_CPMK101_2026-09-12.xlsx,
     * ditingkatkan dengan kartu KPI, zebra striping, badge kelulusan, dan pengesahan resmi.
     */
    private function buildCpmkSheet(
        Worksheet $sheet,
        ClassSection $section,
        Cpmk $cpmk,
        Collection $students,
        Collection|array $cpmkWeights
    ): void {
        $sheet->setTitle(substr($cpmk->code, 0, 31));
        $sheet->setShowGridlines(true);

        $measuringAssessments = $section->assessments()
            ->whereHas('cpmks', fn ($q) => $q->where('cpmks.id', $cpmk->id))
            ->with('cpmks')
            ->orderBy('code')
            ->get();

        $asmtCount = $measuringAssessments->count();
        $numCols = 3 + $asmtCount + 2; // Col A=No, B=NIM, C=Nama, Assessments..., Nilai Capaian, Status Kelulusan
        $lastColLetter = Coordinate::stringFromColumnIndex($numCols);
        $penultimateLetter = Coordinate::stringFromColumnIndex($numCols - 1);

        // ── 1. KOP SURAT RESMI ──
        $currRow = $this->applyKopSurat($sheet, $section, 'Rekapitulasi Penilaian Capaian ' . $cpmk->code, $numCols);

        // ── 2. BANNER HIJAU PEMBOBOTAN (#92D050 - persis referensi) ──
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'PEMBOBOTAN NILAI ASESMEN ' . $cpmk->code);
        $this->styleBannerGreen($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;
        // Strip pemisah kuning (#FFFF00)
        $this->styleAccentStrip($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");

        $currRow++;
        // Bobot CPMK terhadap total bobot MK
        $cpmkWeightVal = $cpmkWeights[$cpmk->id] ?? 0;
        $sheet->mergeCells("A{$currRow}:{$penultimateLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", "Bobot {$cpmk->code} terhadap total jumlah seluruh CPMK:");
        $sheet->getStyle("A{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
        $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$currRow}:{$penultimateLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');

        $sheet->setCellValue("{$lastColLetter}{$currRow}", rtrim(rtrim(number_format($cpmkWeightVal, 1), '0'), '.') . '%');
        $sheet->getStyle("{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10.5)->setBold(true);
        $sheet->getStyle("{$lastColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_HIGHLIGHT_YELLOW);
        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
        $sheet->getRowDimension($currRow)->setRowHeight(21);

        // Baris rincian bobot per penilaian (UTS, Kuis, dsb.)
        $pIdx = 1;
        $splitColLetter = Coordinate::stringFromColumnIndex(4);
        foreach ($measuringAssessments as $asmt) {
            $currRow++;
            $effWeight = $this->obe->assessmentCpmkEffectiveWeight($asmt, $cpmk);
            $rowBg = ($pIdx % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("A{$currRow}", "Bobot Penilaian-{$pIdx}");
            $sheet->getStyle("A{$currRow}")->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->mergeCells("{$splitColLetter}{$currRow}:{$penultimateLetter}{$currRow}");
            $sheet->setCellValue("{$splitColLetter}{$currRow}", $asmt->name);
            $sheet->getStyle("{$splitColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10);
            $sheet->getStyle("{$splitColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue("{$lastColLetter}{$currRow}", rtrim(rtrim(number_format($effWeight, 1), '0'), '.') . '%');
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
            $sheet->getStyle("{$lastColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle("A{$currRow}:{$penultimateLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getRowDimension($currRow)->setRowHeight(20);
            $pIdx++;
        }

        $currRow++;
        $sheet->getRowDimension($currRow)->setRowHeight(10); // Spacer
        $currRow++;

        // ── 3. BANNER ORANYE ASESMEN CAPAIAN (#F79646 - persis referensi) ──
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'ASESMEN CAPAIAN PEMBELAJARAN ' . $cpmk->code);
        $this->styleBannerOrange($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;

        // Table Header
        $sheet->setCellValue("A{$currRow}", 'No');
        $sheet->setCellValue("B{$currRow}", 'NIM');
        $sheet->setCellValue("C{$currRow}", 'NAMA');

        $colIdx = 4;
        foreach ($measuringAssessments as $asmt) {
            $cLet = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$cLet}{$currRow}", $asmt->name);
            $colIdx++;
        }

        $capColLetter = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$capColLetter}{$currRow}", 'NILAI CAPAIAN ' . $cpmk->code);
        $colIdx++;

        $statColLetter = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$statColLetter}{$currRow}", 'STATUS KELULUSAN ' . $cpmk->code);

        $this->styleTableHeader($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(30);

        // Data Rows
        $threshold = (float) ($cpmk->threshold ?: 60);
        $cpmkScoresRecorded = [];
        $asmtScoreSums = [];
        $asmtScoreCounts = [];
        $passedCount = 0;

        foreach ($students as $sIdx => $student) {
            $currRow++;
            $rowBg = ($sIdx % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $sIdx + 1);
            $sheet->setCellValueExplicit("B{$currRow}", (string) ($student->nim_nidn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $student->name);

            $colIdx = 4;
            foreach ($measuringAssessments as $asmt) {
                $cLet = Coordinate::stringFromColumnIndex($colIdx);
                $sc = $this->obe->studentScoreForAssessmentCpmk($asmt, $cpmk, $student->id);
                $sheet->setCellValue("{$cLet}{$currRow}", $sc !== null ? number_format($sc, 1) : '—');

                if ($sc !== null) {
                    $asmtScoreSums[$asmt->id] = ($asmtScoreSums[$asmt->id] ?? 0.0) + $sc;
                    $asmtScoreCounts[$asmt->id] = ($asmtScoreCounts[$asmt->id] ?? 0) + 1;
                }
                $colIdx++;
            }

            $cpmkSc = $this->obe->cpmkScore($cpmk, $student->id, $section->id);
            $capLet = Coordinate::stringFromColumnIndex($colIdx);
            if ($cpmkSc !== null) {
                $sheet->setCellValue("{$capLet}{$currRow}", number_format($cpmkSc, 1));
                $cpmkScoresRecorded[] = $cpmkSc;
            } else {
                $sheet->setCellValue("{$capLet}{$currRow}", '—');
            }
            $colIdx++;

            $statLet = Coordinate::stringFromColumnIndex($colIdx);
            if ($cpmkSc !== null) {
                $isPassed = $cpmkSc >= $threshold;
                if ($isPassed) {
                    $passedCount++;
                    $sheet->setCellValue("{$statLet}{$currRow}", "Lulus {$cpmk->code}");
                    $sheet->getStyle("{$statLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PASS_BG);
                    $sheet->getStyle("{$statLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_PASS_TEXT);
                } else {
                    $sheet->setCellValue("{$statLet}{$currRow}", "Belum Lulus {$cpmk->code}");
                    $sheet->getStyle("{$statLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_FAIL_BG);
                    $sheet->getStyle("{$statLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_FAIL_TEXT);
                }
            } else {
                $sheet->setCellValue("{$statLet}{$currRow}", 'Belum Dinilai');
                $sheet->getStyle("{$statLet}{$currRow}")->getFont()->setItalic(true)->getColor()->setARGB('FF94A3B8');
            }

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(11);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);

            for ($c = 4; $c <= $numCols; $c++) {
                $cLet = Coordinate::stringFromColumnIndex($c);
                $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Highlight Nilai Capaian cell (Kuning Lembut)
            $sheet->getStyle("{$capLet}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_CAPAIAN_TEXT);
            $sheet->getStyle("{$capLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPAIAN_BG);

            $sheet->getStyle("A{$currRow}:C{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getRowDimension($currRow)->setRowHeight(22);
        }

        // Summary row
        $avgScore = null;
        $maxScore = null;
        $minScore = null;
        $passRate = 0.0;
        $totalStudents = count($students);

        if (! empty($cpmkScoresRecorded)) {
            $currRow++;
            $avgScore = round(array_sum($cpmkScoresRecorded) / count($cpmkScoresRecorded), 1);
            $maxScore = max($cpmkScoresRecorded);
            $minScore = min($cpmkScoresRecorded);
            $passRate = round(($passedCount / max(1, $totalStudents)) * 100, 1);

            $sheet->mergeCells("A{$currRow}:C{$currRow}");
            $sheet->setCellValue("A{$currRow}", 'RATA-RATA KELAS');
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
            $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);

            // Averages for each assessment
            $cIdx = 4;
            foreach ($measuringAssessments as $asmt) {
                $cLet = Coordinate::stringFromColumnIndex($cIdx);
                $cnt = $asmtScoreCounts[$asmt->id] ?? 0;
                $sum = $asmtScoreSums[$asmt->id] ?? 0.0;
                $aAvg = $cnt > 0 ? round($sum / $cnt, 1) : null;
                $sheet->setCellValue("{$cLet}{$currRow}", $aAvg !== null ? number_format($aAvg, 1) : '—');
                $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true);
                $cIdx++;
            }

            $capColLetter = Coordinate::stringFromColumnIndex(4 + $asmtCount);
            $sheet->setCellValue("{$capColLetter}{$currRow}", number_format($avgScore, 1));
            $sheet->getStyle("{$capColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$capColLetter}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_CAPAIAN_TEXT);
            $sheet->getStyle("{$capColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_CAPAIAN_BG);

            $statColLetter = Coordinate::stringFromColumnIndex(4 + $asmtCount + 1);
            $sheet->setCellValue("{$statColLetter}{$currRow}", "Tuntas: {$passRate}% ({$passedCount}/{$totalStudents})");
            $sheet->getStyle("{$statColLetter}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$statColLetter}{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_PASS_TEXT);
            $sheet->getStyle("{$statColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PASS_BG);

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->doubleBottomBorderArray());
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
            $sheet->getRowDimension($currRow)->setRowHeight(24);
        }

        // Executive KPI Dashboard Card
        $currRow = $this->appendKpiSummaryCard(
            $sheet,
            $currRow,
            $numCols,
            $totalStudents,
            $passedCount,
            $passRate,
            $avgScore,
            $maxScore,
            $minScore,
            $threshold,
            $cpmk->code
        );

        $this->appendSignBlock($sheet, $section, $currRow, $numCols);

        // Column widths explicit (identik dan proporsional dengan file referensi)
        $widths = [
            'A' => 6.0,
            'B' => 18.0,
            'C' => 34.0,
        ];
        $colIdx = 4;
        foreach ($measuringAssessments as $asmt) {
            $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 15.0;
        }
        $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 24.0; // Nilai Capaian CPMK
        $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 26.0; // Status Kelulusan CPMK

        $this->applyExplicitColumnWidths($sheet, $widths);
        $this->setupPageAndPrint($sheet);
    }

    /**
     * Membangun Sheet Matriks Asesmen & CPMK (seperti pada tampilan web rekap.blade.php).
     */
    private function buildMatrixSheet(
        Worksheet $sheet,
        ClassSection $section,
        Collection $cpmks,
        Collection $assessments,
        Collection $students
    ): void {
        $sheet->setShowGridlines(true);

        $assessmentIds = $assessments->pluck('id');
        $rawAssessmentScores = StudentAssessmentScore::whereIn('assessment_id', $assessmentIds)
            ->get()
            ->groupBy(fn ($r) => $r->assessment_id . '_' . $r->mahasiswa_id);
        $rawCpmkScores = StudentAssessmentCpmkScore::whereIn('assessment_id', $assessmentIds)
            ->get()
            ->groupBy(fn ($r) => $r->assessment_id . '_' . $r->cpmk_id . '_' . $r->mahasiswa_id);

        $columns = [];
        $totalSubCols = 0;
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
                $totalSubCols += count($cpmkCols) + 1;
            }
        }

        $numCols = 3 + $totalSubCols;
        $lastColLetter = Coordinate::stringFromColumnIndex($numCols);

        $currRow = $this->applyKopSurat($sheet, $section, 'Matriks Penilaian Asesmen & Capaian CPMK Mahasiswa', $numCols);

        // Banner Hijau: Matriks Penilaian
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'MATRIKS PENILAIAN ASESMEN & CAPAIAN CPMK');
        $this->styleBannerGreen($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->getRowDimension($currRow)->setRowHeight(23);

        $currRow++;
        $this->styleAccentStrip($sheet, "A{$currRow}:{$lastColLetter}{$currRow}");

        $currRow++;
        // Header Baris 1: Grup Asesmen
        $h1Row = $currRow;
        $currRow++;
        // Header Baris 2: Sub-kolom CPMK
        $h2Row = $currRow;

        $sheet->mergeCells("A{$h1Row}:A{$h2Row}");
        $sheet->setCellValue("A{$h1Row}", 'No');

        $sheet->mergeCells("B{$h1Row}:B{$h2Row}");
        $sheet->setCellValue("B{$h1Row}", 'NIM');

        $sheet->mergeCells("C{$h1Row}:C{$h2Row}");
        $sheet->setCellValue("C{$h1Row}", 'Nama Mahasiswa');

        $colIdx = 4;
        foreach ($columns as $col) {
            $asmt = $col['assessment'];
            $subColCount = count($col['cpmk_cols']) + 1; // CPMK cols + Total col
            $startLet = Coordinate::stringFromColumnIndex($colIdx);
            $endLet = Coordinate::stringFromColumnIndex($colIdx + $subColCount - 1);

            $sheet->mergeCells("{$startLet}{$h1Row}:{$endLet}{$h1Row}");
            $sheet->setCellValue("{$startLet}{$h1Row}", $asmt->name . ' (' . ucfirst($asmt->type ?? 'Asesmen') . ')');

            foreach ($col['cpmk_cols'] as $cc) {
                $subLet = Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue("{$subLet}{$h2Row}", $cc['cpmk']->code . "\n(" . $cc['weight_fmt'] . '%)');
                $colIdx++;
            }

            // Total Col
            $totLet = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$totLet}{$h2Row}", "Total\n(100)");
            $colIdx++;
        }

        $this->styleTableHeader($sheet, "A{$h1Row}:{$lastColLetter}{$h2Row}");
        $sheet->getRowDimension($h1Row)->setRowHeight(24);
        $sheet->getRowDimension($h2Row)->setRowHeight(28);

        // Data Rows
        $matrixSums = [];
        $matrixCounts = [];

        foreach ($students as $sIdx => $student) {
            $currRow++;
            $rowBg = ($sIdx % 2 === 0) ? 'FFFFFFFF' : self::COLOR_ZEBRA_BG;

            $sheet->setCellValue("A{$currRow}", $sIdx + 1);
            $sheet->setCellValueExplicit("B{$currRow}", (string) ($student->nim_nidn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currRow}", $student->name);

            $colIdx = 4;
            foreach ($columns as $col) {
                $asmtId = $col['assessment']->id;
                $asmtScoreKey = $asmtId . '_' . $student->id;
                $asmtRow = $rawAssessmentScores->get($asmtScoreKey)?->first();

                $asmtHasPending = false;
                $allSubCellsGraded = true;
                $sumCellScores = 0.0;
                $hasAnyCellScore = false;

                foreach ($col['cpmk_cols'] as $cc) {
                    $cpmk = $cc['cpmk'];
                    $cpmkKey = $asmtId . '_' . $cpmk->id . '_' . $student->id;
                    $cpmkSpecific = $rawCpmkScores->get($cpmkKey)?->first();
                    $maxScore = (float) $cc['weight'];

                    $cellLet = Coordinate::stringFromColumnIndex($colIdx);

                    if ($cpmkSpecific !== null && $cpmkSpecific->score !== null) {
                        $rawVal = (float) $cpmkSpecific->score;
                        if (count($col['cpmk_cols']) > 1 && $rawVal > ($maxScore + 0.01) && $maxScore > 0) {
                            $cellScore = round(($rawVal * $maxScore) / 100, 1);
                        } else {
                            $cellScore = round($rawVal, 1);
                        }
                        $sheet->setCellValue("{$cellLet}{$currRow}", number_format($cellScore, 1));
                        $sumCellScores += $cellScore;
                        $hasAnyCellScore = true;
                        $matrixSums[$colIdx] = ($matrixSums[$colIdx] ?? 0.0) + $cellScore;
                        $matrixCounts[$colIdx] = ($matrixCounts[$colIdx] ?? 0) + 1;
                    } elseif ($asmtRow === null) {
                        $sheet->setCellValue("{$cellLet}{$currRow}", '—');
                        $allSubCellsGraded = false;
                    } elseif ($asmtRow->score !== null) {
                        $cellScore = round(((float) $asmtRow->score * $maxScore) / 100, 1);
                        $sheet->setCellValue("{$cellLet}{$currRow}", number_format($cellScore, 1));
                        $sumCellScores += $cellScore;
                        $hasAnyCellScore = true;
                        $matrixSums[$colIdx] = ($matrixSums[$colIdx] ?? 0.0) + $cellScore;
                        $matrixCounts[$colIdx] = ($matrixCounts[$colIdx] ?? 0) + 1;
                    } else {
                        $sheet->setCellValue("{$cellLet}{$currRow}", 'Menunggu');
                        $sheet->getStyle("{$cellLet}{$currRow}")->getFont()->getColor()->setARGB('FFB45309');
                        $asmtHasPending = true;
                        $allSubCellsGraded = false;
                    }
                    $sheet->getStyle("{$cellLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $colIdx++;
                }

                // Total Asesmen (Skala 100)
                $totLet = Coordinate::stringFromColumnIndex($colIdx);
                if ($asmtHasPending || ($asmtRow !== null && $asmtRow->score === null)) {
                    $sheet->setCellValue("{$totLet}{$currRow}", 'Menunggu');
                    $sheet->getStyle("{$totLet}{$currRow}")->getFont()->getColor()->setARGB('FFB45309');
                } elseif ($asmtRow !== null && $asmtRow->score !== null) {
                    $totVal = (float) $asmtRow->score;
                    $sheet->setCellValue("{$totLet}{$currRow}", number_format($totVal, 1));
                    $sheet->getStyle("{$totLet}{$currRow}")->getFont()->setBold(true);
                    $matrixSums[$colIdx] = ($matrixSums[$colIdx] ?? 0.0) + $totVal;
                    $matrixCounts[$colIdx] = ($matrixCounts[$colIdx] ?? 0) + 1;
                } elseif ($allSubCellsGraded && $hasAnyCellScore) {
                    $sheet->setCellValue("{$totLet}{$currRow}", number_format($sumCellScores, 1));
                    $sheet->getStyle("{$totLet}{$currRow}")->getFont()->setBold(true);
                    $matrixSums[$colIdx] = ($matrixSums[$colIdx] ?? 0.0) + $sumCellScores;
                    $matrixCounts[$colIdx] = ($matrixCounts[$colIdx] ?? 0) + 1;
                } else {
                    $sheet->setCellValue("{$totLet}{$currRow}", '—');
                }
                $sheet->getStyle("{$totLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$totLet}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                $colIdx++;
            }

            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10.5);
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("A{$currRow}:C{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rowBg);
            $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->thinBorderArray());
            $sheet->getRowDimension($currRow)->setRowHeight(22);
        }

        // Summary row
        $currRow++;
        $sheet->mergeCells("A{$currRow}:C{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'RATA-RATA KELAS');
        $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
        $sheet->getStyle("A{$currRow}")->getFont()->setBold(true);

        for ($c = 4; $c <= $numCols; $c++) {
            $cLet = Coordinate::stringFromColumnIndex($c);
            $cnt = $matrixCounts[$c] ?? 0;
            $sum = $matrixSums[$c] ?? 0.0;
            $avg = $cnt > 0 ? round($sum / $cnt, 1) : null;
            $sheet->setCellValue("{$cLet}{$currRow}", $avg !== null ? number_format($avg, 1) : '—');
            $sheet->getStyle("{$cLet}{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$cLet}{$currRow}")->getFont()->setBold(true);
        }

        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->applyFromArray($this->doubleBottomBorderArray());
        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true);
        $sheet->getRowDimension($currRow)->setRowHeight(24);

        $this->appendSignBlock($sheet, $section, $currRow, $numCols);

        $widths = [
            'A' => 6.0,
            'B' => 18.0,
            'C' => 34.0,
        ];
        $colIdx = 4;
        foreach ($columns as $col) {
            foreach ($col['cpmk_cols'] as $cc) {
                $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 12.5;
            }
            $widths[Coordinate::stringFromColumnIndex($colIdx++)] = 13.5; // Total
        }

        $this->applyExplicitColumnWidths($sheet, $widths);
        $this->setupPageAndPrint($sheet);
    }

    /**
     * Tulis Kop Surat Resmi pada bagian atas Worksheet dengan tata letak kartu metadata terpadu.
     * Mengembalikan nomor baris berikutnya yang siap diisi konten.
     */
    private function applyKopSurat(Worksheet $sheet, ClassSection $section, string $docTitle, int $maxCols): int
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($maxCols);

        // Baris 1: Nama Sistem & Ekosistem Utama (Warna Hitam)
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', 'SISTEM INFORMASI AKADEMIK & OUTCOME-BASED EDUCATION (SALE)');
        $sheet->getStyle('A1')->getFont()->setName('Times New Roman')->setSize(13.5)->setBold(true)->getColor()->setARGB(self::COLOR_HEADER_BLACK);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Baris 2: Judul Dokumen Laporan (Warna Hitam)
        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', mb_strtoupper($docTitle, 'UTF-8'));
        $sheet->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(11)->setBold(true)->getColor()->setARGB(self::COLOR_HEADER_BLACK);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Baris 3: Garis Aksen Kop Surat (#FFFF00 Warna Kuning)
        $sheet->mergeCells("A3:{$lastColLetter}3");
        $sheet->getStyle("A3:{$lastColLetter}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ACCENT_YELLOW);
        $sheet->getRowDimension(3)->setRowHeight(4.5);

        // Baris 4-7: Box Informasi Kartu Metadata Terpadu (Academic Info Card)
        $mk = $section->mataKuliah;
        $mkLabel = $mk ? ($mk->code . ' - ' . $mk->name . ($mk->sks ? " ({$mk->sks} SKS)" : '')) : '—';
        $prodiName = $mk?->prodi?->name ?? '—';
        $dosenName = $section->dosen?->name ?? '—';
        $dosenNip = $section->dosen?->nim_nidn ?: '—';
        $semesterName = $section->semester?->name ?? 'Semester Aktif';
        $classCode = 'Kelas ' . ($section->section_code ?: ($section->name ?: 'A'));
        $studentCount = $section->students()->count();

        // Tentukan pembagian kolom kiri dan kanan secara adaptif
        if ($maxCols <= 6) {
            $leftValEnd = 'C';
            $rightLabelCol = 'D';
            $rightValStart = 'E';
        } elseif ($maxCols <= 7) {
            $leftValEnd = 'D';
            $rightLabelCol = 'E';
            $rightValStart = 'F';
        } else {
            $splitIdx = (int) ceil($maxCols / 2);
            $leftValEnd = Coordinate::stringFromColumnIndex($splitIdx - 1);
            $rightLabelCol = Coordinate::stringFromColumnIndex($splitIdx);
            $rightValStart = Coordinate::stringFromColumnIndex($splitIdx + 1);
        }

        // Row 4
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('A4', 'Mata Kuliah');
        $sheet->mergeCells("C4:{$leftValEnd}4");
        $sheet->setCellValue('C4', ': ' . $mkLabel);
        $sheet->setCellValue("{$rightLabelCol}4", 'Semester');
        $sheet->mergeCells("{$rightValStart}4:{$lastColLetter}4");
        $sheet->setCellValue("{$rightValStart}4", ': ' . $semesterName);

        // Row 5
        $sheet->mergeCells('A5:B5');
        $sheet->setCellValue('A5', 'Program Studi');
        $sheet->mergeCells("C5:{$leftValEnd}5");
        $sheet->setCellValue('C5', ': ' . $prodiName);
        $sheet->setCellValue("{$rightLabelCol}5", 'Kelas / Sesi');
        $sheet->mergeCells("{$rightValStart}5:{$lastColLetter}5");
        $sheet->setCellValue("{$rightValStart}5", ': ' . $classCode);

        // Row 6
        $sheet->mergeCells('A6:B6');
        $sheet->setCellValue('A6', 'Dosen Pengampu');
        $sheet->mergeCells("C6:{$leftValEnd}6");
        $sheet->setCellValue('C6', ': ' . $dosenName);
        $sheet->setCellValue("{$rightLabelCol}6", 'NIP / NIDN');
        $sheet->mergeCells("{$rightValStart}6:{$lastColLetter}6");
        $sheet->setCellValue("{$rightValStart}6", ': ' . $dosenNip);

        // Row 7
        $sheet->mergeCells('A7:B7');
        $sheet->setCellValue('A7', 'Jumlah Mahasiswa');
        $sheet->mergeCells("C7:{$leftValEnd}7");
        $sheet->setCellValue('C7', ': ' . $studentCount . ' Orang Terdaftar');
        $sheet->setCellValue("{$rightLabelCol}7", 'Tanggal Ekspor');
        $sheet->mergeCells("{$rightValStart}7:{$lastColLetter}7");
        $sheet->setCellValue("{$rightValStart}7", ': ' . now()->locale('id')->translatedFormat('d F Y, H:i') . ' WIB');

        // Style Metadata Card
        $sheet->getStyle('A4:A7')->getFont()->setName('Times New Roman')->setSize(9.5)->setBold(true)->getColor()->setARGB('FF334155');
        $sheet->getStyle("{$rightLabelCol}4:{$rightLabelCol}7")->getFont()->setName('Times New Roman')->setSize(9.5)->setBold(true)->getColor()->setARGB('FF334155');
        $sheet->getStyle("C4:{$leftValEnd}7")->getFont()->setName('Times New Roman')->setSize(9.5)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle("{$rightValStart}4:{$lastColLetter}7")->getFont()->setName('Times New Roman')->setSize(9.5)->getColor()->setARGB('FF0F172A');

        $sheet->getStyle("A4:{$lastColLetter}7")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_INFO_BOX_BG);
        $sheet->getStyle("A4:{$lastColLetter}7")->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_GRAY],
                ],
            ],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(18);
        $sheet->getRowDimension(6)->setRowHeight(18);
        $sheet->getRowDimension(7)->setRowHeight(18);

        // Row 8 is blank spacer
        $sheet->getRowDimension(8)->setRowHeight(10);

        return 9;
    }

    /**
     * Berikan gaya Banner Hijau (#92D050) seperti pada Rekap_OBE_CPMK101.
     */
    private function styleBannerGreen(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_BANNER_GREEN);
        $sheet->getStyle($cellRange)->getFont()->setName('Times New Roman')->setSize(10.5)->setBold(true)->getColor()->setARGB('FF000000');
        $sheet->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($cellRange)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF65A30D'],
                ],
            ],
        ]);
    }

    /**
     * Berikan gaya Strip Pemisah Kuning (#FFFF00) menggantikan biru sesuai permintaan user.
     */
    private function styleAccentStrip(Worksheet $sheet, string $cellRange): void
    {
        $sheet->mergeCells($cellRange);
        $sheet->getStyle($cellRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_ACCENT_YELLOW);
        $rowNum = (int) filter_var($cellRange, FILTER_SANITIZE_NUMBER_INT);
        $sheet->getRowDimension($rowNum)->setRowHeight(4.0);
    }

    /**
     * Berikan gaya Banner Oranye (#F79646) seperti pada Rekap_OBE_CPMK101.
     */
    private function styleBannerOrange(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_BANNER_ORANGE);
        $sheet->getStyle($cellRange)->getFont()->setName('Times New Roman')->setSize(10.5)->setBold(true)->getColor()->setARGB('FF000000');
        $sheet->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($cellRange)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFEA580C'],
                ],
            ],
        ]);
    }

    /**
     * Berikan gaya Header Tabel Umum.
     */
    private function styleTableHeader(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->getFont()->setName('Times New Roman')->setSize(10)->setBold(true)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle($cellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($cellRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_TABLE_HEADER_BG);
        $sheet->getStyle($cellRange)->applyFromArray($this->thinBorderArray());
    }

    /**
     * Buat Kartu Dashboard / Ringkasan Eksekutif KPI (Nilai Tertinggi, Terendah, % Kelulusan)
     */
    private function appendKpiSummaryCard(
        Worksheet $sheet,
        int $currRow,
        int $maxCols,
        int $totalStudents,
        int $passedCount,
        float $passRate,
        ?float $avgScore,
        ?float $maxScore,
        ?float $minScore,
        float $threshold,
        string $codeLabel
    ): int {
        $lastColLetter = Coordinate::stringFromColumnIndex($maxCols);
        $failedCount = max(0, $totalStudents - $passedCount);
        $failRate = round(100.0 - $passRate, 1);

        $currRow++;
        $sheet->getRowDimension($currRow)->setRowHeight(12); // Spacer

        $currRow++;
        // Header Ringkasan Eksekutif
        $sheet->mergeCells("A{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'RINGKASAN EKSEKUTIF CAPAIAN & STATISTIK KELAS (' . $codeLabel . ')');
        $sheet->getStyle("A{$currRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$currRow}:{$lastColLetter}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_DARK_CHARCOAL);
        $sheet->getRowDimension($currRow)->setRowHeight(22);

        $splitIdx = (int) max(4, ceil($maxCols / 2));
        $splitLet = Coordinate::stringFromColumnIndex($splitIdx);
        $splitValStart = Coordinate::stringFromColumnIndex($splitIdx + 1);

        // KPI Row 1
        $currRow++;
        $sheet->mergeCells("A{$currRow}:B{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'Total Mahasiswa');
        $leftValCol = Coordinate::stringFromColumnIndex($splitIdx - 1);
        $sheet->mergeCells("C{$currRow}:{$leftValCol}{$currRow}");
        $sheet->setCellValue("C{$currRow}", "{$totalStudents} Mahasiswa");

        $sheet->setCellValue("{$splitLet}{$currRow}", 'Rata-rata Nilai Kelas');
        $sheet->mergeCells("{$splitValStart}{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("{$splitValStart}{$currRow}", $avgScore !== null ? number_format($avgScore, 1) : '—');

        $this->styleKpiRow($sheet, $currRow, $leftValCol, $splitLet, $splitValStart, $lastColLetter);

        // KPI Row 2
        $currRow++;
        $sheet->mergeCells("A{$currRow}:B{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'Mahasiswa Tuntas (Lulus)');
        $sheet->mergeCells("C{$currRow}:{$leftValCol}{$currRow}");
        $sheet->setCellValue("C{$currRow}", "{$passedCount} Mahasiswa ({$passRate}%)");
        $sheet->getStyle("C{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_PASS_TEXT);
        $sheet->getStyle("C{$currRow}:{$leftValCol}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_PASS_BG);

        $sheet->setCellValue("{$splitLet}{$currRow}", 'Nilai Tertinggi (Maksimum)');
        $sheet->mergeCells("{$splitValStart}{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("{$splitValStart}{$currRow}", $maxScore !== null ? number_format($maxScore, 1) : '—');

        $this->styleKpiRow($sheet, $currRow, $leftValCol, $splitLet, $splitValStart, $lastColLetter);

        // KPI Row 3
        $currRow++;
        $sheet->mergeCells("A{$currRow}:B{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'Mahasiswa Belum Tuntas');
        $sheet->mergeCells("C{$currRow}:{$leftValCol}{$currRow}");
        $sheet->setCellValue("C{$currRow}", "{$failedCount} Mahasiswa ({$failRate}%)");
        $sheet->getStyle("C{$currRow}")->getFont()->setBold(true)->getColor()->setARGB(self::COLOR_FAIL_TEXT);
        $sheet->getStyle("C{$currRow}:{$leftValCol}{$currRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::COLOR_FAIL_BG);

        $sheet->setCellValue("{$splitLet}{$currRow}", 'Nilai Terendah (Minimum)');
        $sheet->mergeCells("{$splitValStart}{$currRow}:{$lastColLetter}{$currRow}");
        $sheet->setCellValue("{$splitValStart}{$currRow}", $minScore !== null ? number_format($minScore, 1) : '—');

        $this->styleKpiRow($sheet, $currRow, $leftValCol, $splitLet, $splitValStart, $lastColLetter);

        // KPI Row 4
        $currRow++;
        $sheet->mergeCells("A{$currRow}:B{$currRow}");
        $sheet->setCellValue("A{$currRow}", 'Standar Kelulusan Minimum');
        $sheet->mergeCells("C{$currRow}:{$leftValCol}{$currRow}");
        $sheet->setCellValue("C{$currRow}", "≥ {$threshold} (Skala 100)");

        $sheet->setCellValue("{$splitLet}{$currRow}", 'Status Pencapaian Kelas');
        $sheet->mergeCells("{$splitValStart}{$currRow}:{$lastColLetter}{$currRow}");
        $statusEvaluation = $passRate >= 75.0 ? 'Sangat Baik (Memenuhi Standar OBE)' : ($passRate >= 60.0 ? 'Cukup (Perlu Pemantauan)' : 'Perlu Remediasi Intensif');
        $sheet->setCellValue("{$splitValStart}{$currRow}", $statusEvaluation);

        $this->styleKpiRow($sheet, $currRow, $leftValCol, $splitLet, $splitValStart, $lastColLetter);

        return $currRow;
    }

    private function styleKpiRow(Worksheet $sheet, int $r, string $leftValCol, string $splitLet, string $splitValStart, string $lastColLetter): void
    {
        $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")->applyFromArray($this->thinBorderArray());
        $sheet->getStyle("A{$r}")->getFont()->setName('Times New Roman')->setSize(9.5)->setBold(true)->getColor()->setARGB('FF334155');
        $sheet->getStyle("{$splitLet}{$r}")->getFont()->setName('Times New Roman')->setSize(9.5)->setBold(true)->getColor()->setARGB('FF334155');
        $sheet->getStyle("C{$r}")->getFont()->setName('Times New Roman')->setSize(9.5)->setBold(true);
        $sheet->getStyle("{$splitValStart}{$r}")->getFont()->setName('Times New Roman')->setSize(9.5)->setBold(true);

        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$splitValStart}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(19);
    }

    /**
     * Tambahkan blok tanda tangan pengesahan dosen di bawah tabel dengan format akademik formal.
     */
    private function appendSignBlock(Worksheet $sheet, ClassSection $section, int $currRow, int $maxCols): void
    {
        $signRow = $currRow + 3;
        $signCol = (int) max(1, $maxCols - 2);
        $signLet = Coordinate::stringFromColumnIndex($signCol);
        $lastColLet = Coordinate::stringFromColumnIndex($maxCols);

        // Catatan legalitas di sisi kiri
        $sheet->setCellValue("A{$signRow}", 'Catatan & Legalitas Dokumen:');
        $sheet->getStyle("A{$signRow}")->getFont()->setName('Times New Roman')->setSize(9)->setBold(true)->getColor()->setARGB('FF475569');
        $sheet->setCellValue("A" . ($signRow + 1), '1. Rekapitulasi nilai ini sah dan terintegrasi langsung dengan Outcome-Based Education (OBE) SALE.');
        $sheet->setCellValue("A" . ($signRow + 2), '2. Nilai capaian dihitung otomatis berdasarkan pemetaan pembobotan asesmen pada RPS.');
        $sheet->setCellValue("A" . ($signRow + 3), '3. Mahasiswa yang belum tuntas direkomendasikan mengikuti program remedial atau evaluasi tambahan.');
        $sheet->getStyle("A" . ($signRow + 1) . ":A" . ($signRow + 3))->getFont()->setName('Times New Roman')->setSize(8.5)->getColor()->setARGB('FF64748B');

        // Tanda tangan di sisi kanan
        $todayStr = now()->locale('id')->translatedFormat('d F Y');
        $sheet->mergeCells("{$signLet}{$signRow}:{$lastColLet}{$signRow}");
        $sheet->setCellValue("{$signLet}{$signRow}", 'Dosen Pengampu Mata Kuliah,');
        $sheet->getStyle("{$signLet}{$signRow}")->getFont()->setName('Times New Roman')->setSize(10)->getColor()->setARGB('FF334155');
        $sheet->getStyle("{$signLet}{$signRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $dosenName = $section->dosen?->name ?? '—';
        $dosenNidn = $section->dosen?->nim_nidn ? 'NIP/NIDN. ' . $section->dosen->nim_nidn : '';

        $nameRow = $signRow + 4;
        $sheet->mergeCells("{$signLet}{$nameRow}:{$lastColLet}{$nameRow}");
        $sheet->setCellValue("{$signLet}{$nameRow}", '( ' . $dosenName . ' )');
        $sheet->getStyle("{$signLet}{$nameRow}")->getFont()->setName('Times New Roman')->setSize(10)->setBold(true)->setUnderline(true);
        $sheet->getStyle("{$signLet}{$nameRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($dosenNidn) {
            $nidnRow = $nameRow + 1;
            $sheet->mergeCells("{$signLet}{$nidnRow}:{$lastColLet}{$nidnRow}");
            $sheet->setCellValue("{$signLet}{$nidnRow}", $dosenNidn);
            $sheet->getStyle("{$signLet}{$nidnRow}")->getFont()->setName('Times New Roman')->setSize(9.5)->getColor()->setARGB('FF64748B');
            $sheet->getStyle("{$signLet}{$nidnRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    /**
     * Terapkan lebar kolom eksplisit yang proporsional dan presisi tanpa gangguan autoSize yang menggelembung.
     */
    private function applyExplicitColumnWidths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth($w);
        }
    }

    /**
     * Konfigurasi Tata Letak Cetak (Landscape, A4, Fit to Page) - TANPA freezePane agar tidak terjadi split window/double header.
     */
    private function setupPageAndPrint(Worksheet $sheet): void
    {
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        // CATATAN: freezePane sengaja TIDAK digunakan untuk mencegah bug window split / tampilan 2 header di Excel/WPS.
    }

    private function thinBorderArray(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_GRAY],
                ],
            ],
        ];
    }

    private function doubleBottomBorderArray(): array
    {
        return [
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_DARK],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => self::COLOR_BORDER_DARK],
                ],
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_GRAY],
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_GRAY],
                ],
            ],
        ];
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

    private function gradeBadgeColors(string $grade): array
    {
        return match ($grade) {
            'A' => ['bg' => self::COLOR_GRADE_A_BG, 'text' => self::COLOR_GRADE_A_TEXT],
            'AB', 'B' => ['bg' => self::COLOR_GRADE_B_BG, 'text' => self::COLOR_GRADE_B_TEXT],
            'BC', 'C' => ['bg' => self::COLOR_GRADE_C_BG, 'text' => self::COLOR_GRADE_C_TEXT],
            default => ['bg' => self::COLOR_FAIL_BG, 'text' => self::COLOR_FAIL_TEXT],
        };
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
