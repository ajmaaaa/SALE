@extends('layouts.mahasiswa')

@section('title', 'Pengaturan Sistem | SALE')
@section('header', 'Pengaturan Sistem')

@section('content')
@php
    $settings = session('admin.settings', []);
    $institution = $settings['institution'] ?? 'Universitas Contoh';
    $institutionCode = $settings['institution_code'] ?? 'UNIV-01';
    $activeSemester = $settings['semester'] ?? 'Ganjil 2026/2027';
    $supportEmail = $settings['support'] ?? 'akademik@example.test';
    $aiQuota = $settings['ai_token_quota'] ?? 1000000;
    $aiModel = $settings['ai_model'] ?? 'Gemini AI Assistant (OBE Tutor)';
    $maintenance = $settings['maintenance_mode'] ?? '0';

    // Ambil daftar semester dari Master Data Akademik
    $availableSemesters = array_filter($academic, fn($a) => $a['type'] === 'semester');
@endphp

<div class="space-y-8">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-heading">Pengaturan Sistem</h1>
            <p class="page-description">Konfigurasi preferensi global, integrasi kuota AI, identitas kampus, dan parameter operasional.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                Sistem Berjalan Normal
            </span>
        </div>
    </header>

    {{-- Callout Edukatif: Perbedaan Pengaturan Sistem vs Data Akademik --}}
    <div class="rounded-xl border border-line/70 bg-canvas/60 p-4 text-xs text-muted flex items-start gap-3">
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand font-bold">i</span>
        <div class="space-y-1">
            <p class="font-semibold text-ink">Perbedaan Pengaturan Sistem dan Data Akademik</p>
            <p class="leading-relaxed">
                Halaman ini mengatur <strong>konfigurasi global aplikasi</strong> (seperti nama kampus, email bantuan, batas kuota AI, dan semester acuan default).
                Untuk menyusun master struktur seperti <strong>Fakultas (maksimal 1)</strong>, <strong>Program Studi</strong>, atau mendaftarkan semester baru, silakan gunakan menu
                <a href="{{ route('admin.page', 'akademik') }}" class="font-semibold text-brand hover:underline">Data Akademik</a>.
            </p>
        </div>
    </div>

    {{-- Form Konfigurasi Sistem Lengkap --}}
    <form class="space-y-6" method="post" action="{{ route('admin.settings.store') }}">
        @csrf

        {{-- Seksi 1: Profil & Identitas Institusi --}}
        <section class="surface p-6 border border-line/60 space-y-5">
            <div class="border-b border-line/50 pb-3">
                <h2 class="text-base font-bold text-ink">1. Identitas &amp; Profil Institusi</h2>
                <p class="text-xs text-muted mt-0.5">Nama resmi dan kontak yang ditampilkan pada antarmuka publik dan header.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="institution">Nama Resmi Institusi / Kampus</label>
                    <input required class="field" name="institution" id="institution" type="text" value="{{ old('institution', $institution) }}" placeholder="Contoh: Universitas Contoh">
                    <p class="mt-1 text-[11px] text-muted">Ditampilkan pada navbar dan dokumen laporan cetak.</p>
                </div>

                <div>
                    <label class="form-label" for="institution_code">Kode Institusi</label>
                    <input class="field font-mono" name="institution_code" id="institution_code" type="text" value="{{ old('institution_code', $institutionCode) }}" placeholder="Contoh: UNIV-01">
                    <p class="mt-1 text-[11px] text-muted">Identifikasi kode kampus untuk integrasi sistem eksternal.</p>
                </div>

                <div>
                    <label class="form-label" for="support">Email Narahubung &amp; Bantuan Teknis</label>
                    <input required class="field" name="support" id="support" type="email" value="{{ old('support', $supportEmail) }}" placeholder="bantuan@kampus.ac.id">
                    <p class="mt-1 text-[11px] text-muted">Tujuan kontak ketika pengguna mengalami kendala akses atau teknis.</p>
                </div>

                <div>
                    <label class="form-label" for="campus_domain">Domain Layanan Kampus</label>
                    <input class="field font-mono" id="campus_domain" type="text" disabled value="https://sale.campus.ac.id" readonly>
                    <p class="mt-1 text-[11px] text-muted">Domain utama portal LMS institusi (konfigurasi web server).</p>
                </div>
            </div>
        </section>

        {{-- Seksi 2: Konfigurasi Operasional Akademik --}}
        <section class="surface p-6 border border-line/60 space-y-5">
            <div class="border-b border-line/50 pb-3">
                <h2 class="text-base font-bold text-ink">2. Konfigurasi Operasional Akademik Global</h2>
                <p class="text-xs text-muted mt-0.5">Menentukan semester acuan aktif yang berlaku di seluruh portal mahasiswa dan dosen.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="semester">Semester Aktif Berjalan (Default Sistem)</label>
                    <select class="field" name="semester" id="semester">
                        @if(count($availableSemesters) > 0)
                            @foreach($availableSemesters as $sem)
                                <option value="{{ $sem['name'] }}" @selected(old('semester', $activeSemester) === $sem['name'])>
                                    {{ $sem['name'] }} ({{ $sem['code'] }})
                                </option>
                            @endforeach
                        @else
                            <option value="Ganjil 2026/2027" @selected(old('semester', $activeSemester) === 'Ganjil 2026/2027')>Ganjil 2026/2027</option>
                            <option value="Genap 2026/2027" @selected(old('semester', $activeSemester) === 'Genap 2026/2027')>Genap 2026/2027</option>
                        @endif
                    </select>
                    <p class="mt-1 text-[11px] text-muted">Pilihan semester diambil dari master data semester di Data Akademik.</p>
                </div>

                <div>
                    <label class="form-label">Kebijakan Struktur Fakultas</label>
                    <input class="field" type="text" disabled readonly value="1 Fakultas (Fakultas Ilmu Komputer)">
                    <p class="mt-1 text-[11px] text-muted">Kebijakan sistem saat ini membatasi institusi hanya mengelola 1 fakultas induk.</p>
                </div>
            </div>
        </section>

        {{-- Seksi 3: Batas Layanan & Kuota AI --}}
        <section class="surface p-6 border border-line/60 space-y-5">
            <div class="border-b border-line/50 pb-3">
                <h2 class="text-base font-bold text-ink">3. Integrasi &amp; Batas Kuota Layanan AI</h2>
                <p class="text-xs text-muted mt-0.5">Batas konsumsi token bulanan institusi untuk evaluasi otomatis, AI Tutor, dan perbaikan kode.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="ai_token_quota">Batas Kuota Token Bulanan (Token)</label>
                    <input class="field font-mono" name="ai_token_quota" id="ai_token_quota" type="number" step="10000" min="10000" value="{{ old('ai_token_quota', $aiQuota) }}">
                    <p class="mt-1 text-[11px] text-muted">Batas institusi saat ini: 1.000.000 token per bulan kalender.</p>
                </div>

                <div>
                    <label class="form-label" for="ai_model">Penyedia Model AI Utama</label>
                    <input class="field" name="ai_model" id="ai_model" type="text" value="{{ old('ai_model', $aiModel) }}">
                    <p class="mt-1 text-[11px] text-muted">Model bahasa yang digunakan agen evaluasi dan asisten belajar.</p>
                </div>
            </div>
        </section>

        {{-- Seksi 4: Keamanan & Sesi Sistem --}}
        <section class="surface p-6 border border-line/60 space-y-5">
            <div class="border-b border-line/50 pb-3">
                <h2 class="text-base font-bold text-ink">4. Keamanan Sesi &amp; Pemeliharaan</h2>
                <p class="text-xs text-muted mt-0.5">Pengaturan retensi sesi dan status operasional sistem.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="maintenance_mode">Status Operasional Sistem</label>
                    <select class="field" name="maintenance_mode" id="maintenance_mode">
                        <option value="0" @selected(old('maintenance_mode', $maintenance) === '0')>Aktif Normal (Siap Digunakan)</option>
                        <option value="1" @selected(old('maintenance_mode', $maintenance) === '1')>Mode Pemeliharaan (Maintenance)</option>
                    </select>
                    <p class="mt-1 text-[11px] text-muted">Dalam mode pemeliharaan, hanya administrator yang dapat masuk.</p>
                </div>

                <div>
                    <label class="form-label">Durasi Masa Aktif Sesi</label>
                    <input class="field" type="text" disabled readonly value="120 Menit (Otomatis Diperpanjang)">
                    <p class="mt-1 text-[11px] text-muted">Sesi pratinjau interaktif disimpan lokal pada browser sesi ini.</p>
                </div>
            </div>
        </section>

        {{-- Tombol Aksi Simpan --}}
        <div class="flex items-center justify-between pt-2">
            <p class="text-xs text-muted">Seluruh perubahan konfigurasi akan langsung berlaku dan tercatat di activity log.</p>
            <button type="submit" class="button-primary">
                Simpan Seluruh Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
