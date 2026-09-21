@extends('layouts.mahasiswa')

@section('title', 'Daftar Asesmen | SALE')
@section('header', 'Daftar Asesmen')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex items-center justify-between">
        <div>
            <h2 class="section-heading">2. Input Nilai per Komponen Asesmen</h2>
            <p class="mt-1 text-sm text-muted">Pilih instrumen penilaian untuk menginput nilai mahasiswa per CPMK yang diukur.</p>
        </div>
    </div>

    @php
        $totalFinalWeight = round((float) $assessments->sum('final_weight'), 2);
        $formattedTotal = rtrim(rtrim(number_format($totalFinalWeight, 2), '0'), '.');
        $isComplete = $assessments->isNotEmpty() && abs($totalFinalWeight - 100.0) < 0.01;
        $isOver = $totalFinalWeight > 100.0 && !$isComplete;
        $diff = round(abs($totalFinalWeight - 100.0), 2);
        $diffFormatted = rtrim(rtrim(number_format($diff, 2), '0'), '.');
    @endphp

    @if($assessments->isNotEmpty() && ! $isComplete)
        <div class="rounded-xl border {{ $isOver ? 'border-rose-200 bg-rose-50/70 text-rose-900' : 'border-amber-200 bg-amber-50/70 text-amber-900' }} p-4 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-2.5">
                <svg class="h-4 w-4 shrink-0 mt-0.5 {{ $isOver ? 'text-rose-600' : 'text-amber-700' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <span class="font-semibold text-ink">
                        {{ $isOver ? 'Bobot Matriks Melebihi 100% (' . $formattedTotal . '%)' : 'Bobot Matriks Belum Lengkap (' . $formattedTotal . '% / 100%)' }}
                    </span>
                    <p class="mt-0.5 text-muted leading-relaxed">
                        Input nilai dinonaktifkan sementara. {{ $isOver ? 'Terdapat kelebihan bobot +' . $diffFormatted . '%.' : 'Terdapat kekurangan bobot -' . $diffFormatted . '%.' }} Sesuaikan dan simpan matriks tepat 100% untuk membuka pengisian nilai mahasiswa.
                    </p>
                </div>
            </div>
            <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="inline-flex items-center justify-center gap-1.5 shrink-0 px-3 py-1.5 rounded-lg border border-line bg-white text-xs font-semibold text-ink hover:border-ink transition-colors shadow-2xs">
                <span>Atur Matriks Penilaian</span>
                <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @endif

    @if($assessments->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum Ada Komponen Asesmen</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Komponen penilaian disusun dari RPS pada matriks penilaian kelas. Silakan periksa atau atur bobot komponen terlebih dahulu.
            </p>
            <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-primary text-xs mt-4 inline-flex">
                Buka Langkah 1: Matriks Penilaian
            </a>
        </div>
    @else
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Asesmen</th>
                        <th>Jenis</th>
                        <th>Bobot Nilai Akhir</th>
                        <th>CPMK yang Diukur</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessments as $assessment)
                        <tr>
                            <td class="font-mono text-xs text-muted">{{ $assessment->code }}</td>
                            <td class="font-medium text-ink">{{ $assessment->name }}</td>
                            <td class="capitalize">{{ $assessment->type }}</td>
                            <td>{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}%</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @php $obeService = $obe ?? app(\App\Services\ObeCalculationService::class); @endphp
                                    @forelse($assessment->cpmks as $cpmk)
                                        @php $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk); @endphp
                                        <span class="status bg-brand-soft text-brand font-medium">
                                            {{ $cpmk->code }} (Bobot: {{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}%)
                                        </span>
                                    @empty
                                        <span class="text-xs text-muted">Belum dipetakan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if($assessment->status === 'published')
                                    <span class="status bg-brand-soft text-brand">Published</span>
                                @elseif($assessment->status === 'closed')
                                    <span class="status bg-canvas text-muted">Closed</span>
                                @else
                                    <span class="status bg-amber-50 text-amber-700">Draft</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if($isComplete)
                                    <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="inline-flex items-center rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-dark transition shadow-2xs">
                                        Input Nilai
                                    </a>
                                @else
                                    <button type="button" disabled
                                            title="Input nilai belum dibuka. Total bobot matriks penilaian harus tepat 100% (saat ini {{ $formattedTotal }}%)."
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-canvas/70 px-3 py-1.5 text-xs font-medium text-muted/60 cursor-not-allowed select-none">
                                        <svg class="w-3 h-3 text-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span>Input Nilai</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-canvas/40 border-t border-line font-medium text-xs">
                        <td colspan="3" class="text-right text-muted uppercase tracking-wider font-semibold py-3 px-4">
                            Total Bobot Nilai Akhir:
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold {{ $isComplete ? 'text-brand' : ($isOver ? 'text-rose-700' : 'text-amber-800') }}">
                                {{ $formattedTotal }}%
                            </span>
                            @if($isComplete)
                                <span class="status bg-emerald-50 text-emerald-700 border-emerald-200 ml-1.5">✓ Lengkap (100%)</span>
                            @elseif($isOver)
                                <span class="status bg-rose-50 text-rose-700 border-rose-200 ml-1.5">Kelebihan +{{ $diffFormatted }}%</span>
                            @else
                                <span class="status bg-amber-50 text-amber-800 border-amber-200 ml-1.5">Kurang -{{ $diffFormatted }}%</span>
                            @endif
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-xs text-muted">Klik <strong>"Input Nilai"</strong> untuk mengisi nilai mahasiswa per CPMK yang diukur. Pengaturan pembobotan dilakukan pada <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="text-brand hover:underline font-medium">Langkah 1: Matriks Penilaian</a>.</p>
    @endif
</div>
@endsection
