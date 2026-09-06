@extends('layouts.mahasiswa')

@section('title', 'Struktur Data dan Algoritma | SALE')
@section('header', 'Struktur Data dan Algoritma')

@section('content')
<div class="space-y-7">
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-muted">
        <a href="{{ route('mahasiswa.course.index') }}" class="hover:text-brand">Course</a>
        <span aria-hidden="true">/</span>
        <span class="text-ink">IF204</span>
    </nav>

    <header class="pb-2">
        <div>
            <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm font-semibold text-brand"><span>IF204</span><span>3 SKS</span><span>Wajib</span></div>
            <h1 class="page-heading mt-2">Struktur Data dan Algoritma</h1>
            <p class="page-description">Mempelajari struktur data fundamental, analisis kompleksitas, serta penerapannya dalam penyelesaian masalah komputasi.</p>
        </div>
    </header>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_300px]">
        <div class="min-w-0 space-y-8">
            <section aria-labelledby="video-heading">
                <div class="aspect-video overflow-hidden rounded-xl bg-[#172633] shadow-sm">
                    <div class="flex h-full flex-col items-center justify-center px-6 text-center text-white">
                        <svg class="mb-4 h-11 w-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4z"/></svg>
                        <h2 id="video-heading" class="text-lg font-semibold">Traversal pada Binary Tree</h2>
                        <p class="mt-1 text-sm text-[#c9d3d9]">Video pengantar, 24 menit</p>
                        <button type="button" class="mt-5 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-[#172633]">Putar video</button>
                    </div>
                </div>
            </section>

            <section aria-labelledby="module-heading">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div><h2 id="module-heading" class="section-heading">Modul mingguan</h2><p class="mt-1 text-sm text-muted">Materi disusun berdasarkan RPS course.</p></div>
                    <a href="#rps" class="quiet-link shrink-0">Lihat RPS dan CPMK</a>
                </div>

                <div class="space-y-2">
                    <details open class="group overflow-hidden rounded-xl shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center gap-4 bg-white p-5 hover:bg-[#f8f9fa]">
                            <span class="w-8 shrink-0 text-sm font-semibold text-ink">01</span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold text-ink">Pengenalan struktur data</span><span class="mt-0.5 block text-sm text-muted">Minggu 1, selesai</span></span>
                            <svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="space-y-2 bg-[#f3f6f9] p-3 sm:pl-[68px]">
                            <button type="button" class="grid w-full gap-3 rounded-lg bg-white px-4 py-3 text-left sm:grid-cols-[24px_minmax(0,1fr)_100px] sm:items-center"><svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h8l4 4v14H5V3zM14 3v5h5"/></svg><span><span class="block text-xs font-semibold text-brand">Materi PDF</span><span class="mt-1 block text-sm font-semibold text-ink">Slide pengantar struktur data</span><span class="mt-1 block text-xs text-muted">2,4 MB</span></span><span class="text-sm font-medium text-ink sm:text-right">Selesai</span></button>
                            <button type="button" class="grid w-full gap-3 rounded-lg bg-white px-4 py-3 text-left sm:grid-cols-[24px_minmax(0,1fr)_100px] sm:items-center"><svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 8 7 4-7 4z"/><circle cx="12" cy="12" r="9"/></svg><span><span class="block text-xs font-semibold text-brand">Materi video</span><span class="mt-1 block text-sm font-semibold text-ink">Rekaman perkuliahan</span><span class="mt-1 block text-xs text-muted">48 menit</span></span><span class="text-sm font-medium text-ink sm:text-right">Selesai</span></button>
                        </div>
                    </details>

                    <details class="group overflow-hidden rounded-xl shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center gap-4 bg-white p-5 hover:bg-[#f8f9fa]">
                            <span class="w-8 shrink-0 text-sm font-semibold text-ink">02</span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold text-ink">Linked list, stack, dan queue</span><span class="mt-0.5 block text-sm text-muted">Minggu 2, selesai</span></span>
                            <svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="bg-[#eef0f2] px-5 py-4 text-sm text-muted sm:pl-[68px]">Tiga materi dan satu kuis telah diselesaikan.</div>
                    </details>

                    <details open class="group overflow-hidden rounded-xl shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center gap-4 bg-white p-5 hover:bg-[#f8f9fa]">
                            <span class="w-8 shrink-0 text-sm font-semibold text-brand">03</span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold text-ink">Tree dan traversal</span><span class="mt-0.5 block text-sm text-muted">Minggu 3, sedang dipelajari</span></span>
                            <svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="space-y-2 bg-[#f3f6f9] p-3 sm:pl-[68px]">
                            <button type="button" class="grid w-full gap-3 rounded-lg bg-white px-4 py-3 text-left sm:grid-cols-[24px_minmax(0,1fr)_100px] sm:items-center"><svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 8 7 4-7 4z"/><circle cx="12" cy="12" r="9"/></svg><span><span class="block text-xs font-semibold text-brand">Materi video</span><span class="mt-1 block text-sm font-semibold text-ink">Traversal pada Binary Tree</span><span class="mt-1 block text-xs text-muted">24 menit</span></span><span class="text-sm font-semibold text-brand sm:text-right">Lanjutkan</span></button>
                            <a href="{{ route('mahasiswa.assignment.code', 1) }}" class="grid gap-3 rounded-lg bg-white px-4 py-3 sm:grid-cols-[24px_minmax(0,1fr)_100px] sm:items-center"><svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/></svg><span><span class="block text-xs font-semibold text-brand">Tugas coding</span><span class="mt-1 block text-sm font-semibold text-ink">Praktikum Binary Tree</span><span class="mt-1 block text-xs text-danger">Tenggat hari ini, 23.59</span></span><span class="text-sm font-semibold text-brand sm:text-right">Kerjakan</span></a>
                            <button type="button" class="grid w-full gap-3 rounded-lg bg-white px-4 py-3 text-left sm:grid-cols-[24px_minmax(0,1fr)_100px] sm:items-center"><svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg><span><span class="block text-xs font-semibold text-brand">Kuis</span><span class="mt-1 block text-sm font-semibold text-ink">Kuis Traversal Tree</span><span class="mt-1 block text-xs text-muted">20 menit, satu percobaan</span></span><span class="text-sm font-medium text-muted sm:text-right">7 September</span></button>
                        </div>
                    </details>

                    <details class="group overflow-hidden rounded-xl shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center gap-4 bg-white p-5 hover:bg-[#f8f9fa]">
                            <span class="w-8 shrink-0 text-sm font-semibold text-muted">04</span>
                            <span class="min-w-0 flex-1"><span class="block font-semibold text-ink">Graph dan algoritma pencarian</span><span class="mt-0.5 block text-sm text-muted">Minggu 4, belum dibuka</span></span>
                            <svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="bg-[#f3f6f9] p-3 pl-[68px]"><div class="grid gap-3 rounded-lg bg-white px-4 py-3 sm:grid-cols-[24px_minmax(0,1fr)_100px] sm:items-center"><svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v14H4zM8 2v6M16 2v6M4 10h16"/></svg><span><span class="block text-xs font-semibold text-brand">Ujian</span><span class="mt-1 block text-sm font-semibold text-ink">Ujian Tengah Semester</span><span class="mt-1 block text-xs text-muted">90 menit, jadwal terkontrol</span></span><span class="text-sm font-medium text-muted sm:text-right">Belum dibuka</span></div></div>
                    </details>
                </div>
            </section>
        </div>

        <aside class="space-y-7">
            <section class="rounded-xl bg-brand px-5 py-5 text-white shadow-sm" aria-labelledby="announcement-heading">
                <p class="text-sm font-semibold text-white">Pengumuman course</p>
                <h2 id="announcement-heading" class="mt-3 font-semibold text-white">Perubahan ruang perkuliahan</h2>
                <p class="mt-2 text-sm leading-6 text-white">Pertemuan Kamis dipindahkan ke Lab Komputasi 2 pada pukul 10.00.</p>
                <p class="mt-3 text-xs text-white">Diperbarui 31 Agustus 2026</p>
            </section>

            <section class="rounded-xl bg-white px-5 py-5 shadow-sm" aria-labelledby="lecturer-heading">
                <p class="text-xs font-semibold uppercase tracking-[0.06em] text-muted">Dosen pengampu</p>
                <h2 id="lecturer-heading" class="mt-2 text-base font-semibold text-ink">Dr. Budi Santoso, M.Kom.</h2>
                <p class="mt-1 text-sm text-muted">Fakultas Ilmu Komputer</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-muted">Email</dt><dd class="mt-0.5 break-all text-ink">budi.santoso@kampus.ac.id</dd></div>
                    <div><dt class="text-muted">Konsultasi</dt><dd class="mt-0.5 text-ink">Selasa dan Kamis, 13.00 sampai 15.00</dd></div>
                </dl>
                <button type="button" class="button-secondary mt-5 w-full">Kirim pesan</button>
            </section>

            <section id="rps" class="rounded-xl bg-white px-5 py-5 shadow-sm" aria-labelledby="outcome-heading">
                <p class="text-xs font-semibold uppercase tracking-[0.06em] text-muted">Capaian pembelajaran</p>
                <h2 id="outcome-heading" class="mt-2 text-base font-semibold text-ink">CPMK terkait modul ini</h2>
                <p class="mt-2 text-sm leading-6 text-muted">Mahasiswa mampu memilih dan menerapkan struktur data yang tepat untuk menyelesaikan masalah komputasi.</p>
                <button type="button" class="quiet-link mt-3 inline-block">Buka dokumen RPS</button>
            </section>
        </aside>
    </div>
</div>
@endsection
