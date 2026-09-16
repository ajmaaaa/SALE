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

    <aside data-sidebar data-open="false" class="fixed inset-y-0 left-0 z-50 flex w-[248px] -translate-x-full flex-col bg-white shadow-[2px_0_16px_rgba(29,39,48,0.03)] transition-transform data-[open=true]:translate-x-0 lg:translate-x-0">
        <div class="px-6 pb-4 pt-6">
            <a href="{{ route('mahasiswa.dashboard') }}" class="block" aria-label="SALE, halaman utama">
                <span class="block text-xl font-semibold tracking-[-0.03em] text-ink">SALE</span>
                <span class="mt-0.5 block text-xs text-muted">{{ session('admin.settings.institution','Smart Academic Learning Ecosystem') }}</span>
            </a>
        </div>

        @if(request()->is('admin*'))
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi administrator">
            <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-wider text-muted">Administrasi</p>
            @foreach(['dashboard'=>'Dashboard','akademik'=>'Data akademik','pengguna'=>'Pengguna & hak akses','aktivitas'=>'Activity log','monitoring'=>'Monitoring sistem','laporan'=>'Laporan','pengaturan'=>'Pengaturan sistem'] as $section=>$label)
            <a href="{{ route('admin.page',$section) }}" @if(request()->is('admin/'.$section)) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->is('admin/'.$section) ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft' }}"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h4"/></svg>{{ $label }}</a>
            @endforeach
        </nav>
        @elseif(request()->is('dosen*'))
        @php
            $isCourseActive = request()->routeIs('dosen.course.*') || request()->routeIs('dosen.item.*');
            $isGradesActive = request()->routeIs('dosen.grades*') || request()->routeIs('dosen.academic*') || request()->routeIs('dosen.penilaian.*');
            $isGradebookActive = request()->routeIs('dosen.gradebook*') || request()->routeIs('dosen.scores.*');
        @endphp
        <nav class="flex-1 px-3 py-5" aria-label="Navigasi dosen">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang Mengajar</p>
            <div class="space-y-1">
                <a href="{{ route('dosen.dashboard') }}" @if(request()->routeIs('dosen.dashboard')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.dashboard') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('dosen.course.index') }}" @if($isCourseActive) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isCourseActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                    Course Saya
                </a>
                <a href="{{ route('dosen.grades') }}" @if($isGradesActive) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isGradesActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Penilaian Saya
                </a>
                <a href="{{ route('dosen.gradebook') }}" @if($isGradebookActive) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isGradebookActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Rekap Nilai
                </a>
            </div>
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
                <a href="{{ route('mahasiswa.nilai') }}" @if(request()->routeIs('mahasiswa.nilai')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.nilai') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Nilai &amp; CPMK
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
    </aside>

    <div class="min-h-screen lg:pl-[248px]">
        @php
            $activeUser = session('auth_user') ?? (
                request()->is('admin*') ? \App\Support\AdminPreview::users()[3] :
                (request()->is('dosen*') ? \App\Support\AdminPreview::users()[2] : \App\Support\AdminPreview::users()[1])
            );
            $initials = collect(explode(' ', $activeUser['name']))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('');
        @endphp
        <header class="sticky top-0 z-30 bg-white/95 shadow-[0_2px_12px_rgba(29,39,48,0.07)] backdrop-blur-sm">
            <div class="flex h-16 w-full items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button data-sidebar-toggle type="button" aria-label="Buka navigasi" aria-expanded="false" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-ink shadow-sm hover:bg-brand-soft lg:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">@yield('header', 'Dashboard')</p>
                        <p class="hidden truncate text-xs text-muted sm:block">Semester {{ session('admin.settings.semester','Ganjil 2026/2027') }}</p>
                    </div>
                </div>
                <div class="relative">
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg p-1.5 hover:bg-[#eceeeb] focus:outline-none">
                            <span class="hidden text-right sm:block">
                                <span class="block text-sm font-semibold leading-4 text-ink">{{ $activeUser['name'] }}</span>
                                <span class="block text-xs text-muted">{{ $activeUser['number'] ?? '—' }} · {{ ucfirst($activeUser['role']) }}</span>
                            </span>
                            <span class="flex h-9 w-9 items-center justify-center rounded-full border border-[#cbd1d0] bg-brand-soft text-sm font-semibold text-brand-dark">
                                {{ $initials }}
                            </span>
                            <svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-line bg-white p-3 shadow-xl">
                            <div class="border-b border-line/60 pb-3">
                                <p class="text-sm font-bold text-ink">{{ $activeUser['name'] }}</p>
                                <p class="text-xs text-muted">{{ $activeUser['email'] ?? 'user@example.test' }}</p>
                                <span class="mt-1.5 inline-block rounded bg-brand-soft px-2 py-0.5 text-[11px] font-semibold text-brand">
                                    {{ ucfirst($activeUser['role']) }} · {{ $activeUser['number'] ?? '—' }}
                                </span>
                            </div>
                            <div class="py-2">
                                <p class="px-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-muted">Beralih Peran Cepat</p>
                                <a href="{{ route('switch-role', 'mahasiswa') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Mahasiswa (Ahmad)</span>
                                    @if($activeUser['role'] === 'mahasiswa')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                                <a href="{{ route('switch-role', 'dosen') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Dosen (Dr. Budi Santoso)</span>
                                    @if($activeUser['role'] === 'dosen')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                                <a href="{{ route('switch-role', 'admin') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Admin (Akademik)</span>
                                    @if($activeUser['role'] === 'admin')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                            </div>
                            <div class="border-t border-line/60 pt-2">
                                <a href="{{ route('login') }}" class="block rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-slate-100">
                                    Halaman Masuk (Login)
                                </a>
                                <a href="{{ route('logout') }}" class="block rounded-lg px-2.5 py-1.5 text-xs font-semibold text-danger hover:bg-danger/10">
                                    Keluar (Logout)
                                </a>
                            </div>
                        </div>
                    </details>
                </div>
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
