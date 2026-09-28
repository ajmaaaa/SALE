<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f5f2">
    <title>@yield('title', 'SALE')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { scrollbar-gutter: stable; }
        dialog { position: fixed !important; inset: 0 !important; margin: auto !important; }
    </style>
</head>
<body class="min-h-screen font-sans antialiased">
    <a href="#main-content" class="fixed left-3 top-3 z-[70] -translate-y-20 rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white focus:translate-y-0">
        Lewati ke konten utama
    </a>

    <div data-sidebar-backdrop data-open="false" class="fixed inset-0 z-40 hidden bg-ink/30 data-[open=true]:block lg:hidden"></div>

    <aside data-sidebar data-open="false" class="fixed inset-y-0 left-0 z-50 flex w-[248px] flex-col bg-white shadow-[2px_0_16px_rgba(29,39,48,0.03)] overscroll-contain overflow-hidden max-lg:-translate-x-full max-lg:transition-transform max-lg:data-[open=true]:translate-x-0">
        <div class="px-6 pb-4 pt-6">
            @php
                $brandHome = request()->is('admin-prodi*') ? route('admin-prodi.dashboard') :
                    (request()->is('admin*') ? route('admin.page', 'dashboard') :
                    (request()->is('dosen*') ? route('dosen.dashboard') : route('mahasiswa.dashboard')));
            @endphp
            <a href="{{ $brandHome }}" class="block" aria-label="SALE, halaman utama">
                <span class="block text-xl font-semibold tracking-[-0.03em] text-ink">SALE</span>
                <span class="mt-0.5 block text-xs text-muted">{{ \App\Models\SystemSetting::valueFor('institution', 'Smart Academic Learning Ecosystem') }}</span>
            </a>
        </div>

        @if(request()->is('admin-prodi*'))
        <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5 [scrollbar-width:thin]" aria-label="Navigasi admin prodi">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang Admin Prodi</p>
            <div class="space-y-1">
                <a href="{{ route('admin-prodi.dashboard') }}" @if(request()->routeIs('admin-prodi.dashboard')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.dashboard') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/></svg>
                    Dashboard Prodi
                </a>
                <a href="{{ route('admin-prodi.kurikulum.index') }}" @if(request()->routeIs('admin-prodi.kurikulum.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.kurikulum.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 12 10 5 10-5M2 17l10 5 10-5"/></svg>
                    Kurikulum (CPL &amp; CPMK)
                </a>
                <a href="{{ route('admin-prodi.akademik.matakuliah') }}" @if(request()->routeIs('admin-prodi.akademik.matakuliah*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.akademik.matakuliah*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                    Mata Kuliah
                </a>
                <a href="{{ route('admin-prodi.akademik.kelas') }}" @if(request()->routeIs('admin-prodi.akademik.kelas*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.akademik.kelas*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Kelas &amp; Dosen Pengampu
                </a>
                <a href="{{ route('admin-prodi.users.index') }}" @if(request()->routeIs('admin-prodi.users.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.users.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 11h5M18.5 8.5v5"/></svg>
                    Dosen &amp; Mahasiswa
                </a>
                <a href="{{ route('admin-prodi.laporan.index') }}" @if(request()->routeIs('admin-prodi.laporan.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.laporan.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Laporan Semester
                </a>
            </div>
            <p class="px-3 pt-5 text-xs leading-5 text-muted">Tata kelola kurikulum, kelas, dan pelaporan capaian prodi.</p>
        </nav>
        @elseif(request()->is('admin*'))
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-5 [scrollbar-width:thin]" aria-label="Navigasi administrator">
            <p class="px-3 pb-3 text-xs font-semibold uppercase tracking-wider text-muted">
                Administrasi Sistem
            </p>
            @foreach(['dashboard'=>'Dashboard','akademik'=>'Data akademik','pengguna'=>'Pengguna & hak akses','monitoring'=>'Monitoring sistem','laporan'=>'Laporan','pengaturan'=>'Pengaturan sistem'] as $section=>$label)
                <a href="{{ route('admin.page',$section) }}" @if(request()->is('admin/'.$section.'*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->is('admin/'.$section.'*') ? 'bg-brand-dark text-white' : 'text-muted hover:bg-brand-soft' }}"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h4"/></svg>{{ $label }}</a>
            @endforeach
        </nav>
        @elseif(request()->is('dosen*'))
        @php
            $currentSection = request()->route('section');
            $currentSectionId = is_object($currentSection) ? $currentSection->id : ($currentSection ?? session('last_active_section_id'));
            $isPenilaianActive = request()->routeIs('dosen.penilaian.index', 'dosen.penilaian.dashboard', 'dosen.penilaian.asesmen*', 'dosen.penilaian.pengaturan');
            $isRekapActive = request()->routeIs('dosen.rekap.*', 'dosen.penilaian.rekap', 'dosen.penilaian.cpmk', 'dosen.penilaian.cpl', 'dosen.penilaian.export*');
            $isCpmkActive = request()->routeIs('dosen.penilaian.rekap', 'dosen.penilaian.cpmk');
            $isCplActive = request()->routeIs('dosen.penilaian.cpl');
            $pendingGradingCount = \App\Support\DosenNavigation::pendingGradingCount();
        @endphp
        <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-5 [scrollbar-width:thin]" aria-label="Navigasi dosen">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang mengajar</p>
            <div class="space-y-1">
                <a href="{{ route('dosen.dashboard') }}" 
                   @if(request()->routeIs('dosen.dashboard')) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.dashboard') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('dosen.course.index') }}" 
                   @if(request()->routeIs('dosen.course.*', 'dosen.item.*')) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.course.*', 'dosen.item.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                    Course saya
                </a>

                <a href="{{ route('dosen.penilaian.index') }}" 
                   @if($isPenilaianActive) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isPenilaianActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/><path d="M9 14l2 2 4-4"/></svg>
                    <span class="min-w-0 flex-1">Penilaian</span>
                    @if($pendingGradingCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $pendingGradingCount }} kelas belum selesai dinilai">{{ $pendingGradingCount > 99 ? '99+' : $pendingGradingCount }}</span>
                    @endif
                </a>

                <a href="{{ $currentSectionId ? route('dosen.penilaian.rekap', $currentSectionId) : route('dosen.rekap.index') }}" 
                   @if($isRekapActive) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isRekapActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Rekap Nilai
                </a>
                <a href="{{ route('dosen.discussion.index') }}"
                   @if(request()->routeIs('dosen.discussion.*')) aria-current="page" @endif
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.discussion.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                    Forum Diskusi
                </a>
            </div>

            <p class="px-3 pb-2 pt-7 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Akun</p>
            <div class="space-y-1">
                @php
                    $notificationService = app(\App\Services\DatabaseNotificationService::class);
                    $dosenUnreadNotifCount = auth()->check() ? $notificationService->unreadCount(auth()->user(), 'dosen') : 0;
                    $isDosenNotifActive = request()->routeIs('dosen.notifications*') || request()->is('*notifikasi*');
                @endphp
                <a href="{{ route('dosen.notifications') }}" @if($isDosenNotifActive) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isDosenNotifActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8zM10 20h4"/></svg>
                    <span class="min-w-0 flex-1">Notifikasi</span>
                    @if($dosenUnreadNotifCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $dosenUnreadNotifCount }} notifikasi belum dibaca">{{ $dosenUnreadNotifCount > 99 ? '99+' : $dosenUnreadNotifCount }}</span>
                    @endif
                </a>
                <a href="{{ route('dosen.profile.index') }}" @if(request()->routeIs('dosen.profile.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.profile.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                    Profil &amp; Pengaturan
                </a>
            </div>
            <p class="px-3 pt-5 text-xs leading-5 text-muted">Materi, tugas, RPS, dan pengumuman dikelola dari course masing-masing.</p>
        </nav>
        @else
        @php
            $notificationService = app(\App\Services\DatabaseNotificationService::class);
            $studentNotifications = auth()->check() ? collect($notificationService->forUser(auth()->user(), 'mahasiswa')) : collect();
            $forumUnreadCount = $studentNotifications->where('category', 'diskusi')->where('is_read', false)->count();
            $pendingTaskCount = auth()->check() ? $notificationService->pendingTaskCount(auth()->user()) : 0;
            $unreadNotifCount = $studentNotifications->where('is_read', false)->count();
        @endphp
        <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5 [scrollbar-width:thin]" aria-label="Navigasi mahasiswa">
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
                <a href="{{ route('mahasiswa.discussion.index') }}" @if(request()->routeIs('mahasiswa.discussion.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.discussion.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                    <span class="min-w-0 flex-1">Forum Diskusi</span>
                    @if($forumUnreadCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $forumUnreadCount }} pesan belum dibaca">{{ $forumUnreadCount > 99 ? '99+' : $forumUnreadCount }}</span>
                    @endif
                </a>
                <a href="{{ route('mahasiswa.assignment.index') }}" @if(request()->routeIs('mahasiswa.assignment.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.assignment.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="min-w-0 flex-1">Tugas &amp; Kuis</span>
                    @if($pendingTaskCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $pendingTaskCount }} tugas dan kuis belum dikerjakan">{{ $pendingTaskCount > 99 ? '99+' : $pendingTaskCount }}</span>
                    @endif
                </a>
            </div>

            <p class="px-3 pb-2 pt-7 text-xs font-semibold uppercase tracking-[0.08em] text-muted">Akun</p>
            <div class="space-y-1">
                @php
                    $isNotifActive = request()->routeIs('mahasiswa.notifications*') || request()->is('*notifikasi*');
                @endphp
                <a href="{{ route('mahasiswa.notifications') }}" @if($isNotifActive) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isNotifActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8zM10 20h4"/></svg>
                    <span class="min-w-0 flex-1">Notifikasi</span>
                    @if($unreadNotifCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $unreadNotifCount }} notifikasi belum dibaca">{{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}</span>
                    @endif
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
            $workspaceRole = request()->is('admin-prodi*') ? 'admin_prodi' :
                (request()->is('admin*') ? 'admin' :
                (request()->is('dosen*') ? 'dosen' : 'mahasiswa'));
            $rawRole = $workspaceRole;

            $activeUser = auth()->check() ? [
                'name' => auth()->user()->name,
                'number' => auth()->user()->nim_nidn ?? auth()->user()->email,
                'role' => $rawRole,
                'role_label' => match($rawRole) {
                        'admin' => 'Admin Sistem',
                        'admin_prodi' => 'Admin Prodi',
                        'dosen' => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                        default => ucfirst($rawRole)
                    },
                'email' => auth()->user()->email,
            ] : [
                'name' => 'Pengguna',
                'email' => '',
                'number' => '',
                'role' => 'user',
                'status' => 'aktif',
            ];

            $roleName = is_string($activeUser['role'] ?? '') ? $activeUser['role'] : ($activeUser['role']['name'] ?? 'user');
            $roleLabel = $activeUser['role_label'] ?? (
                match($roleName) {
                    'admin' => 'Admin Sistem',
                    'admin_prodi' => 'Admin Prodi',
                    'dosen' => 'Dosen',
                    'mahasiswa' => 'Mahasiswa',
                    default => ucfirst($roleName)
                }
            );
            $initials = collect(explode(' ', $activeUser['name'] ?? 'User'))->map(fn($part)=>mb_substr($part,0,1))->take(2)->implode('');
        @endphp
        <header class="sticky top-0 z-30 bg-white border-b border-line/60 shadow-xs">
            <div class="flex h-16 w-full items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button data-sidebar-toggle type="button" aria-label="Buka navigasi" aria-expanded="false" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-ink shadow-sm hover:bg-brand-soft lg:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">@yield('header', 'Dashboard')</p>
                        <p class="hidden truncate text-xs text-muted sm:block">Semester {{ \App\Models\Semester::where('is_active', true)->value('name') ?? 'Belum ditetapkan' }}</p>
                    </div>
                </div>
                <div class="relative">
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg py-1.5 pl-2 pr-3 hover:bg-[#eceeeb] focus:outline-none">
                            <span class="hidden text-right sm:block">
                                <span class="block text-sm font-semibold leading-4 text-ink">{{ $activeUser['name'] }}</span>
                                <span class="block text-xs text-muted">{{ $roleLabel }}{{ !empty($activeUser['number']) ? ' (' . $activeUser['number'] . ')' : '' }}</span>
                            </span>
                            @if(!empty(auth()->user()?->profile_photo_url))
                                <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ $activeUser['name'] }}" class="h-9 w-9 shrink-0 rounded-full border border-[#cbd1d0] object-cover">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-sm font-semibold text-brand-dark">
                                    {{ $initials }}
                                </span>
                            @endif
                            <svg class="h-4 w-4 text-muted shrink-0 transition-transform group-open:rotate-180 ml-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="absolute right-0 z-50 mt-2 w-72 rounded-xl border border-line bg-white p-3 shadow-xl">
                            <div class="border-b border-line/60 pb-3">
                                <p class="text-sm font-bold text-ink">{{ $activeUser['name'] }}</p>
                                <p class="text-xs text-muted">{{ $activeUser['email'] ?? 'user@example.test' }}</p>
                                <p class="mt-1 text-xs text-muted">{{ $roleLabel }} ({{ $activeUser['number'] ?? '' }})</p>
                            </div>
                            @php
                                $accessibleRoleCount = auth()->check()
                                    ? auth()->user()->roles()->pluck('roles.name')->push(auth()->user()->role?->name)->filter()->unique()->count()
                                    : 0;
                            @endphp
                            @if(auth()->check() && ($accessibleRoleCount > 1 || auth()->user()->hasRole('admin')))
                                <div class="border-b border-line/60 py-2.5">
                                    <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-muted">Ruang kerja yang dapat diakses</p>
                                    <div class="space-y-1 text-xs">
                                        @if(auth()->user()->hasRole('dosen'))
                                            <a href="{{ route('dosen.dashboard') }}" class="block rounded-lg px-2 py-1.5 text-ink hover:bg-canvas">Ruang Dosen</a>
                                        @endif
                                        @if(auth()->user()->hasRole('admin_prodi') || auth()->user()->hasRole('admin'))
                                            <a href="{{ route('admin-prodi.dashboard') }}" class="block rounded-lg px-2 py-1.5 text-ink hover:bg-canvas">Administrasi Program Studi</a>
                                        @endif
                                        @if(auth()->user()->hasRole('admin'))
                                            <a href="{{ route('admin.page', 'dashboard') }}" class="block rounded-lg px-2 py-1.5 text-ink hover:bg-canvas">Administrasi Sistem</a>
                                        @endif
                                        @if(auth()->user()->hasRole('mahasiswa'))
                                            <a href="{{ route('mahasiswa.dashboard') }}" class="block rounded-lg px-2 py-1.5 text-ink hover:bg-canvas">Ruang Mahasiswa</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="pt-2">
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full rounded-lg px-2.5 py-1.5 text-left text-xs font-semibold text-danger hover:bg-danger/10">Keluar (Logout)</button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main id="main-content" class="page-shell">
            @yield('content')
        </main>

    </div>

    {{-- Toast Notification --}}
    @if(session('notice') || session('success'))
    <div id="toast-notice"
         role="status"
         aria-live="polite"
         class="fixed top-20 left-1/2 z-[9999] flex items-center gap-3 rounded-xl bg-[#1a2e44] px-4 py-3 shadow-xl text-white text-sm font-medium"
         style="transform: translateX(-50%) translateY(0); transition: opacity 0.4s ease, transform 0.4s ease; opacity: 1;">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/20">
            <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
        </span>
        <span>{{ session('notice') ?? session('success') }}</span>
    </div>
    <script>
        (function () {
            var toast = document.getElementById('toast-notice');
            if (!toast) return;
            setTimeout(function () {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(-12px)';
                setTimeout(function () { toast.remove(); }, 450);
            }, 2800);
        })();
    </script>
    @endif

    <dialog id="sale-dialog" class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl border border-line bg-white p-0 text-ink shadow-2xl backdrop:bg-slate-950/40">
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex items-start gap-3 min-w-0">
                    <span data-sale-dialog-icon class="shrink-0 mt-0.5 text-slate-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M9 6V4h6v2M8 10v7M12 10v7M16 10v7M5 6l1 15h12l1-15"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 data-sale-dialog-title class="text-base font-bold text-ink">Konfirmasi tindakan</h2>
                        <p data-sale-dialog-message class="mt-1 whitespace-pre-line text-sm leading-6 text-muted"></p>
                    </div>
                </div>
                <button type="button" data-sale-dialog-close class="shrink-0 rounded-lg p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Tutup">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" data-sale-dialog-cancel class="button-secondary px-4 py-2 text-sm">Batal</button>
                <button type="button" data-sale-dialog-confirm class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800">Ya, lanjutkan</button>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('sale-dialog');
            const title = dialog?.querySelector('[data-sale-dialog-title]');
            const message = dialog?.querySelector('[data-sale-dialog-message]');
            const icon = dialog?.querySelector('[data-sale-dialog-icon]');
            const cancel = dialog?.querySelector('[data-sale-dialog-cancel]');
            const confirmButton = dialog?.querySelector('[data-sale-dialog-confirm]');
            const closeBtn = dialog?.querySelector('[data-sale-dialog-close]');
            let finish = null;

            const complete = (value) => {
                if (!finish) return;
                const resolve = finish;
                finish = null;
                dialog.close();
                resolve(value);
            };

            window.saleConfirm = (options = {}) => {
                const config = typeof options === 'string' ? { message: options } : options;
                if (!dialog || typeof dialog.showModal !== 'function') {
                    return Promise.resolve(window.confirm(config.message || 'Lanjutkan tindakan ini?'));
                }

                title.textContent = config.title || 'Konfirmasi tindakan';
                message.textContent = config.message || 'Tindakan ini perlu dikonfirmasi.';
                confirmButton.textContent = config.confirmLabel || 'Ya, lanjutkan';
                cancel.hidden = false;
                // Icon polos: hanya warna stroke, tanpa bg berwarna
                icon.className = 'shrink-0 mt-0.5 text-rose-600';
                confirmButton.className = 'rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800';
                dialog.showModal();
                cancel.focus();

                return new Promise(resolve => { finish = resolve; });
            };

            window.saleNotice = (options = {}) => {
                const config = typeof options === 'string' ? { message: options } : options;
                if (!dialog || typeof dialog.showModal !== 'function') {
                    window.alert(config.message || 'Informasi');
                    return Promise.resolve(true);
                }

                title.textContent = config.title || 'Informasi';
                message.textContent = config.message || '';
                confirmButton.textContent = config.confirmLabel || 'Mengerti';
                cancel.hidden = true;
                // Icon polos: hanya warna stroke, tanpa bg berwarna
                icon.className = 'shrink-0 mt-0.5 text-[#102f50]';
                confirmButton.className = 'button-primary px-4 py-2 text-sm font-semibold';
                dialog.showModal();
                confirmButton.focus();

                return new Promise(resolve => { finish = resolve; });
            };

            cancel?.addEventListener('click', () => complete(false));
            closeBtn?.addEventListener('click', () => complete(false));
            confirmButton?.addEventListener('click', () => complete(true));
            dialog?.addEventListener('click', event => {
                if (event.target === dialog) complete(false);
            });
            dialog?.addEventListener('cancel', event => {
                event.preventDefault();
                complete(false);
            });

            document.addEventListener('submit', async event => {
                const form = event.target.closest('form[data-confirm]');
                if (!form || form.dataset.confirmed === 'true') return;
                event.preventDefault();
                const accepted = await window.saleConfirm({
                    title: form.dataset.confirmTitle || 'Konfirmasi tindakan',
                    message: form.dataset.confirm,
                    confirmLabel: form.dataset.confirmLabel || 'Ya, lanjutkan',
                });
                if (!accepted) return;
                form.dataset.confirmed = 'true';
                form.requestSubmit(event.submitter || undefined);
            }, true);
        })();
    </script>
</body>
</html>
