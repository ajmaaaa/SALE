@extends('layouts.mahasiswa')

@section('title', 'Pengguna & Hak Akses | SALE')
@section('header', 'Pengguna & Hak Akses')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-heading">Pengguna &amp; Hak Akses</h1>
            <p class="page-description">Kelola identitas, nomor induk institusi, peran akses, dan status akun.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <button type="button" onclick="document.getElementById('bulk-import-section').toggleAttribute('hidden')" class="button-secondary">
                Import Massal
            </button>
            <a class="button-primary" href="{{ route('admin.page', 'pengguna') }}?create=1">
                + Tambah Pengguna
            </a>
        </div>
    </header>

    {{-- Bulk Import Section (Collapsible) --}}
    <section id="bulk-import-section" hidden class="surface p-6">
        <div class="flex items-center justify-between pb-3 border-b border-line/60">
            <div>
                <h2 class="text-base font-semibold text-ink">Import Pengguna Sekaligus (CSV / Salin-Tempel)</h2>
                <p class="mt-0.5 text-xs text-muted">Input banyak mahasiswa atau dosen sekaligus tanpa harus memasukkan satu per satu.</p>
            </div>
            <button type="button" onclick="document.getElementById('bulk-import-section').setAttribute('hidden', '')" class="text-xs text-muted hover:text-ink">
                Tutup
            </button>
        </div>

        <form class="mt-4 space-y-4" method="post" action="{{ route('admin.users.bulk') }}">
            @csrf
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="form-label text-xs" for="raw_users">Data Pengguna (CSV / Salin-Tempel)</label>
                    <button type="button" class="text-xs font-semibold text-brand hover:underline" onclick="document.getElementById('raw_users').value = '231011401235, Siti Rahma, rahma@example.test, mahasiswa, aktif\n231011401236, Dimas Pratama, dimas@example.test, mahasiswa, aktif\n231011401237, Maya Lestari, maya@example.test, mahasiswa, aktif\nDSN002, Ratna Prameswari, ratna@example.test, dosen, aktif';">
                        Muat Contoh Data
                    </button>
                </div>
                <textarea id="raw_users" name="raw_users" rows="6" required class="field font-mono text-xs" placeholder="NIM/NIDN, Nama Lengkap, Email, Peran (mahasiswa/dosen/admin), Status (aktif/nonaktif)&#10;Contoh:&#10;231011401235, Siti Rahma, rahma@example.test, mahasiswa, aktif&#10;DSN002, Ratna Prameswari, ratna@example.test, dosen, aktif"></textarea>
                <p class="mt-1.5 text-xs text-muted">Format tiap baris: <code class="font-mono font-semibold">NIM/NIDN, Nama, Email, Peran, Status</code>. Pisahkan dengan tanda koma (,) atau tab.</p>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit" class="button-primary">
                    Simpan Semua Pengguna
                </button>
                <button type="button" onclick="document.getElementById('bulk-import-section').setAttribute('hidden', '')" class="button-secondary">
                    Batal
                </button>
            </div>
        </form>
    </section>

    {{-- Single User Form (Create / Edit) --}}
    @if(request('create') || $record || $errors->any())
        <form class="surface p-6 space-y-5" method="post" action="{{ route('admin.users.store') }}">
            @csrf
            @if($record)
                <input type="hidden" name="id" value="{{ $record['id'] }}">
            @endif
            <h2 class="section-heading">{{ $record ? 'Edit Pengguna' : 'Pengguna Baru' }}</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="number">NIM / NIDN / NIP</label>
                    <input class="field" id="number" name="number" type="text" required placeholder="Contoh: 231011401234 atau DSN001" value="{{ old('number', $record['number'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input class="field" id="name" name="name" type="text" required placeholder="Nama lengkap" value="{{ old('name', $record['name'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label" for="email">Alamat Email</label>
                    <input class="field" id="email" name="email" type="email" required placeholder="nama@kampus.ac.id" value="{{ old('email', $record['email'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label" for="role">Peran Akses</label>
                    <select class="field" id="role" name="role">
                        @foreach(['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin' => 'Administrator'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('role', $record['role'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="status">Status Akun</label>
                    <select class="field" id="status" name="status">
                        @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $record['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="text-xs text-muted">Perubahan pengguna akan otomatis dicatat ke dalam activity log sistem.</p>
            <div class="flex gap-3 pt-2">
                <button class="button-primary">Simpan Pengguna</button>
                <a class="button-secondary" href="{{ route('admin.page', 'pengguna') }}">Batal</a>
            </div>
        </form>
    @endif

    {{-- Search and Filter --}}
    <form class="flex flex-wrap gap-3">
        <label class="sr-only" for="q">Cari pengguna</label>
        <input class="field sm:w-72" id="q" name="q" placeholder="Cari NIM, NIDN, nama, atau email" value="{{ request('q') }}">
        <label class="sr-only" for="role-filter">Filter peran</label>
        <select class="field sm:w-44" id="role-filter" name="role">
            <option value="">Semua peran</option>
            @foreach(['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin' => 'Administrator'] as $key => $label)
                <option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="button-secondary">Cari</button>
    </form>

    {{-- Users Table with Separate NIM / NIDN / NIP Column (No pastel colored badges, clean typography) --}}
    <div class="surface overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="whitespace-nowrap">NIM / NIDN / NIP</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th class="text-right"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($visibleUsers as $user)
                    <tr>
                        <td class="font-mono text-xs font-semibold text-ink whitespace-nowrap">
                            {{ $user['number'] }}
                        </td>
                        <td class="font-semibold text-ink">
                            {{ $user['name'] }}
                        </td>
                        <td class="text-xs text-muted">
                            {{ $user['email'] }}
                        </td>
                        <td>
                            {{ ucfirst($user['role']) }}
                        </td>
                        <td>
                            {{ ucfirst($user['status']) }}
                        </td>
                        <td class="text-right">
                            <a class="quiet-link text-xs" href="{{ route('admin.page', 'pengguna') }}?edit={{ $user['id'] }}">
                                Edit<span class="sr-only"> {{ $user['name'] }}</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-sm text-muted">
                            Pengguna tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
