@extends('layouts.mahasiswa')
@section('title', 'Notifikasi - SALE')
@section('header', 'Notifikasi')

@section('content')
<div class="w-full space-y-6 pb-12">
    <!-- Header Area (CRAP Principles: Clear Contrast, Repetition, Alignment & Proximity) -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200/80 pb-5">
        <!-- Title Group -->
        <div class="space-y-1">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Notifikasi</h1>
            <p class="text-xs text-slate-500 font-medium">Pemberitahuan aktivitas kelas, tugas, dan pembaruan akademik SALE</p>
        </div>

        <!-- Action Buttons Group -->
        <div class="flex items-center gap-2.5 text-xs">
            <form action="{{ route('mahasiswa.notifications.read-all') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-2xs transition-all">
                    <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Tandai semua dibaca
                </button>
            </form>

            <form action="{{ route('mahasiswa.notifications.clear') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-transparent px-3 py-1.5 text-xs font-medium text-slate-500 hover:text-rose-600 hover:bg-rose-50/80 transition-all">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Bersihkan riwayat
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="rounded-2xl border border-line/70 bg-white p-6 sm:p-7 shadow-2xs space-y-6">
        <!-- Category Filter Bar (Pill Bar) -->
        @php
            $categories = [
                'semua' => 'Semua',
                'tugas' => 'Tugas',
                'pengumuman' => 'Pengumuman',
                'diskusi' => 'Sistem & Diskusi',
            ];
            $allNotifs = collect($allNotifications ?? []);
            $notifCollection = collect($notifications ?? []);
            $todayItems = $notifCollection->where('group', 'hari_ini');
            $olderItems = $notifCollection->where('group', 'kemarin_sebelumnya');
            if ($todayItems->isEmpty() && $olderItems->isEmpty()) {
                $todayItems = $notifCollection;
            }
            $unreadCount = $unreadCount ?? $allNotifs->where('is_read', false)->count();
            $activeCourse = $activeCourse ?? null;
        @endphp

        <div class="flex items-center justify-between border-b border-slate-100 pb-4 flex-wrap gap-3">
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach($categories as $key => $label)
                    @php
                        $count = $key === 'semua' ? $allNotifs->count() : $allNotifs->where('category', $key)->count();
                        $isActive = ($activeCategory ?? 'semua') === $key;
                    @endphp
                    <a href="{{ route('mahasiswa.notifications', array_filter(['category' => $key !== 'semua' ? $key : null, 'course' => $activeCourse])) }}"
                       class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all {{ $isActive ? 'bg-[#102f50] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 hover:text-slate-900' }}">
                        <span>{{ $label }}</span>
                        <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $count }}
                        </span>
                    </a>
                @endforeach
            </div>

            @if($unreadCount > 0)
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200/60">
                    {{ $unreadCount }} belum dibaca
                </span>
            @endif
        </div>

        <!-- Notifications List -->
        @if(!empty($notifications))
            <!-- HARI INI SECTION -->
            @if($todayItems->isNotEmpty())
                <div class="space-y-2">
                    <h2 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 font-sans border-b border-slate-100 pb-2">HARI INI</h2>
                    <div class="space-y-0.5">
                        @foreach($todayItems as $notif)
                            @include('learning.partials.notification-item', ['notif' => $notif])
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- KEMARIN & SEBELUMNYA SECTION -->
            @if($olderItems->isNotEmpty())
                <div class="space-y-2 pt-5">
                    <h2 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 font-sans border-b border-slate-100 pb-2">KEMARIN & SEBELUMNYA</h2>
                    <div class="space-y-0.5">
                        @foreach($olderItems as $notif)
                            @include('learning.partials.notification-item', ['notif' => $notif])
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center rounded-xl bg-canvas/30 p-12 text-center border border-line/40">
                <div class="h-12 w-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8zM10 20h4"/></svg>
                </div>
                <p class="text-sm font-bold text-slate-800">Tidak ada notifikasi</p>
                <p class="text-xs text-slate-500 mt-1">Belum ada notifikasi pada kategori ini.</p>
            </div>
        @endif
    </div>
</div>
@endsection
