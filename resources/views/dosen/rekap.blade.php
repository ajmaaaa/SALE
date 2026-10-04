@extends('layouts.mahasiswa')

@section('title', 'Rekap Capaian CPMK | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian per CPMK')

@section('content')
@php
    $totalSubCols = 0;
    foreach ($columns as $col) {
        $totalSubCols += count($col['cpmk_cols']) + 1;
    }
    $totalCols = 3 + $totalSubCols;
@endphp
<div class="space-y-5">
    @include('dosen.partials.header')

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="section-heading">Rekap Capaian per CPMK</h2>
        </div>
        <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
            <a href="{{ route('dosen.penilaian.export.cpmk.excel', $section->id) }}" 
               class="button-primary text-xs flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs w-full sm:w-auto"
               title="Export ke Excel (.xlsx) dengan Kop Surat resmi dan format berwarna">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="8" y1="13" x2="16" y2="13"></line>
                    <line x1="8" y1="17" x2="16" y2="17"></line>
                </svg>
                <span>Export Excel (.xlsx)</span>
            </a>
        </div>
    </div>

    @if($cpmks->isEmpty())
        <div class="surface p-12 text-center rounded-xl border border-line">
            <h3 class="text-base font-semibold text-ink mb-1.5">Belum Ada CPMK</h3>
            <p class="text-sm text-muted">Hubungi Admin Prodi agar CPMK dapat dipetakan.</p>
        </div>
    @elseif(empty($columns))
        <div class="surface p-12 text-center rounded-xl border border-line">
            <h3 class="text-base font-semibold text-ink mb-1.5">Belum Ada Komponen Asesmen</h3>
            <p class="text-sm text-muted mb-4">Komponen asesmen masih kosong. Silakan selesaikan atau cek pada menu Penilaian.</p>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-primary text-xs">Cek Menu Penilaian</a>
            </div>
        </div>
    @else

        {{-- Tab Navigation (PRD §5.6) --}}
        @php $inactiveCount = isset($inactiveStudents) ? $inactiveStudents->count() : 0; @endphp
        <div class="flex items-center gap-1 border-b border-line/60 mb-4" role="tablist">
            <button role="tab"
                    onclick="showRekapTab('active')"
                    id="tab-active"
                    class="px-3 py-2 text-xs font-semibold border-b-2 border-brand text-brand -mb-px transition"
                    aria-selected="true">
                Peserta Aktif ({{ count($rows) }})
            </button>
            <button role="tab"
                    onclick="showRekapTab('inactive')"
                    id="tab-inactive"
                    class="px-3 py-2 text-xs font-medium border-b-2 border-transparent text-muted hover:text-ink -mb-px transition"
                    aria-selected="false">
                Riwayat Mahasiswa Keluar ({{ $inactiveCount }})
            </button>
        </div>

        {{-- Panel: Peserta Aktif --}}
        <div id="rekap-panel-active">

        {{-- Tabel utama --}}
        <div class="surface rounded-xl border border-line/60 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto relative" tabindex="0" role="region" aria-label="Rekap capaian CPMK">
                <table class="border-separate border-spacing-0 text-xs w-full" style="min-width: max-content;">
                    <thead>
                        {{-- Baris 1: grup nama asesmen --}}
                        <tr class="bg-[#f8fafc]">
                            <th class="py-2.5 px-3 text-center text-muted font-semibold w-12 min-w-[48px] max-w-[48px] md:sticky md:left-0 bg-[#f8fafc] md:z-30 border-r border-line/50 border-b-0 align-bottom">#</th>
                            <th class="py-2.5 px-3 text-left text-muted font-semibold w-[130px] min-w-[130px] max-w-[130px] md:sticky md:left-[48px] bg-[#f8fafc] md:z-30 border-r border-line/40 border-b-0 align-bottom">NIM</th>
                            <th class="py-2.5 px-3 text-left text-muted font-semibold w-[220px] min-w-[220px] max-w-[220px] md:sticky md:left-[178px] bg-[#f8fafc] md:z-30 border-r border-line/50 border-b-0 align-bottom">Nama Mahasiswa</th>

                            @foreach($columns as $col)
                                <th class="py-2.5 px-3 text-center font-semibold text-ink border-b border-l border-line/50 bg-[#f8fafc]"
                                    colspan="{{ count($col['cpmk_cols']) + 1 }}">
                                    <div class="flex flex-col items-center justify-center text-center w-full px-1">
                                        <div class="font-semibold text-ink text-center truncate max-w-full" title="{{ $col['assessment']->name }}">
                                            {{ $col['assessment']->name }}
                                        </div>
                                        <div class="text-[10px] text-muted font-normal capitalize mt-0.5 text-center">
                                            {{ $col['assessment']->type }}
                                        </div>
                                    </div>
                                </th>
                            @endforeach
                        </tr>

                        {{-- Baris 2: sub-kolom CPMK per asesmen + kolom Total --}}
                        <tr class="bg-[#f8fafc]">
                            <th class="py-1.5 px-3 text-center w-12 min-w-[48px] max-w-[48px] md:sticky md:left-0 bg-[#f8fafc] md:z-30 border-b border-r border-line/50 border-t-0">&nbsp;</th>
                            <th class="py-1.5 px-3 text-left w-[130px] min-w-[130px] max-w-[130px] md:sticky md:left-[48px] bg-[#f8fafc] md:z-30 border-b border-r border-line/40 border-t-0">&nbsp;</th>
                            <th class="py-1.5 px-3 text-left w-[220px] min-w-[220px] max-w-[220px] md:sticky md:left-[178px] bg-[#f8fafc] md:z-30 border-b border-r border-line/50 border-t-0">&nbsp;</th>

                            @foreach($columns as $col)
                                @foreach($col['cpmk_cols'] as $cc)
                                    <th class="py-2 px-2 text-center min-w-[85px] border-b border-l border-line/40 bg-[#f8fafc]">
                                        <div class="font-bold text-ink text-[11px]">{{ $cc['cpmk']->code }}</div>
                                    </th>
                                @endforeach
                                <th class="py-2 px-2 text-center min-w-[75px] border-b border-l border-line/40 bg-[#f8fafc]">
                                    <div class="font-bold text-ink text-[11px]">Total</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $idx => $row)
                            <tr class="group bg-white hover:bg-slate-50 transition-colors">
                                <td class="py-2.5 px-3 text-center text-muted/70 w-12 min-w-[48px] max-w-[48px] md:sticky md:left-0 bg-white group-hover:bg-slate-50 md:z-20 border-b border-r border-line/40">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono text-muted w-[130px] min-w-[130px] max-w-[130px] md:sticky md:left-[48px] bg-white group-hover:bg-slate-50 md:z-20 text-[11px] border-b border-r border-line/40">{{ $row['student']->nim_nidn ?? '' }}</td>
                                <td class="py-2.5 px-3 font-medium text-ink w-[220px] min-w-[220px] max-w-[220px] md:sticky md:left-[178px] bg-white group-hover:bg-slate-50 md:z-20 border-b border-r border-line/50">
                                    <div class="truncate" title="{{ $row['student']->name }}">{{ $row['student']->name }}</div>
                                </td>

                                @foreach($columns as $col)
                                    @foreach($col['cpmk_cols'] as $cc)
                                        @php
                                            $cellKey = $col['assessment']->id . '_' . $cc['cpmk']->id;
                                            $val     = $row['cells'][$cellKey] ?? null;
                                            $status  = $row['statuses'][$cellKey] ?? 'no_submission';
                                        @endphp
                                        <td class="py-2.5 px-2 text-center border-b border-l border-line/30 bg-white group-hover:bg-slate-50">
                                            @if($val !== null)
                                                <span class="font-mono font-semibold text-ink">{{ number_format($val, 1) }}</span>
                                            @elseif($status === 'pending')
                                                <span class="text-xs font-medium text-muted whitespace-nowrap">Menunggu</span>
                                            @else
                                                <span class="text-muted font-mono"></span>
                                            @endif
                                        </td>
                                    @endforeach

                                    {{-- Kolom Total Asesmen (Skala 100) --}}
                                    @php
                                        $asmtTotal = $row['asmt_totals'][$col['assessment']->id] ?? ['score' => null, 'status' => 'no_submission'];
                                    @endphp
                                    <td class="py-2.5 px-2 text-center border-b border-l border-line/40 bg-white group-hover:bg-slate-50">
                                        @if($asmtTotal['status'] === 'scored' && $asmtTotal['score'] !== null)
                                            <span class="font-mono font-bold text-ink">{{ number_format($asmtTotal['score'], 1) }}</span>
                                        @elseif($asmtTotal['status'] === 'pending')
                                            <span class="text-xs font-medium text-muted whitespace-nowrap">Menunggu</span>
                                        @else
                                            <span class="text-muted font-mono"></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalCols }}" class="text-center py-8 text-muted bg-white">
                                    Belum ada mahasiswa terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>{{-- /rekap-panel-active --}}

        {{-- Panel: Riwayat Mahasiswa Keluar (PRD §5.6) --}}
        <div id="rekap-panel-inactive" class="hidden">
            @if(isset($inactiveStudents) && $inactiveStudents->isNotEmpty())
            <div class="surface rounded-xl border border-line/60 overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="text-xs w-full">
                        <thead class="bg-canvas/60">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-ink">Nama Mahasiswa</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink">NIM / Akun</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink">Status Keluar</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink">Tanggal Keluar</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink">Alasan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40 bg-white">
                            @foreach($inactiveStudents as $entry)
                            <tr class="hover:bg-canvas/20">
                                <td class="px-4 py-3 font-medium text-ink">{{ $entry['student']->name }}</td>
                                <td class="px-4 py-3 font-mono text-muted">{{ $entry['student']->number ?? $entry['student']->email ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($entry['status'] === 'kicked')
                                        <span class="inline-flex items-center rounded border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">
                                            Dikeluarkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                            Keluar Sendiri
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-muted whitespace-nowrap">{{ $entry['left_at']?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-muted">
                                    @if($entry['reason'])
                                        <span>{{ $entry['reason'] }}</span>
                                        @if($entry['kicked_by_name'])
                                            <span class="text-[11px] text-muted"> (oleh {{ $entry['kicked_by_name'] }})</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="surface rounded-xl border border-line/60 p-8 text-center text-muted text-xs">
                Tidak ada riwayat mahasiswa yang keluar atau dikeluarkan pada kelas ini.
            </div>
            @endif
        </div>{{-- /rekap-panel-inactive --}}

    @endif
</div>

<script nonce="{{ $cspNonce }}">
function showRekapTab(tab) {
    const isActive = tab === 'active';
    const panelActive = document.getElementById('rekap-panel-active');
    const panelInactive = document.getElementById('rekap-panel-inactive');
    const tabActive = document.getElementById('tab-active');
    const tabInactive = document.getElementById('tab-inactive');

    if (panelActive) panelActive.classList.toggle('hidden', !isActive);
    if (panelInactive) panelInactive.classList.toggle('hidden', isActive);

    if (tabActive) {
        tabActive.classList.toggle('border-brand', isActive);
        tabActive.classList.toggle('text-brand', isActive);
        tabActive.classList.toggle('font-semibold', isActive);
        tabActive.classList.toggle('border-transparent', !isActive);
        tabActive.classList.toggle('text-muted', !isActive);
        tabActive.setAttribute('aria-selected', isActive ? 'true' : 'false');
    }
    if (tabInactive) {
        tabInactive.classList.toggle('border-brand', !isActive);
        tabInactive.classList.toggle('text-brand', !isActive);
        tabInactive.classList.toggle('font-semibold', !isActive);
        tabInactive.classList.toggle('border-transparent', isActive);
        tabInactive.classList.toggle('text-muted', isActive);
        tabInactive.setAttribute('aria-selected', !isActive ? 'true' : 'false');
    }
}
</script>
@endsection
