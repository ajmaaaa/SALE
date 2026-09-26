@extends('layouts.mahasiswa')

@section('title', 'Nilai Rubrik — ' . $assessment->name . ' | SALE')
@section('header', 'Nilai Rubrik')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <a href="{{ route('dosen.penilaian.asesmen.rubrik', [$section->id, $assessment->id]) }}" class="hover:text-brand">Rubrik</a>
            <span>/</span>
            <span class="text-ink font-semibold">Input Nilai Rubrik</span>
        </nav>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="section-heading">Input Nilai Rubrik: {{ $assessment->name }}</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ $rubric->name }} ({{ $criteria->count() }} kriteria)
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('dosen.penilaian.asesmen.rubrik', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Kelola Kriteria</a>
                <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Input Langsung</a>
            </div>
        </div>
    </header>

    {{-- Criteria legend --}}
    <div class="surface p-4">
        <p class="text-xs font-semibold text-muted mb-2">KRITERIA RUBRIK</p>
        <div class="flex flex-wrap gap-3">
            @foreach($criteria as $criterion)
                <div class="text-xs">
                    <span class="font-semibold text-ink">{{ $criterion->name }}</span>
                    <span class="text-muted">
                        ({{ rtrim(rtrim(number_format($criterion->weight, 1), '0'), '.') }}%, maks {{ rtrim(rtrim(number_format($criterion->max_score, 1), '0'), '.') }})
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    @if($students->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada mahasiswa terdaftar</h2>
            <p class="mt-2 text-sm text-muted">Mahasiswa harus terdaftar di kelas ini sebelum dapat dinilai.</p>
        </div>
    @else
        <form method="post" action="{{ route('dosen.penilaian.asesmen.rubrik.nilai.store', [$section->id, $assessment->id]) }}">
            @csrf

            <div class="surface overflow-x-auto relative">
                <table class="border-separate border-spacing-0 text-xs w-full" style="min-width: max-content;">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-800 text-left">
                            <th class="py-3 px-3 text-center text-muted font-semibold w-12 min-w-[48px] max-w-[48px] sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 border-b border-r border-line/50">No</th>
                            <th class="py-3 px-3 text-left text-muted font-semibold w-[130px] min-w-[130px] max-w-[130px] sticky left-[48px] bg-slate-100 dark:bg-slate-800 z-20 border-b border-r border-line/40">NIM</th>
                            <th class="py-3 px-3 text-left text-muted font-semibold w-[200px] min-w-[200px] max-w-[220px] sticky left-[178px] bg-slate-100 dark:bg-slate-800 z-20 border-b border-r-2 border-line/80 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.08)]">Nama</th>
                            @foreach($criteria as $criterion)
                                <th class="py-3 px-3 text-center min-w-[100px] border-b border-l border-line/40 bg-slate-100 dark:bg-slate-800">
                                    <div class="text-xs font-semibold text-ink">{{ $criterion->name }}</div>
                                    <div class="text-[10px] text-muted font-normal">maks {{ number_format($criterion->max_score, 0) }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $i => $student)
                            <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-center text-muted w-12 min-w-[48px] max-w-[48px] sticky left-0 bg-white group-hover:bg-slate-50 dark:bg-slate-900 dark:group-hover:bg-slate-800 z-10 border-b border-r border-line/40">{{ $i + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono text-xs text-muted w-[130px] min-w-[130px] max-w-[130px] sticky left-[48px] bg-white group-hover:bg-slate-50 dark:bg-slate-900 dark:group-hover:bg-slate-800 z-10 border-b border-r border-line/40">{{ $student->nim_nidn ?? '—' }}</td>
                                <td class="py-2.5 px-3 font-medium text-ink w-[200px] min-w-[200px] max-w-[220px] sticky left-[178px] bg-white group-hover:bg-slate-50 dark:bg-slate-900 dark:group-hover:bg-slate-800 z-10 border-b border-r-2 border-line/80 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.08)]">
                                    <div class="truncate" title="{{ $student->name }}">{{ $student->name }}</div>
                                </td>
                                @foreach($criteria as $criterion)
                                    @php
                                        $key = $criterion->id . ':' . $student->id;
                                        $existing = $existingScores[$key] ?? null;
                                        $currentValue = old("rubric_scores.{$student->id}.{$criterion->id}", $existing?->score);
                                    @endphp
                                    <td class="py-2.5 px-2 text-center border-b border-l border-line/30">
                                        <input type="number"
                                               name="rubric_scores[{{ $student->id }}][{{ $criterion->id }}]"
                                               value="{{ $currentValue !== null ? $currentValue : '' }}"
                                               min="0"
                                               max="{{ $criterion->max_score }}"
                                               step="0.01"
                                               class="field text-sm w-20 text-center mx-auto"
                                               placeholder="—">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between mt-4">
                <p class="text-xs text-muted max-w-lg">
                    Nilai kosong berarti "belum dinilai". Setelah disimpan, nilai asesmen akan otomatis dihitung dari bobot kriteria rubrik.
                </p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-sm">Kembali</a>
                    <button type="submit" class="button-primary">Simpan Nilai Rubrik</button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection
