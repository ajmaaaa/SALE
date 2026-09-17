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
<header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.dashboard') }}" class="hover:text-brand">Dashboard</a>
            <span>/</span>
            <span class="text-ink font-semibold">Penilaian Kelas</span>
        </nav>
        <h1 class="page-heading">Kelas yang Saya Ajar</h1>
        <p class="page-description">Kelola penilaian dan capaian CPMK dari kelas yang sedang Anda ampu.</p>
    </div>
    <div class="flex flex-wrap gap-2.5 shrink-0">
        <a class="button-secondary" href="{{ route('dosen.gradebook') }}">Rekap Nilai Kelas</a>
    </div>
</header>

{{-- Filter Controls --}}
<div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
    <form class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]" onsubmit="event.preventDefault()">
        <label for="class-search" class="text-xs font-semibold text-muted shrink-0">Cari Kelas:</label>
        <input type="text" id="class-search" onkeyup="filterClasses()" placeholder="Cari kode atau nama mata kuliah..." class="field text-xs font-semibold max-w-xs">
        <label for="status-filter" class="text-xs font-semibold text-muted shrink-0">Status:</label>
        <select id="status-filter" onchange="filterClasses()" class="field text-xs font-semibold w-48">
            <option value="all">Semua Status Penilaian</option>
            <option value="belum_selesai">Penilaian Belum Selesai</option>
            <option value="selesai">Penilaian Selesai</option>
        </select>
    </form>
    <div class="flex items-center gap-4 text-xs text-muted">
        <span>Total Kelas: <strong class="text-ink">6 Aktif</strong></span>
        <span>Beban Mengajar: <strong class="text-brand">18 SKS</strong></span>
        <span>Mahasiswa: <strong class="text-ink">184 Total</strong></span>
    </div>
</div>

{{-- Class Table --}}
<div class="surface overflow-x-auto">
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
