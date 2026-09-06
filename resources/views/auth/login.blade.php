<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>Masuk ke Portal Akademik | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-ink bg-slate-50">

    <div class="min-h-screen grid lg:grid-cols-12">

        {{-- Left Hero Panel: Institutional Branding & Overview --}}
        <div class="relative hidden lg:flex lg:col-span-5 flex-col justify-between p-12 xl:p-16 bg-gradient-to-br from-[#0a192f] via-[#102f50] to-[#1a4473] text-white overflow-hidden">
            {{-- Ambient glow & grid --}}
            <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-blue-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>

            {{-- Top: Brand Logo --}}
            <div class="relative z-10">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur border border-white/20 text-white font-black text-xl shadow-inner">
                        S
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight block text-white leading-none">SALE</span>
                        <span class="text-[11px] text-blue-200/80 font-medium">Smart Academic Learning Ecosystem</span>
                    </div>
                </a>
            </div>

            {{-- Center: Institutional Value & Features --}}
            <div class="relative z-10 my-auto py-10 space-y-8">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-blue-200 backdrop-blur border border-white/15">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Portal Resmi Institusi
                    </span>
                    <h2 class="mt-4 text-2xl xl:text-3xl font-bold tracking-tight text-white leading-snug">
                        Ekosistem Akademik &amp; Pembelajaran Berkelanjutan
                    </h2>
                    <p class="mt-3 text-sm text-blue-100/80 leading-relaxed max-w-md">
                        Mengintegrasikan perkuliahan berbasis Capaian Pembelajaran Lulusan (OBE), praktikum koding interaktif, dan transparansi evaluasi mahasiswa.
                    </p>
                </div>

                <div class="space-y-4 pt-2 border-t border-white/15">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white text-xs font-bold">
                            01
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Pemetaan CPMK &amp; CPL Otomatis</h3>
                            <p class="text-xs text-blue-200/70 mt-0.5">Penilaian tugas, kuis, dan ujian terukur langsung ke kurikulum.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white text-xs font-bold">
                            02
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Interactive Coding Workbench</h3>
                            <p class="text-xs text-blue-200/70 mt-0.5">Editor kode dengan eksekusi terminal sandbox dan asisten cerdas.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white text-xs font-bold">
                            03
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Kolaborasi &amp; Forum Diskusi</h3>
                            <p class="text-xs text-blue-200/70 mt-0.5">Ruang diskusi tematik per topik materi kuliah bersama dosen.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom: Security & Accreditation --}}
            <div class="relative z-10 pt-6 border-t border-white/15 flex items-center justify-between text-xs text-blue-200/60">
                <span>Terhubung ke Pangkalan Data Akademik</span>
                <span>Enkripsi SSL 256-bit</span>
            </div>
        </div>

        {{-- Right Panel: Real Authentication Form --}}
        <div class="lg:col-span-7 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 xl:px-24 bg-white">
            <div class="mx-auto w-full max-w-md space-y-8">

                {{-- Mobile Brand Header (hidden on large screens) --}}
                <div class="flex items-center justify-between lg:hidden pb-4 border-b border-line/60">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#102f50] text-white font-bold text-sm">
                            S
                        </div>
                        <div>
                            <span class="font-bold text-ink">SALE</span>
                            <span class="text-xs text-muted block -mt-1">Portal Akademik</span>
                        </div>
                    </div>
                    <span class="text-xs text-muted">Akses Resmi</span>
                </div>

                {{-- Welcome Title --}}
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">Masuk ke Portal</h1>
                    <p class="mt-2 text-xs sm:text-sm text-muted">
                        Gunakan identitas akun resmi (Email Institusi atau NIM / NIDN) untuk mengakses portal perkuliahan.
                    </p>
                </div>

                {{-- Flash Notifications --}}
                @if(session('notice'))
                    <div role="status" class="rounded-xl border border-blue-200 bg-blue-50/80 p-4 text-xs font-medium text-blue-800 flex items-center gap-2.5">
                        <span class="h-2 w-2 rounded-full bg-blue-600 shrink-0"></span>
                        <span>{{ session('notice') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Terjadi kesalahan autentikasi:
                        </p>
                        <ul class="list-inside list-disc pl-1 space-y-0.5 text-[11px]">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Official Login Form --}}
                <form method="post" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    {{-- Username / Email / NIM field --}}
                    <div>
                        <label for="login_id" class="block text-xs font-semibold text-ink mb-1.5">
                            Email Institusi atau NIM / NIDN
                        </label>
                        <div class="relative">
                            <input
                                id="login_id"
                                name="login_id"
                                type="text"
                                required
                                autocomplete="username"
                                autofocus
                                value="{{ old('login_id', old('email')) }}"
                                placeholder="nama@kampus.ac.id atau nomor induk"
                                class="w-full rounded-xl border border-line bg-canvas/40 px-4 py-3 text-sm text-ink placeholder:text-muted/60 focus:border-brand focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-brand/15 transition">
                        </div>
                        <p class="mt-1.5 text-[11px] text-muted">Mahasiswa dapat memasukkan 12 digit NIM; Dosen/Staf menggunakan NIDN atau email institusi.</p>
                    </div>

                    {{-- Password field --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-ink">
                                Kata Sandi
                            </label>
                            <a href="#" class="text-xs font-semibold text-brand hover:underline" tabindex="-1">
                                Lupa kata sandi?
                            </a>
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi akun"
                                class="w-full rounded-xl border border-line bg-canvas/40 px-4 py-3 text-sm text-ink placeholder:text-muted/60 focus:border-brand focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-brand/15 transition pr-11">
                            <button
                                type="button"
                                onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-muted hover:text-ink"
                                aria-label="Lihat kata sandi">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 text-xs text-muted cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-line text-brand focus:ring-brand">
                            <span class="text-ink font-medium">Ingat sesi saya di perangkat ini</span>
                        </label>
                    </div>

                    {{-- Submit button --}}
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-[#102f50] py-3 px-4 text-sm font-bold text-white shadow-sm hover:bg-[#0d2642] active:bg-[#091a2e] transition flex items-center justify-center gap-2">
                            <span>Masuk ke Portal</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>

                    {{-- Divider --}}
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-line/70"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="bg-white px-3 text-muted">atau masuk dengan</span>
                        </div>
                    </div>

                    {{-- SSO Institusi Button --}}
                    <button
                        type="button"
                        onclick="document.getElementById('login_id').value='ahmad@example.test'; document.getElementById('password').value='demo123'; document.querySelector('form').submit();"
                        class="w-full rounded-xl border border-line/80 bg-canvas/30 py-2.5 px-4 text-xs font-semibold text-ink hover:bg-slate-100 transition flex items-center justify-center gap-2.5">
                        <svg class="h-4 w-4 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Single Sign-On (SSO Kampus)</span>
                    </button>
                </form>

                {{-- Support and Helpdesk info --}}
                <div class="text-center pt-2 text-xs text-muted space-y-1">
                    <p>Mengalami kendala saat masuk? Hubungi <a href="mailto:helpdesk@institusi.ac.id" class="font-semibold text-ink hover:underline">Helpdesk IT Kampus</a></p>
                    <p class="text-[11px] text-muted/80">Layanan beroperasi Senin – Jumat, 08:00 – 16:00 WIB</p>
                </div>

                {{-- Collapsible Quick Testing Helper (Collapsed by default, subtle for development convenience) --}}
                <details class="rounded-xl border border-line/50 bg-canvas/40 p-3.5 text-xs text-muted space-y-2.5 transition">
                    <summary class="cursor-pointer font-medium text-muted hover:text-ink select-none flex items-center justify-between">
                        <span>Akses Pengujian Cepat (Khusus Simulasi &amp; Pengembang)</span>
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="pt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 border-t border-line/40">
                        <form method="post" action="{{ route('login.post') }}">
                            @csrf
                            <input type="hidden" name="persona_id" value="{{ $personas['mahasiswa']['id'] }}">
                            <button type="submit" class="w-full rounded-lg bg-white p-2.5 text-left border border-line/60 hover:border-brand shadow-2xs transition">
                                <p class="font-bold text-xs text-ink">{{ $personas['mahasiswa']['name'] }}</p>
                                <p class="text-[10px] text-muted">Mahasiswa · NIM: {{ $personas['mahasiswa']['number'] }}</p>
                            </button>
                        </form>

                        <form method="post" action="{{ route('login.post') }}">
                            @csrf
                            <input type="hidden" name="persona_id" value="{{ $personas['dosen']['id'] }}">
                            <button type="submit" class="w-full rounded-lg bg-white p-2.5 text-left border border-line/60 hover:border-brand shadow-2xs transition">
                                <p class="font-bold text-xs text-ink">{{ $personas['dosen']['name'] }}</p>
                                <p class="text-[10px] text-muted">Dosen · NIDN: {{ $personas['dosen']['number'] }}</p>
                            </button>
                        </form>

                        <form method="post" action="{{ route('login.post') }}">
                            @csrf
                            <input type="hidden" name="persona_id" value="{{ $personas['admin']['id'] }}">
                            <button type="submit" class="w-full rounded-lg bg-white p-2.5 text-left border border-line/60 hover:border-brand shadow-2xs transition">
                                <p class="font-bold text-xs text-ink">{{ $personas['admin']['name'] }}</p>
                                <p class="text-[10px] text-muted">Admin · NIP: {{ $personas['admin']['number'] }}</p>
                            </button>
                        </form>
                    </div>
                </details>

            </div>
        </div>

    </div>

</body>
</html>
