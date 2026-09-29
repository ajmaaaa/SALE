@extends('layouts.mahasiswa')

@section('title', 'Dashboard Penilaian | SALE')
@section('header', 'Dashboard Penilaian Kelas')

@section('content')
<div class="space-y-6">
    <header>
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
            <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('dosen.penilaian.index') }}">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                <span>Penilaian</span>
            </a>
            <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-semibold text-slate-800" aria-current="page">
                {{ $section->mataKuliah->code }}-{{ $section->section_code }}
            </span>
        </nav>
        <h1 class="page-heading">{{ $section->mataKuliah->name }}</h1>
        <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }}, {{ $section->semester->name }}, Diampu oleh {{ $section->dosen->name }}</p>
    </header>

    <div class="surface p-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
            <p class="text-xs text-muted">Jumlah Mahasiswa</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $section->students_count }}</p>
        </div>
        <div>
            <p class="text-xs text-muted">Jumlah Asesmen</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $section->assessments_count }}</p>
        </div>
        <div>
            <p class="text-xs text-muted">CPMK Digunakan</p>
            <p class="mt-1 text-xl font-semibold text-ink">0</p>
        </div>
        <div>
            <p class="text-xs text-muted">CPL Digunakan</p>
            <p class="mt-1 text-xl font-semibold text-ink">0</p>
        </div>
    </div>

    <div class="surface p-8 text-center">
        <h2 class="section-heading">Tab Rekap, Asesmen, CPMK, CPL, dan Pengaturan Penilaian</h2>
        <p class="mt-2 text-sm text-muted max-w-lg mx-auto">
            Halaman ini akan dilengkapi bertahap: Matriks Penilaian dan Daftar Asesmen berikutnya, lalu Rekap CPMK, Rekap CPL, dan Rekap Keseluruhan.
        </p>
    </div>
</div>
@endsection
