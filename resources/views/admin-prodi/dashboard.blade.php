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
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Mata Kuliah &amp; Kelas</p>
            <p class="mt-1.5 text-2xl font-bold text-brand">{{ $stats['total_matakuliah'] }} <span class="text-xs font-normal text-muted">MK</span> &middot; {{ $stats['total_kelas'] }} <span class="text-xs font-normal text-muted">Kelas</span></p>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="mt-3 inline-block text-xs font-semibold text-brand hover:underline">Daftar kelas &amp; barcode &rarr;</a>
        </div>
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Dosen &amp; Mahasiswa</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $stats['total_dosen'] }} <span class="text-xs font-normal text-muted">Dosen</span> &middot; {{ $stats['total_mahasiswa'] }} <span class="text-xs font-normal text-muted">Mhs</span></p>
            <a href="{{ route('admin-prodi.users.index') }}" class="mt-3 inline-block text-xs font-semibold text-brand hover:underline">Impor / input data &rarr;</a>
        </div>
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Standar Mutu OBE</p>
            <p class="mt-1.5 text-2xl font-bold text-emerald-600">{{ $stats['total_cpl'] }} <span class="text-xs font-normal text-muted">CPL</span> &middot; {{ $stats['total_cpmk'] }} <span class="text-xs font-normal text-muted">CPMK</span></p>
            <a href="{{ route('admin-prodi.kurikulum.index') }}" class="mt-3 inline-block text-xs font-semibold text-emerald-700 hover:underline">Matriks pemetaan &rarr;</a>
        </div>
    </div>

    <!-- Quick Navigation Modules -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('admin-prodi.kurikulum.index') }}" class="surface p-5 hover:border-brand transition-all flex flex-col justify-between group">
            <div>
                <div class="h-10 w-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg mb-3">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                </div>
                <h3 class="text-base font-bold text-ink group-hover:text-brand">1. Kurikulum CPL &amp; CPMK</h3>
                <p class="mt-1 text-xs text-muted leading-relaxed">
                    Tetapkan butir Capaian Pembelajaran Lulusan (CPL) dan CPMK per mata kuliah secara terpusat agar dosen pengampu tinggal memilih saat membuat asesmen tugas/kuis/ujian.
                </p>
            </div>
            <span class="mt-4 text-xs font-bold text-brand flex items-center gap-1">Buka Kurikulum &rarr;</span>
        </a>

        <a href="{{ route('admin-prodi.akademik.kelas') }}" class="surface p-5 hover:border-brand transition-all flex flex-col justify-between group">
            <div>
                <div class="h-10 w-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg mb-3">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3 class="text-base font-bold text-ink group-hover:text-brand">2. Kelas, Dosen Ketua/Wakil &amp; Barcode</h3>
                <p class="mt-1 text-xs text-muted leading-relaxed">
                    Bentuk kelas perkuliahan, tetapkan Dosen Ketua (Koordinator) dan Dosen Wakil (Pendamping), serta dapatkan Kode Unik Masuk &amp; Barcode/QR Code untuk mahasiswa join kelas.
                </p>
            </div>
            <span class="mt-4 text-xs font-bold text-brand flex items-center gap-1">Buka Kelas Perkuliahan &rarr;</span>
        </a>

        <a href="{{ route('admin-prodi.laporan.index') }}" class="surface p-5 hover:border-brand transition-all flex flex-col justify-between group">
            <div>
                <div class="h-10 w-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg mb-3">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <h3 class="text-base font-bold text-ink group-hover:text-brand">3. Laporan Semester &amp; Ekspor</h3>
                <p class="mt-1 text-xs text-muted leading-relaxed">
                    Pantau metrik spesifik prodi: jumlah dosen/mahasiswa, intake mahasiswa baru per semester, rata-rata nilai hasil evaluasi kelas, dan ekspor ke Excel/CSV siap cetak.
                </p>
            </div>
            <span class="mt-4 text-xs font-bold text-brand flex items-center gap-1">Buka Laporan Prodi &rarr;</span>
        </a>
    </div>

    <!-- Kelas Aktif Terbaru -->
    <div class="surface p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-ink">Kelas Perkuliahan Aktif Terbaru</h2>
                <p class="text-xs text-muted">Daftar seksi kelas dengan penetapan Dosen Ketua &amp; Dosen Wakil</p>
            </div>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="text-xs font-semibold text-brand hover:underline">Lihat Semua Kelas &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="p-3">Kode / Kelas</th>
                        <th class="p-3">Mata Kuliah</th>
                        <th class="p-3">Dosen Ketua (Koordinator)</th>
                        <th class="p-3">Dosen Wakil (Pendamping)</th>
                        <th class="p-3 text-center">Kode Masuk</th>
                        <th class="p-3 text-center">Mahasiswa</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($recentClasses as $rc)
                    <tr class="hover:bg-canvas/30">
                        <td class="p-3 font-bold text-brand">{{ $rc->display_code }}</td>
                        <td class="p-3">
                            <span class="font-semibold text-ink">{{ $rc->mataKuliah->name }}</span>
                            <span class="block text-[11px] text-muted">{{ $rc->mataKuliah->prodi->name ?? '-' }} &middot; {{ $rc->mataKuliah->sks }} SKS</span>
                        </td>
                        <td class="p-3">
                            <span class="font-medium text-ink">{{ $rc->dosen?->name ?? 'Belum ditentukan' }}</span>
                            <span class="block text-[10px] text-blue-700 font-semibold uppercase">Dosen Ketua</span>
                        </td>
                        <td class="p-3">
                            @if($rc->dosenPendamping)
                                <span class="font-medium text-ink">{{ $rc->dosenPendamping->name }}</span>
                                <span class="block text-[10px] text-purple-700 font-semibold uppercase">Dosen Wakil</span>
                            @else
                                <span class="text-muted italic">— Tidak ada —</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <code class="px-2 py-0.5 rounded bg-slate-100 font-mono font-bold text-ink border border-line">{{ $rc->enrollment_code }}</code>
                        </td>
                        <td class="p-3 text-center font-bold text-ink">
                            {{ $rc->students_count }} <span class="font-normal text-muted">/ {{ $rc->capacity ?? '∞' }}</span>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-secondary text-[11px] py-1 px-2.5">Kelola</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-muted">Belum ada kelas yang dibuat. Buat kelas di menu Kelas &amp; Dosen Pengampu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
