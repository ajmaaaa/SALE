@extends('layouts.mahasiswa')

@section('title', 'Manajemen Mata Kuliah | SALE')
@section('header', 'Mata Kuliah Program Studi')

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
                    <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.akademik.matakuliah') }}">
                        Mata Kuliah
                    </a>
                    <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="font-semibold text-slate-800" aria-current="page">
                        {{ $activeProdi->name }}
                    </span>
                @else
                    <span class="font-semibold text-slate-800" aria-current="page">
                        Mata Kuliah
                    </span>
                @endif
            </nav>
            <h1 class="page-heading">Mata Kuliah Program Studi</h1>
            <p class="page-description">Kelola mata kuliah kurikulum, penetapan SKS, dan pembukaan kelas perkuliahan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto sm:ml-auto">
            <button type="button" onclick="openCreateMkModal()" class="button-primary text-xs whitespace-nowrap w-full sm:w-auto justify-center">
                + Tambah Mata Kuliah
            </button>
        </div>
    </header>

@if(! $activeProdi)
    @include('admin-prodi.partials.prodi-selector', [
        'hideHeader' => true,
        'menuTitle' => 'Mata Kuliah',
        'description' => 'Silakan pilih program studi terlebih dahulu untuk mengelola kurikulum dan mata kuliah.',
        'targetRoute' => 'admin-prodi.akademik.matakuliah',
        'actionLabel' => 'Kelola Mata Kuliah',
    ])
@else

    @if(session('notice') || session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs text-emerald-800">
            {{ session('notice') ?? session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-800 space-y-1.5 shadow-xs">
            <div class="font-bold flex items-center gap-2 text-rose-900">
                <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Terjadi kendala saat menyimpan data mata kuliah:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700 pl-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="surface p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-64 max-w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <input type="text" id="mk-search" onkeyup="filterMks()" placeholder="Cari kode atau nama..." class="field text-xs font-medium w-full" style="padding-left: 2.25rem !important;">
                </div>
                <select id="filter-semester-paket" onchange="switchSemesterPaket(this.value)" class="field text-xs font-semibold w-full sm:w-40">
                    <option value="">Semua Semester</option>
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" {{ isset($selectedSemesterPaket) && $selectedSemesterPaket === $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="text-xs text-muted break-words">
                Total terdaftar di <span class="font-semibold text-ink">{{ $activeProdi?->name }}</span>: <strong class="text-ink font-bold" id="mk-count">{{ $mataKuliahs->count() }}</strong> mata kuliah
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 text-center w-14 !align-middle">No</th>
                        <th class="px-4 py-3.5 w-32 !align-middle">Kode MK</th>
                        <th class="px-4 py-3.5 !align-middle">Nama Mata Kuliah</th>
                        <th class="px-4 py-3.5 text-center w-24 !align-middle">Semester</th>
                        <th class="px-4 py-3.5 text-center w-24 !align-middle">Bobot SKS</th>
                        <th class="px-4 py-3.5 text-center w-32 !align-middle">Kelas Terbuka</th>
                        <th class="px-4 py-3.5 text-center w-36 !align-middle">CPMK Terdefinisi</th>
                        <th class="px-4 py-3.5 text-right w-36 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($mataKuliahs as $index => $mk)
                    <tr class="hover:bg-canvas/30 transition-colors mk-row" data-search="{{ strtolower($mk->code . ' ' . $mk->name) }}">
                        <td class="px-4 py-3.5 text-center text-muted font-medium !align-middle">{{ $index + 1 }}</td>
                        <td class="px-4 py-3.5 font-mono font-bold text-brand !align-middle whitespace-nowrap">
                            {{ $mk->code }}
                        </td>
                        <td class="px-4 py-3.5 font-semibold text-ink !align-middle">
                            {{ $mk->name }}
                        </td>
                        <td class="px-4 py-3.5 text-center font-medium text-ink !align-middle whitespace-nowrap">
                            @if($mk->semester_paket)
                                <span class="text-xs font-medium text-ink">Semester {{ $mk->semester_paket }}</span>
                            @else
                                <span class="text-xs text-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center font-medium text-ink !align-middle whitespace-nowrap">
                            {{ $mk->sks }} SKS
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <a href="{{ route('admin-prodi.akademik.kelas', ['prodi_id' => $mk->prodi_id]) }}" class="font-semibold text-ink hover:text-brand hover:underline transition-colors">
                                {{ $mk->class_sections_count }} Kelas
                            </a>
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $mk->prodi_id, 'tab' => 'cpmk']) }}" class="font-semibold text-ink hover:text-brand hover:underline transition-colors">
                                {{ $mk->cpmks_count }} CPMK
                            </a>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <button type="button"
                                        onclick="openEditMkModal({{ $mk->id }}, '{{ addslashes($mk->code) }}', '{{ addslashes($mk->name) }}', {{ $mk->sks }}, {{ $mk->is_lintas_prodi ? 'true' : 'false' }}, {{ $mk->semester_paket ?? 'null' }}, {{ json_encode($mk->cpmk_cpl_pairs) }})"
                                        class="button-secondary text-[11px] py-1 px-2.5">
                                    Ubah
                                </button>
                                <form action="{{ route('admin-prodi.akademik.matakuliah.destroy', $mk->id) }}" method="POST" data-confirm="Hapus mata kuliah {{ $mk->name }}?" data-confirm-title="Hapus mata kuliah" data-confirm-label="Hapus" class="inline">
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
                        <td colspan="8" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="text-xs">Belum ada mata kuliah untuk program studi ini.</p>
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

