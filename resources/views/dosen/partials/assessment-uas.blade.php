{{-- ========================================================= --}}
{{-- PARTIAL: PENILAIAN UAS (UAS-01)                           --}}
{{-- ========================================================= --}}
@php
    $uasStudents = [
        ['no' => '01', 'initials' => 'AP', 'name' => 'Andi Pratama',  'number' => '2024081001', 'cpmk1' => 86, 'cpmk2' => 80, 'cpmk3' => 88, 'submitted_at' => '10 Jan 2027, 13:45 WIB'],
        ['no' => '02', 'initials' => 'BS', 'name' => 'Budi Santoso',  'number' => '2024081002', 'cpmk1' => 80, 'cpmk2' => 75, 'cpmk3' => 78, 'submitted_at' => '10 Jan 2027, 14:10 WIB'],
        ['no' => '03', 'initials' => 'CL', 'name' => 'Citra Lestari', 'number' => '2024081003', 'cpmk1' => 95, 'cpmk2' => 90, 'cpmk3' => 94, 'submitted_at' => '10 Jan 2027, 11:20 WIB'],
        ['no' => '04', 'initials' => 'DS', 'name' => 'Dedi Saputra',  'number' => '2024081004', 'cpmk1' => 64, 'cpmk2' => 60, 'cpmk3' => 65, 'submitted_at' => '09 Jan 2027, 23:58 WIB'],
        ['no' => '05', 'initials' => 'EW', 'name' => 'Eka Wahyuni',   'number' => '2024081005', 'cpmk1' => 90, 'cpmk2' => 86, 'cpmk3' => 90, 'submitted_at' => '10 Jan 2027, 09:15 WIB'],
        ['no' => '06', 'initials' => 'FM', 'name' => 'Fajar Maulana', 'number' => '2024081006', 'cpmk1' => 80, 'cpmk2' => 73, 'cpmk3' => 76, 'submitted_at' => '10 Jan 2027, 14:02 WIB'],
    ];
@endphp

