@extends('layouts.mahasiswa')

@section('title', 'Kurikulum OBE (CPL & CPMK) | SALE')
@section('header', 'Kurikulum & Standar Mutu OBE')

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
                    <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.kurikulum.index') }}">
                        Kurikulum OBE
                    </a>
                    <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="font-semibold text-slate-800" aria-current="page">
                        {{ $activeProdi->name }}
                    </span>
                @else
                    <span class="font-semibold text-slate-800" aria-current="page">
                        Kurikulum OBE
                    </span>
                @endif
            </nav>
            <h1 class="page-heading">Penetapan CPL &amp; CPMK Program Studi</h1>
            <p class="page-description">Kelola butir Capaian Pembelajaran Lulusan (CPL) dan Capaian Pembelajaran Mata Kuliah (CPMK) untuk kurikulum program studi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto sm:ml-auto">
        </div>
    </header>

@if(! $activeProdi)
    @include('admin-prodi.partials.prodi-selector', [
        'hideHeader' => true,
        'menuTitle' => 'Kurikulum OBE',
        'description' => 'Silakan pilih program studi terlebih dahulu untuk menetapkan butir CPL dan CPMK mata kuliah.',
        'targetRoute' => 'admin-prodi.kurikulum.index',
        'targetParams' => ['tab' => $tab ?? 'cpl'],
        'actionLabel' => 'Kelola Kurikulum OBE',
    ])
@else


    <!-- Tabs Navigation -->
    <div class="flex border-b border-line gap-2">
        <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpl']) }}" 
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'cpl' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            1. Butir CPL Prodi ({{ $cpls->count() }})
        </a>
        <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpmk']) }}" 
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'cpmk' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            2. Butir CPMK ({{ $allCpmks->count() }})
        </a>
    </div>

    @if($tab === 'cpl')
    <!-- ================= TAB 1: CPL ================= -->
    <div class="surface p-5 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-ink">Capaian Pembelajaran Lulusan (CPL): {{ $activeProdi?->name }}</h2>
            </div>
            <div class="flex items-center justify-end shrink-0 sm:ml-auto w-full sm:w-auto">
                <button type="button" onclick="openCreateCplModal()" class="button-primary text-xs whitespace-nowrap w-full sm:w-auto justify-center">
                    + Tambah Butir CPL
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-32 !align-middle">Kode CPL</th>
                        <th class="px-4 py-3.5 !align-middle">Deskripsi Capaian Pembelajaran</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">CPMK Terkait</th>
                        <th class="px-4 py-3.5 text-right w-36 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($cpls as $cpl)
                    <tr class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 !align-middle whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-brand text-white tracking-wide">
                                {{ $cpl->code }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 font-medium text-ink leading-relaxed !align-middle">
                            {{ $cpl->description }}
                        </td>
                        <td class="px-4 py-3.5 text-center font-semibold text-ink !align-middle whitespace-nowrap">
                            {{ $cpl->cpmks_count }} CPMK
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditCplModal({{ $cpl->id }}, '{{ addslashes($cpl->code) }}', '{{ addslashes($cpl->description) }}')" 
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.kurikulum.cpl.destroy', $cpl->id) }}" method="POST" onsubmit="return confirm('Hapus butir CPL {{ $cpl->code }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-secondary text-[11px] py-1 px-2.5 text-danger hover:bg-danger/10 hover:border-danger/30">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs">Belum ada butir CPL untuk prodi ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @elseif($tab === 'cpmk')
    <!-- ================= TAB 2: CPMK ================= -->
    <div class="surface p-5 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-line">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-ink">Capaian Pembelajaran Mata Kuliah (CPMK): {{ $activeProdi?->name }}</h2>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 shrink-0 w-full sm:w-auto">
                <div class="relative w-full sm:w-64 max-w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <input type="text" id="cpmk-search" onkeyup="filterCpmkRows()" placeholder="Cari kode atau deskripsi CPMK..." class="field text-xs py-1.5 h-8 w-full" style="padding-left: 2.25rem !important;">
                </div>
                <button type="button" onclick="openCreateCpmkModal()" class="button-primary text-xs h-8 px-3 whitespace-nowrap shrink-0 w-full sm:w-auto justify-center">
                    + Tambah Butir CPMK
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-28 !align-middle">Kode CPMK</th>
                        <th class="px-4 py-3.5 !align-middle">Deskripsi Capaian Pembelajaran</th>
                        <th class="px-4 py-3.5 text-center w-32 !align-middle">Standar Kelulusan</th>
                        <th class="px-4 py-3.5 text-right w-36 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($allCpmks as $cpmk)
                    <tr data-cpmk-row class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 !align-middle whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-brand text-white tracking-wide">
                                {{ $cpmk->code }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 font-medium text-ink leading-relaxed !align-middle">
                            <div>{{ $cpmk->description }}</div>
                        </td>
                        <td class="px-4 py-3.5 text-center font-semibold text-ink !align-middle whitespace-nowrap">
                            {{ (float)$cpmk->threshold }}%
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        onclick="openEditCpmkModal({{ $cpmk->id }}, '{{ addslashes($cpmk->code) }}', '{{ addslashes($cpmk->description) }}', {{ $cpmk->threshold }}, {{ json_encode($cpmk->cpls->pluck('id')) }})" 
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.kurikulum.cpmk.destroy', $cpmk->id) }}" method="POST" onsubmit="return confirm('Hapus butir CPMK {{ $cpmk->code }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button-secondary text-[11px] py-1 px-2.5 text-danger hover:bg-danger/10 hover:border-danger/30">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs">Belum ada butir CPMK untuk prodi ini. Silakan klik tombol "+ Tambah Butir CPMK" untuk menambahkan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @endif
@endif
</div>



<!-- Modal Tambah CPL -->
<div id="createCplModal" onclick="if(event.target === this) closeCreateCplModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Tambah Butir CPL</h2>
            <button type="button" onclick="closeCreateCplModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.kurikulum.cpl.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">
            <div>
                <label for="cpl_create_code" class="block text-xs font-semibold text-ink mb-1">Kode CPL (contoh: CPL-01)</label>
                <input type="text" name="code" id="cpl_create_code" required maxlength="20" placeholder="CPL-01" class="field text-xs font-semibold uppercase">
            </div>
            <div>
                <label for="cpl_create_desc" class="block text-xs font-semibold text-ink mb-1">Deskripsi Capaian Pembelajaran Lulusan</label>
                <textarea name="description" id="cpl_create_desc" required rows="4" placeholder="Mampu merancang dan menerapkan algoritma komputasi..." class="field text-xs"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Butir CPL</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit CPL -->
<div id="editCplModal" onclick="if(event.target === this) closeEditCplModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Butir CPL</h2>
            <button type="button" onclick="closeEditCplModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editCplForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="cpl_edit_code" class="block text-xs font-semibold text-ink mb-1">Kode CPL</label>
                <input type="text" name="code" id="cpl_edit_code" required maxlength="20" class="field text-xs font-semibold uppercase">
            </div>
            <div>
                <label for="cpl_edit_desc" class="block text-xs font-semibold text-ink mb-1">Deskripsi Capaian Pembelajaran Lulusan</label>
                <textarea name="description" id="cpl_edit_desc" required rows="4" class="field text-xs"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah / Tetapkan CPMK -->
<div id="createCpmkModal" onclick="if(event.target === this) closeCreateCpmkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <div>
                <h2 class="text-base font-bold text-ink">Tetapkan Butir CPMK Baru</h2>
                <p id="cpmk_modal_subtitle" class="text-xs text-muted mt-0.5">Penetapan CPMK berbasis CPL untuk kurikulum prodi.</p>
            </div>
            <button type="button" onclick="closeCreateCpmkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.kurikulum.cpmk.store') }}" method="POST" onsubmit="return validateCreateCpmkForm(event)" class="space-y-4">
            @csrf
            <input type="hidden" name="prodi_id" value="{{ $activeProdi?->id }}">

            {{-- Dropdown Select Multiple CPL (CPMK memerlukan CPL yang sudah dibuat) --}}
            <div>
                <label class="block text-xs font-semibold text-ink mb-1">
                    Pilih CPL yang Didukung <span class="text-danger">*</span>
                </label>
                <div id="cplDropdownContainer" class="relative">
                    <button type="button" onclick="toggleCplDropdown(event)" class="field text-xs font-medium flex items-center justify-between w-full text-left cursor-pointer">
                        <span id="cplDropdownSummary" class="truncate text-muted">Pilih satu atau beberapa CPL...</span>
                        <svg class="h-4 w-4 text-muted shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="cplDropdownMenu" class="hidden absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-lg border border-line bg-white p-2 shadow-lg space-y-1">
                        @forelse($cpls as $cpl)
                        <label class="flex items-start gap-2 p-1.5 rounded hover:bg-canvas/60 transition cursor-pointer text-xs">
                            <input type="checkbox" name="cpl_ids[]" value="{{ $cpl->id }}" data-cpl-code="{{ $cpl->code }}" onchange="updateCplSelectedSummary()" class="mt-0.5 rounded text-brand focus:ring-brand">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-ink">{{ $cpl->code }}</span>
                                <span class="text-muted truncate text-[11px] block">- {{ $cpl->description }}</span>
                            </div>
                        </label>
                        @empty
                        <p class="text-xs text-muted p-2 text-center">Belum ada CPL untuk program studi ini. Tambahkan CPL terlebih dahulu.</p>
                        @endforelse
                    </div>
                </div>
                <p class="text-[11px] text-muted mt-1">Pilih CPL kurikulum prodi yang diturunkan menjadi CPMK ini.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="cpmk_create_code" class="block text-xs font-semibold text-ink mb-1">Kode CPMK <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="cpmk_create_code" required maxlength="20" placeholder="CPMK-01" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="cpmk_create_threshold" class="block text-xs font-semibold text-ink mb-1">Ambang Batas Kelulusan (%) <span class="text-danger">*</span></label>
                    <input type="number" name="threshold" id="cpmk_create_threshold" required min="0" max="100" value="65" class="field text-xs font-semibold">
                </div>
            </div>
            <div>
                <label for="cpmk_create_desc" class="block text-xs font-semibold text-ink mb-1">Deskripsi CPMK <span class="text-danger">*</span></label>
                <textarea name="description" id="cpmk_create_desc" required rows="3" placeholder="Tuliskan capaian pembelajaran mata kuliah..." class="field text-xs"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeCreateCpmkModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan &amp; Tetapkan CPMK</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit CPMK -->
<div id="editCpmkModal" onclick="if(event.target === this) closeEditCpmkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <div>
                <h2 class="text-base font-bold text-ink">Ubah Butir CPMK</h2>
                <p class="text-xs text-muted mt-0.5">Perbarui kode, standar kelulusan, deskripsi, dan CPL terkait.</p>
            </div>
            <button type="button" onclick="closeEditCpmkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editCpmkForm" method="POST" onsubmit="return validateEditCpmkForm(event)" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Dropdown Select Multiple CPL --}}
            <div>
                <label class="block text-xs font-semibold text-ink mb-1">
                    Pilih CPL yang Didukung <span class="text-danger">*</span>
                </label>
                <div id="cplEditDropdownContainer" class="relative">
                    <button type="button" onclick="toggleEditCplDropdown(event)" class="field text-xs font-medium flex items-center justify-between w-full text-left cursor-pointer">
                        <span id="cplEditDropdownSummary" class="truncate text-muted">Pilih satu atau beberapa CPL...</span>
                        <svg class="h-4 w-4 text-muted shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="cplEditDropdownMenu" class="hidden absolute left-0 right-0 z-30 mt-1 max-h-48 overflow-y-auto rounded-lg border border-line bg-white p-2 shadow-lg space-y-1">
                        @forelse($cpls as $cpl)
                        <label class="flex items-start gap-2 p-1.5 rounded hover:bg-canvas/60 transition cursor-pointer text-xs">
                            <input type="checkbox" name="cpl_ids[]" value="{{ $cpl->id }}" data-cpl-code="{{ $cpl->code }}" onchange="updateEditCplSelectedSummary()" class="edit-cpl-checkbox mt-0.5 rounded text-brand focus:ring-brand">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-ink">{{ $cpl->code }}</span>
                                <span class="text-muted truncate text-[11px] block">- {{ $cpl->description }}</span>
                            </div>
                        </label>
                        @empty
                        <p class="text-xs text-muted p-2 text-center">Belum ada CPL untuk program studi ini.</p>
                        @endforelse
                    </div>
                </div>
                <p class="text-[11px] text-muted mt-1">Pilih CPL kurikulum prodi yang diturunkan menjadi CPMK ini.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="cpmk_edit_code" class="block text-xs font-semibold text-ink mb-1">Kode CPMK <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="cpmk_edit_code" required maxlength="20" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="cpmk_edit_threshold" class="block text-xs font-semibold text-ink mb-1">Ambang Batas Kelulusan (%) <span class="text-danger">*</span></label>
                    <input type="number" name="threshold" id="cpmk_edit_threshold" required min="0" max="100" class="field text-xs font-semibold">
                </div>
            </div>
            <div>
                <label for="cpmk_edit_desc" class="block text-xs font-semibold text-ink mb-1">Deskripsi CPMK <span class="text-danger">*</span></label>
                <textarea name="description" id="cpmk_edit_desc" required rows="3" class="field text-xs"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeEditCpmkModal()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchProdi(prodiId) {
        window.location.href = `{{ route('admin-prodi.kurikulum.index') }}?prodi_id=${prodiId}&tab={{ $tab }}`;
    }

    function openCreateCplModal() {
        document.getElementById('createCplModal').classList.remove('hidden');
        document.getElementById('createCplModal').classList.add('flex');
    }
    function closeCreateCplModal() {
        document.getElementById('createCplModal').classList.add('hidden');
        document.getElementById('createCplModal').classList.remove('flex');
    }

    function openEditCplModal(id, code, desc) {
        const form = document.getElementById('editCplForm');
        form.action = `/admin-prodi/kurikulum/cpl/${id}`;
        document.getElementById('cpl_edit_code').value = code;
        document.getElementById('cpl_edit_desc').value = desc;
        document.getElementById('editCplModal').classList.remove('hidden');
        document.getElementById('editCplModal').classList.add('flex');
    }
    function closeEditCplModal() {
        document.getElementById('editCplModal').classList.add('hidden');
        document.getElementById('editCplModal').classList.remove('flex');
    }

    function toggleCplDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('cplDropdownMenu');
        if (menu) menu.classList.toggle('hidden');
    }

    function updateCplSelectedSummary() {
        const checked = Array.from(document.querySelectorAll('input[name="cpl_ids[]"]:checked'));
        const summary = document.getElementById('cplDropdownSummary');
        if (!summary) return;
        if (checked.length === 0) {
            summary.textContent = 'Pilih satu atau beberapa CPL...';
            summary.classList.add('text-muted');
            summary.classList.remove('text-ink', 'font-semibold');
        } else {
            const codes = checked.map(cb => cb.getAttribute('data-cpl-code') || cb.value);
            summary.textContent = codes.join(', ') + ` (${checked.length} CPL terpilih)`;
            summary.classList.remove('text-muted');
            summary.classList.add('text-ink', 'font-semibold');
        }
    }

    function resetCreateCpmkModal() {
        document.querySelectorAll('input[name="cpl_ids[]"]').forEach(cb => cb.checked = false);
        updateCplSelectedSummary();
        const cplMenu = document.getElementById('cplDropdownMenu');
        if (cplMenu) cplMenu.classList.add('hidden');

        const codeInput = document.getElementById('cpmk_create_code');
        if (codeInput) codeInput.value = '';
        const thresholdInput = document.getElementById('cpmk_create_threshold');
        if (thresholdInput) thresholdInput.value = '65';
        const descInput = document.getElementById('cpmk_create_desc');
        if (descInput) descInput.value = '';
    }

    function validateCreateCpmkForm(e) {
        const checkedCpls = document.querySelectorAll('input[name="cpl_ids[]"]:checked');
        if (checkedCpls.length === 0) {
            e.preventDefault();
            alert('Silakan pilih minimal satu CPL yang didukung melalui dropdown CPL.');
            const menu = document.getElementById('cplDropdownMenu');
            if (menu) menu.classList.remove('hidden');
            return false;
        }

        const code = document.getElementById('cpmk_create_code')?.value.trim();
        const desc = document.getElementById('cpmk_create_desc')?.value.trim();

        if (!code || !desc) {
            e.preventDefault();
            alert('Kode CPMK dan Deskripsi CPMK wajib diisi.');
            return false;
        }

        return true;
    }

    // Close CPL dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('cplDropdownContainer');
        const menu = document.getElementById('cplDropdownMenu');
        if (container && menu && !container.contains(e.target)) {
            menu.classList.add('hidden');
        }

        const editContainer = document.getElementById('cplEditDropdownContainer');
        const editMenu = document.getElementById('cplEditDropdownMenu');
        if (editContainer && editMenu && !editContainer.contains(e.target)) {
            editMenu.classList.add('hidden');
        }
    });

    function openCreateCpmkModal() {
        resetCreateCpmkModal();
        const subtitle = document.getElementById('cpmk_modal_subtitle');
        if (subtitle) subtitle.textContent = 'Penetapan CPMK berbasis CPL untuk kurikulum prodi.';
        document.getElementById('createCpmkModal').classList.remove('hidden');
        document.getElementById('createCpmkModal').classList.add('flex');
    }

    function closeCreateCpmkModal() {
        document.getElementById('createCpmkModal').classList.add('hidden');
        document.getElementById('createCpmkModal').classList.remove('flex');
    }

    function toggleEditCplDropdown(e) {
        if (e) e.stopPropagation();
        const menu = document.getElementById('cplEditDropdownMenu');
        if (menu) menu.classList.toggle('hidden');
    }

    function updateEditCplSelectedSummary() {
        const checked = Array.from(document.querySelectorAll('.edit-cpl-checkbox:checked'));
        const summary = document.getElementById('cplEditDropdownSummary');
        if (!summary) return;
        if (checked.length === 0) {
            summary.textContent = 'Pilih satu atau beberapa CPL...';
            summary.classList.add('text-muted');
            summary.classList.remove('text-ink', 'font-semibold');
        } else {
            const codes = checked.map(cb => cb.getAttribute('data-cpl-code') || cb.value);
            summary.textContent = codes.join(', ') + ` (${checked.length} CPL terpilih)`;
            summary.classList.remove('text-muted');
            summary.classList.add('text-ink', 'font-semibold');
        }
    }

    function validateEditCpmkForm(e) {
        const checkedCpls = document.querySelectorAll('.edit-cpl-checkbox:checked');
        if (checkedCpls.length === 0) {
            e.preventDefault();
            alert('Silakan pilih minimal satu CPL yang didukung melalui dropdown CPL.');
            const menu = document.getElementById('cplEditDropdownMenu');
            if (menu) menu.classList.remove('hidden');
            return false;
        }

        const code = document.getElementById('cpmk_edit_code')?.value.trim();
        const desc = document.getElementById('cpmk_edit_desc')?.value.trim();

        if (!code || !desc) {
            e.preventDefault();
            alert('Kode CPMK dan Deskripsi CPMK wajib diisi.');
            return false;
        }

        return true;
    }

    function openEditCpmkModal(id, code, desc, threshold, cplIds = []) {
        const form = document.getElementById('editCpmkForm');
        form.action = `/admin-prodi/kurikulum/cpmk/${id}`;
        document.getElementById('cpmk_edit_code').value = code;
        document.getElementById('cpmk_edit_desc').value = desc;
        document.getElementById('cpmk_edit_threshold').value = threshold;

        const assignedIds = Array.isArray(cplIds) ? cplIds.map(Number) : [];
        document.querySelectorAll('.edit-cpl-checkbox').forEach(cb => {
            cb.checked = assignedIds.includes(parseInt(cb.value));
        });
        updateEditCplSelectedSummary();

        const editMenu = document.getElementById('cplEditDropdownMenu');
        if (editMenu) editMenu.classList.add('hidden');

        document.getElementById('editCpmkModal').classList.remove('hidden');
        document.getElementById('editCpmkModal').classList.add('flex');
    }

    function closeEditCpmkModal() {
        document.getElementById('editCpmkModal').classList.add('hidden');
        document.getElementById('editCpmkModal').classList.remove('flex');
    }

    function filterCpmkRows() {
        const query = document.getElementById('cpmk-search')?.value.toLowerCase().trim() || '';
        document.querySelectorAll('[data-cpmk-row]').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }
</script>
@endsection
