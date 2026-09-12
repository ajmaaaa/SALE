<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f5f7">
    <title>{{ $item['title'] }} | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    @php
    $language = $item['language'] ?? 'python';
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
                <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink hover:bg-slate-100 transition" aria-label="Kembali ke Detail Tugas">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold text-ink">{{ $item['title'] }}</h1>
                    <p class="truncate text-xs text-muted">{{ $course['code'] }} · {{ $course['title'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a class="button-secondary text-xs py-2 px-3" href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}#diskusi">
                    Diskusi Tugas
                </a>
                <form data-code-submit method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}">
                    @csrf
                    <input type="hidden" name="answer" data-code-answer>
                    <button disabled class="button-primary text-xs py-2 px-4 font-bold">
                        Kumpulkan Kode
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="w-full p-4 sm:p-5">
        @if($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-danger bg-white p-3 text-xs text-danger">{{ $errors->first() }}</div>
        @endif

        {{-- 3-Panel Workbench: Left (Soal & Instruksi), Center (Code Editor & Linux Terminal), Right (Lumina AI Assistant) --}}
        <div class="grid gap-4 xl:grid-cols-[330px_minmax(0,1fr)_330px] 2xl:grid-cols-[360px_minmax(0,1fr)_360px] items-start">

            {{-- PANEL 1 (KIRI): Soal & Capaian Pembelajaran ("soalnya di kiri") --}}
            <section class="surface flex flex-col h-[calc(100vh-100px)] min-h-[640px] rounded-xl overflow-y-auto p-5 shadow-sm border border-line/60 space-y-5" aria-labelledby="question-heading">
                <div>
                    <span class="text-xs font-semibold text-muted uppercase tracking-wider">Praktikum Coding</span>
                    <h2 id="question-heading" class="text-base font-bold text-ink mt-1">{{ $item['title'] }}</h2>
                    <p class="text-xs text-muted mt-0.5">{{ $item['module'] }} · {{ $course['lecturer'] }}</p>
                </div>

                <div class="border-t border-line/60 pt-4">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider mb-2">Petunjuk Pengerjaan</h3>
                    <p class="text-xs leading-relaxed text-ink font-medium whitespace-pre-line">{{ $item['body'] }}</p>
                </div>

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

                <div class="border-t border-line/60 pt-4 space-y-1.5 text-xs">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Capaian Pembelajaran (CPMK)</h3>
                    <p class="text-xs text-muted leading-relaxed">{{ $item['cpmk'] }}</p>
                </div>

                <div class="border-t border-line/60 pt-4 mt-auto">
                    <div class="rounded-lg bg-canvas p-3 text-xs mb-3">
                        <p class="font-bold text-ink">Pengumpulan Kode</p>
                        <p class="text-muted mt-0.5">Kode dari editor otomatis dievaluasi dan disimpan ke portofolio Anda.</p>
                    </div>
                    <button type="button" onclick="document.querySelector('[data-code-submit]').requestSubmit()" class="button-primary w-full py-2.5 text-xs font-bold">
                        Kumpulkan Kode Solusi
                    </button>
                </div>
            </section>

            {{-- PANEL 2 (TENGAH): Code Editor & Linux Sandbox Terminal --}}
            <div class="min-w-0 space-y-4 flex flex-col h-[calc(100vh-100px)] min-h-[640px]">
                {{-- Editor Section --}}
                <section class="flex-1 flex flex-col min-h-0 rounded-xl bg-white shadow-sm border border-line/60 overflow-hidden" aria-labelledby="editor-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 border-b border-line/60 bg-white">
                        <div class="flex items-center gap-2">
                            <h2 id="editor-heading" class="text-xs font-bold text-ink" data-code-filename>{{ $language === 'web' ? 'index.html' : 'main.py' }}</h2>
                            <span data-file-language-badge class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase"></span>
                            <span class="text-[11px] text-muted">· <span data-python-version>{{ $language === 'web' ? 'HTML · CSS · JS' : 'Python' }}</span></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-mention-code disabled class="button-secondary text-xs py-1 px-2.5">
                                Tanyakan Baris Terpilih
                            </button>
                            <button type="button" data-code-reset class="button-secondary text-xs py-1 px-2.5">
                                Reset Template
                            </button>
                        </div>
                    </div>

                    {{-- File Tabs: tambah / ganti / rename / hapus berkas seperti editor pada umumnya --}}
                    <div data-file-tabs class="flex items-center gap-1 overflow-x-auto border-b border-line/60 bg-slate-50 px-3 py-1.5" role="tablist" aria-label="Berkas kode"></div>

                    <textarea data-code-files-json class="hidden" aria-hidden="true">@json($defaultFiles)</textarea>
                    <div data-code-editor data-assignment-id="{{ $item['id'] }}" data-runtime-url="{{ asset('vendor/pyodide') }}/" data-code-language="{{ $language }}" data-max-files="5" data-max-file-chars="8000" data-max-total-chars="20000" class="code-editor flex-1 overflow-auto bg-[#282c34]" aria-label="Editor kode {{ $language === 'web' ? 'HTML/CSS/JS' : 'Python' }}"></div>
                    <div class="flex items-center justify-between gap-3 px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4]">
                        <span data-code-save-status>Draf tersimpan di browser</span>
                        <span class="flex items-center gap-3">
                            <span data-chars-count></span>
                            <span>UTF-8 · 4 Spasi</span>
                        </span>
                    </div>
                </section>

                {{-- Output Panel: Console / Preview Tabs --}}
                <section class="h-64 shrink-0 flex flex-col rounded-xl bg-[#0d1117] text-[#c9d1d9] shadow-sm border border-line/60 overflow-hidden" aria-labelledby="terminal-heading">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-[#161b22] border-b border-white/10 select-none">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded-full bg-[#ff5f56] inline-block shadow-xs" title="Close"></span>
                                <span class="h-3 w-3 rounded-full bg-[#ffbd2e] inline-block shadow-xs" title="Minimize"></span>
                                <span class="h-3 w-3 rounded-full bg-[#27c93f] inline-block shadow-xs" title="Maximize"></span>
                            </div>
                            <span class="text-xs font-mono text-slate-300 font-medium">{{ $language === 'web' ? 'Output Web · Pratinjau Browser' : 'Output Python · Latihan' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" disabled data-run-code class="inline-flex items-center gap-1.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1 text-xs font-mono font-bold transition shadow-xs">
                                <span>▶</span> Jalankan Kode
                            </button>
                            @if($item['id'] === 1 && $language !== 'web')
                                <button type="button" disabled data-test-code class="rounded bg-sky-700 hover:bg-sky-600 text-white px-3 py-1 text-xs font-mono font-bold">Uji Tugas BST</button>
                            @endif
                            <button type="button" data-stop-code hidden class="text-xs text-rose-300 px-2 py-1">Hentikan</button>
                            <button type="button" data-clear-terminal class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition">
                                Bersihkan
                            </button>
                        </div>
                    </div>

                    {{-- Output Tabs --}}
                    <div class="flex items-end gap-1 px-3 pt-2 bg-[#0d1117] border-b border-white/10" role="tablist" aria-label="Panel output">
                        <button type="button" role="tab" aria-selected="true" data-output-tab="console" class="rounded-t-md px-3 py-1.5 font-mono text-[11px] border border-b-0 border-white/10 bg-white/10 text-white">Konsol</button>
                        @if($language === 'web')
                            <button type="button" role="tab" aria-selected="false" data-output-tab="preview" class="rounded-t-md px-3 py-1.5 font-mono text-[11px] border border-b-0 border-transparent text-slate-400 hover:text-slate-200">Pratinjau</button>
                        @endif
                    </div>

                    {{-- Console Panel --}}
                    <div data-console-panel class="flex-1 flex flex-col min-h-0">
                        <div data-terminal-body class="flex-1 overflow-y-auto p-3.5 font-mono text-xs leading-relaxed space-y-1 select-text bg-[#0d1117]">
                            <p class="text-slate-500">{{ $language === 'web' ? 'Jalankan Kode untuk merender halaman. Tab Konsol menampilkan console.log/error, tab Pratinjau menampilkan hasilnya.' : 'Jalankan Kode untuk melihat output program. Uji Tugas BST memeriksa implementasi BinaryTree; hasilnya bukan nilai resmi.' }}</p>
                            <div data-terminal-output class="space-y-1"></div>
                        </div>
                    </div>

                    {{-- Preview Panel --}}
                    @if($language === 'web')
                        <div data-preview-panel hidden class="flex-1 min-h-0 overflow-hidden">
                            <iframe data-preview-frame title="Pratinjau HTML/CSS/JS" sandbox="allow-scripts allow-forms" class="h-full w-full border-0 bg-white"></iframe>
                        </div>
                    @endif
                </section>
            </div>

            {{-- PANEL 3 (KANAN): Lumina AI Assistant ("ai assitennya di kanan") --}}
            <aside class="surface flex flex-col h-[calc(100vh-100px)] min-h-[640px] rounded-xl overflow-hidden shadow-sm border border-line/60" aria-labelledby="assistant-heading">
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
                    <form method="POST" action="{{ route('ai.login') }}" class="p-3 space-y-2 border-b border-line">
                        @csrf
                        <input type="hidden" name="assignment" value="{{ $item['id'] }}">
                        <p class="text-xs text-muted">Masuk dengan akun AI yang diberikan pengelola. Akun pratinjau peran tidak memberikan akses AI.</p>
                        <label class="block text-xs">Email akun AI<input class="field mt-1" type="email" name="email" required autocomplete="username"></label>
                        <label class="block text-xs">Password<input class="field mt-1" type="password" name="password" required autocomplete="current-password"></label>
                        <button class="button-primary text-xs" type="submit">Masuk akun AI</button>
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
                    <label for="assistant-message" class="sr-only">Pertanyaan untuk Lumina AI</label>
                    <textarea required maxlength="2000" id="assistant-message" rows="2" class="field resize-none text-xs" placeholder="Tanyakan petunjuk konsep kode..."></textarea>
                    <div class="mt-2.5 flex items-center justify-between gap-2">
                        <span data-ai-status role="status" class="text-[11px] text-muted">Memuat kuota AI…</span>
                        <button disabled type="submit" class="button-primary text-xs py-1.5 px-3 disabled:opacity-50">Kirim</button>
                    </div>
                </form>
            </aside>

        </div>
    </main>
</body>
</html>
