@extends('layouts.mahasiswa')
@section('header', 'Laporan Fakultas')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Navigation Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin.page', 'dashboard') }}" class="hover:text-brand transition">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.page', 'laporan') }}" class="hover:text-brand transition">Laporan</a>
                <span>/</span>
                <span class="text-ink font-semibold">Rincian Fakultas</span>
            </div>
            <h1 class="page-heading">Rincian Data Fakultas</h1>
            <p class="page-description">Informasi rekapitulasi data akademik fakultas dan program studi binaan.</p>
        </div>
        <div>
            {{-- Button Export Sesuai Permintaan --}}
            <a href="{{ route('admin.export') }}" class="button-primary inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="8" y1="13" x2="16" y2="13"></line>
                    <line x1="8" y1="17" x2="16" y2="17"></line>
                </svg>
                Export
            </a>
        </div>
    </div>

    {{-- Main Fakultas Detail Card --}}
    <section class="surface p-6 space-y-5">
        {{-- Card Pemilih Nama Fakultas (Dropdown Bersih Tanpa Panah) --}}
        <div class="p-4 bg-white rounded-xl border border-line shadow-xs relative" id="fakultas-dropdown-container">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Nama Fakultas</span>
                    <h2 id="field-nama-fakultas" class="text-lg font-bold text-ink mt-0.5">
                        {{ $faculty['name'] }}
                    </h2>
                    <p id="faculty-subtitle" class="text-xs text-muted mt-0.5">
                        Kode: {{ $faculty['code'] }} · {{ $faculty['prodis_count'] }} Program Studi Binaan
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" 
                            id="fakultas-dropdown-button"
                            onclick="toggleDropdown('fakultas-dropdown-menu')"
                            class="button-secondary text-xs py-2 px-3 font-semibold">
                        Ganti Fakultas
                    </button>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Aktif
                    </span>
                </div>
            </div>

            {{-- Dropdown Menampilkan Semua Fakultas --}}
            <div id="fakultas-dropdown-menu" hidden class="absolute left-0 right-0 top-full mt-2 z-30 bg-white rounded-xl border border-line shadow-xl p-2 space-y-1">
                <div class="px-3 py-2 text-[11px] font-bold text-muted uppercase tracking-wider border-b border-line/60">
                    Pilih Fakultas (3 Fakultas Tersedia)
                </div>
                @foreach($faculties as $fCode => $f)
                    <button type="button" 
                            onclick="selectFaculty('{{ $fCode }}')"
                            class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-brand-soft transition flex items-center justify-between text-xs group">
                        <div>
                            <span class="font-bold text-ink group-hover:text-brand block text-sm">{{ $f['name'] }} ({{ $f['code'] }})</span>
                            <span class="text-muted block text-[11px]">Dekan: {{ $f['dekan'] }}</span>
                        </div>
                        <span class="text-[11px] font-semibold text-brand bg-brand-soft/80 px-2 py-0.5 rounded border border-brand/20">
                            {{ $f['prodis_count'] }} Prodi
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- 4 Field Rincian Fakultas (Rapi & Seimbang) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 1. Jumlah Prodi --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Prodi</span>
                <span id="field-jumlah-prodi" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $faculty['prodis_count'] }} Program Studi
                </span>
                <p class="text-xs text-muted mt-1">Seluruh prodi terakreditasi aktif</p>
            </div>

            {{-- 2. Dekan --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Dekan</span>
                <span id="field-dekan" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $faculty['dekan'] }}
                </span>
                <p class="text-xs text-muted mt-1">Pimpinan Fakultas</p>
            </div>

            {{-- 3. Wakil --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Wakil</span>
                <span id="field-wakil" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $faculty['wakil'] }}
                </span>
                <p class="text-xs text-muted mt-1">Wakil Dekan Bidang Akademik</p>
            </div>

            {{-- 4. Jumlah Mahasiswa --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                <span id="field-jumlah-mahasiswa" class="text-base font-bold text-brand mt-1.5 block">
                    {{ $faculty['students_count'] }} Mahasiswa
                </span>
                <p class="text-xs text-muted mt-1">Total mahasiswa aktif fakultas</p>
            </div>
        </div>
    </section>

    {{-- Tabel Daftar 4 Program Studi di Bawah Fakultas Terpilih --}}
    <section class="surface p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 id="table-prodi-heading" class="text-base font-bold text-ink">Daftar Program Studi: {{ $faculty['name'] }}</h3>
                <p class="text-xs text-muted">4 Program Studi yang bernaung di bawah fakultas ini</p>
            </div>
        </div>

        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Program Studi</th>
                    <th>Jenjang</th>
                    <th>Kaprodi</th>
                    <th>Wakil</th>
                    <th>Jumlah Mahasiswa</th>
                    <th>IPK Rata-Rata</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="faculty-prodi-table-body">
                @foreach($faculty['prodis'] as $pCode => $p)
                    <tr class="hover:bg-canvas/50 transition">
                        <td class="font-bold text-ink">{{ $p['code'] }}</td>
                        <td class="font-semibold text-ink">{{ $p['name'] }}</td>
                        <td>{{ $p['jenjang'] }}</td>
                        <td>{{ $p['kaprodi'] }}</td>
                        <td>{{ $p['wakil'] }}</td>
                        <td class="font-bold text-brand">{{ $p['students_count'] }} Mahasiswa</td>
                        <td class="font-bold text-emerald-600">{{ $p['avg_ipk'] }}</td>
                        <td>
                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                {{ $p['status'] }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.laporan.prodi') }}?prodi={{ $p['code'] }}" class="button-secondary text-xs py-1 px-3">
                                Rincian Prodi
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>

{{-- Script Interaktivitas Dropdown Instan --}}
<script>
    const masterData = @json($faculties);
    let currentFacultyCode = '{{ $faculty['code'] }}';

    function toggleDropdown(menuId) {
        const menu = document.getElementById(menuId);
        if (!menu) return;

        const isHidden = menu.hasAttribute('hidden');
        if (isHidden) {
            menu.removeAttribute('hidden');
        } else {
            menu.setAttribute('hidden', '');
        }
    }

    // Fungsi Ketika Memilih Fakultas:
    function selectFaculty(facultyCode) {
        if (!masterData[facultyCode]) return;
        currentFacultyCode = facultyCode;
        const fac = masterData[facultyCode];

        // 1. Update Teks Header & Subtitle
        document.getElementById('field-nama-fakultas').textContent = fac.name;
        document.getElementById('faculty-subtitle').textContent = 'Kode: ' + fac.code + ' · ' + fac.prodis_count + ' Program Studi Binaan';

        // 2. Update Field-Field Fakultas
        document.getElementById('field-jumlah-prodi').textContent = fac.prodis_count + ' Program Studi';
        document.getElementById('field-dekan').textContent = fac.dekan;
        document.getElementById('field-wakil').textContent = fac.wakil;
        document.getElementById('field-jumlah-mahasiswa').textContent = fac.students_count + ' Mahasiswa';

        // 3. Update Tabel Prodi Binaan (Tanpa Panah)
        document.getElementById('table-prodi-heading').textContent = 'Daftar Program Studi: ' + fac.name;
        const prodiKeys = Object.keys(fac.prodis);
        let tableRowsHtml = '';
        prodiKeys.forEach(code => {
            const p = fac.prodis[code];
            tableRowsHtml += `
                <tr class="hover:bg-canvas/50 transition">
                    <td class="font-bold text-ink">${p.code}</td>
                    <td class="font-semibold text-ink">${p.name}</td>
                    <td>${p.jenjang}</td>
                    <td>${p.kaprodi}</td>
                    <td>${p.wakil}</td>
                    <td class="font-bold text-brand">${p.students_count} Mahasiswa</td>
                    <td class="font-bold text-emerald-600">${p.avg_ipk}</td>
                    <td>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                            ${p.status}
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.laporan.prodi') }}?prodi=${p.code}" class="button-secondary text-xs py-1 px-3">
                            Rincian Prodi
                        </a>
                    </td>
                </tr>
            `;
        });
        document.getElementById('faculty-prodi-table-body').innerHTML = tableRowsHtml;

        // Tutup dropdown fakultas
        toggleDropdown('fakultas-dropdown-menu');
    }

    // Tutup dropdown jika klik di luar area
    document.addEventListener('click', function(e) {
        const facContainer = document.getElementById('fakultas-dropdown-container');
        const facMenu = document.getElementById('fakultas-dropdown-menu');
        if (facContainer && facMenu && !facContainer.contains(e.target)) {
            facMenu.setAttribute('hidden', '');
        }
    });
</script>
@endsection
