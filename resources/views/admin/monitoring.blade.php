@extends('layouts.mahasiswa')
@section('header','Monitoring sistem')
@section('title', 'Monitoring sistem | SALE')
@section('content')
@php
    $demo = request()->boolean('contoh');
    $detail = request('detail') === 'kuota' ? 'ai' : (in_array(request('detail'), ['ai', 'server', 'backup']) ? request('detail') : null);
    $rangeRequest = request()->duplicate();
    if ($detail === 'ai' && in_array(request('rentang'), [null, 'realtime', 'requests'], true)) {
        $rangeRequest->query->set('rentang', 'month');
    }
    $rangeData = \App\Support\MonitoringRange::fromRequest($rangeRequest);
    if ($detail === 'ai') {
        unset($rangeData['choices']['realtime']);
        $rangeData['choices'] = ['requests' => 'Per request'] + $rangeData['choices'];
        if (in_array(request('rentang'), [null, 'realtime', 'requests'], true)) {
            $rangeData['range'] = 'requests';
        }
    }
    $metric = request('metrik') === 'permintaan' ? 'permintaan' : 'token';
    $filters = array_merge(request()->only(['rentang', 'bulan', 'mulai', 'akhir']), ['metrik' => $metric]);
    $monitorUrl = fn ($target = null) => route('admin.page', array_merge($filters, ['section' => 'monitoring', 'contoh' => $demo ? 1 : 0, 'detail' => $target]));
    $scale = $metric === 'token' ? 1000 : 10;
    $series = ['labels' => [], 'values' => [], 'caption' => $rangeData['caption']];
    foreach ($rangeData['buckets'] as $bucket) {
        $series['labels'][] = ($rangeData['label'])($bucket);
        $series['values'][] = 12 + ((int) $bucket->format('dmHi') * 7 % 80);
    }
