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
    <header class="sticky top-0 z-30 bg-white/95 shadow-[0_2px_12px_rgba(29,39,48,0.08)] backdrop-blur-sm">
        <div class="flex min-h-16 w-full flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-ink transition-colors hover:bg-brand-soft" aria-label="Kembali ke Tugas dan Kuis">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="min-w-0"><h1 class="truncate text-sm font-semibold text-ink sm:text-base">{{ $item['title'] }}</h1><p class="truncate text-xs text-muted">{{ $course['code'] }}, {{ $course['title'] }}</p></div>
            </div>
            <div class="flex items-center gap-3">
                <a class="button-secondary" href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}#diskusi">Diskusi tugas</a>
                <form data-code-submit method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}">@csrf<input type="hidden" name="answer" data-code-answer><button disabled class="button-primary">Kumpulkan kode</button></form>
            </div>
        </div>
    </header>

    <main class="w-full p-4 sm:p-6 lg:p-7">
        <div class="grid gap-5 2xl:grid-cols-[280px_minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">
            <section class="rounded-xl bg-white px-5 py-5 shadow-sm xl:col-span-2 2xl:col-span-1" aria-labelledby="question-heading">
                <div class="flex items-center justify-between gap-3 pb-2"><p class="text-sm font-semibold text-ink">Praktikum</p><p class="text-xs text-muted">CPMK · Struktur data</p></div>
                <div class="py-5"><h2 id="question-heading" class="text-lg font-semibold">{{ $item['title'] }}</h2><p class="prose-content mt-4 text-sm">{{ $item['body'] }}</p></div><details class="border-t border-line pt-4"><summary class="cursor-pointer text-sm font-semibold">Capaian pembelajaran</summary><p class="mt-3 text-sm text-muted">{{ $item['cpmk'] }}</p></details>
            </section>

            <div class="min-w-0 space-y-5">
                <section aria-labelledby="editor-heading">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div><h2 id="editor-heading" class="section-heading">Kode jawaban</h2><p class="mt-1 text-sm text-muted">Berkas aktif: main.py</p></div>
                        <div class="flex items-center gap-3"><button type="button" data-code-reset class="button-secondary">Reset</button><button type="button" data-mention-code disabled class="button-primary">Tanyakan baris terpilih</button></div>
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

        return root@else# Tulis jawaban Python kamu di sini
@endif</textarea>
                    <div data-code-editor data-assignment-id="{{ $item['id'] }}" class="code-editor overflow-hidden rounded-t-xl bg-[#282c34] shadow-sm" aria-label="Editor kode Python"></div>
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-b-xl bg-[#20242b] px-4 py-2 text-xs text-[#aeb8c4] shadow-sm"><span data-code-save-status>Draf lokal di browser ini</span><span>Python 3.12</span></div>
                </section>

                <section class="surface p-5" aria-labelledby="output-heading"><h2 id="output-heading" class="section-heading">Pengujian kode</h2><p class="mt-2 text-sm leading-6 text-muted">Runner Python belum terhubung. Belum ada hasil pengujian. Kamu bisa menulis, menyimpan draf, dan mengumpulkan kode.</p></section>
            </div>

            <aside class="surface flex min-h-[560px] min-w-0 flex-col xl:sticky xl:top-[88px]" aria-labelledby="assistant-heading">
                <div class="border-b border-line p-5"><div class="flex items-center justify-between"><h2 id="assistant-heading" class="font-semibold">Asisten belajar</h2><span class="status">Pratinjau</span></div><p class="mt-1 text-xs text-muted">{{ $course['title'] }} · main.py</p></div>
                <div data-ai-messages class="max-h-[400px] flex-1 space-y-4 overflow-y-auto p-5 text-sm leading-6" aria-live="polite"><p class="text-muted">Blok bagian kode yang ingin dibahas, lalu pilih <strong class="font-medium text-ink">Tanyakan baris terpilih</strong>. Potongan kode akan dilampirkan ke pertanyaanmu.</p></div>
                <form data-ai-form class="border-t border-line p-4">
                    <div data-code-context hidden class="mb-3 overflow-hidden rounded-lg border border-line bg-canvas"><div class="flex items-center justify-between gap-2 px-3 py-2"><span data-code-context-label class="text-xs font-semibold text-brand"></span><button type="button" data-remove-context aria-label="Hapus lampiran kode" class="px-2 text-muted">×</button></div><pre data-code-context-text class="max-h-32 overflow-auto px-3 pb-3 text-xs"></pre></div>
                    <label for="assistant-message" class="form-label">Pertanyaanmu</label><textarea required maxlength="3000" id="assistant-message" rows="3" class="field resize-none" placeholder="Misalnya, kenapa rekursi ini tidak berhenti?"></textarea><div class="mt-3 flex items-center justify-between gap-3"><p class="text-xs text-muted">AI belum terhubung.</p><button class="button-primary">Pratinjau pesan ↑</button></div>
                </form>
            </aside>
        </div>
    </main>
</body>
</html>
