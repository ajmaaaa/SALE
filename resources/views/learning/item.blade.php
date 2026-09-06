@extends('layouts.mahasiswa')

@section('title', $item['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $isLecturer = (session('auth_user.role') === 'dosen') || request()->routeIs('dosen.*');
    $isTask = in_array($item['type'], ['tugas', 'coding', 'kuis']);
    $hasMultiQuestions = !empty($item['questions']);
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

        @if($isTask)
        <div class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_340px]">
        @else
        <div class="max-w-4xl space-y-6">
        @endif
            {{-- Main Column: Instructions, Multi-Question Cards, Stimulus, Attachments, Discussions --}}
            <div class="min-w-0 space-y-6">
                {{-- Instructions Card --}}
                <section class="surface p-6 sm:p-7">
                    <h2 class="section-heading">{{ $item['type'] === 'materi' ? 'Materi Pembelajaran' : 'Petunjuk Pengerjaan' }}</h2>
                    <p class="prose-content mt-4 text-sm">{{ $item['body'] }}</p>

                    @if($item['type'] === 'coding')
                        {{-- Harmonious Lumina AI banner (Clean, not out-of-place black bar) --}}
                        <div class="mt-6 rounded-xl border border-brand/20 bg-brand-soft/40 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-brand uppercase tracking-wider">Lumina AI Asisten Coding</span>
                                <h3 class="text-base font-bold text-ink mt-0.5">Code Editor &amp; Pengujian Interaktif</h3>
                                <p class="text-xs text-muted mt-1">Kerjakan kode di editor interaktif dengan syntax highlighting, draf lokal, dan tanya petunjuk logika ke Lumina AI.</p>
                            </div>
                            <a href="{{ route('mahasiswa.assignment.code', $item['id']) }}" class="button-primary shrink-0 text-xs py-2.5 px-4 font-semibold inline-flex items-center gap-2 shadow-xs">
                                Buka Editor &amp; Tanya AI ↗
                            </a>
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

                    @if(!empty($item['cpmk']))
                        <details class="mt-6 border-t border-line/60 pt-4">
                            <summary class="cursor-pointer text-xs font-semibold text-ink">Capaian Pembelajaran (CPMK)</summary>
                            <p class="mt-2 text-xs text-muted leading-relaxed">{{ $item['cpmk'] }}</p>
                        </details>
                    @endif
                </section>

                {{-- Multi-Question Items Rendering with Step-by-Step Navigator --}}
                @if($hasMultiQuestions)
                    <section class="space-y-4" data-quiz-container>
                        {{-- Quiz Header & Question Number Navigator --}}
                        <div class="surface p-4 sm:p-5 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/50 pb-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-md bg-brand-soft px-2 py-0.5 text-xs font-semibold text-brand">
                                            {{ count($item['questions']) }} Soal
                                        </span>
                                        <h2 class="text-base font-bold text-ink">{{ $item['title'] }}</h2>
                                    </div>
                                    <p class="mt-1 text-xs text-muted" data-quiz-counter-text>
                                        Sedang menampilkan <strong class="text-ink font-semibold">Soal <span data-quiz-current-num>1</span></strong> dari {{ count($item['questions']) }} soal.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xs font-semibold text-ink bg-canvas px-2.5 py-1 rounded-md border border-line/60">
                                        Total {{ $item['points'] ?? array_sum(array_column($item['questions'], 'points')) }} Poin
                                    </span>
                                    @if(!$isLecturer)
                                        <button type="submit" class="button-primary text-xs py-1.5 px-3.5 font-bold bg-emerald-700 hover:bg-emerald-800 text-white shadow-2xs">
                                            Kumpulkan Kuis
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Question Selector Buttons (1, 2, 3, ...) --}}
                            <div class="flex flex-wrap items-center gap-2 pt-0.5" role="tablist" aria-label="Navigasi nomor soal kuis">
                                <span class="text-xs font-semibold text-muted mr-1 shrink-0">Nomor Soal:</span>
                                @foreach($item['questions'] as $qIdx => $q)
                                    <button type="button"
                                        data-quiz-tab="{{ $qIdx }}"
                                        title="Buka Soal {{ $qIdx + 1 }} ({{ $q['type'] }})"
                                        class="h-8 min-w-8 px-2.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 border {{ $qIdx === 0 ? 'bg-[#102f50] text-white border-[#102f50] shadow-xs' : 'bg-white text-ink border-line hover:border-brand' }}">
                                        <span>{{ $qIdx + 1 }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Individual Question Cards (One active at a time) --}}
                        @foreach($item['questions'] as $qIdx => $q)
                            <div data-quiz-card="{{ $qIdx }}" class="rounded-xl bg-white p-5 sm:p-6 shadow-sm space-y-4 {{ $qIdx === 0 ? '' : 'hidden' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line/60 pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#102f50] text-xs font-bold text-white">
                                            {{ $qIdx + 1 }}
                                        </span>
                                        <span class="text-sm font-bold text-ink">Soal {{ $qIdx + 1 }} dari {{ count($item['questions']) }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-medium text-ink bg-canvas px-2 py-0.5 rounded border border-line/40">
                                            {{ $q['cpmk'] }} @if(!empty($q['cpl']))→ {{ $q['cpl'] }} @endif
                                        </span>
                                        <span class="text-xs text-muted">·</span>
                                        <span class="text-xs font-semibold text-brand">
                                            {{ $q['points'] }} Poin
                                        </span>
                                        <span class="text-xs text-muted">·</span>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">
                                            {{ $q['type'] === 'uraian' ? 'Essay' : ($q['type'] === 'pilihan' ? 'Pilihan Ganda' : ($q['type'] === 'kompleks' ? 'Pilihan Kompleks' : ($q['type'] === 'benar_salah' ? 'Benar / Salah' : ($q['type'] === 'mencocokkan' ? 'Mencocokkan' : 'Coding')))) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-sm text-ink leading-relaxed font-medium">
                                    {{ $q['prompt'] }}
                                </div>

                                @if(!empty($q['image']))
                                    <figure class="mt-3">
                                        <img src="{{ route('preview.file', $q['image']) }}" alt="{{ $q['alt'] ?? 'Stimulus soal' }}" class="max-h-60 rounded-lg object-contain border border-line/40 p-1 bg-white">
                                        @if(!empty($q['alt']))
                                            <figcaption class="mt-1 text-[11px] text-muted">{{ $q['alt'] }}</figcaption>
                                        @endif
                                    </figure>
                                @endif

                                {{-- Question Answering Inputs (Only for Students) --}}
                                @if(!$isLecturer)
                                    @if($q['type'] === 'pilihan')
                                        @php
                                            $opts = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                        @endphp
                                        <div class="space-y-2 pt-1">
                                            @foreach($opts as $optIdx => $opt)
                                                <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-sm cursor-pointer hover:bg-slate-100 transition">
                                                    <input type="radio" name="question_answers[{{ $qIdx }}][choices][]" value="{{ $opt }}"
                                                        @checked(in_array($opt, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                                    <span class="font-semibold text-muted text-xs mr-1">{{ chr(65 + $optIdx) }}.</span>
                                                    <span class="text-ink">{{ $opt }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($q['type'] === 'kompleks')
                                        @php
                                            $opts = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                        @endphp
                                        <div class="space-y-2 pt-1">
                                            <p class="text-xs text-muted">Pilih semua jawaban yang sesuai:</p>
                                            @foreach($opts as $optIdx => $opt)
                                                <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-sm cursor-pointer hover:bg-slate-100 transition">
                                                    <input type="checkbox" name="question_answers[{{ $qIdx }}][choices][]" value="{{ $opt }}"
                                                        @checked(in_array($opt, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                                    <span class="font-semibold text-muted text-xs mr-1">{{ chr(65 + $optIdx) }}.</span>
                                                    <span class="text-ink">{{ $opt }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($q['type'] === 'benar_salah')
                                        <div class="flex items-center gap-4 pt-1">
                                            <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-sm cursor-pointer hover:bg-slate-100 transition">
                                                <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar"
                                                    @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                                <span class="font-medium text-ink">Benar</span>
                                            </label>
                                            <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-sm cursor-pointer hover:bg-slate-100 transition">
                                                <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah"
                                                    @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                                <span class="font-medium text-ink">Salah</span>
                                            </label>
                                        </div>
                                    @elseif($q['type'] === 'mencocokkan')
                                        @php
                                            $pairLines = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                            $pairs = [];
                                            foreach($pairLines as $pLine) {
                                                if (str_contains($pLine, '=')) {
                                                    [$left, $right] = explode('=', $pLine, 2);
                                                    $pairs[] = ['left' => trim($left), 'right' => trim($right)];
                                                } else {
                                                    $pairs[] = ['left' => $pLine, 'right' => $pLine];
                                                }
                                            }
                                            if (empty($pairs)) {
                                                $pairs = [
                                                    ['left' => 'Binary Tree', 'right' => 'Setiap simpul memiliki maksimal 2 anak'],
                                                    ['left' => 'Linked List', 'right' => 'Struktur data linier terhubung pointer'],
                                                    ['left' => 'Stack', 'right' => 'LIFO (Last In First Out)'],
                                                ];
                                            }
                                            $rightOptions = array_column($pairs, 'right');
                                            $hasImageRight = collect($rightOptions)->contains(function($opt) {
                                                return str_starts_with($opt, 'http://') || str_starts_with($opt, 'https://') || str_starts_with($opt, 'data:image') || str_starts_with($opt, '/');
                                            });
                                        @endphp

                                        <div class="space-y-3 pt-1">
                                            @if($hasImageRight)
                                                <div class="rounded-lg border border-line/60 bg-canvas/30 p-3 space-y-2">
                                                    <p class="text-[11px] font-bold text-muted uppercase tracking-wider">Katalog Gambar Pilihan Pasangan:</p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                        @foreach($rightOptions as $rIdx => $rOpt)
                                                            @php
                                                                $isRImg = str_starts_with($rOpt, 'http://') || str_starts_with($rOpt, 'https://') || str_starts_with($rOpt, 'data:image') || str_starts_with($rOpt, '/');
                                                            @endphp
                                                            <div class="flex flex-col items-center p-2 rounded-lg border border-line/40 bg-white text-center shadow-2xs">
                                                                <span class="text-[11px] font-bold text-brand mb-1">Pilihan {{ chr(65 + $rIdx) }}</span>
                                                                @if($isRImg)
                                                                    <img src="{{ $rOpt }}" alt="Pilihan {{ chr(65 + $rIdx) }}" class="max-h-20 object-contain rounded border border-line/30 bg-slate-50 p-1">
                                                                @else
                                                                    <span class="text-xs text-ink">{{ $rOpt }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <p class="text-xs text-muted">Jodohkan item premis di sebelah kiri dengan pasangan yang tepat:</p>
                                            <div class="space-y-2.5">
                                                @foreach($pairs as $pIdx => $pair)
                                                    @php
                                                        $isLeftImg = str_starts_with($pair['left'], 'http://') || str_starts_with($pair['left'], 'https://') || str_starts_with($pair['left'], 'data:image') || str_starts_with($pair['left'], '/');
                                                    @endphp
                                                    <div class="grid sm:grid-cols-2 gap-3 items-center p-3.5 rounded-lg bg-canvas border border-line/40">
                                                        <div>
                                                            <span class="text-[11px] font-bold text-muted block mb-1">Premis {{ $pIdx + 1 }}:</span>
                                                            @if($isLeftImg)
                                                                <img src="{{ $pair['left'] }}" alt="Gambar premis {{ $pIdx + 1 }}" class="max-h-24 rounded object-contain border border-line/60 bg-white p-1 mb-1">
                                                            @else
                                                                <span class="font-medium text-xs sm:text-sm text-ink">{{ $pair['left'] }}</span>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <span class="text-[11px] font-bold text-muted block mb-1">Pasangan Jawaban:</span>
                                                            <select name="question_answers[{{ $qIdx }}][matching][{{ $pIdx }}]" class="field text-xs py-2">
                                                                <option value="">-- Pilih Pasangan --</option>
                                                                @foreach($rightOptions as $optIdx => $opt)
                                                                    @php
                                                                        $isOptImg = str_starts_with($opt, 'http://') || str_starts_with($opt, 'https://') || str_starts_with($opt, 'data:image') || str_starts_with($opt, '/');
                                                                        $optLabel = $isOptImg ? 'Pilihan ' . chr(65 + $optIdx) : $opt;
                                                                    @endphp
                                                                    <option value="{{ $opt }}" @selected(old("question_answers.$qIdx.matching.$pIdx", $submission['question_answers'][$qIdx]['matching'][$pIdx] ?? '') === $opt)>
                                                                        {{ $optLabel }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($q['type'] === 'coding')
                                        <div class="pt-1 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="form-label text-xs">Jawaban Pemrograman (Kode Solusi)</label>
                                                <a href="{{ route('mahasiswa.assignment.code', $item['id']) }}" class="quiet-link text-xs font-semibold">
                                                    Buka di Code Editor &amp; Lumina AI ↗
                                                </a>
                                            </div>
                                            <textarea rows="7" name="question_answers[{{ $qIdx }}][text]" class="field font-mono text-xs leading-relaxed" placeholder="// Tuliskan implementasi kode solusi Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? ($q['options'] ?? '')) }}</textarea>
                                        </div>
                                    @else
                                        <div class="pt-1">
                                            <label class="form-label text-xs">Jawaban Uraian / Analisis</label>
                                            <textarea rows="4" name="question_answers[{{ $qIdx }}][text]" class="field text-sm" placeholder="Tuliskan uraian dan argumen jawaban Anda...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? '') }}</textarea>
                                        </div>
                                    @endif
                                @endif

                                {{-- If Lecturer Graded This Question --}}
                                @if(isset($itemGrade['points'][$qIdx]))
                                    <div class="rounded-lg bg-canvas p-3 text-xs flex items-center justify-between">
                                        <div>
                                            <span class="font-semibold text-ink">Nilai Dosen: {{ $itemGrade['points'][$qIdx] }} / {{ $q['points'] }} Poin</span>
                                            <span class="ml-2 text-muted">· Capaian {{ $q['cpmk'] }} tercatat</span>
                                        </div>
                                        <span class="font-semibold text-ink">Dinilai</span>
                                    </div>
                                @endif

                                {{-- Question Card Stepper Controls (Kembali / Selanjutnya / Kumpulkan) --}}
                                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line/50 pt-4 mt-6">
                                    <div>
                                        @if($qIdx > 0)
                                            <button type="button" data-quiz-nav-btn="{{ $qIdx - 1 }}" class="button-secondary text-xs py-2 px-3.5 font-semibold flex items-center gap-1.5">
                                                <svg class="h-3.5 w-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                                <span>← Kembali (Soal {{ $qIdx }})</span>
                                            </button>
                                        @else
                                            <span class="text-xs text-muted italic">Awal kuis</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xs text-muted mr-1 hidden sm:inline">Soal {{ $qIdx + 1 }} dari {{ count($item['questions']) }}</span>
                                        @if($qIdx < count($item['questions']) - 1)
                                            <button type="button" data-quiz-nav-btn="{{ $qIdx + 1 }}" class="button-primary text-xs py-2 px-4 font-semibold flex items-center gap-1.5">
                                                <span>Selanjutnya (Soal {{ $qIdx + 2 }}) →</span>
                                            </button>
                                        @elseif(!$isLecturer)
                                            <button type="submit" class="button-primary text-xs py-2 px-4 font-bold bg-emerald-700 hover:bg-emerald-800 text-white flex items-center gap-1.5 shadow-2xs">
                                                <span>Kumpulkan Semua Jawaban ✓</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>
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

                {{-- Module Discussion Section --}}
                <section id="diskusi" class="surface scroll-mt-24 p-6 sm:p-7">
                    <h2 class="section-heading">Diskusi Modul</h2>
                    <p class="mt-1 text-xs text-muted">Percakapan dan tanya-jawab khusus untuk {{ $item['title'] }}.</p>

                    <div class="my-6 space-y-4">
                        @forelse(\App\Support\LearningPreview::discussions($item['id']) as $message)
                            <article class="border-b border-line/50 pb-4 last:border-b-0">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-canvas text-xs font-semibold text-muted">
                                        {{ collect(explode(' ', $message['author']))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('') }}
                                    </span>
                                    <div>
                                        <h3 class="text-xs font-bold text-ink">{{ $message['author'] }}</h3>
                                        <span class="text-[11px] text-muted">{{ $message['time'] }}</span>
                                    </div>
                                </div>
                                <p class="prose-content mt-2 text-xs leading-relaxed">{{ $message['message'] }}</p>
                            </article>
                        @empty
                            <p class="rounded-lg bg-canvas p-4 text-xs text-muted">Belum ada diskusi. Ajukan pertanyaan bila ada materi atau soal yang belum jelas.</p>
                        @endforelse
                    </div>

                    {{-- Form discuss remains standalone but handled properly --}}
                    <div>
                        <label class="form-label text-xs" for="discuss_message">Tulis Pertanyaan atau Tanggapan</label>
                        <textarea maxlength="3000" name="message" id="discuss_message" rows="3" class="field text-xs" placeholder="Bagian mana yang ingin didiskusikan?" form="discuss-form"></textarea>
                        <div class="mt-3 flex items-center justify-between gap-4">
                            <p class="text-[11px] text-muted">Percakapan tersimpan dalam sesi ini.</p>
                            <button type="submit" form="discuss-form" class="button-primary text-xs">Kirim ke Diskusi</button>
                        </div>
                    </div>
                </section>
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
                    {{-- Student Submission Panel (Google Classroom Style) --}}
                    <aside class="rounded-xl bg-white p-5 shadow-sm space-y-4 h-fit xl:sticky xl:top-24 border border-line/60">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-ink">{{ $item['type'] === 'kuis' ? 'Kuis Anda' : 'Tugas Anda' }}</h2>
                            @if($submission)
                                <span class="text-xs font-semibold text-emerald-600">Sudah dikumpulkan</span>
                            @elseif($isPast)
                                <span class="text-xs font-medium text-slate-500">Terlambat</span>
                            @else
                                <span class="text-xs font-semibold text-rose-600">Belum diserahkan</span>
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
                                    @if($item['type'] === 'kuis')
                                        {{ $submission ? 'Kerjakan Ulang Kuis' : 'Kerjakan Kuis' }}
                                    @else
                                        {{ $submission ? 'Kumpulkan Ulang Jawaban' : 'Kumpulkan Tugas' }}
                                    @endif
                                </button>
                            @endif
                        </div>
                    </aside>
                @endif
            @endif
        </div>
    </form>

    {{-- Standalone form for discussions to avoid nested forms --}}
    <form id="discuss-form" method="post" action="{{ route('mahasiswa.course.discuss', [$course['id'], $item['id']]) }}" hidden>
        @csrf
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

    {{-- Quiz Stepper Controller Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const quizContainer = document.querySelector('[data-quiz-container]');
            if (!quizContainer) return;

            const cards = quizContainer.querySelectorAll('[data-quiz-card]');
            const tabs = quizContainer.querySelectorAll('[data-quiz-tab]');
            const currentNumSpan = quizContainer.querySelector('[data-quiz-current-num]');

            const updateAnsweredIndicators = () => {
                cards.forEach((card, idx) => {
                    const tab = tabs[idx];
                    if (!tab) return;
                    
                    const radios = card.querySelectorAll('input[type="radio"]:checked');
                    const checkboxes = card.querySelectorAll('input[type="checkbox"]:checked');
                    const selects = card.querySelectorAll('select');
                    let selectAnswered = selects.length > 0;
                    selects.forEach(s => { if (!s.value) selectAnswered = false; });
                    const textareas = card.querySelectorAll('textarea');
                    let textAnswered = false;
                    textareas.forEach(t => { if (t.value.trim().length > 0) textAnswered = true; });

                    const isAnswered = radios.length > 0 || checkboxes.length > 0 || (selects.length > 0 && selectAnswered) || textAnswered;
                    
                    if (isAnswered && !tab.classList.contains('bg-[#102f50]')) {
                        tab.classList.add('border-emerald-600', 'text-emerald-700', 'bg-emerald-50/60');
                    } else if (!isAnswered && !tab.classList.contains('bg-[#102f50]')) {
                        tab.classList.remove('border-emerald-600', 'text-emerald-700', 'bg-emerald-50/60');
                    }
                });
            };

            const showQuestion = (index) => {
                const targetIdx = Number(index);
                cards.forEach((c, idx) => {
                    if (idx === targetIdx) {
                        c.classList.remove('hidden');
                    } else {
                        c.classList.add('hidden');
                    }
                });

                tabs.forEach((tab, idx) => {
                    if (idx === targetIdx) {
                        tab.className = 'h-8 min-w-8 px-2.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 border bg-[#102f50] text-white border-[#102f50] shadow-xs';
                    } else {
                        tab.className = 'h-8 min-w-8 px-2.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 border bg-white text-ink border-line hover:border-brand';
                    }
                });

                if (currentNumSpan) currentNumSpan.textContent = `${targetIdx + 1}`;
                updateAnsweredIndicators();
            };

            quizContainer.addEventListener('click', (e) => {
                const tabBtn = e.target.closest('[data-quiz-tab]');
                if (tabBtn) {
                    showQuestion(tabBtn.dataset.quizTab);
                    return;
                }

                const navBtn = e.target.closest('[data-quiz-nav-btn]');
                if (navBtn) {
                    showQuestion(navBtn.dataset.quizNavBtn);
                    quizContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }
            });

            quizContainer.addEventListener('change', updateAnsweredIndicators);
            quizContainer.addEventListener('input', updateAnsweredIndicators);
            updateAnsweredIndicators();
        });
    </script>
</div>
@endsection
