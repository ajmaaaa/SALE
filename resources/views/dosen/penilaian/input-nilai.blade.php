@extends('layouts.mahasiswa')

@section('title', 'Input Nilai | ' . $assessment->name . ' | SALE')
@section('header', 'Input Nilai')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    {{-- Header Asesmen --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-muted mb-2">
                <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-ink transition-colors">Asesmen</a>
                <span>/</span>
                <span class="text-ink font-semibold">{{ $assessment->name }}</span>
            </nav>
            <h1 class="page-heading">{{ $assessment->name }}</h1>
            <div class="flex flex-wrap items-center gap-2 text-xs text-muted mt-1.5 font-medium">
                <span class="font-semibold text-brand tracking-wide uppercase">{{ $assessment->code }}</span>
                <span class="h-3 w-px bg-line"></span>
                <span class="capitalize">{{ $assessment->type }}</span>
                @if($cpmks->count())
                    <span class="h-3 w-px bg-line"></span>
                    <span class="flex flex-wrap items-center gap-1.5">
                        <span class="text-muted">Mengukur:</span>
                        @foreach($cpmks as $cpmk)
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold bg-canvas text-ink border border-line/60">
                                {{ $cpmk->code }}
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

    {{-- Progress --}}
    @php $total = $students->count(); @endphp
    <div>
        @if($total > 0 && $gradedCount >= $total)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                {{ $gradedCount }} dari {{ $total }} mahasiswa &bull; Sudah dinilai lengkap
            </span>
        @elseif($gradedCount > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                {{ $gradedCount }} dari {{ $total }} mahasiswa &bull; Belum selesai
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-canvas text-muted border border-line">
                0 dari {{ $total }} mahasiswa &bull; Belum dinilai
            </span>
        @endif
    </div>

    {{-- Error --}}
    @if($errors->any())
        <div class="rounded-xl border border-line bg-canvas/80 px-4 py-3 text-xs text-ink">
            <div class="font-semibold mb-1 text-rose-700">Harap periksa kembali input nilai:</div>
            <ul class="space-y-0.5 list-disc list-inside text-muted">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Table --}}
    @if($students->isEmpty())
        <div class="surface p-12 text-center rounded-xl border border-line">
            <div class="text-sm text-muted">Belum ada mahasiswa terdaftar di kelas ini.</div>
        </div>
    @else
        <form id="form-input-nilai" method="post" action="{{ route('dosen.penilaian.asesmen.nilai.store', [$section->id, $assessment->id]) }}">
            @csrf
            <div class="surface overflow-x-auto rounded-xl border border-line shadow-2xs">
                <table class="w-full text-left border-collapse text-sm" id="table-input-nilai">
                    <thead>
                        <tr class="border-b border-line bg-canvas/50">
                            <th class="w-10 py-3 px-3 text-center text-xs font-medium text-muted">#</th>
                            <th class="py-3 px-3 text-xs font-medium text-muted min-w-[110px]">NIM</th>
                            <th class="py-3 px-3 text-xs font-medium text-muted min-w-[180px]">Nama Mahasiswa</th>

                            @if($cpmks->isNotEmpty())
                                @foreach($cpmks as $cpmk)
                                    @php
                                        $meta = $cpmkMeta[$cpmk->id] ?? ['max_display' => '100', 'max_score' => 100, 'description' => ''];
                                    @endphp
                                    <th class="py-2 px-3 text-center min-w-[150px] border-l border-line/50" title="{{ $meta['description'] }}">
                                        <div class="font-bold text-ink text-xs">{{ $cpmk->code }}</div>
                                        {{-- Deskripsi CPMK singkat --}}
                                        @if($meta['description'])
                                            <div class="text-[10px] text-muted font-normal mt-0.5 leading-tight max-w-[140px] mx-auto truncate" title="{{ $meta['description'] }}">
                                                {{ $meta['description'] }}
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-brand font-semibold mt-1">
                                            Skor 0 &ndash; {{ $meta['max_display'] }}
                                        </div>
                                    </th>
                                @endforeach
                                <th class="py-2 px-3 text-center min-w-[110px] border-l border-line/50 bg-canvas/70">
                                    <div class="text-xs font-semibold text-ink">Total Asesmen</div>
                                    <div class="text-[10px] text-muted font-normal mt-0.5">Skala 0&ndash;100</div>
                                </th>
                            @else
                                <th class="py-3 px-3 text-center min-w-[130px] border-l border-line/50">
                                    <div class="text-xs font-semibold text-ink">Nilai Asesmen</div>
                                    <div class="text-[11px] text-muted font-normal mt-0.5">Skala 0&ndash;100</div>
                                </th>
                            @endif

                            {{-- Kolom Jawaban Esai --}}
                            <th class="py-3 px-3 text-center w-28 text-xs font-medium text-muted border-l border-line/50">Jawaban</th>
                            <th class="py-3 px-3 text-center w-28 text-xs font-medium text-muted border-l border-line/50">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        @foreach($students as $i => $student)
                            @php
                                $existing = $existingScores[$student->id] ?? null;
                                $isGraded = $existing && $existing->score !== null;
                            @endphp
                            <tr class="student-row hover:bg-canvas/30 transition-colors" data-student-id="{{ $student->id }}">
                                <td class="py-3 px-3 text-center text-xs text-muted/70 font-mono">{{ $i + 1 }}</td>
                                <td class="py-3 px-3 text-xs font-mono text-muted">{{ $student->nim_nidn ?? '' }}</td>
                                <td class="py-3 px-3 font-semibold text-ink text-xs sm:text-sm">{{ $student->name }}</td>

                                @if($cpmks->isNotEmpty())
                                    @foreach($cpmks as $cpmk)
                                        @php
                                            $meta = $cpmkMeta[$cpmk->id] ?? ['max_score' => 100, 'max_display' => '100'];
                                            $cpmkScoreObj = $existingCpmkScores[$cpmk->id . ':' . $student->id] ?? null;
                                            $val = old("cpmk_scores.{$student->id}.{$cpmk->id}", $cpmkScoreObj?->score);
                                            $isInvalid = ($val !== null && $val !== '' && ((float)$val > $meta['max_score'] || (float)$val < 0)) || $errors->has("cpmk_scores.{$student->id}.{$cpmk->id}");
                                        @endphp
                                        <td class="py-2.5 px-3 text-center border-l border-line/40">
                                            <input type="number"
                                                   name="cpmk_scores[{{ $student->id }}][{{ $cpmk->id }}]"
                                                   value="{{ $val !== null ? (float)$val : '' }}"
                                                   min="0"
                                                   max="{{ $meta['max_score'] }}"
                                                   step="any"
                                                   placeholder="0"
                                                   class="h-9 w-24 mx-auto text-center text-sm font-bold rounded-lg border border-line bg-canvas/60 text-ink focus:border-brand focus:ring-1 focus:ring-brand focus:bg-white outline-none transition cpmk-input {{ $isInvalid ? '!border-rose-400 !bg-rose-50 !text-rose-700' : '' }}"
                                                   data-max="{{ $meta['max_score'] }}"
                                                   data-student-id="{{ $student->id }}"
                                                   oninput="calcStudentTotal({{ $student->id }})">
                                        </td>
                                    @endforeach

                                    <td class="py-3 px-3 text-center border-l border-line/40 bg-canvas/40 font-bold text-xs sm:text-sm text-ink">
                                        <span class="total-display-{{ $student->id }}">
                                            {{ $existing?->score !== null ? number_format((float)$existing->score, 2, ',', '.') : '—' }}
                                        </span>
                                    </td>
                                @else
                                    @php $singleScore = old("scores.{$student->id}", $existing?->score); @endphp
                                    <td class="py-2.5 px-3 text-center border-l border-line/40">
                                        <input type="number"
                                               name="scores[{{ $student->id }}]"
                                               value="{{ $singleScore !== null ? (float)$singleScore : '' }}"
                                               min="0" max="100" step="any"
                                               placeholder="0"
                                               class="h-9 w-24 mx-auto text-center text-sm font-bold rounded-lg border border-line bg-canvas/60 text-ink focus:border-brand focus:ring-1 focus:ring-brand focus:bg-white outline-none transition"
                                               oninput="updateSingleStatus({{ $student->id }}, this)">
                                    </td>
                                @endif

                                {{-- Kolom Jawaban Esai --}}
                                <td class="py-3 px-3 text-center border-l border-line/40">
                                    <button type="button"
                                            onclick="openAnswerModal({{ $student->id }}, '{{ addslashes($student->name) }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-line/70 bg-canvas text-muted hover:border-brand hover:text-brand transition cursor-pointer"
                                            title="Lihat jawaban esai {{ $student->name }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Jawaban
                                    </button>
                                </td>

                                {{-- Kolom Status --}}
                                <td class="py-3 px-3 text-center border-l border-line/40 status-col-{{ $student->id }}">
                                    @if($isGraded)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Sudah Dinilai
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-canvas text-muted border border-line/60">
                                            Belum Dinilai
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
                <p class="text-xs text-muted">Total dihitung otomatis skala 0&ndash;100. Kosongkan jika mahasiswa belum dinilai.</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-xs">Kembali</a>
                    <button type="submit" id="btn-simpan-nilai" class="button-primary text-xs py-2 px-4 font-semibold shadow-2xs">Simpan Nilai</button>
                </div>
            </div>
        </form>
    @endif
</div>

{{-- MODAL: Lihat Jawaban Esai Mahasiswa --}}
<div id="answer-modal-overlay"
     class="hidden fixed inset-0 z-50 flex items-center justify-center"
     style="background:rgba(15,23,42,0.45);"
     onclick="if(event.target===this) closeAnswerModal()">
    <div class="bg-white rounded-2xl border border-line shadow-2xl max-w-2xl w-[calc(100%-2rem)] max-h-[85vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-ink">Jawaban Esai</h3>
                <p class="text-xs text-muted mt-0.5" id="modal-student-name">Mahasiswa</p>
            </div>
            <button type="button" onclick="closeAnswerModal()"
                    class="h-7 w-7 rounded-lg text-muted hover:text-ink hover:bg-canvas flex items-center justify-center font-bold text-lg leading-none cursor-pointer">
                &times;
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 space-y-5 text-sm" id="modal-answer-body">
            <p class="text-muted text-xs">Memuat...</p>
        </div>
        <div class="px-6 py-3 border-t border-line/60 bg-canvas/30 shrink-0 flex justify-end">
            <button type="button" onclick="closeAnswerModal()"
                    class="button-secondary text-xs py-1.5 px-4 font-semibold cursor-pointer">Tutup</button>
        </div>
    </div>
</div>

<script>
    // ─── Max scores per CPMK ─────────────────────────────────────────────────
    const cpmkMaxMap = @json($cpmkMeta ?? []);

    function calcStudentTotal(studentId) {
        const row = document.querySelector(`.student-row[data-student-id="${studentId}"]`);
        if (!row) return;
        const inputs = row.querySelectorAll('.cpmk-input');
        let totalSum = 0, count = 0, hasExceeded = false;

        inputs.forEach(inp => {
            const val = inp.value.trim();
            const maxVal = parseFloat(inp.getAttribute('data-max') || 100);
            inp.classList.remove('!border-rose-400', '!text-rose-700', '!bg-rose-50');
            if (val !== '' && !isNaN(val)) {
                const score = parseFloat(val);
                if (score > maxVal || score < 0) {
                    hasExceeded = true;
                    inp.classList.add('!border-rose-400', '!text-rose-700', '!bg-rose-50');
                } else {
                    totalSum += score;
                    count++;
                }
            }
        });

        const display = document.querySelector(`.total-display-${studentId}`);
        const statusCol = document.querySelector(`.status-col-${studentId}`);

        if (display) {
            if (count > 0 && !hasExceeded) {
                const avg = totalSum / count;
                display.textContent = avg.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else if (hasExceeded) {
                display.textContent = 'Melebihi batas';
            } else {
                display.textContent = '—';
            }
        }

        if (statusCol) {
            if (count > 0 && !hasExceeded) {
                statusCol.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">Sudah Dinilai</span>';
            } else {
                statusCol.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-canvas text-muted border border-line/60">Belum Dinilai</span>';
            }
        }
    }

    function updateSingleStatus(studentId, input) {
        const statusCol = document.querySelector(`.status-col-${studentId}`);
        if (!statusCol) return;
        const val = input.value.trim();
        if (val !== '' && !isNaN(val) && parseFloat(val) >= 0 && parseFloat(val) <= 100) {
            statusCol.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">Sudah Dinilai</span>';
        } else {
            statusCol.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-canvas text-muted border border-line/60">Belum Dinilai</span>';
        }
    }

    // ─── Modal Jawaban Esai ──────────────────────────────────────────────────
    function openAnswerModal(studentId, studentName) {
        document.getElementById('modal-student-name').textContent = studentName;
        const body = document.getElementById('modal-answer-body');

        body.innerHTML = `
            <div class="rounded-xl border border-line/60 bg-canvas/50 p-5 space-y-3 text-center">
                <svg class="h-10 w-10 mx-auto text-muted/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-semibold text-ink">Jawaban mahasiswa belum tersedia di sini.</p>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    Halaman ini digunakan untuk input skor per CPMK secara manual.
                    Untuk melihat dan menilai jawaban esai dari kuis/tugas, gunakan menu
                    <strong>Penilaian Asesmen</strong> di halaman course.
                </p>
            </div>
        `;

        document.getElementById('answer-modal-overlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeAnswerModal() {
        document.getElementById('answer-modal-overlay').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Tutup dengan Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAnswerModal();
    });
</script>
@endsection
