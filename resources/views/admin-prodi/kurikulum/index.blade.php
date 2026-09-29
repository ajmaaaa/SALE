@extends('layouts.mahasiswa')

@section('title', 'Kurikulum OBE (CPL & CPMK) | SALE')
@section('header', 'Kurikulum & Standar Mutu OBE')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-3 pb-1">
        <div class="min-w-0 flex-1">
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin-prodi.dashboard') }}" class="hover:text-brand">Admin Prodi</a>
                <span>/</span>
                <span class="text-ink font-semibold">Kurikulum OBE</span>
            </nav>
            <h1 class="page-heading">Penetapan CPL &amp; CPMK Program Studi</h1>
            <p class="page-description">Tetapkan butir CPL prodi dan CPMK per mata kuliah secara terpusat. Dosen pengampu nantinya tinggal memilih CPMK yang telah disiapkan saat menyusun asesmen kelas.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label for="select-prodi" class="text-xs font-semibold text-muted whitespace-nowrap">Program Studi:</label>
            <select id="select-prodi" onchange="switchProdi(this.value)" class="field text-xs font-semibold w-56 max-w-full">
                @foreach($prodis as $p)
                    <option value="{{ $p->id }}" {{ $activeProdi && $activeProdi->id === $p->id ? 'selected' : '' }}>
                        {{ $p->code }} - {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </header>


    <!-- Tabs Navigation -->
    <div class="flex border-b border-line gap-2">
        <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpl']) }}" 
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'cpl' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            1. Butir CPL Prodi ({{ $cpls->count() }})
        </a>
        <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpmk']) }}" 
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'cpmk' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            2. Butir CPMK per Mata Kuliah ({{ $allCpmks->count() }})
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
                <button type="button" onclick="openCreateCplModal()" class="button-primary text-xs whitespace-nowrap">
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
                <h2 class="text-base font-bold text-ink">Capaian Pembelajaran Mata Kuliah (CPMK)</h2>
                <p class="text-xs text-muted mt-0.5">Kelola butir CPMK per mata kuliah beserta standar kelulusan dan pemetaan CPL terkait.</p>
            </div>
            <div class="flex flex-wrap sm:flex-nowrap items-center justify-end gap-2.5 shrink-0 w-full sm:w-auto">
                <div class="relative w-full sm:w-64 max-w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <input type="text" id="cpmk-search" onkeyup="filterCpmkCards()" placeholder="Cari mata kuliah atau CPMK..." class="field text-xs py-1.5 h-8 w-full" style="padding-left: 2.25rem !important;">
                </div>
                <button type="button" onclick="openCreateCpmkModal()" class="button-primary text-xs h-8 px-3 whitespace-nowrap shrink-0">
                    + Tetapkan CPMK Baru
                </button>
            </div>
        </div>

        <div class="space-y-3 pt-1">
            @forelse($mataKuliahs as $mk)
            <div data-mk-card class="rounded-xl border border-line bg-white shadow-2xs overflow-hidden">
                {{-- Header MK --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 bg-canvas/40 hover:bg-canvas/60 transition-colors border-b border-line">
                    <div onclick="toggleMk({{ $mk->id }})" class="flex items-center gap-3 min-w-0 flex-1 cursor-pointer select-none">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-brand text-white shrink-0">
                            {{ $mk->code }}
                        </span>
                        <span class="font-bold text-sm text-ink truncate">{{ $mk->name }}</span>
                        <span class="text-xs text-muted shrink-0">{{ $mk->sks }} SKS</span>
                        @if($mk->cpmks->count() > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-white border border-line text-ink shadow-2xs shrink-0">
                                {{ $mk->cpmks->count() }} CPMK
                            </span>
                        @else
                            <span class="text-xs text-muted italic shrink-0">0 CPMK</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                            onclick="openCreateCpmkForMk({{ $mk->id }}, '{{ addslashes($mk->name) }}')"
                            class="button-secondary text-[11px] py-1 px-2.5">
                            + Tambah CPMK
                        </button>
                        <button type="button"
                            onclick="toggleMk({{ $mk->id }})"
                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-line/60 bg-white text-muted hover:text-ink hover:bg-canvas transition cursor-pointer shadow-2xs shrink-0"
                            title="Buka / Tutup">
                            <svg id="chevron-{{ $mk->id }}" class="h-4 w-4 transition-transform duration-200 {{ $mk->cpmks->isNotEmpty() ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body MK: Terbuka default jika ada CPMK, atau tertutup jika kosong --}}
                <div id="mk-body-{{ $mk->id }}" class="{{ $mk->cpmks->isEmpty() ? 'hidden' : '' }}">
                    @if($mk->cpmks->isEmpty())
                        <div class="py-6 px-5 text-center text-xs text-muted">
                            <p>Belum ada butir CPMK yang ditetapkan untuk mata kuliah ini.</p>
                            <button type="button"
                                onclick="openCreateCpmkForMk({{ $mk->id }}, '{{ addslashes($mk->name) }}')"
                                class="button-secondary text-xs mt-2">
                                + Tetapkan CPMK untuk {{ $mk->code }}
                            </button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="admin-table w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-line bg-canvas/30 text-muted">
                                        <th class="px-4 py-3 w-32 !align-middle">Kode</th>
                                        <th class="px-4 py-3 !align-middle">Deskripsi CPMK</th>
                                        <th class="px-4 py-3 text-center w-36 !align-middle">Standar Kelulusan</th>
                                        <th class="px-4 py-3 w-40 !align-middle">CPL Terkait</th>
                                        <th class="px-4 py-3 text-right w-32 !align-middle">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/60">
                                    @foreach($mk->cpmks as $cpmk)
                                    <tr class="hover:bg-canvas/30 transition-colors">
                                        <td class="px-4 py-3 !align-middle whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-brand text-white tracking-wide" title="{{ $cpmk->description }}">
                                                {{ $cpmk->code }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-ink leading-relaxed !align-middle">
                                            {{ $cpmk->description }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-semibold text-ink !align-middle whitespace-nowrap">
                                            {{ (float)$cpmk->threshold }}%
                                        </td>
                                        <td class="px-4 py-3 !align-middle">
                                            @if($cpmk->cpls->isEmpty())
                                                <span class="text-muted text-[11px]">Belum dipetakan</span>
                                            @else
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($cpmk->cpls as $cpl)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-brand text-white tracking-wide" title="{{ $cpl->description }}">
                                                            {{ $cpl->code }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right !align-middle whitespace-nowrap">
                                            <div class="inline-flex items-center justify-end gap-1.5">
                                                <button type="button"
                                                        onclick="openEditCpmkModal({{ $cpmk->id }}, '{{ addslashes($cpmk->code) }}', '{{ addslashes($cpmk->description) }}', {{ $cpmk->threshold }})"
                                                        class="button-secondary text-[11px] py-1 px-2.5">
                                                    Ubah
                                                </button>
                                                <form action="{{ route('admin-prodi.kurikulum.cpmk.destroy', $cpmk->id) }}" method="POST" onsubmit="return confirm('Hapus CPMK {{ $cpmk->code }}?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="button-secondary text-[11px] py-1 px-2.5 text-danger hover:bg-danger/10 hover:border-danger/30">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="py-12 text-center text-muted text-sm">
                Belum ada mata kuliah yang terdaftar pada program studi ini. Tambahkan mata kuliah di menu Mata Kuliah terlebih dahulu.
            </div>
            @endforelse
        </div>
    </div>

    @endif
</div>



<!-- Modal Tambah CPL -->
<div id="createCplModal" onclick="if(event.target === this) closeCreateCplModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
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
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
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

<!-- Modal Tambah CPMK -->
<div id="createCpmkModal" onclick="if(event.target === this) closeCreateCpmkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-lg p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Tetapkan Butir CPMK Baru</h2>
            <button type="button" onclick="closeCreateCpmkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.kurikulum.cpmk.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="cpmk_create_mk" class="block text-xs font-semibold text-ink mb-1">Mata Kuliah</label>
                <select name="mata_kuliah_id" id="cpmk_create_mk" required class="field text-xs font-semibold">
                    @foreach($mataKuliahs as $mk)
                        <option value="{{ $mk->id }}">{{ $mk->code }} - {{ $mk->name }} ({{ $mk->sks }} SKS)</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="cpmk_create_code" class="block text-xs font-semibold text-ink mb-1">Kode CPMK (contoh: CPMK-01)</label>
                    <input type="text" name="code" id="cpmk_create_code" required maxlength="20" placeholder="CPMK-01" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="cpmk_create_threshold" class="block text-xs font-semibold text-ink mb-1">Ambang Batas Kelulusan (%)</label>
                    <input type="number" name="threshold" id="cpmk_create_threshold" required min="0" max="100" value="65" class="field text-xs font-semibold">
                </div>
            </div>
            <div>
                <label for="cpmk_create_desc" class="block text-xs font-semibold text-ink mb-1">Deskripsi CPMK</label>
                <textarea name="description" id="cpmk_create_desc" required rows="3" placeholder="Mampu mengimplementasikan struktur data linked list dan tree..." class="field text-xs"></textarea>
            </div>
            @if($cpls->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-ink mb-1.5">Pilih CPL yang Didukung (Opsional):</label>
                <div class="space-y-1.5 max-h-36 overflow-y-auto p-2.5 rounded-lg border border-line bg-canvas/40">
                    @foreach($cpls as $cpl)
                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                        <input type="checkbox" name="cpl_ids[]" value="{{ $cpl->id }}" class="rounded text-brand focus:ring-brand">
                        <span class="font-bold text-ink">{{ $cpl->code }}</span>
                        <span class="text-muted truncate text-[11px]">- {{ $cpl->description }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan &amp; Tetapkan CPMK</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit CPMK -->
<div id="editCpmkModal" onclick="if(event.target === this) closeEditCpmkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Butir CPMK</h2>
            <button type="button" onclick="closeEditCpmkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editCpmkForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="cpmk_edit_code" class="block text-xs font-semibold text-ink mb-1">Kode CPMK</label>
                    <input type="text" name="code" id="cpmk_edit_code" required maxlength="20" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="cpmk_edit_threshold" class="block text-xs font-semibold text-ink mb-1">Threshold (%)</label>
                    <input type="number" name="threshold" id="cpmk_edit_threshold" required min="0" max="100" class="field text-xs font-semibold">
                </div>
            </div>
            <div>
                <label for="cpmk_edit_desc" class="block text-xs font-semibold text-ink mb-1">Deskripsi CPMK</label>
                <textarea name="description" id="cpmk_edit_desc" required rows="4" class="field text-xs"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
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

    function openCreateCpmkModal() {
        document.getElementById('createCpmkModal').classList.remove('hidden');
        document.getElementById('createCpmkModal').classList.add('flex');
    }
    function openCreateCpmkForMk(mkId, mkName) {
        const sel = document.getElementById('cpmk_create_mk');
        if (sel) sel.value = mkId;
        openCreateCpmkModal();
    }
    function closeCreateCpmkModal() {
        document.getElementById('createCpmkModal').classList.add('hidden');
        document.getElementById('createCpmkModal').classList.remove('flex');
    }

    function openEditCpmkModal(id, code, desc, threshold) {
        const form = document.getElementById('editCpmkForm');
        form.action = `/admin-prodi/kurikulum/cpmk/${id}`;
        document.getElementById('cpmk_edit_code').value = code;
        document.getElementById('cpmk_edit_desc').value = desc;
        document.getElementById('cpmk_edit_threshold').value = threshold;
        document.getElementById('editCpmkModal').classList.remove('hidden');
        document.getElementById('editCpmkModal').classList.add('flex');
    }
    function closeEditCpmkModal() {
        document.getElementById('editCpmkModal').classList.add('hidden');
        document.getElementById('editCpmkModal').classList.remove('flex');
    }

    function toggleMk(mkId) {
        const body = document.getElementById(`mk-body-${mkId}`);
        const chevron = document.getElementById(`chevron-${mkId}`);
        if (!body) return;
        const isHidden = body.classList.contains('hidden');
        if (isHidden) {
            body.classList.remove('hidden');
            chevron?.classList.add('rotate-180');
        } else {
            body.classList.add('hidden');
            chevron?.classList.remove('rotate-180');
        }
    }



    function filterCpmkCards() {
        const query = document.getElementById('cpmk-search')?.value.toLowerCase().trim() || '';
        document.querySelectorAll('[data-mk-card]').forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(query) ? '' : 'none';
        });
    }
</script>
@endsection
