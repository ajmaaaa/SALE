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
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.page', 'dashboard') }}" class="button-secondary inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Dashboard
            </a>
            <a href="{{ route('admin.page', 'laporan') }}" class="button-secondary">Tabel Laporan</a>
        </div>
    </div>

    {{-- Main Fakultas Detail Card --}}
    <section class="surface p-6">
        <div class="flex flex-wrap items-center justify-between pb-4 mb-6 border-b border-line/60 gap-3">
            <div class="flex items-center gap-3">
                <div id="faculty-avatar" class="h-12 w-12 rounded-xl bg-brand/10 text-brand flex items-center justify-center font-bold text-xl">
                    {{ $faculty['code'] }}
                </div>
                <div>
                    <h2 id="faculty-title" class="text-lg font-bold text-ink">{{ $faculty['name'] }}</h2>
                    <p id="faculty-subtitle" class="text-xs text-muted">Kode: {{ $faculty['code'] }} · {{ $faculty['prodis_count'] }} Program Studi Binaan</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Status: Aktif
            </span>
        </div>

        {{-- 6 Field Utama Sesuai Permintaan:
             1. Nama Fakultas (Klik untuk Dropdown Semua Fakultas)
             2. Nama Prodi (Klik untuk Dropdown Semua Prodi sesuai Fakultas terpilih)
             3. Jumlah Prodi
             4. Dekan
             5. Wakil
             6. Jumlah Mahasiswa --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            {{-- 1. Nama Fakultas (Interaktif: Klik membuka dropdown memilih fakultas) --}}
            <div class="p-4 bg-white rounded-xl border-2 border-brand/50 shadow-xs relative" id="fakultas-dropdown-container">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold text-brand uppercase tracking-wider block">Nama Fakultas</span>
                    <span class="text-[10px] font-semibold text-brand bg-brand-soft px-2 py-0.5 rounded-full">Klik Dropdown &dtrif;</span>
                </div>
                <button type="button" 
                        id="fakultas-dropdown-button"
                        onclick="toggleDropdown('fakultas-dropdown-menu', 'fakultas-dropdown-arrow')"
                        class="w-full text-left flex items-center justify-between p-2.5 rounded-lg bg-slate-50 hover:bg-brand-soft/60 border border-line/60 transition group">
                    <div>
                        <span id="field-nama-fakultas" class="text-base font-bold text-ink group-hover:text-brand transition block">
                            {{ $faculty['name'] }}
                        </span>
                        <span class="text-xs text-muted block">Klik untuk ganti fakultas</span>
                    </div>
                    <svg id="fakultas-dropdown-arrow" class="h-5 w-5 text-brand transition-transform duration-200 shrink-0 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

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

            {{-- 2. Nama Prodi (Interaktif: Klik membuka dropdown prodi sesuai fakultas terpilih) --}}
            <div class="p-4 bg-white rounded-xl border-2 border-emerald-500/50 shadow-xs relative" id="prodi-dropdown-container">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Nama Prodi</span>
                    <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Klik Dropdown &dtrif;</span>
                </div>
                <button type="button" 
                        id="prodi-dropdown-button"
                        onclick="toggleDropdown('prodi-dropdown-menu', 'prodi-dropdown-arrow')"
                        class="w-full text-left flex items-center justify-between p-2.5 rounded-lg bg-slate-50 hover:bg-emerald-50/70 border border-line/60 transition group">
                    <div>
                        <span id="field-nama-prodi" class="text-base font-bold text-ink group-hover:text-emerald-700 transition block">
                            {{ $prodi['name'] }}
                        </span>
                        <span id="field-prodi-sub" class="text-xs text-muted block">
                            Kaprodi: {{ $prodi['kaprodi'] }}
                        </span>
                    </div>
                    <svg id="prodi-dropdown-arrow" class="h-5 w-5 text-emerald-600 transition-transform duration-200 shrink-0 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                {{-- Dropdown Menampilkan Semua Prodi Sesuai Fakultas yang Dipilih --}}
                <div id="prodi-dropdown-menu" hidden class="absolute left-0 right-0 top-full mt-2 z-30 bg-white rounded-xl border border-line shadow-xl p-2 space-y-1">
                    <div class="px-3 py-2 text-[11px] font-bold text-muted uppercase tracking-wider border-b border-line/60 flex items-center justify-between">
                        <span id="prodi-dropdown-header">Prodi di {{ $faculty['name'] }}</span>
                        <span id="prodi-dropdown-count" class="text-[10px] font-normal text-muted">{{ count($faculty['prodis']) }} Prodi</span>
                    </div>
                    <div id="prodi-options-list" class="space-y-1">
                        @foreach($faculty['prodis'] as $pCode => $p)
                            <button type="button" 
                                    onclick="selectProdi('{{ $pCode }}')"
                                    class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-emerald-50 transition flex items-center justify-between text-xs group">
                                <div>
                                    <span class="font-bold text-ink group-hover:text-emerald-700 block text-sm">{{ $p['name'] }}</span>
                                    <span class="text-muted block text-[11px]">Kaprodi: {{ $p['kaprodi'] }} · {{ $p['students_count'] }} Mahasiswa</span>
                                </div>
                                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    Pilih &rarr;
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <div class="pt-2 border-t border-line/60 px-2">
                        <a id="link-to-prodi-page" href="{{ route('admin.laporan.prodi') }}?prodi={{ $prodi['code'] }}" class="block text-center text-xs font-semibold text-emerald-700 hover:underline py-1">
                            Buka Halaman Rincian Lengkap Prodi Ini &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- 3. Jumlah Prodi --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Prodi</span>
                <span id="field-jumlah-prodi" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $faculty['prodis_count'] }} Program Studi
                </span>
                <p class="text-xs text-muted mt-1">Seluruh prodi terakreditasi aktif</p>
            </div>

            {{-- 4. Dekan --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Dekan</span>
                <span id="field-dekan" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $faculty['dekan'] }}
                </span>
                <p class="text-xs text-muted mt-1">Pimpinan Fakultas</p>
            </div>

            {{-- 5. Wakil --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Wakil</span>
                <span id="field-wakil" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $faculty['wakil'] }}
                </span>
                <p class="text-xs text-muted mt-1">Wakil Dekan Bidang Akademik &amp; Kemahasiswaan</p>
            </div>

            {{-- 6. Jumlah Mahasiswa --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                <span id="field-jumlah-mahasiswa" class="text-base font-bold text-brand mt-1.5 block">
                    {{ $faculty['students_count'] }} Mahasiswa
                </span>
                <p class="text-xs text-muted mt-1">Total mahasiswa aktif terdaftar pada fakultas ini</p>
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
                            <a href="{{ route('admin.laporan.prodi') }}?prodi={{ $p['code'] }}" class="button-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1">
                                Rincian Prodi &rarr;
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>

{{-- Data JSON Master untuk Interaktivitas Dropdown Instan --}}
<script>
    const masterData = @json($faculties);
    let currentFacultyCode = '{{ $faculty['code'] }}';
    let currentProdiCode = '{{ $prodi['code'] }}';

    function toggleDropdown(menuId, arrowId) {
        const menu = document.getElementById(menuId);
        const arrow = document.getElementById(arrowId);
        if (!menu) return;

        const isHidden = menu.hasAttribute('hidden');
        if (isHidden) {
            menu.removeAttribute('hidden');
            if (arrow) arrow.classList.add('rotate-180');
        } else {
            menu.setAttribute('hidden', '');
            if (arrow) arrow.classList.remove('rotate-180');
        }
    }

    // Fungsi Ketika Memilih Fakultas:
    // Menyesuaikan seluruh data fakultas, dan memperbarui daftar prodi sesuai fakultas yang dipilih
    function selectFaculty(facultyCode) {
        if (!masterData[facultyCode]) return;
        currentFacultyCode = facultyCode;
        const fac = masterData[facultyCode];

        // 1. Update Teks Header & Avatar
        document.getElementById('faculty-avatar').textContent = fac.code;
        document.getElementById('faculty-title').textContent = fac.name;
        document.getElementById('faculty-subtitle').textContent = 'Kode: ' + fac.code + ' · ' + fac.prodis_count + ' Program Studi Binaan';

        // 2. Update Field-Field Fakultas
        document.getElementById('field-nama-fakultas').textContent = fac.name;
        document.getElementById('field-jumlah-prodi').textContent = fac.prodis_count + ' Program Studi';
        document.getElementById('field-dekan').textContent = fac.dekan;
        document.getElementById('field-wakil').textContent = fac.wakil;
        document.getElementById('field-jumlah-mahasiswa').textContent = fac.students_count + ' Mahasiswa';

        // 3. Reset Prodi Aktif ke Prodi Pertama Fakultas Ini
        const prodiKeys = Object.keys(fac.prodis);
        if (prodiKeys.length > 0) {
            selectProdi(prodiKeys[0]);
        }

        // 4. Update Daftar Dropdown Prodi
        document.getElementById('prodi-dropdown-header').textContent = 'Prodi di ' + fac.name;
        document.getElementById('prodi-dropdown-count').textContent = fac.prodis_count + ' Prodi';
        
        let prodiOptionsHtml = '';
        prodiKeys.forEach(code => {
            const p = fac.prodis[code];
            prodiOptionsHtml += `
                <button type="button" 
                        onclick="selectProdi('${p.code}')"
                        class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-emerald-50 transition flex items-center justify-between text-xs group">
                    <div>
                        <span class="font-bold text-ink group-hover:text-emerald-700 block text-sm">${p.name}</span>
                        <span class="text-muted block text-[11px]">Kaprodi: ${p.kaprodi} · ${p.students_count} Mahasiswa</span>
                    </div>
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        Pilih &rarr;
                    </span>
                </button>
            `;
        });
        document.getElementById('prodi-options-list').innerHTML = prodiOptionsHtml;

        // 5. Update Tabel Prodi Binaan
        document.getElementById('table-prodi-heading').textContent = 'Daftar Program Studi: ' + fac.name;
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
                        <a href="{{ route('admin.laporan.prodi') }}?prodi=${p.code}" class="button-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1">
                            Rincian Prodi &rarr;
                        </a>
                    </td>
                </tr>
            `;
        });
        document.getElementById('faculty-prodi-table-body').innerHTML = tableRowsHtml;

        // Tutup dropdown fakultas
        toggleDropdown('fakultas-dropdown-menu', 'fakultas-dropdown-arrow');
    }

    // Fungsi Ketika Memilih Prodi:
    // Menyesuaikan data prodi yang dipilih
    function selectProdi(prodiCode) {
        const fac = masterData[currentFacultyCode];
        if (!fac || !fac.prodis[prodiCode]) return;
        currentProdiCode = prodiCode;
        const p = fac.prodis[prodiCode];

        document.getElementById('field-nama-prodi').textContent = p.name;
        document.getElementById('field-prodi-sub').textContent = 'Kaprodi: ' + p.kaprodi + ' · ' + p.students_count + ' Mhs';
        document.getElementById('link-to-prodi-page').href = "{{ route('admin.laporan.prodi') }}?prodi=" + p.code;

        // Tutup dropdown prodi jika terbuka
        const pMenu = document.getElementById('prodi-dropdown-menu');
        if (pMenu && !pMenu.hasAttribute('hidden')) {
            toggleDropdown('prodi-dropdown-menu', 'prodi-dropdown-arrow');
        }
    }

    // Tutup dropdown jika klik di luar area
    document.addEventListener('click', function(e) {
        const facContainer = document.getElementById('fakultas-dropdown-container');
        const facMenu = document.getElementById('fakultas-dropdown-menu');
        if (facContainer && facMenu && !facContainer.contains(e.target)) {
            facMenu.setAttribute('hidden', '');
            const arrow = document.getElementById('fakultas-dropdown-arrow');
            if (arrow) arrow.classList.remove('rotate-180');
        }

        const prodiContainer = document.getElementById('prodi-dropdown-container');
        const prodiMenu = document.getElementById('prodi-dropdown-menu');
        if (prodiContainer && prodiMenu && !prodiContainer.contains(e.target)) {
            prodiMenu.setAttribute('hidden', '');
            const arrow = document.getElementById('prodi-dropdown-arrow');
            if (arrow) arrow.classList.remove('rotate-180');
        }
    });
</script>
@endsection
