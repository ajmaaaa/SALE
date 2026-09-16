@extends('layouts.mahasiswa')

@section('title', 'Preview & Validasi Impor: ' . $assessment->name . ' | SALE')
@section('header', 'Preview & Validasi Impor Nilai')

@section('content')
<div class="space-y-6 max-w-6xl">
    <!-- Breadcrumbs & Header -->
    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <a href="{{ route('dosen.penilaian.asesmen.show', [$section->id, $assessment->id]) }}" class="hover:text-brand">{{ $assessment->code }}</a>
            <span>/</span>
            <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="hover:text-brand">Input Nilai</a>
            <span>/</span>
            <span class="text-ink font-semibold">Preview & Validasi</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="page-heading">Preview & Validasi Impor: {{ $assessment->name }} <span class="font-mono text-base font-normal text-muted">({{ $assessment->code }})</span></h1>
                <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->mataKuliah->name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dosen.penilaian.asesmen.import.cancel', [$section->id, $assessment->id]) }}" class="button-secondary text-xs text-danger">
                    Batal Impor
                </a>
            </div>
        </div>
    </header>

    <!-- Ringkasan Hasil Validasi (Step 17) -->
    <section class="surface p-5 space-y-3">
        <h2 class="section-heading">Ringkasan Validasi File</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="p-3 rounded-lg border border-line/60 bg-canvas/40">
                <span class="text-xs text-muted block">Total Baris Terbaca</span>
                <span class="text-xl font-bold font-mono text-ink">{{ $totalRows }}</span>
            </div>
            <div class="p-3 rounded-lg border border-emerald-200 bg-emerald-50/60">
                <span class="text-xs text-emerald-700 font-medium block">Data Valid (Siap Disimpan)</span>
                <span class="text-xl font-bold font-mono text-emerald-800">{{ count($validRows) }}</span>
            </div>
            <div class="p-3 rounded-lg border border-rose-200 bg-rose-50/60">
                <span class="text-xs text-rose-700 font-medium block">Data Bermasalah (Ditolak)</span>
                <span class="text-xl font-bold font-mono text-rose-800">{{ count($invalidRows) }}</span>
            </div>
        </div>

        @if(count($invalidRows) > 0)
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-xs text-amber-800 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <span class="font-semibold">Perhatian Integritas Data OBE:</span>
                    Terdapat <strong>{{ count($invalidRows) }}</strong> baris data yang bermasalah. Sistem <span class="font-semibold underline">tidak akan menyimpan</span> data yang invalid. Hanya baris valid yang akan disimpan ke database.
                </div>
            </div>
        @else
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Seluruh baris data valid dan sesuai dengan daftar mahasiswa serta kriteria asesmen.</span>
            </div>
        @endif
    </section>

    <!-- Tabel Preview Data -->
    <section class="surface overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="w-16 text-center">Baris</th>
                    <th class="w-24 text-center">Status</th>
                    <th class="min-w-[120px]">NIM</th>
                    <th class="min-w-[180px]">Nama Mahasiswa</th>

                    @if($isRubric)
                        @foreach($criteria as $crit)
                            <th class="w-28 text-center">
                                {{ $crit->name }}
                                <span class="block text-[11px] font-normal text-muted">({{ rtrim(rtrim(number_format($crit->weight, 1), '0'), '.') }}%)</span>
                            </th>
                        @endforeach
                        <th class="w-24 text-center">Nilai Akhir</th>
                    @else
                        <th class="w-28 text-center">Nilai</th>
                    @endif

                    <th class="min-w-[220px]">Keterangan / Rincian Error</th>
                </tr>
            </thead>
            <tbody>
                {{-- Gabungkan baris untuk preview, dengan invalid disorot di atas atau berurutan --}}
                @php
                    $allRows = array_merge($invalidRows, $validRows);
                    usort($allRows, fn($a, $b) => $a['row_number'] <=> $b['row_number']);
                @endphp

                @foreach($allRows as $row)
                    <tr class="{{ $row['is_valid'] ? 'hover:bg-canvas/30' : 'bg-rose-50/40 hover:bg-rose-50/60' }}">
                        <td class="text-center font-mono text-xs text-muted">{{ $row['row_number'] }}</td>
                        <td class="text-center">
                            @if($row['is_valid'])
                                <span class="status bg-emerald-50 text-emerald-700 text-[11px] font-semibold">Valid</span>
                            @else
                                <span class="status bg-rose-50 text-rose-700 text-[11px] font-semibold">Error</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs {{ $row['is_valid'] ? 'text-ink' : 'text-rose-700 font-semibold' }}">
                            {{ $row['nim'] ?: '—' }}
                        </td>
                        <td class="font-medium text-xs text-ink">{{ $row['name'] ?: '—' }}</td>

                        @if($isRubric)
                            @foreach($criteria as $crit)
                                <td class="text-center font-mono text-xs">
                                    {{ isset($row['criteria_scores'][$crit->id]) && $row['criteria_scores'][$crit->id] !== null ? rtrim(rtrim(number_format($row['criteria_scores'][$crit->id], 2), '0'), '.') : '—' }}
                                </td>
                            @endforeach
                            <td class="text-center font-mono text-xs font-semibold">
                                {{ $row['score'] !== null ? rtrim(rtrim(number_format($row['score'], 2), '0'), '.') : '—' }}
                            </td>
                        @else
                            <td class="text-center font-mono text-xs font-semibold">
                                {{ $row['score'] !== null ? rtrim(rtrim(number_format($row['score'], 2), '0'), '.') : '—' }}
                            </td>
                        @endif

                        <td class="text-xs">
                            @if($row['is_valid'])
                                <span class="text-emerald-700">Data valid dan siap disimpan.</span>
                                @if(!empty($row['feedback']))
                                    <span class="block text-[11px] text-muted mt-0.5">Catatan: {{ $row['feedback'] }}</span>
                                @endif
                            @else
                                <ul class="list-disc list-inside text-rose-700 space-y-0.5 font-medium">
                                    @foreach($row['errors'] as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <!-- Tombol Konfirmasi & Batal (Step 17) -->
    <div class="surface p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="text-xs text-muted">
            Hanya <strong class="text-emerald-700">{{ count($validRows) }}</strong> baris data valid yang akan disimpan ke database.
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dosen.penilaian.asesmen.import.cancel', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">
                Batal Impor
            </a>

            <form method="POST" action="{{ route('dosen.penilaian.asesmen.import.confirm', [$section->id, $assessment->id]) }}">
                @csrf
                <button type="submit" class="button-primary text-xs px-5 py-2.5" {{ count($validRows) === 0 ? 'disabled' : '' }}>
                    Konfirmasi & Simpan ({{ count($validRows) }} Data Valid)
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
