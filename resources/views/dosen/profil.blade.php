@extends('layouts.mahasiswa')

@section('title', 'Profil & Pengaturan | SALE')
@section('header', 'Profil & Pengaturan')

@section('content')
@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Dosen';
    $userNidn  = $user?->nim_nidn ?? '';
    $userEmail = $user?->email ?? '';
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
                @if(session('status') === 'profile-photo-uploaded')
                    <p role="status" class="mt-4 text-sm font-semibold text-emerald-700">Foto profil berhasil diperbarui.</p>
                @elseif(session('status') === 'profile-photo-deleted')
                    <p role="status" class="mt-4 text-sm font-semibold text-emerald-700">Foto profil telah dihapus dan kembali ke avatar default.</p>
                @endif
                @error('photo')<p role="alert" class="mt-4 text-sm text-danger">{{ $message }}</p>@enderror
                @if($settingsWritable)
                    <div class="mt-5 rounded-lg bg-[#f3f6f9] px-4 py-3 text-sm leading-6 text-ink"><span class="font-semibold text-brand">Perhatian:</span> gunakan pas foto resmi (JPG, PNG, atau WebP, maks. 2MB). Klik foto profil untuk mengubah.</div>
                @endif
                <div class="mt-6 flex items-center gap-4 pb-6">
                    @if($settingsWritable)
                        <form id="avatar-form-dosen" action="{{ route('dosen.profile.photo') }}" method="POST" enctype="multipart/form-data" class="hidden">
                            @csrf
                            <input id="dosen-profile-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
                        </form>
                        {{-- Tombol avatar: buka popup pilihan, bukan langsung file picker --}}
                        <button type="button" onclick="document.getElementById('dosen-avatar-picker-modal').showModal()"
                            class="relative group cursor-pointer block h-16 w-16 shrink-0 rounded-full select-none" title="Ubah foto profil">
                            @if(!empty($profilePhotoUrl))
                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $userName }}" class="h-16 w-16 shrink-0 rounded-full border border-[#cbd1d0] object-cover object-top group-hover:opacity-90 transition">
                            @else
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-lg font-semibold text-brand-dark group-hover:bg-slate-50 transition">{{ $userInitials }}</div>
                            @endif
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-brand text-white shadow-xs ring-2 ring-white group-hover:bg-brand-dark transition-colors">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                            </span>
                        </button>
                    @else
                        @if(!empty($profilePhotoUrl))
                            <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $userName }}" class="h-16 w-16 shrink-0 rounded-full border border-[#cbd1d0] object-cover object-top">
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-[#cbd1d0] bg-white text-lg font-semibold text-brand-dark">{{ $userInitials }}</div>
                        @endif
                    @endif
                    <div>
                        <p class="text-lg font-semibold text-ink">{{ $userName }}</p>
                        <p class="mt-1 text-sm text-muted">Dosen Pengampu{{ !empty($activeSemester) ? ', '.$activeSemester : '' }}</p>
                    </div>
                </div>

                {{-- Popup pilihan foto profil dosen --}}
                @if($settingsWritable)
                <dialog id="dosen-avatar-picker-modal" onclick="if(event.target === this) this.close()" class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-xs overflow-hidden rounded-2xl border border-line bg-white p-0 text-ink shadow-2xl backdrop:bg-slate-900/50">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-semibold text-ink">Foto Profil</h2>
                            <button type="button" onclick="document.getElementById('dosen-avatar-picker-modal').close()"
                                class="rounded-md p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors" aria-label="Tutup">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div class="space-y-1">
                            @if(!empty($profilePhotoUrl))
                            <form action="{{ route('dosen.profile.photo.destroy') }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-ink hover:bg-slate-100 active:bg-slate-200 active:scale-[0.98] transition-all text-left">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white border border-[#cbd1d0] text-xs font-bold text-brand-dark">{{ $userInitials }}</span>
                                    <span class="font-medium">Gunakan avatar default</span>
                                </button>
                            </form>
                            @endif
                            <label for="dosen-profile-photo" class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-ink hover:bg-slate-100 active:bg-slate-200 active:scale-[0.98] transition-all cursor-pointer"
                                onclick="document.getElementById('dosen-avatar-picker-modal').close()">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 border border-line/60 text-slate-500">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </span>
                                <span class="font-medium">{{ !empty($profilePhotoUrl) ? 'Ganti foto' : 'Unggah foto profil' }}</span>
                            </label>
                        </div>
                    </div>
                </dialog>
                @endif
                <dl class="grid gap-x-8 gap-y-5 pt-6 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Nama lengkap</dt><dd class="mt-1 font-semibold text-ink">{{ $userName }}</dd></div>
                    <div><dt class="text-sm text-muted">NIDN</dt><dd class="mt-1 font-semibold text-ink">{{ $userNidn }}</dd></div>
                    <div><dt class="text-sm text-muted">Email</dt><dd class="mt-1 break-all font-semibold text-ink">{{ $userEmail }}</dd></div>
                    <div><dt class="text-sm text-muted">Program studi</dt><dd class="mt-1 font-semibold text-ink">{{ $user?->prodi?->name ?? ($sessionUser['prodi'] ?? 'Teknik Informatika') }}</dd></div>
                    <div><dt class="text-sm text-muted">Semester aktif</dt><dd class="mt-1 font-semibold text-ink">{{ $activeSemester ?? 'Belum ada semester aktif' }}</dd></div>
                    <div><dt class="text-sm text-muted">Kelas diampu</dt><dd class="mt-1 font-semibold text-ink">{{ $totalClasses ?? ($user ? $user->totalClassSectionsTeachingCount() : 0) }} Kelas</dd></div>
                    <div><dt class="text-sm text-muted">Status</dt><dd class="mt-1 font-semibold text-ink">Aktif</dd></div>
                </dl>
            </section>

            <section data-settings-panel id="keamanan" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-keamanan" tabindex="0">
                <h2 id="security-heading" class="section-heading">Keamanan akun</h2>
                <p class="mt-1 text-sm text-muted">Gunakan kata sandi yang unik dan tidak dipakai pada layanan lain.</p>
                @if(session('status') === 'password-updated')
                    <p role="status" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">Kata sandi berhasil diperbarui.</p>
                @endif
                @if(!$settingsWritable)
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Perubahan keamanan tersedia untuk akun dosen yang tersimpan di database.</p>
                @endif
                <form action="{{ route('dosen.profile.password') }}" method="POST" class="mt-6 grid max-w-4xl gap-4 lg:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div><label for="current-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi saat ini</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required @disabled(!$settingsWritable) class="field">@error('current_password')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                    <div><label for="new-password" class="mb-1.5 block text-sm font-semibold text-ink">Kata sandi baru</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="8" required @disabled(!$settingsWritable) class="field" aria-describedby="password-help"><p id="password-help" class="mt-1.5 text-xs text-muted">Minimal 8 karakter dengan kombinasi huruf dan angka.</p>@error('new_password')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                    <div><label for="confirm-password" class="mb-1.5 block text-sm font-semibold text-ink">Konfirmasi kata sandi baru</label><input id="confirm-password" name="new_password_confirmation" type="password" autocomplete="new-password" minlength="8" required @disabled(!$settingsWritable) class="field"></div>
                    <div class="flex items-end"><button type="submit" @disabled(!$settingsWritable) class="button-primary">Perbarui kata sandi</button></div>
                </form>
            </section>

            <section data-settings-panel id="notifikasi" class="hidden rounded-xl bg-white p-6 shadow-sm" role="tabpanel" aria-labelledby="tab-notifikasi" tabindex="0">
                <h2 id="notification-heading" class="section-heading">Preferensi Notifikasi</h2>
                <p class="mt-1 text-sm text-muted">Atur pemberitahuan yang ingin Anda terima terkait aktivitas pengajaran.</p>
                @if(session('status') === 'notification-preferences-updated')
                    <p role="status" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">Preferensi notifikasi berhasil disimpan.</p>
                @endif
                @if(!$settingsWritable)
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Preferensi hanya dapat disimpan untuk akun dosen yang tersimpan di database.</p>
                @endif
                <form action="{{ route('dosen.profile.notifications') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <fieldset @disabled(!$settingsWritable) class="mt-5 grid gap-3 xl:grid-cols-2">
                        <legend class="sr-only">Preferensi notifikasi dosen</legend>
                        @foreach ([
                            ['id' => 'notif_submission', 'title' => 'Mahasiswa mengumpulkan tugas', 'description' => 'Pemberitahuan ketika ada mahasiswa yang baru mengumpulkan jawaban'],
                            ['id' => 'notif_deadline', 'title' => 'Pengingat batas penilaian', 'description' => 'Ingatkan jika ada asesmen belum selesai dinilai setelah 3 hari'],
                            ['id' => 'notif_forum', 'title' => 'Aktivitas forum diskusi', 'description' => 'Notifikasi untuk pertanyaan baru dari mahasiswa di forum'],
                            ['id' => 'notif_rekap', 'title' => 'Rekap OBE tersedia', 'description' => 'Pemberitahuan ketika rekap CPMK dan CPL telah terkalkulasi'],
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
