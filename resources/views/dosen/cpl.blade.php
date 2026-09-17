@extends('layouts.mahasiswa')

@section('title', 'Rekap CPL | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian per CPL')

@section('content')
<div class="space-y-5">
    @include('dosen.partials.header')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="section-heading">4. Rekap Capaian per CPL</h2>
            <p class="mt-1 text-sm text-muted">Kontribusi ketercapaian CPL dari mata kuliah ini berdasarkan capaian CPMK.</p>
        </div>
        <a href="{{ route('dosen.penilaian.export.cpl', $section->id) }}" class="button-secondary text-xs shrink-0">Export CSV</a>
    </div>

    @if($cpls->isEmpty())
        <div class="surface p-12 text-center">
            <h3 class="text-base font-semibold text-ink mb-1.5">Belum Ada CPL Terhubung</h3>
            <p class="text-sm text-muted">Pemetaan CPL ke CPMK dikonfigurasi di level kurikulum program studi.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($cplCards as $card)
                @php
                    $cpl = $card['cpl'];
                    $hasCpmk = !empty($card['contributing_cpmks']);
                    $agg = $card['aggregate'];
                    $avg = $agg['average'];
                    $rate = $agg['pass_rate'];
                    $threshold = 65;
                    $pred = $agg['predicate'];
                @endphp

                <section class="surface border border-line/60 rounded-xl overflow-hidden">
                    {{-- Header --}}
                    <div class="px-5 py-3 border-b border-line/60 bg-canvas/25 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="font-bold text-xs text-brand uppercase">{{ $cpl->code }}</span>
                            <span class="h-3.5 w-px bg-line"></span>
                            <h3 class="text-sm font-semibold text-ink">{{ $cpl->description }}</h3>
                            @if(!empty($card['contributing_cpmks']))
                                <span class="h-3.5 w-px bg-line"></span>
                                <span class="text-xs text-muted flex flex-wrap items-center gap-1.5">
                                    <span>Disusun dari CPMK:</span>
                                    @foreach($card['contributing_cpmks'] as $cItem)
                                        <span class="inline-flex items-center gap-1 rounded bg-white px-2 py-0.5 text-xs font-medium text-ink border border-line/60">
                                            <span>{{ $cItem['cpmk']->code }}</span>
                                            <span class="text-muted font-normal">({{ $cItem['weight_formatted'] }})</span>
                                        </span>
                                    @endforeach
                                </span>
                            @endif
                        </div>

                        @if($avg !== null || $rate !== null)
                            <div class="flex items-center gap-3.5 text-xs text-muted shrink-0">
                                @if($avg !== null)
                                    <span>Rata-rata: <strong class="text-ink font-semibold text-sm">{{ number_format($avg, 1) }}</strong></span>
                                @endif
                                @if($rate !== null)
                                    <span class="h-3.5 w-px bg-line"></span>
                                    <span>Tuntas &ge;{{ $threshold }}: <strong class="text-ink font-semibold text-sm">{{ $rate }}%</strong></span>
                                @endif
                                @if($pred)
                                    <span class="h-3.5 w-px bg-line"></span>
                                    <span>Predikat: <strong class="text-ink font-semibold text-sm">{{ $pred }}</strong></span>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if(!$hasCpmk)
                        <div class="px-5 py-3 text-xs text-muted">
                            Belum ada pemetaan CPMK untuk CPL ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-line/50 bg-canvas/30">
                                        <th class="w-10 py-3 px-3 text-center text-muted font-medium">#</th>
                                        <th class="py-3 px-3 text-muted font-medium w-28">NIM</th>
                                        <th class="py-3 px-3 text-muted font-medium min-w-[160px]">Nama</th>
                                        @foreach($card['contributing_cpmks'] as $cItem)
                                            <th class="py-3 px-3 text-center border-l border-line/40 font-medium min-w-[100px] w-28">
                                                <div class="text-ink font-semibold">{{ $cItem['cpmk']->code }}</div>
                                                <div class="text-muted font-normal text-[11px] mt-0.5">Bobot: {{ $cItem['weight_formatted'] }}</div>
                                            </th>
                                        @endforeach
                                        <th class="py-3 px-3 text-center font-semibold text-ink border-l border-line/40 min-w-[100px] w-28 bg-canvas/50">Nilai {{ $cpl->code }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/30">
                                    @forelse($card['students'] as $idx => $sRow)
                                        @php $cplScore = $sRow['cpl_score']; @endphp
                                        <tr class="hover:bg-canvas/20 transition-colors">
                                            <td class="py-2.5 px-3 text-center text-muted/70">{{ $idx + 1 }}</td>
                                            <td class="py-2.5 px-3 text-muted">{{ $sRow['student']->nim_nidn ?? '' }}</td>
                                            <td class="py-2.5 px-3 font-medium text-ink">{{ $sRow['student']->name }}</td>
                                            @foreach($card['contributing_cpmks'] as $cItem)
                                                @php $sc = $sRow['cpmk_scores'][$cItem['cpmk']->id] ?? null; @endphp
                                                <td class="py-2.5 px-3 text-center border-l border-line/30">{{ $sc !== null ? number_format($sc, 1) : '' }}</td>
                                            @endforeach
                                            <td class="py-2.5 px-3 text-center font-semibold text-ink border-l border-line/30 bg-canvas/30">
                                                {{ $cplScore !== null ? number_format($cplScore, 2) : '' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 4 + count($card['contributing_cpmks']) }}" class="text-center py-6 text-muted">
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

        <p class="text-xs text-muted px-1">
            Catatan Ketercapaian CPL: Capaian CPL di atas merupakan kontribusi dari <strong class="text-ink font-medium">{{ $section->mataKuliah->code }} {{ $section->mataKuliah->name }}</strong>. Ketercapaian CPL program studi dihitung lintas mata kuliah.
        </p>
    @endif
</div>
@endsection
