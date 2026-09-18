@extends('layouts.mahasiswa')

@section('title', 'Mahasiswa '.$course['title'].' | SALE')
@section('header', 'Mahasiswa Course')

@section('content')
<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-muted" aria-label="Breadcrumb">
        <a href="{{ route('dosen.course.show', $course['id']) }}" class="hover:text-brand">{{ $course['code'] }}</a>
        <span aria-hidden="true">/</span>
        <span class="font-semibold text-ink">Mahasiswa</span>
    </nav>

    <header>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">{{ $course['code'] }}</p>
        <h1 class="page-heading mt-1">Kelola mahasiswa</h1>
        <p class="page-description">Atur peserta yang dapat mengikuti materi, tugas, kuis, dan penilaian pada {{ $course['title'] }}.</p>
    </header>

    <form method="post" action="{{ route('dosen.course.students.save', $course['id']) }}" class="surface overflow-hidden">
        @csrf
        <div class="flex flex-col gap-3 border-b border-line/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-ink">Daftar peserta aktif</h2>
                <p class="mt-1 text-xs text-muted">Perubahan berlaku khusus untuk course ini.</p>
            </div>
            <button type="submit" class="button-primary">Simpan peserta</button>
        </div>
        <div class="divide-y divide-line/50">
            @forelse($students as $student)
                <label class="flex cursor-pointer items-center gap-4 px-5 py-4 hover:bg-canvas">
                    <input type="checkbox" name="students[]" value="{{ $student['id'] }}" @checked(in_array($student['id'], $enrolled, true)) class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-soft text-xs font-bold text-brand-dark">{{ collect(explode(' ', $student['name']))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-ink">{{ $student['name'] }}</span><span class="block text-xs text-muted">{{ $student['number'] }} · {{ $student['email'] }}</span></span>
                    @if(in_array($student['id'], $enrolled, true))<span class="status bg-brand-soft text-brand">Peserta</span>@endif
                </label>
            @empty
                <p class="px-5 py-8 text-sm text-muted">Belum ada data mahasiswa aktif. Tambahkan mahasiswa melalui data pengguna terlebih dahulu.</p>
            @endforelse
        </div>
    </form>

    <details class="surface p-5">
        <summary class="cursor-pointer text-sm font-semibold text-ink">Tambah mahasiswa baru</summary>
        <form method="post" action="{{ route('dosen.course.students.create', $course['id']) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
            @csrf
            <label class="text-xs font-semibold text-muted">Nama<input name="name" value="{{ old('name') }}" required class="field mt-1" placeholder="Nama lengkap"></label>
            <label class="text-xs font-semibold text-muted">NIM<input name="number" value="{{ old('number') }}" required class="field mt-1" placeholder="Nomor mahasiswa"></label>
            <label class="text-xs font-semibold text-muted">Email<input type="email" name="email" value="{{ old('email') }}" required class="field mt-1" placeholder="Email mahasiswa"></label>
            <div class="sm:col-span-3"><button type="submit" class="button-secondary">Tambah &amp; daftarkan</button></div>
        </form>
    </details>
</div>
@endsection
