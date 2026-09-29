<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanProdiController extends AdminProdiController
{
    public function __construct(private ObeCalculationService $obe) {}

    public function index(Request $request): View
    {
        $data = $this->prepareReportData($request);

        return view('admin-prodi.laporan.index', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->prepareReportData($request);

        $activeProdi    = $data['activeProdi'];
        $activeSemester = $data['activeSemester'];
        $safeProdi = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $activeProdi?->code ?? 'PRODI');
        $safeSem   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $activeSemester?->code ?? 'SEM');
        $fileName  = "laporan-prodi-{$safeProdi}-{$safeSem}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Prodi');

        $borderThin   = ['borderStyle' => Border::BORDER_THIN,   'color' => ['argb' => 'FF000000']];
        $borderMedium = ['borderStyle' => Border::BORDER_MEDIUM,  'color' => ['argb' => 'FF000000']];
        $colLetters   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        // ── Baris 1: Judul ────────────────────────────────────────────────────
        $sheet->setCellValue('A1', 'LAPORAN AKADEMIK & CAPAIAN PROGRAM STUDI');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '102F50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => $borderMedium],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Baris 2–4: Info dokumen ───────────────────────────────────────────
        $sheet->setCellValue('A2', 'Program Studi: ' . ($activeProdi?->name ?? 'Semua') . ' (' . ($activeProdi?->code ?? '-') . ')');
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A3', 'Semester: ' . ($activeSemester?->name ?? 'Semua') . ' (' . ($activeSemester?->code ?? '-') . ')');
        $sheet->mergeCells('A3:J3');
        $sheet->setCellValue('A4', 'Tanggal Ekspor: ' . now()->translatedFormat('d F Y, H:i'));
        $sheet->mergeCells('A4:J4');

        foreach ([2, 3, 4] as $rInfo) {
            $sheet->getStyle("A{$rInfo}:J{$rInfo}")->applyFromArray([
                'font'    => ['bold' => ($rInfo === 2)],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                'borders' => ['allBorders' => $borderThin],
            ]);
            $sheet->getRowDimension($rInfo)->setRowHeight(20);
        }

        // ── Baris 6: Sub-judul Ringkasan ─────────────────────────────────────
        $r = 6;
        $sheet->setCellValue('A' . $r, 'RINGKASAN METRIK SEMESTER');
        $sheet->mergeCells('A' . $r . ':J' . $r);
        $sheet->getStyle('A' . $r)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'borders'   => ['outline' => $borderMedium],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(22);

        // ── Baris 7: Header ringkasan ──────────────────────────────────────────
        $r = 7;
        $sheet->setCellValue('A' . $r, 'Indikator');
        $sheet->setCellValue('B' . $r, 'Nilai');
        $sheet->mergeCells('B' . $r . ':J' . $r);
        $sheet->getStyle('A' . $r . ':J' . $r)->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders'   => ['allBorders' => $borderThin],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(20);

        // ── Baris 8–12: Data ringkasan (5 metrik) ───────────────────────────
        $metricsData = [
            ['Total Dosen Homebase / Pengampu',        $data['metrics']['total_dosen']    . ' Orang'],
            ['Total Mahasiswa Terdaftar di Prodi',      $data['metrics']['total_mahasiswa'] . ' Orang'],
            ['Mahasiswa Baru Masuk Semester Ini',       $data['metrics']['mahasiswa_baru']  . ' Orang'],
            ['Total Kelas Perkuliahan Aktif',           $data['metrics']['total_kelas']     . ' Kelas'],
            ['Rata-rata Nilai Mahasiswa (Skala 0-100)',
                $data['metrics']['average_grade'] !== null
                    ? number_format($data['metrics']['average_grade'], 2)
                    : 'Belum ada nilai diinput'],
        ];
        $r = 8;
        foreach ($metricsData as [$label, $val]) {
            $sheet->setCellValue('A' . $r, $label);
            $sheet->setCellValue('B' . $r, $val);
            $sheet->mergeCells('B' . $r . ':J' . $r);
            $sheet->getStyle('A' . $r)->applyFromArray([
                'borders' => ['allBorders' => $borderThin],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('B' . $r . ':J' . $r)->applyFromArray([
                'borders' => ['allBorders' => $borderThin],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        // ── Baris 13 & 14 kosong ──
        // ── Baris 15: Sub-judul tabel kelas ──────────────────────────────────
        $r = 15;
        $sheet->setCellValue('A' . $r, 'RINCIAN KELAS PERKULIAHAN & RATA-RATA NILAI');
        $sheet->mergeCells('A' . $r . ':J' . $r);
        $sheet->getStyle('A' . $r)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'borders'   => ['outline' => $borderMedium],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(22);

        // ── Baris 16: Header tabel kelas ─────────────────────────────────────
        $r = 16;
        $tableHeaderRow = $r;
        $headers = ['No', 'Kode MK', 'Nama Mata Kuliah', 'SKS', 'Kode Kelas', 'Dosen Ketua', 'Dosen Wakil', 'Jml Mhs', 'Jml Asesmen', 'Rata-rata Nilai'];
        foreach ($headers as $k => $h) {
            $sheet->setCellValue($colLetters[$k] . $r, $h);
        }
        $sheet->getStyle("A{$r}:J{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'borders'   => ['allBorders' => $borderThin],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(26);

        // ── Data baris kelas ──────────────────────────────────────────────────
        $r++;
        $no = 1;
        foreach ($data['classReports'] as $cr) {
            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValue('B' . $r, $cr['mk_code']);
            $sheet->setCellValue('C' . $r, $cr['mk_name']);
            $sheet->setCellValue('D' . $r, $cr['sks']);
            $sheet->setCellValue('E' . $r, $cr['section_code']);
            $sheet->setCellValue('F' . $r, $cr['dosen_ketua']);
            $sheet->setCellValue('G' . $r, $cr['dosen_wakil'] !== '-' ? $cr['dosen_wakil'] : '');
            $sheet->setCellValue('H' . $r, $cr['students_count']);
            $sheet->setCellValue('I' . $r, $cr['assessments_count']);
            $sheet->setCellValue('J' . $r,
                $cr['class_average'] !== null
                    ? number_format($cr['class_average'], 2)
                    : 'Belum dinilai'
            );
            $bgZebra = ($no % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$r}:J{$r}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgZebra]],
                'borders'   => ['allBorders' => $borderThin],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            // Center kolom angka
            $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}:E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$r}:J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        // ── Baris total ───────────────────────────────────────────────────────
        if ($no > 1) {
            $sheet->setCellValue('A' . $r, 'TOTAL');
            $sheet->mergeCells('A' . $r . ':G' . $r);
            $sheet->setCellValue('H' . $r, array_sum(array_column($data['classReports'], 'students_count')));
            $sheet->setCellValue('I' . $r, array_sum(array_column($data['classReports'], 'assessments_count')));
            $sheet->setCellValue('J' . $r, count($data['classReports']) . ' Kelas');
            $sheet->getStyle("A{$r}:J{$r}")->applyFromArray([
                'font'      => ['bold' => true],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                'borders'   => ['allBorders' => $borderThin, 'outline' => $borderMedium],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // ── Lebar kolom tetap ──────────────────────────────────────────────────
        $colWidths = [5, 12, 32, 5, 10, 26, 24, 10, 12, 14];
        foreach ($colLetters as $k => $col) {
            $sheet->getColumnDimension($col)->setWidth($colWidths[$k]);
        }

        // ── Freeze pane di bawah header tabel ────────────────────────────────
        $sheet->freezePane('A' . ($tableHeaderRow + 1));

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }


    public function print(Request $request): View
    {
        $data = $this->prepareReportData($request);

        return view('admin-prodi.laporan.print', $data);
    }

    private function prepareReportData(Request $request): array
    {
        $prodis = $this->allowedProdis();
        $activeProdi = $this->resolveActiveProdi($request);

        $semesters = Semester::orderByDesc('id')->get();
        $selectedSemesterId = $request->integer('semester_id') ?: ($semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id ?? 0);
        $activeSemester = $semesters->firstWhere('id', $selectedSemesterId) ?? $semesters->first();

        $prodiId = $activeProdi?->id;
        $semesterId = $activeSemester?->id;

        // 1. Dosen count: total dosen prodi + dosen pengampu kelas di prodi semester ini
        $dosenHomebaseCount = User::withRoleName(Role::DOSEN)
            ->where(fn ($q) => $q->where('prodi_id', $prodiId)->orWhere('managing_prodi_id', $prodiId))
            ->count();

        // 2. Mahasiswa count di prodi
        $mahasiswaTotalCount = User::withRoleName(Role::MAHASISWA)
            ->where('prodi_id', $prodiId)
            ->count();

        // 3. Mahasiswa Baru Masuk per Semester (intake)
        // Kita hitung mahasiswa yang terdaftar di prodi yang masuk pada semester ini (atau semester year)
        $semesterCodeYear = substr($activeSemester?->code ?? '', 0, 4);
        $mahasiswaBaruCount = User::withRoleName(Role::MAHASISWA)
            ->where('prodi_id', $prodiId)
            ->where(function ($q) use ($semesterCodeYear) {
                if ($semesterCodeYear) {
                    $q->where('nim_nidn', 'like', $semesterCodeYear.'%')
                        ->orWhereYear('created_at', (int) $semesterCodeYear);
                }
            })
            ->count();

        // 4. Kelas-kelas di bawah prodi & semester ini
        $classes = ClassSection::query()
            ->when($prodiId, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId)))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->with(['mataKuliah', 'dosen', 'dosenPendamping', 'assessments', 'students'])
            ->withCount(['students', 'assessments'])
            ->get();

        $totalClassCount = $classes->count();

        // 5. Rata-rata Nilai Mahasiswa per Semester
        $classReports = [];
        $allStudentClassGrades = [];

        foreach ($classes as $section) {
            $assessmentIds = $section->assessments->pluck('id');
            $studentScores = StudentAssessmentScore::whereIn('assessment_id', $assessmentIds)
                ->whereNotNull('score')
                ->get();

            // Hitung rata-rata per mahasiswa di kelas ini
            $studentsInClass = $section->students;
            $classScoresList = [];

            foreach ($studentsInClass as $student) {
                $scoresForStudent = $studentScores->where('mahasiswa_id', $student->id);
                if ($scoresForStudent->isNotEmpty()) {
                    $avg = $scoresForStudent->avg('score');
                    $classScoresList[] = $avg;
                    $allStudentClassGrades[] = $avg;
                }
            }

            $classAvg = ! empty($classScoresList) ? round(array_sum($classScoresList) / count($classScoresList), 2) : null;

            $classReports[] = [
                'section_id' => $section->id,
                'mk_code' => $section->mataKuliah->code,
                'mk_name' => $section->mataKuliah->name,
                'semester_paket' => $section->mataKuliah->semester_paket,
                'sks' => $section->mataKuliah->sks,
                'section_code' => $section->section_code,
                'dosen_ketua' => $section->dosen?->name ?? '-',
                'dosen_wakil' => $section->dosenPendamping?->name ?? '-',
                'students_count' => $section->students_count,
                'assessments_count' => $section->assessments_count,
                'class_average' => $classAvg,
            ];
        }

        $overallAverage = ! empty($allStudentClassGrades)
            ? round(array_sum($allStudentClassGrades) / count($allStudentClassGrades), 2)
            : null;

        $metrics = [
            'total_dosen' => $dosenHomebaseCount,
            'total_mahasiswa' => $mahasiswaTotalCount,
            'mahasiswa_baru' => $mahasiswaBaruCount,
            'total_kelas' => $totalClassCount,
            'average_grade' => $overallAverage,
        ];

        return compact(
            'prodis',
            'activeProdi',
            'semesters',
            'activeSemester',
            'metrics',
            'classReports'
        );
    }

    /** @return array<int, string> */
    private function sanitizeCsvRow(array $row): array
    {
        return array_map(function (mixed $value): string {
            $string = (string) $value;

            return $string !== '' && in_array($string[0], ['=', '+', '-', '@', "\t", "\r"], true)
                ? "'".$string
                : $string;
        }, $row);
    }
}
