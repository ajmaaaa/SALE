@extends('layouts.mahasiswa')

@section('title', $course['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $role = request()->is('dosen*') ? 'dosen' : 'mahasiswa';
    $cpmkList = \App\Support\AcademicPreview::config($course['id'])['cpmk'] ?? [];
    $modules = collect($items)->where('type', '!=', 'pengumuman')->groupBy('module');
    $announcements = collect($items)->where('type', 'pengumuman');
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
        @endif
    </header>

    {{-- 2-Column Responsive Layout: Content di Kiri & Sidebar/Forum Diskusi di Kanan --}}
    <div class="grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_380px]">
        
        {{-- KOLOM KIRI: Video Pengantar Perkuliahan & Daftar Modul/Tugas --}}
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
                        <p class="mt-1 text-xs text-[#c9d3d9]">Video pengantar perkuliahan · 24 menit</p>
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

            {{-- Modules List --}}
            <section aria-labelledby="module-heading">
                <div class="mb-3.5 flex items-end justify-between gap-4">
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
                                        {{-- Icon: simple, clean, no background box --}}
                                        <span class="shrink-0 text-muted group-hover:text-ink transition">
                                            @if($item['type'] === 'coding')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                            @elseif($item['type'] === 'kuis')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                                            @elseif($item['type'] === 'materi')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h8l4 4v14H5V3zM14 3v5h5"/></svg>
                                            @else
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            @endif
                                        </span>

                                        {{-- Content info --}}
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h4>
                                            <p class="mt-0.5 text-xs text-muted">
                                                <span>{{ \App\Support\LearningPreview::labels()[$item['type']] }}</span>
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

        {{-- KOLOM KANAN (SIDEBAR DISAMPING): Dosen Pengampu, Pengumuman, CPMK, & Forum Diskusi (Chat Paling Bawah) --}}
        <aside class="space-y-6">
            {{-- 1. Dosen Pengampu Info Card --}}
            <section class="surface p-5 rounded-xl border border-line/60" aria-labelledby="lecturer-heading">
                <span class="text-xs font-semibold text-muted uppercase tracking-wider block">Dosen Pengampu</span>
                <h3 id="lecturer-heading" class="mt-1.5 text-sm font-bold text-ink">{{ $course['lecturer'] }}</h3>
                <p class="mt-0.5 text-xs text-muted">Fakultas Ilmu Komputer</p>
                <p class="mt-2 text-xs leading-relaxed text-muted">Diskusikan materi perkuliahan atau tugas melalui forum diskusi kelas di bawah.</p>
            </section>

            {{-- 2. Pengumuman Kelas jika ada --}}
            @if(count($announcements) > 0)
                <section class="surface p-5 rounded-xl border border-line/60" aria-labelledby="announcement-heading">
                    <div class="flex items-center justify-between border-b border-line/40 pb-2.5 mb-3">
                        <h2 id="announcement-heading" class="text-xs font-bold uppercase tracking-wider text-ink">Pengumuman Kelas</h2>
                        <span class="text-xs text-muted font-semibold">{{ count($announcements) }}</span>
                    </div>
                    <div class="divide-y divide-line/40">
                        @foreach($announcements as $announcement)
                            <article class="py-2.5 first:pt-0 last:pb-0">
                                <a href="{{ route('mahasiswa.course.item', [$course['id'], $announcement['id']]) }}" class="group block">
                                    <h3 class="text-xs font-bold text-ink group-hover:text-brand transition">{{ $announcement['title'] }}</h3>
                                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($announcement['body'], 120) }}</p>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- 3. CPMK Outcomes (Collapsible Accordion) --}}
            @if(count($cpmkList) > 0)
                <details class="surface p-4 sm:p-5 rounded-xl border border-line/60">
                    <summary class="cursor-pointer text-xs font-semibold text-ink">Capaian Pembelajaran (CPMK)</summary>
                    <div class="mt-3 divide-y divide-line/40 text-xs">
                        @foreach($cpmkList as $cpmk)
                            <div class="py-2.5 first:pt-0 last:pb-0">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-ink">{{ $cpmk['code'] }}</span>
                                    <span class="text-[11px] text-muted">{{ $cpmk['cpl'] ?? 'CPL' }}</span>
                                </div>
                                <p class="mt-1 text-muted leading-relaxed">{{ $cpmk['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- 4. Forum Diskusi Kelas (Chat Paling Bawah di Sidebar) --}}
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
                <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
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
@endsection
