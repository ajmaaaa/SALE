@extends('layouts.mahasiswa')

@section('title', 'Tugas & Kuis | SALE')
@section('header', 'Tugas & Kuis')

@section('content')
<div class="space-y-6">
    <header>
        <h1 class="page-heading">Tugas &amp; Kuis</h1>
        <p class="page-description">Daftar evaluasi pembelajaran, penugasan, dan kuis dari seluruh kelas.</p>
    </header>

    {{-- Tab Navigasi --}}
    <nav aria-label="Tampilan tugas" class="flex gap-6 text-sm font-semibold border-b border-line/60">
        <a href="{{ route('mahasiswa.assignment.index') }}" class="pb-3 {{ request('tab') !== 'nilai' ? 'border-b-2 border-brand text-brand' : 'text-muted hover:text-ink' }}">Semua pekerjaan</a>
        <a href="{{ route('mahasiswa.assignment.index', ['tab'=>'nilai']) }}" class="pb-3 {{ request('tab') === 'nilai' ? 'border-b-2 border-brand text-brand' : 'text-muted hover:text-ink' }}">Nilai</a>
    </nav>

    {{-- Filter Form --}}
    <form class="flex flex-wrap items-center gap-3" method="get">
        <input type="hidden" name="tab" value="{{ request('tab') }}">
        <label class="sr-only" for="q">Cari tugas</label>
        <input id="q" name="q" class="field sm:w-64" value="{{ request('q') }}" placeholder="Cari tugas atau kuis">
        <label class="sr-only" for="course">Course</label>
        <select id="course" name="course" class="field sm:w-60">
            <option value="">Semua mata kuliah</option>
            @foreach($courses as $course)
                <option value="{{ $course['id'] }}" @selected(request('course') == $course['id'])>{{ $course['code'] }} - {{ $course['title'] }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="type">Jenis</label>
        <select id="type" name="type" class="field sm:w-44">
            <option value="">Semua jenis</option>
            @foreach(['tugas'=>'Tugas','coding'=>'Tugas coding','kuis'=>'Kuis'] as $value=>$label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="button-secondary text-xs">Terapkan Filter</button>
    </form>

    @if(request('tab') === 'nilai')
        <div class="rounded-lg bg-emerald-50/80 border border-emerald-200 p-3 text-xs text-emerald-950 font-medium flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>Menampilkan semua tugas dan kuis yang telah dinilai.</span>
        </div>
    @endif

    {{-- Clean Assignment List (Clickable rows) --}}
    <section class="surface overflow-hidden divide-y divide-line/40" aria-label="Daftar Penugasan">
        @forelse($items as $item)
            @php
                $dbScore = $studentScores[$item['id']] ?? null;
                $hasDbGrade = $dbScore && $dbScore->score !== null;
                $sessionGrade = session('learning.grades.'.$item['id']) ?? session('academic.item_grades.'.$item['id'].'.1');
                $hasSessionGrade = $sessionGrade !== null;
                $isGraded = $hasDbGrade || $hasSessionGrade;
                $scoreValue = $hasDbGrade ? (float)$dbScore->score : ($hasSessionGrade ? (is_array($sessionGrade) ? array_sum($sessionGrade['points'] ?? []) : (float)$sessionGrade) : null);
                $isSubmitted = session('learning.submissions.'.$item['id']) || $isGraded;
                $targetUrl = ($item['type'] === 'coding') ? route('mahasiswa.assignment.code', $item['id']) : route('mahasiswa.course.item', [$item['course'], $item['id']]);
                $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
            @endphp
            <a href="{{ $targetUrl }}" class="group flex flex-col sm:flex-row sm:items-start justify-between gap-4 p-5 hover:bg-canvas transition">
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h2>
                    <p class="mt-1 text-xs text-muted flex flex-wrap items-center gap-2">
                        <span class="font-medium text-ink">{{ $courses[$item['course']]['code'] ?? '' }} - {{ $courses[$item['course']]['title'] ?? '' }}</span>
                        <span>({{ $item['module'] ?? '' }}, {{ \App\Support\LearningPreview::labels()[$item['type']] ?? $item['type'] }})</span>
                    </p>
                </div>
                <div class="shrink-0 flex flex-col sm:items-end gap-1 text-xs">
                    @if($isGraded)
                        <span class="text-sm font-bold text-emerald-600">
                            {{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }}
                        </span>
                    @else
                        @if($isSubmitted)
                            <span class="text-xs font-semibold text-emerald-600">
                                Sudah dikumpulkan
                            </span>
                        @elseif($isPast)
                            <span class="text-xs font-semibold text-rose-600">
                                Terlambat
                            </span>
                        @else
                            <span class="text-xs font-semibold text-rose-600">
                                Belum dikumpulkan
                            </span>
                        @endif
                        <p class="text-[11px] text-muted">
                            {{ $item['due'] ? 'Tenggat ' . \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tanpa batas tenggat' }}
                        </p>
                    @endif
                </div>
            </a>
        @empty
            <div class="p-8 text-center text-xs text-muted">
                {{ request('tab') === 'nilai' ? 'Belum ada tugas atau kuis yang selesai dinilai.' : 'Tidak ada penugasan yang sesuai dengan filter yang dipilih.' }}
            </div>
        @endforelse
    </section>
</div>
@endsection
