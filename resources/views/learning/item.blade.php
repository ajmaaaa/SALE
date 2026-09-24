@extends('layouts.mahasiswa')

@section('title', $item['title'].' | SALE')
@section('header', $course['title'])

@section('content')
@php
    $currentRole = auth()->user()?->role?->name ?? (session('auth_user.role') ?? (request()->routeIs('dosen.*') ? 'dosen' : 'mahasiswa'));
    $isLecturer = in_array($currentRole, ['dosen', 'kaprodi'], true) || request()->routeIs('dosen.*');
    if ($currentRole === 'mahasiswa' || session('auth_user.role') === 'mahasiswa') {
        $isLecturer = false;
    }
    $isTask = in_array($item['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas']);
    $hasMultiQuestions = !empty($item['questions']);
    $isDedicatedQuiz = in_array($item['type'], ['kuis', 'uts', 'uas'], true);
    $isCodingMaterial = $item['type'] === 'materi' && ($item['material_mode'] ?? null) === 'coding';
    $submission = session('learning.submissions.'.$item['id']);
    $sessionGrade = session('learning.grades.'.$item['id']) ?? session('academic.item_grades.'.$item['id'].'.1');
    $studentId = auth()->id() ?? (session('auth_user.id') ?? 1);
    $dbScore = null;
    if (\Illuminate\Support\Facades\Schema::hasTable('student_assessment_scores')) {
        $dbScore = \App\Models\StudentAssessmentScore::where('assessment_id', $item['id'])
            ->where('mahasiswa_id', $studentId)
            ->first();
    }
    $hasDbGrade = $dbScore && $dbScore->score !== null;
    $hasSessionGrade = $sessionGrade !== null;
    $isGraded = $hasDbGrade || $hasSessionGrade;
    $scoreValue = $hasDbGrade ? (float)$dbScore->score : ($hasSessionGrade ? (is_array($sessionGrade) ? array_sum($sessionGrade['points'] ?? []) : (float)$sessionGrade) : null);
    $isSubmitted = !empty($submission) || $isGraded;
    $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
    $allowLate = $item['allow_late'] ?? true;
    $isLocked = !$isSubmitted && $isPast && !$allowLate;
