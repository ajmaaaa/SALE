@extends('layouts.mahasiswa')

@section('title', 'Dashboard | SALE')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-7">
    <header class="flex flex-col gap-5 border-b border-line/60 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, Ahmad.</h1>
            <p class="page-description">Lanjutkan perkuliahan dari materi terakhir atau periksa pekerjaan yang segera berakhir.</p>
        </div>
            <a href="{{ route('mahasiswa.course.show', [1]) }}" class="button-primary shrink-0">Lanjutkan belajar</a>
    </header>

        <section class="grid gap-4 md:grid-cols-3" aria-label="Ringkasan akademik">
            <div class="surface flex items-center gap-4 p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-soft text-brand">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                </div>
                <div><p class="text-xs text-muted">Course diikuti</p><p class="mt-1 text-2xl font-bold text-ink">{{ count($courses) }}</p></div>
            </div>
            <div class="surface flex items-center gap-4 p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V5M4 19h16"/><path d="m7 15 3-4 3 2 5-7"/></svg>
                </div>
                <div><p class="text-xs text-muted">Nilai tersedia</p><p class="mt-1 text-2xl font-bold text-ink">{{ collect($courseResults)->filter(fn($result) => $result['average'] !== null)->count() }} <span class="text-sm font-normal text-muted">course</span></p></div>
            </div>
            <div class="surface flex items-center gap-4 p-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div><p class="text-xs text-muted">Pekerjaan belum dikumpulkan</p><p class="mt-1 text-2xl font-bold text-ink">{{ $activeItems->count() }}</p></div>
            </div>
        </section>

    <section class="rounded-xl bg-[#102f50] p-6 sm:p-7 text-white shadow-2xs border border-[#1b3f68]" aria-labelledby="recommendation-heading">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-slate-200">
                    <span>Rekomendasi Materi</span>
                </div>
                <h2 id="recommendation-heading" class="text-lg sm:text-xl font-bold text-white tracking-tight">Perkuat pemahaman traversal pada binary tree</h2>
                <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">Pelajari kembali preorder, inorder, dan postorder, lalu terapkan pemahaman Anda pada lembar praktikum interaktif.</p>
            </div>
            <a href="{{ route('mahasiswa.course.item', [1, 3]) }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-white px-5 text-xs font-bold text-[#102f50] hover:bg-slate-100 transition shadow-xs">
                Buka Materi Rekomendasi
            </a>
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.75fr)] xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">
            {{-- Column 1 (KIRI): Course semester ini (4 kartu petak-petak 2x2) --}}
            <section aria-labelledby="course-heading" class="min-w-0">
                <div class="mb-4 flex h-12 items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="course-heading" class="section-heading text-base sm:text-lg truncate">Course semester ini</h2>
                        <p class="mt-0.5 text-xs text-muted truncate">Kelas aktif program studi</p>
                    </div>
                    <a href="{{ route('mahasiswa.course.index') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand hover:text-brand-dark pt-0.5">
                        <span>Lihat semua</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(collect($courses)->take(4) as $course)
                        @include('learning.partials.course-card', ['course' => $course, 'role' => 'mahasiswa', 'isFirst' => $loop->first])
                    @endforeach
                </div>
            </section>

            <div class="min-w-0 space-y-6">
            {{-- Column 2 (TENGAH): Tenggat terdekat --}}
            <aside aria-labelledby="deadline-heading" class="min-w-0">
                <div class="mb-4 flex h-12 items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="deadline-heading" class="section-heading text-base sm:text-lg truncate">Tenggat terdekat</h2>
                        <p class="mt-0.5 text-xs text-muted truncate">Tugas segera berakhir</p>
                    </div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand hover:text-brand-dark pt-0.5">
                        <span>Lihat semua</span>
                    </a>
                </div>

                <div class="rounded-xl bg-white px-2 py-2 shadow-sm border border-line/60">
                    @forelse($activeItems->take(4) as $item)
                        @if(!$loop->first)
                            <div class="mx-4 h-px bg-[#e7eaee]" aria-hidden="true"></div>
                        @endif
                        @php
                            $isToday = $item['due'] && \Carbon\Carbon::parse($item['due'])->isToday();
                            $dateText = $item['due'] ? ($isToday ? 'Hari ini' : \Carbon\Carbon::parse($item['due'])->translatedFormat('d M')) : 'Bebas';
                            $timeText = $item['due'] ? \Carbon\Carbon::parse($item['due'])->format('H.i') : '';
                        @endphp
                        <a href="{{ route('mahasiswa.course.item', [$item['course'], $item['id']]) }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9] transition">
                            <span>
                                <span class="block text-xs font-semibold {{ $isToday ? 'text-danger' : 'text-ink' }}">{{ $dateText }}</span>
                                @if($timeText)
                                    <span class="mt-1 block text-xs text-muted">{{ $timeText }}</span>
                                @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold leading-5 text-ink">{{ $item['title'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-muted">{{ \App\Support\LearningPreview::course($item['course'])['title'] }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="p-4 text-sm text-muted">Semua pekerjaan telah dikumpulkan.</p>
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
                    </a>
                </div>

                <div class="rounded-xl bg-white shadow-sm divide-y divide-line/60 overflow-hidden border border-line/60">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                        <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="block p-4 text-xs transition duration-200 hover:bg-canvas">
                            <p class="text-[11px] font-medium text-muted">{{ $discussion['course_title'] }}</p>
                            <p class="mt-1 text-xs font-semibold leading-relaxed text-ink line-clamp-2">{{ $discussion['message'] }}</p>
                            <p class="mt-1.5 text-[11px] text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
            </div>
        </div>
    </div>
</div>
@endsection
