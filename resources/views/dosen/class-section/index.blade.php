@extends('layouts.mahasiswa')

@php
    $isRekap = ($mode ?? 'penilaian') === 'rekap';
@endphp

@section('title', ($isRekap ? 'Rekap Nilai' : 'Penilaian') . ' | SALE')
@section('header', $isRekap ? 'Rekap Nilai' : 'Penilaian')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="page-heading">{{ $isRekap ? 'Rekap Nilai' : 'Penilaian' }}</h1>
            <p class="page-description">
                {{ $isRekap ? 'Lihat rekap nilai mahasiswa untuk setiap kelas yang Anda ampu.' : 'Kelola penilaian berbasis OBE untuk setiap kelas yang Anda ampu pada semester berjalan.' }}
            </p>
        </div>
        @unless($isRekap)
            <button type="button" onclick="document.getElementById('join-class-modal').showModal()" class="button-secondary shrink-0 text-xs font-semibold">
                + Gabung Kelas
            </button>
        @endunless
    </header>

    {{-- Tab Navigasi [Kelas Aktif] / [Arsip Kelas] (Sama seperti Menu Course) --}}
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

    <form class="flex flex-col gap-3 sm:flex-row sm:items-center" action="{{ route($isRekap ? 'dosen.rekap.index' : 'dosen.penilaian.index') }}" method="GET">
        <input type="hidden" name="tab" value="{{ $currentTab }}">
        <label class="sr-only" for="class-search">Cari kelas</label>
        <div class="relative w-full sm:max-w-md">
            <input id="class-search" name="q" type="search" class="field w-full" placeholder="Cari judul, kode, atau dosen..." value="{{ request('q') }}" autocomplete="off">
        </div>

        @if(!empty($semesters) && $semesters->count())
            <label class="sr-only" for="class-semester">Semester</label>
            <select id="class-semester" name="semester" onchange="this.form.submit()" class="field sm:w-56 text-xs font-semibold">
                <option value="">Semua Semester</option>
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ (string) request('semester', $selectedSemesterId ?? '') === (string) $sem->id ? 'selected' : '' }}>
                        {{ $sem->display_name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        @endif
    </form>

    @if($sections->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">{{ request()->filled('q') || request()->filled('semester') ? 'Kelas tidak ditemukan' : ($currentTab === 'archived' ? 'Belum ada kelas yang diarsipkan' : 'Belum ada kelas yang diampu') }}</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                {{ request()->filled('q') || request()->filled('semester') ? 'Coba ubah kata kunci pencarian atau pilih semester lain.' : ($currentTab === 'archived' ? 'Kelas yang telah diarsipkan pada semester sebelumnya akan muncul di sini.' : 'Kelas yang ditugaskan Admin Prodi atau Anda masuki melalui kode akan muncul di sini.') }}
            </p>
        </div>
    @else
        <div id="class-list-container" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($sections as $section)
                @php
                    $progress = $section->grading_progress;
                    $currentUserId = auth()->id();
                    $isAnggota = ($section->dosen_pendamping_id == $currentUserId)
                        || ($section->relationLoaded('dosenAnggota')
                            ? $section->dosenAnggota->contains('id', $currentUserId)
                            : $section->dosenAnggota()->where('users.id', $currentUserId)->exists());
                    $isWakil = $isAnggota;
                    $allAnggota = $section->relationLoaded('dosenAnggota') && $section->dosenAnggota->isNotEmpty()
                        ? $section->dosenAnggota
                        : ($section->dosenPendamping ? collect([$section->dosenPendamping]) : collect());
                    $searchData = mb_strtolower($section->mataKuliah->code . ' ' . $section->section_code . ' ' . $section->mataKuliah->name . ' ' . $section->dosen->name . ' ' . $allAnggota->pluck('name')->join(' '));
                @endphp
                <div class="class-card-item h-full flex flex-col" data-search="{{ $searchData }}">
                @if($isRekap)
                    {{-- Rekap Nilai Card Design (Variasi B: Struktur Baris Data Laporan / Row Data Ledger) --}}
                    <div class="surface p-6 border border-line/80 rounded-2xl flex flex-col justify-between hover:border-slate-400 hover:shadow-md transition-all duration-200 min-h-[250px] bg-white group">
                        <div class="flex-1">
                            {{-- Header Meta: Kode Kelas & Semester di kiri, Persentase polos (icon + teks tanpa box label) di kanan --}}
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs font-bold font-mono text-brand shrink-0">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
                                    @if($isAnggota)
                                        <span class="text-xs text-muted shrink-0">(Dosen Anggota)<span class="sr-only">Dosen Wakil</span></span>
                                    @endif
                                    <span class="text-slate-300 shrink-0">•</span>
                                    <span class="text-xs text-muted truncate">{{ $section->semester->name }}</span>
                                </div>
                                @if($progress !== null)
                                    {{-- Persentase polos: hanya icon dan teks, tanpa label bewarna / border / box --}}
                                    <div class="flex items-center gap-1.5 text-slate-500 shrink-0" title="Kelengkapan Rekapitulasi: {{ $progress }}%">
                                        <svg class="h-4 w-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                            <polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                        <span class="font-mono text-xs font-bold text-ink">{{ $progress }}%</span>
                                    </div>
                                @endif
                            </div>

                            <h2 class="text-base font-bold text-ink leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-brand transition-colors" title="{{ $section->mataKuliah->name }}">
                                {{ $section->mataKuliah->name }}
                            </h2>
                            
                            <p class="text-xs text-muted mt-1">
                                Pengampu: {{ $section->dosen->name }}@if($allAnggota->isNotEmpty()), {{ $allAnggota->pluck('name')->join(', ') }}@endif
                            </p>

                            {{-- Variasi B: Baris Laporan Ringkas Bersih (Row Data Ledger) --}}
                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-2 text-xs">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="text-muted flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                        Peserta Terdaftar
                                    </span>
                                    <span class="font-bold text-ink font-mono">{{ $section->students_count }} Mahasiswa</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="text-muted flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        Komponen Nilai
                                    </span>
                                    <span class="font-bold text-ink font-mono">{{ $section->assessments_count }} Asesmen</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="text-muted flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h10"/></svg>
                                        Bobot Perkuliahan
                                    </span>
                                    <span class="font-bold text-ink font-mono">{{ $section->mataKuliah->sks ? $section->mataKuliah->sks . ' SKS' : '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-line/50">
                            <a href="{{ route('dosen.penilaian.rekap', $section->id) }}" class="button-primary text-xs w-full py-2.5 text-center font-bold flex items-center justify-center gap-2 shadow-xs hover:shadow transition">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                Rekap Nilai
                            </a>
                        </div>
                    </div>
                @else
                    {{-- Penilaian Card Design (Pertahankan persis seperti saat ini dengan Circular Ring) --}}
                    <div class="surface p-6 border border-line/70 rounded-2xl flex flex-col justify-between hover:border-brand/40 hover:shadow-md transition-all duration-200 min-h-[250px] bg-white group">
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1.5 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold font-mono text-brand">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
                                        @if($isAnggota)
                                            <span class="text-xs text-muted">(Dosen Anggota)<span class="sr-only">Dosen Wakil</span></span>
                                        @endif
                                    </div>
                                    <h2 class="text-base font-bold text-ink leading-snug line-clamp-2 min-h-[2.75rem] group-hover:text-brand transition-colors" title="{{ $section->mataKuliah->name }}">
                                        {{ $section->mataKuliah->name }}
                                    </h2>
                                    <p class="text-xs text-muted flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                        <span>{{ $section->semester->name }}</span>
                                    </p>
                                    <p class="text-xs text-muted mt-0.5">
                                        Pengampu: {{ $section->dosen->name }}@if($allAnggota->isNotEmpty()), {{ $allAnggota->pluck('name')->join(', ') }}@endif
                                    </p>
                                </div>

                                {{-- Circular Progress Ring --}}
                                @if($progress !== null)
                                    <div class="relative flex items-center justify-center w-12 h-12 shrink-0 rounded-full bg-canvas/60 p-1" title="Kemajuan Penilaian: {{ $progress }}%">
                                        <svg class="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-line/60" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path class="text-brand" stroke-width="3" stroke-dasharray="{{ $progress }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute text-[11px] font-bold text-ink font-mono">{{ $progress }}%</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-5 pt-3.5 border-t border-line/50 grid grid-cols-2 gap-2 text-xs text-muted">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span><strong class="font-semibold text-ink">{{ $section->students_count }}</strong> Mahasiswa</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <span><strong class="font-semibold text-ink">{{ $section->assessments_count }}</strong> Asesmen</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-line/50">
                            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-primary text-xs w-full py-2.5 text-center font-bold block shadow-xs hover:shadow transition">
                                Kelola Penilaian
                            </a>
                        </div>
                    </div>
                @endif
                </div>
            @endforeach
            <div id="no-search-results" class="col-span-full py-8 text-center text-sm text-muted" style="display: none;"></div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('class-search');
        const container = document.getElementById('class-list-container');
        const emptyNotice = document.getElementById('no-search-results');
        if (!searchInput || !container) return;

        const cards = Array.from(container.querySelectorAll('.class-card-item'));

        function applyFilter() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            cards.forEach(card => {
                const haystack = card.getAttribute('data-search') || '';
                const match = !query || haystack.includes(query);
                card.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            if (cards.length > 0) {
                if (visibleCount === 0) {
                    emptyNotice.style.display = 'block';
                    emptyNotice.textContent = query
                        ? `Kelas dengan kata kunci "${searchInput.value}" tidak ditemukan.`
                        : 'Tidak ada kelas.';
                } else {
                    emptyNotice.style.display = 'none';
                }
            }
        }

        searchInput.addEventListener('input', applyFilter);
    });
</script>

@unless($isRekap)
    @include('learning.partials.join-class-dialog', [
        'joinAsDosen' => true,
        'joinDialogId' => 'join-class-modal',
        'joinInputId' => 'dosen-join-code',
    ])
@endunless
@endsection
