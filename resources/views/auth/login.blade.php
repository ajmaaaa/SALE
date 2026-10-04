<!DOCTYPE html>
<html lang="id" class="min-h-full bg-[#0e2740]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#102f50">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk | SALE - Smart Academic Learning Ecosystem</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=4">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=4">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=4">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=4">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=4">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=4">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Mencegah icon mata ganda dari native browser (Microsoft Edge & WebKit) */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }
    </style>
</head>
<body class="min-h-screen min-h-[100dvh] bg-gradient-to-br from-[#0e2740] via-[#12385b] to-[#1c5384] bg-fixed font-sans antialiased text-white flex flex-col justify-between items-center px-4 py-4 sm:p-10 relative overflow-x-hidden select-none">

    {{-- Kontainer Dekoratif Latar Belakang (Fixed & Overflow Hidden agar tidak memicu scroll/cut-off di HP) --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        {{-- Ambient Glow Effects --}}
        <div class="absolute top-12 left-[18%] h-[380px] w-[380px] rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="absolute bottom-16 right-[15%] h-[420px] w-[420px] rounded-full bg-blue-500/20 blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 h-[350px] w-[350px] rounded-full bg-cyan-500/10 blur-3xl"></div>

        {{-- Corak SVG Pendukung tersebar secara organik di latar belakang --}}
        {{-- Corak 1: Tree Network di area kanan atas melayang miring --}}
        <svg class="absolute top-8 right-[10%] xl:right-[16%] h-72 w-72 text-white opacity-[0.13] rotate-12" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/>
        </svg>

        {{-- Corak 2: Node Network melayang di sisi kiri tengah --}}
        <svg class="absolute top-[36%] -left-8 sm:left-[4%] xl:left-[8%] h-72 w-72 text-white opacity-[0.12] -rotate-12" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/>
        </svg>

        {{-- Corak 3: Browser / Code Window di area kanan bawah --}}
        <svg class="absolute bottom-12 right-[18%] xl:right-[24%] h-52 w-52 text-white opacity-[0.10] rotate-6 hidden md:block" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/>
        </svg>

        {{-- Corak 4: Arsitektur di area kiri atas --}}
        <svg class="absolute top-24 left-[14%] xl:left-[18%] h-44 w-44 text-white opacity-[0.08] -rotate-6 hidden sm:block" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="15" y="20" width="34" height="22" rx="4"/><rect x="70" y="20" width="34" height="22" rx="4"/><rect x="43" y="79" width="34" height="22" rx="4"/><path d="M49 31h21M32 42v24h28v13M87 42v24H60"/>
        </svg>
    </div>

    {{-- Header Top Bar: Brand di Kiri Atas Layar --}}
    <header class="w-full flex items-start justify-between z-20 mb-3 sm:mb-0">
        <a href="{{ route('login') }}" class="flex items-center gap-2.5 focus:outline-none group" aria-label="Smart Academic Learning Ecosystem, halaman masuk">
            <img src="{{ asset('icon/white_icon.svg') }}" alt="Logo SALE" class="h-9 w-9 shrink-0 object-contain">
            <span class="text-xs font-bold leading-tight text-white max-w-[170px]">
                Smart Academic Learning Ecosystem
            </span>
        </a>
    </header>

    {{-- Bagian Tengah: Teks Pengantar & Pop-up Card Login --}}
    <div class="my-auto w-full flex flex-col items-center z-10 py-3 sm:py-8">
        {{-- Teks Pengantar Proporsional & Rapi (Tanpa orphan word / wrapping canggung) --}}
        <div class="text-center mb-4 sm:mb-8 max-w-xl mx-auto w-full px-2 sm:px-4">
            <h1 class="text-lg sm:text-2xl font-semibold tracking-tight text-white sm:whitespace-nowrap">
                Satu ruang untuk aktivitas perkuliahan
            </h1>
            <p class="mt-1.5 sm:mt-2 text-xs sm:text-sm text-[#cbdbe8] max-w-md mx-auto leading-relaxed text-balance">
                Akses course, materi, asesmen, dan administrasi sesuai peran akun institusi Anda.
            </p>
        </div>

        {{-- Kotak / Card Pop-Up Login di Tengah Layar --}}
        <main class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 sm:p-8 border border-white/20 text-ink">
            <div class="mb-5 sm:mb-7 text-center">
                <h2 class="text-xl sm:text-2xl font-bold tracking-[-0.025em] text-ink uppercase">MASUK KE SALE</h2>
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

            <form class="space-y-4 sm:space-y-5" method="post" action="{{ route('login.post') }}">
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
                            <svg id="eye-off-icon" class="h-4 w-4 hidden" style="display: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        </main>
    </div>

    {{-- Footer --}}
    <footer class="w-full max-w-5xl text-center text-xs text-[#b9cedf] z-20 mt-3 sm:mt-0">
        Smart Academic Learning Ecosystem &copy; {{ date('Y') }}
    </footer>

    <script nonce="{{ $cspNonce }}">
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
                        eyeIcon.style.display = isPassword ? 'none' : 'block';
                        eyeOffIcon.style.display = isPassword ? 'block' : 'none';
                    }
                    toggleBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                });
            }
        });

        // Tangani navigasi kembali browser (BFCache) agar token CSRF tidak kedaluwarsa (mencegah 419)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
