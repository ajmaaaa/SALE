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

    @if($sections->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada kelas yang diampu</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Kelas yang ditugaskan Admin Prodi atau Anda masuki melalui kode akan muncul di sini.
            </p>
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($sections as $section)
                @php
                    $progress = $section->grading_progress;
                    $currentUserId = auth()->id();
                    $isWakil = $section->dosen_pendamping_id == $currentUserId;
                @endphp
                <div class="surface p-6 border border-line/70 rounded-2xl flex flex-col justify-between hover:border-brand/40 hover:shadow-md transition-all duration-200 min-h-[250px] bg-white group">
                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold font-mono text-brand">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
                                    @if($isWakil)
                                        <span class="text-xs text-muted">(Dosen Wakil)</span>
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
                                    Pengampu: {{ $section->dosen->name }}@if($section->dosenPendamping), {{ $section->dosenPendamping->name }}@endif
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
                        @if($isRekap)
                            <a href="{{ route('dosen.penilaian.rekap', $section->id) }}" class="button-primary text-xs w-full py-2.5 text-center font-bold block shadow-xs hover:shadow transition">Rekap Nilai</a>
                        @else
                            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-primary text-xs w-full py-2.5 text-center font-bold block shadow-xs hover:shadow transition">
                                Kelola Penilaian
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@unless($isRekap)
    @include('learning.partials.join-class-dialog', [
        'joinAsDosen' => true,
        'joinDialogId' => 'join-class-modal',
        'joinInputId' => 'dosen-join-code',
    ])
@endunless
@endsection
