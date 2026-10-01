@extends('layouts.mahasiswa')

@section('title', 'Manajemen Kelas Perkuliahan | SALE')
@section('header', 'Kelas Perkuliahan & Penugasan Dosen')

@section('content')
<div class="space-y-6 w-full">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
                <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.dashboard') }}">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Admin Prodi</span>
                </a>
                <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @if($activeProdi)
                    <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.akademik.kelas') }}">
                        Kelas Perkuliahan
                    </a>
                    <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="font-semibold text-slate-800" aria-current="page">
                        {{ $activeProdi->name }}
                    </span>
                @else
                    <span class="font-semibold text-slate-800" aria-current="page">
                        Kelas Perkuliahan
                    </span>
                @endif
            </nav>
            <h1 class="page-heading">Kelas Perkuliahan &amp; Penugasan Dosen</h1>
            <p class="page-description">Bentuk kelas mata kuliah, tetapkan Dosen Ketua &amp; Dosen Anggota, serta bagikan Link / Barcode QR Code untuk pendaftaran mahasiswa.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto sm:ml-auto">
            <button type="button" onclick="{{ $activeProdi ? 'openCreateKelasModal()' : 'window.saleNotice({ title: \'Pilih Program Studi\', message: \'Silakan pilih salah satu program studi terlebih dahulu untuk membuka kelas baru.\' })' }}" class="button-primary text-xs whitespace-nowrap w-full sm:w-auto justify-center">
                + Buka Kelas Baru
            </button>
        </div>
    </header>

@if(! $activeProdi)
    @include('admin-prodi.partials.prodi-selector', [
        'hideHeader' => true,
        'menuTitle' => 'Kelas Perkuliahan',
        'description' => 'Silakan pilih program studi terlebih dahulu untuk mengelola kelas perkuliahan dan penugasan dosen.',
        'targetRoute' => 'admin-prodi.akademik.kelas',
        'actionLabel' => 'Kelola Kelas Perkuliahan',
    ])
