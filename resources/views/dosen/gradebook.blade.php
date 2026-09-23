@extends('layouts.mahasiswa')

@section('title', 'Rekap Ketercapaian CPMK & Gradebook Course | SALE')
@section('header', 'Rekap Nilai & CPMK Course')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="status font-bold text-brand bg-brand-soft border-brand-soft uppercase text-[11px]">REKAP TINGKAT COURSE</span>
                <span class="status font-semibold text-muted bg-canvas text-[11px]">{{ $course['code'] }}</span>
            </div>
            <h1 class="page-heading">Rekap Nilai &amp; Ketercapaian CPMK Course</h1>
            <p class="page-description">Rekapitulasi ketercapaian CPMK dan evaluasi nilai mahasiswa tingkat Course (Mata Kuliah) berdasarkan pemetaan asesmen dan formula OBE.</p>
        </div>
        <div class="flex flex-wrap gap-2.5 shrink-0">
            <button type="button" onclick="document.getElementById('bulk-score-section').toggleAttribute('hidden')" class="button-secondary">
                Input Nilai Massal
            </button>
        </div>
    </header>

    {{-- Course Selector (Purely per Course) --}}
    <div class="surface p-5 rounded-2xl border border-line/70 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <form class="flex flex-wrap items-center gap-4 flex-1">
            <div class="flex items-center gap-2.5 min-w-[280px] max-w-md flex-1">
                <label for="course" class="text-xs font-semibold text-muted shrink-0">Pilih Mata Kuliah (Course):</label>
                <select name="course" id="course" onchange="this.form.submit()" class="field text-xs font-bold text-brand py-2 shadow-2xs">
                    @foreach(\App\Support\LearningPreview::courses() as $c)
                        <option value="{{ $c['id'] }}" @selected($c['id'] === $course['id'])>
                            {{ $c['code'] }} - {{ $c['title'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <div class="flex items-center gap-2.5 text-xs shrink-0">
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
                    <span class="text-muted font-medium">Total CPMK Course:</span>
                    <span class="font-bold text-brand">{{ count($config['cpmk']) }}</span>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
                    <span class="text-muted font-medium">Komponen:</span>
                    <span class="font-bold text-ink">{{ count($config['components']) }}</span>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
                    <span class="text-muted font-medium">Total Bobot:</span>
                    <span class="font-bold text-ink">{{ array_sum(array_column($config['components'], 'weight')) }}%</span>
                </div>
            </div>
        </div>
    </div>

    @php
        // Agregasi Ketercapaian CPMK tingkat Course (seluruh mahasiswa pada Course ini)
        $cpmkStats = [];
        foreach ($config['cpmk'] as $c) {
            $cpmkStats[$c['code']] = [
                'code' => $c['code'],
                'description' => $c['description'] ?? '',
                'threshold' => $c['threshold'] ?? 60,
                'cpl' => $c['cpl'] ?? '',
                'scores' => [],
                'passed_count' => 0,
                'graded_count' => 0,
            ];
        }

        $studentAttainments = [];
        $totalStudentsPassedCourse = 0;
        $totalStudents = count($students);

        foreach ($students as $stu) {
            $breakdown = \App\Support\AcademicPreview::breakdown($course['id'], $stu['id']);
            $studentAttainments[$stu['id']] = $breakdown;
            if ($breakdown['passed'] === true) {
                $totalStudentsPassedCourse++;
            }

            foreach ($breakdown['cpmk'] as $cCode => $outcome) {
                if (isset($cpmkStats[$cCode])) {
                    if ($outcome['score'] !== null) {
                        $cpmkStats[$cCode]['scores'][] = $outcome['score'];
                        $cpmkStats[$cCode]['graded_count']++;
                        if ($outcome['passed']) {
                            $cpmkStats[$cCode]['passed_count']++;
                        }
                    }
                }
            }
        }

        $overallCoursePassRate = $totalStudents > 0 ? round(($totalStudentsPassedCourse / $totalStudents) * 100, 1) : 0;
    @endphp

    {{-- Section 1: Rekap Ketercapaian CPMK Course (Mata Kuliah) --}}
    @if($componentFilter === '')
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-ink">Rekap Ketercapaian CPMK Course</h2>
                <p class="text-xs text-muted">Statistik menyeluruh capaian pembelajaran mata kuliah {{ $course['title'] }} tanpa sekat kelas paralel.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800">
                    <span>{{ $totalStudentsPassedCourse }}/{{ $totalStudents }} Mahasiswa Tuntas Semua CPMK ({{ $overallCoursePassRate }}%)</span>
                </span>
            </div>
        </div>

        {{-- CPMK Summary Cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($config['cpmk'] as $cpmkItem)
                @php
                    $code = $cpmkItem['code'];
                    $stats = $cpmkStats[$code] ?? null;
                    $scores = $stats['scores'] ?? [];
                    $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;
                    $passCount = $stats['passed_count'] ?? 0;
                    $gradedCount = $stats['graded_count'] ?? 0;
                    $passRate = $gradedCount > 0 ? round(($passCount / $gradedCount) * 100, 1) : 0;
                    $threshold = $stats['threshold'] ?? 60;
                    $isSatisfied = $avgScore !== null && $avgScore >= $threshold;
                @endphp
                <div class="surface p-4 rounded-xl border border-line/60 flex flex-col justify-between hover:border-brand/30 transition shadow-2xs">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-brand bg-brand-soft px-2 py-0.5 rounded">
                                {{ $code }}
                            </span>
                            @if(!empty($cpmkItem['cpl']))
                                <span class="text-[10px] font-semibold text-muted bg-canvas border border-line px-1.5 py-0.5 rounded">
                                    {{ $cpmkItem['cpl'] }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs font-semibold text-ink line-clamp-2">
                            {{ $cpmkItem['description'] }}
                        </p>
                        <p class="text-[11px] text-muted">
                            Ambang Batas Kelulusan: <strong class="text-ink">≥ {{ $threshold }}</strong>
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-line/50 grid grid-cols-2 gap-2 text-center">
                        <div class="p-2 rounded-lg bg-canvas/60">
                            <span class="text-[10px] uppercase font-bold text-muted block">Rata-Rata</span>
                            <span class="text-sm font-extrabold {{ $isSatisfied ? 'text-emerald-700' : 'text-danger' }}">
                                {{ $avgScore !== null ? number_format($avgScore, 1, ',', '.') : '—' }}
                            </span>
                        </div>
                        <div class="p-2 rounded-lg bg-canvas/60">
                            <span class="text-[10px] uppercase font-bold text-muted block">% Tuntas</span>
                            <span class="text-sm font-extrabold text-ink">
                                {{ $gradedCount > 0 ? $passRate.'%' : '—' }}
                            </span>
                            <span class="text-[9px] text-muted block">({{ $passCount }}/{{ $gradedCount }})</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tabel Matriks Ketercapaian CPMK Mahasiswa per Course --}}
        <div class="surface rounded-2xl border border-line/70 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-line/50 bg-canvas/40 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-ink">Matriks Ketercapaian CPMK Mahasiswa Course</h3>
                    <p class="text-xs text-muted">Evaluasi capaian masing-masing mahasiswa terhadap setiap indikator CPMK.</p>
                </div>
                <span class="text-xs text-muted">Total Mahasiswa: <strong class="text-ink">{{ count($students) }}</strong></span>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">NO</th>
                            <th class="whitespace-nowrap">NIM</th>
                            <th>NAMA MAHASISWA</th>
                            @foreach($config['cpmk'] as $c)
                                <th class="text-center min-w-[120px]">
                                    {{ $c['code'] }}
                                    <span class="block font-normal text-[10px] text-muted mt-0.5">(≥{{ $c['threshold'] ?? 60 }})</span>
                                </th>
                            @endforeach
                            <th class="text-center min-w-[170px]">STATUS CPMK COURSE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $idx => $student)
                            @php
                                $attain = $studentAttainments[$student['id']] ?? null;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="text-center font-mono text-xs text-muted">{{ $idx + 1 }}</td>
                                <td class="font-mono text-xs font-bold text-ink whitespace-nowrap">{{ $student['number'] }}</td>
                                <td class="font-semibold text-ink whitespace-nowrap">{{ $student['name'] }}</td>
                                @foreach($config['cpmk'] as $c)
                                    @php
                                        $cOutcome = $attain['cpmk'][$c['code']] ?? null;
                                        $cScore = $cOutcome['score'] ?? null;
                                        $cPassed = $cOutcome['passed'] ?? null;
                                    @endphp
                                    <td class="text-center">
                                        @if($cScore !== null)
                                            <span class="font-mono text-xs font-bold {{ $cPassed ? 'text-emerald-700' : 'text-danger' }}">
                                                {{ number_format($cScore, 1, ',', '.') }}
                                            </span>
                                            <span class="block text-[10px] {{ $cPassed ? 'text-emerald-700' : 'text-danger' }}">
                                                {{ $cPassed ? 'Tercapai' : 'Belum' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-muted font-mono">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center whitespace-nowrap">
                                    @if($attain['passed'] === true)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold text-emerald-800 bg-emerald-100/80">
                                            Memenuhi Seluruh CPMK
                                        </span>
                                    @elseif($attain['passed'] === false)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold text-rose-800 bg-rose-100/80">
                                            Belum Memenuhi CPMK
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold text-muted bg-canvas">
                                            Menunggu Penilaian
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($config['cpmk']) + 4 }}" class="p-8 text-center text-xs text-muted">
                                    Belum ada mahasiswa terdaftar di mata kuliah ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

    {{-- Detail Telusuri Penilaian & Rincian Soal Asesmen --}}
    @include('dosen.partials.gradebook-detail')

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

    {{-- Main Gradebook Table (Rekapitulasi Nilai Akhir & Komponen) --}}
    @if($componentFilter === '')
    <form method="post" action="{{ route('dosen.scores.save', $course['id']) }}">
        @csrf
        <div class="surface rounded-2xl border border-line/70 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-line/50 flex flex-wrap items-center justify-between gap-3 bg-canvas/30">
                <div>
                    <h3 class="text-base font-bold text-ink">Rekapitulasi Nilai &amp; Indeks Akhir</h3>
                    <p class="text-xs text-muted">Seluruh akumulasi nilai komponen terhitung secara otomatis berdasarkan bobot RPS.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-muted">Jumlah Mahasiswa: <strong class="text-ink">{{ count($students) }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">NIM</th>
                            <th>MAHASISWA</th>
                            @foreach($config['components'] as $component)
                                <th class="text-center min-w-[100px]">
                                    {{ strtoupper($component['name']) }}
                                    <span class="mt-0.5 block text-[11px] font-normal text-muted">Bobot {{ $component['weight'] }}%</span>
                                </th>
                            @endforeach
                            <th class="text-center min-w-[110px]">NILAI / 100</th>
                            <th class="text-center">INDEKS</th>
                            <th class="min-w-[220px]">Ketercapaian CPMK</th>
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
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="font-mono text-xs font-bold text-ink whitespace-nowrap">
                                    {{ $student['number'] }}
                                </td>
                                <td class="font-bold text-ink whitespace-nowrap">
                                    {{ $student['name'] }}
                                </td>
                                @foreach($config['components'] as $component)
                                    <td class="text-center p-2">
                                        <input aria-label="{{ $student['name'].' '.$component['name'] }}"
                                            class="field text-center font-mono text-xs py-1.5 px-2.5 max-w-[85px] mx-auto block rounded-lg shadow-2xs focus:border-brand"
                                            type="number" min="0" max="100" step="0.01"
                                            name="scores[{{ $student['id'] }}][{{ $component['code'] }}]"
                                            value="{{ $result['scores'][$component['code']] ?? '' }}"
                                            placeholder="—">
                                    </td>
                                @endforeach
                                <td class="text-center whitespace-nowrap">
                                    <span class="text-sm font-extrabold text-ink">{{ $result['average'] !== null ? number_format($result['average'], 1, ',', '.') : '—' }}</span>
                                    <span class="block text-[11px] font-medium text-muted">
                                        {{ $result['complete'] ? 'Nilai akhir' : 'Sementara' }}
                                    </span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-md bg-brand-soft text-xs font-bold text-brand shadow-2xs">
                                        {{ $letter }}
                                    </span>
                                </td>
                                <td>
                                    @php($attainment = $studentAttainments[$student['id']] ?? \App\Support\AcademicPreview::breakdown($course['id'], $student['id']))
                                    <details class="text-xs">
                                        <summary class="cursor-pointer font-semibold {{ $attainment['passed'] === false ? 'text-danger' : 'text-brand' }} hover:underline">
                                            {{ $attainment['passed'] === null ? 'Menunggu penilaian' : ($attainment['passed'] ? 'Memenuhi seluruh CPMK' : 'Belum memenuhi CPMK') }}
                                        </summary>
                                        <div class="mt-3 p-3 rounded-xl bg-canvas/60 border border-line/50 space-y-2.5">
                                            @foreach($attainment['cpmk'] as $outcome)
                                                <div class="flex items-center justify-between text-[11px] border-b border-line/40 pb-1.5 last:border-0 last:pb-0">
                                                    <div>
                                                        <span class="font-bold text-ink">{{ $outcome['code'] }}</span>
                                                        <span class="text-muted ml-1">(Batas {{ $outcome['threshold'] }})</span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="font-semibold text-ink">{{ $outcome['score'] === null ? 'Belum lengkap' : number_format($outcome['score'], 1, ',', '.').' / 100' }}</span>
                                                        <span class="ml-1 text-[10px] font-bold {{ $outcome['passed'] ? 'text-emerald-700' : ($outcome['passed'] === false ? 'text-danger' : 'text-muted') }}">
                                                            ({{ $outcome['passed'] === null ? 'Menunggu' : ($outcome['passed'] ? 'Tercapai' : 'Belum tercapai') }})
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($config['components']) + 5 }}" class="p-8 text-center text-xs text-muted">
                                    Belum ada mahasiswa terdaftar di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-4 p-1">
            <p class="text-xs text-muted leading-relaxed max-w-xl">
                Nilai sementara dihitung dari bobot komponen yang sudah dinilai. Nilai komponen manual tidak menjadi bukti ketercapaian CPMK. Buka filter penilaian untuk melihat rincian soal.
            </p>
            <div class="flex items-center gap-3">
                <a href="{{ route('dosen.penilaian.index') }}" class="quiet-link text-xs">
                    Tinjau pengumpulan tugas
                </a>
                <button type="submit" class="button-primary text-xs py-2.5 px-5 font-bold shadow-2xs">
                    Simpan Semua Nilai
                </button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

