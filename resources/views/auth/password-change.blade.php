<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#102f50">
    <title>Ganti Password | SALE - Smart Academic Learning Ecosystem</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-[#0e2740] via-[#12385b] to-[#1c5384] font-sans antialiased text-white flex flex-col justify-between items-center p-6 sm:p-10 relative overflow-x-hidden select-none">

    {{-- Ambient Glow Effects --}}
    <div class="pointer-events-none absolute top-12 left-[18%] h-[380px] w-[380px] rounded-full bg-cyan-400/15 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-16 right-[15%] h-[420px] w-[420px] rounded-full bg-blue-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-20 h-[350px] w-[350px] rounded-full bg-cyan-500/10 blur-3xl"></div>

    {{-- Corak SVG Pendukung tersebar secara organik di latar belakang --}}
    {{-- Corak 1: Tree Network di area kanan atas melayang miring --}}
    <svg class="absolute top-8 right-[10%] xl:right-[16%] h-72 w-72 text-white opacity-[0.13] rotate-12 pointer-events-none" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/>
    </svg>

    {{-- Corak 2: Node Network melayang di sisi kiri tengah --}}
    <svg class="absolute top-[36%] -left-8 sm:left-[4%] xl:left-[8%] h-72 w-72 text-white opacity-[0.12] -rotate-12 pointer-events-none" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/>
    </svg>

    {{-- Corak 3: Browser / Code Window di area kanan bawah --}}
    <svg class="absolute bottom-12 right-[18%] xl:right-[24%] h-52 w-52 text-white opacity-[0.10] rotate-6 pointer-events-none hidden md:block" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/>
    </svg>

    {{-- Corak 4: Arsitektur di area kiri atas --}}
    <svg class="absolute top-24 left-[14%] xl:left-[18%] h-44 w-44 text-white opacity-[0.08] -rotate-6 pointer-events-none hidden sm:block" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <rect x="15" y="20" width="34" height="22" rx="4"/><rect x="70" y="20" width="34" height="22" rx="4"/><rect x="43" y="79" width="34" height="22" rx="4"/><path d="M49 31h21M32 42v24h28v13M87 42v24H60"/>
    </svg>

    {{-- Header Top Bar: SALE & Portal Akademik di Kiri Atas Layar --}}
    <header class="w-full flex items-baseline justify-between z-20 mb-4 sm:mb-0">
        <a href="{{ route('login') }}" class="flex items-baseline gap-2.5 focus:outline-none" aria-label="SALE, halaman masuk">
            <span class="text-2xl font-bold tracking-tight text-white">SALE</span>
            <span class="text-xs sm:text-sm text-[#b9cedf] font-medium">Portal Akademik</span>
        </a>
    </header>

    {{-- Bagian Tengah: Pop-up Card Ganti Password --}}
    <div class="my-auto w-full flex flex-col items-center z-10 py-6 sm:py-8">
        <main class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 sm:p-8 border border-white/20 text-ink">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center text-brand-dark">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg>
                </div>
                <h1 class="mt-4 text-xl font-bold tracking-tight text-ink">Buat password baru</h1>
                <p class="mt-1 text-xs leading-5 text-muted">Password sementara perlu diganti sebelum Anda melanjutkan ke ruang akademik.</p>
            </div>

            @if($errors->any())
                <div role="alert" class="mt-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-rose-800 shadow-sm">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold">Password belum dapat disimpan</p>
                        <ul class="mt-1 space-y-0.5 text-xs leading-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.change.update') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="password" class="form-label text-xs">Password baru</label>
                    <div class="relative mt-1">
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="field pr-10 text-sm" placeholder="Minimal 8 karakter">
                        <button type="button" data-password-toggle="password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer" aria-label="Tampilkan password">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Minimal 8 karakter dan berbeda dari password sementara.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="form-label text-xs">Konfirmasi password baru</label>
                    <div class="relative mt-1">
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="field pr-10 text-sm" placeholder="Ketik ulang password baru">
                        <button type="button" data-password-toggle="password_confirmation" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer" aria-label="Tampilkan konfirmasi password">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="button-primary w-full py-2.5 text-sm font-semibold">Simpan password baru</button>
                </div>
            </form>
        </main>
    </div>

    {{-- Footer --}}
    <footer class="w-full max-w-5xl text-center text-xs text-[#b9cedf] z-20 mt-4 sm:mt-0">
        Smart Academic Learning Ecosystem &copy; {{ date('Y') }}
    </footer>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.passwordToggle);
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
            });
        });
    </script>
</body>
</html>
