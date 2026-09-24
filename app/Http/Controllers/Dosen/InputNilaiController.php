<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\StudentAssessmentCpmkScore;
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

class InputNilaiController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman Input Nilai — menampilkan semua mahasiswa enrolled
     * dengan kolom input untuk setiap CPMK yang diukur oleh asesmen.
     */
    public function show(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $students = $section->students()->orderBy('name')->get();
        $cpmks = $assessment->cpmks()->orderBy('code')->get();

        // Existing per-CPMK scores: keyed by "{cpmk_id}:{mahasiswa_id}"
        $existingCpmkScores = StudentAssessmentCpmkScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn ($s) => $s->cpmk_id.':'.$s->mahasiswa_id);

        // Prefetch existing overall scores indexed by mahasiswa_id
        $existingScores = StudentAssessmentScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('mahasiswa_id');

        $gradedCount = $existingScores->filter(fn ($s) => $s->score !== null)->count();

        return view('dosen.penilaian.input-nilai', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'students' => $students,
            'cpmks' => $cpmks,
            'existingCpmkScores' => $existingCpmkScores,
            'existingScores' => $existingScores,
            'gradedCount' => $gradedCount,
            'obe' => $this->obe,
        ]);
    }

    /**
     * Simpan nilai dari form input — jika asesmen memiliki CPMK,
     * simpan per-CPMK dan hitung nilai total asesmen secara otomatis.
     */
    public function store(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $cpmks = $assessment->cpmks()->orderBy('code')->get();
        $enrolledIds = $section->students()->pluck('users.id');
        $dosenId = Auth::guard('web')->id();

        if ($request->has('rubric_scores') && $assessment->uses_rubric && $assessment->rubric) {
            $criteria = $assessment->rubric->criteria;
            $request->validate([
                'rubric_scores' => ['required', 'array'],
                'rubric_scores.*' => ['array'],
                'rubric_scores.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ]);

            DB::transaction(function () use ($request, $assessment, $criteria, $enrolledIds, $dosenId) {
                foreach ($request->input('rubric_scores', []) as $mahasiswaId => $criterionScores) {
                    $mahasiswaId = (int) $mahasiswaId;
                    if (! $enrolledIds->contains($mahasiswaId)) {
                        continue;
                    }

                    foreach ($criteria as $criterion) {
                        $rawScore = $criterionScores[$criterion->id] ?? null;
                        $scoreValue = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;

                        StudentRubricScore::updateOrCreate(
                            [
                                'rubric_criterion_id' => $criterion->id,
                                'mahasiswa_id' => $mahasiswaId,
                            ],
                            [
                                'score' => $scoreValue,
                            ]
                        );
                    }

                    $this->obe->syncRubricToAssessmentScore($assessment, $mahasiswaId, $dosenId);
                }
            });
        } elseif ($request->has('scores') || $cpmks->isEmpty()) {
            // Asesmen dengan input nilai langsung (direct score) atau tanpa CPMK
            $request->validate([
                'scores' => ['required', 'array'],
                'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ]);

            DB::transaction(function () use ($request, $assessment, $enrolledIds, $dosenId) {
                foreach ($request->input('scores', []) as $mahasiswaId => $score) {
                    $mahasiswaId = (int) $mahasiswaId;
                    if (! $enrolledIds->contains($mahasiswaId)) {
                        continue;
                    }

                    $scoreValue = ($score !== null && $score !== '') ? (float) $score : null;

                    StudentAssessmentScore::updateOrCreate(
                        [
                            'assessment_id' => $assessment->id,
                            'mahasiswa_id' => $mahasiswaId,
                        ],
                        [
                            'score' => $scoreValue,
                            'feedback' => null,
                            'graded_by' => $scoreValue !== null ? $dosenId : null,
                            'graded_at' => $scoreValue !== null ? now() : null,
                        ]
                    );
                }
            });
        } else {
            $rules = [
                'cpmk_scores' => ['required', 'array'],
                'cpmk_scores.*' => ['array'],
            ];
            $messages = [];

            foreach ($cpmks as $cpmk) {
                $maxScore = round($this->obe->assessmentCpmkMaxScore($assessment, $cpmk), 1);
                $maxScoreFormatted = rtrim(rtrim(number_format($maxScore, 1), '0'), '.');
                $rules["cpmk_scores.*.{$cpmk->id}"] = ['nullable', 'numeric', 'min:0', 'max:'.$maxScore];
                $messages["cpmk_scores.*.{$cpmk->id}.max"] = "Nilai {$cpmk->code} tidak boleh melebihi batas maksimal {$maxScoreFormatted}.";
                $messages["cpmk_scores.*.{$cpmk->id}.min"] = "Nilai {$cpmk->code} tidak boleh kurang dari 0.";
            }

            $request->validate($rules, $messages);

            DB::transaction(function () use ($request, $assessment, $cpmks, $enrolledIds, $dosenId) {
                foreach ($request->input('cpmk_scores', []) as $mahasiswaId => $cpmkValues) {
                    $mahasiswaId = (int) $mahasiswaId;
                    if (! $enrolledIds->contains($mahasiswaId)) {
                        continue;
                    }

                    $sumOfPoints = 0.0;
                    $hasAnyScore = false;
                    foreach ($cpmks as $cpmk) {
                        $rawScore = $cpmkValues[$cpmk->id] ?? null;
                        $scoreValue = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;

                        StudentAssessmentCpmkScore::updateOrCreate(
                            [
                                'assessment_id' => $assessment->id,
                                'cpmk_id' => $cpmk->id,
                                'mahasiswa_id' => $mahasiswaId,
                            ],
                            [
                                'score' => $scoreValue,
                            ]
                        );

                        if ($scoreValue !== null) {
                            $hasAnyScore = true;
                            $sumOfPoints += $scoreValue;
                        }
                    }

                    $overallScore = $hasAnyScore ? round($sumOfPoints, 2) : null;

                    StudentAssessmentScore::updateOrCreate(
                        [
                            'assessment_id' => $assessment->id,
                            'mahasiswa_id' => $mahasiswaId,
                        ],
                        [
                            'score' => $overallScore,
                            'feedback' => null,
                            'graded_by' => $overallScore !== null ? $dosenId : null,
                            'graded_at' => $overallScore !== null ? now() : null,
                        ]
                    );
                }
            });
        }

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', 'Nilai berhasil disimpan.');
    }

    /**
     * Download template CSV for bulk import (tanpa feedback, per CPMK jika ada).
     */
    public function downloadTemplate(ClassSection $section, Assessment $assessment): StreamedResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        $students = $section->students()->orderBy('name')->get();
        $cpmks = $assessment->cpmks()->orderBy('code')->get();

        $filename = 'template_nilai_'.$assessment->code.'.csv';

        return response()->streamDownload(function () use ($students, $cpmks) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($cpmks->isNotEmpty()) {
                $header = ['NIM', 'Nama'];
                foreach ($cpmks as $cpmk) {
                    $header[] = $cpmk->code;
                }
                fputcsv($handle, $header, ';');

                foreach ($students as $student) {
                    $row = [$this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name)];
                    foreach ($cpmks as $cpmk) {
                        $row[] = '';
                    }
                    fputcsv($handle, $row, ';');
                }
            } else {
                fputcsv($handle, ['NIM', 'Nama', 'Nilai'], ';');
                foreach ($students as $student) {
                    fputcsv($handle, [$this->sanitizeCsv($student->nim_nidn ?? ''), $this->sanitizeCsv($student->name), ''], ';');
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Halaman import nilai — upload CSV/Excel.
     */
    public function import(ClassSection $section, Assessment $assessment): View
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        return view('dosen.penilaian.import-nilai', [
            'section' => $this->withHeaderCounts($section),
            'assessment' => $assessment,
            'preview' => session('import_preview'),
        ]);
    }

    /**
     * Process CSV upload: parse, validate, preview, then save on confirm.
     *
     * Two-phase flow:
     * - Phase 1 (no 'confirm'): parse CSV, validate, store preview in session
     * - Phase 2 ('confirm' = true): save previewed data to DB
     */
    public function processImport(Request $request, ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $this->authorizeOwnership($section);
        $this->authorizeAssessmentBelongsToSection($section, $assessment);

        // Phase 2: confirm and save
        if ($request->boolean('confirm')) {
            return $this->confirmImport($section, $assessment);
        }

        // Phase 1: parse and preview
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Pilih file CSV untuk diimport.',
            'file.mimes' => 'Format file harus CSV (.csv).',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        // Filter non-empty lines
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== '' && ! str_starts_with(trim($l), '#')));

        if (count($lines) < 2) {
            return back()->withErrors(['file' => 'File CSV kosong atau hanya berisi header.']);
        }

        // Parse header row
        $headerLine = trim($lines[0]);
        $delimiter = str_contains($headerLine, ';') ? ';' : (str_contains($headerLine, "\t") ? "\t" : ',');
        $rawHeaderCols = array_map('trim', str_getcsv($headerLine, $delimiter, '"', '\\'));

        if (count($rawHeaderCols) < 3) {
            return back()->withErrors(['file' => 'Format header CSV tidak valid (minimal 3 kolom: NIM, Nama, Nilai/CPMK).']);
        }

        // Assessment CPMKs
        $assessmentCpmks = $assessment->cpmks()->orderBy('code')->get();
        $cpmkByCode = $assessmentCpmks->keyBy(fn ($c) => strtoupper(trim($c->code)));

        $colsToCheck = array_slice($rawHeaderCols, 2);
        $firstColName = strtolower(trim($colsToCheck[0] ?? ''));

        $mode = 'legacy';
        $cpmkMapping = [];

        // Deteksi mode: Multi-CPMK vs Legacy
        if ($assessmentCpmks->isNotEmpty() && $firstColName !== 'nilai' && $firstColName !== 'score' && ! str_starts_with($firstColName, 'nilai')) {
            $mode = 'cpmk';

            foreach ($colsToCheck as $idxOffset => $headerColName) {
                $colIdx = 2 + $idxOffset;
                $code = strtoupper(trim($headerColName));
                if ($code === '') {
                    continue;
                }

                if (! $cpmkByCode->has($code)) {
                    return back()->withErrors([
                        'file' => "Kolom header '$headerColName' bukan CPMK yang diukur oleh asesmen ini.",
                    ]);
                }

                $cpmk = $cpmkByCode->get($code);
                $maxScore = $this->obe->assessmentCpmkMaxScore($assessment, $cpmk);

                $cpmkMapping[$colIdx] = [
                    'cpmk_id' => $cpmk->id,
                    'code' => $cpmk->code,
                    'max' => $maxScore,
                ];
            }

            if (empty($cpmkMapping)) {
                return back()->withErrors([
                    'file' => 'Tidak ditemukan kolom CPMK yang valid pada header CSV.',
                ]);
            }
        }

        // Get enrolled students indexed by NIM
        $enrolledStudents = $section->students()->orderBy('name')->get();
        $studentsByNim = $enrolledStudents->keyBy('nim_nidn');

        $rows = [];
        $errors = [];

        // Parse data rows
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));
            $nim = $cols[0] ?? '';
            $name = $cols[1] ?? '';

            $student = $studentsByNim[$nim] ?? null;

            if (! $student) {
                $errors[] = 'Baris '.($i + 1).": NIM '$nim' tidak ditemukan di kelas ini.";

                continue;
            }

            if ($mode === 'cpmk') {
                $cpmkScores = [];
                $sumOfPoints = 0.0;
                $hasAnyScore = false;
                $rowHasError = false;

                foreach ($cpmkMapping as $colIndex => $mapping) {
                    $rawVal = $cols[$colIndex] ?? '';
                    $cpmkId = $mapping['cpmk_id'];
                    $cpmkCode = $mapping['code'];
                    $maxScore = $mapping['max'];

                    if ($rawVal === '' || $rawVal === null) {
                        $cpmkScores[$cpmkId] = null;

                        continue;
                    }

                    if (! is_numeric($rawVal)) {
                        $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} ('$rawVal') tidak valid (harus berupa angka).";
                        $rowHasError = true;

                        continue;
                    }

                    $numVal = (float) $rawVal;

                    if ($numVal < 0) {
                        $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} tidak boleh kurang dari 0.";
                        $rowHasError = true;

                        continue;
                    }

                    if ($numVal > $maxScore) {
                        $errors[] = 'Baris '.($i + 1).": Nilai {$cpmkCode} ($numVal) melebihi batas maksimal ".(int) $maxScore.'.';
                        $rowHasError = true;

                        continue;
                    }

                    $cpmkScores[$cpmkId] = $numVal;
                    $sumOfPoints += $numVal;
                    $hasAnyScore = true;
                }

                if ($rowHasError) {
                    continue;
                }

                $overallScore = $hasAnyScore ? min(100.0, round($sumOfPoints, 2)) : null;

                $rows[] = [
                    'mahasiswa_id' => $student->id,
                    'nim' => $nim,
                    'name' => $student->name,
                    'cpmk_scores' => $cpmkScores,
                    'overall_score' => $overallScore,
                    'status' => $hasAnyScore ? 'valid' : 'kosong',
                ];
            } else {
                $score = $cols[2] ?? '';
                $feedback = $cols[3] ?? '';

                if ($score !== '' && (! is_numeric($score) || (float) $score < 0 || (float) $score > 100)) {
                    $errors[] = 'Baris '.($i + 1).": Nilai '$score' tidak valid (harus 0-100).";

                    continue;
                }

                $scoreVal = $score !== '' ? (float) $score : null;

                $rows[] = [
                    'mahasiswa_id' => $student->id,
                    'nim' => $nim,
                    'name' => $student->name,
                    'score' => $scoreVal,
                    'feedback' => $feedback,
                    'status' => $scoreVal !== null ? 'valid' : 'kosong',
                ];
            }
        }

        if (empty($rows) && ! empty($errors)) {
            return back()->withErrors(['file' => 'Semua baris mengandung error.'])->with('import_errors', $errors);
        }

        // Store preview in session for confirmation
        session([
            'import_preview' => [
                'mode' => $mode,
                'cpmk_headers' => array_values($cpmkMapping),
                'rows' => $rows,
                'errors' => $errors,
                'assessment_id' => $assessment->id,
                'section_id' => $section->id,
            ],
        ]);

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai.import', [$section->id, $assessment->id])
            ->with('import_preview_ready', true);
    }

    /**
     * Confirm import — saves previewed data to DB.
     */
    private function confirmImport(ClassSection $section, Assessment $assessment): RedirectResponse
    {
        $preview = session('import_preview');

        if (! $preview || $preview['assessment_id'] !== $assessment->id || $preview['section_id'] !== $section->id) {
            return back()->withErrors(['file' => 'Data preview tidak ditemukan. Silakan upload ulang.']);
        }

        $dosenId = Auth::guard('web')->id();
        $mode = $preview['mode'] ?? 'legacy';
        $saved = 0;

        DB::transaction(function () use ($preview, $assessment, $dosenId, $mode, &$saved) {
            foreach ($preview['rows'] as $row) {
                if ($mode === 'cpmk') {
                    foreach ($row['cpmk_scores'] as $cpmkId => $val) {
                        StudentAssessmentCpmkScore::updateOrCreate(
                            [
                                'assessment_id' => $assessment->id,
                                'cpmk_id' => $cpmkId,
                                'mahasiswa_id' => $row['mahasiswa_id'],
                            ],
                            [
                                'score' => $val,
                            ],
                        );
                    }

                    StudentAssessmentScore::updateOrCreate(
                        [
                            'assessment_id' => $assessment->id,
                            'mahasiswa_id' => $row['mahasiswa_id'],
                        ],
                        [
                            'score' => $row['overall_score'],
                            'feedback' => null,
                            'graded_by' => $row['overall_score'] !== null ? $dosenId : null,
                            'graded_at' => $row['overall_score'] !== null ? now() : null,
                        ],
                    );

                    if ($row['overall_score'] !== null) {
                        $saved++;
                    }
                } else {
                    StudentAssessmentScore::updateOrCreate(
                        [
                            'assessment_id' => $assessment->id,
                            'mahasiswa_id' => $row['mahasiswa_id'],
                        ],
                        [
                            'score' => $row['score'],
                            'feedback' => $row['feedback'] ?: null,
                            'graded_by' => $row['score'] !== null ? $dosenId : null,
                            'graded_at' => $row['score'] !== null ? now() : null,
                        ],
                    );

                    // Jika asesmen memiliki 1 CPMK, sinkronkan juga ke StudentAssessmentCpmkScore
                    if ($assessment->cpmks()->count() === 1) {
                        $singleCpmk = $assessment->cpmks()->first();
                        StudentAssessmentCpmkScore::updateOrCreate(
                            [
                                'assessment_id' => $assessment->id,
                                'cpmk_id' => $singleCpmk->id,
                                'mahasiswa_id' => $row['mahasiswa_id'],
                            ],
                            [
                                'score' => $row['score'],
                            ],
                        );
                    }

                    if ($row['score'] !== null) {
                        $saved++;
                    }
                }
            }
        });

        session()->forget('import_preview');

        return redirect()
            ->route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id])
            ->with('notice', "Import berhasil: $saved nilai disimpan.");
    }

    // ── Helpers ──

    private function withHeaderCounts(ClassSection $section): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);

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

    private function sanitizeCsv(mixed $value): string
    {
        $string = (string) $value;
        if ($string !== '' && in_array($string[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$string;
        }

        return $string;
    }
}
