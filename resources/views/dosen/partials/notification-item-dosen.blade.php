@php
    $isRead = $notif['is_read'] ?? false;
    $category = $notif['category'] ?? 'tugas';
    $iconType = $notif['icon_type'] ?? 'alert';
    $notifTs = $notif['timestamp'] ?? time();
    $fullDateStr = \Carbon\Carbon::createFromTimestamp($notifTs)->translatedFormat('l, d F Y - H:i');
@endphp

<div class="group flex items-start justify-between gap-4 p-4 sm:p-4.5 transition-all duration-200 {{ $isRead ? 'opacity-60 hover:opacity-100 bg-white hover:bg-slate-50/60' : 'opacity-100 bg-slate-50/70 hover:bg-slate-100/60' }}">
    <div class="flex items-start gap-4 min-w-0 flex-1">
        <!-- Icon Notifikasi (h-5 w-5, stroke-width 1.8) -->
        <div class="shrink-0 mt-0.5 {{ $isRead ? 'text-slate-400' : 'text-slate-600' }}">
            @if($category === 'nilai' || $iconType === 'grade')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                </svg>
            @elseif($category === 'diskusi' || $iconType === 'chat')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            @elseif($category === 'sistem' || $iconType === 'system')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8z"></path>
                    <path d="M10 20h4"></path>
                </svg>
            @elseif($iconType === 'quiz')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            @elseif($iconType === 'check')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            @else
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            @endif
        </div>

        <!-- Notification Details -->
        <div class="min-w-0 flex-1 space-y-1.5">
            <h3 class="text-sm sm:text-[15px] font-bold transition-colors leading-snug {{ $isRead ? 'text-slate-500 group-hover:text-slate-800 font-semibold' : 'text-slate-900 group-hover:text-[#102f50]' }}">
                <a href="{{ route('dosen.notifications.read', [$notif['id'], 'target' => $notif['link']]) }}" class="hover:underline">
                    {{ $notif['title'] }}
                </a>
            </h3>

            <p class="text-xs sm:text-sm leading-relaxed max-w-3xl {{ $isRead ? 'text-slate-400' : 'text-slate-600' }}">
                {{ $notif['message'] }}
            </p>

            <!-- Actions Row -->
            <div class="flex items-center gap-2.5 pt-1 flex-wrap">
                @if(!empty($notif['action_label']))
                    <a href="{{ route('dosen.notifications.read', [$notif['id'], 'target' => $notif['link']]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs active:scale-[0.98] transition-all {{ $isRead ? 'bg-slate-600 hover:bg-slate-800' : 'bg-[#102f50] hover:bg-[#081d33]' }}">
                        <span>{{ $notif['action_label'] }}</span>
                        <svg class="h-3.5 w-3.5 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                @endif

                @if(!$isRead)
                    <form action="{{ route('dosen.notifications.read', $notif['id']) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-emerald-700 hover:underline transition-colors cursor-pointer bg-transparent border-0 p-0">
                            <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Tandai dibaca</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('dosen.notifications.delete', $notif['id']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus notifikasi ini?');">
                    @csrf
                    <button type="submit" title="Hapus notifikasi" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-red-600 hover:underline transition-colors cursor-pointer bg-transparent border-0 p-0">
                        <svg class="h-3.5 w-3.5 text-slate-400 hover:text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>Hapus</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Timestamp -->
    <span class="text-xs font-semibold shrink-0 whitespace-nowrap pt-0.5 {{ $isRead ? 'text-slate-400' : 'text-slate-600' }}" title="{{ $fullDateStr }}">
        {{ $notif['time'] }}
    </span>
</div>
