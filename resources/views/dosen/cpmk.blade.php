@extends('layouts.mahasiswa')

@section('title', 'Rekap CPMK | SALE')
@section('header', 'Rekap CPMK')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="section-heading">Rekap Capaian CPMK</h2>
            <p class="mt-0.5 text-xs text-muted">Capaian per mahasiswa terhadap CPMK mata kuliah ini.</p>
        </div>
        @if(!$cpmks->isEmpty())
        <div>
            <a href="{{ route('dosen.penilaian.cpmk.export', $section->id) }}" class="button-secondary text-xs inline-flex items-center gap-1.5" title="Ekspor CPMK ke Excel/CSV">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Ekspor Excel/CSV
            </a>
        </div>
        @endif
    </div>

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
