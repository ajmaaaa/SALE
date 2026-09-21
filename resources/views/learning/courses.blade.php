@extends('layouts.mahasiswa')

@section('title', 'Course | SALE')
@section('header', 'Course')

@section('content')
<div class="space-y-7">
    <header class="flex flex-wrap items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="page-heading">Course</h1>
            <p class="page-description">Kelas aktif yang telah ditetapkan oleh program studi pada semester ini.</p>
        </div>
        <div class="flex items-center gap-2.5">
            @if(request()->is('dosen*'))
                <a class="button-primary" href="{{ route('dosen.course.create') }}">+ Tambah course</a>
            @else
                <button type="button" onclick="document.getElementById('join-class-modal').showModal()" class="button-secondary text-xs font-semibold">
                    + Gabung Kelas
                </button>
            @endif
        </div>
    </header>

    <form class="flex flex-col gap-3 sm:flex-row" action="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" method="GET">
        <label class="sr-only" for="course-search">Cari course</label>
        <input id="course-search" name="q" type="search" class="field sm:max-w-md" placeholder="Cari judul, kode, atau dosen" value="{{ request('q') }}">
        <button type="submit" class="button-secondary">Terapkan</button>
    </form>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar course">
        @forelse ($courses as $course)
            @include('learning.partials.course-card', ['course' => $course, 'isFirst' => $loop->first])
        @empty
            <p class="p-6 text-sm text-muted">Course tidak ditemukan. Coba kata pencarian lainnya.</p>
        @endforelse
    </section>

    {{-- Modal Gabung Kelas via Kode --}}
    <dialog id="join-class-modal" class="backdrop:bg-black/40 rounded-xl p-0 shadow-lg border border-line/60 w-full max-w-md overflow-hidden m-auto">
        <div class="p-5 border-b border-line/60 flex items-center justify-between">
            <h2 class="text-sm font-bold text-ink">Gabung Kelas Perkuliahan</h2>
            <button type="button" onclick="document.getElementById('join-class-modal').close()" class="text-muted hover:text-ink text-sm p-1">✕</button>
        </div>
        <form onsubmit="const c = document.getElementById('input-join-code').value.trim(); if(c) { location.href = '{{ url('/join-kelas') }}/' + encodeURIComponent(c); } return false;" class="p-5 space-y-4">
            <div>
                <label for="input-join-code" class="block text-xs font-semibold text-ink mb-1">Kode Masuk Kelas</label>
                <input id="input-join-code" type="text" placeholder="Contoh: IF204-2026" required class="field w-full font-mono uppercase text-sm tracking-wider">
                <p class="mt-1 text-[11px] text-muted">Dapatkan kode masuk atau tautan QR dari dosen pengampu kelas.</p>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('join-class-modal').close()" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs font-semibold">Gabung Kelas</button>
            </div>
        </form>
    </dialog>
</div>
@endsection
