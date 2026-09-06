<section aria-labelledby="academic-heading" class="min-w-0">
    <div class="mb-4 flex min-h-[56px] items-start justify-between gap-4">
        <div><h2 id="academic-heading" class="section-heading">Perkembangan akademik</h2><p class="mt-1 text-sm text-muted">Hasil belajar dari semester ke semester.</p></div>
        <span class="shrink-0 text-xs text-muted">Data contoh</span>
    </div>
    <div class="rounded-xl bg-white p-5 shadow-sm sm:p-6" data-academic-chart>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><p class="text-xs text-muted">IPK kumulatif</p><p class="mt-1 text-3xl font-semibold tracking-tight">3,65 <span class="text-sm font-normal text-muted">/ 4,00</span></p><p class="mt-2 text-xs text-muted">84 SKS · 4 semester</p></div>
            <div class="flex rounded-lg bg-canvas p-1" role="group" aria-label="Jenis grafik"><button type="button" data-chart-mode="ips" aria-pressed="true" class="rounded-md bg-white px-3 py-2 text-xs font-semibold text-brand shadow-sm">IP semester</button><button type="button" data-chart-mode="ipk" aria-pressed="false" class="rounded-md px-3 py-2 text-xs font-semibold text-muted">IPK</button></div>
        </div>
        <svg class="mt-6 w-full overflow-visible" viewBox="0 0 460 230" role="img" aria-labelledby="chart-title chart-description">
            <title id="chart-title">IP semester</title><desc id="chart-description">Data contoh IP semester 1 sampai 4: 3,45; 3,62; 3,74; 3,80.</desc>
            <g stroke="#e8edf2" stroke-dasharray="3 6"><path d="M42 25H435M42 68H435M42 111H435M42 154H435M42 197H435"/></g>
            <g fill="#77818c" font-size="11"><text x="8" y="29">4,0</text><text x="8" y="72">3,0</text><text x="8" y="115">2,0</text><text x="8" y="158">1,0</text><text x="8" y="201">0,0</text></g>
            <path data-chart-area d="M60 48.65L180 41.34L300 36.18L420 33.6V197H60Z" fill="#edf3f9"/>
            <path data-chart-line d="M60 48.65L180 41.34L300 36.18L420 33.6" fill="none" stroke="#1f4b7a" stroke-width="2.5" stroke-linejoin="round"/>
            @foreach([['x'=>60,'y'=>48.65,'ips'=>'3,45','ipk'=>'3,45'],['x'=>180,'y'=>41.34,'ips'=>'3,62','ipk'=>'3,54'],['x'=>300,'y'=>36.18,'ips'=>'3,74','ipk'=>'3,61'],['x'=>420,'y'=>33.6,'ips'=>'3,80','ipk'=>'3,65']] as $point)
            <g data-chart-point data-x="{{ $point['x'] }}" data-ips="{{ $point['ips'] }}" data-ipk="{{ $point['ipk'] }}"><text data-point-value x="{{ $point['x'] }}" y="{{ $point['y'] - 8 }}" text-anchor="middle" fill="#1f4b7a" font-size="12" font-weight="600">{{ $point['ips'] }}</text><text x="{{ $point['x'] }}" y="222" text-anchor="middle" fill="#77818c" font-size="11">Sem {{ $loop->iteration }}</text></g>
            @endforeach
        </svg>
        <p class="mt-4 text-xs leading-5 text-muted" data-chart-caption>IP semester menunjukkan hasil setiap semester. IPK memperhitungkan seluruh SKS yang sudah ditempuh.</p>
        <details class="mt-4 text-xs"><summary class="cursor-pointer font-semibold text-brand">Lihat rincian semester</summary><table class="mt-3 w-full text-left"><caption class="sr-only">Data contoh akademik per semester</caption><thead><tr class="text-muted"><th class="py-2">Semester</th><th>SKS</th><th>IP</th><th>IPK</th></tr></thead><tbody>@foreach([[1,20,'3,45','3,45'],[2,22,'3,62','3,54'],[3,22,'3,74','3,61'],[4,20,'3,80','3,65']] as $row)<tr>@foreach($row as $cell)<td class="py-2">{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></details>
    </div>
</section>
