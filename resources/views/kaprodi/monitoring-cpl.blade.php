@extends('layouts.mahasiswa')

@section('title', 'Monitoring Capaian CPL | SALE Kaprodi')
@section('header', 'Monitoring Capaian CPL')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="section-heading text-xl font-bold text-ink">Monitoring Capaian CPL Program Studi</h2>
        <p class="mt-1 text-xs text-muted">Pemantauan ketercapaian Capaian Pembelajaran Lulusan (CPL) untuk akreditasi dan standar evaluasi kurikulum OBE.</p>
    </div>

    @if($cplStats->isEmpty())
        <div class="surface p-12 text-center">
            <h3 class="text-base font-semibold text-ink">Belum Ada CPL</h3>
            <p class="mt-1 text-sm text-muted">Belum ada CPL yang didefinisikan untuk program studi ini di database.</p>
        </div>
    @else
        <!-- Overview Stats -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="surface p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total Butir CPL</p>
                <p class="mt-2 text-2xl font-bold text-ink">{{ $cplStats->count() }}</p>
                <p class="mt-1 text-xs text-muted">Standar kompetensi lulusan prodi</p>
            </div>
            <div class="surface p-5">
                @php
                    $achievedCount = $cplStats->filter(fn($c) => $c['percent_achieved'] >= 75)->count();
                @endphp
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">CPL Memenuhi Standar Mutu</p>
                <p class="mt-2 text-2xl font-bold text-brand">{{ $achievedCount }} <span class="text-xs font-normal text-muted">/ {{ $cplStats->count() }}</span></p>
                <p class="mt-1 text-xs text-muted">&ge; 75% mahasiswa mencapai batas kelulusan</p>
            </div>
            <div class="surface p-5">
                @php
                    $overallAvg = $cplStats->filter(fn($c) => $c['average'] !== null)->average('average');
                @endphp
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Indeks Rata-rata CPL Prodi</p>
                <p class="mt-2 text-2xl font-bold {{ $overallAvg >= 65 ? 'text-brand' : 'text-amber-600' }}">
                    {{ $overallAvg !== null ? number_format($overallAvg, 2) : '—' }}
                </p>
                <p class="mt-1 text-xs text-muted">Berdasarkan data asesmen aktif</p>
            </div>
        </div>

        <!-- Tabel Monitoring CPL -->
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode CPL</th>
                        <th>Deskripsi Capaian Pembelajaran Lulusan</th>
                        <th class="text-center">Sampel Mahasiswa</th>
                        <th class="text-center">Rata-rata Skor</th>
                        <th class="text-center">Ketercapaian (&ge; 65)</th>
                        <th class="text-center">Status Akreditasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cplStats as $stat)
                        @php
                            $cpl = $stat['cpl'];
                            $percent = $stat['percent_achieved'];
                        @endphp
                        <tr>
                            <td class="font-bold text-brand whitespace-nowrap">{{ $cpl->code }}</td>
                            <td class="max-w-lg text-xs text-ink leading-relaxed">{{ $cpl->description }}</td>
                            <td class="text-center text-xs">
                                <span class="font-medium text-ink">{{ $stat['count'] }}</span>
                                <span class="text-muted"> entri</span>
                            </td>
                            <td class="text-center text-xs font-semibold">
                                {{ $stat['average'] !== null ? number_format($stat['average'], 2) : '—' }}
                            </td>
                            <td class="text-center text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <span class="font-bold {{ $percent >= 75 ? 'text-brand' : ($percent >= 50 ? 'text-amber-600' : 'text-rose-600') }}">
                                        {{ $percent }}%
                                    </span>
                                    <span class="text-muted">({{ $stat['achieved_count'] }})</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($stat['count'] === 0)
                                    <span class="status bg-canvas text-muted">Belum Dinilai</span>
                                @elseif($percent >= 80)
                                    <span class="status bg-brand-soft text-brand">Sangat Baik / Unggul</span>
                                @elseif($percent >= 65)
                                    <span class="status bg-emerald-50 text-emerald-700">Memenuhi Standar</span>
                                @else
                                    <span class="status bg-amber-50 text-amber-700">Perlu Peningkatan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Standar capaian lulusan OBE: Ambang batas kelulusan per butir CPL adalah nilai 65. Target akreditasi prodi menghendaki minimal 75% mahasiswa melampaui ambang batas.</p>
    @endif
</div>
@endsection
