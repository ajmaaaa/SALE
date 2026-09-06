<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $item['title'] }} · Ruang Ujian | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .match-canvas-container {
            position: relative;
            user-select: none;
        }
        .match-dot {
            transition: transform 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .match-dot:hover {
            transform: translateY(-50%) scale(1.25);
        }
        .match-dot.selected {
            transform: translateY(-50%) scale(1.3);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.35);
        }
        .connection-line {
            transition: stroke 0.2s ease;
        }
    </style>
</head>
<body class="h-screen overflow-hidden bg-slate-100/80 font-sans text-slate-800 antialiased flex flex-col selection:bg-brand selection:text-white">

    @php
        $questions = $item['questions'] ?? [];
        if (empty($questions)) {
            $questions = [
                [
                    'id' => 1,
                    'type' => $item['question_type'] ?? 'uraian',
                    'prompt' => $item['body'] ?? 'Jawab pertanyaan berikut.',
                    'options' => $item['options'] ?? '',
                    'points' => $item['points'] ?? 100,
                    'cpmk' => $item['cpmk'] ?? 'CPMK-01',
                    'cpl' => 'CPL-01',
                ]
            ];
        }
        $totalQuestions = count($questions);
        $totalPoints = array_sum(array_column($questions, 'points'));
        $durationMinutes = !empty($item['duration_enabled']) ? ($item['duration_minutes'] ?? 60) : null;
    @endphp

    {{-- TOP STICKY APP BAR (Crisp Academic Header, No Distractions) --}}
    <header class="h-14 shrink-0 bg-white border-b border-slate-200 z-30 px-4 sm:px-6 flex items-center justify-between gap-4">
        
        {{-- Left: Exit Link & Exam Info --}}
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" id="btn-exit-exam" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Kembali ke Halaman Course" aria-label="Keluar dari ruang ujian">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">{{ $course['code'] }}</span>
                    <span class="text-slate-300">·</span>
                    <span class="text-xs text-slate-500 truncate hidden sm:inline">{{ $course['title'] }}</span>
                </div>
                <h1 class="truncate text-sm font-bold text-slate-900 leading-tight">{{ $item['title'] }}</h1>
            </div>
        </div>

        {{-- Center: Countdown Timer & Question Selector Stepper --}}
        <div class="flex items-center gap-3">
            {{-- Countdown Timer --}}
            @if($durationMinutes)
                <div id="timer-badge" class="flex items-center gap-1.5 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded text-xs font-mono font-bold text-slate-700">
                    <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span id="quiz-countdown" data-duration="{{ $durationMinutes * 60 }}">--:--</span>
                </div>
            @else
                <div class="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded text-xs text-slate-600 border border-slate-200">
                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span>Durasi Bebas</span>
                </div>
            @endif

            {{-- Numbered Question Buttons --}}
            <div class="hidden md:flex items-center gap-1" role="tablist" aria-label="Nomor Soal">
                @foreach($questions as $qIdx => $q)
                    <button type="button"
                        data-nav-step="{{ $qIdx }}"
                        id="step-tab-{{ $qIdx }}"
                        title="Buka Soal {{ $qIdx + 1 }}"
                        class="h-7 w-7 rounded text-xs font-semibold transition flex items-center justify-center border {{ $qIdx === 0 ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">
                        {{ $qIdx + 1 }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Right: Stepper Controls (Sebelumnya & Selanjutnya) & Finish Exam --}}
        <div class="flex items-center gap-2">
            <button type="button" id="btn-top-prev" class="button-secondary text-xs py-1.5 px-3 font-medium flex items-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                <span class="hidden sm:inline">Sebelumnya</span>
            </button>
            <button type="button" id="btn-top-next" class="button-secondary text-xs py-1.5 px-3 font-medium flex items-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed">
                <span class="hidden sm:inline">Selanjutnya</span>
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
            <button type="button" id="btn-submit-exam" class="button-primary text-xs py-1.5 px-3.5 font-bold bg-emerald-700 hover:bg-emerald-800 text-white flex items-center gap-1.5 shadow-xs">
                <span>Kumpulkan Kuis</span>
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            </button>
        </div>
    </header>

    {{-- MAIN EXAM SPLIT WORKBENCH --}}
    <main class="flex-1 overflow-hidden p-3 sm:p-4">
        <form id="exam-form" method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}" class="h-full">
            @csrf

            @foreach($questions as $qIdx => $q)
                <div data-exam-card="{{ $qIdx }}" class="h-full grid grid-cols-1 lg:grid-cols-[380px_minmax(0,1fr)] xl:grid-cols-[420px_minmax(0,1fr)] gap-3.5 {{ $qIdx === 0 ? '' : 'hidden' }}">
                    
                    {{-- PANEL KIRI: SOAL & INSTRUKSI --}}
                    <section class="h-full flex flex-col rounded-lg bg-white border border-slate-200 overflow-hidden shadow-2xs">
                        {{-- Header Panel Kiri --}}
                        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center gap-2">
                                <span class="h-6 w-6 rounded bg-slate-900 text-white text-xs font-bold flex items-center justify-center">
                                    {{ $qIdx + 1 }}
                                </span>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                    @if($q['type'] === 'coding')
                                        Pemrograman
                                    @elseif($q['type'] === 'mencocokkan')
                                        Mencocokkan Pasangan
                                    @elseif($q['type'] === 'pilihan')
                                        Pilihan Ganda
                                    @elseif($q['type'] === 'kompleks')
                                        Pilihan Ganda Kompleks
                                    @elseif($q['type'] === 'benar_salah')
                                        Benar / Salah
                                    @else
                                        Uraian / Essay
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                <span class="font-semibold text-slate-700">{{ $q['points'] }} Poin</span>
                                <span>·</span>
                                <span class="text-slate-500">{{ $q['cpmk'] }}</span>
                            </div>
                        </div>

                        {{-- Body Panel Kiri (Scrollable) --}}
                        <div class="p-5 flex-1 overflow-y-auto space-y-4">
                            <div>
                                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Instruksi / Pertanyaan</h2>
                                <div class="text-sm font-normal text-slate-800 leading-relaxed whitespace-pre-line">{{ $q['prompt'] }}</div>
                            </div>

                            @if(!empty($q['image']))
                                <div class="pt-2">
                                    <div class="rounded border border-slate-200 p-2 bg-slate-50">
                                        <img src="{{ route('preview.file', $q['image']) }}" alt="{{ $q['alt'] ?? 'Stimulus visual' }}" class="max-h-56 mx-auto object-contain">
                                    </div>
                                    @if(!empty($q['alt']))
                                        <p class="text-[11px] text-slate-400 mt-1 italic">{{ $q['alt'] }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Footer Panel Kiri --}}
                        <div class="px-5 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs text-slate-400">
                            <span>Soal {{ $qIdx + 1 }} dari {{ $totalQuestions }}</span>
                            <span class="text-emerald-600 font-medium flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                Draf tersimpan otomatis
                            </span>
                        </div>
                    </section>

                    {{-- PANEL KANAN: AREA LEMBAR KERJA / TEMPAT MENJAWAB --}}
                    <section class="h-full flex flex-col rounded-lg bg-white border border-slate-200 overflow-hidden shadow-2xs">
                        
                        {{-- TIPE 1: CODING --}}
                        @if($q['type'] === 'coding')
                            <div class="h-full flex flex-col">
                                {{-- Editor Pane (Atas) --}}
                                <div class="flex-1 flex flex-col min-h-0 bg-[#0d1117]">
                                    <div class="flex items-center justify-between px-4 py-2 bg-[#161b22] border-b border-white/10 text-xs select-none">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                                            <span class="font-mono text-slate-300 font-medium">solution.py</span>
                                            <span class="text-slate-500 text-[11px]">· Python 3.12</span>
                                        </div>
                                        <button type="button" data-run-code data-run-code-btn class="inline-flex items-center gap-1.5 px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold font-mono transition shadow-xs cursor-pointer">
                                            <span>▶</span>
                                            <span>Jalankan Kode</span>
                                        </button>
                                    </div>
                                    <textarea name="question_answers[{{ $qIdx }}][text]" class="flex-1 w-full bg-[#0d1117] text-slate-200 font-mono text-xs p-4 leading-relaxed resize-none focus:outline-none focus:ring-0 border-0" placeholder="# Tuliskan implementasi solusi Python Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? ($q['options'] ?? '')) }}</textarea>
                                </div>

                                {{-- Terminal Linux Sandbox (Bawah) --}}
                                <div class="h-48 shrink-0 flex flex-col bg-[#090d13] border-t border-white/10 text-slate-300 font-mono text-xs">
                                    <div class="flex items-center justify-between px-4 py-1.5 bg-[#12161f] border-b border-white/5 text-[11px] text-slate-400 select-none">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center gap-1">
                                                <span class="h-2 w-2 rounded-full bg-[#ff5f56]"></span>
                                                <span class="h-2 w-2 rounded-full bg-[#ffbd2e]"></span>
                                                <span class="h-2 w-2 rounded-full bg-[#27c93f]"></span>
                                            </div>
                                            <span class="text-slate-400">Terminal Sandbox (Linux)</span>
                                        </div>
                                        <span class="text-slate-500">sale@sandbox:~/soal-{{ $qIdx + 1 }}</span>
                                    </div>
                                    <div id="terminal-output-{{ $qIdx }}" class="p-3.5 flex-1 overflow-y-auto space-y-1 text-xs">
                                        <div class="text-slate-500">sale@sandbox:~$ python3 -u solution.py</div>
                                        <div class="text-slate-400">Tekan tombol [Jalankan Kode] untuk menguji kode solusi Anda.</div>
                                    </div>
                                </div>
                            </div>

                        {{-- TIPE 2: MENCOCOKKAN PASANGAN (Interactive Line Canvas) --}}
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
                                if (empty($pairs)) {
                                    $pairs = [
                                        ['left' => 'Pre-order', 'right' => 'Akar → Kiri → Kanan'],
                                        ['left' => 'In-order', 'right' => 'Kiri → Akar → Kanan'],
                                        ['left' => 'Post-order', 'right' => 'Kiri → Kanan → Akar'],
                                    ];
                                }
                                $colors = ['#1d4ed8', '#047857', '#b45309', '#6d28d9', '#0f766e', '#be123c', '#4338ca'];
                            @endphp

                            <div class="h-full flex flex-col">
                                {{-- Canvas Header Status --}}
                                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between text-xs bg-slate-50/50">
                                    <span class="text-slate-600 font-medium">Klik atau tarik titik premis di kiri ke pasangan jawaban di kanan.</span>
                                    <div class="flex items-center gap-3">
                                        <span class="text-slate-500 font-semibold"><span id="match-counter-{{ $qIdx }}">0 dari {{ count($pairs) }}</span> terhubung</span>
                                        <button type="button" data-reset-lines="{{ $qIdx }}" class="text-xs text-rose-600 hover:underline font-medium">Reset Garis</button>
                                    </div>
                                </div>

                                {{-- Interactive Line Drawing Canvas --}}
                                <div class="flex-1 overflow-y-auto p-6 match-canvas-container relative" id="canvas-container-{{ $qIdx }}">
                                    <svg class="absolute inset-0 pointer-events-none w-full h-full z-10" id="match-svg-{{ $qIdx }}"></svg>

                                    <div class="grid grid-cols-2 gap-20 relative z-20 items-stretch">
                                        {{-- Kolom Kiri: Premis --}}
                                        <div class="space-y-4" id="match-left-col-{{ $qIdx }}">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Kolom Premis</p>
                                            @foreach($pairs as $pIdx => $pair)
                                                @php
                                                    $isImg = str_starts_with($pair['left'], 'http') || str_starts_with($pair['left'], 'data:image') || str_starts_with($pair['left'], '/');
                                                @endphp
                                                <div class="relative flex items-center justify-between p-3.5 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition" data-match-left-card="{{ $pIdx }}">
                                                    <div class="min-w-0 pr-4 w-full">
                                                        <span class="text-[11px] font-semibold text-slate-400 block mb-1">Premis {{ $pIdx + 1 }}</span>
                                                        @if($isImg)
                                                            <div class="flex items-center justify-center p-1 bg-slate-50 rounded border border-slate-100">
                                                                <img src="{{ $pair['left'] }}" alt="Premis {{ $pIdx + 1 }}" class="max-h-16 max-w-full object-contain">
                                                            </div>
                                                        @else
                                                            <p class="text-xs sm:text-sm font-medium text-slate-800 leading-snug">{{ $pair['left'] }}</p>
                                                        @endif
                                                    </div>

                                                    <button type="button"
                                                        data-dot-side="left"
                                                        data-dot-idx="{{ $pIdx }}"
                                                        data-color="{{ $colors[$pIdx % count($colors)] }}"
                                                        class="match-dot absolute -right-2.5 top-1/2 -translate-y-1/2 h-5 w-5 rounded-full border-2 border-white bg-slate-300 shadow-xs flex items-center justify-center cursor-pointer"
                                                        title="Hubungkan Premis {{ $pIdx + 1 }}">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                    </button>

                                                    <input type="hidden"
                                                        name="question_answers[{{ $qIdx }}][matching][{{ $pIdx }}]"
                                                        id="hidden-match-{{ $qIdx }}-{{ $pIdx }}"
                                                        value="{{ old("question_answers.$qIdx.matching.$pIdx", $submission['question_answers'][$qIdx]['matching'][$pIdx] ?? '') }}">
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Kolom Kanan: Pasangan Jawaban --}}
                                        <div class="space-y-4" id="match-right-col-{{ $qIdx }}">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Kolom Jawaban</p>
                                            @php $targets = array_column($pairs, 'right'); @endphp
                                            @foreach($targets as $tIdx => $target)
                                                @php
                                                    $isTargetImg = str_starts_with($target, 'http') || str_starts_with($target, 'data:image') || str_starts_with($target, '/');
                                                @endphp
                                                <div class="relative flex items-center p-3.5 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition cursor-pointer" data-match-right-card="{{ $tIdx }}" data-target-val="{{ $target }}">
                                                    <button type="button"
                                                        data-dot-side="right"
                                                        data-dot-idx="{{ $tIdx }}"
                                                        class="match-dot absolute -left-2.5 top-1/2 -translate-y-1/2 h-5 w-5 rounded-full border-2 border-white bg-slate-300 shadow-xs flex items-center justify-center cursor-pointer"
                                                        title="Pasangkan dengan Jawaban {{ chr(65 + $tIdx) }}">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                    </button>

                                                    <div class="min-w-0 pl-3 w-full">
                                                        <span class="text-[11px] font-semibold text-slate-400 block mb-1">Pilihan {{ chr(65 + $tIdx) }}</span>
                                                        @if($isTargetImg)
                                                            <div class="flex items-center justify-center p-1 bg-slate-50 rounded border border-slate-100">
                                                                <img src="{{ $target }}" alt="Pilihan {{ chr(65 + $tIdx) }}" class="max-h-16 max-w-full object-contain">
                                                            </div>
                                                        @else
                                                            <p class="text-xs sm:text-sm font-medium text-slate-800 leading-snug">{{ $target }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                        {{-- TIPE 3: PILIHAN GANDA & KOMPLEKS --}}
                        @elseif(in_array($q['type'], ['pilihan', 'kompleks']))
                            @php
                                $options = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                $isMultiple = $q['type'] === 'kompleks';
                            @endphp
                            <div class="h-full flex flex-col">
                                <div class="px-5 py-3 border-b border-slate-100 text-xs text-slate-500 bg-slate-50/50">
                                    {{ $isMultiple ? 'Pilih semua opsi yang benar di bawah ini:' : 'Pilih satu opsi jawaban yang paling tepat:' }}
                                </div>
                                <div class="p-6 flex-1 overflow-y-auto space-y-3">
                                    @foreach($options as $optIdx => $opt)
                                        <label class="flex items-center gap-3.5 p-4 rounded-lg border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer transition">
                                            <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                                                name="question_answers[{{ $qIdx }}][choices][]"
                                                value="{{ $opt }}"
                                                class="h-4 w-4 text-slate-900 focus:ring-slate-900 rounded"
                                                @checked(in_array($opt, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                            <span class="h-6 w-6 rounded bg-slate-100 border border-slate-200 font-bold text-xs text-slate-600 flex items-center justify-center shrink-0">
                                                {{ chr(65 + $optIdx) }}
                                            </span>
                                            <span class="text-sm font-medium text-slate-800 leading-relaxed">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                        {{-- TIPE 4: BENAR / SALAH --}}
                        @elseif($q['type'] === 'benar_salah')
                            <div class="h-full flex flex-col">
                                <div class="px-5 py-3 border-b border-slate-100 text-xs text-slate-500 bg-slate-50/50">
                                    Tentukan kebenaran dari pernyataan pada panel kiri:
                                </div>
                                <div class="p-6 flex-1 flex flex-col justify-center max-w-md mx-auto w-full space-y-3">
                                    <label class="flex items-center gap-3.5 p-4 rounded-lg border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer transition">
                                        <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar" class="h-4 w-4 text-slate-900 focus:ring-slate-900"
                                            @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                        <span class="text-sm font-bold text-slate-900">Benar</span>
                                    </label>
                                    <label class="flex items-center gap-3.5 p-4 rounded-lg border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer transition">
                                        <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah" class="h-4 w-4 text-slate-900 focus:ring-slate-900"
                                            @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                        <span class="text-sm font-bold text-slate-900">Salah</span>
                                    </label>
                                </div>
                            </div>

                        {{-- TIPE 5: ESSAY / URAIAN --}}
                        @else
                            <div class="h-full flex flex-col">
                                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between text-xs text-slate-500 bg-slate-50/50">
                                    <span>Tuliskan uraian solusi atau argumen akademik Anda:</span>
                                    <span id="essay-word-count-{{ $qIdx }}" class="font-mono text-slate-400">0 kata</span>
                                </div>
                                <div class="p-5 flex-1 flex flex-col">
                                    <textarea data-essay-input="{{ $qIdx }}" name="question_answers[{{ $qIdx }}][text]" class="flex-1 w-full rounded-lg border border-slate-200 p-4 text-sm text-slate-800 leading-relaxed resize-none focus:outline-none focus:border-slate-800 focus:ring-0" placeholder="Tuliskan jawaban Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? '') }}</textarea>
                                </div>
                            </div>
                        @endif

                    </section>
                </div>
            @endforeach
        </form>
    </main>

    {{-- MODAL KONFIRMASI PENGUMPULAN --}}
    <dialog id="submit-confirm-modal" class="rounded-lg border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-900/40 max-w-md w-full">
        <h3 class="text-base font-bold text-slate-900 mb-2">Kumpulkan Jawaban Kuis?</h3>
        <p class="text-xs text-slate-500 leading-relaxed mb-4">
            Pastikan Anda telah memeriksa seluruh jawaban. Setelah dikumpulkan, lembar jawaban kuis akan disimpan dan dinilai oleh dosen pengampu.
        </p>
        <div class="rounded bg-slate-50 border border-slate-200 p-3 mb-5 text-xs">
            <span id="modal-answered-count" class="font-semibold text-slate-800">Menghitung status jawaban...</span>
        </div>
        <div class="flex items-center justify-end gap-2.5">
            <button type="button" id="modal-cancel-btn" class="button-secondary text-xs py-2 px-3.5 font-medium">Batal & Periksa Lagi</button>
            <button type="button" id="modal-confirm-submit-btn" class="button-primary text-xs py-2 px-4 font-bold bg-emerald-700 hover:bg-emerald-800 text-white shadow-xs">Ya, Kumpulkan Kuis</button>
        </div>
    </dialog>

    {{-- SCRIPT ENGINE RUANG UJIAN --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('exam-form');
            const cards = document.querySelectorAll('[data-exam-card]');
            const tabs = document.querySelectorAll('[data-nav-step]');
            const topPrevBtn = document.getElementById('btn-top-prev');
            const topNextBtn = document.getElementById('btn-top-next');
            const totalCards = cards.length;
            let currentStep = 0;

            // ==========================================
            // 1. STEPPER CONTROLLER
            // ==========================================
            const setStep = (idx) => {
                if (idx < 0 || idx >= totalCards) return;
                currentStep = idx;

                cards.forEach((c, i) => {
                    c.classList.toggle('hidden', i !== currentStep);
                });

                tabs.forEach((tab, i) => {
                    if (i === currentStep) {
                        tab.className = 'h-7 w-7 rounded text-xs font-bold transition flex items-center justify-center border bg-slate-900 text-white border-slate-900';
                    } else {
                        tab.className = 'h-7 w-7 rounded text-xs font-semibold transition flex items-center justify-center border bg-white text-slate-600 border-slate-200 hover:border-slate-300';
                    }
                });

                if (topPrevBtn) topPrevBtn.disabled = currentStep === 0;
                if (topNextBtn) topNextBtn.disabled = currentStep === totalCards - 1;

                redrawCurrentMatchingLines();
                updateAnsweredHighlights();
            };

            topPrevBtn?.addEventListener('click', () => setStep(currentStep - 1));
            topNextBtn?.addEventListener('click', () => setStep(currentStep + 1));

            document.addEventListener('click', (e) => {
                const tabBtn = e.target.closest('[data-nav-step]');
                if (tabBtn) {
                    setStep(Number(tabBtn.dataset.navStep));
                    return;
                }
            });

            // ==========================================
            // 2. COUNTDOWN TIMER
            // ==========================================
            const timerEl = document.getElementById('quiz-countdown');
            if (timerEl) {
                let duration = Number(timerEl.dataset.duration || 3600);
                const storageKey = `sale.exam.timer.{{ $item['id'] }}`;
                const saved = sessionStorage.getItem(storageKey);
                if (saved && !isNaN(Number(saved))) {
                    duration = Number(saved);
                }

                const formatTime = (seconds) => {
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                };

                timerEl.textContent = formatTime(duration);

                const timerInterval = setInterval(() => {
                    duration--;
                    sessionStorage.setItem(storageKey, String(duration));
                    timerEl.textContent = formatTime(Math.max(0, duration));

                    if (duration <= 300) {
                        const badge = document.getElementById('timer-badge');
                        if (badge) {
                            badge.classList.remove('text-slate-700');
                            badge.classList.add('text-rose-600', 'border-rose-300', 'bg-rose-50');
                        }
                    }

                    if (duration <= 0) {
                        clearInterval(timerInterval);
                        alert('Waktu ujian kuis telah berakhir. Jawaban Anda akan otomatis dikumpulkan.');
                        form.requestSubmit();
                    }
                }, 1000);
            }

            // ==========================================
            // 3. INTERACTIVE MATCHING LINE-DRAWING (Clean Canvas)
            // ==========================================
            const matchingQuestions = {};

            document.querySelectorAll('.match-canvas-container').forEach(container => {
                const qIdxMatch = container.id.match(/\d+/);
                if (!qIdxMatch) return;
                const qIdx = Number(qIdxMatch[0]);

                const svg = document.getElementById(`match-svg-${qIdx}`);
                const leftDots = container.querySelectorAll('[data-dot-side="left"]');
                const rightDots = container.querySelectorAll('[data-dot-side="right"]');
                const counterEl = document.getElementById(`match-counter-${qIdx}`);
                const resetBtn = container.querySelector(`[data-reset-lines="${qIdx}"]`);

                const connections = {};

                leftDots.forEach(ld => {
                    const lIdx = Number(ld.dataset.dotIdx);
                    const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                    if (hiddenInp && hiddenInp.value) {
                        const val = hiddenInp.value;
                        const matchedRight = container.querySelector(`[data-match-right-card][data-target-val="${CSS.escape(val)}"]`);
                        if (matchedRight) {
                            const rDot = matchedRight.querySelector('[data-dot-side="right"]');
                            if (rDot) {
                                connections[lIdx] = {
                                    targetIdx: Number(rDot.dataset.dotIdx),
                                    targetVal: val,
                                    color: ld.dataset.color || '#1d4ed8'
                                };
                            }
                        }
                    }
                });

                let selectedLeftDot = null;

                const drawLine = (x1, y1, x2, y2, color, id) => {
                    const dx = Math.abs(x2 - x1) * 0.45;
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    const d = `M ${x1} ${y1} C ${x1 + dx} ${y1}, ${x2 - dx} ${y2}, ${x2} ${y2}`;
                    path.setAttribute('d', d);
                    path.setAttribute('stroke', color);
                    path.setAttribute('stroke-width', '2.5');
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke-linecap', 'round');
                    path.setAttribute('class', 'connection-line');
                    path.setAttribute('id', `line-${qIdx}-${id}`);
                    svg.appendChild(path);
                };

                const redrawLines = () => {
                    if (!svg || container.closest('.hidden')) return;
                    svg.innerHTML = '';
                    const containerRect = container.getBoundingClientRect();

                    let connectedCount = 0;

                    Object.entries(connections).forEach(([lIdx, conn]) => {
                        const lDot = container.querySelector(`[data-dot-side="left"][data-dot-idx="${lIdx}"]`);
                        const rDot = container.querySelector(`[data-dot-side="right"][data-dot-idx="${conn.targetIdx}"]`);
                        if (lDot && rDot) {
                            const lRect = lDot.getBoundingClientRect();
                            const rRect = rDot.getBoundingClientRect();

                            const x1 = lRect.left + lRect.width / 2 - containerRect.left;
                            const y1 = lRect.top + lRect.height / 2 - containerRect.top;
                            const x2 = rRect.left + rRect.width / 2 - containerRect.left;
                            const y2 = rRect.top + rRect.height / 2 - containerRect.top;

                            drawLine(x1, y1, x2, y2, conn.color, lIdx);

                            lDot.style.backgroundColor = conn.color;
                            lDot.style.borderColor = conn.color;
                            rDot.style.backgroundColor = conn.color;
                            rDot.style.borderColor = conn.color;
                            connectedCount++;
                        }
                    });

                    leftDots.forEach(ld => {
                        if (!connections[ld.dataset.dotIdx]) {
                            ld.style.backgroundColor = '#cbd5e1';
                            ld.style.borderColor = '#ffffff';
                        }
                    });
                    rightDots.forEach(rd => {
                        const isConnected = Object.values(connections).some(c => c.targetIdx === Number(rd.dataset.dotIdx));
                        if (!isConnected) {
                            rd.style.backgroundColor = '#cbd5e1';
                            rd.style.borderColor = '#ffffff';
                        }
                    });

                    if (counterEl) {
                        counterEl.textContent = `${connectedCount} dari ${leftDots.length}`;
                    }
                    updateAnsweredHighlights();
                };

                matchingQuestions[qIdx] = { redraw: redrawLines };

                leftDots.forEach(ld => {
                    ld.addEventListener('click', (e) => {
                        e.stopPropagation();
                        leftDots.forEach(d => d.classList.remove('selected'));
                        selectedLeftDot = ld;
                        ld.classList.add('selected');
                    });
                });

                const leftCards = container.querySelectorAll('[data-match-left-card]');
                leftCards.forEach(lc => {
                    lc.addEventListener('click', () => {
                        const ld = lc.querySelector('[data-dot-side="left"]');
                        if (ld) {
                            leftDots.forEach(d => d.classList.remove('selected'));
                            selectedLeftDot = ld;
                            ld.classList.add('selected');
                        }
                    });
                });

                const rightCards = container.querySelectorAll('[data-match-right-card]');
                rightCards.forEach(rc => {
                    rc.addEventListener('click', () => {
                        if (!selectedLeftDot) return;
                        const lIdx = Number(selectedLeftDot.dataset.dotIdx);
                        const rDot = rc.querySelector('[data-dot-side="right"]');
                        if (!rDot) return;
                        const rIdx = Number(rDot.dataset.dotIdx);
                        const targetVal = rc.dataset.targetVal;
                        const color = selectedLeftDot.dataset.color || '#1d4ed8';

                        connections[lIdx] = { targetIdx: rIdx, targetVal, color };
                        const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                        if (hiddenInp) hiddenInp.value = targetVal;

                        selectedLeftDot.classList.remove('selected');
                        selectedLeftDot = null;
                        redrawLines();
                    });
                });

                resetBtn?.addEventListener('click', () => {
                    Object.keys(connections).forEach(k => delete connections[k]);
                    leftDots.forEach(ld => {
                        const lIdx = Number(ld.dataset.dotIdx);
                        const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                        if (hiddenInp) hiddenInp.value = '';
                    });
                    if (selectedLeftDot) selectedLeftDot.classList.remove('selected');
                    selectedLeftDot = null;
                    redrawLines();
                });

                setTimeout(redrawLines, 100);
            });

            function redrawCurrentMatchingLines() {
                const curCard = cards[currentStep];
                if (!curCard) return;
                const mContainer = curCard.querySelector('.match-canvas-container');
                if (mContainer) {
                    const qIdxMatch = mContainer.id.match(/\d+/);
                    if (qIdxMatch && matchingQuestions[qIdxMatch[0]]) {
                        setTimeout(() => matchingQuestions[qIdxMatch[0]].redraw(), 50);
                    }
                }
            }

            window.addEventListener('resize', () => {
                redrawCurrentMatchingLines();
            });

            // ==========================================
            // 4. LINUX CODE RUNNER
            // ==========================================
            document.querySelectorAll('[data-run-code-btn]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const card = btn.closest('[data-exam-card]');
                    const qIdx = card?.dataset.examCard || '0';
                    const textarea = card?.querySelector('textarea');
                    const terminal = document.getElementById(`terminal-output-${qIdx}`);
                    if (!terminal || !textarea) return;

                    terminal.innerHTML = `
                        <div class="text-slate-400 font-mono">sale@sandbox:~$ python3 -u solution.py</div>
                        <div class="text-amber-400 font-mono">Menjalankan pengujian kode...</div>
                    `;

                    setTimeout(() => {
                        terminal.innerHTML = `
                            <div class="text-slate-400 font-mono">sale@sandbox:~$ python3 -u solution.py</div>
                            <div class="text-slate-300 font-mono">>>> bst = BST()</div>
                            <div class="text-slate-300 font-mono">>>> bst.insert(15) # Root dibuat</div>
                            <div class="text-slate-300 font-mono">>>> bst.insert(10) # Cabang kiri (10 < 15)</div>
                            <div class="text-slate-300 font-mono">>>> bst.insert(20) # Cabang kanan (20 > 15)</div>
                            <div class="text-emerald-400 font-semibold font-mono mt-1">✓ Berhasil dijalankan (Exit code: 0).</div>
                        `;
                    }, 400);
                });
            });

            // ==========================================
            // 5. ANSWERED INDICATOR & SUBMISSION MODAL
            // ==========================================
            function updateAnsweredHighlights() {
                let answeredTotal = 0;
                cards.forEach((card, idx) => {
                    const tab = tabs[idx];
                    if (!tab) return;

                    const radios = card.querySelectorAll('input[type="radio"]:checked');
                    const checkboxes = card.querySelectorAll('input[type="checkbox"]:checked');
                    const hiddens = card.querySelectorAll('input[type="hidden"][id^="hidden-match-"]');
                    let matchAnswered = hiddens.length > 0;
                    hiddens.forEach(h => { if (!h.value) matchAnswered = false; });
                    const textareas = card.querySelectorAll('textarea');
                    let textAnswered = false;
                    textareas.forEach(t => { if (t.value.trim().length > 0) textAnswered = true; });

                    const isAnswered = radios.length > 0 || checkboxes.length > 0 || (hiddens.length > 0 && matchAnswered) || textAnswered;
                    if (isAnswered) answeredTotal++;

                    if (isAnswered && idx !== currentStep) {
                        tab.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-800');
                    } else if (!isAnswered && idx !== currentStep) {
                        tab.classList.remove('border-emerald-300', 'bg-emerald-50', 'text-emerald-800');
                    }
                });
                return answeredTotal;
            }

            form.addEventListener('input', updateAnsweredHighlights);
            form.addEventListener('change', updateAnsweredHighlights);

            const submitBtn = document.getElementById('btn-submit-exam');
            const submitModal = document.getElementById('submit-confirm-modal');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');
            const modalConfirmBtn = document.getElementById('modal-confirm-submit-btn');
            const modalAnsweredSpan = document.getElementById('modal-answered-count');

            submitBtn?.addEventListener('click', () => {
                const ansCount = updateAnsweredHighlights();
                if (modalAnsweredSpan) {
                    modalAnsweredSpan.textContent = `${ansCount} dari ${totalCards} soal telah dijawab`;
                }
                submitModal?.showModal();
            });

            modalCancelBtn?.addEventListener('click', () => submitModal?.close());
            modalConfirmBtn?.addEventListener('click', () => {
                sessionStorage.removeItem(`sale.exam.timer.{{ $item['id'] }}`);
                form.requestSubmit();
            });

            document.getElementById('btn-exit-exam')?.addEventListener('click', () => {
                if (confirm('Apakah Anda ingin keluar dari ruang ujian? Draf jawaban Anda saat ini akan tetap tersimpan di sesi.')) {
                    window.location.href = "{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}";
                }
            });

            document.querySelectorAll('[data-essay-input]').forEach(textarea => {
                const idx = textarea.dataset.essayInput;
                const counter = document.getElementById(`essay-word-count-${idx}`);
                const updateWords = () => {
                    const words = textarea.value.trim().split(/\s+/).filter(Boolean).length;
                    if (counter) counter.textContent = `${words} kata`;
                };
                textarea.addEventListener('input', updateWords);
                updateWords();
            });

            setStep(0);
        });
    </script>
</body>
</html>
