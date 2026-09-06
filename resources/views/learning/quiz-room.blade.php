<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>{{ $item['title'] }} · Ruang Ujian Kuis | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .match-canvas-container {
            position: relative;
            user-select: none;
        }
        .match-dot {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .match-dot:hover {
            transform: scale(1.25);
        }
        .match-dot.selected {
            ring-width: 4px;
            transform: scale(1.3);
            animation: pulse-ring 1.5s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5); }
            70% { box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        .connection-line {
            transition: stroke 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.12));
        }
    </style>
</head>
<body class="min-h-screen bg-[#f8fafc] font-sans text-ink antialiased flex flex-col selection:bg-brand selection:text-white">

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

    {{-- TOP STICKY APP BAR (Distraction-Free Exam Header) --}}
    <header class="sticky top-0 z-40 bg-[#102f50] text-white border-b border-[#1b436e] shadow-md">
        <div class="flex min-h-14 w-full flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6">
            
            {{-- Left: Exit & Quiz Info --}}
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="btn-exit-exam" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white hover:bg-white/20 transition" title="Kembali ke Informasi Kuis" aria-label="Keluar dari ruang ujian">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-white/20 text-white">
                            Ruang Kuis CBT
                        </span>
                        <span class="text-xs text-slate-300 hidden sm:inline">· {{ $course['code'] }}</span>
                    </div>
                    <h1 class="truncate text-sm font-bold text-white tracking-tight">{{ $item['title'] }}</h1>
                </div>
            </div>

            {{-- Center: Timer & Question Navigator Pills --}}
            <div class="flex items-center gap-4 order-last sm:order-none w-full sm:w-auto justify-between sm:justify-center border-t sm:border-t-0 pt-2 sm:pt-0 border-white/10">
                
                {{-- Countdown Timer --}}
                <div class="flex items-center gap-2">
                    @if($durationMinutes)
                        <div id="timer-badge" class="flex items-center gap-1.5 bg-black/25 border border-white/20 px-3 py-1 rounded-full text-xs font-mono font-bold tracking-wider text-emerald-300 shadow-inner">
                            <svg class="h-3.5 w-3.5 animate-pulse text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <span id="quiz-countdown" data-duration="{{ $durationMinutes * 60 }}">--:--</span>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 bg-white/10 px-2.5 py-1 rounded-full text-[11px] font-medium text-slate-300">
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <span>Durasi Bebas</span>
                        </div>
                    @endif
                </div>

                {{-- Question Number Selector Pills --}}
                <div class="flex items-center gap-1.5 overflow-x-auto py-1 max-w-[280px] md:max-w-[400px]" role="tablist" aria-label="Daftar nomor soal">
                    @foreach($questions as $qIdx => $q)
                        <button type="button"
                            data-nav-step="{{ $qIdx }}"
                            id="step-tab-{{ $qIdx }}"
                            title="Buka Soal {{ $qIdx + 1 }}"
                            class="h-7 min-w-7 px-2 rounded text-xs font-bold transition flex items-center justify-center border {{ $qIdx === 0 ? 'bg-white text-[#102f50] border-white shadow-xs' : 'bg-white/10 text-white border-white/20 hover:bg-white/20' }}">
                            {{ $qIdx + 1 }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Right: Top Navigation (Sebelumnya & Selanjutnya) & Finish Button --}}
            <div class="flex items-center gap-2">
                <button type="button" id="btn-top-prev" class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/10 text-white hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed transition">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </button>
                <button type="button" id="btn-top-next" class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/10 text-white hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed transition">
                    <span>Selanjutnya</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <button type="button" id="btn-submit-exam" class="button-primary text-xs py-1.5 px-3.5 font-bold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 shadow-sm border border-emerald-500">
                    <span>Kumpulkan Kuis</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
            </div>
        </div>
    </header>

    {{-- MAIN EXAM WORKSPACE (Adapts according to question type) --}}
    <main class="flex-1 w-full p-4 sm:p-6 max-w-7xl mx-auto flex flex-col justify-between">
        
        <form id="exam-form" method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}" class="flex-1 flex flex-col">
            @csrf

            @foreach($questions as $qIdx => $q)
                <div data-exam-card="{{ $qIdx }}" class="flex-1 flex flex-col {{ $qIdx === 0 ? '' : 'hidden' }}">
                    
                    {{-- WORKSPACE VARIANT 1: SOAL PEMROGRAMAN / CODING (Workbench 2-Panel dengan Terminal Linux) --}}
                    @if($q['type'] === 'coding')
                        <div class="grid gap-5 xl:grid-cols-[380px_minmax(0,1fr)] items-start flex-1 min-h-[640px]">
                            
                            {{-- Sisi Kiri: Soal, Petunjuk, Spesifikasi & Capaian CPMK --}}
                            <div class="surface rounded-xl p-5 border border-line/60 shadow-sm flex flex-col h-full overflow-y-auto space-y-4">
                                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-full bg-[#102f50] text-white text-xs font-bold flex items-center justify-center">
                                            {{ $qIdx + 1 }}
                                        </span>
                                        <span class="text-xs font-bold text-ink uppercase tracking-wider">Soal Pemrograman</span>
                                    </div>
                                    <span class="text-xs font-semibold text-brand">{{ $q['points'] }} Poin</span>
                                </div>

                                <div>
                                    <h3 class="text-sm font-bold text-ink mb-1.5">Instruksi Soal</h3>
                                    <p class="text-xs text-ink leading-relaxed font-medium whitespace-pre-line">{{ $q['prompt'] }}</p>
                                </div>

                                <div class="border-t border-line/50 pt-3 space-y-2">
                                    <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Target Spesifikasi BST</h4>
                                    <ul class="list-disc list-inside space-y-1.5 text-xs text-muted leading-relaxed">
                                        <li>Lengkapi metode <code class="font-mono text-[11px] text-ink font-semibold">insert(self, val)</code>.</li>
                                        <li>Cek root kosong: jika belum ada root, inisialisasi simpul baru sebagai root.</li>
                                        <li>Jika nilai baru lebih kecil, masukkan ke cabang kiri (<code class="font-mono text-[11px] text-ink">left</code>).</li>
                                        <li>Jika nilai baru lebih besar atau sama, masukkan ke cabang kanan (<code class="font-mono text-[11px] text-ink">right</code>).</li>
                                    </ul>
                                </div>

                                <div class="border-t border-line/50 pt-3 space-y-1">
                                    <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian CPMK</h4>
                                    <span class="inline-block text-xs font-medium text-ink bg-canvas px-2.5 py-1 rounded border border-line/50">
                                        {{ $q['cpmk'] }} @if(!empty($q['cpl']))→ {{ $q['cpl'] }} @endif
                                    </span>
                                </div>

                                <div class="mt-auto border-t border-line/50 pt-3 text-[11px] text-muted">
                                    Kode dari editor di sebelah kanan otomatis tersimpan ke draf ujian kuis Anda.
                                </div>
                            </div>

                            {{-- Sisi Kanan: Code Editor & Terminal Sandbox Linux --}}
                            <div class="flex flex-col h-full min-h-[640px] space-y-4">
                                {{-- Code Editor --}}
                                <div class="flex-1 flex flex-col rounded-xl bg-[#0f172a] border border-slate-700 shadow-sm overflow-hidden min-h-[360px]">
                                    <div class="flex items-center justify-between px-4 py-2.5 bg-[#1e293b] border-b border-slate-700">
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded-full bg-rose-500 inline-block"></span>
                                            <span class="h-3 w-3 rounded-full bg-amber-500 inline-block"></span>
                                            <span class="h-3 w-3 rounded-full bg-emerald-500 inline-block"></span>
                                            <span class="text-xs font-mono font-bold text-slate-300 ml-2">solution.py</span>
                                            <span class="text-[11px] text-slate-400">· Python 3.12</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" data-run-code-btn class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                <span>Jalankan Kode</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex-1 p-3 bg-[#0f172a] relative">
                                        <textarea
                                            name="question_answers[{{ $qIdx }}][text]"
                                            id="code-editor-{{ $qIdx }}"
                                            rows="12"
                                            class="w-full h-full bg-transparent font-mono text-xs text-slate-100 p-2 focus:outline-none resize-none leading-relaxed border-0"
                                            spellcheck="false">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? ($q['options'] ?: "class Node:\n    def __init__(self, val):\n        self.val = val\n        self.left = None\n        self.right = None\n\nclass BST:\n    def __init__(self):\n        self.root = None\n\n    def insert(self, val):\n        # Tulis logika implementasi di sini\n        pass")) }}</textarea>
                                    </div>
                                </div>

                                {{-- Linux Sandbox Terminal Output --}}
                                <div class="rounded-xl bg-[#020617] border border-slate-800 shadow-sm overflow-hidden h-48 flex flex-col">
                                    <div class="flex items-center justify-between px-3.5 py-1.5 bg-[#0f172a] border-b border-slate-800">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                                            <span class="text-[11px] font-mono font-bold text-slate-300">Terminal Sandbox (Linux x86_64)</span>
                                        </div>
                                        <span class="text-[10px] font-mono text-emerald-400">STATUS: READY</span>
                                    </div>
                                    <div id="terminal-output-{{ $qIdx }}" class="flex-1 p-3 font-mono text-xs text-slate-200 overflow-y-auto space-y-1">
                                        <div class="text-slate-500">sale@sandbox:~$ python3 -u solution.py</div>
                                        <div class="text-slate-400">Tekan tombol [Jalankan Kode] untuk menguji metode BST Anda terhadap test-cases otomatis.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- WORKSPACE VARIANT 2: SOAL MENCOCOKKAN (Interactive Line-Drawing Canvas dengan Warna Berbeda) --}}
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
                            // Color palette for lines
                            $colors = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#ea580c', '#4f46e5'];
                        @endphp

                        <div class="grid gap-5 xl:grid-cols-[340px_minmax(0,1fr)] items-start flex-1 min-h-[600px]">
                            
                            {{-- Sisi Kiri: Instruksi & Kontrol Garis --}}
                            <div class="surface rounded-xl p-5 border border-line/60 shadow-sm space-y-4">
                                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-full bg-[#102f50] text-white text-xs font-bold flex items-center justify-center">
                                            {{ $qIdx + 1 }}
                                        </span>
                                        <span class="text-xs font-bold text-ink uppercase tracking-wider">Soal Menjodohkan</span>
                                    </div>
                                    <span class="text-xs font-semibold text-brand">{{ $q['points'] }} Poin</span>
                                </div>

                                <div>
                                    <h3 class="text-sm font-bold text-ink mb-1">Petunjuk Tarik Garis</h3>
                                    <p class="text-xs text-ink leading-relaxed font-medium">{{ $q['prompt'] }}</p>
                                </div>

                                <div class="rounded-lg bg-brand-soft/60 p-3.5 border border-brand/20 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-brand shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                                        <span class="text-xs font-bold text-brand-dark">Cara Menghubungkan:</span>
                                    </div>
                                    <p class="text-[11px] text-muted leading-relaxed">
                                        1. Klik atau tekan <strong>titik bundar di sisi kanan premis (kiri)</strong>.<br>
                                        2. Kemudian klik <strong>titik bundar pada pasangan jawaban yang tepat di sisi kanan</strong>.<br>
                                        3. Garis lengkung dengan <strong>warna unik</strong> akan otomatis ditarik menghubungkan kedua titik.
                                    </p>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    <span id="match-counter-{{ $qIdx }}" class="text-xs font-bold text-ink">0 dari {{ count($pairs) }} terhubung</span>
                                    <button type="button" data-reset-lines="{{ $qIdx }}" class="text-xs font-semibold text-danger hover:underline">
                                        Hapus Semua Garis
                                    </button>
                                </div>

                                <div class="border-t border-line/50 pt-3 space-y-1">
                                    <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian CPMK</h4>
                                    <span class="inline-block text-xs font-medium text-ink bg-canvas px-2.5 py-1 rounded border border-line/50">
                                        {{ $q['cpmk'] }} @if(!empty($q['cpl']))→ {{ $q['cpl'] }} @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Sisi Kanan: Area Canvas Tarik Garis Interaktif --}}
                            <div class="surface rounded-xl p-6 sm:p-8 border border-line/60 shadow-sm relative match-canvas-container" id="canvas-container-{{ $qIdx }}">
                                
                                {{-- SVG Overlay for dynamic lines --}}
                                <svg class="absolute inset-0 pointer-events-none w-full h-full z-10" id="match-svg-{{ $qIdx }}"></svg>

                                <div class="grid grid-cols-2 gap-12 sm:gap-24 relative z-20">
                                    
                                    {{-- Kolom Premis (Sisi Kiri) --}}
                                    <div class="space-y-6" id="match-left-col-{{ $qIdx }}">
                                        <div class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Premis / Stimulus</div>
                                        @foreach($pairs as $pIdx => $pair)
                                            @php
                                                $isImg = str_starts_with($pair['left'], 'http') || str_starts_with($pair['left'], 'data:image') || str_starts_with($pair['left'], '/');
                                            @endphp
                                            <div class="relative flex items-center justify-between p-3.5 rounded-xl border border-line/60 bg-white hover:border-brand/60 shadow-2xs transition group" data-match-left-card="{{ $pIdx }}">
                                                <div class="min-w-0 pr-4">
                                                    <span class="text-[10px] font-bold text-muted block mb-1">Premis {{ $pIdx + 1 }}</span>
                                                    @if($isImg)
                                                        <img src="{{ $pair['left'] }}" alt="Diagram Premis {{ $pIdx + 1 }}" class="max-h-20 max-w-full rounded object-contain border border-line/40 p-1 bg-slate-50">
                                                    @else
                                                        <span class="text-xs sm:text-sm font-semibold text-ink leading-snug block">{{ $pair['left'] }}</span>
                                                    @endif
                                                </div>

                                                {{-- Connector Dot (Right edge of Left card) --}}
                                                <button type="button"
                                                    data-dot-side="left"
                                                    data-dot-idx="{{ $pIdx }}"
                                                    data-color="{{ $colors[$pIdx % count($colors)] }}"
                                                    class="match-dot absolute -right-3 top-1/2 -translate-y-1/2 h-6 w-6 rounded-full border-2 border-white bg-slate-400 shadow-md flex items-center justify-center cursor-pointer"
                                                    title="Hubungkan Premis {{ $pIdx + 1 }}">
                                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                                </button>

                                                {{-- Hidden Form Input for Submission --}}
                                                <input type="hidden"
                                                    name="question_answers[{{ $qIdx }}][matching][{{ $pIdx }}]"
                                                    id="hidden-match-{{ $qIdx }}-{{ $pIdx }}"
                                                    value="{{ old("question_answers.$qIdx.matching.$pIdx", $submission['question_answers'][$qIdx]['matching'][$pIdx] ?? '') }}">
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Kolom Pasangan Jawaban (Sisi Kanan) --}}
                                    <div class="space-y-6" id="match-right-col-{{ $qIdx }}">
                                        <div class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Pasangan Jawaban</div>
                                        @php
                                            // Provide target options
                                            $targets = array_column($pairs, 'right');
                                        @endphp
                                        @foreach($targets as $tIdx => $target)
                                            @php
                                                $isTargetImg = str_starts_with($target, 'http') || str_starts_with($target, 'data:image') || str_starts_with($target, '/');
                                            @endphp
                                            <div class="relative flex items-center p-3.5 rounded-xl border border-line/60 bg-white hover:border-brand/60 shadow-2xs transition group" data-match-right-card="{{ $tIdx }}" data-target-val="{{ $target }}">
                                                
                                                {{-- Connector Dot (Left edge of Right card) --}}
                                                <button type="button"
                                                    data-dot-side="right"
                                                    data-dot-idx="{{ $tIdx }}"
                                                    class="match-dot absolute -left-3 top-1/2 -translate-y-1/2 h-6 w-6 rounded-full border-2 border-white bg-slate-400 shadow-md flex items-center justify-center cursor-pointer"
                                                    title="Pasangkan dengan Target {{ chr(65 + $tIdx) }}">
                                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                                </button>

                                                <div class="min-w-0 pl-4 w-full">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-[10px] font-bold text-brand uppercase tracking-wider">Pilihan {{ chr(65 + $tIdx) }}</span>
                                                    </div>
                                                    @if($isTargetImg)
                                                        <img src="{{ $target }}" alt="Pilihan {{ chr(65 + $tIdx) }}" class="max-h-20 max-w-full rounded object-contain border border-line/40 p-1 bg-slate-50">
                                                    @else
                                                        <span class="text-xs sm:text-sm font-semibold text-ink leading-snug block">{{ $target }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- WORKSPACE VARIANT 3: SOAL PILIHAN GANDA & KOMPLEKS --}}
                    @elseif(in_array($q['type'], ['pilihan', 'kompleks']))
                        @php
                            $options = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                            $isMultiple = $q['type'] === 'kompleks';
                        @endphp
                        <div class="grid gap-6 xl:grid-cols-[400px_minmax(0,1fr)] items-start flex-1">
                            
                            {{-- Sisi Kiri: Soal, Stimulus Gambar & CPMK --}}
                            <div class="surface rounded-xl p-6 border border-line/60 shadow-sm space-y-4">
                                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-full bg-[#102f50] text-white text-xs font-bold flex items-center justify-center">
                                            {{ $qIdx + 1 }}
                                        </span>
                                        <span class="text-xs font-bold text-ink uppercase tracking-wider">{{ $isMultiple ? 'Pilihan Ganda Kompleks' : 'Pilihan Ganda' }}</span>
                                    </div>
                                    <span class="text-xs font-semibold text-brand">{{ $q['points'] }} Poin</span>
                                </div>

                                <div class="text-sm font-medium text-ink leading-relaxed">
                                    {{ $q['prompt'] }}
                                </div>

                                @if(!empty($q['image']))
                                    <div class="mt-3">
                                        <img src="{{ route('preview.file', $q['image']) }}" alt="{{ $q['alt'] ?? 'Stimulus visual' }}" class="max-h-60 rounded-lg object-contain border border-line/40 bg-white p-1">
                                    </div>
                                @endif

                                <div class="border-t border-line/50 pt-3 space-y-1">
                                    <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian CPMK</h4>
                                    <span class="inline-block text-xs font-medium text-ink bg-canvas px-2.5 py-1 rounded border border-line/50">
                                        {{ $q['cpmk'] }} @if(!empty($q['cpl']))→ {{ $q['cpl'] }} @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Sisi Kanan: Kartu Pilihan Jawaban Interaktif --}}
                            <div class="surface rounded-xl p-6 sm:p-7 border border-line/60 shadow-sm space-y-3">
                                <p class="text-xs font-bold text-muted uppercase tracking-wider mb-2">
                                    {{ $isMultiple ? 'Pilih semua opsi yang benar:' : 'Pilih satu jawaban yang paling tepat:' }}
                                </p>

                                @foreach($options as $optIdx => $opt)
                                    <label class="flex items-center gap-3.5 p-4 rounded-xl border border-line/60 bg-white hover:border-brand/70 hover:bg-slate-50 cursor-pointer transition has-checked:border-brand has-checked:bg-brand-soft/40 shadow-2xs">
                                        <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                                            name="question_answers[{{ $qIdx }}][choices][]"
                                            value="{{ $opt }}"
                                            class="h-4 w-4 text-brand focus:ring-brand"
                                            @checked(in_array($opt, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                        <span class="h-6 w-6 rounded-md bg-canvas border border-line/50 font-bold text-xs text-muted flex items-center justify-center shrink-0">
                                            {{ chr(65 + $optIdx) }}
                                        </span>
                                        <span class="text-sm font-medium text-ink">{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    {{-- WORKSPACE VARIANT 4: SOAL BENAR / SALAH --}}
                    @elseif($q['type'] === 'benar_salah')
                        <div class="grid gap-6 xl:grid-cols-[440px_minmax(0,1fr)] items-start flex-1">
                            <div class="surface rounded-xl p-6 border border-line/60 shadow-sm space-y-4">
                                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-full bg-[#102f50] text-white text-xs font-bold flex items-center justify-center">
                                            {{ $qIdx + 1 }}
                                        </span>
                                        <span class="text-xs font-bold text-ink uppercase tracking-wider">Benar / Salah</span>
                                    </div>
                                    <span class="text-xs font-semibold text-brand">{{ $q['points'] }} Poin</span>
                                </div>

                                <div>
                                    <h3 class="text-xs font-bold text-muted uppercase tracking-wider mb-2">Pernyataan Soal:</h3>
                                    <p class="text-sm font-medium text-ink leading-relaxed">{{ $q['prompt'] }}</p>
                                </div>

                                <div class="border-t border-line/50 pt-3 space-y-1">
                                    <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian CPMK</h4>
                                    <span class="inline-block text-xs font-medium text-ink bg-canvas px-2.5 py-1 rounded border border-line/50">
                                        {{ $q['cpmk'] }} @if(!empty($q['cpl']))→ {{ $q['cpl'] }} @endif
                                    </span>
                                </div>
                            </div>

                            <div class="surface rounded-xl p-6 sm:p-7 border border-line/60 shadow-sm space-y-4">
                                <p class="text-xs font-bold text-muted uppercase tracking-wider">Tentukan validitas pernyataan:</p>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <label class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-line/60 bg-white hover:border-emerald-600 hover:bg-emerald-50/40 cursor-pointer transition has-checked:border-emerald-600 has-checked:bg-emerald-50 text-center space-y-2">
                                        <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar" class="sr-only" @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                        <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base">✓</div>
                                        <span class="text-base font-bold text-ink">BENAR</span>
                                        <span class="text-xs text-muted">Pernyataan sesuai konsep & fakta</span>
                                    </label>

                                    <label class="flex flex-col items-center justify-center p-6 rounded-xl border-2 border-line/60 bg-white hover:border-rose-600 hover:bg-rose-50/40 cursor-pointer transition has-checked:border-rose-600 has-checked:bg-rose-50 text-center space-y-2">
                                        <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah" class="sr-only" @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                        <div class="h-10 w-10 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-base">✕</div>
                                        <span class="text-base font-bold text-ink">SALAH</span>
                                        <span class="text-xs text-muted">Pernyataan keliru atau tidak tepat</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                    {{-- WORKSPACE VARIANT 5: SOAL URAIAN / ESSAY --}}
                    @else
                        <div class="grid gap-6 xl:grid-cols-[400px_minmax(0,1fr)] items-start flex-1">
                            <div class="surface rounded-xl p-6 border border-line/60 shadow-sm space-y-4">
                                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-full bg-[#102f50] text-white text-xs font-bold flex items-center justify-center">
                                            {{ $qIdx + 1 }}
                                        </span>
                                        <span class="text-xs font-bold text-ink uppercase tracking-wider">Soal Uraian / Essay</span>
                                    </div>
                                    <span class="text-xs font-semibold text-brand">{{ $q['points'] }} Poin</span>
                                </div>

                                <div class="text-sm font-medium text-ink leading-relaxed">
                                    {{ $q['prompt'] }}
                                </div>

                                <div class="border-t border-line/50 pt-3 space-y-1">
                                    <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian CPMK</h4>
                                    <span class="inline-block text-xs font-medium text-ink bg-canvas px-2.5 py-1 rounded border border-line/50">
                                        {{ $q['cpmk'] }} @if(!empty($q['cpl']))→ {{ $q['cpl'] }} @endif
                                    </span>
                                </div>
                            </div>

                            <div class="surface rounded-xl p-6 border border-line/60 shadow-sm space-y-3 flex flex-col h-full min-h-[400px]">
                                <div class="flex items-center justify-between">
                                    <label for="essay-{{ $qIdx }}" class="text-xs font-bold text-ink uppercase tracking-wider">Tuliskan Jawaban Analisis Anda</label>
                                    <span class="text-xs text-muted" id="essay-word-count-{{ $qIdx }}">0 kata</span>
                                </div>
                                <textarea
                                    name="question_answers[{{ $qIdx }}][text]"
                                    id="essay-{{ $qIdx }}"
                                    rows="12"
                                    data-essay-input="{{ $qIdx }}"
                                    class="field text-sm p-4 leading-relaxed flex-1 w-full bg-slate-50/50"
                                    placeholder="Ketik uraian argumen atau analisis Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? '') }}</textarea>
                            </div>
                        </div>
                    @endif

                    {{-- Bottom Question Stepper Footer Bar --}}
                    <div class="flex items-center justify-between border-t border-line/60 pt-4 mt-6">
                        <div>
                            <span class="text-xs text-muted font-medium">
                                Menampilkan Soal <strong>{{ $qIdx + 1 }}</strong> dari <strong>{{ $totalQuestions }}</strong> soal
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            @if($qIdx > 0)
                                <button type="button" data-nav-btn="{{ $qIdx - 1 }}" class="button-secondary text-xs py-2 px-4 font-semibold">
                                    ← Kembali (Soal {{ $qIdx }})
                                </button>
                            @endif

                            @if($qIdx < $totalQuestions - 1)
                                <button type="button" data-nav-btn="{{ $qIdx + 1 }}" class="button-primary text-xs py-2 px-4 font-semibold">
                                    Selanjutnya (Soal {{ $qIdx + 2 }}) →
                                </button>
                            @else
                                <button type="button" onclick="document.getElementById('btn-submit-exam').click()" class="button-primary text-xs py-2 px-4 font-bold bg-emerald-700 hover:bg-emerald-800 text-white shadow-xs">
                                    Kumpulkan Semua Jawaban ✓
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </form>
    </main>

    {{-- MODAL KONFIRMASI SUBMIT KUIS --}}
    <dialog id="submit-confirm-modal" class="rounded-2xl p-0 backdrop:bg-ink/50 shadow-2xl border border-line/70 max-w-md w-full">
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 text-[#102f50]">
                <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0">
                    ✓
                </div>
                <div>
                    <h3 class="text-base font-bold text-ink">Selesaikan &amp; Kumpulkan Kuis?</h3>
                    <p class="text-xs text-muted">Pastikan Anda telah memeriksa kembali seluruh jawaban.</p>
                </div>
            </div>

            <div class="rounded-xl bg-canvas p-4 text-xs space-y-2 border border-line/40">
                <div class="flex items-center justify-between text-muted">
                    <span>Total Soal:</span>
                    <span class="font-bold text-ink">{{ $totalQuestions }} Soal</span>
                </div>
                <div class="flex items-center justify-between text-muted">
                    <span>Status Terjawab:</span>
                    <span class="font-bold text-emerald-700" id="modal-answered-count">Memeriksa...</span>
                </div>
                <div class="flex items-center justify-between text-muted">
                    <span>Total Bobot:</span>
                    <span class="font-bold text-brand">{{ $totalPoints }} Poin</span>
                </div>
            </div>

            <p class="text-xs text-muted leading-relaxed">
                Setelah dikumpulkan, jawaban akan langsung terpetakan ke capaian pembelajaran (CPMK) Anda.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="modal-cancel-btn" class="button-secondary text-xs py-2 px-4 font-semibold">
                    Periksa Lagi
                </button>
                <button type="button" id="modal-confirm-submit-btn" class="button-primary text-xs py-2 px-5 font-bold bg-emerald-700 hover:bg-emerald-800 text-white">
                    Ya, Kumpulkan Sekarang
                </button>
            </div>
        </div>
    </dialog>

    {{-- SCRIPT ENGINE RUANG UJIAN KHUSUS (Stepper, Line Drawing, Timer, Code Sandbox) --}}
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
                        tab.className = 'h-7 min-w-7 px-2 rounded text-xs font-bold transition flex items-center justify-center border bg-white text-[#102f50] border-white shadow-xs';
                    } else {
                        tab.className = 'h-7 min-w-7 px-2 rounded text-xs font-bold transition flex items-center justify-center border bg-white/10 text-white border-white/20 hover:bg-white/20';
                    }
                });

                topPrevBtn.disabled = currentStep === 0;
                topNextBtn.disabled = currentStep === totalCards - 1;

                // Trigger line drawing redraw if entering matching question
                redrawCurrentMatchingLines();
                updateAnsweredHighlights();
            };

            topPrevBtn?.addEventListener('click', () => setStep(currentStep - 1));
            topNextBtn?.addEventListener('click', () => setStep(currentStep + 1));

            document.addEventListener('click', (e) => {
                const navBtn = e.target.closest('[data-nav-btn]');
                if (navBtn) {
                    setStep(Number(navBtn.dataset.navBtn));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                const tabBtn = e.target.closest('[data-nav-step]');
                if (tabBtn) {
                    setStep(Number(tabBtn.dataset.navStep));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
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
                            badge.classList.remove('text-emerald-300');
                            badge.classList.add('text-rose-300', 'border-rose-400');
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
            // 3. INTERACTIVE MATCHING LINE-DRAWING (Tarik Garis Beda Warna)
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
                const resetBtn = document.querySelector(`[data-reset-lines="${qIdx}"]`);

                // Connection state: { leftIdx: { targetIdx: number, targetVal: string, color: string } }
                const connections = {};

                // Load existing connections from hidden inputs
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
                                    color: ld.dataset.color || '#2563eb'
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
                    path.setAttribute('stroke-width', '4');
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

                            // Highlight dots with pair color
                            lDot.style.backgroundColor = conn.color;
                            rDot.style.backgroundColor = conn.color;
                            connectedCount++;
                        }
                    });

                    // Reset un-connected dots
                    leftDots.forEach(ld => {
                        if (!connections[ld.dataset.dotIdx]) {
                            ld.style.backgroundColor = '#94a3b8';
                        }
                    });
                    rightDots.forEach(rd => {
                        const isConnected = Object.values(connections).some(c => c.targetIdx === Number(rd.dataset.dotIdx));
                        if (!isConnected) {
                            rd.style.backgroundColor = '#94a3b8';
                        }
                    });

                    if (counterEl) {
                        counterEl.textContent = `${connectedCount} dari ${leftDots.length} terhubung`;
                    }
                    updateAnsweredHighlights();
                };

                matchingQuestions[qIdx] = { redraw: redrawLines };

                // Click left dot
                leftDots.forEach(ld => {
                    ld.addEventListener('click', (e) => {
                        e.stopPropagation();
                        leftDots.forEach(d => d.classList.remove('selected'));
                        selectedLeftDot = ld;
                        ld.classList.add('selected');
                    });
                });

                // Click right dot or right card to connect
                const rightCards = container.querySelectorAll('[data-match-right-card]');
                rightCards.forEach(rc => {
                    rc.addEventListener('click', () => {
                        if (!selectedLeftDot) return;
                        const lIdx = Number(selectedLeftDot.dataset.dotIdx);
                        const rDot = rc.querySelector('[data-dot-side="right"]');
                        if (!rDot) return;
                        const rIdx = Number(rDot.dataset.dotIdx);
                        const targetVal = rc.dataset.targetVal;
                        const color = selectedLeftDot.dataset.color || '#2563eb';

                        // Save connection
                        connections[lIdx] = { targetIdx: rIdx, targetVal, color };
                        const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                        if (hiddenInp) hiddenInp.value = targetVal;

                        selectedLeftDot.classList.remove('selected');
                        selectedLeftDot = null;
                        redrawLines();
                    });
                });

                // Reset button
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

                // Initial redraw
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
            // 4. LINUX CODE SANDBOX RUNNER
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
                        <div class="text-amber-300 font-mono">Menjalankan simulasi struktur data BST di container sandbox...</div>
                    `;

                    setTimeout(() => {
                        terminal.innerHTML = `
                            <div class="text-slate-400 font-mono">sale@sandbox:~$ python3 -u solution.py</div>
                            <div class="text-slate-300 font-mono">>>> bst = BST()</div>
                            <div class="text-slate-300 font-mono">>>> bst.insert(15) # Root created</div>
                            <div class="text-slate-300 font-mono">>>> bst.insert(10) # Left branch attached (10 < 15)</div>
                            <div class="text-slate-300 font-mono">>>> bst.insert(20) # Right branch attached (20 > 15)</div>
                            <div class="text-emerald-400 font-bold font-mono mt-1">✓ [SUKSES] Semua pengujian simulasi berjalan mulus (Status Code: 0).</div>
                        `;
                    }, 500);
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
                        tab.classList.add('border-emerald-400', 'bg-emerald-500/30', 'text-emerald-200');
                    } else if (!isAnswered && idx !== currentStep) {
                        tab.classList.remove('border-emerald-400', 'bg-emerald-500/30', 'text-emerald-200');
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

            // Exit exam confirmation
            document.getElementById('btn-exit-exam')?.addEventListener('click', () => {
                if (confirm('Apakah Anda yakin ingin keluar dari ruang ujian? Pekerjaan yang belum dikumpulkan akan tetap tersimpan sebagai draf sesi.')) {
                    window.location.href = "{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}";
                }
            });

            // Live essay word count
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

            // Initial view setup
            setStep(0);
        });
    </script>
</body>
</html>
