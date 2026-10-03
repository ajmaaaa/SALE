@extends('layouts.mahasiswa')

@section('title', $item['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $currentRole = request()->routeIs('dosen.*') ? 'dosen' : 'mahasiswa';
    $isLecturer = $currentRole === 'dosen';
    $isTask = in_array($item['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project', 'lainnya'], true);
    $hasMultiQuestions = !empty($item['questions']);
    $taskMode = $item['task_mode'] ?? null;
    $isDedicatedQuiz = ($taskMode === 'quiz')
        || ($item['type'] === 'kuis' && $taskMode !== 'coding')
        || (($item['component'] ?? '') === 'kuis' && $taskMode !== 'coding')
        || (in_array($item['type'], ['uts', 'uas'], true) && $taskMode !== 'coding' && !empty($item['questions']));
    $isCodingMaterial = $item['type'] === 'materi' && ($item['material_mode'] ?? null) === 'coding';
    $isCodingTask = in_array($item['type'], ['coding'], true)
        || ($item['task_mode'] ?? null) === 'coding'
        || ($item['question_type'] ?? null) === 'coding'
        || !empty($item['coding_steps'])
        || collect($item['questions'] ?? [])->contains(fn($q) => ($q['type'] ?? '') === 'coding');
    $submission = null;
    $studentId = auth()->id();
    if (empty($submission) && auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('submissions')) {
        $dbSub = \App\Models\Submission::where('assessment_id', $item['id'])
            ->where(fn ($q) => $q->where('user_id', $studentId)->orWhere('mahasiswa_id', $studentId))
            ->latest('id')
            ->first();
        if ($dbSub) {
            $submission = [
                'id' => $dbSub->id,
                'answer' => $dbSub->answer,
                'link' => $dbSub->link,
                'question_answers' => $dbSub->question_answers ?? [],
                'files' => $dbSub->file_ids ?? [],
                'student_number' => $dbSub->student_number,
                'time' => $dbSub->submitted_at?->format('d M Y, H:i') ?? '',
                'submitted_at' => $dbSub->submitted_at,
                'status' => $dbSub->status,
                'attempt' => $dbSub->attempt,
                'version' => $dbSub->version,
            ];
        }
    }
    $dbScore = null;
    if (\Illuminate\Support\Facades\Schema::hasTable('student_assessment_scores')) {
        $dbScore = \App\Models\StudentAssessmentScore::where('assessment_id', $item['id'])
            ->where('mahasiswa_id', $studentId)
            ->where(function ($q) {
                $q->whereNotNull('score')
                  ->orWhereIn('status', [
                      \App\Models\StudentAssessmentScore::STATUS_FINAL,
                      \App\Models\StudentAssessmentScore::STATUS_PUBLISHED,
                  ]);
            })
            ->first();
    }
    $hasDbGrade = $dbScore && $dbScore->score !== null;
    $isGraded = $hasDbGrade;
    $scoreValue = $hasDbGrade ? (float)$dbScore->score : null;

    $cpmkThreshold = 65.0;
    if (!empty($item['cpmk_threshold'])) {
        $cpmkThreshold = (float) $item['cpmk_threshold'];
    } elseif (!empty($item['id']) && \Illuminate\Support\Facades\Schema::hasTable('assessments')) {
        $assessmentModel = \App\Models\Assessment::with('cpmks')->find($item['id']);
        if ($assessmentModel && $assessmentModel->cpmks->isNotEmpty()) {
            $cpmkThreshold = (float) $assessmentModel->cpmks->avg('threshold');
        } elseif (!empty($item['cpmk'])) {
            $cpmkObj = \App\Models\Cpmk::where('code', $item['cpmk'])->first();
            if ($cpmkObj && $cpmkObj->threshold !== null) {
                $cpmkThreshold = (float) $cpmkObj->threshold;
            }
        }
    }
    $maxItemPoints = (float)($item['points'] ?? 100);
    $scorePct = ($scoreValue !== null && $maxItemPoints > 0) ? (($scoreValue / $maxItemPoints) * 100) : ($scoreValue ?? 0);
    $isScorePassed = $scoreValue !== null && ($scorePct >= $cpmkThreshold);
    $scoreColorClass = $isScorePassed ? 'text-emerald-600' : 'text-rose-600';
    $scoreBadgeClass = $isScorePassed ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200';

    $isSubmitted = !empty($submission) || $isGraded;
    $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
    $allowLate = (bool) ($item['allow_late'] ?? true);
    $studentAttempt = null;
    if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('assessment_attempts')) {
        $studentAttempt = \App\Models\AssessmentAttempt::where('assessment_id', $item['id'])
            ->where('mahasiswa_id', $studentId)
            ->latest('attempt')
            ->first();
    }
    $isAttemptRejected = $studentAttempt && ($studentAttempt->status === \App\Models\AssessmentAttempt::STATUS_REJECTED || ($studentAttempt->status === \App\Models\AssessmentAttempt::STATUS_IN_PROGRESS && $studentAttempt->deadline_at && now()->greaterThan($studentAttempt->deadline_at->copy()->addSeconds(30))));
    $isLocked = !$isSubmitted && $isPast && !$allowLate;
    $isInputsDisabled = !empty($submission) || $isLocked || $isGraded || $isAttemptRejected;
    $isTaskOrQuiz = in_array($item['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project', 'lainnya'], true);
    $targetTab = $isTaskOrQuiz ? 'tugas' : 'materi';
    $courseBaseUrl = $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.show', $course['id']);
    $courseBackUrl = $courseBaseUrl . '?tab=' . $targetTab;
    $isArchived = !empty($course['is_archived']) || (!empty($course['id']) && (\App\Models\ClassSection::find($course['id'])?->isArchived() ?? false));
@endphp

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.index') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>{{ $isLecturer ? 'Course Dosen' : 'Course' }}</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ $courseBackUrl }}">
            {{ $course['code'] }}
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800 truncate max-w-xs sm:max-w-md" aria-current="page">
            {{ $item['module'] }}
        </span>
    </nav>

    {{-- Title & Header --}}
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="page-heading">{{ $item['title'] }}</h1>
            <p class="page-description">
                {{ $course['lecturer'] }}, {{ $item['module'] }}@if(!empty($item['published_at_formatted'])) &bull; Diterbitkan {{ $item['published_at_formatted'] }}@endif
            </p>
        </div>

    </header>

    {{-- Submission form wraps main questions & side actions if student on regular tasks --}}
    @unless($isLecturer || $isDedicatedQuiz || $isCodingTask)
    <form data-submission-form method="post" enctype="multipart/form-data" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}">
        @csrf
    @endunless

        @if($isLecturer || ($isTask && !$isDedicatedQuiz && !$isCodingTask))
        <div class="grid items-stretch gap-7 xl:grid-cols-[minmax(0,1fr)_340px]">
        @else
        <div class="w-full space-y-6">
        @endif
            {{-- Main Column: Instructions, Multi-Question Cards, Stimulus, Attachments, Discussions --}}
            <div class="min-w-0 space-y-6 flex flex-col">

                {{-- Instructions Card --}}
                <section class="surface p-6 sm:p-7 flex flex-col flex-1">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="section-heading">{{ $item['type'] === 'materi' ? 'Materi Pembelajaran' : 'Petunjuk Pengerjaan' }}</h2>
                        @if($isLecturer && !$isArchived)
                            <div class="flex items-center gap-1.5 shrink-0" aria-label="Aksi konten">
                                <a href="{{ route('dosen.item.edit', [$course['id'], $item['id']]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-line bg-white text-muted hover:border-brand hover:text-brand transition shadow-2xs" title="Edit konten" aria-label="Edit konten">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="post" action="{{ route('dosen.item.destroy', [$course['id'], $item['id']]) }}" onsubmit="event.preventDefault(); window.saleConfirm({title: 'Hapus konten ini?', message: 'Konten dan seluruh data terkait akan dihapus secara permanen.', confirmLabel: 'Hapus', isDestructive: true}).then(ok => ok && this.submit())" class="inline-flex items-center m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-line bg-white text-muted hover:border-rose-300 hover:text-rose-600 transition shadow-2xs" title="Hapus konten" aria-label="Hapus konten">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <p class="prose-content mt-4 text-sm">{{ $item['body'] }}</p>

                    @if($isDedicatedQuiz)
                        <div class="mt-6 pt-5 border-t border-line/60">
                            <div class="rounded-xl border border-line bg-canvas/40 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-muted">Informasi Ujian &amp; Penilaian</p>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-ink">
                                        <span>Durasi: <strong>{{ !empty($item['duration_enabled']) ? ($item['duration_minutes'] ?? 60).' menit' : 'Tanpa batas waktu' }}</strong></span>
                                        <span><strong>{{ count($item['questions'] ?? []) ?: 1 }}</strong> butir soal</span>
                                        <span>Total <strong>{{ $item['points'] ?? 100 }} poin</strong></span>
                                        @if(!empty($item['due']))
                                            @php $isDuePast = \Carbon\Carbon::parse($item['due'])->isPast(); @endphp
                                            @if(!$isLecturer && $isDuePast && !$submission && $scoreValue === null)
                                                <span class="text-rose-600 font-semibold">Tenggat: <strong>{{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}</strong> (Terlambat)</span>
                                            @else
                                                <span>Tenggat: <strong>{{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}</strong></span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Nilai kuis di kiri bawah --}}
                                    @if(!$isLecturer && $scoreValue !== null)
                                        <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-2">
                                            <span class="text-xs text-muted font-medium">Nilai Kuis:</span>
                                            <span class="text-sm font-bold {{ $scoreColorClass }} font-mono">{{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }} Poin</span>
                                            @if($submission)
                                                <span class="text-xs text-slate-300">·</span>
                                                <span class="text-xs font-semibold text-emerald-600">Sudah diserahkan {{ !empty($submission['time']) ? '('.$submission['time'].')' : '' }}</span>
                                            @endif
                                        </div>
                                    @elseif(!$isLecturer && $submission)
                                        <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-1.5">
                                            <span class="text-xs font-semibold text-emerald-600">Sudah diserahkan {{ !empty($submission['time']) ? '· '.$submission['time'] : '' }}</span>
                                            <span class="text-xs text-slate-300">·</span>
                                            <span class="text-xs font-medium text-amber-600">Menunggu penilaian esai</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($isLecturer)
                                            {{-- Tombol Lihat Jawaban dihapus; gunakan "Lihat dan Nilai Mahasiswa" di halaman penilaian --}}
                                        @elseif($isGraded || $submission)
                                            <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-secondary bg-white text-xs py-2.5 px-5 font-semibold">Lihat Jawaban</a>
                                        @elseif(empty($item['questions']) || !empty($item['questions_empty']))
                                            <button type="button" disabled class="button-primary text-xs py-2.5 px-5 font-semibold opacity-50 cursor-not-allowed" title="Soal belum tersedia">Mulai Kerjakan Kuis</button>
                                        @elseif($isArchived)
                                            <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-secondary bg-white text-xs py-2.5 px-5 font-semibold">Buka Lembar Kuis (Read-Only)</a>
                                        @elseif($isLocked || $isAttemptRejected)
                                            <button type="button" disabled class="button-secondary text-xs py-2.5 px-5 font-semibold opacity-50 cursor-not-allowed">Kerjakan</button>
                                        @else
                                            <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-primary text-xs py-2.5 px-5 font-semibold">Mulai Kerjakan Kuis</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($isCodingTask || $isCodingMaterial)
                        <div class="mt-6 pt-5 border-t border-line/60">
                            <div class="rounded-xl border border-line bg-canvas/40 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-muted">{{ $isCodingMaterial ? 'Informasi Praktikum Pemrograman' : 'Informasi Tugas Pemrograman & Penilaian' }}</p>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-ink">
                                        @if(!$isCodingMaterial)
                                            <span>Durasi: <strong>{{ !empty($item['duration_enabled']) ? ($item['duration_minutes'] ?? 60).' menit' : 'Tanpa batas waktu' }}</strong></span>
                                        @endif
                                        <span><strong>{{ count($item['coding_steps'] ?? []) ?: (count($item['questions'] ?? []) ?: 1) }}</strong> butir instruksi / soal</span>
                                        @if(!$isCodingMaterial)
                                            <span>Total <strong>{{ $item['points'] ?? 100 }} poin</strong></span>
                                        @endif
                                        @if(!empty($item['due']))
                                            @php $isDuePast = \Carbon\Carbon::parse($item['due'])->isPast(); @endphp
                                            @if(!$isLecturer && $isDuePast && !$submission && $scoreValue === null)
                                                <span class="text-rose-600 font-semibold">Tenggat: <strong>{{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}</strong> (Terlambat)</span>
                                            @else
                                                <span>Tenggat: <strong>{{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}</strong></span>
                                            @endif
                                        @endif
                                    </div>

                                    @if(!$isLecturer && !$isCodingMaterial)
                                        @if($scoreValue !== null)
                                            <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-2">
                                                <span class="text-xs text-muted font-medium">Nilai Tugas:</span>
                                                <span class="text-sm font-bold {{ $scoreColorClass }} font-mono">{{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }} Poin</span>
                                                @if($submission)
                                                    <span class="text-xs text-slate-300">·</span>
                                                    <span class="text-xs font-semibold text-emerald-600">Sudah diserahkan {{ !empty($submission['time']) ? '('.$submission['time'].')' : '' }}</span>
                                                @endif
                                            </div>
                                        @elseif($submission)
                                            <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-1.5">
                                                <span class="text-xs font-semibold text-emerald-600">Sudah diserahkan {{ !empty($submission['time']) ? '· '.$submission['time'] : '' }}</span>
                                                <span class="text-xs text-slate-300">·</span>
                                                <span class="text-xs font-medium text-amber-600">Menunggu penilaian dosen</span>
                                            </div>
                                        @else
                                            <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-1.5">
                                                <span class="text-xs font-medium text-muted">Status: Belum diserahkan</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($isLecturer)
                                            @if($isCodingTask)
                                                <a href="{{ route('dosen.penilaian.asesmen.nilai', [$course['id'], $item['id']]) }}" class="button-secondary bg-white text-xs py-2.5 px-5 font-semibold">Lihat dan Nilai Mahasiswa</a>
                                            @else
                                                <a href="{{ route('course.assignment.code', [$course['id'], $item['id']]) }}" class="button-secondary bg-white text-xs py-2.5 px-5 font-semibold">Buka Praktikum Kode</a>
                                            @endif
                                        @elseif($isGraded || $submission)
                                            <a href="{{ route('course.assignment.code', [$course['id'], $item['id']]) }}" class="button-secondary bg-white text-xs py-2.5 px-5 font-semibold">Buka Editor Kode / Jawaban</a>
                                        @elseif($isLocked)
                                            <button type="button" disabled class="button-secondary text-xs py-2.5 px-5 font-semibold opacity-60">Tugas Ditutup</button>
                                        @else
                                            <a href="{{ route('course.assignment.code', [$course['id'], $item['id']]) }}" class="button-primary text-xs py-2.5 px-5 font-semibold">{{ $isCodingMaterial ? 'Buka Praktikum Kode' : 'Mulai Kerjakan Tugas Koding' }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php
                        $hasAttachments = !empty($item['question_image']) || !empty($item['attachments']) || !empty($item['link']);
                        $youtubeAttachment = \App\Support\LearningPreview::youtubeEmbedUrl($item['link'] ?? null);
                        $youtubeAttachmentUrl = $youtubeAttachment ? $youtubeAttachment.'&'.http_build_query([
                            'origin' => request()->getSchemeAndHttpHost(),
                            'widget_referrer' => request()->fullUrl(),
                        ]) : null;
                    @endphp
                    @if($hasAttachments)
                        <h3 class="mt-7 text-sm font-bold text-ink">Lampiran</h3>
                        <div class="mt-3 flex flex-wrap items-start gap-3">
                            {{-- Lampiran Video YouTube --}}
                            @if($youtubeAttachmentUrl)
                                @php
                                    $ytVideoId = \App\Support\LearningPreview::youtubeVideoId($item['link'] ?? null);
                                    $ytThumb = $ytVideoId ? "https://img.youtube.com/vi/{$ytVideoId}/hqdefault.jpg" : null;
                                    $ytPreviewData = [
                                        'title' => $item['title'].': Video YouTube',
                                        'url' => $youtubeAttachmentUrl,
                                        'downloadUrl' => $item['link'] ?? ($ytVideoId ? "https://www.youtube.com/watch?v={$ytVideoId}" : ''),
                                        'type' => 'youtube',
                                        'ext' => 'YOUTUBE',
                                        'meta' => 'Video YouTube perkuliahan',
                                        'videoId' => $ytVideoId,
                                    ];
                                @endphp
                                <div class="w-44 shrink-0 overflow-hidden rounded-lg border border-line/70 bg-white shadow-2xs hover:border-brand/40 transition" style="contain: paint;">
                                    <button type="button" onclick="openAttachmentPreview(event, {{ Illuminate\Support\Js::from($ytPreviewData) }})" class="relative flex aspect-video w-full flex-col items-center justify-center overflow-hidden border-b border-line bg-slate-900 group cursor-pointer" title="Putar Video YouTube">
                                        @if($ytThumb)
                                            <img src="{{ $ytThumb }}" alt="Thumbnail YouTube" class="h-full w-full object-cover group-hover:scale-105 transition duration-300">
                                            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/20 transition"></div>
                                        @endif
                                        <div class="absolute flex h-9 w-9 items-center justify-center rounded-full bg-red-600 text-white shadow-md group-hover:scale-110 transition">
                                            <svg class="h-4 w-4 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                        <span class="absolute bottom-1.5 right-1.5 rounded bg-black/75 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-white uppercase">YouTube</span>
                                    </button>
                                    <div class="flex h-9 min-w-0 items-center justify-between gap-1.5 px-2.5">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                                            </svg>
                                            <button type="button" onclick="openAttachmentPreview(event, {{ Illuminate\Support\Js::from($ytPreviewData) }})" class="min-w-0 flex-1 truncate text-left text-xs font-medium text-ink hover:underline cursor-pointer" title="{{ $item['title'] }}">
                                                Video YouTube
                                            </button>
                                        </div>
                                        <button type="button" onclick="openAttachmentPreview(event, {{ Illuminate\Support\Js::from($ytPreviewData) }})" class="shrink-0 text-muted hover:text-red-600 transition cursor-pointer" title="Putar Video YouTube">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                        </button>
                                    </div>
                                    <template id="youtube-player-template">
                                        <iframe class="w-full h-full border-0" src="{{ $youtubeAttachmentUrl }}" title="Video {{ $item['title'] }}" referrerpolicy="origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    </template>
                                </div>
                            @endif
                            {{-- Lampiran Gambar Soal / Materi --}}
                            @if(!empty($item['question_image']))
                                @php
                                    $imageMeta = \App\Support\LearningPreview::fileMeta($item['question_image']);
                                    $imageAlt = $item['image_alt'] ?? 'Gambar pendukung';
                                    $imageName = !empty($item['image_alt']) ? $item['image_alt'] : ($imageMeta['name'] ?? 'Gambar pendukung');
                                    $imgUrl = route('preview.file', ['file' => $item['question_image'], 'inline' => 1], false);
                                    $imgDownloadUrl = route('preview.file', ['file' => $item['question_image'], 'download' => 1], false);
                                @endphp
                                <div class="w-44 shrink-0 overflow-hidden rounded-lg border border-line/70 bg-white shadow-2xs hover:border-brand/40 transition">
                                    <a href="{{ $imgUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($imageName) }}', url: '{{ $imgUrl }}', downloadUrl: '{{ $imgDownloadUrl }}', type: 'image', ext: 'PNG' })" class="block aspect-video w-full overflow-hidden border-b border-line bg-slate-50 cursor-pointer" title="{{ $imageName }}">
                                        <img src="{{ $imgUrl }}" alt="{{ $imageAlt }}" class="h-full w-full object-contain">
                                    </a>
                                    <div class="flex h-9 min-w-0 items-center justify-between gap-1.5 px-2.5">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                                            <a href="{{ $imgUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($imageName) }}', url: '{{ $imgUrl }}', downloadUrl: '{{ $imgDownloadUrl }}', type: 'image', ext: 'PNG' })" class="min-w-0 flex-1 truncate text-xs font-medium text-ink hover:underline cursor-pointer" title="{{ $imageName }}">{{ $imageName }}</a>
                                        </div>
                                        <a href="{{ $imgDownloadUrl }}" class="shrink-0 text-muted hover:text-ink" aria-label="Unduh {{ $imageName }}" title="Unduh">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 21h14"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endif

                            {{-- Lampiran Berkas Dokumen / PDF / Gambar / Video / Slide Tambahan --}}
                            @foreach($item['attachments'] ?? [] as $file)
                                @php
                                    $fileId = is_array($file) ? ($file['uuid'] ?? $file['id'] ?? $file['path'] ?? '') : (string) $file;
                                    $fileMeta = \App\Support\LearningPreview::fileMeta($file);
                                    $fileMime = $fileMeta['mime'] ?? '';
                                    $fileName = $fileMeta['name'] ?? (is_string($file) && !\Illuminate\Support\Str::isUuid($file) ? basename($file) : 'Berkas lampiran');
                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: (pathinfo($fileMeta['path'] ?? '', PATHINFO_EXTENSION) ?: ''));
                                    if (empty($fileExt) && str_contains($fileMime, 'pdf')) {
                                        $fileExt = 'pdf';
                                    } elseif (empty($fileExt)) {
                                        $fileExt = 'file';
                                    }
                                    $isPdf = $fileMime === 'application/pdf' || $fileExt === 'pdf' || str_ends_with(strtolower($fileName), '.pdf');
                                    if ($isPdf) {
                                        $fileExt = 'pdf';
                                    }
                                    $isImage = str_starts_with($fileMime, 'image/') || in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $isVideo = str_starts_with($fileMime, 'video/') || in_array($fileExt, ['mp4', 'webm', 'ogg'], true);
                                    $isSlides = in_array($fileExt, ['ppt', 'pptx'], true);
                                    $isWord = in_array($fileExt, ['doc', 'docx'], true);

                                    $previewType = $isVideo ? 'video' : ($isPdf ? 'pdf' : ($isImage ? 'image' : ($isSlides ? 'slides' : ($isWord ? 'word' : 'file'))));
                                    $fileUrl = route('preview.file', ['file' => $fileId, 'inline' => ($isPdf || $isVideo || $isImage) ? 1 : null], false);
                                    $fileDownloadUrl = route('preview.file', ['file' => $fileId, 'download' => 1], false);
                                @endphp
                                <div class="w-44 shrink-0 overflow-hidden rounded-lg border border-line/70 bg-white shadow-2xs hover:border-brand/40 transition" style="contain: paint;">
                                    @if($isPdf)
                                        @php
                                            $thumbnailUrl = route('preview.file', ['file' => $fileId, 'thumbnail' => 1], false);
                                        @endphp
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'pdf', ext: 'PDF' })" class="relative flex aspect-video w-full flex-col items-center justify-center overflow-hidden border-b border-line bg-slate-50 cursor-pointer group hover:bg-slate-100/80 transition" title="Buka pratinjau {{ $fileName }}">
                                            {{-- Underlying fallback icon --}}
                                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 z-0">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white border border-slate-200 text-rose-700 shadow-2xs group-hover:scale-105 transition">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                        <path d="M9 13h6"/>
                                                        <path d="M9 17h4"/>
                                                    </svg>
                                                </div>
                                                <span class="text-[11px] font-bold tracking-wider text-rose-700 uppercase">Dokumen PDF</span>
                                            </div>
                                            {{-- Server-side instant thumbnail image --}}
                                            <img src="{{ $thumbnailUrl }}" alt="Pratinjau {{ $fileName }}" loading="lazy" class="absolute top-0 left-0 w-full h-full object-cover object-top block z-1 bg-white opacity-0 transition-opacity duration-200 pointer-events-none" onload="this.classList.remove('opacity-0')" onerror="this.remove()">
                                            {{-- Click capture overlay and hover effect --}}
                                            <div class="absolute inset-0 z-10 bg-transparent group-hover:bg-slate-900/10 transition"></div>
                                        </a>
                                    @elseif($isVideo)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'video', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-slate-900 text-white cursor-pointer group hover:opacity-95 transition" title="Putar video {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand text-white shadow-md group-hover:scale-110 transition">
                                                <svg class="h-5 w-5 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </div>
                                            <span class="text-[11px] font-bold tracking-wider text-white uppercase">Video {{ strtoupper($fileExt) }}</span>
                                        </a>
                                    @elseif($isSlides)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'slides', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-slate-50 cursor-pointer group hover:bg-slate-100/80 transition" title="Slide presentasi {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-700 shadow-2xs group-hover:scale-105 transition">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <rect width="18" height="14" x="3" y="3" rx="2"/>
                                                    <path d="M3 9h18"/>
                                                    <path d="m8 21 4-4 4 4"/>
                                                </svg>
                                            </div>
                                            <span class="text-[11px] font-bold tracking-wider text-amber-800 uppercase">Slide {{ strtoupper($fileExt) }}</span>
                                        </a>
                                    @elseif($isWord)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'word', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-slate-50 cursor-pointer group hover:bg-slate-100/80 transition" title="Dokumen {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white border border-slate-200 text-blue-700 shadow-2xs group-hover:scale-105 transition">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <path d="M8 13h8"/>
                                                    <path d="M8 17h6"/>
                                                </svg>
                                            </div>
                                            <span class="text-[11px] font-bold tracking-wider text-blue-700 uppercase">Dokumen Word</span>
                                        </a>
                                    @elseif($isImage)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'image', ext: '{{ strtoupper($fileExt) }}' })" class="block aspect-video w-full overflow-hidden border-b border-line bg-slate-50 cursor-pointer" title="{{ $fileName }}">
                                            <img src="{{ $fileUrl }}" alt="{{ $fileName }}" class="h-full w-full object-contain">
                                        </a>
                                    @else
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'file', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1 border-b border-line bg-slate-50 text-muted cursor-pointer hover:bg-slate-100 transition" title="{{ $fileName }}">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <span class="text-[10px] font-medium text-muted uppercase">{{ $fileExt }}</span>
                                        </a>
                                    @endif
                                    <div class="flex h-9 min-w-0 items-center justify-between gap-1.5 px-2.5">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                            @if($isImage)
                                                <svg class="h-3.5 w-3.5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                                            @elseif($isVideo)
                                                <svg class="h-3.5 w-3.5 shrink-0 text-brand" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                            @elseif($isSlides)
                                                <svg class="h-3.5 w-3.5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="14" x="3" y="3" rx="2"/><path d="M3 9h18"/></svg>
                                            @else
                                                <svg class="h-3.5 w-3.5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            @endif
                                            <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: '{{ $previewType }}', ext: '{{ strtoupper($fileExt) }}' })" class="min-w-0 flex-1 truncate text-xs font-medium text-ink hover:underline cursor-pointer" title="{{ $fileName }}">{{ $fileName }}</a>
                                        </div>
                                        <a href="{{ $fileDownloadUrl }}" class="shrink-0 text-muted hover:text-ink" aria-label="Unduh {{ $fileName }}" title="Unduh">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 21h14"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Lampiran Tautan Luar jika ada --}}
                            @if(!empty($item['link']) && !$youtubeAttachment)
                                @php
                                    $linkPreviewData = [
                                        'title' => $item['title'].': Tautan',
                                        'url' => $item['link'],
                                        'type' => 'link',
                                        'ext' => 'LINK',
                                        'meta' => 'Tautan materi perkuliahan',
                                    ];
                                @endphp
                                <div class="w-44 shrink-0 overflow-hidden rounded-lg border border-line/70 bg-white shadow-2xs hover:border-brand/40 transition">
                                    <button type="button" onclick="openAttachmentPreview(event, {{ Illuminate\Support\Js::from($linkPreviewData) }})" class="flex aspect-video w-full flex-col items-center justify-center gap-1 border-b border-line bg-slate-50 text-muted hover:text-brand transition group" title="Pratinjau {{ $item['link'] }}">
                                        <svg class="h-6 w-6 text-muted group-hover:text-brand transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                        <span class="text-[10px] font-medium text-muted group-hover:text-brand transition">Pratinjau Tautan</span>
                                    </button>
                                    <div class="flex h-9 min-w-0 items-center justify-between gap-1.5 px-2.5">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                            </svg>
                                            <button type="button" onclick="openAttachmentPreview(event, {{ Illuminate\Support\Js::from($linkPreviewData) }})" class="min-w-0 flex-1 truncate text-left text-xs font-medium text-ink hover:underline" title="Pratinjau {{ $item['link'] }}">
                                                {{ preg_replace('#^https?://#', '', $item['link']) }}
                                            </button>
                                        </div>
                                        <button type="button" onclick="openAttachmentPreview(event, {{ Illuminate\Support\Js::from($linkPreviewData) }})" class="shrink-0 text-muted hover:text-ink" title="Pratinjau tautan">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 5h18v14H3z"/><path d="m8 9 3 3-3 3M13 15h3"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                </section>

                {{-- Task Questions for Regular Assignments (Non-CBT Quiz, Non-Coding) --}}
                @if(!$isLecturer && $isTask && !$isDedicatedQuiz && !$isCodingTask && $hasMultiQuestions)
                    <div class="space-y-5">
                        @foreach($item['questions'] as $qIdx => $q)
                            <section class="surface p-6 sm:p-7 space-y-4">
                                <input type="hidden" name="question_answers[{{ $q['id'] }}][question_id]" value="{{ $q['id'] }}">
                                <div class="flex items-center justify-between border-b border-line/50 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-brand">Soal {{ $qIdx + 1 }}</span>
                                        <span class="text-xs font-semibold text-muted uppercase">{{ $q['type'] }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-ink">{{ $q['points'] ?? 10 }} Poin</span>
                                </div>
                                <p class="text-sm text-ink leading-relaxed">{{ $q['prompt'] }}</p>

                                @if(!empty($q['image']))
                                    <div class="rounded-lg overflow-hidden border border-line bg-white p-2 text-center max-w-md">
                                        <img src="{{ route('preview.file', ['file' => $q['image'], 'inline' => 1], false) }}" alt="{{ $q['alt'] ?? 'Gambar soal' }}" class="max-h-56 mx-auto rounded object-contain">
                                    </div>
                                @endif

                                @if($q['type'] === 'benar_salah')
                                    <div class="flex items-center gap-4 pt-1">
                                        @foreach($q['option_items'] ?? [] as $option)
                                            <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                                <input type="radio" name="question_answers[{{ $q['id'] }}][option_ids][]" value="{{ $option['id'] }}" @checked(in_array($option['id'], old('question_answers.'.$q['id'].'.option_ids', $submission['question_answers'][$q['id']]['option_ids'] ?? [])))>
                                                <span class="font-medium text-ink">{{ $option['text'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($q['type'] === 'mencocokkan')
                                    @php
                                        $pairs = $q['matching_items'] ?? [];
                                        $allRights = array_map(fn($pair) => ['id' => $pair['option_id'], 'text' => $pair['answer']], $pairs);
                                        // Randomize target order deterministically per student so that:
                                        // 1) Different students see different positions (beda orang beda susunan)
                                        // 2) The choices in the dropdown are NOT in the same line/order as the premises (tidak sebaris)
                                        $userSeed = auth()->id();
                                        $seed = (int) ($item['id'] ?? 1) * 37 + (int) $userSeed * 19 + ($qIdx + 1) * 11;
                                        mt_srand($seed);
                                        $shuffledRights = $allRights;
                                        shuffle($shuffledRights);
                                        mt_srand();
                                    @endphp
                                    <div class="space-y-3 pt-1">
                                        @foreach($pairs as $pIdx => $pair)
                                            @php
                                                    $isLeftImg = str_starts_with($pair['prompt'], 'http') || str_starts_with($pair['prompt'], 'data:image') || str_starts_with($pair['prompt'], '/');
                                            @endphp
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-lg bg-canvas border border-line/50">
                                                <div class="min-w-0 flex-1">
                                                    <span class="text-[11px] font-bold text-muted block mb-1">Premis {{ $pIdx + 1 }}</span>
                                                    @if($isLeftImg)
                                                        <img src="{{ $pair['prompt'] }}" alt="Premis {{ $pIdx + 1 }}" class="h-14 max-w-xs object-contain rounded border border-line/60 bg-white p-1">
                                                    @else
                                                        <span class="text-xs font-medium text-ink">{{ $pair['prompt'] }}</span>
                                                    @endif
                                                </div>
                                                <select name="question_answers[{{ $q['id'] }}][matches][{{ $pair['id'] }}]" class="field text-xs sm:w-64" @disabled($isInputsDisabled)>
                                                    <option value="">Pilih Pasangan</option>
                                                    @foreach($shuffledRights as $target)
                                                        <option value="{{ $target['id'] }}" @selected(old('question_answers.'.$q['id'].'.matches.'.$pair['id'], $submission['question_answers'][$q['id']]['matches'][$pair['id']] ?? '') === $target['id'])>
                                                            {{ str_starts_with($target['text'], 'data:image') ? 'Gambar Pasangan' : $target['text'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(in_array($q['type'], ['pilihan', 'kompleks']))
                                    @php
                                        $options = $q['option_items'] ?? [];
                                        $isMultiple = $q['type'] === 'kompleks';
                                    @endphp
                                    <div class="space-y-2 pt-1">
                                        @foreach($options as $option)
                                            <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-xs {{ $isInputsDisabled ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                                <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}" name="question_answers[{{ $q['id'] }}][option_ids][]" value="{{ $option['id'] }}"
                                                    @checked(in_array($option['id'], old('question_answers.'.$q['id'].'.option_ids', $submission['question_answers'][$q['id']]['option_ids'] ?? [])))
                                                    @disabled($isInputsDisabled)>
                                                <span class="text-ink">{{ $option['text'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <textarea name="question_answers[{{ $q['id'] }}][text]" rows="4" class="field text-xs" @readonly($isInputsDisabled) placeholder="Tulis jawaban di sini...">{{ old('question_answers.'.$q['id'].'.text', $submission['question_answers'][$q['id']]['text'] ?? '') }}</textarea>
                                @endif
                            </section>
                        @endforeach
                    </div>
                @endif

                {{-- Single Objective Questions (Only Choices in Center, No Duplicate Button) --}}
                @if(!$isLecturer && $isTask && !$hasMultiQuestions && !$isCodingTask && in_array($item['question_type'], ['pilihan', 'kompleks', 'benar_salah']))
                    <section class="surface p-6 sm:p-7 space-y-4">
                        <h2 class="section-heading">Lembar Jawaban Soal</h2>
                        @if(in_array($item['question_type'], ['pilihan', 'kompleks']))
                            <fieldset class="space-y-2">
                                <legend class="form-label text-xs font-bold">{{ $item['question_type'] === 'kompleks' ? 'Pilih semua jawaban yang benar' : 'Pilih satu jawaban' }}</legend>
                                <div class="space-y-2">
                                    @foreach(array_filter(array_map('trim', explode("\n", $item['options'] ?? '')), fn($option) => $option !== '') as $option)
                                        <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-xs {{ $isInputsDisabled ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                            <input type="{{ $item['question_type'] === 'kompleks' ? 'checkbox' : 'radio' }}" name="choices[]" value="{{ $option }}"
                                                @checked(in_array($option, old('choices', $submission['choices'] ?? [])))
                                                @disabled($isInputsDisabled)>
                                            <span class="text-ink">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @elseif($item['question_type'] === 'benar_salah')
                            <fieldset class="space-y-2">
                                <legend class="form-label text-xs font-bold">Pilih Benar atau Salah</legend>
                                <div class="flex items-center gap-4 pt-1">
                                    <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs {{ $isInputsDisabled ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                        <input type="radio" name="boolean_choice" value="Benar" @checked(old('boolean_choice', $submission['boolean_choice'] ?? '') === 'Benar') @disabled($isInputsDisabled)>
                                        <span class="font-medium text-ink">Benar</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs {{ $isInputsDisabled ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                        <input type="radio" name="boolean_choice" value="Salah" @checked(old('boolean_choice', $submission['boolean_choice'] ?? '') === 'Salah') @disabled($isInputsDisabled)>
                                        <span class="font-medium text-ink">Salah</span>
                                    </label>
                                </div>
                            </fieldset>
                        @endif
                    </section>
                @endif

            </div>

            {{-- Right Aside Column: Status & Submission (Student) or Content Control (Lecturer) --}}
            @if($isLecturer)
                {{-- Lecturer Management Panel --}}
                <aside class="rounded-xl bg-white p-5 shadow-sm space-y-5 h-fit xl:sticky xl:top-24 border border-line/60">
                    <div class="border-b border-line/60 pb-3">
                        <h2 class="text-sm font-bold text-ink">Pengelolaan Pengampu</h2>
                    </div>

                    @if($isTask)
                        <div class="space-y-2.5 text-xs">
                            @if($item['type'] !== 'lainnya')
                                <div class="flex items-center justify-between text-muted">
                                    <span>Total Bobot:</span>
                                    <span class="font-bold text-ink">{{ $item['points'] ?? 100 }} Poin</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between text-muted">
                                <span>Tenggat Waktu:</span>
                                <span class="font-medium text-ink">{{ !empty($item['due']) ? \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tanpa tenggat' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted">
                                <span>Pengumpulan Terlambat:</span>
                                <span class="font-medium text-ink">{{ !empty($item['allow_late']) ? 'Diizinkan' : 'Ditolak / Dikunci' }}</span>
                            </div>
                            @if(!empty($item['component']) && $item['type'] !== 'lainnya')
                                <div class="flex items-center justify-between text-muted">
                                    <span>Komponen Evaluasi:</span>
                                    <span class="font-semibold text-ink uppercase">{{ $item['component'] }}</span>
                                </div>
                            @endif
                        </div>

                        @php
                            $totalEnrolled = 0;
                            $submittedCount = 0;
                            $lateCount = 0;
                            if ($isLecturer && \Illuminate\Support\Facades\Schema::hasTable('class_sections')) {
                                $sectionModel = \App\Models\ClassSection::find($course['id']);
                                if ($sectionModel) {
                                    $totalEnrolled = $sectionModel->students()->count();
                                    if (\Illuminate\Support\Facades\Schema::hasTable('submissions')) {
                                        $allSubs = \App\Models\Submission::where('assessment_id', $item['id'])->get();
                                        $submittedCount = $allSubs->count();
                                        $dueDate = !empty($item['due']) ? \Carbon\Carbon::parse($item['due']) : null;
                                        if ($dueDate) {
                                            $lateCount = $allSubs->filter(fn($s) => $s->submitted_at && $s->submitted_at->greaterThan($dueDate))->count();
                                        }
                                    }
                                }
                            }
                        @endphp

                        <div class="rounded-lg bg-canvas p-3 text-xs space-y-1.5 border border-line/60">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-ink">Pengumpulan Mahasiswa</span>
                                <span class="font-mono font-bold text-ink">{{ $submittedCount }} / {{ $totalEnrolled }}</span>
                            </div>
                            @if($lateCount > 0)
                                <div class="flex items-center justify-between text-[11px] text-amber-800 font-semibold pt-1 border-t border-line/60">
                                    <span>Dikumpulkan terlambat:</span>
                                    <span>{{ $lateCount }} mahasiswa</span>
                                </div>
                            @endif
                            <p class="text-muted text-[11px] pt-0.5">{{ $item['type'] === 'lainnya' ? 'Lihat berkas atau data yang dikumpulkan oleh mahasiswa.' : 'Lihat seluruh pengumpulan mahasiswa dan lakukan penilaian jawaban serta berkas tugas.' }}</p>
                        </div>

                        <div class="pt-1">
                            <a href="{{ route('dosen.penilaian.asesmen.nilai', [$course['id'], $item['id']]) }}" class="button-primary w-full py-2.5 text-xs font-bold text-center block shadow-xs">
                                {{ $item['type'] === 'lainnya' ? 'Lihat Pengumpulan Mahasiswa' : 'Lihat & Nilai Mahasiswa' }}
                            </a>
                        </div>
                    @else
                        <div class="space-y-2 text-xs text-muted">
                            <div class="flex items-center justify-between">
                                <span>Modul:</span>
                                <span class="font-semibold text-ink">{{ $item['module'] ?? 'Umum' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Status:</span>
                                <span class="font-semibold text-emerald-600">Dipublikasikan</span>
                            </div>
                        </div>
                    @endif

                    @if(!$isArchived)
                        <div class="space-y-2 pt-2 border-t border-line/60">
                            <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-secondary w-full py-2 text-xs font-semibold text-center block">
                                + Tambah Konten / Soal Baru
                            </a>
                        </div>
                    @endif
                </aside>
            @elseif($isTask && !$isDedicatedQuiz && !$isCodingTask)
                {{-- Student Submission Panel (Google Classroom Style for Tugas, PBL, etc.) --}}
                <aside class="rounded-xl bg-white p-5 shadow-sm space-y-4 h-fit xl:sticky xl:top-24 border border-line/60">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-ink">{{ $item['type'] === 'lainnya' ? 'Pengumpulan Anda' : 'Tugas Anda' }}</h2>
                        @if($isGraded)
                            <div class="text-right">
                                <span class="whitespace-nowrap text-sm font-bold {{ $scoreColorClass }}">{{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }}</span>
                                <span class="block text-[11px] font-semibold {{ $scoreColorClass }}">{{ $isScorePassed ? 'Sudah dinilai (Tuntas)' : 'Sudah dinilai (Di bawah ambang)' }}</span>
                            </div>
                        @elseif($submission)
                            @php
                                $submittedAt = !empty($submission['submitted_at']) ? \Carbon\Carbon::parse($submission['submitted_at']) : (!empty($submission['time']) ? \Carbon\Carbon::parse($submission['time']) : null);
                                $wasSubmittedLate = !empty($item['due']) && $submittedAt && $submittedAt->greaterThan(\Carbon\Carbon::parse($item['due']));
                            @endphp
                            @if($wasSubmittedLate)
                                <div class="text-right">
                                    <span class="text-xs font-semibold text-emerald-600">Diserahkan</span>
                                    <span class="block text-[11px] font-semibold text-amber-600">Terlambat</span>
                                </div>
                            @else
                                <span class="text-xs font-semibold text-emerald-600">Diserahkan</span>
                            @endif
                        @elseif($isPast)
                            <div class="text-right">
                                <span class="text-xs font-semibold text-rose-600">Terlambat</span>
                                <span class="block text-[10px] text-muted">Belum diserahkan</span>
                            </div>
                        @else
                            <span class="text-xs font-semibold text-rose-600">Belum diserahkan</span>
                        @endif
                    </div>

                    <div class="text-xs text-muted flex items-center gap-2">
                        @if($item['type'] !== 'lainnya')
                            <span>{{ $item['points'] ?? 100 }} Poin</span>
                        @endif
                        @if(!empty($item['due']))
                            @php $isDuePast = \Carbon\Carbon::parse($item['due'])->isPast(); @endphp
                            <span class="{{ $isDuePast && empty($submission) ? 'text-rose-600 font-medium' : '' }}">Tenggat {{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}</span>
                        @else
                            <span>Tanpa tenggat</span>
                        @endif
                    </div>

                    {{-- Attached Work Items (Existing or New) --}}
                    <div class="space-y-2" data-attachment-container>
                        {{-- Saved files --}}
                        @if(!empty($submission['files']))
                            @foreach($submission['files'] as $sf)
                                @php
                                    $fileId = is_array($sf) ? ($sf['id'] ?? '') : (string) $sf;
                                    $fileMeta = \App\Support\LearningPreview::fileMeta($fileId);
                                    $fileName = is_array($sf) ? ($sf['name'] ?? $fileId) : ($fileMeta['name'] ?? (\App\Models\Attachment::where('uuid', $fileId)->value('name') ?? $fileId));
                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: '');
                                    $isSubImg = in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $subFileUrl = route('preview.file', ['file' => $fileId, 'inline' => 1], false);
                                @endphp
                                <div class="space-y-1.5 p-2.5 rounded-lg bg-canvas border border-line/40 text-xs">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <a href="{{ $subFileUrl }}" target="_blank" class="text-ink truncate font-medium hover:text-brand hover:underline">{{ $fileName }}</a>
                                        </div>
                                    </div>
                                    @if($isSubImg)
                                        <div class="rounded-lg overflow-hidden border border-line bg-white p-1 text-center">
                                            <a href="{{ $subFileUrl }}" target="_blank" title="Klik untuk membuka gambar penuh">
                                                <img src="{{ $subFileUrl }}" alt="{{ $fileName }}" class="max-h-36 mx-auto rounded object-contain">
                                            </a>
                                        </div>
                                    @endif
                                    <input type="hidden" name="keep_files[]" value="{{ $fileId }}">
                                </div>
                            @endforeach
                        @endif

                        {{-- Saved link --}}
                        @if(!empty($submission['link']))
                            <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-canvas border border-line/40">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    <a href="{{ $submission['link'] }}" target="_blank" class="text-brand truncate font-medium hover:underline">{{ $submission['link'] }}</a>
                                </div>
                            </div>
                        @endif

                        {{-- Saved answer preview if exists --}}
                        @if(!empty($submission['answer']) && empty($item['questions']))
                            @php
                                $isJsonCode = str_starts_with(trim($submission['answer']), '[') || str_starts_with(trim($submission['answer']), '{');
                            @endphp
                            <div class="text-xs p-2.5 rounded-lg bg-canvas border border-line/40 space-y-1">
                                <span class="text-muted block text-[11px] font-semibold">Catatan / Jawaban:</span>
                                @if($isJsonCode)
                                    <div class="flex items-center justify-between gap-2 pt-0.5">
                                        <span class="text-ink font-medium">Kode program telah diserahkan.</span>
                                        <a href="{{ route('course.assignment.code', [$course['id'], $item['id']]) }}" class="text-brand hover:underline font-semibold shrink-0">Buka di Editor Kode ↗</a>
                                    </div>
                                @else
                                    <p class="text-ink line-clamp-3">{{ $submission['answer'] }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Dynamic list for new additions --}}
                        <div data-active-attachments class="space-y-2"></div>
                    </div>

                    {{-- Action Button: + Tambah atau buat --}}
                    @if(!$isLocked && !$submission && !$isGraded && !$isArchived)
                        <div class="relative" data-add-work-dropdown>
                            <button type="button" data-toggle-dropdown class="button-secondary w-full py-2 text-xs font-semibold flex items-center justify-center gap-2">
                                <span class="text-sm font-bold text-brand">+</span> Tambah atau buat
                            </button>
                            <div data-dropdown-menu hidden class="absolute left-0 right-0 top-full mt-1.5 z-20 rounded-xl bg-white p-1.5 shadow-lg border border-line/60 space-y-1">
                                <button type="button" data-action-add="link" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-ink hover:bg-slate-100 transition text-left">
                                    <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    <span>Tautan / Link</span>
                                </button>
                                <button type="button" data-action-add="file" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-ink hover:bg-slate-100 transition text-left">
                                    <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span>Berkas / File</span>
                                </button>
                                <button type="button" data-action-add="text" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-ink hover:bg-slate-100 transition text-left">
                                    <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Catatan / Jawaban Teks</span>
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-muted leading-tight mt-1.5">Maks. 5 berkas (masing-masing maks. 5 MB). Untuk berkas besar, silakan gunakan opsi tautan Google Drive.</p>
                    @endif

                    {{-- Hidden inputs for file/link/answer --}}
                    <input type="file" name="files[]" multiple data-submission-files class="hidden" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.webp">
                    <input type="hidden" name="link" data-submission-link value="{{ old('link') }}">

                    {{-- Optional text answer area --}}
                    @if(!$submission && !$isLocked && !$isGraded && !$isArchived)
                        <div data-text-answer-box hidden class="space-y-1 pt-1">
                            <div class="flex items-center justify-between">
                                <label class="form-label text-[11px] mb-0" for="answer_field">Jawaban Teks</label>
                                <button type="button" data-remove-text-box class="text-[11px] text-danger hover:underline">Batal</button>
                            </div>
                            <textarea id="answer_field" name="answer" rows="4" class="field text-xs" placeholder="Tuliskan jawaban atau catatan pengerjaan tugas...">{{ old('answer', $submission['answer'] ?? '') }}</textarea>
                        </div>
                    @endif

                    {{-- Submit / Status Button (Right sidebar) --}}
                    <div class="pt-2">
                        @if($isGraded)
                            <button type="button" disabled class="button-secondary bg-slate-100 text-slate-600 w-full py-2.5 text-xs font-semibold cursor-default">
                                Sudah Dinilai
                            </button>
                        @elseif($submission)
                            @if(($isPast && !$allowLate) || $isArchived)
                                <button type="button" disabled class="button-secondary bg-slate-100 text-slate-600 w-full py-2.5 text-xs font-semibold cursor-default">
                                    Sudah Diserahkan
                                </button>
                            @else
                                <button type="button" onclick="cancelSubmissionConfirm()" class="button-secondary w-full py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 border-rose-200 transition">
                                    Batalkan Serahkan
                                </button>
                            @endif
                        @elseif($isArchived)
                            <div class="rounded-lg border border-line bg-canvas/60 p-3 text-center">
                                <p class="text-xs font-medium text-muted">Kelas telah diarsipkan. Pengumpulan tugas ditutup.</p>
                            </div>
                        @elseif($isLocked)
                            <button type="button" disabled class="button-secondary w-full py-2.5 text-xs font-semibold opacity-60 cursor-not-allowed">
                                Pengumpulan Ditutup
                            </button>
                        @else
                            <button type="submit" class="button-primary w-full py-2.5 text-xs font-bold">
                                {{ $item['type'] === 'lainnya' ? 'Kirimkan' : 'Kumpulkan Tugas' }}
                            </button>
                        @endif
                    </div>
                </aside>
            @endif
        </div>
    @unless($isLecturer || $isDedicatedQuiz || $isCodingTask)
    </form>
    @endunless

    @if($submission && (!$isPast || $allowLate) && !$isGraded && !$isArchived)
        <form id="cancel-submission-form" action="{{ route('mahasiswa.course.submission.cancel', [$course['id'], $item['id']]) }}" method="POST" class="hidden">
            @csrf
        </form>
    @endif

    {{-- Modal Dialog Tambah Link --}}
    <dialog id="link-modal" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 max-w-md w-[calc(100%-2rem)] overflow-hidden h-fit">
        <div class="px-5 py-4 border-b border-line/60 flex items-center justify-between">
            <h3 class="text-sm font-bold text-ink">Tambahkan Tautan</h3>
            <button type="button" id="modal-link-close-btn" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <p class="text-xs text-muted leading-relaxed">Tempelkan tautan URL repositori, Google Drive, atau dokumen referensi tugas.</p>
            <div class="space-y-1.5">
                <label for="modal-link-input" class="form-label text-xs">URL Tautan <span class="text-danger">*</span></label>
                <input type="url" id="modal-link-input" class="field text-xs py-2 w-full font-mono" placeholder="https://example.com/tugas">
                <p id="modal-link-error" class="text-[11px] text-danger hidden">Tautan harus berupa URL valid yang diawali http:// atau https://</p>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-canvas/30 border-t border-line/60 flex items-center justify-end gap-2.5">
            <button type="button" id="modal-link-cancel" class="button-secondary text-xs py-1.5 px-3 cursor-pointer">Batal</button>
            <button type="button" id="modal-link-submit" class="button-primary text-xs py-1.5 px-3.5 font-semibold cursor-pointer">Tambahkan Link</button>
        </div>
    </dialog>

    {{-- Google Drive / Classroom Style Attachment Previewer Modal --}}
    <dialog id="attachment-preview-dialog" class="submission-preview" aria-labelledby="attachment-preview-title">
        <header class="submission-header">
            <span class="submission-filetype text-xs font-bold" id="attachment-preview-badge" aria-hidden="true">FILE</span>
            <div class="min-w-0 flex-1">
                <h2 id="attachment-preview-title" class="text-sm font-semibold text-ink truncate">Pratinjau Berkas</h2>
                <p id="attachment-preview-meta" class="mt-0.5 text-xs text-muted truncate">Lampiran Pembelajaran</p>
            </div>
            <div class="submission-actions">
                <a id="attachment-preview-download" href="#" class="button-secondary text-xs inline-flex items-center gap-1.5" download title="Unduh berkas">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 21h14"/></svg>
                    <span>Unduh</span>
                </a>
                <a id="attachment-preview-open" href="#" target="_blank" rel="noopener noreferrer" class="button-secondary text-xs inline-flex items-center gap-1.5" title="Buka di tab baru">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span class="hidden sm:inline">Buka Tab Baru</span>
                </a>
                <button type="button" class="submission-close" onclick="closeAttachmentPreview()" aria-label="Tutup pratinjau">
                    <svg width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></svg>
                </button>
            </div>
        </header>
        <div class="submission-body flex items-center justify-center p-4 bg-slate-100/90 flex-1 min-h-[420px]" id="attachment-preview-body">
            <!-- Dynamic Preview Content injected via JS -->
        </div>
    </dialog>
</div>

<script>
    function openAttachmentPreview(e, fileData) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const dialog = document.getElementById('attachment-preview-dialog');
        if (!dialog) return;

        const titleEl = document.getElementById('attachment-preview-title');
        const badgeEl = document.getElementById('attachment-preview-badge');
        const metaEl = document.getElementById('attachment-preview-meta');
        const downloadEl = document.getElementById('attachment-preview-download');
        const openEl = document.getElementById('attachment-preview-open');
        const bodyEl = document.getElementById('attachment-preview-body');

        titleEl.textContent = fileData.title || 'Berkas Lampiran';
        badgeEl.textContent = (fileData.ext || 'FILE').toUpperCase().slice(0, 6);
        metaEl.textContent = fileData.meta || 'Lampiran perkuliahan';
        let ytId = fileData.videoId || '';
        if (!ytId && fileData.url) {
            const m = fileData.url.match(/(?:embed\/|v\/|watch\?v=|\.be\/)([a-zA-Z0-9_-]{11})/);
            if (m) ytId = m[1];
        }
        if (!ytId && fileData.downloadUrl) {
            const m = fileData.downloadUrl.match(/(?:embed\/|v\/|watch\?v=|\.be\/)([a-zA-Z0-9_-]{11})/);
            if (m) ytId = m[1];
        }
        const ytWatchUrl = ytId ? `https://www.youtube.com/watch?v=${ytId}` : (fileData.downloadUrl || fileData.url);

        const isExternalLink = fileData.type === 'link' || fileData.type === 'youtube';
        downloadEl.href = fileData.downloadUrl || fileData.url;
        openEl.href = fileData.type === 'youtube' ? ytWatchUrl : ((isExternalLink && fileData.downloadUrl) ? fileData.downloadUrl : fileData.url);
        downloadEl.hidden = isExternalLink;
        openEl.querySelector('span').textContent = fileData.type === 'youtube' ? 'Tonton di YouTube' : (isExternalLink ? 'Buka Sumber' : 'Buka Tab Baru');

        bodyEl.innerHTML = '';

        if (fileData.type === 'video') {
            bodyEl.className = 'submission-body flex items-center justify-center p-4 bg-slate-900/90 flex-1 min-h-[420px]';
            const vid = document.createElement('video');
            vid.src = fileData.url;
            vid.controls = true;
            vid.autoplay = true;
            vid.className = 'max-h-[75vh] max-w-full rounded-lg shadow-md bg-black';
            bodyEl.appendChild(vid);
        } else if (fileData.type === 'youtube') {
            bodyEl.className = 'submission-body flex flex-col p-2 sm:p-4 bg-slate-900/90 flex-1 min-h-[500px] h-full';
            const wrapper = document.createElement('div');
            wrapper.className = 'w-full flex-1 flex flex-col items-center justify-center';
            const frame = document.createElement('iframe');
            const embedSrc = ytId
                ? `https://www.youtube.com/embed/${ytId}?autoplay=1&rel=0&playsinline=1`
                : fileData.url;
            frame.src = embedSrc;
            frame.title = `Pratinjau ${fileData.title}`;
            frame.className = 'w-full flex-1 min-h-[460px] h-full border-0 rounded-lg bg-black shadow-md';
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            frame.allowFullscreen = true;
            wrapper.appendChild(frame);
            if (ytWatchUrl) {
                const fallback = document.createElement('div');
                fallback.className = 'mt-2 text-center text-xs text-slate-300 flex items-center justify-center gap-2';
                fallback.innerHTML = `<span>Jika pemutaran video YouTube terkendala di peramban:</span> <a href="${ytWatchUrl}" target="_blank" rel="noopener noreferrer" class="text-red-400 hover:text-red-300 font-medium underline inline-flex items-center gap-1">Tonton di YouTube <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a>`;
                wrapper.appendChild(fallback);
            }
            bodyEl.appendChild(wrapper);
        } else if (fileData.type === 'pdf' || fileData.type === 'link') {
            bodyEl.className = 'submission-body flex flex-col p-2 sm:p-4 bg-slate-100/90 flex-1 min-h-[500px] h-full';
            const frame = document.createElement('iframe');
            frame.src = fileData.url;
            frame.title = `Pratinjau ${fileData.title}`;
            frame.className = 'w-full flex-1 min-h-[520px] h-full border-0 rounded-lg bg-white shadow-xs';
            frame.referrerPolicy = 'origin';
            if (fileData.type === 'link') {
                frame.sandbox = 'allow-forms allow-popups allow-same-origin allow-scripts';
            }
            bodyEl.appendChild(frame);
        } else if (fileData.type === 'image') {
            bodyEl.className = 'submission-body flex items-center justify-center p-4 bg-slate-100/90 flex-1 min-h-[420px]';
            const img = document.createElement('img');
            img.src = fileData.url;
            img.alt = fileData.title;
            img.className = 'max-h-[75vh] max-w-full object-contain rounded-lg shadow-sm';
            img.addEventListener('error', function() {
                img.remove();
                bodyEl.innerHTML = '<div class="text-xs text-muted text-center p-6">Gambar tidak dapat dimuat. Gunakan tombol Unduh atau Buka Tab Baru di atas.</div>';
            });
            bodyEl.appendChild(img);
        } else {
            bodyEl.className = 'submission-body flex items-center justify-center p-4 bg-slate-100/90 flex-1 min-h-[420px]';
            const isSlides = fileData.type === 'slides';
            const isWord = fileData.type === 'word';
            const card = document.createElement('div');
            card.className = 'text-center p-8 bg-white rounded-xl shadow-xs border border-line max-w-md w-full';
            card.innerHTML = `
                <div class="h-12 w-12 rounded-full bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center mx-auto mb-3">
                    ${isSlides ? '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="14" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="m8 21 4-4 4 4"/></svg>' : (isWord ? '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>' : '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>')}
                </div>
                <h4 class="text-sm font-bold text-ink mb-1">${fileData.title}</h4>
                <p class="text-xs text-muted mb-4">${isSlides ? 'Slide presentasi dapat dibuka menggunakan aplikasi PowerPoint atau Google Slides setelah diunduh.' : (isWord ? 'Dokumen Word dapat dibuka menggunakan aplikasi pengolah kata setelah diunduh.' : 'Pratinjau langsung tidak didukung oleh browser untuk tipe berkas ini. Silakan unduh untuk membukanya.')}</p>
                <a href="${fileData.downloadUrl || fileData.url}" class="button-primary text-xs py-2 px-4 inline-flex items-center gap-1.5" download>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 21h14"/></svg>
                    <span>Unduh Berkas</span>
                </a>
            `;
            bodyEl.appendChild(card);
        }

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    }

    function closeAttachmentPreview() {
        const dialog = document.getElementById('attachment-preview-dialog');
        if (dialog) {
            if (typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
            const bodyEl = document.getElementById('attachment-preview-body');
            if (bodyEl) bodyEl.innerHTML = '';
        }
    }

    (function() {
        const dialog = document.getElementById('attachment-preview-dialog');
        if (dialog) {
            dialog.addEventListener('click', function(e) {
                const rect = dialog.getBoundingClientRect();
                if (e.target === dialog && (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom)) {
                    closeAttachmentPreview();
                }
            });
            dialog.addEventListener('close', function() {
                const bodyEl = document.getElementById('attachment-preview-body');
                if (bodyEl) bodyEl.innerHTML = '';
            });
        }
        window.addEventListener('beforeunload', function() {
            closeAttachmentPreview();
        });

        const linkModal = document.getElementById('link-modal');
        if (linkModal) {
            linkModal.addEventListener('click', function(e) {
                if (e.target === linkModal) linkModal.close();
            });
            document.getElementById('modal-link-close-btn')?.addEventListener('click', () => linkModal.close());
        }

        const subFileInput = document.querySelector('[data-submission-files]');
        if (subFileInput) {
            subFileInput.addEventListener('change', function() {
                const maxBytes = 5 * 1024 * 1024;
                const oversized = [...(this.files || [])].filter(f => f.size > maxBytes);
                if (oversized.length > 0) {
                    this.value = '';
                    document.querySelectorAll('[data-active-attachments] [data-file-chip]').forEach(c => c.remove());
                    const message = 'File tidak dapat diunggah jika ukurannya lebih dari 5 MB.';
                    if (typeof window.saleNotice === 'function') {
                        window.saleNotice({
                            title: 'Ukuran File Terlalu Besar',
                            message: message,
                            confirmLabel: 'Mengerti'
                        });
                    } else {
                        alert(message);
                    }
                }
            });
        }
    })();

    async function cancelSubmissionConfirm() {
        const confirmed = typeof window.saleConfirm === 'function'
            ? await window.saleConfirm({
                title: 'Batalkan Penyerahan Tugas?',
                message: 'Tugas yang telah dikirim akan dibatalkan. Anda dapat mengunggah kembali berkas atau jawaban sebelum batas waktu berakhir.',
                confirmLabel: 'Batalkan Serahkan',
            })
            : confirm('Apakah Anda yakin ingin membatalkan pengumpulan tugas ini?');

        if (confirmed) {
            const form = document.getElementById('cancel-submission-form');
            if (form) form.submit();
        }
    }

    (() => {
        const subForm = document.querySelector('[data-submission-form]');
        if (subForm) {
            let isConfirmed = false;
            subForm.addEventListener('submit', async (e) => {
                if (isConfirmed) return;
                e.preventDefault();
                const ok = typeof window.saleConfirm === 'function'
                    ? await window.saleConfirm({
                        title: 'Kumpulkan Tugas?',
                        message: 'Pastikan seluruh berkas atau tautan jawaban yang Anda lampirkan sudah lengkap dan benar. Apakah Anda yakin ingin mengumpulkan tugas ini sekarang?',
                        confirmLabel: 'Kumpulkan Tugas',
                    })
                    : confirm('Apakah Anda yakin ingin mengumpulkan tugas ini sekarang?');
                if (ok) {
                    isConfirmed = true;
                    subForm.submit();
                }
            });
        }
    })();
</script>
@endsection
