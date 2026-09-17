@extends('layouts.mahasiswa')
@section('header', 'Laporan Fakultas')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Navigation Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin.page', 'laporan') }}" class="hover:text-brand transition">Laporan &amp; Rekapitulasi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Rincian Fakultas</span>
            </div>
            <h1 class="page-heading">Rincian Data Fakultas</h1>
            <p class="page-description">Informasi rekapitulasi data akademik fakultas dan program studi binaan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.page', 'laporan') }}" class="button-secondary inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Laporan
            </a>
            <a href="{{ route('admin.export') }}" class="button-primary">Unduh rekap CSV</a>
        </div>
    </div>

    {{-- Main Fakultas Detail Card --}}
    <section class="surface p-6">
        <div class="flex flex-wrap items-center justify-between pb-4 mb-6 border-b border-line/60 gap-3">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-brand/10 text-brand flex items-center justify-center font-bold text-lg">
                    FIK
                </div>
                <div>
                    <h2 class="text-lg font-bold text-ink">Fakultas Ilmu Komputer</h2>
                    <p class="text-xs text-muted">Kode: FIK · Jenjang Pendidikan Tinggi Komputer</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Status: Aktif
            </span>
        </div>

        {{-- 6 Field Utama Sesuai Permintaan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- 1. Nama Fakultas --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Nama Fakultas</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Fakultas Ilmu Komputer</span>
                <p class="text-xs text-muted mt-1">Fakultas induk sains &amp; teknologi informasi</p>
            </div>

            {{-- 2. Nama Prodi (Klik untuk membuka Dropdown seluruh prodi di fakultas) --}}
            <div class="p-4 bg-white rounded-xl border-2 border-brand/40 shadow-xs relative" id="prodi-dropdown-container">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold text-brand uppercase tracking-wider block">Nama Prodi</span>
                    <span class="text-[10px] font-semibold text-brand bg-brand-soft px-2 py-0.5 rounded-full">Klik Dropdown &dtrif;</span>
                </div>
                <button type="button" 
                        id="prodi-dropdown-button"
                        onclick="toggleProdiDropdown()"
                        class="w-full text-left flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-brand-soft/60 border border-line/60 transition group">
                    <div>
                        <span id="selected-prodi-label" class="text-base font-bold text-ink group-hover:text-brand transition block">
                            S1 Teknik Informatika
                        </span>
                        <span id="selected-prodi-sub" class="text-xs text-muted block">
                            Kode: IF · Klik untuk lihat prodi lainnya
                        </span>
                    </div>
                    <svg id="prodi-dropdown-arrow" class="h-5 w-5 text-brand transition-transform duration-200 shrink-0 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                {{-- Dropdown Menampilkan Semua Nama Prodi Sesuai Fakultas yang Diklik --}}
                <div id="prodi-dropdown-menu" hidden class="absolute left-0 right-0 top-full mt-2 z-30 bg-white rounded-xl border border-line shadow-xl p-2 space-y-1">
                    <div class="px-3 py-2 text-[11px] font-bold text-muted uppercase tracking-wider border-b border-line/60 flex items-center justify-between">
                        <span>Semua Prodi di FIK</span>
                        <span class="text-[10px] font-normal text-muted">{{ count($facultyProdis) }} Program Studi</span>
                    </div>
                    @foreach($facultyProdis as $fp)
                        <button type="button" 
                                onclick="selectProdiItem('{{ $fp['name'] }}', '{{ $fp['code'] }}', '{{ $fp['kaprodi'] }}', '{{ $fp['wakil'] }}', '{{ $fp['mahasiswa'] }}')"
                                class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-brand-soft transition flex items-center justify-between text-xs group">
                            <div>
                                <span class="font-bold text-ink group-hover:text-brand block text-sm">{{ $fp['name'] }}</span>
                                <span class="text-muted block text-[11px]">Kaprodi: {{ $fp['kaprodi'] }} · Wakil: {{ $fp['wakil'] }}</span>
                            </div>
                            <span class="text-[11px] font-semibold text-brand bg-brand-soft/80 px-2.5 py-1 rounded-md border border-brand/20">
                                {{ $fp['mahasiswa'] }} Mhs
                            </span>
                        </button>
                    @endforeach
                    <div class="pt-2 border-t border-line/60 px-2">
                        <a href="{{ route('admin.laporan.prodi') }}" class="block text-center text-xs font-semibold text-brand hover:underline py-1">
                            Buka Halaman Rincian Prodi Lengkap &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- 3. Jumlah Prodi --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Prodi</span>
                <span class="text-base font-bold text-ink mt-1.5 block">{{ count($facultyProdis) }} Program Studi</span>
                <p class="text-xs text-muted mt-1">S1 Informatika, S1 Sistem Informasi, S1 TI</p>
            </div>

            {{-- 4. Dekan --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Dekan</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Prof. Dr. Ir. H. M. Zain, M.Kom.</span>
                <p class="text-xs text-muted mt-1">NIP: 196803151993031002</p>
            </div>

            {{-- 5. Wakil --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Wakil</span>
                <span class="text-base font-bold text-ink mt-1.5 block">Dr. Eng. Rina Marlina, M.Kom.</span>
                <p class="text-xs text-muted mt-1">Wakil Dekan Bidang Akademik &amp; Kemahasiswaan</p>
            </div>

            {{-- 6. Jumlah Mahasiswa --}}
            <div class="p-4 bg-slate-50/70 rounded-xl border border-line/50">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Jumlah Mahasiswa</span>
                <span class="text-base font-bold text-brand mt-1.5 block">120 Mahasiswa</span>
                <p class="text-xs text-muted mt-1">Total mahasiswa aktif di seluruh program studi binaan</p>
            </div>
        </div>
    </section>

    {{-- Daftar Tabel Seluruh Program Studi di Bawah Fakultas --}}
    <section class="surface p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-ink">Daftar Program Studi Binaan Fakultas</h3>
                <p class="text-xs text-muted">Semua program studi yang bernaung di bawah Fakultas Ilmu Komputer</p>
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
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facultyProdis as $fp)
                    <tr class="hover:bg-canvas/50 transition">
                        <td class="font-bold text-ink">{{ $fp['code'] }}</td>
                        <td class="font-semibold text-ink">{{ $fp['name'] }}</td>
                        <td>{{ $fp['jenjang'] }}</td>
                        <td>{{ $fp['kaprodi'] }}</td>
                        <td>{{ $fp['wakil'] }}</td>
                        <td class="font-bold text-brand">{{ $fp['mahasiswa'] }} Mahasiswa</td>
                        <td>
                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                {{ $fp['status'] }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.laporan.prodi') }}" class="button-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1">
                                Lihat Rincian &rarr;
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>

<script>
    function toggleProdiDropdown() {
        const menu = document.getElementById('prodi-dropdown-menu');
        const arrow = document.getElementById('prodi-dropdown-arrow');
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

    function selectProdiItem(name, code, kaprodi, wakil, mhs) {
        document.getElementById('selected-prodi-label').textContent = name;
        document.getElementById('selected-prodi-sub').textContent = 'Kode: ' + code + ' · Kaprodi: ' + kaprodi;
        toggleProdiDropdown();
    }

    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('prodi-dropdown-container');
        const menu = document.getElementById('prodi-dropdown-menu');
        if (container && menu && !container.contains(e.target)) {
            menu.setAttribute('hidden', '');
            const arrow = document.getElementById('prodi-dropdown-arrow');
            if (arrow) arrow.classList.remove('rotate-180');
        }
    });
</script>
@endsection
