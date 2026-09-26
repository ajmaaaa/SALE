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

    {{-- Summary Stat Cards: Total Mahasiswa, Total Dosen, Administrator, Pengguna Aktif --}}
    @php
        $mahasiswaCount = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'mahasiswa')));
        $dosenCount = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'dosen')));
        $adminCount = count(array_filter($users, fn($u) => \App\Support\AdminPreview::hasRole($u, 'admin')));
        $aktifCount = count(array_filter($users, fn($u) => $u['status'] === 'aktif'));
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface p-5 border border-line/60">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">TOTAL MAHASISWA</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <p class="mt-2.5 text-2xl font-bold text-ink">{{ $mahasiswaCount }}</p>
            <p class="mt-1 text-xs text-muted">Peserta akademik terdaftar</p>
        </div>

        <div class="surface p-5 border border-line/60">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">TOTAL DOSEN</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <p class="mt-2.5 text-2xl font-bold text-ink">{{ $dosenCount }}</p>
            <p class="mt-1 text-xs text-muted">Tenaga pendidik &amp; pengampu</p>
        </div>

        <div class="surface p-5 border border-line/60">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">ADMINISTRATOR &amp; PRODI</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <p class="mt-2.5 text-2xl font-bold text-ink">{{ count(array_filter($users, fn($u) => collect(['admin', 'admin_prodi'])->contains(fn($role) => \App\Support\AdminPreview::hasRole($u, $role)))) }}</p>
            <p class="mt-1 text-xs text-muted">Pengelola sistem &amp; prodi</p>
        </div>

        <div class="surface p-5 border border-line/60">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-muted">AKUN AKTIF</p>
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <p class="mt-2.5 text-2xl font-bold text-ink">{{ $aktifCount }}</p>
            <p class="mt-1 text-xs text-muted">Dapat login ke ekosistem</p>
        </div>
    </div>

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
                    <span class="form-label">Peran akses</span>
                    @php($selectedRoles = old('roles', $record['roles'] ?? (isset($record['role']) ? [$record['role']] : ['mahasiswa'])))
                    <div class="grid grid-cols-2 gap-2 rounded-lg border border-line bg-white p-3">
                        @foreach(['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin_prodi' => 'Admin Prodi', 'admin' => 'Administrator'] as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 text-xs text-ink"><input type="checkbox" name="roles[]" value="{{ $key }}" class="rounded border-line text-brand" @checked(in_array($key, $selectedRoles, true))><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Satu identitas dapat memiliki beberapa peran tanpa membuat akun baru.</p>
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
            @foreach(['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin_prodi' => 'Admin Prodi', 'admin' => 'Administrator'] as $key => $label)
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
                            @foreach($user['roles'] ?? [$user['role']] as $role)
                                {{ ['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin_prodi' => 'Admin Prodi', 'admin' => 'Administrator'][$role] ?? ucfirst($role) }}@if(!$loop->last), @endif
                            @endforeach
                        </td>
                        <td>
                            {{ ucfirst($user['status']) }}
                        </td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <a class="quiet-link text-xs" href="{{ route('admin.page', 'pengguna') }}?edit={{ $user['id'] }}">
                                    Edit<span class="sr-only"> {{ $user['name'] }}</span>
                                </a>
                                <span class="text-line">|</span>
                                <form method="post" action="{{ route('admin.users.destroy', $user['id']) }}" class="inline" onsubmit="return confirm('Hapus pengguna &quot;{{ $user['name'] }}&quot;?');">
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
