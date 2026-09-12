{{-- ========================================================= --}}
{{-- PARTIAL: PENILAIAN TUGAS (TUGAS-01)                       --}}
{{-- ========================================================= --}}
@php
    $tugasStudents = [
        ['no' => '01', 'initials' => 'AP', 'name' => 'Andi Pratama',  'number' => '2024081001', 'score' => 88, 'cpmk1' => 88, 'cpmk2' => 82, 'submitted_at' => '05 Sep 2026, 10:30 WIB'],
        ['no' => '02', 'initials' => 'BS', 'name' => 'Budi Santoso',  'number' => '2024081002', 'score' => 78, 'cpmk1' => 80, 'cpmk2' => 74, 'submitted_at' => '05 Sep 2026, 14:20 WIB'],
        ['no' => '03', 'initials' => 'CL', 'name' => 'Citra Lestari', 'number' => '2024081003', 'score' => 95, 'cpmk1' => 96, 'cpmk2' => 92, 'submitted_at' => '04 Sep 2026, 22:15 WIB'],
        ['no' => '04', 'initials' => 'DS', 'name' => 'Dedi Saputra',  'number' => '2024081004', 'score' => 55, 'cpmk1' => 58, 'cpmk2' => 50, 'submitted_at' => '06 Sep 2026, 08:05 WIB'],
        ['no' => '05', 'initials' => 'EW', 'name' => 'Eka Wahyuni',   'number' => '2024081005', 'score' => 86, 'cpmk1' => 88, 'cpmk2' => 82, 'submitted_at' => '05 Sep 2026, 09:50 WIB'],
        ['no' => '06', 'initials' => 'FM', 'name' => 'Fajar Maulana', 'number' => '2024081006', 'score' => 74, 'cpmk1' => 76, 'cpmk2' => 70, 'submitted_at' => '05 Sep 2026, 21:33 WIB'],
    ];
@endphp

<div class="assessment-sheet">
{{-- Assessment Overview Card --}}
<div class="surface assessment-overview p-6 space-y-4">
    <div class="assessment-overview-layout flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2">
                <span class="status font-bold text-brand bg-brand-soft border-brand-soft uppercase text-[11px]">TUGAS MANDIRI</span>
                <span class="status font-semibold text-muted bg-canvas text-[11px]">Bobot: 15%</span>
            </div>
            <h2 class="text-xl font-bold text-ink">Tugas 1: Implementasi Binary Search Tree</h2>
            <p class="text-xs text-muted leading-relaxed max-w-2xl">
                Penilaian tugas pemrograman mandiri untuk menguji pemahaman struktur data pohon pencarian biner, manipulasi simpul, traversal (in-order, pre-order, post-order), serta analisis kompleksitas waktu.
            </p>
            <div class="assessment-meta flex flex-wrap items-center gap-4 pt-1 text-xs text-muted">
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg>
                    Format: <strong class="text-ink font-semibold">Coding &amp; Laporan Usability</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="m7 12 3 3 7-7"/></svg>
                    CPMK Terukur: <strong class="text-ink font-semibold">CPMK 01, CPMK 02</strong>
                </span>
            </div>
        </div>

        {{-- Target CPMK boxes --}}
        <div class="assessment-targets flex flex-wrap items-center gap-3 shrink-0">
            <div class="assessment-target rounded-lg p-3 text-center min-w-[120px]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Target CPMK-01</p>
                <p class="text-xs font-semibold text-ink mt-0.5">Skor ≥ 50</p>
            </div>
            <div class="assessment-target rounded-lg p-3 text-center min-w-[130px]">
                <p class="text-[10px] font-bold uppercZZZZZZZZZZZZZZZZZZZZZZZZZZZZase tracking-wider text-amber-800">Target CPMK-02 (Ketat)</p>
                <p class="text-xs font-semibold text-amber-900 mt-0.5">Skor ≥ 75</p>
            </div>
        </div>
    </div>
