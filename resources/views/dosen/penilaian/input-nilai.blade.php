@extends('layouts.mahasiswa')

@section('title', 'Input Nilai — ' . $assessment->name . ' | SALE')
@section('header', 'Input Nilai')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <span class="text-ink font-semibold">Input Nilai</span>
        </nav>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="section-heading">{{ $assessment->name }}</h2>
                <p class="mt-1 text-sm text-muted">
                    Kode: <span class="font-mono font-semibold text-ink">{{ $assessment->code }}</span> ·
                    Jenis: <span class="font-medium text-ink">{{ ucfirst($assessment->type) }}</span> ·
                    Bobot Nilai Akhir: <span class="font-semibold text-brand">{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}%</span>
                    @php $obeService = $obe ?? app(\App\Services\ObeCalculationService::class); @endphp
                    @if($cpmks->count())
                        · Mengukur {{ $cpmks->count() }} CPMK: 
                        <span class="font-semibold text-ink">
                            @foreach($cpmks as $idx => $cpmk)
                                @php
                                    $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                    $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                @endphp
                                {{ $cpmk->code }} (Maks: {{ rtrim(rtrim(number_format($maxScore, 1), '0'), '.') }} · Bobot: {{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}%){{ $idx < $cpmks->count() - 1 ? ', ' : '' }}
                            @endforeach
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dosen.penilaian.asesmen.nilai.template', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Template CSV</a>
                <a href="{{ route('dosen.penilaian.asesmen.nilai.import', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Import CSV</a>
            </div>
        </div>
    </header>

    {{-- Progress bar --}}
    @php $total = $students->count(); $pct = $total > 0 ? round($gradedCount / $total * 100) : 0; @endphp
    <div class="surface p-4">
        <div class="flex items-center justify-between text-sm mb-2">
            <span class="font-semibold text-ink">Progress Penilaian Kelas</span>
            <span class="text-muted">{{ $gradedCount }}/{{ $total }} mahasiswa dinilai ({{ $pct }}%)</span>
        </div>
        <div class="h-2 w-full rounded-full bg-canvas overflow-hidden">
            <div class="h-full bg-brand rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    {{-- Score input form --}}
    @if($students->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada mahasiswa terdaftar</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">Mahasiswa harus terdaftar di kelas ini sebelum dapat dinilai.</p>
        </div>
    @else
        @if($errors->any())
            <div class="rounded-lg border border-rose-500 p-4 text-xs text-rose-600 mb-4">
                <strong class="font-semibold block mb-1">Gagal menyimpan nilai — terdapat input melebihi batas:</strong>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="form-input-nilai" method="post" action="{{ route('dosen.penilaian.asesmen.nilai.store', [$section->id, $assessment->id]) }}">
            @csrf

            <div class="surface overflow-x-auto">
                <table class="admin-table" id="table-input-nilai">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            @if($cpmks->isNotEmpty())
                                @foreach($cpmks as $cpmk)
                                    @php
                                        $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                        $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                    @endphp
                                    <th class="text-center min-w-[110px]">
                                        <div class="font-mono font-bold">{{ $cpmk->code }}</div>
                                        <div class="text-[10px] font-normal text-muted">
                                            Maks: {{ rtrim(rtrim(number_format($maxScore, 1), '0'), '.') }}
                                            <span class="text-muted/60">(Bobot: {{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}%)</span>
                                        </div>
                                    </th>
                                @endforeach
                                <th class="text-center min-w-[110px] bg-canvas/60">
                                    <div class="font-bold text-ink">Total Asesmen</div>
                                    <div class="text-[10px] font-normal text-muted">Poin (0–100)</div>
                                </th>
                            @else
                                <th class="text-center min-w-[130px]">Nilai (0–100)</th>
                            @endif
                            <th class="text-center w-28">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $i => $student)
                            @php
                                $existing = $existingScores[$student->id] ?? null;
                                $isGraded = $existing && $existing->score !== null;
                            @endphp
                            <tr class="student-row" data-student-id="{{ $student->id }}">
                                <td class="text-muted font-mono text-xs text-center">{{ $i + 1 }}</td>
                                <td class="font-mono text-xs text-muted">{{ $student->nim_nidn ?? '—' }}</td>
                                <td class="font-medium text-ink">{{ $student->name }}</td>

                                @if($cpmks->isNotEmpty())
                                    {{-- Kolom Input per CPMK --}}
                                    @foreach($cpmks as $cpmk)
                                        @php
                                            $cpmkScoreObj = $existingCpmkScores[$cpmk->id . ':' . $student->id] ?? null;
                                            $val = old("cpmk_scores.{$student->id}.{$cpmk->id}", $cpmkScoreObj?->score);
                                            $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                            $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                            $isInvalid = ($val !== null && $val !== '' && (float)$val > $maxScore) || $errors->has("cpmk_scores.{$student->id}.{$cpmk->id}");
                                        @endphp
                                        <td class="text-center">
                                            <input type="number"
                                                   name="cpmk_scores[{{ $student->id }}][{{ $cpmk->id }}]"
                                                   value="{{ $val !== null ? $val : '' }}"
                                                   min="0" max="{{ $maxScore }}" step="0.5"
                                                   class="field text-center font-mono text-sm w-20 mx-auto block cpmk-input {{ $isInvalid ? '!border-rose-500 !text-rose-600' : '' }}"
                                                   placeholder="0–{{ rtrim(rtrim(number_format($maxScore, 1), '0'), '.') }}"
                                                   data-max="{{ $maxScore }}"
                                                   data-student-id="{{ $student->id }}"
                                                   oninput="calcStudentTotal({{ $student->id }})">
                                        </td>
                                    @endforeach

                                    {{-- Nilai Total Asesmen (Auto calculated) --}}
                                    <td class="text-center bg-canvas/30 font-mono font-bold text-ink">
                                        <span class="total-display-{{ $student->id }}">
                                            {{ $existing?->score !== null ? number_format($existing->score, 1) : '—' }}
                                        </span>
                                    </td>
                                @else
                                    {{-- Kolom Nilai Asesmen Tunggal --}}
                                    @php
                                        $singleScore = old("scores.{$student->id}", $existing?->score);
                                    @endphp
                                    <td class="text-center">
                                        <input type="number"
                                               name="scores[{{ $student->id }}]"
                                               value="{{ $singleScore !== null ? $singleScore : '' }}"
                                               min="0" max="100" step="0.5"
                                               class="field text-center font-mono text-sm w-24 mx-auto block"
                                               placeholder="—">
                                    </td>
                                @endif

                                <td class="text-center">
                                    @if($isGraded)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                            Dinilai
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-canvas px-2 py-0.5 text-xs text-muted border border-line">
                                            Belum
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
                <p class="text-xs text-muted">
                    * Nilai total asesmen dihitung otomatis dari penjumlahan poin tiap CPMK (maksimal 100 poin). Kosongkan field jika mahasiswa belum dinilai.
                </p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-sm">Kembali</a>
                    <button type="submit" id="btn-simpan-nilai" class="button-primary text-sm">Simpan Nilai</button>
                </div>
            </div>
        </form>
    @endif
</div>

<script>
    function calcStudentTotal(studentId) {
        const row = document.querySelector(`.student-row[data-student-id="${studentId}"]`);
        if (!row) return;

        const inputs = row.querySelectorAll('.cpmk-input');
        let totalSum = 0;
        let hasAny = false;
        let hasExceeded = false;

        inputs.forEach(inp => {
            const val = inp.value.trim();
            const max = parseFloat(inp.dataset.max) || 100;
            
            inp.classList.remove('!border-rose-500', '!text-rose-600');

            if (val !== '' && !isNaN(val)) {
                hasAny = true;
                const score = parseFloat(val);
                if (score > max || score < 0) {
                    hasExceeded = true;
                    inp.classList.add('!border-rose-500', '!text-rose-600');
                } else {
                    totalSum += score;
                }
            }
        });

        const display = document.querySelector(`.total-display-${studentId}`);
        if (display) {
            if (hasExceeded) {
                // Tanpa background dan tanpa emoji, hanya teks berwarna merah
                display.innerHTML = '<span class="font-mono text-xs font-semibold text-rose-600">Lewat Batas</span>';
            } else if (hasAny) {
                display.textContent = Math.min(100, totalSum).toFixed(1);
            } else {
                display.textContent = '—';
            }
        }

        checkFormValidity();
    }

    function checkFormValidity() {
        const invalidInputs = document.querySelectorAll('.cpmk-input.\\!border-rose-500');
        const submitBtn = document.getElementById('btn-simpan-nilai');
        if (submitBtn) {
            if (invalidInputs.length > 0) {
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                submitBtn.title = 'Perbaiki nilai yang melebihi batas terlebih dahulu';
            } else {
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                submitBtn.removeAttribute('title');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.student-row').forEach(row => {
            const sid = row.dataset.studentId;
            if (sid) calcStudentTotal(sid);
        });

        const form = document.getElementById('form-input-nilai');
        if (form) {
            form.addEventListener('submit', function (e) {
                const invalidInputs = document.querySelectorAll('.cpmk-input.\\!border-rose-500');
                if (invalidInputs.length > 0) {
                    e.preventDefault();
                    alert('Terdapat nilai yang melebihi batas maksimal poin. Silakan periksa kotak yang bertanda merah sebelum menyimpan.');
                    invalidInputs[0].focus();
                }
            });
        }
    });
</script>
@endsection
