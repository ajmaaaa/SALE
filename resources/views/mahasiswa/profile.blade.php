@extends('layouts.mahasiswa')

@section('title', 'Profil & Pengaturan | SALE')
@section('header', 'Profil & Pengaturan')

@section('content')
@php
    $sessionUser = session('auth_user', []);
    $userName = $user?->name ?? ($sessionUser['name'] ?? 'Mahasiswa');
    $userNumber = $user?->nim_nidn ?? ($sessionUser['number'] ?? '—');
    $userEmail = $user?->email ?? ($sessionUser['email'] ?? '—');
    $userInitials = collect(explode(' ', $userName))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
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
                    <div><h2 id="profile-heading" class="section-heading">Informasi profil</h2><p class="mt-1 text-sm text-muted">Data akademik utama berasal dari sistem informasi kampus.</p></div>
                    @if(!$profilePhotoUrl && $settingsWritable)
                        <form action="{{ route('mahasiswa.profile.photo') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <label for="profile-photo" class="sr-only">Pilih foto profil</label>
                            <input id="profile-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required class="field max-w-56 text-xs">
                            <button type="submit" class="button-primary shrink-0">Upload foto</button>
                        </form>
                    @elseif($profilePhotoUrl)
                        <span class="text-xs font-semibold text-muted">Foto profil sudah diunggah</span>
                    @endif
                </div>
                @if(session('status') === 'profile-photo-uploaded')
                    <p role="status" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">Foto profil berhasil diunggah.</p>
                @endif
                @error('photo')<p role="alert" class="mt-4 text-sm text-danger">{{ $message }}</p>@enderror
                @if(!$profilePhotoUrl && $settingsWritable)
                    <div class="mt-5 rounded-lg bg-[#f3f6f9] px-4 py-3 text-sm leading-6 text-ink"><span class="font-semibold text-brand">Perhatian:</span> gunakan pas foto resmi dengan almamater. Foto profil hanya dapat diunggah satu kali dan akan digunakan sebagai identitas akademik.</div>
                @endif
                <div class="mt-6 flex items-center gap-4 pb-6">
                    @if($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $userName }}" class="h-16 w-16 shrink-0 rounded-full border border-[#cbd1d0] object-cover">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-lg font-semibold text-brand-dark">{{ $userInitials }}</div>
                    @endif
                    <div><p class="text-lg font-semibold text-ink">{{ $userName }}</p><p class="mt-1 text-sm text-muted">{{ $settingsWritable ? 'Mahasiswa aktif' : ($sessionUser['role'] ?? 'Mode pratinjau') }}{{ $activeSemester ? ', '.$activeSemester : '' }}</p></div>
                </div>
                <dl class="grid gap-x-8 gap-y-5 pt-6 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Nama lengkap</dt><dd class="mt-1 font-semibold text-ink">{{ $userName }}</dd></div>
                    <div><dt class="text-sm text-muted">NIM</dt><dd class="mt-1 font-semibold text-ink">{{ $userNumber }}</dd></div>
                    <div><dt class="text-sm text-muted">Program studi</dt><dd class="mt-1 font-semibold text-ink">{{ $user?->prodi?->name ?? ($sessionUser['prodi'] ?? 'Belum ditetapkan') }}</dd></div>
                    <div><dt class="text-sm text-muted">Email akademik</dt><dd class="mt-1 break-all font-semibold text-ink">{{ $userEmail }}</dd></div>
                    <div><dt class="text-sm text-muted">Semester aktif</dt><dd class="mt-1 font-semibold text-ink">{{ $activeSemester ?? 'Belum terdaftar di kelas aktif' }}</dd></div>
                    <div><dt class="text-sm text-muted">Status akademik</dt><dd class="mt-1 font-semibold text-ink">{{ $settingsWritable ? 'Aktif' : 'Mode pratinjau' }}</dd></div>
                </dl>
            </section>

            <section data-settings-panel id="keamanan" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-keamanan" tabindex="0">
                <h2 id="security-heading" class="section-heading">Keamanan akun</h2>
                <p class="mt-1 text-sm text-muted">Gunakan kata sandi yang unik dan tidak dipakai pada layanan lain.</p>
                @if(session('status') === 'password-updated')
                    <p role="status" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">Kata sandi berhasil diperbarui.</p>
                @endif
                @if(!$settingsWritable)
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Perubahan kata sandi memerlukan akun mahasiswa database.</p>
                @endif
                <form action="{{ route('mahasiswa.profile.password') }}" method="POST" class="mt-6 grid max-w-4xl gap-4 lg:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div><label for="current-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi saat ini</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required @disabled(!$settingsWritable) class="field">@error('current_password')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                    <div><label for="new-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi baru</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="12" required @disabled(!$settingsWritable) class="field" aria-describedby="password-help"><p id="password-help" class="mt-1.5 text-xs text-muted">Minimal 12 karakter dengan kombinasi huruf dan angka.</p>@error('new_password')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                    <div><label for="confirm-password" class="mb-1.5 block text-sm font-semibold text-ink">Konfirmasi kata sandi baru</label><input id="confirm-password" name="new_password_confirmation" type="password" autocomplete="new-password" minlength="12" required @disabled(!$settingsWritable) class="field"></div>
                    <div class="flex items-end"><button type="submit" @disabled(!$settingsWritable) class="button-primary">Perbarui kata sandi</button></div>
                </form>
            </section>

            <section data-settings-panel id="notifikasi" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-notifikasi" tabindex="0">
                <h2 id="notification-heading" class="section-heading">Notifikasi</h2>
                <p class="mt-1 text-sm text-muted">Pilih informasi akademik yang perlu dikirimkan kepada Anda.</p>
                @if(session('status') === 'notification-preferences-updated')
                    <p role="status" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">Preferensi notifikasi berhasil disimpan.</p>
                @endif
                @if(!$settingsWritable)
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Preferensi dapat disimpan setelah masuk dengan akun mahasiswa database.</p>
                @endif
                <form action="{{ route('mahasiswa.profile.notifications') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <fieldset @disabled(!$settingsWritable) class="mt-5 grid gap-3 xl:grid-cols-2">
                        <legend class="sr-only">Preferensi notifikasi</legend>
                        @foreach ([
                            ['id' => 'announcement', 'title' => 'Pengumuman course', 'description' => 'Informasi baru dari dosen pengampu'],
                            ['id' => 'deadline', 'title' => 'Pengingat tenggat', 'description' => 'Pengingat 24 jam sebelum tugas berakhir'],
                            ['id' => 'grade', 'title' => 'Nilai dipublikasikan', 'description' => 'Pemberitahuan ketika dosen membuka nilai'],
                            ['id' => 'forum', 'title' => 'Balasan forum', 'description' => 'Balasan dan penyebutan nama pada diskusi'],
                        ] as $preference)
                            <label for="{{ $preference['id'] }}" class="flex cursor-pointer items-start gap-4 rounded-lg bg-brand-soft px-4 py-4 has-[:disabled]:cursor-not-allowed">
                                <input id="{{ $preference['id'] }}" name="preferences[{{ $preference['id'] }}]" value="1" type="checkbox" @checked($preferences[$preference['id']]) class="mt-1 h-4 w-4 rounded-sm border-line text-brand focus:ring-brand">
                                <span><span class="block font-semibold text-ink">{{ $preference['title'] }}</span><span class="mt-1 block text-sm text-muted">{{ $preference['description'] }}</span></span>
                            </label>
                        @endforeach
                    </fieldset>
                    <button type="submit" @disabled(!$settingsWritable) class="button-primary mt-5">Simpan preferensi</button>
                </form>
            </section>

        </div>
    </div>
</div>
@endsection
