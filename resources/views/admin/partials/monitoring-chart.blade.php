{{-- Each series has its own explicitly labelled scale. Tooltips also work with keyboard focus. --}}
<div class="mt-5 rounded-xl bg-canvas/40 p-4 sm:p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h3 class="text-sm font-semibold">{{ $chartTitle }}</h3><p class="mt-1 text-xs text-muted">Sorot grafik untuk melihat detail.</p></div>
        <div class="flex flex-wrap gap-4 text-xs text-muted">
            @foreach($chartTracks as $track)
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $track['color'] }}" aria-hidden="true"></span>{{ $track['label'] }} ({{ $track['unit'] }})</span>
            @endforeach
        </div>
    </div>
    <div class="relative mt-4 overflow-x-auto pb-2" role="group" aria-label="{{ $chartTitle }}">
        <div class="relative min-w-[480px] pt-20">
            <p class="absolute left-0 top-0 text-xs text-muted">{{ $chartCaption }}</p>
            <div class="mb-3 flex justify-between gap-4 text-[11px] text-muted"><span>{{ $chartTracks[0]['label'] }} ({{ $chartTracks[0]['unit'] }})</span>@if(count($chartTracks) > 1)<span>{{ $chartTracks[1]['label'] }} (skala kanan %)</span>@endif</div>
            <div class="relative mx-10">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-48" aria-hidden="true">
                    @foreach([100, 75, 50, 25, 0] as $tick)
                        <div class="absolute inset-x-0 border-t border-line/40" style="top: {{ 100 - $tick }}%"><span class="absolute -left-10 -top-2 w-9 text-right text-[10px] text-muted">{{ number_format($chartTracks[0]['max'] * $tick / 100, 0, ',', '.') }}</span>@if(count($chartTracks) > 1)<span class="absolute -right-10 -top-2 text-[10px] text-muted">{{ $tick }}%</span>@endif</div>
                    @endforeach
                </div>
                <div class="flex gap-1">
                    @foreach($chartRows as $row)
                        @php
                            $tooltip = $row['label'];
                            foreach ($chartTracks as $track) {
                                $tooltip .= ', '.$track['label'].': '.number_format($row[$track['key']], 0, ',', '.').' '.$track['unit'];
                            }
                        @endphp
                        <div tabindex="0" role="img" aria-label="{{ $tooltip }}" class="group min-w-0 flex-1 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                            <div class="relative flex h-48 items-end justify-center gap-0.5 group-hover:bg-canvas/60 group-focus:bg-canvas/60">
                                @foreach($chartTracks as $track)
                                    <div class="w-3 max-w-[35%] rounded-t {{ $track['color'] }}" style="height: {{ max(0, min(100, $row[$track['key']] / $track['max'] * 100)) }}%"></div>
                                @endforeach
                            </div>
                            <span class="mt-3 block text-center text-[10px] leading-4 text-muted">{{ $row['label'] }}</span>
                            <div aria-hidden="true" class="pointer-events-none absolute left-0 -top-14 z-10 hidden max-w-full rounded-lg bg-ink px-3 py-2 text-xs leading-5 text-white shadow-sm group-hover:block group-focus:block">{{ $tooltip }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @if(count($chartTracks) > 1)<p class="mt-3 text-xs leading-5 text-muted">Pengunjung memakai skala kiri, CPU memakai skala kanan (0–100%). Bandingkan perubahan keduanya sepanjang waktu; tinggi batang mewakili satuan yang berbeda.</p>@endif
</div>
