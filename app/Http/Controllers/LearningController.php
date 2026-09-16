<?php

namespace App\Http\Controllers;

use App\Support\LearningPreview as Learning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LearningController extends Controller
{
    public function courses(Request $request)
    {
        $q = mb_strtolower((string) $request->query('q', ''));
        $courses = array_filter(Learning::courses(), fn ($course) => str_contains(mb_strtolower($course['title'].' '.$course['code'].' '.$course['lecturer']), $q));

        return view('learning.courses', compact('courses'));
    }

    public function course(Request $request, int $course)
    {
        $activeSection = strtoupper((string) $request->query('section', 'A'));
        if (!in_array($activeSection, ['A', 'B', 'C'])) {
            $activeSection = 'A';
        }

        $items = array_filter(Learning::items(), function ($item) use ($course, $activeSection) {
            $itemSection = $item['section'] ?? 'A';
            return $item['course'] === $course && ($itemSection === $activeSection || $itemSection === 'ALL');
        });

        return view('learning.course', [
            'course' => Learning::course($course),
            'items' => $items,
            'activeSection' => $activeSection,
        ]);
    }

    public function item(int $course, int $item)
    {
        return view('learning.item', ['course' => Learning::course($course), 'item' => Learning::resource($course, $item)]);
    }

    public function quizRoom(int $course, int $item)
    {
        $resource = Learning::resource($course, $item);
        abort_unless(in_array($resource['type'], ['kuis', 'tugas', 'coding']), 404);

        $submission = session("learning.submissions.$item", null);

        return view('learning.quiz-room', [
            'course' => Learning::course($course),
            'item' => $resource,
            'submission' => $submission,
        ]);
    }

    public function assignments(Request $request)
    {
        $items = array_filter(Learning::items(), function ($item) use ($request) {
            return in_array($item['type'], ['tugas', 'coding', 'kuis'])
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
        $data = $request->validate([
            'title' => 'required|string|max:160', 'module' => 'required|string|max:100',
            'type' => ['required', Rule::in(array_keys(Learning::labels()))],
            'body' => 'required|string|max:15000', 'due' => 'nullable|date',
            'allow_late' => 'nullable|in:0,1,true,false',
            'link' => 'nullable|url:http,https|max:2000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp,mp4|max:20480',
            'formats' => 'required_if:type,tugas,kuis,coding|array|min:1',
            'formats.*' => [Rule::in(['file', 'image', 'link', 'text'])],
            'question_type' => ['required', Rule::in(['uraian', 'pilihan', 'kompleks', 'coding', 'benar_salah', 'mencocokkan'])],
            'question_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_alt' => 'required_with:question_image|nullable|string|max:300',
            'option_images' => 'nullable|array|max:20',
            'option_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'points' => 'nullable|integer|min:1|max:1000',
            'component' => ['nullable', Rule::in(array_column($academic['components'],'code'))],
            'questions' => 'nullable|array|min:1|max:30',
            'questions.*.type' => ['required', Rule::in(['uraian','pilihan','kompleks','coding','benar_salah','mencocokkan'])],
            'questions.*.prompt' => 'required|string|max:10000',
            'questions.*.points' => 'required|integer|min:1|max:1000',
            'questions.*.cpmk' => ['required', Rule::in(array_column($academic['cpmk'],'code'))],
            'questions.*.options' => 'nullable|string|max:3000',
            'questions.*.matching' => 'nullable|array',
            'questions.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'questions.*.alt' => 'nullable|string|max:300',
            'options' => 'nullable|string|max:3000', 'cpmk' => 'required|string|max:1000',
            'duration_mode' => 'nullable|in:enabled,disabled',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);
        if (in_array($data['question_type'], ['pilihan', 'kompleks']) && in_array($data['type'], ['tugas', 'kuis'])) {
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
            if ($request->hasFile("questions.$index.image") && empty($question['alt'])) {
                return back()->withErrors(["questions.$index.alt"=>'Deskripsi gambar soal wajib diisi.'])->withInput();
            }
            if (in_array($question['type'], ['pilihan','kompleks'])) {
                $options = array_values(array_filter(array_map('trim', explode("\n",$question['options'] ?? '')), fn($v)=>$v!==''));
                if (count($options)<2 || count($options)>20 || count(array_unique($options))!==count($options)) return back()->withErrors(["questions.$index.options"=>'Isi 2–20 pilihan berbeda untuk soal '.($index+1).'.'])->withInput();
            }
        }
        if (!empty($data['questions'])) {
            if (!in_array($data['type'], ['tugas','kuis'])) return back()->withErrors(['type'=>'Paket soal campuran digunakan untuk tugas atau kuis.'])->withInput();
            foreach ($data['questions'] as $index => &$question) {
                $question['image'] = $request->hasFile("questions.$index.image") ? $this->upload($request->file("questions.$index.image")) : null;
                $mapping = collect($academic['cpmk'])->firstWhere('code',$question['cpmk']);
                $question['cpl'] = $mapping['cpl'];
            }
            unset($question);
            $data['questions'] = array_values($data['questions']);
            $data['points'] = array_sum(array_column($data['questions'],'points'));
            $data['component'] ??= $data['type']==='kuis' ? 'kuis' : 'tugas';
        }
        $data['question_image'] = $request->hasFile('question_image') ? $this->upload($request->file('question_image')) : null;
        $data['option_images'] = array_map(fn ($file) => $this->upload($file), $request->file('option_images', []));
        $data['attachments'] = array_map(fn ($file) => $this->upload($file), $request->file('attachments', []));
        $data['points'] = $data['points'] ?? 100;
        $data['allow_late'] = $request->boolean('allow_late', true);
        $data['duration_enabled'] = $request->input('duration_mode', 'enabled') === 'enabled';
        $data['duration_minutes'] = $data['duration_enabled'] ? (int) $request->input('duration_minutes', 60) : null;
        $data += ['formats' => [], 'link' => null, 'due' => null, 'options' => null];
        $items = Learning::items();
        $data['id'] = max(array_keys($items)) + 1;
        $data['course'] = $course;
        $items[$data['id']] = $data;
        session(['learning.items' => $items]);

        return redirect()->route('dosen.course.show', $course)->with('notice', 'Konten ditambahkan ke modul dalam sesi pratinjau ini.');
    }

    public function discuss(Request $request, int $course, int $item)
    {
        Learning::resource($course, $item);
        $data = $request->validate(['message' => 'required|string|max:3000']);
        $messages = Learning::discussions($item);
        $author = session('auth_user.name', 'Pengguna');
        $messages[] = ['author' => $author, 'message' => $data['message'], 'time' => now()->format('d M, H:i'), 'timestamp' => now()->timestamp];
        session(["learning.discussions.$item" => $messages]);

        $routePrefix = request()->is('dosen*') ? 'dosen' : 'mahasiswa';
        return redirect(route($routePrefix.'.course.item', [$course, $item]).'#diskusi');
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
        if (!empty($resource['questions'])) {
            $answers=$data['question_answers'] ?? [];
            if (count($answers)!==count($resource['questions'])) return back()->withErrors(['question_answers'=>'Jawab seluruh soal sebelum mengumpulkan.'])->withInput();
            foreach ($resource['questions'] as $index=>$question) {
                $answer=$answers[$index] ?? [];
                if (in_array($question['type'],['pilihan','kompleks'])) {
                    $options=array_values(array_filter(array_map('trim',explode("\n",$question['options'] ?? '')),fn($v)=>$v!==''));
                    $choices=$answer['choices'] ?? [];
                    if (!$choices || array_diff($choices,$options) || ($question['type']==='pilihan' && count($choices)!==1)) return back()->withErrors(['question_answers'=>'Periksa pilihan pada soal '.($index+1).'.'])->withInput();
                } elseif ($question['type'] === 'benar_salah') {
                    if (empty($answer['boolean_choice'])) return back()->withErrors(['question_answers'=>'Pilih Benar atau Salah pada soal '.($index+1).'.'])->withInput();
                } elseif ($question['type'] === 'mencocokkan') {
                    if (empty($answer['matching'])) return back()->withErrors(['question_answers'=>'Pasangkan seluruh item pada soal '.($index+1).'.'])->withInput();
                } elseif (trim($answer['text'] ?? '')==='') return back()->withErrors(['question_answers'=>'Isi jawaban soal '.($index+1).'.'])->withInput();
            }
        } else {
            unset($data['question_answers']);
        }
        $previousFiles = session("learning.submissions.$item.files", []);
        $keep = $request->input('keep_files', $request->boolean('replace_files') ? [] : $previousFiles);
        abort_if(array_diff($keep, $previousFiles), 422);
        if (count($keep) + count($request->file('files', [])) > 5) {
            return back()->withErrors(['files' => 'Maksimal lima lampiran, termasuk berkas sebelumnya.'])->withInput();
        }
        if (empty($data['question_answers']) && ! $keep && ! $request->filled('answer') && ! $request->filled('link') && ! $request->hasFile('files') && ! $request->filled('choices') && ! $request->filled('boolean_choice') && ! $request->filled('matching')) {
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
            || ($request->boolean('inline') && $meta['mime'] === 'application/pdf');
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($inline && !$request->boolean('download')) {
            return Storage::disk('local')->response($meta['path'], $meta['name'], $headers + ['Content-Type' => $meta['mime']]);
        }

        return Storage::disk('local')->download($meta['path'], $meta['name'], $headers);
    }
}
