@extends('layouts.mahasiswa')

@php
    $isRekap = ($mode ?? 'penilaian') === 'rekap';
@endphp

@section('title', ($isRekap ? 'Rekap Nilai' : 'Penilaian') . ' | SALE')
@section('header', $isRekap ? 'Rekap Nilai OBE' : 'Penilaian')

@section('content')
<div class="space-y-6">
    <header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-heading">{{ $isRekap ? 'Rekap Nilai OBE' : 'Penilaian' }}</h1>
                <p class="page-description">
                    {{ $isRekap ? 'Pantau dan evaluasi ketercapaian CPMK serta CPL mahasiswa. Anda dapat meninjau rekapan CPMK per Course secara komprehensif.' : 'Kelola penilaian berbasis OBE untuk setiap kelas yang Anda ampu pada semester berjalan.' }}
                </p>
            </div>
            @if($isRekap)
                <a href="{{ route('dosen.gradebook') }}" class="button-primary text-xs py-2 px-3.5 font-semibold shrink-0">
                    Rekapan CPMK per Course
                </a>
            @endif
        </div>
    </header>

    @if($sections->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada kelas yang diampu</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Kelas yang ditugaskan kepada Anda oleh Admin Prodi akan muncul di sini beserta status penilaiannya.
            </p>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($sections as $section)
                @php
                    $progress = $section->grading_progress;
                    $currentUserId = auth()->id() ?? (session('auth_user.id') ?? null);
                    $isWakil = $section->dosen_pendamping_id == $currentUserId;
                @endphp
                <div class="surface p-5 border border-line/60 rounded-xl flex flex-col justify-between hover:border-ink/20 transition">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-brand">
                                        {{ $section->mataKuliah->code }}-{{ $section->section_code }}
                                    </span>
                                    @if($isWakil)
                                        <span class="text-xs text-muted">(Dosen Wakil)</span>
                                    @endif
                                </div>
                                <h2 class="text-base font-semibold text-ink leading-snug">
                                    {{ $section->mataKuliah->name }}
                                </h2>
                                <p class="text-xs text-muted">{{ $section->semester->name }}</p>
                            </div>

                            {{-- Circular Progress Ring (Tanpa teks label keterangan, tidak monoton) --}}
                            @if($progress !== null)
                                <div class="relative flex items-center justify-center w-11 h-11 shrink-0" title="{{ $progress }}%">
                                    <svg class="w-11 h-11 -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-line/60" stroke-width="2.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path class="text-brand" stroke-width="2.5" stroke-dasharray="{{ $progress }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <span class="absolute text-xs font-semibold text-ink font-mono">{{ $progress }}%</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-line/40 flex items-center gap-4 text-xs text-muted">
                            <span><strong class="font-medium text-ink">{{ $section->students_count }}</strong> Mahasiswa</span>
                            <span class="h-3 w-px bg-line"></span>
                            <span><strong class="font-medium text-ink">{{ $section->assessments_count }}</strong> Asesmen</span>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-t border-line/40">
                        @if($isRekap)
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('dosen.penilaian.rekap', $section->id) }}" class="button-primary text-xs py-2 text-center font-semibold">
                                    Rekap CPMK
                                </a>
                                <a href="{{ route('dosen.penilaian.cpl', $section->id) }}" class="button-secondary text-xs py-2 text-center font-semibold">
                                    Rekap CPL
                                </a>
                            </div>
                        @else
                            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-primary text-xs w-full py-2 text-center font-semibold">
                                Kelola Penilaian
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
