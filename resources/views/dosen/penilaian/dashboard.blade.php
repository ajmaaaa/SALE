@extends('layouts.mahasiswa')

@section('title', 'Dashboard Penilaian | SALE')
@section('header', 'Dashboard Penilaian Kelas')

@section('content')
<div class="space-y-6">
    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.index') }}" class="hover:text-brand">Daftar Kelas</a>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
        </nav>
        <h1 class="page-heading">{{ $section->mataKuliah->name }}</h1>
        <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->semester->name }} · Diampu oleh {{ $section->dosen->name }}</p>
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
            <p class="mt-1 text-xl font-semibold text-ink">—</p>
        </div>
        <div>
            <p class="text-xs text-muted">CPL Digunakan</p>
            <p class="mt-1 text-xl font-semibold text-ink">—</p>
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
