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
        <div class="relative w-full sm:w-64">
            <input id="q" name="q" class="field w-full" value="{{ request('q') }}" placeholder="Cari tugas atau kuis..." autocomplete="off">
        </div>
        <label class="sr-only" for="course">Course</label>
        <select id="course" name="course" class="field sm:w-60" onchange="this.form.submit()">
            <option value="">Semua mata kuliah</option>
            @foreach($courses as $course)
                <option value="{{ $course['id'] }}" @selected(request('course') == $course['id'])>{{ $course['code'] }} - {{ $course['title'] }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="type">Jenis</label>
        <select id="type" name="type" class="field sm:w-44" onchange="this.form.submit()">
            <option value="">Semua jenis</option>
            @foreach(['tugas'=>'Tugas','kuis'=>'Kuis','uts'=>'UTS','uas'=>'UAS','lainnya'=>'Lainnya'] as $value=>$label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    {{-- Clean Assignment List (Clickable rows) --}}
    <section id="assignment-list-container" class="surface overflow-hidden divide-y divide-line/40" aria-label="Daftar Penugasan">
        @forelse($items as $item)
            @php
                $dbScore = $studentScores[$item['id']] ?? null;
                $hasDbGrade = $dbScore && $dbScore->score !== null;
                $isGraded = $hasDbGrade;
                $scoreValue = $hasDbGrade ? (float)$dbScore->score : null;
                $isSubmitted = in_array($item['id'], $submittedAssessmentIds ?? [], true) || $isGraded;
                $isCoding = !empty($item['is_coding'])
                    || ($item['type'] === 'coding')
                    || (($item['task_mode'] ?? null) === 'coding')
                    || (($item['question_type'] ?? null) === 'coding')
                    || !empty($item['coding_steps']);
                $targetUrl = route('mahasiswa.course.item', [$item['course'], $item['id']]);
                $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
            @endphp
            <div class="assignment-item-row group flex items-center justify-between gap-4 px-5 py-4 hover:bg-canvas transition" data-search="{{ mb_strtolower($item['title'] . ' ' . ($courses[$item['course']]['code'] ?? '') . ' ' . ($courses[$item['course']]['title'] ?? '')) }}">
                <a href="{{ $targetUrl }}" class="flex items-start gap-4 min-w-0 flex-1">
                    <span class="shrink-0 mt-0.5 {{ $isSubmitted ? 'text-emerald-600' : 'text-brand' }}">
                        @if($item['type'] === 'kuis')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.3 2c-1 .8-1.8 1.2-1.8 2.5M12 17h.01"/></svg>
                        @elseif($isCoding)
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-semibold text-ink group-hover:text-brand transition leading-snug">{{ $item['title'] }}</h4>
                        <div class="mt-1 text-xs text-muted flex flex-wrap items-center gap-2">
                            <span>{{ $isCoding ? 'Tugas coding' : (\App\Support\LearningPreview::labels()[$item['type']] ?? ucfirst($item['type'])) }}</span>
                            <span class="h-2.5 w-px bg-line"></span>
                            <span class="font-medium text-slate-700">{{ $courses[$item['course']]['code'] ?? '' }} - {{ $courses[$item['course']]['title'] ?? '' }}</span>
                            @if(!empty($item['published_at_formatted']))
                                <span class="h-2.5 w-px bg-line"></span>
                                <span>Diterbitkan {{ $item['published_at_formatted'] }}</span>
                            @endif
                            <span class="h-2.5 w-px bg-line"></span>
                            <span class="{{ $isPast && !$isSubmitted ? 'text-rose-600 font-semibold' : '' }}">{{ $isPast ? 'Terlambat' : ($item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tugas perkuliahan') }}</span>
                        </div>
                    </div>
                </a>

                <div class="flex items-center shrink-0 self-center">
                    <a href="{{ $targetUrl }}" class="text-xs font-semibold leading-snug whitespace-nowrap hover:underline @if($isGraded) text-emerald-600 font-bold text-sm @elseif($isSubmitted) text-emerald-700 @elseif($isPast) text-rose-600 @else text-brand @endif">
                        @if($isGraded)
                            {{ number_format($scoreValue, 0) }}/{{ $item['points'] ?? 100 }}
                        @elseif($isSubmitted)
                            Sudah dikerjakan
                        @elseif($isPast)
                            Terlambat
                        @else
                            Kerjakan
                        @endif
                    </a>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-xs text-muted">
                {{ request('tab') === 'nilai' ? 'Belum ada tugas atau kuis yang selesai dinilai.' : 'Tidak ada penugasan yang sesuai dengan filter yang dipilih.' }}
            </div>
        @endforelse
        <div id="no-assignment-results" class="p-8 text-center text-xs text-muted" style="display: none;"></div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('q');
        const container = document.getElementById('assignment-list-container');
        const emptyNotice = document.getElementById('no-assignment-results');
        if (!searchInput || !container) return;

        function filterAssignments() {
            const query = searchInput.value.trim().toLowerCase();
            const rows = container.querySelectorAll('.assignment-item-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = (row.getAttribute('data-search') || row.textContent || '').toLowerCase();
                const matches = !query || text.includes(query);
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            if (emptyNotice) {
                if (visibleCount === 0 && rows.length > 0) {
                    emptyNotice.textContent = ['Tidak', 'ada', 'penugasan', 'yang', 'sesuai.'].join(' ');
                    emptyNotice.style.display = '';
                } else {
                    emptyNotice.style.display = 'none';
                }
            }
        }

        searchInput.form?.addEventListener('submit', function (e) {
            e.preventDefault();
            filterAssignments();
        });

        searchInput.addEventListener('input', filterAssignments);
        searchInput.addEventListener('keyup', filterAssignments);
        searchInput.addEventListener('search', filterAssignments);
    });
</script>
@endsection
