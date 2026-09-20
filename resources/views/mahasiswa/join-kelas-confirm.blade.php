@extends('layouts.mahasiswa')

@section('title', 'Konfirmasi Bergabung Kelas | SALE')
@section('header', 'Bergabung ke Kelas Perkuliahan')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="surface p-8 space-y-6">
        <div class="text-center space-y-2">
            <div class="h-16 w-16 rounded-full bg-brand/10 text-brand flex items-center justify-center mx-auto">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h1 class="page-heading text-xl">
                @if($alreadyEnrolled)
                    Anda Sudah Terdaftar
                @else
                    Konfirmasi Pendaftaran Kelas
                @endif
            </h1>
            <p class="text-sm text-muted">
                @if($alreadyEnrolled)
                    Anda sudah terdaftar di kelas ini. Tidak ada tindakan yang diperlukan.
                @else
                    Pastikan informasi kelas di bawah sudah benar sebelum mendaftar.
                @endif
            </p>
        </div>

        <!-- Card Rincian Kelas -->
        <div class="rounded-xl border border-line bg-canvas/40 p-5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-line">
                <span class="px-2 py-0.5 rounded bg-brand text-white font-mono font-bold text-xs">{{ $section->display_code }}</span>
                <span class="text-xs text-muted">{{ $section->semester->name ?? 'Semester Aktif' }}</span>
            </div>

            <div>
                <h3 class="text-base font-bold text-ink">{{ $section->mataKuliah->name }}</h3>
                <p class="text-xs text-muted">{{ $section->mataKuliah->prodi->name ?? '' }} &middot; {{ $section->mataKuliah->sks }} SKS</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                <div>
                    <span class="text-muted text-[11px] block">Dosen Ketua:</span>
                    <span class="font-bold text-ink">{{ $section->dosen?->name ?? '-' }}</span>
                </div>
                @if($section->dosenPendamping)
                <div>
                    <span class="text-muted text-[11px] block">Dosen Wakil:</span>
                    <span class="font-bold text-ink">{{ $section->dosenPendamping->name }}</span>
                </div>
                @endif
                @if($section->capacity)
                <div>
                    <span class="text-muted text-[11px] block">Kapasitas:</span>
                    <span class="font-bold text-ink">{{ $section->students_count }} / {{ $section->capacity }} mahasiswa</span>
                </div>
                @endif
            </div>
        </div>

        @if($alreadyEnrolled)
            <div class="flex justify-center gap-3">
                <a href="{{ route('mahasiswa.course.index') }}" class="button-primary text-xs px-5 py-2.5">
                    Buka Course Saya
                </a>
            </div>
        @else
            {{-- S06: Gunakan form POST dengan CSRF protection --}}
            <form method="POST" action="{{ route('mahasiswa.join-kelas.post', $code) }}">
                @csrf
                <div class="flex justify-center gap-3">
                    <a href="{{ route('mahasiswa.dashboard') }}" class="button-secondary text-xs px-5 py-2.5">
                        Batal
                    </a>
                    <button type="submit" class="button-primary text-xs px-5 py-2.5">
                        Ya, Daftarkan Saya
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
