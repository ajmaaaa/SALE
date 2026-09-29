@extends('layouts.mahasiswa')

@section('title', 'Laporan & Rekapitulasi | SALE')
@section('header', 'Laporan & Rekapitulasi')

@section('content')
@php
    $fakultasCount = count(array_filter($academic, fn($a) => $a['type'] === 'fakultas'));
    $prodiCount = count(array_filter($academic, fn($a) => $a['type'] === 'prodi'));
    $semesterCount = count(array_filter($academic, fn($a) => $a['type'] === 'semester'));

    $mahasiswaCount = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'mahasiswa')));
    $mahasiswaAktif = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'mahasiswa') && $u['status'] === 'aktif'));

    $dosenCount = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'dosen')));
    $dosenAktif = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'dosen') && $u['status'] === 'aktif'));

    $adminCount = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'admin')));
    $adminAktif = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'admin') && $u['status'] === 'aktif'));

    $logs = $logs ?? [];
@endphp

<div class="space-y-8 w-full">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <h1 class="page-heading">Laporan &amp; Rekapitulasi</h1>
            <p class="page-description">Laporan komprehensif data institusi, demografi pengguna, dan konsumsi sumber daya sistem.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full sm:w-auto sm:ml-auto">
            <a class="button-primary text-xs inline-flex items-center justify-center gap-2 w-full sm:w-auto" href="{{ route('admin.export') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Unduh Rekap Excel
            </a>
        </div>
    </header>

    {{-- 4 Kartu Indikator Kunci --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface p-5 border border-line/60">
            <p class="text-xs font-semibold text-muted">TOTAL PENGGUNA</p>
            <p class="mt-2 text-2xl font-bold text-ink">{{ count($users) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $mahasiswaCount }} mahasiswa, {{ $dosenCount }} dosen</p>
        </div>
        <div class="surface p-5 border border-line/60">
            <p class="text-xs font-semibold text-muted">STRUKTUR AKADEMIK</p>
            <p class="mt-2 text-2xl font-bold text-ink">{{ $prodiCount }} <span class="text-sm font-normal text-muted">Prodi</span></p>
            <p class="mt-1 text-xs text-muted">Dalam {{ $fakultasCount }} fakultas utama</p>
        </div>
        <div class="surface p-5 border border-line/60">
            <p class="text-xs font-semibold text-muted">KONSUMSI TOKEN AI</p>
            <p class="mt-2 text-2xl font-bold text-ink">{{ number_format((int) $aiMetrics['total_tokens'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-muted">{{ number_format((int) $aiMetrics['requests'], 0, ',', '.') }} permintaan tercatat</p>
        </div>
        <div class="surface p-5 border border-line/60">
            <p class="text-xs font-semibold text-muted">KETERSEDIAAN SISTEM</p>
            <p class="mt-2 text-lg font-bold text-ink">Belum diukur</p>
            <p class="mt-1 text-xs text-muted">Kolektor uptime belum terhubung</p>
        </div>
    </div>

    {{-- Seksi 1: Laporan Struktur Institusi & Data Akademik --}}
    <section class="surface p-6 border border-line/60 space-y-4">
        <div class="flex items-center justify-between border-b border-line/50 pb-3">
            <div>
                <h2 class="text-base font-bold text-ink">Rekapitulasi Struktur Institusi</h2>
                <p class="text-xs text-muted mt-0.5">Master data yang dikelola oleh administrator institusi.</p>
            </div>
            <a href="{{ route('admin.page', 'akademik') }}" class="text-xs font-semibold text-brand hover:text-brand-dark">
                Kelola data akademik
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th>Kelompok Struktur</th>
                        <th>Jumlah Terdaftar</th>
                        <th>Status Aktif</th>
                        <th>Keterangan Kebijakan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-semibold text-ink">Fakultas</td>
                        <td>{{ $fakultasCount }}</td>
                        <td><span class="text-xs font-semibold text-emerald-700">{{ $fakultasCount }} Aktif</span></td>
                        <td class="text-xs text-muted">Maksimal 1 fakultas per institusi</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-ink">Program Studi (Prodi)</td>
                        <td>{{ $prodiCount }}</td>
                        <td><span class="text-xs font-semibold text-emerald-700">{{ $prodiCount }} Aktif</span></td>
                        <td class="text-xs text-muted">Bernaung di bawah fakultas utama</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-ink">Semester / Tahun Ajaran</td>
                        <td>{{ $semesterCount }}</td>
                        <td><span class="text-xs font-semibold text-emerald-700">{{ $semesterCount }} Aktif</span></td>
                        <td class="text-xs text-muted">{{ collect($academic)->first(fn($item) => $item['type'] === 'semester' && $item['status'] === 'aktif')['name'] ?? 'Belum ditetapkan' }} sebagai semester berjalan</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Seksi 2: Laporan Pemakaian Token AI & Komputasi --}}
    <section class="surface p-6 border border-line/60 space-y-4">
        <div class="flex items-center justify-between border-b border-line/50 pb-3">
            <div>
                <h2 class="text-base font-bold text-ink">Laporan Pemakaian Token AI &amp; Komputasi</h2>
                <p class="text-xs text-muted mt-0.5">Ringkasan penggunaan token AI bulan berjalan dan estimasi kuota.</p>
            </div>
            <a href="{{ route('admin.page', ['section' => 'monitoring', 'detail' => 'ai']) }}" class="text-xs font-semibold text-brand hover:text-brand-dark">
                Panel monitoring AI
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-canvas/60 p-4 border border-line/40">
                <p class="text-xs text-muted font-medium">Token Input (Prompt)</p>
                <p class="text-xl font-bold text-ink mt-1">{{ number_format((int) $aiMetrics['input_tokens'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-muted mt-0.5">Berdasarkan log API bulan berjalan</p>
            </div>
            <div class="rounded-xl bg-canvas/60 p-4 border border-line/40">
                <p class="text-xs text-muted font-medium">Token Output (Completion)</p>
                <p class="text-xl font-bold text-ink mt-1">{{ number_format((int) $aiMetrics['output_tokens'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-muted mt-0.5">Berdasarkan log API bulan berjalan</p>
            </div>
            <div class="rounded-xl bg-canvas/60 p-4 border border-line/40">
                <p class="text-xs text-muted font-medium">Total Permintaan</p>
                <p class="text-xl font-bold text-ink mt-1">{{ number_format((int) $aiMetrics['requests'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-muted mt-0.5">Panggilan API bulan berjalan</p>
            </div>
        </div>

        <div class="overflow-x-auto pt-2">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th>Modul Layanan AI</th>
                        <th>Total Permintaan</th>
                        <th>Token Terpakai</th>
                        <th>Rata-rata / Permintaan</th>
                        <th>Status Layanan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aiByFeature as $feature)
                        <tr><td class="font-semibold text-ink">{{ ucfirst(str_replace('_', ' ', $feature->feature)) }}</td><td>{{ number_format((int) $feature->requests, 0, ',', '.') }}</td><td>{{ number_format((int) $feature->total_tokens, 0, ',', '.') }} token</td><td class="text-xs text-muted">{{ number_format((float) $feature->average_tokens, 0, ',', '.') }} token</td><td><span class="text-xs font-semibold text-emerald-700">Tercatat</span></td></tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-sm text-muted">Belum ada panggilan AI yang tercatat bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Seksi 3: Demografi & Distribusi Pengguna --}}
    <section class="surface p-6 border border-line/60 space-y-4">
        <div class="flex items-center justify-between border-b border-line/50 pb-3">
            <div>
                <h2 class="text-base font-bold text-ink">Distribusi Pengguna &amp; Hak Akses</h2>
                <p class="text-xs text-muted mt-0.5">Rasio keaktifan akun berdasarkan peran dalam ekosistem akademik.</p>
            </div>
            <a href="{{ route('admin.page', 'pengguna') }}" class="text-xs font-semibold text-brand hover:text-brand-dark">
                Kelola pengguna
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th>Peran Pengguna</th>
                        <th>Total Akun</th>
                        <th>Akun Aktif</th>
                        <th>Akun Nonaktif</th>
                        <th>Tingkat Keaktifan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-semibold text-ink">Mahasiswa</td>
                        <td>{{ $mahasiswaCount }}</td>
                        <td>{{ $mahasiswaAktif }}</td>
                        <td>{{ $mahasiswaCount - $mahasiswaAktif }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-24 bg-line rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full" style="width: {{ $mahasiswaCount ? round(($mahasiswaAktif / $mahasiswaCount) * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs font-medium">{{ $mahasiswaCount ? round(($mahasiswaAktif / $mahasiswaCount) * 100) : 0 }}%</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-ink">Dosen</td>
                        <td>{{ $dosenCount }}</td>
                        <td>{{ $dosenAktif }}</td>
                        <td>{{ $dosenCount - $dosenAktif }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-24 bg-line rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full" style="width: {{ $dosenCount ? round(($dosenAktif / $dosenCount) * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs font-medium">{{ $dosenCount ? round(($dosenAktif / $dosenCount) * 100) : 0 }}%</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-ink">Administrator</td>
                        <td>{{ $adminCount }}</td>
                        <td>{{ $adminAktif }}</td>
                        <td>{{ $adminCount - $adminAktif }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-24 bg-line rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full" style="width: {{ $adminCount ? round(($adminAktif / $adminCount) * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs font-medium">{{ $adminCount ? round(($adminAktif / $adminCount) * 100) : 0 }}%</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Seksi 4: Log Audit Administratif Terkini --}}
    <section class="surface p-6 border border-line/60 space-y-4">
        <div class="flex items-center justify-between border-b border-line/50 pb-3">
            <div>
                <h2 class="text-base font-bold text-ink">Riwayat Audit Administratif</h2>
                <p class="text-xs text-muted mt-0.5">Catatan aktivitas dan perubahan konfigurasi sistem terbaru.</p>
            </div>
            <a href="{{ route('admin.page', 'aktivitas') }}" class="text-xs font-semibold text-brand hover:text-brand-dark">
                Buka semua log
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th>Waktu Kejadian</th>
                        <th>Pelaksana (Aktor)</th>
                        <th>Aktivitas Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(array_slice($logs, 0, 8) as $log)
                        <tr>
                            <td class="font-mono text-xs text-muted whitespace-nowrap">{{ $log['time'] }}</td>
                            <td class="font-semibold text-ink whitespace-nowrap">{{ $log['actor'] }}</td>
                            <td class="text-xs text-ink">{{ $log['action'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-xs text-muted">Belum ada riwayat aktivitas tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
