<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\StudentRubricScore;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NilaiController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman Input Nilai Asesmen (Step 14).
     */
    public function index(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $assessment->load(['cpmks', 'rubric.criteria']);
        $students = $section->students()->orderBy('name')->get();

        $assessmentScores = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('mahasiswa_id');

        $rubricScores = collect();
        if ($assessment->uses_rubric && $assessment->rubric) {
            $criterionIds = $assessment->rubric->criteria->pluck('id');
            $rubricScores = StudentRubricScore::whereIn('rubric_criterion_id', $criterionIds)
                ->get()
                ->groupBy('mahasiswa_id')
                ->map(fn ($scores) => $scores->keyBy('rubric_criterion_id'));
        }

        return view('dosen.penilaian.input-nilai', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'students' => $students,
            'assessmentScores' => $assessmentScores,
            'rubricScores' => $rubricScores,
            'criteria' => $assessment->uses_rubric && $assessment->rubric ? $assessment->rubric->criteria : collect(),
        ]);
    }

    /**
     * Simpan Input Nilai Asesmen (Step 14).
     */
    public function store(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $enrolledStudents = $section->students()->pluck('users.id');
        $feedbacks = $request->input('feedback', []);
        $graderId = Auth::guard('web')->id() ?? $section->dosen_id;

        if ($assessment->uses_rubric && $assessment->rubric) {
            $criteria = $assessment->rubric->criteria->keyBy('id');
            $rawScores = $request->input('rubric_scores', []);

            // Validasi skor rubrik
            foreach ($rawScores as $studentId => $critScores) {
                if (! $enrolledStudents->contains((int) $studentId)) {
                    return back()->withErrors(['scores' => 'Mahasiswa tidak terdaftar pada kelas ini.'])->withInput();
                }

                if (is_array($critScores)) {
                    foreach ($critScores as $critId => $scoreVal) {
                        if (! $criteria->has((int) $critId)) {
                            return back()->withErrors(['scores' => 'Kriteria rubrik tidak valid untuk asesmen ini.'])->withInput();
                        }

                        if ($scoreVal !== null && $scoreVal !== '') {
                            $max = $criteria->get((int) $critId)->max_score ?: 100;
                            if (! is_numeric($scoreVal) || $scoreVal < 0 || $scoreVal > $max) {
                                return back()->withErrors(['scores' => "Skor kriteria harus antara 0 dan {$max}."])->withInput();
                            }
                        }
                    }
                }
            }

            DB::transaction(function () use ($enrolledStudents, $criteria, $rawScores, $feedbacks, $assessment, $graderId) {
                foreach ($enrolledStudents as $studentId) {
                    $studentScores = $rawScores[$studentId] ?? [];
                    $hasAnyGraded = false;

                    foreach ($criteria as $critId => $criterion) {
                        $val = $studentScores[$critId] ?? null;

                        if ($val !== null && $val !== '') {
                            StudentRubricScore::updateOrCreate(
                                ['rubric_criterion_id' => $critId, 'mahasiswa_id' => $studentId],
                                ['score' => (float) $val]
                            );
                            $hasAnyGraded = true;
                        } else {
                            StudentRubricScore::updateOrCreate(
                                ['rubric_criterion_id' => $critId, 'mahasiswa_id' => $studentId],
                                ['score' => null]
                            );
                        }
                    }

                    // Hitung nilai akhir asesmen dari rubrik melalui ObeCalculationService
                    $finalScore = $this->obe->rubricScore($assessment->rubric, $studentId);
                    $feedback = ! empty($feedbacks[$studentId]) ? trim($feedbacks[$studentId]) : null;

                    StudentAssessmentScore::updateOrCreate(
                        ['assessment_id' => $assessment->id, 'mahasiswa_id' => $studentId],
                        [
                            'score' => $finalScore,
                            'feedback' => $feedback,
                            'graded_by' => $graderId,
                            'graded_at' => $finalScore !== null ? now() : null,
                        ]
                    );
                }
            });
        } else {
            // Asesmen Non-Rubrik (Nilai Langsung)
            $rawScores = $request->input('scores', []);

            // Validasi
            foreach ($rawScores as $studentId => $scoreVal) {
                if (! $enrolledStudents->contains((int) $studentId)) {
                    return back()->withErrors(['scores' => 'Mahasiswa tidak terdaftar pada kelas ini.'])->withInput();
                }

                if ($scoreVal !== null && $scoreVal !== '') {
                    if (! is_numeric($scoreVal) || $scoreVal < 0 || $scoreVal > 100) {
                        return back()->withErrors(['scores' => 'Nilai asesmen harus berupa angka antara 0 dan 100.'])->withInput();
                    }
                }
            }

            DB::transaction(function () use ($enrolledStudents, $rawScores, $feedbacks, $assessment, $graderId) {
                foreach ($enrolledStudents as $studentId) {
                    $val = $rawScores[$studentId] ?? null;
                    $feedback = ! empty($feedbacks[$studentId]) ? trim($feedbacks[$studentId]) : null;

                    if ($val !== null && $val !== '') {
                        StudentAssessmentScore::updateOrCreate(
                            ['assessment_id' => $assessment->id, 'mahasiswa_id' => $studentId],
                            [
                                'score' => (float) $val,
                                'feedback' => $feedback,
                                'graded_by' => $graderId,
                                'graded_at' => now(),
                            ]
                        );
                    } else {
                        StudentAssessmentScore::updateOrCreate(
                            ['assessment_id' => $assessment->id, 'mahasiswa_id' => $studentId],
                            [
                                'score' => null,
                                'feedback' => $feedback,
                                'graded_by' => $graderId,
                                'graded_at' => null,
                            ]
                        );
                    }
                }
            });
        }

        return redirect()->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', "Nilai untuk asesmen \"{$assessment->name}\" berhasil disimpan.");
    }

    /**
     * Unduh Template Excel/CSV (Step 15).
     */
    public function downloadTemplate(ClassSection $section, Assessment $assessment): StreamedResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $students = $section->students()->orderBy('nim_nidn')->orderBy('name')->get();
        $isRubric = $assessment->uses_rubric && $assessment->rubric;

        $headers = ['NIM', 'Nama Mahasiswa'];
        if ($isRubric) {
            foreach ($assessment->rubric->criteria as $criterion) {
                $headers[] = $criterion->name . ' (' . rtrim(rtrim(number_format($criterion->weight, 2), '0'), '.') . '%)';
            }
        } else {
            $headers[] = 'Nilai (0-100)';
        }
        $headers[] = 'Catatan';

        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $assessment->code);
        $fileName = "template-nilai-{$section->section_code}-{$safeCode}.csv";

        return response()->streamDownload(function () use ($headers, $students, $isRubric, $assessment) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM agar dibuka sempurna di Microsoft Excel
            fputs($file, "\xEF\xBB\xBF");

            // Baris Header
            fputcsv($file, $headers);

            // Baris Mahasiswa
            foreach ($students as $student) {
                $row = [
                    $student->nim_nidn ?? '',
                    $student->name,
                ];

                if ($isRubric) {
                    // Kosongkan kolom nilai kriteria sebagai template siap isi
                    foreach ($assessment->rubric->criteria as $criterion) {
                        $row[] = '';
                    }
                } else {
                    $row[] = '';
                }

                $row[] = ''; // Catatan kosong

                // Cegah formula injection
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
     * Upload & Validasi file CSV untuk Preview (Step 16 & 17).
     */
    public function uploadImport(Request $request, ClassSection $section, Assessment $assessment): View|RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ], [
            'file.required' => 'Pilih file CSV yang akan diimpor.',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ]);

        $uploadedFile = $request->file('file');
        $rawContent = file_get_contents($uploadedFile->getRealPath());

        // Hapus UTF-8 BOM jika ada
        $cleanContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);
        $lines = preg_split('/\r\n|\r|\n/', trim($cleanContent));

        if (empty($lines) || count($lines) < 2) {
            return back()->withErrors(['file' => 'File CSV kosong atau tidak memiliki baris data.']);
        }

        $headerLine = $lines[0];
        $delimiter = str_contains($headerLine, "\t") ? "\t" : (str_contains($headerLine, ';') ? ';' : ',');
        $headers = array_map('trim', str_getcsv($headerLine, $delimiter, '"', '\\'));

        $assessment->load(['rubric.criteria']);
        $isRubric = $assessment->uses_rubric && $assessment->rubric;
        $criteria = $isRubric ? $assessment->rubric->criteria : collect();

        // Cari indeks kolom NIM, Nama, Feedback
        $nimIdx = null;
        $nameIdx = null;
        $feedbackIdx = null;
        $scoreIdx = null;
        $critColMap = []; // [crit_id => col_index]

        foreach ($headers as $colIdx => $colName) {
            $colLower = strtolower($colName);
            if ($nimIdx === null && (str_contains($colLower, 'nim') || str_contains($colLower, 'nomor'))) {
                $nimIdx = $colIdx;
            } elseif ($nameIdx === null && str_contains($colLower, 'nama')) {
                $nameIdx = $colIdx;
            } elseif ($feedbackIdx === null && (str_contains($colLower, 'catatan') || str_contains($colLower, 'feedback') || str_contains($colLower, 'komentar'))) {
                $feedbackIdx = $colIdx;
            } elseif (! $isRubric && $scoreIdx === null && (str_contains($colLower, 'nilai') || str_contains($colLower, 'skor') || str_contains($colLower, 'score'))) {
                $scoreIdx = $colIdx;
            }
        }

        // Default jika header tidak dinamai standar
        if ($nimIdx === null) $nimIdx = 0;
        if ($nameIdx === null) $nameIdx = 1;
        if (! $isRubric && $scoreIdx === null) $scoreIdx = 2;

        // Pemetaan kolom kriteria untuk rubrik
        if ($isRubric) {
            foreach ($criteria as $criterion) {
                $foundIdx = null;
                $critNameLower = strtolower($criterion->name);

                foreach ($headers as $colIdx => $colName) {
                    if (str_contains(strtolower($colName), $critNameLower)) {
                        $foundIdx = $colIdx;
                        break;
                    }
                }

                // Jika nama tidak cocok, petakan secara berurutan setelah kolom nama (indeks 2, 3, dst)
                if ($foundIdx === null) {
                    $fallbackIdx = 2 + count($critColMap);
                    if (isset($headers[$fallbackIdx])) {
                        $foundIdx = $fallbackIdx;
                    }
                }

                $critColMap[$criterion->id] = $foundIdx;
            }
        }

        // Daftar mahasiswa terdaftar
        $enrolledStudents = $section->students()->get()->keyBy(fn ($s) => trim((string)($s->nim_nidn ?? '')));

        $validRows = [];
        $invalidRows = [];

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
            $rowNum = $i + 1;

            $rawNim = isset($cols[$nimIdx]) ? trim($cols[$nimIdx], "'\" \t\n\r\0\x0B") : '';
            $rawName = isset($cols[$nameIdx]) ? trim($cols[$nameIdx], "'\" \t\n\r\0\x0B") : '';
            $rawFeedback = ($feedbackIdx !== null && isset($cols[$feedbackIdx])) ? trim($cols[$feedbackIdx], "'\" \t\n\r\0\x0B") : null;

            $errors = [];
            $student = $enrolledStudents->get($rawNim);

            if ($rawNim === '') {
                $errors[] = 'NIM kosong pada baris ini.';
            } elseif (! $student) {
                $errors[] = "NIM '{$rawNim}' tidak terdaftar pada kelas ini.";
            }

            $parsedScores = [];
            $calculatedAssessmentScore = null;

            if ($isRubric) {
                $hasAnyGraded = false;
                $weightedSum = 0;
                $weightGraded = 0;

                foreach ($criteria as $criterion) {
                    $cIdx = $critColMap[$criterion->id] ?? null;
                    $val = ($cIdx !== null && isset($cols[$cIdx])) ? trim($cols[$cIdx]) : '';
                    $valClean = str_replace(',', '.', $val);

                    if ($valClean !== '') {
                        if (! is_numeric($valClean)) {
                            $errors[] = "Skor kriteria '{$criterion->name}' harus berupa angka (ditemukan '{$val}').";
                            $parsedScores[$criterion->id] = null;
                        } else {
                            $numVal = (float) $valClean;
                            $max = (float) ($criterion->max_score ?: 100);

                            if ($numVal < 0 || $numVal > $max) {
                                $errors[] = "Skor '{$criterion->name}' harus antara 0 dan {$max} (ditemukan {$numVal}).";
                            }

                            $parsedScores[$criterion->id] = $numVal;
                            $hasAnyGraded = true;
                            $normalized = ($numVal / $max) * 100;
                            $weightedSum += $normalized * ($criterion->weight / 100);
                            $weightGraded += $criterion->weight;
                        }
                    } else {
                        $parsedScores[$criterion->id] = null;
                    }
                }

                if ($hasAnyGraded && $weightGraded > 0) {
                    $calculatedAssessmentScore = round($weightedSum / ($weightGraded / 100), 2);
                }
            } else {
                $rawScore = isset($cols[$scoreIdx]) ? trim($cols[$scoreIdx]) : '';
                $scoreClean = str_replace(',', '.', $rawScore);

                if ($scoreClean !== '') {
                    if (! is_numeric($scoreClean)) {
                        $errors[] = "Nilai harus berupa angka (ditemukan '{$rawScore}').";
                        $calculatedAssessmentScore = null;
                    } else {
                        $numVal = (float) $scoreClean;
                        if ($numVal < 0 || $numVal > 100) {
                            $errors[] = "Nilai harus antara 0 dan 100 (ditemukan {$numVal}).";
                        }
                        $calculatedAssessmentScore = $numVal;
                    }
                } else {
                    $calculatedAssessmentScore = null;
                }
            }

            $rowData = [
                'row_number' => $rowNum,
                'nim' => $rawNim,
                'name' => $student ? $student->name : $rawName,
                'student_id' => $student?->id,
                'is_valid' => empty($errors),
                'errors' => $errors,
                'score' => $calculatedAssessmentScore,
                'criteria_scores' => $parsedScores,
                'feedback' => $rawFeedback,
            ];

            if (empty($errors)) {
                $validRows[] = $rowData;
            } else {
                $invalidRows[] = $rowData;
            }
        }

        // Simpan ke session untuk tahap konfirmasi (Step 17)
        session(["import_preview_{$assessment->id}" => [
            'assessment_id' => $assessment->id,
            'is_rubric' => $isRubric,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ]]);

        return view('dosen.penilaian.import-preview', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'validRows' => $validRows,
            'invalidRows' => $invalidRows,
            'totalRows' => count($validRows) + count($invalidRows),
            'criteria' => $criteria,
            'isRubric' => $isRubric,
        ]);
    }

    /**
     * Konfirmasi & Simpan data valid dari Import (Step 16 & 17).
     */
    public function confirmImport(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $sessionKey = "import_preview_{$assessment->id}";
        $previewData = session($sessionKey);

        if (! $previewData || empty($previewData['valid_rows'])) {
            return redirect()->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
                ->withErrors(['file' => 'Tidak ada data valid yang dapat disimpan atau sesi telah berakhir. Silakan upload ulang.']);
        }

        $validRows = $previewData['valid_rows'];
        $isRubric = $assessment->uses_rubric && $assessment->rubric;
        $graderId = Auth::guard('web')->id() ?? $section->dosen_id;

        DB::transaction(function () use ($validRows, $assessment, $isRubric, $graderId) {
            foreach ($validRows as $row) {
                $studentId = $row['student_id'];
                if (! $studentId) continue;

                if ($isRubric) {
                    // Simpan skor tiap kriteria
                    foreach ($row['criteria_scores'] as $critId => $critVal) {
                        StudentRubricScore::updateOrCreate(
                            ['rubric_criterion_id' => $critId, 'mahasiswa_id' => $studentId],
                            ['score' => $critVal !== null ? (float) $critVal : null]
                        );
                    }

                    // Hitung nilai akhir asesmen melalui ObeCalculationService
                    $calculatedFinal = $this->obe->rubricScore($assessment->rubric, $studentId);

                    StudentAssessmentScore::updateOrCreate(
                        ['assessment_id' => $assessment->id, 'mahasiswa_id' => $studentId],
                        [
                            'score' => $calculatedFinal,
                            'feedback' => $row['feedback'],
                            'graded_by' => $graderId,
                            'graded_at' => $calculatedFinal !== null ? now() : null,
                        ]
                    );
                } else {
                    $score = $row['score'];
                    StudentAssessmentScore::updateOrCreate(
                        ['assessment_id' => $assessment->id, 'mahasiswa_id' => $studentId],
                        [
                            'score' => $score !== null ? (float) $score : null,
                            'feedback' => $row['feedback'],
                            'graded_by' => $graderId,
                            'graded_at' => $score !== null ? now() : null,
                        ]
                    );
                }
            }
        });

        session()->forget($sessionKey);
        $count = count($validRows);

        return redirect()->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', "Berhasil mengimpor dan menyimpan nilai untuk {$count} mahasiswa.");
    }

    /**
     * Batalkan proses import (Step 17).
     */
    public function cancelImport(ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        session()->forget("import_preview_{$assessment->id}");

        return redirect()->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', 'Impor nilai dibatalkan. Tidak ada data yang disimpan.');
    }

    private function cpmksFor(ClassSection $section)
    {
        return Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
    }

    private function cplsFor(ClassSection $section)
    {
        $cpmkIds = $this->cpmksFor($section)->pluck('id');

        return Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))->get();
    }

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);
        $section->cpmk_used_count = $this->cpmksFor($section)->count();
        $section->cpl_used_count = $this->cplsFor($section)->count();

        return $section;
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

        abort_unless($currentUserId && ($section->dosen_id === $currentUserId || $section->dosen_pendamping_id === $currentUserId), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    private function authorizeAssessmentBelongsToSection(ClassSection $section, Assessment $assessment): void
    {
        abort_unless($assessment->class_section_id === $section->id, 404);
    }
}
