{{-- ============================================================ --}}
{{-- PARTIAL: Daftar Kelas yang Saya Ajar                        --}}
{{-- ============================================================ --}}
@php
    $classesList = [
        ['id' => 1, 'name' => 'Pemrograman Web', 'code' => 'IF-302', 'class' => 'TI-A', 'sks' => 3, 'semester' => 5, 'note' => 'Koordinator MK', 'total' => 32, 'done' => 3, 'total_items' => 5, 'cpmk_status' => 'warning', 'cpmk_label' => 'Evaluasi CPMK-02', 'course_id' => 1, 'data_status' => 'belum_selesai', 'data_name' => 'pemrograman web', 'data_code' => 'if-302'],
        ['id' => 2, 'name' => 'Basis Data Relasional', 'code' => 'IF-204', 'class' => 'TI-B', 'sks' => 3, 'semester' => 3, 'note' => 'Reguler Pagi', 'total' => 30, 'done' => 5, 'total_items' => 5, 'cpmk_status' => 'ok', 'cpmk_label' => '4/4 CPMK Tercapai', 'course_id' => 1, 'data_status' => 'selesai', 'data_name' => 'basis data relasional', 'data_code' => 'if-204'],
        ['id' => 3, 'name' => 'Algoritma & Struktur Data', 'code' => 'IF-101', 'class' => 'TI-C', 'sks' => 4, 'semester' => 1, 'note' => 'Praktikum Terintegrasi', 'total' => 28, 'done' => 2, 'total_items' => 5, 'cpmk_status' => 'muted', 'cpmk_label' => '⏱ Berjalan', 'course_id' => 1, 'data_status' => 'belum_selesai', 'data_name' => 'algoritma struktur data', 'data_code' => 'if-101'],
        ['id' => 4, 'name' => 'Pemrograman Web', 'code' => 'IF-302', 'class' => 'TI-D', 'sks' => 3, 'semester' => 5, 'note' => 'Paralel TI-D', 'total' => 31, 'done' => 4, 'total_items' => 5, 'cpmk_status' => 'ok', 'cpmk_label' => '4/4 CPMK Tercapai', 'course_id' => 1, 'data_status' => 'belum_selesai', 'data_name' => 'pemrograman web', 'data_code' => 'if-302'],
        ['id' => 5, 'name' => 'Jaringan Komputer', 'code' => 'IF-208', 'class' => 'TI-E', 'sks' => 3, 'semester' => 3, 'note' => 'Lab Jaringan Dasar', 'total' => 29, 'done' => 5, 'total_items' => 5, 'cpmk_status' => 'ok', 'cpmk_label' => '4/4 CPMK Tercapai', 'course_id' => 1, 'data_status' => 'selesai', 'data_name' => 'jaringan komputer', 'data_code' => 'if-208'],
        ['id' => 6, 'name' => 'Rekayasa Perangkat Lunak', 'code' => 'SI-305', 'class' => 'SI-A', 'sks' => 3, 'semester' => 5, 'note' => 'Sistem Informasi', 'total' => 34, 'done' => 1, 'total_items' => 5, 'cpmk_status' => 'brand', 'cpmk_label' => '4/4 CPMK Tercapai', 'course_id' => 4, 'data_status' => 'belum_selesai', 'data_name' => 'rekayasa perangkat lunak', 'data_code' => 'si-305'],
    ];
    $cpmkColorMap = ['ok' => 'text-brand', 'warning' => 'text-danger', 'muted' => 'text-muted', 'brand' => 'text-brand'];
@endphp

{{-- Header --}}
<header class="space-y-1.5 pb-2">
    <nav class="flex items-center gap-2 text-xs font-medium text-muted mb-1.5">
        <a href="{{ route('dosen.dashboard') }}" class="hover:text-brand transition">Dashboard</a>
        <span class="text-line/80">/</span>
        <span class="text-ink font-semibold">Penilaian Kelas</span>
    </nav>
    <h1 class="page-heading text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">Kelas yang Saya Ajar</h1>
    <p class="page-description mt-1 text-xs sm:text-sm text-muted leading-relaxed max-w-2xl">Kelola penilaian dan capaian CPMK dari kelas yang sedang Anda ampu.</p>
</header>

