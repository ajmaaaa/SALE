<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>Masuk | SALE - Smart Academic Learning Ecosystem</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans antialiased text-ink flex flex-col lg:flex-row overflow-x-hidden">

    {{-- Kolom Kiri: Sisi Biru dengan Konten Asli, Gradasi Modern, dan Corak Course --}}
    <section class="relative hidden bg-gradient-to-br from-[#0e2740] via-[#12385b] to-[#1c5384] px-10 py-12 lg:px-14 lg:py-16 text-white lg:flex lg:flex-col lg:justify-between lg:w-1/2 overflow-hidden select-none" aria-label="Tentang SALE">
        {{-- Ambient Glow Effects --}}
        <div class="pointer-events-none absolute -top-28 -left-28 h-96 w-96 rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 -right-28 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl"></div>

        {{-- Corak SVG seperti pada cover course card --}}
        <svg class="absolute -right-6 -top-6 h-64 w-64 text-white opacity-[0.14] pointer-events-none" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/>
        </svg>
        <svg class="absolute -left-10 -bottom-10 h-72 w-72 text-white opacity-[0.10] pointer-events-none" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/>
        </svg>

        <div class="relative z-10">
            <a href="{{ route('login') }}" class="flex items-baseline gap-2.5" aria-label="SALE, halaman masuk">
                <span class="text-xl font-bold tracking-tight text-white">SALE</span>
                <span class="text-xs text-[#b9cedf]">Portal Akademik</span>
            </a>
        </div>

        <div class="relative z-10 max-w-md my-auto py-12">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#b9cedf]">Portal akademik</p>
            <h1 class="mt-4 text-3xl xl:text-4xl font-semibold leading-tight tracking-[-0.025em]">Satu ruang untuk aktivitas perkuliahan.</h1>
            <p class="mt-4 text-sm leading-6 text-[#d5e1ea]">Akses course, materi, asesmen, dan administrasi sesuai peran akun institusi Anda.</p>
        </div>

        <div class="relative z-10">
            <p class="text-xs text-[#b9cedf]">Smart Academic Learning Ecosystem</p>
        </div>
    </section>

    {{-- Kolom Kanan: Form Login Asli --}}
    <section class="flex flex-col justify-between px-6 py-8 sm:px-12 sm:py-12 lg:px-16 lg:py-16 lg:w-1/2 bg-white flex-1 min-h-screen lg:min-h-0">
        <div class="lg:hidden flex items-baseline gap-2.5 mb-6">
            <span class="text-lg font-semibold tracking-[-0.03em] text-ink">SALE</span>
            <span class="text-xs text-muted">Smart Academic Learning Ecosystem</span>
        </div>

        <div class="my-auto mx-auto w-full max-w-sm">
            <div class="mb-7 text-center">
                <h2 class="text-2xl font-bold tracking-[-0.025em] text-ink uppercase">MASUK KE SALE</h2>
            </div>

            @if($isMaintenance ?? (\App\Models\SystemSetting::valueFor('maintenance_mode', '0') === '1'))
                <p class="mb-4 text-center text-xs font-medium text-rose-600">
                    Mode Pemeliharaan: Hanya akun Administrator yang dapat masuk.
                </p>
            @endif

            @if(session('notice'))
                <div role="status" class="mb-5 rounded-lg border border-brand/20 bg-[#f1f6fa] px-4 py-3 text-sm leading-6 text-[#27465f]">
                    {{ session('notice') }}
                </div>
            @endif

            @if($errors->any())
                <div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50/80 px-4 py-3 text-rose-800">
                    <p class="text-sm font-semibold">Gagal masuk</p>
                    <div class="mt-1 space-y-0.5 text-xs leading-5 text-rose-700">
                        @foreach(array_unique($errors->all()) as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form class="space-y-5" method="post" action="{{ route('login.post') }}">
                @csrf
                <div>
                    <label for="login_id" class="form-label">Email atau NIM / NIDN</label>
                    <input id="login_id" name="login_id" type="text" required autocomplete="username" autofocus class="field mt-1.5" placeholder="nama@kampus.ac.id atau NIM" value="{{ old('login_id', old('email')) }}">
                </div>

                <div>
                    <label for="password" class="form-label">Kata Sandi</label>
                    <div class="relative mt-1.5">
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="field pr-10" placeholder="Masukkan kata sandi">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer" aria-label="Tampilkan kata sandi">
                            <svg id="eye-icon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eye-off-icon" class="h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                <line x1="2" y1="2" x2="22" y2="22"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="button-primary w-full py-2.5 font-semibold">
                        Masuk
                    </button>
                </div>
            </form>
        </div>

        <footer class="pt-6 text-center text-xs text-muted">
            SALE &copy; {{ date('Y') }}
        </footer>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');

            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    if (eyeIcon && eyeOffIcon) {
                        eyeIcon.classList.toggle('hidden', isPassword);
                        eyeOffIcon.classList.toggle('hidden', !isPassword);
                    }
                    toggleBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                });
            }
        });
    </script>
</body>
</html>
