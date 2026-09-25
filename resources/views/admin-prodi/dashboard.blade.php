@extends('layouts.mahasiswa')

@section('title', 'Dashboard Admin Prodi | SALE')
@section('header', 'Dashboard Admin Program Studi')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <span>Administrasi</span>
                <span>/</span>
                <span class="text-ink font-semibold">Admin Prodi</span>
            </nav>
            <h1 class="page-heading">Tata Kelola Akademik &amp; Kurikulum Prodi</h1>
            <p class="page-description">Kelola kurikulum OBE (CPL &amp; CPMK), penugasan Dosen Ketua &amp; Wakil kelas, input mahasiswa, serta laporan semesteran.</p>
        </div>
        <div class="flex flex-wrap gap-2.5 shrink-0">
            <a href="{{ route('admin-prodi.kurikulum.index') }}" class="button-secondary text-xs">Kelola Kurikulum OBE</a>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-primary text-xs">+ Buat Kelas Baru</a>
        </div>
    </header>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Mata Kuliah & Kelas -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Mata Kuliah &amp; Kelas</p>
                <div class="mt-3 grid grid-cols-2 gap-3 divide-x divide-line/60">
                    <div>
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_matakuliah'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Mata Kuliah</span>
                    </div>
                    <div class="pl-3 sm:pl-4">
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_kelas'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Kelas</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Daftar Kelas &amp; Barcode
                </a>
            </div>
        </div>

        <!-- Dosen & Mahasiswa -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Dosen &amp; Mahasiswa</p>
                <div class="mt-3 grid grid-cols-2 gap-3 divide-x divide-line/60">
                    <div>
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_dosen'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Dosen</span>
                    </div>
                    <div class="pl-3 sm:pl-4">
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_mahasiswa'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Mahasiswa</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.users.index') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Impor / Input Data
                </a>
            </div>
        </div>

        <!-- Standar Mutu OBE -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Standar Mutu OBE</p>
                <div class="mt-3 grid grid-cols-2 gap-3 divide-x divide-line/60">
                    <div>
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_cpl'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">CPL</span>
                    </div>
                    <div class="pl-3 sm:pl-4">
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_cpmk'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">CPMK</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.kurikulum.index') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Matriks Pemetaan
                </a>
            </div>
        </div>
    </div>

    <!-- Kelas Aktif Terbaru -->
    <div class="surface p-5 border border-line/60">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-ink">Kelas Perkuliahan Aktif Terbaru</h2>
                <p class="text-xs text-muted">Daftar seksi kelas dengan penetapan Dosen Ketua &amp; Dosen Wakil</p>
            </div>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="text-xs font-semibold text-brand hover:underline">Lihat Semua Kelas</a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-32 !align-middle">Kode / Kelas</th>
                        <th class="px-4 py-3.5 !align-middle">Mata Kuliah</th>
                        <th class="px-4 py-3.5 w-48 !align-middle">Dosen Ketua (Koordinator)</th>
                        <th class="px-4 py-3.5 w-44 !align-middle">Dosen Wakil (Pendamping)</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">Kode Masuk</th>
                        <th class="px-4 py-3.5 text-center w-28 !align-middle">Mahasiswa</th>
                        <th class="px-4 py-3.5 text-right w-28 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($recentClasses as $rc)
                    <tr class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 font-bold text-brand font-mono !align-middle whitespace-nowrap">{{ $rc->display_code }}</td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $rc->mataKuliah->name }}</span>
                            <span class="block text-[11px] text-muted mt-0.5">{{ $rc->mataKuliah->prodi->name ?? '-' }} ({{ $rc->mataKuliah->sks }} SKS)</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-medium text-ink block">{{ $rc->dosen?->name ?? 'Belum ditentukan' }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            @if($rc->dosenPendamping)
                                <span class="font-medium text-ink block">{{ $rc->dosenPendamping->name }}</span>
                            @else
                                <span class="text-muted italic text-[11px]">— Tidak ada —</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <span class="font-mono font-bold tracking-wider text-ink text-xs">{{ $rc->enrollment_code }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-center font-bold text-ink !align-middle whitespace-nowrap">
                            {{ $rc->students_count }} <span class="font-normal text-muted text-[11px]">/ {{ $rc->capacity ?? '∞' }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-secondary text-[11px] py-1 px-2.5">Kelola</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-muted !align-middle">
                            <p class="text-xs">Belum ada kelas yang dibuat. Buat kelas di menu Kelas &amp; Dosen Pengampu.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
