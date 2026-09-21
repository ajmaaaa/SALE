@extends('layouts.mahasiswa')

@section('title', $course['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $currentRole = auth()->user()?->role?->name ?? (session('auth_user.role') ?? (request()->is('dosen*') ? 'dosen' : 'mahasiswa'));
    $isDosen = (in_array($currentRole, ['dosen', 'kaprodi'], true) || request()->is('dosen*')) && $currentRole !== 'mahasiswa' && session('auth_user.role') !== 'mahasiswa';
    $role = $isDosen ? 'dosen' : 'mahasiswa';
    $materiItems = collect($items)->where('type', 'materi');
    $tugasItems = collect($items)->whereIn('type', ['tugas', 'coding', 'kuis']);
    $uncompletedTasksCount = $tugasItems->filter(fn($item) => empty(session('learning.submissions.'.$item['id'])))->count();
    $allUsers = \App\Support\AdminPreview::users();
    $enrolledStudents = array_filter($allUsers, fn($u) => $u['role'] === 'mahasiswa');
    $courseMembers = collect($allUsers)
        ->filter(fn($u) => in_array($u['role'], ['mahasiswa', 'dosen'], true))
        ->sortBy(fn($u) => $u['role'] === 'dosen' ? 0 : 1)
        ->values();
    $courseVideo = $course['video'] ?? null;
    $courseVideoType = $course['video_type'] ?? (filter_var($courseVideo, FILTER_VALIDATE_URL) ? 'url' : 'file');
    $youtubeEmbed = $courseVideoType === 'url' ? \App\Support\LearningPreview::youtubeEmbedUrl($courseVideo) : null;
    $courseVideoMeta = $courseVideoType === 'file' && $courseVideo ? session('learning.files.'.$courseVideo, []) : [];
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
            <div class="flex flex-wrap items-baseline gap-2 text-xs font-medium leading-4 text-muted">
                <span class="font-mono font-semibold leading-4 text-ink">{{ $course['code'] }}</span>
                <span class="h-3 w-px self-center bg-line" aria-hidden="true"></span>
                <span class="leading-4">3 SKS</span>
                <span class="h-3 w-px self-center bg-line" aria-hidden="true"></span>
                <span class="leading-4">Semester Ganjil 2026/2027</span>
                <span class="h-3 w-px self-center bg-line" aria-hidden="true"></span>
                <span class="leading-4">Wajib</span>
            </div>
            <h1 class="page-heading mt-2">{{ $course['title'] }}</h1>
            <p class="mt-1 text-sm text-muted">
                Dosen Pengampu: <span class="font-medium text-ink">{{ $course['lecturer'] }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">

            {{-- Tombol Jumlah Mahasiswa Tergabung (Klik untuk lihat daftar mahasiswa) --}}
            <button type="button" onclick="document.getElementById('enrolled-students-modal').showModal()" class="button-secondary flex items-center gap-1.5 text-xs">
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>{{ count($enrolledStudents) }}</span>
                <span class="sr-only">mahasiswa</span>
            </button>

            @if($role === 'dosen')
                <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-primary">
                    + Tambah Konten
                </a>
            @endif
        </div>
    </header>

    <style>
        @media (min-width: 1024px) {
            .course-layout-grid {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 360px !important;
                align-items: start !important;
                gap: 1.75rem !important;
            }
        }
        @media (min-width: 1280px) {
            .course-layout-grid {
                grid-template-columns: minmax(0, 1fr) 380px !important;
            }
        }
    </style>

    {{-- 2-Column Layout: Konten di Kiri & Forum Diskusi Kelas di Samping (Kanan) --}}
    <div class="course-layout-grid grid items-start gap-7">
        {{-- KOLOM KIRI: Video Pengantar & Modul Terpisah (Materi & Tugas) --}}
        <div class="min-w-0 space-y-7">
            {{-- 16:9 Video Player Card --}}
            <section aria-labelledby="video-heading">
                <div class="aspect-video overflow-hidden rounded-xl bg-[#172633] shadow-md relative group">
                    @if($youtubeEmbed)
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

            {{-- TABS NAV: MATERI & TUGAS --}}
            <div class="space-y-4">
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
                                            : route('mahasiswa.course.item', [$course['id'], $item['id']]);
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
                                            {{ $isCodingMaterial ? 'Mulai praktik' : 'Buka Materi' }}
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
                                        <span class="text-xs font-semibold text-brand">Kerjakan</span>
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
                                        $itemUrl = $isCoding ? route('mahasiswa.assignment.code', $item['id']) : route('mahasiswa.course.item', [$course['id'], $item['id']]);
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
                                                <span>{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tugas perkuliahan' }}</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span>{{ count(\App\Support\LearningPreview::discussions($item['id'])) }} diskusi</span>
                                            </div>
                                        </div>

                                        @if($isDosen)
                                            <span class="shrink-0 text-xs font-semibold text-brand">
                                                Kerjakan
                                            </span>
                                        @elseif($hasSubmission)
                                            <span class="shrink-0 text-xs font-semibold text-emerald-700">
                                                Sudah dikerjakan
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
            </div>

            <script>
                function switchCourseTab(tab) {
                    const btnMateri = document.getElementById('tab-btn-materi');
                    const btnTugas = document.getElementById('tab-btn-tugas');
                    const panelMateri = document.getElementById('course-panel-materi');
                    const panelTugas = document.getElementById('course-panel-tugas');

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
            </script>
        </div>

        {{-- KOLOM KANAN (SIDEBAR DISAMPING): Forum Diskusi Kelas Saja --}}
        <aside class="space-y-6">
            <section id="diskusi-kelas" aria-labelledby="discuss-heading" class="surface scroll-mt-24 p-5 rounded-xl border border-line/60 space-y-4">
                <div class="flex items-center justify-between border-b border-line/50 pb-3">
                    <h2 id="discuss-heading" class="text-sm font-bold text-ink">Forum Diskusi Kelas</h2>
                </div>

                {{-- Messages List --}}
                @php
                    $meName = auth()->check()
                        ? auth()->user()->name
                        : session('auth_user.name', 'Ahmad Maulana');
                    $meRole = auth()->user()?->role?->name ?? session('auth_user.role', 'mahasiswa');
                    $meSenderKey = auth()->check()
                        ? 'user:'.auth()->user()->getAuthIdentifier()
                        : 'preview:'.$meRole.':'.(session('auth_user.id') ?? session('auth_user.number') ?? session('auth_user.email') ?? 1);
                    $previousMessageDate = null;
                    $courseMessages = \App\Support\LearningPreview::courseDiscussions($course['id']);
                    $lastMessageTimestamp = collect($courseMessages)->last()['timestamp'] ?? null;
                    $lastMessageDate = $lastMessageTimestamp ? \Carbon\Carbon::createFromTimestamp($lastMessageTimestamp)->toDateString() : '';
                @endphp
                <div id="chat-messages" data-last-date="{{ $lastMessageDate }}" class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1 flex flex-col">
                    @forelse($courseMessages as $msg)
                        @php
                            $isMe = isset($msg['sender_key'])
                                ? hash_equals($meSenderKey, (string) $msg['sender_key'])
                                : (!empty($meName) && trim($msg['author']) === trim($meName));
                            $initials = collect(explode(' ', $msg['author']))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('');
                            $messageAt = isset($msg['timestamp']) ? \Carbon\Carbon::createFromTimestamp($msg['timestamp']) : now();
                            $messageDate = $messageAt->toDateString();
                            $dateLabel = $messageAt->isToday()
                                ? 'Hari ini'
                                : ($messageAt->isYesterday() ? 'Kemarin' : $messageAt->translatedFormat('d F Y'));
                        @endphp
                        @if($messageDate !== $previousMessageDate)
                            <div class="flex items-center gap-3 py-1" role="separator" aria-label="{{ $dateLabel }}">
                                <span class="h-px flex-1 bg-line/70"></span>
                                <time datetime="{{ $messageDate }}" class="shrink-0 text-[10px] font-medium text-muted">{{ $dateLabel }}</time>
                                <span class="h-px flex-1 bg-line/70"></span>
                            </div>
                            @php($previousMessageDate = $messageDate)
                        @endif
                        <div class="flex w-full {{ $isMe ? 'justify-end' : 'justify-start' }}">
                            <article class="min-w-0 w-fit max-w-[85%] rounded-xl border p-3 shadow-2xs {{ $isMe ? 'bg-[#edf4fb] border-[#cfe0f2]' : 'bg-canvas/70 border-line/60' }}">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $isMe ? 'bg-brand text-white' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $initials }}
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-xs font-bold text-ink truncate flex items-center gap-1.5">
                                                <span>{{ $msg['author'] }}</span>
                                                @if($isMe)
                                                    <span class="text-[10px] font-medium text-brand">(Saya)</span>
                                                @endif
                                                @if(($msg['role'] ?? '') === 'dosen')
                                                    <span class="status text-[10px] font-semibold py-0 px-1.5 text-brand bg-brand-soft border border-brand/20">Dosen</span>
                                                @endif
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <p class="prose-content mt-1.5 break-words whitespace-pre-line pl-8 text-xs leading-relaxed text-slate-800">{{ $msg['message'] }}</p>
                                <time datetime="{{ $messageAt->toIso8601String() }}" class="mt-1 block text-right text-[10px] leading-none text-muted">{{ $messageAt->format('H:i') }}</time>
                            </article>
                        </div>
                    @empty
                        <div class="rounded-xl border border-line/60 bg-canvas p-5 text-center text-xs text-muted">
                            Belum ada pesan di forum kelas ini.
                        </div>
                    @endforelse
                </div>

                {{-- Send Message Form: Tombol Kirim Di Dalam Kolom Chat (Gaya AI Coding Assistant) --}}
                <form id="course-discuss-form" method="post" action="{{ route('mahasiswa.course.discuss.class', $course['id']) }}" class="pt-2 border-t border-line/50 space-y-1.5">
                    @csrf
                    <div class="relative rounded-xl border border-[#b9c0ca] bg-white transition-all focus-within:border-brand focus-within:ring-1 focus-within:ring-brand shadow-2xs">
                        <label for="course_discuss_message" class="sr-only">Tulis Pesan Diskusi Kelas</label>
                        <textarea maxlength="3000" name="message" id="course_discuss_message" rows="2" required class="w-full bg-transparent border-0 p-2.5 pr-10 pb-7 text-xs text-ink placeholder:text-[#737b86] resize-none outline-none focus:outline-none focus:ring-0 leading-relaxed block" placeholder="Tulis pesan untuk dosen &amp; kelas..."></textarea>
                        <div class="absolute right-2 bottom-2 flex items-center">
                            <button id="course-discuss-submit-btn" type="submit" class="button-primary h-7 w-7 !p-0 !min-h-0 rounded-lg inline-flex items-center justify-center transition-all duration-150 transform shrink-0 shadow-xs hover:scale-105 active:scale-95 disabled:opacity-30" title="Kirim pesan (Enter)" aria-label="Kirim pesan">
                                <svg class="h-3.5 w-3.5 fill-current text-white -mr-0.5 -mt-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </aside>
    </div>
</div>

<script>
    (function() {
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

        const discussForm = document.getElementById('course-discuss-form');
        const discussInput = document.getElementById('course_discuss_message');
        const discussSubmitBtn = document.getElementById('course-discuss-submit-btn');

        if (!discussForm || !discussInput) return;

        // Enter submits immediately; Shift+Enter inserts a new line
        discussInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (discussInput.value.trim().length > 0) {
                    discussForm.requestSubmit();
                }
            }
        });

        // AJAX submit with instant UI feedback
        discussForm.addEventListener('submit', async function(e) {
            const messageText = discussInput.value.trim();
            if (!messageText) {
                e.preventDefault();
                return;
            }

            if (window.fetch) {
                e.preventDefault();
                if (discussSubmitBtn) discussSubmitBtn.disabled = true;

                try {
                    const formData = new FormData(discussForm);
                    const response = await fetch(discussForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const msg = data.message;

                        // Clear empty state placeholder if present
                        const emptyBox = chatMessages.querySelector('.bg-canvas');
                        if (emptyBox && emptyBox.textContent.includes('Belum ada pesan')) {
                            emptyBox.remove();
                        }

                        if (chatMessages.dataset.lastDate !== msg.date_key) {
                            const dateSeparator = document.createElement('div');
                            dateSeparator.className = 'flex items-center gap-3 py-1';
                            dateSeparator.setAttribute('role', 'separator');
                            dateSeparator.setAttribute('aria-label', msg.date_label);
                            dateSeparator.innerHTML = `<span class="h-px flex-1 bg-line/70"></span><time datetime="${msg.date_key}" class="shrink-0 text-[10px] font-medium text-muted">${msg.date_label}</time><span class="h-px flex-1 bg-line/70"></span>`;
                            chatMessages.appendChild(dateSeparator);
                            chatMessages.dataset.lastDate = msg.date_key;
                        }

                        // Create message bubble
                        const msgWrapper = document.createElement('div');
                        msgWrapper.className = 'flex w-full justify-end';

                        const initials = (msg.author || 'Me').split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
                        const safeAuthor = (msg.author || 'Saya').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        const safeMsg = msg.message.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');

                        msgWrapper.innerHTML = `
                            <article class="min-w-0 w-fit max-w-[85%] rounded-xl border p-3 shadow-2xs bg-[#edf4fb] border-[#cfe0f2]">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold bg-brand text-white">
                                            ${initials}
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-xs font-bold text-ink truncate flex items-center gap-1.5">
                                                <span>${safeAuthor}</span>
                                                <span class="text-[10px] font-medium text-brand">(Saya)</span>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <p class="prose-content mt-1.5 break-words text-xs leading-relaxed text-slate-800 whitespace-pre-line pl-8">${safeMsg}</p>
                                <time class="mt-1 block text-right text-[10px] leading-none text-muted">${msg.time}</time>
                            </article>
                        `;

                        chatMessages.appendChild(msgWrapper);
                        chatMessages.scrollTop = chatMessages.scrollHeight;

                        discussInput.value = '';
                    } else {
                        discussForm.submit();
                    }
                } catch (err) {
                    discussForm.submit();
                } finally {
                    if (discussSubmitBtn) discussSubmitBtn.disabled = false;
                }
            }
        });
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
    <div class="p-4 border-t border-line/50 text-right">
        <button type="button" onclick="document.getElementById('enrolled-students-modal').close()" class="button-secondary text-xs">
            Tutup
        </button>
    </div>
</dialog>
@endsection
