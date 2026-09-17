@php
    $isRealtime = $rangeData['range'] === 'realtime';
    $periodLabel = $rangeData['caption'];
    $rows = [];
    $visitorIds = [];
    if ($demo && ! $isRealtime) {
        foreach ($rangeData['buckets'] as $bucket) {
            $seed = (int) $bucket->format('mdHi') + 17;
            $visitors = 30 + ($seed * 13 % 170);
            $factor = match ($rangeData['step']) { 'minute', 'hour' => 1, 'month' => 25, default => 6 };
            $visitors *= $factor;
            $ids = range($seed % 1000, $seed % 1000 + $visitors - 1);
            $visitorIds = array_merge($visitorIds, $ids);
            $requests = $visitors * (8 + $seed % 7);
            $rows[] = [
                'label' => ($rangeData['label'])($bucket),
                'visitors' => $visitors, 'requests' => $requests,
                'cpu' => 18 + $seed % 43, 'peak' => 65 + $seed % 30,
                'ram' => 32 + $seed % 35, 'latency' => 110 + $seed % 190,
                'errors' => (int) floor($requests * (($seed % 8) / 1000)),
            ];
        }
    }
    $totalRequests = array_sum(array_column($rows, 'requests'));
    $averageCpu = count($rows) ? round(array_sum(array_column($rows, 'cpu')) / count($rows), 1) : 0;
    $averageRam = count($rows) ? round(array_sum(array_column($rows, 'ram')) / count($rows), 1) : 0;
    $peakCpu = count($rows) ? max(array_column($rows, 'peak')) : 0;
    $busiest = collect($rows)->sortByDesc('requests')->first();
    $formatNumber = fn ($value, $decimals = 0) => number_format($value, $decimals, ',', '.');
@endphp
<section class="surface overflow-hidden" aria-labelledby="server-heading">
    <div class="border-b border-line/60 p-5 sm:p-6">
        <h2 id="server-heading" class="section-heading">{{ $isRealtime ? 'Kondisi sistem saat ini' : 'Pengunjung & beban sistem' }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $isRealtime ? 'Pantau pemakaian RAM, beban CPU, dan kapasitas penyimpanan.' : 'Lihat jumlah pengguna yang mengakses SALE dan sumber daya yang digunakan pada periode pilihan.' }}</p>
        <div class="mt-5">@include('admin.partials.monitoring-filters')</div>
    </div>
    <div class="p-5 sm:p-6">
        @if($isRealtime)
            @include('admin.partials.monitoring-realtime')
        @else
        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Pengunjung unik', $formatNumber(count(array_unique($visitorIds))), 'Pengguna berbeda selama periode ini'],
                ['Permintaan ke sistem', $formatNumber($totalRequests), 'Seluruh permintaan, termasuk akses berulang'],
                ['Rata-rata CPU', $formatNumber($averageCpu, 1).'%', 'Rata-rata penggunaan prosesor'],
                ['Rata-rata RAM', $formatNumber($averageRam, 1).'%', 'Rata-rata penggunaan memori'],
            ] as [$label, $value, $description])
                <div class="rounded-xl border border-line/60 p-4"><dt class="text-xs font-medium text-muted">{{ $label }}</dt><dd class="mt-3 text-2xl font-semibold">{{ $demo ? $value : '—' }}</dd><p class="mt-2 text-xs leading-5 text-muted">{{ $description }}</p></div>
            @endforeach
        </dl>
        @if($demo)
            @include('admin.partials.monitoring-chart', [
                'chartTitle' => 'Kunjungan & beban CPU',
                'chartCaption' => $periodLabel.' · Data simulasi',
                'chartRows' => $rows,
                'chartTracks' => [
                    ['key' => 'visitors', 'label' => 'Pengunjung unik', 'unit' => 'orang', 'color' => 'bg-brand', 'max' => ceil(max(array_column($rows, 'visitors')) / 100) * 100],
                    ['key' => 'cpu', 'label' => 'CPU rata-rata', 'unit' => '%', 'color' => 'bg-slate-400', 'max' => 100],
                ],
            ])
            <div class="mt-5 rounded-xl bg-brand-soft px-4 py-3 text-sm leading-6 text-brand-dark"><span class="font-semibold">Ringkasan periode.</span> Permintaan terbanyak pada {{ $busiest['label'] }} ({{ $formatNumber($busiest['requests']) }} permintaan). Beban CPU tertinggi mencapai {{ $peakCpu }}%. Tercatat {{ $formatNumber(array_sum(array_column($rows, 'errors'))) }} permintaan gagal.</div>
            <details class="mt-6"><summary class="list-none cursor-pointer text-sm font-semibold text-brand [&::-webkit-details-marker]:hidden">Lihat rincian penggunaan</summary><p class="mt-3 text-xs text-muted">Waktu respons dalam milidetik (ms)</p>
            <div class="mt-3 max-h-96 overflow-auto rounded-xl border border-line/60">
                <table class="admin-table"><caption class="sr-only">Simulasi kunjungan dan beban sistem {{ $periodLabel }}</caption><thead class="sticky top-0 bg-white"><tr><th>Waktu</th><th>Pengunjung unik</th><th>Permintaan</th><th>CPU rata-rata</th><th>CPU tertinggi</th><th>RAM rata-rata</th><th>Respons rata-rata</th><th>Gagal</th></tr></thead><tbody>
                    @foreach($rows as $row)<tr><td class="whitespace-nowrap">{{ $row['label'] }}</td><td>{{ $formatNumber($row['visitors']) }}</td><td>{{ $formatNumber($row['requests']) }}</td><td>{{ $row['cpu'] }}%</td><td>{{ $row['peak'] }}%</td><td>{{ $row['ram'] }}%</td><td>{{ $row['latency'] }} ms</td><td>{{ $formatNumber($row['errors']) }}</td></tr>@endforeach
                </tbody></table>
            </div>
            <p class="mt-3 text-xs leading-5 text-muted">Pengunjung unik dihitung satu kali dalam periode yang dipilih. Jumlahnya tidak sama dengan penjumlahan pengunjung setiap baris karena pengguna dapat kembali mengakses SALE.</p></details>
        @else
            <div class="mt-5 rounded-xl border border-dashed border-line px-5 py-8 text-center"><h3 class="text-sm font-semibold">Belum ada data untuk periode ini</h3><p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-muted">Rekap kunjungan, permintaan, CPU, dan RAM akan tersedia setelah pencatatan aktivitas dan pengukuran server terhubung.</p></div>
        @endif
        @endif
        <div class="mt-6 border-t border-line/60 pt-5">
            <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-semibold">Penyimpanan saat ini</h3><p class="text-sm font-semibold">{{ $demo ? '42 GB / 100 GB' : 'Belum diukur' }}</p></div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-line" @if($demo) role="progressbar" aria-label="Penyimpanan terpakai" aria-valuenow="42" aria-valuemin="0" aria-valuemax="100" @endif><div class="h-full rounded-full bg-brand" style="width: {{ $demo ? 42 : 0 }}%"></div></div>
            <p class="mt-2 text-xs text-muted">{{ $demo ? '58 GB tersedia · Data simulasi.' : 'Kapasitas terbaru ditampilkan setelah pengukuran penyimpanan aktif.' }}</p>
        </div>
    </div>
</section>
