@extends('layouts.mahasiswa')

@section('title', 'Forum Diskusi | SALE')
@section('header', 'Forum Diskusi')

@section('content')
@php
    $courseMap = collect($courses)->keyBy('id');
    $isDosenRoute = request()->is('dosen*');
    $discussionRoute = $isDosenRoute ? 'dosen.discussion.index' : 'mahasiswa.discussion.index';
    $courseRoute = $isDosenRoute ? 'dosen.course.show' : 'mahasiswa.course.show';

    // Filter non-announcement items
    $discussionItems = collect($items)->filter(fn($i) => ($i['type'] ?? '') !== 'pengumuman');
@endphp

<div class="space-y-6">
    {{-- Clean Header --}}
    <header class="pb-1">
        <h1 class="page-heading">Forum Diskusi Perkuliahan</h1>
        <p class="page-description">Ruang tanya jawab dan diskusi materi antar mahasiswa dan dosen pengampu.</p>
    </header>

    {{-- Clean Discussion Thread List per Course (Entire row is clickable) --}}
    <section class="surface overflow-hidden divide-y divide-line/40" aria-label="Daftar Forum Diskusi Kelas">
        <div class="px-4 py-3 sm:px-5 bg-white border-b border-line/60 flex items-center justify-between text-xs text-muted font-bold uppercase tracking-wider">
            <span>Daftar Forum Diskusi Kelas</span>
            <span class="hidden sm:inline">Aktivitas Pesan</span>
        </div>

        @forelse($courses as $c)
            @php
                $stats = $discussionStats[$c['id']] ?? ['unread_count' => 0, 'mention_count' => 0, 'latest_message' => null, 'is_read' => true];
                $msgCount = $stats['unread_count'];
                $mentionCount = $stats['mention_count'];
            @endphp
            <a href="{{ route($courseRoute, $c['id']) }}#diskusi-kelas"
                   class="group flex items-center justify-between gap-3 px-4 py-3.5 sm:px-5 sm:py-4 hover:bg-canvas transition">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-brand shrink-0">{{ $c['code'] }}</span>
                            <h3 class="text-sm font-semibold text-ink group-hover:text-brand transition truncate">
                                {{ $c['title'] }}
                            </h3>
                        </div>
                        <p class="mt-1 text-xs text-muted flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span>Pengampu: {{ $c['lecturer'] ?? 'Dosen Pengampu' }}</span>
                            <span class="hidden sm:inline">(Forum Diskusi &amp; Konsultasi Akademik)</span>
                        </p>
                    </div>
                    <div data-course-msg-badge="{{ $c['id'] }}" class="shrink-0 flex items-center gap-1.5 sm:gap-2 justify-end">
                        @if($mentionCount > 0)
                            <span data-course-mention-pill="{{ $c['id'] }}" class="inline-flex h-6 items-center justify-center rounded-full bg-[#102f50] px-2 text-xs font-bold text-white shadow-2xs gap-0.5" title="{{ $mentionCount }} sebutan (@) untuk Anda">
                                <span class="font-mono font-black">@</span>{{ $mentionCount }}
                            </span>
                        @endif
                        @if($msgCount > 0)
                            <span data-course-unread-pill="{{ $c['id'] }}" class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#4c1d95] px-2.5 text-xs font-bold text-white whitespace-nowrap" title="{{ $msgCount }} pesan belum dibaca">
                                {{ $msgCount }} belum dibaca
                            </span>
                        @else
                            <span data-course-unread-pill="{{ $c['id'] }}" class="text-xs text-muted font-normal whitespace-nowrap">Tidak ada pesan baru</span>
                        @endif
                    </div>
                </a>
        @empty
            <div class="p-8 text-center text-xs text-muted">
                Belum ada mata kuliah yang terdaftar.
            </div>
        @endforelse
    </section>
</div>

<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('sale:live-status', function(e) {
            if (!e.detail) return;
            const discussionCounts = e.detail.course_discussion_counts || {};
            const mentionCounts = e.detail.course_mention_counts || {};

            document.querySelectorAll('[data-course-msg-badge]').forEach(container => {
                const courseId = container.getAttribute('data-course-msg-badge');
                const msgCount = Number(discussionCounts[courseId]) || 0;
                const mentionCount = Number(mentionCounts[courseId]) || 0;

                let html = '';
                if (mentionCount > 0) {
                    html += `<span data-course-mention-pill="${courseId}" class="inline-flex h-6 items-center justify-center rounded-full bg-[#102f50] px-2 text-xs font-bold text-white shadow-2xs gap-0.5" title="${mentionCount} sebutan (@) untuk Anda"><span class="font-mono font-black">@</span>${mentionCount}</span>`;
                }
                if (msgCount > 0) {
                    html += `<span data-course-unread-pill="${courseId}" class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#4c1d95] px-2.5 text-xs font-bold text-white whitespace-nowrap" title="${msgCount} pesan belum dibaca">${msgCount} belum dibaca</span>`;
                } else {
                    html += `<span data-course-unread-pill="${courseId}" class="text-xs text-muted font-normal whitespace-nowrap">Tidak ada pesan baru</span>`;
                }
                container.innerHTML = html;
            });
        });
    });
</script>
@endsection
