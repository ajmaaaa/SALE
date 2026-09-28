@extends('layouts.mahasiswa')

@section('title', $course['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $isDosen = request()->routeIs('dosen.*');
    $role = $isDosen ? 'dosen' : 'mahasiswa';
    $submittedAssessmentIds = $submittedAssessmentIds ?? [];

    // Urutkan materi berdasarkan update terbaru
    $materiItems = collect($items)->where('type', 'materi')
        ->sortByDesc(function ($item) {
            return !empty($item['updated_at']) ? \Carbon\Carbon::parse($item['updated_at'])->timestamp : (!empty($item['created_at']) ? \Carbon\Carbon::parse($item['created_at'])->timestamp : ($item['id'] ?? 0));
        });

    // Urutkan tugas berdasarkan tingkat prioritas (deadline terdekat & belum dikerjakan di atas)
    $tugasItems = collect($items)->whereIn('type', ['tugas', 'coding', 'kuis'])
        ->sort(function ($a, $b) use ($submittedAssessmentIds, $isDosen) {
            $aSub = in_array($a['id'], $submittedAssessmentIds, true);
            $bSub = in_array($b['id'], $submittedAssessmentIds, true);

            if (!$isDosen && $aSub !== $bSub) {
                return $aSub ? 1 : -1;
            }

            $aDue = !empty($a['due']) ? \Carbon\Carbon::parse($a['due'])->timestamp : PHP_INT_MAX;
            $bDue = !empty($b['due']) ? \Carbon\Carbon::parse($b['due'])->timestamp : PHP_INT_MAX;

            if ($aDue !== $bDue) {
                return $aDue <=> $bDue;
            }

            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        });

    $uncompletedTasksCount = $tugasItems->reject(fn($item) => in_array($item['id'], $submittedAssessmentIds, true))->count();
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
    }
    $discussionCount = \App\Models\Message::whereHas('room', fn($query) => $query->where('class_section_id', $course['id']))->count();
    $courseVideo = $course['video'] ?? null;
    $courseVideoType = $course['video_type'] ?? (filter_var($courseVideo, FILTER_VALIDATE_URL) ? 'url' : 'file');
    $youtubeEmbed = $courseVideoType === 'url' ? \App\Support\LearningPreview::youtubeEmbedUrl($courseVideo) : null;
    $youtubePlayerUrl = $youtubeEmbed ? $youtubeEmbed.'&'.http_build_query([
        'origin' => request()->getSchemeAndHttpHost(),
        'widget_referrer' => request()->fullUrl(),
    ]) : null;
    $courseVideoMeta = in_array($courseVideoType, ['file', 'image'], true) && $courseVideo ? (\App\Support\LearningPreview::fileMeta($courseVideo) ?? []) : [];
@endphp

<div class="space-y-0">
    {{-- Course Cover Image if present --}}
    @if(!empty($course['cover']))
        <div class="overflow-hidden rounded-xl shadow-sm mb-4">
            <img src="{{ route('preview.file', ['file' => $course['cover'], 'inline' => 1], false) }}" alt="Sampul course" class="h-48 w-full object-cover">
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
                        @php
                            $photoSrc = str_starts_with($courseVideo, 'http') ? $courseVideo : route('preview.file', ['file' => $courseVideo, 'inline' => 1], false);
                            $photoTitle = $course['video_title'] ?? $course['title'];
                        @endphp
                        <div class="relative h-full w-full flex items-center justify-center bg-black/95 overflow-hidden">
                            <img id="video-heading" src="{{ $photoSrc }}" alt="Media Foto Utama: {{ $photoTitle }}" class="h-full w-full object-contain">
                            <span class="sr-only">Media Foto Utama &bull; {{ $courseVideoMeta['name'] ?? ($photoTitle ?? '') }}</span>
                        </div>
                    @elseif($youtubePlayerUrl)
                        <iframe id="video-heading" class="h-full w-full border-0" src="{{ $youtubePlayerUrl }}" title="Video {{ $course['title'] }}" loading="lazy" referrerpolicy="origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    @elseif($courseVideoType === 'file' && !empty($courseVideo) && !empty($courseVideoMeta))
                        <video id="video-heading" class="h-full w-full object-contain" controls preload="metadata" title="Video {{ $courseVideoMeta['name'] ?? $course['title'] }}">
                            <source src="{{ route('preview.file', ['file' => $courseVideo, 'inline' => 1], false) }}" type="{{ $courseVideoMeta['mime'] ?? 'video/mp4' }}">
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

            {{-- TABS NAV: MATERI & TUGAS --}}
            <div id="course-tabs-container" class="bg-[#f4f5f7] pt-2 pb-1">
                <nav class="flex border-b border-line/60 gap-6" aria-label="Tab konten kelas">
                    <button type="button" id="tab-btn-materi" onclick="switchCourseTab('materi')" class="pb-3 text-sm font-semibold border-b-2 -mb-px border-brand text-brand flex items-center gap-1.5 transition">
                        <span>Materi</span>
                        <span class="text-xs text-muted">({{ $materiItems->count() }})</span>
                    </button>
                    <button type="button" id="tab-btn-tugas" onclick="switchCourseTab('tugas')" class="pb-3 text-sm font-medium border-b-2 -mb-px border-transparent text-muted hover:text-ink flex items-center gap-1.5 transition">
                        <span>Tugas</span>
                        @if($uncompletedTasksCount > 0)
                            <span class="text-xs font-bold text-rose-600">({{ $uncompletedTasksCount }})</span>
                        @else
                            <span class="text-xs text-muted">({{ $tugasItems->count() }})</span>
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
                                            ? route('course.assignment.code', [$course['id'], $item['id']])
                                            : ($isDosen ? route('dosen.course.item', [$course['id'], $item['id']]) : route('mahasiswa.course.item', [$course['id'], $item['id']]));
                                    @endphp
                                    <div class="group flex items-center justify-between gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <a href="{{ $materialUrl }}" class="flex items-start gap-4 min-w-0 flex-1">
                                            <span class="shrink-0 text-brand mt-0.5">
                                                @if($isCodingMaterial)
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                                                @else
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                @endif
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition leading-snug">{{ $item['title'] }}</h4>
                                                <p class="mt-1 text-xs text-muted flex flex-wrap items-center gap-2">
                                                    <span>{{ $isCodingMaterial ? 'Tutorial coding & Lumina AI' : 'Materi belajar' }}</span>
                                                    @if(!empty($item['published_at_formatted']))
                                                        <span class="h-2.5 w-px bg-line"></span>
                                                        <span>Diterbitkan {{ $item['published_at_formatted'] }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </a>

                                        @if($isDosen)
                                            {{-- Dosen: Hanya Icon Edit & Hapus, posisi tengah-tengah secara vertikal menyesuaikan teks --}}
                                            <div class="flex items-center gap-1.5 shrink-0 self-center">
                                                <a href="{{ route('dosen.item.edit', [$course['id'], $item['id']]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-line bg-white text-muted hover:border-brand hover:text-brand transition shadow-2xs" title="Edit materi" aria-label="Edit materi">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </a>
                                                <form method="post" action="{{ route('dosen.item.destroy', [$course['id'], $item['id']]) }}" onsubmit="event.preventDefault(); window.saleConfirm({title: 'Hapus materi ini?', message: 'Materi dan seluruh data terkait akan dihapus secara permanen.', confirmLabel: 'Hapus', isDestructive: true}).then(ok => ok && this.submit())" class="inline-flex items-center m-0 p-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-line bg-white text-muted hover:border-rose-300 hover:text-rose-600 transition shadow-2xs" title="Hapus materi" aria-label="Hapus materi">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="flex items-center shrink-0 self-center">
                                                <a href="{{ $materialUrl }}" class="text-xs font-semibold text-brand hover:underline leading-snug whitespace-nowrap">
                                                    {{ $isCodingMaterial ? 'Mulai praktik' : 'Buka Materi' }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

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
                                        $hasSubmission = in_array($item['id'], $submittedAssessmentIds, true);
                                        $isCoding = ($item['type'] === 'coding');
                                        $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
                                        $itemUrl = $isCoding
                                            ? route('course.assignment.code', [$course['id'], $item['id']])
                                            : ($isDosen ? route('dosen.course.item', [$course['id'], $item['id']]) : route('mahasiswa.course.item', [$course['id'], $item['id']]));
                                    @endphp
                                    <div class="group flex items-center justify-between gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <a href="{{ $itemUrl }}" class="flex items-start gap-4 min-w-0 flex-1">
                                            <span class="shrink-0 mt-0.5 {{ $hasSubmission ? 'text-emerald-600' : 'text-brand' }}">
                                                @if($item['type'] === 'kuis')
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                                                @elseif($item['type'] === 'coding')
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                                                @else
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                                @endif
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition leading-snug">{{ $item['title'] }}</h4>
                                                <div class="mt-1 text-xs text-muted flex flex-wrap items-center gap-2">
                                                    <span>{{ \App\Support\LearningPreview::labels()[$item['type']] }}</span>
                                                    @if(!empty($item['published_at_formatted']))
                                                        <span class="h-2.5 w-px bg-line"></span>
                                                        <span>Diterbitkan {{ $item['published_at_formatted'] }}</span>
                                                    @endif
                                                    <span class="h-2.5 w-px bg-line"></span>
                                                    <span class="{{ $isPast && !$hasSubmission ? 'text-rose-600 font-semibold' : '' }}">{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tugas perkuliahan' }}</span>
                                                </div>
                                            </div>
                                        </a>

                                        @if($isDosen)
                                            {{-- Dosen: Hanya Icon Edit & Hapus, posisi tengah-tengah secara vertikal menyesuaikan teks --}}
                                            <div class="flex items-center gap-1.5 shrink-0 self-center">
                                                <a href="{{ route('dosen.item.edit', [$course['id'], $item['id']]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-line bg-white text-muted hover:border-brand hover:text-brand transition shadow-2xs" title="Edit tugas" aria-label="Edit tugas">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </a>
                                                <form method="post" action="{{ route('dosen.item.destroy', [$course['id'], $item['id']]) }}" onsubmit="event.preventDefault(); window.saleConfirm({title: 'Hapus tugas ini?', message: 'Tugas dan seluruh data terkait akan dihapus secara permanen.', confirmLabel: 'Hapus', isDestructive: true}).then(ok => ok && this.submit())" class="inline-flex items-center m-0 p-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-line bg-white text-muted hover:border-rose-300 hover:text-rose-600 transition shadow-2xs" title="Hapus tugas" aria-label="Hapus tugas">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="flex items-center shrink-0 self-center">
                                                <a href="{{ $itemUrl }}" class="text-xs font-semibold leading-snug whitespace-nowrap hover:underline @if($hasSubmission) text-emerald-700 @elseif($isPast) text-rose-600 @else text-brand @endif">
                                                    @if($hasSubmission)
                                                        Sudah dikerjakan
                                                    @elseif($isPast)
                                                        Terlambat
                                                    @else
                                                        Kerjakan
                                                    @endif
                                                </a>
                                            </div>
                                        @endif
                                    </div>
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

                    try {
                        sessionStorage.setItem('active_course_tab_{{ $course['id'] }}', tab);
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tab);
                        window.history.replaceState({}, '', url);
                    } catch (e) {}
                }

                document.addEventListener('DOMContentLoaded', function() {
                    try {
                        const urlParams = new URLSearchParams(window.location.search);
                        const savedTab = sessionStorage.getItem('active_course_tab_{{ $course['id'] }}');
                        const requestedTab = urlParams.get('tab') || (window.location.hash === '#tugas' || window.location.hash === '#course-panel-tugas' ? 'tugas' : (window.location.hash === '#materi' ? 'materi' : savedTab));

                        if (requestedTab === 'tugas') {
                            switchCourseTab('tugas');
                        } else if (requestedTab === 'materi') {
                            switchCourseTab('materi');
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
                    $isDosenUser = $chatUser?->hasRole(\App\Models\Role::DOSEN) ?? false;
                    $meName = $chatUser?->name ?? '';

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
                        $initialMessages = collect();
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
                        <span id="pinned-count-badge" class="text-xs font-bold text-[#1f4b7a]">
                            ({{ $pinnedMessages->count() }})
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
                            <article class="min-w-0 w-fit max-w-[90%] sm:max-w-[85%] rounded-xl border shadow-2xs transition-all relative hover:shadow-xs overflow-visible p-3 {{ $isMe ? 'bg-[#edf4fb] border-[#cfe0f2]' : 'bg-canvas/70 border-line/60' }}">
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
                                                    <span class="text-[11px] font-semibold text-brand shrink-0">· Dosen</span>
                                                @endif
                                            @endunless
                                            @if($isPinned)
                                                <span id="pin-badge-{{ $msg['id'] }}" class="text-[10px] font-bold text-brand shrink-0">
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
                            </article>
                        </div>
                    @empty
                        <div id="empty-chat-placeholder" class="my-auto flex flex-col items-center justify-center p-6 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-line bg-white text-slate-500 mb-3 shadow-2xs">
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
            if (!await window.saleConfirm({
                title: 'Hapus pesan',
                message: 'Pesan ini akan dihapus dari diskusi kelas.',
                confirmLabel: 'Hapus',
            })) return;

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
                            const nameSpan = document.createElement('span');
                            nameSpan.className = 'font-semibold text-ink truncate';
                            nameSpan.textContent = m.name;
                            const roleSpan = document.createElement('span');
                            roleSpan.className = 'text-[10px] text-muted uppercase font-bold shrink-0';
                            roleSpan.textContent = m.role;
                            btn.appendChild(nameSpan);
                            btn.appendChild(roleSpan);
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

        function chatElement(tag, className = '', text = null) {
            const element = document.createElement(tag);
            if (className) element.className = className;
            if (text !== null) element.textContent = String(text);

            return element;
        }

        function appendChatContent(container, content) {
            const value = content == null ? '' : String(content);
            const mentionPattern = /@([A-Za-z0-9_.\s]+?)(?=[,\s\n]|$)/g;
            let cursor = 0;
            let match;

            while ((match = mentionPattern.exec(value)) !== null) {
                container.appendChild(document.createTextNode(value.slice(cursor, match.index)));
                container.appendChild(chatElement(
                    'span',
                    'inline-flex items-center px-1 py-0.2 rounded bg-brand/10 text-brand font-semibold text-[11px]',
                    match[0]
                ));
                cursor = match.index + match[0].length;
            }

            container.appendChild(document.createTextNode(value.slice(cursor)));
        }

        function chatActionsIcon() {
            const namespace = 'http://www.w3.org/2000/svg';
            const svg = document.createElementNS(namespace, 'svg');
            svg.setAttribute('class', 'h-4 w-4');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('fill', 'currentColor');
            svg.setAttribute('aria-hidden', 'true');
            [5, 12, 19].forEach(cx => {
                const circle = document.createElementNS(namespace, 'circle');
                circle.setAttribute('cx', String(cx));
                circle.setAttribute('cy', '12');
                circle.setAttribute('r', '1.7');
                svg.appendChild(circle);
            });

            return svg;
        }

        function buildChatMessage(message) {
            const messageId = Number(message.id);
            if (!Number.isSafeInteger(messageId) || messageId < 1) return null;

            const author = message.author == null ? '' : String(message.author);
            const content = message.content == null ? '' : String(message.content);
            const isMe = Boolean(message.is_me);
            const canDelete = isDosenUser || isMe;
            const initials = (author || 'P').split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase();
            const row = chatElement('div', `chat-message-row group flex w-full ${isMe ? 'justify-end' : 'justify-start'}`);
            row.id = `msg-bubble-${messageId}`;
            row.dataset.messageId = String(messageId);
            row.dataset.author = author;

            const article = chatElement('article', `min-w-0 w-fit max-w-[90%] sm:max-w-[85%] rounded-xl border shadow-2xs transition-all relative hover:shadow-xs overflow-visible p-3 ${isMe ? 'bg-[#edf4fb] border-[#cfe0f2]' : 'bg-canvas/70 border-line/60'}`);
            const layout = chatElement('div', 'flex items-start gap-2.5 min-w-0');
            if (!isMe) {
                layout.appendChild(chatElement('span', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold mt-0.5 bg-slate-200 text-slate-700', initials));
            }

            const body = chatElement('div', 'min-w-0 flex-1');
            const heading = chatElement('div', 'flex flex-wrap items-center gap-1.5 leading-snug');
            if (!isMe) {
                heading.appendChild(chatElement('span', 'text-xs font-bold text-ink break-words', author));
                if (message.role === 'dosen') {
                    heading.appendChild(chatElement('span', 'text-[11px] font-semibold text-brand shrink-0', '· Dosen'));
                }
            }
            if (message.is_pinned) {
                const pinBadge = chatElement('span', 'inline-flex items-center gap-0.5 text-[9px] font-bold text-[#1f4b7a] bg-[#edf4fb] px-1 rounded shrink-0', 'Disematkan');
                pinBadge.id = `pin-badge-${messageId}`;
                heading.appendChild(pinBadge);
            }

            const actions = chatElement('details', 'chat-action-details relative ml-auto shrink-0');
            const actionSummary = chatElement('summary', 'flex h-6 w-6 cursor-pointer list-none items-center justify-center rounded-full text-muted hover:bg-white/80 hover:text-ink [&::-webkit-details-marker]:hidden');
            actionSummary.setAttribute('aria-label', 'Aksi pesan');
            actionSummary.appendChild(chatActionsIcon());
            actions.appendChild(actionSummary);
            const actionMenu = chatElement('div', 'absolute right-0 top-full z-50 mt-1 min-w-32 rounded-lg border border-line bg-white py-1 text-xs shadow-lg');
            const replyButton = chatElement('button', 'block w-full px-3 py-2 text-left text-ink hover:bg-canvas', 'Balas');
            replyButton.type = 'button';
            replyButton.addEventListener('click', () => {
                actions.removeAttribute('open');
                setReplyTarget(messageId, author, content.slice(0, 50));
            });
            actionMenu.appendChild(replyButton);
            if (isDosenUser) {
                const pinButton = chatElement('button', 'block w-full px-3 py-2 text-left text-ink hover:bg-canvas', message.is_pinned ? 'Lepas sematan' : 'Sematkan');
                pinButton.type = 'button';
                pinButton.addEventListener('click', () => {
                    actions.removeAttribute('open');
                    togglePinMessage(messageId);
                });
                actionMenu.appendChild(pinButton);
            }
            if (canDelete) {
                const deleteButton = chatElement('button', 'block w-full px-3 py-2 text-left text-rose-700 hover:bg-rose-50 font-medium', 'Hapus');
                deleteButton.type = 'button';
                deleteButton.addEventListener('click', () => {
                    actions.removeAttribute('open');
                    deleteMessage(messageId);
                });
                actionMenu.appendChild(deleteButton);
            }
            actions.appendChild(actionMenu);
            heading.appendChild(actions);
            body.appendChild(heading);

            if (message.reply_to) {
                const replyBox = chatElement('div', 'mt-2 mb-1 rounded border-l-2 border-brand bg-white/70 px-2.5 py-1 text-[11px] text-slate-600 shadow-2xs');
                replyBox.appendChild(chatElement('span', 'font-bold text-brand block leading-tight', message.reply_to.sender_name));
                replyBox.appendChild(chatElement('span', 'line-clamp-1 italic text-slate-700 mt-0.5', message.reply_to.excerpt));
                body.appendChild(replyBox);
            }

            const messageContent = chatElement('p', 'mt-1 break-words whitespace-pre-line text-xs leading-relaxed text-slate-800');
            appendChatContent(messageContent, content);
            body.appendChild(messageContent);
            const timeWrapper = chatElement('div', 'mt-1 flex justify-end');
            timeWrapper.appendChild(chatElement('time', 'text-[10px] text-muted', message.time));
            body.appendChild(timeWrapper);
            layout.appendChild(body);
            article.appendChild(layout);
            row.appendChild(article);

            return row;
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
                        pinnedList.innerHTML = '';
                        data.pinned_messages.forEach(p => {
                            const wrap = document.createElement('div');
                            wrap.id = `pinned-item-${p.id}`;
                            wrap.className = 'flex items-start gap-2 rounded-lg bg-white/70 p-2';
                            const inner = document.createElement('div');
                            inner.className = 'min-w-0 flex-1';
                            if (!p.is_me) {
                                const authorSpan = document.createElement('span');
                                authorSpan.className = 'font-bold text-[#1f4b7a]';
                                authorSpan.textContent = p.author + ':';
                                inner.appendChild(authorSpan);
                                inner.appendChild(document.createTextNode(' '));
                            }
                            const contentSpan = document.createElement('span');
                            contentSpan.className = 'text-slate-800 line-clamp-2';
                            contentSpan.textContent = p.content;
                            inner.appendChild(contentSpan);
                            wrap.appendChild(inner);
                            if (isDosenUser) {
                                const unpinBtn = document.createElement('button');
                                unpinBtn.type = 'button';
                                unpinBtn.className = 'shrink-0 text-[10px] text-amber-800 hover:text-rose-700 underline font-medium';
                                unpinBtn.title = 'Lepas Sematan';
                                unpinBtn.textContent = 'Lepas';
                                unpinBtn.onclick = () => togglePinMessage(p.id);
                                wrap.appendChild(unpinBtn);
                            }
                            pinnedList.appendChild(wrap);
                        });
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

                        chatMessages.replaceChildren(...data.messages.map(buildChatMessage).filter(Boolean));


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
<dialog id="enrolled-students-modal" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 max-w-lg w-[calc(100%-2rem)] overflow-hidden h-fit max-h-[85vh] flex flex-col">
    <div class="px-5 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30 shrink-0">
        <div>
            <h3 class="font-bold text-ink text-sm">Daftar Anggota Kelas</h3>
            <p class="text-xs text-muted mt-0.5">{{ count($courseMembers) }} Mahasiswa Terdaftar</p>
        </div>
        <button type="button" onclick="document.getElementById('enrolled-students-modal').close()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="p-5 overflow-y-auto divide-y divide-line/40 flex-1">
        @forelse($courseMembers as $student)
            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 border border-line/70 font-mono text-xs font-bold text-ink">
                        {{ strtoupper(substr($student['name'] ?? 'M', 0, 2)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-ink truncate">{{ $student['name'] }}</p>
                        <p class="text-[11px] text-muted truncate font-mono">{{ $student['number'] }}</p>
                    </div>
                </div>
                <span class="text-xs font-semibold text-muted shrink-0">{{ ucfirst($student['role']) }}</span>
            </div>
        @empty
            <div class="text-center py-6 space-y-1">
                <p class="text-xs font-semibold text-ink">Belum Ada Mahasiswa</p>
                <p class="text-xs text-muted">Belum ada mahasiswa yang terdaftar di kelas perkuliahan ini.</p>
            </div>
        @endforelse
    </div>
    <div class="px-5 py-3 bg-canvas/30 border-t border-line/60 flex items-center justify-end shrink-0">
        <button type="button" onclick="document.getElementById('enrolled-students-modal').close()" class="button-secondary text-xs py-1.5 px-4 cursor-pointer">Tutup</button>
    </div>
</dialog>
<script>
    document.getElementById('enrolled-students-modal')?.addEventListener('click', function(e) {
        if (e.target === this) this.close();
    });
</script>
@endsection