<div class="assessment-sheet">
{{-- Assessment Overview Card --}}
<div class="surface assessment-overview p-6 space-y-4">
    <div class="assessment-overview-layout flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2">
                <span class="status font-bold text-brand bg-brand-soft border-brand-soft uppercase text-[11px]">EVALUASI AKHIR</span>
                <span class="status font-semibold text-muted bg-canvas text-[11px]">Bobot Mata Kuliah: 30%</span>
            </div>
            <h2 class="text-xl font-bold text-ink">Ujian Akhir Semester (UAS)</h2>
            <p class="text-xs text-muted leading-relaxed max-w-2xl">
                Penilaian komprehensif akhir semester menguji seluruh indikator capaian pembelajaran lulusan (CPMK 01, CPMK 02, dan CPMK 03) secara menyeluruh melalui studi kasus terintegrasi.
            </p>
            <div class="assessment-meta flex flex-wrap items-center gap-4 pt-1 text-xs text-muted">
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg>
                    Format: <strong class="text-ink font-semibold">Ujian Komprehensif Studi Kasus Terintegrasi</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m7 12 3 3 7-7"/></svg>
                    Total Soal: <strong class="text-ink font-semibold">5 Butir (CPMK 01, CPMK 02, CPMK 03)</strong>
                </span>
            </div>
        </div>

        {{-- Target CPMK & Bobot Distribusi Boxes (desain 1.md) --}}
        <div class="assessment-targets flex flex-wrap items-center gap-3 shrink-0">
            <div class="assessment-target rounded-xl border border-brand/20 bg-brand-soft/40 p-3 text-center min-w-[140px] shadow-2xs">
                <div class="flex items-center justify-center gap-1">
                    <span class="font-mono text-xs font-bold text-brand">CPMK-01</span>
                    <span class="text-[10px] font-bold text-brand bg-white px-1.5 py-0.5 rounded">50%</span>
                </div>
                <p class="text-[11px] font-semibold text-ink mt-1">2 Soal · Porsi 50,00</p>
                <p class="text-[10px] text-muted">Target Skor ≥ 50</p>
            </div>
            <div class="assessment-target rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-center min-w-[140px] shadow-2xs">
                <div class="flex items-center justify-center gap-1">
                    <span class="font-mono text-xs font-bold text-amber-900">CPMK-02</span>
                    <span class="text-[10px] font-bold text-amber-900 bg-white px-1.5 py-0.5 rounded">50%</span>
                </div>
                <p class="text-[11px] font-semibold text-ink mt-1">2 Soal · Porsi 50,00</p>
                <p class="text-[10px] text-amber-800">Target Skor ≥ 80</p>
            </div>
        </div>
    </div>

    {{-- Formula Banner --}}
    <div class="rounded-lg bg-canvas p-3 border border-line/60 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <span class="rounded bg-brand text-white font-bold px-1.5 py-0.5 text-[10px] font-mono">FORMULA</span>
            <span class="text-muted font-mono">Nilai UAS = (Nilai CPMK-01 × 50%) + (Nilai CPMK-02 × 50%)</span>
        </div>
        <span class="text-[11px] text-muted font-medium">Nilai per CPMK maks 100 · Porsi soal = 100/n</span>
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
        <button type="button" onclick="triggerOBESync('Data nilai UAS diekspor')" class="button-secondary text-xs">
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
                <th scope="col" class="text-center min-w-[140px]">
                    NILAI UAS
                    <span class="block text-[10px] font-normal text-muted mt-0.5">(Skala 0–100)</span>
                </th>
                <th scope="col" class="text-center min-w-[150px]">
                    CAPAIAN CPMK-01
                    <span class="block text-[10px] font-normal text-muted mt-0.5">(Bobot 50% · ≥50)</span>
                </th>
                <th scope="col" class="text-center min-w-[150px]">
                    CAPAIAN CPMK-02
                    <span class="block text-[10px] font-normal text-muted mt-0.5">(Bobot 50% · ≥80)</span>
                </th>
                <th scope="col" class="text-center min-w-[130px]">RINCIAN SOAL</th>
                <th scope="col" class="min-w-[170px]">WAKTU PENGUMPULAN</th>
                <th scope="col" class="text-center min-w-[110px]">AKSI DOKUMEN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($uasStudents as $stu)
                @php
                    $calculatedScore = round(($stu['cpmk1'] * 0.5) + ($stu['cpmk2'] * 0.5), 2);
                    $statusKey = ($stu['cpmk1'] >= 50 && $stu['cpmk2'] >= 80) ? 'memenuhi' : (($stu['cpmk2'] < 80 && $stu['cpmk1'] >= 50) ? 'evaluasi' : 'belum');
                @endphp
                <tr class="student-row" data-name="{{ strtolower($stu['name']) }}" data-number="{{ $stu['number'] }}" data-status="{{ $statusKey }}">
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
                    {{-- Nilai UAS: Dihitung otomatis dari Σ (nilai_cpmk * bobot_cpmk) --}}
                    <td class="text-center align-middle">
                        <span class="font-mono text-sm font-extrabold text-brand score-display" id="score-{{ $stu['number'] }}">
                            {{ number_format($calculatedScore, 2, ',', '.') }}
                        </span>
                        <span class="block text-[10px] text-muted">dari 100</span>
                    </td>
                    {{-- Capaian CPMK-01 (Bobot 50%) --}}
                    <td class="text-center align-middle">
                        <input type="number" min="0" max="100" step="0.01"
                               value="{{ $stu['cpmk1'] }}"
                               class="field text-center font-mono text-xs font-bold py-1 px-1.5 w-20 mx-auto block border border-line bg-white shadow-none
                                      {{ $stu['cpmk1'] >= 50 ? 'text-emerald-700' : 'text-danger' }}"
                               data-student="{{ $stu['number'] }}"
                               data-cpmk="cpmk1"
                               data-cpmk-weight="50"
                               data-threshold="50"
                               onchange="recalcScore(this)"
                               aria-label="Capaian CPMK-01 {{ $stu['name'] }}">
                    </td>
                    {{-- Capaian CPMK-02 (Bobot 50%) --}}
                    <td class="text-center align-middle">
                        <input type="number" min="0" max="100" step="0.01"
                               value="{{ $stu['cpmk2'] }}"
                               class="field text-center font-mono text-xs font-bold py-1 px-1.5 w-20 mx-auto block border border-line bg-white shadow-none
                                      {{ $stu['cpmk2'] >= 80 ? 'text-emerald-700' : 'text-danger' }}"
                               data-student="{{ $stu['number'] }}"
                               data-cpmk="cpmk2"
                               data-cpmk-weight="50"
                               data-threshold="80"
                               onchange="recalcScore(this)"
                               aria-label="Capaian CPMK-02 {{ $stu['name'] }}">
                    </td>
                    {{-- Tombol Lihat Rincian Butir Soal Mahasiswa (Section 10 desain 1.md) --}}
                    <td class="text-center align-middle">
                        <button type="button" onclick="openStudentBreakdownModal('{{ $stu['name'] }}', '{{ $stu['number'] }}', {{ $stu['cpmk1'] }}, {{ $stu['cpmk2'] }}, {{ $calculatedScore }})"
                                class="inline-flex items-center gap-1 rounded bg-slate-100 hover:bg-slate-200 px-2 py-1 text-[11px] font-semibold text-ink transition">
                            <span>4 Soal</span>
                        </button>
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
    <div class="assessment-count flex flex-wrap items-center justify-between gap-4 p-4 border-t border-line/60">
        <p class="text-xs text-muted" id="student-count-text">
            Menampilkan <strong class="text-ink">{{ count($uasStudents) }}</strong> dari <strong class="text-ink">{{ count($uasStudents) }}</strong> mahasiswa terdaftar
        </p>
    </div>
