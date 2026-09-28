@extends('layouts.mahasiswa')

@section('title', 'Transkrip Nilai & KHS | SALE')
@section('header', 'Transkrip Nilai')

@section('content')
@php
    $semesterOptions = $semesterOptions ?? [];
    $selectedSemesterKey = $selectedSemester?->code ?? request('semester', array_key_first($semesterOptions) ?? '20261');
    $ips = $ips ?? 0.00;
    $ipk = $ipk ?? 0.00;
    $totalCredits = $totalCredits ?? 0;
    $courses = $courses ?? [];
@endphp

<div class="space-y-6">
    {{-- Header with Academic Year & Semester Selector --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-semibold text-muted uppercase tracking-wider">Hasil Evaluasi Belajar</p>
            <h1 class="page-heading">Transkrip Nilai &amp; Hasil Studi</h1>
            <p class="page-description">Kartu Hasil Studi (KHS) mahasiswa berdasarkan evaluasi capaian perkuliahan.</p>
        </div>

        {{-- Semester & Tahun Penyesuaian --}}
        @if(!empty($semesterOptions))
            <form method="get" action="{{ route('mahasiswa.nilai') }}" class="flex flex-wrap items-center gap-2.5">
                <label for="semester" class="text-xs font-medium text-muted shrink-0">Tahun &amp; Semester:</label>
                <select id="semester" name="semester" onchange="this.form.submit()" class="field py-1.5 text-xs font-semibold min-h-9 sm:w-64">
                    @foreach($semesterOptions as $key => $opt)
                        <option value="{{ $opt['code'] ?? $key }}" @selected($selectedSemesterKey === ($opt['code'] ?? $key))>
                            {{ $opt['label'] }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </header>

    {{-- Tab Navigasi: Transkrip Nilai & Capaian Pembelajaran OBE --}}
    <div class="bg-[#f4f5f7] pt-2 pb-1">
        <nav class="flex border-b border-line/60 gap-6" aria-label="Tab nilai dan capaian">
            <a href="{{ route('mahasiswa.nilai') }}" class="pb-3 text-sm font-semibold border-b-2 -mb-px border-brand text-brand flex items-center gap-2 transition">
                <span>Transkrip Nilai (KHS)</span>
                <span class="rounded-full bg-canvas px-2 py-0.5 text-xs text-muted">{{ count($courses) }}</span>
            </a>
            <a href="{{ route('mahasiswa.obe.progress') }}" class="pb-3 text-sm font-medium border-b-2 -mb-px border-transparent text-muted hover:text-ink flex items-center gap-2 transition">
                <span>Capaian Pembelajaran OBE</span>
            </a>
        </nav>
        <div class="h-2 -mt-1 bg-[#f4f5f7] shadow-[0_8px_16px_-2px_rgba(29,39,48,0.10)] pointer-events-none" aria-hidden="true"></div>
    </div>

    {{-- Ringkasan Akademik Bersih --}}
    <div class="surface p-5 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm w-full">
        <div class="p-3.5 rounded-xl bg-canvas/40 border border-line/50 flex flex-col justify-between min-h-[78px]">
            <span class="text-xs text-muted block">Semester Dipilih</span>
            <span class="mt-1 font-semibold text-ink block truncate" title="{{ $selectedSemester?->name ?? ($semesterOptions[$selectedSemesterKey]['label'] ?? 'Semester Aktif') }}">{{ $selectedSemester?->name ?? ($semesterOptions[$selectedSemesterKey]['label'] ?? 'Semester Aktif') }}</span>
        </div>
        <div class="p-3.5 rounded-xl bg-canvas/40 border border-line/50 flex flex-col justify-between min-h-[78px]">
            <span class="text-xs text-muted block">Beban SKS Semester</span>
            <span class="mt-1 font-semibold text-ink block whitespace-nowrap">{{ $totalCredits }} SKS</span>
        </div>
        <div class="p-3.5 rounded-xl bg-canvas/40 border border-line/50 flex flex-col justify-between min-h-[78px]">
            <span class="text-xs text-muted block">IP Semester (IPS)</span>
            <span class="mt-1 font-bold text-ink text-base block whitespace-nowrap">{{ number_format($ips, 2, ',', '.') }}</span>
        </div>
        <div class="p-3.5 rounded-xl bg-canvas/40 border border-line/50 flex flex-col justify-between min-h-[78px]">
            <span class="text-xs text-muted block">IPK Kumulatif</span>
            <span class="mt-1 font-bold text-ink text-base block whitespace-nowrap">{{ number_format($ipk, 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- KHS Table (Klik baris langsung untuk melihat detail nilai) --}}
    <div class="surface overflow-hidden">
        <div class="border-b border-line/60 px-5 py-3.5 flex items-center justify-between">
            <h2 class="font-semibold text-ink text-sm">Daftar Mata Kuliah Semester</h2>
            <span class="text-xs text-muted">Klik baris untuk melihat Rincian Komponen Nilai</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-line/60 bg-white text-xs font-bold text-muted">
                        <th class="py-3 px-4">Kode &amp; Mata Kuliah</th>
                        <th class="py-3 px-4 text-center w-20 whitespace-nowrap">SKS</th>
                        <th class="py-3 px-4 text-center w-28 whitespace-nowrap">Nilai Angka</th>
                        <th class="py-3 px-4 text-center w-20 whitespace-nowrap">Huruf</th>
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
                                </p>
                            </td>
                            <td class="py-3.5 px-4 text-center text-ink font-medium whitespace-nowrap">
                                {{ $course['sks'] ?? 3 }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="font-semibold text-ink">
                                    {{ $course['final_score'] !== null ? number_format($course['final_score'], 1, ',', '.') : '' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="font-bold text-ink">
                                    {{ $course['letter'] ?? '' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <svg id="arrow-{{ $course['id'] }}" class="h-4 w-4 text-muted transition-transform duration-200 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </td>
                        </tr>

                        {{-- Expanded Details: Rincian Komponen Nilai --}}
                        <tr id="detail-{{ $course['id'] }}" hidden class="bg-slate-50/70">
                            <td colspan="5" class="px-5 sm:px-6 py-4 border-b border-line/50">
                                <div class="space-y-3 w-full">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                        <p class="text-xs font-bold text-ink">Rincian Komponen Nilai:</p>
                                        <span class="text-[11px] text-muted">Skor asesmen (0 - 100) dan bobot kontribusi terhadap nilai akhir</span>
                                    </div>

                                    @if(empty($course['components']))
                                        <div class="p-3 text-xs text-muted italic bg-white rounded-lg border border-line/40">
                                            Belum ada komponen asesmen yang dinilai untuk mata kuliah ini.
                                        </div>
                                    @else
                                        {{-- Grid kotak nilai: penuh dari kiri ke kanan rapat ke sisi kanan --}}
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 w-full">
                                            @foreach($course['components'] as $component)
                                                <div class="p-3 rounded-lg bg-white border border-line/60 shadow-2xs flex flex-col justify-between h-full min-h-[80px] hover:border-brand/40 transition">
                                                    <div>
                                                        <div class="flex items-start justify-between gap-1.5 min-w-0">
                                                            <span class="text-muted block text-xs font-medium truncate flex-1" title="{{ $component['name'] }}">{{ $component['name'] }}</span>
                                                            <span class="text-[10px] font-bold text-ink bg-slate-100 px-1.5 py-0.5 rounded shrink-0">{{ rtrim(rtrim(number_format($component['weight'], 1), '0'), '.') }}%</span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2.5 pt-2 border-t border-line/40 flex items-center justify-between">
                                                        <span class="text-[11px] text-muted font-medium">Nilai:</span>
                                                        <span class="font-mono font-bold text-sm text-ink whitespace-nowrap">
                                                            {{ $component['score'] !== null ? number_format($component['score'], 1) : '' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- Kotak Nilai Akhir Terpadu di Paling Kanan --}}
                                            <div class="p-3 rounded-lg bg-slate-50 border border-line/80 shadow-2xs flex flex-col justify-between h-full min-h-[80px]">
                                                <div>
                                                    <div class="flex items-start justify-between gap-1.5 min-w-0">
                                                        <span class="text-ink block text-xs font-bold whitespace-nowrap truncate flex-1">Total Nilai Akhir</span>
                                                        @if(!empty($course['letter']))
                                                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $course['letter'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="mt-2.5 pt-2 border-t border-line/60 flex items-center justify-between">
                                                    <span class="text-[11px] text-muted font-medium whitespace-nowrap">Skor Akhir:</span>
                                                    <span class="font-mono font-bold text-base text-ink whitespace-nowrap">
                                                        {{ $course['final_score'] !== null ? number_format($course['final_score'], 1) : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-xs text-muted">
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
