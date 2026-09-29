@extends('layouts.mahasiswa')

@section('title', 'Pengaturan Sistem | SALE')
@section('header', 'Pengaturan Sistem')

@section('content')
@php
    $settings = $settings ?? [];
    $institution = $settings['institution'] ?? 'Universitas Contoh';
    $institutionCode = $settings['institution_code'] ?? 'UNIV-01';
    $activeSemester = $settings['semester'] ?? 'Ganjil 2026/2027';
    $supportEmail = $settings['support'] ?? 'akademik@example.test';
    $aiQuota = $settings['ai_token_quota'] ?? 1000000;
    $rawModel = $settings['ai_model'] ?? 'Google AI';
    $aiProvider = $settings['ai_provider'] ?? (in_array(strtolower($rawModel), ['google ai', 'open ai', 'deepseek']) ? $rawModel : 'Google AI');
    $aiModel = (!in_array(strtolower($rawModel), ['google ai', 'open ai', 'deepseek'])) ? $rawModel : 'gemini-2.5-flash';
    $aiApiKey = $settings['ai_api_key'] ?? '';
    $maintenance = $settings['maintenance_mode'] ?? '0';
    $sessionLifetime = $settings['session_lifetime'] ?? (string) config('session.lifetime', 120);

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
                        <select class="field" name="ai_model" id="ai_model">
                            @foreach($availableModels as $m)
                                <option value="{{ $m['id'] }}" @selected($currentModelVal === $m['id'])>{{ $m['displayName'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-muted">Varian model bahasa yang disinkronkan langsung dari penyedia AI.</p>
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
                            <button type="button" id="btn-test-ai-conn" class="button-secondary text-xs font-semibold py-2 px-4 inline-flex items-center justify-center gap-2 cursor-pointer shadow-2xs shrink-0 min-h-10">
                                <span id="btn-ai-label">Save</span>
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

        {{-- Tombol Aksi Simpan --}}
        <div class="flex items-center justify-between pt-2">
            <p class="text-xs text-muted">Seluruh perubahan konfigurasi akan langsung berlaku dan tercatat di activity log.</p>
            <button type="submit" class="button-primary">
                Simpan Seluruh Pengaturan
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const providerSelect = document.getElementById('ai_provider');
    const modelSelect = document.getElementById('ai_model');
    const keyInput = document.getElementById('ai_api_key');
    const btnTest = document.getElementById('btn-test-ai-conn');
    const btnLabel = document.getElementById('btn-ai-label');
    const statusText = document.getElementById('ai-conn-status-text');
    const toggleKey = document.getElementById('toggle-ai-key');
    const eyeShow = document.getElementById('eye-icon-show');
    const eyeHide = document.getElementById('eye-icon-hide');

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
        if (!modelSelect) return;
        const currentKey = keyInput?.value || '';
        try {
            const url = "{{ route('admin.settings.ai-models') }}?ai_provider=" + encodeURIComponent(prov) + "&ai_api_key=" + encodeURIComponent(currentKey);
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
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
        fetchModelsForProvider(providerSelect.value);
    });

    toggleKey?.addEventListener('click', () => {
        const isPass = keyInput.type === 'password';
        keyInput.type = isPass ? 'text' : 'password';
        eyeShow?.classList.toggle('hidden', isPass);
        eyeHide?.classList.toggle('hidden', !isPass);
    });

    btnTest?.addEventListener('click', async () => {
        btnTest.disabled = true;
        btnLabel.textContent = 'Menyimpan...';
        statusText.textContent = 'Memeriksa...';
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
                statusText.textContent = 'Terhubung ke model AI';
                statusText.className = 'text-xs font-semibold text-emerald-600';
                if (data.models && data.models.length > 0) {
                    populateModels(data.models, modelSelect?.value);
                }
            } else {
                statusText.textContent = data.message || 'Tidak terhubung ke model AI';
                statusText.className = 'text-xs font-semibold ' + (data.disconnected ? 'text-muted' : 'text-red-600');
                if (data.models && data.models.length > 0) {
                    populateModels(data.models, modelSelect?.value);
                }
            }
        } catch (err) {
            statusText.textContent = 'Tidak terhubung ke model AI';
            statusText.className = 'text-xs font-semibold text-red-600';
        } finally {
            btnTest.disabled = false;
            btnLabel.textContent = 'Save';
        }
    });
});
</script>
@endsection
