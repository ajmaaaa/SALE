@extends('layouts.mahasiswa')

@section('title', 'Rekap Capaian CPMK | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian per CPMK')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="section-heading">3. Rekap Capaian per CPMK</h2>
            <p class="text-sm text-muted">
                Setiap CPMK disajikan secara mandiri beserta rincian komponen instrumen penilaian RPS yang menyusunnya.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dosen.penilaian.export.cpmk', $section->id) }}" class="button-secondary text-xs">
                Export CSV CPMK
            </a>
        </div>
    </header>

    @if($cpmks->isEmpty())
        <div class="surface p-10 text-center">
            <h3 class="section-heading">Belum Ada CPMK untuk Mata Kuliah Ini</h3>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                CPMK disusun dari kurikulum mata kuliah prodi. Hubungi pengelola kurikulum prodi agar CPMK dapat dipetakan.
            </p>
        </div>
    @else
        <div class="space-y-6">
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

                <section class="surface p-5 sm:p-6 space-y-4 rounded-xl border border-line">
                    {{-- 1. Header Kartu: Kode, Deskripsi, Bobot --}}
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 border-b border-line pb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-bold text-brand bg-brand-soft px-2.5 py-0.5 rounded">
                                    {{ $cpmk->code }}
                                </span>
                                <h3 class="text-base font-bold text-ink">
                                    {{ $cpmk->code }}
                                </h3>
                            </div>
                            <p class="text-sm text-ink/80 leading-relaxed max-w-3xl">
                                {{ $cpmk->description }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            @if($hasWeight)
                                <span class="inline-flex items-center rounded-lg border border-brand/30 bg-brand-soft/50 px-3 py-1.5 text-xs font-bold text-brand">
                                    Bobot: {{ $card['weight_formatted'] }}%
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">
                                    Belum Ada Bobot
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Rincian Bobot Komponen Penyusun --}}
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-muted font-medium">Disusun dari:</span>
                        @forelse($card['components'] as $comp)
                            <span class="inline-flex items-center gap-1 rounded bg-canvas px-2.5 py-1 font-mono text-ink border border-line font-medium">
                                {{ $comp['assessment']->name }} ({{ $comp['weight_formatted'] }}%)
                            </span>
                            @if(!$loop->last)
                                <span class="text-muted/50 font-bold">·</span>
                            @endif
                        @empty
                            <span class="text-muted italic">Belum terhubung ke komponen asesmen di Langkah 1: Matriks Penilaian</span>
                        @endforelse
                    </div>

                    {{-- 3. Agregat Kelas --}}
                    <div class="flex flex-wrap items-center gap-4 text-xs py-2 border-y border-line/60 bg-canvas/30 px-3 rounded-lg">
                        <div>
                            <span class="text-muted">Rata-rata:</span>
                            <strong class="font-mono text-ink text-sm ml-1">{{ $avg !== null ? number_format($avg, 2) : '—' }}</strong>
                        </div>
                        <span class="text-muted/30">|</span>
                        <div>
                            <span class="text-muted">Tuntas (≥{{ (int)$threshold }}):</span>
                            <strong class="font-mono ml-1 {{ $rate !== null && $rate >= 75 ? 'text-emerald-700' : 'text-ink' }}">
                                {{ $rate !== null ? $rate . '%' : '—' }}
                            </strong>
                            <span class="text-muted text-[11px]">({{ $agg['pass_count'] }} dari {{ $agg['graded_count'] }} dinilai)</span>
                        </div>
                        <span class="text-muted/30">|</span>
                        <div>
                            <span class="text-muted">Ambang:</span>
                            <strong class="font-mono text-ink ml-1">{{ (int)$threshold }}</strong>
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

                    {{-- 4. Tabel Mahasiswa Khusus CPMK Ini --}}
                    @if(!$hasWeight)
                        <div class="rounded-lg border border-dashed border-amber-200 bg-amber-50/40 p-4 text-xs text-amber-800 flex items-center justify-between">
                            <span>CPMK ini belum dialokasikan bobot pada matriks asesmen kelas.</span>
                            <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-secondary text-xs">
                                Atur di Matriks Penilaian
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-lg border border-line">
                            <table class="admin-table text-xs">
                                <thead>
                                    <tr class="bg-canvas/60">
                                        <th class="w-12 text-center">No</th>
                                        <th class="w-28">NIM</th>
                                        <th>Nama Mahasiswa</th>
                                        @foreach($card['components'] as $comp)
                                            <th class="text-center font-mono">
                                                {{ $comp['assessment']->name }}
                                                <div class="text-[10px] font-normal text-muted">
                                                    Maks: {{ (int)($comp['max_score'] ?? 100) }} · Bobot: {{ $comp['weight_formatted'] }}%
                                                </div>
                                            </th>
                                        @endforeach
                                        <th class="text-center bg-brand-soft/40 text-brand font-bold">
                                            Nilai {{ $cpmk->code }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($card['students'] as $idx => $sRow)
                                        @php
                                            $cScore = $sRow['cpmk_score'];
                                        @endphp
                                        <tr class="hover:bg-canvas/30 transition">
                                            <td class="text-center text-muted font-mono">{{ $idx + 1 }}</td>
                                            <td class="font-mono text-muted">{{ $sRow['student']->nim_nidn ?? '—' }}</td>
                                            <td class="font-medium text-ink">{{ $sRow['student']->name }}</td>
                                            @foreach($card['components'] as $comp)
                                                @php
                                                    $sc = $sRow['scores'][$comp['assessment']->id] ?? null;
                                                @endphp
                                                <td class="text-center font-mono">
                                                    {{ $sc !== null ? number_format($sc, 1) : '—' }}
                                                </td>
                                            @endforeach
                                            <td class="text-center font-mono font-bold bg-brand-soft/10">
                                                @if($cScore !== null)
                                                    <span class="{{ $cScore >= $threshold ? 'text-ink' : 'text-rose-600' }}">
                                                        {{ number_format($cScore, 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted/40">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 4 + count($card['components']) }}" class="text-center py-4 text-muted">
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
    @endif
</div>
@endsection
