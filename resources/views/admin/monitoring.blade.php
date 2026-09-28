@extends('layouts.mahasiswa')
@section('header', 'Monitoring sistem')
@section('title', 'Monitoring sistem | SALE')

@section('content')
@php
    $detail = in_array(request('detail'), ['ai', 'server', 'backup'], true) ? request('detail') : null;
    $monitorUrl = fn (?string $target = null) => route('admin.page', ['section' => 'monitoring', 'detail' => $target]);
@endphp
<div class="space-y-6">
    <header><h1 class="page-heading">Monitoring sistem</h1><p class="page-description">Data penggunaan yang telah tercatat oleh layanan SALE.</p></header>
    <div role="status" class="rounded-xl border border-line bg-brand-soft px-5 py-4 text-sm leading-6 text-brand-dark"><span class="font-semibold">Tidak ada data simulasi.</span> Pemakaian AI berasal dari log database. Metrik server dan backup ditampilkan setelah kolektor terkait benar-benar terhubung.</div>
    <div class="grid gap-4 md:grid-cols-3">
        @foreach([
            ['ai', 'Pemakaian AI', number_format((int) $aiMetrics['total_tokens'], 0, ',', '.').' token', number_format((int) $aiMetrics['requests'], 0, ',', '.').' permintaan bulan ini'],
            ['server', 'Beban sistem', 'Belum diukur', 'Kolektor CPU, RAM, HTTP, dan penyimpanan belum terhubung'],
            ['backup', 'Backup & pemulihan', 'Belum terhubung', 'Belum ada layanan backup yang mencatat riwayat'],
        ] as [$target, $label, $value, $description])
            <a href="{{ $monitorUrl($detail === $target ? null : $target) }}" @if($detail === $target) aria-current="true" @endif class="surface group border p-5 transition hover:border-brand hover:shadow-md {{ $detail === $target ? 'border-brand ring-1 ring-brand' : 'border-transparent' }}"><h2 class="text-sm text-muted">{{ $label }}</h2><p class="mt-3 text-2xl font-semibold tracking-tight">{{ $value }}</p><p class="mt-2 text-xs leading-5 text-muted">{{ $description }}</p><span class="mt-5 block text-xs font-semibold text-brand">{{ $detail === $target ? 'Tutup detail' : 'Lihat detail' }}</span></a>
        @endforeach
    </div>
    @if($detail === 'ai')
        <section class="surface overflow-hidden" aria-labelledby="ai-heading">
            <div class="border-b border-line/60 p-5 sm:p-6"><h2 id="ai-heading" class="section-heading">Pemakaian AI bulan berjalan</h2><p class="mt-1 text-sm text-muted">Agregat token berasal dari tabel pencatatan panggilan API.</p></div>
            <dl class="grid gap-4 p-5 sm:grid-cols-4 sm:p-6">
                @foreach(['Permintaan' => $aiMetrics['requests'], 'Token input' => $aiMetrics['input_tokens'], 'Token output' => $aiMetrics['output_tokens'], 'Total token' => $aiMetrics['total_tokens']] as $label => $value)
                    <div class="rounded-xl border border-line/50 bg-canvas/50 p-4"><dt class="text-xs text-muted">{{ $label }}</dt><dd class="mt-1 text-xl font-bold text-ink">{{ number_format((int) $value, 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
            <div class="border-t border-line/60 p-5 sm:p-6">@include('admin.partials.monitoring-ai-requests', ['demo' => false])</div>
        </section>
    @elseif($detail === 'server')
        <section class="surface border border-dashed border-line p-8 text-center"><h2 class="section-heading">Metrik server belum tersedia</h2><p class="mt-2 text-sm text-muted">SALE belum menerima pengukuran CPU, RAM, waktu respons, atau kapasitas penyimpanan.</p></section>
    @elseif($detail === 'backup')
        <section class="surface border border-dashed border-line p-8 text-center"><h2 class="section-heading">Layanan backup belum tersedia</h2><p class="mt-2 text-sm text-muted">Belum ada jadwal, lokasi, atau riwayat backup yang tersimpan di sistem.</p></section>
    @endif
</div>
@endsection
