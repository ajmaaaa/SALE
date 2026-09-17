<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ $section->mataKuliah->code }} ({{ $section->section_code }})</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #111827;
            background: #fff;
            padding: 24px;
            font-size: 12px;
            line-height: 1.5;
        }
        .header-kop {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-kop h1 { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .header-kop h2 { font-size: 14px; font-weight: 600; color: #374151; }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .meta-row { display: flex; }
        .meta-label { width: 120px; color: #4b5563; font-weight: 500; }
        .meta-value { font-weight: 600; color: #111827; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: #1f2937;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .footer-sig {
            display: flex;
            justify-content: space-between;
            margin-top: 32px;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 200px;
            text-align: center;
        }
        .sig-line {
            margin-top: 60px;
            border-bottom: 1px solid #111827;
            font-weight: 600;
        }
        .no-print-bar {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }
        .btn-primary { background: #0284c7; color: #fff; border: none; }
        .btn-secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        @media print {
            .no-print-bar { display: none !important; }
            body { padding: 0; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <span>Pratinjau Dokumen Cetak / PDF Laporan OBE</span>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF</button>
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>

    <div class="header-kop">
        <h1>SISTEM AKADEMIK & OBE (SALE)</h1>
        <h2>{{ $title }}</h2>
    </div>

    <div class="meta-grid">
        <div class="meta-row">
            <span class="meta-label">Mata Kuliah:</span>
            <span class="meta-value">{{ $section->mataKuliah->code }} — {{ $section->mataKuliah->name }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Kelas / Semester:</span>
            <span class="meta-value">{{ $section->section_code }} / {{ $section->semester->name ?? 'Aktif' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Dosen Pengampu:</span>
            <span class="meta-value">{{ $section->dosen->name ?? '—' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Jumlah Mahasiswa:</span>
            <span class="meta-value">{{ $rows->count() }} Orang</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th style="width: 110px;">NIM</th>
                <th>Nama Mahasiswa</th>
                @foreach($cpls as $cpl)
                    <th class="text-center" style="width: 65px;">{{ $cpl->code }}</th>
                @endforeach
                <th class="text-center" style="width: 75px;">Nilai Akhir</th>
                <th class="text-center" style="width: 50px;">Grade</th>
                <th class="text-center" style="width: 85px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr>
                    <td class="text-center font-mono">{{ $i + 1 }}</td>
                    <td class="font-mono">{{ $row['student']->nim_nidn ?? '—' }}</td>
                    <td>{{ $row['student']->name }}</td>
                    @foreach($cpls as $cpl)
                        <td class="text-center font-mono">
                            {{ $row['cpl_scores'][$cpl->id] !== null ? number_format($row['cpl_scores'][$cpl->id], 1) : '—' }}
                        </td>
                    @endforeach
                    <td class="text-center font-mono" style="font-weight: 600;">
                        {{ $row['final_score'] !== null ? number_format($row['final_score'], 1) : '—' }}
                    </td>
                    <td class="text-center font-mono" style="font-weight: 600;">
                        {{ $row['grade'] ?? '—' }}
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
                    <td colspan="{{ 6 + $cpls->count() }}" class="text-center" style="padding: 24px;">Belum ada data mahasiswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-sig">
        <div class="sig-box">
            <p>Mengetahui,</p>
            <p>Ketua Program Studi</p>
            <div class="sig-line"></div>
            <p style="font-size: 10px; color: #4b5563; margin-top: 4px;">NIP / NIDN</p>
        </div>
        <div class="sig-box">
            <p>Kota Batam, {{ date('d F Y') }}</p>
            <p>Dosen Pengampu Kelas</p>
            <div class="sig-line">{{ $section->dosen->name ?? 'Dosen Pengampu' }}</div>
            <p style="font-size: 10px; color: #4b5563; margin-top: 4px;">NIP / NIDN: {{ $section->dosen->nim_nidn ?? '—' }}</p>
        </div>
    </div>
</body>
</html>
