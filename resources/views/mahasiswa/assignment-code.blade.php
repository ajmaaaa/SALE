<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f5f7">
    <title>{{ $item['title'] }} | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --workbench-left-width: 340px;
            --workbench-right-width: 340px;
        }
        #workbench-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 85px);
            min-height: 640px;
            position: relative;
        }
        @media (max-width: 1279.98px) {
            #panel-question,
            #panel-ai {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
        }
        @media (min-width: 1280px) {
            #workbench-container {
                flex-direction: row !important;
                align-items: stretch !important;
                gap: 0 !important;
            }
            #panel-question {
                width: var(--workbench-left-width, 340px) !important;
                min-width: 240px !important;
                max-width: 600px !important;
                flex-shrink: 0 !important;
            }
            #panel-editor {
                flex: 1 1 0% !important;
                min-width: 320px !important;
                width: auto !important;
            }
            #panel-ai {
                width: var(--workbench-right-width, 340px) !important;
                min-width: 260px !important;
                max-width: 600px !important;
                flex-shrink: 0 !important;
            }
        }
        #workbench-container,
        #panel-question,
        #panel-editor,
        #panel-ai,
        .code-editor,
        .cm-editor {
            transition: none !important;
            animation: none !important;
        }
    </style>
    <script>
        (function() {
            try {
                if (window.innerWidth >= 1280) {
                    var lw = localStorage.getItem('sale.workbench.leftWidth');
                    var rw = localStorage.getItem('sale.workbench.rightWidth');
                    if (lw) {
                        var w = Math.max(220, Math.min(600, parseInt(lw, 10)));
                        if (!isNaN(w)) document.documentElement.style.setProperty('--workbench-left-width', w + 'px');
                    }
                    if (rw) {
                        var w = Math.max(250, Math.min(600, parseInt(rw, 10)));
                        if (!isNaN(w)) document.documentElement.style.setProperty('--workbench-right-width', w + 'px');
                    }
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    @php
    $currentRole = auth()->user()?->role?->name ?? (session('auth_user.role') ?? (request()->routeIs('dosen.*') ? 'dosen' : 'mahasiswa'));
    $isLecturer = in_array($currentRole, ['dosen', 'kaprodi'], true) || request()->routeIs('dosen.*');
    if ($currentRole === 'mahasiswa' || session('auth_user.role') === 'mahasiswa') {
        $isLecturer = false;
    }
    $storedLanguage = $item['language'] ?? 'python';
    $requestedLanguage = request()->query('language');
    $language = in_array($requestedLanguage, ['python', 'web'], true) ? $requestedLanguage : $storedLanguage;
    if ($language === 'web') {
        $defaultFiles = [[
            'name' => 'index.html',
            'code' => '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pratinjau</title>
    <style>
        /* Tulis CSS di sini */
        body { font-family: sans-serif; margin: 2rem; }
    </style>
</head>
<body>
    <h1>Halo SALE</h1>
    <script>
        // Tulis JavaScript di sini
        console.log(\'Pratinjau siap.\');
    </script>
</body>
</html>',
        ]];
    } elseif ($item['id'] === 1) {
        $defaultFiles = [[
            'name' => 'main.py',
            'code' => 'class Node:
    def __init__(self, key):
        self.left = None
        self.right = None
        self.value = key


class BinaryTree:
    def insert(self, root, key):
        if root is None:
            return Node(key)

        # Lengkapi logika insert di sini

        return root',
        ]];
    } else {
        $defaultFiles = [['name' => 'main.py', 'code' => '# Tulis jawaban Python kamu di sini']];
    }
@endphp
    <header class="sticky top-0 z-30 bg-white shadow-[0_1px_3px_rgba(29,39,48,0.06)] border-b border-line/60">
        <div class="flex min-h-14 w-full flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.show', $course['id']) }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink hover:bg-slate-100 transition" aria-label="Kembali ke Halaman Course">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold text-ink">{{ $item['title'] }}</h1>
                    <p class="truncate text-xs text-muted">{{ $course['code'] }} - {{ $course['title'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($isLecturer)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-200">
                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Mode Tinjau Dosen
                    </span>
                @else
                    <div class="flex items-center rounded-lg border border-line bg-slate-50 p-1" aria-label="Pilih lingkungan pemrograman">
                        <a href="{{ route('mahasiswa.assignment.code', ['assignment' => $item['id'], 'language' => 'python']) }}" class="rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $language === 'python' ? 'bg-white text-brand shadow-sm' : 'text-muted hover:text-ink' }}">Python</a>
                        <a href="{{ route('mahasiswa.assignment.code', ['assignment' => $item['id'], 'language' => 'web']) }}" class="rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $language === 'web' ? 'bg-white text-brand shadow-sm' : 'text-muted hover:text-ink' }}">Web</a>
                    </div>
                    <form data-code-submit method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}">
                        @csrf
                        <input type="hidden" name="answer" data-code-answer>
                        <button disabled class="button-primary text-xs py-2 px-4 font-bold">
                            Kumpulkan Kode
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main class="w-full p-4 sm:p-5">
        @if($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-danger bg-white p-3 text-xs text-danger">{{ $errors->first() }}</div>
        @endif

        {{-- 3-Panel Workbench: Left (Soal & Instruksi), Center (Code Editor & Linux Terminal), Right (Lumina AI Assistant) --}}
        <div id="workbench-container" class="flex flex-col xl:flex-row items-stretch gap-3 xl:gap-0 h-[calc(100vh-85px)] min-h-[640px] relative">

            {{-- PANEL 1 (KIRI): Soal & Capaian Pembelajaran ("soalnya di kiri") --}}
            <section id="panel-question" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-y-auto p-5 shadow-sm border border-line/60 space-y-5 transition-none" style="width: var(--workbench-left-width, 340px); min-width: 240px; max-width: 600px;" aria-labelledby="question-heading">
                <div>
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider">Praktikum Coding</span>
                    <h2 id="question-heading" class="text-base font-bold text-ink mt-1">{{ $item['title'] }}</h2>
                    <p class="text-xs text-muted mt-0.5">{{ $item['module'] }} ({{ $course['lecturer'] }})</p>
                </div>

                <div class="border-t border-line/60 pt-4">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-ink">Petunjuk Pengerjaan</h3>
                    <p class="whitespace-pre-line text-xs font-medium leading-relaxed text-ink">{{ $item['body'] }}</p>
                </div>

                @if(!empty($item['coding_steps']))
                    <div class="border-t border-line/60 pt-4" data-code-steps>
                        <div class="mb-3 flex flex-wrap gap-1" data-code-step-tabs></div>
                        @foreach($item['coding_steps'] as $index => $step)
                            <article data-code-step class="space-y-3" @if($index !== 0) hidden @endif>
                                <div><p class="text-[10px] font-bold uppercase tracking-wider text-brand">Tahap {{ $index + 1 }}</p><h3 class="mt-1 text-sm font-bold text-ink">{{ $step['title'] }}</h3></div>
                                <p class="whitespace-pre-line text-xs leading-relaxed text-ink">{{ $step['body'] }}</p>
                                @if(!empty($step['attachment']))
                                    @php($stepFile = session('learning.files.'.$step['attachment']))
                                    @if(str_starts_with($stepFile['mime'] ?? '', 'image/'))
                                        <img class="max-h-44 w-full rounded-lg border border-line/60 object-contain" src="{{ route('preview.file', $step['attachment']) }}" alt="Lampiran {{ $step['title'] }}">
                                    @else
                                        <a class="button-secondary flex w-full items-center justify-center px-3 py-2 text-xs" href="{{ route('preview.file', $step['attachment']) }}">Buka lampiran{{ !empty($stepFile['name']) ? ': '.$stepFile['name'] : '' }}</a>
                                    @endif
                                @endif
                                @if(!empty($step['link']))<a class="quiet-link inline-flex text-xs" href="{{ $step['link'] }}" target="_blank" rel="noopener">Buka tautan pendukung ↗</a>@endif
                                <p class="text-[11px] font-semibold text-muted">Target {{ $step['cpmk'] }}</p>
                            </article>
                        @endforeach
                        <div class="mt-4 flex justify-between gap-2"><button type="button" class="button-secondary px-2.5 py-1.5 text-[11px]" data-code-step-prev>← Sebelumnya</button><button type="button" class="button-secondary px-2.5 py-1.5 text-[11px]" data-code-step-next>Selanjutnya →</button></div>
                    </div>
                @elseif($item['id'] === 1)
                <div class="border-t border-line/60 pt-4 space-y-2 text-xs">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Target Spesifikasi</h3>
                    <ul class="list-disc list-inside space-y-1.5 text-muted text-xs leading-relaxed">
                        <li>Implementasi metode <code class="font-mono text-[11px] text-ink font-semibold">insert(self, root, key)</code>.</li>
                        <li>Periksa kondisi pohon kosong: <code class="font-mono text-[11px] text-ink">root is None</code>.</li>
                        <li>Jika <code class="font-mono text-[11px] text-ink">key &lt; root.value</code>: rekursi ke anak kiri.</li>
                        <li>Jika <code class="font-mono text-[11px] text-ink">key &gt; root.value</code>: rekursi ke anak kanan.</li>
                        <li>Selalu kembalikan simpul <code class="font-mono text-[11px] text-ink">root</code> setelah modifikasi.</li>
                    </ul>
                </div>
                @endif

                <div class="border-t border-line/60 pt-4 space-y-1.5 text-xs">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian Pembelajaran (CPMK)</h3>
                    <p class="text-xs text-muted leading-relaxed">{{ $item['cpmk'] }}</p>
                </div>
            </section>

            {{-- Handle Geser Kiri (Soal <-> Editor) --}}
            <div data-resizer="left" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar soal">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 2 (TENGAH): Code Editor & Linux Sandbox Terminal --}}
            <div id="panel-editor" class="flex-1 min-w-[320px] flex flex-col h-full overflow-hidden transition-none">
                {{-- Editor Section --}}
                <section class="flex-1 flex flex-col min-h-0 rounded-xl bg-white shadow-sm border border-line/60 overflow-hidden" aria-labelledby="editor-heading">
                    {{-- Single Integrated Toolbar: Tab Berkas di kiri, Aksi & Terminal Toggle di kanan --}}
                    <div class="flex items-center justify-between border-b border-line/60 bg-slate-50 px-2.5 py-1.5 gap-2 select-none">
                        {{-- File Tabs (Kiri) --}}
                        <div data-file-tabs class="flex items-center gap-1 overflow-x-auto min-w-0" role="tablist" aria-label="Berkas kode"></div>

                        {{-- Action Buttons (Kanan): Tanyakan Baris | Terminal (Icon) | Play (Icon) --}}
                        <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                            <button type="button" data-mention-code disabled class="h-8 !min-h-0 px-3 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white text-xs font-semibold text-ink transition hover:border-ink hover:bg-slate-50 disabled:opacity-40 shadow-2xs leading-none" title="Tanyakan baris kode terpilih ke Lumina AI">
                                Tanyakan Baris
                            </button>
                            <button type="button" data-terminal-toggle class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white hover:bg-slate-50 text-slate-700 transition hover:border-ink shadow-2xs leading-none" title="Buka / Tutup Terminal" aria-label="Terminal">
                                <svg class="h-3.5 w-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                            </button>
                            <button type="button" disabled data-run-code class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition disabled:opacity-40 leading-none" title="Jalankan kode" aria-label="Jalankan kode">
                                <svg class="h-3.5 w-3.5 fill-current text-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <textarea data-code-files-json class="hidden" aria-hidden="true">@json($defaultFiles)</textarea>
                    <div data-code-editor data-assignment-id="{{ $item['id'] }}" data-runtime-url="{{ asset('vendor/pyodide') }}/" data-code-language="{{ $language }}" data-max-files="5" data-max-file-chars="8000" data-max-total-chars="20000" class="code-editor flex-1 h-full overflow-auto bg-[#282c34]" aria-label="Editor kode {{ $language === 'web' ? 'HTML/CSS/JS' : 'Python' }}"></div>
                    <div class="flex items-center justify-between gap-3 px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4]">
                        <span data-code-save-status>Draf tersimpan di browser</span>
                        <span class="flex items-center gap-3">
                            <span data-chars-count></span>
                            <span>UTF-8, 4 Spasi</span>
                        </span>
                    </div>
                </section>

                {{-- Terminal Wrapper: Resizer di atas + Terminal Panel (On-demand) --}}
                <div id="terminal-wrapper" class="flex flex-col shrink-0 mt-2" hidden>
                    {{-- Resizer Handle Tinggi Terminal --}}
                    <div data-resizer="terminal" class="h-3 w-full shrink-0 cursor-row-resize flex items-center justify-center bg-slate-200/80 hover:bg-brand/30 group transition-colors rounded-t-lg select-none" title="Geser ke atas/bawah untuk mengatur tinggi terminal">
                        <div class="h-1 w-14 rounded-full bg-slate-400 group-hover:bg-brand transition-colors"></div>
                    </div>

                    <section id="panel-terminal" class="flex flex-col rounded-b-xl bg-[#0d1117] text-[#c9d1d9] shadow-sm border border-line/60 overflow-hidden" style="height: 240px; min-height: 120px;" aria-labelledby="terminal-heading">
                        <div class="flex items-center justify-between px-4 py-2 bg-[#161b22] border-b border-white/10 select-none">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" data-terminal-close class="h-3 w-3 rounded-full bg-[#ff5f56] hover:opacity-80 transition inline-block shadow-xs" title="Tutup Terminal" aria-label="Tutup Terminal"></button>
                                    <button type="button" data-terminal-minimize class="h-3 w-3 rounded-full bg-[#ffbd2e] hover:opacity-80 transition inline-block shadow-xs" title="Perkecil Terminal" aria-label="Perkecil Terminal"></button>
                                    <button type="button" data-terminal-maximize class="h-3 w-3 rounded-full bg-[#27c93f] hover:opacity-80 transition inline-block shadow-xs" title="Perbesar Terminal" aria-label="Perbesar Terminal"></button>
                                </div>
                                <span class="text-xs font-mono text-slate-300 font-medium">{{ $language === 'web' ? 'Output Web / Pratinjau Browser' : 'Output Python / Terminal' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" data-stop-code hidden class="text-xs text-rose-300 px-2 py-1">Hentikan</button>
                                <button type="button" data-clear-terminal class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition">
                                    Bersihkan
                                </button>
                                <button type="button" data-terminal-close-btn class="text-xs text-slate-400 hover:text-white px-2 py-0.5 rounded hover:bg-white/10 transition" title="Tutup Terminal">
                                    ✕
                                </button>
                            </div>
                        </div>

                        {{-- Output Tabs --}}
                        <div class="flex items-end gap-1 px-3 pt-1.5 bg-[#0d1117] border-b border-white/10" role="tablist" aria-label="Panel output">
                            <button type="button" role="tab" aria-selected="true" data-output-tab="console" class="rounded-t-md px-3 py-1 font-mono text-[11px] border border-b-0 border-white/10 bg-white/10 text-white">Konsol</button>
                            <button type="button" role="tab" aria-selected="false" data-output-tab="preview" class="rounded-t-md px-3 py-1 font-mono text-[11px] border border-b-0 border-transparent text-slate-400 hover:text-slate-200">Pratinjau</button>
                        </div>

                        {{-- Console Panel --}}
                        <div data-console-panel class="flex-1 flex flex-col min-h-0">
                            <div data-terminal-body class="flex-1 overflow-y-auto p-3.5 font-mono text-xs leading-relaxed space-y-1 select-text bg-[#0d1117]">
                                <p class="text-slate-500">Jalankan Kode untuk melihat output program (Python di tab Konsol, Web di tab Pratinjau).</p>
                                <div data-terminal-output class="space-y-1"></div>
                            </div>
                        </div>

                        {{-- Preview Panel --}}
                        <div data-preview-panel hidden class="flex-1 min-h-0 overflow-hidden bg-white">
                            <iframe data-preview-frame title="Pratinjau HTML/CSS/JS" sandbox="allow-scripts allow-forms" class="h-full w-full border-0 bg-white"></iframe>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Handle Geser Kanan (Editor <-> Lumina AI) --}}
            <div data-resizer="right" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar Lumina AI">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 3 (KANAN): Lumina AI Assistant ("ai assitennya di kanan") --}}
            <aside id="panel-ai" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-hidden shadow-sm border border-line/60 transition-none" style="width: var(--workbench-right-width, 340px); min-width: 260px; max-width: 600px;" aria-labelledby="assistant-heading">
                <div class="border-b border-line/60 p-4 bg-white">
                    <div class="flex items-center justify-between">
                        <h2 id="assistant-heading" class="text-sm font-bold text-ink">Lumina AI</h2>
                        <span class="text-xs text-muted">Asisten Coding</span>
                    </div>
                    <p class="mt-0.5 text-xs text-muted">Petunjuk konsep dan contoh berbeda, tanpa solusi lengkap.</p>
                </div>

                @if ($errors->has('ai'))
                    <p class="p-3 text-xs text-red-700">{{ $errors->first('ai') }}</p>
                @endif
                @auth
                    <form method="POST" action="{{ route('ai.logout') }}" class="p-3 border-b border-line text-xs">
                        @csrf
                        <input type="hidden" name="assignment" value="{{ $item['id'] }}">
                        <span>{{ auth()->user()->email }}</span>
                        <button class="underline ml-2" type="submit">Keluar akun AI</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('ai.login') }}" class="p-3 space-y-2.5 border-b border-line bg-canvas/30">
                        @csrf
                        <input type="hidden" name="assignment" value="{{ $item['id'] }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-ink">Akses Asisten AI</span>
                            <button type="button" onclick="document.querySelector('#ai-login-email').value='demo.ai@sale.test';document.querySelector('#ai-login-password').value='password123456';this.closest('form').submit();" class="text-[10px] font-bold text-brand hover:underline inline-flex items-center gap-1 bg-brand-soft px-2 py-0.5 rounded border border-brand/20">
                                <span>✦ 1-Klik Masuk Demo AI</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-muted leading-relaxed">Masuk untuk mengaktifkan sesi bimbingan AI, atau gunakan tombol <strong>1-Klik Masuk Demo AI</strong> untuk uji coba langsung.</p>
                        <label class="block text-xs">Email akun AI<input id="ai-login-email" class="field mt-1 text-xs" type="email" name="email" required autocomplete="username" placeholder="demo.ai@sale.test"></label>
                        <label class="block text-xs">Password<input id="ai-login-password" class="field mt-1 text-xs" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></label>
                        <button class="button-primary text-xs w-full py-2 font-bold" type="submit">Masuk akun AI</button>
                    </form>
                @endauth

                {{-- Chat Messages (Full-height scrollable stream) --}}
                <div data-ai-messages class="flex-1 space-y-3 overflow-y-auto p-4 text-xs leading-5 flex flex-col" aria-live="polite">
                    <article class="self-start mr-auto max-w-[92%] rounded-2xl rounded-tl-xs bg-white p-3.5 border border-line/70 shadow-xs">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-brand/10 text-brand text-[10px] font-bold">✦</span>
                            <p class="font-semibold text-ink">Lumina AI</p>
                        </div>
                        <p class="text-muted leading-relaxed">
                            Halo! Saya Lumina AI, asisten coding Anda untuk modul <strong class="text-ink">{{ $item['title'] }}</strong>.
                            Tanyakan satu konsep atau pilih potongan kode lalu klik <strong class="text-ink">Tanyakan baris terpilih</strong>. Contoh mengajarkan konsep pendukung, bukan implementasi tugas. Bantuan dibatasi sepanjang tugas, termasuk setelah membuka chat kembali. Pertanyaan dan kode terpilih dikirim ke layanan AI Google untuk diproses.
                        </p>
                    </article>
                </div>

                {{-- Pinned Chat Input at Bottom --}}
                <form data-ai-form method="POST" action="{{ route('ai.send', $item['id']) }}" class="border-t border-line/60 p-3 bg-white">
                    @csrf
                    <div data-code-context hidden class="mb-2 overflow-hidden rounded-lg border border-line bg-canvas">
                        <div class="flex items-center justify-between gap-2 px-2.5 py-1.5">
                            <span data-code-context-label class="text-[11px] font-semibold text-ink"></span>
                            <button type="button" data-remove-context aria-label="Hapus lampiran kode" class="px-1.5 text-muted hover:text-ink">×</button>
                        </div>
                        <pre data-code-context-text class="max-h-24 overflow-auto px-2.5 pb-2 text-[11px] font-mono text-muted"></pre>
                    </div>
                    <div class="relative rounded-xl border border-[#b9c0ca] bg-white transition-all focus-within:border-brand focus-within:ring-1 focus-within:ring-brand shadow-2xs">
                        <label for="assistant-message" class="sr-only">Pertanyaan untuk Lumina AI</label>
                        <textarea required maxlength="2000" id="assistant-message" rows="2" class="w-full bg-transparent border-0 p-2.5 pr-10 pb-7 text-xs text-ink placeholder:text-[#737b86] resize-none outline-none focus:outline-none focus:ring-0 leading-relaxed block" placeholder="Tanyakan petunjuk konsep kode..."></textarea>
                        <div class="absolute right-2 bottom-2 flex items-center">
                            <button disabled type="submit" class="button-primary h-7 w-7 !p-0 !min-h-0 rounded-lg disabled:opacity-30 inline-flex items-center justify-center transition-all duration-150 transform scale-0 opacity-0 pointer-events-none shrink-0 shadow-xs" title="Kirim pertanyaan ke Lumina AI (Enter)" aria-label="Kirim pertanyaan">
                                <svg class="h-3.5 w-3.5 fill-current text-white -mr-0.5 -mt-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between px-1">
                        <span data-ai-status role="status" class="text-[10px] text-muted">Memuat kuota AI…</span>
                        <span class="text-[10px] text-muted">Tekan <kbd class="font-mono bg-canvas px-1 py-0.5 rounded border border-line/60 font-semibold">Enter ↵</kbd> kirim</span>
                    </div>
                </form>
            </aside>

        </div>
    </main>
</body>
</html>
