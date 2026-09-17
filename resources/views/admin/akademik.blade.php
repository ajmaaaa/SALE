@extends('layouts.mahasiswa')

@section('title', 'Data Akademik | SALE')
@section('header', 'Data Akademik')

@section('content')
@php
    $existingFakultas = collect($academic)->firstWhere('type', 'fakultas');
    $hasFakultas = !empty($existingFakultas);
    $isEditingFakultas = ($record['type'] ?? '') === 'fakultas';
    $adminAcademic = $visibleAcademic;
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-heading">Data Akademik</h1>
            <p class="page-description">Kelola struktur institusi: fakultas (maksimal 1), program studi, dan semester / tahun ajaran.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="post" action="{{ route('admin.academic.reset') }}" onsubmit="return confirm('Kembalikan data akademik ke konfigurasi default institusi?');">
                @csrf
                <button type="submit" class="button-secondary text-xs">
                    Reset Data Default
                </button>
            </form>
            <a class="button-primary text-xs inline-flex items-center gap-1.5" href="{{ route('admin.page', 'akademik') }}?create=1">
                + Tambah Data
            </a>
        </div>
    </header>

    {{-- Banner Batasan 1 Fakultas --}}
    @if($hasFakultas)
        <div class="rounded-xl border border-line/70 bg-canvas/60 p-4 text-xs text-muted flex items-start justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand font-bold">1</span>
                <div>
                    <span class="font-semibold text-ink">Fakultas Terdaftar: {{ $existingFakultas['name'] }} ({{ $existingFakultas['code'] }})</span>
                    <p class="mt-0.5 text-muted">Sistem institusi saat ini dibatasi maksimal 1 fakultas. Anda dapat mengubah data fakultas melalui tombol Edit atau menghapusnya jika ingin mengganti.</p>
                </div>
            </div>
            <a href="{{ route('admin.page', 'akademik') }}?edit={{ $existingFakultas['id'] }}" class="text-xs font-semibold text-brand hover:text-brand-dark shrink-0">
                Edit Fakultas →
            </a>
        </div>
    @endif

    {{-- Form Tambah / Edit Data Akademik --}}
    @if(request('create') || $record || $errors->any())
        <form method="post" action="{{ route('admin.academic.store') }}" class="surface space-y-5 p-6 border border-line/60">
            @csrf
            @if($record)
                <input type="hidden" name="id" value="{{ $record['id'] }}">
            @endif

            <div class="flex items-center justify-between border-b border-line/50 pb-3">
                <h2 class="section-heading text-base">{{ $record ? 'Edit Data Akademik' : 'Tambah Data Akademik Baru' }}</h2>
                <a class="text-xs text-muted hover:text-ink" href="{{ route('admin.page', 'akademik') }}">Batal</a>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                {{-- Jenis Data --}}
                <div>
                    <label class="form-label" for="type">Jenis Data</label>
                    <select name="type" id="type" class="field" onchange="toggleAcademicFields(this.value)">
                        @if(!$hasFakultas || $isEditingFakultas)
                            <option value="fakultas" @selected(old('type', $record['type'] ?? '') === 'fakultas')>Fakultas</option>
                        @else
                            <option value="fakultas" disabled>Fakultas (Maks. 1 fakultas - sudah terdaftar)</option>
                        @endif
                        <option value="prodi" @selected(old('type', $record['type'] ?? '') === 'prodi')>Program Studi (Prodi)</option>
                        <option value="semester" @selected(old('type', $record['type'] ?? '') === 'semester')>Semester / Tahun Ajaran</option>
                    </select>
                    @if($hasFakultas && !$isEditingFakultas)
                        <p class="mt-1 text-[11px] text-muted">Opsi fakultas dinonaktifkan karena batas 1 fakultas telah terisi.</p>
                    @endif
                </div>

                {{-- Kode Unik --}}
                <div>
                    <label class="form-label" for="code">Kode Unik</label>
                    <input class="field" name="code" id="code" required placeholder="Contoh: FIK, IF, atau 2026-1" value="{{ old('code', $record['code'] ?? '') }}">
                </div>

                {{-- Nama --}}
                <div>
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input class="field" name="name" id="name" required placeholder="Contoh: Fakultas Ilmu Komputer, Teknik Informatika" value="{{ old('name', $record['name'] ?? '') }}">
                </div>

                {{-- Status --}}
                <div>
                    <label class="form-label" for="status">Status</label>
                    <select class="field" id="status" name="status">
                        <option value="aktif" @selected(old('status', $record['status'] ?? 'aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status', $record['status'] ?? '') === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>

                {{-- Induk Fakultas (khusus Program Studi) --}}
                <div id="parent-field-container" class="{{ old('type', $record['type'] ?? 'prodi') === 'prodi' ? '' : 'hidden' }} md:col-span-2">
                    <label class="form-label" for="parent">Induk Fakultas</label>
                    <select class="field" name="parent" id="parent">
                        <option value="">Pilih Fakultas Induk</option>
                        @foreach($academic as $entry)
                            @if($entry['type'] === 'fakultas' && $entry['id'] !== ($record['id'] ?? null))
                                <option value="{{ $entry['id'] }}" @selected(old('parent', $record['parent'] ?? '') == $entry['id'])>
                                    {{ $entry['name'] }} ({{ $entry['code'] }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-muted">Setiap program studi harus bernaung di bawah fakultas resmi institusi.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="button-primary">Simpan Data</button>
                <a class="button-secondary" href="{{ route('admin.page', 'akademik') }}">Batal</a>
            </div>
        </form>

        <script>
            function toggleAcademicFields(type) {
                var parentContainer = document.getElementById('parent-field-container');
                if (type === 'prodi') {
                    parentContainer.classList.remove('hidden');
                } else {
                    parentContainer.classList.add('hidden');
                }
            }
        </script>
    @endif

    {{-- Filter & Pencarian --}}
    <form class="flex flex-wrap gap-3" method="get">
        <label class="sr-only" for="q">Cari data</label>
        <input class="field sm:w-72" name="q" id="q" value="{{ request('q') }}" placeholder="Cari kode atau nama...">
        <label class="sr-only" for="type-filter">Jenis data</label>
        <select class="field sm:w-48" name="type" id="type-filter">
            <option value="">Semua jenis</option>
            <option value="fakultas" @selected(request('type') === 'fakultas')>Fakultas</option>
            <option value="prodi" @selected(request('type') === 'prodi')>Program Studi</option>
            <option value="semester" @selected(request('type') === 'semester')>Semester</option>
        </select>
        <button type="submit" class="button-secondary">Cari</button>
    </form>

    {{-- Tabel Data Akademik --}}
    <div class="surface overflow-x-auto border border-line/60">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th>Kode &amp; Nama</th>
                    <th>Jenis Struktur</th>
                    <th>Induk Fakultas</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adminAcademic as $entry)
                    <tr>
                        <td>
                            <p class="font-semibold text-ink">{{ $entry['name'] }}</p>
                            <p class="mt-0.5 font-mono text-xs text-muted">{{ $entry['code'] }}</p>
                        </td>
                        <td>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $entry['type'] === 'fakultas' ? 'bg-brand-soft text-brand' : ($entry['type'] === 'prodi' ? 'bg-slate-100 text-slate-700' : 'bg-canvas text-muted') }}">
                                {{ $entry['type'] === 'fakultas' ? 'Fakultas' : ($entry['type'] === 'prodi' ? 'Program Studi' : 'Semester') }}
                            </span>
                        </td>
                        <td class="text-xs text-muted">
                            {{ !empty($entry['parent']) && isset($academic[$entry['parent']]) ? $academic[$entry['parent']]['name'] : '—' }}
                        </td>
                        <td>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $entry['status'] === 'aktif' ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600 bg-slate-100' }}">
                                {{ ucfirst($entry['status']) }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <a class="quiet-link text-xs" href="{{ route('admin.page', 'akademik') }}?edit={{ $entry['id'] }}">
                                    Edit
                                </a>
                                <span class="text-line">|</span>
                                <form method="post" action="{{ route('admin.academic.destroy', $entry['id']) }}" class="inline" onsubmit="return confirm('Hapus {{ $entry['type'] }} &quot;{{ $entry['name'] }}&quot;?');">
                                    @csrf
                                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 hover:underline">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-sm text-muted">
                            Data akademik tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