@php
    $unmappedCpmks = ($cpmks ?? collect())->filter(fn ($m) => $m->cpls->isEmpty());

    $cplCpmkData = ($cpls ?? collect())->map(function($cpl) {
        return [
            'id' => (string) $cpl->id,
            'code' => $cpl->code,
            'description' => $cpl->description,
            'cpmks' => $cpl->cpmks->map(function($m) use ($cpl) {
                return [
                    'id' => (string) $m->id,
                    'code' => $m->code,
                    'threshold' => (float)$m->threshold,
                    'description' => $m->description,
                    'cpls' => $m->cpls->pluck('code')->all() ?: [$cpl->code],
                ];
            })->values(),
        ];
    })->values();

    if ($unmappedCpmks->isNotEmpty()) {
        $cplCpmkData->push([
            'id' => 'unmapped',
            'code' => 'CPMK Lainnya',
            'description' => 'Butir CPMK yang belum dipetakan ke butir CPL manapun',
            'cpmks' => $unmappedCpmks->map(function($m) {
                return [
                    'id' => (string) $m->id,
                    'code' => $m->code,
                    'threshold' => (float)$m->threshold,
                    'description' => $m->description,
                    'cpls' => [],
                ];
            })->values(),
        ]);
    }

    $allCpmkData = ($cpmks ?? collect())->map(function($m) {
        return [
            'id' => (string) $m->id,
            'code' => $m->code,
            'threshold' => (float)$m->threshold,
            'description' => $m->description,
            'cpls' => $m->cpls->pluck('code')->all(),
        ];
    })->keyBy('id');
@endphp

