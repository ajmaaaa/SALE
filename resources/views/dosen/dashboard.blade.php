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
        $allCourses = \App\Support\LearningPreview::courses();
        $totalCourses = count($allCourses);
        
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

    @php($unreadDiscussions = collect(\App\Support\LearningPreview::unreadDiscussions())->take(3))
    <section aria-labelledby="dosen-discussion-heading" class="surface overflow-hidden rounded-2xl border border-line/70 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line/50 p-5 sm:p-6">
            <div>
                <h2 id="dosen-discussion-heading" class="text-lg font-semibold text-ink">Pesan belum dibaca</h2>
                <p class="mt-0.5 text-xs text-muted">Pertanyaan dan tanggapan terbaru dari forum kelas.</p>
            </div>
            <a href="{{ route('dosen.discussion.index') }}" class="button-secondary shrink-0 px-3 py-2 text-xs">Buka forum</a>
        </div>
        <div class="divide-y divide-line/60">
            @forelse($unreadDiscussions as $discussion)
                <a href="{{ route('dosen.course.show', $discussion['course']) }}#diskusi-kelas" class="block px-5 py-4 transition hover:bg-canvas sm:px-6">
                    <p class="text-[11px] font-medium text-muted">{{ $discussion['course_title'] }}</p>
                    <p class="mt-1 line-clamp-2 text-sm font-semibold leading-relaxed text-ink">{{ $discussion['message'] }}</p>
                    <p class="mt-1.5 text-xs text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                </a>
            @empty
                <p class="p-5 text-sm text-muted sm:p-6">Belum ada pesan yang perlu dibaca.</p>
            @endforelse
        </div>
    </section>

    {{-- Course List Section: Strong Alignment & Card Repetition --}}
    <section class="surface p-6 rounded-2xl border border-line/70 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-line/50 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-ink">Daftar Kelas Saya</h2>
                <p class="mt-0.5 text-xs text-muted">Kelola penilaian berbasis OBE untuk setiap kelas yang Anda ampu pada semester berjalan.</p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($allCourses as $course)
                @include('learning.partials.course-card', ['course' => $course, 'role' => 'dosen', 'isFirst' => $loop->first])
            @endforeach
        </div>

    </section>
</div>
@endsection
