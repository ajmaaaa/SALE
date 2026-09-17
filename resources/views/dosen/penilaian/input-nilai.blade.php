@extends('layouts.mahasiswa')

@section('title', 'Input Nilai | ' . $assessment->name . ' | SALE')
@section('header', 'Input Nilai')

@section('content')
<div class="space-y-5">
    @include('dosen.partials.header')

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-muted mb-2">
                <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-ink transition-colors">Asesmen</a>
                <span>/</span>
                <span class="text-ink">{{ $assessment->name }}</span>
            </nav>
            <h2 class="section-heading">{{ $assessment->name }}</h2>
            @php $obeService = $obe ?? app(\App\Services\ObeCalculationService::class); @endphp
            <div class="flex flex-wrap items-center gap-2 text-xs text-muted mt-1.5">
                <span class="font-semibold text-brand tracking-wide uppercase">{{ $assessment->code }}</span>
                <span class="h-3 w-px bg-line"></span>
                <span class="capitalize">{{ $assessment->type }}</span>
                <span class="h-3 w-px bg-line"></span>
                <span>Bobot: <strong class="font-semibold text-ink">{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}%</strong></span>
                @if($cpmks->count())
                    <span class="h-3 w-px bg-line"></span>
                    <span class="flex flex-wrap items-center gap-1.5">
                        <span>Mengukur:</span>
                        @foreach($cpmks as $idx => $cpmk)
                            @php
                                $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                            @endphp
                            <span class="inline-flex items-center gap-1 rounded bg-canvas px-2 py-0.5 text-xs font-medium text-ink border border-line/60">
                                <span>{{ $cpmk->code }}</span>
                                <span class="text-muted font-normal">(maks {{ (int)$maxScore }}, {{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}%)</span>
                            </span>
                        @endforeach
                    </span>
                @endif
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('dosen.penilaian.asesmen.nilai.template', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Template CSV</a>
            <a href="{{ route('dosen.penilaian.asesmen.nilai.import', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Import CSV</a>
        </div>
    </div>

    {{-- Progress line --}}
    @php $total = $students->count(); $pct = $total > 0 ? round($gradedCount / $total * 100) : 0; @endphp
    <div class="text-xs text-muted">
        <span class="font-semibold text-ink">{{ $gradedCount }}</span> dari <span class="text-ink">{{ $total }}</span> mahasiswa telah dinilai
        <span class="text-muted/70 ml-1">({{ $pct }}%)</span>
    </div>

    {{-- Error --}}
    @if($errors->any())
        <div class="rounded-xl border border-line px-4 py-3 text-xs text-ink">
            <div class="font-semibold mb-1">Nilai melebihi batas, harap periksa kembali:</div>
            <ul class="space-y-0.5 list-disc list-inside text-muted">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Table --}}
    @if($students->isEmpty())
        <div class="surface p-12 text-center">
            <div class="text-sm text-muted">Belum ada mahasiswa terdaftar di kelas ini.</div>
        </div>
    @else
        <form id="form-input-nilai" method="post" action="{{ route('dosen.penilaian.asesmen.nilai.store', [$section->id, $assessment->id]) }}">
            @csrf
            <div class="surface overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-left border-collapse text-sm" id="table-input-nilai">
                    <thead>
                        <tr class="border-b border-line bg-canvas/40">
                            <th class="w-10 py-3.5 px-3 text-center text-xs font-medium text-muted">#</th>
                            <th class="py-3.5 px-3 text-xs font-medium text-muted min-w-[110px]">NIM</th>
                            <th class="py-3.5 px-3 text-xs font-medium text-muted min-w-[180px]">Nama</th>
                            @if($cpmks->isNotEmpty())
                                @foreach($cpmks as $cpmk)
                                    @php
                                        $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                        $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                    @endphp
                                    <th class="py-3.5 px-3 text-center min-w-[120px] border-l border-line/50">
                                        <div class="font-semibold text-ink text-xs">{{ $cpmk->code }}</div>
                                        <div class="text-xs text-muted font-normal mt-0.5">Bobot: {{ rtrim(rtrim(number_format($effWeight, 1), '0'), '.') }}% &bull; Maks: {{ (int)$maxScore }}</div>
                                    </th>
                                @endforeach
                                <th class="py-3.5 px-3 text-center min-w-[90px] border-l border-line/50 bg-canvas/60">
                                    <div class="text-xs font-semibold text-ink">Total Asesmen</div>
                                </th>
                            @else
                                <th class="py-3.5 px-3 text-center min-w-[120px] border-l border-line/50">
                                    <div class="text-xs font-medium text-muted">Nilai (Maks 100)</div>
                                </th>
                            @endif
                            <th class="py-3.5 px-3 text-center w-16 text-xs font-medium text-muted border-l border-line/50"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        @foreach($students as $i => $student)
                            @php
                                $existing = $existingScores[$student->id] ?? null;
                                $isGraded = $existing && $existing->score !== null;
                            @endphp
                            <tr class="student-row hover:bg-canvas/30 transition-colors" data-student-id="{{ $student->id }}">
                                <td class="py-2.5 px-3 text-center text-xs text-muted/60">{{ $i + 1 }}</td>
                                <td class="py-2.5 px-3 text-xs text-muted">{{ $student->nim_nidn ?? '' }}</td>
                                <td class="py-2.5 px-3 font-medium text-ink text-xs sm:text-sm">{{ $student->name }}</td>

                                @if($cpmks->isNotEmpty())
                                    @foreach($cpmks as $cpmk)
                                        @php
                                            $cpmkScoreObj = $existingCpmkScores[$cpmk->id . ':' . $student->id] ?? null;
                                            $val = old("cpmk_scores.{$student->id}.{$cpmk->id}", $cpmkScoreObj?->score);
                                            $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                            $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                            $isInvalid = ($val !== null && $val !== '' && (float)$val > $maxScore) || $errors->has("cpmk_scores.{$student->id}.{$cpmk->id}");
                                        @endphp
                                        <td class="py-2 px-3 text-center border-l border-line/40">
                                            <input type="number"
                                                   name="cpmk_scores[{{ $student->id }}][{{ $cpmk->id }}]"
                                                   value="{{ $val !== null ? $val : '' }}"
                                                   min="0" max="{{ $maxScore }}" step="0.5"
                                                   class="h-8 w-20 mx-auto text-center text-xs font-semibold rounded-md border border-line bg-white text-ink focus:border-brand focus:ring-1 focus:ring-brand focus:outline-none transition-all cpmk-input {{ $isInvalid ? '!border-rose-400 !bg-rose-50 !text-rose-700' : '' }}"
                                                   placeholder=""
                                                   data-max="{{ $maxScore }}"
                                                   data-student-id="{{ $student->id }}"
                                                   oninput="calcStudentTotal({{ $student->id }})">
                                        </td>
                                    @endforeach

                                    <td class="py-2.5 px-3 text-center border-l border-line/40 bg-canvas/40 font-semibold text-xs text-ink">
                                        <span class="total-display-{{ $student->id }}">
                                            {{ $existing?->score !== null ? number_format($existing->score, 1) : '' }}
                                        </span>
                                    </td>
                                @else
                                    @php $singleScore = old("scores.{$student->id}", $existing?->score); @endphp
                                    <td class="py-2 px-3 text-center border-l border-line/40">
                                        <input type="number"
                                               name="scores[{{ $student->id }}]"
                                               value="{{ $singleScore !== null ? $singleScore : '' }}"
                                               min="0" max="100" step="0.5"
                                               class="h-8 w-24 mx-auto text-center text-xs font-semibold rounded-md border border-line bg-white text-ink focus:border-brand focus:ring-1 focus:ring-brand focus:outline-none transition-all"
                                               placeholder="">
                                    </td>
                                @endif

                                <td class="py-2.5 px-3 text-center border-l border-line/40">
                                    @if($isGraded)
                                        <svg class="w-4 h-4 mx-auto text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
                <p class="text-xs text-muted">Total dihitung otomatis. Kosongkan jika mahasiswa belum dinilai.</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-xs">Kembali</a>
                    <button type="submit" id="btn-simpan-nilai" class="button-primary text-xs">Simpan Nilai</button>
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
        let totalSum = 0, hasAny = false, hasExceeded = false;
        inputs.forEach(inp => {
            const val = inp.value.trim();
            const max = parseFloat(inp.dataset.max) || 100;
            inp.classList.remove('!border-rose-400', '!text-rose-700', '!bg-rose-50');
            if (val !== '' && !isNaN(val)) {
                hasAny = true;
                const score = parseFloat(val);
                if (score > max || score < 0) {
                    hasExceeded = true;
                    inp.classList.add('!border-rose-400', '!text-rose-700', '!bg-rose-50');
                } else {
                    totalSum += score;
                }
            }
        });
        const display = document.querySelector(`.total-display-${studentId}`);
        if (display) {
            display.textContent = hasExceeded ? '!' : (hasAny ? Math.min(100, totalSum).toFixed(1) : '');
        }
        const btn = document.getElementById('btn-simpan-nilai');
        const invalid = document.querySelectorAll('.cpmk-input.\\!border-rose-400');
        if (btn) {
            btn.disabled = invalid.length > 0;
            btn.classList.toggle('opacity-50', invalid.length > 0);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.student-row').forEach(row => {
            const sid = row.dataset.studentId;
            if (sid) calcStudentTotal(sid);
        });
        const form = document.getElementById('form-input-nilai');
        if (form) {
            form.addEventListener('submit', e => {
                const invalid = document.querySelectorAll('.cpmk-input.\\!border-rose-400');
                if (invalid.length > 0) { e.preventDefault(); invalid[0].focus(); }
            });
        }
    });
</script>
@endsection
