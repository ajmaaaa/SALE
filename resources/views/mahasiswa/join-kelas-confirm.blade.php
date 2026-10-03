@extends('layouts.mahasiswa')

@section('title', 'Konfirmasi Bergabung Kelas | SALE')
@section('header', 'Bergabung ke Kelas Perkuliahan')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-4">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route(($isDosen ?? false) ? 'dosen.dashboard' : 'mahasiswa.dashboard') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>Dashboard</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800" aria-current="page">
            Konfirmasi Pendaftaran Kelas
        </span>
    </nav>
    <div class="surface p-8 space-y-6">
        <div class="text-center space-y-2">
            <div class="flex items-center justify-center mx-auto text-slate-700 py-1">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h1 class="page-heading text-xl">
                @if($alreadyEnrolled)
                    Anda Sudah Terdaftar
                @elseif($isKicked ?? false)
                    Daftar Ulang Kelas
                @else
                    Konfirmasi Pendaftaran Kelas
                @endif
            </h1>
            <p class="text-sm text-muted">
                @if($alreadyEnrolled)
                    Anda sudah terdaftar di kelas ini. Tidak ada tindakan yang diperlukan.
                @elseif($isKicked ?? false)
                    Anda sebelumnya dikeluarkan dari kelas ini. Silakan periksa pesan dosen di bawah sebelum mendaftar ulang.
                @elseif($isDosen ?? false)
                    Anda akan ditambahkan ke slot Dosen Ketua atau Dosen Pendamping yang masih tersedia.
                @else
                    Pastikan informasi kelas di bawah sudah benar sebelum mendaftar.
                @endif
            </p>
        </div>

        @if($isKicked ?? false)
            {{-- Card Riwayat & Pesan Pengeluaran Dosen --}}
            <div class="rounded-xl border border-line bg-canvas/60 p-4 text-left text-xs space-y-2.5">
                <div class="flex items-center gap-2 font-semibold text-ink">
                    <svg class="h-4 w-4 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>Riwayat Pengeluaran dari Kelas</span>
                </div>
                <p class="text-muted leading-relaxed">
                    Anda sebelumnya telah dikeluarkan dari kelas ini oleh dosen pengampu.
                </p>
                @if(!empty($enrollmentRecord?->kick_reason))
                    <div class="p-3 rounded-lg bg-white border border-line/70 space-y-1">
                        <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Pesan / Alasan Dosen:</span>
                        <p class="font-medium text-ink text-xs leading-relaxed">"{{ $enrollmentRecord->kick_reason }}"</p>
                    </div>
                @endif
                <p class="text-[11px] text-amber-700 font-medium">
                    Perhatian: Jika Anda dikeluarkan satu kali lagi (total 2 kali), akses Anda ke kelas ini akan dibatasi dan memerlukan proses verifikasi ke Admin Prodi.
                </p>
            </div>
        @endif

        <div class="border-t border-b border-line/60 py-5 space-y-3">
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
                    <span class="text-muted text-[11px] block">Dosen Ketua:</span>
                    <span class="font-bold text-ink">{{ $section->dosen?->name ?? '-' }}</span>
                </div>
                @php
                    $allAnggota = $section->relationLoaded('dosenAnggota') && $section->dosenAnggota->isNotEmpty()
                        ? $section->dosenAnggota
                        : ($section->dosenPendamping ? collect([$section->dosenPendamping]) : collect());
                @endphp
                @if($allAnggota->isNotEmpty())
                <div>
                    <span class="text-muted text-[11px] block">Dosen Anggota:</span>
                    <span class="font-bold text-ink">{{ $allAnggota->pluck('name')->join(', ') }}</span>
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
                <a href="{{ route(($isDosen ?? false) ? 'dosen.course.show' : 'mahasiswa.course.show', $section->id) }}" class="button-primary text-xs px-5 py-2.5">
                    Buka Course Saya
                </a>
            </div>
        @elseif($isFull ?? false)
            <div class="space-y-4">
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-center text-xs text-rose-800 space-y-1">
                    <p class="font-bold text-sm">Kapasitas Kelas Telah Penuh</p>
                    <p class="text-rose-700">Kelas ini telah mencapai kapasitas maksimal ({{ $section->capacity }} mahasiswa). Hubungi dosen pengampu atau admin program studi untuk penambahan kuota.</p>
                </div>
                <div class="flex justify-center gap-3">
                    <a href="{{ route('mahasiswa.dashboard') }}" class="button-secondary text-xs px-5 py-2.5">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('mahasiswa.join-kelas.post', $code) }}">
                @csrf
                <div class="flex justify-center gap-3">
                    <a href="{{ route(($isDosen ?? false) ? 'dosen.course.index' : 'mahasiswa.dashboard') }}" class="button-secondary text-xs px-5 py-2.5">
                        {{ ($isKicked ?? false) ? 'Batalkan / Bukan Kelas Saya' : 'Batal' }}
                    </a>
                    <button type="submit" class="button-primary text-xs px-5 py-2.5 cursor-pointer">
                        @if($isDosen ?? false)
                            Ya, Tambahkan Saya
                        @elseif($isKicked ?? false)
                            Daftar Ulang Kelas
                        @else
                            Ya, Daftarkan Saya
                        @endif
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
