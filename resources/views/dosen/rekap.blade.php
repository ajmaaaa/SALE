@extends('layouts.mahasiswa')

@section('title', 'Rekap Capaian CPMK | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian per CPMK')

@section('content')
@php
    $totalSubCols = 0;
    foreach ($columns as $col) {
        $totalSubCols += count($col['cpmk_cols']);
    }
    $totalCols = 3 + $totalSubCols + $cpmks->count();
@endphp
<div class="space-y-5">
    @include('dosen.partials.header')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="section-heading">3. Rekap Capaian per CPMK</h2>
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
        {{-- Kartu ringkasan per CPMK --}}
        @php $hasAgg = collect($cpmkAggregates)->contains(fn($a) => ($a['average'] ?? null) !== null); @endphp
        @if($hasAgg)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ min(6, $cpmks->count()) }} gap-3">
                @foreach($cpmks as $cpmk)
                    @php
                        $agg = $cpmkAggregates[$cpmk->id] ?? [];
                        $avg = $agg['average'] ?? null;
                        $rate = $agg['pass_rate'] ?? null;
                        $thr = (float) ($cpmk->threshold ?: 60);
                        $ok  = $avg !== null && $avg >= $thr;
                    @endphp
                    <div class="surface rounded-xl border {{ $avg !== null ? ($ok ? 'border-emerald-200 bg-emerald-50/30' : 'border-rose-200 bg-rose-50/30') : 'border-line/60' }} p-3.5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-brand uppercase tracking-wide">{{ $cpmk->code }}</span>
                            @if($avg !== null)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $ok ? 'text-emerald-800 bg-emerald-100' : 'text-rose-700 bg-rose-100' }}">
                                    {{ $ok ? 'Tuntas' : 'Belum tuntas' }}
                                </span>
                            @endif
                        </div>
                        <p class="text-2xl font-extrabold text-ink">{{ $avg !== null ? number_format($avg, 1) : '—' }}</p>
                        <p class="text-[11px] text-muted mt-0.5">
                            Threshold {{ $thr }}{{ $rate !== null ? ' &bull; ' . $rate . '% tuntas' : '' }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Legenda --}}
        <div class="flex flex-wrap items-center gap-5 text-[11px] text-muted">
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-100 border border-amber-300"></span> Menunggu penilaian</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-emerald-100 border border-emerald-300"></span> Tuntas CPMK</span>
            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-rose-100 border border-rose-300"></span> Belum tuntas CPMK</span>
            <span class="flex items-center gap-1.5"><span class="text-base text-line leading-none">&mdash;</span> Belum ada data</span>
        </div>

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
                                    colspan="{{ count($col['cpmk_cols']) }}">
                                    <div class="truncate font-semibold" style="max-width: {{ count($col['cpmk_cols']) * 90 }}px" title="{{ $col['assessment']->name }}">
                                        {{ $col['assessment']->name }}
                                    </div>
                                    <div class="text-[10px] text-muted font-normal capitalize mt-0.5">{{ $col['assessment']->type }}</div>
                                </th>
                            @endforeach

                            <th class="py-2.5 px-3 text-center font-bold text-brand border-l-2 border-brand/30 bg-brand-soft/15"
                                colspan="{{ $cpmks->count() }}">
                                Nilai CPMK Final
                            </th>
                        </tr>

                        {{-- Baris 2: sub-kolom CPMK per asesmen + header CPMK final --}}
                        <tr class="border-b border-line/60 bg-canvas/20">
                            @foreach($columns as $col)
                                @foreach($col['cpmk_cols'] as $cc)
                                    <th class="py-2 px-2 text-center min-w-[85px] border-l border-line/40">
                                        <div class="font-bold text-ink text-[11px]">{{ $cc['cpmk']->code }}</div>
                                        <div class="text-[10px] text-muted font-normal mt-0.5">{{ $cc['weight_fmt'] }}%</div>
                                    </th>
                                @endforeach
                            @endforeach

                            @foreach($cpmks as $cpmk)
                                <th class="py-2 px-2 text-center min-w-[85px] border-l border-brand/20 bg-brand-soft/10">
                                    <div class="font-bold text-brand text-[11px]">{{ $cpmk->code }}</div>
                                    <div class="text-[10px] text-muted font-normal mt-0.5">min. {{ (float)($cpmk->threshold ?: 60) }}</div>
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
                                @endforeach

                                @foreach($cpmks as $cpmk)
                                    @php
                                        $finalVal  = $row['cpmk_finals'][$cpmk->id] ?? null;
                                        $thr       = (float) ($cpmk->threshold ?: 60);
                                        $isPending = ($finalVal === null) && $row['has_pending'];
                                    @endphp
                                    <td class="py-2.5 px-2 text-center border-l border-brand/15 bg-brand-soft/5">
                                        @if($finalVal !== null)
                                            <span class="font-mono font-bold {{ $finalVal >= $thr ? 'text-emerald-700' : 'text-rose-600' }}">
                                                {{ number_format($finalVal, 2) }}
                                            </span>
                                        @elseif($isPending)
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

                    @if($hasAgg)
                        <tfoot>
                            <tr class="border-t-2 border-line/60 bg-canvas/60">
                                <td colspan="3" class="py-2.5 px-3 text-right text-xs font-semibold text-muted sticky left-0 bg-canvas/80 z-10 border-r border-line/40">
                                    Rata-rata kelas
                                </td>
                                @foreach($columns as $col)
                                    @foreach($col['cpmk_cols'] as $cc)
                                        <td class="py-2.5 px-2 text-center border-l border-line/30 text-muted font-mono text-[11px]">&mdash;</td>
                                    @endforeach
                                @endforeach
                                @foreach($cpmks as $cpmk)
                                    @php $avg2 = $cpmkAggregates[$cpmk->id]['average'] ?? null; $thr2 = (float)($cpmk->threshold ?: 60); @endphp
                                    <td class="py-2.5 px-2 text-center border-l border-brand/15 bg-brand-soft/5">
                                        @if($avg2 !== null)
                                            <span class="font-mono font-bold text-xs {{ $avg2 >= $thr2 ? 'text-emerald-700' : 'text-rose-600' }}">{{ number_format($avg2, 2) }}</span>
                                        @else
                                            <span class="text-muted font-mono">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="border-t border-line/40 bg-canvas/30">
                                <td colspan="3" class="py-2 px-3 text-right text-[11px] font-semibold text-muted sticky left-0 bg-canvas/60 z-10 border-r border-line/40">
                                    Ketuntasan
                                </td>
                                @foreach($columns as $col)
                                    @foreach($col['cpmk_cols'] as $cc)
                                        <td class="py-2 px-2 text-center border-l border-line/30 text-muted text-[10px]">&mdash;</td>
                                    @endforeach
                                @endforeach
                                @foreach($cpmks as $cpmk)
                                    @php
                                        $agg3  = $cpmkAggregates[$cpmk->id] ?? [];
                                        $rate3 = $agg3['pass_rate'] ?? null;
                                        $pass3 = $agg3['pass_count'] ?? 0;
                                        $tot3  = $agg3['graded_count'] ?? 0;
                                    @endphp
                                    <td class="py-2 px-2 text-center border-l border-brand/15 bg-brand-soft/5 text-[11px]">
                                        @if($rate3 !== null)
                                            <span class="font-semibold {{ $rate3 >= 75 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $rate3 }}%</span>
                                            <span class="text-muted">({{ $pass3 }}/{{ $tot3 }})</span>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @if($totalCpmkWeight > 0)
            <div class="flex flex-wrap gap-4 text-xs text-muted">
                @foreach($cpmks as $cpmk)
                    @php $w = $cpmkWeights[$cpmk->id] ?? 0; @endphp
                    @if($w > 0)
                        <span><span class="font-semibold text-ink">{{ $cpmk->code }}</span> {{ rtrim(rtrim(number_format($w, 1), '0'), '.') }}%</span>
                    @endif
                @endforeach
                <span>| Total: {{ rtrim(rtrim(number_format($totalCpmkWeight, 1), '0'), '.') }}%</span>
            </div>
        @endif
    @endif
</div>
@endsection

