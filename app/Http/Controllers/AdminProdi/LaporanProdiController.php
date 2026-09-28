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

        $activeProdi = $data['activeProdi'];
        $activeSemester = $data['activeSemester'];
        $safeProdi = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $activeProdi?->code ?? 'PRODI');
        $safeSem = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $activeSemester?->code ?? 'SEM');
        $fileName = "laporan-prodi-{$safeProdi}-{$safeSem}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Prodi');

        // Header Dokumen
        $sheet->setCellValue('A1', 'LAPORAN AKADEMIK & CAPAIAN PROGRAM STUDI');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Program Studi: ' . ($data['activeProdi']?->name ?? 'Semua') . ' (' . ($data['activeProdi']?->code ?? '-') . ')');
        $sheet->setCellValue('A3', 'Semester: ' . ($data['activeSemester']?->name ?? 'Semua') . ' (' . ($data['activeSemester']?->code ?? '-') . ')');
        $sheet->setCellValue('A4', 'Tanggal Ekspor: ' . now()->translatedFormat('d F Y, H:i'));

        // Seksi 1: Ringkasan Metrik
        $sheet->setCellValue('A6', 'RINGKASAN METRIK SEMESTER');
        $sheet->getStyle('A6')->getFont()->setBold(true);

        $sheet->setCellValue('A7', 'Indikator');
        $sheet->setCellValue('B7', 'Nilai');
        $sheet->getStyle('A7:B7')->getFont()->setBold(true);
        $sheet->getStyle('A7:B7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

        $metrics = [
            ['Total Dosen Homebase / Pengampu', $data['metrics']['total_dosen']],
            ['Total Mahasiswa Terdaftar di Prodi', $data['metrics']['total_mahasiswa']],
            ['Mahasiswa Baru Masuk Semester Ini', $data['metrics']['mahasiswa_baru']],
            ['Total Kelas Perkuliahan Aktif', $data['metrics']['total_kelas']],
            ['Rata-rata Nilai Mahasiswa (Skala 0-100)', $data['metrics']['average_grade'] !== null ? number_format($data['metrics']['average_grade'], 2) : 'Belum ada nilai'],
        ];

        $rowIdx = 8;
        foreach ($metrics as $m) {
            $sheet->setCellValue('A' . $rowIdx, $m[0]);
            $sheet->setCellValue('B' . $rowIdx, $m[1]);
            $rowIdx++;
        }

        // Seksi 2: Rincian Kelas Perkuliahan
        $rowIdx += 2;
        $sheet->setCellValue('A' . $rowIdx, 'RINCIAN KELAS PERKULIAHAN & RATA-RATA NILAI');
        $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);

        $rowIdx++;
        $headers = ['No', 'Kode MK', 'Nama Mata Kuliah', 'SKS', 'Kelas', 'Dosen Ketua', 'Dosen Wakil', 'Mahasiswa', 'Asesmen', 'Rata-rata Nilai'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($headers as $k => $h) {
            $sheet->setCellValue($cols[$k] . $rowIdx, $h);
        }
        $sheet->getStyle("A{$rowIdx}:J{$rowIdx}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowIdx}:J{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

        $rowIdx++;
        $no = 1;
        foreach ($data['classReports'] as $cr) {
            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValue('B' . $rowIdx, $cr['mk_code']);
            $sheet->setCellValue('C' . $rowIdx, $cr['mk_name']);
            $sheet->setCellValue('D' . $rowIdx, $cr['sks']);
            $sheet->setCellValue('E' . $rowIdx, $cr['section_code']);
            $sheet->setCellValue('F' . $rowIdx, $cr['dosen_ketua']);
            $sheet->setCellValue('G' . $rowIdx, $cr['dosen_wakil']);
            $sheet->setCellValue('H' . $rowIdx, $cr['students_count']);
            $sheet->setCellValue('I' . $rowIdx, $cr['assessments_count']);
            $sheet->setCellValue('J' . $rowIdx, $cr['class_average'] !== null ? number_format($cr['class_average'], 2) : 'Belum dinilai');
            $rowIdx++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
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
