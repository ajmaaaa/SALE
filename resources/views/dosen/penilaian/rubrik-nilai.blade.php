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
                    {{ $rubric->name }} · {{ $criteria->count() }} kriteria
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

            <div class="surface overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="sticky left-0 bg-canvas z-10">No</th>
                            <th class="sticky left-8 bg-canvas z-10 min-w-[100px]">NIM</th>
                            <th class="sticky left-[168px] bg-canvas z-10 min-w-[160px]">Nama</th>
                            @foreach($criteria as $criterion)
                                <th class="text-center min-w-[100px]">
                                    <div class="text-xs">{{ $criterion->name }}</div>
                                    <div class="text-[10px] text-muted font-normal">maks {{ number_format($criterion->max_score, 0) }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $i => $student)
                            <tr>
                                <td class="sticky left-0 bg-white z-10 text-muted">{{ $i + 1 }}</td>
                                <td class="sticky left-8 bg-white z-10 font-mono text-xs text-muted">{{ $student->nim_nidn ?? '—' }}</td>
                                <td class="sticky left-[168px] bg-white z-10 font-medium text-ink whitespace-nowrap">{{ $student->name }}</td>
                                @foreach($criteria as $criterion)
                                    @php
                                        $key = $criterion->id . ':' . $student->id;
                                        $existing = $existingScores[$key] ?? null;
                                        $currentValue = old("rubric_scores.{$student->id}.{$criterion->id}", $existing?->score);
                                    @endphp
                                    <td class="text-center">
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
