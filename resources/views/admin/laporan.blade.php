@extends('layouts.mahasiswa')
@section('header', 'Laporan')

@section('content')
<header class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="page-heading">Laporan &amp; rekapitulasi</h1>
        <p class="page-description">Rekap data akademik yang tersedia pada pratinjau.</p>
    </div>
    <a class="button-primary" href="{{ route('admin.export') }}">Unduh rekap CSV</a>
</header>

@php
    $fakultasRecords = array_values(array_filter($academic, fn ($a) => ($a['type'] ?? '') === 'fakultas'));
    $prodiRecords = array_values(array_filter($academic, fn ($a) => ($a['type'] ?? '') === 'prodi'));
@endphp

<section class="surface mt-7 overflow-x-auto">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Kelompok data</th>
                <th>Jumlah</th>
                <th>Aktif</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            {{-- 1. Baris Fakultas: Diklik langsung menuju halaman rincian fakultas --}}
            <tr onclick="window.location.href='{{ route('admin.laporan.fakultas') }}'"
                tabindex="0"
                role="button"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ route('admin.laporan.fakultas') }}';}"
                class="hover:bg-canvas/60 cursor-pointer transition select-none group border-b border-line/40">
                <td>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.laporan.fakultas') }}" class="font-semibold text-ink group-hover:text-brand transition">
                            Fakultas
                        </a>
                        <span class="text-[11px] text-muted bg-slate-100 group-hover:bg-brand-soft/60 px-2 py-0.5 rounded transition">
                            Klik untuk buka halaman rincian
                        </span>
                    </div>
                </td>
                <td class="font-medium text-ink">{{ count($fakultasRecords) }}</td>
                <td class="font-medium text-emerald-700">{{ count(array_filter($fakultasRecords, fn ($a) => ($a['status'] ?? '') === 'aktif')) }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.laporan.fakultas') }}" class="button-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5 group-hover:border-brand group-hover:text-brand transition">
                        <span>Buka Rincian</span>
                        <svg class="h-3.5 w-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </td>
            </tr>

            {{-- 2. Baris Program Studi: Diklik langsung menuju halaman rincian program studi --}}
            <tr onclick="window.location.href='{{ route('admin.laporan.prodi') }}'"
                tabindex="0"
                role="button"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ route('admin.laporan.prodi') }}';}"
                class="hover:bg-canvas/60 cursor-pointer transition select-none group border-b border-line/40">
                <td>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.laporan.prodi') }}" class="font-semibold text-ink group-hover:text-brand transition">
                            Program studi
                        </a>
                        <span class="text-[11px] text-muted bg-slate-100 group-hover:bg-brand-soft/60 px-2 py-0.5 rounded transition">
                            Klik untuk buka halaman rincian
                        </span>
                    </div>
                </td>
                <td class="font-medium text-ink">{{ count($prodiRecords) }}</td>
                <td class="font-medium text-emerald-700">{{ count(array_filter($prodiRecords, fn ($a) => ($a['status'] ?? '') === 'aktif')) }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.laporan.prodi') }}" class="button-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5 group-hover:border-brand group-hover:text-brand transition">
                        <span>Buka Rincian</span>
                        <svg class="h-3.5 w-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<p class="mt-5 text-sm text-muted">Laporan nilai, CPMK/CPL, dan akreditasi membutuhkan data penilaian institusi yang sudah diverifikasi.</p>
@endsection
