@extends('layouts.mahasiswa')

@section('title', 'Rekap Keseluruhan | SALE')
@section('header', 'Rekap Keseluruhan')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="section-heading">Rekap Nilai &amp; Capaian CPL</h2>
            <p class="mt-0.5 text-xs text-muted">Rekap agregasi nilai akhir dan ketercapaian CPL seluruh mahasiswa di kelas ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dosen.penilaian.rekap.export', $section->id) }}" class="button-secondary text-xs inline-flex items-center gap-1.5" title="Ekspor rekap ke Excel/CSV">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Ekspor Excel/CSV
            </a>
            <a href="{{ route('dosen.penilaian.rekap.print', $section->id) }}" target="_blank" class="button-secondary text-xs inline-flex items-center gap-1.5" title="Cetak atau unduh sebagai PDF">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Cetak / Unduh PDF
            </a>
        </div>
    </div>

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
