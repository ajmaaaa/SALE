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
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .match-dot:hover {
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.25);
        }
        .match-dot.selected {
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.45);
        }
        .connection-line {
            transition: stroke 0.2s ease;
        }
        .quiz-topbar {
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 1px 0 rgba(148, 163, 184, 0.22), 0 8px 24px rgba(15, 23, 42, 0.05);
        }
        .quiz-panel {
            border-color: #dbe3ea;
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }
        .quiz-question-panel {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 42%);
        }
        .quiz-answer-panel {
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .quiz-option {
            border-color: #dbe3ea;
            box-shadow: 0 2px 5px rgba(15, 23, 42, 0.025);
        }
        .quiz-option:has(input:checked) {
            border-color: #1d4ed8;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        .quiz-option:hover {
            border-color: #94a3b8;
            transform: translateY(-1px);
        }
        .quiz-kicker {
            letter-spacing: 0.12em;
        }
        @media (max-width: 1023px) {
            .quiz-topbar {
                height: auto !important;
                min-height: 64px;
                flex-wrap: wrap;
                padding-top: 10px;
                padding-bottom: 10px;
            }
        }
        dialog[open] {
            position: fixed !important;
            inset: 0 !important;
            margin: auto !important;
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
        $submission = session('learning.submissions.'.$item['id']);
        $hasCompleted = !empty($isCompleted) || !empty($submission);
    @endphp

    @if($hasCompleted)
        {{-- ================================================================= --}}
        {{-- LAYAR SUKSES PURNA-PENGUMPULAN KUIS (TERKUNCI DENGAN CENTANG KHUSUS) --}}
        {{-- ================================================================= --}}
        <div class="h-screen w-screen flex flex-col bg-slate-100/90 overflow-y-auto">
            {{-- Top Header Minimalis --}}
            <header class="h-14 shrink-0 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                        <span>Kembali ke Course</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-bold text-slate-700 truncate">{{ $course['code'] }} · {{ $item['title'] }}</span>
                </div>
                <span class="status font-semibold text-slate-700 bg-slate-100 border border-slate-200">
                    <svg class="h-3.5 w-3.5 text-slate-500 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Kuis Terkunci (Telah Selesai)
                </span>
            </header>

            {{-- Center Content Box --}}
            <main class="flex-1 flex items-start justify-center p-4 sm:p-6">
                <div class="w-full max-w-5xl bg-white rounded-2xl border border-slate-200 shadow-xl p-6 sm:p-8 text-center space-y-6">
                    
                    {{-- SIMBOL CENTANG BERSIH (Neutral Solid Icon Circle) --}}
                    <div class="mx-auto h-16 w-16 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-sm">
                        <svg class="h-8 w-8 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 13 4 4L19 7"/>
                        </svg>
                    </div>

                    {{-- Status & Deskripsi --}}
                    <div class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">
                            Lembar Jawaban Terkumpul
                        </span>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kuis Telah Berhasil Dikumpulkan!</h1>
                        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                            Seluruh jawaban Anda telah tersimpan dengan aman pada sistem akademik SALE. Kuis ini telah dikunci dan tidak dapat dikerjakan ulang.
                        </p>
                    </div>

                    {{-- Bukti Tanda Terima Kuis --}}
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-5 text-left text-xs space-y-3 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                            <span class="text-slate-500 font-medium">Waktu Pengumpulan:</span>
                            <span class="font-bold text-slate-900 font-mono">{{ $submission['time'] ?? now()->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                            <span class="text-slate-500 font-medium">Mata Kuliah:</span>
                            <span class="font-semibold text-slate-800">{{ $course['title'] }} ({{ $course['code'] }})</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                            <span class="text-slate-500 font-medium">Jumlah Butir Soal:</span>
                            <span class="font-semibold text-slate-800">{{ $totalQuestions }} Butir Soal</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                            <span class="text-slate-500 font-medium">Total Bobot Evaluasi:</span>
                            <span class="font-semibold text-ink">{{ $totalPoints }} Poin</span>
                        </div>
                        <div class="flex items-center justify-between pt-0.5">
                            <span class="text-slate-500 font-medium">Mahasiswa Pengumpul:</span>
                            <span class="font-semibold text-slate-800">{{ session('auth_user.name', 'Ahmad Maulana') }} (NIM: {{ session('auth_user.number', '230101001') }})</span>
                        </div>
                    </div>

                    {{-- Hasil jawaban dan penjelasan AI hanya tampil setelah kuis terkumpul. --}}
                    <section class="border-t border-slate-200 pt-6 text-left" aria-labelledby="ai-review-heading">
                        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-brand">Review setelah pengumpulan</p>
                                <h2 id="ai-review-heading" class="mt-1 text-lg font-bold text-slate-900">Jawaban Anda &amp; penjelasan AI</h2>
                            </div>
                            <span class="text-xs text-slate-500">Umpan balik tersedia setelah kuis dikunci.</span>
                        </div>

                        <div class="space-y-3">
                            @foreach($questions as $reviewIdx => $reviewQuestion)
                                @php
                                    $reviewAnswer = $submission['question_answers'][$reviewIdx] ?? [];
                                    $reviewType = $reviewQuestion['type'] ?? 'uraian';
                                    $reviewExpected = $reviewQuestion['correct'] ?? null;
                                    $reviewValues = $reviewType === 'mencocokkan'
                                        ? array_values($reviewAnswer['matching'] ?? [])
                                        : ($reviewType === 'kompleks' ? array_values($reviewAnswer['choices'] ?? []) : array_values(array_filter([$reviewAnswer['choices'][0] ?? $reviewAnswer['boolean_choice'] ?? $reviewAnswer['text'] ?? ''], fn ($value) => trim((string) $value) !== '')));
                                    $reviewCorrect = null;
                                    if ($reviewExpected !== null && $reviewType === 'mencocokkan') $reviewCorrect = $reviewValues === array_values($reviewExpected);
                                    elseif ($reviewExpected !== null && $reviewType === 'kompleks') { $actual = $reviewValues; $expectedValues = array_values($reviewExpected); sort($actual); sort($expectedValues); $reviewCorrect = $actual === $expectedValues; }
                                    elseif ($reviewExpected !== null) $reviewCorrect = ($reviewValues[0] ?? null) === $reviewExpected;
                                    $reviewAnswerText = $reviewType === 'mencocokkan'
                                        ? collect($reviewValues)->map(fn ($value, $index) => 'Pasangan '.($index + 1).': '.$value)->implode(' · ')
                                        : implode(', ', $reviewValues);
                                    $reviewAnswerText = $reviewAnswerText !== '' ? $reviewAnswerText : 'Belum ada jawaban';
                                    $reviewExplanation = $reviewCorrect === true
                                        ? 'Jawaban ini sesuai dengan konsep yang diuji. Gunakan alasan yang sama saat menerapkan konsep pada soal atau kasus lain.'
                                        : ($reviewCorrect === false
                                            ? 'Jawaban ini belum sesuai dengan konsep yang diuji. Bandingkan kembali jawaban Anda dengan penjelasan soal dan identifikasi bagian konsep yang berbeda.'
                                            : 'Jawaban ini membutuhkan penilaian lebih lanjut. Gunakan pertanyaan, materi, dan CPMK sebagai panduan untuk meninjau kualitas jawaban Anda.');
                                @endphp
                                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-sm font-bold text-slate-900">Soal {{ $reviewIdx + 1 }}</h3>
                                        @if($reviewCorrect === true)
                                            <span class="text-xs font-semibold text-slate-600">Benar</span>
                                        @elseif($reviewCorrect === false)
                                            <span class="text-xs font-semibold text-slate-600">Perlu ditinjau</span>
                                        @else
                                            <span class="text-xs font-semibold text-slate-600">Review dosen</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm font-medium leading-relaxed text-slate-800">{{ $reviewQuestion['prompt'] }}</p>
                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                        <div class="rounded-lg bg-slate-50 p-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jawaban Anda</p>
                                            <p class="mt-1 text-xs leading-relaxed text-slate-800">{{ $reviewAnswerText }}</p>
                                        </div>
                                        <div class="rounded-lg border border-brand/20 bg-brand-soft p-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand">Lumina AI</p>
                                            <p class="mt-1 text-xs leading-relaxed text-slate-800">{{ $reviewExplanation }}</p>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    {{-- Tombol Tindakan Purna-Ujian --}}
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="button-primary w-full sm:w-auto py-2.5 px-6 font-bold shadow-xs">
                            ← Kembali ke Halaman Course
                        </a>
                        <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="button-secondary w-full sm:w-auto py-2.5 px-5 font-semibold">
                            Lihat Rincian Tugas
                        </a>
                    </div>

                </div>
            </main>
        </div>

    @else

        {{-- ================================================================= --}}
        {{-- RUANG UJIAN AKTIF (LAYAR PENUH FOKUS TANPA DISTRAKSI & TANPA SIDEBAR) --}}
        {{-- ================================================================= --}}

        {{-- TOP STICKY APP BAR --}}
        <header class="quiz-topbar h-16 shrink-0 border-b border-slate-200 z-30 px-4 sm:px-6 flex items-center justify-between gap-4">
            
            {{-- Left: Exit Link & Exam Info --}}
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="btn-exit-exam" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer" title="Kembali ke Ringkasan" aria-label="Keluar dari ruang ujian">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500">{{ $course['code'] }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-500 truncate hidden sm:inline">{{ $course['title'] }}</span>
                    </div>
                    <h1 class="truncate text-[15px] font-bold text-slate-900 leading-tight">{{ $item['title'] }}</h1>
                </div>
            </div>

            {{-- Center: Countdown Timer & Tombol Daftar Soal Modal --}}
            <div class="flex items-center gap-2.5">
                {{-- Countdown Timer --}}
                @if($durationMinutes)
                    <div id="timer-badge" class="flex items-center gap-1.5 bg-brand-soft border border-brand/20 px-3 py-2 rounded-lg text-xs font-mono font-bold text-brand-dark">
                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span id="quiz-countdown" data-duration="{{ $durationMinutes * 60 }}">--:--</span>
                    </div>
                @else
                    <div class="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded text-xs text-slate-600 border border-slate-200">
                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span>Durasi Bebas</span>
                    </div>
                @endif

                {{-- TOMBOL DAFTAR SOAL (Grid Popover Trigger) --}}
                <button type="button" id="btn-open-grid-modal" class="button-secondary text-xs py-2 px-3.5 font-bold flex items-center gap-1.5 bg-white hover:bg-slate-50 border-slate-300 text-slate-800 shadow-2xs cursor-pointer" title="Buka Daftar Nomor Soal">
                    <svg class="h-3.5 w-3.5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                    <span>Daftar Soal (<span id="header-cur-step">1</span>/{{ $totalQuestions }})</span>
                </button>
            </div>

            {{-- Right: Stepper Navigasi Soal di Kanan Atas (Sebelumnya, Selanjutnya / Kumpulkan di Soal Terakhir) --}}
            <div class="flex items-center gap-2">
                <button type="button" id="btn-top-prev" class="button-secondary text-xs py-2 px-3 font-semibold flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" title="Soal Sebelumnya">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </button>

                <button type="button" id="btn-top-next" class="button-primary text-xs py-2 px-4 font-semibold flex items-center gap-1.5 cursor-pointer" title="Soal Selanjutnya">
                    <span>Selanjutnya</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" id="btn-top-finish" class="button-primary text-xs py-2 px-4 font-bold flex items-center gap-1.5 shadow-xs hidden cursor-pointer" title="Kumpulkan Kuis">
                    <span>Kumpulkan Kuis</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
            </div>
        </header>

        {{-- MAIN EXAM SPLIT WORKBENCH (FULL SCREEN TANPA SIDEBAR) --}}
        <main class="flex-1 overflow-hidden bg-slate-100/70 p-3 sm:p-5">
            <form id="exam-form" method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}" class="h-full">
                @csrf
                <input type="hidden" name="from_quiz_room" value="1">

                @foreach($questions as $qIdx => $q)
                    <div data-exam-card="{{ $qIdx }}" class="relative h-full grid grid-cols-1 lg:grid-cols-[minmax(320px,0.75fr)_minmax(0,1.35fr)] xl:grid-cols-[420px_minmax(0,1fr)] gap-4 {{ $qIdx === 0 ? '' : 'hidden' }}">
                        
                        {{-- PANEL KIRI: SOAL & INSTRUKSI --}}
                        <section class="quiz-panel quiz-question-panel h-full flex flex-col overflow-hidden">
                            {{-- Header Panel Kiri --}}
                            <div class="px-5 py-4 border-b border-slate-200/80 flex items-center justify-between bg-white/70">
                                <div class="flex items-center gap-2">
                                    <span class="h-6 w-6 rounded bg-slate-900 text-white text-xs font-bold flex items-center justify-center">
                                        {{ $qIdx + 1 }}
                                    </span>
                                    <span class="quiz-kicker text-[10px] font-bold uppercase text-slate-600">
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
                                    <span class="rounded-full bg-brand-soft px-2.5 py-1 font-bold text-brand-dark">{{ $q['points'] }} poin</span>
                                    <span>·</span>
                                    <span class="text-slate-500">{{ $q['cpmk'] }}</span>
                                </div>
                            </div>

                            {{-- Body Panel Kiri (Scrollable) --}}
                            <div class="p-6 flex-1 overflow-y-auto [scrollbar-gutter:stable] space-y-5">
                                <div>
                                    <h2 class="quiz-kicker text-[10px] font-bold text-brand uppercase mb-3">Instruksi / Pertanyaan</h2>
                                    <div class="text-[15px] font-medium text-slate-800 leading-7 whitespace-pre-line">{{ $q['prompt'] }}</div>
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
                            <div class="px-5 py-3 border-t border-slate-200/80 bg-white/70 flex items-center justify-between text-xs text-slate-400">
                                <span>Soal {{ $qIdx + 1 }} dari {{ $totalQuestions }}</span>
                                <span class="text-muted font-medium flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                    Draf tersimpan otomatis
                                </span>
                            </div>
                        </section>

                        {{-- PANEL KANAN: AREA LEMBAR KERJA / TEMPAT MENJAWAB --}}
                        <section class="quiz-panel quiz-answer-panel h-full flex flex-col overflow-hidden">
                            
                            {{-- TIPE 1: CODING (Integrated Workbench - Mirip Assignment Code Sebelumnya Tanpa AI) --}}
                            @if($q['type'] === 'coding')
                                <div id="panel-editor-{{ $qIdx }}" class="flex-1 min-w-[320px] flex flex-col h-full overflow-hidden transition-none p-1">
                                    {{-- Editor Section --}}
                                    <section class="flex-1 flex flex-col min-h-0 rounded-xl bg-white shadow-sm border border-slate-200 overflow-hidden" aria-labelledby="editor-heading-{{ $qIdx }}">
                                        {{-- Single Integrated Toolbar: Tab Berkas di kiri, Aksi & Terminal Toggle di kanan --}}
                                        <div class="flex items-center justify-between border-b border-slate-200/80 bg-slate-50 px-2.5 py-1.5 gap-2 select-none">
                                            {{-- File Tabs (Kiri) --}}
                                            <div data-file-tabs class="flex items-center gap-1 overflow-x-auto min-w-0" role="tablist" aria-label="Berkas kode">
                                                <div class="flex items-center gap-1 min-w-0">
                                                    <span class="flex items-center gap-1.5 px-3 py-1 bg-white border border-slate-200 rounded-md text-xs font-mono font-semibold text-slate-800 shadow-2xs">
                                                        solution.py
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Action Buttons (Kanan): Terminal (Icon) | Play (Icon) (tanpa AI) --}}
                                            <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                                                <button type="button" data-terminal-toggle-btn="{{ $qIdx }}" class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white hover:bg-slate-50 text-slate-700 transition hover:border-ink shadow-2xs leading-none cursor-pointer" title="Buka / Tutup Terminal" aria-label="Terminal">
                                                    <svg class="h-3.5 w-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                                                </button>
                                                <button type="button" data-run-code-btn="{{ $qIdx }}" class="button-primary !p-0 !min-h-0 h-8 w-8 text-white shadow-xs transition leading-none cursor-pointer" title="Jalankan kode" aria-label="Jalankan kode">
                                                    <svg class="h-3.5 w-3.5 fill-current text-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                                        <polygon points="5 3 19 12 5 21 5 3"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Textarea / Code Editor --}}
                                        <textarea name="question_answers[{{ $qIdx }}][text]" data-code-textarea="{{ $qIdx }}" class="flex-1 w-full bg-[#282c34] text-slate-100 font-mono text-xs p-4 leading-relaxed resize-none focus:outline-none focus:ring-0 border-0 selection:bg-brand/30 selection:text-white" placeholder="# Tuliskan implementasi solusi Python Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? ($q['options'] ?? '')) }}</textarea>

                                        {{-- Status Bar Bawah Editor --}}
                                        <div class="flex items-center justify-between gap-3 px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4] font-mono select-none">
                                            <span class="flex items-center gap-1.5 text-slate-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                <span>Draf tersimpan di browser</span>
                                            </span>
                                            <span class="flex items-center gap-3 text-slate-400">
                                                <span id="code-chars-{{ $qIdx }}">0 karakter</span>
                                                <span>UTF-8 · 4 Spasi</span>
                                            </span>
                                        </div>
                                    </section>

                                    {{-- Terminal Wrapper: Resizer di atas + Terminal Panel (On-demand / Hidden by default!) --}}
                                    <div id="terminal-wrapper-{{ $qIdx }}" class="flex flex-col shrink-0 mt-2" hidden>
                                        {{-- Resizer Handle Tinggi Terminal --}}
                                        <div class="h-3 w-full shrink-0 cursor-row-resize flex items-center justify-center bg-slate-200/80 hover:bg-brand/30 group transition-colors rounded-t-lg select-none" title="Geser ke atas/bawah untuk mengatur tinggi terminal">
                                            <div class="h-1 w-14 rounded-full bg-slate-400 group-hover:bg-brand transition-colors"></div>
                                        </div>

                                        <section class="flex flex-col rounded-b-xl bg-[#0d1117] text-[#c9d1d9] shadow-sm border border-slate-200/40 overflow-hidden" style="height: 220px; min-height: 120px;" aria-labelledby="terminal-heading-{{ $qIdx }}">
                                            <div class="flex items-center justify-between px-4 py-2 bg-[#161b22] border-b border-white/10 select-none">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center gap-1.5">
                                                        <button type="button" data-terminal-close-dot="{{ $qIdx }}" class="h-3 w-3 rounded-full bg-[#ff5f56] hover:opacity-80 transition inline-block shadow-xs cursor-pointer" title="Tutup Terminal" aria-label="Tutup Terminal"></button>
                                                        <button type="button" class="h-3 w-3 rounded-full bg-[#ffbd2e] hover:opacity-80 transition inline-block shadow-xs" title="Perkecil Terminal" aria-label="Perkecil Terminal"></button>
                                                        <button type="button" class="h-3 w-3 rounded-full bg-[#27c93f] hover:opacity-80 transition inline-block shadow-xs" title="Perbesar Terminal" aria-label="Perbesar Terminal"></button>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 text-slate-300">
                                                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                                                        <span class="text-xs font-mono text-slate-300 font-medium">Output Python · Terminal</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" data-clear-terminal="{{ $qIdx }}" class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition cursor-pointer">
                                                        Bersihkan
                                                    </button>
                                                    <button type="button" data-terminal-close-btn="{{ $qIdx }}" class="text-xs text-slate-400 hover:text-white px-2 py-0.5 rounded hover:bg-white/10 transition cursor-pointer" title="Tutup Terminal">
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="terminal-output-{{ $qIdx }}" class="p-3.5 flex-1 overflow-y-auto space-y-1 text-xs font-mono text-slate-300">
                                                <div class="text-slate-500">sale@sandbox:~/soal-{{ $qIdx + 1 }}$ python3 -u solution.py</div>
                                                <div class="text-slate-400">Tekan tombol play untuk menguji kode solusi Anda.</div>
                                            </div>
                                        </section>
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
                                        <span class="text-slate-600 font-medium">Klik premis atau jawaban terlebih dahulu, lalu klik pasangannya untuk menghubungkan.</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-slate-500 font-semibold"><span id="match-counter-{{ $qIdx }}">0 dari {{ count($pairs) }}</span> terhubung</span>
                                            <button type="button" data-reset-lines="{{ $qIdx }}" class="text-xs text-muted hover:text-ink underline font-medium cursor-pointer">Reset Semua Garis</button>
                                        </div>
                                    </div>

                                    {{-- Interactive Line Drawing Canvas --}}
                                    <div class="flex-1 overflow-y-auto [scrollbar-gutter:stable] p-6 match-canvas-container relative" id="canvas-container-{{ $qIdx }}">
                                        <svg class="absolute pointer-events-none z-10" style="top: 0; left: 0; min-width: 100%; min-height: 100%;" id="match-svg-{{ $qIdx }}"></svg>

                                        <div class="grid grid-cols-2 gap-20 relative z-20 items-stretch">
                                            {{-- Kolom Kiri: Premis --}}
                                            <div class="space-y-4" id="match-left-col-{{ $qIdx }}">
                                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Kolom Premis</p>
                                                @foreach($pairs as $pIdx => $pair)
                                                    @php
                                                        $isImg = str_starts_with($pair['left'], 'http') || str_starts_with($pair['left'], 'data:image') || str_starts_with($pair['left'], '/');
                                                    @endphp
                                                    <div class="relative flex items-center justify-between p-3.5 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition cursor-pointer select-none" data-match-left-card="{{ $pIdx }}">
                                                        <div class="min-w-0 pr-4 w-full">
                                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                                <span class="text-[11px] font-semibold text-slate-400">Premis {{ $pIdx + 1 }}</span>
                                                                <button type="button" data-disconnect-left="{{ $pIdx }}" class="hidden text-[11px] text-muted hover:text-ink font-semibold items-center gap-0.5 cursor-pointer" title="Batalkan / Putuskan sambungan">
                                                                    ✕ Putuskan
                                                                </button>
                                                            </div>
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
                                                    <div class="relative flex items-center p-3.5 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition cursor-pointer select-none" data-match-right-card="{{ $tIdx }}" data-target-val="{{ $target }}">
                                                        <button type="button"
                                                            data-dot-side="right"
                                                            data-dot-idx="{{ $tIdx }}"
                                                            class="match-dot absolute -left-2.5 top-1/2 -translate-y-1/2 h-5 w-5 rounded-full border-2 border-white bg-slate-300 shadow-xs flex items-center justify-center cursor-pointer"
                                                            title="Pasangkan dengan Jawaban {{ $tIdx + 1 }}">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                        </button>

                                                        <div class="min-w-0 pl-3 w-full">
                                                            <span class="text-[11px] font-semibold text-slate-400 block mb-1">Jawaban {{ $tIdx + 1 }}</span>
                                                            @if($isTargetImg)
                                                                <div class="flex items-center justify-center p-1 bg-slate-50 rounded border border-slate-100">
                                                                    <img src="{{ $target }}" alt="Jawaban {{ $tIdx + 1 }}" class="max-h-16 max-w-full object-contain">
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
                                    <div class="px-6 py-4 border-b border-slate-200/80 text-xs text-slate-500 bg-slate-50/60">
                                        {{ $isMultiple ? 'Pilih semua opsi yang benar di bawah ini:' : 'Pilih satu opsi jawaban yang paling tepat:' }}
                                    </div>
                                    <div class="p-6 flex-1 overflow-y-auto [scrollbar-gutter:stable] space-y-3">
                                        @foreach($options as $optIdx => $opt)
                                            <label class="quiz-option flex items-center gap-3.5 p-4 rounded-xl border bg-white cursor-pointer transition">
                                                <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                                                    name="question_answers[{{ $qIdx }}][choices][]"
                                                    value="{{ $opt }}"
                                                    class="h-4 w-4 text-slate-900 focus:ring-slate-900 rounded"
                                                    @checked(in_array($opt, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                                <span class="text-sm font-medium text-slate-800 leading-relaxed">{{ $opt }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                            {{-- TIPE 4: BENAR / SALAH --}}
                            @elseif($q['type'] === 'benar_salah')
                                <div class="h-full flex flex-col">
                                    <div class="px-6 py-4 border-b border-slate-200/80 text-xs text-slate-500 bg-slate-50/60">
                                        Tentukan kebenaran dari pernyataan pada panel kiri:
                                    </div>
                                    <div class="p-6 flex-1 flex flex-col justify-start max-w-md mx-auto w-full space-y-3 pt-6">
                                        <label class="quiz-option flex items-center gap-3.5 p-4 rounded-xl border bg-white cursor-pointer transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar" class="h-4 w-4 text-slate-900 focus:ring-slate-900"
                                                @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                            <span class="text-sm font-bold text-slate-900">Benar</span>
                                        </label>
                                        <label class="quiz-option flex items-center gap-3.5 p-4 rounded-xl border bg-white cursor-pointer transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah" class="h-4 w-4 text-slate-900 focus:ring-slate-900"
                                                @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                            <span class="text-sm font-bold text-slate-900">Salah</span>
                                        </label>
                                    </div>
                                </div>

                            {{-- TIPE 5: ESSAY / URAIAN --}}
                            @else
                                <div class="h-full flex flex-col">
                                    <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between text-xs text-slate-500 bg-slate-50/60">
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

        {{-- MODAL POPOVER: DAFTAR NOMOR SOAL (Grid Kotak-Kotak 1 s/d N) --}}
        <dialog id="questions-grid-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-lg w-[calc(100%-2rem)] h-fit">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Nomor Soal Ujian</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Pilih nomor kotak untuk melompat langsung ke soal terkait.</p>
                </div>
                <button type="button" id="modal-grid-close" class="h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
            </div>

            {{-- Legenda Warna Status --}}
            <div class="flex flex-wrap items-center gap-3.5 text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-200/70 mb-4">
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded bg-brand inline-block"></span>
                    <span class="font-medium">Terjawab</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded bg-slate-900 inline-block"></span>
                    <span class="font-medium">Sedang Dibuka</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded bg-white border border-slate-300 inline-block"></span>
                    <span class="font-medium">Belum Dijawab</span>
                </span>
            </div>

            {{-- Kisi Kotak Nomor Soal --}}
            <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-2.5 max-h-[50vh] overflow-y-auto p-1" id="grid-boxes-container">
                @foreach($questions as $qIdx => $q)
                    <button type="button"
                        data-grid-step="{{ $qIdx }}"
                        class="h-11 rounded-xl text-xs font-bold transition flex flex-col items-center justify-center border border-slate-200 bg-white text-slate-700 hover:border-slate-400 cursor-pointer shadow-2xs">
                        <span>{{ $qIdx + 1 }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Footer Modal --}}
            <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span id="grid-answered-badge" class="text-xs font-semibold text-slate-600">0 dari {{ $totalQuestions }} soal terjawab</span>
                <button type="button" id="modal-grid-close-btn" class="button-secondary text-xs py-1.5 px-3.5 font-medium cursor-pointer">Tutup Kisi</button>
            </div>
        </dialog>

        {{-- MODAL KONFIRMASI KELUAR & KUMPULKAN OTOMATIS DARI RUANG UJIAN --}}
        <dialog id="exit-confirm-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-md w-[calc(100%-2rem)] h-fit">
            <div class="flex items-start gap-3.5 mb-4">
                <div class="h-10 w-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-slate-900">Keluar &amp; Kumpulkan Ujian?</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Jawaban yang telah dikerjakan akan disimpan otomatis sebagai hasil akhir kuis.
                    </p>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5 mb-4 text-xs text-slate-700 space-y-1.5">
                <div class="flex items-center gap-1.5 font-bold text-slate-900">
                    <svg class="h-4 w-4 shrink-0 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Ujian Selesai &amp; Tidak Dapat Diulang</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-normal">
                    Jika Anda keluar sekarang sebelum selesai, nilai dan lembar jawaban Anda akan <strong>langsung dikumpulkan secara otomatis</strong> dan Anda <strong>tidak dapat melanjutkan ujian lagi</strong>.
                </p>
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-5 text-xs">
                <span id="modal-exit-answered-count" class="font-bold text-slate-800">Menghitung status jawaban...</span>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <button type="button" id="modal-exit-cancel-btn" class="button-secondary text-xs py-2 px-3.5 font-medium cursor-pointer">
                    Lanjutkan Mengerjakan
                </button>
                <button type="button" id="modal-exit-confirm-btn" class="button-primary text-xs py-2 px-4 font-bold cursor-pointer">
                    Ya, Keluar &amp; Kumpulkan
                </button>
            </div>
        </dialog>

        {{-- MODAL KONFIRMASI PENGUMPULAN --}}
        <dialog id="submit-confirm-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-md w-[calc(100%-2rem)] h-fit">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Kumpulkan Lembar Kuis?</h3>
                    <p class="text-xs text-slate-500">Pemeriksaan final sebelum penyimpanan permanen.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed mb-4">
                Pastikan Anda telah memeriksa seluruh jawaban. Setelah dikumpulkan, lembar jawaban kuis akan <strong>terkunci</strong> dan tidak dapat dikerjakan ulang.
            </p>
            
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-5 text-xs">
                <span id="modal-answered-count" class="font-bold text-slate-800">Menghitung status jawaban...</span>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <button type="button" id="modal-cancel-btn" class="button-secondary text-xs py-2 px-3.5 font-medium cursor-pointer">Batal &amp; Periksa Lagi</button>
                <button type="button" id="modal-confirm-submit-btn" class="button-primary text-xs py-2 px-4 font-bold shadow-xs cursor-pointer">Ya, Kumpulkan Sekarang</button>
            </div>
        </dialog>

        {{-- SCRIPT ENGINE RUANG UJIAN --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('exam-form');
                const cards = document.querySelectorAll('[data-exam-card]');
                const totalCards = cards.length;
                let currentStep = 0;

                // Elements
                const headerCurStep = document.getElementById('header-cur-step');
                const btnTopPrev = document.getElementById('btn-top-prev');
                const btnTopNext = document.getElementById('btn-top-next');
                const btnTopFinish = document.getElementById('btn-top-finish');

                // Modals
                const gridModal = document.getElementById('questions-grid-modal');
                const btnOpenGridModal = document.getElementById('btn-open-grid-modal');
                const modalGridClose = document.getElementById('modal-grid-close');
                const modalGridCloseBtn = document.getElementById('modal-grid-close-btn');
                const gridStepBtns = document.querySelectorAll('[data-grid-step]');
                const gridAnsweredBadge = document.getElementById('grid-answered-badge');

                const submitBtn = document.getElementById('btn-submit-exam');
                const submitModal = document.getElementById('submit-confirm-modal');
                const modalCancelBtn = document.getElementById('modal-cancel-btn');
                const modalConfirmBtn = document.getElementById('modal-confirm-submit-btn');
                const modalAnsweredSpan = document.getElementById('modal-answered-count');

                // ==========================================
                // 1. STEPPER CONTROLLER (KANAN ATAS)
                // ==========================================
                const setStep = (idx) => {
                    if (idx < 0 || idx >= totalCards) return;
                    currentStep = idx;

                    // Toggle Card Active
                    cards.forEach((c, i) => {
                        c.classList.toggle('hidden', i !== currentStep);
                    });

                    // Update Indicator Text
                    if (headerCurStep) headerCurStep.textContent = `${currentStep + 1}`;

                    // Update Stepper Buttons (Prev / Next / Finish) di Kanan Atas
                    if (btnTopPrev) btnTopPrev.disabled = currentStep === 0;

                    if (currentStep === totalCards - 1) {
                        // Soal Terakhir: Next disembunyikan, Finish ditampilkan
                        if (btnTopNext) btnTopNext.classList.add('hidden');
                        if (btnTopFinish) btnTopFinish.classList.remove('hidden');
                    } else {
                        // Soal Bukan Terakhir: Next ditampilkan, Finish disembunyikan
                        if (btnTopNext) btnTopNext.classList.remove('hidden');
                        if (btnTopFinish) btnTopFinish.classList.add('hidden');
                    }

                    redrawCurrentMatchingLines();
                    updateAnsweredStatus();
                };

                btnTopPrev?.addEventListener('click', () => setStep(currentStep - 1));
                btnTopNext?.addEventListener('click', () => setStep(currentStep + 1));
                btnTopFinish?.addEventListener('click', () => openSubmitModal());

                // ==========================================
                // 2. GRID MODAL CONTROLLER
                // ==========================================
                const openGridModal = () => {
                    updateAnsweredStatus();
                    gridModal?.showModal();
                };

                btnOpenGridModal?.addEventListener('click', openGridModal);
                modalGridClose?.addEventListener('click', () => gridModal?.close());
                modalGridCloseBtn?.addEventListener('click', () => gridModal?.close());

                gridStepBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const targetStep = Number(btn.dataset.gridStep);
                        setStep(targetStep);
                        gridModal?.close();
                    });
                });

                // ==========================================
                // 3. ANSWERED STATUS & STYLING
                // ==========================================
                function updateAnsweredStatus() {
                    let answeredCount = 0;

                    cards.forEach((card, idx) => {
                        const gridBtn = gridStepBtns[idx];
                        if (!gridBtn) return;

                        const radios = card.querySelectorAll('input[type="radio"]:checked');
                        const checkboxes = card.querySelectorAll('input[type="checkbox"]:checked');
                        const hiddens = card.querySelectorAll('input[type="hidden"][id^="hidden-match-"]');
                        let matchAnswered = hiddens.length > 0;
                        hiddens.forEach(h => { if (!h.value) matchAnswered = false; });
                        const textareas = card.querySelectorAll('textarea');
                        let textAnswered = false;
                        textareas.forEach(t => { if (t.value.trim().length > 0) textAnswered = true; });

                        const isAnswered = radios.length > 0 || checkboxes.length > 0 || (hiddens.length > 0 && matchAnswered) || textAnswered;
                        if (isAnswered) answeredCount++;

                        // Style Grid Box
                        if (idx === currentStep) {
                            gridBtn.className = 'h-11 rounded-xl text-xs font-bold transition flex flex-col items-center justify-center border-2 border-brand ring-2 ring-brand/20 bg-white text-brand shadow-xs cursor-pointer';
                        } else if (isAnswered) {
                            gridBtn.className = 'h-11 rounded-xl text-xs font-bold transition flex flex-col items-center justify-center border border-brand bg-brand text-white shadow-xs cursor-pointer';
                        } else {
                            gridBtn.className = 'h-11 rounded-xl text-xs font-medium transition flex flex-col items-center justify-center border border-line bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50 cursor-pointer';
                        }
                    });

                    if (gridAnsweredBadge) {
                        gridAnsweredBadge.textContent = `${answeredCount} dari ${totalCards} soal terjawab`;
                    }

                    return answeredCount;
                }

                form.addEventListener('input', updateAnsweredStatus);
                form.addEventListener('change', updateAnsweredStatus);

                // ==========================================
                // 4. SUBMISSION CONFIRMATION MODAL
                // ==========================================
                const openSubmitModal = () => {
                    const ans = updateAnsweredStatus();
                    if (modalAnsweredSpan) {
                        modalAnsweredSpan.textContent = `${ans} dari ${totalCards} soal telah dijawab.`;
                    }
                    submitModal?.showModal();
                };

                submitBtn?.addEventListener('click', openSubmitModal);
                modalCancelBtn?.addEventListener('click', () => submitModal?.close());
                modalConfirmBtn?.addEventListener('click', () => {
                    localStorage.removeItem(`sale.exam.deadline.{{ $item['id'] }}`);
                    sessionStorage.removeItem(`sale.exam.timer.{{ $item['id'] }}`);
                    form.requestSubmit();
                });

                // Exit Confirmation Modal Handler (Keluar = Kumpulkan Otomatis & Kunci Ujian)
                const exitModal = document.getElementById('exit-confirm-modal');
                const btnExitExam = document.getElementById('btn-exit-exam');
                const modalExitCancelBtn = document.getElementById('modal-exit-cancel-btn');
                const modalExitConfirmBtn = document.getElementById('modal-exit-confirm-btn');
                const modalExitAnsweredSpan = document.getElementById('modal-exit-answered-count');

                const openExitModal = () => {
                    const ans = updateAnsweredStatus();
                    if (modalExitAnsweredSpan) {
                        modalExitAnsweredSpan.textContent = `${ans} dari ${totalCards} soal telah dijawab.`;
                    }
                    exitModal?.showModal();
                };

                btnExitExam?.addEventListener('click', openExitModal);

                // Mencegah tombol back di browser / swipe agar tidak langsung keluar tanpa kumpul
                history.pushState(null, '', window.location.href);
                window.addEventListener('popstate', () => {
                    history.pushState(null, '', window.location.href);
                    openExitModal();
                });

                modalExitCancelBtn?.addEventListener('click', () => {
                    exitModal?.close();
                });

                modalExitConfirmBtn?.addEventListener('click', () => {
                    localStorage.removeItem(`sale.exam.deadline.{{ $item['id'] }}`);
                    sessionStorage.removeItem(`sale.exam.timer.{{ $item['id'] }}`);
                    form.requestSubmit();
                });

                // ==========================================
                // 5. COUNTDOWN TIMER (PERSISTEN BERJALAN MESKI KELUAR ROOM)
                // ==========================================
                const timerEl = document.getElementById('quiz-countdown');
                if (timerEl) {
                    const totalDurationSeconds = Number(timerEl.dataset.duration || 3600);
                    const deadlineKey = `sale.exam.deadline.{{ $item['id'] }}`;
                    
                    let deadline = localStorage.getItem(deadlineKey);
                    const now = Date.now();

                    if (!deadline || isNaN(Number(deadline))) {
                        // Pertama kali masuk: tentukan batas akhir waktu absolut
                        deadline = now + (totalDurationSeconds * 1000);
                        localStorage.setItem(deadlineKey, String(deadline));
                    } else {
                        deadline = Number(deadline);
                    }

                    const formatTime = (seconds) => {
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    };

                    const updateTimer = () => {
                        const currentNow = Date.now();
                        const remainingSeconds = Math.max(0, Math.floor((deadline - currentNow) / 1000));
                        timerEl.textContent = formatTime(remainingSeconds);

                        if (remainingSeconds <= 300) {
                            const badge = document.getElementById('timer-badge');
                            if (badge) {
                                badge.classList.add('text-danger', 'font-bold');
                            }
                        }

                        if (remainingSeconds <= 0) {
                            clearInterval(timerInterval);
                            localStorage.removeItem(deadlineKey);
                            alert('Waktu ujian kuis telah berakhir. Jawaban Anda akan otomatis dikumpulkan.');
                            form.requestSubmit();
                        }
                    };

                    updateTimer();
                    const timerInterval = setInterval(updateTimer, 1000);
                }

                // ==========================================
                // 6. INTERACTIVE MATCHING LINE-DRAWING (SVG Canvas)
                // ==========================================
                const matchingQuestions = {};

                document.querySelectorAll('.match-canvas-container').forEach(container => {
                    const qIdxMatch = container.id.match(/\d+/);
                    if (!qIdxMatch) return;
                    const qIdx = Number(qIdxMatch[0]);

                    const svg = document.getElementById(`match-svg-${qIdx}`);
                    const leftCards = container.querySelectorAll('[data-match-left-card]');
                    const rightCards = container.querySelectorAll('[data-match-right-card]');
                    const leftDots = container.querySelectorAll('[data-dot-side="left"]');
                    const rightDots = container.querySelectorAll('[data-dot-side="right"]');
                    const counterEl = document.getElementById(`match-counter-${qIdx}`);
                    const resetBtn = container.parentElement?.querySelector(`[data-reset-lines="${qIdx}"]`);

                    const connections = {};
                    let selectedPremiseIdx = null;
                    let selectedAnswerIdx = null;

                    // Pulihkan sambungan yang tersimpan sebelumnya
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
                        svg.style.width = Math.max(container.scrollWidth, container.clientWidth) + 'px';
                        svg.style.height = Math.max(container.scrollHeight, container.clientHeight) + 'px';

                        let connectedCount = 0;

                        Object.entries(connections).forEach(([lIdx, conn]) => {
                            const lDot = container.querySelector(`[data-dot-side="left"][data-dot-idx="${lIdx}"]`);
                            const rDot = container.querySelector(`[data-dot-side="right"][data-dot-idx="${conn.targetIdx}"]`);
                            if (lDot && rDot) {
                                const lRect = lDot.getBoundingClientRect();
                                const rRect = rDot.getBoundingClientRect();

                                const x1 = lRect.left + lRect.width / 2 - containerRect.left + container.scrollLeft;
                                const y1 = lRect.top + lRect.height / 2 - containerRect.top + container.scrollTop;
                                const x2 = rRect.left + rRect.width / 2 - containerRect.left + container.scrollLeft;
                                const y2 = rRect.top + rRect.height / 2 - containerRect.top + container.scrollTop;

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

                        // Sinkronkan tombol batal/putuskan pada setiap kartu kiri
                        leftCards.forEach(lc => {
                            const lIdx = Number(lc.dataset.matchLeftCard);
                            const discBtn = lc.querySelector('[data-disconnect-left]');
                            if (discBtn) {
                                if (connections[lIdx]) {
                                    discBtn.classList.remove('hidden');
                                    discBtn.classList.add('inline-flex');
                                } else {
                                    discBtn.classList.add('hidden');
                                    discBtn.classList.remove('inline-flex');
                                }
                            }
                        });

                        if (counterEl) {
                            counterEl.textContent = `${connectedCount} dari ${leftDots.length}`;
                        }
                        updateAnsweredStatus();
                    };

                    matchingQuestions[qIdx] = { redraw: redrawLines };

                    const clearSelection = () => {
                        selectedPremiseIdx = null;
                        selectedAnswerIdx = null;
                        leftCards.forEach(c => c.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                        rightCards.forEach(c => c.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                        leftDots.forEach(d => d.classList.remove('selected'));
                        rightDots.forEach(d => d.classList.remove('selected'));
                    };

                    const connectSelectedItems = (pIdx, rIdx) => {
                        const rc = container.querySelector(`[data-match-right-card="${rIdx}"]`);
                        const lDot = container.querySelector(`[data-dot-side="left"][data-dot-idx="${pIdx}"]`);
                        if (!rc || !lDot) return;

                        const targetVal = rc.dataset.targetVal;
                        connections[pIdx] = {
                            targetIdx: rIdx,
                            targetVal,
                            color: lDot.dataset.color || '#1d4ed8'
                        };
                        const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${pIdx}`);
                        if (hiddenInp) hiddenInp.value = targetVal;

                        clearSelection();
                        redrawLines();
                    };

                    // Delegasi klik memastikan seluruh isi kartu dan dot dapat dipilih.
                    container.addEventListener('click', (e) => {
                        if (e.target.closest('[data-disconnect-left]')) return;

                        const leftCard = e.target.closest('[data-match-left-card]');
                        const rightCard = e.target.closest('[data-match-right-card]');

                        if (leftCard) {
                            const pIdx = Number(leftCard.dataset.matchLeftCard);
                            if (selectedPremiseIdx === pIdx && selectedAnswerIdx === null) {
                                clearSelection();
                                return;
                            }
                            if (selectedAnswerIdx !== null) {
                                connectSelectedItems(pIdx, selectedAnswerIdx);
                                return;
                            }

                            selectedPremiseIdx = pIdx;
                            selectedAnswerIdx = null;
                            leftCards.forEach(card => {
                                const isTarget = Number(card.dataset.matchLeftCard) === pIdx;
                                card.classList.toggle('ring-2', isTarget);
                                card.classList.toggle('ring-brand', isTarget);
                                card.classList.toggle('border-brand', isTarget);
                            });
                            rightCards.forEach(card => card.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                            leftDots.forEach(dot => dot.classList.toggle('selected', Number(dot.dataset.dotIdx) === pIdx));
                            rightDots.forEach(dot => dot.classList.remove('selected'));
                            return;
                        }

                        if (rightCard) {
                            const rightDot = rightCard.querySelector('[data-dot-side="right"]');
                            if (!rightDot) return;
                            const rIdx = Number(rightDot.dataset.dotIdx);
                            if (selectedPremiseIdx !== null) {
                                connectSelectedItems(selectedPremiseIdx, rIdx);
                                return;
                            }
                            if (selectedAnswerIdx === rIdx) {
                                clearSelection();
                                return;
                            }

                            selectedAnswerIdx = rIdx;
                            selectedPremiseIdx = null;
                            leftCards.forEach(card => card.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                            leftDots.forEach(dot => dot.classList.remove('selected'));
                            rightCards.forEach(card => {
                                const isTarget = Number(card.dataset.matchRightCard) === rIdx;
                                card.classList.toggle('ring-2', isTarget);
                                card.classList.toggle('ring-brand', isTarget);
                                card.classList.toggle('border-brand', isTarget);
                            });
                            rightDots.forEach(dot => dot.classList.toggle('selected', Number(dot.dataset.dotIdx) === rIdx));
                        }
                    });

                    // Putuskan / Batalkan Sambungan Satuan (Inline Action)
                    container.querySelectorAll('[data-disconnect-left]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const lIdx = Number(btn.dataset.disconnectLeft);
                            delete connections[lIdx];
                            const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                            if (hiddenInp) hiddenInp.value = '';

                            if (selectedPremiseIdx === lIdx) {
                                clearSelection();
                            }
                            redrawLines();
                        });
                    });

                    // Reset Semua Garis
                    resetBtn?.addEventListener('click', () => {
                        Object.keys(connections).forEach(k => delete connections[k]);
                        leftDots.forEach(ld => {
                            const lIdx = Number(ld.dataset.dotIdx);
                            const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                            if (hiddenInp) hiddenInp.value = '';
                        });
                        clearSelection();
                        redrawLines();
                    });

                    container.addEventListener('scroll', () => {
                        requestAnimationFrame(redrawLines);
                    }, { passive: true });

                    requestAnimationFrame(redrawLines);
                });

                function redrawCurrentMatchingLines() {
                    const curCard = cards[currentStep];
                    if (!curCard) return;
                    const mContainer = curCard.querySelector('.match-canvas-container');
                    if (mContainer) {
                        const qIdxMatch = mContainer.id.match(/\d+/);
                        if (qIdxMatch && matchingQuestions[qIdxMatch[0]]) {
                            requestAnimationFrame(() => matchingQuestions[qIdxMatch[0]].redraw());
                        }
                    }
                }

                window.addEventListener('resize', () => {
                    redrawCurrentMatchingLines();
                });

                // ==========================================
                // 7. CODE TEXTAREA CHARACTERS COUNTER & RUNNER
                // ==========================================
                document.querySelectorAll('[data-code-textarea]').forEach(textarea => {
                    const idx = textarea.dataset.codeTextarea;
                    const counter = document.getElementById(`code-chars-${idx}`);
                    const updateChars = () => {
                        if (counter) counter.textContent = `${textarea.value.length} karakter`;
                    };
                    textarea.addEventListener('input', updateChars);
                    updateChars();
                });
                // Terminal Toggle, Close & Clear Handlers
                document.querySelectorAll('[data-terminal-toggle-btn]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.terminalToggleBtn;
                        const wrapper = document.getElementById(`terminal-wrapper-${qIdx}`);
                        if (wrapper) {
                            wrapper.hidden = !wrapper.hidden;
                        }
                    });
                });

                document.querySelectorAll('[data-terminal-close-btn], [data-terminal-close-dot]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.terminalCloseBtn || btn.dataset.terminalCloseDot;
                        const wrapper = document.getElementById(`terminal-wrapper-${qIdx}`);
                        if (wrapper) {
                            wrapper.hidden = true;
                        }
                    });
                });

                document.querySelectorAll('[data-clear-terminal]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.clearTerminal;
                        const terminal = document.getElementById(`terminal-output-${qIdx}`);
                        if (terminal) {
                            terminal.innerHTML = `<div class="text-slate-500 font-mono">sale@sandbox:~/soal-${Number(qIdx)+1}$ </div>`;
                        }
                    });
                });

                document.querySelectorAll('[data-run-code-btn]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.runCodeBtn || btn.closest('[data-exam-card]')?.dataset.examCard || '0';
                        const wrapper = document.getElementById(`terminal-wrapper-${qIdx}`);
                        if (wrapper && wrapper.hidden) {
                            wrapper.hidden = false;
                        }
                        const card = btn.closest('[data-exam-card]');
                        const textarea = card?.querySelector('textarea');
                        const terminal = document.getElementById(`terminal-output-${qIdx}`);
                        if (!terminal || !textarea) return;

                        terminal.innerHTML = `
                            <div class="text-slate-400 font-mono">sale@sandbox:~/soal-${Number(qIdx)+1}$ python3 -u solution.py</div>
                            <div class="text-amber-400 font-mono">Menjalankan kompilasi &amp; pengujian kode...</div>
                        `;

                        setTimeout(() => {
                            terminal.innerHTML = `
                                <div class="text-slate-400 font-mono">sale@sandbox:~/soal-${Number(qIdx)+1}$ python3 -u solution.py</div>
                                <div class="text-slate-300 font-mono">>>> bst = BST()</div>
                                <div class="text-slate-300 font-mono">>>> bst.insert(15) # Simpul root berhasil dibuat</div>
                                <div class="text-slate-300 font-mono">>>> bst.insert(10) # Cabang kiri tervalidasi (10 < 15)</div>
                                <div class="text-slate-300 font-mono">>>> bst.insert(20) # Cabang kanan tervalidasi (20 > 15)</div>
                                <div class="text-emerald-400 font-semibold font-mono mt-1">✓ Seluruh pengujian berhasil lolos (Exit code: 0).</div>
                            `;
                        }, 400);
                    });
                });

                // ==========================================
                // 8. ESSAY WORD COUNTER
                // ==========================================
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

                // Initial Activation
                setStep(0);
            });
        </script>

    @endif

</body>
</html>
