@extends('layouts.mahasiswa')

@section('title', 'Pengaturan Sistem | SALE')
@section('header', 'Pengaturan Sistem')

@section('content')
@php
    $settings = $settings ?? [];
    $institution = $settings['institution'] ?? 'Universitas Contoh';
    $activeSemester = $settings['semester'] ?? 'Ganjil 2026/2027';
    $supportEmail = $settings['support'] ?? 'akademik@example.test';
    $aiQuota = $settings['ai_token_quota'] ?? 1000000;
    $rawModel = $settings['ai_model'] ?? 'Google AI';
    $aiProvider = $settings['ai_provider'] ?? (in_array(strtolower($rawModel), ['google ai', 'open ai', 'deepseek']) ? $rawModel : 'Google AI');
    $aiModel = (!in_array(strtolower($rawModel), ['google ai', 'open ai', 'deepseek'])) ? $rawModel : 'gemini-2.5-flash';
    $aiApiKey = $settings['ai_api_key'] ?? '';
    $maintenance = $settings['maintenance_mode'] ?? '0';
    $sessionLifetime = $settings['session_lifetime'] ?? (string) config('session.lifetime', 120);
    $appName = $settings['app_name'] ?? 'SALE';
    $institutionMinistry = $settings['institution_ministry'] ?? '';
    $institutionAddress = $settings['institution_address'] ?? '';
    $institutionPhone = $settings['institution_phone'] ?? '';
    $institutionWebsite = $settings['institution_website'] ?? '';
    $institutionEmail = $settings['institution_email'] ?? '';
    $currentLogoUrl = \App\Models\SystemSetting::logoUrl();

    $selectedProviderKey = 'Google AI';
    $currentAiProvider = strtolower(old('ai_provider', $aiProvider));
    if (str_contains($currentAiProvider, 'open')) {
        $selectedProviderKey = 'Open AI';
    } elseif (str_contains($currentAiProvider, 'deep')) {
        $selectedProviderKey = 'DeepSeek';
    } else {
        $selectedProviderKey = 'Google AI';
    }

    $availableModels = \App\Services\Ai\AiModelFetcher::getModels($selectedProviderKey, $aiApiKey);
    $currentModelVal = old('ai_model', $aiModel);

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
            @if($maintenance === '1')
                <span class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-xs">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Mode Pemeliharaan Aktif
                </span>
            @else
                <span class="text-xs font-semibold text-ink">
                    Sistem Berjalan Normal
                </span>
            @endif
        </div>
    </header>
    
    {{-- Form Konfigurasi Sistem Lengkap --}}
    <form class="space-y-6" method="post" action="{{ route('admin.settings.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Seksi 1: Profil & Identitas Institusi --}}
        <section class="surface p-6 border border-line/60 space-y-5">
            <div class="border-b border-line/50 pb-3">
                <h2 class="text-base font-bold text-ink">1. Identitas &amp; Profil Institusi</h2>
                <p class="text-xs text-muted mt-0.5">Nama resmi dan kontak yang ditampilkan pada antarmuka publik dan header.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="institution">Nama Resmi Institusi / Kampus <span class="text-danger">*</span></label>
                    <input required class="field" name="institution" id="institution" type="text" value="{{ old('institution', $institution) }}" placeholder="Contoh: Universitas Contoh">
                    <p class="mt-1 text-[11px] text-muted">Ditampilkan pada navbar dan dokumen laporan cetak.</p>
                </div>

                <div>
                    <label class="form-label" for="app_name">Nama Aplikasi / Sistem</label>
                    <input class="field" name="app_name" id="app_name" type="text" value="{{ old('app_name', $appName) }}" placeholder="Contoh: SALE">
                    <p class="mt-1 text-[11px] text-muted">Nama sistem yang ditampilkan pada laporan Excel, CSV, dan header dokumen.</p>
                </div>

                <div>
                    <label class="form-label" for="support">Email Narahubung &amp; Bantuan Teknis <span class="text-danger">*</span></label>
                    <input required class="field" name="support" id="support" type="email" value="{{ old('support', $supportEmail) }}" placeholder="bantuan@kampus.ac.id">
                    <p class="mt-1 text-[11px] text-muted">Tujuan kontak ketika pengguna mengalami kendala akses atau teknis.</p>
                </div>

                <div>
                    <label class="form-label" for="institution_ministry">Nama Kementerian / Departemen</label>
                    <input class="field" name="institution_ministry" id="institution_ministry" type="text" value="{{ old('institution_ministry', $institutionMinistry) }}" placeholder="Contoh: KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI">
                    <p class="mt-1 text-[11px] text-muted">Ditampilkan pada kop surat resmi di laporan cetak PDF.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label" for="institution_address">Alamat Kampus</label>
                    <input class="field" name="institution_address" id="institution_address" type="text" value="{{ old('institution_address', $institutionAddress) }}" placeholder="Contoh: Jalan Sultan Mansyur Syah, Dompak, Tanjungpinang 29124">
                    <p class="mt-1 text-[11px] text-muted">Alamat lengkap kampus yang tercetak pada kop surat laporan PDF.</p>
                </div>

                <div>
                    <label class="form-label" for="institution_phone">Telepon / Faksimile</label>
                    <input class="field" name="institution_phone" id="institution_phone" type="text" value="{{ old('institution_phone', $institutionPhone) }}" placeholder="Contoh: Telepon (0771) 4500089, Faksimile (0771) 4500090">
                    <p class="mt-1 text-[11px] text-muted">Ditampilkan pada baris kontak kop surat laporan PDF.</p>
                </div>

                <div>
                    <label class="form-label" for="institution_website">Website Resmi Kampus</label>
                    <input class="field font-mono" name="institution_website" id="institution_website" type="text" value="{{ old('institution_website', $institutionWebsite) }}" placeholder="Contoh: http://umrah.ac.id">
                    <p class="mt-1 text-[11px] text-muted">URL website kampus yang tercetak pada kop surat laporan PDF.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label" for="institution_email">Email Resmi Institusi</label>
                    <input class="field font-mono" name="institution_email" id="institution_email" type="email" value="{{ old('institution_email', $institutionEmail) }}" placeholder="Contoh: email@umrah.ac.id">
                    <p class="mt-1 text-[11px] text-muted">Email resmi kampus yang tercetak pada kop surat laporan PDF.</p>
                </div>

                {{-- Logo Institusi (Gaya Foto Profil dengan Live Preview) --}}
                <div class="md:col-span-2 border-t border-line/60 pt-5">
                    <label class="form-label mb-2">Logo Institusi</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-5 p-4 rounded-xl border border-line/70 bg-canvas/30">
                        {{-- Avatar / Logo Container --}}
                        <div class="relative shrink-0">
                            <button type="button" onclick="document.getElementById('app_logo').click()" class="relative group cursor-pointer block h-20 w-20 shrink-0 rounded-2xl border border-[#cbd1d0] bg-white p-2 shadow-2xs hover:border-brand hover:shadow-xs transition select-none" title="Ubah Logo Institusi">
                                @if(!empty($currentLogoUrl))
                                    <img id="logo-preview-img" src="{{ $currentLogoUrl }}" alt="Logo Institusi" class="h-full w-full object-contain group-hover:opacity-90 transition" onerror="this.classList.add('hidden'); document.getElementById('logo-placeholder').classList.remove('hidden');">
                                    <div id="logo-placeholder" class="hidden flex h-full w-full flex-col items-center justify-center rounded-xl bg-slate-50 text-slate-400 group-hover:text-brand transition">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M10 11h4M10 15h4M16 11h2M16 15h2M4 7l8-4 8 4"/>
                                        </svg>
                                    </div>
                                @else
                                    <img id="logo-preview-img" src="" alt="Logo Institusi" class="hidden h-full w-full object-contain group-hover:opacity-90 transition">
                                    <div id="logo-placeholder" class="flex h-full w-full flex-col items-center justify-center rounded-xl bg-slate-50 text-slate-400 group-hover:text-brand transition">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M10 11h4M10 15h4M16 11h2M16 15h2M4 7l8-4 8 4"/>
                                        </svg>
                                    </div>
                                @endif
                                <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-brand text-white shadow-xs ring-2 ring-white group-hover:bg-brand-dark transition-colors" title="Ubah Logo">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                    </svg>
                                </span>
                            </button>
                        </div>

                        {{-- Info & Action Buttons --}}
                        <div class="space-y-2 flex-1 min-w-0">
                            <div>
                                <p class="text-sm font-bold text-ink">Pratinjau Logo Institusi</p>
                                <p class="text-xs text-muted leading-relaxed mt-0.5">
                                    Format: PNG, JPG, SVG, atau WebP. Maks. 2 MB. Logo ini akan digunakan pada seluruh laporan cetak PDF dan tampilan sistem.
                                </p>
                            </div>

                            <input class="hidden" name="app_logo" id="app_logo" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                            <input type="hidden" name="remove_logo" id="remove_logo" value="0">

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button type="button" onclick="document.getElementById('app_logo').click()" class="button-secondary text-xs py-2 px-3.5 font-semibold inline-flex items-center gap-1.5 shadow-2xs">
                                    <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span id="btn-choose-label">{{ !empty($currentLogoUrl) ? 'Ganti Logo' : 'Pilih Berkas Logo' }}</span>
                                </button>

                                <button type="button" id="btn-remove-logo" onclick="handleRemoveLogo()" class="text-xs font-semibold py-2 px-3 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" @if(!\App\Models\SystemSetting::hasCustomLogo()) hidden @endif>
                                    Hapus Logo
                                </button>

                                <span id="selected-file-info" class="text-xs text-ink font-medium truncate max-w-xs" hidden></span>
                            </div>
                        </div>
                    </div>
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
                    <input class="field" type="text" disabled readonly value="{{ ($settings['faculty_name'] ?? null) ? '1 Fakultas ('.$settings['faculty_name'].')' : 'Belum ada fakultas terdaftar' }}">
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

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Kolom Kiri: Penyedia Model AI Utama (atas) & Pilih Model dari Penyedia (bawah) --}}
                <div class="space-y-5">
                    <div>
                        <label class="form-label" for="ai_provider">Penyedia Model AI Utama</label>
                        <select class="field" name="ai_provider" id="ai_provider">
                            <option value="Google AI" @selected($selectedProviderKey === 'Google AI')>Google AI</option>
                            <option value="Open AI" @selected($selectedProviderKey === 'Open AI')>Open AI</option>
                            <option value="DeepSeek" @selected($selectedProviderKey === 'DeepSeek')>DeepSeek</option>
                        </select>
                        <p class="mt-1 text-[11px] text-muted">Platform kecerdasan buatan utama yang diintegrasikan ke sistem.</p>
                    </div>

                    <div>
                        <label class="form-label" for="ai_model">Pilih Model dari Penyedia</label>
                        <select class="field disabled:bg-canvas/80 disabled:text-muted disabled:cursor-not-allowed disabled:border-line/60" name="ai_model" id="ai_model" @disabled(empty($aiApiKey))>
                            @if(empty($aiApiKey))
                                <option value="" disabled selected>-- Kunci API belum disimpan --</option>
                            @endif
                            @foreach($availableModels as $m)
                                <option value="{{ $m['id'] }}" @selected($currentModelVal === $m['id'])>{{ $m['displayName'] }}</option>
                            @endforeach
                        </select>
                        <p id="ai_model_hint" class="mt-1 text-[11px] {{ empty($aiApiKey) ? 'text-amber-600 font-medium' : 'text-muted' }}">
                            {{ empty($aiApiKey) ? 'Kunci API belum disimpan. Masukkan dan simpan API Key terlebih dahulu untuk memilih model.' : 'Varian model bahasa yang disinkronkan langsung dari penyedia AI.' }}
                        </p>
                    </div>
                </div>

                {{-- Kolom Kanan: API & Save Koneksi Sejajar (atas) & Batas Kuota Token Bulanan (bawah) --}}
                <div class="space-y-5">
                    <div>
                        <label class="form-label" for="ai_api_key">API untuk Koneksi ke Model AI</label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input class="field font-mono text-xs pr-10" name="ai_api_key" id="ai_api_key" type="password" placeholder="Masukkan API Key model AI" value="{{ old('ai_api_key', $aiApiKey) }}" autocomplete="off">
                                <button type="button" id="toggle-ai-key" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink cursor-pointer" title="Tampilkan / Sembunyikan API Key">
                                    <svg id="eye-icon-show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg id="eye-icon-hide" class="h-4 w-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="button" id="btn-test-ai-conn" class="button-secondary text-xs font-semibold py-2 px-4 inline-flex items-center justify-center gap-2 cursor-pointer shadow-2xs shrink-0 min-h-10" title="Uji koneksi ke API dan muat daftar model">
                                <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span id="btn-ai-label">Uji Koneksi</span>
                            </button>
                        </div>
                        <div class="mt-1 flex items-center min-h-4">
                            <span id="ai-conn-status-text" class="text-xs font-semibold {{ !empty($aiApiKey) ? 'text-emerald-600' : 'text-muted' }}">{{ !empty($aiApiKey) ? 'Terhubung ke model AI' : 'Tidak terhubung ke model AI' }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="ai_token_quota">Batas Kuota Token Bulanan (Token)</label>
                        <input class="field font-mono" name="ai_token_quota" id="ai_token_quota" type="number" step="10000" min="10000" value="{{ old('ai_token_quota', $aiQuota) }}">
                        <p class="mt-1 text-[11px] text-muted">Batas institusi saat ini: {{ number_format((int) $aiQuota, 0, ',', '.') }} token per bulan kalender.</p>
                    </div>
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
                    <label class="form-label" for="session_lifetime">Durasi Masa Aktif Sesi (Menit)</label>
                    <input class="field font-mono" name="session_lifetime" id="session_lifetime" type="number" min="5" max="10080" step="5" value="{{ old('session_lifetime', $sessionLifetime) }}" placeholder="120" required>
                    <p class="mt-1 text-[11px] text-muted">Durasi kedaluwarsa sesi pengguna (dalam menit) sebelum harus masuk kembali.</p>
                </div>
            </div>
        </section>

        {{-- Keterangan Simpan --}}
        <div class="pt-2 text-xs text-muted pb-16">
            Seluruh perubahan konfigurasi akan langsung berlaku dan tercatat di riwayat aktivitas sistem.
        </div>

        {{-- Floating Action Button (Hanya Muncul Saat Ada Perubahan Pengaturan di Form) --}}
        <div id="sticky-save-bar" class="fixed bottom-6 right-6 z-40 flex items-center gap-2.5 transition-all duration-300 ease-in-out transform translate-y-12 opacity-0 pointer-events-none">
            <button type="button" id="btn-reset-form" class="button-secondary text-xs py-2 px-3.5 shadow-lg bg-white cursor-pointer">
                Batalkan
            </button>
            <button type="submit" id="btn-save-settings" class="button-primary text-xs py-2 px-4.5 cursor-pointer shadow-lg inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', () => {
    // Live preview dan manipulasi logo institusi (mirip photo profil)
    const logoInput = document.getElementById('app_logo');
    const logoPreviewImg = document.getElementById('logo-preview-img');
    const logoPlaceholder = document.getElementById('logo-placeholder');
    const btnRemoveLogo = document.getElementById('btn-remove-logo');
    const btnChooseLabel = document.getElementById('btn-choose-label');
    const selectedFileInfo = document.getElementById('selected-file-info');
    const removeLogoInput = document.getElementById('remove_logo');
    const initialLogoUrl = @json($currentLogoUrl);
    const hasCustomLogoInitially = @json(\App\Models\SystemSetting::hasCustomLogo());

    if (logoInput) {
        logoInput.addEventListener('change', () => {
            const file = logoInput.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran berkas logo melebihi 2 MB. Silakan pilih berkas yang lebih kecil.');
                logoInput.value = '';
                return;
            }
            if (removeLogoInput) removeLogoInput.value = '0';
            const reader = new FileReader();
            reader.onload = (e) => {
                if (logoPreviewImg) {
                    logoPreviewImg.src = e.target.result;
                    logoPreviewImg.classList.remove('hidden');
                }
                if (logoPlaceholder) logoPlaceholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
            if (selectedFileInfo) {
                selectedFileInfo.textContent = `${file.name} (${(file.size / 1024).toFixed(0)} KB)`;
                selectedFileInfo.hidden = false;
            }
            if (btnChooseLabel) btnChooseLabel.textContent = 'Ganti Berkas';
            if (btnRemoveLogo) {
                btnRemoveLogo.hidden = false;
                btnRemoveLogo.textContent = 'Batalkan Pilihan';
            }
        });
    }

    window.handleRemoveLogo = function () {
        if (logoInput && logoInput.files.length > 0) {
            // Membatalkan file baru yang baru dipilih
            logoInput.value = '';
            if (selectedFileInfo) {
                selectedFileInfo.textContent = '';
                selectedFileInfo.hidden = true;
            }
            if (hasCustomLogoInitially && initialLogoUrl) {
                if (logoPreviewImg) {
                    logoPreviewImg.src = initialLogoUrl;
                    logoPreviewImg.classList.remove('hidden');
                }
                if (logoPlaceholder) logoPlaceholder.classList.add('hidden');
                if (btnRemoveLogo) {
                    btnRemoveLogo.textContent = 'Hapus Logo';
                    btnRemoveLogo.hidden = false;
                }
                if (btnChooseLabel) btnChooseLabel.textContent = 'Ganti Logo';
            } else {
                if (logoPreviewImg) logoPreviewImg.classList.add('hidden');
                if (logoPlaceholder) logoPlaceholder.classList.remove('hidden');
                if (btnRemoveLogo) btnRemoveLogo.hidden = true;
                if (btnChooseLabel) btnChooseLabel.textContent = 'Pilih Berkas Logo';
            }
            if (removeLogoInput) removeLogoInput.value = '0';
        } else if (hasCustomLogoInitially) {
            // Menandai hapus logo yang sudah tersimpan
            const currentlyMarkedForRemoval = removeLogoInput && removeLogoInput.value === '1';
            if (!currentlyMarkedForRemoval) {
                if (removeLogoInput) removeLogoInput.value = '1';
                if (logoPreviewImg) logoPreviewImg.classList.add('hidden');
                if (logoPlaceholder) logoPlaceholder.classList.remove('hidden');
                if (selectedFileInfo) {
                    selectedFileInfo.textContent = 'Logo akan dihapus saat disimpan.';
                    selectedFileInfo.hidden = false;
                }
                if (btnRemoveLogo) {
                    btnRemoveLogo.textContent = 'Batal Hapus';
                }
            } else {
                if (removeLogoInput) removeLogoInput.value = '0';
                if (logoPreviewImg) {
                    logoPreviewImg.src = initialLogoUrl;
                    logoPreviewImg.classList.remove('hidden');
                }
                if (logoPlaceholder) logoPlaceholder.classList.add('hidden');
                if (selectedFileInfo) {
                    selectedFileInfo.textContent = '';
                    selectedFileInfo.hidden = true;
                }
                if (btnRemoveLogo) {
                    btnRemoveLogo.textContent = 'Hapus Logo';
                }
            }
        }
        if (typeof checkFormDirty === 'function') checkFormDirty();
    };



    const providerSelect = document.getElementById('ai_provider');
    const modelSelect = document.getElementById('ai_model');
    const modelHint = document.getElementById('ai_model_hint');
    const keyInput = document.getElementById('ai_api_key');
    const btnTest = document.getElementById('btn-test-ai-conn');
    const btnLabel = document.getElementById('btn-ai-label');
    const statusText = document.getElementById('ai-conn-status-text');
    const toggleKey = document.getElementById('toggle-ai-key');
    const eyeShow = document.getElementById('eye-icon-show');
    const eyeHide = document.getElementById('eye-icon-hide');

    let isApiSaved = {{ !empty($aiApiKey) ? 'true' : 'false' }};

    function setModelSelectState(enabled, message = null) {
        if (!modelSelect) return;
        modelSelect.disabled = !enabled;
        if (modelHint) {
            if (enabled) {
                modelHint.textContent = message || 'Varian model bahasa yang disinkronkan langsung dari penyedia AI.';
                modelHint.className = 'mt-1 text-[11px] text-muted';
            } else {
                modelHint.textContent = message || 'Kunci API belum disimpan. Masukkan dan simpan API Key terlebih dahulu untuk memilih model.';
                modelHint.className = 'mt-1 text-[11px] text-amber-600 font-medium';
            }
        }
    }

    function populateModels(models, selectedValue) {
        if (!modelSelect) return;
        const previousVal = selectedValue || modelSelect.value;
        modelSelect.innerHTML = '';
        models.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.displayName;
            if (m.id === previousVal) {
                opt.selected = true;
            }
            modelSelect.appendChild(opt);
        });
        if (!modelSelect.value && models.length > 0) {
            modelSelect.value = models[0].id;
        }
    }

    async function fetchModelsForProvider(prov) {
        if (!modelSelect || !isApiSaved) return;
        const currentKey = keyInput?.value || '';
        try {
            const res = await fetch("{{ route('admin.settings.ai-models') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ ai_provider: prov, ai_api_key: currentKey })
            });
            if (res.ok) {
                const data = await res.json();
                if (data.models && data.models.length > 0) {
                    populateModels(data.models);
                }
            }
        } catch (e) {
            console.warn('Gagal sinkronisasi model:', e);
        }
    }

    providerSelect?.addEventListener('change', () => {
        if (!isApiSaved) return;
        fetchModelsForProvider(providerSelect.value);
    });

    keyInput?.addEventListener('input', () => {
        // Jika input API key diubah/dikosongkan dan belum di-save
        if (!keyInput.value.trim()) {
            isApiSaved = false;
            setModelSelectState(false);
            if (statusText) {
                statusText.textContent = 'Tidak terhubung ke model AI';
                statusText.className = 'text-xs font-semibold text-muted';
            }
        }
    });

    toggleKey?.addEventListener('click', () => {
        const isPass = keyInput.type === 'password';
        keyInput.type = isPass ? 'text' : 'password';
        eyeShow?.classList.toggle('hidden', isPass);
        eyeHide?.classList.toggle('hidden', !isPass);
    });

    btnTest?.addEventListener('click', async () => {
        btnTest.disabled = true;
        btnLabel.textContent = 'Menguji...';
        statusText.textContent = 'Memeriksa koneksi & model...';
        statusText.className = 'text-xs text-muted font-medium';

        const csrfToken = document.querySelector('input[name="_token"]')?.value;

        try {
            const res = await fetch("{{ route('admin.settings.test-ai') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    ai_provider: providerSelect?.value,
                    ai_model: modelSelect?.value,
                    ai_api_key: keyInput?.value
                })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                isApiSaved = true;
                statusText.textContent = 'Terhubung ke model AI';
                statusText.className = 'text-xs font-semibold text-emerald-600';
                setModelSelectState(true);
                if (data.models && data.models.length > 0) {
                    populateModels(data.models, modelSelect?.value);
                }
            } else {
                statusText.textContent = data.message || 'Tidak terhubung ke model AI';
                statusText.className = 'text-xs font-semibold ' + (data.disconnected ? 'text-muted' : 'text-rose-600');
                if (data.disconnected || !data.success) {
                    isApiSaved = false;
                    setModelSelectState(false, data.disconnected ? 'Kunci API dikosongkan. Masukkan dan simpan API Key untuk memilih model.' : (data.message || 'Kunci API belum disimpan. Simpan API Key terlebih dahulu.'));
                }
                if (data.models && data.models.length > 0) {
                    populateModels(data.models, modelSelect?.value);
                }
            }
        } catch (err) {
            isApiSaved = false;
            statusText.textContent = 'Tidak terhubung ke model AI';
            statusText.className = 'text-xs font-semibold text-rose-600';
            setModelSelectState(false, 'Gagal menghubungi server untuk verifikasi kunci API.');
        } finally {
            btnTest.disabled = false;
            btnLabel.textContent = 'Uji Koneksi';
            checkFormDirty();
        }
    });

    // =========================================================================
    // Sticky Save Bar: Muncul hanya ketika ada perubahan data pada form
    // =========================================================================
    const settingsForm = document.querySelector('form[action="{{ route('admin.settings.store') }}"]');
    const stickySaveBar = document.getElementById('sticky-save-bar');
    const btnResetForm = document.getElementById('btn-reset-form');
    const btnSaveSettings = document.getElementById('btn-save-settings');

    window.resetLogoToInitialState = function () {
        if (logoInput) logoInput.value = '';
        if (removeLogoInput) removeLogoInput.value = '0';
        if (selectedFileInfo) {
            selectedFileInfo.textContent = '';
            selectedFileInfo.hidden = true;
        }
        if (hasCustomLogoInitially && initialLogoUrl) {
            if (logoPreviewImg) {
                logoPreviewImg.src = initialLogoUrl;
                logoPreviewImg.classList.remove('hidden');
            }
            if (logoPlaceholder) logoPlaceholder.classList.add('hidden');
            if (btnRemoveLogo) {
                btnRemoveLogo.textContent = 'Hapus Logo';
                btnRemoveLogo.hidden = false;
            }
            if (btnChooseLabel) btnChooseLabel.textContent = 'Ganti Logo';
        } else {
            if (logoPreviewImg) logoPreviewImg.classList.add('hidden');
            if (logoPlaceholder) logoPlaceholder.classList.remove('hidden');
            if (btnRemoveLogo) btnRemoveLogo.hidden = true;
            if (btnChooseLabel) btnChooseLabel.textContent = 'Pilih Berkas Logo';
        }
    };

    function captureFormState() {
        if (!settingsForm) return '';
        const elements = settingsForm.elements;
        const data = [];
        for (let i = 0; i < elements.length; i++) {
            const el = elements[i];
            if (!el.name || (el.type === 'hidden' && el.name === '_token')) continue;
            if (el.type === 'checkbox' || el.type === 'radio') {
                data.push(el.name + '=' + (el.checked ? '1' : '0'));
            } else if (el.type === 'file') {
                data.push(el.name + '=' + (el.files && el.files[0] ? el.files[0].name + ':' + el.files[0].size : ''));
            } else {
                data.push(el.name + '=' + el.value);
            }
        }
        return data.join('&');
    }

    let initialFormState = captureFormState();

    function checkFormDirty() {
        if (!stickySaveBar) return;
        const currentState = captureFormState();
        const isDirty = currentState !== initialFormState;
        if (isDirty) {
            stickySaveBar.classList.remove('translate-y-12', 'opacity-0', 'pointer-events-none');
            stickySaveBar.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
        } else {
            stickySaveBar.classList.add('translate-y-12', 'opacity-0', 'pointer-events-none');
            stickySaveBar.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
        }
    }

    settingsForm?.addEventListener('input', checkFormDirty);
    settingsForm?.addEventListener('change', checkFormDirty);

    btnResetForm?.addEventListener('click', () => {
        if (!settingsForm) return;
        settingsForm.reset();
        window.resetLogoToInitialState();
        checkFormDirty();
    });

    settingsForm?.addEventListener('submit', () => {
        if (modelSelect && modelSelect.disabled) {
            modelSelect.disabled = false;
        }
        if (btnSaveSettings) {
            btnSaveSettings.disabled = true;
            btnSaveSettings.textContent = 'Menyimpan Seluruh Pengaturan...';
        }
    });
});
</script>
@endsection
