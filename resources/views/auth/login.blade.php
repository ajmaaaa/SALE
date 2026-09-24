<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>Masuk | SALE - Smart Academic Learning Ecosystem</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f6f9] font-sans antialiased text-ink flex flex-col justify-between">
    <header class="border-b border-line/50 bg-white px-6 py-4 shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between">
            <a href="{{ route('login') }}" class="flex items-center gap-2">
                <span class="text-xl font-bold tracking-tight text-ink">SALE</span>
                <span class="text-xs text-muted">Portal Akademik</span>
            </a>
        </div>
    </header>

    <main class="mx-auto my-auto w-full max-w-md px-4 py-8">
        @if(session('notice'))
            <div role="status" class="mb-6 rounded-lg bg-brand-soft p-4 text-sm font-medium text-brand">
                {{ session('notice') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mb-6 rounded-lg bg-white p-4 text-sm text-danger shadow-sm border border-danger/20">
                <p class="font-semibold">Gagal masuk:</p>
                <ul class="mt-1 list-inside list-disc text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="surface p-6 sm:p-8">
            <h2 class="text-base font-bold text-ink">Masuk Akun Institusi</h2>
            <p class="mt-1 text-xs text-muted">Gunakan alamat email atau nomor induk yang terdaftar pada sistem.</p>

            <form class="mt-5 space-y-4" method="post" action="{{ route('login.post') }}">
                @csrf
                <div>
                    <label for="login_id" class="form-label text-xs">Email atau NIM / NIDN</label>
                    <input id="login_id" name="login_id" type="text" required autocomplete="username" autofocus class="field text-sm" placeholder="nama@kampus.ac.id atau NIM" value="{{ old('login_id', old('email')) }}">
                </div>

                <div>
                    <label for="password" class="form-label text-xs">Kata Sandi</label>
                    <div class="relative mt-1">
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="field text-sm pr-10" placeholder="••••••••">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Tampilkan kata sandi">
                            <svg id="eye-icon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
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

                <div class="pt-2">
                    <button type="submit" class="button-primary w-full py-2.5 text-sm font-semibold">
                        Masuk
                    </button>
                </div>
            </form>

        </div>
    </main>

    <footer class="border-t border-line/50 bg-white px-6 py-4 text-center text-xs text-muted">
        SALE - Smart Academic Learning Ecosystem
    </footer>

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
