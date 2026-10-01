@extends('layouts.mahasiswa')

@section('title', 'Transkrip Nilai & KHS | SALE')
@section('header', 'Transkrip Nilai')

@section('content')
@php
    $semesterOptions = $semesterOptions ?? [];
    $selectedSemesterKey = $selectedSemester?->code ?? request('semester', array_key_first($semesterOptions) ?? '20261');
    $totalCredits = $totalCredits ?? 0;
    $courses = $courses ?? [];
@endphp

<div class="space-y-6 w-full">
    {{-- Header with Academic Year & Semester Selector --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <p class="mb-1 text-xs font-semibold text-muted uppercase tracking-wider">Hasil Evaluasi Belajar</p>
            <h1 class="page-heading">Transkrip Nilai &amp; Hasil Studi</h1>
            <p class="page-description">Kartu Hasil Studi (KHS) mahasiswa berdasarkan evaluasi capaian perkuliahan.</p>
        </div>

        {{-- Semester & Tahun Penyesuaian --}}
        @if(!empty($semesterOptions))
            <form method="get" action="{{ route('mahasiswa.nilai') }}" class="flex flex-col sm:items-end gap-1.5 shrink-0 w-full sm:w-auto sm:ml-auto">
                <label for="semester" class="text-xs font-medium text-muted shrink-0">Tahun &amp; Semester:</label>
                <select id="semester" name="semester" onchange="this.form.submit()" class="field py-1.5 text-xs font-semibold min-h-9 w-full sm:w-80">
                    @foreach($semesterOptions as $key => $opt)
                        <option value="{{ $opt['code'] ?? $key }}" @selected($selectedSemesterKey === ($opt['code'] ?? $key))>
                            {{ $opt['label'] }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </header>

    {{-- KHS Table (Klik baris langsung untuk melihat detail nilai) --}}
    <div class="surface overflow-hidden">
        <div class="border-b border-line/60 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-ink text-sm">Daftar Mata Kuliah ({{ $selectedSemester?->name ?? 'Semester Terpilih' }})</h2>
            <span class="text-xs text-muted">Total Beban: <strong class="font-semibold text-ink">{{ $totalCredits }} SKS</strong></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-line/60 bg-white text-xs font-bold text-muted">
                        <th class="py-3 px-4">Kode &amp; Mata Kuliah</th>
                        <th class="py-3 px-4 text-center w-20 whitespace-nowrap">SKS</th>
                        <th class="py-3 px-4 text-center w-16" aria-label="Status detail"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/30">
                    @forelse($courses as $course)
                        {{-- Row is clickable directly to reveal Rincian Komponen Nilai --}}
                        <tr onclick="document.getElementById('detail-{{ $course['id'] }}').toggleAttribute('hidden'); document.getElementById('arrow-{{ $course['id'] }}').classList.toggle('rotate-180');"
                            class="hover:bg-canvas/60 cursor-pointer transition select-none group">
                            <td class="py-3.5 px-4 min-w-[240px]">
                                <span class="font-semibold text-ink group-hover:text-brand transition block leading-snug">
                                    {{ $course['title'] }}
                                </span>
                                <p class="text-xs text-muted mt-0.5">
                                    {{ $course['code'] }} &bull; {{ $course['lecturer'] }}
                                    @if(!empty($course['semester_paket']))
                                        &bull; <span class="text-slate-600 font-semibold">Sem. {{ $course['semester_paket'] }}</span>
                                    @endif
                                </p>
                            </td>
                            <td class="py-3.5 px-4 text-center text-ink font-medium whitespace-nowrap">
                                {{ $course['sks'] ?? 3 }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <svg id="arrow-{{ $course['id'] }}" class="h-4 w-4 text-muted transition-transform duration-200 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </td>
                        </tr>

                        {{-- Expanded Details: Rincian Komponen Nilai --}}
                        <tr id="detail-{{ $course['id'] }}" hidden class="bg-slate-50/50">
                            <td colspan="3" class="px-6 py-4 border-b border-line/50">
                                <div class="space-y-3 w-full">
                                    <p class="text-xs font-semibold text-ink mb-2">Rincian Komponen Nilai:</p>

                                    @if(empty($course['components']))
                                        <div class="p-3 text-xs text-muted italic bg-white rounded-lg border border-line/40">
                                            Belum ada komponen asesmen yang dinilai untuk mata kuliah ini.
                                        </div>
                                    @else
                                        {{-- Kotak nilai desain lama: ukuran menyesuaikan penuh memenuhi card agar sisi samping tidak kosong --}}
                                        <div class="grid gap-2.5 w-full text-xs" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                                            @foreach($course['components'] as $component)
                                                <div class="p-2.5 rounded bg-white border border-line/40 shadow-2xs">
                                                    <span class="text-muted block text-[11px] truncate" title="{{ $component['name'] }}">{{ $component['name'] }}</span>
                                                    <span class="font-semibold text-ink mt-0.5 block text-sm">
                                                        {{ $component['score'] !== null ? number_format($component['score'], 1) : '-' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-xs text-muted">
                                Belum ada mata kuliah yang terdaftar pada semester ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
