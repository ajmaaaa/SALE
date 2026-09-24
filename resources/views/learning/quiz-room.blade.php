<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $item['title'] }} - Ruang Ujian | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .match-canvas-container {
            position: relative;
            user-select: none;
        }
        .match-dot {
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .match-dot:hover {
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.25);
        }
        .match-dot.selected {
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.45);
        }
        .connection-line {
            transition: stroke 0.2s ease;
        }
        dialog[open] {
            position: fixed !important;
            inset: 0 !important;
            margin: auto !important;
        }
    </style>
</head>
<body class="h-screen overflow-hidden bg-slate-100/80 font-sans text-slate-800 antialiased flex flex-col selection:bg-brand selection:text-white">

    @php
        $questions = $item['questions'] ?? [];
        if (empty($questions)) {
            $questions = \App\Support\LearningPreview::defaultQuizQuestions();
        }
        $totalQuestions = count($questions);
        $totalPoints = array_sum(array_column($questions, 'points'));
        $durationMinutes = !empty($item['duration_enabled']) ? ($item['duration_minutes'] ?? 60) : null;
        $submission = session('learning.submissions.'.$item['id']);
        $hasCompleted = !empty($isCompleted) || !empty($submission);
    @endphp

    @if($hasCompleted)
        {{-- ================================================================= --}}
        {{-- LAYAR EVALUASI PURNA-PENGUMPULAN KUIS: NILAI & PEMERIKSAAN JAWABAN --}}
        {{-- ================================================================= --}}
        @php
            $evaluations = [];
            $totalCorrect = 0;
            $totalWrong = 0;
            $totalPending = 0;
            $computedScore = 0;

            foreach ($questions as $qIdx => $q) {
                $qType = $q['type'] ?? 'pilihan';
                $qPoints = (float) ($q['points'] ?? 25);
                $ans = $submission['question_answers'][$qIdx] ?? [];
                if (empty($ans) && $totalQuestions === 1) {
                    $ans = [
                        'choices' => $submission['choices'] ?? [],
                        'boolean_choice' => $submission['boolean_choice'] ?? null,
                        'matching' => $submission['matching'] ?? [],
                        'text' => $submission['answer'] ?? null,
                    ];
                }

                $status = 'wrong';
                $earned = 0;
                $pairResults = [];

                if ($qType === 'pilihan') {
                    $options = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn ($v) => $v !== ''));
                    $correct = $q['correct_answer'] ?? (str_contains($q['prompt'] ?? '', 'imbalance') ? 'F1-Score dan ROC-AUC' : (str_contains($q['prompt'] ?? '', 'BST') ? 'Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan' : ($options[0] ?? '')));
                    $userChoice = $ans['choices'][0] ?? null;
                    if ($userChoice !== null && $userChoice === $correct) {
                        $status = 'correct';
                        $earned = $qPoints;
                        $totalCorrect++;
                    } else {
                        $status = 'wrong';
                        $totalWrong++;
                    }
                } elseif ($qType === 'benar_salah') {
                    $correct = $q['correct_answer'] ?? (str_contains($q['prompt'] ?? '', '95%') ? 'Salah' : 'Benar');
                    $userChoice = $ans['boolean_choice'] ?? null;
                    if ($userChoice !== null && $userChoice === $correct) {
                        $status = 'correct';
                        $earned = $qPoints;
                        $totalCorrect++;
                    } else {
                        $status = 'wrong';
                        $totalWrong++;
                    }
                } elseif ($qType === 'kompleks') {
                    $options = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn ($v) => $v !== ''));
                    $correct = $q['correct_answers'] ?? ['Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)', 'Kompleksitas pencarian rata-rata pada balanced BST adalah O(log n)'];
                    $userChoices = $ans['choices'] ?? [];
                    $uSorted = $userChoices;
                    $cSorted = $correct;
                    sort($uSorted);
                    sort($cSorted);
                    if (! empty($uSorted) && $uSorted === $cSorted) {
                        $status = 'correct';
                        $earned = $qPoints;
                        $totalCorrect++;
                    } else {
                        $status = 'wrong';
                        $totalWrong++;
                    }
                } elseif ($qType === 'mencocokkan') {
                    $matching = $ans['matching'] ?? [];
                    $pairs = array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn ($v) => str_contains($v, '='));
                    $totalPairs = count($pairs);
                    $matchedCount = 0;
                    foreach (array_values($pairs) as $pIdx => $pairStr) {
                        [$term, $def] = array_map('trim', explode('=', $pairStr, 2));
                        $uMatch = $matching[$pIdx] ?? null;
                        $isMatchCorrect = ($uMatch !== null && $uMatch === $def);
                        if ($isMatchCorrect) $matchedCount++;
                        $pairResults[] = [
                            'term' => $term,
                            'user' => $uMatch,
                            'expected' => $def,
                            'is_correct' => $isMatchCorrect,
                        ];
                    }
                    if ($totalPairs > 0 && $matchedCount === $totalPairs) {
                        $status = 'correct';
                        $earned = $qPoints;
                        $totalCorrect++;
                    } elseif ($matchedCount > 0) {
                        $status = 'partial';
                        $earned = ($matchedCount / $totalPairs) * $qPoints;
                        $totalWrong++;
                    } else {
                        $status = 'wrong';
                        $totalWrong++;
                    }
                } else {
                    $status = 'pending';
                    $totalPending++;
                }

                $computedScore += $earned;
                $evaluations[$qIdx] = compact('status', 'earned', 'pairResults', 'q');
            }

            $finalScore = $scoreValue !== null ? $scoreValue : $computedScore;
        @endphp

        <div class="h-screen w-screen flex flex-col bg-slate-100/90 overflow-y-auto">
            {{-- Top Header Minimalis --}}
            <header class="h-14 shrink-0 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                        <span>Kembali ke Course</span>
                    </a>
                    <span class="text-slate-300">|</span>
                    <span class="text-xs font-bold text-slate-700 truncate">{{ $course['code'] }} - {{ $item['title'] }}</span>
                </div>
                <span class="status font-semibold text-slate-700 bg-slate-100 border border-slate-200">
                    <svg class="h-3.5 w-3.5 text-slate-500 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Kuis Terkunci (Telah Selesai)
                </span>
            </header>

            {{-- Main Content: Ringkasan Nilai & Pemeriksaan Jawaban Soal --}}
            <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl mx-auto space-y-7">

                    {{-- Card Ringkasan & Tanda Terima Kuis --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-7 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 border-b border-slate-100 pb-5">
                                <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ $item['title'] }}</h1>
                                <p class="text-xs text-slate-500 mt-1">{{ $course['title'] }} ({{ $course['code'] }})</p>
                            </div>

                            {{-- Nilai Kuis (Tampil di Kanan) --}}
                            <div class="flex sm:flex-col items-center sm:items-end justify-between bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-200">
                                <span class="text-xs font-medium text-slate-500">Nilai Perolehan Kuis:</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-3xl font-extrabold text-slate-900 font-mono tracking-tight">{{ number_format($finalScore, 0) }}</span>
                                    <span class="text-sm font-semibold text-slate-400">/ {{ $totalPoints }} Poin</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1.5 text-xs text-slate-600">
                                    <span class="font-medium text-slate-700">{{ $totalCorrect }} Benar</span>
                                    @if($totalWrong > 0)
                                        <span class="text-slate-300">·</span>
                                        <span class="font-medium text-slate-700">{{ $totalWrong }} Salah</span>
                                    @endif
                                    @if($totalPending > 0)
                                        <span class="text-slate-300">·</span>
                                        <span class="font-medium text-slate-700">{{ $totalPending }} Uraian</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Metadata Bukti Tanda Terima --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 font-medium block">Waktu Pengumpulan</span>
                                <span class="font-bold text-slate-800 font-mono mt-0.5 block">{{ $submission['time'] ?? now()->format('d M Y, H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-medium block">Mahasiswa</span>
                                <span class="font-bold text-slate-800 truncate mt-0.5 block">{{ session('auth_user.name', 'Ahmad Maulana') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-medium block">NIM / Identitas</span>
                                <span class="font-bold text-slate-800 font-mono mt-0.5 block">{{ session('auth_user.number', '230101001') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-medium block">Jumlah Butir Soal</span>
                                <span class="font-bold text-slate-800 mt-0.5 block">{{ $totalQuestions }} Butir Soal</span>
                            </div>
                        </div>

                        {{-- Tombol Navigasi --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
                            <p class="text-xs text-slate-500">Periksa hasil koreksi jawaban pada daftar butir soal di bawah.</p>
                            <div class="flex items-center gap-2.5">
                                <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="button-secondary text-xs py-2 px-4 font-semibold">
                                    Lihat Rincian Tugas
                                </a>
                                <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="button-primary text-xs py-2 px-4 font-bold shadow-xs">
                                    ← Kembali ke Course
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Daftar Rincian Soal: Hasil Jawaban Mahasiswa vs Kunci Jawaban Benar --}}
                    <div class="space-y-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">Hasil Pemeriksaan Lembar Jawaban</h2>
                                <p class="text-xs text-slate-500 mt-0.5">Sistem telah mencocokkan jawaban yang Anda serahkan dengan kunci jawaban evaluasi.</p>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">{{ $totalQuestions }} Butir Soal</span>
                        </div>

                        <div class="space-y-4">
                            @foreach($questions as $qIdx => $q)
                                @php
                                    $eval = $evaluations[$qIdx] ?? [];
                                    $status = $eval['status'] ?? 'wrong';
                                    $qType = $q['type'] ?? 'pilihan';
                                    $ans = $submission['question_answers'][$qIdx] ?? [];
                                    if (empty($ans) && $totalQuestions === 1) {
                                        $ans = [
                                            'choices' => $submission['choices'] ?? [],
                                            'boolean_choice' => $submission['boolean_choice'] ?? null,
                                            'matching' => $submission['matching'] ?? [],
                                            'text' => $submission['answer'] ?? null,
                                        ];
                                    }
                                @endphp
                                <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 sm:p-6 space-y-4">
                                    {{-- Header Soal --}}
                                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <span class="h-6 w-6 rounded bg-slate-100 text-slate-800 text-xs font-bold font-mono flex items-center justify-center">
                                                {{ $qIdx + 1 }}
                                            </span>
                                            <div>
                                                <span class="text-xs font-bold text-slate-800">
                                                    @if($qType === 'pilihan') Pilihan Ganda
                                                    @elseif($qType === 'kompleks') Pilihan Ganda Kompleks
                                                    @elseif($qType === 'benar_salah') Benar / Salah
                                                    @elseif($qType === 'mencocokkan') Menjodohkan Pasangan
                                                    @elseif($qType === 'coding') Praktikum Coding
                                                    @else Uraian / Essay
                                                    @endif
                                                </span>
                                                <span class="text-slate-300 mx-1">·</span>
                                                <span class="text-xs text-slate-500">{{ $q['points'] }} Poin</span>
                                                @if(!empty($q['cpmk']))
                                                    <span class="text-slate-300 mx-1">·</span>
                                                    <span class="text-xs font-medium text-slate-500">{{ $q['cpmk'] }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Status Benar/Salah (Teks Bersih Tanpa Label Pudar) --}}
                                        <div>
                                            @if($status === 'correct')
                                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                    <span>Benar (+{{ $q['points'] }} Poin)</span>
                                                </span>
                                            @elseif($status === 'partial')
                                                <span class="text-xs font-bold text-slate-700">
                                                    <span>Sebagian Tepat (+{{ number_format($eval['earned'], 1) }} Poin)</span>
                                                </span>
                                            @elseif($status === 'pending')
                                                <span class="text-xs font-medium text-slate-500 flex items-center gap-1.5">
                                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                    <span>Tersimpan (Dinilai Pengampu)</span>
                                                </span>
                                            @else
                                                <span class="text-xs font-bold text-rose-700 flex items-center gap-1.5">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                    <span>Salah (0 Poin)</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Pertanyaan --}}
                                    <div class="text-sm font-medium text-slate-800 leading-relaxed whitespace-pre-line">
                                        {{ $q['prompt'] }}
                                    </div>

                                    @if(!empty($q['image']))
                                        <div class="pt-1">
                                            <div class="rounded-xl border border-slate-200 p-2 bg-slate-50 max-w-md">
                                                <img src="{{ route('preview.file', $q['image']) }}" alt="{{ $q['alt'] ?? 'Gambar soal' }}" class="max-h-52 mx-auto object-contain">
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Opsi Pilihan Ganda & Kompleks --}}
                                    @if(in_array($qType, ['pilihan', 'kompleks']))
                                        @php
                                            $options = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($v) => $v !== ''));
                                            $correctList = $qType === 'kompleks'
                                                ? ($q['correct_answers'] ?? ['Traversal In-order pada BST akan menghasilkan urutan data terurut menaik (ascending)', 'Kompleksitas pencarian rata-rata pada balanced BST adalah O(log n)'])
                                                : [$q['correct_answer'] ?? (str_contains($q['prompt'] ?? '', 'imbalance') ? 'F1-Score dan ROC-AUC' : (str_contains($q['prompt'] ?? '', 'BST') ? 'Simpul 12 berada di subtree kiri dan simpul 18 berada di subtree kanan' : ($options[0] ?? '')))];
                                            $userChoices = $ans['choices'] ?? [];
                                        @endphp
                                        <div class="space-y-2 pt-1">
                                            @foreach($options as $opt)
                                                @php
                                                    $isUserPicked = in_array($opt, $userChoices, true);
                                                    $isOptCorrect = in_array($opt, $correctList, true);
                                                @endphp
                                                <div class="flex items-center justify-between gap-3 p-3 rounded-lg border text-xs {{ $isUserPicked ? 'border-slate-300 bg-slate-50/80 font-medium text-slate-900' : ($isOptCorrect ? 'border-slate-300 bg-white text-slate-800' : 'border-slate-200 bg-white text-slate-600') }}">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <span class="h-4 w-4 shrink-0 rounded-full flex items-center justify-center text-[10px] {{ $isUserPicked && $isOptCorrect ? 'bg-emerald-600 text-white font-bold' : ($isUserPicked && !$isOptCorrect ? 'bg-rose-600 text-white font-bold' : ($isOptCorrect ? 'border border-emerald-500 text-emerald-700 font-bold' : 'border border-slate-300 text-slate-400')) }}">
                                                            @if($isUserPicked && $isOptCorrect) ✓
                                                            @elseif($isUserPicked && !$isOptCorrect) ✕
                                                            @elseif($isOptCorrect) ✓
                                                            @endif
                                                        </span>
                                                        <span class="break-words leading-relaxed">{{ $opt }}</span>
                                                    </div>
                                                    <div class="shrink-0 flex items-center gap-1.5">
                                                        @if($isUserPicked && $isOptCorrect)
                                                            <span class="text-xs font-semibold text-emerald-700">✓ Jawaban Anda (Benar)</span>
                                                        @elseif($isUserPicked && !$isOptCorrect)
                                                            <span class="text-xs font-semibold text-rose-700">✕ Jawaban Anda (Salah)</span>
                                                        @elseif($isOptCorrect)
                                                            <span class="text-xs font-medium text-emerald-700">✓ Kunci Jawaban Benar</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- Opsi Benar / Salah --}}
                                    @elseif($qType === 'benar_salah')
                                        @php
                                            $correctChoice = $q['correct_answer'] ?? (str_contains($q['prompt'] ?? '', '95%') ? 'Salah' : 'Benar');
                                            $userChoice = $ans['boolean_choice'] ?? null;
                                        @endphp
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                            @foreach(['Benar', 'Salah'] as $opt)
                                                @php
                                                    $isUserPicked = ($userChoice === $opt);
                                                    $isOptCorrect = ($correctChoice === $opt);
                                                @endphp
                                                <div class="flex items-center justify-between gap-3 p-3 rounded-lg border text-xs {{ $isUserPicked ? 'border-slate-300 bg-slate-50/80 font-medium text-slate-900' : ($isOptCorrect ? 'border-slate-300 bg-white text-slate-800' : 'border-slate-200 bg-white text-slate-600') }}">
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="h-4 w-4 shrink-0 rounded-full flex items-center justify-center text-[10px] {{ $isUserPicked && $isOptCorrect ? 'bg-emerald-600 text-white font-bold' : ($isUserPicked && !$isOptCorrect ? 'bg-rose-600 text-white font-bold' : ($isOptCorrect ? 'border border-emerald-500 text-emerald-700 font-bold' : 'border border-slate-300 text-slate-400')) }}">
                                                            @if($isUserPicked && $isOptCorrect) ✓
                                                            @elseif($isUserPicked && !$isOptCorrect) ✕
                                                            @elseif($isOptCorrect) ✓
                                                            @endif
                                                        </span>
                                                        <span class="font-bold">{{ $opt }}</span>
                                                    </div>
                                                    <div class="shrink-0 flex items-center gap-1.5">
                                                        @if($isUserPicked && $isOptCorrect)
                                                            <span class="text-xs font-semibold text-emerald-700">✓ Jawaban Anda (Benar)</span>
                                                        @elseif($isUserPicked && !$isOptCorrect)
                                                            <span class="text-xs font-semibold text-rose-700">✕ Jawaban Anda (Salah)</span>
                                                        @elseif($isOptCorrect)
                                                            <span class="text-xs font-medium text-emerald-700">✓ Kunci Jawaban Benar</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- Menjodohkan Pasangan --}}
                                    @elseif($qType === 'mencocokkan')
                                        @php
                                            $pairResults = $eval['pairResults'] ?? [];
                                        @endphp
                                        <div class="space-y-2.5 pt-1">
                                            @foreach($pairResults as $res)
                                                <div class="p-3.5 rounded-lg border border-slate-200 bg-white text-xs space-y-2">
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                        <div class="font-semibold text-slate-800 flex items-center gap-2">
                                                            @if(str_starts_with($res['term'], 'data:image'))
                                                                <img src="{{ $res['term'] }}" alt="Item visual" class="h-10 border border-slate-200 rounded p-1 bg-white">
                                                            @else
                                                                <span>{{ $res['term'] }}</span>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            @if($res['is_correct'])
                                                                <span class="text-xs font-semibold text-emerald-700">✓ Pasangan Tepat</span>
                                                            @else
                                                                <span class="text-xs font-semibold text-rose-700">✕ Pasangan Salah</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-slate-100 text-[11px]">
                                                        <div>
                                                            <span class="text-slate-400 block font-medium">Pilihan Anda:</span>
                                                            @if(!empty($res['user']) && (str_starts_with($res['user'], 'data:image') || str_starts_with($res['user'], 'http') || str_starts_with($res['user'], '/')))
                                                                <img src="{{ $res['user'] }}" alt="Pilihan Anda" class="h-10 border border-slate-200 rounded p-1 bg-white mt-1">
                                                            @else
                                                                <span class="font-semibold {{ $res['is_correct'] ? 'text-emerald-800' : 'text-rose-800' }}">{{ $res['user'] ?? '(Tidak dijawab)' }}</span>
                                                            @endif
                                                        </div>
                                                        @if(!$res['is_correct'])
                                                            <div>
                                                                <span class="text-emerald-700 block font-medium">Kunci yang Benar:</span>
                                                                @if(!empty($res['expected']) && (str_starts_with($res['expected'], 'data:image') || str_starts_with($res['expected'], 'http') || str_starts_with($res['expected'], '/')))
                                                                    <img src="{{ $res['expected'] }}" alt="Kunci Benar" class="h-10 border border-slate-200 rounded p-1 bg-white mt-1">
                                                                @else
                                                                    <span class="font-bold text-emerald-900">{{ $res['expected'] }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                    {{-- Uraian --}}
                                    @elseif($qType === 'uraian')
                                        <div class="space-y-2 pt-1">
                                            <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-xs font-normal text-slate-800 whitespace-pre-line leading-relaxed">
                                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Jawaban Anda:</span>
                                                {{ $ans['text'] ?? '(Tidak ada jawaban tertulis)' }}
                                            </div>
                                            <p class="text-[11px] text-slate-500 italic">* Jawaban uraian tersimpan di sistem dan dinilai secara manual oleh dosen pengampu.</p>
                                        </div>

                                    {{-- Coding --}}
                                    @elseif($qType === 'coding')
                                        <div class="space-y-2 pt-1">
                                            <div class="rounded-lg bg-slate-900 text-slate-100 p-4 text-xs font-mono overflow-x-auto">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2 font-sans">Kode Program Anda:</span>
                                                <code>{{ $ans['text'] ?? '# Tidak ada kode yang dikirim' }}</code>
                                            </div>
                                            <p class="text-[11px] text-slate-500 italic">* Kode program Anda telah tersimpan dan siap ditinjau oleh pengampu praktikum.</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </main>
        </div>

    @else

        {{-- ================================================================= --}}
        {{-- RUANG UJIAN AKTIF (LAYAR PENUH FOKUS TANPA DISTRAKSI & TANPA SIDEBAR) --}}
        {{-- ================================================================= --}}

        {{-- TOP STICKY APP BAR --}}
        <header class="h-14 shrink-0 bg-white border-b border-slate-200 z-30 px-4 sm:px-6 flex items-center justify-between gap-4">
            
            {{-- Left: Exit Link & Exam Info --}}
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="btn-exit-exam" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer" title="Kembali ke Ringkasan" aria-label="Keluar dari ruang ujian">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500">{{ $course['code'] }}</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-xs text-slate-500 truncate hidden sm:inline">{{ $course['title'] }}</span>
                    </div>
                    <h1 class="truncate text-sm font-bold text-slate-900 leading-tight">{{ $item['title'] }}</h1>
                </div>
            </div>

            {{-- Center: Countdown Timer & Tombol Daftar Soal Modal --}}
            <div class="flex items-center gap-2.5">
                {{-- Countdown Timer --}}
                @if($durationMinutes)
                    <div id="timer-badge" class="flex items-center gap-1.5 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded text-xs font-mono font-bold text-slate-700">
                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span id="quiz-countdown" data-duration="{{ $durationMinutes * 60 }}">--:--</span>
                    </div>
                @else
                    <div class="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded text-xs text-slate-600 border border-slate-200">
                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span>Durasi Bebas</span>
                    </div>
                @endif

                {{-- TOMBOL DAFTAR SOAL (Grid Popover Trigger) --}}
                <button type="button" id="btn-open-grid-modal" class="button-secondary text-xs py-1.5 px-3 font-bold flex items-center gap-1.5 bg-white hover:bg-slate-50 border-slate-300 text-slate-800 shadow-2xs cursor-pointer" title="Buka Daftar Nomor Soal">
                    <svg class="h-3.5 w-3.5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                    <span>Daftar Soal (<span id="header-cur-step">1</span>/{{ $totalQuestions }})</span>
                </button>
            </div>

            {{-- Right: Stepper Navigasi Soal di Kanan Atas (Sebelumnya, Selanjutnya / Kumpulkan di Soal Terakhir) --}}
            <div class="flex items-center gap-2">
                <button type="button" id="btn-top-prev" class="button-secondary text-xs py-1.5 px-3 font-semibold flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" title="Soal Sebelumnya">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </button>

                <button type="button" id="btn-top-next" class="button-primary text-xs py-1.5 px-3.5 font-semibold flex items-center gap-1.5 cursor-pointer" title="Soal Selanjutnya">
                    <span>Selanjutnya</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" id="btn-top-finish" class="button-primary text-xs py-1.5 px-3.5 font-bold flex items-center gap-1.5 shadow-xs hidden cursor-pointer" title="Kumpulkan Kuis">
                    <span>Kumpulkan Kuis</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
            </div>
        </header>

        {{-- MAIN EXAM SPLIT WORKBENCH (FULL SCREEN TANPA SIDEBAR) --}}
        <main class="flex-1 overflow-hidden p-3 sm:p-4">
            <form id="exam-form" method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}" class="h-full">
                @csrf
                <input type="hidden" name="from_quiz_room" value="1">

                @foreach($questions as $qIdx => $q)
                    <div data-exam-card="{{ $qIdx }}" class="h-full grid grid-cols-1 lg:grid-cols-[380px_minmax(0,1fr)] xl:grid-cols-[420px_minmax(0,1fr)] gap-3.5 {{ $qIdx === 0 ? '' : 'hidden' }}">
                        
                        {{-- PANEL KIRI: SOAL & INSTRUKSI --}}
                        <section class="h-full flex flex-col rounded-lg bg-white border border-slate-200 overflow-hidden shadow-2xs">
                            {{-- Header Panel Kiri --}}
                            @php
                                $cpmkRaw = $q['cpmk'] ?? '';
                                $cpmkCode = $cpmkRaw ? trim(explode(':', $cpmkRaw)[0]) : '';
                                if (empty($cpmkCode) && !empty($q['cpl'])) {
                                    $cpmkCode = trim(explode(':', $q['cpl'])[0]);
                                }
                            @endphp
                            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-soft text-brand font-bold text-xs">
                                        {{ $qIdx + 1 }}
                                    </span>
                                    <span class="text-xs font-semibold text-ink">
                                        @if($q['type'] === 'coding')
                                            Pemrograman
                                        @elseif($q['type'] === 'mencocokkan')
                                            Mencocokkan Pasangan
                                        @elseif($q['type'] === 'pilihan')
                                            Pilihan Ganda
                                        @elseif($q['type'] === 'kompleks')
                                            Pilihan Ganda Kompleks
                                        @elseif($q['type'] === 'benar_salah')
                                            Benar / Salah
                                        @else
                                            Uraian / Esai
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if(!empty($cpmkCode))
                                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">
                                            {{ $cpmkCode }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white border border-line/70 text-ink shadow-2xs">
                                        {{ $q['points'] ?? 15 }} Poin
                                    </span>
                                </div>
                            </div>

                            {{-- Body Panel Kiri (Scrollable) --}}
                            <div class="p-5 flex-1 overflow-y-auto [scrollbar-gutter:stable] space-y-4">
                                <div>
                                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Instruksi / Pertanyaan</h2>
                                    <div class="text-sm font-normal text-slate-800 leading-relaxed whitespace-pre-line">{{ $q['prompt'] }}</div>
                                </div>

                                @if(!empty($q['image']))
                                    <div class="pt-2">
                                        <div class="rounded border border-slate-200 p-2 bg-slate-50">
                                            <img src="{{ route('preview.file', $q['image']) }}" alt="{{ $q['alt'] ?? 'Stimulus visual' }}" class="max-h-56 mx-auto object-contain">
                                        </div>
                                        @if(!empty($q['alt']))
                                            <p class="text-[11px] text-slate-400 mt-1 italic">{{ $q['alt'] }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Footer Panel Kiri --}}
                            <div class="px-5 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs text-slate-400">
                                <span>Soal {{ $qIdx + 1 }} dari {{ $totalQuestions }}</span>
                                <span class="text-muted font-medium flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                    Draf tersimpan otomatis
                                </span>
                            </div>
                        </section>

                        {{-- PANEL KANAN: AREA LEMBAR KERJA / TEMPAT MENJAWAB --}}
                        <section class="h-full flex flex-col rounded-lg bg-white border border-slate-200 overflow-hidden shadow-2xs">
                            
                            {{-- TIPE 1: CODING (Integrated Workbench - Mirip Assignment Code Sebelumnya Tanpa AI) --}}
                            @if($q['type'] === 'coding')
                                <div id="panel-editor-{{ $qIdx }}" class="flex-1 min-w-[320px] flex flex-col h-full overflow-hidden transition-none p-1">
                                    {{-- Editor Section --}}
                                    <section class="flex-1 flex flex-col min-h-0 rounded-xl bg-white shadow-sm border border-slate-200 overflow-hidden" aria-labelledby="editor-heading-{{ $qIdx }}">
                                        {{-- Single Integrated Toolbar: Tab Berkas di kiri, Aksi & Terminal Toggle di kanan --}}
                                        <div class="flex items-center justify-between border-b border-slate-200/80 bg-slate-50 px-2.5 py-1.5 gap-2 select-none">
                                            {{-- File Tabs (Kiri) --}}
                                            <div data-file-tabs class="flex items-center gap-1 overflow-x-auto min-w-0" role="tablist" aria-label="Berkas kode">
                                                <div class="flex items-center gap-1 min-w-0">
                                                    <span class="flex items-center gap-1.5 px-3 py-1 bg-white border border-slate-200 rounded-md text-xs font-mono font-semibold text-slate-800 shadow-2xs">
                                                        solution.py
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Action Buttons (Kanan): Terminal (Icon) | Play (Icon) (tanpa AI) --}}
                                            <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                                                <button type="button" data-terminal-toggle-btn="{{ $qIdx }}" class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white hover:bg-slate-50 text-slate-700 transition hover:border-ink shadow-2xs leading-none cursor-pointer" title="Buka / Tutup Terminal" aria-label="Terminal">
                                                    <svg class="h-3.5 w-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                                                </button>
                                                <button type="button" data-run-code-btn="{{ $qIdx }}" class="button-primary !p-0 !min-h-0 h-8 w-8 text-white shadow-xs transition leading-none cursor-pointer" title="Jalankan kode" aria-label="Jalankan kode">
                                                    <svg class="h-3.5 w-3.5 fill-current text-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                                        <polygon points="5 3 19 12 5 21 5 3"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Textarea / Code Editor --}}
                                        <textarea name="question_answers[{{ $qIdx }}][text]" data-code-textarea="{{ $qIdx }}" class="flex-1 w-full bg-[#282c34] text-slate-100 font-mono text-xs p-4 leading-relaxed resize-none focus:outline-none focus:ring-0 border-0 selection:bg-brand/30 selection:text-white" placeholder="# Tuliskan implementasi solusi Python Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? ($q['options'] ?? '')) }}</textarea>

                                        {{-- Status Bar Bawah Editor --}}
                                        <div class="flex items-center justify-between gap-3 px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4] font-mono select-none">
                                            <span class="flex items-center gap-1.5 text-slate-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                <span>Draf tersimpan di browser</span>
                                            </span>
                                            <span class="flex items-center gap-3 text-slate-400">
                                                <span id="code-chars-{{ $qIdx }}">0 karakter</span>
                                                <span>UTF-8, 4 Spasi</span>
                                            </span>
                                        </div>
                                    </section>

                                    {{-- Terminal Wrapper: Resizer di atas + Terminal Panel (On-demand / Hidden by default!) --}}
                                    <div id="terminal-wrapper-{{ $qIdx }}" class="flex flex-col shrink-0 mt-2" hidden>
                                        {{-- Resizer Handle Tinggi Terminal --}}
                                        <div class="h-3 w-full shrink-0 cursor-row-resize flex items-center justify-center bg-slate-200/80 hover:bg-brand/30 group transition-colors rounded-t-lg select-none" title="Geser ke atas/bawah untuk mengatur tinggi terminal">
                                            <div class="h-1 w-14 rounded-full bg-slate-400 group-hover:bg-brand transition-colors"></div>
                                        </div>

                                        <section class="flex flex-col rounded-b-xl bg-[#0d1117] text-[#c9d1d9] shadow-sm border border-slate-200/40 overflow-hidden" style="height: 220px; min-height: 120px;" aria-labelledby="terminal-heading-{{ $qIdx }}">
                                            <div class="flex items-center justify-between px-4 py-2 bg-[#161b22] border-b border-white/10 select-none">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center gap-1.5">
                                                        <button type="button" data-terminal-close-dot="{{ $qIdx }}" class="h-3 w-3 rounded-full bg-[#ff5f56] hover:opacity-80 transition inline-block shadow-xs cursor-pointer" title="Tutup Terminal" aria-label="Tutup Terminal"></button>
                                                        <button type="button" class="h-3 w-3 rounded-full bg-[#ffbd2e] hover:opacity-80 transition inline-block shadow-xs" title="Perkecil Terminal" aria-label="Perkecil Terminal"></button>
                                                        <button type="button" class="h-3 w-3 rounded-full bg-[#27c93f] hover:opacity-80 transition inline-block shadow-xs" title="Perbesar Terminal" aria-label="Perbesar Terminal"></button>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 text-slate-300">
                                                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                                                        <span class="text-xs font-mono text-slate-300 font-medium">Output Python (Terminal)</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" data-clear-terminal="{{ $qIdx }}" class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition cursor-pointer">
                                                        Bersihkan
                                                    </button>
                                                    <button type="button" data-terminal-close-btn="{{ $qIdx }}" class="text-xs text-slate-400 hover:text-white px-2 py-0.5 rounded hover:bg-white/10 transition cursor-pointer" title="Tutup Terminal">
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="terminal-output-{{ $qIdx }}" class="p-3.5 flex-1 overflow-y-auto space-y-1 text-xs font-mono text-slate-300">
                                                <div class="text-slate-500">sale@sandbox:~/soal-{{ $qIdx + 1 }}$ python3 -u solution.py</div>
                                                <div class="text-slate-400">Tekan tombol play untuk menguji kode solusi Anda.</div>
                                            </div>
                                        </section>
                                    </div>
                                </div>

                            {{-- TIPE 2: MENCOCOKKAN PASANGAN (Interactive Line Canvas) --}}
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
                                    if (empty($pairs)) {
                                        $pairs = [
                                            ['left' => 'Pre-order', 'right' => 'Akar - Kiri - Kanan'],
                                            ['left' => 'In-order', 'right' => 'Kiri - Akar - Kanan'],
                                            ['left' => 'Post-order', 'right' => 'Kiri - Kanan - Akar'],
                                        ];
                                    }
                                    $colors = ['#1d4ed8', '#047857', '#b45309', '#6d28d9', '#0f766e', '#be123c', '#4338ca'];
                                @endphp

                                <div class="h-full flex flex-col">
                                    {{-- Canvas Header Status --}}
                                    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between text-xs bg-slate-50/50">
                                        <span class="text-slate-600 font-medium">Klik premis di kiri lalu klik pasangan jawaban di kanan untuk menghubungkan.</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-slate-500 font-semibold"><span id="match-counter-{{ $qIdx }}">0 dari {{ count($pairs) }}</span> terhubung</span>
                                            <button type="button" data-reset-lines="{{ $qIdx }}" class="text-xs text-muted hover:text-ink underline font-medium cursor-pointer">Reset Semua Garis</button>
                                        </div>
                                    </div>

                                    {{-- Interactive Line Drawing Canvas --}}
                                    <div class="flex-1 overflow-y-auto [scrollbar-gutter:stable] p-6 match-canvas-container relative" id="canvas-container-{{ $qIdx }}">
                                        <svg class="absolute pointer-events-none z-10" style="top: 0; left: 0; min-width: 100%; min-height: 100%;" id="match-svg-{{ $qIdx }}"></svg>

                                        <div class="grid grid-cols-2 gap-20 relative z-20 items-stretch">
                                            {{-- Kolom Kiri: Premis --}}
                                            <div class="space-y-4" id="match-left-col-{{ $qIdx }}">
                                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Kolom Premis</p>
                                                @foreach($pairs as $pIdx => $pair)
                                                    @php
                                                        $isImg = str_starts_with($pair['left'], 'http') || str_starts_with($pair['left'], 'data:image') || str_starts_with($pair['left'], '/');
                                                    @endphp
                                                    <div class="relative flex items-center justify-between p-3.5 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition cursor-pointer select-none" data-match-left-card="{{ $pIdx }}">
                                                        <div class="min-w-0 pr-4 w-full">
                                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                                <span class="text-[11px] font-semibold text-slate-400">Premis {{ $pIdx + 1 }}</span>
                                                                <button type="button" data-disconnect-left="{{ $pIdx }}" class="hidden text-[11px] text-muted hover:text-ink font-semibold items-center gap-0.5 cursor-pointer" title="Batalkan / Putuskan sambungan">
                                                                    ✕ Putuskan
                                                                </button>
                                                            </div>
                                                            @if($isImg)
                                                                <div class="flex items-center justify-center p-1 bg-slate-50 rounded border border-slate-100">
                                                                    <img src="{{ $pair['left'] }}" alt="Premis {{ $pIdx + 1 }}" class="max-h-16 max-w-full object-contain">
                                                                </div>
                                                            @else
                                                                <p class="text-xs sm:text-sm font-medium text-slate-800 leading-snug">{{ $pair['left'] }}</p>
                                                            @endif
                                                        </div>

                                                        <button type="button"
                                                            data-dot-side="left"
                                                            data-dot-idx="{{ $pIdx }}"
                                                            data-color="{{ $colors[$pIdx % count($colors)] }}"
                                                            class="match-dot absolute -right-2.5 top-1/2 -translate-y-1/2 h-5 w-5 rounded-full border-2 border-white bg-slate-300 shadow-xs flex items-center justify-center cursor-pointer"
                                                            title="Hubungkan Premis {{ $pIdx + 1 }}">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                        </button>

                                                        <input type="hidden"
                                                            name="question_answers[{{ $qIdx }}][matching][{{ $pIdx }}]"
                                                            id="hidden-match-{{ $qIdx }}-{{ $pIdx }}"
                                                            value="{{ old("question_answers.$qIdx.matching.$pIdx", $submission['question_answers'][$qIdx]['matching'][$pIdx] ?? '') }}">
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- Kolom Kanan: Pasangan Jawaban --}}
                                            <div class="space-y-4" id="match-right-col-{{ $qIdx }}">
                                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">Kolom Jawaban</p>
                                                @php
                                                    $targets = array_column($pairs, 'right');
                                                    $userSeed = auth()->id() ?? (session('auth_user.id') ?? (session('auth_user.number') ? crc32((string) session('auth_user.number')) : 1));
                                                    $seed = (int) ($item['id'] ?? 1) * 31 + (int) $userSeed * 17 + ($qIdx + 1) * 7;
                                                    mt_srand($seed);
                                                    shuffle($targets);
                                                    mt_srand();
                                                @endphp
                                                @foreach($targets as $tIdx => $target)
                                                    @php
                                                        $isTargetImg = str_starts_with($target, 'http') || str_starts_with($target, 'data:image') || str_starts_with($target, '/');
                                                    @endphp
                                                    <div class="relative flex items-center p-3.5 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition cursor-pointer select-none" data-match-right-card="{{ $tIdx }}" data-target-val="{{ $target }}">
                                                        <button type="button"
                                                            data-dot-side="right"
                                                            data-dot-idx="{{ $tIdx }}"
                                                            class="match-dot absolute -left-2.5 top-1/2 -translate-y-1/2 h-5 w-5 rounded-full border-2 border-white bg-slate-300 shadow-xs flex items-center justify-center cursor-pointer"
                                                            title="Pasangkan dengan Jawaban {{ $tIdx + 1 }}">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                        </button>

                                                        <div class="min-w-0 pl-3 w-full">
                                                            <span class="text-[11px] font-semibold text-slate-400 block mb-1">Jawaban {{ $tIdx + 1 }}</span>
                                                            @if($isTargetImg)
                                                                <div class="flex items-center justify-center p-1 bg-slate-50 rounded border border-slate-100">
                                                                    <img src="{{ $target }}" alt="Jawaban {{ $tIdx + 1 }}" class="max-h-16 max-w-full object-contain">
                                                                </div>
                                                            @else
                                                                <p class="text-xs sm:text-sm font-medium text-slate-800 leading-snug">{{ $target }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            {{-- TIPE 3: PILIHAN GANDA & KOMPLEKS --}}
                            @elseif(in_array($q['type'], ['pilihan', 'kompleks']))
                                @php
                                    $options = array_values(array_filter(array_map('trim', explode("\n", $q['options'] ?? '')), fn($o) => $o !== ''));
                                    $isMultiple = $q['type'] === 'kompleks';
                                @endphp
                                <div class="h-full flex flex-col">
                                    <div class="px-5 py-3 border-b border-slate-100 text-xs text-slate-500 bg-slate-50/50">
                                        {{ $isMultiple ? 'Pilih semua opsi yang benar di bawah ini:' : 'Pilih satu opsi jawaban yang paling tepat:' }}
                                    </div>
                                    <div class="p-6 flex-1 overflow-y-auto [scrollbar-gutter:stable] space-y-3">
                                        @foreach($options as $optIdx => $opt)
                                            <label class="flex items-center gap-3.5 p-4 rounded-lg border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer transition">
                                                <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                                                    name="question_answers[{{ $qIdx }}][choices][]"
                                                    value="{{ $opt }}"
                                                    class="h-4 w-4 text-slate-900 focus:ring-slate-900 rounded"
                                                    @checked(in_array($opt, old("question_answers.$qIdx.choices", $submission['question_answers'][$qIdx]['choices'] ?? [])))>
                                                <span class="text-sm font-medium text-slate-800 leading-relaxed">{{ $opt }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                            {{-- TIPE 4: BENAR / SALAH --}}
                            @elseif($q['type'] === 'benar_salah')
                                <div class="h-full flex flex-col">
                                    <div class="px-5 py-3 border-b border-slate-100 text-xs text-slate-500 bg-slate-50/50">
                                        Tentukan kebenaran dari pernyataan pada panel kiri:
                                    </div>
                                    <div class="p-6 flex-1 flex flex-col justify-start max-w-md mx-auto w-full space-y-3 pt-6">
                                        <label class="flex items-center gap-3.5 p-4 rounded-lg border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Benar" class="h-4 w-4 text-slate-900 focus:ring-slate-900"
                                                @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Benar')>
                                            <span class="text-sm font-bold text-slate-900">Benar</span>
                                        </label>
                                        <label class="flex items-center gap-3.5 p-4 rounded-lg border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer transition">
                                            <input type="radio" name="question_answers[{{ $qIdx }}][boolean_choice]" value="Salah" class="h-4 w-4 text-slate-900 focus:ring-slate-900"
                                                @checked(old("question_answers.$qIdx.boolean_choice", $submission['question_answers'][$qIdx]['boolean_choice'] ?? '') === 'Salah')>
                                            <span class="text-sm font-bold text-slate-900">Salah</span>
                                        </label>
                                    </div>
                                </div>

                            {{-- TIPE 5: ESSAY / URAIAN --}}
                            @else
                                <div class="h-full flex flex-col">
                                    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between text-xs text-slate-500 bg-slate-50/50">
                                        <span>Tuliskan uraian solusi atau argumen akademik Anda:</span>
                                        <span id="essay-word-count-{{ $qIdx }}" class="font-mono text-slate-400">0 kata</span>
                                    </div>
                                    <div class="p-5 flex-1 flex flex-col">
                                        <textarea data-essay-input="{{ $qIdx }}" name="question_answers[{{ $qIdx }}][text]" class="flex-1 w-full rounded-lg border border-slate-200 p-4 text-sm text-slate-800 leading-relaxed resize-none focus:outline-none focus:border-slate-800 focus:ring-0" placeholder="Tuliskan jawaban Anda di sini...">{{ old("question_answers.$qIdx.text", $submission['question_answers'][$qIdx]['text'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            @endif

                        </section>
                    </div>
                @endforeach
            </form>
        </main>

        {{-- MODAL POPOVER: DAFTAR NOMOR SOAL (Grid Kotak-Kotak 1 s/d N) --}}
        <dialog id="questions-grid-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-lg w-[calc(100%-2rem)] h-fit">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Nomor Soal Ujian</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Pilih nomor kotak untuk melompat langsung ke soal terkait.</p>
                </div>
                <button type="button" id="modal-grid-close" class="h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
            </div>

            {{-- Legenda Warna Status --}}
            <div class="flex flex-wrap items-center gap-3.5 text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-200/70 mb-4">
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded bg-brand inline-block"></span>
                    <span class="font-medium">Terjawab</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded bg-slate-900 inline-block"></span>
                    <span class="font-medium">Sedang Dibuka</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded bg-white border border-slate-300 inline-block"></span>
                    <span class="font-medium">Belum Dijawab</span>
                </span>
            </div>

            {{-- Kisi Kotak Nomor Soal --}}
            <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-2.5 max-h-[50vh] overflow-y-auto p-1" id="grid-boxes-container">
                @foreach($questions as $qIdx => $q)
                    <button type="button"
                        data-grid-step="{{ $qIdx }}"
                        class="h-11 rounded-xl text-xs font-bold transition flex flex-col items-center justify-center border border-slate-200 bg-white text-slate-700 hover:border-slate-400 cursor-pointer shadow-2xs">
                        <span>{{ $qIdx + 1 }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Footer Modal --}}
            <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span id="grid-answered-badge" class="text-xs font-semibold text-slate-600">0 dari {{ $totalQuestions }} soal terjawab</span>
                <button type="button" id="modal-grid-close-btn" class="button-secondary text-xs py-1.5 px-3.5 font-medium cursor-pointer">Tutup Kisi</button>
            </div>
        </dialog>

        {{-- MODAL KONFIRMASI KELUAR & KUMPULKAN OTOMATIS DARI RUANG UJIAN --}}
        <dialog id="exit-confirm-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-md w-[calc(100%-2rem)] h-fit">
            <div class="flex items-start gap-3.5 mb-4">
                <div class="h-10 w-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-slate-900">Keluar &amp; Kumpulkan Ujian?</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Jawaban yang telah dikerjakan akan disimpan otomatis sebagai hasil akhir kuis.
                    </p>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5 mb-4 text-xs text-slate-700 space-y-1.5">
                <div class="flex items-center gap-1.5 font-bold text-slate-900">
                    <svg class="h-4 w-4 shrink-0 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Ujian Selesai &amp; Tidak Dapat Diulang</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-normal">
                    Jika Anda keluar sekarang sebelum selesai, nilai dan lembar jawaban Anda akan <strong>langsung dikumpulkan secara otomatis</strong> dan Anda <strong>tidak dapat melanjutkan ujian lagi</strong>.
                </p>
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-5 text-xs">
                <span id="modal-exit-answered-count" class="font-bold text-slate-800">Menghitung status jawaban...</span>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <button type="button" id="modal-exit-cancel-btn" class="button-secondary text-xs py-2 px-3.5 font-medium cursor-pointer">
                    Lanjutkan Mengerjakan
                </button>
                <button type="button" id="modal-exit-confirm-btn" class="button-primary text-xs py-2 px-4 font-bold cursor-pointer">
                    Ya, Keluar &amp; Kumpulkan
                </button>
            </div>
        </dialog>

        {{-- MODAL KONFIRMASI PENGUMPULAN --}}
        <dialog id="submit-confirm-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-md w-[calc(100%-2rem)] h-fit">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Kumpulkan Lembar Kuis?</h3>
                    <p class="text-xs text-slate-500">Pemeriksaan final sebelum penyimpanan permanen.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed mb-4">
                Pastikan Anda telah memeriksa seluruh jawaban. Setelah dikumpulkan, lembar jawaban kuis akan <strong>terkunci</strong> dan tidak dapat dikerjakan ulang.
            </p>
            
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-5 text-xs">
                <span id="modal-answered-count" class="font-bold text-slate-800">Menghitung status jawaban...</span>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <button type="button" id="modal-cancel-btn" class="button-secondary text-xs py-2 px-3.5 font-medium cursor-pointer">Batal &amp; Periksa Lagi</button>
                <button type="button" id="modal-confirm-submit-btn" class="button-primary text-xs py-2 px-4 font-bold shadow-xs cursor-pointer">Ya, Kumpulkan Sekarang</button>
            </div>
        </dialog>

        {{-- SCRIPT ENGINE RUANG UJIAN --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('exam-form');
                const cards = document.querySelectorAll('[data-exam-card]');
                const totalCards = cards.length;
                let currentStep = 0;

                // Elements
                const headerCurStep = document.getElementById('header-cur-step');
                const btnTopPrev = document.getElementById('btn-top-prev');
                const btnTopNext = document.getElementById('btn-top-next');
                const btnTopFinish = document.getElementById('btn-top-finish');

                // Modals
                const gridModal = document.getElementById('questions-grid-modal');
                const btnOpenGridModal = document.getElementById('btn-open-grid-modal');
                const modalGridClose = document.getElementById('modal-grid-close');
                const modalGridCloseBtn = document.getElementById('modal-grid-close-btn');
                const gridStepBtns = document.querySelectorAll('[data-grid-step]');
                const gridAnsweredBadge = document.getElementById('grid-answered-badge');

                const submitBtn = document.getElementById('btn-submit-exam');
                const submitModal = document.getElementById('submit-confirm-modal');
                const modalCancelBtn = document.getElementById('modal-cancel-btn');
                const modalConfirmBtn = document.getElementById('modal-confirm-submit-btn');
                const modalAnsweredSpan = document.getElementById('modal-answered-count');

                // ==========================================
                // 1. STEPPER CONTROLLER (KANAN ATAS)
                // ==========================================
                const setStep = (idx) => {
                    if (idx < 0 || idx >= totalCards) return;
                    currentStep = idx;

                    // Toggle Card Active
                    cards.forEach((c, i) => {
                        c.classList.toggle('hidden', i !== currentStep);
                    });

                    // Update Indicator Text
                    if (headerCurStep) headerCurStep.textContent = `${currentStep + 1}`;

                    // Update Stepper Buttons (Prev / Next / Finish) di Kanan Atas
                    if (btnTopPrev) btnTopPrev.disabled = currentStep === 0;

                    if (currentStep === totalCards - 1) {
                        // Soal Terakhir: Next disembunyikan, Finish ditampilkan
                        if (btnTopNext) btnTopNext.classList.add('hidden');
                        if (btnTopFinish) btnTopFinish.classList.remove('hidden');
                    } else {
                        // Soal Bukan Terakhir: Next ditampilkan, Finish disembunyikan
                        if (btnTopNext) btnTopNext.classList.remove('hidden');
                        if (btnTopFinish) btnTopFinish.classList.add('hidden');
                    }

                    redrawCurrentMatchingLines();
                    updateAnsweredStatus();
                };

                btnTopPrev?.addEventListener('click', () => setStep(currentStep - 1));
                btnTopNext?.addEventListener('click', () => setStep(currentStep + 1));
                btnTopFinish?.addEventListener('click', () => openSubmitModal());

                // ==========================================
                // 2. GRID MODAL CONTROLLER
                // ==========================================
                const openGridModal = () => {
                    updateAnsweredStatus();
                    gridModal?.showModal();
                };

                btnOpenGridModal?.addEventListener('click', openGridModal);
                modalGridClose?.addEventListener('click', () => gridModal?.close());
                modalGridCloseBtn?.addEventListener('click', () => gridModal?.close());

                gridStepBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const targetStep = Number(btn.dataset.gridStep);
                        setStep(targetStep);
                        gridModal?.close();
                    });
                });

                // ==========================================
                // 3. ANSWERED STATUS & STYLING
                // ==========================================
                function updateAnsweredStatus() {
                    let answeredCount = 0;

                    cards.forEach((card, idx) => {
                        const gridBtn = gridStepBtns[idx];
                        if (!gridBtn) return;

                        const radios = card.querySelectorAll('input[type="radio"]:checked');
                        const checkboxes = card.querySelectorAll('input[type="checkbox"]:checked');
                        const hiddens = card.querySelectorAll('input[type="hidden"][id^="hidden-match-"]');
                        let matchAnswered = hiddens.length > 0;
                        hiddens.forEach(h => { if (!h.value) matchAnswered = false; });
                        const textareas = card.querySelectorAll('textarea');
                        let textAnswered = false;
                        textareas.forEach(t => { if (t.value.trim().length > 0) textAnswered = true; });

                        const isAnswered = radios.length > 0 || checkboxes.length > 0 || (hiddens.length > 0 && matchAnswered) || textAnswered;
                        if (isAnswered) answeredCount++;

                        // Style Grid Box
                        if (idx === currentStep) {
                            gridBtn.className = 'h-11 rounded-xl text-xs font-bold transition flex flex-col items-center justify-center border-2 border-brand ring-2 ring-brand/20 bg-white text-brand shadow-xs cursor-pointer';
                        } else if (isAnswered) {
                            gridBtn.className = 'h-11 rounded-xl text-xs font-bold transition flex flex-col items-center justify-center border border-brand bg-brand text-white shadow-xs cursor-pointer';
                        } else {
                            gridBtn.className = 'h-11 rounded-xl text-xs font-medium transition flex flex-col items-center justify-center border border-line bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50 cursor-pointer';
                        }
                    });

                    if (gridAnsweredBadge) {
                        gridAnsweredBadge.textContent = `${answeredCount} dari ${totalCards} soal terjawab`;
                    }

                    return answeredCount;
                }

                form.addEventListener('input', updateAnsweredStatus);
                form.addEventListener('change', updateAnsweredStatus);

                // ==========================================
                // 4. SUBMISSION CONFIRMATION MODAL
                // ==========================================
                const openSubmitModal = () => {
                    const ans = updateAnsweredStatus();
                    if (modalAnsweredSpan) {
                        modalAnsweredSpan.textContent = `${ans} dari ${totalCards} soal telah dijawab.`;
                    }
                    submitModal?.showModal();
                };

                submitBtn?.addEventListener('click', openSubmitModal);
                modalCancelBtn?.addEventListener('click', () => submitModal?.close());
                modalConfirmBtn?.addEventListener('click', () => {
                    localStorage.removeItem(`sale.exam.deadline.{{ $item['id'] }}`);
                    sessionStorage.removeItem(`sale.exam.timer.{{ $item['id'] }}`);
                    form.requestSubmit();
                });

                // Exit Confirmation Modal Handler (Keluar = Kumpulkan Otomatis & Kunci Ujian)
                const exitModal = document.getElementById('exit-confirm-modal');
                const btnExitExam = document.getElementById('btn-exit-exam');
                const modalExitCancelBtn = document.getElementById('modal-exit-cancel-btn');
                const modalExitConfirmBtn = document.getElementById('modal-exit-confirm-btn');
                const modalExitAnsweredSpan = document.getElementById('modal-exit-answered-count');

                const openExitModal = () => {
                    const ans = updateAnsweredStatus();
                    if (modalExitAnsweredSpan) {
                        modalExitAnsweredSpan.textContent = `${ans} dari ${totalCards} soal telah dijawab.`;
                    }
                    exitModal?.showModal();
                };

                btnExitExam?.addEventListener('click', openExitModal);

                // Mencegah tombol back di browser / swipe agar tidak langsung keluar tanpa kumpul
                history.pushState(null, '', window.location.href);
                window.addEventListener('popstate', () => {
                    history.pushState(null, '', window.location.href);
                    openExitModal();
                });

                modalExitCancelBtn?.addEventListener('click', () => {
                    exitModal?.close();
                });

                modalExitConfirmBtn?.addEventListener('click', () => {
                    localStorage.removeItem(`sale.exam.deadline.{{ $item['id'] }}`);
                    sessionStorage.removeItem(`sale.exam.timer.{{ $item['id'] }}`);
                    form.requestSubmit();
                });

                // ==========================================
                // 5. COUNTDOWN TIMER (PERSISTEN BERJALAN MESKI KELUAR ROOM)
                // ==========================================
                const timerEl = document.getElementById('quiz-countdown');
                if (timerEl) {
                    const totalDurationSeconds = Number(timerEl.dataset.duration || 3600);
                    const deadlineKey = `sale.exam.deadline.{{ $item['id'] }}`;
                    
                    let deadline = localStorage.getItem(deadlineKey);
                    const now = Date.now();

                    if (!deadline || isNaN(Number(deadline))) {
                        // Pertama kali masuk: tentukan batas akhir waktu absolut
                        deadline = now + (totalDurationSeconds * 1000);
                        localStorage.setItem(deadlineKey, String(deadline));
                    } else {
                        deadline = Number(deadline);
                    }

                    const formatTime = (seconds) => {
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    };

                    const updateTimer = () => {
                        const currentNow = Date.now();
                        const remainingSeconds = Math.max(0, Math.floor((deadline - currentNow) / 1000));
                        timerEl.textContent = formatTime(remainingSeconds);

                        if (remainingSeconds <= 300) {
                            const badge = document.getElementById('timer-badge');
                            if (badge) {
                                badge.classList.add('text-danger', 'font-bold');
                            }
                        }

                        if (remainingSeconds <= 0) {
                            clearInterval(timerInterval);
                            localStorage.removeItem(deadlineKey);
                            alert('Waktu ujian kuis telah berakhir. Jawaban Anda akan otomatis dikumpulkan.');
                            form.requestSubmit();
                        }
                    };

                    updateTimer();
                    const timerInterval = setInterval(updateTimer, 1000);
                }

                // ==========================================
                // 6. INTERACTIVE MATCHING LINE-DRAWING (SVG Canvas)
                // ==========================================
                const matchingQuestions = {};

                document.querySelectorAll('.match-canvas-container').forEach(container => {
                    const qIdxMatch = container.id.match(/\d+/);
                    if (!qIdxMatch) return;
                    const qIdx = Number(qIdxMatch[0]);

                    const svg = document.getElementById(`match-svg-${qIdx}`);
                    const leftCards = container.querySelectorAll('[data-match-left-card]');
                    const rightCards = container.querySelectorAll('[data-match-right-card]');
                    const leftDots = container.querySelectorAll('[data-dot-side="left"]');
                    const rightDots = container.querySelectorAll('[data-dot-side="right"]');
                    const counterEl = document.getElementById(`match-counter-${qIdx}`);
                    const resetBtn = container.querySelector(`[data-reset-lines="${qIdx}"]`);

                    const connections = {};
                    let selectedPremiseIdx = null;

                    // Pulihkan sambungan yang tersimpan sebelumnya
                    leftDots.forEach(ld => {
                        const lIdx = Number(ld.dataset.dotIdx);
                        const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                        if (hiddenInp && hiddenInp.value) {
                            const val = hiddenInp.value;
                            const matchedRight = [...container.querySelectorAll('[data-match-right-card]')].find(c => c.dataset.targetVal === val);
                            if (matchedRight) {
                                const rDot = matchedRight.querySelector('[data-dot-side="right"]');
                                if (rDot) {
                                    connections[lIdx] = {
                                        targetIdx: Number(rDot.dataset.dotIdx),
                                        targetVal: val,
                                        color: ld.dataset.color || '#1d4ed8'
                                    };
                                }
                            }
                        }
                    });

                    const drawLine = (x1, y1, x2, y2, color, id) => {
                        const dx = Math.abs(x2 - x1) * 0.45;
                        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        const d = `M ${x1} ${y1} C ${x1 + dx} ${y1}, ${x2 - dx} ${y2}, ${x2} ${y2}`;
                        path.setAttribute('d', d);
                        path.setAttribute('stroke', color);
                        path.setAttribute('stroke-width', '2.5');
                        path.setAttribute('fill', 'none');
                        path.setAttribute('stroke-linecap', 'round');
                        path.setAttribute('class', 'connection-line');
                        path.setAttribute('id', `line-${qIdx}-${id}`);
                        svg.appendChild(path);
                    };

                    const redrawLines = () => {
                        if (!svg || container.closest('.hidden')) return;
                        svg.innerHTML = '';
                        const containerRect = container.getBoundingClientRect();
                        svg.style.width = Math.max(container.scrollWidth, container.clientWidth) + 'px';
                        svg.style.height = Math.max(container.scrollHeight, container.clientHeight) + 'px';

                        let connectedCount = 0;

                        Object.entries(connections).forEach(([lIdx, conn]) => {
                            const lDot = container.querySelector(`[data-dot-side="left"][data-dot-idx="${lIdx}"]`);
                            const rDot = container.querySelector(`[data-dot-side="right"][data-dot-idx="${conn.targetIdx}"]`);
                            if (lDot && rDot) {
                                const lRect = lDot.getBoundingClientRect();
                                const rRect = rDot.getBoundingClientRect();

                                const x1 = lRect.left + lRect.width / 2 - containerRect.left + container.scrollLeft;
                                const y1 = lRect.top + lRect.height / 2 - containerRect.top + container.scrollTop;
                                const x2 = rRect.left + rRect.width / 2 - containerRect.left + container.scrollLeft;
                                const y2 = rRect.top + rRect.height / 2 - containerRect.top + container.scrollTop;

                                drawLine(x1, y1, x2, y2, conn.color, lIdx);

                                lDot.style.backgroundColor = conn.color;
                                lDot.style.borderColor = conn.color;
                                rDot.style.backgroundColor = conn.color;
                                rDot.style.borderColor = conn.color;
                                connectedCount++;
                            }
                        });

                        leftDots.forEach(ld => {
                            if (!connections[ld.dataset.dotIdx]) {
                                ld.style.backgroundColor = '#cbd5e1';
                                ld.style.borderColor = '#ffffff';
                            }
                        });
                        rightDots.forEach(rd => {
                            const isConnected = Object.values(connections).some(c => c.targetIdx === Number(rd.dataset.dotIdx));
                            if (!isConnected) {
                                rd.style.backgroundColor = '#cbd5e1';
                                rd.style.borderColor = '#ffffff';
                            }
                        });

                        // Sinkronkan tombol batal/putuskan pada setiap kartu kiri
                        leftCards.forEach(lc => {
                            const lIdx = Number(lc.dataset.matchLeftCard);
                            const discBtn = lc.querySelector('[data-disconnect-left]');
                            if (discBtn) {
                                if (connections[lIdx]) {
                                    discBtn.classList.remove('hidden');
                                    discBtn.classList.add('inline-flex');
                                } else {
                                    discBtn.classList.add('hidden');
                                    discBtn.classList.remove('inline-flex');
                                }
                            }
                        });

                        if (counterEl) {
                            counterEl.textContent = `${connectedCount} dari ${leftDots.length}`;
                        }
                        updateAnsweredStatus();
                    };

                    matchingQuestions[qIdx] = { redraw: redrawLines };

                    // 1-Click Select Premis di Kolom Kiri
                    leftCards.forEach(lc => {
                        lc.addEventListener('click', (e) => {
                            if (e.target.closest('[data-disconnect-left]')) return;

                            const pIdx = Number(lc.dataset.matchLeftCard);

                            if (selectedPremiseIdx === pIdx) {
                                // Deselect jika ditekan kembali
                                selectedPremiseIdx = null;
                                leftCards.forEach(c => c.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                                leftDots.forEach(d => d.classList.remove('selected'));
                                return;
                            }

                            selectedPremiseIdx = pIdx;
                            leftCards.forEach(c => {
                                const isTarget = Number(c.dataset.matchLeftCard) === pIdx;
                                c.classList.toggle('ring-2', isTarget);
                                c.classList.toggle('ring-brand', isTarget);
                                c.classList.toggle('border-brand', isTarget);
                            });
                            leftDots.forEach(d => {
                                d.classList.toggle('selected', Number(d.dataset.dotIdx) === pIdx);
                            });
                        });
                    });

                    // 1-Click Pasangkan dengan Jawaban di Kolom Kanan
                    rightCards.forEach(rc => {
                        rc.addEventListener('click', () => {
                            if (selectedPremiseIdx === null) return;
                            const lIdx = selectedPremiseIdx;
                            const rDot = rc.querySelector('[data-dot-side="right"]');
                            if (!rDot) return;
                            const rIdx = Number(rDot.dataset.dotIdx);
                            const targetVal = rc.dataset.targetVal;
                            const lDot = container.querySelector(`[data-dot-side="left"][data-dot-idx="${lIdx}"]`);
                            const color = lDot?.dataset.color || '#1d4ed8';

                            // Pasangkan atau pindahkan sambungan
                            connections[lIdx] = { targetIdx: rIdx, targetVal, color };
                            const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                            if (hiddenInp) hiddenInp.value = targetVal;

                            // Reset seleksi
                            selectedPremiseIdx = null;
                            leftCards.forEach(c => c.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                            leftDots.forEach(d => d.classList.remove('selected'));
                            redrawLines();
                        });
                    });

                    // Putuskan / Batalkan Sambungan Satuan (Inline Action)
                    container.querySelectorAll('[data-disconnect-left]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const lIdx = Number(btn.dataset.disconnectLeft);
                            delete connections[lIdx];
                            const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                            if (hiddenInp) hiddenInp.value = '';

                            if (selectedPremiseIdx === lIdx) {
                                selectedPremiseIdx = null;
                                leftCards.forEach(c => c.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                                leftDots.forEach(d => d.classList.remove('selected'));
                            }
                            redrawLines();
                        });
                    });

                    // Reset Semua Garis
                    resetBtn?.addEventListener('click', () => {
                        Object.keys(connections).forEach(k => delete connections[k]);
                        leftDots.forEach(ld => {
                            const lIdx = Number(ld.dataset.dotIdx);
                            const hiddenInp = document.getElementById(`hidden-match-${qIdx}-${lIdx}`);
                            if (hiddenInp) hiddenInp.value = '';
                        });
                        selectedPremiseIdx = null;
                        leftCards.forEach(c => c.classList.remove('ring-2', 'ring-brand', 'border-brand'));
                        leftDots.forEach(d => d.classList.remove('selected'));
                        redrawLines();
                    });

                    container.addEventListener('scroll', () => {
                        requestAnimationFrame(redrawLines);
                    }, { passive: true });

                    requestAnimationFrame(redrawLines);
                });

                function redrawCurrentMatchingLines() {
                    const curCard = cards[currentStep];
                    if (!curCard) return;
                    const mContainer = curCard.querySelector('.match-canvas-container');
                    if (mContainer) {
                        const qIdxMatch = mContainer.id.match(/\d+/);
                        if (qIdxMatch && matchingQuestions[qIdxMatch[0]]) {
                            requestAnimationFrame(() => matchingQuestions[qIdxMatch[0]].redraw());
                        }
                    }
                }

                window.addEventListener('resize', () => {
                    redrawCurrentMatchingLines();
                });

                // ==========================================
                // 7. CODE TEXTAREA CHARACTERS COUNTER & RUNNER
                // ==========================================
                document.querySelectorAll('[data-code-textarea]').forEach(textarea => {
                    const idx = textarea.dataset.codeTextarea;
                    const counter = document.getElementById(`code-chars-${idx}`);
                    const updateChars = () => {
                        if (counter) counter.textContent = `${textarea.value.length} karakter`;
                    };
                    textarea.addEventListener('input', updateChars);
                    updateChars();
                });
                // Terminal Toggle, Close & Clear Handlers
                document.querySelectorAll('[data-terminal-toggle-btn]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.terminalToggleBtn;
                        const wrapper = document.getElementById(`terminal-wrapper-${qIdx}`);
                        if (wrapper) {
                            wrapper.hidden = !wrapper.hidden;
                        }
                    });
                });

                document.querySelectorAll('[data-terminal-close-btn], [data-terminal-close-dot]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.terminalCloseBtn || btn.dataset.terminalCloseDot;
                        const wrapper = document.getElementById(`terminal-wrapper-${qIdx}`);
                        if (wrapper) {
                            wrapper.hidden = true;
                        }
                    });
                });

                document.querySelectorAll('[data-clear-terminal]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.clearTerminal;
                        const terminal = document.getElementById(`terminal-output-${qIdx}`);
                        if (terminal) {
                            terminal.innerHTML = `<div class="text-slate-500 font-mono">sale@sandbox:~/soal-${Number(qIdx)+1}$ </div>`;
                        }
                    });
                });

                document.querySelectorAll('[data-run-code-btn]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const qIdx = btn.dataset.runCodeBtn || btn.closest('[data-exam-card]')?.dataset.examCard || '0';
                        const wrapper = document.getElementById(`terminal-wrapper-${qIdx}`);
                        if (wrapper && wrapper.hidden) {
                            wrapper.hidden = false;
                        }
                        const card = btn.closest('[data-exam-card]');
                        const textarea = card?.querySelector('textarea');
                        const terminal = document.getElementById(`terminal-output-${qIdx}`);
                        if (!terminal || !textarea) return;

                        terminal.innerHTML = `
                            <div class="text-slate-400 font-mono">sale@sandbox:~/soal-${Number(qIdx)+1}$ python3 -u solution.py</div>
                            <div class="text-amber-400 font-mono">Menjalankan kompilasi &amp; pengujian kode...</div>
                        `;

                        setTimeout(() => {
                            terminal.innerHTML = `
                                <div class="text-slate-400 font-mono">sale@sandbox:~/soal-${Number(qIdx)+1}$ python3 -u solution.py</div>
                                <div class="text-slate-300 font-mono">>>> bst = BST()</div>
                                <div class="text-slate-300 font-mono">>>> bst.insert(15) # Simpul root berhasil dibuat</div>
                                <div class="text-slate-300 font-mono">>>> bst.insert(10) # Cabang kiri tervalidasi (10 < 15)</div>
                                <div class="text-slate-300 font-mono">>>> bst.insert(20) # Cabang kanan tervalidasi (20 > 15)</div>
                                <div class="text-emerald-400 font-semibold font-mono mt-1">✓ Seluruh pengujian berhasil lolos (Exit code: 0).</div>
                            `;
                        }, 400);
                    });
                });

                // ==========================================
                // 8. ESSAY WORD COUNTER
                // ==========================================
                document.querySelectorAll('[data-essay-input]').forEach(textarea => {
                    const idx = textarea.dataset.essayInput;
                    const counter = document.getElementById(`essay-word-count-${idx}`);
                    const updateWords = () => {
                        const words = textarea.value.trim().split(/\s+/).filter(Boolean).length;
                        if (counter) counter.textContent = `${words} kata`;
                    };
                    textarea.addEventListener('input', updateWords);
                    updateWords();
                });

                // Initial Activation
                setStep(0);
            });
        </script>

    @endif

</body>
</html>
