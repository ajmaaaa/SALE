@extends('layouts.mahasiswa')

@section('title', 'Pengaturan Penilaian | SALE')
@section('header', 'Pengaturan Penilaian')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="surface p-5">
        <h2 class="section-heading">Total Bobot Nilai Akhir</h2>
        <p class="mt-1 text-sm {{ $totalFinalWeight == 100 ? 'text-ink' : 'text-danger font-semibold' }}">
            {{ rtrim(rtrim(number_format($totalFinalWeight, 1), '0'), '.') }}% dari 100%
            @if($totalFinalWeight != 100)
                — bobot seluruh asesmen belum berjumlah 100%.
            @endif
        </p>
    </div>

    <div class="surface p-5">
        <h2 class="section-heading">Pemetaan CPL ↔ CPMK</h2>
        @if($cpls->isEmpty())
            <p class="mt-2 text-sm text-muted">Belum ada CPL yang terhubung ke CPMK mata kuliah ini.</p>
        @else
            <div class="mt-3 space-y-3">
                @foreach($cpls as $cpl)
                    <div class="rounded-lg border border-line/60 p-3">
                        <p class="text-sm font-semibold text-ink">{{ $cpl->code }} — {{ $cpl->description }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($cpl->cpmks as $cpmk)
                                <span class="status bg-brand-soft text-brand">{{ $cpmk->code }} ({{ rtrim(rtrim(number_format($cpmk->pivot->weight, 1), '0'), '.') }}%)</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="surface p-5">
        <h2 class="section-heading">Daftar CPMK Mata Kuliah</h2>
        @if($cpmks->isEmpty())
            <p class="mt-2 text-sm text-muted">Belum ada CPMK.</p>
        @else
            <div class="mt-3 divide-y divide-line/50">
                @foreach($cpmks as $cpmk)
                    <div class="py-2.5 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ $cpmk->code }}</p>
                            <p class="text-xs text-muted">{{ $cpmk->description }}</p>
                        </div>
                        <span class="text-xs text-muted whitespace-nowrap">Threshold {{ rtrim(rtrim(number_format($cpmk->threshold, 1), '0'), '.') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <p class="text-xs text-muted">Halaman ini saat ini bersifat tampilan saja. Form untuk menambah/mengubah CPL, CPMK, dan pemetaannya akan ditambahkan pada tahap berikutnya.</p>
</div>
@endsection
