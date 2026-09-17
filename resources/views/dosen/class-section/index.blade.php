@extends('layouts.mahasiswa')

@php
    $isRekap = ($mode ?? 'penilaian') === 'rekap';
@endphp

@section('title', ($isRekap ? 'Rekap Nilai' : 'Daftar Kelas') . ' | SALE')
@section('header', $isRekap ? 'Rekap Nilai OBE' : 'Daftar Kelas Saya')

@section('content')
<div class="space-y-6">
    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.dashboard') }}" class="hover:text-brand">Dashboard</a>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $isRekap ? 'Rekap Nilai' : 'Penilaian OBE' }}</span>
        </nav>
        <h1 class="page-heading">{{ $isRekap ? 'Rekap Nilai OBE' : 'Daftar Kelas Saya' }}</h1>
        <p class="page-description">
            {{ $isRekap ? 'Pantau dan evaluasi rekapitulasi ketercapaian CPMK serta CPL mahasiswa untuk setiap kelas yang Anda ampu.' : 'Kelola penilaian berbasis OBE untuk setiap kelas yang Anda ampu pada semester berjalan.' }}
        </p>
    </header>

    @if($sections->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada kelas yang diampu</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Kelas yang ditugaskan kepada Anda oleh Admin Prodi akan muncul di sini beserta status penilaiannya.
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($sections as $section)
                @php
                    $progress = $section->grading_progress;
                    $statusLabel = match(true) {
                        $progress === null => 'Belum ada asesmen',
                        $progress >= 100 => 'Penilaian lengkap',
                        $progress > 0 => 'Sedang berjalan',
                        default => 'Belum dinilai',
                    };
                    $statusClasses = match(true) {
                        $progress === null => 'bg-canvas text-muted',
                        $progress >= 100 => 'bg-brand-soft text-brand',
                        $progress > 0 => 'bg-amber-50 text-amber-700',
                        default => 'bg-canvas text-muted',
                    };
                @endphp
                <div class="surface p-5 flex flex-col gap-4">
                    <div>
                        <p class="text-xs font-semibold text-brand">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</p>
                        <h2 class="mt-1 text-base font-semibold text-ink leading-snug">{{ $section->mataKuliah->name }}</h2>
                        <p class="mt-1 text-xs text-muted">{{ $section->semester->name }}</p>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <dt class="text-muted">Mahasiswa</dt>
                            <dd class="mt-0.5 font-semibold text-ink">{{ $section->students_count }} orang</dd>
                        </div>
                        <div>
                            <dt class="text-muted">Asesmen</dt>
                            <dd class="mt-0.5 font-semibold text-ink">{{ $section->assessments_count }} dibuat</dd>
                        </div>
                    </dl>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="status {{ $statusClasses }}">{{ $statusLabel }}</span>
                            @if($progress !== null)
                                <span class="font-semibold text-ink">{{ $progress }}%</span>
                            @endif
                        </div>
                        @if($progress !== null)
                            <div class="h-1.5 w-full rounded-full bg-canvas overflow-hidden" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full bg-brand" style="width: {{ $progress }}%"></div>
                            </div>
                        @endif
                    </div>

                    @if($isRekap)
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <a href="{{ route('dosen.penilaian.rekap', $section->id) }}" class="button-primary text-xs py-2 text-center font-semibold">
                                Rekap CPMK
                            </a>
                            <a href="{{ route('dosen.penilaian.cpl', $section->id) }}" class="button-secondary text-xs py-2 text-center font-semibold">
                                Rekap CPL
                            </a>
                        </div>
                    @else
                        <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-primary text-xs mt-1 text-center font-semibold">
                            Kelola Penilaian
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
