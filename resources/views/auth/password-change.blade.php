<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>Ganti Password | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col justify-between bg-[#f3f6f9] font-sans text-ink antialiased">
    <header class="border-b border-line/50 bg-white py-4 shadow-sm">
        <div class="flex w-full items-center px-6 lg:w-[248px]">
            <a href="{{ route('login') }}" class="block" aria-label="SALE, halaman masuk">
                <span class="block text-xl font-semibold tracking-[-0.03em] text-ink">SALE</span>
                <span class="mt-0.5 block text-xs text-muted">Smart Academic Learning Ecosystem</span>
            </a>
        </div>
    </header>

    <main class="mx-auto my-auto w-full max-w-md px-4 py-8">
        @if($errors->any())
            <div role="alert" class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-rose-800 shadow-sm">
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

        <section class="surface p-6 sm:p-8">
            <div class="text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand-dark">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg>
                </div>
                <h1 class="mt-4 text-lg font-bold text-ink">Buat password baru</h1>
                <p class="mt-1 text-xs leading-5 text-muted">Password sementara perlu diganti sebelum Anda melanjutkan ke ruang akademik.</p>
            </div>

            <form method="POST" action="{{ route('password.change.update') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="password" class="form-label text-xs">Password baru</label>
                    <div class="relative mt-1">
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="field pr-10 text-sm" placeholder="Minimal 8 karakter">
                        <button type="button" data-password-toggle="password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" aria-label="Tampilkan password">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Minimal 8 karakter dan berbeda dari password sementara.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="form-label text-xs">Konfirmasi password baru</label>
                    <div class="relative mt-1">
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="field pr-10 text-sm" placeholder="Ketik ulang password baru">
                        <button type="button" data-password-toggle="password_confirmation" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" aria-label="Tampilkan konfirmasi password">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="button-primary w-full py-2.5 text-sm font-semibold">Simpan password baru</button>
            </form>
        </section>
    </main>

    <footer class="border-t border-line/50 bg-white px-6 py-4 text-center text-xs text-muted">SALE - Smart Academic Learning Ecosystem</footer>

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
