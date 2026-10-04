@extends('layouts.mahasiswa')

@section('title', 'Course | SALE')
@section('header', 'Course')

@section('content')
<div class="space-y-7">
    <header class="flex flex-wrap items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="page-heading">Course</h1>
            <p class="page-description">Kelas aktif yang telah ditetapkan oleh program studi pada semester ini.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="document.getElementById('join-class-modal').showModal()" class="button-secondary text-xs font-semibold">
                + Gabung Kelas
            </button>
        </div>
    </header>

    {{-- Tab Navigasi [Kelas Aktif] / [Arsip Kelas] (PRD §6.3) --}}
    @php
        $currentTab = $tab ?? 'active';
        $activeBadge = $activeCount ?? 0;
        $archivedBadge = $archivedCount ?? 0;
    @endphp
    <div class="flex items-center gap-2 border-b border-line/60" role="tablist">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'active']) }}"
           class="px-4 py-2 text-xs font-semibold border-b-2 {{ $currentTab !== 'archived' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }} -mb-px transition flex items-center gap-1.5"
           aria-selected="{{ $currentTab !== 'archived' ? 'true' : 'false' }}">
            <span>Kelas Aktif</span>
            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $currentTab !== 'archived' ? 'bg-brand/10 text-brand' : 'bg-slate-100 text-slate-600' }} font-bold">
                {{ $activeBadge }}
            </span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'archived']) }}"
           class="px-4 py-2 text-xs font-semibold border-b-2 {{ $currentTab === 'archived' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }} -mb-px transition flex items-center gap-1.5"
           aria-selected="{{ $currentTab === 'archived' ? 'true' : 'false' }}">
            <span>Arsip Kelas</span>
            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $currentTab === 'archived' ? 'bg-brand/10 text-brand' : 'bg-slate-100 text-slate-600' }} font-bold">
                {{ $archivedBadge }}
            </span>
        </a>
    </div>

    <form class="flex flex-col gap-3 sm:flex-row sm:items-center" action="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" method="GET">
        <input type="hidden" name="tab" value="{{ $currentTab }}">
        <label class="sr-only" for="course-search">Cari course</label>
        <div class="relative w-full sm:max-w-md">
            <input id="course-search" name="q" type="search" class="field w-full" placeholder="Cari judul, kode, atau dosen..." value="{{ request('q') }}" autocomplete="off">
        </div>

        @if(!empty($semesters) && $semesters->count())
            <label class="sr-only" for="course-semester">Semester</label>
            <select id="course-semester" name="semester" onchange="this.form.submit()" class="field sm:w-56 text-xs font-semibold">
                <option value="">Semua Semester</option>
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ (string) request('semester', $selectedSemesterId ?? '') === (string) $sem->id ? 'selected' : '' }}>
                        {{ $sem->display_name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        @endif
    </form>

    <section id="course-list-container" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar course">
        @forelse ($courses as $course)
            <div class="course-card-item h-full" data-search="{{ mb_strtolower($course['code'] . ' ' . $course['title'] . ' ' . ($course['lecturer'] ?? '') . ' ' . ($course['dosen_ketua'] ?? '') . ' ' . ($course['enrollment_code'] ?? '')) }}">
                @include('learning.partials.course-card', ['course' => $course, 'role' => request()->is('dosen*') ? 'dosen' : 'mahasiswa', 'isFirst' => $loop->first])
            </div>
        @empty
            <div class="col-span-full py-2 text-xs text-muted">
                {{ request()->filled('q') ? 'Kelas tidak ditemukan.' : 'Belum ada kelas.' }}
            </div>
        @endforelse
        <div id="no-search-results" class="col-span-full py-2 text-xs text-muted" style="display: none;"></div>
    </section>

    @include('learning.partials.join-class-dialog', ['joinAsDosen' => request()->is('dosen*')])
</div>

<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('course-search');
        const container = document.getElementById('course-list-container');
        const emptyNotice = document.getElementById('no-search-results');
        if (!searchInput || !container) return;

        function filterCourses() {
            const query = searchInput.value.trim().toLowerCase();
            const cards = container.querySelectorAll('.course-card-item');
            let visibleCount = 0;

            cards.forEach(card => {
                const text = (card.getAttribute('data-search') || card.textContent || '').toLowerCase();
                const matches = !query || text.includes(query);
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            if (emptyNotice) {
                if (visibleCount === 0 && cards.length > 0) {
                    emptyNotice.textContent = ['Kelas', 'tidak', 'ditemukan.'].join(' ');
                    emptyNotice.style.display = '';
                } else {
                    emptyNotice.style.display = 'none';
                }
            }
        }

        searchInput.form?.addEventListener('submit', function (e) {
            e.preventDefault();
            filterCourses();
        });

        searchInput.addEventListener('input', filterCourses);
        searchInput.addEventListener('keyup', filterCourses);
        searchInput.addEventListener('search', filterCourses);
    });
</script>
@endsection
