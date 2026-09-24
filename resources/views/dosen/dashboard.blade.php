@extends('layouts.mahasiswa')

@section('title', 'Dashboard Dosen | SALE')
@section('header', 'Dashboard Dosen')

@section('content')
<div class="space-y-8">
    <header class="pb-1">
        <h1 class="page-heading">Dashboard Dosen</h1>
        <p class="page-description">Ringkasan perkuliahan dan status penilaian kelas Anda semester ini.</p>
    </header>

    {{-- Simple Dashboard: Only 2 Main Stat Cards --}}
    @php
        $allCourses = collect($courses);
        $totalCourses = $allCourses->count();
        
        $classesList = [
            ['data_status' => 'belum_selesai'],
            ['data_status' => 'selesai'],
            ['data_status' => 'belum_selesai'],
            ['data_status' => 'belum_selesai'],
            ['data_status' => 'selesai'],
            ['data_status' => 'belum_selesai'],
        ];
        $pendingCount = count(array_filter($classesList, fn($c) => $c['data_status'] === 'belum_selesai'));
    @endphp

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="surface p-5 flex items-center justify-between gap-4 border border-line/50">
            <div>
                <p class="text-sm text-muted">Jumlah Course (Matkul)</p>
                <p class="mt-0.5 text-xl font-semibold text-ink">{{ $totalCourses }} <span class="text-sm font-normal text-muted">matkul aktif</span></p>
            </div>
            <a href="{{ route('dosen.course.index') }}" class="button-secondary text-xs shrink-0">Kelola Matkul</a>
        </div>
        <div class="surface p-5 flex items-center justify-between gap-4 border border-line/50">
            <div>
                <p class="text-sm text-muted">Penilaian Belum Dinilai</p>
                <p class="mt-0.5 text-xl font-semibold text-ink">{{ $pendingCount }} <span class="text-sm font-normal text-muted">kelas</span></p>
            </div>
            <a href="{{ route('dosen.grades') }}" class="button-secondary text-xs shrink-0">Lihat Penilaian</a>
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.75fr)] xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">
        {{-- Course List Section: Strong Alignment & Card Repetition --}}
        <section aria-labelledby="dosen-course-heading" class="min-w-0">
            <div class="mb-4 flex h-12 items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="dosen-course-heading" class="section-heading truncate text-base sm:text-lg">Daftar Kelas Saya</h2>
                    <p class="mt-0.5 truncate text-xs text-muted">Kelas yang Anda ampu semester ini</p>
                </div>
                <a href="{{ route('dosen.course.index') }}" class="shrink-0 pt-0.5 text-xs font-semibold text-brand hover:text-brand-dark">Lihat semua</a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @forelse($allCourses as $course)
                    @include('learning.partials.course-card', ['course' => $course, 'role' => 'dosen', 'isFirst' => $loop->first])
                @empty
                    <p class="col-span-full py-2 text-xs text-muted">Belum ada kelas.</p>
                @endforelse
            </div>
        </section>

        @php($unreadDiscussions = collect(\App\Support\LearningPreview::unreadDiscussions())->take(3))
        <section aria-labelledby="dosen-discussion-heading" class="min-w-0">
            <div class="mb-4 flex h-12 items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="dosen-discussion-heading" class="section-heading truncate text-base sm:text-lg">Pesan belum dibaca</h2>
                    <p class="mt-0.5 truncate text-xs text-muted">Pesan masuk dari forum kelas</p>
                </div>
                <a href="{{ route('dosen.discussion.index') }}" class="shrink-0 pt-0.5 text-xs font-semibold text-brand hover:text-brand-dark">Buka forum</a>
            </div>
            <div class="divide-y divide-line/60 overflow-hidden rounded-xl border border-line/60 bg-white shadow-sm">
                @forelse($unreadDiscussions as $discussion)
                    <a href="{{ route('dosen.course.show', $discussion['course']) }}#diskusi-kelas" class="block p-4 text-xs transition duration-200 hover:bg-canvas">
                        <p class="text-[11px] font-medium text-muted">{{ $discussion['course_title'] }}</p>
                        <p class="mt-1 line-clamp-2 text-xs font-semibold leading-relaxed text-ink">{{ $discussion['message'] }}</p>
                        <p class="mt-1.5 text-xs leading-4 text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                    </a>
                @empty
                    <p class="p-4 text-sm text-muted">Belum ada pesan terbaru.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
