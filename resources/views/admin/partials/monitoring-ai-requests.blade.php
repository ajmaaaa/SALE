<div class="mt-6" aria-labelledby="ai-requests-heading">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 id="ai-requests-heading" class="text-sm font-semibold">Request terbaru</h3>
        <span class="text-xs text-muted">{{ $demo ? '12 respons contoh' : 'Belum ada respons tercatat' }}</span>
    </div>
    <p class="mt-2 text-xs leading-5 text-muted">Satu baris untuk setiap request yang selesai menghasilkan respons. Input adalah token yang dikirim; output adalah token jawaban AI.</p>
    <div tabindex="0" role="region" aria-label="Riwayat token per request AI" class="mt-4 max-h-96 overflow-auto rounded-xl border border-line/60 focus-visible:outline-2 focus-visible:outline-brand">
        <table class="admin-table w-full">
            <caption class="sr-only">Pemakaian token per request, terbaru terlebih dahulu{{ $demo ? ' (Data simulasi)' : '' }}</caption>
            <thead class="sticky top-0 z-10 bg-canvas"><tr><th>Model</th><th class="whitespace-nowrap">Token input</th><th class="whitespace-nowrap">Token output</th><th>Waktu selesai</th></tr></thead>
            <tbody>
                @if($demo)
                    @foreach([[37629, 823, 0], [36679, 95, 14], [36194, 240, 18], [34813, 1343, 25], [33997, 117, 46], [33822, 134, 50], [33290, 290, 55], [32757, 435, 60], [32348, 110, 72], [30734, 166, 89], [30126, 177, 98], [27722, 199, 112]] as [$input, $output, $secondsAgo])
                        @php($completedAt = now()->subSeconds($secondsAgo))
                        <tr>
                            <td><span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-1.5 w-1.5 rounded-full bg-brand" aria-hidden="true"></span><span class="font-medium">sale-ai-demo</span><span class="sr-only">Respons selesai</span></span></td>
                            <td class="tabular-nums text-muted">{{ number_format($input, 0, ',', '.') }}</td>
                            <td class="font-semibold tabular-nums text-brand">{{ number_format($output, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap text-xs text-muted"><time datetime="{{ $completedAt->toIso8601String() }}" title="{{ $completedAt->format('d M Y H:i:s T') }}">{{ $completedAt->format('H:i:s') }}</time></td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="4" class="px-4 py-10 text-center"><p class="text-sm font-semibold text-ink">Belum ada riwayat request</p><p class="mx-auto mt-2 max-w-sm text-xs leading-5 text-muted">Pencatatan token belum terhubung. Setelah aktif, pemakaian akan dicatat saat respons AI selesai.</p></td></tr>
                @endif
            </tbody>
        </table>
    </div>
    @if($demo)<p class="mt-3 text-xs text-muted">Nama model, token, dan waktu di atas adalah contoh tampilan.</p>@endif
</div>