<!-- Modal Tambah MK -->
<div id="createMkModal" onclick="if(event.target === this) closeCreateMkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Tambah Mata Kuliah Baru</h2>
            <button type="button" onclick="closeCreateMkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('admin-prodi.akademik.matakuliah.store') }}" method="POST" class="space-y-4">
            @csrf
            @if($activeProdi)
                <input type="hidden" name="prodi_id" value="{{ $activeProdi->id }}">
            @else
                <div>
                    <label for="mk_create_prodi" class="block text-xs font-semibold text-ink mb-1">Program Studi</label>
                    <select name="prodi_id" id="mk_create_prodi" required class="field text-xs font-semibold">
                        <option value="" disabled selected>-- Pilih Program Studi --</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="grid grid-cols-4 gap-3">
                <div class="col-span-2">
                    <label for="mk_create_code" class="block text-xs font-semibold text-ink mb-1">Kode MK (contoh: IF204)</label>
                    <input type="text" name="code" id="mk_create_code" required maxlength="20" placeholder="IF204" value="{{ old('code') }}" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="mk_create_sks" class="block text-xs font-semibold text-ink mb-1">Bobot SKS</label>
                    <input type="number" name="sks" id="mk_create_sks" required min="1" max="8" value="{{ old('sks', 3) }}" class="field text-xs font-semibold">
                </div>
                <div>
                    <label for="mk_create_sem" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_paket" id="mk_create_sem" class="field text-xs">
                        <option value="">-</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ old('semester_paket') == $i ? 'selected' : '' }}>Sem. {{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label for="mk_create_name" class="block text-xs font-semibold text-ink mb-1">Nama Mata Kuliah</label>
                <input type="text" name="name" id="mk_create_name" required maxlength="150" placeholder="Struktur Data dan Algoritma" value="{{ old('name') }}" class="field text-xs font-semibold">
            </div>
            <label class="flex items-start gap-2 rounded-lg border border-line/70 p-3 text-xs text-ink">
                <input type="hidden" name="is_lintas_prodi" value="0">
                <input type="checkbox" name="is_lintas_prodi" value="1" class="mt-0.5 rounded border-line text-brand">
                <span><strong class="block">Mata kuliah lintas prodi</strong><span class="text-muted">Izinkan penetapan dosen dari program studi lain pada kelas mata kuliah ini.</span></span>
            </label>
            {{-- Pilihan Multiple Choice CPMK: Pilih CPL terlebih dahulu, kemudian muncul dropdown CPMK --}}
            <div class="space-y-2.5 pt-1 border-t border-line/70">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-bold text-ink">
                            Pilih Butir CPMK yang Diampu (Multiple Choice)
                        </label>
                        <p class="text-[11px] text-muted mt-0.5">Centang CPL yang didukung mata kuliah ini untuk menampilkan pilihan butir CPMK.</p>
                    </div>
                    <span id="mk_create_total_count_badge" class="text-[11px] font-semibold text-brand">0 CPMK dipilih</span>
                </div>

                @if(isset($cpls) && $cpls->isNotEmpty())
                    <div class="space-y-2 max-h-60 overflow-y-auto p-1 pr-1.5 [scrollbar-gutter:stable]">
                        @foreach($cpls as $cpl)
                            <div class="rounded-xl border border-line bg-canvas/30 transition-all p-3 space-y-2" id="mk_create_cpl_card_{{ $cpl->id }}">
                                <div class="flex items-start justify-between gap-2.5">
                                    <label class="flex items-start gap-2.5 cursor-pointer flex-1 select-none">
                                        <input type="checkbox"
                                               class="mk-create-cpl-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                               value="{{ $cpl->id }}"
                                               data-cpl-id="{{ $cpl->id }}"
                                               onchange="handleCplToggle(this, 'create')">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono font-bold text-xs text-ink">{{ $cpl->code }}</span>
                                                <span class="text-[11px] text-muted">({{ $cpl->cpmks->count() }} CPMK)</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">{{ $cpl->description }}</p>
                                        </div>
                                    </label>
                                    <span id="mk_create_cpl_badge_{{ $cpl->id }}" class="hidden text-[10px] font-semibold text-brand bg-brand/5 border border-brand/20 px-1.5 py-0.5 rounded shrink-0">
                                        0 dipilih
                                    </span>
                                </div>

                                {{-- Sub-list CPMK --}}
                                <div id="mk_create_cpmk_group_{{ $cpl->id }}" class="hidden pl-6 pt-2 border-t border-line/60 space-y-2">
                                    <div class="flex items-center justify-between text-[11px] text-muted">
                                        <span class="font-semibold text-ink">Pilihan CPMK untuk {{ $cpl->code }}:</span>
                                        @if($cpl->cpmks->isNotEmpty())
                                            <button type="button" onclick="selectAllCpmkInCpl('create', {{ $cpl->id }})" class="text-brand hover:underline font-semibold cursor-pointer">
                                                Pilih Semua
                                            </button>
                                        @endif
                                    </div>
                                    <div class="space-y-1.5">
                                        @forelse($cpl->cpmks as $cpmk)
                                            <label class="flex items-start gap-2.5 p-2 rounded-lg border border-line/70 bg-white hover:bg-canvas/50 cursor-pointer transition text-xs select-none">
                                                <input type="checkbox"
                                                       name="cpmk_cpl_pairs[]"
                                                       value="{{ $cpmk->id }}_{{ $cpl->id }}"
                                                       data-cpmk-id="{{ $cpmk->id }}"
                                                       data-cpl-id="{{ $cpl->id }}"
                                                       class="mk-create-cpmk-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                                       onchange="onCpmkCheckboxClick(this, 'create', {{ $cpl->id }})">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-mono font-bold text-[11px] bg-brand text-white px-1.5 py-0.2 rounded">{{ $cpmk->code }}</span>
                                                        <span class="text-[10px] text-muted font-medium">Standar: {{ $cpmk->threshold }}%</span>
                                                    </div>
                                                    <p class="text-[11px] text-ink mt-0.5 leading-snug">{{ $cpmk->description ?: '-' }}</p>
                                                </div>
                                            </label>
                                        @empty
                                            <p class="text-xs text-muted italic py-1">Belum ada butir CPMK yang dipetakan pada CPL ini.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($unmappedCpmks->isNotEmpty())
                            <div class="rounded-xl border border-line bg-canvas/30 transition-all p-3 space-y-2" id="mk_create_cpl_card_unmapped">
                                <div class="flex items-start justify-between gap-2.5">
                                    <label class="flex items-start gap-2.5 cursor-pointer flex-1 select-none">
                                        <input type="checkbox"
                                               class="mk-create-cpl-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                               value="unmapped"
                                               data-cpl-id="unmapped"
                                               onchange="handleCplToggle(this, 'create')">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono font-bold text-xs text-ink">CPMK Lainnya</span>
                                                <span class="text-[11px] text-muted">({{ $unmappedCpmks->count() }} CPMK Belum Terhubung CPL)</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">Butir CPMK yang belum dipetakan ke butir CPL manapun.</p>
                                        </div>
                                    </label>
                                    <span id="mk_create_cpl_badge_unmapped" class="hidden text-[10px] font-semibold text-brand bg-brand/5 border border-brand/20 px-1.5 py-0.5 rounded shrink-0">
                                        0 dipilih
                                    </span>
                                </div>

                                {{-- Sub-list CPMK --}}
                                <div id="mk_create_cpmk_group_unmapped" class="hidden pl-6 pt-2 border-t border-line/60 space-y-2">
                                    <div class="flex items-center justify-between text-[11px] text-muted">
                                        <span class="font-semibold text-ink">Pilihan CPMK:</span>
                                        <button type="button" onclick="selectAllCpmkInCpl('create', 'unmapped')" class="text-brand hover:underline font-semibold cursor-pointer">
                                            Pilih Semua
                                        </button>
                                    </div>
                                    <div class="space-y-1.5">
                                        @foreach($unmappedCpmks as $cpmk)
                                            <label class="flex items-start gap-2.5 p-2 rounded-lg border border-line/70 bg-white hover:bg-canvas/50 cursor-pointer transition text-xs select-none">
                                                <input type="checkbox"
                                                       name="cpmk_cpl_pairs[]"
                                                       value="{{ $cpmk->id }}_unmapped"
                                                       data-cpmk-id="{{ $cpmk->id }}"
                                                       data-cpl-id="unmapped"
                                                       class="mk-create-cpmk-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                                       onchange="onCpmkCheckboxClick(this, 'create', 'unmapped')">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-mono font-bold text-[11px] bg-brand text-white px-1.5 py-0.2 rounded">{{ $cpmk->code }}</span>
                                                        <span class="text-[10px] text-muted font-medium">Standar: {{ $cpmk->threshold }}%</span>
                                                    </div>
                                                    <p class="text-[11px] text-ink mt-0.5 leading-snug">{{ $cpmk->description ?: '-' }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-3 text-center rounded-lg border border-dashed border-line bg-canvas/30 text-xs text-muted">
                        Belum ada butir CPMK yang dibuat pada prodi ini. Buat CPMK terlebih dahulu di menu <a href="{{ route('admin-prodi.kurikulum.index', ['prodi_id' => $activeProdi?->id, 'tab' => 'cpmk']) }}" class="text-brand underline font-semibold">Kurikulum &gt; CPMK</a>.
                    </div>
                @endif
                <template class="hidden"><input type="hidden" name="cpmk_ids[]" value=""></template>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Mata Kuliah</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit MK -->
<div id="editMkModal" onclick="if(event.target === this) closeEditMkModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl rounded-2xl border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
            <h2 class="text-base font-bold text-ink">Ubah Mata Kuliah</h2>
            <button type="button" onclick="closeEditMkModal()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="editMkForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-4 gap-3">
                <div class="col-span-2">
                    <label for="mk_edit_code" class="block text-xs font-semibold text-ink mb-1">Kode MK</label>
                    <input type="text" name="code" id="mk_edit_code" required maxlength="20" class="field text-xs font-semibold uppercase">
                </div>
                <div>
                    <label for="mk_edit_sks" class="block text-xs font-semibold text-ink mb-1">Bobot SKS</label>
                    <input type="number" name="sks" id="mk_edit_sks" required min="1" max="8" class="field text-xs font-semibold">
                </div>
                <div>
                    <label for="mk_edit_sem" class="block text-xs font-semibold text-ink mb-1">Semester</label>
                    <select name="semester_paket" id="mk_edit_sem" class="field text-xs">
                        <option value="">-</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}">Sem. {{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label for="mk_edit_name" class="block text-xs font-semibold text-ink mb-1">Nama Mata Kuliah</label>
                <input type="text" name="name" id="mk_edit_name" required maxlength="150" class="field text-xs font-semibold">
            </div>
            <label class="flex items-start gap-2 rounded-lg border border-line/70 p-3 text-xs text-ink">
                <input type="hidden" name="is_lintas_prodi" value="0">
                <input type="checkbox" name="is_lintas_prodi" id="mk_edit_lintas" value="1" class="mt-0.5 rounded border-line text-brand">
                <span><strong class="block">Mata kuliah lintas prodi</strong><span class="text-muted">Izinkan penetapan dosen dari program studi lain.</span></span>
            </label>
            {{-- Pilihan Multiple Choice CPMK: Pilih CPL terlebih dahulu, kemudian muncul dropdown CPMK --}}
            <div class="space-y-2.5 pt-1 border-t border-line/70">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-bold text-ink">
                            Pilih Butir CPMK yang Diampu (Multiple Choice)
                        </label>
                        <p class="text-[11px] text-muted mt-0.5">Centang CPL yang didukung mata kuliah ini untuk menampilkan pilihan butir CPMK.</p>
                    </div>
                    <span id="mk_edit_total_count_badge" class="text-[11px] font-semibold text-brand">0 CPMK dipilih</span>
                </div>

                @if(isset($cpls) && $cpls->isNotEmpty())
                    <div class="space-y-2 max-h-60 overflow-y-auto p-1 pr-1.5 [scrollbar-gutter:stable]">
                        @foreach($cpls as $cpl)
                            <div class="rounded-xl border border-line bg-canvas/30 transition-all p-3 space-y-2" id="mk_edit_cpl_card_{{ $cpl->id }}">
                                <div class="flex items-start justify-between gap-2.5">
                                    <label class="flex items-start gap-2.5 cursor-pointer flex-1 select-none">
                                        <input type="checkbox"
                                               class="mk-edit-cpl-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                               value="{{ $cpl->id }}"
                                               data-cpl-id="{{ $cpl->id }}"
                                               onchange="handleCplToggle(this, 'edit')">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono font-bold text-xs text-ink">{{ $cpl->code }}</span>
                                                <span class="text-[11px] text-muted">({{ $cpl->cpmks->count() }} CPMK)</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">{{ $cpl->description }}</p>
                                        </div>
                                    </label>
                                    <span id="mk_edit_cpl_badge_{{ $cpl->id }}" class="hidden text-[10px] font-semibold text-brand bg-brand/5 border border-brand/20 px-1.5 py-0.5 rounded shrink-0">
                                        0 dipilih
                                    </span>
                                </div>

                                {{-- Sub-list CPMK --}}
                                <div id="mk_edit_cpmk_group_{{ $cpl->id }}" class="hidden pl-6 pt-2 border-t border-line/60 space-y-2">
                                    <div class="flex items-center justify-between text-[11px] text-muted">
                                        <span class="font-semibold text-ink">Pilihan CPMK untuk {{ $cpl->code }}:</span>
                                        @if($cpl->cpmks->isNotEmpty())
                                            <button type="button" onclick="selectAllCpmkInCpl('edit', {{ $cpl->id }})" class="text-brand hover:underline font-semibold cursor-pointer">
                                                Pilih Semua
                                            </button>
                                        @endif
                                    </div>
                                    <div class="space-y-1.5">
                                        @forelse($cpl->cpmks as $cpmk)
                                            <label class="flex items-start gap-2.5 p-2 rounded-lg border border-line/70 bg-white hover:bg-canvas/50 cursor-pointer transition text-xs select-none">
                                                <input type="checkbox"
                                                       name="cpmk_cpl_pairs[]"
                                                       value="{{ $cpmk->id }}_{{ $cpl->id }}"
                                                       data-cpmk-id="{{ $cpmk->id }}"
                                                       data-cpl-id="{{ $cpl->id }}"
                                                       class="mk-edit-cpmk-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                                       onchange="onCpmkCheckboxClick(this, 'edit', {{ $cpl->id }})">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-mono font-bold text-[11px] bg-brand text-white px-1.5 py-0.2 rounded">{{ $cpmk->code }}</span>
                                                        <span class="text-[10px] text-muted font-medium">Standar: {{ $cpmk->threshold }}%</span>
                                                    </div>
                                                    <p class="text-[11px] text-ink mt-0.5 leading-snug">{{ $cpmk->description ?: '-' }}</p>
                                                </div>
                                            </label>
                                        @empty
                                            <p class="text-xs text-muted italic py-1">Belum ada butir CPMK yang dipetakan pada CPL ini.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($unmappedCpmks->isNotEmpty())
                            <div class="rounded-xl border border-line bg-canvas/30 transition-all p-3 space-y-2" id="mk_edit_cpl_card_unmapped">
                                <div class="flex items-start justify-between gap-2.5">
                                    <label class="flex items-start gap-2.5 cursor-pointer flex-1 select-none">
                                        <input type="checkbox"
                                               class="mk-edit-cpl-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                               value="unmapped"
                                               data-cpl-id="unmapped"
                                               onchange="handleCplToggle(this, 'edit')">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono font-bold text-xs text-ink">CPMK Lainnya</span>
                                                <span class="text-[11px] text-muted">({{ $unmappedCpmks->count() }} CPMK Belum Terhubung CPL)</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">Butir CPMK yang belum dipetakan ke butir CPL manapun.</p>
                                        </div>
                                    </label>
                                    <span id="mk_edit_cpl_badge_unmapped" class="hidden text-[10px] font-semibold text-brand bg-brand/5 border border-brand/20 px-1.5 py-0.5 rounded shrink-0">
                                        0 dipilih
                                    </span>
                                </div>

                                {{-- Sub-list CPMK --}}
                                <div id="mk_edit_cpmk_group_unmapped" class="hidden pl-6 pt-2 border-t border-line/60 space-y-2">
                                    <div class="flex items-center justify-between text-[11px] text-muted">
                                        <span class="font-semibold text-ink">Pilihan CPMK:</span>
                                        <button type="button" onclick="selectAllCpmkInCpl('edit', 'unmapped')" class="text-brand hover:underline font-semibold cursor-pointer">
                                            Pilih Semua
                                        </button>
                                    </div>
                                    <div class="space-y-1.5">
                                        @foreach($unmappedCpmks as $cpmk)
                                            <label class="flex items-start gap-2.5 p-2 rounded-lg border border-line/70 bg-white hover:bg-canvas/50 cursor-pointer transition text-xs select-none">
                                                <input type="checkbox"
                                                       name="cpmk_cpl_pairs[]"
                                                       value="{{ $cpmk->id }}_unmapped"
                                                       data-cpmk-id="{{ $cpmk->id }}"
                                                       data-cpl-id="unmapped"
                                                       class="mk-edit-cpmk-cb h-4 w-4 mt-0.5 rounded border-line text-brand focus:ring-brand"
                                                       onchange="onCpmkCheckboxClick(this, 'edit', 'unmapped')">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-mono font-bold text-[11px] bg-brand text-white px-1.5 py-0.2 rounded">{{ $cpmk->code }}</span>
                                                        <span class="text-[10px] text-muted font-medium">Standar: {{ $cpmk->threshold }}%</span>
                                                    </div>
                                                    <p class="text-[11px] text-ink mt-0.5 leading-snug">{{ $cpmk->description ?: '-' }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-3 text-center rounded-lg border border-dashed border-line bg-canvas/30 text-xs text-muted">
                        Belum ada data CPL/CPMK pada prodi ini.
                    </div>
                @endif
                <template class="hidden"><input type="hidden" name="cpmk_ids[]" value=""></template>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="submit" class="button-primary text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const cplData = @json($cplCpmkData);
    const allCpmkData = @json($allCpmkData);

    // (selectedCpmks Map tidak lagi digunakan – diganti sistem checkbox langsung)

    // ─── Fungsi untuk sistem checkbox CPL / CPMK ─────────────────────────────

    /**
     * Dipanggil saat checkbox CPL di-klik.
     */
    function handleCplToggle(checkbox, mode) {
        const cplId = String(checkbox.getAttribute('data-cpl-id'));
        const group = document.getElementById(`mk_${mode}_cpmk_group_${cplId}`);
        const card  = document.getElementById(`mk_${mode}_cpl_card_${cplId}`);
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        if (!modal) return;

        if (checkbox.checked) {
            if (group) group.classList.remove('hidden');
            if (card) {
                card.classList.add('border-brand/40', 'bg-brand/5');
                card.classList.remove('bg-canvas/30');
            }
        } else {
            if (group) {
                group.classList.add('hidden');
                // Uncheck hanya CPMK di dalam grup CPL ini saja
                group.querySelectorAll(`input[type="checkbox"].mk-${mode}-cpmk-cb`).forEach(cb => {
                    cb.checked = false;
                });
            }
            if (card) {
                card.classList.remove('border-brand/40', 'bg-brand/5');
                card.classList.add('bg-canvas/30');
            }
        }

        refreshAllCplBadges(mode);
        updateTotalBadge(mode);
    }

    /**
     * Dipanggil saat checkbox CPMK di dalam sub-list di-klik.
     * Opsi 3: Setiap pasangan (CPL, CPMK) mandiri dan independen.
     * CPMK yang sama di CPL lain TIDAK ikut tersinkronisasi / dicentang otomatis.
     */
    function onCpmkCheckboxClick(checkbox, mode, cplId) {
        const isChecked = checkbox.checked;
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Jika CPMK dicentang, pastikan kartu CPL tempat checkbox tersebut berada ikut ditandai aktif jika belum
        if (isChecked && cplId) {
            const cplCb = modal.querySelector(`.mk-${mode}-cpl-cb[data-cpl-id="${cplId}"]`);
            if (cplCb && !cplCb.checked) {
                cplCb.checked = true;
                const card = document.getElementById(`mk_${mode}_cpl_card_${cplId}`);
                if (card) {
                    card.classList.add('border-brand/40', 'bg-brand/5');
                    card.classList.remove('bg-canvas/30');
                }
            }
        }

        refreshAllCplBadges(mode);
        updateTotalBadge(mode);
    }

    /**
     * Update badge jumlah CPMK dipilih per CPL card.
     */
    function refreshAllCplBadges(mode) {
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.querySelectorAll(`.mk-${mode}-cpl-cb`).forEach(cplCb => {
            const cplId = String(cplCb.getAttribute('data-cpl-id'));
            const group = document.getElementById(`mk_${mode}_cpmk_group_${cplId}`);
            const badge = document.getElementById(`mk_${mode}_cpl_badge_${cplId}`);
            if (!group || !badge) return;

            const checkedCount = group.querySelectorAll(`input[type="checkbox"].mk-${mode}-cpmk-cb:checked`).length;
            if (checkedCount > 0) {
                badge.textContent = `${checkedCount} dipilih`;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    /**
     * Update badge total CPMK dipilih (berdasarkan butir CPMK yang unik).
     */
    function updateTotalBadge(mode) {
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const checkedBoxes = modal.querySelectorAll(`input[type="checkbox"].mk-${mode}-cpmk-cb:checked`);
        const uniqueIds = new Set(Array.from(checkedBoxes).map(cb => cb.getAttribute('data-cpmk-id') || String(cb.value).split('_')[0]));
        const badge = document.getElementById(`mk_${mode}_total_count_badge`);
        if (badge) {
            badge.textContent = `${uniqueIds.size} CPMK dipilih`;
        }
    }

    /**
     * Centang semua CPMK dalam satu CPL secara independen (tidak menyentuh CPL lain).
     */
    function selectAllCpmkInCpl(mode, cplId) {
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        const group = document.getElementById(`mk_${mode}_cpmk_group_${cplId}`);
        if (!group || !modal) return;

        const checkboxes = group.querySelectorAll(`input[type="checkbox"].mk-${mode}-cpmk-cb`);
        checkboxes.forEach(cb => {
            cb.checked = true;
        });

        if (cplId) {
            const cplCb = modal.querySelector(`.mk-${mode}-cpl-cb[data-cpl-id="${cplId}"]`);
            if (cplCb) {
                cplCb.checked = true;
                const card = document.getElementById(`mk_${mode}_cpl_card_${cplId}`);
                if (card) {
                    card.classList.add('border-brand/40', 'bg-brand/5');
                    card.classList.remove('bg-canvas/30');
                }
            }
        }

        refreshAllCplBadges(mode);
        updateTotalBadge(mode);
    }

    /**
     * Reset semua CPL & CPMK checkbox di modal.
     */
    function resetCplCpmkCheckboxes(mode) {
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.querySelectorAll(`.mk-${mode}-cpl-cb`).forEach(cb => {
            cb.checked = false;
            const cplId = cb.getAttribute('data-cpl-id');
            const group = document.getElementById(`mk_${mode}_cpmk_group_${cplId}`);
            const card  = document.getElementById(`mk_${mode}_cpl_card_${cplId}`);
            const badge = document.getElementById(`mk_${mode}_cpl_badge_${cplId}`);

            if (group) group.classList.add('hidden');
            if (card) {
                card.classList.remove('border-brand/40', 'bg-brand/5');
                card.classList.add('bg-canvas/30');
            }
            if (badge) badge.classList.add('hidden');
        });

        modal.querySelectorAll(`.mk-${mode}-cpmk-cb`).forEach(cb => {
            cb.checked = false;
        });

        updateTotalBadge(mode);
    }

    /**
     * Pre-tick CPMK checkbox berdasarkan array pasangan yang sudah tersimpan.
     * Mendukung format array of "cpmkId_cplId" dan fallback legacy array of "cpmkId".
     */
    function preselectCpmks(mode, savedPairs) {
        const modalId = mode === 'create' ? 'createMkModal' : 'editMkModal';
        const modal = document.getElementById(modalId);
        if (!modal) return;

        resetCplCpmkCheckboxes(mode);

        if (!Array.isArray(savedPairs) || savedPairs.length === 0) {
            return;
        }

        const pairSet = new Set(savedPairs.map(p => String(p)));

        modal.querySelectorAll(`input[type="checkbox"].mk-${mode}-cpmk-cb`).forEach(cb => {
            const pairVal = String(cb.value);
            const cpmkId = String(cb.getAttribute('data-cpmk-id') || pairVal.split('_')[0]);
            if (pairSet.has(pairVal) || pairSet.has(cpmkId)) {
                cb.checked = true;
            }
        });

        modal.querySelectorAll(`.mk-${mode}-cpl-cb`).forEach(cplCb => {
            const cplId = String(cplCb.getAttribute('data-cpl-id'));
            const group = document.getElementById(`mk_${mode}_cpmk_group_${cplId}`);
            const card  = document.getElementById(`mk_${mode}_cpl_card_${cplId}`);

            const hasChecked = group && group.querySelector(`input[type="checkbox"].mk-${mode}-cpmk-cb:checked`);
            if (hasChecked) {
                cplCb.checked = true;
                if (group) group.classList.remove('hidden');
                if (card) {
                    card.classList.add('border-brand/40', 'bg-brand/5');
                    card.classList.remove('bg-canvas/30');
                }
            }
        });

        refreshAllCplBadges(mode);
        updateTotalBadge(mode);
    }

    function switchProdi(prodiId) {
        window.location.href = `{{ route('admin-prodi.akademik.matakuliah') }}?prodi_id=${prodiId}`;
    }

    function switchSemesterPaket(sem) {
        const url = new URL(window.location.href);
        if (sem) {
            url.searchParams.set('semester_paket', sem);
        } else {
            url.searchParams.delete('semester_paket');
        }
        window.location.href = url.toString();
    }

    function openCreateMkModal() {
        resetCplCpmkCheckboxes('create');
        document.getElementById('createMkModal').classList.remove('hidden');
        document.getElementById('createMkModal').classList.add('flex');
    }
    function closeCreateMkModal() {
        document.getElementById('createMkModal').classList.add('hidden');
        document.getElementById('createMkModal').classList.remove('flex');
    }

    function openEditMkModal(id, code, name, sks, isLintasProdi, semesterPaket, cpmkIds = []) {
        const form = document.getElementById('editMkForm');
        form.action = `/admin-prodi/akademik/matakuliah/${id}`;
        document.getElementById('mk_edit_code').value = code;
        document.getElementById('mk_edit_name').value = name;
        document.getElementById('mk_edit_sks').value = sks;
        document.getElementById('mk_edit_sem').value = semesterPaket || '';
        document.getElementById('mk_edit_lintas').checked = Boolean(isLintasProdi);

        resetCplCpmkCheckboxes('edit');
        preselectCpmks('edit', cpmkIds);

        document.getElementById('editMkModal').classList.remove('hidden');
        document.getElementById('editMkModal').classList.add('flex');
    }
    function closeEditMkModal() {
        document.getElementById('editMkModal').classList.add('hidden');
        document.getElementById('editMkModal').classList.remove('flex');
    }

    function filterMks() {
        const query = document.getElementById('mk-search').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.mk-row');
        let count = 0;
        rows.forEach(r => {
            const match = !query || (r.dataset.search && r.dataset.search.includes(query));
            if (match) {
                r.style.display = '';
                count++;
            } else {
                r.style.display = 'none';
            }
        });
        const label = document.getElementById('mk-count');
        if (label) label.textContent = count;
    }

    @if($errors->any() && (old('code') || old('name')) && !old('_method'))
        document.addEventListener('DOMContentLoaded', function() {
            openCreateMkModal();
            @if(is_array(old('cpmk_cpl_pairs')))
                preselectCpmks('create', @json(old('cpmk_cpl_pairs', [])));
            @elseif(is_array(old('cpmk_ids')))
                preselectCpmks('create', @json(old('cpmk_ids', [])));
            @endif
        });
    @endif
</script>
@endsection
