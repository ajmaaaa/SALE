@extends('layouts.mahasiswa')

@section('title', 'Daftar Asesmen | SALE')
@section('header', 'Daftar Asesmen')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="section-heading">Input Nilai per Komponen Asesmen</h2>
            <p class="mt-1 text-sm text-muted">Pilih instrumen asesmen untuk melihat dan menginputkan nilai mahasiswa per CPMK.</p>
        </div>
        <a href="{{ route('dosen.item.create', $section->id) }}" class="button-primary inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 shrink-0 shadow-2xs">
            <span>+ Tambah Konten / Asesmen</span>
        </a>
    </div>

    @if($assessments->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum Ada Komponen Asesmen</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto mb-5">
                Belum ada instrumen asesmen yang dibuat untuk kelas ini. Tambahkan komponen asesmen untuk memulai pengisian nilai.
            </p>
            <a href="{{ route('dosen.item.create', $section->id) }}" class="button-primary inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-2 shadow-2xs">
                <span>+ Tambah Konten / Asesmen Baru</span>
            </a>
        </div>
    @else
        <div class="surface overflow-x-auto rounded-xl border border-line shadow-2xs">
            <table class="admin-table w-full text-left">
                <thead>
                    <tr class="border-b border-line bg-canvas/40">
                        <th class="py-3 px-4 text-xs font-medium text-muted">Kode</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted">Nama Asesmen</th>
                        <th class="py-3 px-4 text-xs font-medium text-muted">Jenis</th>

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
                            <td class="py-3 px-4">
                                <div class="font-semibold text-ink text-sm">{{ $assessment->name }}</div>
                                @if($assessment->published_at)
                                    <div class="text-[11px] text-muted mt-0.5">Diterbitkan {{ $assessment->published_at->translatedFormat('d M Y, H:i') }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 capitalize text-xs text-muted">{{ $assessment->type }}</td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($assessment->cpmks as $cpmk)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-brand text-white tracking-wide" title="{{ $cpmk->description }}">
                                            {{ $cpmk->code }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-muted">Belum dipetakan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-4 text-xs font-semibold text-ink">
                                {{ $gradedCount }}/{{ $totalStudents }}
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
