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

        // Set font formal Times New Roman
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);

        $borderThin   = ['borderStyle' => Border::BORDER_THIN,   'color' => ['argb' => 'FF000000']];
        $borderMedium = ['borderStyle' => Border::BORDER_MEDIUM,  'color' => ['argb' => 'FF000000']];
        $colLetters   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        // ── Baris 1: Judul ────────────────────────────────────────────────────
        $appN = \App\Models\SystemSetting::appName();
        $instN = \App\Models\SystemSetting::valueFor('institution', '');
        $excelTitle = $instN
            ? mb_strtoupper($instN, 'UTF-8') . ' - LAPORAN AKADEMIK & CAPAIAN PROGRAM STUDI'
            : 'LAPORAN AKADEMIK & CAPAIAN PROGRAM STUDI';
        $sheet->setCellValue('A1', $excelTitle);
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => $borderMedium],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Baris 2–4: Info dokumen ───────────────────────────────────────────
        $sheet->setCellValue('A2', 'Program Studi: ' . ($activeProdi?->name ?? 'Semua') . ' (' . ($activeProdi?->code ?? '-') . ')');
        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A3', 'Semester: ' . ($activeSemester?->name ?? 'Semua') . ' (' . ($activeSemester?->code ?? '-') . ')');
        $sheet->mergeCells('A3:I3');
        $sheet->setCellValue('A4', 'Tanggal Ekspor: ' . now()->translatedFormat('d F Y, H:i'));
        $sheet->mergeCells('A4:I4');

        foreach ([2, 3, 4] as $rInfo) {
            $sheet->getStyle("A{$rInfo}:I{$rInfo}")->applyFromArray([
                'font'    => ['bold' => ($rInfo === 2)],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                'borders' => ['allBorders' => $borderThin],
            ]);
            $sheet->getRowDimension($rInfo)->setRowHeight(20);
        }

        // ── Baris 6: Sub-judul Ringkasan ─────────────────────────────────────
        $r = 6;
        $sheet->setCellValue('A' . $r, 'RINGKASAN METRIK SEMESTER');
        $sheet->mergeCells('A' . $r . ':I' . $r);
        $sheet->getStyle('A' . $r)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders'   => ['outline' => $borderMedium],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(22);

        // ── Baris 7: Header ringkasan (Format Sesuai Gambar Referensi) ───────
        $r = 7;
        $sheet->setCellValue('A' . $r, 'NO');
        $sheet->setCellValue('B' . $r, 'INDIKATOR AKADEMIK');
        $sheet->mergeCells('B' . $r . ':F' . $r);
        $sheet->setCellValue('G' . $r, 'NILAI / CAPAIAN');
        $sheet->mergeCells('G' . $r . ':I' . $r);
        $sheet->getStyle('A' . $r . ':I' . $r)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders'   => ['allBorders' => $borderThin],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(22);

        // ── Baris 8–11: Data ringkasan (4 metrik) ───────────────────────────
        $metricsData = [
            ['Total Dosen Pengampu',                    $data['metrics']['total_dosen']    . ' Orang'],
            ['Total Mahasiswa Terdaftar (Aktif)',      $data['metrics']['total_mahasiswa'] . ' Orang'],
            ['Total Kelas Perkuliahan Aktif',           $data['metrics']['total_kelas']     . ' Kelas'],
            ['Rata-rata Nilai Mahasiswa (Skala 0-100)',
                $data['metrics']['average_grade'] !== null
                    ? number_format($data['metrics']['average_grade'], 2)
                    : 'Belum ada nilai diinput'],
        ];
        $r = 8;
        $noMetrik = 1;
        foreach ($metricsData as [$label, $val]) {
            $sheet->setCellValue('A' . $r, $noMetrik++);
            $sheet->setCellValue('B' . $r, $label);
            $sheet->mergeCells('B' . $r . ':F' . $r);
            $sheet->setCellValue('G' . $r, $val);
            $sheet->mergeCells('G' . $r . ':I' . $r);

            $sheet->getStyle('A' . $r)->applyFromArray([
                'borders'   => ['allBorders' => $borderThin],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('B' . $r . ':F' . $r)->applyFromArray([
                'borders'   => ['allBorders' => $borderThin],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('G' . $r . ':I' . $r)->applyFromArray([
                'borders'   => ['allBorders' => $borderThin],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // ── Baris pemisah ──
        $r += 1;
        // ── Sub-judul tabel kelas ──────────────────────────────────────────
        $sheet->setCellValue('A' . $r, 'RINCIAN KELAS PERKULIAHAN & RATA-RATA NILAI');
        $sheet->mergeCells('A' . $r . ':I' . $r);
        $sheet->getStyle('A' . $r)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders'   => ['outline' => $borderMedium],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(22);

        // ── Header tabel kelas ─────────────────────────────────────────────
        $r++;
        $tableHeaderRow = $r;
        $headers = ['No', 'Kode MK', 'Nama Mata Kuliah', 'SKS', 'Kode Kelas', 'Dosen Ketua', 'Dosen Anggota', 'Jml Mhs', 'Rata-rata Nilai'];
        foreach ($headers as $k => $h) {
            $sheet->setCellValue($colLetters[$k] . $r, $h);
        }
        $sheet->getStyle("A{$r}:I{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders'   => ['allBorders' => $borderThin],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(26);

        // ── Data baris kelas ──────────────────────────────────────────────────
        $r++;
        $no = 1;
        foreach ($data['classReports'] as $cr) {
            $hasAnggota = !empty($cr['dosen_wakil']) && $cr['dosen_wakil'] !== '-';
            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValue('B' . $r, $cr['mk_code']);
            $sheet->setCellValue('C' . $r, $cr['mk_name']);
            $sheet->setCellValue('D' . $r, $cr['sks']);
            $sheet->setCellValue('E' . $r, $cr['section_code']);
            $sheet->setCellValue('F' . $r, $cr['dosen_ketua']);
            $sheet->setCellValue('G' . $r, $hasAnggota ? $cr['dosen_wakil'] : '-');
            $sheet->setCellValue('H' . $r, $cr['students_count']);
            $sheet->setCellValue('I' . $r,
                $cr['class_average'] !== null
                    ? number_format($cr['class_average'], 2)
                    : 'Belum dinilai'
            );
            $sheet->getStyle("A{$r}:I{$r}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => $borderThin],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            // Center kolom angka & tanda strip
            $sheet->getStyle("A{$r}:B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}:E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if (! $hasAnggota) {
                $sheet->getStyle("G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $sheet->getStyle("H{$r}:I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        // ── Baris total / rata-rata ───────────────────────────────────────────
        if ($no > 1) {
            $sheet->setCellValue('A' . $r, 'RATA-RATA NILAI MAHASISWA');
            $sheet->mergeCells('A' . $r . ':H' . $r);
            $sheet->setCellValue('I' . $r,
                $data['metrics']['average_grade'] !== null
                    ? number_format($data['metrics']['average_grade'], 2)
                    : 'Belum dinilai'
            );
            $sheet->getStyle("A{$r}:I{$r}")->applyFromArray([
                'font'      => ['bold' => true],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'borders'   => ['allBorders' => $borderThin, 'outline' => $borderMedium],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // ── Ruang Tanda Tangan (Format tamplate.docx) ─────────────────────────
        $r += 2;
        $sheet->setCellValue("A{$r}", 'Mengetahui,');
        $sheet->setCellValue("G{$r}", 'Tanjungpinang, ' . now()->translatedFormat('d F Y'));
        $sheet->mergeCells("G{$r}:I{$r}");

        $r++;
        $sheet->setCellValue("A{$r}", 'Ketua Program Studi ' . ($activeProdi?->name ?? ''));
        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("G{$r}", 'Admin Program Studi ' . ($activeProdi?->code ?? ''));
        $sheet->mergeCells("G{$r}:I{$r}");

        $r += 4;
        $sheet->setCellValue("A{$r}", $data['kaprodiName']);
        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setUnderline(true);

        $sheet->setCellValue("G{$r}", $data['adminProdiName']);
        $sheet->mergeCells("G{$r}:I{$r}");
        $sheet->getStyle("G{$r}")->getFont()->setBold(true)->setUnderline(true);

        $r++;
        $sheet->setCellValue("A{$r}", 'NIP. ' . $data['kaprodiNip']);
        $sheet->mergeCells("A{$r}:D{$r}");

        $sheet->setCellValue("G{$r}", 'NIP/ID. ' . $data['adminProdiNip']);
        $sheet->mergeCells("G{$r}:I{$r}");

        // ── Catatan Kaki Elektronik (BSrE BSSN) ───────────────────────────────
        $r += 2;
        $sheet->setCellValue("A{$r}", 'Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara (BSSN).');
        $sheet->mergeCells("A{$r}:I{$r}");
        $sheet->getStyle("A{$r}")->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Lebar kolom tetap ──────────────────────────────────────────────────
        $colWidths = [6, 12, 34, 6, 10, 26, 24, 10, 16];
        foreach ($colLetters as $k => $col) {
            $sheet->getColumnDimension($col)->setWidth($colWidths[$k]);
        }

        // ── Pengaturan Tampilan & Cetak Lembar Kerja (Tanpa split pane / freeze agar tidak terduplikasi) ──
        $sheet->setSelectedCell('A1');
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

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
        $activeProdi = $this->resolveActiveProdi($request, true);

        $semesters = Semester::orderByDesc('id')->get();
        $selectedSemesterId = $request->integer('semester_id') ?: ($semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id ?? 0);
        $activeSemester = $semesters->firstWhere('id', $selectedSemesterId) ?? $semesters->first();

        $prodiId = $activeProdi?->id;
        $semesterId = $activeSemester?->id;

        // 1. Kelas-kelas di bawah prodi & semester ini
        $classes = ClassSection::query()
            ->when($prodiId, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId)))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->with(['mataKuliah', 'dosen', 'dosenPendamping', 'dosenAnggota', 'assessments', 'students'])
            ->withCount(['students', 'assessments'])
            ->get();

        $totalClassCount = $classes->count();

        // 2. Dosen pengampu unik di prodi semester ini (Ketua, Pendamping, atau Anggota dihitung 1 orang)
        $dosenIds = collect();
        foreach ($classes as $section) {
            if (! empty($section->dosen_id)) {
                $dosenIds->push((int) $section->dosen_id);
            }
            if (! empty($section->dosen_pendamping_id)) {
                $dosenIds->push((int) $section->dosen_pendamping_id);
            }
            if ($section->relationLoaded('dosenAnggota')) {
                foreach ($section->dosenAnggota as $anggota) {
                    if (! empty($anggota->id)) {
                        $dosenIds->push((int) $anggota->id);
                    }
                }
            }
        }
        $uniqueDosenCount = $dosenIds->unique()->values()->count();

        // 3. Mahasiswa terdaftar di prodi & semester ini secara unik (tanpa duplikasi antar kelas)
        $studentIdsFromClasses = $classes->flatMap(function ($section) {
            return $section->relationLoaded('students')
                ? $section->students->pluck('id')
                : collect();
        });

        $studentIdsFromProdi = User::withRoleName(Role::MAHASISWA)
            ->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))
            ->when($semesterId, fn ($q) => $q->whereHas('classSectionsEnrolled', fn ($sq) => $sq->where('semester_id', $semesterId)))
            ->pluck('id');

        $uniqueStudentIds = $studentIdsFromClasses
            ->concat($studentIdsFromProdi)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $mahasiswaTotalCount = $uniqueStudentIds->count();

        // 4. Mahasiswa Baru Masuk per Semester (intake)
        $targetYear = (int) ($activeSemester?->academic_year_start ?? substr($activeSemester?->code ?? '', 0, 4));
        $mahasiswaBaruCount = User::withRoleName(Role::MAHASISWA)
            ->where(function ($q) use ($prodiId, $uniqueStudentIds) {
                if ($prodiId) {
                    $q->where('prodi_id', $prodiId);
                }
                if ($uniqueStudentIds->isNotEmpty()) {
                    $prodiId ? $q->orWhereIn('id', $uniqueStudentIds) : $q->whereIn('id', $uniqueStudentIds);
                }
            })
            ->where(function ($q) use ($targetYear) {
                if ($targetYear > 0) {
                    $q->where('angkatan', $targetYear)
                        ->orWhere('nim_nidn', 'like', $targetYear.'%')
                        ->orWhereYear('created_at', $targetYear);
                }
            })
            ->count();

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

                $anggotaNames = $section->relationLoaded('dosenAnggota') && $section->dosenAnggota->isNotEmpty()
                    ? $section->dosenAnggota->pluck('name')->join(', ')
                    : ($section->dosenPendamping?->name ?? '-');

                $classReports[] = [
                    'section_id' => $section->id,
                    'mk_code' => $section->mataKuliah->code,
                    'mk_name' => $section->mataKuliah->name,
                    'semester_paket' => $section->mataKuliah->semester_paket,
                    'sks' => $section->mataKuliah->sks,
                    'section_code' => $section->section_code,
                    'dosen_ketua' => $section->dosen?->name ?? '-',
                    'dosen_wakil' => $anggotaNames,
                    'dosen_anggota' => $anggotaNames,
                    'students_count' => $section->students_count,
                    'assessments_count' => $section->assessments_count,
                    'class_average' => $classAvg,
                ];
        }

        $overallAverage = ! empty($allStudentClassGrades)
            ? round(array_sum($allStudentClassGrades) / count($allStudentClassGrades), 2)
            : null;

        $metrics = [
            'total_dosen' => $uniqueDosenCount,
            'total_mahasiswa' => $mahasiswaTotalCount,
            'mahasiswa_baru' => $mahasiswaBaruCount,
            'total_kelas' => $totalClassCount,
            'average_grade' => $overallAverage,
        ];

        // Resolusi Pejabat Penandatangan Laporan (Format tamplate.docx)
        $kaprodiMap = [
            'IF' => ['name' => 'Dr. Eng. Ahmad Zaki, M.Kom.', 'nip' => '198203152008121002'],
            'SI' => ['name' => 'Maya Kartika, S.Kom., M.T.', 'nip' => '198506222010122003'],
            'SK' => ['name' => 'Ir. Hendra Pratama, M.T.', 'nip' => '197911042005011001'],
        ];

        $dosenKaprodi = User::where('prodi_id', $prodiId)
            ->whereHas('role', fn ($q) => $q->where('name', Role::DOSEN))
            ->first();

        $kaprodiDefault = $kaprodiMap[$activeProdi?->code] ?? null;
        $kaprodiName = $kaprodiDefault['name'] ?? ($dosenKaprodi?->name ?? 'Dr. H. Kaprodi, M.T.');
        $kaprodiNip  = $kaprodiDefault['nip'] ?? ($dosenKaprodi?->nim_nidn ?? '197501012000031001');

        $adminProdiUser = auth()->user();
        $adminProdiName = $adminProdiUser?->name ?? ('Admin Prodi ' . ($activeProdi?->code ?? ''));
        $adminProdiNip  = $adminProdiUser?->nim_nidn ?? ('AP' . str_pad((string) ($activeProdi?->id ?? 1), 3, '0', STR_PAD_LEFT));

        return compact(
            'prodis',
            'activeProdi',
            'semesters',
            'activeSemester',
            'metrics',
            'classReports',
            'kaprodiName',
            'kaprodiNip',
            'adminProdiName',
            'adminProdiNip'
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
