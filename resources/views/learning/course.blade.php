@extends('layouts.mahasiswa')

@section('title', $course['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $currentRole = auth()->user()?->role?->name ?? (session('auth_user.role') ?? (request()->is('dosen*') ? 'dosen' : 'mahasiswa'));
    $isDosen = ($currentRole === 'dosen' || request()->is('dosen*')) && $currentRole !== 'mahasiswa' && session('auth_user.role') !== 'mahasiswa';
    $role = $isDosen ? 'dosen' : 'mahasiswa';
    $materiItems = collect($items)->where('type', 'materi');
    $tugasItems = collect($items)->whereIn('type', ['tugas', 'coding', 'kuis']);
    $uncompletedTasksCount = $tugasItems->filter(fn($item) => empty(session('learning.submissions.'.$item['id'])))->count();
    if (isset($classSection)) {
        $classSection->loadMissing(['students', 'dosen', 'dosenPendamping']);
        $enrolledStudents = $classSection->students->map(fn($user) => [
            'name' => $user->name,
            'number' => $user->nim_nidn ?? $user->email,
            'role' => 'mahasiswa',
        ])->values()->all();
        $courseMembers = collect([$classSection->dosen, $classSection->dosenPendamping])
            ->filter()
            ->map(fn($user) => [
                'name' => $user->name,
                'number' => $user->nim_nidn ?? $user->email,
                'role' => 'dosen',
            ])
            ->concat($enrolledStudents)
            ->values();
    } else {
        $allUsers = \App\Support\AdminPreview::users();
        $enrolledStudents = array_filter($allUsers, fn($u) => $u['role'] === 'mahasiswa');
        $courseMembers = collect($allUsers)
            ->filter(fn($u) => in_array($u['role'], ['mahasiswa', 'dosen'], true))
            ->sortBy(fn($u) => $u['role'] === 'dosen' ? 0 : 1)
            ->values();
    }
    $courseVideo = $course['video'] ?? null;
    $courseVideoType = $course['video_type'] ?? (filter_var($courseVideo, FILTER_VALIDATE_URL) ? 'url' : 'file');
    $youtubeEmbed = $courseVideoType === 'url' ? \App\Support\LearningPreview::youtubeEmbedUrl($courseVideo) : null;
    $courseVideoMeta = in_array($courseVideoType, ['file', 'image'], true) && $courseVideo ? (\App\Support\LearningPreview::fileMeta($courseVideo) ?? []) : [];
@endphp

<div class="space-y-0">
    {{-- Course Cover Image if present --}}
    @if(!empty($course['cover']))
        <div class="overflow-hidden rounded-xl shadow-sm mb-4">
            <img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul course" class="h-48 w-full object-cover">
        </div>
    @endif

    {{-- Sticky Course Header (Breadcrumb, Title, Code, Lecturer, Enrolled Count & Actions) --}}
    <div id="course-header-sticky" class="sticky top-16 z-20 -mt-6 lg:-mt-7 pt-5 lg:pt-6 pb-2.5 bg-[#f4f5f7] -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 xl:-mx-10 xl:px-10">
        <div class="space-y-2.5">
            {{-- Breadcrumb --}}
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route($role.'.course.index') }}">
                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Course</span>
                </a>
                <svg class="h-3.5 w-3.5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="font-semibold text-slate-800" aria-current="page">
                    {{ $course['code'] }}
                </span>
            </nav>

            {{-- Course Header --}}
            <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-baseline gap-2 text-xs font-medium leading-4 text-muted">
                        <span class="font-mono font-semibold leading-4 text-ink">{{ $course['code'] }}</span>
                        <span class="h-3 w-px self-center bg-line" aria-hidden="true"></span>
                        <span class="leading-4">{{ $course['sks'] ?? '3 SKS' }}</span>
                        <span class="h-3 w-px self-center bg-line" aria-hidden="true"></span>
                        <span class="leading-4">{{ $course['semester'] ?? 'Semester Ganjil 2026/2027' }}</span>
                    </div>
                    <h1 class="page-heading mt-2">{{ $course['title'] }}</h1>
                    <p class="mt-1 text-sm text-muted">
                        Dosen Pengampu: <span class="font-medium text-ink">{{ $course['lecturer'] }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    {{-- Tombol Jumlah Mahasiswa: Cukup icon dan angka saja tanpa tulisan 'mahasiswa' --}}
                    <button type="button" onclick="document.getElementById('enrolled-students-modal').showModal()" class="button-secondary flex items-center gap-1.5 text-xs py-1.5 px-2.5 shadow-2xs hover:bg-canvas transition" title="{{ count($enrolledStudents) }} Mahasiswa Terdaftar">
                        <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="font-mono font-bold text-ink">{{ count($enrolledStudents) }}</span>
                        <span class="sr-only">mahasiswa</span>
                    </button>

                    @if($role === 'dosen')
                        <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-primary text-xs py-1.5 px-3 font-semibold shadow-2xs">
                            + Tambah Konten
                        </a>
                    @endif
                </div>
            </header>
        </div>

        {{-- Efek bayangan pemisah murni (tanpa garis) tepat pada batas atas card video tanpa jarak, khusus pada area kolom kiri jika ada video --}}
        @if(!empty($courseVideo))
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_380px] gap-7 pointer-events-none mt-2" aria-hidden="true">
                <div class="h-3 bg-[#f4f5f7] shadow-[0_10px_20px_-3px_rgba(29,39,48,0.12)]"></div>
                <div class="hidden lg:block"></div>
            </div>
        @endif
    </div>

    {{-- 2-Column Layout: Konten di Kiri & Forum Diskusi Kelas di Samping (Kanan) --}}
    <div id="course-main-grid" class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_380px] gap-7 mt-0">
        {{-- KOLOM KIRI: Video Pengantar & Modul Terpisah (Materi & Tugas) --}}
        <div class="min-w-0 space-y-5">

            {{-- 16:9 Media Banner / Player Card: hanya tampil jika course memiliki video atau foto yang dipasang. --}}
            @if(!empty($courseVideo))
                @php
                    $isImageMedia = ($course['media_kind'] ?? '') === 'image'
                        || $courseVideoType === 'image'
                        || str_starts_with($courseVideoMeta['mime'] ?? '', 'image/');
                @endphp
                <section aria-labelledby="video-heading">
                    <div id="course-video-card" class="aspect-video overflow-hidden rounded-xl bg-[#172633] shadow-md relative group">
                    @if($isImageMedia)
                        <div class="relative h-full w-full flex items-center justify-center bg-slate-900 overflow-hidden">
                            <img id="video-heading" src="{{ route('preview.file', $courseVideo) }}" alt="{{ $course['video_title'] ?? $course['title'] }}" class="h-full w-full object-contain">
                            <div class="absolute bottom-3 left-3 rounded-lg bg-black/60 px-3 py-1.5 text-xs text-white backdrop-blur-sm flex items-center gap-2">
                                <svg class="h-4 w-4 text-brand-light" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                                <span>Media Foto Utama &bull; {{ $courseVideoMeta['name'] ?? ($course['video_title'] ?? '') }}</span>
                            </div>
                        </div>
                    @elseif($youtubeEmbed)
                        <iframe id="video-heading" class="h-full w-full border-0" src="{{ $youtubeEmbed }}" title="Video {{ $course['title'] }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    @elseif($courseVideoType === 'file' && !empty($courseVideo) && !empty($courseVideoMeta))
                        <video id="video-heading" class="h-full w-full object-contain" controls preload="metadata" title="Video {{ $courseVideoMeta['name'] ?? $course['title'] }}">
                            <source src="{{ route('preview.file', $courseVideo) }}" type="{{ $courseVideoMeta['mime'] ?? 'video/mp4' }}">
                            Browser Anda tidak mendukung pemutaran video.
                        </video>
                    @elseif($courseVideoType === 'url' && !empty($courseVideo))
                        <video id="video-heading" class="h-full w-full object-contain" controls preload="metadata" src="{{ $courseVideo }}" title="Video {{ $course['title'] }}"></video>
                    @else
                        <div class="flex h-full items-center justify-center px-6 text-center text-sm text-[#c9d3d9]">Video pengantar belum ditambahkan.</div>
                    @endif
                    </div>
                </section>
            @endif

            {{-- TABS NAV: MATERI & TUGAS (Sticky sehingga saat materi/tugas panjang di-scroll, tab tetap terlihat dan hanya list yang meluncur di bawahnya) --}}
            <div id="course-tabs-container" class="sticky z-10 bg-[#f4f5f7] pt-2 pb-1 transition-all">
                <nav class="flex border-b border-line/60 gap-6" aria-label="Tab konten kelas">
                    <button type="button" id="tab-btn-materi" onclick="switchCourseTab('materi')" class="pb-3 text-sm font-semibold border-b-2 -mb-px border-brand text-brand flex items-center gap-2 transition">
                        <span>Materi</span>
                        <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ $materiItems->count() }}</span>
                    </button>
                    <button type="button" id="tab-btn-tugas" onclick="switchCourseTab('tugas')" class="pb-3 text-sm font-medium border-b-2 -mb-px border-transparent text-muted hover:text-ink flex items-center gap-2 transition">
                        <span>Tugas</span>
                        @if($uncompletedTasksCount > 0)
                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-600">{{ $uncompletedTasksCount }}</span>
                        @else
                            <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ $tugasItems->count() }}</span>
                        @endif
                    </button>
                </nav>
                {{-- Efek bayangan pemisah murni di bawah tab materi/tugas saat list materi atau tugas meluncur di bawahnya --}}
                <div class="h-2 -mt-1 bg-[#f4f5f7] shadow-[0_8px_16px_-2px_rgba(29,39,48,0.10)] pointer-events-none" aria-hidden="true"></div>
            </div>

                {{-- PANEL MATERI --}}
                <div id="course-panel-materi" class="space-y-4">
                    @forelse($materiItems->groupBy('module') as $moduleName => $contents)
                        <section class="surface overflow-hidden">
                            <div class="border-b border-line/50 px-5 py-3 flex items-center justify-between bg-canvas/30">
                                <h3 class="font-semibold text-ink text-sm">{{ $moduleName }}</h3>
                                <span class="text-xs text-muted">{{ count($contents) }} materi</span>
                            </div>

                            <div class="divide-y divide-line/40">
                                @foreach($contents as $item)
                                    @php
                                        $isCodingMaterial = ($item['material_mode'] ?? null) === 'coding';
                                        $materialUrl = $isCodingMaterial
                                            ? route('mahasiswa.assignment.code', $item['id'])
                                            : ($isDosen ? route('dosen.course.item', [$course['id'], $item['id']]) : route('mahasiswa.course.item', [$course['id'], $item['id']]));
                                    @endphp
                                    <a href="{{ $materialUrl }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <span class="shrink-0 text-brand">
                                            @if($isCodingMaterial)
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                                            @else
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h4>
                                            <p class="mt-0.5 text-xs text-muted flex items-center gap-2">
                                                <span>{{ $isCodingMaterial ? 'Tutorial coding & Lumina AI' : 'Materi belajar' }}</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span>{{ count(\App\Support\LearningPreview::discussions($item['id'])) }} diskusi</span>
                                            </p>
                                        </div>
                                        <span class="text-xs font-semibold text-brand">
                                            {{ $isCodingMaterial ? ($isDosen ? 'Buka Praktikum' : 'Mulai praktik') : 'Buka Materi' }}
                                        </span>
                                    </a>
                                @endforeach

                                {{-- Praktikum Coding: tampil sebagai item list standar seperti materi lainnya --}}
                                @if(str_contains(strtolower($moduleName), 'tree') || ($loop->last && $course['id'] === 1))
                                    <a href="{{ route('mahasiswa.assignment.code', 1) }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <span class="shrink-0 text-brand">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">Praktikum Coding: Binary Search Tree</h4>
                                            </div>
                                            <p class="mt-0.5 text-xs text-muted flex items-center gap-2">
                                                <span>Editor kode &amp; terminal</span>
                                            </p>
                                        </div>
                                        <span class="text-xs font-semibold text-brand">{{ $isDosen ? 'Buka Praktikum' : 'Kerjakan' }}</span>
                                    </a>
                                @endif
                            </div>
                        </section>
                    @empty
                        <div class="surface p-6 text-center text-sm text-muted">Belum ada materi perkuliahan yang diunggah.</div>
                    @endforelse
                </div>

                {{-- PANEL TUGAS --}}
                <div id="course-panel-tugas" class="space-y-4" style="display: none;">
                    @forelse($tugasItems->groupBy('module') as $moduleName => $contents)
                        <section class="surface overflow-hidden">
                            <div class="border-b border-line/50 px-5 py-3 flex items-center justify-between bg-canvas/30">
                                <h3 class="font-semibold text-ink text-sm">{{ $moduleName }}</h3>
                                <span class="text-xs text-muted">{{ count($contents) }} tugas</span>
                            </div>

                            <div class="divide-y divide-line/40">
                                @foreach($contents as $item)
                                    @php
                                        $hasSubmission = session('learning.submissions.'.$item['id']);
                                        $isCoding = ($item['type'] === 'coding');
                                        $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
                                        $itemUrl = $isCoding
                                            ? route('mahasiswa.assignment.code', $item['id'])
                                            : ($isDosen ? route('dosen.course.item', [$course['id'], $item['id']]) : route('mahasiswa.course.item', [$course['id'], $item['id']]));
                                    @endphp
                                    <a href="{{ $itemUrl }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <span class="shrink-0 {{ $hasSubmission ? 'text-emerald-600' : 'text-brand' }}">
                                            @if($item['type'] === 'kuis')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                                            @elseif($item['type'] === 'coding')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                                            @else
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            @endif
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h4>
                                            </div>
                                            <div class="mt-0.5 text-xs text-muted flex flex-wrap items-center gap-2">
                                                <span>{{ \App\Support\LearningPreview::labels()[$item['type']] }}</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span class="{{ $isPast && !$hasSubmission ? 'text-rose-600 font-semibold' : '' }}">{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tugas perkuliahan' }}</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span>{{ count(\App\Support\LearningPreview::discussions($item['id'])) }} diskusi</span>
                                            </div>
                                        </div>

                                        @if($isDosen)
                                            <span class="shrink-0 text-xs font-semibold text-brand">
                                                {{ $item['type'] === 'kuis' ? 'Kelola Kuis' : 'Kelola / Nilai' }}
                                            </span>
                                        @elseif($hasSubmission)
                                            <span class="shrink-0 text-xs font-semibold text-emerald-700">
                                                Sudah dikerjakan
                                            </span>
                                        @elseif($isPast)
                                            <span class="shrink-0 text-xs font-semibold text-rose-600">
                                                Terlambat
                                            </span>
                                        @elseif($isCoding)
                                            <span class="shrink-0 text-xs font-semibold text-brand">
                                                Kerjakan
                                            </span>
                                        @else
                                            <span class="shrink-0 text-xs font-semibold text-brand">
                                                Kerjakan
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="surface p-6 text-center text-sm text-muted">Belum ada tugas perkuliahan.</div>
                    @endforelse
                </div>

            <script>
                function switchCourseTab(tab) {
                    const btnMateri = document.getElementById('tab-btn-materi');
                    const btnTugas = document.getElementById('tab-btn-tugas');
                    const panelMateri = document.getElementById('course-panel-materi');
                    const panelTugas = document.getElementById('course-panel-tugas');

                    if (!btnMateri || !btnTugas || !panelMateri || !panelTugas) return;

                    if (tab === 'materi') {
                        btnMateri.className = 'pb-3 text-sm font-semibold border-b-2 -mb-px border-brand text-brand flex items-center gap-2 transition';
                        btnTugas.className = 'pb-3 text-sm font-medium border-b-2 -mb-px border-transparent text-muted hover:text-ink flex items-center gap-2 transition';
                        panelMateri.style.display = 'block';
                        panelTugas.style.display = 'none';
                    } else {
                        btnMateri.className = 'pb-3 text-sm font-medium border-b-2 -mb-px border-transparent text-muted hover:text-ink flex items-center gap-2 transition';
                        btnTugas.className = 'pb-3 text-sm font-semibold border-b-2 -mb-px border-brand text-brand flex items-center gap-2 transition';
                        panelMateri.style.display = 'none';
                        panelTugas.style.display = 'block';
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    try {
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.get('tab') === 'tugas' || window.location.hash === '#tugas' || window.location.hash === '#course-panel-tugas') {
                            switchCourseTab('tugas');
                        }
                    } catch (e) {}
                });
            </script>
            </div>

        {{-- KOLOM KANAN (SIDEBAR DI SAMPING): Forum Diskusi Kelas Saja --}}
        <aside data-discuss-aside class="lg:sticky lg:top-20 z-20 self-start w-full">
            <section id="diskusi-kelas" aria-labelledby="discuss-heading" class="surface scroll-mt-24 p-5 rounded-xl border border-line/60 flex flex-col min-h-[440px] max-h-[90vh]">
                <div class="shrink-0 flex items-center justify-between border-b border-line/50 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" title="Sistem Diskusi Terhubung"></span>
                        <h2 id="discuss-heading" class="text-sm font-bold text-ink">Forum Diskusi Kelas</h2>
                    </div>
                    <span id="chat-total-badge" class="text-[11px] font-medium text-muted">
                        0 pesan
                    </span>
                </div>

                {{-- Messages List --}}
                @php
                    $hasChatTables = \Illuminate\Support\Facades\Schema::hasTable('rooms') && \Illuminate\Support\Facades\Schema::hasTable('messages');
                    $chatRoom = $hasChatTables ? \App\Models\Room::forCourse($course['id'], $course['title']) : null;
                    $chatUser = auth()->user();
                    if (! $chatUser && is_array(session('auth_user'))) {
                        $sU = session('auth_user');
                        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                            try {
                                $chatUser = \App\Models\User::where('email', $sU['email'] ?? '')->orWhere('nim_nidn', $sU['number'] ?? '')->first();
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                    $isDosenUser = ($chatUser && $chatUser->hasRole(\App\Models\Role::DOSEN))
                        || (is_array(session('auth_user')) && (session('auth_user')['role'] ?? '') === \App\Models\Role::DOSEN);

                    $meName = $chatUser?->name ?? session('auth_user.name', 'Ahmad Maulana');
                    $meRole = $chatUser?->role?->name ?? session('auth_user.role', 'mahasiswa');
                    $meSenderKey = $chatUser
                        ? 'user:'.$chatUser->getAuthIdentifier()
                        : 'preview:'.$meRole.':'.(session('auth_user.id') ?? session('auth_user.number') ?? session('auth_user.email') ?? 1);

                    if ($hasChatTables && $chatRoom && \App\Models\Message::where('room_id', $chatRoom->id)->exists()) {
                        $rawMessages = \App\Models\Message::where('room_id', $chatRoom->id)
                            ->with(['user.role', 'replyTo.user', 'mentions.mentionedUser'])
                            ->orderByDesc('id')
                            ->limit(40)
                            ->get();
                        $initialMessages = $rawMessages->reverse()->values()->map(function ($m) use ($chatUser, $meName) {
                            $p = $m->toChatPayload($chatUser);
                            if (! $p['is_me'] && ! empty($meName) && trim($p['author']) === trim($meName)) {
                                $p['is_me'] = true;
                            }
                            return $p;
                        });
                        $pinnedMessages = \App\Models\Message::where('room_id', $chatRoom->id)
                            ->where('is_pinned', true)
                            ->with(['user.role', 'replyTo.user'])
                            ->orderByDesc('id')
                            ->get()
                            ->map(fn($m) => $m->toChatPayload($chatUser));

                        $roomMembersList = $chatRoom->members()->select('users.id', 'users.name')->get()->map(fn($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'role' => $u->pivot->role ?? 'mahasiswa'
                        ]);
                    } else {
                        $previewMessages = collect(\App\Support\LearningPreview::courseDiscussions($course['id']));
                        $initialMessages = $previewMessages->map(function ($m, $idx) use ($meName, $meSenderKey) {
                            $isMe = isset($m['sender_key'])
                                ? hash_equals($meSenderKey, (string) $m['sender_key'])
                                : (!empty($meName) && trim($m['author'] ?? '') === trim($meName));
                            $msgAt = isset($m['timestamp']) ? \Carbon\Carbon::createFromTimestamp($m['timestamp']) : now();
                            return [
                                'id' => $idx + 1,
                                'room_id' => 1,
                                'content' => $m['message'] ?? '',
                                'is_pinned' => false,
                                'user_id' => 0,
                                'author' => $m['author'] ?? 'Pengguna',
                                'role' => $m['role'] ?? 'mahasiswa',
                                'is_me' => $isMe,
                                'time' => $m['time'] ?? $msgAt->format('H:i'),
                                'date_key' => $m['date_key'] ?? $msgAt->toDateString(),
                                'date_label' => $m['date_label'] ?? ($msgAt->isToday() ? 'Hari ini' : ($msgAt->isYesterday() ? 'Kemarin' : $msgAt->translatedFormat('d F Y'))),
                                'timestamp' => $m['timestamp'] ?? $msgAt->timestamp,
                                'reply_to' => null,
                                'mentions' => [],
                            ];
                        });
                        $pinnedMessages = collect();
                        $roomMembersList = collect();
                    }

                    $currentUserId = $chatUser?->id ?? 0;
                    $previousMessageDate = null;
                @endphp

                {{-- Banner Pesan yang Disematkan Dosen --}}
                <div id="pinned-announcements-container" class="{{ $pinnedMessages->isEmpty() ? 'hidden' : '' }} shrink-0 mt-2 rounded-xl bg-[#edf4fb] p-3 text-xs">
                    <div class="flex items-center justify-between gap-2 pb-1.5 mb-2">
                        <div class="flex items-center gap-1.5 font-bold text-[#1f4b7a]">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                            <span>Pesan Disematkan Dosen</span>
                        </div>
                        <span id="pinned-count-badge" class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-[#1f4b7a]">
                            {{ $pinnedMessages->count() }}
                        </span>
                    </div>
                    <div id="pinned-messages-list" class="space-y-1.5 max-h-24 overflow-y-auto pr-1">
                        @foreach($pinnedMessages as $pinMsg)
                            <div id="pinned-item-{{ $pinMsg['id'] }}" class="flex items-start gap-2 rounded-lg bg-white/70 p-2">
                                <div class="min-w-0 flex-1">
                                    @unless($pinMsg['is_me'])
                                        <span class="font-bold text-[#1f4b7a]">{{ $pinMsg['author'] }}:</span>
                                    @endunless
                                    <span class="text-slate-800 line-clamp-2">{{ $pinMsg['content'] }}</span>
                                </div>
                                @if($isDosenUser)
                                    <button type="button" onclick="togglePinMessage({{ $pinMsg['id'] }})" class="shrink-0 text-[10px] text-amber-800 hover:text-rose-700 underline font-medium" title="Lepas Sematan">Lepas</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="chat-messages" class="my-2.5 space-y-2.5 flex-1 min-h-0 overflow-y-auto pr-1 flex flex-col">
                    @forelse($initialMessages as $msg)
                        @php
                            $isMe = $msg['is_me'];
                            $initials = collect(explode(' ', $msg['author'] ?? 'P'))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('');
                            $isPinned = !empty($msg['is_pinned']);
                            $canDelete = $isDosenUser || $isMe;
                            $msgDateKey = $msg['date_key'] ?? now()->toDateString();
                            $msgDateLabel = $msg['date_label'] ?? 'Hari ini';
                        @endphp
                        @if($msgDateKey !== $previousMessageDate)
                            <div class="flex items-center gap-3 py-1" role="separator" aria-label="{{ $msgDateLabel }}">
                                <span class="h-px flex-1 bg-line/70"></span>
                                <time datetime="{{ $msgDateKey }}" class="shrink-0 text-[10px] font-medium text-muted">{{ $msgDateLabel }}</time>
                                <span class="h-px flex-1 bg-line/70"></span>
                            </div>
                            @php($previousMessageDate = $msgDateKey)
                        @endif
                        <div id="msg-bubble-{{ $msg['id'] }}" class="chat-message-row group flex w-full {{ $isMe ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg['id'] }}" data-author="{{ $msg['author'] }}">
                            <article class="min-w-0 w-fit max-w-[90%] sm:max-w-[85%] rounded-xl border shadow-2xs transition-all relative hover:shadow-xs overflow-visible flex {{ $isMe ? 'bg-[#edf4fb] border-[#cfe0f2] flex-row-reverse' : 'bg-canvas/70 border-line/60 flex-row' }}">
                                {{-- Garis Vertikal (brand untuk pesan saya, abu untuk pesan lain) --}}
                                <span class="shrink-0 w-[3.5px] self-stretch {{ $isMe ? 'bg-[#1f4b7a] rounded-r-xl' : 'bg-[#c2c8d0] rounded-l-xl' }}" aria-hidden="true"></span>
                                <div class="p-3 min-w-0 flex-1">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    @unless($isMe)
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold mt-0.5 bg-slate-200 text-slate-700">
                                            {{ $initials }}
                                        </span>
                                    @endunless
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5 leading-snug">
                                            @unless($isMe)
                                                <span class="text-xs font-bold text-ink break-words">{{ $msg['author'] }}</span>
                                                @if(($msg['role'] ?? '') === 'dosen')
                                                    <span class="status text-[10px] font-semibold py-0 px-1.5 text-brand bg-brand-soft border border-brand/20 shrink-0">Dosen</span>
                                                @endif
                                            @endunless
                                            @if($isPinned)
                                                <span id="pin-badge-{{ $msg['id'] }}" class="inline-flex items-center gap-0.5 text-[9px] font-bold text-[#1f4b7a] bg-[#edf4fb] px-1 rounded shrink-0">
                                                    Disematkan
                                                </span>
                                            @endif
                                            <details class="chat-action-details relative ml-auto shrink-0">
                                                <summary class="flex h-6 w-6 cursor-pointer list-none items-center justify-center rounded-full text-muted hover:bg-white/80 hover:text-ink [&::-webkit-details-marker]:hidden" aria-label="Aksi pesan">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                                </summary>
                                                <div class="absolute right-0 top-full z-50 mt-1 min-w-32 rounded-lg border border-line bg-white py-1 text-xs shadow-lg">
                                                    <button type="button" onclick="this.closest('details').removeAttribute('open'); setReplyTarget({{ $msg['id'] }}, @js($msg['author']), @js(Str::limit($msg['content'], 50)))" class="block w-full px-3 py-2 text-left text-ink hover:bg-canvas">Balas</button>
                                                    @if($isDosenUser)
                                                        <button type="button" onclick="this.closest('details').removeAttribute('open'); togglePinMessage({{ $msg['id'] }})" class="block w-full px-3 py-2 text-left text-ink hover:bg-canvas">{{ $isPinned ? 'Lepas sematan' : 'Sematkan' }}</button>
                                                    @endif
                                                    @if($canDelete)
                                                        <button type="button" onclick="this.closest('details').removeAttribute('open'); deleteMessage({{ $msg['id'] }})" class="block w-full px-3 py-2 text-left text-rose-700 hover:bg-rose-50 font-medium">Hapus</button>
                                                    @endif
                                                </div>
                                            </details>
                                        </div>

                                        {{-- Kutipan Balasan (Reply Quote Bubble) --}}
                                        @if(!empty($msg['reply_to']))
                                            <div class="mt-2 mb-1 rounded border-l-2 border-brand bg-white/70 px-2.5 py-1 text-[11px] text-slate-600 shadow-2xs">
                                                <span class="font-bold text-brand block leading-tight">{{ $msg['reply_to']['sender_name'] }}</span>
                                                <span class="line-clamp-1 italic text-slate-700 mt-0.5">{{ $msg['reply_to']['excerpt'] }}</span>
                                            </div>
                                        @endif

                                        {{-- Isi Pesan (Render Mention @User dengan badge) --}}
                                        <p class="mt-1 break-words whitespace-pre-line text-xs leading-relaxed text-slate-800">{!! preg_replace('/@([A-Za-z0-9_.\\s]+?)(?=[,\\s\\n]|$)/', '<span class="inline-flex items-center px-1 py-0.2 rounded bg-brand/10 text-brand font-semibold text-[11px]">@$1</span>', e(trim($msg['content']))) !!}</p>
                                        <div class="mt-1 flex justify-end">
                                            <time class="text-[10px] text-muted">{{ $msg['time'] }}</time>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </article>
                        </div>
                    @empty
                        <div id="empty-chat-placeholder" class="my-auto flex flex-col items-center justify-center p-6 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand mb-3 shadow-2xs">
                                <svg class="h-6 w-6 stroke-[1.6]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <h3 class="text-xs font-bold text-ink">Belum Ada Diskusi</h3>
                            <p class="mt-1 max-w-[240px] text-[11px] text-muted leading-relaxed">
                                Jadilah yang pertama memulai obrolan kelas atau ajukan pertanyaan kepada dosen pengampu.
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Area Input Pesan dengan Preview Reply & Dropdown Mention --}}
                <div class="relative shrink-0 pt-1 border-t border-line/50 space-y-1.5">
                    {{-- Preview Balasan Pesan --}}
                    <div id="reply-preview-bar" class="hidden rounded-t-xl border border-b-0 border-[#b9c0ca] bg-slate-50 px-3 py-2 text-xs flex items-center justify-between gap-2 border-l-4 !border-l-brand animate-fadeIn">
                        <div class="min-w-0 flex-1 flex items-center gap-2">
                            <svg class="h-3.5 w-3.5 text-brand shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                            <div class="min-w-0 truncate">
                                <span class="text-slate-500">Membalas ke:</span>
                                <strong id="reply-author-label" class="font-bold text-ink"></strong>
                                <span id="reply-content-excerpt" class="text-muted ml-1 truncate"></span>
                            </div>
                        </div>
                        <button type="button" onclick="cancelReplyMode()" class="text-slate-400 hover:text-rose-600 transition shrink-0 p-1" title="Batalkan balasan" aria-label="Batalkan balasan">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    {{-- Dropdown Autocomplete @Mention --}}
                    <div id="mention-dropdown" class="hidden absolute bottom-full left-0 mb-1 z-30 w-64 max-h-48 overflow-y-auto rounded-xl border border-line/80 bg-white p-1 shadow-lg divide-y divide-line/30">
                    </div>

                    {{-- Form Input Pesan --}}
                    <form id="course-discuss-form" method="post" action="{{ route($isDosen ? 'dosen.course.discuss.class' : 'mahasiswa.course.discuss.class', $course['id']) }}" class="space-y-1">
                        @csrf
                        <input type="hidden" name="reply_to_message_id" id="reply_to_message_id" value="">
                        <div class="relative rounded-xl border border-[#b9c0ca] bg-white transition-all focus-within:border-brand focus-within:ring-1 focus-within:ring-brand shadow-2xs">
                            <label for="course_discuss_message" class="sr-only">Tulis Pesan Diskusi Kelas</label>
                            <textarea maxlength="3000" name="message" id="course_discuss_message" rows="2" required class="w-full bg-transparent border-0 p-2.5 pr-10 pb-7 text-xs text-ink placeholder:text-[#737b86] resize-none outline-none focus:outline-none focus:ring-0 leading-relaxed block" placeholder="Tulis pesan... Ketik @ untuk mention dosen / teman"></textarea>
                            <div class="absolute right-2 bottom-2 flex items-center">
                                <button id="course-discuss-submit-btn" type="submit" class="button-primary h-7 w-7 !p-0 !min-h-0 rounded-lg inline-flex items-center justify-center transition-all duration-150 transform shrink-0 shadow-xs hover:scale-105 active:scale-95 disabled:opacity-30" title="Kirim pesan (Enter)" aria-label="Kirim pesan">
                                    <svg class="h-3.5 w-3.5 fill-current text-white -mr-0.5 -mt-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </aside>
    </div>
</div>

<script>
    (function() {
        const courseId = {{ $course['id'] }};
        const roomId = {{ $chatRoom?->id ?? 1 }};
        const currentUserId = {{ $currentUserId }};
        const isDosenUser = {{ $isDosenUser ? 'true' : 'false' }};
        const roomMembers = @json($roomMembersList ?? []);

        const chatMessages = document.getElementById('chat-messages');
        const discussForm = document.getElementById('course-discuss-form');
        const discussInput = document.getElementById('course_discuss_message');
        const discussSubmitBtn = document.getElementById('course-discuss-submit-btn');
        const replyPreviewBar = document.getElementById('reply-preview-bar');
        const replyInput = document.getElementById('reply_to_message_id');
        const replyAuthorLabel = document.getElementById('reply-author-label');
        const replyContentExcerpt = document.getElementById('reply-content-excerpt');
        const mentionDropdown = document.getElementById('mention-dropdown');
        const pinnedContainer = document.getElementById('pinned-announcements-container');
        const pinnedList = document.getElementById('pinned-messages-list');
        const pinnedCountBadge = document.getElementById('pinned-count-badge');
        const totalBadge = document.getElementById('chat-total-badge');

        let activeReplyTarget = null;
        let mentionStartIndex = -1;
        let selectedMentionUserIds = [];

        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // 1. Reply Handling
        window.setReplyTarget = function(messageId, author, excerpt) {
            activeReplyTarget = { id: messageId, author: author, excerpt: excerpt };
            if (replyInput) replyInput.value = messageId;
            if (replyAuthorLabel) replyAuthorLabel.textContent = author;
            if (replyContentExcerpt) replyContentExcerpt.textContent = excerpt;
            if (replyPreviewBar) replyPreviewBar.classList.remove('hidden');
            if (discussInput) {
                discussInput.focus();
            }
        };

        window.cancelReplyMode = function() {
            activeReplyTarget = null;
            if (replyInput) replyInput.value = '';
            if (replyPreviewBar) replyPreviewBar.classList.add('hidden');
        };

        // 2. Pin / Unpin Handling
        window.togglePinMessage = async function(messageId) {
            try {
                const response = await fetch(`/chat/messages/${messageId}/pin`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    refreshChatStream();
                }
            } catch (err) {
                console.error('Gagal toggle pin:', err);
            }
        };

        // 3. Delete Message Handling
        window.deleteMessage = async function(messageId) {
            if (!confirm('Apakah Anda yakin ingin menghapus pesan ini?')) return;

            try {
                const response = await fetch(`/chat/messages/${messageId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    const row = document.getElementById(`msg-bubble-${messageId}`);
                    if (row) row.remove();
                    refreshChatStream();
                }
            } catch (err) {
                console.error('Gagal menghapus pesan:', err);
            }
        };

        // 4. @Mention Dropdown Autocomplete
        if (discussInput && mentionDropdown) {
            discussInput.addEventListener('input', function() {
                const text = discussInput.value;
                const cursorPos = discussInput.selectionStart;
                const textBeforeCursor = text.slice(0, cursorPos);
                const lastAtPos = textBeforeCursor.lastIndexOf('@');

                if (lastAtPos !== -1 && (lastAtPos === 0 || /\s/.test(text[lastAtPos - 1]))) {
                    const query = textBeforeCursor.slice(lastAtPos + 1).toLowerCase();
                    mentionStartIndex = lastAtPos;

                    const matches = roomMembers.filter(m => m.name.toLowerCase().includes(query));
                    if (matches.length > 0) {
                        mentionDropdown.innerHTML = '';
                        matches.slice(0, 5).forEach(m => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'w-full text-left px-3 py-1.5 text-xs hover:bg-slate-100 flex items-center justify-between gap-2 rounded-lg transition';
                            btn.innerHTML = `
                                <span class="font-semibold text-ink truncate">${m.name}</span>
                                <span class="text-[10px] text-muted uppercase font-bold shrink-0">${m.role}</span>
                            `;
                            btn.onclick = () => selectMentionUser(m);
                            mentionDropdown.appendChild(btn);
                        });
                        mentionDropdown.classList.remove('hidden');
                    } else {
                        mentionDropdown.classList.add('hidden');
                    }
                } else {
                    mentionDropdown.classList.add('hidden');
                }
            });

            document.addEventListener('click', function(e) {
                if (!mentionDropdown.contains(e.target) && e.target !== discussInput) {
                    mentionDropdown.classList.add('hidden');
                }
            });
        }

        function selectMentionUser(user) {
            if (mentionStartIndex === -1) return;
            const text = discussInput.value;
            const textBefore = text.slice(0, mentionStartIndex);
            const textAfter = text.slice(discussInput.selectionStart);
            discussInput.value = `${textBefore}@${user.name} ${textAfter}`;
            mentionDropdown.classList.add('hidden');
            if (!selectedMentionUserIds.includes(user.id)) {
                selectedMentionUserIds.push(user.id);
            }
            discussInput.focus();
        }

        // 5. Enter Key and AJAX Submit
        if (discussInput && discussForm) {
            discussInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (discussInput.value.trim().length > 0) {
                        discussForm.requestSubmit();
                    }
                }
            });

            discussForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const content = discussInput.value.trim();
                if (!content) return;

                if (discussSubmitBtn) discussSubmitBtn.disabled = true;

                try {
                    const payload = {
                        content: content,
                        message: content,
                        reply_to_message_id: replyInput && replyInput.value ? Number(replyInput.value) : null,
                        mentioned_user_ids: selectedMentionUserIds,
                    };

                    let response = await fetch(`/chat/course/${courseId}/messages`, {
                        method: 'POST',
                        body: JSON.stringify(payload),
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    if (!response.ok) {
                        const formData = new FormData(discussForm);
                        response = await fetch(discussForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                    }

                    if (response.ok) {
                        discussInput.value = '';
                        selectedMentionUserIds = [];
                        cancelReplyMode();
                        const emptyPlaceholder = document.getElementById('empty-chat-placeholder');
                        if (emptyPlaceholder) emptyPlaceholder.remove();
                        refreshChatStream(true);
                    } else {
                        discussForm.submit();
                    }
                } catch (err) {
                    discussForm.submit();
                } finally {
                    if (discussSubmitBtn) discussSubmitBtn.disabled = false;
                }
            });
        }

        // 6. Polling & Real-Time Sync
        async function refreshChatStream(scrollToBottom = false) {
            try {
                const res = await fetch(`/chat/course/${courseId}/messages`);
                if (!res.ok) return;
                const data = await res.json();
                if (!data.success) return;

                if (totalBadge) totalBadge.textContent = `${data.messages.length} pesan`;

                if (pinnedContainer && pinnedList) {
                    if (data.pinned_messages && data.pinned_messages.length > 0) {
                        pinnedContainer.classList.remove('hidden');
                        if (pinnedCountBadge) pinnedCountBadge.textContent = data.pinned_messages.length;
                        pinnedList.innerHTML = data.pinned_messages.map(p => `
                            <div id="pinned-item-${p.id}" class="flex items-start gap-2 rounded-lg bg-white/70 p-2">
                                <div class="min-w-0 flex-1">
                                    ${!p.is_me ? `<span class="font-bold text-[#1f4b7a]">${p.author}:</span> ` : ''}
                                    <span class="text-slate-800 line-clamp-2">${p.content}</span>
                                </div>
                                ${isDosenUser ? `<button type="button" onclick="togglePinMessage(${p.id})" class="shrink-0 text-[10px] text-amber-800 hover:text-rose-700 underline font-medium" title="Lepas Sematan">Lepas</button>` : ''}
                            </div>
                        `).join('');
                    } else {
                        pinnedContainer.classList.add('hidden');
                    }
                }

                if (chatMessages && data.messages) {
                    const hasRenderedMessages = chatMessages.querySelector('[data-message-id]');
                    if (data.messages.length === 0 && hasRenderedMessages) {
                        return;
                    }

                    const emptyState = document.getElementById('empty-chat-placeholder');
                    if (data.messages.length > 0 && emptyState) {
                        emptyState.remove();
                    }

                    const currentIds = Array.from(chatMessages.querySelectorAll('[data-message-id]')).map(el => Number(el.dataset.messageId));
                    const newIds = data.messages.map(m => m.id);

                    const isDifferent = currentIds.length !== newIds.length || !newIds.every((id, idx) => id === currentIds[idx]);

                    if (isDifferent) {
                        const wasAtBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 50;

                        chatMessages.innerHTML = data.messages.map(m => {
                            const isMe = m.is_me;
                            const initials = (m.author || 'P').split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
                            const canDelete = isDosenUser || isMe;
                            const pinBadge = m.is_pinned ? `<span id="pin-badge-${m.id}" class="inline-flex items-center gap-0.5 text-[9px] font-bold text-[#1f4b7a] bg-[#edf4fb] px-1 rounded shrink-0">Disematkan</span>` : '';
                            const replyBox = m.reply_to ? `
                                <div class="mt-2 mb-1 rounded border-l-2 border-brand bg-white/70 px-2.5 py-1 text-[11px] text-slate-600 shadow-2xs">
                                    <span class="font-bold text-brand block leading-tight">${m.reply_to.sender_name}</span>
                                    <span class="line-clamp-1 italic text-slate-700 mt-0.5">${m.reply_to.excerpt}</span>
                                </div>
                            ` : '';

                            const formattedContent = m.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/@([A-Za-z0-9_.\s]+?)(?=[,\s\n]|$)/g, '<span class="inline-flex items-center px-1 py-0.2 rounded bg-brand/10 text-brand font-semibold text-[11px]">@$1</span>');
                            const replyAuthor = JSON.stringify(m.author).replace(/'/g, '&#39;');
                            const replyExcerpt = JSON.stringify(m.content.slice(0, 50)).replace(/'/g, '&#39;');

                            return `
                                <div id="msg-bubble-${m.id}" class="chat-message-row group flex w-full ${isMe ? 'justify-end' : 'justify-start'}" data-message-id="${m.id}" data-author="${m.author}">
                                    <article class="min-w-0 w-fit max-w-[90%] sm:max-w-[85%] rounded-xl border shadow-2xs transition-all relative hover:shadow-xs overflow-visible flex ${isMe ? 'bg-[#edf4fb] border-[#cfe0f2] flex-row-reverse' : 'bg-canvas/70 border-line/60 flex-row'}">
                                        <span class="shrink-0 w-[3.5px] self-stretch ${isMe ? 'bg-[#1f4b7a] rounded-r-xl' : 'bg-[#c2c8d0] rounded-l-xl'}" aria-hidden="true"></span>
                                        <div class="p-3 min-w-0 flex-1">
                                            <div class="flex items-start gap-2.5 min-w-0">
                                                ${!isMe ? `
                                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold mt-0.5 bg-slate-200 text-slate-700">
                                                        ${initials}
                                                    </span>
                                                ` : ''}
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-1.5 leading-snug">
                                                        ${!isMe ? `
                                                            <span class="text-xs font-bold text-ink break-words">${m.author}</span>
                                                            ${m.role === 'dosen' ? '<span class="status text-[10px] font-semibold py-0 px-1.5 text-brand bg-brand-soft border border-brand/20 shrink-0">Dosen</span>' : ''}
                                                        ` : ''}
                                                        ${pinBadge}
                                                        <details class="chat-action-details relative ml-auto shrink-0">
                                                            <summary class="flex h-6 w-6 cursor-pointer list-none items-center justify-center rounded-full text-muted hover:bg-white/80 hover:text-ink [&::-webkit-details-marker]:hidden" aria-label="Aksi pesan">
                                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                                            </summary>
                                                            <div class="absolute right-0 top-full z-50 mt-1 min-w-32 rounded-lg border border-line bg-white py-1 text-xs shadow-lg">
                                                                <button type="button" onclick='this.closest("details").removeAttribute("open"); setReplyTarget(${m.id}, ${replyAuthor}, ${replyExcerpt})' class="block w-full px-3 py-2 text-left text-ink hover:bg-canvas">Balas</button>
                                                                ${isDosenUser ? `<button type="button" onclick="this.closest('details').removeAttribute('open'); togglePinMessage(${m.id})" class="block w-full px-3 py-2 text-left text-ink hover:bg-canvas">${m.is_pinned ? 'Lepas sematan' : 'Sematkan'}</button>` : ''}
                                                                ${canDelete ? `<button type="button" onclick="this.closest('details').removeAttribute('open'); deleteMessage(${m.id})" class="block w-full px-3 py-2 text-left text-rose-700 hover:bg-rose-50 font-medium">Hapus</button>` : ''}
                                                            </div>
                                                        </details>
                                                    </div>
                                                    ${replyBox}
                                                    <p class="mt-1 break-words whitespace-pre-line text-xs leading-relaxed text-slate-800">${formattedContent}</p>
                                                    <div class="mt-1 flex justify-end">
                                                        <time class="text-[10px] text-muted">${m.time}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            `;
                        }).join('');

                        if (scrollToBottom || wasAtBottom) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                }
            } catch (err) {
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.chat-action-details')) {
                document.querySelectorAll('.chat-action-details[open]').forEach(el => el.removeAttribute('open'));
            }
        });

        document.addEventListener('toggle', function(e) {
            if (e.target.matches && e.target.matches('.chat-action-details[open]')) {
                document.querySelectorAll('.chat-action-details[open]').forEach(el => {
                    if (el !== e.target) el.removeAttribute('open');
                });
            }
        }, true);

        let pollTimer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                refreshChatStream();
            }
        }, 3500);

        if (window.Echo) {
            try {
                window.Echo.private(`room.${roomId}`)
                    .listen('MessageSent', () => refreshChatStream(true))
                    .listen('MessagePinned', () => refreshChatStream())
                    .listen('MessageDeleted', (e) => {
                        const id = e.message_id || e.id;
                        if (id) {
                            const row = document.getElementById(`msg-bubble-${id}`);
                            if (row) row.remove();
                        }
                        refreshChatStream();
                    });
            } catch (echoErr) {
            }
        }

        function syncDiscussionCard() {
            const discussAside = document.querySelector('aside[data-discuss-aside]');
            const discussCard = document.getElementById('diskusi-kelas');
            const videoCard = document.getElementById('course-video-card');
            const stickyHeader = document.getElementById('course-header-sticky');
            const gridEl = document.getElementById('course-main-grid');
            if (!discussAside || !discussCard) return;

            if (window.innerWidth >= 1024) {
                // Ketinggian card forum diskusi presisi setinggi card pemutar video
                if (videoCard) {
                    const h = videoCard.offsetHeight;
                    if (h > 150) {
                        discussCard.style.height = `${h}px`;
                        discussCard.style.minHeight = `${h}px`;
                        discussCard.style.maxHeight = `${h}px`;
                    }
                } else {
                    discussCard.style.height = '540px';
                    discussCard.style.minHeight = '500px';
                    discussCard.style.maxHeight = '85vh';
                }

                discussAside.style.marginTop = '0px';

                // Posisi sticky aside: terkunci persis di koordinat naturalnya saat render
                // sehingga sama sekali tidak bergeser atau melompat sedikit pun saat di-scroll
                discussAside.style.position = 'sticky';
                let naturalStickyTop = 64 + (stickyHeader ? stickyHeader.offsetHeight : 0);
                if (gridEl) {
                    const gridDocTop = gridEl.getBoundingClientRect().top + window.scrollY;
                    if (gridDocTop > 50) {
                        naturalStickyTop = Math.round(gridDocTop);
                    }
                }
                discussAside.style.top = `${naturalStickyTop}px`;
            } else {
                discussCard.style.height = '';
                discussCard.style.minHeight = '';
                discussCard.style.maxHeight = '';
                discussAside.style.position = '';
                discussAside.style.top = '';
                discussAside.style.marginTop = '';
            }

            // Tabs Materi & Tugas sticky top: persis di bawah batas sticky header
            const tabsContainer = document.getElementById('course-tabs-container');
            if (tabsContainer) {
                const headerH = stickyHeader ? stickyHeader.offsetHeight : 120;
                tabsContainer.style.top = `${64 + headerH}px`;
            }
        }
        window.addEventListener('resize', syncDiscussionCard);
        window.addEventListener('load', syncDiscussionCard);
        document.addEventListener('DOMContentLoaded', syncDiscussionCard);
        setTimeout(syncDiscussionCard, 50);

        if (window.ResizeObserver) {
            const videoCardEl = document.getElementById('course-video-card');
            if (videoCardEl) {
                new ResizeObserver(() => syncDiscussionCard()).observe(videoCardEl);
            }
            const stickyHeaderEl = document.getElementById('course-header-sticky');
            if (stickyHeaderEl) {
                new ResizeObserver(() => syncDiscussionCard()).observe(stickyHeaderEl);
            }
        }
    })();
</script>

{{-- Modal daftar anggota kelas --}}
<dialog id="enrolled-students-modal" class="backdrop:bg-black/40 rounded-xl p-0 shadow-lg border border-line/60 w-full max-w-lg overflow-hidden m-auto">
    <div class="p-4 sm:p-5 border-b border-line/60 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-ink text-base">Anggota Kelas</h3>
        </div>
        <button type="button" onclick="document.getElementById('enrolled-students-modal').close()" class="text-muted hover:text-ink text-sm p-1">
            <span class="sr-only">Tutup</span>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="p-4 sm:p-5 max-h-[60vh] overflow-y-auto divide-y divide-line/40">
        @forelse($courseMembers as $student)
            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ink truncate">{{ $student['name'] }}</p>
                    <p class="text-xs text-muted truncate font-mono">{{ $student['number'] }}</p>
                </div>
                <span class="text-xs font-semibold text-muted shrink-0">{{ ucfirst($student['role']) }}</span>
            </div>
        @empty
            <p class="text-center text-sm text-muted py-4">Belum ada mahasiswa yang terdaftar di kelas ini.</p>
        @endforelse
    </div>
</dialog>
@endsection
