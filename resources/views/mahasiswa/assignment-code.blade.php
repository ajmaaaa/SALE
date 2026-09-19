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
                            <h2 id="editor-heading" class="text-xs font-bold text-ink">main.py</h2>
                            <span class="text-[11px] text-muted">· Python 3.12</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-code-reset class="button-secondary text-xs py-1 px-2.5">
                                Reset Template
                            </button>
                            <button type="button" data-mention-code disabled class="button-secondary text-xs py-1 px-2.5">
                                Tanyakan Baris Terpilih
                            </button>
                        </div>
                    </div>

                    <textarea data-code-source class="hidden" aria-hidden="true">@if($item['id'] === 1)class Node:
    def __init__(self, key):
        self.left = None
        self.right = None
        self.value = key


class BinaryTree:
    def insert(self, root, key):
        if root is None:
            return Node(key)

        # Lengkapi logika insert di sini

        return root
@else
# Tulis jawaban Python kamu di sini
@endif</textarea>
                    <div data-code-editor data-assignment-id="{{ $item['id'] }}" class="code-editor flex-1 overflow-auto bg-[#282c34]" aria-label="Editor kode Python"></div>
                    <div class="flex items-center justify-between px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4]">
                        <span data-code-save-status>Draf tersimpan di browser</span>
                        <span>UTF-8 · 4 Spasi</span>
                    </div>
                </section>

                {{-- Linux-Style Terminal Section --}}
                <section class="h-64 shrink-0 flex flex-col rounded-xl bg-[#0d1117] text-[#c9d1d9] shadow-sm border border-line/60 overflow-hidden" aria-labelledby="terminal-heading">
                    {{-- Linux Window Header with 3 Control Dots --}}
                    <div class="flex items-center justify-between px-4 py-2.5 bg-[#161b22] border-b border-white/10 select-none">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded-full bg-[#ff5f56] inline-block shadow-xs" title="Close"></span>
                                <span class="h-3 w-3 rounded-full bg-[#ffbd2e] inline-block shadow-xs" title="Minimize"></span>
                                <span class="h-3 w-3 rounded-full bg-[#27c93f] inline-block shadow-xs" title="Maximize"></span>
                            </div>
                            <span class="text-xs font-mono text-slate-300 font-medium">sale@sandbox: ~/praktikum-insert-bst (bash)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-run-code class="inline-flex items-center gap-1.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1 text-xs font-mono font-bold transition shadow-xs">
                                <span>▶</span> Jalankan (Run)
                            </button>
                            <button type="button" data-clear-terminal class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition">
                                Bersihkan
                            </button>
                        </div>
                    </div>

                    {{-- Linux Terminal Console Body --}}
                    <div data-terminal-body class="flex-1 overflow-y-auto p-3.5 font-mono text-xs leading-relaxed space-y-1 select-text bg-[#0d1117]">
                        <p class="text-slate-400">
                            <span class="text-emerald-400 font-bold">sale@sandbox</span>:<span class="text-sky-400 font-bold">~/bst</span>$ python3 --version
                        </p>
                        <p class="text-slate-300 pl-2">Python 3.12.3 (SALE Linux Sandbox Environment)</p>
                        <p class="text-slate-500 mt-1 pl-2"># Klik tombol 'Jalankan (Run)' di atas untuk menguji fungsi BST insert Anda.</p>
                        <div data-terminal-output class="space-y-1"></div>
                    </div>
                </section>
            </div>

            {{-- PANEL 3 (KANAN): Lumina AI Assistant ("ai assitennya di kanan") --}}
            <aside class="surface flex flex-col h-[calc(100vh-100px)] min-h-[640px] rounded-xl overflow-hidden shadow-sm border border-line/60" aria-labelledby="assistant-heading">
                <div class="border-b border-line/60 p-4 bg-white">
                    <div class="flex items-center justify-between">
                        <h2 id="assistant-heading" class="text-sm font-bold text-ink">Lumina AI</h2>
                        <span class="text-xs text-muted">Asisten Coding</span>
                    </div>
                    <p class="mt-0.5 text-xs text-muted">Bantuan logika &amp; analisis kode · main.py</p>
                </div>

                {{-- Prompt Suggestions Chips --}}
                <div class="border-b border-line/50 px-3 py-2 bg-canvas/60 flex flex-wrap gap-1.5 text-[11px]">
                    <button type="button" onclick="document.getElementById('assistant-message').value='Bagaimana cara menangani kondisi ketika root is None?'; document.querySelector('[data-ai-form]').dispatchEvent(new Event('submit'));"
                        class="rounded bg-white px-2 py-1 text-ink border border-line/60 hover:bg-slate-50 transition">
                        Kondisi root is None?
                    </button>
                    <button type="button" onclick="document.getElementById('assistant-message').value='Bagaimana perbandingan nilai key dengan root.value?'; document.querySelector('[data-ai-form]').dispatchEvent(new Event('submit'));"
                        class="rounded bg-white px-2 py-1 text-ink border border-line/60 hover:bg-slate-50 transition">
                        Bandingkan key &amp; root?
                    </button>
                    <button type="button" onclick="document.getElementById('assistant-message').value='Kenapa bisa terjadi IndentationError pada Python?'; document.querySelector('[data-ai-form]').dispatchEvent(new Event('submit'));"
                        class="rounded bg-white px-2 py-1 text-ink border border-line/60 hover:bg-slate-50 transition">
                        Cek IndentationError
                    </button>
                </div>

                {{-- Chat Messages (Full-height scrollable stream) --}}
                <div data-ai-messages class="flex-1 space-y-3 overflow-y-auto p-4 text-xs leading-5" aria-live="polite">
                    <article class="rounded-lg bg-canvas p-3 border border-line/40">
                        <p class="font-semibold text-ink mb-1">Lumina AI</p>
                        <p class="text-muted leading-relaxed">
                            Halo! Saya Lumina AI, asisten coding Anda untuk modul <strong class="text-ink">{{ $item['title'] }}</strong>.
                            Anda dapat menanyakan logika rekursi, cara menangani cabang kiri/kanan Binary Search Tree, atau memblok baris kode di editor lalu klik <strong class="text-ink">Tanyakan baris terpilih</strong>.
                        </p>
                    </article>
                </div>

                {{-- Pinned Chat Input at Bottom --}}
                <form data-ai-form class="border-t border-line/60 p-3 bg-white">
                    <div data-code-context hidden class="mb-2 overflow-hidden rounded-lg border border-line bg-canvas">
                        <div class="flex items-center justify-between gap-2 px-2.5 py-1.5">
                            <span data-code-context-label class="text-[11px] font-semibold text-ink"></span>
                            <button type="button" data-remove-context aria-label="Hapus lampiran kode" class="px-1.5 text-muted hover:text-ink">×</button>
                        </div>
                        <pre data-code-context-text class="max-h-24 overflow-auto px-2.5 pb-2 text-[11px] font-mono text-muted"></pre>
                    </div>
                    <label for="assistant-message" class="sr-only">Pertanyaan untuk Lumina AI</label>
                    <textarea required maxlength="3000" id="assistant-message" rows="2" class="field resize-none text-xs" placeholder="Tanyakan petunjuk konsep kode..."></textarea>
                    <div class="mt-2.5 flex items-center justify-between gap-2">
                        <span class="text-[11px] text-muted">Asisten siap membantu</span>
                        <button type="submit" class="button-primary text-xs py-1.5 px-3">Kirim</button>
                    </div>
                </form>
            </aside>

        </div>
    </main>
</body>
</html>
