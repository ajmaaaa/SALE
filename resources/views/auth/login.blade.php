<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>Masuk Portal Akademik | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f6f9] font-sans antialiased text-ink flex flex-col justify-between">
    <header class="border-b border-line/50 bg-white px-6 py-4 shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between">
            <a href="{{ route('login') }}" class="flex items-center gap-2">
                <span class="text-xl font-bold tracking-tight text-ink">SALE</span>
                <span class="text-xs text-muted">Smart Academic Learning Ecosystem</span>
            </a>
            <span class="text-xs font-semibold text-brand bg-brand-soft px-3 py-1 rounded-full">
                Semester Ganjil 2026/2027
            </span>
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

        {{-- Main Login Card --}}
        <div class="surface p-6 sm:p-8 rounded-2xl border border-line/70 shadow-md">
            {{-- Role Switcher Tabs --}}
            <div class="mb-6 flex rounded-xl bg-slate-200/80 p-1 text-xs font-semibold">
                <button type="button" id="tab-mahasiswa" onclick="selectRole('mahasiswa')" class="flex-1 rounded-lg py-2.5 text-center transition">
                    Mahasiswa
                </button>
                <button type="button" id="tab-dosen" onclick="selectRole('dosen')" class="flex-1 rounded-lg py-2.5 text-center transition">
                    Dosen
                </button>
                <button type="button" id="tab-admin" onclick="selectRole('admin')" class="flex-1 rounded-lg py-2.5 text-center transition">
                    Admin
                </button>
            </div>

            {{-- Header info --}}
            <div id="role-header-mahasiswa" class="role-header">
                <h1 class="text-lg font-bold text-ink">Masuk Portal Mahasiswa</h1>
                <p class="mt-1 text-xs text-muted">Akses ruang belajar, tugas, kuis, dan pencapaian CPMK Anda.</p>
            </div>
            <div id="role-header-dosen" class="role-header hidden">
                <h1 class="text-lg font-bold text-ink">Masuk Portal Dosen</h1>
                <p class="mt-1 text-xs text-muted">Akses ruang mengajar, kelola course, dan kumpulkan asesmen OBE.</p>
            </div>
            <div id="role-header-admin" class="role-header hidden">
                <h1 class="text-lg font-bold text-ink">Masuk Portal Administrator</h1>
                <p class="mt-1 text-xs text-muted">Kelola data master akademik, pengguna, dan konfigurasi sistem.</p>
            </div>

            {{-- Standard Credential Form --}}
            <form class="mt-5 space-y-4" method="post" action="{{ route('login.post') }}">
                @csrf
                <input type="hidden" name="role" id="role_input" value="{{ $defaultRole ?? 'mahasiswa' }}">

                <div>
                    <label for="login_id" id="login_label" class="form-label text-xs font-semibold">Email atau NIM / NIDN</label>
                    <input id="login_id" name="login_id" type="text" autocomplete="username" class="field text-sm" placeholder="nama@kampus.ac.id atau Nomor Induk" value="{{ old('login_id') }}">
                </div>

                <div>
                    <label for="password" class="form-label text-xs font-semibold">Kata Sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" class="field text-sm" placeholder="••••••••">
                </div>

                <div class="pt-2">
                    <button type="submit" id="btn-submit" class="button-primary w-full py-2.5 text-sm font-bold shadow-sm">
                        Masuk sebagai Mahasiswa
                    </button>
                </div>
            </form>

            {{-- Divider --}}
            <div class="relative my-6 text-center">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-line/60"></div></div>
                <span class="relative bg-white px-3 text-[11px] font-semibold text-muted uppercase tracking-wider">Atau Akses Cepat</span>
            </div>

            {{-- Quick Persona Login Buttons --}}
            <div class="space-y-2.5">
                <form method="post" action="{{ route('login.post') }}">
                    @csrf
                    <input type="hidden" name="persona_id" value="1">
                    <button type="submit" id="quick-mahasiswa" class="w-full rounded-xl border border-line/80 bg-canvas/70 px-4 py-2.5 text-left text-xs font-medium hover:bg-brand-soft hover:border-brand/40 hover:text-brand transition flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand/10 font-bold text-brand text-xs">M</span>
                            <div>
                                <p class="font-bold text-ink text-xs">Ahmad Maulana</p>
                                <p class="text-[11px] text-muted">231011401234 · Mahasiswa</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-brand">Masuk</span>
                    </button>
                </form>

                <form method="post" action="{{ route('login.post') }}">
                    @csrf
                    <input type="hidden" name="persona_id" value="2">
                    <button type="submit" id="quick-dosen" class="w-full rounded-xl border border-line/80 bg-canvas/70 px-4 py-2.5 text-left text-xs font-medium hover:bg-brand-soft hover:border-brand/40 hover:text-brand transition flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-800 text-xs">D</span>
                            <div>
                                <p class="font-bold text-ink text-xs">Dr. Budi Santoso, M.Kom.</p>
                                <p class="text-[11px] text-muted">DSN001 · Dosen Pengampu</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-brand">Masuk</span>
                    </button>
                </form>

                <form method="post" action="{{ route('login.post') }}">
                    @csrf
                    <input type="hidden" name="persona_id" value="3">
                    <button type="submit" id="quick-admin" class="w-full rounded-xl border border-line/80 bg-canvas/70 px-4 py-2.5 text-left text-xs font-medium hover:bg-brand-soft hover:border-brand/40 hover:text-brand transition flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-purple-100 font-bold text-purple-800 text-xs">A</span>
                            <div>
                                <p class="font-bold text-ink text-xs">Admin Akademik</p>
                                <p class="text-[11px] text-muted">ADM001 · Administrator</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-brand">Masuk</span>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="border-t border-line/50 bg-white px-6 py-4 text-center text-xs text-muted">
        SALE · Smart Academic Learning Ecosystem
    </footer>

    <script>
        function selectRole(role) {
            const roles = ['mahasiswa', 'dosen', 'admin'];
            if (!roles.includes(role)) role = 'mahasiswa';

            document.getElementById('role_input').value = role;

            roles.forEach(r => {
                const tab = document.getElementById('tab-' + r);
                const header = document.getElementById('role-header-' + r);
                if (r === role) {
                    tab.className = 'flex-1 rounded-lg py-2.5 text-center transition bg-brand text-white shadow-sm font-bold';
                    if (header) header.classList.remove('hidden');
                } else {
                    tab.className = 'flex-1 rounded-lg py-2.5 text-center transition text-muted hover:text-ink font-semibold';
                    if (header) header.classList.add('hidden');
                }
            });

            const loginLabel = document.getElementById('login_label');
            const loginInput = document.getElementById('login_id');
            const btnSubmit = document.getElementById('btn-submit');

            if (role === 'dosen') {
                loginLabel.innerText = 'Email Dosen atau NIDN';
                loginInput.placeholder = 'budi@example.test atau NIDN';
                btnSubmit.innerText = 'Masuk sebagai Dosen';
            } else if (role === 'admin') {
                loginLabel.innerText = 'Email Admin atau ID Pegawai';
                loginInput.placeholder = 'admin@example.test atau ID Admin';
                btnSubmit.innerText = 'Masuk sebagai Admin';
            } else {
                loginLabel.innerText = 'Email Mahasiswa atau NIM';
                loginInput.placeholder = 'ahmad@example.test atau 231011401234';
                btnSubmit.innerText = 'Masuk sebagai Mahasiswa';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const initialRole = "{{ $defaultRole ?? 'mahasiswa' }}";
            selectRole(initialRole);
        });
    </script>
</body>
</html>
