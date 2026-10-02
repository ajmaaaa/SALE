<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}: {{ $section->mataKuliah->code }} ({{ $section->section_code }}) - {{ $section->mataKuliah->name }} | UMRAH</title>
    @php
        $logoBase64 = \App\Models\SystemSetting::logoBase64();

        $prodi = $section->mataKuliah->prodi;
        $kaprodiMap = [
            'IF' => ['name' => 'Dr. Eng. Ahmad Zaki, M.Kom.', 'nip' => '198203152008121002'],
            'SI' => ['name' => 'Maya Kartika, S.Kom., M.T.', 'nip' => '198506222010122003'],
            'SK' => ['name' => 'Ir. Hendra Pratama, M.T.', 'nip' => '197911042005011001'],
        ];

        $dosenKaprodi = \App\Models\User::where('prodi_id', $prodi?->id)
            ->whereHas('role', fn ($q) => $q->where('name', \App\Models\Role::DOSEN))
            ->first();

        $kaprodiDefault = $kaprodiMap[$prodi?->code] ?? null;
        $kaprodiName = $kaprodiDefault['name'] ?? ($dosenKaprodi?->name ?? 'Dr. H. Kaprodi, M.T.');
        $kaprodiNip  = $kaprodiDefault['nip'] ?? ($dosenKaprodi?->nim_nidn ?? '197501012000031001');

        $dosenName = $section->dosen?->name ?? 'Dosen Pengampu';
        $dosenNip  = $section->dosen?->nim_nidn ?? '-';
    @endphp
    <style>
        /* ── Dimensi & Pengaturan Kertas Formal ── */
        :root {
            --paper-width: 297mm;
            --paper-min-height: 210mm;
            --paper-padding: 15mm 15mm 20mm 20mm; /* Atas, Kanan, Bawah, Kiri */
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            background: #d1d5db;
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            line-height: 1.35;
            color: #000;
        }

        /* ── Action bar (Hanya Tampil di Browser) ── */
        #action-bar {
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
        }
        #action-bar label {
            font-size: 12px;
            color: #374151;
            font-weight: 600;
        }
        #action-bar select {
            font-size: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 10px;
            color: #111827;
            background: #f9fafb;
            cursor: pointer;
        }
        #action-bar .btn {
            font-size: 12px;
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
            font-weight: 500;
            transition: all 0.15s ease;
        }
        #action-bar .btn:hover {
            background: #f3f4f6;
            color: #111827;
        }
        #action-bar .btn-primary {
            background: #111827;
            color: #ffffff;
            border-color: #111827;
        }
        #action-bar .btn-primary:hover {
            background: #374151;
            color: #ffffff;
        }

        /* ── Area Kertas ── */
        #paper-wrap {
            display: flex;
            justify-content: center;
            padding: 24px 0 48px;
        }
        #paper {
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: var(--paper-width);
            min-height: var(--paper-min-height);
            padding: var(--paper-padding);
            box-sizing: border-box;
            position: relative;
        }

        /* ── Kop Surat Resmi (Sesuai Standar Tata Naskah Dinas UMRAH) ── */
        .kop-container {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 2px;
        }
        .kop-logo-cell {
            display: table-cell;
            width: 85px;
            vertical-align: middle;
            text-align: left;
        }
        .kop-logo-cell img {
            width: 82px;
            height: auto;
            max-height: 95px;
            display: block;
        }
        .kop-spacer-cell {
            display: table-cell;
            width: 85px;
            vertical-align: middle;
        }
        .kop-text-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .kop-instansi-1 {
            font-size: 13pt;
            font-weight: normal;
            letter-spacing: 0.04em;
            line-height: 1.15;
            text-transform: uppercase;
        }
        .kop-instansi-2 {
            font-size: 13pt;
            font-weight: normal;
            letter-spacing: 0.04em;
            line-height: 1.15;
            text-transform: uppercase;
        }
        .kop-univ {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.05em;
            line-height: 1.25;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .kop-alamat {
            font-size: 9pt;
            line-height: 1.2;
            margin-top: 1px;
        }
        .kop-kontak, .kop-web {
            font-size: 8.5pt;
            line-height: 1.2;
        }

        /* ── Garis Pembatas Kop Surat Ganda ── */
        .kop-divider {
            border-top: 2.5px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin: 6px 0 16px 0;
            clear: both;
        }

        /* ── Judul & Nomor Dokumen ── */
        .doc-title-block {
            text-align: center;
            margin-bottom: 16px;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 0 0 3px;
        }
        .doc-nomor {
            font-size: 10pt;
            margin: 0;
        }

        /* ── Tabel Metadata Dokumen ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 9.5pt;
        }
        .meta-table td {
            padding: 2.5px 2px;
            vertical-align: top;
            border: none;
        }
        .meta-label {
            width: 16%;
            font-weight: normal;
        }
        .meta-sep {
            width: 2%;
            text-align: center;
        }
        .meta-val {
            width: 32%;
            font-weight: 600;
        }

        /* ── Tabel Formal Hitam Putih (Polos Tanpa Warna) ── */
        .formal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 16px;
        }
        .formal-table th,
        .formal-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            color: #000;
        }
        .formal-table thead th {
            background-color: transparent;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            padding: 5px 4px;
        }
        .formal-table tbody td.text-center {
            text-align: center;
        }
        .formal-table tbody td.text-right {
            text-align: right;
        }
        .formal-table tbody td.font-mono {
            font-family: "Courier New", Courier, monospace;
            font-size: 8.5pt;
        }
        .formal-table .table-total-row td {
            background-color: transparent;
            font-weight: bold;
        }

        /* ── Ruang Tanda Tangan Formal ── */
        .signatures-container {
            margin-top: 28px;
            display: table;
            width: 100%;
            table-layout: fixed;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }
        .sig-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .sig-left {
            text-align: left;
        }
        .sig-right {
            text-align: left;
            padding-left: 20%;
        }
        .sig-heading {
            margin: 0;
            line-height: 1.3;
        }
        .sig-role {
            margin: 0;
            line-height: 1.3;
        }
        .sig-space {
            height: 60px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            line-height: 1.3;
        }
        .sig-id {
            margin: 2px 0 0;
            font-size: 9pt;
            line-height: 1.3;
        }

        /* ── Catatan Kaki BSrE BSSN ── */
        .doc-footer-bsre {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 0.5px solid #666;
            font-size: 7.5pt;
            font-style: italic;
            color: #333;
            text-align: center;
            line-height: 1.3;
            page-break-inside: avoid;
        }

        /* ── Pengaturan Cetak / Print Media ── */
        @media print {
            #action-bar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            #paper-wrap {
                padding: 0 !important;
                display: block !important;
            }
            #paper {
                box-shadow: none !important;
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
            }
            @page {
                size: var(--paper-width) var(--paper-min-height);
                margin: var(--paper-padding);
            }
            .formal-table th,
            .formal-table thead th,
            .formal-table .table-total-row td {
                background-color: transparent !important;
            }
            .signatures-container,
            .doc-footer-bsre {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- ── Action Bar ── -->
    <div id="action-bar">
        <div class="left-group">
            <a href="{{ route('dosen.penilaian.show', $section->id) }}" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Penilaian
            </a>
            <label for="paperSize">Format Kertas:</label>
            <select id="paperSize" onchange="changePaperSize(this.value)">
                <option value="a4_land" selected>A4 Landscape (297 × 210 mm)</option>
                <option value="f4_land">F4 / Folio Landscape (330 × 215 mm)</option>
                <option value="letter_land">Letter Landscape (279 × 216 mm)</option>
            </select>
        </div>
        <div class="right-group">
            <a href="{{ route('dosen.penilaian.export.keseluruhan.excel', $section->id) }}" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                Export Excel
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>

    <!-- ── Area Kertas Dokumen Formal ── -->
    <div id="paper-wrap">
        <div id="paper">

            <!-- 1. Kop Surat Resmi -->
            <div class="kop-container">
                <div class="kop-logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo Institusi">
                    @else
                        <img src="{{ asset('images/logo-umrah.png') }}" alt="Logo Institusi">
                    @endif
                </div>
                <div class="kop-text-cell">
                    @php
                        $kopMinistry = \App\Models\SystemSetting::valueFor('institution_ministry', 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI');
                        $kopUniv = \App\Models\SystemSetting::valueFor('institution', 'UNIVERSITAS MARITIM RAJA ALI HAJI');
                        $kopAddress = \App\Models\SystemSetting::valueFor('institution_address', 'Jalan Sultan Mansyur Syah, Dompak, Tanjungpinang 29124');
                        $kopPhone = \App\Models\SystemSetting::valueFor('institution_phone', 'Telepon (0771) 4500089, Faksimile (0771) 4500090, SLI (0771) 4500091, Kotak Pos 155');
                        $kopWeb = \App\Models\SystemSetting::valueFor('institution_website', 'http://umrah.ac.id');
                        $kopEmail = \App\Models\SystemSetting::valueFor('institution_email', 'email@umrah.ac.id');
                    @endphp
                    @if($kopMinistry)
                        <div class="kop-instansi-1">{{ mb_strtoupper($kopMinistry) }}</div>
                    @endif
                    <div class="kop-univ">{{ mb_strtoupper($kopUniv) }}</div>
                    @if($kopAddress)
                        <div class="kop-alamat">{{ $kopAddress }}</div>
                    @endif
                    @if($kopPhone)
                        <div class="kop-kontak">{{ $kopPhone }}</div>
                    @endif
                    @if($kopWeb || $kopEmail)
                        <div class="kop-web">{{ $kopWeb ? 'Laman '.$kopWeb : '' }}{{ ($kopWeb && $kopEmail) ? ', ' : '' }}{{ $kopEmail ? 'Posel '.$kopEmail : '' }}</div>
                    @endif
                </div>
                <div class="kop-spacer-cell"></div>
            </div>

            <!-- Garis Ganda Pembatas Kop Surat -->
            <div class="kop-divider"></div>

            <!-- 2. Judul & Nomor Dokumen -->
            <div class="doc-title-block">
                <div class="doc-title">LAPORAN REKAPITULASI PENILAIAN &amp; CAPAIAN PEMBELAJARAN (OBE)</div>
                <div class="doc-nomor">Nomor : 002/UN53.1/{{ $prodi?->code ?? 'PRODI' }}/AK.04.01/{{ now()->year }}</div>
            </div>

            <!-- 3. Tabel Metadata Dokumen -->
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Mata Kuliah</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $section->mataKuliah->code }} - {{ $section->mataKuliah->name }} ({{ $section->mataKuliah->sks }} SKS)</td>
                    <td class="meta-label">Program Studi</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $prodi?->name ?? 'Semua' }} ({{ $prodi?->code ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="meta-label">Kelas / Seksi</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $section->section_code }}</td>
                    <td class="meta-label">Semester / TA</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $section->semester->name ?? 'Aktif' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Dosen Pengampu</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $dosenName }}</td>
                    <td class="meta-label">Jumlah Mahasiswa</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $rows->count() }} Orang Terdaftar</td>
                </tr>
            </table>

            <!-- 4. Tabel Rincian Rekapitulasi Nilai & Capaian CPL Mahasiswa -->
            <table class="formal-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 13%;">NIM</th>
                        <th style="width: 28%;">Nama Mahasiswa</th>
                        @foreach($cpls as $cpl)
                            <th class="text-center" style="width: {{ count($cpls) > 0 ? round(27 / count($cpls), 1) : 6 }}%;">{{ $cpl->code }}</th>
                        @endforeach
                        <th class="text-center" style="width: 10%;">Nilai Akhir</th>
                        <th class="text-center" style="width: 7%;">Grade</th>
                        <th class="text-center" style="width: 11%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-center font-mono">{{ $row['student']->nim_nidn ?? '' }}</td>
                            <td>{{ $row['student']->name }}</td>
                            @foreach($cpls as $cpl)
                                <td class="text-center font-mono">
                                    {{ $row['cpl_scores'][$cpl->id] !== null ? number_format($row['cpl_scores'][$cpl->id], 1) : '-' }}
                                </td>
                            @endforeach
                            <td class="text-center font-mono" style="font-weight: bold;">
                                {{ $row['final_score'] !== null ? number_format($row['final_score'], 1) : '-' }}
                            </td>
                            <td class="text-center font-mono" style="font-weight: bold;">
                                {{ $row['grade'] ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if($row['coverage'] < 100)
                                    Provisional
                                @elseif($row['final_score'] !== null)
                                    Final
                                @else
                                    Belum Dinilai
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + count($cpls) }}" class="text-center" style="padding: 16px;">Belum ada data mahasiswa pada kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($rows) > 0)
                <tfoot>
                    <tr class="table-total-row">
                        <td colspan="{{ 3 + count($cpls) }}" style="text-align: left; padding-left: 8px;">RATA-RATA KELAS</td>
                        <td class="text-center font-mono" style="font-weight: bold;">
                            {{ $classAverage !== null ? number_format($classAverage, 2) : '-' }}
                        </td>
                        <td colspan="2" class="text-center">-</td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <!-- 5. Ruang Tanda Tangan Formal -->
            <div class="signatures-container">
                <div class="sig-col sig-left">
                    <div class="sig-block">
                        <p class="sig-heading">Mengetahui,</p>
                        <p class="sig-role">Ketua Program Studi {{ $prodi?->name }}</p>
                        <div class="sig-space"></div>
                        <p class="sig-name">{{ $kaprodiName }}</p>
                        <p class="sig-id">NIP. {{ $kaprodiNip }}</p>
                    </div>
                </div>
                <div class="sig-col sig-right">
                    <div class="sig-block">
                        <p class="sig-heading">Tanjungpinang, {{ now()->translatedFormat('d F Y') }}</p>
                        <p class="sig-role">Dosen Pengampu Mata Kuliah</p>
                        <div class="sig-space"></div>
                        <p class="sig-name">{{ $dosenName }}</p>
                        <p class="sig-id">NIP/NIDN. {{ $dosenNip }}</p>
                    </div>
                </div>
            </div>

            <!-- 6. Catatan Kaki Elektronik (BSrE BSSN) -->
            <div class="doc-footer-bsre">
                <p>Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara (BSSN).</p>
            </div>

        </div><!-- #paper -->
    </div><!-- #paper-wrap -->

    <script>
        const paperSizes = {
            a4_land:     { width: '297mm', height: '210mm', padding: '15mm 15mm 20mm 20mm' },
            f4_land:     { width: '330mm', height: '215mm', padding: '15mm 15mm 20mm 20mm' },
            letter_land: { width: '279mm', height: '216mm', padding: '15mm 15mm 20mm 20mm' },
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
