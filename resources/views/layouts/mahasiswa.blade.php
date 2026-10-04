<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#f4f5f7">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\SystemSetting::appName())</title>
    @auth
        @if(!empty(auth()->user()?->profile_photo_url))
            <link rel="preload" as="image" href="{{ auth()->user()->profile_photo_url }}" fetchpriority="high">
        @endif
    @endauth
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { scrollbar-gutter: stable; color-scheme: light; }
        dialog { position: fixed !important; inset: 0 !important; margin: auto !important; }
    </style>
</head>
<body class="min-h-screen font-sans antialiased">
    <a href="#main-content" class="fixed left-3 top-3 z-[70] -translate-y-20 rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white focus:translate-y-0">
        Lewati ke konten utama
    </a>

    <div data-sidebar-backdrop data-open="false" class="fixed inset-0 z-40 hidden bg-ink/30 data-[open=true]:block lg:hidden"></div>

    <aside data-sidebar data-open="false" class="fixed inset-y-0 left-0 z-50 flex w-[248px] flex-col bg-white shadow-[2px_0_16px_rgba(29,39,48,0.03)] overscroll-contain overflow-hidden max-lg:-translate-x-full max-lg:transition-transform max-lg:data-[open=true]:translate-x-0">
        <div class="px-6 pb-4 pt-6 flex items-start justify-between">
            <div>
                @php
                    $brandHome = request()->is('admin-prodi*') ? route('admin-prodi.dashboard') :
                        (request()->is('admin*') ? route('admin.page', 'dashboard') :
                        (request()->is('dosen*') ? route('dosen.dashboard') : route('mahasiswa.dashboard')));
                @endphp
                <a href="{{ $brandHome }}" class="block" aria-label="SALE, halaman utama">
                    <span class="block text-xl font-semibold tracking-[-0.03em] text-ink">{{ \App\Models\SystemSetting::appName() }}</span>
                    <span class="mt-0.5 block text-xs text-muted">{{ \App\Models\SystemSetting::valueFor('institution', 'Smart Academic Learning Ecosystem') }}</span>
                </a>
            </div>
            <button type="button" data-sidebar-close class="lg:hidden -mr-2 -mt-1 p-2 rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup navigasi">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <path d="M9 3v18"/>
                </svg>
            </button>
        </div>

        @if(request()->is('admin-prodi*'))
        <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5 [scrollbar-width:thin]" aria-label="Navigasi admin prodi">
            <div class="px-3 pb-3">
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-muted">Ruang Admin Prodi</p>
                @php
                    $sidebarProdi = auth()->user()?->managingProdi ?? auth()->user()?->prodi;
                    $pendingAppealCount = \App\Models\ClassEnrollmentAppeal::where('status', \App\Models\ClassEnrollmentAppeal::STATUS_PENDING)
                        ->when($sidebarProdi, fn ($q) => $q->whereHas('classSection.mataKuliah', fn ($mk) => $mk->where('prodi_id', $sidebarProdi->id)))
                        ->count();
                @endphp
                @if($sidebarProdi)
                    <p class="mt-1.5 text-sm font-semibold text-ink truncate" title="{{ $sidebarProdi->name }}">
                        {{ $sidebarProdi->name }}
                    </p>
                @endif
            </div>
            <div class="space-y-1">
                <a href="{{ route('admin-prodi.dashboard') }}" @if(request()->routeIs('admin-prodi.dashboard')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.dashboard') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v3H4zM14 15.5h6v3h-6z"/></svg>
                    Dashboard Prodi
                </a>
                <a href="{{ route('admin-prodi.users.index') }}" @if(request()->routeIs('admin-prodi.users.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.users.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 11h5M18.5 8.5v5"/></svg>
                    Dosen &amp; Mahasiswa
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
                <a href="{{ route('admin-prodi.akademik.verifikasi-peserta') }}" @if(request()->routeIs('admin-prodi.akademik.verifikasi-peserta*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.akademik.verifikasi-peserta*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Peserta</span>
                    @if($pendingAppealCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white" aria-label="{{ $pendingAppealCount }} permohonan menunggu verifikasi">{{ $pendingAppealCount > 99 ? '99+' : $pendingAppealCount }}</span>
                    @endif
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
                    <span id="sidebar-dosen-grading-badge" data-badge="dosen-grading" style="{{ $pendingGradingCount > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white {{ $pendingGradingCount > 0 ? '' : 'hidden' }}" aria-label="{{ $pendingGradingCount }} kelas belum selesai dinilai">{{ $pendingGradingCount > 0 ? ($pendingGradingCount > 99 ? '99+' : $pendingGradingCount) : '' }}</span>
                </a>

                <a href="{{ route('dosen.rekap.index') }}" 
                   @if($isRekapActive) aria-current="page" @endif 
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ $isRekapActive ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Rekap Nilai
                </a>
                @php
                    $notificationService = app(\App\Services\DatabaseNotificationService::class);
                    $dosenNotifications = auth()->check() ? collect($notificationService->forUser(auth()->user(), 'dosen')) : collect();
                    $dosenForumUnread = (int) $dosenNotifications->where('category', 'diskusi')->where('is_read', false)->sum('unread_count');
                    if ($dosenForumUnread === 0) {
                        $dosenForumUnread = $dosenNotifications->where('category', 'diskusi')->where('is_read', false)->count();
                    }
                    $dosenForumMention = (int) $dosenNotifications->where('category', 'diskusi')->where('is_read', false)->sum('mention_count');
                @endphp
                <a href="{{ route('dosen.discussion.index') }}"
                   @if(request()->routeIs('dosen.discussion.*')) aria-current="page" @endif
                   class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('dosen.discussion.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                    <span class="min-w-0 flex-1">Forum Diskusi</span>
                    <span id="sidebar-dosen-forum-mention-badge" data-badge="dosen-forum-mention" style="{{ $dosenForumMention > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#102f50] px-1.5 text-[11px] font-bold leading-none text-white {{ $dosenForumMention > 0 ? '' : 'hidden' }}" aria-label="{{ $dosenForumMention }} sebutan (@) untuk Anda">{{ $dosenForumMention > 0 ? ($dosenForumMention > 99 ? '99+' : '@'.$dosenForumMention) : '' }}</span>
                    <span id="sidebar-dosen-forum-badge" data-badge="dosen-forum" style="{{ $dosenForumUnread > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white {{ $dosenForumUnread > 0 ? '' : 'hidden' }}" aria-label="{{ $dosenForumUnread }} pesan belum dibaca">{{ $dosenForumUnread > 0 ? ($dosenForumUnread > 99 ? '99+' : $dosenForumUnread) : '' }}</span>
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
                    <span id="sidebar-dosen-notif-badge" data-badge="dosen-notif" style="{{ $dosenUnreadNotifCount > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white {{ $dosenUnreadNotifCount > 0 ? '' : 'hidden' }}" aria-label="{{ $dosenUnreadNotifCount }} notifikasi belum dibaca">{{ $dosenUnreadNotifCount > 0 ? ($dosenUnreadNotifCount > 99 ? '99+' : $dosenUnreadNotifCount) : '' }}</span>
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
            $forumUnreadCount = (int) $studentNotifications->where('category', 'diskusi')->where('is_read', false)->sum('unread_count');
            if ($forumUnreadCount === 0) {
                $forumUnreadCount = $studentNotifications->where('category', 'diskusi')->where('is_read', false)->count();
            }
            $forumMentionCount = (int) $studentNotifications->where('category', 'diskusi')->where('is_read', false)->sum('mention_count');
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
                <a href="{{ route('mahasiswa.nilai') }}" @if(request()->routeIs('mahasiswa.nilai*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.nilai*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                    Nilai
                </a>
                <a href="{{ route('mahasiswa.discussion.index') }}" @if(request()->routeIs('mahasiswa.discussion.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.discussion.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                    <span class="min-w-0 flex-1">Forum Diskusi</span>
                    <span id="sidebar-mhs-forum-mention-badge" data-badge="mhs-forum-mention" style="{{ $forumMentionCount > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#102f50] px-1.5 text-[11px] font-bold leading-none text-white {{ $forumMentionCount > 0 ? '' : 'hidden' }}" aria-label="{{ $forumMentionCount }} sebutan (@) untuk Anda">{{ $forumMentionCount > 0 ? ($forumMentionCount > 99 ? '99+' : '@'.$forumMentionCount) : '' }}</span>
                    <span id="sidebar-mhs-forum-badge" data-badge="mhs-forum" style="{{ $forumUnreadCount > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white {{ $forumUnreadCount > 0 ? '' : 'hidden' }}" aria-label="{{ $forumUnreadCount }} pesan belum dibaca">{{ $forumUnreadCount > 0 ? ($forumUnreadCount > 99 ? '99+' : $forumUnreadCount) : '' }}</span>
                </a>
                <a href="{{ route('mahasiswa.assignment.index') }}" @if(request()->routeIs('mahasiswa.assignment.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.assignment.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="min-w-0 flex-1">Tugas &amp; Kuis</span>
                    <span id="sidebar-mhs-task-badge" data-badge="mhs-task" style="{{ $pendingTaskCount > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white {{ $pendingTaskCount > 0 ? '' : 'hidden' }}" aria-label="{{ $pendingTaskCount }} tugas dan kuis belum dikerjakan">{{ $pendingTaskCount > 0 ? ($pendingTaskCount > 99 ? '99+' : $pendingTaskCount) : '' }}</span>
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
                    <span id="sidebar-mhs-notif-badge" data-badge="mhs-notif" style="{{ $unreadNotifCount > 0 ? '' : 'display: none !important;' }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#4c1d95] px-1.5 text-[11px] font-bold leading-none text-white {{ $unreadNotifCount > 0 ? '' : 'hidden' }}" aria-label="{{ $unreadNotifCount }} notifikasi belum dibaca">{{ $unreadNotifCount > 0 ? ($unreadNotifCount > 99 ? '99+' : $unreadNotifCount) : '' }}</span>
                </a>
                <a href="{{ route('mahasiswa.profile.index') }}" @if(request()->routeIs('mahasiswa.profile.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.profile.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                    Profil &amp; Pengaturan
                </a>
            </div>
        </nav>
        @endif

        @php
            $sidebarSupportEmail = \App\Models\SystemSetting::valueFor('support');
        @endphp
        @if($sidebarSupportEmail)
        <div class="border-t border-line/70 p-3 bg-canvas/40 shrink-0">
            <a href="mailto:{{ $sidebarSupportEmail }}" class="group flex items-center gap-2.5 rounded-lg p-2 transition hover:bg-white hover:shadow-xs" title="Hubungi Admin: {{ $sidebarSupportEmail }}">
                <svg class="h-5 w-5 shrink-0 text-muted transition group-hover:text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-muted group-hover:text-ink leading-tight">Hubungi Admin</span>
                    <span class="block truncate text-xs font-semibold text-brand leading-snug">
                        {{ $sidebarSupportEmail }}
                    </span>
                </div>
            </a>
        </div>
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
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="3" rx="2"/>
                            <path d="M9 3v18"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">@yield('header', 'Dashboard')</p>
                        <p class="hidden truncate text-xs text-muted sm:block">Semester {{ \App\Models\Semester::where('is_active', true)->value('name') ?? 'Belum ditetapkan' }}</p>
                    </div>
                </div>
                <div class="relative">
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 sm:gap-3 rounded-lg p-1 sm:py-1.5 sm:pl-2 sm:pr-2.5 hover:bg-[#eceeeb] focus:outline-none select-none [&::-webkit-details-marker]:hidden">
                            <span class="max-sm:hidden sm:!block text-right">
                                <span class="block text-sm font-semibold leading-4 text-ink max-w-[130px] sm:max-w-[180px] md:max-w-[240px] truncate">{{ $activeUser['name'] }}</span>
                                <span class="block text-xs text-muted truncate">{{ $roleLabel }}{{ !empty($activeUser['number']) ? ' (' . $activeUser['number'] . ')' : '' }}</span>
                            </span>
                            @if(!empty(auth()->user()?->profile_photo_url))
                                <img id="topbar-avatar-img"
                                     src="{{ auth()->user()->profile_photo_url }}"
                                     alt="{{ $activeUser['name'] }}"
                                     width="36"
                                     height="36"
                                     loading="eager"
                                     decoding="sync"
                                     fetchpriority="high"
                                     class="h-9 w-9 shrink-0 rounded-full border border-[#cbd1d0] object-cover">
                                <script>
                                    try {
                                        localStorage.removeItem('sale_avatar_data_{{ auth()->id() }}');
                                        localStorage.removeItem('sale_avatar_url_{{ auth()->id() }}');
                                    } catch (e) {}
                                </script>
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-sm font-semibold text-brand-dark shrink-0">
                                    {{ $initials }}
                                </span>
                            @endif
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
                            @if(auth()->check() && $accessibleRoleCount > 1)
                                <div class="border-b border-line/60 py-2.5">
                                    <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-muted">Ruang kerja yang dapat diakses</p>
                                    <div class="space-y-1 text-xs">
                                        @if(auth()->user()->hasRole('dosen'))
                                            <a href="{{ route('dosen.dashboard') }}" class="block rounded-lg px-2 py-1.5 text-ink hover:bg-canvas">Ruang Dosen</a>
                                        @endif
                                        @if(auth()->user()->hasRole('admin_prodi'))
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
    @if(session('notice') || session('success') || session('status') === 'notification-preferences-updated')
    <div id="toast-notice"
         role="status"
         aria-live="polite"
         class="fixed top-20 left-1/2 z-[9999] flex max-w-[calc(100vw-2rem)] items-center gap-3 rounded-xl bg-[#1a2e44] px-4 py-3 shadow-xl text-white text-sm font-medium"
         style="transform: translateX(-50%) translateY(0); transition: opacity 0.4s ease, transform 0.4s ease; opacity: 1;">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/20">
            <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
        </span>
        <span>{{ session('notice') ?? session('success') ?? 'Preferensi notifikasi berhasil disimpan.' }}</span>
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
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span data-sale-dialog-icon class="shrink-0 text-slate-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M9 6V4h6v2M8 10v7M12 10v7M16 10v7M5 6l1 15h12l1-15"/></svg>
                    </span>
                    <h2 data-sale-dialog-title class="text-base font-bold text-ink truncate">Konfirmasi tindakan</h2>
                </div>
                <button type="button" data-sale-dialog-close class="shrink-0 rounded-lg p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Tutup">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <p data-sale-dialog-message class="whitespace-pre-line text-sm leading-relaxed text-muted mb-5"></p>
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
                icon.className = 'shrink-0 text-rose-600';
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
                icon.className = 'shrink-0 text-[#102f50]';
                confirmButton.className = 'button-primary px-4 py-2 text-sm font-semibold';
                dialog.showModal();
                confirmButton.focus();

                return new Promise(resolve => { finish = resolve; });
            };

            window.validateAndSubmitPhoto = function(input) {
                if (!input || !input.files || !input.files[0]) return;
                const file = input.files[0];
                const maxPhotoSize = 2 * 1024 * 1024; // 2 MB
                if (file.size > maxPhotoSize) {
                    input.value = '';
                    const message = 'Foto tidak dapat diunggah jika ukurannya lebih dari 2 MB.';
                    if (typeof window.saleNotice === 'function') {
                        window.saleNotice({
                            title: 'Ukuran Foto Terlalu Besar',
                            message: message,
                            confirmLabel: 'Mengerti'
                        });
                    } else {
                        alert(message);
                    }
                    return;
                }
                input.form?.submit();
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

            document.addEventListener('click', event => {
                const openDetails = document.querySelector('header details[open]');
                if (openDetails && !openDetails.contains(event.target)) {
                    openDetails.removeAttribute('open');
                }
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
    @auth
    <script>
        (function() {
            function updateBadgeEl(el, count, singularLabel) {
                if (!el) return;
                const num = Number(count) || 0;
                if (num > 0) {
                    el.textContent = num > 99 ? '99+' : num;
                    el.classList.remove('hidden');
                    el.style.setProperty('display', 'inline-flex');
                    el.setAttribute('aria-label', `${num} ${singularLabel}`);
                } else {
                    el.textContent = '';
                    el.classList.add('hidden');
                    el.style.setProperty('display', 'none', 'important');
                    el.removeAttribute('aria-label');
                }
            }

            function updateMentionBadgeEl(el, count) {
                if (!el) return;
                const num = Number(count) || 0;
                if (num > 0) {
                    el.textContent = num > 99 ? '99+' : '@' + num;
                    el.classList.remove('hidden');
                    el.style.setProperty('display', 'inline-flex');
                    el.setAttribute('aria-label', `${num} sebutan (@) untuk Anda`);
                } else {
                    el.textContent = '';
                    el.classList.add('hidden');
                    el.style.setProperty('display', 'none', 'important');
                    el.removeAttribute('aria-label');
                }
            }

            function applyLiveStatus(data) {
                if (!data) return;

                if (data.pending_grading_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-dosen-grading-badge'), data.pending_grading_count, 'kelas belum selesai dinilai');
                }
                if (data.dosen_unread_notif_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-dosen-notif-badge'), data.dosen_unread_notif_count, 'notifikasi belum dibaca');
                } else if (data.unread_notif_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-dosen-notif-badge'), data.unread_notif_count, 'notifikasi belum dibaca');
                }

                if (data.forum_unread_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-mhs-forum-badge'), data.forum_unread_count, 'pesan belum dibaca');
                    updateBadgeEl(document.getElementById('sidebar-dosen-forum-badge'), data.forum_unread_count, 'pesan belum dibaca');
                }
                if (data.forum_mention_count !== undefined) {
                    updateMentionBadgeEl(document.getElementById('sidebar-mhs-forum-mention-badge'), data.forum_mention_count);
                    updateMentionBadgeEl(document.getElementById('sidebar-dosen-forum-mention-badge'), data.forum_mention_count);
                }
                if (data.pending_task_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-mhs-task-badge'), data.pending_task_count, 'tugas dan kuis belum dikerjakan');
                }
                if (data.mhs_unread_notif_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-mhs-notif-badge'), data.mhs_unread_notif_count, 'notifikasi belum dibaca');
                } else if (data.unread_notif_count !== undefined) {
                    updateBadgeEl(document.getElementById('sidebar-mhs-notif-badge'), data.unread_notif_count, 'notifikasi belum dibaca');
                }
            }

            window.addEventListener('sale:live-status', function(e) {
                applyLiveStatus(e.detail);
            });

            let userInteracted = false;
            ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(function(evt) {
                window.addEventListener(evt, function() {
                    userInteracted = true;
                }, { passive: true, capture: true });
            });

            async function pollLiveStatus() {
                if (document.visibilityState !== 'visible') return;

                const headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                };
                if (userInteracted) {
                    headers['X-User-Activity'] = '1';
                    userInteracted = false;
                }

                try {
                    const res = await fetch('{{ route('live-status') }}', { headers: headers });
                    if (res.status === 401) {
                        window.location.href = '{{ route('login') }}';
                        return;
                    }
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data.success) return;

                    applyLiveStatus(data);
                    window.dispatchEvent(new CustomEvent('sale:live-status', { detail: data }));
                } catch (err) {
                }
            }

            setInterval(pollLiveStatus, 8000);

            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    pollLiveStatus();
                }
            });
        })();
    </script>
    @endauth
    <script>
        // Tangani navigasi kembali browser (BFCache) agar token CSRF tidak kedaluwarsa (mencegah 419)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