</div>

<div class="surface assessment-savebar p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3 text-xs text-muted">
        <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand font-bold">
            ∑
        </span>
        <div>
            <p class="font-bold text-ink">Kalkulasi Otomatis Berbasis CPMK (desain 1.md)</p>
            <p class="text-muted">Nilai UAS dihitung otomatis dari akumulasi: Nilai CPMK × (Jumlah Soal CPMK / Total Soal).</p>
        </div>
    </div>

    <div class="assessment-save-actions flex items-center gap-3 self-end sm:self-auto">
        <a href="{{ route('dosen.grades') }}" class="button-secondary text-xs">
            Batal
        </a>
        <button type="button" onclick="triggerOBESync('Nilai UAS berhasil disimpan!')" class="button-primary text-xs">
            <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8M14 5v2"/></svg>
            Simpan Nilai UAS
        </button>
    </div>
</div>

</div>

{{-- Modal Rincian Butir Soal (Transparansi Perhitungan Bagian 10 desain 1.md) --}}
<div id="breakdown-modal" hidden class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="surface max-w-4xl w-full rounded-2xl shadow-xl border border-line/60 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 border-b border-line/60 flex items-center justify-between bg-canvas/30">
            <div>
                <span class="text-[11px] font-bold text-brand uppercase tracking-wider">Rincian Perhitungan Butir Soal (OBE)</span>
                <h3 class="text-base font-bold text-ink" id="modal-student-name">Nama Mahasiswa</h3>
                <p class="text-xs text-muted font-mono" id="modal-student-nim">NIM: 2024081001</p>
            </div>
            <button type="button" onclick="document.getElementById('breakdown-modal').setAttribute('hidden', '')" class="h-8 w-8 rounded-lg bg-canvas text-muted hover:text-ink font-bold flex items-center justify-center">×</button>
        </div>
        <div class="p-5 overflow-y-auto space-y-4 text-xs">
            <p class="text-muted leading-relaxed">
                Tabel di bawah memperlihatkan bagaimana skor mentah tiap butir soal dikonversi menjadi persentase skor, dikalikan porsi soal di dalam CPMK, hingga membentuk Nilai CPMK dan Nilai Asesmen total.
            </p>
            <div class="overflow-x-auto border border-line/60 rounded-xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-canvas/50 border-b border-line/60 text-muted text-[11px]">
                            <th class="py-2.5 px-3">Soal</th>
                            <th class="py-2.5 px-3">Tipe Soal</th>
                            <th class="py-2.5 px-3 text-center">CPMK</th>
                            <th class="py-2.5 px-3 text-center">Poin Dosen</th>
                            <th class="py-2.5 px-3 text-center">Jawaban</th>
                            <th class="py-2.5 px-3 text-center">Skor</th>
                            <th class="py-2.5 px-3 text-center">Persen</th>
                            <th class="py-2.5 px-3 text-center">Porsi (100/n)</th>
                            <th class="py-2.5 px-3 text-right">Nilai Soal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 1</td>
                            <td class="py-2 px-3 text-muted">Pilihan Ganda</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-brand">CPMK 1</td>
                            <td class="py-2 px-3 text-center font-mono">10</td>
                            <td class="py-2 px-3 text-center text-emerald-700 font-medium">Benar</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">10</td>
                            <td class="py-2 px-3 text-center font-mono">100%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">50,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">50,00</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 2</td>
                            <td class="py-2 px-3 text-muted">PG Kompleks (Parsial)</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-brand">CPMK 1</td>
                            <td class="py-2 px-3 text-center font-mono">20</td>
                            <td class="py-2 px-3 text-center text-ink font-medium">Parsial Benar</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">15</td>
                            <td class="py-2 px-3 text-center font-mono">75%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">50,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">37,50</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 3</td>
                            <td class="py-2 px-3 text-muted">Benar / Salah</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-amber-800">CPMK 2</td>
                            <td class="py-2 px-3 text-center font-mono">10</td>
                            <td class="py-2 px-3 text-center text-emerald-700 font-medium">Benar</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">10</td>
                            <td class="py-2 px-3 text-center font-mono">100%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">50,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">50,00</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 4</td>
                            <td class="py-2 px-3 text-muted">Menjodohkan</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-amber-800">CPMK 2</td>
                            <td class="py-2 px-3 text-center font-mono">25</td>
                            <td class="py-2 px-3 text-center text-ink font-medium">4 dari 5 pasang</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">20</td>
                            <td class="py-2 px-3 text-center font-mono">80%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">50,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">40,00</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 5</td>
                            <td class="py-2 px-3 text-muted">Esai Analisis</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-emerald-800">CPMK 3</td>
                            <td class="py-2 px-3 text-center font-mono">35</td>
                            <td class="py-2 px-3 text-center text-blue-700 font-medium">Dinilai Dosen (30)</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">30</td>
                            <td class="py-2 px-3 text-center font-mono">85,71%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">100,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">85,71</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 sm:grid-cols-4 pt-2">
                <div class="rounded-xl border border-brand/20 bg-brand-soft/40 p-3">
                    <span class="text-[10px] font-bold text-brand uppercase">Nilai CPMK 1</span>
                    <p class="text-base font-extrabold text-brand mt-0.5">87,50</p>
                    <p class="text-[10px] text-muted">50,00 + 37,50 · Bobot 40%</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                    <span class="text-[10px] font-bold text-amber-800 uppercase">Nilai CPMK 2</span>
                    <p class="text-base font-extrabold text-amber-900 mt-0.5">90,00</p>
                    <p class="text-[10px] text-muted">50,00 + 40,00 · Bobot 40%</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase">Nilai CPMK 3</span>
                    <p class="text-base font-extrabold text-emerald-900 mt-0.5">85,71</p>
                    <p class="text-[10px] text-muted">85,71 · Bobot 20%</p>
                </div>
                <div class="rounded-xl border border-line/60 bg-white p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-ink uppercase">Nilai UAS</span>
                    <p class="text-base font-extrabold text-ink mt-0.5" id="modal-assessment-score">88,14</p>
                    <p class="text-[10px] text-muted">(87,5×0,4)+(90×0,4)+(85,71×0,2)</p>
                </div>
            </div>
        </div>
        <div class="p-4 border-t border-line/60 flex justify-end bg-canvas/30">
            <button type="button" onclick="document.getElementById('breakdown-modal').setAttribute('hidden', '')" class="button-secondary text-xs">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Script: Hitung ulang Nilai UAS berdasarkan bobot CPMK (desain 1.md) --}}
