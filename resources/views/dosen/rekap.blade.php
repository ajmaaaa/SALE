@extends('layouts.mahasiswa')

@section('title', 'Rekap Capaian CPMK | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian per CPMK')

@section('content')
<div class="space-y-5">
    @include('dosen.partials.header')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="section-heading">3. Rekap Capaian per CPMK</h2>
            <p class="mt-1 text-sm text-muted">Rincian capaian tiap CPMK berdasarkan komponen asesmen yang dipetakan.</p>
        </div>
        <a href="{{ route('dosen.penilaian.export.cpmk', $section->id) }}" class="button-secondary text-xs shrink-0">Export CSV</a>
    </div>

    @if($cpmks->isEmpty())
        <div class="surface p-12 text-center">
            <h3 class="text-base font-semibold text-ink mb-1.5">Belum Ada CPMK</h3>
            <p class="text-sm text-muted">Hubungi pengelola kurikulum agar CPMK dapat dipetakan.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($cpmkCards as $card)
                @php
                    $cpmk = $card['cpmk'];
                    $hasWeight = $card['weight'] > 0 && !empty($card['components']);
                    $agg = $card['aggregate'];
                    $avg = $agg['average'];
                    $rate = $agg['pass_rate'];
                    $threshold = (float)($cpmk->threshold ?: 60);
                    $pred = $agg['predicate'];
                @endphp

                <section class="surface border border-line/60 rounded-xl overflow-hidden">
                    {{-- Header --}}
                    <div class="px-5 py-3.5 border-b border-line/60 bg-canvas/25">
                        {{-- Identity row --}}
                        <div class="flex flex-wrap items-center gap-2.5 mb-0">
                            <span class="font-bold text-xs text-brand uppercase tracking-wide">{{ $cpmk->code }}</span>
                            <span class="h-3.5 w-px bg-line"></span>
                            <h3 class="text-sm font-semibold text-ink">{{ $cpmk->description }}</h3>
                            <span class="h-3.5 w-px bg-line"></span>
                            <span class="text-xs text-muted">Bobot: <strong class="text-ink font-semibold">{{ rtrim(rtrim(number_format($card['weight'], 1), '0'), '.') }}%</strong></span>
                        </div>

                        @if($hasWeight && ($avg !== null || $rate !== null))
                            @php
                                $metricCount = ($avg !== null ? 1 : 0) + ($rate !== null ? 1 : 0) + ($pred ? 1 : 0);
                            @endphp
                            {{-- Metrics grid: each metric gets equal space --}}
                            <div class="mt-3 grid gap-0 divide-x divide-line/50 border border-line/50 rounded-lg overflow-hidden"
                                 style="grid-template-columns: repeat({{ $metricCount }}, 1fr)">
                                @if($avg !== null)
                                    <div class="px-4 py-2.5 text-center">
                                        <p class="text-[11px] text-muted uppercase tracking-wide font-medium mb-0.5">Rata-rata</p>
                                        <p class="text-base font-bold text-ink">{{ number_format($avg, 1) }}</p>
                                    </div>
                                @endif
                                @if($rate !== null)
                                    <div class="px-4 py-2.5 text-center">
                                        <p class="text-[11px] text-muted uppercase tracking-wide font-medium mb-0.5">Tuntas</p>
                                        <p class="text-base font-bold text-ink">{{ $rate }}% <span class="text-xs text-muted font-normal">({{ $agg['pass_count'] }}/{{ $agg['graded_count'] }})</span></p>
                                    </div>
                                @endif
                                @if($pred)
                                    <div class="px-4 py-2.5 text-center">
                                        <p class="text-[11px] text-muted uppercase tracking-wide font-medium mb-0.5">Predikat</p>
                                        <p class="text-base font-bold text-ink">{{ $pred }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if(!$hasWeight)
                        <div class="px-5 py-3 flex items-center justify-between text-xs text-muted gap-3">
                            <span>Belum ada bobot yang dialokasikan pada matriks.</span>
                            <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-secondary text-xs shrink-0">Atur Matriks</a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            @php
                                $compCount = count($card['components']);
                                // Each assessment col + 1 score col share remaining width evenly
                                $scoreCols = $compCount + 1;
                            @endphp
                            <table class="w-full text-left border-collapse text-xs table-fixed">
                                <colgroup>
                                    <col style="width: 2.5rem">{{-- # --}}
                                    <col style="width: 7.5rem">{{-- NIM --}}
                                    <col style="width: 11rem">{{-- Nama --}}
                                    @for($ci = 0; $ci < $scoreCols; $ci++)
                                        <col style="width: {{ max(6, round(24 / $scoreCols)) }}rem">
                                    @endfor
                                </colgroup>
                                <thead>
                                    <tr class="border-b border-line/50 bg-canvas/30">
                                        <th class="py-3 px-3 text-center text-muted font-medium">#</th>
                                        <th class="py-3 px-3 text-muted font-medium">NIM</th>
                                        <th class="py-3 px-3 text-muted font-medium">Nama</th>
                                        @foreach($card['components'] as $comp)
                                            <th class="py-3 px-3 text-center border-l border-line/40 font-medium">
                                                <div class="text-ink font-semibold truncate">{{ $comp['assessment']->name }}</div>
                                                <div class="text-muted font-normal text-[11px] mt-0.5">Bobot {{ $comp['weight_formatted'] }}%</div>
                                            </th>
                                        @endforeach
                                        <th class="py-3 px-3 text-center font-semibold text-ink border-l border-line/40 bg-canvas/50">Nilai {{ $cpmk->code }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/30">
                                    @forelse($card['students'] as $idx => $sRow)
                                        @php $cScore = $sRow['cpmk_score']; @endphp
                                        <tr class="hover:bg-canvas/20 transition-colors">
                                            <td class="py-2.5 px-3 text-center text-muted/70">{{ $idx + 1 }}</td>
                                            <td class="py-2.5 px-3 text-muted truncate">{{ $sRow['student']->nim_nidn ?? '' }}</td>
                                            <td class="py-2.5 px-3 font-medium text-ink truncate">{{ $sRow['student']->name }}</td>
                                            @foreach($card['components'] as $comp)
                                                @php $sc = $sRow['scores'][$comp['assessment']->id] ?? null; @endphp
                                                <td class="py-2.5 px-3 text-center border-l border-line/30">{{ $sc !== null ? number_format($sc, 1) : '' }}</td>
                                            @endforeach
                                            <td class="py-2.5 px-3 text-center font-semibold text-ink border-l border-line/30 bg-canvas/30">
                                                {{ $cScore !== null ? number_format($cScore, 2) : '' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 4 + count($card['components']) }}" class="text-center py-6 text-muted">
                                                Belum ada mahasiswa terdaftar.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
