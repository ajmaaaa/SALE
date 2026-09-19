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

        @if(request()->is('admin-prodi*'))
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi admin prodi">
            <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-wider text-muted">Ruang Admin Prodi</p>
            <a href="{{ route('admin-prodi.dashboard') }}" @if(request()->routeIs('admin-prodi.dashboard')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin-prodi.dashboard') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft hover:text-ink' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard Prodi
            </a>
            <a href="{{ route('admin-prodi.akademik.matakuliah') }}" @if(request()->routeIs('admin-prodi.akademik.matakuliah*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin-prodi.akademik.matakuliah*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft hover:text-ink' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Mata Kuliah
            </a>
            <a href="{{ route('admin-prodi.kurikulum.index') }}" @if(request()->routeIs('admin-prodi.kurikulum.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin-prodi.kurikulum.*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft hover:text-ink' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                Kurikulum (CPL &amp; CPMK)
            </a>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" @if(request()->routeIs('admin-prodi.akademik.kelas*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin-prodi.akademik.kelas*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft hover:text-ink' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Kelas &amp; Dosen Pengampu
            </a>
            <a href="{{ route('admin-prodi.users.index') }}" @if(request()->routeIs('admin-prodi.users.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin-prodi.users.*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft hover:text-ink' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                Dosen &amp; Mahasiswa (Excel)
            </a>
            <a href="{{ route('admin-prodi.laporan.index') }}" @if(request()->routeIs('admin-prodi.laporan.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin-prodi.laporan.*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft hover:text-ink' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Laporan &amp; Ekspor Semester
            </a>
            <p class="px-3 pt-5 text-xs leading-5 text-muted">Tata kelola kurikulum, kelas, dan pelaporan capaian prodi.</p>
        </nav>
        @elseif(request()->is('admin*'))
        @php
            $isAdminProdi = (session('auth_user.role') ?? '') === 'admin_prodi' || (auth()->user()?->role?->name ?? '') === 'admin_prodi';
        @endphp
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi administrator">
            <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-wider text-muted">
                {{ $isAdminProdi ? 'Ruang Admin Prodi' : 'Administrasi Sistem' }}
            </p>
            @if($isAdminProdi)
                @foreach(['akademik'=>'Data akademik prodi','laporan'=>'Laporan akademik'] as $section=>$label)
                    <a href="{{ route('admin.page',$section) }}" @if(request()->is('admin/'.$section.'*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->is('admin/'.$section.'*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft' }}"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h4"/></svg>{{ $label }}</a>
                @endforeach
            @else
                @foreach(['dashboard'=>'Dashboard','akademik'=>'Data akademik','pengguna'=>'Pengguna & hak akses','monitoring'=>'Monitoring sistem','laporan'=>'Laporan','pengaturan'=>'Pengaturan sistem'] as $section=>$label)
                    <a href="{{ route('admin.page',$section) }}" @if(request()->is('admin/'.$section.'*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->is('admin/'.$section.'*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft' }}"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h4"/></svg>{{ $label }}</a>
                @endforeach
            @endif
        </nav>
        @elseif(request()->is('dosen*') || (request()->is('join-kelas*') && (auth()->user()?->hasRole(\App\Models\Role::DOSEN) || (session('auth_user.role') ?? '') === 'dosen')) || (request()->is('join-kelas*') && (auth()->user()?->role?->name ?? '') === 'dosen'))
        @php
            $currentSection = request()->route('section');
            $currentSectionId = is_object($currentSection) ? $currentSection->id : ($currentSection ?? session('last_active_section_id'));
            $isPenilaianActive = request()->routeIs('dosen.penilaian.index', 'dosen.penilaian.matriks', 'dosen.penilaian.asesmen*');
            $isRekapActive = request()->routeIs('dosen.rekap.*', 'dosen.penilaian.rekap', 'dosen.penilaian.cpmk', 'dosen.penilaian.cpl', 'dosen.penilaian.export*');
            $isCpmkActive = request()->routeIs('dosen.penilaian.rekap', 'dosen.penilaian.cpmk');
            $isCplActive = request()->routeIs('dosen.penilaian.cpl');
        @endphp
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi dosen">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang mengajar</p>
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dosen.dashboard') }}" 
                   @if(request()->routeIs('dosen.dashboard')) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.dashboard') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/></svg>
                    Dashboard
                </a>

                <!-- Course Saya -->
                <a href="{{ route('dosen.course.index') }}" 
                   @if(request()->routeIs('dosen.course.*', 'dosen.item.*')) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.course.*', 'dosen.item.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                    Course saya
                </a>

                <!-- Penilaian OBE -->
                <a href="{{ route('dosen.penilaian.index') }}" 
                   @if($isPenilaianActive) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isPenilaianActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/><path d="M9 14l2 2 4-4"/></svg>
                    Penilaian OBE
                </a>

                <!-- Rekap Nilai -->
                <a href="{{ $currentSectionId ? route('dosen.penilaian.rekap', $currentSectionId) : route('dosen.rekap.index') }}" 
                   @if($isRekapActive) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isRekapActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Rekap Nilai
                </a>
            </div>
            <p class="px-3 pt-5 text-xs leading-5 text-muted">Materi, tugas, RPS, dan pengumuman dikelola dari course masing-masing.</p>
        </nav>
        @elseif(request()->is('kaprodi*'))
        <nav class="flex-1 px-3 py-5" aria-label="Navigasi kaprodi">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang Kaprodi</p>
            <div class="space-y-1">
                <a href="{{ route('kaprodi.monitoring.cpmk') }}" @if(request()->routeIs('kaprodi.monitoring.cpmk')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('kaprodi.monitoring.cpmk') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10M6 14h6"/></svg>
                    Monitoring CPMK
                </a>
                <a href="{{ route('kaprodi.monitoring.cpl') }}" @if(request()->routeIs('kaprodi.monitoring.cpl')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('kaprodi.monitoring.cpl') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Monitoring CPL
                </a>
                <a href="{{ route('kaprodi.export.index') }}" @if(request()->routeIs('kaprodi.export.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('kaprodi.export.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Export Rekap Nilai
                </a>
            </div>
            <p class="px-3 pt-5 text-xs leading-5 text-muted">Akses pemantauan ketercapaian CPMK dan CPL program studi serta ekspor rekapitulasi nilai.</p>
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
                <a href="{{ route('mahasiswa.nilai') }}" @if(request()->routeIs('mahasiswa.nilai*', 'mahasiswa.obe.progress')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.nilai*', 'mahasiswa.obe.progress') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Nilai &amp; Capaian OBE
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
            $rawRole = auth()->check() ? (is_string(auth()->user()->role) ? auth()->user()->role : (auth()->user()->role?->name ?? 'dosen')) : (session('auth_user.role') ?? (
                request()->is('admin-prodi*') ? 'admin_prodi' :
                (request()->is('admin*') ? 'admin' :
                (request()->is('dosen*') ? 'dosen' :
                (request()->is('kaprodi*') ? 'kaprodi' : 'mahasiswa')))
            ));

            $activeUser = auth()->check() ? [
                'name' => auth()->user()->name,
                'number' => auth()->user()->nim_nidn ?? auth()->user()->email,
                'role' => $rawRole,
                'role_label' => auth()->user()->role?->label ?? (
                    match($rawRole) {
                        'admin' => 'Admin Sistem',
                        'admin_prodi' => 'Admin Prodi',
                        'kaprodi' => 'Kaprodi',
                        'dosen' => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                        default => ucfirst($rawRole)
                    }
                ),
                'email' => auth()->user()->email,
            ] : (session('auth_user') ?? (
                request()->is('admin-prodi*') ? \App\Support\AdminPreview::users()[4] :
                (request()->is('admin*') ? \App\Support\AdminPreview::users()[3] :
                (request()->is('dosen*') ? \App\Support\AdminPreview::users()[2] :
                (request()->is('kaprodi*') ? \App\Support\AdminPreview::users()[5] :
                \App\Support\AdminPreview::users()[1])))
            ));

            $roleName = is_string($activeUser['role'] ?? '') ? $activeUser['role'] : ($activeUser['role']['name'] ?? 'user');
            $roleLabel = $activeUser['role_label'] ?? (
                match($roleName) {
                    'admin' => 'Admin Sistem',
                    'admin_prodi' => 'Admin Prodi',
                    'kaprodi' => 'Kaprodi',
                    'dosen' => 'Dosen',
                    'mahasiswa' => 'Mahasiswa',
                    default => ucfirst($roleName)
                }
            );
            $initials = collect(explode(' ', $activeUser['name'] ?? 'User'))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('');
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
                                <span class="block text-xs text-muted">{{ $activeUser['number'] ?? '—' }} · {{ $roleLabel }}</span>
                            </span>
                            <span class="flex h-9 w-9 items-center justify-center rounded-full border border-[#cbd1d0] bg-brand-soft text-sm font-semibold text-brand-dark">
                                {{ $initials }}
                            </span>
                            <svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 w-72 rounded-xl border border-line bg-white p-3 shadow-xl">
                            <div class="border-b border-line/60 pb-3">
                                <p class="text-sm font-bold text-ink">{{ $activeUser['name'] }}</p>
                                <p class="text-xs text-muted">{{ $activeUser['email'] ?? 'user@example.test' }}</p>
                                <span class="mt-1.5 inline-block rounded bg-brand-soft px-2 py-0.5 text-[11px] font-semibold text-brand">
                                    {{ $roleLabel }} · {{ $activeUser['number'] ?? '—' }}
                                </span>
                            </div>
                            <div class="py-2">
                                <p class="px-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-muted">Beralih Peran Cepat (5 Role)</p>
                                <a href="{{ route('switch-role', 'mahasiswa') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Mahasiswa (Ahmad Maulana)</span>
                                    @if($roleName === 'mahasiswa')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                                <a href="{{ route('switch-role', 'dosen') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Dosen (Budi Santoso, M.Kom.)</span>
                                    @if($roleName === 'dosen')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                                <a href="{{ route('switch-role', 'kaprodi') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Kaprodi (Dr. H. Kaprodi, M.T.)</span>
                                    @if($roleName === 'kaprodi')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                                <a href="{{ route('switch-role', 'admin_prodi') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Admin Prodi (Kurikulum &amp; Akademik)</span>
                                    @if($roleName === 'admin_prodi')
                                        <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                                <a href="{{ route('switch-role', 'admin') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs text-ink hover:bg-canvas">
                                    <span>Admin Sistem (Pengaturan &amp; Sistem)</span>
                                    @if($roleName === 'admin')
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
