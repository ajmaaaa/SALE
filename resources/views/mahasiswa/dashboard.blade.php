@extends('layouts.mahasiswa')

@section('title', 'Dashboard | SALE')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-8">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, Ahmad.</h1>
            <p class="page-description">Lanjutkan perkuliahan dari materi terakhir atau periksa pekerjaan yang segera berakhir.</p>
        </div>
        <a href="{{ route('mahasiswa.course.item', [1, 3]) }}" class="button-primary shrink-0">Lanjutkan belajar</a>
    </header>

    <section class="relative grid min-h-52 overflow-hidden rounded-xl px-5 py-7 text-white shadow-sm sm:px-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:gap-8" aria-labelledby="recommendation-heading">
        <img src="{{ asset('images/learning-banner.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center" fetchpriority="high">
        <div class="absolute inset-0 bg-[#102f50]/80" aria-hidden="true"></div>
        <div class="relative z-10">
            <p class="text-sm font-semibold text-white">Rekomendasi materi untuk Anda</p>
            <h2 id="recommendation-heading" class="mt-2 text-xl font-semibold text-white">Perkuat pemahaman traversal pada binary tree</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-white">Pelajari kembali preorder dan postorder, lalu terapkan pemahamanmu pada praktikum berikutnya.</p>
        </div>
        <a href="{{ route('mahasiswa.course.item', [1, 3]) }}" class="relative z-10 inline-flex min-h-10 items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand-dark hover:bg-[#f1f3f5]">Buka materi rekomendasi</a>
    </section>

    <div class="rounded-2xl bg-[#e9edf1] p-4 sm:p-5">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            {{-- Kartu course asli disimpan di mahasiswa.partials.dashboard-courses-original. --}}
            @include('mahasiswa.partials.semester-chart')

            <aside aria-labelledby="deadline-heading">
                <div class="mb-4 flex min-h-[92px] items-start justify-between gap-2">
                    <div><h2 id="deadline-heading" class="section-heading">Tenggat terdekat</h2><p class="mt-1 text-sm leading-5 text-muted">Pekerjaan yang perlu segera diselesaikan.</p></div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark"><span class="hidden min-[1320px]:inline">Lihat semua</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="rounded-2xl bg-white px-2 py-2 shadow-sm">
                    @forelse(collect(\App\Support\LearningPreview::items())->whereIn('type',['tugas','coding','kuis'])->filter(fn($i)=>!session('learning.submissions.'.$i['id']))->sortBy('due')->take(3) as $item)
                    <a href="{{ route('mahasiswa.course.item', [$item['course'], $item['id']]) }}" class="grid grid-cols-[58px_minmax(0,1fr)] gap-3 rounded-xl px-3 py-4 hover:bg-canvas"><span class="text-xs text-muted">{{ $item['due'] ? \Carbon\Carbon::parse($item['due'])->translatedFormat('d M') : 'Bebas' }}<span class="mt-1 block">{{ $item['due'] ? \Carbon\Carbon::parse($item['due'])->format('H:i') : '' }}</span></span><span><span class="block text-sm font-semibold leading-5">{{ $item['title'] }}</span><span class="mt-1 block text-xs leading-5 text-muted">{{ \App\Support\LearningPreview::course($item['course'])['title'] }}</span></span></a>
                    @empty<p class="p-4 text-sm text-muted">Semua pekerjaan telah dikumpulkan.</p>@endforelse
                </div>
            </aside>

            <section aria-labelledby="discussion-heading">
                <div class="mb-4 flex min-h-[92px] items-start justify-between gap-2">
                    <div><h2 id="discussion-heading" class="section-heading">Diskusi terbaru</h2><p class="mt-1 text-sm leading-5 text-muted">Percakapan dari course aktif.</p></div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark"><span class="hidden min-[1320px]:inline">Buka forum</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="space-y-3">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                    <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="block rounded-xl bg-white p-4 shadow-sm hover:shadow-md">
                        <p class="text-xs font-semibold text-brand">{{ $discussion['course_title'] }}</p>
                        <p class="mt-3 text-sm font-semibold leading-5">{{ $discussion['message'] }}</p>
                        <p class="mt-3 text-xs text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                    </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
