@extends('layouts.mahasiswa')
@section('header', 'Laporan')

@section('content')
<header class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="page-heading">Laporan & rekapitulasi</h1>
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
            </tr>
        </thead>
        <tbody>
            {{-- 1. Baris Fakultas (Dapat Diklik untuk melihat rincian) --}}
            <tr onclick="toggleAcademicDetail('fakultas')"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleAcademicDetail('fakultas');}"
                tabindex="0"
                role="button"
                aria-expanded="false"
                aria-controls="detail-fakultas"
                class="hover:bg-canvas/60 cursor-pointer transition select-none group border-b border-line/40">
                <td>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-ink group-hover:text-brand transition">Fakultas</span>
                            <span class="text-[11px] text-muted bg-slate-100 group-hover:bg-brand-soft/60 px-2 py-0.5 rounded transition">Klik untuk rincian</span>
                        </div>
                        <svg id="arrow-fakultas" class="h-4 w-4 text-muted group-hover:text-brand transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </td>
                <td class="font-medium text-ink">{{ count($fakultasRecords) }}</td>
                <td class="font-medium text-emerald-700">{{ count(array_filter($fakultasRecords, fn ($a) => ($a['status'] ?? '') === 'aktif')) }}</td>
            </tr>

            {{-- Panel Detail Fakultas --}}
            <tr id="detail-fakultas" hidden class="bg-slate-50/70 border-b border-line/60">
                <td colspan="3" class="p-4 sm:p-5">
                    @forelse($fakultasRecords as $fakultas)
                        <div class="bg-white rounded-xl border border-line/60 p-4 shadow-xs {{ !$loop->first ? 'mt-3' : '' }}">
                            <div class="flex items-center justify-between pb-3 mb-3 border-b border-line/40">
                                <span class="text-xs font-bold text-ink uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-brand"></span>
                                    Data Fakultas: {{ $fakultas['name'] }} ({{ $fakultas['code'] }})
                                </span>
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full {{ ($fakultas['status'] ?? 'aktif') === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($fakultas['status'] ?? 'aktif') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nama Fakultas</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">{{ $fakultas['name'] }}</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Jumlah Prodi</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">
                                        {{ count(array_filter($prodiRecords, fn ($p) => ($p['parent'] ?? null) == $fakultas['id'])) ?: count($prodiRecords) }} Program Studi
                                    </span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Dekan</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Prof. Dr. Ir. H. M. Zain, M.Kom.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Wakil</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Dr. Eng. Rina Marlina, M.Kom.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                                    <span class="text-sm font-bold text-brand mt-1 block">120 Mahasiswa</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border border-line/60 p-4 shadow-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nama Fakultas</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Fakultas Ilmu Komputer</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Jumlah Prodi</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">1 Program Studi</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Dekan</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Prof. Dr. Ir. H. M. Zain, M.Kom.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Wakil</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Dr. Eng. Rina Marlina, M.Kom.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                                    <span class="text-sm font-bold text-brand mt-1 block">120 Mahasiswa</span>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </td>
            </tr>

            {{-- 2. Baris Program Studi (Dapat Diklik untuk melihat rincian) --}}
            <tr onclick="toggleAcademicDetail('prodi')"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleAcademicDetail('prodi');}"
                tabindex="0"
                role="button"
                aria-expanded="false"
                aria-controls="detail-prodi"
                class="hover:bg-canvas/60 cursor-pointer transition select-none group border-b border-line/40">
                <td>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-ink group-hover:text-brand transition">Program studi</span>
                            <span class="text-[11px] text-muted bg-slate-100 group-hover:bg-brand-soft/60 px-2 py-0.5 rounded transition">Klik untuk rincian</span>
                        </div>
                        <svg id="arrow-prodi" class="h-4 w-4 text-muted group-hover:text-brand transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </td>
                <td class="font-medium text-ink">{{ count($prodiRecords) }}</td>
                <td class="font-medium text-emerald-700">{{ count(array_filter($prodiRecords, fn ($a) => ($a['status'] ?? '') === 'aktif')) }}</td>
            </tr>

            {{-- Panel Detail Program Studi --}}
            <tr id="detail-prodi" hidden class="bg-slate-50/70 border-b border-line/60">
                <td colspan="3" class="p-4 sm:p-5">
                    @forelse($prodiRecords as $prodi)
                        <div class="bg-white rounded-xl border border-line/60 p-4 shadow-xs {{ !$loop->first ? 'mt-3' : '' }}">
                            <div class="flex items-center justify-between pb-3 mb-3 border-b border-line/40">
                                <span class="text-xs font-bold text-ink uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Data Program Studi: {{ $prodi['name'] }} ({{ $prodi['code'] }})
                                </span>
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full {{ ($prodi['status'] ?? 'aktif') === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($prodi['status'] ?? 'aktif') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nama Prodi</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">{{ $prodi['name'] }}</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Kaprodi</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Dr. H. Kaprodi, M.T.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Wakil</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Dr. Budi Santoso, M.Kom.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                                    <span class="text-sm font-bold text-brand mt-1 block">85 Mahasiswa</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">IPK Rata-Rata</span>
                                    <span class="text-sm font-bold text-emerald-600 mt-1 block">3,58</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border border-line/60 p-4 shadow-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nama Prodi</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Teknik Informatika (S1)</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Kaprodi</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Dr. H. Kaprodi, M.T.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Wakil</span>
                                    <span class="text-sm font-bold text-ink mt-1 block">Dr. Budi Santoso, M.Kom.</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                                    <span class="text-sm font-bold text-brand mt-1 block">85 Mahasiswa</span>
                                </div>
                                <div class="p-3 bg-slate-50/80 rounded-lg border border-line/40">
                                    <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">IPK Rata-Rata</span>
                                    <span class="text-sm font-bold text-emerald-600 mt-1 block">3,58</span>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </td>
            </tr>
        </tbody>
    </table>
</section>

<p class="mt-5 text-sm text-muted">Laporan nilai, CPMK/CPL, dan akreditasi membutuhkan data penilaian institusi yang sudah diverifikasi.</p>

<script>
    function toggleAcademicDetail(type) {
        const detailRow = document.getElementById('detail-' + type);
        const arrow = document.getElementById('arrow-' + type);
        if (!detailRow) return;

        const isHidden = detailRow.hasAttribute('hidden');
        if (isHidden) {
            detailRow.removeAttribute('hidden');
            if (arrow) arrow.classList.add('rotate-180');
        } else {
            detailRow.setAttribute('hidden', '');
            if (arrow) arrow.classList.remove('rotate-180');
        }
    }
</script>
@endsection
