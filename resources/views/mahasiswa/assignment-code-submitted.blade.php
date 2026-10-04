@extends('layouts.mahasiswa')

@section('title', 'Status Pengumpulan Tugas | SALE')
@section('header', 'Pengumpulan Tugas Pemrograman')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-4 justify-center">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('mahasiswa.dashboard') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>Dashboard</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ route('mahasiswa.course.show', $section->id) }}">
            <span>{{ $section->mataKuliah->name ?? 'Course' }}</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800" aria-current="page">
            Status Pengumpulan
        </span>
    </nav>

    <div class="surface p-8 text-center space-y-6">
        <div class="flex items-center justify-center mx-auto text-emerald-600">
            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
        </div>

        <div>
            <h1 class="page-heading text-xl">Tugas Pemrograman Berhasil Diserahkan</h1>
            <p class="text-xs text-muted mt-1">Seluruh berkas kode program Anda telah berhasil disimpan dan diserahkan ke dosen pengampu.</p>
        </div>

        <div class="border-t border-b border-line/60 py-5 text-left space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-line/40">
                <span class="px-2.5 py-1 rounded bg-brand text-white font-mono font-bold text-xs">{{ $section->display_code }}</span>
                <span class="text-xs text-muted">{{ $assessment->module ?? 'Tugas Pemrograman' }}</span>
            </div>

            <div>
                <h3 class="text-base font-bold text-ink">{{ $assessment->title ?? $assessment->name }}</h3>
                <p class="text-xs text-muted">{{ $section->mataKuliah->name ?? '' }}@if(!empty($section->mataKuliah->prodi->name)) ({{ $section->mataKuliah->prodi->name }})@endif</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                <div>
                    <span class="text-muted text-[11px] block">Waktu Penyerahan:</span>
                    <span class="font-bold text-ink">
                        @if($submission && $submission->submitted_at)
                            {{ \Carbon\Carbon::parse($submission->submitted_at)->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB
                        @else
                            Baru saja
                        @endif
                    </span>
                </div>
                <div>
                    <span class="text-muted text-[11px] block">Status Penyerahan:</span>
                    <span class="font-bold text-emerald-600">Sudah Diserahkan</span>
                </div>
            </div>
        </div>

        <div class="flex justify-center gap-3 pt-2">
            <a href="{{ route('mahasiswa.course.show', $section->id) }}?tab=tugas" class="button-primary text-xs px-6 py-2.5">
                Kembali
            </a>
        </div>
    </div>
</div>
@endsection
