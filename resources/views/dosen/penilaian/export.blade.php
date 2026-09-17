@extends('layouts.mahasiswa')

@section('title', 'Export Rekap | SALE')
@section('header', 'Export Rekap')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header>
        <h2 class="section-heading">5. Export Data Penilaian</h2>
        <p class="mt-1 text-sm text-muted">Pilih jenis rekap data penilaian yang ingin diunduh dalam format CSV.</p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Rekap Nilai & CPMK --}}
        <div class="surface p-5 space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">Rekap Nilai &amp; CPMK</h3>
                <p class="text-xs text-muted mt-1">Nilai akhir, grade, predikat, dan capaian seluruh CPMK mata kuliah beserta bobotnya per mahasiswa.</p>
            </div>
            <a href="{{ route('dosen.penilaian.export.keseluruhan', $section->id) }}"
               class="button-primary text-xs w-full justify-center">
                Download CSV
            </a>
        </div>

        {{-- Rekap CPMK --}}
        <div class="surface p-5 space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">Rekap CPMK</h3>
                <p class="text-xs text-muted mt-1">Skor CPMK setiap mahasiswa berdasarkan asesmen yang mengukurnya.</p>
            </div>
            <a href="{{ route('dosen.penilaian.export.cpmk', $section->id) }}"
               class="button-primary text-xs w-full justify-center">
                Download CSV
            </a>
        </div>

        {{-- Rekap CPL --}}
        <div class="surface p-5 space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">Rekap CPL</h3>
                <p class="text-xs text-muted mt-1">Skor CPL setiap mahasiswa berdasarkan kontribusi CPMK.</p>
            </div>
            <a href="{{ route('dosen.penilaian.export.cpl', $section->id) }}"
               class="button-primary text-xs w-full justify-center">
                Download CSV
            </a>
        </div>

        {{-- Nilai per Asesmen --}}
        <div class="surface p-5 space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-ink">Nilai per Asesmen</h3>
                <p class="text-xs text-muted mt-1">Nilai setiap asesmen per mahasiswa beserta bobot masing-masing.</p>
            </div>
            <a href="{{ route('dosen.penilaian.export.nilai', $section->id) }}"
               class="button-primary text-xs w-full justify-center">
                Download CSV
            </a>
        </div>
    </div>

    <p class="text-xs text-muted">File CSV menggunakan encoding UTF-8 dengan BOM dan delimiter titik koma (;) agar kompatibel dengan Microsoft Excel.</p>
</div>
@endsection
