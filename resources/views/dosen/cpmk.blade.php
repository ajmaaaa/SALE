@extends('layouts.mahasiswa')

@section('title', 'Rekap Capaian CPMK | ' . $section->display_code . ' | SALE')
@section('header', 'Rekap Capaian CPMK')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="section-heading">Rekap Capaian CPMK Mahasiswa</h2>
            <p class="text-sm text-muted">
                Perhitungan nilai CPMK per mahasiswa dihitung dari rata-rata tertimbang seluruh instrumen asesmen pembentuknya sesuai formula OBE.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dosen.penilaian.export.cpmk', $section->id) }}" class="button-secondary text-xs">
                ⬇ Export CSV
            </a>
        </div>
    </header>

    @if($cpmks->isEmpty())
        <div class="surface p-10 text-center">
            <h3 class="section-heading">Belum Ada CPMK Terhubung</h3>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">Tambahkan pemetaan CPMK untuk mata kuliah ini di tab Pengaturan Penilaian.</p>
        </div>
    @else
        {{-- Ringkasan Agregat Kelas per CPMK (Level 3 OBE: Rata-rata & % Mahasiswa Tuntas) --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach($cpmks as $cpmk)
                @php
                    $agg = $aggregates[$cpmk->id] ?? null;
                    $avg = $agg['average'] ?? null;
                    $rate = $agg['pass_rate'] ?? null;
                    $pred = $agg['predicate'] ?? null;
                    $cWeight = $cpmkWeights[$cpmk->id] ?? 0;
                @endphp
                <div class="surface p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <span class="font-mono text-xs font-bold text-brand bg-brand-soft px-2 py-0.5 rounded">{{ $cpmk->code }}</span>
                            <div class="flex items-center gap-1">
                                <span class="rounded border px-1.5 py-0.5 text-[10px] font-semibold bg-brand-soft/50 text-brand">
                                    {{ rtrim(rtrim(number_format($cWeight, 1), '0'), '.') }}%
                                </span>
                                @if($pred)
                                    <span class="rounded border px-1.5 py-0.5 text-[10px] font-semibold {{ $obe->predicateBadgeClass($pred) }}">
                                        {{ $pred }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-muted line-clamp-2" title="{{ $cpmk->description }}">
                            {{ $cpmk->description }}
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-line/60">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-muted">Rata-rata:</span>
                            <span class="font-mono text-base font-bold text-ink">
                                {{ $avg !== null ? number_format($avg, 1) : '—' }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-baseline justify-between text-xs">
                            <span class="text-muted">Tuntas (≥{{ (int)($cpmk->threshold ?: 60) }}):</span>
                            <span class="font-semibold {{ $rate !== null && $rate >= 75 ? 'text-emerald-700' : 'text-ink' }}">
                                {{ $rate !== null ? $rate . '%' : '—' }}
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-canvas overflow-hidden">
                            <div class="h-full bg-brand rounded-full transition-all" style="width: {{ $rate ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tabel Capaian CPMK per Mahasiswa --}}
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        @foreach($cpmks as $cpmk)
                            @php $cWeight = $cpmkWeights[$cpmk->id] ?? 0; @endphp
                            <th class="text-center min-w-[110px]">
                                <div class="font-mono font-bold text-brand">{{ $cpmk->code }}</div>
                                <div class="text-[10px] font-semibold text-brand/80">Bobot: {{ rtrim(rtrim(number_format($cWeight, 1), '0'), '.') }}%</div>
                                <div class="text-[9px] font-normal text-muted">Ambang: {{ (int)($cpmk->threshold ?: 60) }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                        <tr>
                            <td class="text-muted font-mono text-xs">{{ $i + 1 }}</td>
                            <td class="font-mono text-xs text-muted">{{ $row['student']->nim_nidn ?? '—' }}</td>
                            <td class="font-medium text-ink">{{ $row['student']->name }}</td>
                            @foreach($cpmks as $cpmk)
                                @php
                                    $score = $row['scores'][$cpmk->id];
                                    $predicate = $row['predicates'][$cpmk->id];
                                    $threshold = (float)($cpmk->threshold ?: 60);
                                    $isPassed = $score !== null && $score >= $threshold;
                                @endphp
                                <td class="text-center">
                                    @if($score === null)
                                        <span class="text-muted font-mono">—</span>
                                    @else
                                        <div class="inline-flex flex-col items-center">
                                            <span class="font-mono font-bold text-sm {{ $isPassed ? 'text-ink' : 'text-rose-600' }}">
                                                {{ number_format($score, 1) }}
                                            </span>
                                            <span class="mt-0.5 rounded border px-1.5 py-0.2 text-[9px] font-medium {{ $obe->predicateBadgeClass($predicate) }}">
                                                {{ $predicate }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + $cpmks->count() }}" class="text-center text-muted py-8">
                                Belum ada mahasiswa terdaftar di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- Baris Rata-rata & Bobot Kelas --}}
                @if($rows->isNotEmpty())
                    <tfoot>
                        <tr class="bg-brand-soft/20 font-semibold border-t-2 border-line text-xs">
                            <td colspan="3" class="text-right uppercase tracking-wider text-brand font-bold pr-4">
                                Bobot CPMK Penilaian
                            </td>
                            @foreach($cpmks as $cpmk)
                                @php $cWeight = $cpmkWeights[$cpmk->id] ?? 0; @endphp
                                <td class="text-center font-mono font-bold text-brand">
                                    {{ rtrim(rtrim(number_format($cWeight, 1), '0'), '.') }}%
                                </td>
                            @endforeach
                        </tr>
                        <tr class="bg-canvas/80 font-semibold border-t border-line/60">
                            <td colspan="3" class="text-right text-xs uppercase tracking-wider text-ink font-bold pr-4">
                                Rata-rata Kelas
                            </td>
                            @foreach($cpmks as $cpmk)
                                @php $agg = $aggregates[$cpmk->id] ?? null; @endphp
                                <td class="text-center font-mono text-sm font-bold text-ink">
                                    {{ $agg['average'] !== null ? number_format($agg['average'], 1) : '—' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr class="bg-canvas/80 font-semibold">
                            <td colspan="3" class="text-right text-xs uppercase tracking-wider text-muted pr-4">
                                % Mahasiswa Tuntas
                            </td>
                            @foreach($cpmks as $cpmk)
                                @php $agg = $aggregates[$cpmk->id] ?? null; @endphp
                                <td class="text-center font-mono text-xs font-semibold {{ $agg['pass_rate'] !== null && $agg['pass_rate'] >= 75 ? 'text-emerald-700' : 'text-muted' }}">
                                    {{ $agg['pass_rate'] !== null ? $agg['pass_rate'] . '%' : '—' }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Penjelasan Rumus Sesuai Dokumen Desain --}}
        <div class="rounded-xl border border-line bg-surface p-4 text-xs text-muted space-y-2">
            <div class="font-semibold text-ink flex items-center gap-1.5">
                <svg class="h-4 w-4 text-brand" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>Panduan Perhitungan Ketercapaian CPMK (OBE):</span>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <div>
                    <span class="font-semibold text-ink">Rumus CPMK Mahasiswa:</span>
                    <p class="font-mono mt-0.5 text-[11px] bg-canvas p-1.5 rounded">NCPMK(k) = Σ(Bobot Asesmen × Nilai Asesmen) / Σ(Bobot Asesmen)</p>
                </div>
                <div>
                    <span class="font-semibold text-ink">Kategori Predikat Ketercapaian:</span>
                    <p class="mt-0.5 text-[11px]">≥85: Sangat Baik · 70–84.9: Baik · 60–69.9: Cukup · &lt;60: Kurang</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
