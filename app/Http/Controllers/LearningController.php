<?php

namespace App\Http\Controllers;

use App\Support\LearningPreview as Learning;
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
        $courses = array_filter(Learning::courses(), fn ($course) => str_contains(mb_strtolower($course['title'].' '.$course['code'].' '.$course['lecturer']), $q));

        $user = auth()->user();
        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = \App\Models\User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        if ($user && $user->hasRole(\App\Models\Role::MAHASISWA)) {
            $enrolledSections = $user->classSectionsEnrolled()
                ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])
                ->withCount(['students', 'assessments'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->whereHas('mataKuliah', fn ($m) => $m->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
                })
                ->get();

            foreach ($enrolledSections as $sec) {
                $alreadyIncluded = collect($courses)->contains(function ($card) use ($sec) {
                    return ($card['title'] ?? '') === $sec->mataKuliah->name || ($card['code'] ?? '') === $sec->display_code;
                });

                if (! $alreadyIncluded) {
                    $courses[] = [
                        'id' => $sec->id,
                        'code' => $sec->display_code,
                        'sks' => $sec->mataKuliah->sks . ' SKS',
                        'title' => $sec->mataKuliah->name,
                        'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                        'dosen_ketua' => $sec->dosen?->name ?? 'Dosen Pengampu',
                        'dosen_wakil' => $sec->dosenPendamping?->name ?? null,
                        'cover' => null,
                        'type' => 'Kelas Aktif',
                        'work' => 'Perkuliahan semester ' . ($sec->semester->name ?? 'aktif'),
                        'due' => '',
                        'students_count' => $sec->students_count,
                        'assessments_count' => $sec->assessments_count,
                        'enrollment_code' => $sec->enrollment_code,
                        'enrollment_url' => $sec->enrollment_url,
                        'qr_url' => route('kelas.qr', $sec->id),
                        'svg_index' => ($sec->id % 4) + 1,
                    ];
                }
            }
        }

        return view('learning.courses', compact('courses'));
    }

    public function course(int $course)
    {
        $courseData = Learning::course($course);
        session(["learning.discussion_reads.$course" => count(Learning::courseDiscussions($course))]);

        return view('learning.course', ['course' => $courseData, 'items' => array_filter(Learning::items(), fn ($item) => $item['course'] === $course)]);
    }

    public function item(int $course, int $item)
    {
        return view('learning.item', ['course' => Learning::course($course), 'item' => Learning::resource($course, $item)]);
    }

    public function quizRoom(int $course, int $item)
    {
        $isDosen = (session('auth_user.role') === 'dosen') || (auth()->user()?->hasRole(\App\Models\Role::DOSEN));
        abort_if($isDosen, 403, 'Akses ditolak: Dosen tidak dapat mengikuti ujian CBT mahasiswa.');

        $resource = Learning::resource($course, $item);
        abort_unless(in_array($resource['type'], ['kuis', 'tugas', 'coding', 'uts', 'uas']), 404);

        $submission = session("learning.submissions.$item", null);

        if (!empty($resource['randomize_questions']) && !empty($resource['questions'])) {
            $studentId = session('auth_user.id', 1);
            $cacheKey = "learning.quiz_order.{$item}.{$studentId}";
            $order = session($cacheKey);
            if (!is_array($order) || count($order) !== count($resource['questions'])) {
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
            'course' => Learning::course($course),
            'item' => $resource,
            'submission' => $submission,
            'isCompleted' => !empty($submission),
        ]);
    }

    public function assignments(Request $request)
    {
        $items = array_filter(Learning::items(), function ($item) use ($request) {
            return in_array($item['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas'])
                && (! $request->filled('course') || $item['course'] === (int) $request->query('course'))
                && (! $request->filled('type') || $item['type'] === $request->query('type'))
                && str_contains(mb_strtolower($item['title']), mb_strtolower((string) $request->query('q', '')));
        });
        uasort($items, fn ($a, $b) => strcmp($a['due'] ?? '9999', $b['due'] ?? '9999'));

        return view('learning.assignments', ['items' => $items, 'courses' => Learning::courses()]);
    }

    public function discussions()
    {
        return view('learning.discussions', ['items' => Learning::items(), 'courses' => Learning::courses()]);
    }

    public function createCourse()
    {
        return view('dosen.course-form');
    }

    public function storeCourse(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120', 'code' => 'required|string|max:20',
            'description' => 'required|string|max:2000', 'lecturer' => 'required|string|max:120',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video' => 'nullable|url:http,https|max:2000',
        ]);
        $data['video'] ??= null;
        $courses = Learning::courses();
        $data['id'] = max(array_keys($courses)) + 1;
        $data['cover'] = $request->hasFile('cover') ? $this->upload($request->file('cover')) : null;
        $courses[$data['id']] = $data;
        session(['learning.courses' => $courses]);

        return redirect()->route('dosen.course.show', $data['id'])->with('notice', 'Course ditambahkan ke sesi pratinjau ini.');
    }

    public function createItem(int $course)
    {
        return view('dosen.item-form', ['course' => Learning::course($course)]);
    }

    public function storeItem(Request $request, int $course)
    {
        Learning::course($course);
        $academic = \App\Support\AcademicPreview::config($course);

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
            'component' => ['nullable', Rule::in(array_column($academic['components'],'code'))],
            'questions' => 'nullable|array|min:1',
            'questions.*.type' => ['required', Rule::in(['uraian','pilihan','kompleks','coding','benar_salah','mencocokkan'])],
            'questions.*.prompt' => 'required|string|max:10000',
            'questions.*.points' => 'required|integer|min:1|max:1000',
            'questions.*.score_mode' => 'nullable|in:parsial,semua_atau_nol',
            'questions.*.cpmk' => ['required', Rule::in(array_column($academic['cpmk'],'code'))],
            'questions.*.options' => 'nullable|string|max:3000',
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
            'options' => 'nullable|string|max:3000', 'cpmk' => 'required|string|max:1000',
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
            if (in_array($question['type'], ['pilihan','kompleks'])) {
                $options = array_values(array_filter(array_map('trim', explode("\n",$question['options'] ?? '')), fn($v)=>$v!==''));
                if (count($options)<2 || count($options)>20 || count(array_unique($options))!==count($options)) return back()->withErrors(["questions.$index.options"=>'Isi 2–20 pilihan berbeda untuk soal '.($index+1).'.'])->withInput();
            }
        }
        if (!empty($data['questions'])) {
            if (! in_array($category, ['tugas', 'coding', 'kuis', 'uts', 'uas'], true)) {
                return back()->withErrors(['type' => 'Paket soal campuran hanya dapat digunakan untuk Tugas, Kuis, UTS, dan UAS.'])->withInput();
            }
            foreach ($data['questions'] as $index => &$question) {
                $question['points'] = !empty($question['points']) && (int)$question['points'] > 0 ? (int)$question['points'] : 100;
                $question['score_mode'] = $question['score_mode'] ?? 'parsial';
                $question['image'] = $request->hasFile("questions.$index.image") ? $this->upload($request->file("questions.$index.image")) : null;
                $question['alt'] = $question['image']
                    ? trim((string) ($question['alt'] ?? '')) ?: Str::limit('Gambar pendukung untuk '.strip_tags($question['prompt']), 300, '')
                    : null;
                $mapping = collect($academic['cpmk'])->firstWhere('code',$question['cpmk']);
                $question['cpl'] = $mapping['cpl'];
            }
            unset($question);
            $data['questions'] = array_values($data['questions']);
            $data['points'] = array_sum(array_column($data['questions'],'points'));
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
            $videoAttachment = collect($data['attachments'])->first(function ($file) {
                return str_starts_with((string) session("learning.files.$file.mime", ''), 'video/');
            });
            $videoLink = trim((string) ($data['link'] ?? ''));
            $playableLink = $videoLink !== '' && (
                Learning::youtubeEmbedUrl($videoLink) !== null
                || (bool) preg_match('/\.(?:mp4|webm|ogg)(?:[?#].*)?$/i', $videoLink)
            );

            if (! $videoAttachment && ! $playableLink) {
                return back()->withErrors(['pin_video' => 'Pilih tautan YouTube/video atau lampirkan berkas video MP4 terlebih dahulu.'])->withInput();
            }

            $courses = Learning::courses();
            $courseData = $courses[$course];
            $courseData['video'] = $playableLink ? $videoLink : $videoAttachment;
            $courseData['video_type'] = $playableLink ? 'url' : 'file';
            $courseData['video_title'] = $data['title'];
            $courses[$course] = $courseData;
            session(['learning.courses' => $courses]);
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
        $messages = Learning::courseDiscussions($course);

        $user = auth()->user();
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
        $messages[] = $newMessage;
        session([
            "learning.course_discussions.$course" => $messages,
            "learning.discussion_reads.$course" => count($messages),
        ]);

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
        Learning::resource($course, $item);
        $data = $request->validate(['message' => 'required|string|max:3000']);
        $messages = Learning::courseDiscussions($course);

        $user = auth()->user();
        $sessionUser = session('auth_user');

        $author = $user?->name ?? ($sessionUser['name'] ?? 'Ahmad Maulana');
        $role = $user?->role?->name ?? ($sessionUser['role'] ?? 'mahasiswa');
        $senderKey = $user
            ? 'user:'.$user->getAuthIdentifier()
            : 'preview:'.$role.':'.($sessionUser['id'] ?? $sessionUser['number'] ?? $sessionUser['email'] ?? 1);

        $messages[] = [
            'author' => $author,
            'sender_key' => $senderKey,
            'message' => $data['message'],
            'time' => now()->format('H:i'),
            'timestamp' => now()->timestamp,
            'date_key' => now()->toDateString(),
            'date_label' => 'Hari ini',
            'role' => $role,
        ];
        session([
            "learning.course_discussions.$course" => $messages,
            "learning.discussion_reads.$course" => count($messages),
        ]);
        session(["learning.discussions.$item" => $messages]);

        $redirectRoute = ($role === 'dosen') ? 'dosen.course.show' : 'mahasiswa.course.show';

        return redirect(route($redirectRoute, $course).'#diskusi-kelas');
    }

    public function submit(Request $request, int $course, int $item)
    {
        $resource = Learning::resource($course, $item);
        abort_unless(in_array($resource['type'], ['tugas', 'coding', 'kuis']), 404);

        $allowLate = $resource['allow_late'] ?? true;
        if (! $allowLate && ! empty($resource['due']) && \Carbon\Carbon::parse($resource['due'])->isPast()) {
            return back()->withErrors(['answer' => 'Batas waktu pengumpulan telah berakhir. Pengampu mengunci tugas ini dan tidak menerima pengumpulan terlambat.'])->withInput();
        }

        $data = $request->validate([
            'question_answers' => 'nullable|array',
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

        if (!empty($resource['questions'])) {
            $answers = $data['question_answers'] ?? [];
            if (! $isFromQuizRoom && count($answers) !== count($resource['questions'])) {
                return back()->withErrors(['question_answers' => 'Jawab seluruh soal sebelum mengumpulkan.'])->withInput();
            }
            foreach ($resource['questions'] as $index => $question) {
                $answer = $answers[$index] ?? [];
                if (in_array($question['type'], ['pilihan', 'kompleks'])) {
                    $options = array_values(array_filter(array_map('trim', explode("\n", $question['options'] ?? '')), fn ($v) => $v !== ''));
                    $choices = $answer['choices'] ?? [];
                    if (!empty($choices)) {
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

        if ($request->boolean('from_quiz_room')) {
            return redirect()->route('mahasiswa.quiz.room', [$course, $item])->with('notice', 'Jawaban berhasil dikirim! Kuis Anda telah berhasil dikumpulkan.');
        }

        return redirect()->route('mahasiswa.course.item', [$course, $item])->with('notice', 'Jawaban dikumpulkan dalam sesi pratinjau. Belum dinilai.');
    }

    private function upload($file): string
    {
        $id = (string) Str::uuid();
        session(["learning.files.$id" => ['path' => $file->store('learning-preview', 'local'), 'name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType()]]);

        return $id;
    }

    public function file(Request $request, string $file)
    {
        $meta = session("learning.files.$file");
        abort_unless($meta && Storage::disk('local')->exists($meta['path']), 404);
        $inline = in_array($meta['mime'], ['image/jpeg', 'image/png', 'image/webp'])
            || str_starts_with((string) $meta['mime'], 'video/')
            || ($request->boolean('inline') && $meta['mime'] === 'application/pdf');
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($inline && !$request->boolean('download')) {
            return Storage::disk('local')->response($meta['path'], $meta['name'], $headers + ['Content-Type' => $meta['mime']]);
        }

        return Storage::disk('local')->download($meta['path'], $meta['name'], $headers);
    }
}
