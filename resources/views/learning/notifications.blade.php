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
                <span data-category-count="all" class="text-xs font-bold {{ empty($selectedCategory) ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['all'] ?? count($notifications) }})
                </span>
            </a>

            {{-- Materi --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'materi']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'materi' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Materi</span>
                <span data-category-count="materi" class="text-xs font-bold {{ $selectedCategory === 'materi' ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['materi'] ?? 0 }})
                </span>
            </a>

            {{-- Tugas & Kuis --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'tugas']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'tugas' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Tugas &amp; Kuis</span>
                <span data-category-count="tugas" class="text-xs font-bold {{ $selectedCategory === 'tugas' ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['tugas'] ?? 0 }})
                </span>
            </a>

            {{-- Nilai --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'nilai']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'nilai' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Nilai</span>
                <span data-category-count="nilai" class="text-xs font-bold {{ $selectedCategory === 'nilai' ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['nilai'] ?? 0 }})
                </span>
            </a>

            {{-- Sistem --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'sistem']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'sistem' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Sistem</span>
                <span data-category-count="sistem" class="text-xs font-bold {{ $selectedCategory === 'sistem' ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['sistem'] ?? 0 }})
                </span>
            </a>

            {{-- Diskusi --}}
            <a href="{{ route('mahasiswa.notifications', ['category' => 'diskusi']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs whitespace-nowrap transition-all {{ $selectedCategory === 'diskusi' ? 'bg-[#102f50] text-white font-bold shadow-2xs' : 'bg-slate-100 border border-slate-200/80 text-slate-700 font-semibold hover:bg-slate-200 hover:text-slate-900' }}">
                <span>Diskusi</span>
                <span data-category-count="diskusi" class="text-xs font-bold {{ $selectedCategory === 'diskusi' ? 'text-white' : 'text-slate-600' }}">
                    ({{ $categoryCounts['diskusi'] ?? 0 }})
                </span>
            </a>
        </nav>

        @php
            $hasUnread = collect($notifications)->contains(fn ($n) => empty($n['is_read']));
        @endphp
        <div class="flex items-center gap-2 pb-2.5 sm:pb-3 shrink-0">
            <div id="notif-mark-all-container" class="{{ $hasUnread ? '' : 'hidden' }}">
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
            </div>

            <div id="notif-clear-all-container" class="{{ count($notifications) > 0 ? '' : 'hidden' }}">
                <form action="{{ route('mahasiswa.notifications.clear') }}" method="POST"
                      data-confirm="Apakah Anda yakin ingin menghapus semua notifikasi? Tindakan ini tidak dapat dibatalkan."
                      data-confirm-title="Hapus Semua Notifikasi"
                      data-confirm-label="Hapus Semua">
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
            </div>
        </div>
    </div>

    {{-- Daftar Notifikasi Dikelompokkan per Tanggal --}}
    <div id="notification-container" class="space-y-8 pt-1">
        @include('learning.partials.notification-list', ['groupedNotifications' => $groupedNotifications, 'notifications' => $notifications])
    </div>
</div>

<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        let currentFingerprint = '{{ md5(json_encode(collect($notifications)->map(fn ($n) => $n['id'] . ':' . (!empty($n['is_read']) ? '1' : '0'))->all())) }}';

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

        // Real-time polling for notifications
        async function checkLiveNotifications() {
            if (document.visibilityState !== 'visible') return;

            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.status === 401) {
                    window.location.href = '{{ route('login') }}';
                    return;
                }
                if (!response.ok) return;
                const data = await response.json();
                if (!data.success) return;

                // Update category count badges
                if (data.category_counts) {
                    Object.entries(data.category_counts).forEach(([cat, count]) => {
                        const el = document.querySelector(`[data-category-count="${cat}"]`);
                        if (el) {
                            el.textContent = `(${count})`;
                        }
                    });
                }

                // Update mark all as read button visibility
                const markAllContainer = document.getElementById('notif-mark-all-container');
                if (markAllContainer) {
                    if (data.has_unread) {
                        markAllContainer.classList.remove('hidden');
                    } else {
                        markAllContainer.classList.add('hidden');
                    }
                }

                // Update clear all button visibility
                const clearAllContainer = document.getElementById('notif-clear-all-container');
                if (clearAllContainer) {
                    if (data.total_count > 0) {
                        clearAllContainer.classList.remove('hidden');
                    } else {
                        clearAllContainer.classList.add('hidden');
                    }
                }

                // If content fingerprint changed, update notification items smoothly
                if (data.fingerprint && data.fingerprint !== currentFingerprint) {
                    currentFingerprint = data.fingerprint;
                    const container = document.getElementById('notification-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }
                }

                // Dispatch global event for sidebar layout badge sync
                window.dispatchEvent(new CustomEvent('sale:live-status', {
                    detail: {
                        unread_notif_count: data.unread_count,
                        category_counts: data.category_counts
                    }
                }));
            } catch (err) {
                // Silently swallow background network glitches
            }
        }

        const notifPollInterval = setInterval(checkLiveNotifications, 4500);

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                checkLiveNotifications();
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

