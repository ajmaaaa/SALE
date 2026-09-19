<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InputNilaiController extends Controller
{
    public function __construct(private \App\Services\ObeCalculationService $obe) {}

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
        $existingCpmkScores = \App\Models\StudentAssessmentCpmkScore::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy(fn ($s) => $s->cpmk_id . ':' . $s->mahasiswa_id);

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

        if ($cpmks->isNotEmpty()) {
            $rules = [
                'cpmk_scores' => ['required', 'array'],
                'cpmk_scores.*' => ['array'],
            ];
            $messages = [];

            foreach ($cpmks as $cpmk) {
                $maxScore = round($this->obe->assessmentCpmkMaxScore($assessment, $cpmk), 1);
                $maxScoreFormatted = rtrim(rtrim(number_format($maxScore, 1), '0'), '.');
                $rules["cpmk_scores.*.{$cpmk->id}"] = ['nullable', 'numeric', 'min:0', 'max:' . ($maxScore + 0.05)];
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
                    $hasExceededLimit = false;

                    foreach ($cpmks as $cpmk) {
                        $rawScore = $cpmkValues[$cpmk->id] ?? null;
                        $scoreValue = ($rawScore !== null && $rawScore !== '') ? (float) $rawScore : null;
                        $maxScore = $this->obe->assessmentCpmkMaxScore($assessment, $cpmk);

                        if ($scoreValue !== null && ($scoreValue > $maxScore + 0.05 || $scoreValue < 0)) {
                            $hasExceededLimit = true;
                        }

                        \App\Models\StudentAssessmentCpmkScore::updateOrCreate(
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

                    // Total nilai asesmen dikosongkan (null / tidak ada) jika ada nilai yang lewat batas
                    $overallScore = ($hasAnyScore && ! $hasExceededLimit) ? min(100.0, round($sumOfPoints, 2)) : null;

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
        } else {
            // Asesmen tanpa pemetaan CPMK langsung
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

        $filename = 'template_nilai_' . $assessment->code . '.csv';

        return response()->streamDownload(function () use ($students, $cpmks) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($cpmks->isNotEmpty()) {
                $header = ['NIM', 'Nama'];
                foreach ($cpmks as $cpmk) {
                    $header[] = $cpmk->code;
                }
                fputcsv($handle, $header, ';');

                foreach ($students as $student) {
                    $row = [$student->nim_nidn ?? '', $student->name];
                    foreach ($cpmks as $cpmk) {
                        $row[] = '';
                    }
                    fputcsv($handle, $row, ';');
                }
            } else {
                fputcsv($handle, ['NIM', 'Nama', 'Nilai'], ';');
                foreach ($students as $student) {
                    fputcsv($handle, [$student->nim_nidn ?? '', $student->name, ''], ';');
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

        if (count($lines) < 2) {
            return back()->withErrors(['file' => 'File CSV kosong atau hanya berisi header.']);
        }

        // Get enrolled students indexed by NIM
        $enrolledStudents = $section->students()->orderBy('name')->get();
        $studentsByNim = $enrolledStudents->keyBy('nim_nidn');

        $rows = [];
        $errors = [];

        // Skip header row
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $delimiter = str_contains($line, ';') ? ';' : (str_contains($line, "\t") ? "\t" : ',');
            $cols = array_map('trim', str_getcsv($line, $delimiter, '"', '\\'));

            if (count($cols) < 3) {
                $errors[] = "Baris " . ($i + 1) . ": kurang dari 3 kolom.";
                continue;
            }

            $nim = $cols[0];
            $name = $cols[1];
            $score = $cols[2];
            $feedback = $cols[3] ?? '';

            $student = $studentsByNim[$nim] ?? null;

            if (! $student) {
                $errors[] = "Baris " . ($i + 1) . ": NIM '$nim' tidak ditemukan di kelas ini.";
                continue;
            }

            if ($score !== '' && (! is_numeric($score) || (float) $score < 0 || (float) $score > 100)) {
                $errors[] = "Baris " . ($i + 1) . ": Nilai '$score' tidak valid (harus 0-100).";
                continue;
            }

            $rows[] = [
                'mahasiswa_id' => $student->id,
                'nim' => $nim,
                'name' => $student->name,
                'score' => $score !== '' ? (float) $score : null,
                'feedback' => $feedback,
                'status' => $score !== '' ? 'valid' : 'kosong',
            ];
        }

        if (empty($rows) && ! empty($errors)) {
            return back()->withErrors(['file' => 'Semua baris mengandung error.'])->with('import_errors', $errors);
        }

        // Store preview in session for confirmation
        session([
            'import_preview' => [
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
        $saved = 0;

        DB::transaction(function () use ($preview, $assessment, $dosenId, &$saved) {
            foreach ($preview['rows'] as $row) {
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

                if ($row['score'] !== null) {
                    $saved++;
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
            $currentUserId = $user?->hasRole(\App\Models\Role::DOSEN) ? $user->id : null;
        }

        abort_unless($currentUserId && $section->dosen_id === $currentUserId, 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    private function authorizeAssessmentBelongsToSection(ClassSection $section, Assessment $assessment): void
    {
        abort_unless($assessment->class_section_id === $section->id, 404);
    }
}
