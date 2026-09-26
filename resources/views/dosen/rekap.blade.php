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

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="section-heading">Rekap Capaian per CPMK</h2>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('dosen.penilaian.export.cpmk.excel', $section->id) }}" 
               class="button-primary text-xs flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs"
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
            <h3 class="text-base font-semibold text-ink mb-1.5">Matriks Belum Dikonfigurasi</h3>
            <p class="text-sm text-muted mb-4">Belum ada bobot asesmen yang dipetakan ke CPMK.</p>
            <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-primary text-xs">Atur Matriks</a>
        </div>
    @else

        {{-- Tabel utama --}}
        <div class="surface rounded-xl border border-line/60 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto relative">
                <table class="border-separate border-spacing-0 text-xs w-full" style="min-width: max-content;">
                    <thead>
                        {{-- Baris 1: grup nama asesmen --}}
                        <tr class="bg-slate-100 dark:bg-slate-800">
                            <th class="py-3 px-3 text-center text-muted font-semibold w-12 min-w-[48px] max-w-[48px] sticky left-0 bg-slate-100 dark:bg-slate-800 z-30 border-b border-r border-line/50" rowspan="2">#</th>
                            <th class="py-3 px-3 text-left text-muted font-semibold w-[130px] min-w-[130px] max-w-[130px] sticky left-[48px] bg-slate-100 dark:bg-slate-800 z-30 border-b border-r border-line/40" rowspan="2">NIM</th>
                            <th class="py-3 px-3 text-left text-muted font-semibold w-[200px] min-w-[200px] max-w-[220px] sticky left-[178px] bg-slate-100 dark:bg-slate-800 z-30 border-b border-r-2 border-line/80 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.08)]" rowspan="2">Nama</th>

                            @foreach($columns as $col)
                                <th class="py-2.5 px-3 text-center font-semibold text-ink border-b border-l border-line/50 bg-slate-100 dark:bg-slate-800"
                                    colspan="{{ count($col['cpmk_cols']) + 1 }}">
                                    <div class="truncate font-semibold" style="max-width: {{ (count($col['cpmk_cols']) + 1) * 90 }}px" title="{{ $col['assessment']->name }}">
                                        {{ $col['assessment']->name }}
                                    </div>
                                    <div class="text-[10px] text-muted font-normal capitalize mt-0.5">{{ $col['assessment']->type }}</div>
                                </th>
                            @endforeach
                        </tr>

                        {{-- Baris 2: sub-kolom CPMK per asesmen + kolom Total --}}
                        <tr class="bg-slate-50 dark:bg-slate-850">
                            @foreach($columns as $col)
                                @foreach($col['cpmk_cols'] as $cc)
                                    <th class="py-2 px-2 text-center min-w-[85px] border-b border-l border-line/40 bg-slate-50 dark:bg-slate-800/70">
                                        <div class="font-bold text-ink text-[11px]">{{ $cc['cpmk']->code }}</div>
                                        <div class="text-[10px] text-muted font-normal mt-0.5">{{ $cc['weight_fmt'] }}%</div>
                                    </th>
                                @endforeach
                                <th class="py-2 px-2 text-center min-w-[75px] border-b border-l border-line/50 bg-slate-100/80 dark:bg-slate-800">
                                    <div class="font-bold text-brand text-[11px]">Total</div>
                                    <div class="text-[10px] text-muted font-medium mt-0.5">(100)</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $idx => $row)
                            <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors {{ $row['has_pending'] ? 'bg-amber-50/40' : '' }}">
                                <td class="py-2.5 px-3 text-center text-muted/70 w-12 min-w-[48px] max-w-[48px] sticky left-0 {{ $row['has_pending'] ? 'bg-[#fffdf2] group-hover:bg-[#fef9c3]' : 'bg-white group-hover:bg-slate-50' }} dark:bg-slate-900 dark:group-hover:bg-slate-800 z-20 border-b border-r border-line/40">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono text-muted w-[130px] min-w-[130px] max-w-[130px] sticky left-[48px] {{ $row['has_pending'] ? 'bg-[#fffdf2] group-hover:bg-[#fef9c3]' : 'bg-white group-hover:bg-slate-50' }} dark:bg-slate-900 dark:group-hover:bg-slate-800 z-20 text-[11px] border-b border-r border-line/40">{{ $row['student']->nim_nidn ?? '' }}</td>
                                <td class="py-2.5 px-3 font-medium text-ink w-[200px] min-w-[200px] max-w-[220px] sticky left-[178px] {{ $row['has_pending'] ? 'bg-[#fffdf2] group-hover:bg-[#fef9c3]' : 'bg-white group-hover:bg-slate-50' }} dark:bg-slate-900 dark:group-hover:bg-slate-800 z-20 border-b border-r-2 border-line/80 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.08)]">
                                    <div class="truncate" title="{{ $row['student']->name }}">{{ $row['student']->name }}</div>
                                </td>

                                @foreach($columns as $col)
                                    @foreach($col['cpmk_cols'] as $cc)
                                        @php
                                            $cellKey = $col['assessment']->id . '_' . $cc['cpmk']->id;
                                            $val     = $row['cells'][$cellKey] ?? null;
                                            $status  = $row['statuses'][$cellKey] ?? 'no_submission';
                                        @endphp
                                        <td class="py-2.5 px-2 text-center border-b border-l border-line/30">
                                            @if($val !== null)
                                                <span class="font-mono font-semibold text-ink">{{ number_format($val, 1) }}</span>
                                            @elseif($status === 'pending')
                                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold text-amber-800 bg-amber-100 border border-amber-200 whitespace-nowrap">
                                                    Menunggu
                                                </span>
                                            @else
                                                <span class="text-line/60 font-mono">&mdash;</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    {{-- Kolom Total Asesmen (Skala 100) --}}
                                    @php
                                        $asmtTotal = $row['asmt_totals'][$col['assessment']->id] ?? ['score' => null, 'status' => 'no_submission'];
                                    @endphp
                                    <td class="py-2.5 px-2 text-center border-b border-l border-line/50 bg-slate-50/60 dark:bg-slate-800/40">
                                        @if($asmtTotal['status'] === 'scored' && $asmtTotal['score'] !== null)
                                            <span class="font-mono font-bold text-ink">{{ number_format($asmtTotal['score'], 1) }}</span>
                                        @elseif($asmtTotal['status'] === 'pending')
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold text-amber-800 bg-amber-100 border border-amber-200 whitespace-nowrap">
                                                Menunggu
                                            </span>
                                        @else
                                            <span class="text-line/60 font-mono">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalCols }}" class="text-center py-8 text-muted">
                                    Belum ada mahasiswa terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection