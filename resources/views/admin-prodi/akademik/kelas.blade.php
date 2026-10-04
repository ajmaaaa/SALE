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

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-800 space-y-1.5 shadow-xs">
            <div class="font-bold flex items-center gap-2 text-rose-900">
                <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Gagal menyimpan data kelas. Silakan periksa hal berikut:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700 pl-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="surface p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <form id="filterForm" method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <input type="hidden" name="prodi_id" value="{{ $activeProdi->id }}">
            <label for="filter_semester" class="text-xs font-semibold text-muted shrink-0">Semester:</label>
            <select name="semester_id" id="filter_semester" onchange="this.form.submit()" class="field text-xs font-semibold w-full sm:w-48">
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $selectedSemesterId == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="text-xs text-muted break-words">
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
                        <th class="px-4 py-3 text-center w-20 !align-middle">QR</th>
                        <th class="px-4 py-3 text-center w-28 !align-middle">Kapasitas</th>
                        <th class="px-4 py-3 text-right w-64 !align-middle">Aksi</th>
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
                                    onclick="showDashboardQrModal('{{ $cls->display_code }}', '{{ addslashes($cls->mataKuliah->name) }}', '{{ $cls->enrollment_code }}', '{{ $cls->enrollment_url }}', '{{ route('kelas.qr', $cls->id) }}')"
                                    class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-muted hover:text-brand hover:bg-canvas transition cursor-pointer"
                                    title="Tampilkan QR Code">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="3" height="3"/><rect x="19" y="14" width="2" height="2"/><rect x="14" y="19" width="2" height="2"/><rect x="18" y="18" width="3" height="3"/></svg>
                            </button>
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <span class="text-xs text-ink font-medium"><strong class="font-bold text-ink">{{ $cls->students_count }}</strong><span class="text-muted"> / {{ $cls->capacity ?? '∞' }}</span></span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditKelasModal({{ $cls->id }}, '{{ $cls->section_code }}', {{ $cls->capacity ?? 'null' }}, {{ $cls->dosen_id ?? 'null' }}, {{ json_encode($cls->dosenAnggota->isNotEmpty() ? $cls->dosenAnggota->pluck('id')->values()->all() : ($cls->dosen_pendamping_id ? [(int) $cls->dosen_pendamping_id] : [])) }}, {{ $cls->mataKuliah->is_lintas_prodi ? 'true' : 'false' }}, {{ (int) $cls->mataKuliah->prodi_id }})"
                                        class="button-secondary text-xs py-1.5 w-20 inline-flex items-center justify-center text-center">
                                    Ubah
                                </button>
                                @if($cls->isArchived())
                                    <form action="{{ route('admin-prodi.akademik.kelas.unarchive', $cls->id) }}" method="POST" class="inline-block m-0 p-0">
                                        @csrf
                                        <button type="submit" class="button-secondary text-xs py-1.5 w-20 inline-flex items-center justify-center text-center text-ink hover:text-ink hover:bg-canvas border border-line">
                                            Buka Arsip
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin-prodi.akademik.kelas.archive', $cls->id) }}" method="POST"
                                          data-confirm="Arsipkan kelas {{ $cls->display_code }}? Kelas yang diarsipkan tidak menerima peserta baru."
                                          data-confirm-title="Arsipkan Kelas"
                                          data-confirm-label="Arsipkan"
                                          class="inline-block m-0 p-0">
                                        @csrf
                                        <button type="submit" class="button-secondary text-xs py-1.5 w-20 inline-flex items-center justify-center text-center text-ink hover:text-ink hover:bg-canvas border border-line">
                                            Arsipkan
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin-prodi.akademik.kelas.destroy', $cls->id) }}" method="POST" data-confirm="Hapus kelas {{ $cls->display_code }}?" data-confirm-title="Hapus kelas" data-confirm-label="Hapus" class="inline-block m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-secondary text-xs py-1.5 w-20 inline-flex items-center justify-center text-center text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-line">
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
        @if($errors->any() && (old('mata_kuliah_id') || old('section_code')))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50/95 p-3 text-xs text-rose-800 space-y-1">
                <div class="font-bold text-rose-900 flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Gagal Menyimpan Kelas:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-rose-700 pl-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('admin-prodi.akademik.kelas.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="create_mk_id" class="block text-xs font-semibold text-ink mb-1">Mata Kuliah</label>
                <select name="mata_kuliah_id" id="create_mk_id" required class="field text-xs font-semibold" onchange="handleCreateMkChange()">
                    <option value="" disabled {{ old('mata_kuliah_id') ? '' : 'selected' }}>-- Pilih Mata Kuliah --</option>
                    @foreach($mataKuliahs as $mk)
                        <option value="{{ $mk->id }}"
                                data-is-lintas-prodi="{{ $mk->is_lintas_prodi ? '1' : '0' }}"
                                data-prodi-id="{{ $mk->prodi_id }}"
                                {{ (string) old('mata_kuliah_id') === (string) $mk->id ? 'selected' : '' }}>
                            {{ $mk->code }} - {{ $mk->name }} {{ $mk->semester_paket ? '(Sem. ' . $mk->semester_paket . ')' : '' }} ({{ $mk->sks }} SKS){{ $mk->is_lintas_prodi ? ' [Lintas Prodi]' : '' }}
                        </option>
                    @endforeach
                </select>
                <p id="create_mk_hint" class="text-[11px] text-muted mt-1 hidden"></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="create_semester_id" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_id" id="create_semester_id" required class="field text-xs font-semibold" onchange="suggestNextSectionCode()">
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ (string) old('semester_id', $selectedSemesterId) === (string) $sem->id ? 'selected' : '' }}>
                                {{ $sem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create_section_code" class="block text-xs font-semibold text-ink mb-1">Kode Seksi / Kelas (A, B, C)</label>
                    <input type="text" name="section_code" id="create_section_code" required maxlength="10" value="{{ old('section_code', 'A') }}" placeholder="A" class="field text-xs font-semibold uppercase">
                </div>
            </div>

            <div>
                <label for="create_capacity" class="block text-xs font-semibold text-ink mb-1">Kuota Mahasiswa (Kapasitas)</label>
                <input type="number" name="capacity" id="create_capacity" min="1" max="200" value="{{ old('capacity', '40') }}" class="field text-xs font-semibold">
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
                            @php
                                $dsnProdiCode = $dsn->prodi ? ($dsn->prodi->code ?? $dsn->prodi->name) : '';
                                $fullDsnLabel = $dsn->name . ' (' . ($dsn->nim_nidn ?? 'NIDN') . ')' . ($dsnProdiCode ? ' - ' . $dsnProdiCode : '');
                            @endphp
                            <option value="{{ $dsn->id }}"
                                    data-prodi-id="{{ $dsn->prodi_id }}"
                                    data-base-label="{{ $fullDsnLabel }}"
                                    {{ (string) old('dosen_id') === (string) $dsn->id ? 'selected' : '' }}>
                                {{ $fullDsnLabel }}
                            </option>
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
                            @php
                                $dsnProdiCode = $dsn->prodi ? ($dsn->prodi->code ?? $dsn->prodi->name) : '';
                                $fullDsnLabel = $dsn->name . ' (' . ($dsn->nim_nidn ?? 'NIDN') . ')' . ($dsnProdiCode ? ' - ' . $dsnProdiCode : '');
                            @endphp
                            <option value="{{ $dsn->id }}"
                                    data-prodi-id="{{ $dsn->prodi_id }}"
                                    data-base-label="{{ $fullDsnLabel }}">
                                {{ $fullDsnLabel }}
                            </option>
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

{{-- Modal QR Code Kelas --}}
<div id="dashboardQrModal" onclick="if(event.target === this) closeDashboardQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between px-6 pt-6 pb-4">
            <div>
                <h2 class="text-base font-bold text-ink">QR Code &amp; Akses Kelas</h2>
                <p id="dash_qr_subtitle" class="text-xs text-muted mt-0.5"></p>
            </div>
            <button type="button" onclick="closeDashboardQrModal()"
                    class="text-muted hover:text-ink transition p-1 rounded-lg hover:bg-canvas ml-3 shrink-0"
                    aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="flex justify-center px-6 pb-4">
            <div class="p-4 bg-white border border-line rounded-2xl shadow-xs inline-flex">
                <img id="dash_qr_image" src="" alt="QR Code Akses Kelas" class="h-44 w-44 object-contain">
            </div>
        </div>
        <div class="text-center px-6 pb-4">
            <p class="text-[10px] font-bold text-muted uppercase tracking-widest mb-1">Kode Akses Kelas</p>
            <p id="dash_qr_code_display" class="text-3xl font-bold text-ink tracking-[0.15em] font-mono"></p>
            <p class="mt-3 text-xs text-muted leading-relaxed max-w-[260px] mx-auto">
                Mahasiswa dapat memindai QR Code di atas atau memasukkan kode akses kelas untuk bergabung ke kelas ini.
            </p>
        </div>
        <input type="hidden" id="dash_qr_code">
        <input type="hidden" id="dash_qr_url">
        <div class="flex gap-2 px-6 pb-6">
            <button type="button" onclick="dashCopyCode(this)" id="dashBtnCopyCode"
                    class="flex-1 button-secondary text-xs py-2.5 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <span>Salin Kode</span>
            </button>
            <button type="button" onclick="dashCopyUrl(this)" id="dashBtnCopyUrl"
                    class="flex-1 button-primary text-xs py-2.5 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>Salin Link</span>
            </button>
        </div>
    </div>
</div>


<script>
    const activeProdiId = {{ $activeProdi ? (int) $activeProdi->id : 'null' }};
    const availableDosens = [
        @foreach($dosens as $dsn)
            {
                id: {{ (int) $dsn->id }},
                name: {!! json_encode($dsn->name) !!},
                nidn: {!! json_encode($dsn->nim_nidn ?? 'NIDN') !!},
                prodi_id: {{ $dsn->prodi_id ? (int) $dsn->prodi_id : 'null' }},
                prodi_code: {!! json_encode($dsn->prodi?->code ?? $dsn->prodi?->name ?? '') !!},
                label: {!! json_encode($dsn->name . ' (' . ($dsn->nim_nidn ?? 'NIDN') . ')' . ($dsn->prodi ? ' - ' . ($dsn->prodi->code ?? $dsn->prodi->name) : '')) !!}
            },
        @endforeach
    ];

    const existingClassesData = [
        @if(isset($existingSections))
            @foreach($existingSections as $sec)
                {
                    mk_id: {{ (int) $sec->mata_kuliah_id }},
                    sem_id: {{ (int) $sec->semester_id }},
                    code: {!! json_encode(strtoupper(trim($sec->section_code))) !!}
                },
            @endforeach
        @endif
    ];

    window.editClassIsLintasProdi = false;
    window.editClassProdiId = null;

    function getSelectedMkContext(prefix) {
        if (prefix === 'create') {
            const mkSelect = document.getElementById('create_mk_id');
            const opt = mkSelect && mkSelect.selectedIndex >= 0 ? mkSelect.options[mkSelect.selectedIndex] : null;
            if (opt && opt.value) {
                return {
                    isLintasProdi: opt.getAttribute('data-is-lintas-prodi') === '1',
                    prodiId: opt.getAttribute('data-prodi-id') ? parseInt(opt.getAttribute('data-prodi-id'), 10) : activeProdiId
                };
            }
            return { isLintasProdi: false, prodiId: activeProdiId };
        } else if (prefix === 'edit') {
            return {
                isLintasProdi: window.editClassIsLintasProdi === true,
                prodiId: window.editClassProdiId ? parseInt(window.editClassProdiId, 10) : activeProdiId
            };
        }
        return { isLintasProdi: true, prodiId: null };
    }

    function suggestNextSectionCode() {
        const mkSelect = document.getElementById('create_mk_id');
        const semSelect = document.getElementById('create_semester_id');
        const sectionInput = document.getElementById('create_section_code');
        if (!mkSelect || !semSelect || !sectionInput) return;

        const mkId = parseInt(mkSelect.value, 10);
        const semId = parseInt(semSelect.value, 10);
        if (!mkId || !semId) return;

        const usedCodes = existingClassesData
            .filter(c => c.mk_id === mkId && c.sem_id === semId)
            .map(c => c.code.toUpperCase());

        let nextCode = 'A';
        for (let charCode = 65; charCode <= 90; charCode++) {
            const letter = String.fromCharCode(charCode);
            if (!usedCodes.includes(letter)) {
                nextCode = letter;
                break;
            }
        }

        const currentVal = (sectionInput.value || '').trim().toUpperCase();
        if (!currentVal || usedCodes.includes(currentVal)) {
            sectionInput.value = nextCode;
        }
    }

    function handleCreateMkChange() {
        const mkSelect = document.getElementById('create_mk_id');
        const selectedOption = mkSelect && mkSelect.selectedIndex >= 0 ? mkSelect.options[mkSelect.selectedIndex] : null;
        const hintEl = document.getElementById('create_mk_hint');

        if (!selectedOption || !selectedOption.value) {
            if (hintEl) hintEl.classList.add('hidden');
            return;
        }

        const isLintasProdi = selectedOption.getAttribute('data-is-lintas-prodi') === '1';
        const mkProdiId = selectedOption.getAttribute('data-prodi-id') ? parseInt(selectedOption.getAttribute('data-prodi-id'), 10) : activeProdiId;

        if (hintEl) {
            if (isLintasProdi) {
                hintEl.innerHTML = '<span class="text-blue-600 font-medium">✓ Mata Kuliah Lintas Prodi:</span> Bebas memilih dosen pengampu dari prodi manapun.';
                hintEl.classList.remove('hidden');
            } else {
                hintEl.innerHTML = '<span class="text-slate-600 font-medium">ℹ Mata Kuliah Reguler Prodi:</span> Hanya dapat diampu oleh dosen dari program studi ini.';
                hintEl.classList.remove('hidden');
            }
        }

        // Filter Dosen Ketua
        const leadSelect = document.getElementById('create_dosen_id');
        if (leadSelect) {
            Array.from(leadSelect.options).forEach(opt => {
                if (!opt.value) return;
                const dsnProdiId = opt.getAttribute('data-prodi-id') ? parseInt(opt.getAttribute('data-prodi-id'), 10) : null;
                const isForeign = dsnProdiId && mkProdiId && dsnProdiId !== mkProdiId;
                const baseLabel = opt.getAttribute('data-base-label') || opt.textContent.replace(/\s*\(Bukan Prodi Ini\)/g, '').trim();

                if (!isLintasProdi && isForeign) {
                    opt.disabled = true;
                    opt.textContent = `${baseLabel} (Bukan Prodi Ini)`;
                    if (String(leadSelect.value) === String(opt.value)) {
                        leadSelect.value = '';
                    }
                } else {
                    opt.disabled = false;
                    opt.textContent = baseLabel;
                }
            });
        }

        suggestNextSectionCode();
        syncDosenAnggotaOptions('create');
    }

    function syncDosenAnggotaOptions(prefix) {
        const leadSelect = document.getElementById(`${prefix}_dosen_id`);
        const leadId = leadSelect ? String(leadSelect.value || '') : '';
        const container = document.getElementById(`${prefix}_dosen_anggota_container`);
        const emptyState = document.getElementById(`${prefix}_dosen_anggota_empty`);
        if (!container) return;

        const mkContext = getSelectedMkContext(prefix);

        const rows = container.querySelectorAll('.dosen-anggota-row');
        if (emptyState) {
            emptyState.style.display = rows.length === 0 ? 'block' : 'none';
        }

        // Pass 1: Validate selected values, clear duplicates and conflicts with Dosen Ketua or foreign prodi
        const selectedIds = [];
        rows.forEach(row => {
            const sel = row.querySelector('.dosen-anggota-select');
            if (sel && sel.value) {
                const val = String(sel.value);
                const dsn = availableDosens.find(d => String(d.id) === val);
                const isForeign = !mkContext.isLintasProdi && dsn && dsn.prodi_id && mkContext.prodiId && dsn.prodi_id !== mkContext.prodiId;

                if (isForeign) {
                    sel.value = '';
                    if (window.saleNotice) {
                        window.saleNotice({
                            title: 'Dosen Tidak Memenuhi Syarat',
                            message: 'Dosen dari prodi lain hanya dapat dipilih untuk mata kuliah yang ditandai Lintas Prodi.'
                        });
                    }
                } else if (val === leadId) {
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
                    baseText = opt.textContent.replace(/\s*\((Dosen Ketua|Sudah dipilih|Bukan Prodi Ini)\)/g, '').trim();
                    opt.setAttribute('data-base-text', baseText);
                }

                const dsn = availableDosens.find(d => String(d.id) === optVal);
                const isForeign = !mkContext.isLintasProdi && dsn && dsn.prodi_id && mkContext.prodiId && dsn.prodi_id !== mkContext.prodiId;
                const isLead = optVal === leadId;
                const isChosenElsewhere = selectedIds.includes(optVal) && optVal !== currentVal;

                opt.disabled = isForeign || isLead || isChosenElsewhere;

                if (isForeign) {
                    opt.textContent = `${baseText} (Bukan Prodi Ini)`;
                } else if (isLead) {
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

        const mkContext = getSelectedMkContext(prefix);
        const eligibleDosens = availableDosens.filter(d => {
            if (mkContext.isLintasProdi) return true;
            return !d.prodi_id || !mkContext.prodiId || d.prodi_id === mkContext.prodiId;
        });

        const currentRows = container.querySelectorAll('.dosen-anggota-row');
        const selectedCount = Array.from(currentRows).filter(r => {
            const sel = r.querySelector('.dosen-anggota-select');
            return sel && sel.value;
        }).length;

        const totalUsed = selectedCount + (leadId ? 1 : 0);
        if (!selectedValue && totalUsed >= eligibleDosens.length && eligibleDosens.length > 0) {
            if (window.saleNotice) {
                window.saleNotice({
                    title: 'Batas Pilihan Dosen',
                    message: 'Semua dosen yang memenuhi syarat sudah dipilih sebagai Dosen Ketua atau Dosen Anggota.'
                });
            }
            return;
        }

        const row = document.createElement('div');
        row.className = 'dosen-anggota-row flex items-center gap-2';

        let optionsHtml = '<option value="">Pilih Dosen Anggota...</option>';
        availableDosens.forEach(d => {
            optionsHtml += `<option value="${d.id}" data-base-text="${d.label}">${d.label}</option>`;
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
        if (leadSelect && !leadSelect.value) leadSelect.value = '';
        handleCreateMkChange();
        document.getElementById('createKelasModal').classList.remove('hidden');
        document.getElementById('createKelasModal').classList.add('flex');
    }
    function closeCreateKelasModal() {
        document.getElementById('createKelasModal').classList.add('hidden');
        document.getElementById('createKelasModal').classList.remove('flex');
    }

    function openEditKelasModal(id, sectionCode, capacity, dosenId, dosenAnggotaIds, isLintasProdi = false, prodiId = null) {
        window.editClassIsLintasProdi = !!isLintasProdi;
        window.editClassProdiId = prodiId;
        const form = document.getElementById('editKelasForm');
        form.action = `/admin-prodi/akademik/kelas/${id}`;
        document.getElementById('edit_section_code').value = sectionCode;
        document.getElementById('edit_capacity').value = capacity || '';

        const leadSelect = document.getElementById('edit_dosen_id');
        if (leadSelect) {
            Array.from(leadSelect.options).forEach(opt => {
                if (!opt.value) return;
                const dsnProdiId = opt.getAttribute('data-prodi-id') ? parseInt(opt.getAttribute('data-prodi-id'), 10) : null;
                const isForeign = !isLintasProdi && dsnProdiId && prodiId && dsnProdiId !== prodiId;
                const baseLabel = opt.getAttribute('data-base-label') || opt.textContent.replace(/\s*\(Bukan Prodi Ini\)/g, '').trim();

                if (isForeign) {
                    opt.disabled = true;
                    opt.textContent = `${baseLabel} (Bukan Prodi Ini)`;
                } else {
                    opt.disabled = false;
                    opt.textContent = baseLabel;
                }
            });
            leadSelect.value = dosenId || '';
        }

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

    @if($errors->any() && (old('mata_kuliah_id') || old('section_code')))
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('createKelasModal').classList.remove('hidden');
            document.getElementById('createKelasModal').classList.add('flex');
            @if(old('dosen_anggota_ids'))
                @foreach((array) old('dosen_anggota_ids') as $oldAnggotaId)
                    @if($oldAnggotaId)
                        addDosenAnggotaRow('create', '{{ $oldAnggotaId }}');
                    @endif
                @endforeach
            @endif
            handleCreateMkChange();
        });
    @endif

    function showDashboardQrModal(classCode, mkName, code, url, qrSrc) {
        document.getElementById('dash_qr_subtitle').textContent = mkName + ' (' + classCode + ')';
        document.getElementById('dash_qr_code_display').textContent = code;
        document.getElementById('dash_qr_code').value = code;
        document.getElementById('dash_qr_url').value = url;
        document.getElementById('dash_qr_image').src = qrSrc;
        document.getElementById('dashboardQrModal').classList.remove('hidden');
        document.getElementById('dashboardQrModal').classList.add('flex');
    }
    function closeDashboardQrModal() {
        document.getElementById('dashboardQrModal').classList.add('hidden');
        document.getElementById('dashboardQrModal').classList.remove('flex');
    }

    async function dashCopyTextToClipboard(text) {
        if (!text) return false;

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {}
        }

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

    async function dashCopyCode(btn) {
        const code = document.getElementById('dash_qr_code').value;
        const targetBtn = btn || document.getElementById('dashBtnCopyCode');
        const textSpan = targetBtn ? (targetBtn.querySelector('span') || targetBtn) : null;
        const originalText = textSpan ? textSpan.textContent : 'Salin Kode';

        const success = await dashCopyTextToClipboard(code);
        if (success && textSpan) {
            textSpan.textContent = 'Tersalin!';
            setTimeout(() => {
                if (textSpan) textSpan.textContent = originalText;
            }, 1800);
        }
    }

    async function dashCopyUrl(btn) {
        const url = document.getElementById('dash_qr_url').value;
        const targetBtn = btn || document.getElementById('dashBtnCopyUrl');
        const textSpan = targetBtn ? (targetBtn.querySelector('span') || targetBtn) : null;
        const originalText = textSpan ? textSpan.textContent : 'Salin Link';

        const success = await dashCopyTextToClipboard(url);
        if (success && textSpan) {
            textSpan.textContent = 'Tersalin!';
            setTimeout(() => {
                if (textSpan) textSpan.textContent = originalText;
            }, 1800);
        }
    }

    // Backward-compatibility aliases
    function showBarcodeModal(classCode, mkName, code, url, qrSrc, barcodeSrc) {
        showDashboardQrModal(classCode, mkName, code, url, qrSrc);
    }
    function closeBarcodeModal() {
        closeDashboardQrModal();
    }
</script>
@endsection
