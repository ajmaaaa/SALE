<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f5f7">
    <title>Praktikum Binary Tree | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    <header class="sticky top-0 z-30 bg-white/95 shadow-[0_2px_12px_rgba(29,39,48,0.08)] backdrop-blur-sm">
        <div class="flex min-h-16 w-full flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('mahasiswa.assignment.index') }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-ink transition-colors hover:bg-brand-soft" aria-label="Kembali ke Tugas dan Kuis">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="min-w-0"><h1 class="truncate text-sm font-semibold text-ink sm:text-base">Praktikum Binary Tree</h1><p class="truncate text-xs text-muted">IF204, Struktur Data dan Algoritma</p></div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right"><p class="text-xs text-muted">Sisa waktu</p><p class="font-mono text-sm font-semibold text-danger" aria-label="Sisa waktu 1 jam 45 menit 22 detik">01:45:22</p></div>
                <button type="button" class="button-primary">Kumpulkan</button>
            </div>
        </div>
    </header>

    <main class="w-full p-4 sm:p-6 lg:p-7">
        <div class="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)_320px] xl:items-start">
            <section class="rounded-xl bg-white px-5 py-5 shadow-sm xl:sticky xl:top-[88px] xl:max-h-[calc(100vh-112px)] xl:overflow-y-auto" aria-labelledby="question-heading">
                <div class="flex items-center justify-between gap-3 pb-2"><p class="text-sm font-semibold text-ink">Soal 1 dari 5</p><p class="text-xs text-muted">Pilihan kompleks AKM</p></div>
                <div class="py-5">
                    <h2 id="question-heading" class="text-lg font-semibold leading-6 text-ink">Pohon biner dalam sistem basis data</h2>
                    <p class="mt-3 text-sm leading-6 text-[#46525d]">Binary Search Tree digunakan untuk mengatur data sehingga proses pencarian, penyisipan, dan penghapusan dapat dilakukan secara terstruktur. Kinerja operasi tersebut dipengaruhi oleh tinggi pohon.</p>
                    <div class="mt-6 rounded-lg bg-brand px-4 py-4 text-white"><p class="text-sm font-semibold">Tugas pemrograman</p><p class="mt-1 text-sm leading-6">Lengkapi metode <code class="font-mono text-[13px]">insert()</code> agar bekerja sesuai prinsip Binary Search Tree.</p></div>
                </div>
                <fieldset class="pt-2">
                    <legend class="text-sm font-semibold leading-5 text-ink">Pilih semua pernyataan yang benar.</legend>
                    <div class="mt-4 space-y-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-[#f2f5f7] px-3 py-4"><input type="checkbox" class="mt-0.5 h-4 w-4 rounded-sm border-line text-brand focus:ring-brand"><span class="text-sm leading-5 text-ink">Anak kiri memiliki nilai lebih kecil daripada simpul induk.</span></label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-brand-soft px-3 py-4"><input type="checkbox" checked class="mt-0.5 h-4 w-4 rounded-sm border-line text-brand focus:ring-brand"><span class="text-sm font-medium leading-5 text-ink">Pencarian pada pohon yang tidak seimbang dapat memiliki kompleksitas O(n).</span></label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-[#f2f5f7] px-3 py-4"><input type="checkbox" class="mt-0.5 h-4 w-4 rounded-sm border-line text-brand focus:ring-brand"><span class="text-sm leading-5 text-ink">B-Tree memiliki struktur dan batas jumlah anak yang sama dengan Binary Tree.</span></label>
                    </div>
                </fieldset>
            </section>

            <div class="min-w-0 space-y-5">
                <section aria-labelledby="editor-heading">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div><h2 id="editor-heading" class="section-heading">Kode jawaban</h2><p class="mt-1 text-sm text-muted">Berkas aktif: main.py</p></div>
                        <div class="flex items-center gap-3"><button type="button" class="button-secondary">Reset</button><button type="button" class="button-primary">Jalankan kode</button></div>
                    </div>

                    <textarea data-code-source class="hidden" aria-hidden="true">class Node:
    def __init__(self, key):
        self.left = None
        self.right = None
        self.value = key


class BinaryTree:
    def insert(self, root, key):
        if root is None:
            return Node(key)

        # Lengkapi logika insert di sini

        return root</textarea>
                    <div data-code-editor class="code-editor overflow-hidden rounded-t-xl bg-[#282c34] shadow-sm" aria-label="Editor kode Python"></div>
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-b-xl bg-[#20242b] px-4 py-2 text-xs text-[#aeb8c4] shadow-sm"><span>Perubahan disimpan otomatis</span><span>Python 3.12</span></div>
                </section>

                <section class="rounded-xl bg-white px-5 py-5 shadow-sm" aria-labelledby="output-heading">
                    <div class="flex items-center justify-between"><h2 id="output-heading" class="section-heading">Hasil pengujian</h2><span class="text-sm font-semibold text-danger">1 pengujian gagal</span></div>
                    <div class="mt-4 grid gap-2 font-mono text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-4 rounded-lg bg-[#f2f5f7] px-4 py-3"><span>test_left_child</span><span class="font-semibold text-ink">Lulus</span></div>
                        <div class="flex justify-between gap-4 rounded-lg bg-[#f2f5f7] px-4 py-3"><span>test_right_child</span><span class="font-semibold text-danger">Gagal</span></div>
                    </div>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-[#20242b] p-4 font-mono text-xs leading-6 text-[#d7dde5]"><code>AssertionError: right child was not inserted
File "test_cases.py", line 16</code></pre>
                </section>
            </div>

            <aside class="flex min-h-[620px] flex-col rounded-xl bg-white shadow-sm xl:sticky xl:top-[88px] xl:max-h-[calc(100vh-112px)]" aria-labelledby="assistant-heading">
                <div class="rounded-t-xl bg-[#2563eb] px-5 py-4 text-white">
                    <h2 id="assistant-heading" class="font-semibold">Asisten course</h2>
                    <p class="mt-1 text-xs text-white">Menggunakan materi IF204</p>
                </div>
                <p class="px-5 py-3 text-xs leading-5 text-muted">Asisten memberikan petunjuk konsep, bukan jawaban akhir.</p>
                <div class="flex-1 space-y-4 overflow-y-auto px-4 py-3 text-sm leading-6">
                    <div class="max-w-[90%] rounded-xl rounded-tl-sm bg-[#f1f5f9] px-4 py-3 text-ink">Pengujian menunjukkan cabang kanan belum ditangani. Kondisi apa yang membedakan penyisipan ke kiri dan ke kanan?</div>
                    <div class="ml-auto max-w-[90%] rounded-xl rounded-tr-sm bg-[#2563eb] px-4 py-3 text-white">Apakah saya perlu membandingkan key dengan root.value?</div>
                    <div class="max-w-[90%] rounded-xl rounded-tl-sm bg-[#f1f5f9] px-4 py-3 text-ink">Ya. Setelah perbandingan, tentukan cabang rekursif yang perlu diperbarui.</div>
                </div>
                <form class="p-4"><label for="assistant-message" class="sr-only">Pertanyaan untuk asisten course</label><textarea id="assistant-message" rows="3" class="field resize-none" placeholder="Tanyakan konsep atau minta petunjuk"></textarea><button type="button" class="mt-3 inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-[#2563eb] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1d4ed8]">Kirim pertanyaan</button></form>
            </aside>
        </div>
    </main>
</body>
</html>
