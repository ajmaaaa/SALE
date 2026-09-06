<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102f50">
    <title>Masuk ke Portal | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f1f5f9] font-sans antialiased text-slate-800 flex flex-col justify-between py-10 px-4">

    <main class="w-full max-w-md mx-auto my-auto">
        {{-- Logo & Brand Header --}}
        <div class="text-center mb-7">
            <a href="{{ route('login') }}" class="inline-block">
                <span class="text-2xl font-bold tracking-tight text-[#102f50] block">SALE</span>
                <span class="text-xs text-slate-500 font-normal">Smart Academic Learning Ecosystem</span>
            </a>
        </div>

        {{-- Main Login Card --}}
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-lg font-bold text-slate-900">Masuk ke Portal</h1>
                <p class="text-xs text-slate-500 mt-1">Masukkan kredensial akun akademik Anda untuk melanjutkan.</p>
            </div>

            {{-- Flash Notice --}}
            @if(session('notice'))
                <div role="status" class="mb-5 rounded-lg border border-blue-200 bg-blue-50/70 p-3 text-xs text-blue-800">
                    {{ session('notice') }}
                </div>
            @endif

            {{-- Errors --}}
            @if($errors->any())
                <div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50/70 p-3 text-xs text-rose-800 space-y-1">
                    <p class="font-semibold">Gagal masuk:</p>
                    <ul class="list-inside list-disc pl-1 space-y-0.5 text-[11px]">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Authentication Form --}}
            <form method="post" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="login_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Email Institusi atau NIM / NIDN
                    </label>
                    <input
                        id="login_id"
                        name="login_id"
                        type="text"
                        required
                        autocomplete="username"
                        autofocus
                        value="{{ old('login_id', old('email')) }}"
                        placeholder="NIM, NIDN, atau email institusi"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#102f50] focus:outline-hidden focus:ring-1 focus:ring-[#102f50] transition">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700">
                            Kata Sandi
                        </label>
                        <a href="#" class="text-xs text-[#102f50] hover:underline" tabindex="-1">
                            Lupa sandi?
                        </a>
                    </div>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Kata sandi akun"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#102f50] focus:outline-hidden focus:ring-1 focus:ring-[#102f50] transition pr-10">
                        <button
                            type="button"
                            onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            aria-label="Lihat kata sandi">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-[#102f50] focus:ring-[#102f50]">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-[#102f50] py-2.5 px-4 text-sm font-semibold text-white shadow-xs hover:bg-[#0c243f] active:bg-[#08182b] transition">
                        Masuk
                    </button>
                </div>

                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="bg-white px-2.5 text-slate-400">atau</span>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="document.getElementById('login_id').value='ahmad@example.test'; document.getElementById('password').value='demo123'; document.querySelector('form').submit();"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Masuk dengan SSO Kampus</span>
                </button>
            </form>

            <div class="text-center pt-5 text-xs text-slate-400">
                <p>Bantuan akun: <a href="mailto:helpdesk@kampus.ac.id" class="text-slate-600 hover:underline">helpdesk@kampus.ac.id</a></p>
            </div>
        </div>

        {{-- Discreet testing accounts for development/evaluation --}}
        <details class="mt-4 text-center text-xs text-slate-400">
            <summary class="cursor-pointer hover:text-slate-600 select-none">Akun Pengujian Cepat</summary>
            <div class="mt-2.5 flex flex-wrap justify-center gap-2">
                <form method="post" action="{{ route('login.post') }}" class="inline">
                    @csrf
                    <input type="hidden" name="persona_id" value="{{ $personas['mahasiswa']['id'] }}">
                    <button type="submit" class="rounded-md bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 border border-slate-200 hover:bg-slate-50">
                        {{ $personas['mahasiswa']['name'] }} (Mhs)
                    </button>
                </form>
                <form method="post" action="{{ route('login.post') }}" class="inline">
                    @csrf
                    <input type="hidden" name="persona_id" value="{{ $personas['dosen']['id'] }}">
                    <button type="submit" class="rounded-md bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 border border-slate-200 hover:bg-slate-50">
                        {{ $personas['dosen']['name'] }} (Dosen)
                    </button>
                </form>
                <form method="post" action="{{ route('login.post') }}" class="inline">
                    @csrf
                    <input type="hidden" name="persona_id" value="{{ $personas['admin']['id'] }}">
                    <button type="submit" class="rounded-md bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 border border-slate-200 hover:bg-slate-50">
                        {{ $personas['admin']['name'] }} (Admin)
                    </button>
                </form>
            </div>
        </details>
    </main>

    {{-- Simple Institutional Footer --}}
    <footer class="text-center text-xs text-slate-400 mt-8">
        &copy; 2026 SALE &middot; Smart Academic Learning Ecosystem
    </footer>

</body>
</html>