</div>

{{-- Filter Controls --}}
<div class="surface assessment-toolbar p-4 flex flex-wrap items-center justify-between gap-4">
    <div class="assessment-filters flex flex-wrap items-center gap-3 flex-1 min-w-[260px]">
        <div class="relative flex-1 min-w-[200px]">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 shrink-0 text-muted" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
            </span>
            <input type="text" id="student-search" oninput="filterStudents()" aria-label="Cari mahasiswa berdasarkan nama atau NIM" placeholder="Cari nama atau NIM..." class="field pl-9 text-xs">
        </div>

        <select id="student-status-filter" onchange="filterStudents()" aria-label="Filter status capaian mahasiswa" class="field text-xs font-semibold w-44">
            <option value="all">Semua Status</option>
            <option value="memenuhi">Memenuhi Target</option>
            <option value="evaluasi">Evaluasi CPMK-02</option>
            <option value="belum">Belum Memenuhi</option>
        </select>
    </div>

    <div class="assessment-toolbar-actions flex items-center gap-2.5 shrink-0">
        <button type="button" data-grade-import-open aria-haspopup="dialog" aria-controls="grade-import" class="button-secondary text-xs">
            <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m13 2-9 12h7l-1 8 10-12h-7Z"/></svg>
            Unggah Nilai CSV/Excel
        </button>
        <button type="button" onclick="triggerOBESync('Data nilai Tugas diekspor')" class="button-secondary text-xs">
            <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 3v12m-5-5 5 5 5-5M4 15v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/></svg>
            Ekspor CSV
        </button>
    </div>
</div>

{{-- Student Table --}}
<div class="surface assessment-table-panel overflow-hidden">
    <div class="overflow-x-auto">
    <table class="admin-table assessment-table w-full" id="students-table">
        <thead>
            <tr>
                <th scope="col" class="w-12 text-center">NO</th>
                <th scope="col" class="min-w-[200px]">MAHASISWA</th>
                <th scope="col" class="text-center min-w-[130px]">NILAI TUGAS <span class="block font-normal mt-1">(BOBOT 15%)</span></th>
                <th scope="col" class="text-center min-w-[140px]">CAPAIAN CPMK-01 <span class="block font-normal mt-1">(≥50)</span></th>
                <th scope="col" class="text-center min-w-[140px]">CAPAIAN CPMK-02 <span class="block font-normal mt-1">(≥75)</span></th>
                <th scope="col" class="min-w-[170px]">WAKTU PENGUMPULAN</th>
                <th scope="col" class="text-center min-w-[110px]">AKSI DOKUMEN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tugasStudents as $stu)
                <tr class="student-row" data-name="{{ strtolower($stu['name']) }}" data-number="{{ $stu['number'] }}" data-status="{{ $stu['status_key'] ?? ($stu['cpmk1'] >= 50 ? ($stu['cpmk2'] >= 75 ? 'memenuhi' : 'evaluasi') : 'belum') }}">
                    <td class="text-center font-mono text-xs text-muted align-middle">
                        {{ $stu['no'] }}
                    </td>
                    <td class="align-middle">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-soft text-xs font-bold text-brand shrink-0">
                                {{ $stu['initials'] }}
                            </span>
                            <div>
                                <p class="font-semibold text-ink leading-tight">{{ $stu['name'] }}</p>
                                <p class="font-mono text-xs text-muted">{{ $stu['number'] }}</p>
                            </div>
                        </div>
                    </td>
                    {{-- Nilai Tugas: READONLY --}}
                    <td class="text-center align-middle">
                        <span class="font-mono text-sm font-bold text-ink score-display" id="score-{{ $stu['number'] }}">{{ $stu['score'] }}</span>
                    </td>
                    {{-- Capaian CPMK-01: EDITABLE --}}
                    <td class="text-center align-middle">
                        <input type="number" min="0" max="100" step="0.5"
                               value="{{ $stu['cpmk1'] }}"
                               class="field text-center font-mono text-xs font-bold py-1 px-1.5 w-16 mx-auto block border border-line bg-white shadow-none
                                      {{ $stu['cpmk1'] >= 50 ? 'text-emerald-700' : 'text-danger' }}"
                               data-student="{{ $stu['number'] }}"
                               data-cpmk="cpmk1"
                               data-threshold="50"
                               onchange="recalcScore(this)"
                               aria-label="Capaian CPMK-01 {{ $stu['name'] }}">
                    </td>
                    {{-- Capaian CPMK-02: EDITABLE --}}
                    <td class="text-center align-middle">
                        <input type="number" min="0" max="100" step="0.5"
                               value="{{ $stu['cpmk2'] }}"
                               class="field text-center font-mono text-xs font-bold py-1 px-1.5 w-16 mx-auto block border border-line bg-white shadow-none
                                      {{ $stu['cpmk2'] >= 75 ? 'text-emerald-700' : 'text-danger' }}"
                               data-student="{{ $stu['number'] }}"
                               data-cpmk="cpmk2"
                               data-threshold="75"
                               onchange="recalcScore(this)"
                               aria-label="Capaian CPMK-02 {{ $stu['name'] }}">
                    </td>
                    {{-- Waktu Pengumpulan --}}
                    <td class="align-middle whitespace-nowrap">
                        <span class="text-xs text-muted">{{ $stu['submitted_at'] }}</span>
                    </td>
                    {{-- Aksi Dokumen --}}
                    <td class="text-center align-middle">
                        @include('dosen.partials.submission-button')
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="assessment-count flex flex-wrap items-center justify-between gap-4">
        <p class="text-xs text-muted" id="student-count-text">
            Menampilkan <strong class="text-ink">{{ count($tugasStudents) }}</strong> dari <strong class="text-ink">{{ count($tugasStudents) }}</strong> mahasiswa terdaftar
        </p>
    </div>
