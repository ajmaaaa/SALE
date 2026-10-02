<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akademik &amp; Capaian Prodi {{ $activeProdi?->code }} - {{ $activeSemester?->name }} | UMRAH</title>
    @php
        $logoPath = public_path('images/logo-umrah.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    @endphp
    <style>
        /* ── Dimensi & Pengaturan Kertas ── */
        :root {
            --paper-width: 210mm;
            --paper-min-height: 297mm;
            --paper-padding: 20mm 20mm 25mm 25mm; /* Atas, Kanan, Bawah, Kiri */
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
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
        }

        /* ── Action bar (Layar Saja, Disembunyikan saat Print/PDF) ── */
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

        /* ── Area Kertas (Preview Dokumen Formal) ── */
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

        /* ── Kop Surat Resmi (Sesuai tamplate.docx) ── */
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
        .kop-kontak {
            font-size: 8.5pt;
            line-height: 1.2;
        }
        .kop-web {
            font-size: 8.5pt;
            line-height: 1.2;
        }

        /* ── Garis Pembatas Kop Surat Ganda (Standar Tata Naskah Dinas) ── */
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
            margin-bottom: 18px;
            font-size: 10pt;
        }
        .meta-table td {
            padding: 3px 2px;
            vertical-align: top;
            border: none;
        }
        .meta-label {
            width: 20%;
            font-weight: normal;
        }
        .meta-sep {
            width: 2%;
            text-align: center;
        }
        .meta-val {
            width: 28%;
            font-weight: 600;
        }

        /* ── Judul Bagian / Sub-heading ── */
        .section-heading {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin: 16px 0 6px;
            padding-bottom: 0;
            border-bottom: none;
        }

        /* ── Tabel Formal Hitam Putih ── */
        .formal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
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
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            padding: 5px 6px;
        }
        .formal-table tbody td.text-center {
            text-align: center;
        }
        .formal-table tbody td.text-right {
            text-align: right;
        }
        .formal-table tbody td.font-mono {
            font-family: "Courier New", Courier, monospace;
            font-size: 9pt;
        }
        .formal-table .table-total-row td {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .formal-table .val {
            font-weight: bold;
        }

        /* ── Ruang Tanda Tangan Formal (Kiri: Kaprodi, Kanan: Admin Prodi) ── */
        .signatures-container {
            margin-top: 32px;
            display: table;
            width: 100%;
            table-layout: fixed;
            font-size: 10pt;
            page-break-inside: avoid;
        }
        .sig-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .sig-col.sig-left {
            text-align: left;
        }
        .sig-col.sig-right {
            text-align: right;
        }
        .sig-block {
            display: inline-block;
            text-align: left;
        }
        .sig-heading {
            margin: 0 0 2px;
        }
        .sig-role {
            margin: 0;
            font-weight: bold;
        }
        .sig-space {
            height: 60px;
        }
        .sig-name {
            margin: 0;
            font-weight: bold;
            text-decoration: underline;
        }
        .sig-id {
            margin: 2px 0 0;
            font-size: 9pt;
        }

        /* ── Catatan Kaki Elektronik (BSrE BSSN) Sesuai tamplate.docx ── */
        .doc-footer-bsre {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px dashed #777;
            font-size: 8pt;
            font-style: italic;
            color: #333;
            text-align: center;
            line-height: 1.35;
            page-break-inside: avoid;
        }
        .doc-footer-bsre p {
            margin: 0;
        }

        /* ── Media Print (Cetak Dokumen & Simpan PDF) ── */
        @media print {
            #action-bar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
            #paper-wrap {
                padding: 0 !important;
                background: #ffffff !important;
            }
            #paper {
                box-shadow: none !important;
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            @page {
                size: var(--paper-width) var(--paper-min-height);
                margin: var(--paper-padding);
            }
            .formal-table th {
                background-color: #f2f2f2 !important;
            }
            .signatures-container,
            .doc-footer-bsre {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- ── Action Bar (Hanya tampil di layar browser) ── -->
    <div id="action-bar">
        <div class="left-group">
            <a href="{{ route('admin-prodi.laporan.index', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
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

            <!-- 1. Kop Surat Resmi (Sesuai tamplate.docx) -->
            <div class="kop-container">
                <div class="kop-logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo UMRAH">
                    @else
                        <img src="{{ asset('images/logo-umrah.png') }}" alt="Logo UMRAH">
                    @endif
                </div>
                <div class="kop-text-cell">
                    <div class="kop-instansi-1">KEMENTERIAN PENDIDIKAN TINGGI,</div>
                    <div class="kop-instansi-2">SAINS, DAN TEKNOLOGI</div>
                    <div class="kop-univ">UNIVERSITAS MARITIM RAJA ALI HAJI</div>
                    <div class="kop-alamat">Jalan Sultan Mansyur Syah, Dompak, Tanjungpinang 29124</div>
                    <div class="kop-kontak">Telepon (0771) 4500089, Faksimile (0771) 4500090, SLI (0771) 4500091, Kotak Pos 155</div>
                    <div class="kop-web">Laman http://umrah.ac.id, Posel email@umrah.ac.id</div>
                </div>
                <div class="kop-spacer-cell"></div>
            </div>

            <!-- Garis Ganda Pembatas Kop Surat -->
            <div class="kop-divider"></div>

            <!-- 2. Judul & Nomor Dokumen -->
            <div class="doc-title-block">
                <div class="doc-title">LAPORAN AKADEMIK &amp; KELAS PERKULIAHAN PROGRAM STUDI</div>
                <div class="doc-nomor">Nomor : 001/UN53.1/{{ $activeProdi?->code ?? 'PRODI' }}/AK.04.00/{{ $activeSemester?->academic_year_start ?? now()->year }}</div>
            </div>

            <!-- 3. Tabel Metadata Dokumen -->
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Program Studi</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $activeProdi?->name }} ({{ $activeProdi?->code }})</td>
                    <td class="meta-label">Semester / TA</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $activeSemester?->name }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Fakultas / Unit Kerja</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">Fakultas Teknik dan Teknologi Kemaritiman</td>
                    <td class="meta-label">Tanggal Dokumen</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ now()->translatedFormat('d F Y') }}</td>
                </tr>
            </table>

            <!-- 4. Bagian I: Ringkasan Metrik Semester -->
            <div class="section-heading">I. RINGKASAN METRIK SEMESTER</div>
            <table class="formal-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">No</th>
                        <th style="width: 54%;">Indikator Akademik</th>
                        <th style="width: 40%;">Nilai / Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Total Dosen Pengampu</td>
                        <td class="text-center"><span class="val">{{ $metrics['total_dosen'] }}</span> Orang</td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Total Mahasiswa Terdaftar (Aktif)</td>
                        <td class="text-center"><span class="val">{{ $metrics['total_mahasiswa'] }}</span> Orang</td>
                    </tr>
                    <tr>
                        <td class="text-center">3</td>
                        <td>Total Kelas Perkuliahan Aktif</td>
                        <td class="text-center"><span class="val">{{ $metrics['total_kelas'] }}</span> Kelas</td>
                    </tr>
                    <tr>
                        <td class="text-center">4</td>
                        <td>Rata-rata Nilai Mahasiswa (Skala 0-100)</td>
                        <td class="text-center"><span class="val">{{ $metrics['average_grade'] !== null ? number_format($metrics['average_grade'], 2) : '0.00' }}</span></td>
                    </tr>
                </tbody>
            </table>

            <!-- 5. Bagian II: Rincian Kelas Perkuliahan & Capaian Nilai -->
            <div class="section-heading">II. RINCIAN KELAS PERKULIAHAN &amp; CAPAIAN NILAI</div>
            <table class="formal-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 10%;">Kode MK</th>
                        <th style="width: 28%;">Mata Kuliah (SKS)</th>
                        <th style="width: 7%;">Kelas</th>
                        <th style="width: 21%;">Dosen Ketua</th>
                        <th style="width: 16%;">Dosen Anggota</th>
                        <th style="width: 6%;">Mhs</th>
                        <th style="width: 8%;">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classReports as $idx => $cr)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center font-mono">{{ $cr['mk_code'] }}</td>
                        <td>{{ $cr['mk_name'] }} ({{ $cr['sks'] }} SKS{{ !empty($cr['semester_paket']) ? ' - Sem. ' . $cr['semester_paket'] : '' }})</td>
                        <td class="text-center font-mono">{{ $cr['section_code'] }}</td>
                        <td>{{ $cr['dosen_ketua'] }}</td>
                        @php
                            $hasAnggota = !empty($cr['dosen_wakil']) && $cr['dosen_wakil'] !== '-';
                        @endphp
                        <td class="{{ $hasAnggota ? '' : 'text-center' }}">{{ $hasAnggota ? $cr['dosen_wakil'] : '-' }}</td>
                        <td class="text-center">{{ $cr['students_count'] }}</td>
                        <td class="text-center">{{ $cr['class_average'] !== null ? number_format($cr['class_average'], 2) : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada kelas perkuliahan pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($classReports) > 0)
                <tfoot>
                    <tr class="table-total-row">
                        <td colspan="7" style="text-align: left; padding-left: 8px;">RATA-RATA NILAI MAHASISWA</td>
                        <td class="text-center">{{ $metrics['average_grade'] !== null ? number_format($metrics['average_grade'], 2) : '-' }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>

            <!-- 6. Ruang Tanda Tangan (Format tamplate.docx) -->
            <div class="signatures-container">
                <div class="sig-col sig-left">
                    <div class="sig-block">
                        <p class="sig-heading">Mengetahui,</p>
                        <p class="sig-role">Ketua Program Studi {{ $activeProdi?->name }}</p>
                        <div class="sig-space"></div>
                        <p class="sig-name">{{ $kaprodiName }}</p>
                        <p class="sig-id">NIP. {{ $kaprodiNip }}</p>
                    </div>
                </div>
                <div class="sig-col sig-right">
                    <div class="sig-block">
                        <p class="sig-heading">Tanjungpinang, {{ now()->translatedFormat('d F Y') }}</p>
                        <p class="sig-role">Admin Program Studi {{ $activeProdi?->code }}</p>
                        <div class="sig-space"></div>
                        <p class="sig-name">{{ $adminProdiName }}</p>
                        <p class="sig-id">NIP/ID. {{ $adminProdiNip }}</p>
                    </div>
                </div>
            </div>

            <!-- 7. Catatan Kaki Elektronik (BSrE BSSN) Sesuai tamplate.docx -->
            <div class="doc-footer-bsre">
                <p>Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara (BSSN).</p>
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
