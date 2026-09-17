@extends('layouts.mahasiswa')

@section('title', 'Rekap CPL | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian per CPL')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="section-heading">4. Rekap Capaian per CPL</h2>
            <p class="text-sm text-muted">
                Estimasi kontribusi ketercapaian CPL Program Studi dari total capaian CPMK yang dipetakan pada mata kuliah ini.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dosen.penilaian.export.cpl', $section->id) }}" class="button-secondary text-xs">
                Export CSV CPL
            </a>
        </div>
    </header>

    @if($cpls->isEmpty())
        <div class="surface p-10 text-center">
            <h3 class="section-heading">Belum Ada CPL yang Terhubung</h3>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Pemetaan CPL ke CPMK mata kuliah dikonfigurasi di level kurikulum program studi.
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($cplCards as $card)
                @php
                    $cpl = $card['cpl'];
                    $hasCpmk = !empty($card['contributing_cpmks']);
                    $agg = $card['aggregate'];
                    $avg = $agg['average'];
                    $rate = $agg['pass_rate'];
                    $threshold = 65; // Standar CPL
                    $pred = $agg['predicate'];
                @endphp

                <section class="surface p-5 sm:p-6 space-y-4 rounded-xl border border-line">
                    {{-- 1. Header Kartu: Kode CPL, Deskripsi, Rata-rata Kelas --}}
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 border-b border-line pb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-bold text-brand bg-brand-soft px-2.5 py-0.5 rounded">
                                    {{ $cpl->code }}
                                </span>
                                <h3 class="text-base font-bold text-ink">
                                    {{ $cpl->code }}
                                </h3>
                            </div>
                            <p class="text-sm text-ink/80 leading-relaxed max-w-3xl">
                                {{ $cpl->description }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center rounded-lg border border-brand/30 bg-brand-soft/50 px-3 py-1.5 text-xs font-bold text-brand">
                                Rata-rata: {{ $avg !== null ? number_format($avg, 2) : '—' }}
                            </span>
                        </div>
                    </div>

                    {{-- 2. Rincian CPMK Penyusun --}}
                    <div class="space-y-2 text-xs">
                        <span class="text-muted font-medium">Disusun dari CPMK:</span>
                        <div class="flex flex-wrap gap-2">
                            @forelse($card['contributing_cpmks'] as $cItem)
                                <div class="inline-flex items-center gap-2 rounded bg-canvas px-3 py-1.5 font-mono text-xs border border-line">
                                    <span class="font-bold text-brand">{{ $cItem['cpmk']->code }}</span>
                                    <span class="text-muted">bobot kontribusi {{ $cItem['weight_formatted'] }}</span>
                                    <span class="text-muted/40">·</span>
                                    <span class="text-ink">rata-rata kelas <strong>{{ $cItem['class_average'] !== null ? number_format($cItem['class_average'], 2) : '—' }}</strong></span>
                                </div>
                            @empty
                                <span class="text-rose-600 italic">Belum ada CPMK yang terhubung ke CPL ini.</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- 3. Agregat Kelas CPL --}}
                    <div class="flex flex-wrap items-center gap-4 text-xs py-2 border-y border-line/60 bg-canvas/30 px-3 rounded-lg">
                        <div>
                            <span class="text-muted">Tuntas (≥{{ $threshold }}):</span>
                            <strong class="font-mono ml-1 {{ $rate !== null && $rate >= 75 ? 'text-emerald-700' : 'text-ink' }}">
                                {{ $rate !== null ? $rate . '%' : '—' }}
                            </strong>
                            <span class="text-muted text-[11px]">({{ $agg['pass_count'] }} dari {{ $agg['graded_count'] }} dinilai)</span>
                        </div>
                        <span class="text-muted/30">|</span>
                        <div>
                            <span class="text-muted">Ambang Standar:</span>
                            <strong class="font-mono text-ink ml-1">{{ $threshold }}</strong>
                        </div>
                        @if($pred)
                            <span class="text-muted/30">|</span>
                            <div class="flex items-center gap-1">
                                <span class="text-muted">Predikat:</span>
                                <span class="rounded border px-2 py-0.5 text-[11px] font-semibold {{ $obe->predicateBadgeClass($pred) }}">
                                    {{ $pred }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- 4. Tabel Nilai CPL per Mahasiswa --}}
                    @if(!$hasCpmk)
                        <div class="rounded-lg border border-dashed border-line bg-canvas/50 p-4 text-xs text-muted">
                            Belum ada pemetaan CPMK yang menyusun CPL ini pada kurikulum mata kuliah.
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-lg border border-line">
                            <table class="admin-table text-xs">
                                <thead>
                                    <tr class="bg-canvas/60">
                                        <th class="w-12 text-center">No</th>
                                        <th class="w-28">NIM</th>
                                        <th>Nama Mahasiswa</th>
                                        @foreach($card['contributing_cpmks'] as $cItem)
                                            <th class="text-center font-mono">
                                                {{ $cItem['cpmk']->code }}
                                                <div class="text-[10px] font-normal text-muted">Kontribusi: {{ $cItem['weight_formatted'] }}</div>
                                            </th>
                                        @endforeach
                                        <th class="text-center bg-brand-soft/40 text-brand font-bold">
                                            Nilai {{ $cpl->code }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($card['students'] as $idx => $sRow)
                                        @php
                                            $cplScore = $sRow['cpl_score'];
                                        @endphp
                                        <tr class="hover:bg-canvas/30 transition">
                                            <td class="text-center text-muted font-mono">{{ $idx + 1 }}</td>
                                            <td class="font-mono text-muted">{{ $sRow['student']->nim_nidn ?? '—' }}</td>
                                            <td class="font-medium text-ink">{{ $sRow['student']->name }}</td>
                                            @foreach($card['contributing_cpmks'] as $cItem)
                                                @php
                                                    $sc = $sRow['cpmk_scores'][$cItem['cpmk']->id] ?? null;
                                                @endphp
                                                <td class="text-center font-mono">
                                                    {{ $sc !== null ? number_format($sc, 1) : '—' }}
                                                </td>
                                            @endforeach
                                            <td class="text-center font-mono font-bold bg-brand-soft/10">
                                                @if($cplScore !== null)
                                                    <span class="{{ $cplScore >= $threshold ? 'text-ink' : 'text-rose-600' }}">
                                                        {{ number_format($cplScore, 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted/40">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 4 + count($card['contributing_cpmks']) }}" class="text-center py-4 text-muted">
                                                Belum ada mahasiswa terdaftar di kelas ini.
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

        {{-- Catatan Penting Evaluasi CPL --}}
        <div class="rounded-xl border border-line bg-surface p-4 text-xs text-muted space-y-1.5">
            <div class="font-semibold text-ink flex items-center gap-1.5">
                <svg class="h-4 w-4 text-brand" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>Catatan Ketercapaian CPL:</span>
            </div>
            <p>
                Angka capaian CPL di atas merupakan kontribusi yang dihasilkan dari satu mata kuliah ini (<strong>{{ $section->mataKuliah->code }} · {{ $section->mataKuliah->name }}</strong>). Ketercapaian CPL program studi yang sesungguhnya dihitung secara komprehensif lintas mata kuliah di level Program Studi.
            </p>
        </div>
    @endif
</div>
@endsection
