@extends('layouts.mahasiswa')

@section('title', 'Rekap Keseluruhan | SALE')
@section('header', 'Rekap Keseluruhan')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    @if($cpls->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada CPL yang terhubung</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Petakan CPL ke CPMK mata kuliah ini di tab Pengaturan Penilaian agar rekap dapat dihitung.
            </p>
        </div>
    @else
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIM</th>
                        <th>Nama</th>
                        @foreach($cpls as $cpl)
                            <th>{{ $cpl->code }}</th>
                        @endforeach
                        <th>Nilai Akhir</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['student']->nim_nidn ?? '—' }}</td>
                            <td class="font-medium text-ink">{{ $row['student']->name }}</td>
                            @foreach($cpls as $cpl)
                                <td>{{ $row['cpl_scores'][$cpl->id] !== null ? number_format($row['cpl_scores'][$cpl->id], 1) : '—' }}</td>
                            @endforeach
                            <td class="font-semibold text-ink">
                                {{ $row['final_score'] !== null ? number_format($row['final_score'], 1) : '—' }}
                                @if($row['coverage'] < 100)
                                    <span class="block text-[11px] font-normal text-muted">{{ $row['coverage'] }}% dinilai</span>
                                @endif
                            </td>
                            <td class="font-semibold text-ink">{{ $row['grade'] ?? '—' }}</td>
                            <td>
                                @if($row['coverage'] < 100)
                                    <span class="status bg-amber-50 text-amber-700">Provisional</span>
                                @elseif($row['final_score'] !== null)
                                    <span class="status bg-brand-soft text-brand">Final</span>
                                @else
                                    <span class="status bg-canvas text-muted">Belum dinilai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 6 + $cpls->count() }}" class="text-center text-muted py-8">Belum ada mahasiswa terdaftar di kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Nilai Akhir dihitung dari bobot masing-masing asesmen terhadap nilai akhir mata kuliah, bukan dari rata-rata CPL. "Provisional" berarti belum seluruh asesmen dinilai.</p>
    @endif
</div>
@endsection
