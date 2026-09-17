<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akademik Prodi {{ $activeProdi?->code }} - {{ $activeSemester?->name }} | SALE</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 12px; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 p-6 sm:p-10 antialiased font-sans">
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-xl shadow-xs border border-slate-200">
        <!-- Action Bar (Hidden on print) -->
        <div class="no-print flex items-center justify-between pb-6 mb-6 border-b border-slate-200">
            <a href="{{ route('admin-prodi.laporan.index', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="button-secondary text-xs">
                &larr; Kembali ke Sistem
            </a>
            <div class="flex gap-2">
                <a href="{{ route('admin-prodi.laporan.export', ['prodi_id' => $activeProdi?->id, 'semester_id' => $activeSemester?->id]) }}" class="button-secondary text-xs">
                    Ekspor CSV / Excel
                </a>
                <button type="button" onclick="window.print()" class="button-primary text-xs">
                    Cetak Dokumen (Print)
                </button>
            </div>
        </div>

        <!-- Institutional Header -->
        <div class="text-center pb-6 border-b-2 border-slate-900 space-y-1">
            <h1 class="text-lg font-bold uppercase tracking-wider text-slate-900">{{ session('admin.settings.institution', 'SMART ACADEMIC LEARNING ECOSYSTEM (SALE)') }}</h1>
            <h2 class="text-base font-semibold text-slate-800">LAPORAN AKADEMIK &amp; KELAS PERKULIAHAN PROGRAM STUDI</h2>
            <p class="text-xs text-slate-600">
                Program Studi: <strong>{{ $activeProdi?->name }} ({{ $activeProdi?->code }})</strong> &middot; Semester: <strong>{{ $activeSemester?->name }}</strong>
            </p>
            <p class="text-[11px] text-slate-400">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i:s') }}</p>
        </div>

        <!-- Executive Summary Cards -->
        <div class="my-6 grid grid-cols-5 gap-3 text-center">
            <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                <p class="text-[10px] uppercase font-semibold text-slate-500">Mahasiswa Aktif</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ $metrics['total_mahasiswa'] }}</p>
            </div>
            <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                <p class="text-[10px] uppercase font-semibold text-slate-500">Dosen Pengampu</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ $metrics['total_dosen'] }}</p>
            </div>
            <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                <p class="text-[10px] uppercase font-semibold text-slate-500">Mahasiswa Baru</p>
                <p class="text-xl font-bold text-blue-700 mt-1">{{ $metrics['mahasiswa_baru'] }}</p>
            </div>
            <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                <p class="text-[10px] uppercase font-semibold text-slate-500">Rata-rata Nilai</p>
                <p class="text-xl font-bold text-emerald-700 mt-1">{{ $metrics['average_grade'] !== null ? number_format($metrics['average_grade'], 2) : '—' }}</p>
            </div>
            <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                <p class="text-[10px] uppercase font-semibold text-slate-500">Total Kelas</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ $metrics['total_kelas'] }}</p>
            </div>
        </div>

        <!-- Detail Kelas -->
        <div class="my-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Daftar Kelas Perkuliahan &amp; Capaian Nilai</h3>
            <table class="w-full text-left text-xs border border-slate-300 divide-y divide-slate-300">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-bold">
                        <th class="p-2 border-r border-slate-300 text-center w-8">No</th>
                        <th class="p-2 border-r border-slate-300">Kode &amp; Seksi</th>
                        <th class="p-2 border-r border-slate-300">Mata Kuliah (SKS)</th>
                        <th class="p-2 border-r border-slate-300">Dosen Ketua</th>
                        <th class="p-2 border-r border-slate-300">Dosen Wakil</th>
                        <th class="p-2 border-r border-slate-300 text-center">Mahasiswa</th>
                        <th class="p-2 text-center">Rata-rata Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($classReports as $idx => $cr)
                    <tr>
                        <td class="p-2 border-r border-slate-200 text-center text-slate-500">{{ $idx + 1 }}</td>
                        <td class="p-2 border-r border-slate-200 font-mono font-bold">{{ $cr['mk_code'] }}-{{ $cr['section_code'] }}</td>
                        <td class="p-2 border-r border-slate-200">{{ $cr['mk_name'] }} ({{ $cr['sks'] }} SKS)</td>
                        <td class="p-2 border-r border-slate-200 font-medium">{{ $cr['dosen_ketua'] }}</td>
                        <td class="p-2 border-r border-slate-200 text-slate-600">{{ $cr['dosen_wakil'] }}</td>
                        <td class="p-2 border-r border-slate-200 text-center">{{ $cr['students_count'] }}</td>
                        <td class="p-2 text-center font-bold {{ $cr['class_average'] !== null ? 'text-emerald-800' : 'text-slate-400' }}">
                            {{ $cr['class_average'] !== null ? number_format($cr['class_average'], 2) : 'Belum dinilai' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-slate-500">Tidak ada kelas perkuliahan pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="mt-12 pt-6 grid grid-cols-2 gap-8 text-xs text-slate-800">
            <div>
                <p>Mengetahui,</p>
                <p class="font-bold mt-1">Ketua Program Studi {{ $activeProdi?->name }}</p>
                <div class="h-20"></div>
                <p class="font-bold underline">Dr. H. Kaprodi, M.T.</p>
                <p class="text-slate-500">NIP. 197501012000031001</p>
            </div>
            <div class="text-right">
                <p>Batam, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold mt-1">Admin Program Studi</p>
                <div class="h-20"></div>
                <p class="font-bold underline">Admin Prodi {{ $activeProdi?->code }}</p>
                <p class="text-slate-500">NIP/ID. AP001</p>
            </div>
        </div>
    </div>
</body>
</html>
