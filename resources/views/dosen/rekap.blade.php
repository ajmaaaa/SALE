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
            <p class="mt-1 text-sm text-muted">
                Nilai tiap CPMK per mahasiswa dari seluruh komponen asesmen yang dipetakan.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('dosen.penilaian.export.cpmk', $section->id) }}" class="button-secondary text-xs">Export CSV</a>
        </div>
    </div>

    @if($cpmks->isEmpty())
        <div class="surface p-12 text-center rounded-xl border border-line">
            <h3 class="text-base font-semibold text-ink mb-1.5">Belum Ada CPMK</h3>
            <p class="text-sm text-muted">Hubungi pengelola kurikulum agar CPMK dapat dipetakan.</p>
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
            <div class="overflow-x-auto">
                <table class="border-collapse text-xs" style="min-width: max-content; width: 100%">
                    <thead>
                        {{-- Baris 1: grup nama asesmen --}}
                        <tr class="border-b border-line/60 bg-canvas/40">
                            <th class="py-3 px-3 text-center text-muted font-medium w-10 sticky left-0 bg-canvas/80 z-10 border-r border-line/40" rowspan="2">#</th>
                            <th class="py-3 px-3 text-left text-muted font-medium min-w-[6rem] sticky left-10 bg-canvas/80 z-10" rowspan="2">NIM</th>
                            <th class="py-3 px-3 text-left text-muted font-medium min-w-[10rem] sticky left-[7.5rem] bg-canvas/80 z-10 border-r border-line/60" rowspan="2">Nama</th>

                            @foreach($columns as $col)
                                <th class="py-2.5 px-3 text-center font-semibold text-ink border-l border-line/50"
                                    colspan="{{ count($col['cpmk_cols']) + 1 }}">
                                    <div class="truncate font-semibold" style="max-width: {{ (count($col['cpmk_cols']) + 1) * 90 }}px" title="{{ $col['assessment']->name }}">
                                        {{ $col['assessment']->name }}
                                    </div>
                                    <div class="text-[10px] text-muted font-normal capitalize mt-0.5">{{ $col['assessment']->type }}</div>
                                </th>
                            @endforeach
                        </tr>

                        {{-- Baris 2: sub-kolom CPMK per asesmen + kolom Total --}}
                        <tr class="border-b border-line/60 bg-canvas/20">
                            @foreach($columns as $col)
                                @foreach($col['cpmk_cols'] as $cc)
                                    <th class="py-2 px-2 text-center min-w-[85px] border-l border-line/40">
                                        <div class="font-bold text-ink text-[11px]">{{ $cc['cpmk']->code }}</div>
                                        <div class="text-[10px] text-muted font-normal mt-0.5">{{ $cc['weight_fmt'] }}%</div>
                                    </th>
                                @endforeach
                                <th class="py-2 px-2 text-center min-w-[75px] border-l border-line/50 bg-canvas/40">
                                    <div class="font-bold text-brand text-[11px]">Total</div>
                                    <div class="text-[10px] text-muted font-medium mt-0.5">(100)</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-line/30">
                        @forelse($rows as $idx => $row)
                            <tr class="hover:bg-canvas/30 transition-colors {{ $row['has_pending'] ? 'bg-amber-50/20' : '' }}">
                                <td class="py-2.5 px-3 text-center text-muted/70 sticky left-0 bg-white/95 z-10 border-r border-line/30">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono text-muted sticky left-10 bg-white/95 z-10 text-[11px]">{{ $row['student']->nim_nidn ?? '' }}</td>
                                <td class="py-2.5 px-3 font-medium text-ink sticky left-[7.5rem] bg-white/95 z-10 border-r border-line/40">{{ $row['student']->name }}</td>

                                @foreach($columns as $col)
                                    @foreach($col['cpmk_cols'] as $cc)
                                        @php
                                            $cellKey = $col['assessment']->id . '_' . $cc['cpmk']->id;
                                            $val     = $row['cells'][$cellKey] ?? null;
                                            $status  = $row['statuses'][$cellKey] ?? 'no_submission';
                                        @endphp
                                        <td class="py-2.5 px-2 text-center border-l border-line/30">
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
                                    <td class="py-2.5 px-2 text-center border-l border-line/50 bg-canvas/15">
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