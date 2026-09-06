@extends('layouts.mahasiswa')

@section('title', 'Rekap Nilai & Gradebook | SALE')
@section('header', 'Rekap Nilai Kelas')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('dosen.dashboard') }}" class="hover:text-brand">Dashboard</a>
                <span>/</span>
                <a href="{{ route('dosen.course.show', $course['id']) }}" class="hover:text-brand">{{ $course['code'] }}</a>
                <span>/</span>
                <span class="text-ink font-semibold">Gradebook</span>
            </nav>
            <h1 class="page-heading">Rekap Nilai Kelas</h1>
            <p class="page-description">Perhitungan nilai transparan berdasarkan multi-komponen bobot (Tugas, Kuis, UTS, UAS, Proyek, Partisipasi).</p>
        </div>
        <div class="flex flex-wrap gap-2.5 shrink-0">
            <button type="button" onclick="document.getElementById('bulk-score-section').toggleAttribute('hidden')" class="button-secondary">
                Input Nilai Massal
            </button>
            <a class="button-secondary" href="{{ route('dosen.academic', $course['id']) }}">
                Atur Bobot &amp; CPMK
            </a>
        </div>
    </header>

    {{-- Course Selector --}}
    <div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
        <form class="flex items-center gap-3 flex-1 min-w-[280px]">
            <label for="course" class="text-xs font-semibold text-muted shrink-0">Pilih Kelas:</label>
            <select name="course" id="course" onchange="this.form.submit()" class="field text-xs font-semibold">
                @foreach(\App\Support\LearningPreview::courses() as $c)
                    <option value="{{ $c['id'] }}" @selected($c['id'] === $course['id'])>
                        {{ $c['code'] }} · {{ $c['title'] }} ({{ $c['lecturer'] }})
                    </option>
                @endforeach
            </select>
        </form>

        <div class="flex items-center gap-3 text-xs text-muted">
            <span>Total Komponen: <strong class="text-ink">{{ count($config['components']) }}</strong></span>
            <span>Total Bobot: <strong class="text-brand">{{ array_sum(array_column($config['components'], 'weight')) }}%</strong></span>
        </div>
    </div>

    {{-- Bulk Score Section (Collapsible) --}}
    <section id="bulk-score-section" hidden class="surface p-6">
        <div class="flex items-center justify-between pb-3 border-b border-line/60">
            <div>
                <h2 class="text-base font-semibold text-ink">Input Nilai Massal (CSV / Salin-Tempel)</h2>
                <p class="mt-0.5 text-xs text-muted">Masukkan nilai seluruh mahasiswa sekaligus tanpa harus menginput satu per satu.</p>
            </div>
            <button type="button" onclick="document.getElementById('bulk-score-section').setAttribute('hidden', '')" class="text-xs text-muted hover:text-ink">
                Tutup
            </button>
        </div>

        <form class="mt-4 space-y-4" method="post" action="{{ route('dosen.scores.bulk', $course['id']) }}">
            @csrf
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="form-label text-xs" for="raw_scores">
                        Format Kolom: NIM, {{ implode(', ', array_column($config['components'], 'name')) }}
                    </label>
                    <button type="button" class="text-xs font-semibold text-brand hover:underline" onclick="
                        const template = '231011401234, 88, 92, 85, 90, 95, 100\n231011401235, 78, 85, 80, 82, 88, 90';
                        document.getElementById('raw_scores').value = template;
                    ">
                        Muat Contoh Nilai
                    </button>
                </div>
                <textarea id="raw_scores" name="raw_scores" rows="5" required class="field font-mono text-xs" placeholder="NIM, {{ implode(', ', array_column($config['components'], 'code')) }}&#10;231011401234, 88, 92, 85, 90, 95, 100"></textarea>
                <p class="mt-1 text-xs text-muted">Nilai berkisar 0–100. Pisahkan data mahasiswa dengan baris baru, dan nilai tiap komponen dengan koma (,) atau tab.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="button-primary text-xs py-2">
                    Terapkan Nilai Massal
                </button>
                <button type="button" onclick="document.getElementById('bulk-score-section').setAttribute('hidden', '')" class="button-secondary text-xs py-2">
                    Batal
                </button>
            </div>
        </form>
    </section>

    {{-- Main Gradebook Table --}}
    <form method="post" action="{{ route('dosen.scores.save', $course['id']) }}">
        @csrf
        <div class="surface overflow-x-auto">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">NIM</th>
                        <th>Mahasiswa</th>
                        @foreach($config['components'] as $component)
                            <th class="text-center min-w-[90px]">
                                {{ $component['name'] }}
                                <span class="mt-0.5 block text-xs font-normal text-muted">{{ $component['weight'] }}%</span>
                            </th>
                        @endforeach
                        <th class="text-center min-w-[120px]">Nilai Akhir</th>
                        <th class="text-center">Indeks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $result = \App\Support\AcademicPreview::result($course['id'], $student['id']);
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
                        <tr>
                            <td class="font-mono text-xs font-semibold text-ink whitespace-nowrap">
                                {{ $student['number'] }}
                            </td>
                            <td class="font-semibold text-ink whitespace-nowrap">
                                {{ $student['name'] }}
                            </td>
                            @foreach($config['components'] as $component)
                                <td class="text-center p-2">
                                    <input aria-label="{{ $student['name'].' '.$component['name'] }}"
                                        class="field text-center font-mono text-xs py-1.5 px-2 max-w-[85px] mx-auto block"
                                        type="number" min="0" max="100" step="0.01"
                                        name="scores[{{ $student['id'] }}][{{ $component['code'] }}]"
                                        value="{{ $result['scores'][$component['code']] ?? '' }}"
                                        placeholder="—">
                                </td>
                            @endforeach
                            <td class="text-center whitespace-nowrap">
                                <span class="text-sm font-bold text-ink">{{ $result['average'] !== null ? number_format($result['average'], 1, ',', '.') : '—' }}</span>
                                <span class="block text-xs text-muted">
                                    {{ $result['complete'] ? 'Lengkap' : $result['coverage'].'% dinilai' }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <span class="font-bold text-ink">
                                    {{ $letter }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($config['components']) + 4 }}" class="p-6 text-center text-xs text-muted">
                                Belum ada mahasiswa terdaftar di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <p class="text-xs text-muted leading-relaxed max-w-xl">
                Skala nilai: 0–100. Komponen tugas dihitung dari rata-rata pengumpulan tugas mahasiswa jika sudah dinilai pengampu.
            </p>
            <div class="flex items-center gap-3">
                <a href="{{ route('dosen.grades') }}" class="quiet-link text-xs">
                    Tinjau pengumpulan tugas →
                </a>
                <button type="submit" class="button-primary text-xs">
                    Simpan Semua Nilai
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
