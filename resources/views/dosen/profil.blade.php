@extends('layouts.mahasiswa')

@section('title', 'Profil & Pengaturan | SALE')
@section('header', 'Profil & Pengaturan')

@section('content')
@php
    $user = auth()->user();
    // Fallback ke session (preview mode)
    $sessionUser = session('auth_user');
    $userName = $user?->name ?? $sessionUser['name'] ?? 'Dosen';
    $userNidn  = $user?->nim_nidn ?? $sessionUser['number'] ?? '—';
    $userEmail = $user?->email ?? $sessionUser['email'] ?? '—';
    $userInitials = collect(explode(' ', $userName))->map(fn($p) => mb_substr($p,0,1))->take(2)->implode('');
@endphp
<div class="space-y-7">
    <header class="pb-2">
        <h1 class="page-heading">Profil &amp; Pengaturan</h1>
        <p class="page-description">Kelola informasi akun, keamanan, serta preferensi notifikasi pembelajaran.</p>
    </header>

    <div>
        <nav data-settings-nav class="flex overflow-x-auto border-b border-line" aria-label="Bagian pengaturan" role="tablist">
            <a data-settings-link id="tab-profil" href="#profil" class="shrink-0 border-b-2 border-brand px-4 py-3 text-sm font-semibold text-brand" role="tab" aria-controls="profil" aria-selected="true">Informasi profil</a>
            <a data-settings-link id="tab-keamanan" href="#keamanan" class="shrink-0 border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-muted hover:text-ink" role="tab" aria-controls="keamanan" aria-selected="false" tabindex="-1">Keamanan akun</a>
            <a data-settings-link id="tab-notifikasi" href="#notifikasi" class="shrink-0 border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-muted hover:text-ink" role="tab" aria-controls="notifikasi" aria-selected="false" tabindex="-1">Notifikasi</a>
        </nav>

        <div class="mt-6 min-w-0">
            <section data-settings-panel id="profil" class="rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-profil" tabindex="0">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 id="profile-heading" class="section-heading">Informasi profil</h2><p class="mt-1 text-sm text-muted">Data identitas Anda sebagai dosen pengampu di platform SALE.</p></div>
                </div>
                <div class="mt-6 flex items-center gap-4 pb-6">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-[#cbd1d0] bg-brand-soft text-lg font-semibold text-brand-dark">{{ $userInitials }}</div>
                    <div><p class="text-lg font-semibold text-ink">{{ $userName }}</p><p class="mt-1 text-sm text-muted">Dosen Pengampu</p></div>
                </div>
                <dl class="grid gap-x-8 gap-y-5 pt-6 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Nama lengkap</dt><dd class="mt-1 font-semibold text-ink">{{ $userName }}</dd></div>
                    <div><dt class="text-sm text-muted">NIDN</dt><dd class="mt-1 font-semibold text-ink">{{ $userNidn }}</dd></div>
                    <div><dt class="text-sm text-muted">Email</dt><dd class="mt-1 break-all font-semibold text-ink">{{ $userEmail }}</dd></div>
                    <div><dt class="text-sm text-muted">Program studi</dt><dd class="mt-1 font-semibold text-ink">{{ $user?->prodi?->name ?? ($sessionUser['prodi'] ?? 'Teknik Informatika') }}</dd></div>
                    <div><dt class="text-sm text-muted">Kelas diampu</dt><dd class="mt-1 font-semibold text-ink">{{ $user ? $user->classSectionsTeaching()->count() : 1 }} Kelas Aktif</dd></div>
                    <div><dt class="text-sm text-muted">Status</dt><dd class="mt-1 font-semibold text-ink">Aktif</dd></div>
                </dl>
            </section>

            <section data-settings-panel id="keamanan" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-keamanan" tabindex="0">
                <h2 id="security-heading" class="section-heading">Keamanan akun</h2>
                <p class="mt-1 text-sm text-muted">Gunakan kata sandi yang unik dan tidak dipakai pada layanan lain.</p>
                <form class="mt-6 grid max-w-4xl gap-4 lg:grid-cols-2">
                    <input type="text" name="username" value="{{ $userNidn }}" autocomplete="username" class="sr-only" tabindex="-1" aria-hidden="true">
                    <div><label for="current-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi saat ini</label><input id="current-password" type="password" autocomplete="current-password" class="field"></div>
                    <div><label for="new-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi baru</label><input id="new-password" type="password" autocomplete="new-password" class="field" aria-describedby="password-help"><p id="password-help" class="mt-1.5 text-xs text-muted">Minimal 12 karakter dengan kombinasi huruf dan angka.</p></div>
                    <div><label for="confirm-password" class="mb-1.5 block text-sm font-semibold text-ink">Konfirmasi kata sandi baru</label><input id="confirm-password" type="password" autocomplete="new-password" class="field"></div>
                    <div class="flex items-end"><button type="button" class="button-primary">Perbarui kata sandi</button></div>
                </form>
            </section>

            <section data-settings-panel id="notifikasi" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-notifikasi" tabindex="0">
                <h2 id="notification-heading" class="section-heading">Preferensi Notifikasi</h2>
                <p class="mt-1 text-sm text-muted">Atur pemberitahuan yang ingin Anda terima terkait aktivitas pengajaran.</p>
                <fieldset class="mt-5 grid gap-3 xl:grid-cols-2">
                    <legend class="sr-only">Preferensi notifikasi dosen</legend>
                    @foreach ([
                        ['id' => 'notif_submission', 'title' => 'Mahasiswa mengumpulkan tugas', 'description' => 'Pemberitahuan ketika ada mahasiswa yang baru mengumpulkan jawaban', 'checked' => true],
                        ['id' => 'notif_deadline', 'title' => 'Pengingat batas penilaian', 'description' => 'Ingatkan jika ada asesmen belum selesai dinilai setelah 3 hari', 'checked' => true],
                        ['id' => 'notif_forum', 'title' => 'Aktivitas forum diskusi', 'description' => 'Notifikasi untuk pertanyaan baru dari mahasiswa di forum', 'checked' => false],
                        ['id' => 'notif_rekap', 'title' => 'Rekap OBE tersedia', 'description' => 'Pemberitahuan ketika rekap CPMK dan CPL telah terkalkulasi', 'checked' => true],
                    ] as $preference)
                        <label for="{{ $preference['id'] }}" class="flex cursor-pointer items-start gap-4 rounded-lg bg-brand-soft px-4 py-4">
                            <input id="{{ $preference['id'] }}" type="checkbox" @checked($preference['checked']) class="mt-1 h-4 w-4 rounded-sm border-line text-brand focus:ring-brand">
                            <span><span class="block font-semibold text-ink">{{ $preference['title'] }}</span><span class="mt-1 block text-sm text-muted">{{ $preference['description'] }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                <button type="button" class="button-primary mt-5">Simpan preferensi</button>
            </section>
        </div>
    </div>
</div>
@endsection
