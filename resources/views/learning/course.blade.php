@extends('layouts.mahasiswa')

@section('title', $course['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $role = request()->is('dosen*') ? 'dosen' : 'mahasiswa';
    $cpmkList = \App\Support\AcademicPreview::config($course['id'])['cpmk'] ?? [];
    $modules = collect($items)->where('type', '!=', 'pengumuman')->groupBy('module');
    $announcements = collect($items)->where('type', 'pengumuman');
    $materiItems = collect($items)->where('type', 'materi');
    $tugasItems = collect($items)->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas']);
    $uncompletedTasksCount = $tugasItems->filter(fn($item) => empty(session('learning.submissions.'.$item['id'])))->count();
    $allUsers = \App\Support\AdminPreview::users();
    $enrolledStudents = array_filter($allUsers, fn($u) => $u['role'] === 'mahasiswa');
@endphp

<div class="space-y-7">
    {{-- Breadcrumb --}}
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-muted">
        <a href="{{ route($role.'.course.index') }}" class="hover:text-brand">Course</a>
        <span aria-hidden="true">/</span>
        <span class="text-ink font-semibold">{{ $course['code'] }}</span>
    </nav>

    {{-- Course Cover Image if present --}}
    @if(!empty($course['cover']))
        <div class="overflow-hidden rounded-xl shadow-sm">
            <img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul course" class="h-48 w-full object-cover">
        </div>
    @endif

    {{-- Course Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-muted font-medium">
                <span class="font-semibold text-ink">{{ $course['code'] }}</span>
                <span>·</span>
                <span>3 SKS</span>
                <span>·</span>
                <span>Semester Ganjil 2026/2027</span>
                <span>·</span>
                <span>Wajib</span>
            </div>
            <h1 class="page-heading mt-2">{{ $course['title'] }}</h1>
            <p class="page-description mt-1">{{ $course['description'] }}</p>
        </div>

        @if($role === 'dosen')
            <div class="flex flex-wrap gap-2.5 shrink-0">
                <a href="{{ route('dosen.academic', $course['id']) }}" class="button-secondary">
                    Atur CPL &amp; CPMK
                </a>
                <a href="{{ route('dosen.gradebook', ['course' => $course['id']]) }}" class="button-secondary">
                    Rekap Nilai
                </a>
                <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-primary">
                    + Tambah Konten
                </a>
            </div>
        @else
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('mahasiswa.nilai') }}" class="button-secondary text-xs">
                    Lihat Nilai Saya →
                </a>
            </div>
        @endif
    </header>

    {{-- Main Grid --}}
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
        {{-- Left: Video player & Weekly module accordions --}}
        <div class="min-w-0 space-y-7">
            {{-- 16:9 Video Player Card (Restored from beloved original design) --}}
            <section aria-labelledby="video-heading">
                <div class="aspect-video overflow-hidden rounded-xl bg-[#172633] shadow-md relative group">
                    <div class="flex h-full flex-col items-center justify-center px-6 text-center text-white">
                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-white/10 backdrop-blur-sm text-white transition group-hover:scale-110 group-hover:bg-brand">
                            <svg class="h-7 w-7 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="m9 7 9 5-9 5V7z"/>
                            </svg>
                        </div>
                        <h2 id="video-heading" class="text-xl font-bold text-white">
                            {{ $course['title'] }}: Pengantar &amp; Konsep Utama
                        </h2>
                        <p class="mt-1 text-sm text-[#c9d3d9]">Video pengantar perkuliahan · 24 menit</p>
                        @if(!empty($course['video']))
                            <a href="{{ $course['video'] }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-[#172633] shadow hover:bg-slate-100 transition">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                Putar Video Pengantar ↗
                            </a>
                        @else
                            <button type="button" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-[#172633] shadow hover:bg-slate-100 transition">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                Putar Video
                            </button>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Modules List --}}
            <section aria-labelledby="module-heading">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 id="module-heading" class="section-heading">Materi &amp; pekerjaan kelas</h2>
                        <p class="mt-1 text-xs text-muted">Buka konten untuk melihat lampiran, instruksi, dan diskusinya.</p>
                    </div>
                    @if($role === 'dosen')
                        <a href="{{ route('dosen.academic', $course['id']) }}" class="quiet-link shrink-0 text-xs">
                            Atur Bobot &amp; CPMK
                        </a>
                    @endif
                </div>

                <div class="space-y-4">
                    @forelse($modules as $moduleName => $contents)
                        <section class="surface overflow-hidden">
                            <div class="border-b border-line/50 px-5 py-3.5 flex items-center justify-between">
                                <h3 class="font-semibold text-ink text-sm sm:text-base">{{ $moduleName }}</h3>
                                <span class="text-xs text-muted">{{ count($contents) }} materi &amp; tugas</span>
                            </div>

                            <div class="divide-y divide-line/40">
                                @foreach($contents as $item)
                                    @php
                                        $hasSubmission = session('learning.submissions.'.$item['id']);
                                    @endphp
                                    <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <span class="shrink-0 text-muted group-hover:text-ink transition">
                                            @if(in_array($item['type'], ['kuis', 'uts', 'uas']))
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                                            @elseif($item['type'] === 'materi')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h8l4 4v14H5V3zM14 3v5h5"/></svg>
                                            @else
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            @endif
                                        </span>

                                        {{-- Content info: Clean, natural hierarchy without colored label on top --}}
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h4>
                                            <p class="mt-0.5 text-xs text-muted">
                                                <span>{{ \App\Support\LearningPreview::labels()[$item['type']] ?? \App\Support\LearningPreview::label($item['type']) }}</span>
                                                @if(!empty($item['cpmk']))
                                                    <span>· {{ $item['cpmk'] }}</span>
                                                @endif
                                                <span>· {{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Materi belajar' }}</span>
                                                <span>· {{ count(\App\Support\LearningPreview::discussions($item['id'])) }} diskusi</span>
                                            </p>
                                        </div>

                                        {{-- Submission status if any --}}
                                        @if($hasSubmission)
                                            <span class="text-xs font-medium text-muted shrink-0">
                                                Dikumpulkan
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="surface p-8 text-center">
                            <h3 class="font-semibold text-ink">Belum ada modul aktif</h3>
                            <p class="mt-1 text-xs text-muted">
                                {{ $role === 'dosen' ? 'Tambahkan materi, tugas, atau kuis untuk kelas ini.' : 'Dosen belum membagikan materi untuk kelas ini.' }}
                            </p>
                            @if($role === 'dosen')
                                <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-primary mt-4 inline-flex">
                                    + Tambah Konten
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Right: Course Announcements, Lecturer Info, CPMK --}}
        <aside class="space-y-5">
            {{-- Lecturer Info Card (Clean, no Tanya button) --}}
            <section class="surface p-5" aria-labelledby="lecturer-heading">
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Dosen Pengampu</p>
                <h2 id="lecturer-heading" class="mt-2 text-base font-semibold text-ink">{{ $course['lecturer'] }}</h2>
                <p class="mt-1 text-xs text-muted">Fakultas Ilmu Komputer</p>
                <p class="mt-3 text-xs leading-relaxed text-muted">Diskusikan pertanyaan melalui materi atau tugas terkait di kelas ini.</p>
            </section>

            {{-- Announcements Card (Clean surface, not dark blue block) --}}
            <section class="surface p-5" aria-labelledby="announcement-heading">
                <div class="flex items-center justify-between">
                    <h2 id="announcement-heading" class="text-sm font-semibold text-ink">Pengumuman</h2>
                    @if(count($announcements) > 0)
                        <span class="text-xs text-muted">{{ count($announcements) }}</span>
                    @endif
                </div>
                <div class="mt-3 divide-y divide-line/50">
                    @forelse($announcements as $announcement)
                        <article class="py-3 first:pt-0 last:pb-0">
                            <a href="{{ route('mahasiswa.course.item', [$course['id'], $announcement['id']]) }}" class="group block">
                                <h3 class="text-sm font-semibold text-ink group-hover:text-brand">{{ $announcement['title'] }}</h3>
                                <p class="mt-1 text-xs leading-5 text-muted">{{ \Illuminate\Support\Str::limit($announcement['body'], 120) }}</p>
                            </a>
                        </article>
                    @empty
                        <p class="pt-2 text-xs text-muted">Belum ada pengumuman untuk kelas ini.</p>
                    @endforelse
                </div>
            </section>

            {{-- CPMK & CPL Outcomes Card (Neutral clean design, no rainbow/brand chips) --}}
            <details class="surface p-5">
                <summary class="cursor-pointer text-xs font-semibold text-ink">Capaian Pembelajaran (CPMK)</summary>
                <div class="mt-3 divide-y divide-line/40 text-xs">
                    @forelse($cpmkList as $cpmk)
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-ink">{{ $cpmk['code'] }}</span>
                                <span class="text-[11px] text-muted">{{ $cpmk['cpl'] ?? 'CPL' }}</span>
                            </div>
                            <p class="mt-1 text-muted leading-relaxed">{{ $cpmk['description'] }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-muted">CPMK belum diatur oleh dosen.</p>
                    @endforelse
                </div>
            </details>
        </aside>
    </div>
</div>
@endsection
