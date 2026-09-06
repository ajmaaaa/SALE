@extends('layouts.mahasiswa')

@section('title', 'Tugas & Kuis | SALE')
@section('header', 'Tugas & Kuis')

@section('content')
<div class="space-y-8">
    <header>
        <h1 class="page-heading">Tugas &amp; Kuis</h1>
        <p class="page-description">Pekerjaan kelas dikelompokkan berdasarkan course agar lebih mudah dipantau.</p>
    </header>

    <section id="pekerjaan" aria-labelledby="work-heading" class="scroll-mt-24 space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div><h2 id="work-heading" class="section-heading">Pekerjaan kelas</h2><p class="mt-1 text-sm text-muted">Tugas, kuis, dan ujian dari setiap course.</p></div>
            <form class="flex flex-col gap-3 sm:flex-row" action="{{ route('mahasiswa.assignment.index') }}" method="GET">
                <label class="sr-only" for="assignment-search">Cari pekerjaan</label><input id="assignment-search" name="q" type="search" class="field sm:w-64" placeholder="Cari pekerjaan">
                <label class="sr-only" for="assignment-course">Pilih course</label><select id="assignment-course" name="course" class="field sm:w-52"><option>Semua course</option><option>IF204</option><option>IF218</option><option>IF221</option></select>
                <label class="sr-only" for="assignment-type">Pilih tipe</label><select id="assignment-type" name="type" class="field sm:w-40"><option>Semua tipe</option><option>Tugas</option><option>Kuis</option><option>Ujian</option></select>
                <button type="submit" class="button-secondary">Terapkan</button>
            </form>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl bg-white shadow-sm" aria-labelledby="course-if204">
                <div class="bg-brand-dark px-5 py-4 text-white"><p class="text-xs font-semibold text-white">IF204, 3 SKS</p><h3 id="course-if204" class="mt-1 text-lg font-semibold text-white">Struktur Data dan Algoritma</h3></div>
                <div class="space-y-1 p-3">
                    <a href="{{ route('mahasiswa.assignment.code', 1) }}" class="group grid gap-3 rounded-lg px-3 py-3 hover:bg-[#f3f6f9] sm:grid-cols-[28px_minmax(0,1fr)_140px] sm:items-center">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/></svg>
                        <div><p class="text-xs font-semibold text-brand">Tugas coding</p><h4 class="mt-1 font-semibold text-ink">Praktikum Binary Tree</h4><p class="mt-1 text-sm text-muted">Belum dikumpulkan</p></div>
                        <p class="text-sm font-semibold text-danger sm:text-right">Hari ini, 23.59</p>
                    </a>
                    <button type="button" class="group grid w-full gap-3 rounded-lg px-3 py-3 text-left hover:bg-[#f3f6f9] sm:grid-cols-[28px_minmax(0,1fr)_140px] sm:items-center">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                        <div><p class="text-xs font-semibold text-brand">Kuis</p><h4 class="mt-1 font-semibold text-ink">Kuis Struktur Data Dasar</h4><p class="mt-1 text-sm text-muted">30 menit, satu percobaan</p></div>
                        <p class="text-sm font-medium text-muted sm:text-right">7 September</p>
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm" aria-labelledby="course-if218">
                <div class="bg-brand-dark px-5 py-4 text-white"><p class="text-xs font-semibold text-white">IF218, 3 SKS</p><h3 id="course-if218" class="mt-1 text-lg font-semibold text-white">Interaksi Manusia dan Komputer</h3></div>
                <div class="space-y-1 p-3">
                    <button type="button" class="grid w-full gap-3 rounded-lg px-3 py-3 text-left hover:bg-[#f3f6f9] sm:grid-cols-[28px_minmax(0,1fr)_140px] sm:items-center">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h8l4 4v14H5V3z"/><path d="M14 3v5h5M8 13h8M8 17h6"/></svg>
                        <div><p class="text-xs font-semibold text-brand">Tugas dokumen</p><h4 class="mt-1 font-semibold text-ink">Laporan Evaluasi Usability</h4><p class="mt-1 text-sm text-muted">Draf tersimpan</p></div>
                        <p class="text-sm font-medium text-ink sm:text-right">3 September</p>
                    </button>
                    <button type="button" class="grid w-full gap-3 rounded-lg px-3 py-3 text-left hover:bg-[#f3f6f9] sm:grid-cols-[28px_minmax(0,1fr)_140px] sm:items-center">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v14H4zM8 2v6M16 2v6M4 10h16"/></svg>
                        <div><p class="text-xs font-semibold text-brand">Ujian</p><h4 class="mt-1 font-semibold text-ink">Ujian Tengah Semester</h4><p class="mt-1 text-sm text-muted">Belum dibuka</p></div>
                        <p class="text-sm font-medium text-muted sm:text-right">18 September</p>
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl bg-white shadow-sm" aria-labelledby="course-if221">
                <div class="bg-brand-dark px-5 py-4 text-white"><p class="text-xs font-semibold text-white">IF221, 3 SKS</p><h3 id="course-if221" class="mt-1 text-lg font-semibold text-white">Kecerdasan Buatan Terapan</h3></div>
                <div class="p-3">
                    <button type="button" class="grid w-full gap-3 rounded-lg px-3 py-3 text-left hover:bg-[#f3f6f9] sm:grid-cols-[28px_minmax(0,1fr)_140px] sm:items-center">
                        <svg class="h-5 w-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                        <div><p class="text-xs font-semibold text-brand">Kuis</p><h4 class="mt-1 font-semibold text-ink">Kuis Evaluasi Model</h4><p class="mt-1 text-sm text-muted">Belum dibuka</p></div>
                        <p class="text-sm font-medium text-muted sm:text-right">7 September</p>
                    </button>
                </div>
            </section>
        </div>
    </section>

</div>
@endsection
