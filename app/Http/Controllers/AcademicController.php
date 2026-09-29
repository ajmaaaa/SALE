<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use App\Services\ObeCalculationService;
use App\Support\AcademicPreview as Academic;
use App\Support\AdminPreview;
use App\Support\LearningPreview as Learning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademicController extends Controller
{
    public function __construct(private ObeCalculationService $grades) {}

    public function gradeItem(Request $request, int $item)
    {
        $resource = Learning::items()[$item] ?? null;
        $submission = $this->resolveSubmission($item);
        abort_unless($resource && $submission, 404);
        if (! empty($resource['questions'])) {
            $questions = $resource['questions'];
        } elseif (($resource['scoring_mode'] ?? null) === 'manual_cpmk' && ! empty($resource['manual_cpmk_weights'])) {
            $questions = array_map(fn ($weight) => ['points' => 100], array_values($resource['manual_cpmk_weights']));
        } else {
            $questions = [['points' => $resource['points'] ?? 100]];
        }
        $data = $request->validate(['points' => 'required|array|size:'.count($questions), 'points.*' => 'required|numeric|min:0', 'feedback' => 'nullable|string|max:3000']);
        foreach ($questions as $index => $question) {
            if (! isset($data['points'][$index]) || $data['points'][$index] > $question['points']) {
                return back()->withErrors(['points' => 'Nilai soal tidak boleh melebihi poin maksimal.'])->withInput();
            }
        }
        session(["academic.item_grades.$item.1" => $data]);

        return back()->with('notice', 'Penilaian disimpan dan masuk ke komponen course terkait.');
    }

    public function student(Request $request)
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $allSemesters = Schema::hasTable('semesters')
            ? Semester::orderChronological()->get()
            : collect();
        $activeSemester = $allSemesters->firstWhere('is_active', true) ?? $allSemesters->first();

        $selectedSemesterParam = $request->query('semester');
        $selectedSemester = null;
        if ($selectedSemesterParam && $allSemesters->isNotEmpty()) {
            $selectedSemester = $allSemesters->first(function ($s) use ($selectedSemesterParam) {
                return (string) $s->id === (string) $selectedSemesterParam
                    || $s->code === $selectedSemesterParam
                    || Str::slug($s->name) === Str::slug($selectedSemesterParam);
            });
        }
        $selectedSemester ??= $activeSemester;

        $courses = [];
        $totalCredits = 0;

        if ($user && Schema::hasTable('class_sections')) {
            $sectionsQuery = $user->classSectionsEnrolled()->with([
                'mataKuliah',
                'dosen',
                'semester',
                'assessments' => fn ($q) => $q->whereNotIn('type', ['materi', 'pengumuman'])->orderBy('id'),
            ]);

            if ($selectedSemester) {
                $sectionsQuery->where('semester_id', $selectedSemester->id);
            }

            $sections = $sectionsQuery->get();

            // Jika kosong di semester terpilih dan pengguna belum memilih filter eksplisit, cari semester yang ada kelasnya
            if ($sections->isEmpty() && ! $request->has('semester') && $allSemesters->isNotEmpty()) {
                $enrolledSemId = $user->classSectionsEnrolled()->value('semester_id');
                if ($enrolledSemId && $enrolledSemId !== $selectedSemester?->id) {
                    $selectedSemester = $allSemesters->firstWhere('id', $enrolledSemId) ?? $selectedSemester;
                    $sections = $user->classSectionsEnrolled()
                        ->where('semester_id', $selectedSemester->id)
                        ->with([
                            'mataKuliah',
                            'dosen',
                            'semester',
                            'assessments' => fn ($q) => $q->whereNotIn('type', ['materi', 'pengumuman'])->orderBy('id'),
                        ])->get();
                }
            }

            foreach ($sections as $sec) {
                $sks = (int) ($sec->mataKuliah->sks ?? 3);
                $totalCredits += $sks;

                $final = $this->grades->finalScore($sec, $user->id, false);
                $finalScore = $final['score'];

                $letter = '-';
                if ($finalScore !== null) {
                    $letter = match (true) {
                        $finalScore >= 85 => 'A',
                        $finalScore >= 80 => 'A-',
                        $finalScore >= 75 => 'B+',
                        $finalScore >= 70 => 'B',
                        $finalScore >= 65 => 'B-',
                        $finalScore >= 60 => 'C+',
                        $finalScore >= 55 => 'C',
                        $finalScore >= 40 => 'D',
                        default => 'E',
                    };
                }

                $components = [];
                foreach ($sec->assessments as $asmt) {
                    $score = $this->grades->assessmentScore($asmt->id, $user->id);
                    $components[] = [
                        'id' => $asmt->id,
                        'name' => $asmt->name,
                        'type' => $asmt->type,
                        'weight' => (float) $asmt->final_weight,
                        'score' => $score,
                    ];
                }

                $courses[$sec->id] = [
                    'id' => $sec->id,
                    'code' => $sec->display_code,
                    'title' => $sec->mataKuliah->name,
                    'semester_paket' => $sec->mataKuliah->semester_paket,
                    'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                    'sks' => $sks,
                    'final_score' => $finalScore,
                    'letter' => $letter,
                    'grade_point' => 0.0,
                    'components' => $components,
                ];
            }
        }

        // Fallback untuk unauthenticated demo / testing
        if (empty($courses) && (! $user || (config('app.demo_mode') && app()->environment(['local', 'testing'])))) {
            $demoCourses = Learning::courses();
            $studentId = session('auth_user.id') ?? 1;
            foreach ($demoCourses as $c) {
                $res = \App\Support\AcademicPreview::result($c['id'], $studentId);
                $cfg = \App\Support\AcademicPreview::config($c['id']);
                $sks = 3;
                $totalCredits += $sks;
                $finalScore = $res['average'] ?? null;
                $letter = '-';
                if ($finalScore !== null) {
                    $letter = match (true) {
                        $finalScore >= 85 => 'A',
                        $finalScore >= 80 => 'A-',
                        $finalScore >= 75 => 'B+',
                        $finalScore >= 70 => 'B',
                        $finalScore >= 65 => 'B-',
                        $finalScore >= 60 => 'C+',
                        $finalScore >= 55 => 'C',
                        $finalScore >= 40 => 'D',
                        default => 'E',
                    };
                }
                $components = [];
                foreach ($cfg['components'] ?? [] as $comp) {
                    $components[] = [
                        'id' => 0,
                        'name' => $comp['name'],
                        'type' => 'tugas',
                        'weight' => (float) $comp['weight'],
                        'score' => $res['scores'][$comp['code']] ?? null,
                    ];
                }
                $courses[$c['id']] = [
                    'id' => $c['id'],
                    'code' => $c['code'] ?? 'MKB-'.$c['id'],
                    'title' => $c['title'],
                    'lecturer' => $c['lecturer'] ?? 'Dosen Pengampu',
                    'sks' => $sks,
                    'final_score' => $finalScore,
                    'letter' => $letter,
                    'grade_point' => 0.0,
                    'components' => $components,
                ];
            }
        }

        $semesterOptions = [];
        foreach ($allSemesters as $s) {
            $studentSemNum = ($user && method_exists($user, 'semesterTempuhAt')) ? $user->semesterTempuhAt($s) : null;
            $semLabel = $studentSemNum ? "Semester {$studentSemNum} ({$s->display_name})" : $s->display_name;
            if ($s->is_active) {
                $semLabel .= ' - Semester Aktif';
            }

            $semesterOptions[$s->code] = [
                'id' => $s->id,
                'semester_tempuh' => $studentSemNum,
                'label' => $semLabel,
                'code' => $s->code,
                'is_active' => $s->is_active,
            ];
        }

        return view('learning.grades', [
            'courses' => $courses,
            'semesters' => $allSemesters,
            'selectedSemester' => $selectedSemester,
            'semesterOptions' => $semesterOptions,
            'totalCredits' => $totalCredits,
        ]);
    }

    private function authorizeOwnership(int $course, int $item = 0): void
    {
        $section = Schema::hasTable('class_sections') ? ClassSection::find($course) : null;

        if ($section) {
            $user = auth()->user();
            abort_unless(
                $user
                && $user->can('manage', $section),
                403,
                'Anda bukan pengampu kelas ini.'
            );

            if ($item !== 0) {
                $databaseItemExists = Assessment::where('class_section_id', $section->id)
                    ->whereKey($item)
                    ->exists();
                $previewItem = Learning::items()[$item] ?? null;
                $previewAllowed = config('app.demo_mode')
                    && app()->environment(['local', 'testing'])
                    && $previewItem
                    && ($previewItem['course'] ?? null) === $course;

                abort_unless($databaseItemExists || $previewAllowed, 404, 'Item tidak ditemukan atau bukan milik course ini.');
            }

            return;
        }

        abort_unless(
            config('app.demo_mode') && app()->environment(['local', 'testing']),
            404
        );

        $courseData = Learning::course($course);
        abort_unless($courseData, 404);

        if ($item !== 0) {
            $itemData = null;
            if (Schema::hasTable('assessments')) {
                $assessment = Assessment::where('class_section_id', $course)->find($item);
                if ($assessment) {
                    $itemData = Learning::databaseAssessment($assessment);
                }
            }
            $itemData ??= Learning::items()[$item] ?? null;
            abort_unless($itemData && ($itemData['course'] ?? null) === $course, 404, 'Item tidak ditemukan atau bukan milik course ini.');
        }
    }

    private function authorizeStudentEnrollment(int $course, int $item, int $student): void
    {
        if (! Schema::hasTable('class_sections')) {
            return;
        }

        $section = ClassSection::find($course);
        if (! $section) {
            return;
        }

        $isDatabaseAssessment = Schema::hasTable('assessments')
            && Assessment::where('class_section_id', $section->id)->whereKey($item)->exists();
        if (! $isDatabaseAssessment && config('app.demo_mode') && app()->environment(['local', 'testing'])) {
            return;
        }

        abort_unless(
            $section->students()->where('users.id', $student)->exists(),
            403,
            'Mahasiswa tidak terdaftar pada kelas ini.'
        );
    }

    public function assessmentGrading(int $course, int $item)
    {
        $this->authorizeOwnership($course, $item);
        $evaluation = Academic::assessmentEvaluation($course, $item);

        return view('dosen.penilaian.item-grading', [
            'course' => $evaluation['course'],
            'item' => $evaluation['item'],
            'questions' => $evaluation['questions'],
            'students' => $evaluation['students'],
            'pendingQueue' => $evaluation['pending_queue'],
            'results' => $evaluation['results'],
            'totalPending' => $evaluation['total_pending'],
            'allCompleted' => $evaluation['all_completed'],
        ]);
    }

    public function evaluateEssay(int $course, int $item, int $student, ?int $questionIndex = null)
    {
        $this->authorizeOwnership($course, $item);
        $evaluation = Academic::assessmentEvaluation($course, $item);
        $questions = $evaluation['questions'];

        $studentObj = collect($evaluation['students'])->firstWhere('id', $student);
        abort_unless($studentObj, 404);

        // Kumpulkan semua soal esai + jawaban + skor yang sudah ada
        $grades = session("academic.item_grades.{$item}.{$student}.points", []);
        $submission = $this->resolveSubmission($item, $student);

        $essayItems = [];
        foreach ($questions as $qIdx => $q) {
            if (! $q['is_essay']) {
                continue;
            }

            $answerText = $submission['question_answers'][(string) $q['id']]['text']
                ?? $submission['question_answers'][$qIdx]['text']
                ?? ($submission['answer'] ?? '');

            $currentScore = isset($grades[$qIdx]) && is_numeric($grades[$qIdx]) ? (float) $grades[$qIdx] : null;
            $maxPoints = (float) $q['points'];
            $porsiSoal = $q['porsi_soal_raw'];

            $persen = ($currentScore !== null && $maxPoints > 0) ? ($currentScore / $maxPoints) : null;
            $nilaiSoal = $persen !== null ? round($persen * $porsiSoal, 2) : null;

            $essayItems[] = [
                'question_index' => $qIdx,
                'question' => $q,
                'answer_text' => $answerText,
                'current_score' => $currentScore,
                'max_points' => $maxPoints,
                'porsi_soal' => $porsiSoal,
                'persen' => $persen !== null ? round($persen * 100, 1) : null,
                'nilai_soal' => $nilaiSoal,
            ];
        }

        abort_unless(count($essayItems) > 0, 404);

        return view('dosen.penilaian.essay-evaluation', [
            'course' => $evaluation['course'],
            'item' => $evaluation['item'],
            'student' => $studentObj,
            'essay_items' => $essayItems,
            'questions' => $questions,
        ]);
    }

    public function saveEssayScore(Request $request, int $course, int $item, int $student, int $questionIndex)
    {
        $this->authorizeOwnership($course, $item);
        $this->authorizeStudentEnrollment($course, $item, $student);
        $evaluation = Academic::assessmentEvaluation($course, $item);
        $questions = $evaluation['questions'];
        $question = $questions[$questionIndex] ?? null;
        abort_unless($question && $question['is_essay'], 404);

        $maxPoints = (float) $question['points'];
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$maxPoints],
        ], [
            'score.required' => 'Masukkan skor nilai untuk jawaban ini.',
            'score.numeric' => 'Skor harus berupa angka.',
            'score.min' => 'Skor minimal adalah 0.',
            'score.max' => "Skor maksimal untuk soal ini adalah {$maxPoints}.",
        ]);

        $grades = session("academic.item_grades.{$item}.{$student}.points", []);
        $grades[$questionIndex] = (float) $validated['score'];
        session(["academic.item_grades.{$item}.{$student}.points" => $grades]);

        // Hitung ulang penilaian & simpan ke database jika tabel tersedia
        $evalAfter = Academic::assessmentEvaluation($course, $item);
        $studentResult = collect($evalAfter['results'])->firstWhere('student.id', $student);
        if ($studentResult) {
            $isCompleted = ($studentResult['status_key'] ?? '') === 'selesai';
            $finalScore = $isCompleted ? ($studentResult['nilai_asesmen'] ?? null) : null;

            $assessment = Assessment::with('cpmks')->find($item);
            if ($assessment && Schema::hasTable('student_assessment_scores')) {
                if ($isCompleted) {
                    $cpmkValues = [];
                    foreach ($studentResult['cpmk_breakdown'] ?? [] as $cCode => $cInfo) {
                        $cpmkModel = $assessment->cpmks->first(function ($c) use ($cCode) {
                            $c1 = strtoupper(trim(str_replace(' ', '-', $c->code)));
                            $c2 = strtoupper(trim(str_replace(' ', '-', $cCode)));

                            return $c1 === $c2;
                        }) ?? (Schema::hasTable('cpmks') ? Cpmk::where('code', $cCode)->first() : null);

                        if ($cpmkModel) {
                            $cpmkValues[$cpmkModel->id] = round(($cInfo['score'] * $cInfo['weight']) / 100, 2);
                        }
                    }
                    if ($assessment->cpmks->isNotEmpty()) {
                        $this->grades->syncCpmkScores($assessment, $student, $cpmkValues, auth()->id(), true);
                    } else {
                        $this->grades->syncDirectScore($assessment, $student, $finalScore, auth()->id(), true);
                    }
                } else {
                    $this->grades->syncIncompleteState($assessment, $student, count(array_filter($grades, 'is_numeric')) > 0);
                }
            }
        }

        return redirect()
            ->route('dosen.item.penilaian.esai', [$course, $item, $student])
            ->with('notice', 'Skor berhasil disimpan.');
    }

    /**
     * Halaman penilaian Tugas Biasa — menampilkan daftar mahasiswa
     * dan status pengumpulan masing-masing.
     */
    public function tugasGrading(int $course, int $item)
    {
        $this->authorizeOwnership($course, $item);
        $itemData = null;
        if (Schema::hasTable('assessments')) {
            $assessment = Assessment::where('class_section_id', $course)->find($item);
            if ($assessment) {
                $itemData = Learning::databaseAssessment($assessment);
            }
        }
        $itemData ??= Learning::items()[$item] ?? null;
        abort_unless($itemData && in_array($itemData['type'], ['tugas', 'coding'], true), 404);

        $courseData = Learning::course($course);
        $poinTugas = (float) ($itemData['points'] ?? 100);
        $manualWeights = $itemData['manual_cpmk_weights'] ?? [];

        // Daftar mahasiswa demo
        $baseStudents = array_values(array_filter(AdminPreview::users(), fn ($u) => ($u['role'] ?? '') === 'mahasiswa'));
        $extraStudents = [
            ['id' => 4, 'name' => 'Dewi Anggraini', 'email' => 'dewi@example.test', 'number' => '231011401235', 'role' => 'mahasiswa'],
            ['id' => 5, 'name' => 'Fajar Ramadhan', 'email' => 'fajar@example.test', 'number' => '231011401238', 'role' => 'mahasiswa'],
            ['id' => 6, 'name' => 'Rizky Pratama', 'email' => 'rizky@example.test', 'number' => '231011401239', 'role' => 'mahasiswa'],
            ['id' => 7, 'name' => 'Siti Nurhaliza', 'email' => 'siti@example.test', 'number' => '231011401240', 'role' => 'mahasiswa'],
        ];
        $students = $baseStudents;
        foreach ($extraStudents as $extra) {
            if (! collect($students)->contains('id', $extra['id'])) {
                $students[] = $extra;
            }
        }
        if (Schema::hasTable('class_sections')) {
            $section = ClassSection::find($course);
            if ($section) {
                $dbStudents = $section->students()->get()->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'number' => $u->nim_nidn ?? (string) $u->id,
                    'role' => 'mahasiswa',
                ])->toArray();
                foreach ($dbStudents as $dbStu) {
                    if (! collect($students)->contains('id', $dbStu['id'])) {
                        $students[] = $dbStu;
                    }
                }
            }
        }

        $pendingQueue = [];
        $results = [];

        foreach ($students as $stu) {
            $stuId = $stu['id'];
            $submission = $this->resolveSubmission($item, $stuId);
            if ($submission && isset($submission['student_number']) && $submission['student_number'] !== $stu['number'] && ! session()->has("learning.submissions.{$item}.{$stuId}")) {
                $submission = null;
            }
            $hasSubmitted = ! empty($submission);

            // Skor tunggal yang sudah diinput dosen
            $skorRaw = session("academic.item_grades.{$item}.{$stuId}.skor_tugas");
            $skorValue = is_numeric($skorRaw) ? (float) $skorRaw : null;

            // nilai_tugas = skor / poin_tugas × 100
            $nilaiTugas = ($skorValue !== null && $poinTugas > 0)
                ? round($skorValue / $poinTugas * 100, 2)
                : null;

            // Distribusi ke CPMK: nilai_cpmk[k] = nilai_tugas × bobot_cpmk[k] / 100
            $nilaiCpmk = [];
            foreach ($manualWeights as $cCode => $bobot) {
                $nilaiCpmk[$cCode] = $nilaiTugas !== null ? round($nilaiTugas * $bobot / 100, 2) : null;
            }

            $statusKey = 'belum_dikerjakan';
            if ($hasSubmitted) {
                $statusKey = $skorValue !== null ? 'selesai' : 'menunggu';
            }

            $row = [
                'student' => $stu,
                'has_submitted' => $hasSubmitted,
                'skor' => $skorValue,
                'poin_tugas' => $poinTugas,
                'nilai_tugas' => $nilaiTugas,
                'nilai_cpmk' => $nilaiCpmk,
                'status_key' => $statusKey,
            ];

            if ($statusKey === 'menunggu') {
                $pendingQueue[] = $row;
            }
            $results[] = $row;
        }

        return view('dosen.penilaian.tugas-grading', [
            'course' => $courseData,
            'item' => $itemData,
            'poinTugas' => $poinTugas,
            'manualWeights' => $manualWeights,
            'pendingQueue' => $pendingQueue,
            'results' => $results,
            'totalPending' => count($pendingQueue),
        ]);
    }

    /**
     * Simpan satu skor tugas biasa untuk satu mahasiswa.
     */
    public function saveTugasScore(Request $request, int $course, int $item, int $student)
    {
        $this->authorizeOwnership($course, $item);
        $this->authorizeStudentEnrollment($course, $item, $student);

        $assessment = Schema::hasTable('assessments')
            ? Assessment::with('cpmks')->where('class_section_id', $course)->find($item)
            : null;
        $itemData = $assessment
            ? Learning::databaseAssessment($assessment)
            : (Learning::items()[$item] ?? null);
        abort_unless($itemData && in_array($itemData['type'], ['tugas', 'coding'], true), 404);

        $poinTugas = (float) ($itemData['points'] ?? 100);

        $validated = $request->validate([
            'skor' => ['required', 'numeric', 'min:0', 'max:'.$poinTugas],
        ], [
            'skor.required' => 'Masukkan skor tugas.',
            'skor.numeric' => 'Skor harus berupa angka.',
            'skor.min' => 'Skor minimal adalah 0.',
            'skor.max' => "Skor maksimal adalah {$poinTugas}.",
        ]);

        $skor = (float) $validated['skor'];

        // Hitung nilai_tugas dan distribusi CPMK (skala 100)
        $nilaiTugas = $poinTugas > 0 ? round($skor / $poinTugas * 100, 2) : 0;
        $manualWeights = $itemData['manual_cpmk_weights'] ?? [];
        $nilaiCpmk = [];
        foreach ($manualWeights as $cCode => $bobot) {
            $nilaiCpmk[$cCode] = round($nilaiTugas * $bobot / 100, 2);
        }

        // Simpan ke session
        session([
            "academic.item_grades.{$item}.{$student}.skor_tugas" => $skor,
            "academic.item_grades.{$item}.{$student}.nilai_tugas" => $nilaiTugas,
            "academic.item_grades.{$item}.{$student}.nilai_cpmk" => $nilaiCpmk,
        ]);

        // Simpan nilai assessment dan breakdown CPMK melalui satu sumber perhitungan.
        if (Schema::hasTable('student_assessment_scores')) {
            if ($assessment) {
                $this->grades->syncDirectScore($assessment, $student, $nilaiTugas, auth()->id(), true);
            }
        }

        return redirect()
            ->route('dosen.item.penilaian.tugas', [$course, $item])
            ->with('notice', 'Skor tugas disimpan. Nilai tugas: '.number_format($nilaiTugas, 2, ',', '.').' dari 100.');
    }

    private function resolveSubmission(int $item, ?int $studentId = null): ?array
    {
        $submission = $studentId
            ? (session("learning.submissions.{$item}.{$studentId}") ?? session("learning.submissions.{$item}"))
            : session("learning.submissions.{$item}");

        if (! $submission && Schema::hasTable('submissions')) {
            $query = Submission::where('assessment_id', $item);
            if ($studentId) {
                $query->where(fn ($q) => $q->where('user_id', $studentId)->orWhere('mahasiswa_id', $studentId));
            }
            $dbSub = $query->latest('id')->first();
            if ($dbSub) {
                $submission = [
                    'answer' => $dbSub->answer,
                    'link' => $dbSub->link,
                    'question_answers' => $dbSub->question_answers ?? [],
                    'files' => $dbSub->file_ids ?? [],
                    'student_number' => $dbSub->student_number,
                    'time' => $dbSub->submitted_at?->format('d M Y, H:i') ?? '',
                    'status' => $dbSub->status,
                    'attempt' => $dbSub->attempt,
                    'version' => $dbSub->version,
                ];
            }
        }

        return $submission;
    }
}
