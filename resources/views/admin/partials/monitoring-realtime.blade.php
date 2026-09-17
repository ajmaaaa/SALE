<div data-realtime-panel data-demo="{{ $demo ? 'true' : 'false' }}" class="space-y-6">
    @foreach([
        ['ram', 'RAM saat ini', 40, '3,2 GB / 8 GB', '4,8 GB tersedia'],
        ['cpu', 'Beban CPU saat ini', 24, '24%', 'Penggunaan prosesor'],
    ] as [$key, $label, $percent, $value, $caption])
        <div data-resource="{{ $key }}">
            <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-semibold">{{ $label }}</h3><p data-resource-value class="text-sm font-semibold tabular-nums">{{ $demo ? $value : 'Belum diukur' }}</p></div>
            <div data-resource-progress class="mt-3 h-2 overflow-hidden rounded-full bg-line" @if($demo) role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" @endif>
                <div data-resource-bar class="h-full rounded-full bg-brand transition-[width] duration-1000 ease-in-out motion-reduce:transition-none" style="width: {{ $demo ? $percent : 0 }}%"></div>
            </div>
            <p data-resource-caption class="mt-2 text-xs text-muted">{{ $demo ? $caption : 'Menunggu data pengukuran server.' }}</p>
        </div>
    @endforeach
    @if($demo)
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-muted"><p data-realtime-status>Simulasi diperbarui setiap 3 detik.</p><button data-realtime-toggle type="button" aria-pressed="false" class="font-semibold text-brand">Jeda simulasi</button></div>
        <noscript><p class="text-xs text-muted">Aktifkan JavaScript untuk melihat pergerakan simulasi.</p></noscript>
    @endif
</div>
