{{-- ========================================================= --}}
{{-- PARTIAL: PENILAIAN UTS (UTS-01)                           --}}
{{-- ========================================================= --}}
@php
    $utsStudents = [
        ['no' => '01', 'initials' => 'AP', 'name' => 'Andi Pratama', 'number' => '2024081001', 'score' => 80, 'cpmk1' => 82, 'cpmk2' => 76, 'status_key' => 'evaluasi', 'status_label' => 'Evaluasi CPMK-02', 'status_class' => 'text-amber-700 bg-amber-50 border-amber-200'],
        ['no' => '02', 'initials' => 'BS', 'name' => 'Budi Santoso', 'number' => '2024081002', 'score' => 75, 'cpmk1' => 78, 'cpmk2' => 82, 'status_key' => 'memenuhi', 'status_label' => 'Memenuhi Target', 'status_class' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
        ['no' => '03', 'initials' => 'CL', 'name' => 'Citra Lestari', 'number' => '2024081003', 'score' => 90, 'cpmk1' => 92, 'cpmk2' => 88, 'status_key' => 'memenuhi', 'status_label' => 'Memenuhi Target', 'status_class' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
        ['no' => '04', 'initials' => 'DS', 'name' => 'Dedi Saputra', 'number' => '2024081004', 'score' => 60, 'cpmk1' => 65, 'cpmk2' => 58, 'status_key' => 'belum', 'status_label' => 'Belum Memenuhi', 'status_class' => 'text-danger bg-rose-50 border-rose-200'],
        ['no' => '05', 'initials' => 'EW', 'name' => 'Eka Wahyuni', 'number' => '2024081005', 'score' => 85, 'cpmk1' => 88, 'cpmk2' => 84, 'status_key' => 'memenuhi', 'status_label' => 'Memenuhi Target', 'status_class' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
        ['no' => '06', 'initials' => 'FM', 'name' => 'Fajar Maulana', 'number' => '2024081006', 'score' => 78, 'cpmk1' => 80, 'cpmk2' => 74, 'status_key' => 'evaluasi', 'status_label' => 'Evaluasi CPMK-02', 'status_class' => 'text-amber-700 bg-amber-50 border-amber-200'],
    ];
@endphp

<div class="assessment-sheet">
{{-- Assessment Overview Card --}}
<div class="surface assessment-overview p-6 space-y-4">
    <div class="assessment-overview-layout flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2">
                <span class="status font-bold text-brand bg-brand-soft border-brand-soft uppercase text-[11px]">EVALUASI UTAMA</span>
                <span class="status font-semibold text-muted bg-canvas text-[11px]">Bobot: 20%</span>
            </div>
            <h2 class="text-xl font-bold text-ink">Ujian Tengah Semester (UTS)</h2>
            <p class="text-xs text-muted leading-relaxed max-w-2xl">
                Penilaian komprehensif paruh semester menguji kemampuan penguasaan arsitektur aplikasi web, manajemen state, serta perancangan komponen antarmuka terintegrasi.
            </p>
            <div class="assessment-meta flex flex-wrap items-center gap-4 pt-1 text-xs text-muted">
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg>
                    Format: <strong class="text-ink font-semibold">Campuran 5 Tipe Soal (PG, PG Kompleks, Esai, B/S, Jodohkan)</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m7 12 3 3 7-7"/></svg>
                    Total Soal: <strong class="text-ink font-semibold">5 Butir (CPMK 01 &amp; CPMK 02)</strong>
                </span>
            </div>
        </div>

        {{-- Target CPMK & Bobot Distribusi Boxes (desain 1.md) --}}
        <div class="assessment-targets flex flex-wrap items-center gap-3 shrink-0">
            <div class="assessment-target rounded-xl border border-brand/20 bg-brand-soft/40 p-3 text-center min-w-[140px] shadow-2xs">
                <div class="flex items-center justify-center gap-1">
                    <span class="font-mono text-xs font-bold text-brand">CPMK-01</span>
                    <span class="text-[10px] font-bold text-brand bg-white px-1.5 py-0.5 rounded">60%</span>
                </div>
                <p class="text-[11px] font-semibold text-ink mt-1">3 Soal · Porsi 33,33</p>
                <p class="text-[10px] text-muted">Target Skor ≥ 50</p>
            </div>
            <div class="assessment-target rounded-xl border border-amber-200 bg-amber-50/60 p-3 text-center min-w-[140px] shadow-2xs">
                <div class="flex items-center justify-center gap-1">
                    <span class="font-mono text-xs font-bold text-amber-900">CPMK-02</span>
                    <span class="text-[10px] font-bold text-amber-900 bg-white px-1.5 py-0.5 rounded">40%</span>
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
            <span class="text-muted font-mono">Nilai UTS = (Nilai CPMK-01 × 60%) + (Nilai CPMK-02 × 40%)</span>
        </div>
        <span class="text-[11px] text-muted font-medium">Nilai per CPMK maks 100 · Skala asesmen 0–100</span>
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

        <select id="student-status-filter" onchange="filterStudents()" aria-label="Filter status mahasiswa" class="field text-xs font-semibold w-44">
            <option value="all">Semua Status</option>
            <option value="memenuhi">Memenuhi Target</option>
            <option value="evaluasi">Evaluasi CPMK-02</option>
            <option value="belum">Belum Memenuhi</option>
        </select>
    </div>

    <div class="assessment-toolbar-actions flex items-center gap-2.5 shrink-0">
        <button type="button" data-grade-import-open aria-haspopup="dialog" aria-controls="grade-import" class="button-secondary text-xs">
            <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="m13 2-9 12h7l-1 8 10-12h-7l1-8Z"/></svg>
            Unggah Nilai CSV/Excel
        </button>
        <button type="button" onclick="triggerOBESync('Data nilai UTS diekspor')" class="button-secondary text-xs">
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
                    NILAI UTS
                    <span class="block text-[10px] font-normal text-muted mt-0.5">(Skala 0–100)</span>
                </th>
                <th scope="col" class="text-center min-w-[150px]">
                    CAPAIAN CPMK-01
                    <span class="block text-[10px] font-normal text-muted mt-0.5">(Bobot 60% · ≥50)</span>
                </th>
                <th scope="col" class="text-center min-w-[150px]">
                    CAPAIAN CPMK-02
                    <span class="block text-[10px] font-normal text-muted mt-0.5">(Bobot 40% · ≥80)</span>
                </th>
                <th scope="col" class="text-center min-w-[130px]">RINCIAN SOAL</th>
                <th scope="col" class="min-w-[170px]">WAKTU PENGUMPULAN</th>
                <th scope="col" class="text-center min-w-[110px]">AKSI DOKUMEN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($utsStudents as $stu)
                @php
                    // Hitung nilai asesmen berdasarkan rumus desain (1).md: (CPMK1 * 0.6) + (CPMK2 * 0.4)
                    $calculatedScore = round(($stu['cpmk1'] * 0.6) + ($stu['cpmk2'] * 0.4), 2);
                @endphp
                <tr class="student-row" data-name="{{ strtolower($stu['name']) }}" data-number="{{ $stu['number'] }}" data-status="{{ $stu['status_key'] }}">
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
                    {{-- Nilai UTS: Dihitung otomatis dari Σ (nilai_cpmk * bobot_cpmk) --}}
                    <td class="text-center align-middle">
                        <span class="font-mono text-sm font-extrabold text-brand score-display" id="score-{{ $stu['number'] }}">
                            {{ number_format($calculatedScore, 2, ',', '.') }}
                        </span>
                        <span class="block text-[10px] text-muted">dari 100</span>
                    </td>
                    {{-- Capaian CPMK-01 (Bobot 60%) --}}
                    <td class="text-center align-middle">
                        <input type="number" min="0" max="100" step="0.01"
                               value="{{ $stu['cpmk1'] }}"
                               class="field text-center font-mono text-xs font-bold py-1 px-1.5 w-20 mx-auto block border border-line bg-white shadow-none
                                      {{ $stu['cpmk1'] >= 50 ? 'text-emerald-700' : 'text-danger' }}"
                               data-student="{{ $stu['number'] }}"
                               data-cpmk="cpmk1"
                               data-cpmk-weight="60"
                               data-threshold="50"
                               onchange="recalcScore(this)"
                               aria-label="Capaian CPMK-01 {{ $stu['name'] }}">
                    </td>
                    {{-- Capaian CPMK-02 (Bobot 40%) --}}
                    <td class="text-center align-middle">
                        <input type="number" min="0" max="100" step="0.01"
                               value="{{ $stu['cpmk2'] }}"
                               class="field text-center font-mono text-xs font-bold py-1 px-1.5 w-20 mx-auto block border border-line bg-white shadow-none
                                      {{ $stu['cpmk2'] >= 80 ? 'text-emerald-700' : 'text-danger' }}"
                               data-student="{{ $stu['number'] }}"
                               data-cpmk="cpmk2"
                               data-cpmk-weight="40"
                               data-threshold="80"
                               onchange="recalcScore(this)"
                               aria-label="Capaian CPMK-02 {{ $stu['name'] }}">
                    </td>
                    {{-- Tombol Lihat Rincian Butir Soal Mahasiswa (Section 10 desain 1.md) --}}
                    <td class="text-center align-middle">
                        <button type="button" onclick="openStudentBreakdownModal('{{ $stu['name'] }}', '{{ $stu['number'] }}', {{ $stu['cpmk1'] }}, {{ $stu['cpmk2'] }}, {{ $calculatedScore }})"
                                class="inline-flex items-center gap-1 rounded bg-slate-100 hover:bg-slate-200 px-2 py-1 text-[11px] font-semibold text-ink transition">
                            <span>5 Soal</span>
                        </button>
                    </td>
                    {{-- Waktu Pengumpulan --}}
                    <td class="align-middle whitespace-nowrap">
                        <span class="text-xs text-muted">{{ $stu['submitted_at'] ?? 'Belum tersedia' }}</span>
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
            Menampilkan <strong class="text-ink">{{ count($utsStudents) }}</strong> dari <strong class="text-ink">{{ count($utsStudents) }}</strong> mahasiswa terdaftar
        </p>
    </div>
</div>

<div class="surface assessment-savebar p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3 text-xs text-muted">
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand font-bold" aria-hidden="true">
            ∑
        </span>
        <div>
            <p class="font-bold text-ink">Kalkulasi Otomatis Berbasis CPMK (desain 1.md)</p>
            <p class="text-muted">Nilai asesmen dihitung otomatis dari akumulasi: Nilai CPMK × (Jumlah Soal CPMK / Total Soal).</p>
        </div>
    </div>

    <div class="assessment-save-actions flex items-center gap-3 self-end sm:self-auto">
        <a href="{{ route('dosen.grades') }}" class="button-secondary text-xs">
            Batal
        </a>
        <button type="button" onclick="triggerOBESync('Nilai UTS berhasil disimpan!')" class="button-primary text-xs">
            <svg class="h-4 w-4 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8M14 5v2"/></svg>
            Simpan Nilai UTS
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
                            <td class="py-2 px-3 text-center font-mono">5</td>
                            <td class="py-2 px-3 text-center text-emerald-700 font-medium">Benar</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">5</td>
                            <td class="py-2 px-3 text-center font-mono">100%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">33,33</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">33,33</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 2</td>
                            <td class="py-2 px-3 text-muted">PG Kompleks (Parsial, 3 kunci)</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-brand">CPMK 1</td>
                            <td class="py-2 px-3 text-center font-mono">15</td>
                            <td class="py-2 px-3 text-center text-ink font-medium">2 benar, 0 salah</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">10</td>
                            <td class="py-2 px-3 text-center font-mono">66,67%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">33,33</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">22,22</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 3</td>
                            <td class="py-2 px-3 text-muted">Esai (Uraian)</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-brand">CPMK 1</td>
                            <td class="py-2 px-3 text-center font-mono">20</td>
                            <td class="py-2 px-3 text-center text-blue-700 font-medium">Dinilai Dosen (15)</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">15</td>
                            <td class="py-2 px-3 text-center font-mono">75%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">33,33</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">25,00</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 4</td>
                            <td class="py-2 px-3 text-muted">Benar / Salah</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-amber-800">CPMK 2</td>
                            <td class="py-2 px-3 text-center font-mono">20</td>
                            <td class="py-2 px-3 text-center text-danger font-medium">Salah</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">0</td>
                            <td class="py-2 px-3 text-center font-mono">0%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">50,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">0,00</td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 font-semibold text-ink">Soal 5</td>
                            <td class="py-2 px-3 text-muted">Jodohkan (5 pasangan)</td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-amber-800">CPMK 2</td>
                            <td class="py-2 px-3 text-center font-mono">40</td>
                            <td class="py-2 px-3 text-center text-ink font-medium">4 pasang benar</td>
                            <td class="py-2 px-3 text-center font-mono font-semibold">32</td>
                            <td class="py-2 px-3 text-center font-mono">80%</td>
                            <td class="py-2 px-3 text-center font-mono text-muted">50,00</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-ink">40,00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 pt-2">
                <div class="rounded-xl border border-brand/20 bg-brand-soft/40 p-3">
                    <span class="text-[10px] font-bold text-brand uppercase">Nilai CPMK 1 (Maks 100)</span>
                    <p class="text-base font-extrabold text-brand mt-0.5">80,56</p>
                    <p class="text-[10px] text-muted">33,33 + 22,22 + 25,00 · Bobot 60%</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                    <span class="text-[10px] font-bold text-amber-800 uppercase">Nilai CPMK 2 (Maks 100)</span>
                    <p class="text-base font-extrabold text-amber-900 mt-0.5">40,00</p>
                    <p class="text-[10px] text-muted">0,00 + 40,00 · Bobot 40%</p>
                </div>
                <div class="rounded-xl border border-line/60 bg-white p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-ink uppercase">Nilai Asesmen UTS</span>
                    <p class="text-base font-extrabold text-ink mt-0.5" id="modal-assessment-score">64,33</p>
                    <p class="text-[10px] text-muted">(80,56 × 0,6) + (40 × 0,4)</p>
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

{{-- Script: Hitung ulang Nilai UTS berdasarkan bobot CPMK (desain 1.md) --}}
<script>
function recalcScore(inputEl) {
    const studentId = inputEl.dataset.student;
    const row = inputEl.closest('tr');
    const cpmkInputs = row.querySelectorAll('input[data-cpmk]');
    let assessmentTotal = 0;

    cpmkInputs.forEach(inp => {
        const val = parseFloat(inp.value) || 0;
        const weight = (parseFloat(inp.dataset.cpmkWeight) || 50) / 100;
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

function openStudentBreakdownModal(name, nim, cpmk1, cpmk2, score) {
    const modal = document.getElementById('breakdown-modal');
    if (!modal) return;
    document.getElementById('modal-student-name').textContent = name;
    document.getElementById('modal-student-nim').textContent = 'NIM: ' + nim;
    document.getElementById('modal-assessment-score').textContent = typeof score === 'number' ? score.toFixed(2).replace('.', ',') : score;
    modal.removeAttribute('hidden');
}
</script>