<script>
function recalcScore(inputEl) {
    const studentId = inputEl.dataset.student;
    const row = inputEl.closest('tr');
    const cpmkInputs = row.querySelectorAll('input[data-cpmk]');
    let assessmentTotal = 0;

    cpmkInputs.forEach(inp => {
        const val = parseFloat(inp.value) || 0;
        const weight = (parseFloat(inp.dataset.cpmkWeight) || 33.33) / 100;
        assessmentTotal += val * weight;

        const threshold = parseFloat(inp.dataset.threshold) || 0;
        inp.classList.toggle('text-emerald-700', val >= threshold);
        inp.classList.toggle('text-danger', val < threshold);
    });

    const rounded = Math.round(assessmentTotal * 100) / 100;
    const scoreEl = document.getElementById('score-' + studentId);
    if (scoreEl) {
        scoreEl.textContent = rounded.toFixed(2).replace('.', ',');
    }
}

function openStudentBreakdownModal(name, nim, cpmk1, cpmk2, cpmk3, score) {
    const modal = document.getElementById('breakdown-modal');
    if (!modal) return;
    document.getElementById('modal-student-name').textContent = name;
    document.getElementById('modal-student-nim').textContent = 'NIM: ' + nim;
    document.getElementById('modal-assessment-score').textContent = typeof score === 'number' ? score.toFixed(2).replace('.', ',') : score;
    modal.removeAttribute('hidden');
}
</script>

