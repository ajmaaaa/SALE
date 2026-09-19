@extends('layouts.mahasiswa')

@section('title', 'Dashboard | SALE')
@section('header', 'Dashboard')

@section('content')
@php
    $allCourses = isset($courses) ? collect($courses) : collect(\App\Support\LearningPreview::courses());
    $totalCourses = $allCourses->count();
    $allItems = collect(\App\Support\LearningPreview::items());
    $pendingTasks = $allItems
        ->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas'])
        ->filter(fn($i) => !session('learning.submissions.' . $i['id']))
        ->count();
@endphp
<div class="space-y-8">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, Ahmad.</h1>
            <p class="page-description">Lanjutkan perkuliahan dari materi terakhir atau periksa pekerjaan yang segera berakhir.</p>
        </div>
        <a href="{{ route('mahasiswa.course.item', [1, 3]) }}" class="button-primary shrink-0">Lanjutkan belajar</a>
    </header>

    <section class="rounded-xl bg-[#102f50] p-6 sm:p-7 text-white shadow-2xs border border-[#1b3f68]" aria-labelledby="recommendation-heading">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-slate-200">
                    <span>Rekomendasi Materi</span>
                </div>
                <h2 id="recommendation-heading" class="text-lg sm:text-xl font-bold text-white tracking-tight">Perkuat pemahaman traversal pada binary tree</h2>
                <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">Pelajari kembali preorder, inorder, dan postorder, lalu terapkan pemahaman Anda pada lembar praktikum interaktif.</p>
            </div>
            <a href="{{ route('mahasiswa.course.item', [1, 3]) }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-white px-5 text-xs font-bold text-[#102f50] hover:bg-slate-100 transition shadow-xs">
                Buka Materi Rekomendasi →
            </a>
        </div>
    </section>

    <div class="rounded-2xl bg-[#e9edf1] p-4 sm:p-5">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            @include('mahasiswa.partials.semester-chart')

            <aside aria-labelledby="deadline-heading">
                <div class="mb-4 flex min-h-[56px] items-start justify-between gap-2">
                    <div><h2 id="deadline-heading" class="section-heading">Tenggat terdekat</h2><p class="mt-1 text-sm leading-5 text-muted">Pekerjaan yang perlu segera diselesaikan.</p></div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark"><span class="hidden min-[1320px]:inline">Lihat semua</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="rounded-xl bg-white px-2 py-2 shadow-sm border border-line/60">
                    @forelse(collect(\App\Support\LearningPreview::items())->whereIn('type',['tugas','coding','kuis','uts','uas'])->filter(fn($i)=>!session('learning.submissions.'.$i['id']))->sortBy('due')->take(4) as $item)
                        @if(!$loop->first)
                            <div class="mx-4 h-px bg-[#e7eaee]" aria-hidden="true"></div>
                        @endif
                        @php
                            $isToday = $item['due'] && \Carbon\Carbon::parse($item['due'])->isToday();
                            $dateText = $item['due'] ? ($isToday ? 'Hari ini' : \Carbon\Carbon::parse($item['due'])->translatedFormat('d M')) : 'Bebas';
                            $timeText = $item['due'] ? \Carbon\Carbon::parse($item['due'])->format('H.i') : '';
                        @endphp
                        <a href="{{ route('mahasiswa.course.item', [$item['course'], $item['id']]) }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9] transition">
                            <span>
                                <span class="block text-xs font-semibold {{ $isToday ? 'text-danger' : 'text-ink' }}">{{ $dateText }}</span>
                                @if($timeText)
                                    <span class="mt-1 block text-xs text-muted">{{ $timeText }}</span>
                                @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold leading-5 text-ink">{{ $item['title'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-muted">{{ \App\Support\LearningPreview::course($item['course'])['title'] }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="p-4 text-sm text-muted">Semua pekerjaan telah dikumpulkan.</p>
                    @endforelse
                </div>
            </aside>

            <section aria-labelledby="discussion-heading">
                <div class="mb-4 flex min-h-[56px] items-start justify-between gap-2">
                    <div><h2 id="discussion-heading" class="section-heading">Diskusi terbaru</h2><p class="mt-1 text-sm leading-5 text-muted">Percakapan dari course aktif.</p></div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark"><span class="hidden min-[1320px]:inline">Buka forum</span><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="rounded-xl bg-white shadow-sm divide-y divide-line/60">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                        <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="block p-4 text-xs transition duration-200 hover:bg-canvas">
                            <p class="text-[11px] font-medium text-muted">{{ $discussion['course_title'] }}</p>
                            <p class="mt-1 text-xs font-semibold leading-relaxed text-ink line-clamp-2">{{ $discussion['message'] }}</p>
                            <p class="mt-1.5 text-[11px] text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
