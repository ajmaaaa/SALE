@extends('layouts.mahasiswa')

@section('title', 'Dashboard | SALE')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-8">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">Selasa, 1 September 2026</p>
            <h1 class="page-heading">Selamat pagi, Ahmad.</h1>
            <p class="page-description">Lanjutkan perkuliahan dari materi terakhir atau periksa pekerjaan yang segera berakhir.</p>
        </div>
        <a href="{{ route('mahasiswa.course.show', 1) }}" class="button-primary shrink-0">Lanjutkan belajar</a>
    </header>

    <section class="relative grid min-h-52 overflow-hidden rounded-xl px-5 py-7 text-white shadow-sm sm:px-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:gap-8" aria-labelledby="recommendation-heading">
        <img src="https://images.pexels.com/photos/7989138/pexels-photo-7989138.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1600" alt="" class="absolute inset-0 h-full w-full object-cover object-center" fetchpriority="high">
        <div class="absolute inset-0 bg-[#102f50]/80" aria-hidden="true"></div>
        <div class="relative z-10">
            <p class="text-sm font-semibold text-white">Rekomendasi materi untuk Anda</p>
            <h2 id="recommendation-heading" class="mt-2 text-xl font-semibold text-white">Perkuat pemahaman traversal pada binary tree</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-white">Hasil kuis terakhir menunjukkan materi preorder dan postorder perlu dipelajari kembali sebelum praktikum berikutnya.</p>
        </div>
        <a href="{{ route('mahasiswa.course.show', 1) }}" class="relative z-10 inline-flex min-h-10 items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand-dark hover:bg-[#f1f3f5]">Buka materi rekomendasi</a>
    </section>

    <div class="rounded-2xl bg-[#e9edf1] p-4 sm:p-5">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            <section aria-labelledby="course-heading">
                <div class="mb-4 flex min-h-[92px] items-start justify-between gap-4">
                    <div><h2 id="course-heading" class="section-heading">Course semester ini</h2><p class="mt-1 text-sm text-muted">Kelas aktif dari program studi.</p></div>
                    <a href="{{ route('mahasiswa.course.index') }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand hover:text-brand-dark">Lihat semua <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(210px,100%),1fr))] gap-3">
                    @foreach ([
                        ['id' => 1, 'code' => 'IF204', 'sks' => '3 SKS', 'title' => 'Struktur Data dan Algoritma', 'type' => 'Tugas coding', 'work' => 'Praktikum Binary Tree', 'due' => 'Hari ini, 23.59', 'lecturer' => 'Dr. Budi Santoso, M.Kom.'],
                        ['id' => 2, 'code' => 'IF218', 'sks' => '3 SKS', 'title' => 'Interaksi Manusia dan Komputer', 'type' => 'Tugas dokumen', 'work' => 'Laporan Evaluasi Usability', 'due' => '3 September', 'lecturer' => 'Dr. Ratna Prameswari, M.Ds.'],
                        ['id' => 3, 'code' => 'IF221', 'sks' => '3 SKS', 'title' => 'Kecerdasan Buatan Terapan', 'type' => 'Kuis', 'work' => 'Kuis Evaluasi Model', 'due' => '7 September', 'lecturer' => 'Prof. Nadia Rahman, Ph.D.'],
                    ] as $course)
                        <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="group flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="relative min-h-28 overflow-hidden bg-brand-dark px-5 py-5 text-white">
                                @if($course['id'] === 1)
                                    <svg class="absolute -right-3 -top-3 h-32 w-32 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/></svg>
                                @elseif($course['id'] === 2)
                                    <svg class="absolute -right-3 -top-2 h-32 w-32 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/></svg>
                                @else
                                    <svg class="absolute -right-3 -top-3 h-32 w-32 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/></svg>
                                @endif
                                <div class="relative z-10"><div class="flex items-center gap-3 text-xs font-semibold text-white"><span>{{ $course['code'] }}</span><span>{{ $course['sks'] }}</span></div><h3 class="mt-3 text-lg font-semibold leading-6 text-white">{{ $course['title'] }}</h3><p class="mt-1 text-xs text-white">{{ $course['lecturer'] }}</p></div>
                            </div>
                            <div class="flex flex-1 flex-col px-5 py-4"><p class="text-xs font-semibold text-brand">{{ $course['type'] }}</p><p class="mt-1 text-sm font-semibold text-ink">{{ $course['work'] }}</p><p class="mt-3 text-xs font-medium {{ $loop->first ? 'text-danger' : 'text-muted' }}">{{ $course['due'] }}</p></div>
                        </a>
                    @endforeach
                </div>
            </section>

            <aside aria-labelledby="deadline-heading">
                <div class="mb-4 flex min-h-[92px] items-start justify-between gap-2">
                    <div><h2 id="deadline-heading" class="section-heading">Tenggat terdekat</h2><p class="mt-1 text-sm leading-5 text-muted">Pekerjaan yang perlu segera diselesaikan.</p></div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark"><span class="hidden min-[1320px]:inline">Lihat semua</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="rounded-2xl bg-white px-2 py-2 shadow-sm">
                    <a href="{{ route('mahasiswa.assignment.code', 1) }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9]"><span><span class="block text-xs font-semibold text-danger">Hari ini</span><span class="mt-1 block text-xs text-muted">23.59</span></span><span><span class="block text-sm font-semibold leading-5 text-ink">Praktikum Binary Tree</span><span class="mt-1 block text-xs leading-5 text-muted">Struktur Data dan Algoritma</span></span></a>
                    <div class="mx-4 h-px bg-[#e7eaee]" aria-hidden="true"></div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9]"><span><span class="block text-xs font-semibold text-ink">3 Sep</span><span class="mt-1 block text-xs text-muted">17.00</span></span><span><span class="block text-sm font-semibold leading-5 text-ink">Laporan evaluasi usability</span><span class="mt-1 block text-xs leading-5 text-muted">Interaksi Manusia dan Komputer</span></span></a>
                    <div class="mx-4 h-px bg-[#e7eaee]" aria-hidden="true"></div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9]"><span><span class="block text-xs font-semibold text-ink">7 Sep</span><span class="mt-1 block text-xs text-muted">20.00</span></span><span><span class="block text-sm font-semibold leading-5 text-ink">Kuis evaluasi model</span><span class="mt-1 block text-xs leading-5 text-muted">Kecerdasan Buatan Terapan</span></span></a>
                </div>
            </aside>

            <section aria-labelledby="discussion-heading">
                <div class="mb-4 flex min-h-[92px] items-start justify-between gap-2">
                    <div><h2 id="discussion-heading" class="section-heading">Diskusi terbaru</h2><p class="mt-1 text-sm leading-5 text-muted">Percakapan dari course aktif.</p></div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark"><span class="hidden min-[1320px]:inline">Buka forum</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="block rounded-xl bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"><p class="text-xs font-semibold text-brand">Struktur Data dan Algoritma</p><p class="mt-3 text-sm font-semibold leading-5 text-ink">Kendala implementasi Binary Search Tree di Java</p><p class="mt-3 text-xs text-muted">Budi Santoso, 4 balasan</p></a>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="block rounded-xl bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"><p class="text-xs font-semibold text-brand">Interaksi Manusia dan Komputer</p><p class="mt-3 text-sm font-semibold leading-5 text-ink">Catatan evaluasi usability untuk tugas kelompok</p><p class="mt-3 text-xs text-muted">Siti Aminah, 9 balasan</p></a>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="block rounded-xl bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"><p class="text-xs font-semibold text-brand">Kecerdasan Buatan Terapan</p><p class="mt-3 text-sm font-semibold leading-5 text-ink">Memilih metrik evaluasi untuk data tidak seimbang</p><p class="mt-3 text-xs text-muted">Raka Putra, 3 balasan</p></a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
