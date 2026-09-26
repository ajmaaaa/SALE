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

    <aside data-sidebar data-open="false" class="fixed inset-y-0 left-0 z-50 flex w-[248px] flex-col bg-white shadow-[2px_0_16px_rgba(29,39,48,0.03)] overscroll-contain overflow-hidden max-lg:-translate-x-full max-lg:transition-transform max-lg:data-[open=true]:translate-x-0">
        <div class="px-6 pb-4 pt-6">
            @php
                $brandHome = request()->is('admin-prodi*') ? route('admin-prodi.dashboard') :
                    (request()->is('admin*') ? route('admin.page', 'dashboard') :
                    (request()->is('dosen*') ? route('dosen.dashboard') : route('mahasiswa.dashboard')));
            @endphp
            <a href="{{ $brandHome }}" class="block" aria-label="SALE, halaman utama">
                <span class="block text-xl font-semibold tracking-[-0.03em] text-ink">SALE</span>
                <span class="mt-0.5 block text-xs text-muted">{{ session('admin.settings.institution','Smart Academic Learning Ecosystem') }}</span>
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
                <a href="{{ route('admin-prodi.akademik.matakuliah') }}" @if(request()->routeIs('admin-prodi.akademik.matakuliah*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.akademik.matakuliah*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                    Mata Kuliah
                </a>
                <a href="{{ route('admin-prodi.kurikulum.index') }}" @if(request()->routeIs('admin-prodi.kurikulum.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('admin-prodi.kurikulum.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 12 10 5 10-5M2 17l10 5 10-5"/></svg>
                    Kurikulum (CPL &amp; CPMK)
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
            $isPenilaianActive = request()->routeIs('dosen.penilaian.index', 'dosen.penilaian.dashboard', 'dosen.penilaian.matriks', 'dosen.penilaian.asesmen*', 'dosen.penilaian.pengaturan');
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
                    $dosenUnreadNotifCount = \App\Support\LearningPreview::unreadNotificationCount();
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
            $forumUnreadCount = \App\Support\LearningPreview::unreadDiscussionCount();
            $pendingTaskCount = \App\Support\LearningPreview::pendingTaskCount();
            $unreadNotifCount = \App\Support\LearningPreview::unreadNotificationCount();
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
                <a href="{{ route('mahasiswa.assignment.index') }}" @if(request()->routeIs('mahasiswa.assignment.*', 'mahasiswa.grade.*')) aria-current="page" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 text-[14px] font-medium {{ request()->routeIs('mahasiswa.assignment.*', 'mahasiswa.grade.*') ? 'bg-brand-dark font-semibold text-white' : 'text-[#4d5964] hover:bg-brand-soft hover:text-ink' }}">
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
            $rawRole = auth()->check() ? (is_string(auth()->user()->role) ? auth()->user()->role : (auth()->user()->role?->name ?? 'dosen')) : (session('auth_user.role') ?? (
                request()->is('admin-prodi*') ? 'admin_prodi' :
                (request()->is('admin*') ? 'admin' :
                (request()->is('dosen*') ? 'dosen' : 'mahasiswa'))
            ));

            $activeUser = auth()->check() ? [
                'name' => auth()->user()->name,
                'number' => auth()->user()->nim_nidn ?? auth()->user()->email,
                'role' => $rawRole,
                'role_label' => auth()->user()->role?->label ?? (
                    match($rawRole) {
                        'admin' => 'Admin Sistem',
                        'admin_prodi' => 'Admin Prodi',
                        'dosen' => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                        default => ucfirst($rawRole)
                    }
                ),
                'email' => auth()->user()->email,
            ] : (session('auth_user') ?? (
                request()->is('admin-prodi*') ? ['id' => 4, 'name' => 'Admin Prodi TI', 'email' => 'adminprodi@example.test', 'number' => 'AP001', 'role' => 'admin_prodi', 'status' => 'aktif'] :
                (request()->is('admin*') ? \App\Support\AdminPreview::users()[3] :
                (request()->is('dosen*') ? \App\Support\AdminPreview::users()[2] :
                \App\Support\AdminPreview::users()[1]))
            ));

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
                        <p class="hidden truncate text-xs text-muted sm:block">Semester {{ session('admin.settings.semester','Ganjil 2026/2027') }}</p>
                    </div>
                </div>
                <div class="relative">
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg p-1.5 hover:bg-[#eceeeb] focus:outline-none">
                            <span class="hidden text-right sm:block">
                                <span class="block text-sm font-semibold leading-4 text-ink">{{ $activeUser['name'] }}</span>
                                <span class="block text-xs text-muted">{{ $roleLabel }} ({{ $activeUser['number'] ?? '—' }})</span>
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
                                <p class="mt-1 text-xs text-muted">{{ $roleLabel }} ({{ $activeUser['number'] ?? '' }})</p>
                            </div>
                            <!-- TESTING_ONLY: QUICK_ROLE_SWITCHER_START -->
                            <!-- KOMPONEN PENGUJIAN: QUICK ROLE SWITCHER (Beralih Cepat Antar Peran) -->
                            <!-- Hapus blok antara QUICK_ROLE_SWITCHER_START dan QUICK_ROLE_SWITCHER_END saat pengujian selesai -->
                            <div class="py-2.5 my-1 border-y border-line/60 bg-canvas/40 -mx-3 px-3">
                                <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-line/50">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">
                                        Peralihan Peran
                                    </span>
                                    <span class="text-[10px] text-muted font-normal">
                                        Mode Pengujian
                                    </span>
                                </div>
                                <div class="space-y-0.5">
                                    @php
                                        $rolesList = [
                                            'mahasiswa' => ['label' => 'Mahasiswa', 'desc' => 'Ruang Belajar, Tugas & Nilai'],
                                            'dosen' => ['label' => 'Dosen Pengampu', 'desc' => 'Ruang Mengajar & Penilaian'],
                                            'admin_prodi' => ['label' => 'Admin Prodi', 'desc' => 'Kelola Kelas, Kurikulum, MK'],
                                            'admin' => ['label' => 'Admin Sistem', 'desc' => 'Kelola Pengguna & Pengaturan'],
                                        ];
                                    @endphp
                                    @foreach($rolesList as $rKey => $rMeta)
                                        @php($isCurrent = $roleName === $rKey)
                                        <form method="post" action="{{ route('switch-role', $rKey) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                    @disabled($isCurrent)
                                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-left text-xs transition {{ $isCurrent ? 'bg-slate-100 font-semibold text-ink cursor-default' : 'text-slate-600 hover:bg-slate-50 hover:text-ink' }}">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $isCurrent ? 'bg-brand' : 'bg-slate-300' }}"></span>
                                                    <div class="truncate">
                                                        <span class="block leading-tight font-medium {{ $isCurrent ? 'font-semibold text-ink' : 'text-slate-700' }}">{{ $rMeta['label'] }}</span>
                                                        <span class="block text-[10px] text-muted leading-tight">{{ $rMeta['desc'] }}</span>
                                                    </div>
                                                </div>
                                                @if($isCurrent)
                                                    <span class="text-[9px] font-semibold text-slate-500 uppercase tracking-wider shrink-0 bg-white border border-line/60 px-1.5 py-0.5 rounded">Aktif</span>
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                            <!-- TESTING_ONLY: QUICK_ROLE_SWITCHER_END -->
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
            @if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-danger bg-white p-4 text-sm text-danger"><p class="font-semibold">Periksa kembali isian berikut.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
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
</body>
</html>
