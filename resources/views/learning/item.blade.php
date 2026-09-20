@extends('layouts.mahasiswa')

@section('title', $item['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $currentRole = auth()->user()?->role?->name ?? (session('auth_user.role') ?? (request()->routeIs('dosen.*') ? 'dosen' : 'mahasiswa'));
    $isLecturer = in_array($currentRole, ['dosen', 'kaprodi'], true) || request()->routeIs('dosen.*');
    if ($currentRole === 'mahasiswa' || session('auth_user.role') === 'mahasiswa') {
        $isLecturer = false;
    }
    $isTask = in_array($item['type'], ['tugas', 'coding', 'kuis']);
    $hasMultiQuestions = !empty($item['questions']);
    $isDedicatedQuiz = ($item['id'] === 1 || $item['type'] === 'kuis');
    $submission = session('learning.submissions.'.$item['id']);
    $itemGrade = session('academic.item_grades.'.$item['id'].'.1');
    $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
    $allowLate = $item['allow_late'] ?? true;
    $isLocked = !$submission && $isPast && !$allowLate;
@endphp

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex flex-wrap gap-2 text-sm text-muted" aria-label="Breadcrumb">
        <a class="quiet-link" href="{{ $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.index') }}">
            {{ $isLecturer ? 'Course Dosen' : 'Course' }}
        </a>
        <span>/</span>
        <a class="quiet-link" href="{{ $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.show', $course['id']) }}">
            {{ $course['code'] }}
        </a>
        <span>/ {{ $item['module'] }}</span>
    </nav>

    {{-- Title & Header --}}
    <header class="flex flex-col gap-2">
        <div class="flex items-center gap-2">
            <span class="rounded bg-brand-soft px-2.5 py-0.5 text-xs font-bold text-brand">
                {{ \App\Support\LearningPreview::labels()[$item['type']] }}
            </span>
            @if(!empty($item['component']))
                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                    Komponen: {{ strtoupper($item['component']) }}
                </span>
            @endif
        </div>
        <h1 class="page-heading mt-1">{{ $item['title'] }}</h1>
        <p class="page-description">{{ $course['lecturer'] }} · {{ $item['module'] }}</p>
    </header>

    {{-- Submission form wraps main questions & side actions if student --}}
    <form data-submission-form method="post" enctype="multipart/form-data" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}">
        @csrf

        @if($isTask && ($isLecturer || !$isDedicatedQuiz))
        <div class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_340px]">
        @else
        <div class="w-full space-y-6">
        @endif
            {{-- Main Column: Instructions, Multi-Question Cards, Stimulus, Attachments, Discussions --}}
            <div class="min-w-0 space-y-6">
                {{-- Instructions Card --}}
                <section class="surface p-6 sm:p-7">
                    <h2 class="section-heading">{{ $item['type'] === 'materi' ? 'Materi Pembelajaran' : 'Petunjuk Pengerjaan' }}</h2>
                    <p class="prose-content mt-4 text-sm">{{ $item['body'] }}</p>

                    @if($isDedicatedQuiz)
                        <div class="mt-6 pt-5 border-t border-line/60">
                            @if($submission)
                                <div class="rounded-xl border border-line bg-canvas/60 p-5 space-y-3">
                                    <div class="flex items-start sm:items-center gap-3.5">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-ink">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>
                                        </div>
                                        <div class="space-y-0.5">
                                            <h3 class="text-sm font-bold text-ink">Kuis Telah Berhasil Dikumpulkan</h3>
                                            <p class="text-xs text-muted leading-relaxed">
                                                Terkumpul pada <span class="font-semibold text-ink">{{ $submission['time'] ?? 'hari ini' }}</span>. Jawaban Anda telah tersimpan di sistem dan kuis tidak dapat dikerjakan ulang.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="pt-2 flex flex-wrap items-center gap-3 border-t border-line">
                                        <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-secondary text-xs py-2 px-3.5 font-bold">
                                            Lihat Tanda Terima Kuis →
                                        </a>
                                        <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="quiet-link text-xs">
                                            ← Kembali ke Halaman Course
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5 space-y-4">
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <div class="space-y-1">
                                            <span class="text-xs font-bold uppercase tracking-wider text-muted">Informasi Ujian &amp; Penilaian</span>
                                            <div class="flex flex-wrap items-center gap-2.5 text-xs text-slate-600 font-medium pt-0.5">
                                                <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-md border border-slate-200 text-ink font-semibold">
                                                    <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                                    Durasi: {{ !empty($item['duration_enabled']) ? ($item['duration_minutes'] ?? 60).' Menit' : 'Tanpa Batas Waktu' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-md border border-slate-200 text-ink font-semibold">
                                                    <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                    {{ count($item['questions'] ?? []) ?: 1 }} Butir Soal
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-md border border-slate-200 text-ink font-semibold">
                                                    <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                                                    Total {{ $item['points'] ?? 100 }} Poin
                                                </span>
                                                @if(!empty($item['due']))
                                                    <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-md border border-slate-200 text-ink font-semibold">
                                                        <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                                        Tenggat: {{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="shrink-0">
                                            @if($isLecturer)
                                                <a href="{{ route('dosen.gradebook', $course['id']) }}" class="button-secondary text-xs py-2.5 px-5 font-bold shadow-xs">
                                                    Lihat Nilai &amp; Jawaban Kuis →
                                                </a>
                                            @elseif($isLocked)
                                                <button type="button" disabled class="button-secondary text-xs py-2.5 px-5 font-bold opacity-60 cursor-not-allowed">
                                                    Kuis Ditutup
                                                </button>
                                            @else
                                                <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-primary text-xs py-2.5 px-5 font-bold shadow-xs">
                                                    Mulai Kerjakan Kuis →
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-muted leading-relaxed">
                                        @if($isLecturer)
                                            Sebagai dosen pengampu, Anda dapat meninjau butir soal di bawah ini atau memeriksa rekap hasil pengerjaan kuis mahasiswa di buku nilai.
                                        @else
                                            Tekan tombol <strong>Mulai Kerjakan Kuis</strong> untuk masuk ke ruang ujian fokus layar penuh. Soal dapat dikerjakan secara berurutan atau acak menggunakan daftar nomor soal.
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($item['question_image']))
                        <figure class="mt-6">
                            <a href="{{ route('preview.file', $item['question_image']) }}" target="_blank" rel="noopener">
                                <img src="{{ route('preview.file', $item['question_image']) }}" alt="{{ $item['image_alt'] }}" class="max-h-[480px] max-w-full rounded-lg object-contain">
                            </a>
                            <figcaption class="mt-2 text-xs text-muted">{{ $item['image_alt'] }}</figcaption>
                        </figure>
                    @endif

                    @if(!empty($item['attachments']) || !empty($item['link']))
                        <h3 class="mt-7 text-sm font-semibold">Lampiran Berkas &amp; Referensi</h3>
                        <div class="mt-3 space-y-2">
                            @foreach($item['attachments'] ?? [] as $file)
                                <a class="flex items-center justify-between gap-3 rounded-lg bg-canvas p-3.5 text-sm hover:bg-slate-100" href="{{ route('preview.file', $file) }}" target="_blank" rel="noopener">
                                    <span class="flex min-w-0 items-center gap-3">
                                        @if(str_starts_with(session('learning.files.'.$file.'.mime', ''), 'image/'))
                                             <img class="h-12 w-16 rounded-md object-cover" src="{{ route('preview.file', $file) }}" alt="{{ session('learning.files.'.$file.'.name') }}">
                                        @endif
                                        <span class="break-all font-medium">{{ session('learning.files.'.$file.'.name', 'Berkas materi') }}</span>
                                    </span>
                                    <span class="text-xs text-muted">Buka ↗</span>
                                </a>
                            @endforeach
                            @if(!empty($item['link']))
                                <a class="quiet-link block break-all py-2 text-xs" href="{{ $item['link'] }}" target="_blank" rel="noopener noreferrer">
                                    {{ $item['link'] }} ↗
                                </a>
                            @endif
                        </div>
                    @endif

                </section>

                {{-- Task Questions for Regular Assignments (Non-CBT Quiz) --}}
                @if(!$isLecturer && $isTask && !$isDedicatedQuiz && $hasMultiQuestions)
                    <div class="space-y-5">
                        @foreach($item['questions'] as $qIdx => $q)
                            <section class="surface p-6 sm:p-7 space-y-4">
                                <div class="flex items-center justify-between border-b border-line/50 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-slate-900 text-white text-xs font-bold px-2 py-0.5">Soal {{ $qIdx + 1 }}</span>
                                        <span class="text-xs font-semibold text-muted uppercase">{{ $q['type'] }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-ink">{{ $q['points'] ?? 10 }} Poin</span>
                                </div>
                                <p class="text-sm text-ink leading-relaxed">{{ $q['prompt'] }}</p>

                                @if($q['type'] === 'benar_salah')
                                    <div class="flex items-center gap-4 pt-1">
                                        <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar" @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                            <span class="font-medium text-ink">Benar</span>
                                        </label>
                                        <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah" @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                            <span class="font-medium text-ink">Salah</span>
                                        </label>
                                    </div>
                                @elseif($q['type'] === 'mencocokkan')
                                    @php
                                        $pairLines = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                        $pairs = [];
                                        foreach($pairLines as $pLine) {
                                            if (str_contains($pLine, ' = ')) {
                                                [$left, $right] = explode(' = ', $pLine, 2);
                                                $pairs[] = ['left' => trim($left), 'right' => trim($right)];
                                            } elseif (str_contains($pLine, '=')) {
                                                [$left, $right] = explode('=', $pLine, 2);
                                                $pairs[] = ['left' => trim($left), 'right' => trim($right)];
                                            } else {
                                                $pairs[] = ['left' => $pLine, 'right' => $pLine];
                                            }
                                        }
                                        $allRights = array_column($pairs, 'right');
                                    @endphp
                                    <div class="space-y-3 pt-1">
                                        @foreach($pairs as $pIdx => $pair)
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-lg bg-canvas border border-line/50">
                                                <span class="text-xs font-medium text-ink">{{ $pair['left'] }}</span>
                                                <select name="question_answers[{{ $qIdx }}][matching][{{ $pIdx }}]" class="field text-xs sm:w-64">
                                                    <option value="">-- Pilih Pasangan --</option>
                                                    @foreach($allRights as $target)
                                                        <option value="{{ $target }}" @selected(old("question_answers.$qIdx.matching.$pIdx", $submission['question_answers'][$qIdx]['matching'][$pIdx] ?? '') === $target)>
                                                            {{ $target }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(in_array($q['type'], ['pilihan', 'kompleks']))
                                    @php
                                        $options = array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($option) => $option !== '');
                                        $isMultiple = $q['type'] === 'kompleks';
                                    @endphp
                                    <div class="space-y-2 pt-1">
                                        @foreach($options as $option)
                                            <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                                <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}" name="question_answers[{{ $qIdx }}][choices][]" value="{{ $option }}"
                                                    @checked(in_array($option, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                                <span class="text-ink">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <textarea name="question_answers[{{ $qIdx }}][text]" rows="4" class="field text-xs" placeholder="Tulis jawaban di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? '') }}</textarea>
                                @endif
                            </section>
                        @endforeach
                    </div>
                @endif

                {{-- Single Objective Questions (Only Choices in Center, No Duplicate Button) --}}
                @if(!$isLecturer && $isTask && !$hasMultiQuestions && in_array($item['question_type'], ['pilihan', 'kompleks', 'benar_salah']))
                    <section class="surface p-6 sm:p-7 space-y-4">
                        <h2 class="section-heading">Lembar Jawaban Soal</h2>
                        @if(in_array($item['question_type'], ['pilihan', 'kompleks']))
                            <fieldset class="space-y-2">
                                <legend class="form-label text-xs font-bold">{{ $item['question_type'] === 'kompleks' ? 'Pilih semua jawaban yang benar' : 'Pilih satu jawaban' }}</legend>
                                <div class="space-y-2">
                                    @foreach(array_filter(array_map('trim', explode("\n", $item['options'] ?? '')), fn($option) => $option !== '') as $option)
                                        <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                            <input type="{{ $item['question_type'] === 'kompleks' ? 'checkbox' : 'radio' }}" name="choices[]" value="{{ $option }}"
                                                @checked(in_array($option, old('choices', $submission['choices'] ?? [])))>
                                            <span class="text-ink">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @elseif($item['question_type'] === 'benar_salah')
                            <fieldset class="space-y-2">
                                <legend class="form-label text-xs font-bold">Pilih Benar atau Salah</legend>
                                <div class="flex items-center gap-4 pt-1">
                                    <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                        <input type="radio" name="boolean_choice" value="Benar" @checked(old('boolean_choice', $submission['boolean_choice'] ?? '') === 'Benar')>
                                        <span class="font-medium text-ink">Benar</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                        <input type="radio" name="boolean_choice" value="Salah" @checked(old('boolean_choice', $submission['boolean_choice'] ?? '') === 'Salah')>
                                        <span class="font-medium text-ink">Salah</span>
                                    </label>
                                </div>
                            </fieldset>
                        @endif
                    </section>
                @endif

            </div>

            {{-- Right Aside Column: Status & Submission (Student) or Content Control (Lecturer) --}}
            @if($isTask)
                @if($isLecturer)
                    {{-- Lecturer Management Panel --}}
                    <aside class="rounded-xl bg-white p-5 shadow-sm space-y-5 h-fit xl:sticky xl:top-24 border border-line/60">
                        <div class="flex items-center justify-between border-b border-line/60 pb-3">
                            <h2 class="text-sm font-bold text-ink">Pengelolaan Pengampu</h2>
                            <span class="rounded bg-brand-soft px-2 py-0.5 text-[11px] font-bold text-brand">Dosen</span>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between text-muted">
                                <span>Total Bobot:</span>
                                <span class="font-bold text-ink">{{ $item['points'] ?? 100 }} Poin</span>
                            </div>
                            <div class="flex items-center justify-between text-muted">
                                <span>Tenggat Waktu:</span>
                                <span class="font-medium text-ink">{{ $item['due'] ? \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tanpa tenggat' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted">
                                <span>Pengumpulan Terlambat:</span>
                                <span class="font-medium text-ink">{{ !empty($item['allow_late']) ? 'Diizinkan' : 'Ditolak / Dikunci' }}</span>
                            </div>
                            @if(!empty($item['component']))
                                <div class="flex items-center justify-between text-muted">
                                    <span>Komponen Evaluasi:</span>
                                    <span class="font-semibold text-ink uppercase">{{ $item['component'] }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-lg bg-canvas p-3 text-xs space-y-1">
                            <p class="font-semibold text-ink">Penilaian Mahasiswa</p>
                            <p class="text-muted text-[11px]">Buka buku nilai untuk mengevaluasi jawaban yang masuk dan menginputkan skor CPMK.</p>
                        </div>

                        <div class="space-y-2 pt-2">
                            <a href="{{ route('dosen.gradebook', $course['id']) }}" class="button-primary w-full py-2.5 text-xs font-bold text-center block">
                                Lihat &amp; Nilai Jawaban Mahasiswa →
                            </a>
                            <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-secondary w-full py-2 text-xs font-semibold text-center block">
                                + Tambah Konten / Soal Baru
                            </a>
                            <a href="{{ route('dosen.course.show', $course['id']) }}" class="quiet-link text-xs text-center block pt-1">
                                ← Kembali ke Halaman Course
                            </a>
                        </div>
                    </aside>
                @else
                    @if(!$isDedicatedQuiz)
                        {{-- Student Submission Panel (Google Classroom Style for Tugas & Coding) --}}
                        <aside class="rounded-xl bg-white p-5 shadow-sm space-y-4 h-fit xl:sticky xl:top-24 border border-line/60">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-bold text-ink">Tugas Anda</h2>
                                @if($submission)
                                    <span class="status font-medium text-ink bg-canvas">Sudah dikumpulkan</span>
                                @elseif($isPast)
                                    <span class="status font-medium text-muted bg-canvas">Terlambat</span>
                                @else
                                    <span class="status font-medium text-muted bg-canvas">Belum diserahkan</span>
                                @endif
                            </div>

                            <div class="text-xs text-muted">
                                <span>{{ $item['points'] ?? 100 }} Poin</span>
                                <span>·</span>
                                <span>{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M, H:i') : 'Tanpa tenggat' }}</span>
                            </div>

                            @if($submission && $itemGrade)
                                <div class="rounded-lg bg-canvas p-3 text-xs border border-line/50">
                                    <p class="font-bold text-ink">Nilai Dosen: {{ array_sum($itemGrade['points'] ?? []) }} / {{ $item['points'] ?? 100 }}</p>
                                    @if(!empty($itemGrade['feedback']))
                                        <p class="mt-1 text-muted italic">"{{ $itemGrade['feedback'] }}"</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Attached Work Items (Existing or New) --}}
                            <div class="space-y-2" data-attachment-container>
                                {{-- Saved files --}}
                                @if(!empty($submission['files']))
                                    @foreach($submission['files'] as $sf)
                                        <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-canvas border border-line/40">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <span class="text-ink truncate font-medium">{{ $sf['name'] }}</span>
                                            </div>
                                            <input type="hidden" name="keep_files[]" value="{{ $sf['id'] }}">
                                        </div>
                                    @endforeach
                                @endif

                                {{-- Saved link --}}
                                @if(!empty($submission['link']))
                                    <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-canvas border border-line/40">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                            <a href="{{ $submission['link'] }}" target="_blank" class="text-brand truncate font-medium hover:underline">{{ $submission['link'] }}</a>
                                        </div>
                                    </div>
                                @endif

                                {{-- Saved answer preview if exists --}}
                                @if(!empty($submission['answer']) && empty($item['questions']))
                                    <div class="text-xs p-2.5 rounded-lg bg-canvas border border-line/40">
                                        <span class="text-muted block text-[11px] font-semibold mb-1">Catatan / Jawaban:</span>
                                        <p class="text-ink line-clamp-3">{{ $submission['answer'] }}</p>
                                    </div>
                                @endif

                                {{-- Dynamic list for new additions --}}
                                <div data-active-attachments class="space-y-2"></div>
                            </div>

                            {{-- Action Button: + Tambah atau buat --}}
                            @if(!$isLocked)
                                <div class="relative" data-add-work-dropdown>
                                    <button type="button" data-toggle-dropdown class="button-secondary w-full py-2 text-xs font-semibold flex items-center justify-center gap-2">
                                        <span class="text-sm font-bold text-brand">+</span> Tambah atau buat
                                    </button>
                                    <div data-dropdown-menu hidden class="absolute left-0 right-0 top-full mt-1.5 z-20 rounded-xl bg-white p-1.5 shadow-lg border border-line/60 space-y-1">
                                        <button type="button" data-action-add="link" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-ink hover:bg-slate-100 transition text-left">
                                            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                            <span>Tautan / Link</span>
                                        </button>
                                        <button type="button" data-action-add="file" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-ink hover:bg-slate-100 transition text-left">
                                            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Berkas / File</span>
                                        </button>
                                        <button type="button" data-action-add="text" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-ink hover:bg-slate-100 transition text-left">
                                            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>Catatan / Jawaban Teks</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- Hidden inputs for file/link/answer --}}
                            <input type="file" name="files[]" multiple data-submission-files class="hidden" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.webp">
                            <input type="hidden" name="link" data-submission-link value="{{ old('link') }}">

                            {{-- Optional text answer area --}}
                            <div data-text-answer-box hidden class="space-y-1 pt-1">
                                <div class="flex items-center justify-between">
                                    <label class="form-label text-[11px] mb-0" for="answer_field">Jawaban Teks</label>
                                    <button type="button" data-remove-text-box class="text-[11px] text-danger hover:underline">Batal</button>
                                </div>
                                <textarea id="answer_field" name="answer" rows="4" class="field text-xs" placeholder="Tuliskan jawaban atau catatan pengerjaan tugas...">{{ old('answer', $submission['answer'] ?? '') }}</textarea>
                            </div>

                            {{-- Submit Button: ONE AND ONLY (Right sidebar) --}}
                            <div class="pt-2">
                                @if($isLocked)
                                    <button type="button" disabled class="button-secondary w-full py-2.5 text-xs font-bold opacity-60 cursor-not-allowed">
                                        Pengumpulan Ditutup
                                    </button>
                                @else
                                    <button type="submit" class="button-primary w-full py-2.5 text-xs font-bold">
                                        {{ $submission ? 'Kumpulkan Ulang Jawaban' : 'Kumpulkan Tugas' }}
                                    </button>
                                @endif
                            </div>
                        </aside>
                    @endif
                @endif
            @endif
        </div>
    </form>

    {{-- Modal Dialog Tambah Link (Google Classroom Style) --}}
    <dialog id="link-modal" class="rounded-xl border border-line/60 bg-white p-6 shadow-xl backdrop:bg-ink/40 max-w-md w-full">
        <h3 class="text-sm font-bold text-ink mb-1">Tambahkan Link</h3>
        <p class="text-xs text-muted mb-4">Tempelkan tautan URL repositori, Google Drive, atau dokumen referensi tugas.</p>
        <div class="space-y-1.5">
            <label for="modal-link-input" class="form-label text-xs">Link <span class="text-danger">*</span></label>
            <input type="url" id="modal-link-input" class="field text-xs py-2" placeholder="https://example.com/tugas">
            <p id="modal-link-error" class="text-[11px] text-danger hidden">Tautan harus berupa URL valid yang diawali http:// atau https://</p>
        </div>
        <div class="mt-5 flex items-center justify-end gap-2.5">
            <button type="button" id="modal-link-cancel" class="button-secondary text-xs py-1.5 px-3">Batal</button>
            <button type="button" id="modal-link-submit" class="button-primary text-xs py-1.5 px-3 font-semibold">Tambahkan Link</button>
        </div>
    </dialog>
</div>
@endsection
