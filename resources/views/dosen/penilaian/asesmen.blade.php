@extends('layouts.mahasiswa')

@section('title', 'Daftar Asesmen | SALE')
@section('header', 'Daftar Asesmen')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex items-center justify-between">
        <div>
            <h2 class="section-heading">Asesmen</h2>
            <p class="mt-1 text-sm text-muted">Kelola daftar asesmen beserta pemetaan CPMK yang diukurnya.</p>
        </div>
        <a href="{{ route('dosen.penilaian.asesmen.create', $section->id) }}" class="button-primary text-xs">+ Tambah Asesmen</a>
    </div>

    @if($assessments->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada asesmen</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">Asesmen untuk kelas ini akan muncul di sini setelah dibuat.</p>
            <a href="{{ route('dosen.penilaian.asesmen.create', $section->id) }}" class="button-primary text-xs mt-4 inline-flex">Tambah Asesmen</a>
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
                                    @forelse($assessment->cpmks as $cpmk)
                                        <span class="status bg-brand-soft text-brand">{{ $cpmk->code }} ({{ rtrim(rtrim(number_format($cpmk->pivot->weight, 1), '0'), '.') }}%)</span>
                                    @empty
                                        <span class="text-xs text-muted">Belum dipetakan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ $assessment->uses_rubric ? 'Ya' : '—' }}</td>
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
                                <a href="{{ route('dosen.penilaian.asesmen.edit', [$section->id, $assessment->id]) }}" class="quiet-link text-xs">Ubah</a>
                                <form method="post" action="{{ route('dosen.penilaian.asesmen.destroy', [$section->id, $assessment->id]) }}" class="inline" onsubmit="return confirm('Hapus asesmen &quot;{{ $assessment->name }}&quot;? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="quiet-link text-xs text-danger ml-3">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Pengaturan rubrik akan tersedia pada tahap berikutnya.</p>
    @endif
</div>
@endsection
