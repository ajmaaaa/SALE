<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akademik Prodi {{ $activeProdi?->code }} - {{ $activeSemester?->name }} | SALE</title>
    @vite(['resources/css/app.css'])
    <style>
        /* ── Variabel ukuran kertas ── */
        :root {
            --paper-width: 210mm;
            --paper-min-height: 297mm;
            --paper-padding: 20mm 20mm 25mm 25mm; /* top right bottom left */
        }
        body {
            background: #e5e7eb;
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
        }

        /* ── Action bar (di luar area kertas) ── */
        #action-bar {
            background: #fff;
            border-bottom: 1px solid #d1d5db;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 50;
            flex-wrap: wrap;
        }
        #action-bar .left-group,
        #action-bar .right-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media (max-width: 640px) {
            #action-bar {
                padding: 10px 14px;
            }
            #action-bar .left-group,
            #action-bar .right-group {
                width: 100%;
                justify-content: space-between;
            }
            #action-bar .btn,
            #action-bar select {
                font-size: 11px;
                padding: 5px 10px;
            }
        }
        #action-bar label {
            font-size: 12px;
            font-family: ui-sans-serif, system-ui, sans-serif;
            color: #374151;
            font-weight: 600;
        }
        #action-bar select {
            font-size: 12px;
            font-family: ui-sans-serif, system-ui, sans-serif;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 5px 10px;
            color: #111827;
            background: #f9fafb;
            cursor: pointer;
        }
        #action-bar .btn {
            font-size: 12px;
            font-family: ui-sans-serif, system-ui, sans-serif;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #374151;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        #action-bar .btn-primary {
            background: #1e3a5f;
            color: #fff;
            border-color: #1e3a5f;
        }
        #action-bar .btn:hover { opacity: 0.85; }

        /* ── Area kertas ── */
        #paper-wrap {
            display: flex;
            justify-content: center;
            padding: 32px 0 64px;
        }
        #paper {
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
            width: var(--paper-width);
            min-height: var(--paper-min-height);
            padding: var(--paper-padding);
            box-sizing: border-box;
        }

        /* ── Konten laporan ── */
        .doc-header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px solid #000;
            margin-bottom: 16px;
        }
        .doc-header h1 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 4px;
        }
        .doc-header h2 {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 6px;
        }
        .doc-header p {
            font-size: 10pt;
            margin: 2px 0;
            color: #000;
        }

        /* ── Summary table (gantikan cards) ── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #000;
            padding: 5px 8px;
        }
        .summary-table thead th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 9pt;
            letter-spacing: 0.03em;
        }
        .summary-table tbody td {
            text-align: center;
        }
        .summary-table tbody td.label {
            text-align: left;
            font-weight: bold;
        }
        .summary-table .val {
            font-size: 14pt;
            font-weight: bold;
        }

        /* ── Tabel kelas ── */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            margin-top: 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: left;
            vertical-align: middle;
        }
        .data-table thead th {
            background: #e5e7eb;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
        }
        .data-table tbody td.center {
            text-align: center;
        }
        .data-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        /* ── Tanda tangan ── */
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
        }
        .sig-block { }
        .sig-block .sig-line {
            margin-top: 4px;
            font-weight: bold;
        }
        .sig-space { height: 56px; }
        .sig-name { font-weight: bold; text-decoration: underline; }
        .sig-nip { font-size: 9pt; color: #374151; }

        /* ── Print media ── */
        @media print {
            #action-bar { display: none !important; }
            #paper-wrap { padding: 0; background: #fff; }
            #paper {
                box-shadow: none;
                width: var(--paper-width);
                min-height: var(--paper-min-height);
                padding: var(--paper-padding);
            }
            body { background: #fff; }
            @page {
                size: var(--paper-width) var(--paper-min-height);
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <!-- ── Action Bar (di luar area kertas, hidden saat print) ── -->
    <div id="action-bar">
        <div class="left-group">
            <a href="{{ route('admin-prodi.laporan.index', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="btn">
                Kembali ke Sistem
            </a>
            <label for="paperSize">Format Kertas:</label>
            <select id="paperSize" onchange="changePaperSize(this.value)">
                <option value="a4" selected>A4 (210 × 297 mm)</option>
                <option value="f4">F4 / Folio (215 × 330 mm)</option>
                <option value="letter">Letter (216 × 279 mm)</option>
            </select>
        </div>
        <div class="right-group">
            <a href="{{ route('admin-prodi.laporan.export', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                Ekspor Excel
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                Cetak Dokumen (Print)
            </button>
        </div>
    </div>

    <!-- ── Area Kertas ── -->
    <div id="paper-wrap">
        <div id="paper">

            <!-- Kop Laporan -->
            <div class="doc-header">
                <h1>{{ \App\Models\SystemSetting::valueFor('institution', 'SMART ACADEMIC LEARNING ECOSYSTEM (SALE)') }}</h1>
                <h2>LAPORAN AKADEMIK &amp; KELAS PERKULIAHAN PROGRAM STUDI</h2>
                <p>Program Studi: <strong>{{ $activeProdi?->name }} ({{ $activeProdi?->code }})</strong> &nbsp;|&nbsp; Semester: <strong>{{ $activeSemester?->name }}</strong></p>
                <p style="font-size:9pt;color:#555;">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i:s') }}</p>
            </div>

            <!-- Ringkasan Metrik (tabel, bukan card web) -->
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Mahasiswa Aktif</th>
                        <th>Dosen Pengampu</th>
                        <th>Rata-rata Nilai</th>
                        <th>Total Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="val">{{ $metrics['total_mahasiswa'] }}</span></td>
                        <td><span class="val">{{ $metrics['total_dosen'] }}</span></td>
                        <td><span class="val">{{ $metrics['average_grade'] !== null ? number_format($metrics['average_grade'], 2) : '0.00' }}</span></td>
                        <td><span class="val">{{ $metrics['total_kelas'] }}</span></td>
                    </tr>
                </tbody>
            </table>

            <!-- Daftar Kelas Perkuliahan -->
            <p class="section-title">Daftar Kelas Perkuliahan &amp; Capaian Nilai</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:5%">No</th>
                        <th style="width:10%">Kode &amp; Seksi</th>
                        <th style="width:22%">Mata Kuliah (SKS)</th>
                        <th style="width:22%">Dosen Ketua</th>
                        <th style="width:20%">Dosen Anggota</th>
                        <th style="width:9%">Mahasiswa</th>
                        <th style="width:12%">Rata-rata Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classReports as $idx => $cr)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td class="center" style="font-family:monospace;font-weight:bold;">{{ $cr['mk_code'] }}-{{ $cr['section_code'] }}</td>
                        <td>{{ $cr['mk_name'] }} ({{ $cr['sks'] }} SKS{{ !empty($cr['semester_paket']) ? ' - Sem. ' . $cr['semester_paket'] : '' }})</td>
                        <td>{{ $cr['dosen_ketua'] }}</td>
                        <td>{{ $cr['dosen_wakil'] !== '-' ? $cr['dosen_wakil'] : '' }}</td>
                        <td class="center">{{ $cr['students_count'] }}</td>
                        <td class="center">{{ $cr['class_average'] !== null ? number_format($cr['class_average'], 2) : 'Belum dinilai' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="center">Tidak ada kelas perkuliahan pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Tanda Tangan -->
            <div class="signatures">
                <div class="sig-block">
                    <p style="margin:0;">Mengetahui,</p>
                    <p class="sig-line">Ketua Program Studi {{ $activeProdi?->name }}</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">Dr. H. Kaprodi, M.T.</p>
                    <p class="sig-nip">NIP. 197501012000031001</p>
                </div>
                <div class="sig-block" style="text-align:right;">
                    <p style="margin:0;">Batam, {{ now()->translatedFormat('d F Y') }}</p>
                    <p class="sig-line">Admin Program Studi</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">Admin Prodi {{ $activeProdi?->code }}</p>
                    <p class="sig-nip">NIP/ID. AP001</p>
                </div>
            </div>

        </div><!-- #paper -->
    </div><!-- #paper-wrap -->

    <script>
        const paperSizes = {
            a4:     { width: '210mm', height: '297mm', padding: '20mm 20mm 25mm 25mm' },
            f4:     { width: '215mm', height: '330mm', padding: '20mm 20mm 25mm 25mm' },
            letter: { width: '216mm', height: '279mm', padding: '20mm 20mm 25mm 20mm' },
        };

        function changePaperSize(val) {
            const s = paperSizes[val];
            if (!s) return;
            const root = document.documentElement;
            root.style.setProperty('--paper-width', s.width);
            root.style.setProperty('--paper-min-height', s.height);
            root.style.setProperty('--paper-padding', s.padding);
        }
    </script>
</body>
</html>
