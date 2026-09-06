@extends('layouts.mahasiswa')

@section('title', 'Forum Diskusi | SALE')
@section('header', 'Forum Diskusi')

@section('content')
<div class="space-y-7">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="page-heading">Forum Diskusi</h1><p class="page-description">Diskusi course untuk bertanya, membagikan pemahaman, dan menindaklanjuti materi perkuliahan.</p></div>
        <button type="button" class="button-primary shrink-0">Buat diskusi</button>
    </header>

    <form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_260px_auto]" action="{{ route('mahasiswa.discussion.index') }}" method="GET">
        <div><label class="sr-only" for="discussion-search">Cari diskusi</label><input id="discussion-search" name="q" type="search" class="field" placeholder="Cari judul atau isi diskusi"></div>
        <div><label class="sr-only" for="discussion-course">Pilih course</label><select id="discussion-course" name="course" class="field"><option>Semua course</option><option>IF204, Struktur Data</option><option>IF218, Interaksi Manusia dan Komputer</option><option>IF221, Kecerdasan Buatan Terapan</option></select></div>
        <button type="submit" class="button-secondary">Terapkan</button>
    </form>

    <div class="grid gap-7 xl:grid-cols-[360px_minmax(0,1fr)]">
        <section aria-labelledby="thread-heading">
            <div class="mb-3 flex items-center justify-between"><h2 id="thread-heading" class="section-heading">Topik course</h2><span class="text-sm text-muted">18 topik</span></div>
            <div class="space-y-2">
                <a href="#thread-detail" aria-current="true" class="block rounded-xl bg-brand px-4 py-5 text-white shadow-sm">
                    <p class="text-xs font-semibold text-white">IF204, belum terjawab</p>
                    <h3 class="mt-2 font-semibold leading-5 text-white">Kendala implementasi Binary Search Tree di Java</h3>
                    <p class="mt-2 line-clamp-2 text-sm leading-5 text-white">NullPointerException muncul ketika node yang dihapus memiliki dua anak.</p>
                    <p class="mt-3 text-xs text-white">Budi Santoso, 2 jam lalu, 4 balasan</p>
                </a>
                <a href="#thread-detail" class="block rounded-xl bg-white px-4 py-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold text-muted">IF204, diskusi umum</p>
                    <h3 class="mt-2 font-semibold leading-5 text-ink">Pembentukan kelompok tugas besar</h3>
                    <p class="mt-2 line-clamp-2 text-sm leading-5 text-muted">Mencari dua anggota untuk kelompok praktikum minggu depan.</p>
                    <p class="mt-3 text-xs text-muted">Siti Aminah, kemarin, 12 balasan</p>
                </a>
                <a href="#thread-detail" class="block rounded-xl bg-white px-4 py-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold text-muted">IF218, materi</p>
                    <h3 class="mt-2 font-semibold leading-5 text-ink">Contoh penyusunan usability test plan</h3>
                    <p class="mt-2 line-clamp-2 text-sm leading-5 text-muted">Apakah format test plan perlu memuat profil setiap partisipan?</p>
                    <p class="mt-3 text-xs text-muted">Doni Wijaya, 3 hari lalu, 6 balasan</p>
                </a>
            </div>
        </section>

        <article id="thread-detail" class="min-w-0 rounded-xl bg-white px-5 py-6 shadow-sm sm:px-7">
            <header class="pb-5">
                <p class="mb-4 text-sm font-semibold text-brand">Pertanyaan</p>
                <p class="text-xs font-semibold text-muted">IF204, materi Java, topik BST</p>
                <h2 class="mt-2 text-2xl font-semibold leading-8 tracking-[-0.015em] text-ink">Kendala implementasi Binary Search Tree di Java</h2>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm text-muted"><p><span class="font-semibold text-ink">Budi Santoso</span>, mahasiswa</p><time datetime="2026-09-01T09:30">Hari ini, 09.30</time></div>
            </header>

            <div class="space-y-4 py-6 text-[15px] leading-7 text-[#35414c]">
                <p>Saya sedang mengerjakan praktikum dan mengalami masalah saat mengimplementasikan fungsi <code class="font-mono text-[13px] text-brand-dark">deleteNode</code>. Program menampilkan <code class="font-mono text-[13px] text-danger">NullPointerException</code> ketika node yang dihapus memiliki dua anak.</p>
                <pre class="overflow-x-auto rounded-lg bg-[#172633] p-4 font-mono text-[13px] leading-6 text-[#dce4e8]"><code>Node minNode = findMin(root.right);
root.value = minNode.value;
root.right = deleteNode(root.right, minNode.value);</code></pre>
                <p>Apakah kondisi dasar pada fungsi tersebut masih kurang, atau ada kesalahan pada pemanggilan <code class="font-mono text-[13px] text-brand-dark">findMin</code>?</p>
                <button type="button" class="text-sm font-semibold text-brand hover:underline">Pertanyaan ini membantu, 2 suara</button>
            </div>

            <section class="relative ml-2 mt-8 pl-9 sm:ml-4 sm:pl-12" aria-labelledby="reply-heading">
                <span class="absolute bottom-0 left-3 top-[-32px] w-px bg-[#cbd2d9] sm:left-4" aria-hidden="true"></span>
                <span class="absolute left-3 top-7 h-px w-6 bg-[#cbd2d9] sm:left-4 sm:w-8" aria-hidden="true"></span>
                <span class="absolute left-[9px] top-[24px] h-2 w-2 rounded-full bg-brand sm:left-[13px]" aria-hidden="true"></span>
                <article class="rounded-xl bg-white px-5 py-5 shadow-[0_3px_16px_rgba(29,39,48,0.09)]">
                    <p class="text-sm font-semibold text-brand">Jawaban dosen</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-soft text-xs font-semibold text-brand-dark">BS</span><h3 id="reply-heading" class="text-sm font-semibold text-ink">Dr. Budi Santoso, M.Kom.<span class="mt-0.5 block text-xs font-normal text-muted">Dosen pengampu</span></h3></div>
                        <time class="text-xs text-muted">Hari ini, 11.05</time>
                    </div>
                    <div class="mt-4 space-y-3 text-[15px] leading-7 text-[#35414c]"><p>Periksa kondisi ketika <code class="font-mono text-[13px]">root.right</code> kosong sebelum memanggil <code class="font-mono text-[13px]">findMin</code>. Tambahkan juga pengujian untuk node daun dan node dengan satu anak agar alur rekursinya terlihat.</p><p>Jangan langsung mengganti implementasi. Coba tuliskan dahulu kondisi dasar yang harus selalu terpenuhi.</p></div>
                </article>
            </section>

            <form class="mt-7 pt-2">
                <label for="reply" class="mb-2 block text-sm font-semibold text-ink">Tulis tanggapan</label>
                <textarea id="reply" rows="4" class="field resize-y" placeholder="Tambahkan tanggapan atau pertanyaan lanjutan"></textarea>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><button type="button" class="button-secondary">Lampirkan berkas</button><button type="button" class="button-primary">Kirim tanggapan</button></div>
            </form>
        </article>
    </div>
</div>
@endsection
