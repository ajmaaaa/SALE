@extends('layouts.mahasiswa')

@section('title', 'Forum Diskusi | SALE')
@section('header', 'Forum Diskusi')

@section('content')
@php
    $selectedCourseId = request('course');
    $courseMap = collect($courses)->keyBy('id');
    $isDosenRoute = request()->is('dosen*');
    $discussionRoute = $isDosenRoute ? 'dosen.discussion.index' : 'mahasiswa.discussion.index';
    $courseRoute = $isDosenRoute ? 'dosen.course.show' : 'mahasiswa.course.show';

    // Filter non-announcement items
    $discussionItems = collect($items)->filter(fn($i) => ($i['type'] ?? '') !== 'pengumuman');

    if ($selectedCourseId) {
        $discussionItems = $discussionItems->filter(fn($i) => (string)$i['course'] === (string)$selectedCourseId);
    }
@endphp

<div class="space-y-6">
    {{-- Clean Header with Course Selector --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-heading">Forum Diskusi Perkuliahan</h1>
            <p class="page-description">Ruang tanya jawab dan diskusi materi antar mahasiswa dan dosen pengampu.</p>
        </div>

        {{-- Filter Mata Kuliah (Minimalis) --}}
        <form method="get" action="{{ route($discussionRoute) }}" class="flex items-center gap-2">
            <label for="course-filter" class="sr-only">Filter Mata Kuliah</label>
            <select id="course-filter" name="course" onchange="this.form.submit()" class="field py-1.5 text-xs font-semibold min-h-9 sm:w-64">
                <option value="">Semua Mata Kuliah ({{ count($courses) }})</option>
                @foreach($courses as $c)
                    <option value="{{ $c['id'] }}" @selected((string)$selectedCourseId === (string)$c['id'])>
                        {{ $c['code'] }} - {{ $c['title'] }}
                    </option>
                @endforeach
            </select>
            @if($selectedCourseId)
                <a href="{{ route($discussionRoute) }}" class="button-secondary py-1.5 text-xs">Reset</a>
            @endif
        </form>
    </header>

    {{-- Clean Discussion Thread List per Course (Entire row is clickable) --}}
    <section class="surface overflow-hidden divide-y divide-line/40" aria-label="Daftar Forum Diskusi Kelas">
        <div class="px-5 py-3 bg-white border-b border-line/60 flex items-center justify-between text-xs text-muted font-bold uppercase tracking-wider">
            <span>Daftar Forum Diskusi Kelas</span>
            <span>Aktivitas Pesan</span>
        </div>

        @forelse($courses as $c)
            @if(!$selectedCourseId || (string)$selectedCourseId === (string)$c['id'])
                @php
                    $msgCount = \App\Support\LearningPreview::unreadDiscussionCount($c['id']);
                @endphp
                <a href="{{ route($courseRoute, $c['id']) }}#diskusi-kelas"
                   class="group flex items-center justify-between gap-3 px-5 py-4 hover:bg-canvas transition">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $c['code'] }}</span>
                            <h3 class="text-sm font-semibold text-ink group-hover:text-brand transition">
                                {{ $c['title'] }}
                            </h3>
                        </div>
                        <p class="mt-1 text-xs text-muted flex flex-wrap items-center gap-2">
                            <span>Pengampu: {{ $c['lecturer'] ?? 'Dosen Pengampu' }}</span>
                            <span>(Forum Diskusi &amp; Konsultasi Akademik)</span>
                        </p>
                    </div>
                    <div class="shrink-0 flex items-center justify-center">
                        @if($msgCount > 0)
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#4c1d95] px-2.5 text-xs font-bold text-white" title="{{ $msgCount }} pesan belum dibaca">
                                {{ $msgCount }} belum dibaca
                            </span>
                        @else
                            <span class="text-xs text-muted font-normal">Tidak ada pesan baru</span>
                        @endif
                    </div>
                </a>
            @endif
        @empty
            <div class="p-8 text-center text-xs text-muted">
                Belum ada mata kuliah yang terdaftar.
            </div>
        @endforelse
    </section>
</div>
@endsection