</div>

<div class="surface assessment-savebar p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3 text-xs text-muted">
        <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand font-bold">
            i
        </span>
        <div>
            <p class="font-bold text-ink">Kalkulasi Otomatis OBE (Tugas)</p>
            <p class="text-muted">Nilai Tugas berkontribusi 15% pada kalkulasi akhir CPMK 01 dan CPMK 02.</p>
        </div>
    </div>

    <div class="assessment-save-actions flex items-center gap-3 self-end sm:self-auto">
        <a href="{{ route('dosen.grades') }}" class="button-secondary text-xs">
            Batal
        </a>
        <button type="button" onclick="triggerOBESync('Nilai Tugas 1 berhasil disimpan!')" class="button-primary text-xs">
            <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8M14 5v2"/></svg>
            Simpan Nilai TUGAS
        </button>
    </div>
</div>

</div>

{{-- Script: Hitung ulang Nilai Tugas dari rata-rata CPMK --}}
<script>
function recalcScore(inputEl) {
    const studentId = inputEl.dataset.student;
    const row       = inputEl.closest('tr');
    const cpmkInputs = row.querySelectorAll('input[data-cpmk]');
    let total = 0;
    cpmkInputs.forEach(inp => { total += parseFloat(inp.value) || 0; });
    const avg     = cpmkInputs.length > 0 ? (total / cpmkInputs.length) : 0;
    const rounded = Math.round(avg * 10) / 10;
    const scoreEl = document.getElementById('score-' + studentId);
    if (scoreEl) scoreEl.textContent = rounded;
    cpmkInputs.forEach(inp => {
        const threshold = parseFloat(inp.dataset.threshold) || 0;
        const val = parseFloat(inp.value) || 0;
        inp.classList.toggle('text-emerald-700', val >= threshold);
        inp.classList.toggle('text-danger',       val <  threshold);
    });
}
</script>
