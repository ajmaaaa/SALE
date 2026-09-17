@extends('layouts.mahasiswa')
@section('header', 'Laporan Program Studi')

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
                <span class="text-ink font-semibold">Rincian Program Studi</span>
            </div>
            <h1 class="page-heading">Rincian Data Program Studi</h1>
            <p class="page-description">Informasi kurikulum, pimpinan prodi, mata kuliah, dan ketercapaian akademik.</p>
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

    {{-- Main Program Studi Detail Section --}}
    <section class="surface p-6 space-y-5">
        {{-- Card Pemilih Nama Program Studi (Dropdown Bersih Tanpa Panah) --}}
        <div class="p-4 bg-white rounded-xl border border-line shadow-xs relative" id="prodi-select-container">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Nama Program Studi</span>
                    <h2 id="field-nama-prodi" class="text-lg font-bold text-ink mt-0.5">
                        {{ $prodi['name'] }}
                    </h2>
                    <p id="field-prodi-faculty-label" class="text-xs text-muted mt-0.5">
                        {{ $faculty['name'] }} · Kode: {{ $prodi['code'] }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" 
                            id="prodi-select-button"
                            onclick="toggleProdiSelectDropdown()"
                            class="button-secondary text-xs py-2 px-3 font-semibold">
                        Ganti Prodi
                    </button>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Aktif
                    </span>
                </div>
            </div>

            {{-- Dropdown Menampilkan Seluruh 12 Prodi dari 3 Fakultas --}}
            <div id="prodi-select-menu" hidden class="absolute left-0 right-0 top-full mt-2 z-30 bg-white rounded-xl border border-line shadow-xl p-2 max-h-80 overflow-y-auto space-y-2">
                @foreach($faculties as $fCode => $f)
                    <div class="px-2 pt-1 pb-0.5 text-[11px] font-bold text-muted uppercase tracking-wider bg-slate-50 rounded">
                        {{ $f['name'] }} ({{ $f['code'] }})
                    </div>
                    <div class="space-y-1">
                        @foreach($f['prodis'] as $pCode => $p)
                            <button type="button" 
                                    onclick="changeActiveProdi('{{ $pCode }}')"
                                    class="w-full text-left px-3 py-2 rounded-lg hover:bg-emerald-50 transition flex items-center justify-between text-xs group">
                                <div>
                                    <span class="font-bold text-ink group-hover:text-emerald-700 block text-sm">{{ $p['name'] }}</span>
                                    <span class="text-muted block text-[11px]">Kaprodi: {{ $p['kaprodi'] }} · {{ $p['students_count'] }} Mahasiswa</span>
                                </div>
                                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    Pilih
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 6 Field Rincian Program Studi (Rapi, Simetris & Seimbang dalam 3 Kolom) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- 1. Kaprodi --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Kaprodi</span>
                <span id="field-kaprodi" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $prodi['kaprodi'] }}
                </span>
                <p class="text-xs text-muted mt-1">Ketua Program Studi</p>
            </div>

            {{-- 2. Wakil --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Wakil</span>
                <span id="field-wakil" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $prodi['wakil'] }}
                </span>
                <p class="text-xs text-muted mt-1">Wakil Ketua Program Studi</p>
            </div>

            {{-- 3. Semester --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Semester</span>
                <span id="field-semester" class="text-base font-bold text-ink mt-1.5 block">
                    {{ $prodi['semester'] }}
                </span>
                <p class="text-xs text-muted mt-1">Tahun Akademik Berjalan</p>
            </div>

            {{-- 4. Mata Kuliah --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Mata Kuliah</span>
                <span id="field-mata-kuliah" class="text-base font-bold text-brand mt-1.5 block">
                    {{ count($prodi['courses']) }} Mata Kuliah Aktif
                </span>
                <p class="text-xs text-muted mt-1">Kurikulum berbasis OBE</p>
            </div>

            {{-- 5. Jumlah Mahasiswa --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                <span id="field-jumlah-mahasiswa" class="text-base font-bold text-brand mt-1.5 block">
                    {{ $prodi['students_count'] }} Mahasiswa
                </span>
                <p class="text-xs text-muted mt-1">Mahasiswa aktif terdaftar semester ini</p>
            </div>

            {{-- 6. IPK Rata-Rata --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">IPK Rata-Rata</span>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span id="field-ipk-rata-rata" class="text-xl font-black text-emerald-600">
                        {{ $prodi['avg_ipk'] }}
                    </span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-emerald-100/80 text-emerald-800">
                        Memuaskan
                    </span>
                </div>
                <p class="text-xs text-muted mt-1">Rata-rata kumulatif mahasiswa</p>
            </div>
        </div>
    </section>

    {{-- Tabel Mata Kuliah Aktif untuk Program Studi Terpilih --}}
    <section class="surface p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 id="table-courses-heading" class="text-base font-bold text-ink">Daftar Mata Kuliah: {{ $prodi['name'] }}</h3>
                <p class="text-xs text-muted">Mata kuliah aktif pada semester berjalan program studi ini</p>
            </div>
        </div>

        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th>Kode MK</th>
                    <th>Nama Mata Kuliah</th>
                    <th>Bobot SKS</th>
                    <th>Semester</th>
                    <th>Kelas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="courses-table-body">
                @foreach($prodi['courses'] as $c)
                    <tr class="hover:bg-canvas/50 transition">
                        <td class="font-bold text-ink">{{ $c['code'] }}</td>
                        <td class="font-semibold text-ink">{{ $c['name'] }}</td>
                        <td>{{ $c['sks'] }} SKS</td>
                        <td>{{ $c['semester'] }}</td>
                        <td class="font-medium text-brand">{{ $c['kelas'] }}</td>
                        <td>
                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                Aktif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>

{{-- Data JSON Master untuk Interaktivitas Dropdown Instan --}}
<script>
    const allProdisData = @json($allProdis);
    const facultiesData = @json($faculties);

    function toggleProdiSelectDropdown() {
        const menu = document.getElementById('prodi-select-menu');
        if (!menu) return;

        const isHidden = menu.hasAttribute('hidden');
        if (isHidden) {
            menu.removeAttribute('hidden');
        } else {
            menu.setAttribute('hidden', '');
        }
    }

    // Fungsi Mengganti Program Studi Aktif
    function changeActiveProdi(prodiCode) {
        if (!allProdisData[prodiCode]) return;
        const p = allProdisData[prodiCode];
        const fac = facultiesData[p.faculty_code];

        // 1. Update Header Program Studi
        document.getElementById('field-nama-prodi').textContent = p.name;
        document.getElementById('field-prodi-faculty-label').textContent = (fac ? fac.name : '') + ' · Kode: ' + p.code;

        // 2. Update 6 Field Utama
        document.getElementById('field-kaprodi').textContent = p.kaprodi;
        document.getElementById('field-wakil').textContent = p.wakil;
        document.getElementById('field-semester').textContent = p.semester;
        document.getElementById('field-mata-kuliah').textContent = p.courses.length + ' Mata Kuliah Aktif';
        document.getElementById('field-jumlah-mahasiswa').textContent = p.students_count + ' Mahasiswa';
        document.getElementById('field-ipk-rata-rata').textContent = p.avg_ipk;

        // 3. Update Tabel Mata Kuliah
        document.getElementById('table-courses-heading').textContent = 'Daftar Mata Kuliah: ' + p.name;
        let coursesHtml = '';
        p.courses.forEach(c => {
            coursesHtml += `
                <tr class="hover:bg-canvas/50 transition">
                    <td class="font-bold text-ink">${c.code}</td>
                    <td class="font-semibold text-ink">${c.name}</td>
                    <td>${c.sks} SKS</td>
                    <td>${c.semester}</td>
                    <td class="font-medium text-brand">${c.kelas}</td>
                    <td>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                            Aktif
                        </span>
                    </td>
                </tr>
            `;
        });
        document.getElementById('courses-table-body').innerHTML = coursesHtml;

        // Tutup dropdown
        toggleProdiSelectDropdown();
    }

    // Tutup dropdown saat klik di luar
    document.addEventListener('click', function(e) {
        const container = document.getElementById('prodi-select-container');
        const menu = document.getElementById('prodi-select-menu');
        if (container && menu && !container.contains(e.target)) {
            menu.setAttribute('hidden', '');
        }
    });
</script>
@endsection
