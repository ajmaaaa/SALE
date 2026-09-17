@extends('layouts.mahasiswa')

@section('title', $course['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $role = request()->is('dosen*') ? 'dosen' : 'mahasiswa';
    $materiItems = collect($items)->where('type', 'materi');
    $tugasItems = collect($items)->whereIn('type', ['tugas', 'coding', 'kuis']);
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
            <div class="flex flex-wrap items-center gap-2 text-xs text-muted font-medium">
                <span class="font-semibold text-ink">{{ $course['code'] }}</span>
                <span class="h-3 w-px bg-line"></span>
                <span>3 SKS</span>
                <span class="h-3 w-px bg-line"></span>
                <span>Semester Ganjil 2026/2027</span>
                <span class="h-3 w-px bg-line"></span>
                <span>Wajib</span>
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
                <span>{{ count($enrolledStudents) }} Mahasiswa</span>
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
                    <div class="flex h-full flex-col items-center justify-center px-6 text-center text-white">
                        <div class="mb-3.5 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 backdrop-blur-sm text-white transition group-hover:scale-110 group-hover:bg-brand">
                            <svg class="h-6 w-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="m9 7 9 5-9 5V7z"/>
                            </svg>
                        </div>
                        <h2 id="video-heading" class="text-base sm:text-lg font-bold text-white">
                            {{ $course['title'] }}: Pengantar &amp; Konsep Utama
                        </h2>
                        <p class="mt-1 text-xs text-[#c9d3d9]">Video pengantar perkuliahan — 24 menit</p>
                        @if(!empty($course['video']))
                            <a href="{{ $course['video'] }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-bold text-[#172633] shadow hover:bg-slate-100 transition">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                Putar Video Pengantar ↗
                            </a>
                        @else
                            <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-bold text-[#172633] shadow hover:bg-slate-100 transition">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                Putar Video
                            </button>
                        @endif
                    </div>
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
                                    <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-canvas transition">
                                        <span class="shrink-0 text-brand">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h4>
                                            <p class="mt-0.5 text-xs text-muted flex items-center gap-2">
                                                <span>Materi belajar</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span>{{ count(\App\Support\LearningPreview::discussions($item['id'])) }} diskusi</span>
                                            </p>
                                        </div>
                                        <span class="text-xs font-semibold text-brand">
                                            Buka Materi →
                                        </span>
                                    </a>
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
                                        $hasSubmission = session('learning.submissions.'.$item['id']);
                                    @endphp
                                    <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="group flex items-center gap-4 px-5 py-4 hover:bg-canvas transition">
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
                                            <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h4>
                                            <div class="mt-0.5 text-xs text-muted flex flex-wrap items-center gap-2">
                                                <span>{{ \App\Support\LearningPreview::labels()[$item['type']] }}</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span>{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tugas perkuliahan' }}</span>
                                                <span class="h-2.5 w-px bg-line"></span>
                                                <span>{{ count(\App\Support\LearningPreview::discussions($item['id'])) }} diskusi</span>
                                            </div>
                                        </div>

                                        @if($hasSubmission)
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 shrink-0">
                                                Selesai
                                            </span>
                                        @else
                                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 shrink-0">
                                                Kerjakan →
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
                    <div>
                        <h2 id="discuss-heading" class="text-sm font-bold text-ink">Forum Diskusi Kelas</h2>
                        <p class="mt-0.5 text-xs text-muted">Tanya-jawab &amp; diskusi kelas.</p>
                    </div>
                    <span class="status font-semibold text-muted bg-canvas">
                        {{ count(\App\Support\LearningPreview::courseDiscussions($course['id'])) }} Pesan
                    </span>
                </div>

                {{-- Messages List --}}
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    @forelse(\App\Support\LearningPreview::courseDiscussions($course['id']) as $msg)
                        <article class="rounded-xl border border-line/50 bg-canvas/60 p-3.5 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">
                                        {{ collect(explode(' ', $msg['author']))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('') }}
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-xs font-bold text-ink truncate flex items-center gap-1.5">
                                            {{ $msg['author'] }}
                                            @if(($msg['role'] ?? '') === 'dosen')
                                                <span class="status text-[10px] font-medium py-0 px-1 text-muted bg-canvas">Dosen</span>
                                            @endif
                                        </h3>
                                        <span class="text-[10px] text-muted block">{{ $msg['time'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <p class="prose-content text-xs leading-relaxed text-slate-700 whitespace-pre-line">{{ $msg['message'] }}</p>
                        </article>
                    @empty
                        <div class="rounded-xl border border-line/60 bg-canvas p-5 text-center text-xs text-muted">
                            Belum ada pesan di forum kelas ini.
                        </div>
                    @endforelse
                </div>

                {{-- Send Message Form --}}
                <form method="post" action="{{ route('mahasiswa.course.discuss.class', $course['id']) }}" class="space-y-2.5 pt-2 border-t border-line/50">
                    @csrf
                    <div>
                        <label for="course_discuss_message" class="sr-only">Tulis Pesan Diskusi Kelas</label>
                        <textarea maxlength="3000" name="message" id="course_discuss_message" rows="2" required class="field text-xs resize-none" placeholder="Tulis pesan untuk dosen &amp; kelas..."></textarea>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] text-muted">Terbuka untuk seisi kelas</span>
                        <button type="submit" class="button-primary text-xs py-1.5 px-3.5 font-semibold shadow-xs">Kirim Pesan</button>
                    </div>
                </form>
            </section>
        </aside>
    </div>
</div>

{{-- Modal Daftar Mahasiswa Tergabung (Clean & Authentic Academic Design) --}}
<dialog id="enrolled-students-modal" class="backdrop:bg-black/40 rounded-xl p-0 shadow-lg border border-line/60 w-full max-w-lg overflow-hidden m-auto">
    <div class="p-4 sm:p-5 border-b border-line/60 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-ink text-base">Mahasiswa Tergabung</h3>
            <p class="text-xs text-muted mt-0.5">{{ count($enrolledStudents) }} mahasiswa terdaftar pada kelas ini</p>
        </div>
        <button type="button" onclick="document.getElementById('enrolled-students-modal').close()" class="text-muted hover:text-ink text-sm p-1">
            <span class="sr-only">Tutup</span>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <div class="p-4 sm:p-5 max-h-[60vh] overflow-y-auto divide-y divide-line/40">
        @forelse($enrolledStudents as $student)
            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ink truncate">{{ $student['name'] }}</p>
                    <p class="text-xs text-muted truncate"><span class="font-mono">{{ $student['number'] }}</span> <span class="h-2.5 w-px bg-line inline-block mx-1.5 align-middle"></span> <span>{{ $student['email'] }}</span></p>
                </div>
                <span class="text-xs text-muted font-medium shrink-0">Terdaftar</span>
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
