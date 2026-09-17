@extends('layouts.mahasiswa')

@section('title', 'Input Nilai | ' . $section->display_code . ' | SALE')
@section('header', 'Input Nilai')

@section('content')
<div class="space-y-5">
    @include('dosen.partials.header')

    <div>
        <h2 class="section-heading">2. Input Nilai per Komponen Asesmen</h2>
        <p class="mt-1 text-sm text-muted">Pilih komponen asesmen untuk mengisi nilai mahasiswa.</p>
    </div>

    @if($assessments->isEmpty())
        <div class="surface p-12 text-center space-y-3">
            <h3 class="text-base font-semibold text-ink">Belum Ada Komponen Asesmen</h3>
            <p class="text-sm text-muted">Atur bobot komponen terlebih dahulu pada matriks penilaian.</p>
            <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-primary text-xs inline-flex">Ke Langkah 1: Matriks</a>
        </div>
    @else
        @php $obeService = $obe ?? app(\App\Services\ObeCalculationService::class); @endphp
        <div class="surface overflow-x-auto rounded-xl border border-line">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/40 text-muted font-medium">
                        <th class="w-10 py-3 px-3 text-center">#</th>
                        <th class="py-3 px-3 w-24">Kode</th>
                        <th class="py-3 px-3 min-w-[140px]">Nama Asesmen</th>
                        <th class="py-3 px-3 w-20">Jenis</th>
                        <th class="py-3 px-3 text-center w-20">Bobot</th>
                        <th class="py-3 px-3 min-w-[200px]">CPMK yang Diukur</th>
                        <th class="py-3 px-3 text-right w-28"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    @foreach($assessments as $idx => $assessment)
                        <tr class="hover:bg-canvas/20 transition-colors">
                            <td class="py-3 px-3 text-center text-muted/60">{{ $idx + 1 }}</td>
                            <td class="py-3 px-3 font-semibold text-brand">{{ $assessment->code }}</td>
                            <td class="py-3 px-3 font-semibold text-ink text-sm">{{ $assessment->name }}</td>
                            <td class="py-3 px-3 text-muted capitalize">{{ $assessment->type }}</td>
                            <td class="py-3 px-3 text-center font-semibold text-ink">{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}%</td>
                            <td class="py-3 px-3 text-muted">
                                @if($assessment->cpmks->count())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($assessment->cpmks as $cpmk)
                                            @php $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk); @endphp
                                            <span class="inline-flex items-center gap-1 rounded bg-canvas px-2 py-0.5 text-xs font-medium text-ink border border-line/60">
                                                <span>{{ $cpmk->code }}</span>
                                                <span class="text-muted font-normal">({{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}%)</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="italic text-muted/60">Belum dipetakan ke CPMK</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="button-primary text-xs py-1.5 px-3">
                                    Input Nilai
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
