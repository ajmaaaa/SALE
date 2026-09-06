@extends('layouts.mahasiswa')

@section('title', 'Profil & Pengaturan | SALE')
@section('header', 'Profil & Pengaturan')

@section('content')
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
            <a data-settings-link id="tab-tampilan" href="#tampilan" class="shrink-0 border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-muted hover:text-ink" role="tab" aria-controls="tampilan" aria-selected="false" tabindex="-1">Tampilan</a>
        </nav>

        <div class="mt-6 min-w-0">
            <section data-settings-panel id="profil" class="rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-profil" tabindex="0">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 id="profile-heading" class="section-heading">Informasi profil</h2><p class="mt-1 text-sm text-muted">Data akademik utama berasal dari sistem informasi kampus.</p></div>
                    <button type="button" class="button-primary shrink-0">Upload foto</button>
                </div>
                <div class="mt-5 rounded-lg bg-[#f3f6f9] px-4 py-3 text-sm leading-6 text-ink"><span class="font-semibold text-brand">Perhatian:</span> gunakan pas foto resmi dengan almamater. Foto profil hanya dapat diunggah satu kali dan akan digunakan sebagai identitas akademik.</div>
                <div class="mt-6 flex items-center gap-4 pb-6">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-lg font-semibold text-brand-dark">AM</div>
                    <div><p class="text-lg font-semibold text-ink">Ahmad Mahasiswa</p><p class="mt-1 text-sm text-muted">Mahasiswa aktif, semester 5</p></div>
                </div>
                <dl class="grid gap-x-8 gap-y-5 pt-6 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Nama lengkap</dt><dd class="mt-1 font-semibold text-ink">Ahmad Mahasiswa</dd></div>
                    <div><dt class="text-sm text-muted">NIM</dt><dd class="mt-1 font-semibold text-ink">231011401234</dd></div>
                    <div><dt class="text-sm text-muted">Program studi</dt><dd class="mt-1 font-semibold text-ink">Teknik Informatika</dd></div>
                    <div><dt class="text-sm text-muted">Email akademik</dt><dd class="mt-1 break-all font-semibold text-ink">ahmad.mahasiswa@student.kampus.ac.id</dd></div>
                    <div><dt class="text-sm text-muted">Fakultas</dt><dd class="mt-1 font-semibold text-ink">Ilmu Komputer</dd></div>
                    <div><dt class="text-sm text-muted">Status akademik</dt><dd class="mt-1 font-semibold text-ink">Aktif</dd></div>
                </dl>
            </section>

            <section data-settings-panel id="keamanan" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-keamanan" tabindex="0">
                <h2 id="security-heading" class="section-heading">Keamanan akun</h2>
                <p class="mt-1 text-sm text-muted">Gunakan kata sandi yang unik dan tidak dipakai pada layanan lain.</p>
                <form class="mt-6 grid max-w-4xl gap-4 lg:grid-cols-2">
                    <input type="text" name="username" value="231011401234" autocomplete="username" class="sr-only" tabindex="-1" aria-hidden="true">
                    <div><label for="current-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi saat ini</label><input id="current-password" type="password" autocomplete="current-password" class="field"></div>
                    <div><label for="new-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi baru</label><input id="new-password" type="password" autocomplete="new-password" class="field" aria-describedby="password-help"><p id="password-help" class="mt-1.5 text-xs text-muted">Minimal 12 karakter dengan kombinasi huruf dan angka.</p></div>
                    <div><label for="confirm-password" class="mb-1.5 block text-sm font-semibold text-ink">Konfirmasi kata sandi baru</label><input id="confirm-password" type="password" autocomplete="new-password" class="field"></div>
                    <div class="flex items-end"><button type="button" class="button-primary">Perbarui kata sandi</button></div>
                </form>
            </section>

            <section data-settings-panel id="notifikasi" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-notifikasi" tabindex="0">
                <h2 id="notification-heading" class="section-heading">Notifikasi</h2>
                <p class="mt-1 text-sm text-muted">Pilih informasi akademik yang perlu dikirimkan kepada Anda.</p>
                <fieldset class="mt-5 grid gap-3 xl:grid-cols-2">
                    <legend class="sr-only">Preferensi notifikasi</legend>
                    @foreach ([
                        ['id' => 'announcement', 'title' => 'Pengumuman course', 'description' => 'Informasi baru dari dosen pengampu', 'checked' => true],
                        ['id' => 'deadline', 'title' => 'Pengingat tenggat', 'description' => 'Pengingat 24 jam sebelum tugas berakhir', 'checked' => true],
                        ['id' => 'grade', 'title' => 'Nilai dipublikasikan', 'description' => 'Pemberitahuan ketika dosen membuka nilai', 'checked' => true],
                        ['id' => 'forum', 'title' => 'Balasan forum', 'description' => 'Balasan dan penyebutan nama pada diskusi', 'checked' => false],
                    ] as $preference)
                        <label for="{{ $preference['id'] }}" class="flex cursor-pointer items-start gap-4 rounded-lg bg-brand-soft px-4 py-4">
                            <input id="{{ $preference['id'] }}" type="checkbox" @checked($preference['checked']) class="mt-1 h-4 w-4 rounded-sm border-line text-brand focus:ring-brand">
                            <span><span class="block font-semibold text-ink">{{ $preference['title'] }}</span><span class="mt-1 block text-sm text-muted">{{ $preference['description'] }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                <button type="button" class="button-primary mt-5">Simpan preferensi</button>
            </section>

            <section data-settings-panel id="tampilan" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-tampilan" tabindex="0">
                <h2 id="display-heading" class="section-heading">Tampilan</h2>
                <p class="mt-1 text-sm text-muted">Atur tema antarmuka sesuai kenyamanan membaca.</p>
                <fieldset class="mt-5 grid max-w-md gap-3 sm:grid-cols-2">
                    <legend class="sr-only">Pilihan tema</legend>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-brand-dark p-4 text-white"><input type="radio" name="theme" value="light" checked class="h-4 w-4 text-brand focus:ring-brand"><span><span class="block font-semibold text-white">Terang</span><span class="text-sm text-white">Latar netral terang</span></span></label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-brand-soft p-4"><input type="radio" name="theme" value="system" class="h-4 w-4 text-brand focus:ring-brand"><span><span class="block font-semibold text-ink">Ikuti sistem</span><span class="text-sm text-muted">Sesuai perangkat</span></span></label>
                </fieldset>
            </section>
        </div>
    </div>
</div>
@endsection
