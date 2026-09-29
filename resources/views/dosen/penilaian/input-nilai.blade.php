@extends('layouts.mahasiswa')

@section('title', 'Input Nilai | ' . $assessment->name . ' | SALE')
@section('header', 'Input Nilai')

@section('content')
<div class="space-y-6 w-full">
    @php $obeService = $obe ?? app(\App\Services\ObeCalculationService::class); @endphp

    {{-- Header Asesmen Kompak --}}
    <div>
        <nav class="flex items-center gap-1.5 text-xs text-muted mb-1.5">
            <a href="{{ route('dosen.penilaian.index') }}" class="hover:text-brand transition-colors">Penilaian</a>
            <span>/</span>
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand transition-colors">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</a>
            <span>/</span>
            <span class="text-ink font-semibold truncate">{{ $assessment->name }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="page-heading">{{ $assessment->name }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-xs text-muted mt-1 font-medium">
                    <span class="font-semibold text-brand tracking-wide uppercase">{{ $assessment->code }}</span>
                    <span class="h-3 w-px bg-line"></span>
                    <span class="capitalize">{{ $assessment->type }}</span>
                    <span class="h-3 w-px bg-line"></span>
                    <span>{{ $section->mataKuliah->name }}</span>
                    @if($cpmks->count())
                        <span class="h-3 w-px bg-line"></span>
                        <span class="text-muted">CPMK: {{ $cpmks->pluck('code')->join(', ') }}</span>
                    @endif
                </div>
            </div>
            <div class="text-xs text-muted self-start sm:self-auto shrink-0 bg-white border border-line/60 rounded-lg px-3 py-1.5 shadow-2xs">
                <span>Progress: <strong class="text-ink">{{ $gradedCount }}</strong> dari {{ $students->count() }} dinilai</span>
            </div>
        </div>
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

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 w-full">
                @if($isTipeSoal && ! ($hasEssayQuestions ?? false))
                    <p class="text-xs text-muted min-w-0 flex-1">Seluruh butir soal dinilai secara otomatis oleh sistem. Nilai tampil langsung pada tabel.</p>
                @else
                    <p class="text-xs text-muted min-w-0 flex-1">Nilai dinilai melalui tombol <strong>Lihat Jawaban</strong> pada masing-masing mahasiswa.</p>
                @endif
                <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto sm:ml-auto">
                    <button type="submit" name="intent" value="save" id="btn-simpan-nilai" class="button-secondary text-xs py-2 px-4 font-semibold flex-1 sm:flex-initial justify-center">Simpan Draft Nilai</button>
                    <button type="submit" name="intent" value="publish" class="button-primary text-xs py-2 px-4 font-semibold shadow-2xs flex-1 sm:flex-initial justify-center">Simpan &amp; Terbitkan</button>
                </div>
            </div>

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
                                        $effWeight = $obeService->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                        $maxScore = $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                    @endphp
                                    <th class="py-2.5 px-3 text-center min-w-[150px] border-l border-line/50" title="{{ $meta['description'] }}">
                                        <div class="font-bold text-ink text-xs">{{ $cpmk->code }}</div>
                                        {{-- Deskripsi CPMK singkat --}}
                                        @if($meta['description'])
                                            <div class="text-[10px] text-muted font-normal mt-0.5 leading-tight max-w-[140px] mx-auto truncate" title="{{ $meta['description'] }}">
                                                {{ $meta['description'] }}
                                            </div>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="py-2.5 px-3 text-center min-w-[110px] border-l border-line/50 bg-canvas/70">
                                    <div class="text-xs font-semibold text-ink">Total Asesmen</div>
                                </th>
                            @else
                                <th class="py-3 px-3 text-center min-w-[130px] border-l border-line/50">
                                    <div class="text-xs font-semibold text-ink">Nilai Asesmen</div>
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
                                $existing = $existingScores->get($student->id);
                                $gradeStatus = $existing?->status ?? \App\Models\StudentAssessmentScore::STATUS_PENDING;
                            @endphp
                            <tr class="student-row hover:bg-canvas/30 transition-colors" data-student-id="{{ $student->id }}">
                                <td class="py-3 px-3 text-center text-xs text-muted/70 font-mono">{{ $i + 1 }}</td>
                                <td class="py-3 px-3 text-xs font-mono text-muted">{{ $student->nim_nidn ?? '' }}</td>
                                <td class="py-3 px-3 font-semibold text-ink text-xs sm:text-sm">{{ $student->name }}</td>

                                @if($cpmks->isNotEmpty())
                                    @foreach($cpmks as $cpmk)
                                        @php
                                            $cpmkScoreObj = $existingCpmkScores->get($cpmk->id . ':' . $student->id);
                                            $val = old("cpmk_scores.{$student->id}.{$cpmk->id}", $cpmkScoreObj?->score);
                                            $maxScore = (int) $obeService->assessmentCpmkMaxScore($assessment, $cpmk);
                                        @endphp
                                        <td class="py-3 px-3 text-center border-l border-line/40">
                                            <span class="font-mono text-xs sm:text-sm {{ $val !== null ? 'font-bold text-ink' : 'text-muted' }}">
                                                {{ $val !== null ? rtrim(rtrim(number_format((float)$val, 2), '0'), '.') : '' }}
                                            </span>
                                            <input type="hidden" name="cpmk_scores[{{ $student->id }}][{{ $cpmk->id }}]" value="{{ $val !== null ? (float)$val : '' }}" data-max="{{ $maxScore }}" max="{{ $maxScore }}">
                                        </td>
                                    @endforeach

                                    <td class="py-3 px-3 text-center border-l border-line/40 bg-canvas/40 font-bold text-xs sm:text-sm text-ink">
                                        <span class="total-display-{{ $student->id }} font-mono">
                                            {{ $existing?->score !== null ? rtrim(rtrim(number_format((float)$existing->score, 2), '0'), '.') : '' }}
                                        </span>
                                    </td>
                                @else
                                    @php $singleScore = old("scores.{$student->id}", $existing?->score); @endphp
                                    <td class="py-3 px-3 text-center border-l border-line/40">
                                        <span class="font-mono text-xs sm:text-sm {{ $singleScore !== null ? 'font-bold text-ink' : 'text-muted' }}">
                                            {{ $singleScore !== null ? rtrim(rtrim(number_format((float)$singleScore, 2), '0'), '.') : '' }}
                                        </span>
                                        <input type="hidden" name="scores[{{ $student->id }}]" value="{{ $singleScore !== null ? (float)$singleScore : '' }}">
                                    </td>
                                @endif

                                {{-- Kolom Jawaban Esai --}}
                                <td class="py-3 px-3 text-center border-l border-line/40">
                                    @php
                                        $essayInfo = $studentEssayData[$student->id] ?? null;
                                        $hasSub = ! empty($essayInfo['has_submission']);
                                    @endphp
                                    @if($hasSub)
                                        <div class="flex flex-col items-center gap-1">
                                            <button type="button"
                                                    onclick="openAnswerModal({{ $student->id }}, '{{ addslashes($student->name) }}')"
                                                    class="button-secondary min-h-0 text-xs py-1 px-2.5 cursor-pointer shadow-2xs"
                                                    title="Lihat jawaban {{ $student->name }}">
                                                Lihat Jawaban
                                            </button>
                                            @if(!empty($essayInfo['is_late']))
                                                <span class="text-[11px] font-semibold text-amber-800">
                                                    Terlambat
                                                </span>
                                            @else
                                                <span class="text-[11px] font-semibold text-emerald-800">
                                                    Tepat Waktu
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <button type="button"
                                                onclick="openAnswerModal({{ $student->id }}, '{{ addslashes($student->name) }}')"
                                                class="button-secondary min-h-0 text-xs py-1 px-2.5 cursor-pointer shadow-2xs text-muted/70 opacity-75"
                                                title="Belum mengumpulkan jawaban">
                                            Belum Ada Jawaban
                                        </button>
                                    @endif
                                </td>

                                {{-- Kolom Status --}}
                                <td class="py-3 px-3 text-center border-l border-line/40 status-col-{{ $student->id }}">
                                    <span class="text-xs font-semibold text-ink">
                                        {{ match($gradeStatus) {
                                            'partial' => 'Sebagian',
                                            'final' => 'Final',
                                            'published' => 'Diterbitkan',
                                            default => 'Menunggu',
                                        } }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    @endif
</div>

{{-- MODAL: Tinjau Jawaban & Input Nilai --}}
<div id="answer-modal-overlay"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4"
     onclick="if(event.target===this) closeAnswerModal()">
    <div class="bg-white rounded-2xl border border-line shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-ink" id="modal-heading">Jawaban &amp; Penilaian</h3>
                <p class="text-xs text-muted mt-0.5" id="modal-student-name">Mahasiswa</p>
            </div>
            <button type="button" onclick="closeAnswerModal()"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer"
                    aria-label="Tutup modal">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 space-y-5 text-sm" id="modal-answer-body">
            <p class="text-muted text-xs">Memuat...</p>
        </div>
    </div>
</div>

<script>
    // ─── Modal Tinjau Jawaban & Input Nilai ──────────────────────────────────
    const studentEssayMap = @json($studentEssayData ?? []);
    const csrfToken = @json(csrf_token());

    function openAnswerModal(studentId, studentName) {
        document.getElementById('modal-student-name').textContent = studentName;
        const body = document.getElementById('modal-answer-body');
        const heading = document.getElementById('modal-heading');
        const data = studentEssayMap[studentId];

        if (!data) {
            body.innerHTML = `
                <div class="rounded-xl border border-line/60 bg-canvas/40 p-6 space-y-2 text-center">
                    <p class="text-sm font-semibold text-ink">Data Tidak Ditemukan</p>
                </div>
            `;
            document.getElementById('answer-modal-overlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            return;
        }

        let html = '';

        if (data.is_tipe_soal) {
            if (heading) heading.textContent = 'Jawaban & Penilaian Soal Kuis';

            if (!data.has_submission) {
                html += `
                    <div class="rounded-xl border border-line/60 bg-canvas/40 p-6 space-y-2 text-center">
                        <svg class="h-9 w-9 mx-auto text-muted/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm font-semibold text-ink">Belum Ada Lembar Jawaban</p>
                        <p class="text-xs text-muted max-w-sm mx-auto">
                            Mahasiswa belum mengumpulkan jawaban untuk kuis ini.
                        </p>
                    </div>
                `;
            } else {
                const manualQuestions = (data.questions || []).filter(q => q.is_essay);

                if (data.submitted_at) {
                    html += `
                        <div class="flex items-center justify-between text-xs text-muted pb-3 border-b border-line/50">
                            <span>Waktu Pengumpulan: <strong class="text-ink">${escapeHtml(data.submitted_at)}</strong></span>
                            <span>${manualQuestions.length > 0 ? `<strong class="text-ink">${manualQuestions.length}</strong> butir soal esai perlu dinilai` : 'Dinilai otomatis oleh sistem'}</span>
                        </div>
                    `;
                }

                if (manualQuestions.length > 0) {
                    const essayAction = data.essay_score_url || '';
                    html += `
                        <form method="post" action="${escapeHtml(essayAction)}" class="space-y-5">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <div class="space-y-5">
                    `;
                    manualQuestions.forEach((q) => {
                        const answer = (q.answer_text && q.answer_text.trim()) ? escapeHtml(q.answer_text.trim()) : '';
                        const inputId = q.answer_id ? q.answer_id : (q.question_id || q.number);
                        html += `
                            <div class="space-y-2.5 pb-4 border-b border-line/40 last:border-b-0 last:pb-0">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-ink">Soal ${q.number} &bull; ${escapeHtml(q.type_label)}</span>
                                        ${q.cpmk ? `<span class="text-[11px] font-semibold text-slate-700">${escapeHtml(q.cpmk)}</span>` : ''}
                                    </div>
                                    ${q.max_points > 0 ? `<span class="text-xs text-muted font-mono">Maks. ${q.max_points} poin</span>` : ''}
                                </div>

                                <div class="text-xs sm:text-sm font-medium text-ink bg-canvas/40 p-3 rounded-lg border border-line/60">
                                    ${escapeHtml(q.prompt)}
                                </div>

                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Jawaban Mahasiswa:</p>
                                    ${answer ? `
                                        <div class="p-3.5 rounded-xl border border-line/80 bg-white text-xs sm:text-sm text-ink leading-relaxed whitespace-pre-wrap font-sans selection:bg-brand/15">
                                            ${answer}
                                        </div>
                                    ` : `
                                        <div class="p-3 rounded-xl border border-line/50 bg-canvas/30 text-xs text-muted italic">
                                            Mahasiswa tidak mengisi teks jawaban untuk soal ini.
                                        </div>
                                    `}
                                </div>

                                <div class="flex flex-wrap items-end gap-3 pt-1">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold text-ink">Skor esai</span>
                                        <input type="number" name="scores[${escapeHtml(String(inputId))}]" min="0" max="${q.max_points}" step="any"
                                            value="${q.current_score !== null && q.current_score !== undefined ? q.current_score : ''}"
                                            class="field h-9 w-28 text-center font-semibold"
                                            placeholder="0"
                                            aria-label="Skor esai soal ${q.number}">
                                    </label>
                                    <span class="pb-2 text-xs font-semibold text-muted">/ ${q.max_points} poin</span>
                                </div>
                            </div>
                        `;
                    });
                    html += `
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-4 border-t border-line/60">
                                <button type="button" onclick="closeAnswerModal()" class="button-secondary text-xs py-2 px-3">Batal</button>
                                <button type="submit" class="button-primary text-xs py-2 px-5 font-semibold shadow-2xs">Simpan Nilai Esai</button>
                            </div>
                        </form>
                    `;
                } else {
                    html += `
                        <div class="rounded-xl border border-line/60 bg-canvas/40 p-6 space-y-2 text-center">
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-ink border border-line">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-ink">Semua Soal Telah Dinilai Otomatis</p>
                            <p class="text-xs text-muted max-w-sm mx-auto">
                                Seluruh butir soal pada kuis ini dinilai secara otomatis oleh sistem. Tidak ada butir soal esai yang memerlukan penilaian manual.
                            </p>
                        </div>
                    `;
                }
            }
        } else {
            // Kasus Tugas
            if (heading) heading.textContent = 'Jawaban & Penilaian Tugas';

            if (data.has_submission) {
                if (data.submitted_at) {
                    html += `
                        <div class="flex items-center justify-between text-xs text-muted pb-3 border-b border-line/50">
                            <span>Waktu Pengumpulan: <strong class="text-ink">${escapeHtml(data.submitted_at)}</strong></span>
                            ${data.is_late 
                                ? `<span class="text-xs font-semibold text-amber-800">Diserahkan (Terlambat)</span>` 
                                : `<span class="text-xs font-semibold text-emerald-800">Diserahkan (Tepat Waktu)</span>`
                            }
                        </div>
                    `;
                }

                if (data.is_late) {
                    html += `
                        <div class="p-3 rounded-xl border border-line bg-canvas/50 text-xs text-ink flex items-start gap-2">
                            <svg class="h-4 w-4 text-ink shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <span class="font-bold">Pengumpulan Terlambat</span>
                                <p class="text-muted text-[11px] mt-0.5">Tugas dikumpulkan melewati batas tenggat ${data.due_at ? `(${escapeHtml(data.due_at)})` : ''}. Seluruh berkas lampiran dan jawaban tetap dapat diperiksa dan dinilai di bawah.</p>
                            </div>
                        </div>
                    `;
                }

                if (data.answer_text && data.answer_text.trim()) {
                    html += `
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Jawaban Mahasiswa:</p>
                            <div class="p-3.5 rounded-xl border border-line/80 bg-white text-xs sm:text-sm text-ink leading-relaxed whitespace-pre-wrap font-sans selection:bg-brand/15">
                                ${escapeHtml(data.answer_text.trim())}
                            </div>
                        </div>
                    `;
                }

                if (data.files && data.files.length > 0) {
                    html += `
                        <div class="space-y-1.5 pt-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Berkas Lampiran (${data.files.length}):</p>
                            <div class="space-y-2">
                    `;
                    data.files.forEach((f) => {
                        const isImg = /\.(jpe?g|png|webp|gif)$/i.test(f.name);
                        html += `
                            <div class="space-y-2 p-2.5 rounded-lg border border-line/70 bg-canvas/30 text-xs">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 min-w-0 mr-2">
                                        <svg class="h-4 w-4 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="font-medium text-ink truncate">${escapeHtml(f.name)}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <a href="${escapeHtml(f.url)}" target="_blank" class="button-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1" title="Buka berkas di tab baru">
                                            <span>Buka Berkas</span>
                                            <svg class="h-3 w-3 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                        <a href="${escapeHtml(f.url)}?download=1" class="button-secondary text-xs py-1 px-2 text-muted hover:text-ink" title="Unduh Berkas">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    </div>
                                </div>
                                ${isImg ? `
                                    <div class="rounded-lg overflow-hidden border border-line bg-white p-2 text-center">
                                        <a href="${escapeHtml(f.url)}" target="_blank" title="Klik untuk melihat ukuran penuh">
                                            <img src="${escapeHtml(f.url)}" alt="${escapeHtml(f.name)}" class="max-h-60 mx-auto rounded object-contain hover:opacity-95 transition">
                                        </a>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });
                    html += '</div></div>';
                }

                if (data.link) {
                    html += `
                        <div class="pt-2 text-xs flex items-center gap-1.5">
                            <span class="text-muted font-medium">Tautan:</span>
                            <a href="${escapeHtml(data.link)}" target="_blank" rel="noopener noreferrer" class="text-brand hover:underline font-mono truncate max-w-md">
                                ${escapeHtml(data.link)}
                            </a>
                        </div>
                    `;
                }
            } else {
                html += `
                    <div class="p-3.5 rounded-xl border border-line/60 bg-canvas/40 text-xs text-muted">
                        Mahasiswa belum mengumpulkan jawaban secara daring untuk tugas ini. Anda dapat menginput nilai di bawah ini.
                    </div>
                `;
            }

            // Form Penilaian Tugas
            html += `
                <form method="post" action="${escapeHtml(data.score_url)}" class="space-y-3 pt-4 border-t border-line/60">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-muted">Form Penilaian Tugas</h4>
                    </div>
            `;

            if (data.has_cpmks && data.cpmk_list && data.cpmk_list.length > 0) {
                html += '<div class="space-y-2.5">';
                data.cpmk_list.forEach((cpmk) => {
                    html += `
                        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-line bg-canvas/30">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-xs text-ink">${escapeHtml(cpmk.code)}</div>
                                ${cpmk.description ? `<div class="text-[11px] text-muted truncate">${escapeHtml(cpmk.description)}</div>` : ''}
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <input type="number"
                                       name="cpmk_scores[${cpmk.id}]"
                                       value="${cpmk.current_score !== '' ? cpmk.current_score : ''}"
                                       min="0"
                                       max="${cpmk.max_score}"
                                       step="any"
                                       placeholder="0"
                                       class="field h-9 w-20 text-center font-bold text-sm"
                                       aria-label="Nilai ${escapeHtml(cpmk.code)}">
                                <span class="text-xs text-muted font-semibold">/ ${cpmk.max_score}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += `
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-line bg-canvas/30">
                        <div>
                            <div class="font-bold text-xs text-ink">Nilai Asesmen</div>
                            <div class="text-[11px] text-muted">Maksimal 100 poin</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <input type="number"
                                   name="score"
                                   value="${data.single_score !== '' ? data.single_score : ''}"
                                   min="0"
                                   max="100"
                                   step="any"
                                   placeholder="0"
                                   class="field h-9 w-24 text-center font-bold text-sm"
                                   aria-label="Nilai Asesmen">
                            <span class="text-xs text-muted font-semibold">/ 100</span>
                        </div>
                    </div>
                `;
            }

            html += `
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="closeAnswerModal()" class="button-secondary text-xs py-2 px-3">Batal</button>
                        <button type="submit" class="button-primary text-xs py-2 px-5 font-semibold shadow-2xs">Simpan Nilai Tugas</button>
                    </div>
                </form>
            `;
        }

        body.innerHTML = html;
        document.getElementById('answer-modal-overlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
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
