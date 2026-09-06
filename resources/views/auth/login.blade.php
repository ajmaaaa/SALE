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
            <a href="{{ route('mahasiswa.dashboard') }}" class="flex items-center gap-2">
                <span class="text-xl font-bold tracking-tight text-ink">SALE</span>
                <span class="text-xs text-muted">Portal Akademik</span>
            </a>
            <a href="{{ route('mahasiswa.dashboard') }}" class="quiet-link text-xs">
                Lewati ke pratinjau →
            </a>
        </div>
    </header>

    <main class="mx-auto my-auto w-full max-w-4xl px-4 py-8 sm:px-6">
        @if(session('notice'))
            <div role="status" class="mb-6 rounded-lg bg-brand-soft p-4 text-sm font-medium text-brand">
                {{ session('notice') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mb-6 rounded-lg bg-white p-4 text-sm text-danger shadow-sm">
                <p class="font-semibold">Gagal masuk:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
            {{-- Left column: Persona quick switcher --}}
            <div class="space-y-4">
                <div>
                    <h1 class="text-xl font-bold text-ink">Masuk Satu Klik (Demo Persona)</h1>
                    <p class="mt-1 text-xs text-muted">Klik salah satu akun untuk langsung mencoba peran masing-masing.</p>
                </div>

                <div class="space-y-3">
                    {{-- Mahasiswa --}}
                    <form method="post" action="{{ route('login.post') }}">
                        @csrf
                        <input type="hidden" name="persona_id" value="{{ $personas['mahasiswa']['id'] }}">
                        <button type="submit" class="group flex w-full items-center justify-between rounded-xl bg-white p-4 text-left shadow-sm hover:shadow-md transition">
                            <div class="flex items-center gap-3.5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-canvas text-sm font-bold text-ink">
                                    AM
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-sm font-semibold text-ink group-hover:text-brand">{{ $personas['mahasiswa']['name'] }}</h2>
                                        <span class="text-xs text-muted font-normal">Mahasiswa</span>
                                    </div>
                                    <p class="text-xs text-muted">NIM: {{ $personas['mahasiswa']['number'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-brand">Pilih →</span>
                        </button>
                    </form>

                    {{-- Dosen --}}
                    <form method="post" action="{{ route('login.post') }}">
                        @csrf
                        <input type="hidden" name="persona_id" value="{{ $personas['dosen']['id'] }}">
                        <button type="submit" class="group flex w-full items-center justify-between rounded-xl bg-white p-4 text-left shadow-sm hover:shadow-md transition">
                            <div class="flex items-center gap-3.5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-canvas text-sm font-bold text-ink">
                                    BS
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-sm font-semibold text-ink group-hover:text-brand">{{ $personas['dosen']['name'] }}</h2>
                                        <span class="text-xs text-muted font-normal">Dosen</span>
                                    </div>
                                    <p class="text-xs text-muted">NIDN: {{ $personas['dosen']['number'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-brand">Pilih →</span>
                        </button>
                    </form>

                    {{-- Admin --}}
                    <form method="post" action="{{ route('login.post') }}">
                        @csrf
                        <input type="hidden" name="persona_id" value="{{ $personas['admin']['id'] }}">
                        <button type="submit" class="group flex w-full items-center justify-between rounded-xl bg-white p-4 text-left shadow-sm hover:shadow-md transition">
                            <div class="flex items-center gap-3.5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-canvas text-sm font-bold text-ink">
                                    AD
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-sm font-semibold text-ink group-hover:text-brand">{{ $personas['admin']['name'] }}</h2>
                                        <span class="text-xs text-muted font-normal">Administrator</span>
                                    </div>
                                    <p class="text-xs text-muted">NIP: {{ $personas['admin']['number'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-brand">Pilih →</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right column: Standard Credentials Form --}}
            <div class="surface p-6 sm:p-7">
                <h2 class="text-base font-bold text-ink">Masuk Akun Institusi</h2>
                <p class="mt-1 text-xs text-muted">Gunakan alamat email yang terdaftar pada sistem.</p>

                <form class="mt-5 space-y-4" method="post" action="{{ route('login.post') }}">
                    @csrf
                    <div>
                        <label for="email" class="form-label text-xs">Alamat Email</label>
                        <input id="email" name="email" type="email" required autocomplete="email" class="field text-sm" placeholder="nama@kampus.ac.id" value="{{ old('email', 'ahmad@example.test') }}">
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="form-label text-xs">Kata Sandi</label>
                            <span class="text-xs text-muted">Contoh: bebas</span>
                        </div>
                        <input id="password" name="password" type="password" class="field text-sm" placeholder="••••••••" value="demo123">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="button-primary w-full py-2.5 text-sm font-semibold">
                            Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="border-t border-line/50 bg-white px-6 py-4 text-center text-xs text-muted">
        SALE · Smart Academic Learning Ecosystem
    </footer>
</body>
</html>
