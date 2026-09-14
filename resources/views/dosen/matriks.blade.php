@extends('layouts.mahasiswa')

@section('title', 'Matriks Penilaian | SALE')
@section('header', 'Matriks Penilaian')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div>
        <h2 class="section-heading">Matriks Asesmen × CPMK</h2>
        <p class="mt-1 text-sm text-muted">Menunjukkan CPMK mana yang diukur oleh setiap asesmen, beserta bobot kontribusinya.</p>
    </div>

    @if($assessments->isEmpty() || $cpmks->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada asesmen atau CPMK</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">Tambahkan asesmen dan petakan CPMK terlebih dahulu di tab Asesmen dan Pengaturan Penilaian.</p>
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-primary text-xs mt-4 inline-flex">Tambah Asesmen</a>
        </div>
    @else
        <div class="surface overflow-x-auto p-1">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Asesmen</th>
                        @foreach($cpmks as $cpmk)
                            <th class="text-center" title="{{ $cpmk->description }}">{{ $cpmk->code }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessments as $assessment)
                        <tr>
                            <td class="font-medium text-ink">{{ $assessment->name }}</td>
                            @foreach($cpmks as $cpmk)
                                @php
                                    $pivot = $assessment->cpmks->firstWhere('id', $cpmk->id);
                                @endphp
                                <td class="text-center">
                                    @if($pivot)
                                        <span class="status bg-brand-soft text-brand">{{ rtrim(rtrim(number_format($pivot->pivot->weight, 1), '0'), '.') }}%</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Persentase menunjukkan kontribusi asesmen tersebut terhadap CPMK yang bersangkutan (dinormalisasi 100% per CPMK).</p>
    @endif
</div>
@endsection
