@extends('layouts.mahasiswa')

@section('title', 'Rekap CPMK | SALE')
@section('header', 'Rekap CPMK')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    @if($cpmks->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada CPMK</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">Tambahkan CPMK untuk mata kuliah ini di tab Pengaturan Penilaian.</p>
        </div>
    @else
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        @foreach($cpmks as $cpmk)
                            <th class="text-center" title="{{ $cpmk->description }}">{{ $cpmk->code }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="font-medium text-ink">{{ $row['student']->name }}</td>
                            @foreach($cpmks as $cpmk)
                                @php $score = $row['scores'][$cpmk->id]; @endphp
                                <td class="text-center">
                                    @if($score === null)
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="{{ $score >= $cpmk->threshold ? 'text-brand font-semibold' : 'text-ink' }}">{{ number_format($score, 1) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ 1 + $cpmks->count() }}" class="text-center text-muted py-8">Belum ada mahasiswa terdaftar di kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Nilai berwarna menunjukkan CPMK telah mencapai ambang batas (threshold) yang ditentukan. Tanda "—" berarti belum ada asesmen pembentuk CPMK ini yang dinilai.</p>
    @endif
</div>
@endsection
