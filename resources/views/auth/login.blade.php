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
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="field text-sm" placeholder="••••••••">
                </div>

                <div class="pt-2">
                    <button type="submit" class="button-primary w-full py-2.5 text-sm font-semibold">
                        Masuk
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-line/60 pt-4">
                <details class="text-xs group" open>
                    <summary class="cursor-pointer font-semibold text-brand flex items-center justify-between py-1">
                        <span>ℹ️ Akun Demo / Uji Coba Cepat</span>
                        <span class="text-muted group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <div class="mt-3 space-y-2 text-[11px] text-muted bg-canvas p-3 rounded-lg border border-line">
                        <p class="font-medium text-ink">Klik salah satu akun untuk mengisi kredensial otomatis (Password: <code>password</code>):</p>
                        <div class="grid grid-cols-1 gap-1.5 pt-1">
                            <button type="button" onclick="fillLogin('231011401234', 'password')" class="text-left px-2.5 py-1.5 rounded bg-white hover:bg-slate-100 border border-line text-ink flex justify-between items-center cursor-pointer">
                                <span><strong>Mahasiswa:</strong> Ahmad Maulana (231011401234)</span>
                                <span class="text-brand font-semibold text-[10px]">Pilih →</span>
                            </button>
                            <button type="button" onclick="fillLogin('198501012010121001', 'password')" class="text-left px-2.5 py-1.5 rounded bg-white hover:bg-slate-100 border border-line text-ink flex justify-between items-center cursor-pointer">
                                <span><strong>Dosen:</strong> Dr. Budi Santoso (198501012010121001)</span>
                                <span class="text-brand font-semibold text-[10px]">Pilih →</span>
                            </button>
                            <button type="button" onclick="fillLogin('197501012000031001', 'password')" class="text-left px-2.5 py-1.5 rounded bg-white hover:bg-slate-100 border border-line text-ink flex justify-between items-center cursor-pointer">
                                <span><strong>Kaprodi:</strong> Dr. H. Kaprodi, M.T. (197501012000031001)</span>
                                <span class="text-brand font-semibold text-[10px]">Pilih →</span>
                            </button>
                            <button type="button" onclick="fillLogin('AP001', 'password')" class="text-left px-2.5 py-1.5 rounded bg-white hover:bg-slate-100 border border-line text-ink flex justify-between items-center cursor-pointer">
                                <span><strong>Admin Prodi:</strong> AP001</span>
                                <span class="text-brand font-semibold text-[10px]">Pilih →</span>
                            </button>
                            <button type="button" onclick="fillLogin('ADM001', 'password')" class="text-left px-2.5 py-1.5 rounded bg-white hover:bg-slate-100 border border-line text-ink flex justify-between items-center cursor-pointer">
                                <span><strong>Admin Sistem:</strong> ADM001</span>
                                <span class="text-brand font-semibold text-[10px]">Pilih →</span>
                            </button>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </main>

    <script>
        function fillLogin(id, pwd) {
            document.getElementById('login_id').value = id;
            document.getElementById('password').value = pwd;
        }
    </script>

    <footer class="border-t border-line/50 bg-white px-6 py-4 text-center text-xs text-muted">
        SALE · Smart Academic Learning Ecosystem
    </footer>
</body>
</html>