@endphp
<div class="space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div><h1 class="page-heading">Monitoring sistem</h1><p class="page-description">Pantau penggunaan AI, analisis kunjungan dan beban sistem, serta kelola cadangan data.</p></div>
        <a class="button-secondary" href="{{ route('admin.page', array_merge($filters, ['section' => 'monitoring', 'contoh' => $demo ? 0 : 1, 'detail' => $detail])) }}">{{ $demo ? 'Kembali ke status layanan' : 'Lihat contoh data' }}</a>
    </header>
    <div role="status" class="rounded-xl border border-line bg-brand-soft px-5 py-4 text-sm leading-6 text-brand-dark">
        <span class="font-semibold">{{ $demo ? 'Mode contoh data.' : 'Layanan monitoring belum terhubung.' }}</span>
        {{ $demo ? 'Seluruh angka, grafik, dan riwayat adalah simulasi tampilan, bukan kondisi server saat ini.' : 'Data akan tersedia setelah pencatatan AI dan pengukuran server aktif. Pilih Lihat contoh data untuk meninjau grafik.' }}
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach([
            ['ai', 'Pemakaian & kuota AI', '640.000 token', 'Sisa 360.000 dari kuota 1.000.000 token', 'Pemakaian dan sisa kuota belum tersedia'],
            ['server', 'Pengunjung & beban sistem', 'Rekap penggunaan', 'Pengunjung, permintaan, CPU, RAM, dan penyimpanan', 'Statistik kunjungan dan beban belum tersedia'],
            ['backup', 'Backup & pemulihan', '15 Sep, 02.00', 'Contoh backup berhasil · 8,4 GB', 'Belum ada backup'],
        ] as [$target, $label, $value, $description, $empty])
            <a href="{{ $monitorUrl($detail === $target ? null : $target) }}" @if($detail === $target) aria-current="true" @endif class="surface group border p-5 transition hover:border-brand hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand {{ $detail === $target ? 'border-brand ring-1 ring-brand' : 'border-transparent' }}"><h2 class="text-sm text-muted">{{ $label }}</h2><p class="mt-3 text-2xl font-semibold tracking-tight">{{ $demo ? $value : '—' }}</p><p class="mt-2 text-xs leading-5 text-muted">{{ $demo ? $description : $empty }}</p><span class="mt-5 flex items-center justify-between text-xs font-semibold text-brand">{{ $detail === $target ? 'Tutup detail' : 'Lihat detail' }}</span></a>
        @endforeach
    </div>
    @if($detail)
        <div class="flex items-center justify-between gap-4"><p class="text-xs font-semibold uppercase tracking-wider text-muted">Detail monitoring</p><a href="{{ $monitorUrl() }}" class="text-sm font-semibold text-brand">Tutup detail</a></div>
    @endif
    @if($detail === 'ai')
    <section class="surface overflow-hidden" aria-labelledby="ai-heading">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line/60 p-5 sm:p-6">
            <div><h2 id="ai-heading" class="section-heading">Pemakaian & kuota AI</h2><p class="mt-1 text-sm text-muted">Token input dan output setiap respons AI, serta sisa kuota bulanan.</p></div>
            <span class="rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand">{{ $demo ? 'Data contoh' : 'Belum terhubung' }}</span>
        </div>
        <div class="grid lg:grid-cols-[minmax(0,2fr)_minmax(240px,1fr)]">
            <div class="min-w-0 p-5 sm:p-6">
                @include('admin.partials.monitoring-filters')
                @if($rangeData['range'] === 'requests')
                    @include('admin.partials.monitoring-ai-requests')
                @elseif($demo)
                    @include('admin.partials.monitoring-chart', [
                        'chartTitle' => 'Tren pemakaian AI',
                        'chartCaption' => $series['caption'].' · Data simulasi',
                        'chartRows' => collect($series['labels'])->map(fn ($label, $index) => ['label' => $label, 'value' => $series['values'][$index] * $scale])->all(),
                        'chartTracks' => [['key' => 'value', 'label' => ucfirst($metric), 'unit' => $metric, 'color' => 'bg-brand', 'max' => 100 * $scale]],
                    ])
                    <details class="mt-4 text-xs text-muted"><summary class="list-none cursor-pointer font-semibold text-brand [&::-webkit-details-marker]:hidden">Lihat angka pemakaian</summary><div class="mt-3 max-h-48 overflow-auto"><table class="admin-table"><thead><tr><th>Waktu</th><th>{{ ucfirst($metric) }}</th></tr></thead><tbody>@foreach($series['labels'] as $index => $label)<tr><td>{{ $label }}</td><td>{{ number_format($series['values'][$index] * $scale, 0, ',', '.') }}</td></tr>@endforeach</tbody></table></div></details>
                @else
                    <div class="mt-5 flex min-h-52 flex-col items-center justify-center rounded-xl border border-dashed border-line bg-canvas/50 px-5 text-center"><p class="text-sm font-semibold">Belum ada riwayat pemakaian</p><p class="mt-1 max-w-sm text-xs leading-5 text-muted">Grafik akan muncul setelah pencatatan permintaan AI aktif.</p></div>
                @endif
            </div>
            <aside class="border-t border-line/60 bg-canvas/40 p-5 sm:p-6 lg:border-l lg:border-t-0" aria-label="Kuota AI bulanan">
                <p class="text-sm font-semibold">Sisa kuota bulan ini</p><p class="mt-4 text-3xl font-semibold text-brand-dark">{{ $demo ? '36%' : '—' }}</p><p class="mt-1 text-xs text-muted">{{ $demo ? '360.000 token tersedia' : 'Batas kuota belum ditetapkan' }}</p>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-line" @if($demo) role="progressbar" aria-label="Kuota AI terpakai" aria-valuenow="64" aria-valuemin="0" aria-valuemax="100" @endif><div class="h-full rounded-full bg-brand" style="width: {{ $demo ? 64 : 0 }}%"></div></div>
                <dl class="mt-5 space-y-3 text-sm">@foreach(['Terpakai' => '640.000 token', 'Batas bulanan' => '1.000.000 token', 'Reset berikutnya' => '1 Okt 2026'] as $label => $value)<div class="flex justify-between gap-3"><dt class="text-muted">{{ $label }}</dt><dd>{{ $demo ? $value : '—' }}</dd></div>@endforeach</dl>
                <p class="mt-6 text-xs leading-5 text-muted">Kuota adalah batas penggunaan institusi, bukan saldo penyedia AI. Ringkasan mencakup seluruh pemakaian bulan berjalan.</p>
            </aside>
        </div>
    </section>
    @endif
    @if($detail === 'server')
    @include('admin.partials.monitoring-analytics')
    @endif
    @if($detail === 'backup')
    <section class="surface overflow-hidden" aria-labelledby="backup-heading">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line/60 p-5 sm:p-6">
            <div><h2 id="backup-heading" class="section-heading">Backup & pemulihan</h2><p class="mt-1 text-sm text-muted">Simpan cadangan dan pulihkan data dari riwayat yang tersedia.</p></div>
            <button type="button" disabled aria-describedby="backup-unavailable" class="button-primary cursor-not-allowed opacity-50">Buat backup</button>
        </div>
        <div class="p-5 sm:p-6">
            <div class="grid gap-6 rounded-xl bg-canvas/60 p-5 sm:grid-cols-2">
                <div><p class="text-xs font-semibold text-muted">BACKUP TERAKHIR</p><p class="mt-2 text-xl font-semibold">{{ $demo ? '15 September 2026' : 'Belum ada cadangan' }}</p><p class="mt-1 text-sm text-muted">{{ $demo ? '02.00 WIB · 8,4 GB · Data contoh' : 'Cadangan pertama akan muncul di sini.' }}</p></div>
                <dl class="space-y-3 text-sm"><div class="flex justify-between gap-4"><dt class="text-muted">Cakupan</dt><dd class="text-right font-medium">Database & unggahan</dd></div><div class="flex justify-between gap-4"><dt class="text-muted">Backup otomatis</dt><dd class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-muted">Belum aktif</dd></div></dl>
            </div>
            <p id="backup-unavailable" class="mt-4 text-xs leading-5 text-muted">Layanan backup belum terhubung. Pembuatan dan pemulihan cadangan belum tersedia.{{ $demo ? ' Riwayat berikut hanya contoh.' : '' }}</p>
            <div class="mb-3 mt-7 flex items-center justify-between"><h3 class="text-sm font-semibold">Riwayat cadangan</h3><span class="text-xs text-muted">{{ $demo ? '2 contoh cadangan' : '0 cadangan' }}</span></div>
            @if($demo)
                <div class="divide-y divide-line/60 rounded-xl border border-line/60">
                    @foreach([['15 September 2026', '8,4 GB'], ['14 September 2026', '7,6 GB']] as [$date, $size])
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                            <div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v5H4zM6 9v11h12V9M10 13h4"/></svg></span><div><p class="text-sm font-semibold">{{ $date }}</p><p class="mt-1 text-xs text-muted">02.00 WIB · {{ $size }} · Database & unggahan</p></div></div>
                            <div class="flex items-center gap-4"><span class="rounded-full bg-brand-soft px-2.5 py-1 text-xs text-brand">Berhasil · contoh</span><button type="button" disabled aria-describedby="backup-unavailable" class="button-secondary cursor-not-allowed opacity-50">Pulihkan</button></div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-line px-5 py-8 text-center"><p class="text-sm font-semibold">Riwayat masih kosong</p><p class="mt-1 text-xs text-muted">Setelah layanan aktif, cadangan yang tersedia untuk dipulihkan akan tampil di sini.</p></div>
            @endif
        </div>
        <div class="border-t border-line/60 p-5 sm:px-6">
            <h3 class="text-sm font-semibold">Jadwal & penyimpanan backup</h3>
            <p class="mt-1 text-xs text-muted">Konfigurasi belum tersedia. Jadwal dan lokasi akan tampil setelah layanan backup diaktifkan.</p>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                @foreach(['Jadwal otomatis', 'Lama penyimpanan', 'Lokasi cadangan'] as $label)
                    <div class="rounded-lg bg-canvas/60 p-3"><dt class="text-xs text-muted">{{ $label }}</dt><dd class="mt-2 font-medium">Belum diatur</dd></div>
                @endforeach
            </dl>
        </div>
        <div class="border-t border-line/60 p-5 sm:px-6"><h3 class="text-sm font-semibold">Pemulihan data</h3><p class="mt-2 text-sm leading-6 text-muted">Gunakan tombol Pulihkan pada cadangan yang ingin dikembalikan. Sebelum pemulihan berjalan, tanggal dan cakupan data perlu dikonfirmasi karena data setelah tanggal cadangan dapat tertimpa.</p></div>
    </section>
    @endif
</div>
@endsection
