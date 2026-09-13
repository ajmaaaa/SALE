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
        <div class="grid items-start gap-6 md:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            {{-- Column 1 (KIRI): Course yang diampu (4 kartu petak-petak 2x2) --}}
            <section aria-labelledby="course-heading" class="min-w-0 md:col-span-2 xl:col-span-1">
                <div class="mb-4 flex h-12 items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="course-heading" class="section-heading text-base sm:text-lg truncate">Course yang diampu</h2>
                        <p class="mt-0.5 text-xs text-muted truncate">Kelas aktif semester ini</p>
                    </div>
                    <a href="{{ route('dosen.course.index') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand hover:text-brand-dark pt-0.5">
                        <span>Lihat semua</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(collect(\App\Support\LearningPreview::courses())->take(4) as $course)
                        @include('learning.partials.course-card', ['course' => $course, 'role' => 'dosen', 'isFirst' => $loop->first])
                    @endforeach
                </div>
            </section>

            {{-- Column 2 (TENGAH): Tugas perlu dinilai --}}
            <aside aria-labelledby="grading-heading" class="min-w-0">
                <div class="mb-4 flex h-12 items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="grading-heading" class="section-heading text-base sm:text-lg truncate">Tugas perlu dinilai</h2>
                        <p class="mt-0.5 text-xs text-muted truncate">Menunggu penilaian dosen</p>
                    </div>
                    <a href="{{ route('dosen.grades') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand hover:text-brand-dark pt-0.5">
                        <span>Lihat semua</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="rounded-xl bg-white px-2 py-2 shadow-sm border border-line/60">
                    @php
                        $pendingItems = collect(\App\Support\LearningPreview::items())->whereIn('type', ['tugas', 'coding', 'kuis'])->filter(fn($i) => session('learning.submissions.'.$i['id']))->take(4);
                        if ($pendingItems->isEmpty()) {
                            $pendingItems = collect(\App\Support\LearningPreview::items())->whereIn('type', ['tugas', 'coding'])->take(3);
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

            {{-- Column 3 (KANAN): Diskusi terbaru --}}
            <section aria-labelledby="discussion-heading" class="min-w-0">
                <div class="mb-4 flex h-12 items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="discussion-heading" class="section-heading text-base sm:text-lg truncate">Diskusi terbaru</h2>
                        <p class="mt-0.5 text-xs text-muted truncate">Percakapan aktif kelas</p>
                    </div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand hover:text-brand-dark pt-0.5">
                        <span>Buka forum</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="rounded-xl bg-white shadow-sm divide-y divide-line/60 overflow-hidden border border-line/60">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                        <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="block p-4 text-xs transition duration-200 hover:bg-[#f3f6f9]">
                            <p class="text-[11px] font-semibold text-muted">{{ $discussion['course_title'] }}</p>
                            <p class="mt-1 text-xs font-semibold leading-relaxed text-ink line-clamp-2">{{ $discussion['message'] }}</p>
                            <p class="mt-1.5 text-[11px] text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
