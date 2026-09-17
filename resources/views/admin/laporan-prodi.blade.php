@extends('layouts.mahasiswa')
@section('header', 'Laporan Program Studi')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Navigation Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin.page', 'laporan') }}" class="hover:text-brand transition">Laporan &amp; Rekapitulasi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Rincian Program Studi</span>
            </div>
            <h1 class="page-heading">Rincian Data Program Studi</h1>
            <p class="page-description">Informasi kurikulum, pimpinan prodi, mata kuliah, dan ketercapaian akademik.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.page', 'laporan') }}" class="button-secondary inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Laporan
            </a>
            <a href="{{ route('admin.export') }}" class="button-primary">Unduh rekap CSV</a>
        </div>
    </div>

    {{-- Main Program Studi Detail Card --}}
    <section class="surface p-6">
        <div class="flex flex-wrap items-center justify-between pb-4 mb-6 border-b border-line/60 gap-3">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg">
                    IF
                </div>
                <div>
                    <h2 class="text-lg font-bold text-ink">Teknik Informatika (S1)</h2>
                    <p class="text-xs text-muted">Fakultas Ilmu Komputer · Akreditasi Unggul</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Status: Aktif
                </span>
            </div>
        </div>

        {{-- 7 Field Utama Sesuai Permintaan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Nama Prodi --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Nama Prodi</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Teknik Informatika (S1)</span>
                <p class="text-xs text-muted mt-1">Program Sarjana Teknik Informatika</p>
            </div>

            {{-- 2. Kaprodi --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Kaprodi</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Dr. H. Kaprodi, M.T.</span>
                <p class="text-xs text-muted mt-1">Ketua Program Studi</p>
            </div>

            {{-- 3. Wakil --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Wakil</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Dr. Budi Santoso, M.Kom.</span>
                <p class="text-xs text-muted mt-1">Wakil Ketua Program Studi / Team-Teaching</p>
            </div>

            {{-- 4. Semester --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Semester</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Semester Ganjil 2026/2027</span>
                <p class="text-xs text-muted mt-1">Tahun Akademik Berjalan</p>
            </div>

            {{-- 5. Mata Kuliah --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Mata Kuliah</span>
                <span class="text-base font-bold text-brand mt-1.5 block">{{ count($mataKuliahList) }} Mata Kuliah Aktif</span>
                <p class="text-xs text-muted mt-1">Kurikulum berbasis OBE</p>
            </div>

            {{-- 6. Jumlah Mahasiswa --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                <span class="text-base font-bold text-brand mt-1.5 block">85 Mahasiswa</span>
                <p class="text-xs text-muted mt-1">Mahasiswa aktif terdaftar semester ini</p>
            </div>

            {{-- 7. IPK Rata-Rata --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50 md:col-span-2 lg:col-span-2">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">IPK Rata-Rata</span>
                <div class="flex items-baseline gap-3 mt-1.5">
                    <span class="text-2xl font-black text-emerald-600">3,58</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-100/70 text-emerald-800">Sangat Memuaskan / Cumlaude Target</span>
                </div>
                <p class="text-xs text-muted mt-1">Rata-rata kumulatif seluruh mahasiswa terdaftar di program studi</p>
            </div>
        </div>
    </section>

    {{-- Daftar Mata Kuliah Semester Berjalan --}}
    <section class="surface p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-ink">Daftar Mata Kuliah Semester Ini</h3>
                <p class="text-xs text-muted">Mata kuliah aktif pada Semester Ganjil 2026/2027 Program Studi Teknik Informatika</p>
            </div>
        </div>

        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th>Kode MK</th>
                    <th>Nama Mata Kuliah</th>
                    <th>Bobot SKS</th>
                    <th>Semester</th>
                    <th>Kelas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mataKuliahList as $mk)
                    <tr class="hover:bg-canvas/50 transition">
                        <td class="font-bold text-ink">{{ $mk['code'] }}</td>
                        <td class="font-semibold text-ink">{{ $mk['name'] }}</td>
                        <td>{{ $mk['sks'] }} SKS</td>
                        <td>{{ $mk['semester'] }}</td>
                        <td class="font-medium text-brand">{{ $mk['kelas'] }}</td>
                        <td>
                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                Aktif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>
@endsection