@endphp

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.index') }}">
            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>{{ $isLecturer ? 'Course Dosen' : 'Course' }}</span>
        </a>
        <svg class="h-3.5 w-3.5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a class="font-medium text-slate-500 hover:text-brand transition" href="{{ $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.show', $course['id']) }}">
            {{ $course['code'] }}
        </a>
        <svg class="h-3.5 w-3.5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800 truncate max-w-xs sm:max-w-md" aria-current="page">
            {{ $item['module'] }}
        </span>
    </nav>

    {{-- Title & Header --}}
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="page-heading">{{ $item['title'] }}</h1>
            <p class="page-description">{{ $course['lecturer'] }}, {{ $item['module'] }}</p>
        </div>
    </header>

    {{-- Submission form wraps main questions & side actions if student --}}
    <form data-submission-form method="post" enctype="multipart/form-data" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}">
        @csrf

        @if($isTask && ($isLecturer || !$isDedicatedQuiz))
        <div class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_340px]">
        @else
        <div class="w-full space-y-6">
        @endif
            {{-- Main Column: Instructions, Multi-Question Cards, Stimulus, Attachments, Discussions --}}
            <div class="min-w-0 space-y-6">
                {{-- Interactive Coding Workbench & Lumina AI Assistant Banner --}}
                @if(!$isDedicatedQuiz && ($item['type'] === 'coding' || $isCodingMaterial))
                    <div class="rounded-xl border border-line bg-white p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded bg-brand px-2 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider">
                                    <span>✦</span> Lumina AI
                                </span>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                    Editor Monaco &amp; Terminal Linux
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-ink">Ruang Praktikum Coding &amp; Asisten AI Tersedia</h3>
                            <p class="text-xs text-muted leading-relaxed">
                                Anda dapat menguji algoritma Binary Search Tree langsung di editor kode interaktif dengan panduan konsep cerdas dari Lumina AI.
                            </p>
                        </div>
                        <a href="{{ route('mahasiswa.assignment.code', $item['id']) }}" class="button-primary text-xs py-2.5 px-4 font-bold inline-flex items-center gap-1.5 shrink-0 shadow-xs self-start sm:self-center">
                            <span>Buka Editor Kode &amp; Tanya AI</span>
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                @endif

                {{-- Instructions Card --}}
                <section class="surface p-6 sm:p-7">
                    <h2 class="section-heading">{{ $item['type'] === 'materi' ? 'Materi Pembelajaran' : 'Petunjuk Pengerjaan' }}</h2>
                    <p class="prose-content mt-4 text-sm">{{ $item['body'] }}</p>

                    @if($isDedicatedQuiz)
                        <div class="mt-6 pt-5 border-t border-line/60">
                            <div class="rounded-xl border border-line p-5">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-muted">Informasi Ujian &amp; Penilaian</p>
                                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-ink">
                                            <span>Durasi: <strong>{{ !empty($item['duration_enabled']) ? ($item['duration_minutes'] ?? 60).' menit' : 'Tanpa batas waktu' }}</strong></span>
                                            <span><strong>{{ count($item['questions'] ?? []) ?: 1 }}</strong> butir soal</span>
                                            <span>Total <strong>{{ $item['points'] ?? 100 }} poin</strong></span>
                                            @if(!empty($item['due']))
                                                <span>Tenggat: <strong>{{ \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') }}</strong></span>
                                            @endif
                                        </div>

                                        {{-- Nilai kuis di kiri bawah --}}
                                        @if(!$isLecturer && $scoreValue !== null)
                                            <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-2">
                                                <span class="text-xs text-muted font-medium">Nilai Kuis:</span>
                                                <span class="text-sm font-bold text-emerald-600 font-mono">{{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }} Poin</span>
                                                @if($submission)
                                                    <span class="text-xs text-slate-300">·</span>
                                                    <span class="text-xs font-semibold text-emerald-600">Sudah diserahkan {{ !empty($submission['time']) ? '('.$submission['time'].')' : '' }}</span>
                                                @endif
                                            </div>
                                        @elseif(!$isLecturer && $submission)
                                            <div class="mt-3.5 pt-3 border-t border-line/60 flex items-center gap-1.5">
                                                <span class="text-xs font-semibold text-emerald-600">Sudah diserahkan {{ !empty($submission['time']) ? '· '.$submission['time'] : '' }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 flex-col items-end gap-1.5 text-right self-center sm:self-start">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if($isLecturer)
                                                <a href="{{ route('dosen.gradebook', $course['id']) }}" class="button-secondary text-xs py-2.5 px-5 font-semibold">Lihat Jawaban Mahasiswa</a>
                                            @elseif($isGraded || $submission)
                                                <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-secondary bg-white text-xs py-2.5 px-5 font-semibold">Lihat Jawaban</a>
                                            @elseif($isLocked)
                                                <button type="button" disabled class="button-secondary text-xs py-2.5 px-5 font-semibold opacity-60">Kuis Ditutup</button>
                                            @else
                                                <a href="{{ route('mahasiswa.quiz.room', [$course['id'], $item['id']]) }}" class="button-primary text-xs py-2.5 px-5 font-semibold">Mulai Kerjakan Kuis</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php
                        $hasAttachments = !empty($item['question_image']) || !empty($item['attachments']) || !empty($item['link']);
                    @endphp
                    @if($hasAttachments)
                        <h3 class="mt-7 text-sm font-bold text-ink">Lampiran</h3>
                        <div class="mt-3 flex flex-wrap items-start gap-3">
                            {{-- Lampiran Gambar Soal / Materi --}}
                            @if(!empty($item['question_image']))
                                @php
                                    $imageMeta = \App\Support\LearningPreview::fileMeta($item['question_image']);
                                    $imageAlt = $item['image_alt'] ?? 'Gambar pendukung';
                                    $imageName = !empty($item['image_alt']) ? $item['image_alt'] : ($imageMeta['name'] ?? 'Gambar pendukung');
                                    $imgUrl = route('preview.file', $item['question_image']);
                                    $imgDownloadUrl = route('preview.file', ['file' => $item['question_image'], 'download' => 1]);
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
                                    $fileMeta = \App\Support\LearningPreview::fileMeta($file);
                                    $fileMime = $fileMeta['mime'] ?? '';
                                    $fileName = $fileMeta['name'] ?? 'Berkas lampiran';
                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'file');
                                    $isPdf = $fileMime === 'application/pdf' || $fileExt === 'pdf';
                                    $isImage = str_starts_with($fileMime, 'image/') || in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'], true);
                                    $isVideo = str_starts_with($fileMime, 'video/') || in_array($fileExt, ['mp4', 'webm', 'ogg'], true);
                                    $isSlides = in_array($fileExt, ['ppt', 'pptx'], true);
                                    $isWord = in_array($fileExt, ['doc', 'docx'], true);

                                    $previewType = $isVideo ? 'video' : ($isPdf ? 'pdf' : ($isImage ? 'image' : ($isSlides ? 'slides' : ($isWord ? 'word' : 'file'))));
                                    $fileUrl = route('preview.file', ['file' => $file, 'inline' => ($isPdf || $isVideo) ? 1 : null]);
                                    $fileDownloadUrl = route('preview.file', ['file' => $file, 'download' => 1]);
                                @endphp
                                <div class="w-44 shrink-0 overflow-hidden rounded-lg border border-line/70 bg-white shadow-2xs hover:border-brand/40 transition" style="contain: paint;">
                                    @if($isPdf)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'pdf', ext: 'PDF' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-gradient-to-b from-rose-50/70 to-slate-50 cursor-pointer group hover:from-rose-50 transition" title="Buka pratinjau {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600 shadow-2xs group-hover:scale-105 transition">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <path d="M9 13h6"/>
                                                    <path d="M9 17h4"/>
                                                </svg>
                                            </div>
                                            <span class="rounded bg-rose-100/80 px-2 py-0.5 text-[10px] font-bold tracking-wider text-rose-700 uppercase">Dokumen PDF</span>
                                        </a>
                                    @elseif($isVideo)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'video', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-gradient-to-b from-slate-900 to-[#172633] text-white cursor-pointer group hover:opacity-95 transition" title="Putar video {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand text-white shadow-md group-hover:scale-110 transition">
                                                <svg class="h-5 w-5 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </div>
                                            <span class="rounded bg-white/20 px-2 py-0.5 text-[10px] font-bold tracking-wider text-slate-100 uppercase">Video {{ strtoupper($fileExt) }}</span>
                                        </a>
                                    @elseif($isSlides)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'slides', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-gradient-to-b from-amber-50/80 to-slate-50 cursor-pointer group hover:from-amber-50 transition" title="Slide presentasi {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 shadow-2xs group-hover:scale-105 transition">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <rect width="18" height="14" x="3" y="3" rx="2"/>
                                                    <path d="M3 9h18"/>
                                                    <path d="m8 21 4-4 4 4"/>
                                                </svg>
                                            </div>
                                            <span class="rounded bg-amber-100/90 px-2 py-0.5 text-[10px] font-bold tracking-wider text-amber-800 uppercase">Slide {{ strtoupper($fileExt) }}</span>
                                        </a>
                                    @elseif($isWord)
                                        <a href="{{ $fileUrl }}" onclick="openAttachmentPreview(event, { title: '{{ addslashes($fileName) }}', url: '{{ $fileUrl }}', downloadUrl: '{{ $fileDownloadUrl }}', type: 'word', ext: '{{ strtoupper($fileExt) }}' })" class="flex aspect-video w-full flex-col items-center justify-center gap-1.5 border-b border-line bg-gradient-to-b from-blue-50/70 to-slate-50 cursor-pointer group hover:from-blue-50 transition" title="Dokumen {{ $fileName }}">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 shadow-2xs group-hover:scale-105 transition">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <path d="M8 13h8"/>
                                                    <path d="M8 17h6"/>
                                                </svg>
                                            </div>
                                            <span class="rounded bg-blue-100/80 px-2 py-0.5 text-[10px] font-bold tracking-wider text-blue-700 uppercase">Dokumen Word</span>
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
                            @if(!empty($item['link']))
                                <div class="w-44 shrink-0 overflow-hidden rounded-lg border border-line/70 bg-white shadow-2xs hover:border-brand/40 transition">
                                    <a href="{{ $item['link'] }}" target="_blank" rel="noopener noreferrer" class="flex aspect-video w-full flex-col items-center justify-center gap-1 border-b border-line bg-slate-50 text-muted hover:text-brand transition group" title="{{ $item['link'] }}">
                                        <svg class="h-6 w-6 text-muted group-hover:text-brand transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                        </svg>
                                        <span class="text-[10px] font-medium text-muted group-hover:text-brand transition">Tautan Luar</span>
                                    </a>
                                    <div class="flex h-9 min-w-0 items-center justify-between gap-1.5 px-2.5">
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                            </svg>
                                            <a href="{{ $item['link'] }}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1 truncate text-xs font-medium text-ink hover:underline" title="{{ $item['link'] }}">
                                                {{ preg_replace('#^https?://#', '', $item['link']) }}
                                            </a>
                                        </div>
                                        <a href="{{ $item['link'] }}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-muted hover:text-ink" title="Buka tautan">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                </section>

                {{-- Task Questions for Regular Assignments (Non-CBT Quiz) --}}
                @if(!$isLecturer && $isTask && !$isDedicatedQuiz && $hasMultiQuestions)
                    <div class="space-y-5">
                        @foreach($item['questions'] as $qIdx => $q)
                            <section class="surface p-6 sm:p-7 space-y-4">
                                <div class="flex items-center justify-between border-b border-line/50 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-brand-soft text-brand text-xs font-bold px-2 py-0.5">Soal {{ $qIdx + 1 }}</span>
                                        <span class="text-xs font-semibold text-muted uppercase">{{ $q['type'] }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-ink">{{ $q['points'] ?? 10 }} Poin</span>
                                </div>
                                <p class="text-sm text-ink leading-relaxed">{{ $q['prompt'] }}</p>

                                @if($q['type'] === 'benar_salah')
                                    <div class="flex items-center gap-4 pt-1">
                                        <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar" @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                            <span class="font-medium text-ink">Benar</span>
                                        </label>
                                        <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs cursor-pointer hover:bg-slate-100 transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah" @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                            <span class="font-medium text-ink">Salah</span>
                                        </label>
                                    </div>
                                @elseif($q['type'] === 'mencocokkan')
                                    @php
                                        $pairLines = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                        $pairs = [];
                                        foreach($pairLines as $pLine) {
                                            if (str_contains($pLine, ' = ')) {
                                                [$left, $right] = explode(' = ', $pLine, 2);
                                                $pairs[] = ['left' => trim($left), 'right' => trim($right)];
                                            } elseif (str_contains($pLine, '=')) {
                                                [$left, $right] = explode('=', $pLine, 2);
                                                $pairs[] = ['left' => trim($left), 'right' => trim($right)];
                                            } else {
                                                $pairs[] = ['left' => $pLine, 'right' => $pLine];
                                            }
                                        }
                                        $allRights = array_column($pairs, 'right');
                                        // Randomize target order deterministically per student so that:
                                        // 1) Different students see different positions (beda orang beda susunan)
                                        // 2) The choices in the dropdown are NOT in the same line/order as the premises (tidak sebaris)
                                        $userSeed = auth()->id() ?? (session('auth_user.id') ?? (session('auth_user.number') ? crc32((string) session('auth_user.number')) : 1));
                                        $seed = (int) ($item['id'] ?? 1) * 37 + (int) $userSeed * 19 + ($qIdx + 1) * 11;
                                        mt_srand($seed);
                                        $shuffledRights = $allRights;
                                        shuffle($shuffledRights);
                                        mt_srand();
                                    @endphp
                                    <div class="space-y-3 pt-1">
                                        @foreach($pairs as $pIdx => $pair)
                                            @php
                                                $isLeftImg = str_starts_with($pair['left'], 'http') || str_starts_with($pair['left'], 'data:image') || str_starts_with($pair['left'], '/');
                                            @endphp
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-lg bg-canvas border border-line/50">
                                                <div class="min-w-0 flex-1">
                                                    <span class="text-[11px] font-bold text-muted block mb-1">Premis {{ $pIdx + 1 }}</span>
                                                    @if($isLeftImg)
                                                        <img src="{{ $pair['left'] }}" alt="Premis {{ $pIdx + 1 }}" class="h-14 max-w-xs object-contain rounded border border-line/60 bg-white p-1">
                                                    @else
                                                        <span class="text-xs font-medium text-ink">{{ $pair['left'] }}</span>
                                                    @endif
                                                </div>
                                                <select name="question_answers[{{ $qIdx }}][matching][{{ $pIdx }}]" class="field text-xs sm:w-64" @disabled($submission || $isLocked)>
                                                    <option value="">-- Pilih Pasangan --</option>
                                                    @foreach($shuffledRights as $target)
                                                        <option value="{{ $target }}" @selected(old("question_answers.$qIdx.matching.$pIdx", $submission['question_answers'][$qIdx]['matching'][$pIdx] ?? '') === $target)>
                                                            {{ str_starts_with($target, 'data:image') ? 'Gambar Pasangan' : $target }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(in_array($q['type'], ['pilihan', 'kompleks']))
                                    @php
                                        $options = array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($option) => $option !== '');
                                        $isMultiple = $q['type'] === 'kompleks';
                                    @endphp
                                    <div class="space-y-2 pt-1">
                                        @foreach($options as $option)
                                            <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-xs {{ $submission || $isLocked ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                                <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}" name="question_answers[{{ $qIdx }}][choices][]" value="{{ $option }}"
                                                    @checked(in_array($option, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))
                                                    @disabled($submission || $isLocked)>
                                                <span class="text-ink">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <textarea name="question_answers[{{ $qIdx }}][text]" rows="4" class="field text-xs" @readonly($submission || $isLocked) placeholder="Tulis jawaban di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? '') }}</textarea>
                                @endif
                            </section>
                        @endforeach
                    </div>
                @endif

                {{-- Single Objective Questions (Only Choices in Center, No Duplicate Button) --}}
                @if(!$isLecturer && $isTask && !$hasMultiQuestions && in_array($item['question_type'], ['pilihan', 'kompleks', 'benar_salah']))
                    <section class="surface p-6 sm:p-7 space-y-4">
                        <h2 class="section-heading">Lembar Jawaban Soal</h2>
                        @if(in_array($item['question_type'], ['pilihan', 'kompleks']))
                            <fieldset class="space-y-2">
                                <legend class="form-label text-xs font-bold">{{ $item['question_type'] === 'kompleks' ? 'Pilih semua jawaban yang benar' : 'Pilih satu jawaban' }}</legend>
                                <div class="space-y-2">
                                    @foreach(array_filter(array_map('trim', explode("\n", $item['options'] ?? '')), fn($option) => $option !== '') as $option)
                                        <label class="flex items-center gap-3 rounded-lg bg-canvas p-3 text-xs {{ $submission || $isLocked ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                            <input type="{{ $item['question_type'] === 'kompleks' ? 'checkbox' : 'radio' }}" name="choices[]" value="{{ $option }}"
                                                @checked(in_array($option, old('choices', $submission['choices'] ?? [])))
                                                @disabled($submission || $isLocked)>
                                            <span class="text-ink">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @elseif($item['question_type'] === 'benar_salah')
                            <fieldset class="space-y-2">
                                <legend class="form-label text-xs font-bold">Pilih Benar atau Salah</legend>
                                <div class="flex items-center gap-4 pt-1">
                                    <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs {{ $submission || $isLocked ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                        <input type="radio" name="boolean_choice" value="Benar" @checked(old('boolean_choice', $submission['boolean_choice'] ?? '') === 'Benar') @disabled($submission || $isLocked)>
                                        <span class="font-medium text-ink">Benar</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 rounded-lg bg-canvas px-4 py-3 text-xs {{ $submission || $isLocked ? 'cursor-default opacity-85' : 'cursor-pointer hover:bg-slate-100' }} transition">
                                        <input type="radio" name="boolean_choice" value="Salah" @checked(old('boolean_choice', $submission['boolean_choice'] ?? '') === 'Salah') @disabled($submission || $isLocked)>
                                        <span class="font-medium text-ink">Salah</span>
                                    </label>
                                </div>
                            </fieldset>
                        @endif
                    </section>
                @endif

            </div>

            {{-- Right Aside Column: Status & Submission (Student) or Content Control (Lecturer) --}}
            @if($isTask)
                @if($isLecturer)
                    {{-- Lecturer Management Panel --}}
                    <aside class="rounded-xl bg-white p-5 shadow-sm space-y-5 h-fit xl:sticky xl:top-24 border border-line/60">
                        <div class="flex items-center justify-between border-b border-line/60 pb-3">
                            <h2 class="text-sm font-bold text-ink">Pengelolaan Pengampu</h2>
                            <span class="rounded bg-brand-soft px-2 py-0.5 text-[11px] font-bold text-brand">Dosen</span>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between text-muted">
                                <span>Total Bobot:</span>
                                <span class="font-bold text-ink">{{ $item['points'] ?? 100 }} Poin</span>
                            </div>
                            <div class="flex items-center justify-between text-muted">
                                <span>Tenggat Waktu:</span>
                                <span class="font-medium text-ink">{{ $item['due'] ? \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tanpa tenggat' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted">
                                <span>Pengumpulan Terlambat:</span>
                                <span class="font-medium text-ink">{{ !empty($item['allow_late']) ? 'Diizinkan' : 'Ditolak / Dikunci' }}</span>
                            </div>
                            @if(!empty($item['component']))
                                <div class="flex items-center justify-between text-muted">
                                    <span>Komponen Evaluasi:</span>
                                    <span class="font-semibold text-ink uppercase">{{ $item['component'] }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-lg bg-canvas p-3 text-xs space-y-1">
                            <p class="font-semibold text-ink">Penilaian Mahasiswa</p>
                            <p class="text-muted text-[11px]">Buka buku nilai untuk mengevaluasi jawaban yang masuk dan menginputkan skor CPMK.</p>
                        </div>

                        <div class="space-y-2 pt-2">
                            <a href="{{ route('dosen.gradebook', $course['id']) }}" class="button-primary w-full py-2.5 text-xs font-bold text-center block">
                                Lihat &amp; Nilai Jawaban Mahasiswa
                            </a>
                            <a href="{{ route('dosen.item.create', $course['id']) }}" class="button-secondary w-full py-2 text-xs font-semibold text-center block">
                                + Tambah Konten / Soal Baru
                            </a>
                            <a href="{{ route('dosen.course.show', $course['id']) }}" class="quiet-link text-xs text-center block pt-1">
                                Kembali ke Halaman Course
                            </a>
                        </div>
                    </aside>
                @else
                    @if(!$isDedicatedQuiz)
                        {{-- Student Submission Panel (Google Classroom Style for Tugas & Coding) --}}
                        <aside class="rounded-xl bg-white p-5 shadow-sm space-y-4 h-fit xl:sticky xl:top-24 border border-line/60">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-bold text-ink">Tugas Anda</h2>
                                @if($isGraded)
                                    <span class="whitespace-nowrap text-sm font-bold text-emerald-600">{{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }}</span>
                                @elseif($submission)
                                    <span class="text-xs font-semibold text-emerald-600">Diserahkan</span>
                                @elseif($isPast)
                                    <span class="text-xs font-semibold text-rose-600">Terlambat</span>
                                @else
                                    <span class="text-xs font-semibold text-rose-600">Belum diserahkan</span>
                                @endif
                            </div>

                            <div class="text-xs text-muted flex items-center gap-2">
                                <span>{{ $item['points'] ?? 100 }} Poin</span>
                                <span>{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M, H:i') : 'Tanpa tenggat' }}</span>
                            </div>

                            {{-- Attached Work Items (Existing or New) --}}
                            <div class="space-y-2" data-attachment-container>
                                {{-- Saved files --}}
                                @if(!empty($submission['files']))
                                    @foreach($submission['files'] as $sf)
                                        <div class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-canvas border border-line/40">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <span class="text-ink truncate font-medium">{{ $sf['name'] }}</span>
                                            </div>
                                            <input type="hidden" name="keep_files[]" value="{{ $sf['id'] }}">
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
                                    <div class="text-xs p-2.5 rounded-lg bg-canvas border border-line/40">
                                        <span class="text-muted block text-[11px] font-semibold mb-1">Catatan / Jawaban:</span>
                                        <p class="text-ink line-clamp-3">{{ $submission['answer'] }}</p>
                                    </div>
                                @endif

                                {{-- Dynamic list for new additions --}}
                                <div data-active-attachments class="space-y-2"></div>
                            </div>

                            {{-- Action Button: + Tambah atau buat --}}
                            @if(!$isLocked && !$submission)
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
                            @endif

                            {{-- Hidden inputs for file/link/answer --}}
                            <input type="file" name="files[]" multiple data-submission-files class="hidden" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.webp">
                            <input type="hidden" name="link" data-submission-link value="{{ old('link') }}">

                            {{-- Optional text answer area --}}
                            @if(!$submission && !$isLocked)
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
                                @if($isLocked)
                                    <button type="button" disabled class="button-secondary w-full py-2.5 text-xs font-semibold opacity-60 cursor-not-allowed">
                                        Pengumpulan Ditutup
                                    </button>
                                @elseif($submission)
                                    <button type="button" disabled class="button-secondary bg-slate-100 text-slate-600 w-full py-2.5 text-xs font-semibold cursor-default">
                                        {{ $isGraded ? 'Sudah Dinilai' : 'Sudah Diserahkan' }}
                                    </button>
                                @else
                                    <button type="submit" class="button-primary w-full py-2.5 text-xs font-bold">
                                        Kumpulkan Tugas
                                    </button>
                                @endif
                            </div>
                        </aside>
                    @endif
                @endif
            @endif
        </div>
    </form>

    {{-- Modal Dialog Tambah Link (Google Classroom Style) --}}
    <dialog id="link-modal" class="rounded-xl border border-line/60 bg-white p-6 shadow-xl backdrop:bg-ink/40 max-w-md w-full">
        <h3 class="text-sm font-bold text-ink mb-1">Tambahkan Link</h3>
        <p class="text-xs text-muted mb-4">Tempelkan tautan URL repositori, Google Drive, atau dokumen referensi tugas.</p>
        <div class="space-y-1.5">
            <label for="modal-link-input" class="form-label text-xs">Link <span class="text-danger">*</span></label>
            <input type="url" id="modal-link-input" class="field text-xs py-2" placeholder="https://example.com/tugas">
            <p id="modal-link-error" class="text-[11px] text-danger hidden">Tautan harus berupa URL valid yang diawali http:// atau https://</p>
        </div>
        <div class="mt-5 flex items-center justify-end gap-2.5">
            <button type="button" id="modal-link-cancel" class="button-secondary text-xs py-1.5 px-3">Batal</button>
            <button type="button" id="modal-link-submit" class="button-primary text-xs py-1.5 px-3 font-semibold">Tambahkan Link</button>
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
        downloadEl.href = fileData.downloadUrl || fileData.url;
        openEl.href = fileData.url;

        bodyEl.innerHTML = '';

        if (fileData.type === 'video') {
            const vid = document.createElement('video');
            vid.src = fileData.url;
            vid.controls = true;
            vid.autoplay = true;
            vid.className = 'max-h-[75vh] max-w-full rounded-lg shadow-md bg-black';
            bodyEl.appendChild(vid);
        } else if (fileData.type === 'pdf') {
            const frame = document.createElement('iframe');
            frame.src = fileData.url;
            frame.title = `Pratinjau ${fileData.title}`;
            frame.className = 'w-full h-full min-h-[520px] border-0 rounded-lg bg-white shadow-sm';
            bodyEl.appendChild(frame);
        } else if (fileData.type === 'image') {
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
            const isSlides = fileData.type === 'slides';
            const isWord = fileData.type === 'word';
            const card = document.createElement('div');
            card.className = 'text-center p-8 bg-white rounded-xl shadow-xs border border-line max-w-md w-full';
            card.innerHTML = `
                <div class="h-12 w-12 rounded-full ${isSlides ? 'bg-amber-100 text-amber-600' : (isWord ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-muted')} flex items-center justify-center mx-auto mb-3">
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
    })();
</script>
@endsection
