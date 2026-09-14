@extends('layouts.mahasiswa')

@section('title', 'Rekap CPL | SALE')
@section('header', 'Rekap CPL')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    @if($cpls->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada CPL yang terhubung</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">Petakan CPL ke CPMK mata kuliah ini di tab Pengaturan Penilaian.</p>
        </div>
    @else
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        @foreach($cpls as $cpl)
                            <th class="text-center" title="{{ $cpl->description }}">{{ $cpl->code }}</th>
                        @endforeach
                        <th>Status Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $anyNull = collect($row['scores'])->contains(null);
                            $allAchieved = ! $anyNull && collect($row['scores'])->every(fn ($s) => $s >= 65);
                        @endphp
                        <tr>
                            <td class="font-medium text-ink">{{ $row['student']->name }}</td>
                            @foreach($cpls as $cpl)
                                @php $score = $row['scores'][$cpl->id]; @endphp
                                <td class="text-center">{{ $score !== null ? number_format($score, 1) : '—' }}</td>
                            @endforeach
                            <td>
                                @if($anyNull)
                                    <span class="status bg-canvas text-muted">Belum lengkap</span>
                                @elseif($allAchieved)
                                    <span class="status bg-brand-soft text-brand">Tercapai</span>
                                @else
                                    <span class="status bg-amber-50 text-amber-700">Belum Tercapai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 2 + $cpls->count() }}" class="text-center text-muted py-8">Belum ada mahasiswa terdaftar di kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Status "Tercapai" memerlukan seluruh CPL bernilai minimal 65 dan seluruh asesmen pembentuknya sudah dinilai.</p>
    @endif
</div>
@endsection
