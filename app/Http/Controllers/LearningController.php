<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Cpmk;
use App\Models\CourseDiscussion;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Support\AcademicPreview;
use App\Support\LearningPreview as Learning;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LearningController extends Controller
{
    public function courses(Request $request)
    {
        $q = mb_strtolower((string) $request->query('q', ''));
        $previewCourses = array_filter(Learning::courses(), fn ($course) => str_contains(mb_strtolower($course['title'].' '.$course['code'].' '.$course['lecturer']), $q));

        $user = auth()->user();
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

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
                ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])
                ->withCount(['students', 'assessments'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->whereHas('mataKuliah', function ($mataKuliah) use ($q) {
                        $mataKuliah->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                            ->orWhereRaw('LOWER(code) LIKE ?', ["%{$q}%"]);
                    });
                })
                ->orderByDesc('semester_id')
                ->orderBy('mata_kuliah_id')
                ->orderBy('section_code')
                ->get();
        }

        if ($user) {
            $courses = $sections->map(function ($section) {
                if (! $section->enrollment_code) {
                    $section->update(['enrollment_code' => ClassSection::generateUniqueEnrollmentCode()]);
                }

                return [
                    'id' => $section->id,
                    'code' => $section->display_code,
                    'sks' => ($section->mataKuliah->sks ?? 0).' SKS',
                    'title' => $section->mataKuliah->name,
                    'lecturer' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_ketua' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_wakil' => $section->dosenPendamping?->name,
                    'cover' => null,
                    'type' => 'Kelas Aktif',
                    'work' => 'Perkuliahan semester '.($section->semester?->name ?? 'aktif'),
                    'due' => '',
                    'students_count' => $section->students_count,
                    'assessments_count' => $section->assessments_count,
                    'enrollment_code' => $section->enrollment_code,
                    'enrollment_url' => $section->enrollment_url,
                    'qr_url' => route('kelas.qr', $section->id),
                    'svg_index' => ($section->id % 4) + 1,
                ];
            })->all();
        } else {
            $courses = $previewCourses;
        }

        return view('learning.courses', compact('courses'));
    }

    public function course(int $course)
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
            if ($user) {
                auth()->login($user);
            }
        }

        $section = Schema::hasTable('class_sections')
            ? ClassSection::with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'assessments'])->find($course)
            : null;

        if ($section && $user) {
            $canAccess = $user->hasRole(Role::DOSEN)
                ? (in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true) || empty($section->dosen_id))
                : ($user->hasRole(Role::MAHASISWA)
                    && $section->students()->where('users.id', $user->id)->exists());

            if ($canAccess) {
                if ($user->hasRole(Role::DOSEN) && empty($section->dosen_id)) {
                    $section->update(['dosen_id' => $user->id]);
                }

                $courseData = Learning::databaseCourse($section);
                $items = $section->assessments
                    ->mapWithKeys(fn ($assessment) => [$assessment->id => Learning::databaseAssessment($assessment)])
                    ->all();

                session(["learning.discussion_reads.$course" => Learning::chatMessageCount($course) ?? count(Learning::courseDiscussions($course))]);

                return view('learning.course', ['course' => $courseData, 'items' => $items, 'classSection' => $section]);
            }

            abort(403, 'Anda tidak terdaftar pada kelas ini.');
        }

        $courseData = Learning::course($course);
        session(["learning.discussion_reads.$course" => Learning::chatMessageCount($course) ?? count(Learning::courseDiscussions($course))]);

        return view('learning.course', ['course' => $courseData, 'items' => array_filter(Learning::items(), fn ($item) => $item['course'] === $course)]);
    }

    public function item(int $course, int $item)
    {
        $user = auth()->user();
        if ($user && Schema::hasTable('class_sections')) {
            $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->find($course);
            $canAccess = $section && ($user->hasRole(Role::DOSEN)
                ? in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true)
                : ($user->hasRole(Role::MAHASISWA)
                    && $section->students()->where('users.id', $user->id)->exists()));

            if ($canAccess) {
                $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);

                return view('learning.item', [
                    'course' => Learning::databaseCourse($section),
                    'item' => Learning::databaseAssessment($assessment),
                ]);
            }
        }

        return view('learning.item', ['course' => Learning::course($course), 'item' => Learning::resource($course, $item)]);
    }

    public function quizRoom(int $course, int $item)
    {
        $isDosen = (session('auth_user.role') === 'dosen') || (auth()->user()?->hasRole(Role::DOSEN));
        abort_if($isDosen, 403, 'Akses ditolak: Dosen tidak dapat mengikuti ujian CBT mahasiswa.');

        $user = auth()->user();
        $section = $user && Schema::hasTable('class_sections') ? ClassSection::find($course) : null;
        $assessment = $section ? Assessment::where('class_section_id', $course)->find($item) : null;
        if ($assessment) {
            abort_unless(
                $user->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists(),
                403
            );
            $resource = Learning::databaseAssessment($assessment);
            $courseData = Learning::databaseCourse($section->loadMissing(['mataKuliah', 'semester', 'dosen', 'dosenPendamping']));
        } else {
            $resource = Learning::resource($course, $item);
            $courseData = Learning::course($course);
        }
        abort_unless(in_array($resource['type'], ['kuis', 'tugas', 'coding', 'uts', 'uas']), 404);

        $submission = session("learning.submissions.$item", null);
        $dbScore = $user && Schema::hasTable('student_assessment_scores')
            ? StudentAssessmentScore::where('assessment_id', $item)->where('mahasiswa_id', $user->id)->whereNotNull('score')->first()
            : null;
        $sessionGrade = session("learning.grades.$item") ?? session("academic.item_grades.$item.1");
        $scoreValue = $dbScore?->score ?? (is_array($sessionGrade) ? array_sum($sessionGrade['points'] ?? []) : $sessionGrade);

        if (! empty($resource['randomize_questions']) && ! empty($resource['questions'])) {
            $studentId = session('auth_user.id', 1);
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
        ]);
    }

    public function assignments(Request $request)
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
            if ($user) {
                auth()->login($user);
            }
        }

        $courses = [];
        $items = [];
        $studentScores = [];

        if ($user && Schema::hasTable('student_assessment_scores')) {
            $studentScores = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                ->get()
                ->keyBy('assessment_id');
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
                    $assessments = Assessment::where('class_section_id', $sec->id)->get();
                    foreach ($assessments as $asm) {
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

                $sessionItems = array_filter(Learning::items(), fn ($i) => ($i['course'] ?? null) === $sec->id);
                foreach ($sessionItems as $sItem) {
                    if (! isset($items[$sItem['id']])) {
                        $items[$sItem['id']] = $sItem;
                    }
                }
            }
        }

        if (empty($courses)) {
            $courses = Learning::courses();
        }
        if (empty($items)) {
            $items = Learning::items();
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
                $hasSessionGrade = session("learning.grades.{$assessmentId}") !== null
                    || session("academic.item_grades.{$assessmentId}.1") !== null;

                return $hasDbScore || $hasSessionGrade;
            }

            return true;
        });
        uasort($filteredItems, fn ($a, $b) => strcmp($a['due'] ?? '9999', $b['due'] ?? '9999'));

        return view('learning.assignments', [
            'items' => $filteredItems,
            'courses' => $courses,
            'studentScores' => $studentScores,
        ]);
    }

    public function discussions()
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

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
        } elseif (! $user) {
            $courses = Learning::courses();
        }

        return view('learning.discussions', ['items' => Learning::items(), 'courses' => $courses]);
    }

    public function createCourse()
    {
        return view('dosen.course-form');
    }

    public function storeCourse(Request $request)
    {
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
        if (! $user && is_array(session('auth_user')) && Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $courseId = null;

        if (Schema::hasTable('class_sections') && Schema::hasTable('mata_kuliahs')) {
            $prodi = $user?->prodi ?? Prodi::first();
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
                'dosen_id' => $user?->id,
                'capacity' => 40,
                'enrollment_code' => ClassSection::generateUniqueEnrollmentCode(),
            ]);

            $courseId = $section->id;
            $data['id'] = $courseId;
            $data['enrollment_code'] = $section->enrollment_code;
        }

        $courses = Learning::courses();
        if (! $courseId) {
            $courseId = max(array_keys($courses)) + 1;
            $data['id'] = $courseId;
        }
        $courses[$courseId] = $data;
        session(['learning.courses' => $courses]);

        if (! empty($data['video'])) {
            session(["learning.course_video.{$courseId}" => [
                'video' => $data['video'],
                'video_type' => $data['video_type'] ?? 'url',
                'video_title' => $data['title'],
            ]]);
        }

        return redirect()->route('dosen.course.show', $courseId)->with('notice', 'Course berhasil ditambahkan ke database.');
    }

    public function createItem(int $course)
    {
        return view('dosen.item-form', ['course' => Learning::course($course)]);
    }

    public function storeItem(Request $request, int $course)
    {
        Learning::course($course);
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
            'attachments.*' => 'file|mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp,mp4|max:20480',
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
            'questions.*.type' => ['required', Rule::in(['uraian', 'pilihan', 'kompleks', 'coding', 'benar_salah', 'mencocokkan'])],
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
            'coding_steps.*.attachment' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp,mp4|max:20480',
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
                $options = array_values(array_filter(array_map('trim', explode("\n", $question['options'] ?? '')), fn ($v) => $v !== ''));
                if (count($options) < 2 || count($options) > 20 || count(array_unique($options)) !== count($options)) {
                    return back()->withErrors(["questions.$index.options" => 'Isi 2–20 pilihan berbeda untuk soal '.($index + 1).'.'])->withInput();
                }
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
            $data['questions'] = array_values($data['questions']);
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
                return str_starts_with((string) session("learning.files.$file.mime", ''), 'video/');
            });
            $imageAttachments = collect($data['attachments'])->filter(function ($file) {
                return str_starts_with((string) session("learning.files.$file.mime", ''), 'image/');
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
                    $name = session("learning.files.$file.name", '');
                    return $name === $target || $file === $target;
                });
                if ($matchedFile) {
                    $mime = (string) session("learning.files.$matchedFile.mime", '');
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

            $courses = Learning::courses();
            $courseData = $courses[$course] ?? Learning::course($course);
            $courseData['video'] = $pinnedVal;
            $courseData['video_type'] = $pinnedType;
            $courseData['video_title'] = $data['title'];
            $courseData['media_kind'] = $mediaKind;
            $courses[$course] = $courseData;
            session(['learning.courses' => $courses]);
            session(["learning.course_video.{$course}" => [
                'video' => $pinnedVal,
                'video_type' => $pinnedType,
                'video_title' => $data['title'],
                'media_kind' => $mediaKind,
            ]]);
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
        $items = Learning::items();
        $data['id'] = max(array_keys($items)) + 1;
        $data['course'] = $course;
        $items[$data['id']] = $data;
        session(['learning.items' => $items]);

        if (Schema::hasTable('class_sections') && Schema::hasTable('assessments')) {
            $section = ClassSection::find($course);
            if ($section && in_array($category, ['materi', 'tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case'], true)) {
                $assessmentType = ($category === 'coding') ? 'tugas' : $category;
                $asmCount = Assessment::where('class_section_id', $section->id)->count();
                $questionImages = collect($data['questions'] ?? [])->pluck('image')->filter();
                $fileIds = collect($data['attachments'])
                    ->merge($data['option_images'])
                    ->merge([$data['question_image']])
                    ->merge($questionImages)
                    ->filter()
                    ->unique();
                $data['file_meta'] = $fileIds->mapWithKeys(fn ($fileId) => [
                    $fileId => session("learning.files.$fileId"),
                ])->filter()->all();
                $assessment = Assessment::create([
                    'class_section_id' => $section->id,
                    'code' => strtoupper($category).'-'.($asmCount + 1),
                    'name' => $data['title'],
                    'type' => $assessmentType,
                    'description' => $data['body'],
                    'learning_payload' => $data,
                    'final_weight' => $category === 'materi' ? 0 : 10,
                    'uses_rubric' => false,
                    'status' => Assessment::STATUS_PUBLISHED,
                    'due_at' => ! empty($data['due']) ? Carbon::parse($data['due']) : null,
                    'allow_late' => $data['allow_late'],
                ]);

                // Sinkronisasi bobot CPMK ke database (tabel assessment_cpmk)
                $syncData = [];
                $allCpmks = $section->mataKuliah?->cpmks()->get() ?? (Schema::hasTable('cpmks') ? Cpmk::all() : collect());

                if (! empty($data['questions'])) {
                    $cpmkCounts = array_count_values(array_filter(array_column($data['questions'], 'cpmk')));
                    $totalQ = max(1, count($data['questions']));
                    $accumulated = 0.0;
                    $itemsLeft = count($cpmkCounts);
                    foreach ($cpmkCounts as $code => $cnt) {
                        $itemsLeft--;
                        $cpmkModel = $allCpmks->first(function ($c) use ($code) {
                            $c1 = strtoupper(trim(str_replace(' ', '-', $c->code)));
                            $c2 = strtoupper(trim(str_replace(' ', '-', $code)));
                            return $c1 === $c2;
                        });
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
                        $cpmkModel = $allCpmks->first(function ($c) use ($code) {
                            $c1 = strtoupper(trim(str_replace(' ', '-', $c->code)));
                            $c2 = strtoupper(trim(str_replace(' ', '-', $code)));
                            return $c1 === $c2;
                        });
                        if ($cpmkModel) {
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

        if ($data['ai_enabled'] && Schema::hasTable('ai_tasks')) {
            DB::table('ai_tasks')->updateOrInsert(
                ['id' => $data['id']],
                ['title' => $data['title'], 'body' => $data['body'], 'enabled' => true]
            );
        }

        return redirect()->route('dosen.course.show', $course)->with('notice', 'Konten ditambahkan ke modul dalam sesi pratinjau ini.');
    }

    public function discussCourse(Request $request, int $course)
    {
        Learning::course($course);
        $data = $request->validate(['message' => 'required|string|max:3000']);
        $user = auth()->user();
        $this->assertCourseDiscussionAccess($course, $user);
        $sessionUser = session('auth_user');

        $author = $user?->name ?? ($sessionUser['name'] ?? 'Ahmad Maulana');
        $role = $user?->role?->name ?? ($sessionUser['role'] ?? 'mahasiswa');
        $senderKey = $user
            ? 'user:'.$user->getAuthIdentifier()
            : 'preview:'.$role.':'.($sessionUser['id'] ?? $sessionUser['number'] ?? $sessionUser['email'] ?? 1);

        $newMessage = [
            'author' => $author,
            'sender_key' => $senderKey,
            'message' => $data['message'],
            'time' => now()->format('H:i'),
            'timestamp' => now()->timestamp,
            'date_key' => now()->toDateString(),
            'date_label' => 'Hari ini',
            'role' => $role,
        ];
        $newMessage = $this->persistCourseDiscussion($course, $newMessage, $user);
        $messages = Learning::courseDiscussions($course);
        session(["learning.discussion_reads.$course" => count($messages)]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $newMessage,
                'total' => count($messages),
            ]);
        }

        $redirectRoute = ($role === 'dosen') ? 'dosen.course.show' : 'mahasiswa.course.show';

        return redirect(route($redirectRoute, $course).'#diskusi-kelas');
    }

    public function discuss(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        $hasDatabaseAssessment = $user
            && Schema::hasTable('assessments')
            && Assessment::where('class_section_id', $course)->whereKey($item)->exists();
        if (! $hasDatabaseAssessment) {
            Learning::resource($course, $item);
        }
        $data = $request->validate(['message' => 'required|string|max:3000']);
        $this->assertCourseDiscussionAccess($course, $user);
        $sessionUser = session('auth_user');

        $author = $user?->name ?? ($sessionUser['name'] ?? 'Ahmad Maulana');
        $role = $user?->role?->name ?? ($sessionUser['role'] ?? 'mahasiswa');
        $senderKey = $user
            ? 'user:'.$user->getAuthIdentifier()
            : 'preview:'.$role.':'.($sessionUser['id'] ?? $sessionUser['number'] ?? $sessionUser['email'] ?? 1);

        $newMessage = [
            'author' => $author,
            'sender_key' => $senderKey,
            'message' => $data['message'],
            'time' => now()->format('H:i'),
            'timestamp' => now()->timestamp,
            'date_key' => now()->toDateString(),
            'date_label' => 'Hari ini',
            'role' => $role,
        ];
        $this->persistCourseDiscussion($course, $newMessage, $user);
        $messages = Learning::courseDiscussions($course);
        session(["learning.discussion_reads.$course" => count($messages)]);
        session(["learning.discussions.$item" => $messages]);

        $redirectRoute = ($role === 'dosen') ? 'dosen.course.show' : 'mahasiswa.course.show';

        return redirect(route($redirectRoute, $course).'#diskusi-kelas');
    }

    private function persistCourseDiscussion(int $course, array $message, ?User $user): array
    {
        if (Schema::hasTable('course_discussions') && ClassSection::whereKey($course)->exists()) {
            $discussion = CourseDiscussion::create([
                'class_section_id' => $course,
                'user_id' => $user?->getAuthIdentifier(),
                'author_name' => $message['author'],
                'role' => $message['role'],
                'sender_key' => $message['sender_key'],
                'message' => $message['message'],
            ]);

            return [
                'id' => $discussion->id,
                'author' => $discussion->author_name,
                'sender_key' => $discussion->sender_key,
                'message' => $discussion->message,
                'time' => $discussion->created_at->format('H:i'),
                'timestamp' => $discussion->created_at->timestamp,
                'date_key' => $discussion->created_at->toDateString(),
                'date_label' => 'Hari ini',
                'role' => $discussion->role,
            ];
        }

        if (Schema::hasTable('rooms') && Schema::hasTable('messages')) {
            try {
                $room = \App\Models\Room::forCourse($course);
                $userId = $user?->id;
                if (! $userId && is_array(session('auth_user'))) {
                    $sU = session('auth_user');
                    if (Schema::hasTable('users')) {
                        $dbUser = \App\Models\User::where('email', $sU['email'] ?? '')->orWhere('nim_nidn', $sU['number'] ?? '')->first();
                        $userId = $dbUser?->id;
                    }
                }
                if (! $userId && Schema::hasTable('users')) {
                    $dbUser = \App\Models\User::where('email', 'ahmad.maulana@student.test')->first() ?? \App\Models\User::first();
                    $userId = $dbUser?->id;
                }
                if ($userId) {
                    $dbMsg = \App\Models\Message::create([
                        'room_id' => $room->id,
                        'user_id' => $userId,
                        'content' => $message['message'] ?? $message['content'] ?? '',
                    ]);
                    $message['id'] = $dbMsg->id;
                }
            } catch (\Throwable $e) {
            }
        }

        $messages = Learning::courseDiscussions($course);
        $messages[] = $message;
        session(["learning.course_discussions.$course" => $messages]);

        return $message;
    }

    private function assertCourseDiscussionAccess(int $course, ?User $user): void
    {
        if (! $user || ! Schema::hasTable('class_sections')) {
            return;
        }

        $section = ClassSection::find($course);
        if (! $section) {
            return;
        }

        $canAccess = $user->hasRole(Role::DOSEN)
            ? in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true)
            : ($user->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists());

        abort_unless($canAccess, 403);
    }

    public function submit(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        $resource = null;

        if ($user && Schema::hasTable('assessments')) {
            $assessment = Assessment::where('class_section_id', $course)->find($item);
            if ($assessment) {
                $isEnrolled = $user->hasRole(Role::MAHASISWA)
                    && $user->classSectionsEnrolled()->where('class_sections.id', $course)->exists();
                abort_unless($isEnrolled, 403);
                $resource = Learning::databaseAssessment($assessment);
            }
        }

        $resource ??= Learning::resource($course, $item);

        abort_unless(in_array($resource['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'], true), 404);

        $allowLate = $resource['allow_late'] ?? true;
        if (! $allowLate && ! empty($resource['due']) && Carbon::parse($resource['due'])->isPast()) {
            return back()->withErrors(['answer' => 'Batas waktu pengumpulan telah berakhir. Pengampu mengunci tugas ini dan tidak menerima pengumpulan terlambat.'])->withInput();
        }

        $data = $request->validate([
            'question_answers' => 'nullable|array|max:30',
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
            $answers = $data['question_answers'] ?? [];
            if (! $isFromQuizRoom && count($answers) !== count($resource['questions'])) {
                return back()->withErrors(['question_answers' => 'Jawab seluruh soal sebelum mengumpulkan.'])->withInput();
            }
            foreach ($resource['questions'] as $index => $question) {
                $answer = $answers[$index] ?? [];
                if (in_array($question['type'], ['pilihan', 'kompleks'])) {
                    $options = array_values(array_filter(array_map('trim', explode("\n", $question['options'] ?? '')), fn ($v) => $v !== ''));
                    $choices = $answer['choices'] ?? [];
                    if (! empty($choices)) {
                        if (array_diff($choices, $options) || ($question['type'] === 'pilihan' && count($choices) !== 1)) {
                            return back()->withErrors(['question_answers' => 'Periksa pilihan pada soal '.($index + 1).'.'])->withInput();
                        }
                    } elseif (! $isFromQuizRoom) {
                        return back()->withErrors(['question_answers' => 'Periksa pilihan pada soal '.($index + 1).'.'])->withInput();
                    }
                } elseif ($question['type'] === 'benar_salah') {
                    if (empty($answer['boolean_choice']) && ! $isFromQuizRoom) {
                        return back()->withErrors(['question_answers' => 'Pilih Benar atau Salah pada soal '.($index + 1).'.'])->withInput();
                    }
                } elseif ($question['type'] === 'mencocokkan') {
                    if (empty($answer['matching']) && ! $isFromQuizRoom) {
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
        $previousFiles = session("learning.submissions.$item.files", []);
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
        $data['student_number'] = session('auth_user.number');
        session(["learning.submissions.$item" => $data]);

        if ($user) {
            session(["learning.submissions.{$item}.{$user->id}" => $data]);
        }

        if (in_array($resource['type'], ['kuis', 'uts', 'uas'], true) || $isFromQuizRoom) {
            $questions = $resource['questions'] ?? [];
            if (! empty($questions)) {
                $earnedPoints = 0;
                $answers = $data['question_answers'] ?? [];
                $hasEssay = collect($questions)->contains(fn ($q) => in_array($q['type'] ?? 'pilihan', ['uraian', 'coding', 'esai'], true));

                $groupCounts = array_count_values(array_filter(array_column($questions, 'cpmk')));
                $totalQuestions = max(1, count($questions));
                $cpmkScores = [];

                foreach ($questions as $index => $q) {
                    $cCode = $q['cpmk'] ?? 'CPMK-01';
                    $groupCount = max(1, (int) ($groupCounts[$cCode] ?? 1));
                    $porsiSoal = 100 / $groupCount;
                    $bobotCpmk = ($groupCount / $totalQuestions) * 100;

                    if (! isset($cpmkScores[$cCode])) {
                        $cpmkScores[$cCode] = [
                            'total_nilai' => 0.0,
                            'bobot_cpmk' => $bobotCpmk,
                        ];
                    }

                    $ans = $answers[$index] ?? [];
                    $qType = $q['type'] ?? 'pilihan';
                    $qPoints = (float) ($q['points'] ?? 25);
                    $isCorrect = false;
                    $earned = 0.0;

                    if ($qType === 'pilihan') {
                        $choices = $ans['choices'] ?? [];
                        $correct = $q['correct_answer'] ?? (str_contains($q['prompt'] ?? '', 'imbalance') ? 'F1-Score dan ROC-AUC' : (str_contains($q['prompt'] ?? '', 'BST') ? 'Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan' : ''));
                        if (! empty($correct) && count($choices) === 1 && $choices[0] === $correct) {
                            $isCorrect = true;
                            $earned = $qPoints;
                        }
                    } elseif ($qType === 'benar_salah') {
                        $choice = $ans['boolean_choice'] ?? '';
                        $correct = $q['correct_answer'] ?? (str_contains($q['prompt'] ?? '', '95%') ? 'Salah' : 'Benar');
                        if (! empty($choice) && $choice === $correct) {
                            $isCorrect = true;
                            $earned = $qPoints;
                        }
                    } elseif ($qType === 'kompleks') {
                        $choices = $ans['choices'] ?? [];
                        $correct = $q['correct_answers'] ?? ['Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)', 'Kompleksitas pencarian rata-rata pada balanced BST adalah O(log n)'];
                        sort($choices);
                        $sortedCorrect = $correct;
                        sort($sortedCorrect);
                        if ($choices === $sortedCorrect) {
                            $isCorrect = true;
                            $earned = $qPoints;
                        }
                    } elseif ($qType === 'mencocokkan') {
                        $matching = $ans['matching'] ?? [];
                        $pairs = array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn ($v) => str_contains($v, '='));
                        $totalPairs = count($pairs);
                        $matchedCorrect = 0;
                        foreach (array_values($pairs) as $pIdx => $pairStr) {
                            [$term, $def] = array_map('trim', explode('=', $pairStr, 2));
                            if (isset($matching[$pIdx]) && $matching[$pIdx] === $def) {
                                $matchedCorrect++;
                            }
                        }
                        if ($totalPairs > 0 && $matchedCorrect === $totalPairs) {
                            $isCorrect = true;
                            $earned = $qPoints;
                        } elseif ($totalPairs > 0 && $matchedCorrect > 0) {
                            $earned = ($matchedCorrect / $totalPairs) * $qPoints;
                        }
                    }

                    if ($isCorrect) {
                        $earnedPoints += $qPoints;
                    }

                    $persen = $qPoints > 0 ? ($earned / $qPoints) : 0;
                    $nilaiSoal = $persen * $porsiSoal;
                    $cpmkScores[$cCode]['total_nilai'] += $nilaiSoal;
                }

                session(["learning.grades.$item" => round($earnedPoints, 1)]);

                // Integrasi Database
                if ($user && Schema::hasTable('student_assessment_scores')) {
                    if ($hasEssay) {
                        // Ada soal esai -> status MENUNGGU penilaian dosen (score = null)
                        StudentAssessmentScore::updateOrCreate(
                            ['assessment_id' => $item, 'mahasiswa_id' => $user->id],
                            ['score' => null]
                        );
                    } else {
                        // Semua soal otomatis -> hitung nilai total asesmen (skala 100) dan simpan
                        $totalAsesmen = 0.0;
                        foreach ($cpmkScores as $cCode => $cInfo) {
                            $totalAsesmen += $cInfo['total_nilai'] * ($cInfo['bobot_cpmk'] / 100);
                        }
                        $totalAsesmen = round($totalAsesmen, 2);

                        StudentAssessmentScore::updateOrCreate(
                            ['assessment_id' => $item, 'mahasiswa_id' => $user->id],
                            ['score' => $totalAsesmen, 'graded_at' => now()]
                        );

                        if (Schema::hasTable('student_assessment_cpmk_scores')) {
                            $asmtModel = Assessment::with('cpmks')->find($item);
                            if ($asmtModel) {
                                foreach ($cpmkScores as $cCode => $cInfo) {
                                    $cpmkModel = $asmtModel->cpmks->first(function ($c) use ($cCode) {
                                        $c1 = strtoupper(trim(str_replace(' ', '-', $c->code)));
                                        $c2 = strtoupper(trim(str_replace(' ', '-', $cCode)));
                                        return $c1 === $c2;
                                    }) ?? (Schema::hasTable('cpmks') ? Cpmk::where('code', $cCode)->first() : null);

                                    if ($cpmkModel) {
                                        $proportionalScore = round(($cInfo['total_nilai'] * $cInfo['bobot_cpmk']) / 100, 2);
                                        StudentAssessmentCpmkScore::updateOrCreate(
                                            ['assessment_id' => $item, 'cpmk_id' => $cpmkModel->id, 'mahasiswa_id' => $user->id],
                                            ['score' => $proportionalScore]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif (in_array($resource['type'], ['tugas', 'coding'], true)) {
            // Pengumpulan Tugas -> status MENUNGGU penilaian dosen (score = null)
            if ($user && Schema::hasTable('student_assessment_scores')) {
                StudentAssessmentScore::updateOrCreate(
                    ['assessment_id' => $item, 'mahasiswa_id' => $user->id],
                    ['score' => null]
                );
            }
        }

        if ($request->boolean('from_quiz_room')) {
            return redirect()->route('mahasiswa.quiz.room', [$course, $item])->with('notice', 'Jawaban berhasil dikirim! Kuis Anda telah berhasil dikumpulkan.');
        }

        return redirect()->route('mahasiswa.course.item', [$course, $item])->with('notice', 'Jawaban dikumpulkan dalam sesi pratinjau. Belum dinilai.');
    }

    public function cancelSubmission(Request $request, int $course, int $item)
    {
        $user = auth()->user();
        $resource = null;

        if ($user && Schema::hasTable('assessments')) {
            $assessment = Assessment::where('class_section_id', $course)->find($item);
            if ($assessment) {
                abort_unless(
                    $user->hasRole(Role::MAHASISWA)
                    && $user->classSectionsEnrolled()->where('class_sections.id', $course)->exists(),
                    403
                );
                $resource = Learning::databaseAssessment($assessment);
            }
        }
        $resource ??= Learning::resource($course, $item);
        abort_unless(in_array($resource['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'], true), 404);

        return back()->withErrors(['submission' => 'Penyerahan tugas yang sudah dikumpulkan tidak dapat dibatalkan.']);
    }

    private function upload($file): string
    {
        $id = (string) Str::uuid();
        session(["learning.files.$id" => ['path' => $file->store('learning-preview', 'local'), 'name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType()]]);

        return $id;
    }

    public function file(Request $request, string $file)
    {
        $meta = session("learning.files.$file") ?? Learning::fileMeta($file);
        abort_unless($meta && Storage::disk('local')->exists($meta['path']), 404);
        $inline = in_array($meta['mime'], ['image/jpeg', 'image/png', 'image/webp'])
            || str_starts_with((string) $meta['mime'], 'video/')
            || ($request->boolean('inline') && $meta['mime'] === 'application/pdf');
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($inline && ! $request->boolean('download')) {
            return Storage::disk('local')->response($meta['path'], $meta['name'], $headers + ['Content-Type' => $meta['mime']]);
        }

        return Storage::disk('local')->download($meta['path'], $meta['name'], $headers);
    }

    public function notifications(Request $request)
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $allNotifications = Learning::notifications($user);

        $categoryCounts = [
            'all' => count($allNotifications),
            'tugas' => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'tugas')),
            'nilai' => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'nilai')),
            'sistem' => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'sistem')),
            'diskusi' => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'diskusi')),
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
                    ? \Carbon\Carbon::createFromTimestamp((int) $ts)
                    : \Carbon\Carbon::parse($ts);
            } catch (\Throwable) {
                $carbon = \Carbon\Carbon::now();
            }

            if ($carbon->isToday()) {
                return 'Hari Ini';
            }
            if ($carbon->isYesterday()) {
                return 'Kemarin';
            }
            return $carbon->translatedFormat('d F Y');
        });

        return view('learning.notifications', [
            'notifications' => $notifications,
            'groupedNotifications' => $groupedNotifications,
            'selectedCategory' => $category,
            'courses' => Learning::courses(),
            'categoryCounts' => $categoryCounts,
        ]);
    }

    /**
     * Halaman notifikasi untuk dosen — menampilkan pemberitahuan terkait kelas,
     * pengumpulan tugas mahasiswa, dan aktivitas forum.
     * Data bersumber dari database (submissions, assessments, discussions).
     */
    public function dosenNotifications(Request $request)
    {
        $user = auth()->user();
        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = \App\Models\User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        // Kumpulkan notifikasi berbasis database untuk dosen
        $allNotifications = Learning::notifications($user);

        $categoryCounts = [
            'all'     => count($allNotifications),
            'tugas'   => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'tugas')),
            'nilai'   => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'nilai')),
            'sistem'  => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'sistem')),
            'diskusi' => count(array_filter($allNotifications, fn ($n) => ($n['category'] ?? '') === 'diskusi')),
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
                    ? \Carbon\Carbon::createFromTimestamp((int) $ts)
                    : \Carbon\Carbon::parse($ts);
            } catch (\Throwable) {
                $carbon = \Carbon\Carbon::now();
            }
            if ($carbon->isToday()) {
                return 'Hari Ini';
            }
            if ($carbon->isYesterday()) {
                return 'Kemarin';
            }
            return $carbon->translatedFormat('d F Y');
        });

        return view('dosen.notifikasi', [
            'notifications'        => $notifications,
            'groupedNotifications' => $groupedNotifications,
            'selectedCategory'     => $category,
            'courses'              => Learning::courses(),
            'categoryCounts'       => $categoryCounts,
        ]);
    }

    public function markNotificationRead(Request $request, string $id)
    {
        $readNotifs = session('learning.read_notifications', []);

        if ($id === 'all') {
            $submittedIds = $request->input('notification_ids');
            if (is_array($submittedIds) && ! empty($submittedIds)) {
                $readNotifs = array_merge($readNotifs, $submittedIds);
            } else {
                $readNotifs[] = 'all';
            }
        } elseif (! in_array($id, $readNotifs, true)) {
            $readNotifs[] = $id;
        }

        session(['learning.read_notifications' => array_values(array_unique($readNotifs))]);

        $target = $request->query('target');
        if ($target) {
            if ((filter_var($target, FILTER_VALIDATE_URL) && str_starts_with($target, url('/'))) || (str_starts_with($target, '/') && ! str_starts_with($target, '//'))) {
                return redirect($target);
            }
        }

        return back();
    }

    public function clearNotifications(Request $request)
    {
        $category = $request->input('category');
        $cleared = session('learning.cleared_notifications', []);

        if ($category && in_array($category, ['tugas', 'nilai', 'sistem', 'diskusi'])) {
            $user = auth()->user();
            $all = Learning::notifications($user);
            $catIds = array_column(array_filter($all, fn ($n) => ($n['category'] ?? '') === $category), 'id');
            $cleared = array_merge($cleared, $catIds);
        } else {
            $cleared[] = 'all';
            session(['learning.notifications_cleared_at' => now()->timestamp]);
        }

        session(['learning.cleared_notifications' => array_values(array_unique($cleared))]);

        return back()->with('success', 'Semua notifikasi berhasil dibersihkan.');
    }

    public function deleteNotification(Request $request, string $id)
    {
        $cleared = session('learning.cleared_notifications', []);
        if ($id === 'all') {
            $cleared[] = 'all';
            session(['learning.notifications_cleared_at' => now()->timestamp]);
        } else {
            $cleared[] = $id;
        }

        session(['learning.cleared_notifications' => array_values(array_unique($cleared))]);

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }
}
