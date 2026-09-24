@php
    $isRead = $notif['is_read'] ?? false;
    $iconType = $notif['icon_type'] ?? 'alert';
    $actionType = $notif['action_type'] ?? 'link';
@endphp

<div class="group flex items-start justify-between gap-4 py-3.5 px-4 rounded-xl transition-all duration-200 border-b border-slate-100/80 last:border-0 {{ $isRead ? 'opacity-55 hover:opacity-100 bg-white hover:bg-slate-50/80' : 'opacity-100 bg-slate-50/90 border-l-4 border-l-[#102f50] shadow-2xs hover:bg-slate-100/80' }}">
    <div class="flex items-start gap-3.5 min-w-0 flex-1">
        <!-- Ultra-Plain & Simple Monochrome Icon -->
        <div class="shrink-0 mt-1 text-slate-400">
            @if($iconType === 'alert')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="12" y1="9" x2="12.01" y2="9"></line>
                </svg>
            @elseif($iconType === 'check')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            @elseif($iconType === 'video')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 10l5-3v10l-5-3v-4z"></path>
                    <rect x="2" y="6" width="13" height="12" rx="2"></rect>
                </svg>
            @elseif($iconType === 'chat')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            @else
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="6"></circle>
                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                </svg>
            @endif
        </div>

        <!-- Notification Details -->
        <div class="min-w-0 flex-1 space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-sm sm:text-[15px] font-bold text-slate-900 group-hover:text-[#102f50] transition-colors leading-snug">
                    <a href="{{ route('mahasiswa.notifications.read', [$notif['id'], 'target' => $notif['link']]) }}">
                        {{ $notif['title'] }}
                    </a>
                </h3>
            </div>

            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-3xl">
                {{ $notif['message'] }}
            </p>

            <!-- Actions Row -->
            <div class="flex items-center gap-3 pt-1">
                @if(!empty($notif['action_label']))
                    <a href="{{ route('mahasiswa.notifications.read', [$notif['id'], 'target' => $notif['link']]) }}"
                       class="inline-flex items-center rounded-lg bg-[#102f50] px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-slate-900 transition-all">
                        {{ $notif['action_label'] }}
                    </a>
                @endif

                @if(!$isRead)
                    <form action="{{ route('mahasiswa.notifications.read', $notif['id']) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-slate-400 hover:text-slate-700 transition-colors">
                            Tandai dibaca
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Timestamp -->
    <span class="text-xs font-medium text-slate-400 shrink-0 whitespace-nowrap pt-0.5">
        {{ $notif['time'] }}
    </span>
</div>
