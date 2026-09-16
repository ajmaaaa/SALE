{{-- ============================================================ --}}
{{-- PARTIAL: Header & Tab Navigasi Ruang Penilaian              --}}
{{-- $selectedCourse, $activeType, $courseId required            --}}
{{-- ============================================================ --}}

{{-- Breadcrumb & Class Title --}}
@php
    $selectedSection = request()->query('section', 'A');
@endphp

{{-- Unified Premium Control Card for Course, Section, and Room Actions --}}
<div class="surface p-6 rounded-2xl border border-line/60 shadow-sm space-y-5">
    {{-- Top Bar: Title & Selectors --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-line/50 pb-5">
        <div class="space-y-1">
            <nav class="flex items-center gap-2 text-xs text-muted mb-1 font-medium">
                <a href="{{ route('dosen.grades') }}" class="hover:text-brand">Kelas Saya</a>
                <span>/</span>
                <span class="text-brand font-bold">{{ $selectedCourse['code'] }}</span>
                <span>/</span>
                <span class="text-ink font-semibold">Kelas {{ $selectedSection }}</span>
            </nav>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">{{ $selectedCourse['title'] }}</h1>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-soft px-3 py-1 text-xs font-bold text-brand">
                    <span class="h-2 w-2 rounded-full bg-brand"></span>
                    Kelas {{ $selectedSection }} · 32 Mahasiswa
                </span>
            </div>
            <p class="text-xs text-muted">Semester Ganjil 2026/2027 · Evaluasi Penilaian Terintegrasi CPMK &amp; CPL</p>
        </div>

        {{-- Dropdowns & Class Action Button --}}
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <form method="get" action="{{ route('dosen.grades') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="room" value="1">
                <input type="hidden" name="type" value="{{ $activeType }}">
                
                <div class="relative">
                    <label for="course-switcher" class="sr-only">Pilih Matkul</label>
                    <select id="course-switcher" name="course" onchange="this.form.submit()" class="field text-xs font-bold text-ink bg-canvas border-line/80 py-2.5 pr-8 pl-3.5 rounded-xl">
                        <option value="1" @selected($courseId === 1)>Pemrograman Web (IF204)</option>
                        <option value="2" @selected($courseId === 2)>Interaksi Manusia &amp; Komputer (IF218)</option>
                        <option value="3" @selected($courseId === 3)>Kecerdasan Buatan Terapan (IF221)</option>
                        <option value="4" @selected($courseId === 4)>Rekayasa Perangkat Lunak (IF230)</option>
                    </select>
                </div>

                <div class="relative">
                    <label for="section-switcher" class="sr-only">Pilih Kelas</label>
                    <select id="section-switcher" name="section" onchange="this.form.submit()" class="field text-xs font-bold text-brand bg-brand-soft border-brand-soft py-2.5 pr-8 pl-3.5 rounded-xl">
                        <option value="A" @selected($selectedSection === 'A')>Kelas A (Reguler)</option>
                        <option value="B" @selected($selectedSection === 'B')>Kelas B (Paralel)</option>
                        <option value="C" @selected($selectedSection === 'C')>Kelas C (Eksekutif)</option>
                    </select>
                </div>
            </form>

            <button type="button" onclick="document.getElementById('room-manage-class').toggleAttribute('hidden')" class="button-secondary text-xs py-2 px-3.5 font-bold rounded-xl shadow-2xs">
                + Kelola Kelas
            </button>
        </div>
    </div>

    {{-- Collapsible Class CRUD Management Panel --}}
    <div id="room-manage-class" hidden class="p-4 rounded-xl bg-canvas border border-line/60 space-y-3">
        <div class="flex items-center justify-between border-b border-line/50 pb-2">
            <h3 class="text-xs font-bold text-ink">Daftar Kelas Parallel (CRUD Kelas) — {{ $selectedCourse['title'] }}</h3>
            <button type="button" onclick="document.getElementById('room-manage-class').setAttribute('hidden', '')" class="text-xs font-bold text-muted hover:text-ink">Tutup ×</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-3 text-xs">
            <div class="p-3 rounded-lg bg-white border border-line/60 flex items-center justify-between">
                <div>
                    <span class="font-bold text-brand">Kelas A</span>
                    <p class="text-[11px] text-muted">32 Mahasiswa · Reguler</p>
                </div>
                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Aktif</span>
            </div>
            <div class="p-3 rounded-lg bg-white border border-line/60 flex items-center justify-between">
                <div>
                    <span class="font-bold text-ink">Kelas B</span>
                    <p class="text-[11px] text-muted">28 Mahasiswa · Paralel</p>
                </div>
                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Aktif</span>
            </div>
            <div class="p-3 rounded-lg bg-white border border-line/60 flex items-center justify-between">
                <div>
                    <span class="font-bold text-ink">Kelas C</span>
                    <p class="text-[11px] text-muted">20 Mahasiswa · Eksekutif</p>
                </div>
                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">Aktif</span>
            </div>
        </div>
    </div>

    {{-- Bottom Bar: View Switcher (Input Nilai / Capaian CPMK) & Assessment Sub-tabs --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between pt-1">
        {{-- Primary View Switcher Tabs --}}
        <div class="inline-flex rounded-xl bg-canvas p-1 border border-line/60">
            <a href="{{ route('dosen.grades', ['room' => 1, 'course' => $selectedCourse['id'], 'section' => $selectedSection, 'type' => $activeType]) }}"
               class="rounded-lg px-4 py-2 text-xs font-bold transition {{ !request()->routeIs('dosen.academic*') ? 'bg-brand-dark text-white shadow-xs' : 'text-muted hover:text-ink' }}">
                Input Nilai Kelas
            </a>
            <a href="{{ route('dosen.academic', $selectedCourse['id']) }}"
               class="rounded-lg px-4 py-2 text-xs font-bold transition {{ request()->routeIs('dosen.academic*') ? 'bg-brand-dark text-white shadow-xs' : 'text-muted hover:text-ink' }}">
                Capaian CPMK &amp; Rubrik
            </a>
        </div>

        {{-- Assessment Types Sub-tabs --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted mr-1.5 hidden lg:inline">Asesmen:</span>
            @foreach(['tugas' => 'Tugas', 'quiz' => 'Quiz', 'project' => 'Project', 'uts' => 'UTS', 'uas' => 'UAS'] as $key => $label)
                <a href="{{ route('dosen.grades', ['room' => 1, 'course' => $selectedCourse['id'], 'section' => $selectedSection, 'type' => $key]) }}"
                   class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $activeType === $key ? 'bg-brand text-white shadow-2xs' : 'bg-canvas text-ink hover:bg-brand-soft hover:text-brand border border-line/40' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</div>
