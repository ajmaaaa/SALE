<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attachment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Message;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Room;
use App\Models\Semester;
use App\Models\StudentAssessmentCpmkScore;
use App\Models\StudentAssessmentScore;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use App\Services\ObeCalculationService;
use App\Services\DatabaseNotificationService;
use App\Services\QuizGradingService;
use App\Support\AcademicPreview;
use App\Support\LearningPreview as Learning;
use App\Support\QuizQuestion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LearningController extends Controller
{
    public function __construct(
        private ObeCalculationService $grades,
        private DatabaseNotificationService $notifications,
        private QuizGradingService $quizGrades,
    ) {}

    public function courses(Request $request)
    {
        $q = mb_strtolower((string) $request->query('q', ''));
        $user = auth()->user();

        $isDosen = $user?->hasRole(Role::DOSEN) ?? request()->is('dosen*');
        $sections = collect();

        if ($user && Schema::hasTable('class_sections')) {
            $query = $isDosen
                ? ClassSection::query()->where(function ($builder) use ($user) {
                    $builder->where('dosen_id', $user->id)
                        ->orWhere('dosen_pendamping_id', $user->id);
                })
                : $user->classSectionsEnrolled();

            $sections = $query
                ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'assessments'])
                ->withCount(['students', 'assessments'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->whereHas('mataKuliah', function ($mataKuliah) use ($q) {
                        $mataKuliah->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                            ->orWhereRaw('LOWER(code) LIKE ?', ["%{$q}%"]);
                    });
                })
                ->get();
        }

        if ($user) {
            $submittedAssessmentIds = [];
            $scoredAssessmentIds = [];
            if (! $isDosen) {
                if (Schema::hasTable('submissions')) {
                    $submittedAssessmentIds = Submission::where('mahasiswa_id', $user->id)->pluck('assessment_id')->all();
                }
                if (Schema::hasTable('student_assessment_scores')) {
                    $scoredAssessmentIds = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                        ->where(function ($q) {
                            $q->whereNotNull('score')
                              ->orWhereIn('status', [
                                  StudentAssessmentScore::STATUS_FINAL,
                                  StudentAssessmentScore::STATUS_PUBLISHED,
                              ]);
                        })
                        ->pluck('assessment_id')
                        ->all();
                }
            }

            $courses = $sections->map(function ($section) use ($isDosen, $submittedAssessmentIds, $scoredAssessmentIds) {
                $payload = $section->learning_payload ?? [];
                if (! $section->enrollment_code) {
                    $section->update(['enrollment_code' => ClassSection::generateUniqueEnrollmentCode()]);
                }

                $secAssessments = $section->relationLoaded('assessments') ? $section->assessments : $section->assessments()->get();

                // 1. Tugas dalam waktu dekat
                $secTasks = $secAssessments
                    ->whereNotIn('type', ['materi', 'pengumuman'])
                    ->where('status', 'published');

                if (! $isDosen) {
                    $secTasks = $secTasks->reject(fn ($asm) => in_array($asm->id, $submittedAssessmentIds, true) || in_array($asm->id, $scoredAssessmentIds, true));
                }

                $taskWithDue = $secTasks->filter(fn ($asm) => ! empty($asm->due_at))->sortBy('due_at');
                $nearestUpcomingTask = $taskWithDue->first() ?? $secTasks->first();

                // 2. Update materi terbaru
                $latestMaterial = $secAssessments
                    ->where('type', 'materi')
                    ->where('status', 'published')
                    ->sortByDesc(fn ($asm) => $asm->updated_at ? $asm->updated_at->timestamp : ($asm->created_at ? $asm->created_at->timestamp : 0))
                    ->first();

                // Tentukan level prioritas & ranking
                if ($nearestUpcomingTask) {
                    $priorityLevel = 1;
                    $sortKey = $nearestUpcomingTask->due_at ? $nearestUpcomingTask->due_at->timestamp : 1900000000;
                } elseif ($latestMaterial) {
                    $priorityLevel = 2;
                    $materialTime = $latestMaterial->updated_at ? $latestMaterial->updated_at->timestamp : ($latestMaterial->created_at ? $latestMaterial->created_at->timestamp : 0);
                    $sortKey = -1 * $materialTime;
                } else {
                    $priorityLevel = 3;
                    $sortKey = -1 * $section->id;
                }

                return [
                    'id' => $section->id,
                    'code' => $section->display_code,
                    'sks' => ($section->mataKuliah->sks ?? 0).' SKS',
                    'title' => $section->mataKuliah->name,
                    'lecturer' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_ketua' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_wakil' => $section->dosenPendamping?->name,
                    'cover' => $payload['cover'] ?? null,
                    'type' => 'Kelas Aktif',
                    'work' => 'Perkuliahan semester '.($section->semester?->name ?? 'aktif'),
                    'due' => '',
                    'students_count' => $section->students_count,
                    'assessments_count' => $section->assessments_count,
                    'enrollment_code' => $section->enrollment_code,
                    'enrollment_url' => $section->enrollment_url,
                    'qr_url' => route('kelas.qr', $section->id),
                    'svg_index' => ($section->id % 4) + 1,
                    '_priority' => $priorityLevel,
                    '_sort_key' => $sortKey,
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['_priority'] !== $b['_priority']) {
                    return $a['_priority'] <=> $b['_priority'];
                }
                return $a['_sort_key'] <=> $b['_sort_key'];
            })
            ->values()
            ->all();
        } else {
            $courses = [];
        }

        return view('learning.courses', compact('courses'));
    }

    public function course(int $course)
    {
        $user = auth()->user();
        $section = Schema::hasTable('class_sections')
            ? ClassSection::with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'assessments'])->find($course)
            : null;

        if ($section && $user) {
            $canAccess = $user->hasRole(Role::DOSEN)
                ? $user->can('manage', $section)
                : ($user->hasRole(Role::MAHASISWA)
                    && $section->students()->where('users.id', $user->id)->exists());

            if ($canAccess) {
                $courseData = Learning::databaseCourse($section);
                $items = $section->assessments
                    ->when($user->hasRole(Role::MAHASISWA), fn ($assessments) => $assessments->where('status', 'published'))
                    ->sortByDesc('id')
                    ->mapWithKeys(fn ($assessment) => [$assessment->id => Learning::databaseAssessment($assessment)])
                    ->all();

                $this->notifications->markDiscussionRead($user, $section->id);

                $submittedAssessmentIds = $user->hasRole(Role::MAHASISWA)
                    ? array_unique(array_merge(
                        Submission::where(fn ($q) => $q->where('mahasiswa_id', $user->id)->orWhere('user_id', $user->id))
                            ->whereIn('assessment_id', array_keys($items))
                            ->pluck('assessment_id')->all(),
                        StudentAssessmentScore::where('mahasiswa_id', $user->id)->whereIn('assessment_id', array_keys($items))
                            ->where(fn ($q) => $q->whereNotNull('score')->orWhereIn('status', [StudentAssessmentScore::STATUS_FINAL, StudentAssessmentScore::STATUS_PUBLISHED]))
                            ->pluck('assessment_id')->all()
                    ))
                    : [];

                return view('learning.course', compact('items', 'section', 'submittedAssessmentIds') + [
                    'course' => $courseData,
                    'classSection' => $section,
                ]);
            }

            abort(403, 'Anda tidak terdaftar pada kelas ini.');
        }

        abort(404, 'Kelas tidak ditemukan pada database.');
    }

    public function item(int $course, int $item)
    {
        $user = auth()->user();
        if ($user && Schema::hasTable('class_sections')) {
            $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->find($course);
            if ($section) {
                $canAccess = $user->hasRole(Role::DOSEN)
                ? $user->can('manage', $section)
                : ($user->hasRole(Role::MAHASISWA)
                    && $section->students()->where('users.id', $user->id)->exists());

                abort_unless($canAccess, 403, 'Anda tidak terdaftar pada kelas ini.');

                $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);
                if ($user->hasRole(Role::MAHASISWA)) {
                    abort_unless($assessment->status === 'published', 403, 'Asesmen belum tersedia atau sudah ditutup.');
                }

                return view('learning.item', [
                    'course' => Learning::databaseCourse($section),
                    'item' => Learning::databaseAssessment($assessment),
                ]);
            }
        }

        abort(404, 'Konten tidak ditemukan pada database.');
    }

    public function quizRoom(int $course, int $item)
    {
        $isDosen = auth()->user()?->hasRole(Role::DOSEN) ?? false;
        abort_if($isDosen, 403, 'Akses ditolak: Dosen tidak dapat mengikuti ujian CBT mahasiswa.');

        $user = auth()->user();
        $section = $user && Schema::hasTable('class_sections') ? ClassSection::find($course) : null;
        $assessment = null;

        // PERBAIKAN M-03: Jika course merupakan ClassSection di database,
        // assessment HARUS diambil dari database juga — tidak boleh fallback ke preview items.
        // Hal ini mencegah item preview (mis. "Praktikum Binary Tree") muncul untuk assessment berbeda.
        if ($section) {
            $assessment = Assessment::where('class_section_id', $section->id)->find($item);

            // Jika user terdaftar di section ini, assessment harus ada di DB
            abort_unless(
                $user?->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists(),
                403,
                'Anda tidak terdaftar pada kelas ini.'
            );
            abort_unless($assessment, 404, 'Assessment tidak ditemukan pada kelas ini.');
            abort_unless($assessment->status === 'published', 403, 'Asesmen belum tersedia atau sudah ditutup.');

            $resource = Learning::databaseAssessment($assessment);
            $courseData = Learning::databaseCourse($section->loadMissing(['mataKuliah', 'semester', 'dosen', 'dosenPendamping']));
        } else {
            abort(404, 'Kelas tidak ditemukan pada database.');
        }
        abort_unless(in_array($resource['type'], ['kuis', 'tugas', 'coding', 'uts', 'uas']), 404);

        $submission = null;
        if ($user && Schema::hasTable('submissions')) {
            $dbSub = Submission::where('assessment_id', $item)
                ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                ->with('answers')
                ->latest('id')
                ->first();
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
                    'answer_scores' => $dbSub->answers
                        ->where('version', $dbSub->version)
                        ->filter(fn ($answer) => $answer->question_id !== null)
                        ->mapWithKeys(fn ($answer) => [(string) $answer->question_id => $answer->earned_score === null ? null : (float) $answer->earned_score])
                        ->all(),
                ];
            }
        }
        $dbScore = $user && Schema::hasTable('student_assessment_scores')
            ? StudentAssessmentScore::where('assessment_id', $item)
                ->where('mahasiswa_id', $user->id)
                ->where(fn ($q) => $q->whereNotNull('score')->orWhereIn('status', [StudentAssessmentScore::STATUS_FINAL, StudentAssessmentScore::STATUS_PUBLISHED]))
                ->first()
            : null;
        $scoreValue = $dbScore?->score;
        $attemptDeadline = null;

        if ($assessment && ! $submission && $scoreValue === null && $this->timedDurationMinutes($resource) !== null) {
            $attemptDeadline = $this->startTimedAssessmentAttempt($assessment, $user, $resource)->deadline_at;
        }

        if (! empty($resource['randomize_questions']) && ! empty($resource['questions'])) {
            $studentId = $user->id;
            $cacheKey = "learning.quiz_order.{$item}.{$studentId}";
            $order = session($cacheKey);
            if (! is_array($order) || count($order) !== count($resource['questions'])) {
                $order = array_keys($resource['questions']);
                mt_srand($item * 1000 + (int) $studentId);
                shuffle($order);
                mt_srand();
                session([$cacheKey => $order]);
            }
            $shuffled = [];
            foreach ($order as $idx) {
                if (isset($resource['questions'][$idx])) {
                    $shuffled[] = $resource['questions'][$idx];
                }
            }
            if (count($shuffled) === count($resource['questions'])) {
                $resource['questions'] = $shuffled;
            }
        }

        return view('learning.quiz-room', [
            'course' => $courseData,
            'item' => $resource,
            'submission' => $submission,
            'isCompleted' => ! empty($submission) || $scoreValue !== null,
            'scoreValue' => $scoreValue,
            'attemptDeadline' => $attemptDeadline,
        ]);
    }

    public function quizPreview(int $course, int $item)
    {
        $user = auth()->user();
        $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->findOrFail($course);
        abort_unless($user?->hasRole(Role::DOSEN) && $user->can('manage', $section), 403);

        $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);
        $resource = Learning::databaseAssessment($assessment);
        abort_unless(in_array($resource['type'], ['kuis', 'uts', 'uas'], true), 404);

        $questions = QuizQuestion::canonicalizeQuestions($resource['questions'] ?? []);
        $resource['questions'] = $questions;
        $answers = [];
        foreach ($questions as $question) {
            $answers[(string) $question['id']] = [
                'question_id' => (string) $question['id'],
                'option_ids' => $question['answer_key']['option_ids'] ?? [],
                'matches' => $question['answer_key']['matches'] ?? [],
                'text' => $question['essay_guide'] ?? null,
            ];
        }

        return view('learning.quiz-room', [
            'course' => Learning::databaseCourse($section),
            'item' => $resource,
            'submission' => [
                'question_answers' => $answers,
                'time' => null,
                'status' => 'preview',
            ],
            'isCompleted' => true,
            'scoreValue' => null,
            'attemptDeadline' => null,
            'isLecturerPreview' => true,
            'reviewUser' => $user,
        ]);
    }

    public function assignments(Request $request)
    {
        $user = auth()->user();
        $courses = [];
        $items = [];
        $studentScores = [];
        $submittedAssessmentIds = [];

        if ($user && Schema::hasTable('student_assessment_scores')) {
            $studentScores = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                ->where(function ($q) {
                    $q->whereNotNull('score')
                      ->orWhereIn('status', [
                          StudentAssessmentScore::STATUS_FINAL,
                          StudentAssessmentScore::STATUS_PUBLISHED,
                      ]);
                })
                ->get()
                ->keyBy('assessment_id');
            $subIds = Schema::hasTable('submissions')
                ? Submission::where(fn ($q) => $q->where('mahasiswa_id', $user->id)->orWhere('user_id', $user->id))->pluck('assessment_id')->all()
                : [];
            $submittedAssessmentIds = array_unique(array_merge(
                $subIds,
                $studentScores->pluck('assessment_id')->all()
            ));
        }

        if ($user && Schema::hasTable('class_sections')) {
            $isDosen = $user->hasRole(Role::DOSEN);
            $sections = $isDosen
                ? ClassSection::where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id)->with(['mataKuliah', 'dosen'])->get()
                : $user->classSectionsEnrolled()->with(['mataKuliah', 'dosen'])->get();

            foreach ($sections as $sec) {
                $courses[$sec->id] = [
                    'id' => $sec->id,
                    'code' => $sec->display_code,
                    'title' => $sec->mataKuliah?->name ?? 'Mata Kuliah',
                    'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                ];

                if (Schema::hasTable('assessments')) {
                    $assessments = Assessment::where('class_section_id', $sec->id)
                        ->where('type', '!=', 'materi')
                        ->when(! $isDosen, fn ($query) => $query->where('status', 'published'))
                        ->get();
                    foreach ($assessments as $asm) {
                        if (($asm->learning_payload['type'] ?? '') === 'materi') {
                            continue;
                        }
                        $items[$asm->id] = [
                            'id' => $asm->id,
                            'course' => $sec->id,
                            'title' => $asm->name,
                            'module' => $asm->code,
                            'type' => in_array($asm->type, ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case']) ? ($asm->type === 'pbl' ? 'tugas' : $asm->type) : 'tugas',
                            'due' => $asm->due_at?->format('Y-m-d H:i:s') ?? '',
                            'points' => 100,
                        ];
                    }
                }

                // PERBAIKAN M-04: Jangan campur session preview items ke dalam data
                // assignment user database. Session items adalah data contoh, bukan
                // milik section ini — bisa menyebabkan tugas contoh muncul di kelas nyata.
                // $sessionItems dihapus dari pipeline DB user.
            }
        }

        $filteredItems = array_filter($items, function ($item) use ($request, $studentScores) {
            $matches = in_array($item['type'] ?? '', ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case'])
                && (! $request->filled('course') || (string) ($item['course'] ?? '') === (string) $request->query('course'))
                && (! $request->filled('type') || ($item['type'] ?? '') === $request->query('type'))
                && str_contains(mb_strtolower($item['title'] ?? ''), mb_strtolower((string) $request->query('q', '')));

            if (! $matches) {
                return false;
            }

            if ($request->query('tab') === 'nilai') {
                $assessmentId = $item['id'];
                $hasDbScore = isset($studentScores[$assessmentId]) && $studentScores[$assessmentId]->score !== null;
                return $hasDbScore;
            }

            return true;
        });
        uasort($filteredItems, fn ($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        return view('learning.assignments', [
            'items' => $filteredItems,
            'courses' => $courses,
            'studentScores' => $studentScores,
            'submittedAssessmentIds' => $submittedAssessmentIds,
        ]);
    }

    public function discussions()
    {
        $user = auth()->user();

        $courses = [];
        if ($user && Schema::hasTable('class_sections')) {
            $isDosen = $user->hasRole(Role::DOSEN);
            $sections = $isDosen
                ? ClassSection::where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id)->with(['mataKuliah', 'dosen'])->get()
                : $user->classSectionsEnrolled()->with(['mataKuliah', 'dosen'])->get();

            foreach ($sections as $sec) {
                $courses[$sec->id] = [
                    'id' => $sec->id,
                    'code' => $sec->display_code,
                    'title' => $sec->mataKuliah?->name ?? 'Mata Kuliah',
                    'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                ];
            }
        }

        return view('learning.discussions', ['items' => [], 'courses' => $courses]);
    }

    public function createCourse()
    {
        return view('dosen.course-form');
    }

    public function storeCourse(Request $request)
    {
        if ($request->filled('video') && ! preg_match('#^https?://#i', (string) $request->input('video'))) {
            $request->merge(['video' => 'https://'.ltrim((string) $request->input('video'), '/')]);
        }

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'code' => 'required|string|max:20',
            'description' => 'required|string|max:2000',
            'lecturer' => 'required|string|max:120',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video' => 'nullable|url:http,https|max:2000',
            'video_file' => 'nullable|file|mimes:mp4,webm|max:20480',
        ]);
        $data['video'] ??= null;
        $data['video_type'] = 'url';
        if ($request->hasFile('video_file')) {
            $data['video'] = $this->upload($request->file('video_file'));
            $data['video_type'] = 'file';
        }
        unset($data['video_file']);
        $data['cover'] = $request->hasFile('cover') ? $this->upload($request->file('cover')) : null;

        $user = auth()->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);

        $section = DB::transaction(function () use ($data, $user) {
            $prodi = $user->prodi ?? Prodi::first();
            if (! $prodi && Schema::hasTable('prodis')) {
                $prodi = Prodi::firstOrCreate(['code' => 'IF'], ['name' => 'Informatika']);
            }

            $mataKuliah = MataKuliah::firstOrCreate(
                ['code' => strtoupper(trim($data['code']))],
                [
                    'name' => $data['title'],
                    'prodi_id' => $prodi?->id,
                    'sks' => 3,
                ]
            );

            $semester = Semester::where('is_active', true)->first();
            if (! $semester && Schema::hasTable('semesters')) {
                $semester = Semester::firstOrCreate(['code' => '2026-1'], ['name' => 'Ganjil 2026/2027', 'is_active' => true]);
            }

            $existingCount = ClassSection::where('mata_kuliah_id', $mataKuliah->id)
                ->where('semester_id', $semester?->id)
                ->count();
            $sectionCode = chr(65 + $existingCount);

            $section = ClassSection::create([
                'mata_kuliah_id' => $mataKuliah->id,
                'semester_id' => $semester?->id,
                'section_code' => $sectionCode,
                'dosen_id' => $user->id,
                'capacity' => 40,
                'enrollment_code' => ClassSection::generateUniqueEnrollmentCode(),
                'learning_payload' => [
                    'description' => $data['description'],
                    'cover' => $data['cover'],
                    'video' => $data['video'],
                    'video_type' => $data['video_type'],
                    'video_title' => $data['title'],
                    'media_kind' => 'video',
                ],
            ]);

            Attachment::whereIn('uuid', array_filter([$data['cover'], $data['video_type'] === 'file' ? $data['video'] : null]))
                ->update(['class_section_id' => $section->id]);

            return $section;
        });

        return redirect()->route('dosen.course.show', $section->id)->with('notice', 'Course berhasil ditambahkan ke database.');
    }

    public function createItem(int $course)
    {
        $user = auth()->user();
        $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->findOrFail($course);
        abort_unless($user?->hasRole(Role::DOSEN) && $user->can('manage', $section), 403, 'Anda bukan pengampu kelas ini.');

        return view('dosen.item-form', [
            'course' => Learning::databaseCourse($section),
            'modules' => $section->assessments()->get()->pluck('learning_payload.module')->filter()->unique()->values(),
        ]);
    }

    public function storeItem(Request $request, int $course)
    {
        // Pastikan dosen adalah pengampu kelas sebelum membuat konten apapun
        $user = auth()->user();
        $section = ClassSection::with('mataKuliah')->findOrFail($course);
        abort_unless($user?->hasRole(Role::DOSEN) && $user->can('manage', $section), 403, 'Anda bukan pengampu kelas ini.');
        $academic = AcademicPreview::config($course);

        // The lecturer UI exposes one "Tugas" option; programming is chosen as its mode.
        if ($request->input('type') === 'tugas' && $request->filled('task_mode')) {
            $request->merge([
                'type' => $request->input('task_mode') === 'coding' ? 'coding' : 'tugas',
                'question_type' => $request->input('task_mode') === 'coding' ? 'coding' : 'uraian',
            ]);
        }
        if ($request->input('type') === 'materi' && $request->input('material_mode') === 'coding') {
            $request->merge(['question_type' => 'coding']);
        }
        if (! $request->filled('title') && $request->filled('module')) {
            $request->merge(['title' => $request->input('module')]);
        }

        if ($request->filled('link') && ! preg_match('#^https?://#i', (string) $request->input('link'))) {
            $request->merge(['link' => 'https://'.ltrim((string) $request->input('link'), '/')]);
        }

        $data = $request->validate([
            'title' => 'required|string|max:160', 'module' => 'required|string|max:100',
            'type' => ['required', Rule::in(['materi', 'tugas', 'coding', 'kuis', 'uts', 'uas', 'pengumuman', 'lainnya'])],
            'custom_type' => 'nullable|string|max:10',
            'task_mode' => 'nullable|in:regular,coding',
            'material_mode' => 'nullable|in:regular,coding',
            'body' => 'required|string|max:15000', 'due' => 'nullable|date',
            'allow_late' => 'nullable|in:0,1,true,false',
            'link' => 'nullable|url:http,https|max:2000',
            'pin_video' => 'nullable|boolean',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp,mp4,webm|max:20480',
            'formats' => 'required_if:type,tugas,kuis,uts,uas,coding,lainnya|array|min:1',
            'formats.*' => [Rule::in(['file', 'image', 'link', 'text'])],
            'question_type' => ['required', Rule::in(['uraian', 'pilihan', 'kompleks', 'coding', 'benar_salah', 'mencocokkan'])],
            'code_language' => 'nullable|in:python,web',
            'question_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_alt' => 'nullable|string|max:300',
            'option_images' => 'nullable|array|max:20',
            'option_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'points' => 'nullable|integer|min:1|max:1000',
            'component' => ['nullable', Rule::in(array_column($academic['components'], 'code'))],
            'questions' => 'nullable|array|min:1|max:30',
            'questions.*.type' => ['required', Rule::in(['uraian', 'pilihan', 'kompleks', 'benar_salah', 'mencocokkan'])],
            'questions.*.prompt' => 'required|string|max:10000',
            'questions.*.points' => 'nullable|integer|min:1|max:1000',
            'questions.*.cpmk' => ['required', Rule::in(array_column($academic['cpmk'], 'code'))],
            'questions.*.score_mode' => 'nullable|string|in:parsial,semua_atau_nol',
            'questions.*.correct_answer' => 'nullable|string|max:500',
            'questions.*.essay_guide' => 'nullable|string|max:5000',
            'questions.*.boolean_answer' => 'nullable|string|in:Benar,Salah',
            'questions.*.options' => 'nullable|string|max:10000000',
            'questions.*.matching' => 'nullable|array',
            'questions.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'questions.*.alt' => 'nullable|string|max:300',
            'coding_steps' => 'nullable|array|min:1|max:20',
            'coding_steps.*.title' => 'required|string|max:160',
            'coding_steps.*.body' => 'required|string|max:15000',
            'coding_steps.*.cpmk' => ['required', Rule::in(array_column($academic['cpmk'], 'code'))],
            'coding_steps.*.link' => 'nullable|url:http,https|max:2000',
            'coding_steps.*.attachment' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp,mp4,webm|max:20480',
            'manual_cpmk_weights' => 'nullable|array',
            'manual_cpmk_weights.*' => 'nullable|numeric|min:0|max:100',
            'options' => 'nullable|string|max:10000000', 'cpmk' => 'required|string|max:1000',
            'duration_mode' => 'nullable|in:enabled,disabled',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);
        if ($data['type'] === 'lainnya') {
            $customType = strtoupper(trim((string) $request->input('custom_type')));
            if (! in_array($customType, ['UTS', 'UAS'], true)) {
                return back()->withErrors(['custom_type' => 'Pilihan Lainnya hanya boleh diisi "UTS" atau "UAS".'])->withInput();
            }
            $data['type'] = strtolower($customType);
        }

        $category = $data['type'];
        if (in_array($category, ['kuis', 'uts', 'uas'], true)
            && ($request->hasFile('attachments') || $request->filled('link') || $request->boolean('pin_video'))) {
            return back()->withErrors([
                'attachments' => 'Kuis, UTS, dan UAS dikerjakan langsung di ruang soal dan tidak menerima lampiran berkas atau tautan pengumpulan.',
            ])->withInput();
        }
        $isCodingContent = $category === 'coding' || ($category === 'materi' && ($data['material_mode'] ?? null) === 'coding');
        $submittedQuestions = ! empty($data['questions']);

        if ($isCodingContent && empty($data['coding_steps'])) {
            $validCpmk = array_column($academic['cpmk'], 'code');
            $fallbackCpmk = in_array($data['cpmk'] ?? null, $validCpmk, true)
                ? $data['cpmk']
                : ($validCpmk[0] ?? 'CPMK');
            $data['coding_steps'] = [[
                'title' => $data['module'],
                'body' => $data['body'],
                'cpmk' => $fallbackCpmk,
                'link' => null,
                'attachment' => null,
            ]];
        }

        if ($category === 'tugas' && ! $submittedQuestions) {
            $manualWeights = array_filter(
                $data['manual_cpmk_weights'] ?? [],
                fn ($weight) => (float) $weight > 0
            );
            if (empty($manualWeights)) {
                $validCpmk = array_column($academic['cpmk'], 'code');
                $fallbackCpmk = in_array($data['cpmk'] ?? null, $validCpmk, true) ? $data['cpmk'] : ($validCpmk[0] ?? 'CPMK');
                $manualWeights = [$fallbackCpmk => 100];
            } elseif (abs(array_sum($manualWeights) - 100) >= 0.01) {
                return back()->withErrors(['manual_cpmk_weights' => 'Pilih CPMK dan pastikan total persentasenya tepat 100%.'])->withInput();
            }
            $data['manual_cpmk_weights'] = array_map('floatval', $manualWeights);
        }

        if (in_array($data['question_type'], ['pilihan', 'kompleks']) && in_array($category, ['tugas', 'kuis', 'uts', 'uas'], true)) {
            $request->validate(['options' => ['required', function ($attribute, $value, $fail) {
                $options = array_filter(array_map('trim', explode("\n", $value)), fn ($option) => $option !== '');
                if (count($options) < 2 || count($options) > 20 || count(array_unique($options)) !== count($options)) {
                    $fail('Masukkan 2 sampai 20 pilihan berbeda, satu pilihan per baris.');
                }
            }]]);
        }
        $optionCount = count(array_filter(array_map('trim', explode("\n", $data['options'] ?? '')), fn ($option) => $option !== ''));
        foreach (array_keys($request->file('option_images', [])) as $index) {
            abort_unless(ctype_digit((string) $index) && (int) $index < $optionCount && in_array($data['question_type'], ['pilihan', 'kompleks']), 422);
        }
        foreach ($data['questions'] ?? [] as $index => $question) {
            if (in_array($question['type'], ['pilihan', 'kompleks'])) {
                $options = array_values(array_unique(array_filter(
                    array_map('trim', preg_split('/\R/u', (string) ($question['options'] ?? '')) ?: []),
                    fn ($value) => $value !== ''
                )));
                if (count($options) < 2 || count($options) > 20) {
                    return back()->withErrors(["questions.$index.options" => 'Isi 2–20 pilihan berbeda untuk soal '.($index + 1).'.'])->withInput();
                }
                $data['questions'][$index]['options'] = implode("\n", $options);
            }
        }
        if (! empty($data['questions'])) {
            if (! in_array($category, ['tugas', 'coding', 'kuis', 'uts', 'uas'], true)) {
                return back()->withErrors(['type' => 'Paket soal campuran hanya dapat digunakan untuk Tugas, Kuis, UTS, dan UAS.'])->withInput();
            }
            foreach ($data['questions'] as $index => &$question) {
                $question['points'] = (isset($question['points']) && (int) $question['points'] > 0) ? (int) $question['points'] : 100;
                $question['is_essay'] = ($question['type'] === 'uraian');
                $question['score_mode'] = $question['score_mode'] ?? 'parsial';
                $question['correct_answer'] = $question['correct_answer'] ?? null;
                $question['essay_guide'] = $question['essay_guide'] ?? null;
                $question['boolean_answer'] = $question['boolean_answer'] ?? null;
                $question['image'] = $request->hasFile("questions.$index.image") ? $this->upload($request->file("questions.$index.image")) : null;
                $question['alt'] = $question['image']
                    ? trim((string) ($question['alt'] ?? '')) ?: Str::limit('Gambar pendukung untuk '.strip_tags($question['prompt']), 300, '')
                    : null;
                $mapping = collect($academic['cpmk'])->first(function ($c) use ($question) {
                    return strcasecmp(trim(str_replace(' ', '-', $c['code'])), trim(str_replace(' ', '-', $question['cpmk']))) === 0;
                });
                $question['cpl'] = $mapping['cpl'] ?? 'CPL';
            }
            unset($question);
            $data['questions'] = QuizQuestion::canonicalizeQuestions($data['questions']);
            $data['points'] = array_sum(array_column($data['questions'], 'points'));
            $data['component'] ??= in_array($category, ['kuis', 'uts', 'uas'], true) ? $category : 'tugas';
            $data['scoring_mode'] = 'automatic_cpmk';
        }
        if ($isCodingContent) {
            foreach ($data['coding_steps'] as $index => &$step) {
                $step['attachment'] = $request->hasFile("coding_steps.$index.attachment")
                    ? $this->upload($request->file("coding_steps.$index.attachment"))
                    : null;
                $step['link'] = $step['link'] ?? null;
            }
            unset($step);
            $data['coding_steps'] = array_values($data['coding_steps']);
            if ($category === 'coding') {
                $data['questions'] = array_map(fn ($step) => [
                    'type' => 'coding',
                    'prompt' => $step['title'],
                    'cpmk' => $step['cpmk'],
                    'points' => 100,
                ], $data['coding_steps']);
                $data['points'] = count($data['questions']) * 100;
                $data['component'] = 'tugas';
                $data['scoring_mode'] = 'automatic_cpmk';
            }
        }
        if ($category === 'tugas' && ! $submittedQuestions) {
            $data['scoring_mode'] = 'manual_cpmk';
            $data['cpmk'] = array_key_first($data['manual_cpmk_weights']);
            $data['component'] = 'tugas';
        }
        $data['question_image'] = $request->hasFile('question_image') ? $this->upload($request->file('question_image')) : null;
        $data['image_alt'] = $data['question_image']
            ? trim((string) ($data['image_alt'] ?? '')) ?: 'Gambar pendukung untuk '.$data['title']
            : null;
        $data['option_images'] = array_map(fn ($file) => $this->upload($file), $request->file('option_images', []));
        $data['attachments'] = array_map(fn ($file) => $this->upload($file), $request->file('attachments', []));
        $data['pin_video'] = $request->boolean('pin_video');

        if ($data['pin_video'] && $category === 'materi') {
            $videoAttachments = collect($data['attachments'])->filter(function ($file) {
                return str_starts_with((string) Attachment::where('uuid', $file)->value('mime'), 'video/');
            });
            $imageAttachments = collect($data['attachments'])->filter(function ($file) {
                return str_starts_with((string) Attachment::where('uuid', $file)->value('mime'), 'image/');
            });
            $videoLink = trim((string) ($data['link'] ?? ''));
            $playableLink = $videoLink !== '' && (
                Learning::youtubeEmbedUrl($videoLink) !== null
                || (bool) preg_match('/\.(?:mp4|webm|ogg)(?:[?#].*)?$/i', $videoLink)
            );

            $target = $request->input('pin_media_target', 'auto');
            $pinnedVal = null;
            $pinnedType = 'url';
            $mediaKind = 'video';

            if ($target === 'link' && $playableLink) {
                $pinnedVal = $videoLink;
                $pinnedType = 'url';
                $mediaKind = 'video';
            } elseif ($target !== 'auto' && $target !== 'link') {
                $matchedFile = collect($data['attachments'])->first(function ($file) use ($target) {
                    $name = Attachment::where('uuid', $file)->value('name') ?? '';

                    return $name === $target || $file === $target;
                });
                if ($matchedFile) {
                    $mime = (string) Attachment::where('uuid', $matchedFile)->value('mime');
                    $isVid = str_starts_with($mime, 'video/');
                    $pinnedVal = $matchedFile;
                    $pinnedType = $isVid ? 'file' : 'image';
                    $mediaKind = $isVid ? 'video' : 'image';
                }
            }

            if (! $pinnedVal) {
                if ($playableLink) {
                    $pinnedVal = $videoLink;
                    $pinnedType = 'url';
                    $mediaKind = 'video';
                } elseif ($videoAttachments->isNotEmpty()) {
                    $pinnedVal = $videoAttachments->first();
                    $pinnedType = 'file';
                    $mediaKind = 'video';
                } elseif ($imageAttachments->isNotEmpty()) {
                    $pinnedVal = $imageAttachments->first();
                    $pinnedType = 'image';
                    $mediaKind = 'image';
                } elseif (! empty($data['question_image'])) {
                    $pinnedVal = $data['question_image'];
                    $pinnedType = 'image';
                    $mediaKind = 'image';
                }
            }

            if (! $pinnedVal) {
                return back()->withErrors(['pin_video' => 'Pilih tautan video, berkas video MP4, atau foto materi terlebih dahulu untuk disematkan.'])->withInput();
            }

            $data['video'] = $pinnedVal;
            $data['video_type'] = $pinnedType;
            $data['video_title'] = $data['title'];
            $data['media_kind'] = $mediaKind;

        }
        $data['points'] = $data['points'] ?? 100;
        $data['allow_late'] = $request->boolean('allow_late', true);
        if (in_array($category, ['kuis', 'uts', 'uas'], true) && ! empty($data['due'])) {
            $data['allow_late'] = false;
        }
        $data['duration_enabled'] = $request->input('duration_mode', 'disabled') === 'enabled';
        $data['duration_minutes'] = $data['duration_enabled'] ? (int) $request->input('duration_minutes', 60) : null;
        $data['randomize_questions'] = $request->boolean('randomize_questions', false);
        $data['language'] = $data['question_type'] === 'coding' ? ($data['code_language'] ?? 'python') : 'python';
        $data['ai_enabled'] = $data['question_type'] === 'coding' && ! in_array($category, ['kuis', 'uts', 'uas'], true);
        $data += ['formats' => [], 'link' => null, 'due' => null, 'options' => null];
        if (Schema::hasTable('class_sections') && Schema::hasTable('assessments')) {
            if ($section && in_array($category, ['materi', 'tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'pengumuman'], true)) {
                $assessmentType = ($category === 'coding') ? 'tugas' : $category;
                $asmCount = Assessment::where('class_section_id', $section->id)->count();
                $questionImages = collect($data['questions'] ?? [])->pluck('image')->filter();
                $fileIds = collect($data['attachments'])
                    ->merge($data['option_images'])
                    ->merge([$data['question_image']])
                    ->merge($questionImages)
                    ->filter()
                    ->unique();
                $data['file_meta'] = Attachment::whereIn('uuid', $fileIds)->get()
                    ->mapWithKeys(fn (Attachment $attachment) => [$attachment->uuid => [
                        'path' => $attachment->path,
                        'name' => $attachment->name,
                        'mime' => $attachment->mime,
                    ]])->all();
                $databasePayload = $data;
                unset($databasePayload['id'], $databasePayload['course']);
                $assessment = Assessment::create([
                    'class_section_id' => $section->id,
                    'code' => strtoupper($category).'-'.($asmCount + 1),
                    'name' => $data['title'],
                    'type' => $assessmentType,
                    'description' => $data['body'],
                    'learning_payload' => $databasePayload,
                    'final_weight' => in_array($category, ['materi', 'pengumuman'], true) ? 0 : 10,
                    'uses_rubric' => false,
                    'status' => Assessment::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'due_at' => ! empty($data['due']) ? Carbon::parse($data['due']) : null,
                    'allow_late' => $data['allow_late'],
                ]);

                if (Schema::hasTable('attachments') && $fileIds->isNotEmpty()) {
                    Attachment::whereIn('uuid', $fileIds)
                        ->where('user_id', $user->id)
                        ->update([
                            'class_section_id' => $section->id,
                            'assessment_id' => $assessment->id,
                        ]);
                }

                // Sinkronisasi bobot CPMK ke database (tabel assessment_cpmk)
                if (! in_array($category, ['materi', 'pengumuman'], true)) {
                    $syncData = [];
                    $mataKuliahCpmks = $section->mataKuliah?->cpmks()->get();
                    $allCpmks = ($mataKuliahCpmks && $mataKuliahCpmks->isNotEmpty()) ? $mataKuliahCpmks : (Schema::hasTable('cpmks') ? Cpmk::all() : collect());

                    $findCpmk = function ($code) use ($allCpmks) {
                        $normTarget = preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim((string) $code))));
                        return $allCpmks->first(function ($c) use ($normTarget) {
                            $normC = preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($c->code))));
                            return $normC === $normTarget;
                        }) ?? (Schema::hasTable('cpmks') ? Cpmk::where('code', $code)->orWhere('code', str_replace(' ', '-', $code))->first() : null);
                    };

                    if (! empty($data['questions'])) {
                        $cpmkCounts = array_count_values(array_filter(array_column($data['questions'], 'cpmk')));
                        $totalQ = max(1, count($data['questions']));
                        $accumulated = 0.0;
                        $itemsLeft = count($cpmkCounts);
                        foreach ($cpmkCounts as $code => $cnt) {
                            $itemsLeft--;
                            $cpmkModel = $findCpmk($code);
                            if ($cpmkModel) {
                                if ($itemsLeft === 0) {
                                    $w = round(100.00 - $accumulated, 2);
                                } else {
                                    $w = round(($cnt / $totalQ) * 100, 2);
                                    $accumulated += $w;
                                }
                                $syncData[$cpmkModel->id] = ['weight' => $w];
                            }
                        }
                    } elseif (! empty($data['manual_cpmk_weights'])) {
                        foreach ($data['manual_cpmk_weights'] as $code => $weight) {
                            $cpmkModel = $findCpmk($code);
                            if ($cpmkModel && (float) $weight > 0) {
                                $syncData[$cpmkModel->id] = ['weight' => (float) $weight];
                            }
                        }
                    }

                    if (empty($syncData)) {
                        $cpmk = $section->mataKuliah?->cpmks()->first() ?? (Schema::hasTable('cpmks') ? Cpmk::first() : null);
                        if ($cpmk) {
                            $syncData[$cpmk->id] = ['weight' => 100];
                        }
                    }

                    if (! empty($syncData)) {
                        $assessment->cpmks()->sync($syncData);
                    }
                }
            }
        }

        if ($data['ai_enabled'] && Schema::hasTable('ai_tasks')) {
            DB::table('ai_tasks')->updateOrInsert(
                ['id' => $assessment->id],
                ['title' => $data['title'], 'body' => $data['body'], 'enabled' => true]
            );
        }

        return redirect()->route('dosen.course.show', $course)->with('notice', 'Konten berhasil disimpan ke database.');
    }

    public function editItem(int $course, int $item)
    {
        $user = auth()->user();
        $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->findOrFail($course);
        abort_unless($user?->hasRole(Role::DOSEN) && $user->can('manage', $section), 403, 'Anda bukan pengampu kelas ini.');
        $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);
        $itemData = Learning::databaseAssessment($assessment);
        $courseData = Learning::databaseCourse($section);

        return view('dosen.item-form', [
            'course' => $courseData,
            'item' => $itemData,
            'isEdit' => true,
            'assessment' => $assessment,
            'modules' => $section->assessments()->get()->pluck('learning_payload.module')->filter()->unique()->values(),
        ]);
    }

    public function updateItem(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        $section = null;
        $assessment = null;

        if ($user && $user->hasRole(Role::DOSEN) && Schema::hasTable('class_sections')) {
            $section = ClassSection::find($course);
            if ($section && ! $user->can('manage', $section)) {
                abort(403, 'Anda bukan pengampu kelas ini.');
            }
            if ($section && Schema::hasTable('assessments')) {
                $assessment = Assessment::where('class_section_id', $section->id)->find($item);
            }
        }

        abort_unless($assessment, 404, 'Konten tidak ditemukan.');
        $existingItem = Learning::databaseAssessment($assessment);

        if ($request->input('type') === 'tugas' && $request->filled('task_mode')) {
            $request->merge([
                'type' => $request->input('task_mode') === 'coding' ? 'coding' : 'tugas',
                'question_type' => $request->input('task_mode') === 'coding' ? 'coding' : 'uraian',
            ]);
        }
        if ($request->input('type') === 'materi' && $request->input('material_mode') === 'coding') {
            $request->merge(['question_type' => 'coding']);
        }
        if (! $request->filled('title') && $request->filled('module')) {
            $request->merge(['title' => $request->input('module')]);
        }
        if ($request->filled('link') && ! preg_match('#^https?://#i', (string) $request->input('link'))) {
            $request->merge(['link' => 'https://'.ltrim((string) $request->input('link'), '/')]);
        }

        $academic = AcademicPreview::config($course);

        $data = $request->validate([
            'title' => 'required|string|max:160', 'module' => 'required|string|max:100',
            'type' => ['required', Rule::in(['materi', 'tugas', 'coding', 'kuis', 'uts', 'uas', 'pengumuman', 'lainnya'])],
            'custom_type' => 'nullable|string|max:10',
            'task_mode' => 'nullable|in:regular,coding',
            'material_mode' => 'nullable|in:regular,coding',
            'body' => 'required|string|max:15000', 'due' => 'nullable|date',
            'allow_late' => 'nullable|in:0,1,true,false',
            'link' => 'nullable|url:http,https|max:2000',
            'pin_video' => 'nullable|boolean',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp,mp4,webm|max:20480',
            'formats' => 'nullable|array',
            'formats.*' => [Rule::in(['file', 'image', 'link', 'text'])],
            'question_type' => ['nullable', Rule::in(['uraian', 'pilihan', 'kompleks', 'coding', 'benar_salah', 'mencocokkan'])],
            'code_language' => 'nullable|in:python,web',
            'question_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_alt' => 'nullable|string|max:300',
            'option_images' => 'nullable|array|max:20',
            'option_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'points' => 'nullable|integer|min:1|max:1000',
            'component' => ['nullable', Rule::in(array_column($academic['components'], 'code'))],
            'questions' => 'nullable|array|min:1|max:30',
            'questions.*.type' => ['required', Rule::in(['uraian', 'pilihan', 'kompleks', 'benar_salah', 'mencocokkan'])],
            'questions.*.prompt' => 'required|string|max:10000',
            'questions.*.points' => 'nullable|integer|min:1|max:1000',
            'questions.*.cpmk' => ['required', Rule::in(array_column($academic['cpmk'], 'code'))],
            'questions.*.score_mode' => 'nullable|string|in:parsial,semua_atau_nol',
            'questions.*.correct_answer' => 'nullable|string|max:500',
            'questions.*.essay_guide' => 'nullable|string|max:5000',
            'questions.*.boolean_answer' => 'nullable|string|in:Benar,Salah',
            'questions.*.options' => 'nullable|string|max:10000000',
            'questions.*.matching' => 'nullable|array',
            'questions.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'questions.*.alt' => 'nullable|string|max:300',
            'coding_steps' => 'nullable|array|min:1|max:20',
            'coding_steps.*.title' => 'required|string|max:160',
            'coding_steps.*.body' => 'required|string|max:15000',
            'coding_steps.*.cpmk' => ['required', Rule::in(array_column($academic['cpmk'], 'code'))],
            'coding_steps.*.link' => 'nullable|url:http,https|max:2000',
            'coding_steps.*.attachment' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp,mp4,webm|max:20480',
            'manual_cpmk_weights' => 'nullable|array',
            'manual_cpmk_weights.*' => 'nullable|numeric|min:0|max:100',
            'options' => 'nullable|string|max:10000000', 'cpmk' => 'nullable|string|max:1000',
            'duration_mode' => 'nullable|in:enabled,disabled',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'randomize_questions' => 'nullable|boolean',
        ]);

        $category = $data['type'];
        $data['question_type'] = $data['question_type'] ?? ($existingItem['question_type'] ?? 'uraian');
        $data['formats'] = $data['formats'] ?? ($existingItem['formats'] ?? ['file', 'image', 'link', 'text']);
        $data['allow_late'] = $request->boolean('allow_late', true);
        $data['duration_enabled'] = $request->input('duration_mode', 'disabled') === 'enabled';
        $data['duration_minutes'] = $data['duration_enabled'] ? (int) $request->input('duration_minutes', 60) : null;
        $data['randomize_questions'] = $request->boolean('randomize_questions', false);
        $data['pin_video'] = $request->boolean('pin_video');

        $newAttachments = array_map(fn ($file) => $this->upload($file), $request->file('attachments', []));
        $data['attachments'] = ! empty($newAttachments) ? $newAttachments : ($existingItem['attachments'] ?? []);

        if ($request->hasFile('question_image')) {
            $data['question_image'] = $this->upload($request->file('question_image'));
            $data['image_alt'] = trim((string) ($data['image_alt'] ?? '')) ?: 'Gambar pendukung untuk '.$data['title'];
        } else {
            $data['question_image'] = $existingItem['question_image'] ?? null;
            $data['image_alt'] = $existingItem['image_alt'] ?? null;
        }

        if (! empty($data['questions'])) {
            foreach ($data['questions'] as $index => &$question) {
                if (in_array($question['type'], ['pilihan', 'kompleks'])) {
                    $options = array_values(array_unique(array_filter(
                        array_map('trim', preg_split('/\R/u', (string) ($question['options'] ?? '')) ?: []),
                        fn ($value) => $value !== ''
                    )));
                    $question['options'] = implode("\n", $options);
                }
                $question['points'] = (isset($question['points']) && (int) $question['points'] > 0) ? (int) $question['points'] : 100;
                $question['is_essay'] = ($question['type'] === 'uraian');
                $question['score_mode'] = $question['score_mode'] ?? 'parsial';
                $question['correct_answer'] = $question['correct_answer'] ?? null;
                $question['essay_guide'] = $question['essay_guide'] ?? null;
                $question['boolean_answer'] = $question['boolean_answer'] ?? null;
                if ($request->hasFile("questions.$index.image")) {
                    $question['image'] = $this->upload($request->file("questions.$index.image"));
                } elseif (! empty($question['existing_image'])) {
                    $question['image'] = $question['existing_image'];
                } elseif (isset($question['existing_image']) && $question['existing_image'] === '') {
                    $question['image'] = null;
                } else {
                    $question['image'] = $existingItem['questions'][$index]['image'] ?? null;
                }
                $mapping = collect($academic['cpmk'])->first(function ($c) use ($question) {
                    return strcasecmp(trim(str_replace(' ', '-', $c['code'])), trim(str_replace(' ', '-', $question['cpmk']))) === 0;
                });
                $question['cpl'] = $mapping['cpl'] ?? 'CPL';
            }
            unset($question);
            $data['questions'] = QuizQuestion::canonicalizeQuestions($data['questions']);
            $data['points'] = array_sum(array_column($data['questions'], 'points'));
            $data['component'] ??= in_array($category, ['kuis', 'uts', 'uas'], true) ? $category : 'tugas';
            $data['scoring_mode'] = 'automatic_cpmk';
        } else {
            $data['points'] = $data['points'] ?? ($existingItem['points'] ?? 100);
        }

        if ($data['pin_video'] && $category === 'materi') {
            $pinnedVal = $data['link'] ?? null;
            $pinnedType = 'url';
            $mediaKind = 'video';
            if ($pinnedVal) {
                $data['video'] = $pinnedVal;
                $data['video_type'] = $pinnedType;
                $data['video_title'] = $data['title'];
                $data['media_kind'] = $mediaKind;
            } elseif (! empty($data['attachments'])) {
                $att = $data['attachments'][0];
                $mime = (string) Attachment::where('uuid', $att)->value('mime');
                $isVid = str_starts_with($mime, 'video/');
                $data['video'] = $att;
                $data['video_type'] = $isVid ? 'file' : 'image';
                $data['video_title'] = $data['title'];
                $data['media_kind'] = $isVid ? 'video' : 'image';
            }
        }

        if ($assessment) {
            $databasePayload = array_merge($existingItem, $data);
            unset($databasePayload['id'], $databasePayload['course']);

            $assessment->update([
                'name' => $data['title'],
                'description' => $data['body'],
                'type' => ($category === 'coding') ? 'tugas' : $category,
                'learning_payload' => $databasePayload,
                'due_at' => ! empty($data['due']) ? Carbon::parse($data['due']) : null,
                'allow_late' => $data['allow_late'],
            ]);

            $fileIds = collect($data['attachments'] ?? [])
                ->merge([$data['question_image'] ?? null])
                ->merge(collect($data['questions'] ?? [])->pluck('image'))
                ->merge(collect($data['coding_steps'] ?? [])->pluck('attachment'))
                ->filter()
                ->unique();
            if ($fileIds->isNotEmpty()) {
                Attachment::whereIn('uuid', $fileIds)
                    ->where('user_id', $user->id)
                    ->update([
                        'class_section_id' => $section->id,
                        'assessment_id' => $assessment->id,
                    ]);
            }

            if (! in_array($category, ['materi', 'pengumuman'], true)) {
                $mataKuliahCpmks = $section->mataKuliah?->cpmks()->get();
                $allCpmks = ($mataKuliahCpmks && $mataKuliahCpmks->isNotEmpty()) ? $mataKuliahCpmks : (Schema::hasTable('cpmks') ? Cpmk::all() : collect());

                $findCpmk = function ($code) use ($allCpmks) {
                    $normTarget = preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim((string) $code))));
                    return $allCpmks->first(function ($c) use ($normTarget) {
                        $normC = preg_replace('/^CPMK0*([0-9]+)$/', 'CPMK$1', preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($c->code))));
                        return $normC === $normTarget;
                    }) ?? (Schema::hasTable('cpmks') ? Cpmk::where('code', $code)->orWhere('code', str_replace(' ', '-', $code))->first() : null);
                };

                $syncData = [];
                if (! empty($data['questions'])) {
                    $cpmkCounts = array_count_values(array_filter(array_column($data['questions'], 'cpmk')));
                    $accumulated = 0.0;
                    $itemsLeft = count($cpmkCounts);
                    $totalQ = max(1, count($data['questions']));
                    foreach ($cpmkCounts as $code => $cnt) {
                        $itemsLeft--;
                        $cpmkModel = $findCpmk($code);
                        if ($cpmkModel) {
                            $w = ($itemsLeft === 0) ? round(100.00 - $accumulated, 2) : round(($cnt / $totalQ) * 100, 2);
                            $accumulated += $w;
                            $syncData[$cpmkModel->id] = ['weight' => $w];
                        }
                    }
                } elseif (! empty($data['manual_cpmk_weights'])) {
                    foreach ($data['manual_cpmk_weights'] as $code => $weight) {
                        $cpmkModel = $findCpmk($code);
                        if ($cpmkModel && (float) $weight > 0) {
                            $syncData[$cpmkModel->id] = ['weight' => (float) $weight];
                        }
                    }
                }
                if (! empty($syncData)) {
                    $assessment->cpmks()->sync($syncData);
                }
            }
        }

        return redirect()->route('dosen.course.item', [$course, $item])->with('notice', 'Konten berhasil diperbarui.');
    }

    public function destroyItem(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);
        $section = ClassSection::findOrFail($course);
        abort_unless($user->can('manage', $section), 403, 'Anda bukan pengampu kelas ini.');
        $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);

        DB::transaction(function () use ($assessment) {
            StudentAssessmentScore::where('assessment_id', $assessment->id)->delete();
            StudentAssessmentCpmkScore::where('assessment_id', $assessment->id)->delete();
            $assessment->cpmks()->detach();
            Attachment::where('assessment_id', $assessment->id)->delete();
            $assessment->delete();
        });

        return redirect()->route('dosen.course.show', $course)->with('notice', 'Konten berhasil dihapus.');
    }

    public function discussCourse(Request $request, int $course)
    {
        $data = $request->validate(['message' => 'required|string|max:3000']);
        $user = auth()->user();
        $this->assertCourseDiscussionAccess($course, $user);
        $newMessage = $this->persistCourseDiscussion($course, $data['message'], $user);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $newMessage,
                'total' => Message::where('room_id', $newMessage['room_id'])->count(),
            ]);
        }

        $redirectRoute = $user->hasRole(Role::DOSEN) ? 'dosen.course.show' : 'mahasiswa.course.show';

        return redirect(route($redirectRoute, $course).'#diskusi-kelas');
    }

    public function discuss(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        Assessment::where('class_section_id', $course)->findOrFail($item);
        $data = $request->validate(['message' => 'required|string|max:3000']);
        $this->assertCourseDiscussionAccess($course, $user);
        $this->persistCourseDiscussion($course, $data['message'], $user);

        $redirectRoute = $user->hasRole(Role::DOSEN) ? 'dosen.course.show' : 'mahasiswa.course.show';

        return redirect(route($redirectRoute, $course).'#diskusi-kelas');
    }

    private function persistCourseDiscussion(int $course, string $content, User $user): array
    {
        $section = ClassSection::with('mataKuliah')->findOrFail($course);
        $room = Room::forCourse($course, $section->mataKuliah?->name);
        $room->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $user->hasRole(Role::DOSEN) ? 'dosen' : 'mahasiswa',
                'joined_at' => now(),
            ],
        ]);

        $message = Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'content' => $content,
        ]);

        return $message->load('user.role')->toChatPayload($user);
    }

    private function assertCourseDiscussionAccess(int $course, ?User $user): void
    {
        abort_unless($user, 401);
        $section = ClassSection::findOrFail($course);

        $canAccess = $user->hasRole(Role::DOSEN)
            ? $user->can('manage', $section)
            : ($user->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists());

        abort_unless($canAccess, 403);
    }

    public function submit(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        $assessment = null;
        $assessmentAttempt = null;
        $resource = null;

        if ($user && Schema::hasTable('assessments')) {
            $assessment = Assessment::where('class_section_id', $course)->find($item);
            if ($assessment) {
                $isEnrolled = $user->hasRole(Role::MAHASISWA)
                    && $user->classSectionsEnrolled()->where('class_sections.id', $course)->exists();
                abort_unless($isEnrolled, 403);
                abort_unless($assessment->status === 'published', 403, 'Asesmen belum tersedia atau sudah ditutup.');
                $resource = Learning::databaseAssessment($assessment);
            }
        }

        abort_unless($assessment && $resource, 404, 'Asesmen tidak ditemukan pada database.');

        abort_unless(in_array($resource['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'], true), 404);

        if ($assessment) {
            $assessmentAttempt = AssessmentAttempt::where('assessment_id', $assessment->id)
                ->where('mahasiswa_id', $user->id)
                ->latest('attempt')
                ->first();

            if (! $assessmentAttempt && $this->timedDurationMinutes($resource) !== null) {
                return back()->withErrors([
                    'submission' => 'Attempt belum dimulai. Buka ruang kuis terlebih dahulu.',
                ])->withInput();
            }

            if ($assessmentAttempt && $assessmentAttempt->status !== AssessmentAttempt::STATUS_IN_PROGRESS) {
                return back()->withErrors([
                    'submission' => $assessmentAttempt->rejection_reason ?? 'Attempt ini sudah selesai dan tidak dapat dikirim ulang.',
                ])->withInput();
            }

            if ($assessmentAttempt && now()->greaterThanOrEqualTo($assessmentAttempt->deadline_at)) {
                $reason = 'Submission ditolak karena melewati deadline attempt pada '.$assessmentAttempt->deadline_at->format('d M Y, H:i:s').'.';
                $assessmentAttempt->update([
                    'status' => AssessmentAttempt::STATUS_REJECTED,
                    'rejected_at' => now(),
                    'rejection_reason' => $reason,
                ]);

                return back()->withErrors(['submission' => $reason])->withInput();
            }
        }

        if ($assessment && Schema::hasTable('student_assessment_scores')) {
            $hasBeenGraded = StudentAssessmentScore::where('assessment_id', $assessment->id)
                ->where('mahasiswa_id', $user->id)
                ->where(function ($query) {
                    $query->whereIn('status', [
                        StudentAssessmentScore::STATUS_PARTIAL,
                        StudentAssessmentScore::STATUS_FINAL,
                        StudentAssessmentScore::STATUS_PUBLISHED,
                    ])->orWhereNotNull('score');
                })
                ->exists();

            if ($hasBeenGraded) {
                return back()->withErrors([
                    'submission' => 'Jawaban tidak dapat dikirim ulang karena tugas ini sudah dinilai.',
                ])->withInput();
            }
        }

        $isAssignmentType = in_array($resource['type'] ?? '', ['tugas', 'coding', 'pbl', 'case', 'project'], true);
        $allowLate = $isAssignmentType ? true : ($resource['allow_late'] ?? true);
        if (! $allowLate && ! empty($resource['due']) && Carbon::parse($resource['due'])->isPast()) {
            return back()->withErrors(['answer' => 'Batas waktu pengumpulan telah berakhir. Pengampu mengunci tugas ini dan tidak menerima pengumpulan terlambat.'])->withInput();
        }

        $data = $request->validate([
            'question_answers' => 'nullable|array|max:30',
            'question_answers.*.question_id' => 'nullable|string|max:100',
            'question_answers.*.option_ids' => 'nullable|array|max:20',
            'question_answers.*.option_ids.*' => 'string|max:100',
            'question_answers.*.matches' => 'nullable|array|max:50',
            'question_answers.*.matches.*' => 'nullable|string|max:100',
            'question_answers.*.text' => 'nullable|string|max:30000',
            'question_answers.*.choices' => 'nullable|array|max:20',
            'question_answers.*.choices.*' => 'string|max:1000',
            'question_answers.*.boolean_choice' => 'nullable|string|in:Benar,Salah',
            'question_answers.*.matching' => 'nullable|array',
            'answer' => 'nullable|string|max:30000', 'link' => 'nullable|url:http,https|max:2000',
            'files' => 'nullable|array|max:5', 'files.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,webp|max:20480',
            'keep_files' => 'nullable|array|max:5', 'keep_files.*' => 'uuid',
            'choices' => 'nullable|array|max:20', 'choices.*' => 'string|max:1000',
            'boolean_choice' => 'nullable|string|in:Benar,Salah',
            'matching' => 'nullable|array',
        ]);
        $isFromQuizRoom = $request->boolean('from_quiz_room');

        if (! empty($resource['questions'])) {
            $resource['questions'] = QuizQuestion::canonicalizeQuestions($resource['questions']);
            $displayOrder = $user ? session("learning.quiz_order.{$item}.{$user->id}") : null;
            $answers = QuizQuestion::normalizeAnswers(
                $resource['questions'],
                $data['question_answers'] ?? [],
                is_array($displayOrder) ? $displayOrder : null
            );
            if (! $isFromQuizRoom && count($answers) !== count($resource['questions'])) {
                return back()->withErrors(['question_answers' => 'Jawab seluruh soal sebelum mengumpulkan.'])->withInput();
            }
            foreach ($resource['questions'] as $index => $question) {
                $answer = $answers[(string) $question['id']] ?? [];
                if (in_array($question['type'], ['pilihan', 'kompleks'])) {
                    $choices = $answer['option_ids'] ?? [];
                    if (! empty($choices)) {
                        if ($question['type'] === 'pilihan' && count($choices) !== 1) {
                            return back()->withErrors(['question_answers' => 'Periksa pilihan pada soal '.($index + 1).'.'])->withInput();
                        }
                    } elseif (! $isFromQuizRoom) {
                        return back()->withErrors(['question_answers' => 'Periksa pilihan pada soal '.($index + 1).'.'])->withInput();
                    }
                } elseif ($question['type'] === 'benar_salah') {
                    if (empty($answer['option_ids']) && ! $isFromQuizRoom) {
                        return back()->withErrors(['question_answers' => 'Pilih Benar atau Salah pada soal '.($index + 1).'.'])->withInput();
                    }
                } elseif ($question['type'] === 'mencocokkan') {
                    if (empty($answer['matches']) && ! $isFromQuizRoom) {
                        return back()->withErrors(['question_answers' => 'Pasangkan seluruh item pada soal '.($index + 1).'.'])->withInput();
                    }
                } elseif (trim($answer['text'] ?? '') === '' && ! $isFromQuizRoom) {
                    return back()->withErrors(['question_answers' => 'Isi jawaban soal '.($index + 1).'.'])->withInput();
                }
            }
            $data['question_answers'] = $answers;
        } else {
            unset($data['question_answers']);
        }
        $dbSub = Submission::where('assessment_id', $item)->where('mahasiswa_id', $user->id)->first();
        $previousFiles = $dbSub?->file_ids ?? [];
        $keep = $request->input('keep_files', $request->boolean('replace_files') ? [] : $previousFiles);
        abort_if(array_diff($keep, $previousFiles), 422);
        if (count($keep) + count($request->file('files', [])) > 5) {
            return back()->withErrors(['files' => 'Maksimal lima lampiran, termasuk berkas sebelumnya.'])->withInput();
        }
        if (! $isFromQuizRoom && empty($data['question_answers']) && ! $keep && ! $request->filled('answer') && ! $request->filled('link') && ! $request->hasFile('files') && ! $request->filled('choices') && ! $request->filled('boolean_choice') && ! $request->filled('matching')) {
            return back()->withErrors(['answer' => 'Tambahkan jawaban, berkas, atau tautan sebelum mengumpulkan.'])->withInput();
        }
        abort_if($request->filled('link') && ! in_array('link', $resource['formats']), 422);
        abort_if($request->filled('answer') && ! in_array('text', $resource['formats']) && $resource['type'] !== 'coding', 422);
        foreach ($request->file('files', []) as $file) {
            $format = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
            abort_unless(in_array($format, $resource['formats']), 422);
        }
        if ($request->filled('choices')) {
            $options = array_filter(array_map('trim', explode("\n", $resource['options'] ?? '')), fn ($option) => $option !== '');
            abort_unless(in_array($resource['question_type'], ['pilihan', 'kompleks']) && ! array_diff($data['choices'], $options), 422);
            abort_if($resource['question_type'] === 'pilihan' && count($data['choices']) > 1, 422);
        }
        $data['files'] = array_merge($keep, array_map(fn ($file) => $this->upload($file), $request->file('files', [])));
        $data['time'] = now()->format('d M Y, H:i');
        $data['student_number'] = $user->nim_nidn;

        $gradeForSession = null;

        DB::transaction(function () use ($user, $item, $course, $data, $resource, $isFromQuizRoom, $assessmentAttempt, $assessment, &$gradeForSession) {

            // Persist attempt, waktu kirim, status, owner, dan versi jawaban dalam transaksi.
            // Only write to the submissions table when the assessment exists in the database;
            // session-only (preview) items must not trigger FK violations.
            if ($user && $assessment && Schema::hasTable('submissions')) {
                $existing = Submission::where('assessment_id', $item)
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                    ->first();

                $attempt = $existing ? ($existing->attempt + 1) : 1;
                $version = $existing ? ($existing->version + 1) : 1;

                $submission = Submission::updateOrCreate(
                    ['assessment_id' => $item, 'user_id' => $user->id],
                    [
                        'mahasiswa_id' => $user->id,
                        'attempt' => $attempt,
                        'version' => $version,
                        'status' => 'pending',
                        'submitted_at' => now(),
                        'answer' => $data['answer'] ?? null,
                        'link' => $data['link'] ?? null,
                        'question_answers' => $data['question_answers'] ?? null,
                        'file_ids' => $data['files'] ?? [],
                        'student_number' => $data['student_number'] ?? null,
                    ]
                );

                // Simpan versi jawaban ke tabel submission_answers
                if (Schema::hasTable('submission_answers')) {
                    if (! empty($data['question_answers'])) {
                        foreach ($data['question_answers'] as $index => $ans) {
                            SubmissionAnswer::create([
                                'submission_id' => $submission->id,
                                'question_index' => null,
                                'question_id' => $ans['question_id'],
                                'version' => $version,
                                'answer_text' => $ans['text'] ?? null,
                                'link' => $ans['link'] ?? null,
                                'choices' => $ans['option_ids'] ?? null,
                                'boolean_choice' => null,
                                'matching' => $ans['matches'] ?? null,
                            ]);
                        }
                    } elseif (! empty($data['answer']) || ! empty($data['link']) || ! empty($data['choices']) || ! empty($data['boolean_choice']) || ! empty($data['matching'])) {
                        SubmissionAnswer::create([
                            'submission_id' => $submission->id,
                            'question_index' => null,
                            'version' => $version,
                            'answer_text' => $data['answer'] ?? null,
                            'link' => $data['link'] ?? null,
                            'choices' => $data['choices'] ?? null,
                            'boolean_choice' => $data['boolean_choice'] ?? null,
                            'matching' => $data['matching'] ?? null,
                        ]);
                    }
                }

                // Simpan atau hubungkan berkas ke tabel attachments
                if (Schema::hasTable('attachments') && ! empty($data['files'])) {
                    foreach ($data['files'] as $fileUuid) {
                        $attachmentMeta = Attachment::where('uuid', $fileUuid)->first();
                        Attachment::updateOrCreate(
                            ['uuid' => $fileUuid],
                            [
                                'user_id' => $user->id,
                                'class_section_id' => $course,
                                'assessment_id' => $item,
                                'submission_id' => $submission->id,
                                'path' => $attachmentMeta?->path ?? '',
                                'name' => $attachmentMeta?->name ?? '',
                                'mime' => $attachmentMeta?->mime,
                            ]
                        );
                    }
                }
            }

            if (in_array($resource['type'], ['kuis', 'uts', 'uas'], true) || $isFromQuizRoom) {
                $questions = $resource['questions'] ?? [];
                if (! empty($questions)) {
                    $answers = $data['question_answers'] ?? [];
                    if (isset($submission) && $assessment) {
                        $result = $this->quizGrades->recordAutomaticScores($assessment, $submission, $questions, $answers);
                        $gradeForSession = $result['score'];
                    }
                }
            } elseif (in_array($resource['type'], ['tugas', 'coding'], true)) {
                // Pengumpulan Tugas -> status MENUNGGU penilaian dosen (score = null)
                if ($user && Schema::hasTable('student_assessment_scores')) {
                    $assessmentModel = Assessment::find($item);
                    if ($assessmentModel) {
                        $this->grades->syncDirectScore($assessmentModel, $user->id, null, null);
                    }
                }
            }

            if ($assessmentAttempt) {
                $assessmentAttempt->update([
                    'status' => AssessmentAttempt::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }
        });

        if ($request->boolean('from_quiz_room')) {
            return redirect()->route('mahasiswa.quiz.room', [$course, $item])->with('notice', 'Jawaban berhasil dikirim! Kuis Anda telah berhasil dikumpulkan.');
        }

        return redirect()->route('mahasiswa.course.item', [$course, $item])->with('notice', 'Jawaban berhasil disimpan dan menunggu penilaian.');
    }

    private function timedDurationMinutes(array $resource): ?int
    {
        if (empty($resource['duration_enabled'])) {
            return null;
        }

        $duration = (int) ($resource['duration_minutes'] ?? 0);

        return $duration > 0 ? $duration : null;
    }

    private function startTimedAssessmentAttempt(Assessment $assessment, User $user, array $resource): AssessmentAttempt
    {
        $startedAt = now();

        return AssessmentAttempt::firstOrCreate(
            [
                'assessment_id' => $assessment->id,
                'mahasiswa_id' => $user->id,
                'attempt' => 1,
            ],
            [
                'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
                'started_at' => $startedAt,
                'deadline_at' => $startedAt->copy()->addMinutes($this->timedDurationMinutes($resource)),
            ]
        );
    }

    public function cancelSubmission(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole(Role::MAHASISWA), 403);

        $assessment = null;
        $resource = null;

        if (Schema::hasTable('assessments')) {
            $assessment = Assessment::where('class_section_id', $course)->find($item);
            if ($assessment) {
                abort_unless(
                    $user->classSectionsEnrolled()->where('class_sections.id', $course)->exists(),
                    403,
                    'Anda tidak terdaftar pada kelas ini.'
                );
                $resource = Learning::databaseAssessment($assessment);
            }
        }
        abort_unless($assessment && $resource, 404, 'Asesmen tidak ditemukan pada database.');
        abort_unless(in_array($resource['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'], true), 404);

        // Jika sudah dinilai, penyerahan tidak dapat dibatalkan
        if (Schema::hasTable('student_assessment_scores')) {
            $hasBeenGraded = StudentAssessmentScore::where('assessment_id', $assessment->id)
                ->where('mahasiswa_id', $user->id)
                ->where(function ($query) {
                    $query->whereIn('status', [
                        StudentAssessmentScore::STATUS_FINAL,
                        StudentAssessmentScore::STATUS_PUBLISHED,
                    ])->orWhereNotNull('score');
                })
                ->exists();

            if ($hasBeenGraded) {
                return back()->with('notice', 'Pengiriman tugas tidak dapat dibatalkan karena sudah dinilai oleh dosen.');
            }
        }

        // Jika batas waktu lewat dan tidak mengizinkan pengumpulan terlambat, tidak dapat dibatalkan
        $isAssignmentType = in_array($resource['type'] ?? '', ['tugas', 'coding', 'pbl', 'case', 'project'], true);
        $allowLate = $isAssignmentType ? true : ($resource['allow_late'] ?? true);
        if (! $allowLate && ! empty($resource['due']) && Carbon::parse($resource['due'])->isPast()) {
            return back()->with('notice', 'Batas waktu pengumpulan telah berakhir. Pengampu tidak mengizinkan pengumpulan terlambat sehingga penyerahan tugas tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($user, $assessment, $item) {
            if (Schema::hasTable('submissions')) {
                $submission = Submission::where('assessment_id', $item)
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('mahasiswa_id', $user->id))
                    ->first();

                if ($submission) {
                    if (Schema::hasTable('submission_answers')) {
                        SubmissionAnswer::where('submission_id', $submission->id)->delete();
                    }
                    if (Schema::hasTable('attachments')) {
                        Attachment::where('submission_id', $submission->id)->delete();
                    }
                    $submission->delete();
                }
            }

            if (Schema::hasTable('student_assessment_scores')) {
                StudentAssessmentScore::where('assessment_id', $item)
                    ->where('mahasiswa_id', $user->id)
                    ->whereNull('score')
                    ->delete();
            }

            if (Schema::hasTable('assessment_attempts')) {
                AssessmentAttempt::where('assessment_id', $item)
                    ->where('mahasiswa_id', $user->id)
                    ->update([
                        'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
                        'submitted_at' => null,
                    ]);
            }
        });

        return redirect()->route('mahasiswa.course.item', [$course, $item])
            ->with('notice', 'Penyerahan tugas berhasil dibatalkan. Anda dapat mengunggah kembali jawaban tugas.');
    }

    private function upload($file): string
    {
        $id = (string) Str::uuid();
        $path = $file->store('learning-preview', 'local');
        $name = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $user = auth()->user();
        if ($user && Schema::hasTable('attachments')) {
            try {
                Attachment::create([
                    'uuid' => $id,
                    'user_id' => $user->id,
                    'path' => $path,
                    'name' => $name,
                    'mime' => $mime,
                    'size' => $file->getSize(),
                ]);
            } catch (\Throwable) {
            }
        }

        return $id;
    }

    public function file(Request $request, string $file)
    {
        $attachment = Schema::hasTable('attachments') ? Attachment::where('uuid', $file)->first() : null;
        if ($attachment) {
            $user = auth()->user();
            abort_unless($user, 401);
            $this->authorizeAttachmentAccess($attachment, $user);
            $meta = ['path' => $attachment->path, 'name' => $attachment->name, 'mime' => $attachment->mime];
        } else {
            // Berkas lama yang tersimpan di payload asesmen tetap harus memiliki
            // relasi assessment database dan melewati otorisasi kelas.
            $fileWithAssessment = Learning::fileMetaWithAssessment($file);
            if ($fileWithAssessment !== null) {
                $user = auth()->user();
                $assessment = $fileWithAssessment['assessment'];
                $sectionId = $assessment->class_section_id;

                if ($user && $sectionId && Schema::hasTable('class_sections')) {
                    $section = ClassSection::find($sectionId);
                    if ($section) {
                        $authorized = $user->hasRole(Role::DOSEN)
                            ? $user->can('manage', $section)
                            : ($user->hasRole(Role::MAHASISWA)
                                && $section->students()->where('users.id', $user->id)->exists());
                        abort_unless($authorized, 403);
                    }
                }

                $meta = $fileWithAssessment['meta'];
            } else {
                $sessionMeta = session("learning.files.$file");
                abort_unless($sessionMeta !== null, 404);
                $meta = $sessionMeta;
            }
        }

        abort_unless($meta && Storage::disk('local')->exists($meta['path']), 404);
        $inline = in_array($meta['mime'], ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'])
            || str_starts_with((string) $meta['mime'], 'image/')
            || str_starts_with((string) $meta['mime'], 'video/');
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($inline && ! $request->boolean('download')) {
            return Storage::disk('local')->response($meta['path'], $meta['name'], $headers + ['Content-Type' => $meta['mime']]);
        }

        return Storage::disk('local')->download($meta['path'], $meta['name'], $headers);
    }

    private function authorizeAttachmentAccess(Attachment $attachment, User $user): void
    {
        if ($user->hasRole(Role::ADMIN) || $user->hasRole(Role::ADMIN_PRODI)) {
            return;
        }

        // Pengunggah berkas selalu diizinkan mengakses berkasnya sendiri
        if ((int) $attachment->user_id === (int) $user->id) {
            return;
        }

        $submission = $attachment->submission_id && Schema::hasTable('submissions')
            ? Submission::with('assessment')->find($attachment->submission_id)
            : null;

        if (! $submission && Schema::hasTable('submissions')) {
            $submission = Submission::with('assessment')
                ->where(function ($q) use ($attachment) {
                    $q->whereJsonContains('file_ids', $attachment->uuid)
                        ->orWhere('file_ids', 'like', '%'.$attachment->uuid.'%');
                })
                ->latest('id')
                ->first();
        }

        $assessmentId = $attachment->assessment_id ?? $submission?->assessment_id;
        if (! $assessmentId && Schema::hasTable('assessments')) {
            $assessmentId = Assessment::where(function ($q) use ($attachment) {
                $q->whereJsonContains('attachments', $attachment->uuid)
                    ->orWhere('attachments', 'like', '%'.$attachment->uuid.'%')
                    ->orWhere('question_image', $attachment->uuid);
            })->value('id');
        }

        $sectionId = $attachment->class_section_id
            ?? $attachment->assessment?->class_section_id
            ?? $submission?->assessment?->class_section_id;

        if (! $sectionId && $assessmentId && Schema::hasTable('assessments')) {
            $sectionId = Assessment::where('id', $assessmentId)->value('class_section_id');
        }

        $section = $sectionId && Schema::hasTable('class_sections')
            ? ClassSection::find($sectionId)
            : null;

        if ($user->hasRole(Role::DOSEN)) {
            if ($section) {
                $canManage = $user->can('manage', $section)
                    || in_array((int) $user->id, array_map('intval', array_filter([(int) $section->dosen_id, (int) $section->dosen_pendamping_id])), true);
                if ($canManage) {
                    return;
                }
            }

            if ($submission && ($submission->mahasiswa_id || $submission->user_id)) {
                $studentId = $submission->mahasiswa_id ?: $submission->user_id;
                $isStudentInDosenCourse = ClassSection::where(function ($q) use ($user) {
                    $q->where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id);
                })->whereHas('students', function ($q) use ($studentId) {
                    $q->where('users.id', $studentId);
                })->exists();

                if ($isStudentInDosenCourse) {
                    return;
                }
            }

            if ($assessmentId && Schema::hasTable('assessments')) {
                $ass = Assessment::find($assessmentId);
                if ($ass && ($ass->user_id == $user->id || ($ass->class_section_id && ClassSection::where('id', $ass->class_section_id)->where(fn ($q) => $q->where('dosen_id', $user->id)->orWhere('dosen_pendamping_id', $user->id))->exists()))) {
                    return;
                }
            }

            if (! $sectionId && ! $submission) {
                return;
            }

            abort(403);
        }

        if ($submission) {
            $ownerIds = collect([
                $submission->user_id,
                $submission->mahasiswa_id,
                $attachment->user_id,
            ])->filter()->map(fn ($id) => (int) $id);

            abort_unless(
                $user->hasRole(Role::MAHASISWA) && $ownerIds->contains((int) $user->id),
                403
            );

            return;
        }

        if ($section) {
            abort_unless(
                $user->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists(),
                403
            );

            return;
        }

        if ($assessmentId && Schema::hasTable('class_sections')) {
            $isEnrolledInAssessment = ClassSection::whereHas('assessments', fn ($q) => $q->where('id', $assessmentId))
                ->whereHas('students', fn ($q) => $q->where('users.id', $user->id))
                ->exists();
            if ($isEnrolledInAssessment) {
                return;
            }
        }

        if (! $sectionId && ! $attachment->submission_id && ! $submission) {
            return;
        }

        abort_unless((int) $attachment->user_id === (int) $user->id, 403);
    }

    public function notifications(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 401);
        $allNotifications = $this->notifications->forUser($user);
        $unreadNotifications = array_filter($allNotifications, fn ($n) => empty($n['is_read']));

        $categoryCounts = [
            'all' => count($unreadNotifications),
            'tugas' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'tugas')),
            'nilai' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'nilai')),
            'sistem' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'sistem')),
            'diskusi' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'diskusi')),
        ];

        $category = $request->query('category');
        $notifications = $allNotifications;
        if ($category && in_array($category, ['tugas', 'nilai', 'sistem', 'diskusi'])) {
            $notifications = array_values(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === $category));
        }

        $groupedNotifications = collect($notifications)->groupBy(function ($notif) {
            $ts = $notif['timestamp'] ?? time();
            try {
                $carbon = is_numeric($ts)
                    ? Carbon::createFromTimestamp((int) $ts)
                    : Carbon::parse($ts);
            } catch (\Throwable) {
                $carbon = Carbon::now();
            }

            if ($carbon->isToday()) {
                return 'Hari Ini';
            }
            if ($carbon->isYesterday()) {
                return 'Kemarin';
            }

            return $carbon->translatedFormat('d F Y');
        });

        return response()
            ->view('learning.notifications', [
                'notifications' => $notifications,
                'groupedNotifications' => $groupedNotifications,
                'selectedCategory' => $category,
                'courses' => [],
                'categoryCounts' => $categoryCounts,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Halaman notifikasi untuk dosen — menampilkan pemberitahuan terkait kelas,
     * pengumpulan tugas mahasiswa, dan aktivitas forum.
     * Data bersumber dari database (submissions, assessments, discussions).
     */
    public function dosenNotifications(Request $request)
    {
        $user = auth()->user();
        // Kumpulkan notifikasi berbasis database untuk dosen
        abort_unless($user, 401);
        $allNotifications = $this->notifications->forUser($user);
        $unreadNotifications = array_filter($allNotifications, fn ($n) => empty($n['is_read']));

        $categoryCounts = [
            'all' => count($unreadNotifications),
            'tugas' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'tugas')),
            'nilai' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'nilai')),
            'sistem' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'sistem')),
            'diskusi' => count(array_filter($unreadNotifications, fn ($n) => ($n['category'] ?? '') === 'diskusi')),
        ];

        $category = $request->query('category');
        $notifications = $allNotifications;
        if ($category && in_array($category, ['tugas', 'nilai', 'sistem', 'diskusi'])) {
            $notifications = array_values(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === $category));
        }

        $groupedNotifications = collect($notifications)->groupBy(function ($notif) {
            $ts = $notif['timestamp'] ?? time();
            try {
                $carbon = is_numeric($ts)
                    ? Carbon::createFromTimestamp((int) $ts)
                    : Carbon::parse($ts);
            } catch (\Throwable) {
                $carbon = Carbon::now();
            }
            if ($carbon->isToday()) {
                return 'Hari Ini';
            }
            if ($carbon->isYesterday()) {
                return 'Kemarin';
            }

            return $carbon->translatedFormat('d F Y');
        });

        return response()
            ->view('dosen.notifikasi', [
                'notifications' => $notifications,
                'groupedNotifications' => $groupedNotifications,
                'selectedCategory' => $category,
                'courses' => [],
                'categoryCounts' => $categoryCounts,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function markNotificationRead(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user, 401);
        $keys = $id === 'all'
            ? (array) ($request->input('notification_ids') ?: collect($this->notifications->forUser($user))->pluck('id')->all())
            : [$id];
        $this->notifications->markRead($user, $keys);

        $sessionRead = session('learning.read_notifications', []);
        $sessionRead = array_values(array_unique(array_merge($sessionRead, $id === 'all' ? ['all'] : $keys)));
        session(['learning.read_notifications' => $sessionRead]);

        $fallbackRoute = $request->user()?->hasRole(Role::DOSEN)
            ? 'dosen.notifications'
            : 'mahasiswa.notifications';

        $target = $request->query('target');
        $backCategory = $request->query('back_category', $request->input('category', $request->query('category')));
        $backCategory = (! empty($backCategory) && in_array($backCategory, ['tugas', 'nilai', 'sistem', 'diskusi'], true))
            ? $backCategory
            : null;

        if ($target) {
            if ($this->isInternalUrl($target)) {
                return redirect($target);
            }
            // target tidak valid, kembali ke daftar notifikasi dengan kategori asal
            return $backCategory
                ? redirect()->route($fallbackRoute, ['category' => $backCategory])
                : redirect()->route($fallbackRoute);
        }

        if ($backCategory) {
            return redirect()->route($fallbackRoute, ['category' => $backCategory]);
        }

        return redirect()->route($fallbackRoute);
    }

    /**
     * Validasi bahwa URL target merupakan URL internal aplikasi ini.
     *
     * Menerima:
     *  - Path relatif: /mahasiswa/dashboard, /dosen/course/1
     * Menolak:
     *  - Protocol-relative: //evil.com/path
     *  - URL absolut ke host berbeda: https://evil.com/path
     *  - URL absolut yang memakai host lain walaupun diawali dengan URL aplikasi:
     *    https://app.sale.attacker.example/evil  (false prefix match)
     */
    protected function isInternalUrl(string $target): bool
    {
        if ($target === '' || $target !== trim($target)) {
            return false;
        }

        // Tolak karakter kontrol, backslash, dan bentuk protocol-relative, termasuk
        // yang disamarkan dengan percent encoding berlapis.
        $decodedTarget = $target;
        for ($i = 0; $i < 3; $i++) {
            $decodedTarget = rawurldecode($decodedTarget);
        }

        if (preg_match('/[\\x00-\\x1F\\x7F\\\\]/', $decodedTarget)) {
            return false;
        }

        if (str_starts_with($target, '/')) {
            return ! str_starts_with($decodedTarget, '//');
        }

        if (! filter_var($target, FILTER_VALIDATE_URL)) {
            return false;
        }

        $appParsed = parse_url((string) config('app.url'));
        $targetParsed = parse_url($target);
        if (! is_array($appParsed) || ! is_array($targetParsed)) {
            return false;
        }

        if (isset($targetParsed['user']) || isset($targetParsed['pass'])) {
            return false;
        }

        $appScheme = strtolower($appParsed['scheme'] ?? '');
        $targetScheme = strtolower($targetParsed['scheme'] ?? '');
        if (! in_array($targetScheme, ['http', 'https'], true)) {
            return false;
        }

        $appHost = strtolower($appParsed['host'] ?? '');
        $targetHost = strtolower($targetParsed['host'] ?? '');

        $defaultPort = $targetScheme === 'https' ? 443 : 80;
        $appPort = (int) ($appParsed['port'] ?? ($appScheme === 'https' ? 443 : 80));
        $targetPort = (int) ($targetParsed['port'] ?? $defaultPort);

        $currentHost = strtolower(request()->getHost());
        $currentPort = (int) (request()->getPort() ?? $defaultPort);
        $currentScheme = strtolower(request()->getScheme());

        $matchesAppUrl = ($appHost !== '' && $targetHost === $appHost && $targetPort === $appPort && $targetScheme === $appScheme);
        $matchesCurrentRequest = ($currentHost !== '' && $targetHost === $currentHost && $targetPort === $currentPort && $targetScheme === $currentScheme);

        if (! $matchesAppUrl && ! $matchesCurrentRequest) {
            return false;
        }

        $targetPath = $targetParsed['path'] ?? '/';

        if ($targetPath === '' || ! str_starts_with($targetPath, '/')) {
            return false;
        }

        return true;
    }

    public function clearNotifications(Request $request)
    {
        $category = $request->input('category');
        $user = $request->user();
        abort_unless($user, 401);
        $notifications = collect($this->notifications->forUser($user));
        if ($category && in_array($category, ['tugas', 'nilai', 'sistem', 'diskusi'], true)) {
            $notifications = $notifications->where('category', $category);
        }
        $this->notifications->delete($user, $notifications->pluck('id')->all());

        return back()->with('success', 'Semua notifikasi berhasil dibersihkan.');
    }

    public function deleteNotification(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user, 401);
        $keys = $id === 'all'
            ? collect($this->notifications->forUser($user))->pluck('id')->all()
            : [$id];
        $this->notifications->delete($user, $keys);

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }
}
