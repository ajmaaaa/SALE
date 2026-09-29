@extends('layouts.mahasiswa')

@section('title', 'Dashboard Administrator | SALE')
@section('header', 'Dashboard Administrator')

@section('content')
@php
    $activeUsersCount = count(array_filter($users, fn($u) => $u['status'] === 'aktif'));
    $totalUsersCount = count($users);
    $prodiCount = count(array_filter($academic, fn($a) => $a['type'] === 'prodi'));
    $activeSemester = collect($academic)->first(fn($item) => $item['type'] === 'semester' && $item['status'] === 'aktif')['name'] ?? 'Belum ditetapkan';
    $logs = $logs ?? [];
@endphp

<div class="space-y-8 w-full">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between w-full">
        <p class="text-xs font-semibold text-brand min-w-0 flex-1">{{ now()->translatedFormat('l, d F Y') }}</p>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full sm:w-auto sm:ml-auto">
            <a href="{{ route('admin.page', 'pengguna') }}?create=1" class="button-primary inline-flex items-center justify-center gap-2 text-xs flex-1 sm:flex-initial">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Tambah Pengguna
            </a>
            <a href="{{ route('admin.page', 'akademik') }}" class="button-secondary inline-flex items-center justify-center gap-2 text-xs flex-1 sm:flex-initial">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 21h18M3 7v1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7M4 21V11m16 10V11M8 21v-4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4"/></svg>
                Kelola Akademik
            </a>
        </div>
    </header>

    {{-- 4 Stat Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Card 1: Pengguna Aktif --}}
        <a class="surface p-5 hover:shadow-md transition group border border-line/60" href="{{ route('admin.page', 'pengguna') }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">PENGGUNA AKTIF</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <p class="mt-3 text-2xl font-bold text-ink">{{ $activeUsersCount }}</p>
            <p class="mt-1 text-xs text-muted">Dari {{ $totalUsersCount }} total akun terdaftar</p>
        </a>

        {{-- Card 2: Program Studi --}}
        <a class="surface p-5 hover:shadow-md transition group border border-line/60" href="{{ route('admin.page', 'akademik') }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">PROGRAM STUDI</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <p class="mt-3 text-2xl font-bold text-ink">{{ $prodiCount }}</p>
            <p class="mt-1 text-xs text-muted">Bernaung dalam 1 fakultas</p>
        </a>

        {{-- Card 3: Semester Berjalan --}}
        <a class="surface p-5 hover:shadow-md transition group border border-line/60" href="{{ route('admin.page', 'pengaturan') }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">SEMESTER BERJALAN</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <p class="mt-3 text-lg font-bold text-ink truncate">{{ $activeSemester }}</p>
            <p class="mt-1 text-xs text-muted">Tahun ajaran aktif institusi</p>
        </a>

        {{-- Card 4: Pemakaian Token AI --}}
        <a class="surface p-5 hover:shadow-md transition group border border-line/60" href="{{ route('admin.page', ['section' => 'monitoring', 'detail' => 'ai']) }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">KUOTA AI BULAN INI</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            </div>
            <p class="mt-3 text-2xl font-bold text-ink">{{ number_format((int) $aiMetrics['total_tokens'], 0, ',', '.') }} <span class="text-xs font-normal text-muted">token</span></p>
            <p class="mt-1 text-xs text-muted">{{ number_format((int) $aiMetrics['requests'], 0, ',', '.') }} permintaan tercatat</p>
        </a>
    </div>

    {{-- Main 2-Column Grid: Monitoring & Server Health di Kiri, Aktivitas Administratif di Kanan --}}
    <div class="grid gap-6 lg:grid-cols-12 items-start">
        {{-- Kolom Kiri (8 cols): Monitoring Sistem & AI --}}
        <div class="space-y-6 lg:col-span-7 xl:col-span-8">
            {{-- Widget 1: Ringkasan Kuota & Pemakaian AI --}}
            <section class="surface p-5 sm:p-6 border border-line/60 space-y-4" aria-labelledby="ai-monitoring-heading">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/50 pb-3.5">
                    <div>
                        <h2 id="ai-monitoring-heading" class="section-heading text-base">Pemantauan Token &amp; Layanan AI</h2>
                        <p class="mt-0.5 text-xs text-muted">Penggunaan token evaluasi otomatis dan agen asisten pembelajaran.</p>
                    </div>
                    <span class="text-xs font-medium text-emerald-600">Layanan Aktif</span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 pt-1">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted">Sisa Kuota Tersedia</p>
                        <p class="mt-2 text-3xl font-bold text-ink">{{ number_format((int) $aiMetrics['total_tokens'], 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-muted">Token terpakai berdasarkan panggilan API bulan ini</p>
                    </div>

                    <dl class="space-y-2.5 text-xs rounded-xl bg-canvas/60 p-3.5 border border-line/40">
                        <div class="flex justify-between">
                            <dt class="text-muted">Token terpakai bulan ini</dt>
                            <dd class="font-semibold text-ink">{{ number_format((int) $aiMetrics['total_tokens'], 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Total permintaan</dt>
                            <dd class="font-semibold text-ink">{{ number_format((int) $aiMetrics['requests'], 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Latensi rata-rata</dt>
                            <dd class="font-medium text-ink">{{ $aiMetrics['average_latency_ms'] === null ? 'Belum ada data' : number_format((float) $aiMetrics['average_latency_ms'], 0, ',', '.').' ms' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-line/40 pt-3 flex items-center justify-between text-xs">
                    <span class="text-muted">Data berasal dari log panggilan AI</span>
                    <a href="{{ route('admin.page', ['section' => 'monitoring', 'detail' => 'ai']) }}" class="font-semibold text-brand hover:text-brand-dark inline-flex items-center gap-1">
                        Buka rincian pemakaian AI
                    </a>
                </div>
            </section>
        </div>

        {{-- Kolom Kanan (4 cols): Info Struktur Institusi --}}
        <div class="space-y-6 lg:col-span-5 xl:col-span-4">

            {{-- Ringkasan Struktur Institusi --}}
            <section class="surface p-5 border border-line/60 space-y-3">
                <div class="flex items-center justify-between border-b border-line/50 pb-3">
                    <h3 class="font-bold text-ink text-sm">Struktur Institusi</h3>
                    <a href="{{ route('admin.page', 'akademik') }}" class="text-xs font-semibold text-brand hover:text-brand-dark">Kelola</a>
                </div>
                <div class="space-y-2.5 text-xs">
                    @php
                        $fakultas = collect($academic)->firstWhere('type', 'fakultas');
                        $prodis = collect($academic)->where('type', 'prodi');
                    @endphp
                    <div class="rounded-lg bg-canvas/60 p-3 border border-line/40">
                        <p class="text-muted font-medium">Fakultas Utama</p>
                        <p class="font-bold text-ink text-sm mt-0.5">{{ $fakultas['name'] ?? 'Belum terdaftar' }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ $fakultas['code'] ?? '' }} (Batas 1 fakultas tercapai)</p>
                    </div>
                    <div class="p-2 space-y-1">
                        <p class="text-muted font-medium text-[11px]">Program Studi Terdaftar ({{ $prodis->count() }}):</p>
                        <ul class="space-y-1 text-ink">
                            @foreach($prodis as $prodi)
                                <li class="flex items-center justify-between">
                                    <span>{{ $prodi['name'] }}</span>
                                    <span class="font-mono text-muted text-[11px]">{{ $prodi['code'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
