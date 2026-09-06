@extends('layouts.mahasiswa')

@section('title', 'Forum Diskusi | SALE')
@section('header', 'Forum Diskusi')

@section('content')
@php
    $selectedCourseId = request('course');
    $courseMap = collect($courses)->keyBy('id');

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
        <form method="get" action="{{ route('mahasiswa.discussion.index') }}" class="flex items-center gap-2">
            <label for="course-filter" class="sr-only">Filter Mata Kuliah</label>
            <select id="course-filter" name="course" onchange="this.form.submit()" class="field py-1.5 text-xs font-semibold min-h-9 sm:w-64">
                <option value="">Semua Mata Kuliah ({{ count($courses) }})</option>
                @foreach($courses as $c)
                    <option value="{{ $c['id'] }}" @selected((string)$selectedCourseId === (string)$c['id'])>
                        {{ $c['code'] }} · {{ $c['title'] }}
                    </option>
                @endforeach
            </select>
            @if($selectedCourseId)
                <a href="{{ route('mahasiswa.discussion.index') }}" class="button-secondary py-1.5 text-xs">Reset</a>
            @endif
        </form>
    </header>

    {{-- Clean Discussion Thread List (Entire row is clickable) --}}
    <section class="surface overflow-hidden divide-y divide-line/40" aria-label="Daftar Topik Diskusi">
        <div class="px-5 py-3 bg-white border-b border-line/60 flex items-center justify-between text-xs text-muted font-bold uppercase tracking-wider">
            <span>Daftar Topik &amp; Ruang Diskusi ({{ $discussionItems->count() }} Topik)</span>
            <span>Pesan</span>
        </div>

        @forelse($discussionItems as $item)
            @php
                $course = $courseMap[$item['course']] ?? ['id' => $item['course'], 'code' => 'MK', 'title' => 'Mata Kuliah', 'lecturer' => 'Dosen'];
                $messages = \App\Support\LearningPreview::discussions($item['id']);
                $messageCount = count($messages);
            @endphp
            <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}#diskusi"
               class="group flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-canvas transition">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-ink group-hover:text-brand transition">
                        {{ $item['title'] }}
                    </h3>
                    <p class="mt-0.5 text-xs text-muted">
                        <span class="font-medium text-ink">{{ $course['code'] }}</span>
                        <span>·</span>
                        <span>{{ $course['title'] }}</span>
                        <span>·</span>
                        <span>{{ $item['module'] }}</span>
                    </p>
                </div>
                <div class="shrink-0 flex items-center justify-center w-8">
                    @if($messageCount > 0)
                        <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 text-xs font-bold rounded-full bg-blue-600 text-white" title="{{ $messageCount }} pesan diskusi">
                            {{ $messageCount }}
                        </span>
                    @else
                        <span class="text-xs text-muted font-normal" title="Belum ada pesan">0</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="p-8 text-center text-xs text-muted">
                Belum ada ruang diskusi untuk filter mata kuliah yang dipilih.
            </div>
        @endforelse
    </section>
</div>
@endsection