{{-- Filter & Overview Controls --}}
<div class="surface p-5 rounded-2xl border border-line/70 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <form class="flex flex-wrap items-center gap-3 flex-1" onsubmit="event.preventDefault()">
        {{-- Search Field with Icon --}}
        <div class="relative flex-1 min-w-[240px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="class-search" onkeyup="filterClasses()" aria-label="Cari kelas" placeholder="Cari kode atau nama mata kuliah..." class="field pl-9 text-xs py-2 font-medium w-full shadow-2xs">
        </div>

        {{-- Status Filter Dropdown --}}
        <div class="w-full sm:w-52">
            <select id="status-filter" onchange="filterClasses()" aria-label="Filter status penilaian" class="field text-xs py-2 font-medium text-ink bg-white shadow-2xs">
                <option value="all">Semua Status Penilaian</option>
                <option value="belum_selesai">Penilaian Belum Selesai</option>
                <option value="selesai">Penilaian Selesai</option>
            </select>
        </div>
    </form>

    {{-- Overview Stats Badges --}}
    <div class="flex items-center gap-2.5 text-xs shrink-0 flex-wrap">
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
            <span class="text-muted font-medium">Total Kelas:</span>
            <span class="font-bold text-ink">6 Aktif</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
            <span class="text-muted font-medium">Beban Mengajar:</span>
            <span class="font-bold text-ink">18 SKS</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
            <span class="text-muted font-medium">Mahasiswa:</span>
            <span class="font-bold text-ink">184 Total</span>
        </div>
    </div>
</div>

{{-- Class Table --}}
<div class="surface rounded-2xl border border-line/70 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
    <table class="admin-table w-full" id="classes-table">
        <thead>
            <tr>
                <th class="whitespace-nowrap">MATA KULIAH</th>
                <th class="text-center">KELAS</th>
                <th class="whitespace-nowrap">PESERTA</th>
                <th class="min-w-[160px]">PROGRESS PENILAIAN</th>
                <th>STATUS CAPAIAN CPMK</th>
                <th class="text-right">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @foreach($classesList as $cls)
                @php $pct = round(($cls['done'] / $cls['total_items']) * 100); @endphp
                <tr class="class-row" data-name="{{ $cls['data_name'] }}" data-code="{{ $cls['data_code'] }}" data-status="{{ $cls['data_status'] }}">
                    <td>
                        <p class="font-semibold text-ink">
                            <a href="{{ route('dosen.grades', ['room' => 1, 'course' => $cls['course_id'], 'type' => 'uts']) }}" class="hover:text-brand">{{ $cls['name'] }}</a>
                            <span class="font-mono text-xs text-muted ml-1">{{ $cls['code'] }}</span>
                        </p>
                        <p class="text-xs text-muted">{{ $cls['sks'] }} SKS · Semester {{ $cls['semester'] }} ·
                            @if($cls['note'] === 'Koordinator MK')
                                <span class="font-semibold text-brand">{{ $cls['note'] }}</span>
                            @else
                                {{ $cls['note'] }}
                            @endif
                        </p>
                    </td>
                    <td class="text-center"><span class="font-mono text-xs font-bold text-ink">{{ $cls['class'] }}</span></td>
                    <td class="whitespace-nowrap"><span class="text-xs font-semibold text-ink">{{ $cls['total'] }} Mahasiswa</span></td>
                    <td>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-ink">{{ $cls['done'] }}/{{ $cls['total_items'] }} {{ $cls['done'] === $cls['total_items'] ? 'Selesai' : 'Penilaian' }}</span>
                                <span class="{{ $pct === 100 ? 'text-brand font-semibold' : 'text-muted' }}">{{ $pct }}%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-canvas overflow-hidden">
                                <div class="h-full bg-brand rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="text-xs font-semibold {{ $cpmkColorMap[$cls['cpmk_status']] }}">{{ $cls['cpmk_label'] }}</span></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('dosen.grades', ['room' => 1, 'course' => $cls['course_id'], 'type' => 'uts']) }}" class="button-primary text-xs py-1.5 px-3">
                            Buka Ruang Penilaian
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Footer --}}
<div class="mt-4 flex flex-wrap items-center justify-between gap-4">
    <p class="text-xs text-muted leading-relaxed max-w-xl">
        Pilih kelas untuk masuk langsung ke Ruang Penilaian. Seluruh komponen tugas, kuis, UTS, dan UAS akan terpetakan otomatis terhadap matriks CPMK RPS yang telah disahkan.
    </p>
    <div class="flex items-center gap-3">
        <span class="text-xs text-muted" id="class-count-text">
            Menampilkan <strong class="text-ink">6</strong> dari <strong class="text-ink">6</strong> kelas aktif
        </span>
    </div>
</div>
