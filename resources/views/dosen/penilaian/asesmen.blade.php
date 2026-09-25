@extends('layouts.mahasiswa')

@section('title', 'Daftar Asesmen | SALE')
@section('header', 'Daftar Asesmen')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex items-center justify-between">
        <div>
            <h2 class="section-heading">2. Input Nilai per Komponen Asesmen</h2>
            <p class="mt-1 text-sm text-muted">Pilih instrumen asesmen untuk melihat dan menginputkan nilai mahasiswa per CPMK.</p>
        </div>
    </div>

    @if($assessments->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum Ada Komponen Asesmen</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Belum ada instrumen asesmen yang dibuat untuk kelas ini. Tambahkan komponen asesmen untuk memulai pengisian nilai.
            </p>
        </div>
    @else
        <div class="surface overflow-x-auto rounded-xl border border-line shadow-2xs">
            <table class="admin-table w-full text-left">
                <thead>
                    <tr class="border-b border-line bg-canvas/40">
                        <th class="py-3 px-4 text-xs font-medium text-muted">Kode</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted">Nama Asesmen</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted">Jenis</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted text-center">Bobot Nilai Akhir</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted">CPMK yang Diukur</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted">Status Penilaian</th>
                        <th class="py-3 px-4 text-right text-xs font-medium text-muted">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    @php
                        $totalStudents = $section->students()->count();
                        $obeService = $obe ?? app(\App\Services\ObeCalculationService::class);
                    @endphp
                    @foreach($assessments as $assessment)
                        @php
                            $gradedCount = \App\Models\StudentAssessmentScore::where('assessment_id', $assessment->id)
                                ->whereNotNull('score')
                                ->count();
                        @endphp
                        <tr class="hover:bg-canvas/30 transition-colors">
                            <td class="py-3 px-4 font-mono text-xs text-muted">{{ $assessment->code }}</td>
                            <td class="py-3 px-4 font-semibold text-ink text-sm">{{ $assessment->name }}</td>
                            <td class="py-3 px-4 capitalize text-xs text-muted">{{ $assessment->type }}</td>
                            <td class="py-3 px-4 text-center font-semibold text-ink text-xs">{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}%</td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($assessment->cpmks as $cpmk)
                                        @php
                                            $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-canvas text-ink border border-line/60">
                                            {{ $cpmk->code }} ({{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}%)
                                        </span>
                                    @empty
                                        <span class="text-xs text-muted">Belum dipetakan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if($totalStudents > 0 && $gradedCount >= $totalStudents)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $gradedCount }}/{{ $totalStudents }} Dinilai &bull; Selesai
                                    </span>
                                @elseif($gradedCount > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ $gradedCount }}/{{ $totalStudents }} Dinilai &bull; Perlu Dinilai
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-canvas text-muted border border-line">
                                        0/{{ $totalStudents }} Dinilai &bull; Belum Dinilai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="button-primary inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold shadow-2xs">
                                    Input Nilai
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-muted">Klik <strong>"Input Nilai"</strong> untuk mengisi nilai mahasiswa per CPMK yang diukur.</p>
    @endif
</div>
@endsection
