{{-- ============================================================ --}}
{{-- PARTIAL: Header & Tab Navigasi Ruang Penilaian              --}}
{{-- $selectedCourse, $activeType, $courseId required            --}}
{{-- ============================================================ --}}

{{-- Breadcrumb & Class Title --}}
<header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.grades') }}" class="hover:text-brand">Kelas Saya</a>
            <span>/</span>
            <a href="{{ route('dosen.course.show', $selectedCourse['id']) }}" class="hover:text-brand">{{ $selectedCourse['title'] }}</a>
            <span>/</span>
            <span class="text-ink font-semibold">TI-A</span>
        </nav>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="page-heading">{{ $selectedCourse['title'] }}</h1>
            <span class="status font-semibold text-muted bg-canvas">
                | TI-A | 32 Mahasiswa | Semester Ganjil 2026/2027
            </span>
        </div>
    </div>

    <div class="flex items-center gap-2.5 shrink-0">
        <form method="get" action="{{ route('dosen.grades') }}" class="inline-flex items-center gap-2">
            <input type="hidden" name="room" value="1">
            <input type="hidden" name="type" value="{{ $activeType }}">
            <label for="course-switcher" class="sr-only">Ganti Kelas</label>
            <select id="course-switcher" name="course" onchange="this.form.submit()" class="field text-xs font-semibold">
                <option value="1" @selected($courseId === 1)>Pemrograman Web (TI-A)</option>
                <option value="2" @selected($courseId === 2)>Interaksi Manusia &amp; Komputer (TI-B)</option>
                <option value="3" @selected($courseId === 3)>Kecerdasan Buatan Terapan (TI-C)</option>
                <option value="4" @selected($courseId === 4)>Rekayasa Perangkat Lunak (SI-A)</option>
            </select>
        </form>
    </div>
</header>

{{-- Top Tabs: Ringkasan / Input Nilai / Capaian CPMK --}}
<div class="surface p-1.5 flex items-center gap-2">
    <a href="{{ route('dosen.grades', ['room' => 1, 'course' => $selectedCourse['id'], 'type' => $activeType]) }}" class="px-4 py-2 text-xs font-semibold bg-brand-dark text-white rounded-lg transition shadow-xs">
        Input Nilai
    </a>
    <a href="{{ route('dosen.academic', $selectedCourse['id']) }}" class="px-4 py-2 text-xs font-semibold text-muted hover:text-ink rounded-lg transition">
        Capaian CPMK
    </a>
</div>

{{-- Jenis Penilaian Tabs --}}
<div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-xs font-bold uppercase tracking-wider text-muted mr-1">JENIS PENILAIAN:</span>
        @foreach(['tugas' => 'Tugas', 'quiz' => 'Quiz', 'project' => 'Project', 'uts' => 'UTS', 'uas' => 'UAS'] as $key => $label)
            <a href="{{ route('dosen.grades', ['room' => 1, 'course' => $selectedCourse['id'], 'type' => $key]) }}"
               class="px-4 py-2 text-xs font-semibold rounded-lg transition {{ $activeType === $key ? 'bg-brand-dark text-white shadow-xs' : 'bg-canvas text-ink hover:bg-brand-soft hover:text-brand' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
        </svg>
        Struktur OBE Terverifikasi RPS
    </div>
</div>
