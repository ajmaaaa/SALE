@extends('layouts.mahasiswa')

@section('title', 'Monitoring Capaian CPMK | SALE Kaprodi')
@section('header', 'Monitoring Capaian CPMK')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="section-heading text-xl font-bold text-ink">Monitoring Capaian CPMK Program Studi</h2>
            <p class="mt-1 text-xs text-muted">Pemantauan ketercapaian capaian pembelajaran mata kuliah untuk pengendalian mutu kurikulum OBE.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('kaprodi.monitoring.cpl') }}" class="button-secondary text-xs inline-flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Lihat Monitoring CPL
            </a>
        </div>
    </div>

    <!-- Filter Kelas/Mata Kuliah -->
    <div class="surface p-4">
        <form method="GET" action="{{ route('kaprodi.monitoring.cpmk') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <label for="section_id" class="text-xs font-semibold uppercase tracking-wider text-muted shrink-0">Pilih Kelas / Mata Kuliah:</label>
            <select id="section_id" name="section_id" onchange="this.form.submit()" class="w-full sm:w-auto flex-1 rounded-lg border border-line bg-canvas px-3 py-2 text-sm text-ink focus:border-brand focus:outline-none">
                @forelse($sections as $sec)
                    <option value="{{ $sec->id }}" {{ $activeSection && $activeSection->id === $sec->id ? 'selected' : '' }}>
                        {{ $sec->mataKuliah->code }} - {{ $sec->mataKuliah->name }} (Kelas {{ $sec->name }}) &middot; Dosen: {{ $sec->dosen->name ?? '—' }}
                    </option>
                @empty
                    <option value="">Belum ada kelas aktif di semester ini</option>
                @endforelse
            </select>
        </form>
    </div>

    @if(!$activeSection)
        <div class="surface p-12 text-center">
            <p class="text-muted text-sm">Tidak ada kelas aktif yang dipilih untuk dimonitoring.</p>
        </div>
    @elseif($cpmkStats->isEmpty())
        <div class="surface p-12 text-center">
            <h3 class="text-base font-semibold text-ink">Belum Ada CPMK</h3>
            <p class="mt-1 text-sm text-muted">Mata kuliah ini belum memiliki definisi CPMK yang dipetakan oleh dosen atau koordinator.</p>
        </div>
    @else
        <!-- Stats Overview Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="surface p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total CPMK</p>
                <p class="mt-2 text-2xl font-bold text-ink">{{ $cpmkStats->count() }}</p>
                <p class="mt-1 text-xs text-muted">Di mata kuliah {{ $activeSection->mataKuliah->name }}</p>
            </div>
            <div class="surface p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total Mahasiswa Terdaftar</p>
                <p class="mt-2 text-2xl font-bold text-ink">{{ $activeSection->students->count() }}</p>
                <p class="mt-1 text-xs text-muted">Kelas {{ $activeSection->name }}</p>
            </div>
            <div class="surface p-5">
                @php
                    $avgAll = $cpmkStats->filter(fn($c) => $c['average_score'] !== null)->average('average_score');
                @endphp
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Rata-rata Capaian Kelas</p>
                <p class="mt-2 text-2xl font-bold {{ $avgAll >= 65 ? 'text-brand' : 'text-amber-600' }}">
                    {{ $avgAll !== null ? number_format($avgAll, 2) : '—' }}
                </p>
                <p class="mt-1 text-xs text-muted">Rata-rata dari seluruh skor CPMK</p>
            </div>
        </div>

        <!-- Tabel Capaian CPMK -->
        <div class="surface overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Deskripsi CPMK</th>
                        <th class="text-center">Threshold</th>
                        <th class="text-center">Dinilai / Total</th>
                        <th class="text-center">Rata-rata Nilai</th>
                        <th class="text-center">Ketercapaian</th>
                        <th class="text-center">Status Mutu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cpmkStats as $item)
                        @php
                            $cpmk = $item['cpmk'];
                            $isPassing = $item['percent_achieved'] >= 70; // Standar kelulusan mutu kelas >= 70%
                        @endphp
                        <tr>
                            <td class="font-bold text-brand">{{ $cpmk->code }}</td>
                            <td class="max-w-md text-xs text-ink">{{ $cpmk->description }}</td>
                            <td class="text-center text-xs font-medium">{{ number_format($cpmk->threshold, 1) }}</td>
                            <td class="text-center text-xs">
                                <span class="font-medium text-ink">{{ $item['graded_count'] }}</span>
                                <span class="text-muted">/ {{ $item['total_students'] }}</span>
                            </td>
                            <td class="text-center text-xs font-semibold">
                                {{ $item['average_score'] !== null ? number_format($item['average_score'], 2) : '—' }}
                            </td>
                            <td class="text-center text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <span class="font-bold {{ $item['percent_achieved'] >= 70 ? 'text-brand' : 'text-amber-600' }}">
                                        {{ $item['percent_achieved'] }}%
                                    </span>
                                    <span class="text-muted">({{ $item['achieved_count'] }})</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($item['graded_count'] === 0)
                                    <span class="status bg-canvas text-muted">Belum Dinilai</span>
                                @elseif($isPassing)
                                    <span class="status bg-brand-soft text-brand">Memenuhi Target</span>
                                @else
                                    <span class="status bg-amber-50 text-amber-700">Perlu Remedial / Evaluasi</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Target mutu prodi: Minimal 70% mahasiswa terdaftar mencapai threshold CPMK. Data diperbarui secara realtime dari penilaian dosen.</p>
    @endif
</div>
@endsection
