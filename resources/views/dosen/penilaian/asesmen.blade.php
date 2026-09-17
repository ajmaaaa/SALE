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
                                <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="inline-flex items-center rounded bg-brand px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-dark transition shadow-sm">
                                    Input Nilai
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Klik <strong>"Input Nilai"</strong> untuk mengisi nilai mahasiswa per CPMK yang diukur. Pengaturan pembobotan dilakukan pada <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="text-brand hover:underline font-medium">Langkah 1: Matriks Penilaian</a>.</p>
    @endif
</div>
@endsection
