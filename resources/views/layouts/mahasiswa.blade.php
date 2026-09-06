<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f5f2">
    <title>@yield('title', 'SALE')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <a href="#main-content" class="fixed left-3 top-3 z-[70] -translate-y-20 rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white focus:translate-y-0">
        Lewati ke konten utama
    </a>

    <div data-sidebar-backdrop data-open="false" class="fixed inset-0 z-40 hidden bg-ink/30 data-[open=true]:block lg:hidden"></div>

    <aside data-sidebar data-open="false" class="fixed inset-y-0 left-0 z-50 flex w-[248px] -translate-x-full flex-col border-r border-line bg-white transition-transform data-[open=true]:translate-x-0 lg:translate-x-0">
        <div class="px-6 pb-4 pt-6">
            <a href="{{ route('mahasiswa.dashboard') }}" class="block" aria-label="SALE, halaman utama">
                <span class="block text-xl font-semibold tracking-[-0.03em] text-ink">SALE</span>
                <span class="mt-0.5 block text-xs text-muted">Smart Academic Learning Ecosystem</span>
            </a>
        </div>

        @if(request()->is('dosen*'))
        <nav class="flex-1 space-y-2 px-4 py-5" aria-label="Navigasi dosen">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-muted">Ruang mengajar</p>
            @foreach(['dosen.dashboard' => 'Dashboard', 'dosen.course.index' => 'Course saya', 'dosen.grades' => 'Penilaian'] as $route => $label)
                <a href="{{ route($route) }}" class="block rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs($route) || ($route === 'dosen.course.index' && request()->routeIs('dosen.course.*','dosen.item.*')) ? 'bg-brand-dark text-white' : 'hover:bg-brand-soft' }}">{{ $label }}</a>
            @endforeach
            <p class="px-3 pt-5 text-xs leading-5 text-muted">Materi, tugas, RPS, dan pengumuman dikelola dari course masing-masing.</p>
        </nav>
        @else
        <nav class="flex-1 px-3 py-5" aria-label="Navigasi mahasiswa">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang belajar</p>
            <div class="space-y-1">
                <a href="{{ route('mahasiswa.dashboard') }}" @if(request()->routeIs('mahasiswa.dashboard')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.dashboard') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('mahasiswa.course.index') }}" @if(request()->routeIs('mahasiswa.course.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.course.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                    Course
                </a>
                <a href="{{ route('mahasiswa.assignment.index') }}" @if(request()->routeIs('mahasiswa.assignment.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.assignment.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M7 4h10l2 2v14H5V4z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
                    Tugas &amp; Kuis

                </a>
                <a href="{{ route('mahasiswa.discussion.index') }}" @if(request()->routeIs('mahasiswa.discussion.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.discussion.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                    Forum Diskusi
                </a>
            </div>

            <p class="px-3 pb-2 pt-7 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Akun</p>
            <div class="space-y-1">
                <a href="{{ route('mahasiswa.notifications') }}" class="flex min-h-10 w-full items-center gap-3 rounded-md px-3 text-left text-[14px] font-medium text-[#4d5964] hover:bg-[#f0f1ee] hover:text-ink">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8zM10 20h4"/></svg>
                    Notifikasi

                </a>
                <a href="{{ route('mahasiswa.profile.index') }}" @if(request()->routeIs('mahasiswa.profile.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.profile.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                    Profil &amp; Pengaturan
                </a>
            </div>
        </nav>
        @endif
        <div class="border-t border-line p-4 text-xs text-muted"><p class="mb-2">Pratinjau peran</p><div class="flex gap-4"><a class="quiet-link" href="{{ route('mahasiswa.course.index') }}">Mahasiswa</a><a class="quiet-link" href="{{ route('dosen.dashboard') }}">Dosen</a></div></div>
    </aside>

    <div class="min-h-screen lg:pl-[248px]">
        <header class="sticky top-0 z-30 bg-white/95 shadow-[0_2px_12px_rgba(29,39,48,0.07)] backdrop-blur-sm">
            <div class="flex h-16 w-full items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button data-sidebar-toggle type="button" aria-label="Buka navigasi" aria-expanded="false" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-ink shadow-sm hover:bg-brand-soft lg:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">@yield('header', 'Dashboard')</p>
                        <p class="hidden truncate text-xs text-muted sm:block">Semester Ganjil 2026/2027</p>
                    </div>
                </div>
                <a href="{{ route('mahasiswa.profile.index') }}" class="flex items-center gap-3 rounded-md p-1.5 hover:bg-[#eceeeb]">
                    <span class="hidden text-right sm:block">
                        <span class="block text-sm font-semibold leading-4 text-ink">{{ request()->is('dosen*') ? 'Budi Santoso' : 'Ahmad' }}</span>
                        <span class="block text-xs text-muted">{{ request()->is('dosen*') ? 'Dosen' : '231011401234' }}</span>
                    </span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-sm font-semibold text-brand-dark">AM</span>
                </a>
            </div>
        </header>

        <main id="main-content" class="page-shell">
            @if(session('notice'))<div role="status" class="mb-5 rounded-lg border border-line bg-brand-soft p-4 text-sm">{{ session('notice') }}</div>@endif
            @if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-danger bg-white p-4 text-sm text-danger"><p class="font-semibold">Periksa kembali isian berikut.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>

    </div>
</body>
</html>
