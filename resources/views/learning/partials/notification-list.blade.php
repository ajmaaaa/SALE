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
