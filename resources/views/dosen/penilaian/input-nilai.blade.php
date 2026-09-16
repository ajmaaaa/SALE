@extends('layouts.mahasiswa')

@section('title', 'Input Nilai: ' . $assessment->name . ' | SALE')
@section('header', 'Input Nilai Asesmen')

@section('content')
<div class="space-y-6 max-w-6xl">
    <!-- Breadcrumbs & Header -->
    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <a href="{{ route('dosen.penilaian.asesmen.show', [$section->id, $assessment->id]) }}" class="hover:text-brand">{{ $assessment->code }}</a>
            <span>/</span>
            <span class="text-ink font-semibold">Input Nilai</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="page-heading">Input Nilai: {{ $assessment->name }} <span class="font-mono text-base font-normal text-muted">({{ $assessment->code }})</span></h1>
                <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->mataKuliah->name }}</p>
            </div>
            <div class="flex items-center flex-wrap gap-2">
                <a href="{{ route('dosen.penilaian.asesmen.template', [$section->id, $assessment->id]) }}" class="button-secondary text-xs inline-flex items-center gap-1.5" title="Unduh template Excel/CSV yang sesuai dengan asesmen ini">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Unduh Template Excel/CSV
                </a>
                <a href="{{ route('dosen.penilaian.asesmen.show', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Detail & Rubrik</a>
                <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-xs">Kembali</a>
            </div>
        </div>
    </header>

    @if(session('notice'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between">
            <span>{{ session('notice') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 space-y-1">
            <div class="font-semibold">Terjadi kesalahan validasi:</div>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Section Import Nilai (Step 16 & 17) -->
    <section class="surface p-4 border border-line/60 rounded-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-ink flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Impor Nilai dari File Excel / CSV
                </h3>
                <p class="text-xs text-muted mt-0.5">
                    Upload file template hasil unduhan yang sudah diisi nilai. Data akan melalui tahapan <strong>Preview &amp; Validasi</strong> sebelum disimpan ke database.
                </p>
            </div>
            <form method="POST" action="{{ route('dosen.penilaian.asesmen.import.upload', [$section->id, $assessment->id]) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required class="text-xs text-muted file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-soft file:text-brand hover:file:bg-brand/20 cursor-pointer">
                <button type="submit" class="button-primary text-xs shrink-0">Upload &amp; Preview</button>
            </form>
        </div>
    </section>

    <!-- Kartu Status Asesmen -->
    <section class="surface p-4 flex flex-wrap items-center justify-between gap-4 text-sm">
        <div class="flex items-center gap-6">
            <div>
                <span class="text-xs text-muted block">Jenis</span>
                <span class="font-semibold text-ink capitalize">{{ $assessment->type }}</span>
            </div>
            <div>
                <span class="text-xs text-muted block">Bobot Nilai Akhir</span>
                <span class="font-semibold text-ink">{{ rtrim(rtrim(number_format($assessment->final_weight, 2), '0'), '.') }}%</span>
            </div>
            <div>
                <span class="text-xs text-muted block">Metode Penilaian</span>
                <span>
                    @if($assessment->uses_rubric && $assessment->rubric)
                        <span class="status bg-emerald-50 text-emerald-700 font-semibold">Rubrik ({{ $criteria->count() }} Kriteria)</span>
                    @else
                        <span class="status bg-canvas text-muted font-semibold">Nilai Langsung (0-100)</span>
                    @endif
                </span>
            </div>
        </div>
        <div class="text-xs text-muted">
            Kosongkan nilai jika mahasiswa belum dinilai (akan tetap bernilai <span class="font-mono font-semibold">NULL</span>, bukan 0).
        </div>
    </section>

    <!-- Form Input Nilai -->
    <form method="POST" action="{{ route('dosen.penilaian.asesmen.nilai.store', [$section->id, $assessment->id]) }}" id="grade-form">
        @csrf

        @if($students->isEmpty())
            <div class="surface p-10 text-center">
                <h2 class="section-heading">Belum Ada Mahasiswa</h2>
                <p class="mt-2 text-sm text-muted">Belum ada mahasiswa yang terdaftar di kelas ini.</p>
            </div>
        @else
            <div class="surface overflow-x-auto">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th class="min-w-[140px]">NIM</th>
                            <th class="min-w-[200px]">Nama Mahasiswa</th>

                            @if($assessment->uses_rubric && $assessment->rubric)
                                @foreach($criteria as $crit)
                                    <th class="w-28 text-center criterion-header" data-weight="{{ $crit->weight }}" data-max="{{ $crit->max_score ?: 100 }}">
                                        {{ $crit->name }}
                                        <span class="block text-[11px] font-normal text-muted">
                                            {{ rtrim(rtrim(number_format($crit->weight, 2), '0'), '.') }}% (Max: {{ (int)$crit->max_score }})
                                        </span>
                                    </th>
                                @endforeach
                                <th class="w-24 text-center">Nilai Akhir</th>
                            @else
                                <th class="w-32 text-center">Nilai (0–100)</th>
                            @endif

                            <th class="w-28 text-center">Status</th>
                            <th class="min-w-[200px]">Catatan / Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $idx => $student)
                            @php
                                $aScore = $assessmentScores->get($student->id);
                                $finalScoreVal = $aScore?->score;
                                $sFeedback = $aScore?->feedback;
                                $rScores = $rubricScores->get($student->id);
                            @endphp
                            <tr class="student-grading-row" data-student-id="{{ $student->id }}">
                                <td class="text-center font-mono text-xs text-muted">{{ $idx + 1 }}</td>
                                <td class="font-mono text-xs text-ink">{{ $student->nim_nidn ?? '—' }}</td>
                                <td class="font-medium text-ink">{{ $student->name }}</td>

                                @if($assessment->uses_rubric && $assessment->rubric)
                                    @foreach($criteria as $crit)
                                        @php
                                            $critScore = $rScores?->get($crit->id)?->score;
                                            $oldVal = old("rubric_scores.{$student->id}.{$crit->id}", $critScore !== null ? (float)$critScore : '');
                                        @endphp
                                        <td class="text-center">
                                            <input type="number" step="0.01" min="0" max="{{ $crit->max_score ?: 100 }}"
                                                   name="rubric_scores[{{ $student->id }}][{{ $crit->id }}]"
                                                   value="{{ $oldVal }}"
                                                   data-crit-id="{{ $crit->id }}"
                                                   data-weight="{{ $crit->weight }}"
                                                   data-max="{{ $crit->max_score ?: 100 }}"
                                                   class="field text-sm text-center w-24 p-1.5 rubric-score-input"
                                                   placeholder="—"
                                                   oninput="recalculateRow(this)">
                                        </td>
                                    @endforeach
                                    <td class="text-center font-semibold font-mono text-sm final-score-cell">
                                        {{ $finalScoreVal !== null ? rtrim(rtrim(number_format($finalScoreVal, 2), '0'), '.') : '—' }}
                                    </td>
                                @else
                                    @php
                                        $oldDirect = old("scores.{$student->id}", $finalScoreVal !== null ? (float)$finalScoreVal : '');
                                    @endphp
                                    <td class="text-center">
                                        <input type="number" step="0.01" min="0" max="100"
                                               name="scores[{{ $student->id }}]"
                                               value="{{ $oldDirect }}"
                                               class="field text-sm text-center w-24 p-1.5 direct-score-input"
                                               placeholder="0–100"
                                               oninput="updateDirectStatus(this)">
                                    </td>
                                @endif

                                <td class="text-center status-cell">
                                    @if($finalScoreVal === null)
                                        <span class="status bg-canvas text-muted text-xs">Belum Dinilai</span>
                                    @elseif($finalScoreVal >= 65)
                                        <span class="status bg-emerald-50 text-emerald-700 text-xs font-semibold">Memenuhi</span>
                                    @else
                                        <span class="status bg-amber-50 text-amber-700 text-xs font-semibold">Evaluasi</span>
                                    @endif
                                </td>

                                <td>
                                    <input type="text" name="feedback[{{ $student->id }}]"
                                           value="{{ old("feedback.{$student->id}", $sFeedback ?? '') }}"
                                           class="field text-xs w-full" placeholder="Komentar pembimbing...">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Action Bar -->
            <div class="surface p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-xs text-muted">
                    Total Mahasiswa: <span class="font-semibold text-ink">{{ $students->count() }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="button-primary text-xs px-5 py-2.5">Simpan Seluruh Nilai</button>
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-xs">Batal</a>
                </div>
            </div>
        @endif
    </form>
</div>

<script>
    function recalculateRow(input) {
        const row = input.closest('.student-grading-row');
        if (!row) return;

        const inputs = row.querySelectorAll('.rubric-score-input');
        let weightedSum = 0;
        let weightGraded = 0;
        let hasAnyGraded = false;

        inputs.forEach(inp => {
            const val = parseFloat(inp.value);
            const weight = parseFloat(inp.dataset.weight);
            const maxScore = parseFloat(inp.dataset.max) || 100;

            if (!isNaN(val)) {
                hasAnyGraded = true;
                const normalized = (val / maxScore) * 100;
                weightedSum += normalized * (weight / 100);
                weightGraded += weight;
            }
        });

        const scoreCell = row.querySelector('.final-score-cell');
        const statusCell = row.querySelector('.status-cell');

        if (!hasAnyGraded || weightGraded <= 0) {
            if (scoreCell) scoreCell.textContent = '—';
            if (statusCell) statusCell.innerHTML = '<span class="status bg-canvas text-muted text-xs">Belum Dinilai</span>';
            return;
        }

        const finalScore = (weightedSum / (weightGraded / 100)).toFixed(2).replace(/\.?0+$/, '');
        if (scoreCell) scoreCell.textContent = finalScore;

        const scoreNum = parseFloat(finalScore);
        if (statusCell) {
            if (scoreNum >= 65) {
                statusCell.innerHTML = '<span class="status bg-emerald-50 text-emerald-700 text-xs font-semibold">Memenuhi</span>';
            } else {
                statusCell.innerHTML = '<span class="status bg-amber-50 text-amber-700 text-xs font-semibold">Evaluasi</span>';
            }
        }
    }

    function updateDirectStatus(input) {
        const row = input.closest('.student-grading-row');
        if (!row) return;

        const val = parseFloat(input.value);
        const statusCell = row.querySelector('.status-cell');
        if (!statusCell) return;

        if (isNaN(val) || input.value.trim() === '') {
            statusCell.innerHTML = '<span class="status bg-canvas text-muted text-xs">Belum Dinilai</span>';
        } else if (val >= 65) {
            statusCell.innerHTML = '<span class="status bg-emerald-50 text-emerald-700 text-xs font-semibold">Memenuhi</span>';
        } else {
            statusCell.innerHTML = '<span class="status bg-amber-50 text-amber-700 text-xs font-semibold">Evaluasi</span>';
        }
    }
</script>
@endsection
