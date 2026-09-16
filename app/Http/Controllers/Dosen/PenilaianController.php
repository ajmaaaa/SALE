<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenilaianController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman 2 — Dashboard Penilaian Kelas.
     */
    public function dashboard(ClassSection $section)
    {
        $this->authorizeOwnership($section);

        return redirect()->route('dosen.penilaian.rekap', $section);
    }

    /**
     * Tab: Rekap Keseluruhan.
     */
    public function rekap(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($section, $cpls) {
            $cplScores = $this->obe->cplScoresFor($cpls, $student->id);
            $final = $this->obe->finalScore($section, $student->id);

            return [
                'student' => $student,
                'cpl_scores' => $cplScores,
                'final_score' => $final['score'],
                'coverage' => $final['coverage'],
                'grade' => $this->gradeLetter($final['score']),
            ];
        });

        return view('dosen.rekap', [
            'section' => $this->withHeaderCounts($section, $cpls),
            'cpls' => $cpls,
            'rows' => $rows,
        ]);
    }

    /**
     * Tab: Matriks Penilaian.
     */
    public function matriks(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();

        return view('dosen.matriks', [
            'section' => $this->withHeaderCounts($section, null, $cpmks),
            'cpmks' => $cpmks,
            'assessments' => $assessments,
        ]);
    }

    /**
     * Tab: Daftar Asesmen.
     */
    public function asesmen(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();

        return view('dosen.penilaian.asesmen', [
            'section' => $this->withHeaderCounts($section),
            'assessments' => $assessments,
        ]);
    }

    /**
     * Tab: Rekap CPMK.
     */
    public function cpmk(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $students = $section->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($cpmks) {
            $scores = $this->obe->cpmkScoresFor($cpmks, $student->id);

            return [
                'student' => $student,
                'scores' => $scores,
            ];
        });

        return view('dosen.cpmk', [
            'section' => $this->withHeaderCounts($section, null, $cpmks),
            'cpmks' => $cpmks,
            'rows' => $rows,
        ]);
    }

    /**
     * Tab: Rekap CPL.
     */
    public function cpl(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($cpls) {
            $scores = $this->obe->cplScoresFor($cpls, $student->id);

            return [
                'student' => $student,
                'scores' => $scores,
            ];
        });

        return view('dosen.cpl', [
            'section' => $this->withHeaderCounts($section, $cpls),
            'cpls' => $cpls,
            'rows' => $rows,
        ]);
    }

    /**
     * Tab: Pengaturan Penilaian.
     */
    public function pengaturan(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $cpmks = $this->cpmksFor($section);
        $totalFinalWeight = $section->assessments()->sum('final_weight');

        return view('dosen.pengaturan', [
            'section' => $this->withHeaderCounts($section, $cpls, $cpmks),
            'cpls' => $cpls,
            'cpmks' => $cpmks,
            'totalFinalWeight' => (float) $totalFinalWeight,
        ]);
    }

    /**
     * Ekspor Rekap Nilai Keseluruhan ke CSV/Excel (Step 21).
     */
    public function exportRekap(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('nim_nidn')->orderBy('name')->get();

        $headers = ['No', 'NIM', 'Nama Mahasiswa'];
        foreach ($cpls as $cpl) {
            $headers[] = $cpl->code;
        }
        $headers[] = 'Nilai Akhir';
        $headers[] = 'Grade';
        $headers[] = 'Status';
        $headers[] = 'Coverage (%)';

        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $section->section_code);
        $fileName = "rekap-nilai-{$section->mataKuliah->code}-{$safeCode}.csv";

        return response()->streamDownload(function () use ($headers, $students, $cpls, $section) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);

            foreach ($students as $i => $student) {
                $cplScores = $this->obe->cplScoresFor($cpls, $student->id);
                $final = $this->obe->finalScore($section, $student->id);

                $status = 'Belum dinilai';
                if ($final['coverage'] < 100) {
                    $status = 'Provisional (' . $final['coverage'] . '%)';
                } elseif ($final['score'] !== null) {
                    $status = 'Final';
                }

                $row = [
                    $i + 1,
                    $student->nim_nidn ?? '',
                    $student->name,
                ];

                foreach ($cpls as $cpl) {
                    $cScore = $cplScores[$cpl->id];
                    $row[] = $cScore !== null ? number_format($cScore, 2) : '';
                }

                $row[] = $final['score'] !== null ? number_format($final['score'], 2) : '';
                $row[] = $this->gradeLetter($final['score']) ?? '';
                $row[] = $status;
                $row[] = $final['coverage'];

                $safeRow = array_map(function ($value) {
                    $str = (string) $value;
                    return preg_match('/^[=+@\-\t\r\n]/', $str) ? "'" . $str : $str;
                }, $row);

                fputcsv($file, $safeRow);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Ekspor Rekap CPMK ke CSV/Excel (Step 21).
     */
    public function exportCpmk(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $students = $section->students()->orderBy('nim_nidn')->orderBy('name')->get();

        $headers = ['No', 'NIM', 'Nama Mahasiswa'];
        foreach ($cpmks as $cpmk) {
            $headers[] = $cpmk->code . ' (Amb: ' . (float)$cpmk->threshold . ')';
        }

        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $section->section_code);
        $fileName = "rekap-cpmk-{$section->mataKuliah->code}-{$safeCode}.csv";

        return response()->streamDownload(function () use ($headers, $students, $cpmks) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);

            foreach ($students as $i => $student) {
                $scores = $this->obe->cpmkScoresFor($cpmks, $student->id);

                $row = [
                    $i + 1,
                    $student->nim_nidn ?? '',
                    $student->name,
                ];

                foreach ($cpmks as $cpmk) {
                    $score = $scores[$cpmk->id];
                    $row[] = $score !== null ? number_format($score, 2) : '';
                }

                $safeRow = array_map(function ($value) {
                    $str = (string) $value;
                    return preg_match('/^[=+@\-\t\r\n]/', $str) ? "'" . $str : $str;
                }, $row);

                fputcsv($file, $safeRow);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Ekspor Rekap CPL ke CSV/Excel (Step 21).
     */
    public function exportCpl(ClassSection $section): StreamedResponse
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('nim_nidn')->orderBy('name')->get();

        $headers = ['No', 'NIM', 'Nama Mahasiswa'];
        foreach ($cpls as $cpl) {
            $headers[] = $cpl->code;
        }
        $headers[] = 'Status Capaian';

        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $section->section_code);
        $fileName = "rekap-cpl-{$section->mataKuliah->code}-{$safeCode}.csv";

        return response()->streamDownload(function () use ($headers, $students, $cpls) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);

            foreach ($students as $i => $student) {
                $scores = $this->obe->cplScoresFor($cpls, $student->id);
                $anyNull = collect($scores)->contains(null);
                $allAchieved = ! $anyNull && collect($scores)->every(fn ($s) => $s >= 65);

                $status = 'Belum lengkap';
                if (! $anyNull) {
                    $status = $allAchieved ? 'Tercapai' : 'Belum Tercapai';
                }

                $row = [
                    $i + 1,
                    $student->nim_nidn ?? '',
                    $student->name,
                ];

                foreach ($cpls as $cpl) {
                    $score = $scores[$cpl->id];
                    $row[] = $score !== null ? number_format($score, 2) : '';
                }

                $row[] = $status;

                $safeRow = array_map(function ($value) {
                    $str = (string) $value;
                    return preg_match('/^[=+@\-\t\r\n]/', $str) ? "'" . $str : $str;
                }, $row);

                fputcsv($file, $safeRow);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Cetak / Print View Rekap Nilai PDF-Ready (Step 22).
     */
    public function printRekap(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('nim_nidn')->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($section, $cpls) {
            $cplScores = $this->obe->cplScoresFor($cpls, $student->id);
            $final = $this->obe->finalScore($section, $student->id);

            return [
                'student' => $student,
                'cpl_scores' => $cplScores,
                'final_score' => $final['score'],
                'coverage' => $final['coverage'],
                'grade' => $this->gradeLetter($final['score']),
            ];
        });

        return view('dosen.penilaian.cetak-rekap', [
            'section' => $this->withHeaderCounts($section, $cpls),
            'cpls' => $cpls,
            'rows' => $rows,
            'title' => 'Laporan Rekap Nilai & Capaian CPL',
        ]);
    }

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

    private function withHeaderCounts(ClassSection $section, $cpls = null, $cpmks = null): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);
        $section->cpmk_used_count = ($cpmks ?? $this->cpmksFor($section))->count();
        $section->cpl_used_count = ($cpls ?? $this->cplsFor($section))->count();

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

    /**
     * Data-ownership check fleksibel (Auth Laravel + Switch Account Session).
     */
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

        abort_unless($currentUserId && ($section->dosen_id === $currentUserId || $section->dosen_pendamping_id === $currentUserId), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }
}