@else

    <!-- Filter Bar -->
    <div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
        <form id="filterForm" method="GET" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="prodi_id" value="{{ $activeProdi->id }}">
            <label for="filter_semester" class="text-xs font-semibold text-muted">Semester:</label>
            <select name="semester_id" id="filter_semester" onchange="this.form.submit()" class="field text-xs font-semibold w-48">
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $selectedSemesterId == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="text-xs text-muted">
            Menampilkan <strong class="text-ink">{{ $classes->count() }}</strong> seksi kelas prodi <strong class="text-ink">{{ $activeProdi->name }}</strong>
        </div>
    </div>

    <!-- Table Kelas -->
    <div class="surface p-5">
        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3 text-center w-12 !align-middle">No</th>
                        <th class="px-4 py-3 w-28 !align-middle">Kode Kelas</th>
                        <th class="px-4 py-3 !align-middle">Mata Kuliah &amp; SKS</th>
                        <th class="px-4 py-3 w-48 !align-middle">Dosen Ketua</th>
                        <th class="px-4 py-3 w-40 !align-middle">Dosen Anggota</th>
                        <th class="px-4 py-3 text-center w-28 !align-middle">Barcode</th>
                        <th class="px-4 py-3 text-center w-28 !align-middle">Kapasitas</th>
                        <th class="px-4 py-3 text-right w-32 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($classes as $index => $cls)
                    <tr class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 text-center text-muted font-medium !align-middle">{{ $index + 1 }}</td>
                        <td class="px-4 py-3.5 !align-middle whitespace-nowrap">
                            <span class="font-mono font-bold text-brand">{{ $cls->display_code }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $cls->mataKuliah->name }}</span>
                            <span class="text-[11px] text-muted block mt-0.5">
                                {{ $cls->mataKuliah->code }} &bull; {{ $cls->mataKuliah->sks }} SKS
                                @if($cls->mataKuliah->semester_paket)
                                    &bull; <span class="text-slate-600 font-semibold">Sem. {{ $cls->mataKuliah->semester_paket }}</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-medium text-ink block">{{ $cls->dosen?->name ?? 'Belum ditentukan' }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            @php
                                $anggotaList = $cls->dosenAnggota->isNotEmpty()
                                    ? $cls->dosenAnggota
                                    : ($cls->dosenPendamping ? collect([$cls->dosenPendamping]) : collect());
                            @endphp
                            @if($anggotaList->isNotEmpty())
                                @foreach($anggotaList as $anggota)
                                    <span class="font-medium text-ink block">{{ $anggota->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted text-[11px]">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <button type="button" 
                                    onclick="showBarcodeModal('{{ $cls->display_code }}', '{{ addslashes($cls->mataKuliah->name) }}', '{{ $cls->enrollment_code }}', '{{ $cls->enrollment_url }}', '{{ route('kelas.qr', $cls->id) }}', '{{ route('kelas.barcode', $cls->id) }}')"
                                    class="button-secondary text-xs py-1 px-2.5 inline-flex items-center"
                                    title="Tampilkan Barcode / QR Code">
                                <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                            </button>
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <span class="text-xs text-ink font-medium"><strong class="font-bold text-ink">{{ $cls->students_count }}</strong><span class="text-muted"> / {{ $cls->capacity ?? '∞' }}</span></span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditKelasModal({{ $cls->id }}, '{{ $cls->section_code }}', {{ $cls->capacity ?? 'null' }}, {{ $cls->dosen_id ?? 'null' }}, {{ json_encode($cls->dosenAnggota->isNotEmpty() ? $cls->dosenAnggota->pluck('id')->values()->all() : ($cls->dosen_pendamping_id ? [(int) $cls->dosen_pendamping_id] : [])) }})"
                                        class="button-secondary text-xs py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.akademik.kelas.destroy', $cls->id) }}" method="POST" data-confirm="Hapus kelas {{ $cls->display_code }}?" data-confirm-title="Hapus kelas" data-confirm-label="Hapus" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-secondary text-xs py-1 px-2.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-line">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <p class="text-xs">Belum ada kelas yang dibuka untuk program studi dan semester ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
</div>

<!-- Modal Buka Kelas Baru -->
<div id="createKelasModal" onclick="if(event.target === this) closeCreateKelasModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Buka Kelas Perkuliahan Baru</h2>
            <button type="button" onclick="closeCreateKelasModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.akademik.kelas.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="create_mk_id" class="block text-xs font-semibold text-ink mb-1">Mata Kuliah</label>
                <select name="mata_kuliah_id" id="create_mk_id" required class="field text-xs font-semibold">
                    <option value="" disabled selected>-- Pilih Mata Kuliah --</option>
                    @foreach($mataKuliahs as $mk)
                        <option value="{{ $mk->id }}">
                            {{ $mk->code }} - {{ $mk->name }} {{ $mk->semester_paket ? '(Sem. ' . $mk->semester_paket . ')' : '' }} ({{ $mk->sks }} SKS)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="create_semester_id" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_id" id="create_semester_id" required class="field text-xs font-semibold">
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ $selectedSemesterId == $sem->id ? 'selected' : '' }}>
                                {{ $sem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create_section_code" class="block text-xs font-semibold text-ink mb-1">Kode Seksi / Kelas (A, B, C)</label>
                    <input type="text" name="section_code" id="create_section_code" required maxlength="10" placeholder="A" class="field text-xs font-semibold uppercase">
                </div>
            </div>

            <div>
                <label for="create_capacity" class="block text-xs font-semibold text-ink mb-1">Kuota Mahasiswa (Kapasitas)</label>
                <input type="number" name="capacity" id="create_capacity" min="1" max="200" value="40" class="field text-xs font-semibold">
            </div>

            <!-- Dosen Ketua & Dosen Anggota -->
            <div class="rounded-xl border border-line bg-canvas/40 p-3.5 space-y-3">
                <div>
                    <label for="create_dosen_id" class="block text-xs font-bold text-ink mb-1">
                        Dosen Ketua (Koordinator Mata Kuliah) 
                    </label>
                    <select name="dosen_id" id="create_dosen_id" class="field text-xs font-semibold" onchange="handleLeadDosenChange('create')">
                        <option value="">Belum Ditentukan (Dosen Bergabung via Kode / Tautan)</option>
                        @foreach($dosens as $dsn)
                            <option value="{{ $dsn->id }}">{{ $dsn->name }} ({{ $dsn->nim_nidn ?? 'NIDN' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 border-t border-line/60 space-y-2">
                    <input type="hidden" name="dosen_anggota_present" value="1">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-ink">
                                Dosen Anggota (Team-Teaching) <span class="text-muted font-normal">(Opsional)</span>
                            </label>                        
                        </div>
                        <button type="button" onclick="addDosenAnggotaRow('create')" class="button-secondary text-xs py-1.5 px-2.5 shrink-0 inline-flex items-center gap-1 font-semibold text-brand hover:text-brand-dark hover:bg-brand/5 border-brand/30 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Tambah Dosen Anggota</span>
                        </button>
                    </div>

                    <div id="create_dosen_anggota_container" class="space-y-2"></div>

                    <div id="create_dosen_anggota_empty" class="rounded-xl border border-dashed border-line bg-white/40 p-3 text-center text-xs text-muted">
                        Belum ada Dosen Anggota. Klik <button type="button" onclick="addDosenAnggotaRow('create')" class="text-brand font-semibold hover:underline inline cursor-pointer">+ Tambah Dosen Anggota</button> jika kelas ini memiliki dosen pendamping.
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Buat Kelas &amp; Generate Barcode</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Kelas -->
<div id="editKelasModal" onclick="if(event.target === this) closeEditKelasModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Data Kelas</h2>
            <button type="button" onclick="closeEditKelasModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editKelasForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="edit_section_code" class="block text-xs font-semibold text-ink mb-1">Kode Seksi</label>
                    <input type="text" name="section_code" id="edit_section_code" required maxlength="10" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="edit_capacity" class="block text-xs font-semibold text-ink mb-1">Kuota Mahasiswa</label>
                    <input type="number" name="capacity" id="edit_capacity" min="1" max="200" class="field text-xs font-semibold">
                </div>
            </div>

            <div class="rounded-xl border border-line bg-canvas/40 p-3.5 space-y-3">
                <div>
                    <label for="edit_dosen_id" class="block text-xs font-bold text-ink mb-1">Dosen Ketua (Koordinator) <span class="text-muted font-normal">(Opsional)</span></label>
                    <select name="dosen_id" id="edit_dosen_id" class="field text-xs font-semibold" onchange="handleLeadDosenChange('edit')">
                        <option value="">Belum Ditentukan (Dosen Bergabung via Kode)</option>
                        @foreach($dosens as $dsn)
                            <option value="{{ $dsn->id }}">{{ $dsn->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 border-t border-line/60 space-y-2">
                    <input type="hidden" name="dosen_anggota_present" value="1">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-ink">
                                Dosen Anggota (Team-Teaching) <span class="text-muted font-normal">(Opsional)</span>
                            </label>
                            <p class="text-[11px] text-muted mt-0.5">Dosen pendamping asistensi atau mitra team-teaching yang turut menilai mahasiswa.</p>
                        </div>
                        <button type="button" onclick="addDosenAnggotaRow('edit')" class="button-secondary text-xs py-1.5 px-2.5 shrink-0 inline-flex items-center gap-1 font-semibold text-brand hover:text-brand-dark hover:bg-brand/5 border-brand/30 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Tambah Dosen Anggota</span>
                        </button>
                    </div>

                    <div id="edit_dosen_anggota_container" class="space-y-2"></div>

                    <div id="edit_dosen_anggota_empty" class="rounded-xl border border-dashed border-line bg-white/40 p-3 text-center text-xs text-muted">
                        Belum ada Dosen Anggota. Klik <button type="button" onclick="addDosenAnggotaRow('edit')" class="text-brand font-semibold hover:underline inline cursor-pointer">+ Tambah Dosen Anggota</button> jika kelas ini memiliki dosen pendamping.
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tampilkan QR Code Kelas -->
<div id="barcodeModal" onclick="if(event.target === this) closeBarcodeModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-line max-h-[90vh] overflow-y-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between px-6 pt-6 pb-4">
            <div>
                <h2 class="text-base font-bold text-ink">QR Code & Akses Kelas</h2>
                <p id="barcode_mk_subtitle" class="text-xs text-muted mt-0.5"></p>
            </div>
            <button type="button" onclick="closeBarcodeModal()"
                    class="text-muted hover:text-ink transition p-1 rounded-lg hover:bg-canvas ml-3 shrink-0"
                    aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- QR Code --}}
        <div class="flex justify-center px-6 pb-4">
            <div class="p-4 bg-white border border-line rounded-2xl shadow-xs inline-flex">
                <img id="qr_image" src="" alt="QR Code Akses Kelas" class="h-44 w-44 object-contain">
            </div>
        </div>

        {{-- Kode Akses Kelas --}}
        <div class="text-center px-6 pb-4">
            <p class="text-[10px] font-bold text-muted uppercase tracking-widest mb-1">Kode Akses Kelas</p>
            <p id="modal_enroll_code_display" class="text-3xl font-bold text-ink tracking-[0.15em] font-mono"></p>
            <p class="mt-3 text-xs text-muted leading-relaxed max-w-[260px] mx-auto">
                Mahasiswa dapat memindai QR Code di atas atau memasukkan kode akses kelas untuk bergabung ke kelas ini.
            </p>
        </div>

        {{-- Hidden inputs for copy --}}
        <input type="hidden" id="modal_enroll_code">
        <input type="hidden" id="modal_enroll_url">

        {{-- Tombol Aksi --}}
        <div class="flex gap-2 px-6 pb-6">
            <button type="button" onclick="copyModalCode(this)" id="btnCopyCode"
                    class="flex-1 button-secondary text-xs py-2.5 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <span id="btnCopyCodeText">Salin Kode</span>
            </button>
            <button type="button" onclick="copyModalUrl(this)" id="btnCopyUrl"
                    class="flex-1 button-primary text-xs py-2.5 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <span id="btnCopyUrlText">Salin Link</span>
            </button>
        </div>
    </div>
</div>


<script>
    const availableDosens = [
        @foreach($dosens as $dsn)
            { id: {{ (int) $dsn->id }}, name: {!! json_encode($dsn->name) !!}, nidn: {!! json_encode($dsn->nim_nidn ?? 'NIDN') !!} },
        @endforeach
    ];

    function syncDosenAnggotaOptions(prefix) {
        const leadSelect = document.getElementById(`${prefix}_dosen_id`);
        const leadId = leadSelect ? String(leadSelect.value || '') : '';
        const container = document.getElementById(`${prefix}_dosen_anggota_container`);
        const emptyState = document.getElementById(`${prefix}_dosen_anggota_empty`);
        if (!container) return;

        const rows = container.querySelectorAll('.dosen-anggota-row');
        if (emptyState) {
            emptyState.style.display = rows.length === 0 ? 'block' : 'none';
        }

        // Pass 1: Validate selected values, clear duplicates and conflicts with Dosen Ketua
        const selectedIds = [];
        rows.forEach(row => {
            const sel = row.querySelector('.dosen-anggota-select');
            if (sel && sel.value) {
                const val = String(sel.value);
                if (val === leadId) {
                    sel.value = '';
                    if (window.saleNotice) {
                        window.saleNotice({
                            title: 'Dosen Sudah Dipilih',
                            message: 'Dosen ini sudah dipilih sebagai Dosen Ketua, tidak dapat dipilih lagi sebagai Dosen Anggota.'
                        });
                    }
                } else if (selectedIds.includes(val)) {
                    sel.value = '';
                    if (window.saleNotice) {
                        window.saleNotice({
                            title: 'Dosen Sudah Dipilih',
                            message: 'Dosen ini sudah dipilih pada baris Dosen Anggota lain.'
                        });
                    }
                } else {
                    selectedIds.push(val);
                }
            }
        });

        // Pass 2: Update options in each select
        rows.forEach(row => {
            const sel = row.querySelector('.dosen-anggota-select');
            if (!sel) return;
            const currentVal = String(sel.value || '');

            Array.from(sel.options).forEach(opt => {
                if (!opt.value) return; // Keep "Pilih Dosen Anggota..." enabled
                const optVal = String(opt.value);

                let baseText = opt.getAttribute('data-base-text');
                if (!baseText) {
                    baseText = opt.textContent.replace(/\s*\((Dosen Ketua|Sudah dipilih)\)/g, '').trim();
                    opt.setAttribute('data-base-text', baseText);
                }

                const isLead = optVal === leadId;
                const isChosenElsewhere = selectedIds.includes(optVal) && optVal !== currentVal;

                opt.disabled = isLead || isChosenElsewhere;

                if (isLead) {
                    opt.textContent = `${baseText} (Dosen Ketua)`;
                } else if (isChosenElsewhere) {
                    opt.textContent = `${baseText} (Sudah dipilih)`;
                } else {
                    opt.textContent = baseText;
                }
            });
        });
    }

    function addDosenAnggotaRow(prefix, selectedValue = '') {
        const leadSelect = document.getElementById(`${prefix}_dosen_id`);
        const leadId = leadSelect ? String(leadSelect.value || '') : '';
        const container = document.getElementById(`${prefix}_dosen_anggota_container`);
        if (!container) return;

        const currentRows = container.querySelectorAll('.dosen-anggota-row');
        const selectedCount = Array.from(currentRows).filter(r => {
            const sel = r.querySelector('.dosen-anggota-select');
            return sel && sel.value;
        }).length;

        const totalUsed = selectedCount + (leadId ? 1 : 0);
        if (!selectedValue && totalUsed >= availableDosens.length && availableDosens.length > 0) {
            if (window.saleNotice) {
                window.saleNotice({
                    title: 'Batas Pilihan Dosen',
                    message: 'Semua dosen yang tersedia sudah dipilih sebagai Dosen Ketua atau Dosen Anggota.'
                });
            }
            return;
        }

        const row = document.createElement('div');
        row.className = 'dosen-anggota-row flex items-center gap-2';

        let optionsHtml = '<option value="">Pilih Dosen Anggota...</option>';
        availableDosens.forEach(d => {
            const label = `${d.name} (${d.nidn})`;
            optionsHtml += `<option value="${d.id}" data-base-text="${label}">${label}</option>`;
        });

        row.innerHTML = `
            <div class="flex-1 min-w-0">
                <select name="dosen_anggota_ids[]" class="dosen-anggota-select field text-xs font-semibold w-full" onchange="syncDosenAnggotaOptions('${prefix}')">
                    ${optionsHtml}
                </select>
            </div>
            <button type="button" onclick="removeDosenAnggotaRow(this, '${prefix}')" class="button-secondary text-xs py-2 px-2.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-line shrink-0 inline-flex items-center gap-1 cursor-pointer" title="Hapus Dosen Anggota">
                <span>Hapus</span>
            </button>
        `;

        container.appendChild(row);

        if (selectedValue) {
            const sel = row.querySelector('.dosen-anggota-select');
            if (sel) sel.value = String(selectedValue);
        }

        syncDosenAnggotaOptions(prefix);
    }

    function removeDosenAnggotaRow(btn, prefix) {
        const row = btn.closest('.dosen-anggota-row');
        if (row) {
            row.remove();
            syncDosenAnggotaOptions(prefix);
        }
    }

    function handleLeadDosenChange(prefix) {
        syncDosenAnggotaOptions(prefix);
    }

    function openCreateKelasModal() {
        const container = document.getElementById('create_dosen_anggota_container');
        if (container) container.innerHTML = '';
        const leadSelect = document.getElementById('create_dosen_id');
        if (leadSelect) leadSelect.value = '';
        syncDosenAnggotaOptions('create');
        document.getElementById('createKelasModal').classList.remove('hidden');
        document.getElementById('createKelasModal').classList.add('flex');
    }
    function closeCreateKelasModal() {
        document.getElementById('createKelasModal').classList.add('hidden');
        document.getElementById('createKelasModal').classList.remove('flex');
    }

    function openEditKelasModal(id, sectionCode, capacity, dosenId, dosenAnggotaIds) {
        const form = document.getElementById('editKelasForm');
        form.action = `/admin-prodi/akademik/kelas/${id}`;
        document.getElementById('edit_section_code').value = sectionCode;
        document.getElementById('edit_capacity').value = capacity || '';
        document.getElementById('edit_dosen_id').value = dosenId || '';

        const container = document.getElementById('edit_dosen_anggota_container');
        if (container) container.innerHTML = '';

        let selectedIds = [];
        if (Array.isArray(dosenAnggotaIds)) {
            selectedIds = dosenAnggotaIds.map(Number).filter(n => n > 0);
        } else if (dosenAnggotaIds) {
            selectedIds = [Number(dosenAnggotaIds)].filter(n => n > 0);
        }

        selectedIds.forEach(dsnId => {
            addDosenAnggotaRow('edit', dsnId);
        });

        syncDosenAnggotaOptions('edit');

        document.getElementById('editKelasModal').classList.remove('hidden');
        document.getElementById('editKelasModal').classList.add('flex');
    }
    function closeEditKelasModal() {
        document.getElementById('editKelasModal').classList.add('hidden');
        document.getElementById('editKelasModal').classList.remove('flex');
    }

    function showBarcodeModal(classCode, mkName, code, url, qrSrc, barcodeSrc) {
        document.getElementById('barcode_mk_subtitle').textContent = mkName + ' (' + classCode + ')';
        document.getElementById('modal_enroll_code').value = code;
        document.getElementById('modal_enroll_url').value = url;
        document.getElementById('modal_enroll_code_display').textContent = code;
        document.getElementById('qr_image').src = qrSrc;

        document.getElementById('barcodeModal').classList.remove('hidden');
        document.getElementById('barcodeModal').classList.add('flex');
    }
    function closeBarcodeModal() {
        document.getElementById('barcodeModal').classList.add('hidden');
        document.getElementById('barcodeModal').classList.remove('flex');
    }

    async function copyTextToClipboard(text) {
        if (!text) return false;

        // Coba modern Clipboard API jika browser mengizinkan & berada di secure context
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                // fall back to execCommand below
            }
        }

        // Fallback untuk HTTP non-secure (misal akses dari HP via IP lokal seperti http://10.70.233.217)
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '0';
            textarea.style.left = '0';
            textarea.style.width = '2em';
            textarea.style.height = '2em';
            textarea.style.padding = '0';
            textarea.style.border = 'none';
            textarea.style.outline = 'none';
            textarea.style.boxShadow = 'none';
            textarea.style.background = 'transparent';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            if (navigator.userAgent.match(/ipad|ipod|iphone/i)) {
                const range = document.createRange();
                range.selectNodeContents(textarea);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                textarea.setSelectionRange(0, 999999);
            } else {
                textarea.focus();
                textarea.select();
            }

            const successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            return successful;
        } catch (err) {
            console.error('Fallback copy error:', err);
            return false;
        }
    }

    async function copyModalCode(btn) {
        const code = document.getElementById('modal_enroll_code').value;
        const targetBtn = btn || document.getElementById('btnCopyCode');
        const textSpan = targetBtn ? (targetBtn.querySelector('span') || targetBtn) : null;
        const originalText = textSpan ? textSpan.textContent : 'Salin Kode';

        const success = await copyTextToClipboard(code);
        if (success && textSpan) {
            textSpan.textContent = 'Tersalin!';
            setTimeout(() => {
                if (textSpan) textSpan.textContent = originalText;
            }, 1800);
        }

        try {
            if (typeof window.saleNotice === 'function') {
                await window.saleNotice({ title: 'Kode kelas tersalin', message: `Kode ${code} sudah disalin ke clipboard.` });
            }
        } catch (e) {}
    }

    async function copyModalUrl(btn) {
        const url = document.getElementById('modal_enroll_url').value;
        const targetBtn = btn || document.getElementById('btnCopyUrl');
        const textSpan = targetBtn ? (targetBtn.querySelector('span') || targetBtn) : null;
        const originalText = textSpan ? textSpan.textContent : 'Salin Link';

        const success = await copyTextToClipboard(url);
        if (success && textSpan) {
            textSpan.textContent = 'Tersalin!';
            setTimeout(() => {
                if (textSpan) textSpan.textContent = originalText;
            }, 1800);
        }

        try {
            if (typeof window.saleNotice === 'function') {
                await window.saleNotice({ title: 'Tautan kelas tersalin', message: 'Tautan bergabung kelas sudah disalin ke clipboard.' });
            }
        } catch (e) {}
    }
</script>
@endsection
