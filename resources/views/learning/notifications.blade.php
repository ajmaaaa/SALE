@extends('layouts.mahasiswa')

@section('title', 'Notifikasi | SALE')
@section('header', 'Notifikasi')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-1">
        <div>
            <h1 class="page-heading">Notifikasi Pembelajaran</h1>
            <p class="page-description">Pemberitahuan penugasan, kuis, nilai terbit, dan diskusi akademik semester ini.</p>
        </div>
        @php
            $hasUnread = collect($notifications)->contains(fn ($n) => empty($n['is_read']));
        @endphp
        @if($hasUnread)
            <form action="{{ route('mahasiswa.notifications.read', 'all') }}" method="POST">
                @csrf
                @foreach($notifications as $n)
                    @if(empty($n['is_read']))
                        <input type="hidden" name="notification_ids[]" value="{{ $n['id'] }}">
                    @endif
                @endforeach
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-white px-3 py-1.5 text-xs font-semibold text-[#4d5964] shadow-2xs hover:bg-canvas hover:text-ink transition-colors">
                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Tandai semua telah dibaca
                </button>
            </form>
        @endif
    </header>

    {{-- Filter Kategori Notifikasi --}}
    <nav class="flex border-b border-line/60 gap-6 text-xs sm:text-sm font-semibold" aria-label="Kategori Notifikasi">
        <a href="{{ route('mahasiswa.notifications') }}"
           class="pb-3 border-b-2 -mb-px transition flex items-center gap-1.5 {{ empty($selectedCategory) ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            <span>Semua</span>
            <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ $categoryCounts['all'] ?? count($notifications) }}</span>
        </a>
        <a href="{{ route('mahasiswa.notifications', ['category' => 'tugas']) }}"
           class="pb-3 border-b-2 -mb-px transition flex items-center gap-1.5 {{ $selectedCategory === 'tugas' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            <span>Tugas &amp; Kuis</span>
            @if(!empty($categoryCounts['tugas']))
                <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ $categoryCounts['tugas'] }}</span>
            @endif
        </a>
        <a href="{{ route('mahasiswa.notifications', ['category' => 'nilai']) }}"
           class="pb-3 border-b-2 -mb-px transition flex items-center gap-1.5 {{ $selectedCategory === 'nilai' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            <span>Nilai Diterbitkan</span>
            @if(!empty($categoryCounts['nilai']))
                <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ $categoryCounts['nilai'] }}</span>
            @endif
        </a>
        <a href="{{ route('mahasiswa.notifications', ['category' => 'diskusi']) }}"
           class="pb-3 border-b-2 -mb-px transition flex items-center gap-1.5 {{ $selectedCategory === 'diskusi' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            <span>Diskusi Kelas</span>
            @if(!empty($categoryCounts['diskusi']))
                <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ $categoryCounts['diskusi'] }}</span>
            @endif
        </a>
    </nav>

    {{-- Daftar Notifikasi --}}
    <section class="surface overflow-hidden rounded-xl border border-line/60" aria-label="Daftar Notifikasi">
        @forelse($notifications as $notif)
            @include('learning.partials.notification-item', ['notif' => $notif])
        @empty
            <div class="p-12 text-center text-xs text-muted space-y-2">
                <svg class="h-8 w-8 mx-auto text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8zM10 20h4"/></svg>
                <p class="font-medium text-ink">Tidak ada notifikasi untuk kategori ini.</p>
                <p>Semua penugasan dan evaluasi akademik telah ditinjau.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
