@extends('layouts.mahasiswa')

@section('title', 'Daftar Asesmen | SALE')
@section('header', 'Daftar Asesmen')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    @if($assessments->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada asesmen</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto mb-4">Asesmen untuk kelas ini akan muncul di sini setelah dibuat.</p>
            @if(isset($section))
                <a href="{{ route('dosen.item.create', $section->id) }}" class="button-primary inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-2">
                    <span>+ Tambah Konten / Asesmen</span>
                </a>
            @endif
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
                        <th>Rubrik</th>
                        <th>Status</th>
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
                                    @forelse($assessment->cpmks as $cpmk)
                                        <span class="status bg-brand-soft text-brand">{{ $cpmk->code }} ({{ rtrim(rtrim(number_format($cpmk->pivot->weight, 1), '0'), '.') }}%)</span>
                                    @empty
                                        <span class="text-xs text-muted">Belum dipetakan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ $assessment->uses_rubric ? 'Ya' : 'Tidak' }}</td>
                            <td>
                                @if($assessment->status === 'published')
                                    <span class="text-xs font-semibold text-emerald-800">Published</span>
                                @elseif($assessment->status === 'closed')
                                    <span class="text-xs font-semibold text-muted">Closed</span>
                                @else
                                    <span class="text-xs font-semibold text-ink">Draft</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Form untuk menambah dan mengedit asesmen (termasuk pengaturan rubrik) akan tersedia pada tahap berikutnya.</p>
    @endif
</div>
@endsection
