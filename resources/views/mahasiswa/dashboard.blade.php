@extends('layouts.mahasiswa')

@section('title', 'Dashboard | SALE')
@section('header', 'Dashboard')

@section('content')
@php
    $allCourses = collect($courses);
    $studentName = $student?->name ?? session('auth_user.name', 'Ahmad Maulana');
    $firstName = str($studentName)->before(' ');
@endphp
<div class="space-y-7">
    <header class="flex flex-col gap-5 border-b border-line/60 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, {{ $firstName }}.</h1>
    <p class="page-description">Lanjutkan perkuliahan dari materi terakhir atau ikuti percakapan di forum kelas.</p>
        </div>
        <a href="{{ route('mahasiswa.course.show', 1) }}" class="button-primary shrink-0">Lanjutkan belajar</a>
    </header>

    <section class="grid gap-4 sm:grid-cols-2" aria-label="Ringkasan akademik">
        <div class="surface flex items-center justify-between gap-4 border border-line/50 p-5">
            <div>
                <p class="text-sm text-muted">Course diikuti</p>
                <p class="mt-0.5 text-xl font-semibold text-ink">{{ $allCourses->count() }} <span class="text-sm font-normal text-muted">course aktif</span></p>
            </div>
            <a href="{{ route('mahasiswa.course.index') }}" class="button-secondary shrink-0 text-xs">Lihat Course</a>
        </div>
        <div class="surface flex items-center justify-between gap-4 border border-line/50 p-5">
            <div>
                <p class="text-sm text-muted">Tugas belum dikerjakan</p>
                <p class="mt-0.5 text-xl font-semibold text-ink">{{ $activeItems->count() }} <span class="text-sm font-normal text-muted">pekerjaan</span></p>
            </div>
            <a href="{{ route('mahasiswa.assignment.index') }}" class="button-secondary shrink-0 text-xs">Lihat Tugas</a>
        </div>
    </section>

    <section class="rounded-xl border border-[#1b3f68] bg-[#102f50] p-6 text-white shadow-2xs sm:p-7" aria-labelledby="recommendation-heading">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="max-w-3xl space-y-2">
                <div class="inline-flex items-center rounded bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-slate-200">Rekomendasi Materi</div>
                <h2 id="recommendation-heading" class="text-lg font-bold tracking-tight text-white sm:text-xl">Perkuat pemahaman traversal pada binary tree</h2>
                <p class="text-xs leading-relaxed text-slate-200 sm:text-sm">Pelajari kembali preorder, inorder, dan postorder, lalu terapkan pemahaman Anda pada lembar praktikum interaktif.</p>
            </div>
            <a href="{{ route('mahasiswa.course.item', [1, 3]) }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-white px-5 text-xs font-bold text-[#102f50] shadow-xs transition hover:bg-slate-100">Buka Materi Rekomendasi</a>
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.75fr)] xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">
        <section aria-labelledby="course-heading" class="min-w-0">
            <div class="mb-4 flex h-12 items-start justify-between gap-3">
                <div class="min-w-0"><h2 id="course-heading" class="section-heading truncate text-base sm:text-lg">Course semester ini</h2><p class="mt-0.5 truncate text-xs text-muted">Kelas aktif program studi</p></div>
                <a href="{{ route('mahasiswa.course.index') }}" class="shrink-0 pt-0.5 text-xs font-semibold text-brand hover:text-brand-dark">Lihat semua</a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($allCourses->take(4) as $course)
                    @include('learning.partials.course-card', ['course' => $course, 'role' => 'mahasiswa', 'isFirst' => $loop->first])
                @endforeach
            </div>
        </section>

        <div class="min-w-0 space-y-6">
            <section aria-labelledby="discussion-heading" class="min-w-0">
                <div class="mb-4 flex h-12 items-start justify-between gap-3">
                    <div class="min-w-0"><h2 id="discussion-heading" class="section-heading truncate text-base sm:text-lg">Pesan belum dibaca</h2><p class="mt-0.5 truncate text-xs text-muted">Pesan masuk dari forum kelas</p></div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="shrink-0 pt-0.5 text-xs font-semibold text-brand hover:text-brand-dark">Buka forum</a>
                </div>
                <div class="divide-y divide-line/60 overflow-hidden rounded-xl border border-line/60 bg-white shadow-sm">
                    @forelse(collect(\App\Support\LearningPreview::unreadDiscussions())->take(3) as $discussion)
                        <a href="{{ route('mahasiswa.course.show', $discussion['course']) }}#diskusi-kelas" class="block p-4 text-xs transition duration-200 hover:bg-canvas">
                            <p class="text-[11px] font-medium text-muted">{{ $discussion['course_title'] }}</p>
                            <p class="mt-1 line-clamp-2 text-xs font-semibold leading-relaxed text-ink">{{ $discussion['message'] }}</p>
                            <p class="mt-1.5 grid w-fit grid-cols-[auto_1px_auto] items-center gap-2 text-xs leading-4 text-muted"><span>{{ $discussion['author'] }}</span><span class="h-3 w-px bg-line" aria-hidden="true"></span><span>{{ $discussion['time'] }}</span></p>
                        </a>
                    @empty
                        <p class="p-4 text-sm text-muted">Belum ada pesan terbaru.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
