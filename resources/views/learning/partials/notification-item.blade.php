@php
    $isRead = $notif['is_read'] ?? false;
    $category = $notif['category'] ?? 'tugas';
    $iconType = $notif['icon_type'] ?? 'alert';
    $notifTs = $notif['timestamp'] ?? time();
    $fullDateStr = \Carbon\Carbon::createFromTimestamp($notifTs)->translatedFormat('l, d F Y - H:i');
@endphp

<div class="group flex items-start justify-between gap-4 p-4 sm:p-4.5 transition-all duration-200 {{ $isRead ? 'opacity-60 hover:opacity-100 bg-white hover:bg-slate-50/60' : 'opacity-100 bg-slate-50/70 hover:bg-slate-100/60' }}">
    <div class="flex items-start gap-4 min-w-0 flex-1">
        <!-- Standalone Enlarged Single-Color Icon (Tanpa label background, satu warna rapi) -->
        <div class="shrink-0 mt-0.5 {{ $isRead ? 'text-slate-400' : 'text-slate-600' }}">
            @if($category === 'nilai' || $iconType === 'grade')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="6"></circle>
                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                </svg>
            @elseif($category === 'diskusi' || $iconType === 'chat')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            @elseif($category === 'sistem' || $iconType === 'system')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            @elseif($iconType === 'quiz')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            @elseif($iconType === 'check')
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            @else
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                    <path d="M9 14l2 2 4-4"></path>
                </svg>
            @endif
        </div>

        <!-- Notification Details (Tanpa label sistem/nilai, judul & isi langsung bersih) -->
        <div class="min-w-0 flex-1 space-y-1.5">
            <h3 class="text-sm sm:text-[15px] font-bold transition-colors leading-snug {{ $isRead ? 'text-slate-500 group-hover:text-slate-800 font-semibold' : 'text-slate-900 group-hover:text-[#102f50]' }}">
                <a href="{{ route('mahasiswa.notifications.read', [$notif['id'], 'target' => $notif['link']]) }}" class="hover:underline">
                    {{ $notif['title'] }}
                </a>
            </h3>

            <p class="text-xs sm:text-sm leading-relaxed max-w-3xl {{ $isRead ? 'text-slate-400' : 'text-slate-600' }}">
                {{ $notif['message'] }}
            </p>

            <!-- Actions Row -->
            <div class="flex items-center gap-2.5 pt-1 flex-wrap">
                @if(!empty($notif['action_label']))
                    <a href="{{ route('mahasiswa.notifications.read', [$notif['id'], 'target' => $notif['link']]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs active:scale-[0.98] transition-all {{ $isRead ? 'bg-slate-600 hover:bg-slate-800' : 'bg-[#102f50] hover:bg-[#081d33]' }}">
                        <span>{{ $notif['action_label'] }}</span>
                        <svg class="h-3.5 w-3.5 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                @endif

                @if(!$isRead)
                    <form action="{{ route('mahasiswa.notifications.read', $notif['id']) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 shadow-2xs transition-all cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Tandai dibaca</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('mahasiswa.notifications.delete', $notif['id']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus notifikasi ini?');">
                    @csrf
                    <button type="submit" title="Hapus notifikasi" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-500 hover:text-white hover:bg-red-600 hover:border-red-600 shadow-2xs transition-all cursor-pointer">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span class="sr-only sm:not-sr-only">Hapus</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Timestamp on Right (Cukup huruf saja tanpa label bg, tanpa icon jam, tanpa tulisan baru) -->
    <span class="text-xs font-semibold shrink-0 whitespace-nowrap pt-0.5 {{ $isRead ? 'text-slate-400' : 'text-slate-600' }}" title="{{ $fullDateStr }}">
        {{ $notif['time'] }}
    </span>
</div>


