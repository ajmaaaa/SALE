@extends('layouts.mahasiswa')
@section('header', 'Laporan')

@section('content')
<header class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="page-heading">Laporan &amp; rekapitulasi</h1>
        <p class="page-description">Rekap data akademik yang tersedia pada pratinjau.</p>
    </div>
</header>

@php
    $facultyData = \App\Support\AdminPreview::facultyProdiData();
    $totalFaculties = count($facultyData);
    $totalProdis = array_sum(array_map(fn ($f) => count($f['prodis']), $facultyData));
@endphp

<section class="surface mt-7 overflow-x-auto">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Kelompok data</th>
                <th>Jumlah</th>
                <th>Aktif</th>
            </tr>
        </thead>
        <tbody>
            {{-- 1. Baris Fakultas: Diklik biasa langsung membuka halaman rincian fakultas --}}
            <tr onclick="window.location.href='{{ route('admin.laporan.fakultas') }}'"
                tabindex="0"
                role="button"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ route('admin.laporan.fakultas') }}';}"
                class="hover:bg-canvas/60 cursor-pointer transition select-none group border-b border-line/40">
                <td class="font-semibold text-ink group-hover:text-brand transition">
                    Fakultas
                </td>
                <td class="font-medium text-ink">{{ $totalFaculties }}</td>
                <td class="font-medium text-emerald-700">{{ $totalFaculties }}</td>
            </tr>

            {{-- 2. Baris Program Studi: Diklik biasa langsung membuka halaman rincian program studi --}}
            <tr onclick="window.location.href='{{ route('admin.laporan.prodi') }}'"
                tabindex="0"
                role="button"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ route('admin.laporan.prodi') }}';}"
                class="hover:bg-canvas/60 cursor-pointer transition select-none group border-b border-line/40">
                <td class="font-semibold text-ink group-hover:text-brand transition">
                    Program studi
                </td>
                <td class="font-medium text-ink">{{ $totalProdis }}</td>
                <td class="font-medium text-emerald-700">{{ $totalProdis }}</td>
            </tr>
        </tbody>
    </table>
</section>

<p class="mt-5 text-sm text-muted">Laporan nilai, CPMK/CPL, dan akreditasi membutuhkan data penilaian institusi yang sudah diverifikasi.</p>
@endsection
