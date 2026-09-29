@extends('layouts.mahasiswa')

@section('title', 'Pendaftaran Kelas | SALE')
@section('header', 'Pendaftaran Kelas Perkuliahan')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-4 justify-center">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('mahasiswa.dashboard') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>Dashboard</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800" aria-current="page">
            Status Pendaftaran Kelas
        </span>
    </nav>
    <div class="surface p-8 text-center space-y-6">
        @if($status === 'success')
            <div class="h-16 w-16 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center mx-auto text-3xl font-bold">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">
                    Pendaftaran Berhasil
                </span>
                <h1 class="page-heading mt-3 text-xl">Selamat Datang di Kelas Perkuliahan!</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @elseif($status === 'already_enrolled')
            <div class="h-16 w-16 rounded-2xl bg-slate-100 text-ink border border-line flex items-center justify-center mx-auto text-3xl font-bold">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4m0 4h.01M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-ink">
                    Sudah Terdaftar
                </span>
                <h1 class="page-heading mt-3 text-xl">Anda Sudah Bergabung</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @elseif($status === 'full')
            <div class="h-16 w-16 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center mx-auto text-3xl font-bold">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-800">
                    Kuota Penuh
                </span>
                <h1 class="page-heading mt-3 text-xl">Kapasitas Kelas Terpenuhi</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @else
            <div class="h-16 w-16 rounded-2xl bg-slate-100 text-ink border border-line flex items-center justify-center mx-auto text-3xl font-bold">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-ink uppercase tracking-wider">
                    Informasi Akun
                </span>
                <h1 class="page-heading mt-3 text-xl">Tautan Khusus Mahasiswa</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @endif

        <div class="border-t border-b border-line/60 py-5 text-left space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-line/40">
                <span class="px-2.5 py-1 rounded bg-brand text-white font-mono font-bold text-xs">{{ $section->display_code }}</span>
                <span class="text-xs text-muted">{{ $section->semester->name ?? 'Semester Aktif' }}</span>
            </div>

            <div>
                <h3 class="text-base font-bold text-ink">{{ $section->mataKuliah->name }}</h3>
                <p class="text-xs text-muted">{{ $section->mataKuliah->prodi->name ?? '' }} ({{ $section->mataKuliah->sks }} SKS)</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                <div>
                    <span class="text-muted text-[11px] block">Dosen Ketua (Koordinator):</span>
                    <span class="font-bold text-ink">{{ $section->dosen?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-muted text-[11px] block">Dosen Wakil (Pendamping):</span>
                    <span class="font-bold text-ink">{{ $section->dosenPendamping?->name ?? 'Tidak ada' }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-center gap-3 pt-2">
            @if($isDosen ?? false)
                <a href="{{ route('dosen.penilaian.index') }}" class="button-secondary text-xs px-5 py-2.5">
                    Daftar Kelas Saya
                </a>
                <a href="{{ route('dosen.course.index') }}" class="button-primary text-xs px-5 py-2.5">
                    Buka Course Saya
                </a>
            @else
                <a href="{{ route('mahasiswa.dashboard') }}" class="button-secondary text-xs px-5 py-2.5">
                    Ke Dashboard Utama
                </a>
                <a href="{{ route('mahasiswa.course.index') }}" class="button-primary text-xs px-5 py-2.5">
                    Buka Course Saya
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
