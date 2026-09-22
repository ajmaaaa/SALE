@extends('layouts.mahasiswa')

@section('title', 'Transkrip Nilai & KHS | SALE')
@section('header', 'Transkrip Nilai')

@section('content')
@php
    $studentId = session('auth_user.id') ?? 1;
    $selectedSemester = request('semester', '2026-ganjil');

    $semesterOptions = [
        '2026-ganjil' => ['label' => '2026/2027 Ganjil (Semester 5)', 'ips' => 3.85, 'ipk' => 3.65, 'sks' => 12, 'status' => 'Aktif'],
        '2025-genap'  => ['label' => '2025/2026 Genap (Semester 4)',  'ips' => 3.80, 'ipk' => 3.65, 'sks' => 20, 'status' => 'Selesai'],
        '2025-ganjil' => ['label' => '2025/2026 Ganjil (Semester 3)',  'ips' => 3.74, 'ipk' => 3.61, 'sks' => 22, 'status' => 'Selesai'],
        '2024-genap'  => ['label' => '2024/2025 Genap (Semester 2)',  'ips' => 3.62, 'ipk' => 3.54, 'sks' => 22, 'status' => 'Selesai'],
        '2024-ganjil' => ['label' => '2024/2025 Ganjil (Semester 1)',  'ips' => 3.45, 'ipk' => 3.45, 'sks' => 20, 'status' => 'Selesai'],
    ];

    $currentSemInfo = $semesterOptions[$selectedSemester] ?? $semesterOptions['2026-ganjil'];

    $totalCredits = 0;
    $totalWeightedScore = 0;
    $gradedCourses = 0;

    foreach($courses as $c) {
        $res = \App\Support\AcademicPreview::result($c['id'], $studentId);
        $totalCredits += 3;
        if ($res['average'] !== null) {
            $totalWeightedScore += ($res['average'] / 25) * 3;
            $gradedCourses++;
        }
    }
    $gpa = $gradedCourses > 0 ? round($totalWeightedScore / ($gradedCourses * 3), 2) : $currentSemInfo['ips'];
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
        <form method="get" action="{{ route('mahasiswa.nilai') }}" class="flex flex-wrap items-center gap-2.5">
            <label for="semester" class="text-xs font-medium text-muted shrink-0">Tahun &amp; Semester:</label>
            <select id="semester" name="semester" onchange="this.form.submit()" class="field py-1.5 text-xs font-semibold min-h-9 sm:w-64">
                @foreach($semesterOptions as $key => $opt)
                    <option value="{{ $key }}" @selected($selectedSemester === $key)>
                        {{ $opt['label'] }}
                    </option>
                @endforeach
            </select>
        </form>
    </header>

    {{-- Ringkasan Akademik Bersih --}}
    <div class="surface p-5 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
            <span class="text-xs text-muted block">Semester Dipilih</span>
            <span class="mt-1 font-semibold text-ink block truncate">{{ $currentSemInfo['label'] }}</span>
        </div>
        <div>
            <span class="text-xs text-muted block">Beban SKS Semester</span>
            <span class="mt-1 font-semibold text-ink block">{{ $totalCredits }} SKS</span>
        </div>
        <div>
            <span class="text-xs text-muted block">IP Semester (IPS)</span>
            <span class="mt-1 font-bold text-ink text-base block">{{ number_format($gpa, 2, ',', '.') }}</span>
        </div>
        <div>
            <span class="text-xs text-muted block">IPK Kumulatif</span>
            <span class="mt-1 font-bold text-ink text-base block">{{ number_format($currentSemInfo['ipk'], 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- KHS Table (Klik baris langsung untuk melihat detail nilai & CPMK) --}}
    <div class="surface overflow-hidden">
        <div class="border-b border-line/60 px-5 py-3.5 flex items-center justify-between">
            <h2 class="font-semibold text-ink text-sm">Daftar Mata Kuliah Semester</h2>
            <span class="text-xs text-muted">Klik baris untuk melihat Rincian Komponen Nilai &amp; CPMK</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-line/60 bg-white text-xs font-bold text-muted">
                        <th class="py-3 px-4">Kode &amp; Mata Kuliah</th>
                        <th class="py-3 px-4 text-center w-20">SKS</th>
                        <th class="py-3 px-4 text-center w-28">Nilai Angka</th>
                        <th class="py-3 px-4 text-center w-20">Huruf</th>
                        <th class="py-3 px-4 text-center w-16" aria-label="Status detail"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/30">
                    @foreach($courses as $course)
                        @php
                            $result = \App\Support\AcademicPreview::result($course['id'], $studentId);
                            $config = \App\Support\AcademicPreview::config($course['id']);
                            $finalScore = $result['average'] ?? null;
                            $letter = '—';

                            if ($finalScore !== null) {
                                if ($finalScore >= 85) { $letter = 'A'; }
                                elseif ($finalScore >= 80) { $letter = 'A-'; }
                                elseif ($finalScore >= 75) { $letter = 'B+'; }
                                elseif ($finalScore >= 70) { $letter = 'B'; }
                                elseif ($finalScore >= 65) { $letter = 'B-'; }
                                elseif ($finalScore >= 60) { $letter = 'C+'; }
                                elseif ($finalScore >= 55) { $letter = 'C'; }
                                elseif ($finalScore >= 40) { $letter = 'D'; }
                                else { $letter = 'E'; }
                            }
                        @endphp
                        {{-- Row is clickable directly to reveal Rincian Komponen Nilai & Capaian CPMK --}}
                        <tr onclick="document.getElementById('detail-{{ $course['id'] }}').toggleAttribute('hidden'); document.getElementById('arrow-{{ $course['id'] }}').classList.toggle('rotate-180');"
                            class="hover:bg-canvas/60 cursor-pointer transition select-none group">
                            <td class="py-3.5 px-4 min-w-[240px]">
                                <span class="font-semibold text-ink group-hover:text-brand transition">
                                    {{ $course['title'] }}
                                </span>
                                <p class="text-xs text-muted mt-0.5">
                                    {{ $course['code'] }} - {{ $course['lecturer'] }}
                                </p>
                            </td>
                            <td class="py-3.5 px-4 text-center text-ink font-medium">
                                3
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-semibold text-ink">
                                    {{ $finalScore !== null ? number_format($finalScore, 1, ',', '.') : '—' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-ink">
                                    {{ $letter }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <svg id="arrow-{{ $course['id'] }}" class="h-4 w-4 text-muted transition-transform duration-200 inline-block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </td>
                        </tr>

                        {{-- Expanded Details: Rincian Komponen Nilai & Capaian CPMK --}}
                        <tr id="detail-{{ $course['id'] }}" hidden class="bg-slate-50/50">
                            <td colspan="5" class="px-6 py-4 border-b border-line/50">
                                <div class="space-y-4 max-w-4xl">
                                    {{-- Rincian Komponen Nilai --}}
                                    <div>
                                        <p class="text-xs font-semibold text-ink mb-2">Rincian Komponen Nilai:</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-xs">
                                            @foreach($config['components'] as $component)
                                                @php
                                                    $score = $result['scores'][$component['code']] ?? null;
                                                @endphp
                                                <div class="p-2.5 rounded bg-white border border-line/40">
                                                    <span class="text-muted block text-[11px]">{{ $component['name'] }} ({{ $component['weight'] }}%)</span>
                                                    <span class="font-semibold text-ink mt-0.5 block">
                                                        {{ $score !== null ? number_format($score, 1, ',', '.') : '—' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Capaian CPMK dengan evaluasi skor otentik --}}
                                    <div class="pt-3 border-t border-line/40">
                                        <p class="text-xs font-semibold text-ink mb-2">Capaian CPMK (Evaluasi Pembelajaran):</p>
                                        <div class="space-y-2">
                                            @foreach(\App\Support\AcademicPreview::breakdown($course['id'])['cpmk'] as $cpmk)
                                                @php
                                                    $cpmkScore = $cpmk['score'];
                                                    $isPassed = $cpmk['passed'];
                                                @endphp
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded bg-white border border-line/40 text-xs">
                                                    <div class="min-w-0 flex-1">
                                                        <span class="font-bold text-ink">{{ $cpmk['code'] }}</span>
                                                        <span class="text-muted ml-1.5">{{ $cpmk['description'] }} (Batas {{ $cpmk['threshold'] }}/100)</span>
                                                    </div>
                                                    <div class="shrink-0 font-medium sm:text-right">
                                                        @if($cpmkScore !== null)
                                                            <span class="font-bold text-ink">{{ number_format($cpmkScore, 1, ',', '.') }}</span>
                                                            <span class="text-muted">/ 100</span>
                                                            <span class="ml-2 {{ $isPassed ? 'text-ink' : 'text-danger' }}">({{ $isPassed ? 'Tercapai' : 'Belum Tercapai' }})</span>
                                                        @else
                                                            <span class="text-muted">Belum lengkap dinilai</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
