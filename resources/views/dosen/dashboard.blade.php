@extends('layouts.mahasiswa')

@section('title', 'Dashboard Dosen | SALE')
@section('header', 'Dashboard Dosen')

@section('content')
<div class="space-y-8">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, Budi Santoso.</h1>
            <p class="page-description">Kelola materi perkuliahan, rancang soal berbobot CPMK, dan tinjau pekerjaan mahasiswa.</p>
        </div>
        <div class="flex flex-wrap gap-3 shrink-0">
            <a href="{{ route('dosen.gradebook') }}" class="button-secondary">Rekap Nilai</a>
            <a href="{{ route('dosen.course.create') }}" class="button-primary">+ Tambah Course</a>
        </div>
    </header>

    <div class="rounded-2xl bg-[#e9edf1] p-4 sm:p-5">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            {{-- Column 1: Course yang diampu --}}
            <section aria-labelledby="course-heading">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="course-heading" class="section-heading">Course yang diampu</h2>
                        <p class="mt-1 text-sm leading-5 text-muted">Kelas aktif semester ini.</p>
                    </div>
                    <a href="{{ route('dosen.course.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark">
                        <span class="hidden min-[1320px]:inline">Lihat semua</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="space-y-3">
                    @foreach(\App\Support\LearningPreview::courses() as $course)
                        @php
                            $items = collect(\App\Support\LearningPreview::items())->where('course', $course['id']);
                            $config = \App\Support\AcademicPreview::config($course['id']);
                        @endphp
                        <div class="rounded-xl bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <span class="text-xs font-semibold text-muted">{{ $course['code'] }}</span>
                                    <h3 class="mt-1 text-base font-semibold text-ink">
                                        <a href="{{ route('dosen.course.show', $course['id']) }}" class="hover:text-brand">
                                            {{ $course['title'] }}
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-xs text-muted leading-5">{{ $course['description'] }}</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line/50 pt-3 text-xs">
                                <span class="text-muted">{{ count($items) }} Konten · {{ count($config['cpmk']) }} CPMK · {{ count($config['components']) }} Komponen</span>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('dosen.academic', $course['id']) }}" class="quiet-link">Bobot &amp; CPMK</a>
                                    <a href="{{ route('dosen.gradebook', ['course' => $course['id']]) }}" class="quiet-link">Rekap Nilai</a>
                                    <a href="{{ route('dosen.course.show', $course['id']) }}" class="font-semibold text-brand hover:text-brand-dark">Buka Modul →</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Column 2: Tugas perlu dinilai (Exact same structure & style as mahasiswa deadlines) --}}
            <aside aria-labelledby="grading-heading">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="grading-heading" class="section-heading">Tugas perlu dinilai</h2>
                        <p class="mt-1 text-sm leading-5 text-muted">Pengumpulan menunggu penilaian.</p>
                    </div>
                    <a href="{{ route('dosen.grades') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark">
                        <span class="hidden min-[1320px]:inline">Lihat semua</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="rounded-2xl bg-white px-2 py-2 shadow-sm">
                    @php
                        $pendingItems = collect(\App\Support\LearningPreview::items())->whereIn('type', ['tugas', 'coding', 'kuis'])->filter(fn($i) => session('learning.submissions.'.$i['id']))->take(3);
                        if ($pendingItems->isEmpty()) {
                            $pendingItems = collect(\App\Support\LearningPreview::items())->whereIn('type', ['tugas', 'coding'])->take(2);
                        }
                    @endphp
                    @forelse($pendingItems as $item)
                        @if(!$loop->first)
                            <div class="mx-4 h-px bg-[#e7eaee]" aria-hidden="true"></div>
                        @endif
                        <a href="{{ route('dosen.grades') }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9] transition">
                            <span>
                                <span class="block text-xs font-semibold text-brand">Masuk</span>
                                <span class="mt-1 block text-xs text-muted">Perlu nilai</span>
                            </span>
                            <span>
                                <span class="block text-sm font-semibold leading-5 text-ink">{{ $item['title'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-muted">{{ \App\Support\LearningPreview::course($item['course'])['title'] }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="p-4 text-sm text-muted">Belum ada pengumpulan baru.</p>
                    @endforelse
                </div>
            </aside>

            {{-- Column 3: Diskusi terbaru (Exact same structure & style as mahasiswa discussions) --}}
            <section aria-labelledby="discussion-heading">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="discussion-heading" class="section-heading">Diskusi terbaru</h2>
                        <p class="mt-1 text-sm leading-5 text-muted">Percakapan dari course aktif.</p>
                    </div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark">
                        <span class="hidden min-[1320px]:inline">Buka forum</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="space-y-3">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                        <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="block rounded-xl bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <p class="text-xs font-semibold text-muted">{{ $discussion['course_title'] }}</p>
                            <p class="mt-2 text-sm font-semibold leading-5 text-ink">{{ $discussion['message'] }}</p>
                            <p class="mt-2 text-xs text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
