@extends('layouts.mahasiswa')

@section('title', 'Notifikasi | SALE')
@section('header', 'Notifikasi')

@section('content')
<div class="space-y-6">
    <header class="pb-1">
        <h1 class="page-heading">Notifikasi Pembelajaran</h1>
        <p class="page-description">Pemberitahuan penugasan, kuis, nilai terbit, sistem, dan diskusi akademik semester ini.</p>
    </header>

    {{-- Filter Kategori Notifikasi & Tombol Aksi (Tandai & Hapus Semua) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <nav class="flex flex-wrap items-center gap-2 text-xs sm:text-sm font-semibold overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Kategori Notifikasi">
            {{-- Semua --}}
            <a href="{{ route('mahasiswa.notifications') }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ empty($selectedCategory) ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Semua</span>
                <span class="text-xs font-bold {{ empty($selectedCategory) ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['all'] ?? count($notifications) }})
                </span>
            </a>

            {{-- Tugas & Kuis --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'tugas']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'tugas' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Tugas &amp; Kuis</span>
                @if(isset($categoryCounts['tugas']))
                    <span class="text-xs font-bold {{ $selectedCategory === 'tugas' ? 'text-white' : 'text-slate-600' }}">
                        ({{ $categoryCounts['tugas'] }})
                    </span>
                @endif
            </a>

            {{-- Nilai --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'nilai']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'nilai' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Nilai</span>
                @if(isset($categoryCounts['nilai']))
                    <span class="text-xs font-bold {{ $selectedCategory === 'nilai' ? 'text-white' : 'text-slate-600' }}">
                        ({{ $categoryCounts['nilai'] }})
                    </span>
                @endif
            </a>

            {{-- Sistem --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'sistem']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'sistem' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Sistem</span>
                @if(isset($categoryCounts['sistem']))
                    <span class="text-xs font-bold {{ $selectedCategory === 'sistem' ? 'text-white' : 'text-slate-600' }}">
                        ({{ $categoryCounts['sistem'] }})
                    </span>
                @endif
            </a>

            {{-- Diskusi --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'diskusi']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'diskusi' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Diskusi</span>
                @if(isset($categoryCounts['diskusi']))
                    <span class="text-xs font-bold {{ $selectedCategory === 'diskusi' ? 'text-white' : 'text-slate-600' }}">
                        ({{ $categoryCounts['diskusi'] }})
                    </span>
                @endif
            </a>
        </nav>

        @php
            $hasUnread = collect($notifications)->contains(fn ($n) => empty($n['is_read']));
        @endphp
        @if($hasUnread || count($notifications) > 0)
            <div class="flex items-center gap-2 pb-2.5 sm:pb-3 shrink-0">
                @if($hasUnread)
                    <form action="{{ route('mahasiswa.notifications.read', 'all') }}" method="POST">
                        @csrf
                        @foreach($notifications as $n)
                            @if(empty($n['is_read']))
                                <input type="hidden" name="notification_ids[]" value="{{ $n['id'] }}">
                            @endif
                        @endforeach
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#102f50] px-3 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-[#081d33] active:scale-[0.98] transition-all cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-sky-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Tandai semua telah dibaca</span>
                        </button>
                    </form>
                @endif

                @if(count($notifications) > 0)
                    <form action="{{ route('mahasiswa.notifications.clear') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua notifikasi? Tindakan ini tidak dapat dibatalkan.');">
                        @csrf
                        @if(!empty($selectedCategory))
                            <input type="hidden" name="category" value="{{ $selectedCategory }}">
                        @endif
                        <button type="submit" class="button-secondary min-h-0 text-xs py-1.5 px-3 font-semibold text-slate-600 hover:text-red-600 hover:border-red-300 hover:bg-red-50/40 active:scale-[0.98] transition-all cursor-pointer shadow-2xs">
                            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span>Hapus Semua</span>
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    {{-- Daftar Notifikasi Dikelompokkan per Tanggal (Pemisah teks berlabel & berjarak) --}}
    <div class="space-y-8 pt-1">
        @forelse($groupedNotifications as $dateLabel => $groupNotifs)
            <div class="space-y-3">
                {{-- Pemisah Tanggal Berjarak Nyaman dengan Teks Berlabel --}}
                <div class="px-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        {{ $dateLabel }}
                    </span>
                </div>

                {{-- List Notifikasi Dalam Grup Tanggal --}}
                <section class="surface overflow-hidden rounded-xl border border-line divide-y divide-slate-100 shadow-2xs" aria-label="Notifikasi {{ $dateLabel }}">
                    @foreach($groupNotifs as $notif)
                        @include('learning.partials.notification-item', ['notif' => $notif])
                    @endforeach
                </section>
            </div>
        @empty
            <div class="surface rounded-xl border border-line p-12 text-center text-xs text-muted space-y-3 shadow-2xs">
                <div class="h-12 w-12 mx-auto rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8zM10 20h4"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-bold text-ink">Tidak ada notifikasi untuk kategori ini.</p>
                    <p class="text-slate-500">Semua pemberitahuan akademik telah ditinjau atau dibersihkan.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href*="/read"], a[href*="/notifikasi/"]');
            if (!link) return;
            const row = link.closest('.group');
            if (!row) return;

            row.classList.remove('opacity-100', 'bg-slate-50/70', 'hover:bg-slate-100/60');
            row.classList.add('opacity-60', 'bg-white', 'hover:bg-slate-50/60');

            const iconContainer = row.querySelector('.shrink-0');
            if (iconContainer) {
                iconContainer.classList.remove('text-slate-600');
                iconContainer.classList.add('text-slate-400');
            }

            const title = row.querySelector('h3');
            if (title) {
                title.classList.remove('text-slate-900', 'group-hover:text-[#102f50]');
                title.classList.add('text-slate-500', 'font-semibold');
            }

            const markReadForm = row.querySelector('form[action*="/read"]');
            if (markReadForm) {
                markReadForm.remove();
            }

            const actionBtn = row.querySelector('a.shadow-2xs');
            if (actionBtn) {
                actionBtn.classList.remove('bg-[#102f50]', 'hover:bg-[#081d33]');
                actionBtn.classList.add('bg-slate-600', 'hover:bg-slate-800');
            }
        });
    });

    window.addEventListener('pageshow', function (event) {
        var isBack = event.persisted ||
            (window.performance && window.performance.getEntriesByType && window.performance.getEntriesByType("navigation")[0]?.type === "back_forward") ||
            (window.performance && window.performance.navigation && window.performance.navigation.type === 2);
        if (isBack) {
            window.location.reload();
        }
    });
</script>
@endsection

