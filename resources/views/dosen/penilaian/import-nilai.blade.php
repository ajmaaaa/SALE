@extends('layouts.mahasiswa')

@section('title', 'Import Nilai — ' . $assessment->name . ' | SALE')
@section('header', 'Import Nilai')

@section('content')
<div class="space-y-6 max-w-3xl">
    @include('dosen.partials.header')

    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="hover:text-brand">Input Nilai</a>
            <span>/</span>
            <span class="text-ink font-semibold">Import CSV</span>
        </nav>
        <h2 class="section-heading">Import Nilai: {{ $assessment->name }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $assessment->code }} · {{ ucfirst($assessment->type) }}</p>
    </header>

    @if($errors->any())
        <div class="surface p-4 border-l-4 border-danger">
            <ul class="text-sm text-danger space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('import_errors'))
        <div class="surface p-4 border-l-4 border-amber-400">
            <p class="text-sm font-semibold text-ink mb-2">Peringatan pada beberapa baris:</p>
            <ul class="text-xs text-muted space-y-1">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Preview mode: show parsed data for confirmation --}}
    @if($preview && session('import_preview_ready'))
        <div class="surface p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="section-heading">Preview Data Import</h3>
                    <p class="text-xs text-muted mt-1">Periksa data berikut sebelum menyimpan.</p>
                </div>
                <span class="status bg-brand-soft text-brand">{{ count($preview['rows']) }} baris valid</span>
            </div>

            @if(!empty($preview['errors']))
                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200">
                    <p class="text-xs font-semibold text-amber-700 mb-1">{{ count($preview['errors']) }} baris dilewati:</p>
                    <ul class="text-xs text-amber-600 space-y-0.5">
                        @foreach($preview['errors'] as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            @if(($preview['mode'] ?? 'legacy') === 'cpmk')
                                @foreach($preview['cpmk_headers'] as $hdr)
                                    <th class="text-center font-mono">{{ $hdr['code'] }} (Maks: {{ (int)$hdr['max'] }})</th>
                                @endforeach
                                <th class="text-center font-bold">Total Asesmen</th>
                            @else
                                <th>Nilai</th>
                                <th>Feedback</th>
                            @endif
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['rows'] as $row)
                            <tr>
                                <td class="font-mono text-xs">{{ $row['nim'] }}</td>
                                <td class="font-medium text-ink">{{ $row['name'] }}</td>
                                @if(($preview['mode'] ?? 'legacy') === 'cpmk')
                                    @foreach($preview['cpmk_headers'] as $hdr)
                                        <td class="text-center font-mono">
                                            {{ isset($row['cpmk_scores'][$hdr['cpmk_id']]) && $row['cpmk_scores'][$hdr['cpmk_id']] !== null ? number_format($row['cpmk_scores'][$hdr['cpmk_id']], 1) : '—' }}
                                        </td>
                                    @endforeach
                                    <td class="text-center font-mono font-bold text-ink">
                                        {{ $row['overall_score'] !== null ? number_format($row['overall_score'], 1) : '—' }}
                                    </td>
                                @else
                                    <td>{{ $row['score'] !== null ? number_format($row['score'], 2) : '—' }}</td>
                                    <td class="text-xs text-muted">{{ $row['feedback'] ?: '—' }}</td>
                                @endif
                                <td>
                                    @if($row['status'] === 'valid')
                                        <span class="status bg-brand-soft text-brand">Valid</span>
                                    @else
                                        <span class="status bg-canvas text-muted">Kosong</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between pt-2">
                <form method="post" action="{{ route('dosen.penilaian.asesmen.nilai.import.process', [$section->id, $assessment->id]) }}">
                    @csrf
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="button-primary">Konfirmasi & Simpan</button>
                </form>
                <a href="{{ route('dosen.penilaian.asesmen.nilai.import', [$section->id, $assessment->id]) }}" class="quiet-link text-sm">Upload Ulang</a>
            </div>
        </div>
    @else
        {{-- Upload form --}}
        <div class="surface p-5 space-y-4">
            <h3 class="section-heading">Upload File CSV</h3>
            <p class="text-sm text-muted">Format file: CSV dengan delimiter titik koma (;) atau koma (,). Format kolom mengikuti template yang diunduh.</p>

            <div class="p-4 rounded-lg bg-canvas">
                <p class="text-xs font-semibold text-ink mb-2">Alur Import:</p>
                <ol class="text-xs text-muted space-y-1 list-decimal list-inside">
                    <li>Download template CSV dari halaman <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="text-brand hover:underline">Input Nilai</a></li>
                    <li>Isi nilai mahasiswa pada template sesuai kolom yang tersedia</li>
                    <li>Upload file yang sudah diisi di sini</li>
                    <li>Periksa preview data, lalu konfirmasi untuk menyimpan</li>
                </ol>
            </div>

            <form method="post" action="{{ route('dosen.penilaian.asesmen.nilai.import.process', [$section->id, $assessment->id]) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <label class="block">
                    <span class="form-label text-xs">File CSV</span>
                    <input type="file" name="file" accept=".csv,.txt" required class="field text-sm">
                </label>
                <p class="text-xs text-muted">Maksimal 2 MB. Format CSV (.csv) dengan encoding UTF-8.</p>
                <div class="flex items-center gap-3">
                    <button type="submit" class="button-primary">Upload & Preview</button>
                    <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="quiet-link text-sm">Batal</a>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
