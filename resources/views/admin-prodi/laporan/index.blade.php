@extends('layouts.mahasiswa')

@section('title', 'Laporan Semester Program Studi | SALE')
@section('header', 'Laporan Akademik & Capaian Semester')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin-prodi.dashboard') }}" class="hover:text-brand">Admin Prodi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Laporan Semester</span>
            </nav>
            <h1 class="page-heading">Laporan Akademik &amp; Capaian Nilai Prodi</h1>
            <p class="page-description">Laporan metrik spesifik per prodi per semester: jumlah dosen/mahasiswa, intake mahasiswa baru, rata-rata nilai, dan ekspor data.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin-prodi.laporan.print', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" target="_blank" class="button-secondary text-xs flex items-center gap-1.5">
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                Pratinjau Cetak / PDF
            </a>
            <a href="{{ route('admin-prodi.laporan.export', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="button-primary text-xs flex items-center gap-1.5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Ekspor Laporan (Excel / CSV)
            </a>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
        <form id="laporanFilterForm" method="GET" class="flex flex-wrap items-center gap-3">
            <label for="rep_prodi" class="text-xs font-semibold text-muted">Program Studi:</label>
            <select name="prodi_id" id="rep_prodi" onchange="this.form.submit()" class="field text-xs font-semibold w-56">
                @foreach($prodis as $p)
                    <option value="{{ $p->id }}" {{ $activeProdi && $activeProdi->id === $p->id ? 'selected' : '' }}>
                        {{ $p->code }} - {{ $p->name }}
                    </option>
                @endforeach
            </select>

            <label for="rep_semester" class="text-xs font-semibold text-muted">Semester:</label>
            <select name="semester_id" id="rep_semester" onchange="this.form.submit()" class="field text-xs font-semibold w-52">
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $activeSemester && $activeSemester->id === $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="text-xs text-muted">
            Periode: <strong class="text-ink">{{ $activeSemester?->name }}</strong>, Prodi: <strong class="text-ink">{{ $activeProdi?->name }}</strong>
        </div>
    </div>

    <!-- Metrik Spesifik Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">Jumlah Mahasiswa</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $metrics['total_mahasiswa'] }} <span class="text-xs font-normal text-muted">Orang</span></p>
            <p class="mt-1 text-[11px] text-muted">Total mahasiswa di {{ $activeProdi?->code }}</p>
        </div>

        <div class="surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">Jumlah Dosen</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $metrics['total_dosen'] }} <span class="text-xs font-normal text-muted">Dosen</span></p>
            <p class="mt-1 text-[11px] text-muted">Dosen homebase &amp; pengampu</p>
        </div>

        <div class="surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">Mahasiswa Baru (Intake)</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $metrics['mahasiswa_baru'] }} <span class="text-xs font-normal text-muted">Orang</span></p>
            <p class="mt-1 text-[11px] text-muted">Masuk pada semester ini</p>
        </div>

        <div class="surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">Rata-rata Nilai Semester</p>
            @if($metrics['average_grade'] !== null)
                <p class="mt-1.5 text-2xl font-bold text-ink">{{ number_format($metrics['average_grade'], 2) }} <span class="text-xs font-normal text-muted">/ 100</span></p>
                <p class="mt-1 text-[11px] text-muted">Evaluasi capaian akhir</p>
            @else
                <p class="mt-1.5 text-lg font-bold text-muted">—</p>
                <p class="mt-1 text-[11px] text-muted">Belum ada nilai diinput</p>
            @endif
        </div>

        <div class="surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">Total Kelas Aktif</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $metrics['total_kelas'] }} <span class="text-xs font-normal text-muted">Kelas</span></p>
            <p class="mt-1 text-[11px] text-muted">Seksi perkuliahan dibuka</p>
        </div>
    </div>

    <!-- Tabel Rincian Kelas & Nilai Semester -->
    <div class="surface p-5 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-ink">Rincian Kelas Perkuliahan &amp; Rata-rata Nilai</h2>
                <p class="text-xs text-muted">Evaluasi ketercapaian nilai dan penugasan Dosen Ketua / Wakil di semester {{ $activeSemester?->name }}</p>
            </div>
            <a href="{{ route('admin-prodi.laporan.export', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="text-xs font-semibold text-brand hover:underline">
                Unduh Data CSV
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-12 text-center !align-middle">No</th>
                        <th class="px-4 py-3.5 w-32 !align-middle">Kelas / Seksi</th>
                        <th class="px-4 py-3.5 !align-middle">Mata Kuliah &amp; SKS</th>
                        <th class="px-4 py-3.5 w-48 !align-middle">Dosen Ketua</th>
                        <th class="px-4 py-3.5 w-40 !align-middle">Dosen Wakil</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">Mahasiswa Terdaftar</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">Jumlah Asesmen</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">Rata-rata Nilai Kelas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($classReports as $idx => $cr)
                    <tr class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 text-center text-muted font-medium !align-middle">{{ $idx + 1 }}</td>
                        <td class="px-4 py-3.5 font-bold text-brand font-mono !align-middle whitespace-nowrap">
                            {{ $cr['mk_code'] }}-{{ $cr['section_code'] }}
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $cr['mk_name'] }}</span>
                            <span class="text-[11px] text-muted block mt-0.5">{{ $cr['sks'] }} SKS</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $cr['dosen_ketua'] }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            @if($cr['dosen_wakil'] !== '-')
                                <span class="font-medium text-ink block">{{ $cr['dosen_wakil'] }}</span>
                            @else
                                <span class="text-muted italic text-[11px]">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center font-bold text-ink !align-middle whitespace-nowrap">
                            {{ $cr['students_count'] }} <span class="font-normal text-muted">orang</span>
                        </td>
                        <td class="px-4 py-3.5 text-center font-bold text-ink !align-middle whitespace-nowrap">
                            {{ $cr['assessments_count'] }} <span class="font-normal text-muted">asesmen</span>
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            @if($cr['class_average'] !== null)
                                <span class="font-bold text-ink text-sm">
                                    {{ number_format($cr['class_average'], 2) }}
                                </span>
                            @else
                                <span class="text-muted italic text-[11px]">Belum dinilai</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs">Tidak ada data kelas pada semester dan program studi yang dipilih.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
