@extends('layouts.mahasiswa')

@section('title', 'Penilaian Tugas & OBE | SALE')
@section('header', 'Penilaian Tugas & OBE')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('dosen.dashboard') }}" class="hover:text-brand">Dashboard</a>
                <span>/</span>
                <span class="text-ink font-semibold">Penilaian Tugas &amp; OBE</span>
            </nav>
            <h1 class="page-heading">Penilaian Tugas &amp; Capaian OBE</h1>
            <p class="page-description">Kelola penilaian tugas, kuis, UTS, UAS, serta pantau pemenuhan standar mutu OBE (CPMK &amp; CPL) pada setiap kelas yang Anda ampu.</p>
        </div>
        <div class="flex flex-wrap gap-2.5 shrink-0">
            <a class="button-secondary text-xs" href="{{ route('dosen.gradebook') }}">Rekap Nilai Kelas</a>
            <a class="button-secondary text-xs" href="{{ route('dosen.grades', ['room' => 1, 'course' => 1, 'type' => 'uts']) }}">Ruang Evaluasi Tugas</a>
        </div>
    </header>

    @php
        $totalStudents = $sections->sum('students_count');
        $totalAssessments = $sections->sum('assessments_count');
        $completedSections = $sections->filter(fn($s) => $s->grading_progress >= 100)->count();
    @endphp

    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Kelas yang Diampu</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $sections->count() }} <span class="text-xs font-normal text-muted">Kelas Aktif</span></p>
        </div>
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total Mahasiswa</p>
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $totalStudents }} <span class="text-xs font-normal text-muted">Orang</span></p>
        </div>
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Total Asesmen &amp; Tugas</p>
            <p class="mt-1.5 text-2xl font-bold text-brand">{{ $totalAssessments }} <span class="text-xs font-normal text-muted">Asesmen Terjadwal</span></p>
        </div>
        <div class="surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">Status Penilaian Selesai</p>
            <p class="mt-1.5 text-2xl font-bold text-emerald-600">{{ $completedSections }} <span class="text-xs font-normal text-muted">/ {{ $sections->count() }} Kelas</span></p>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="surface p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
            <label for="class-search" class="text-xs font-semibold text-muted shrink-0">Cari Kelas / Mata Kuliah:</label>
            <input type="text" id="class-search" onkeyup="filterClassCards()" placeholder="Ketik kode atau nama..." class="field text-xs font-semibold max-w-xs">
            <label for="status-filter" class="text-xs font-semibold text-muted shrink-0">Status:</label>
            <select id="status-filter" onchange="filterClassCards()" class="field text-xs font-semibold w-48">
                <option value="all">Semua Status</option>
                <option value="lengkap">Penilaian Lengkap</option>
                <option value="berjalan">Sedang Berjalan</option>
                <option value="belum">Belum Dinilai</option>
            </select>
        </div>
        <div class="text-xs text-muted" id="class-count-label">
            Menampilkan <strong class="text-ink">{{ $sections->count() }}</strong> kelas aktif semester ini
        </div>
    </div>

    @if($sections->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada kelas yang diampu</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Kelas yang ditugaskan kepada Anda oleh Admin Prodi akan muncul di sini beserta status penilaian tugas dan ketercapaian OBE-nya.
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" id="class-cards-container">
            @foreach($sections as $section)
                @php
                    $progress = $section->grading_progress;
                    $statusType = match(true) {
                        $progress === null => 'belum',
                        $progress >= 100 => 'lengkap',
                        $progress > 0 => 'berjalan',
                        default => 'belum',
                    };
                    $statusLabel = match(true) {
                        $progress === null => 'Belum ada asesmen',
                        $progress >= 100 => 'Penilaian lengkap',
                        $progress > 0 => 'Sedang berjalan',
                        default => 'Belum dinilai',
                    };
                    $statusClasses = match(true) {
                        $progress === null => 'bg-canvas text-muted',
                        $progress >= 100 => 'bg-brand-soft text-brand font-semibold',
                        $progress > 0 => 'bg-amber-50 text-amber-700 font-semibold',
                        default => 'bg-canvas text-muted',
                    };
                @endphp
                <div class="surface p-5 flex flex-col justify-between gap-4 class-card" 
                     data-name="{{ strtolower($section->mataKuliah->name) }}" 
                     data-code="{{ strtolower($section->mataKuliah->code) }}" 
                     data-status="{{ $statusType }}">
                    @php
                        $currentUserId = auth()->id() ?? (session('auth_user.id') ?? null);
                        $isKetua = $section->dosen_id == $currentUserId;
                        $isWakil = $section->dosen_pendamping_id == $currentUserId;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-brand">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
                            <div class="flex items-center gap-1.5">
                                @if($isKetua)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Dosen Ketua</span>
                                @elseif($isWakil)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">Dosen Wakil</span>
                                @endif
                                <span class="status {{ $statusClasses }} text-[11px]">{{ $statusLabel }}</span>
                            </div>
                        </div>
                        <h2 class="mt-2 text-base font-bold text-ink leading-snug">{{ $section->mataKuliah->name }}</h2>
                        <p class="mt-0.5 text-xs text-muted">{{ $section->semester->name ?? 'Semester Aktif' }} &middot; Kelas {{ $section->display_code }}</p>
                    </div>

                    <!-- Tim Pengajar (Ketua & Wakil) -->
                    <div class="text-xs bg-canvas/30 p-2.5 rounded-lg border border-line/50 space-y-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-muted">Dosen Ketua:</span>
                            <span class="font-semibold text-ink">{{ $section->dosen?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-muted">Dosen Wakil:</span>
                            <span class="font-semibold text-ink">{{ $section->dosenPendamping?->name ?? '—' }}</span>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 text-xs bg-canvas/40 p-3 rounded-lg border border-line/60">
                        <div>
                            <dt class="text-muted text-[11px]">Mahasiswa Terdaftar</dt>
                            <dd class="mt-0.5 font-bold text-ink">{{ $section->students_count }} orang</dd>
                        </div>
                        <div>
                            <dt class="text-muted text-[11px]">Asesmen &amp; Tugas</dt>
                            <dd class="mt-0.5 font-bold text-ink">{{ $section->assessments_count }} dibuat</dd>
                        </div>
                    </dl>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-muted text-[11px] font-medium">Kelengkapan Penilaian:</span>
                            <span class="font-bold text-ink">{{ $progress ?? 0 }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden" role="progressbar" aria-valuenow="{{ $progress ?? 0 }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $progress ?? 0 }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-line/60">
                        <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-center text-xs py-2" title="Kelola Asesmen, Rubrik & Input Nilai">
                            Tugas &amp; Asesmen
                        </a>
                        <a href="{{ route('dosen.penilaian.rekap', $section->id) }}" class="button-primary text-center text-xs py-2" title="Rekap Nilai Akhir, CPMK, CPL & Ekspor">
                            Rekap &amp; OBE
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    function filterClassCards() {
        const query = document.getElementById('class-search')?.value.toLowerCase().trim() ?? '';
        const status = document.getElementById('status-filter')?.value ?? 'all';
        let visibleCount = 0;
        const cards = document.querySelectorAll('.class-card');

        cards.forEach(card => {
            const name = card.dataset.name || '';
            const code = card.dataset.code || '';
            const cardStatus = card.dataset.status || '';

            const matchQuery = !query || name.includes(query) || code.includes(query);
            const matchStatus = status === 'all' || cardStatus === status;

            if (matchQuery && matchStatus) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const label = document.getElementById('class-count-label');
        if (label) {
            label.innerHTML = `Menampilkan <strong class="text-ink">${visibleCount}</strong> dari <strong class="text-ink">${cards.length}</strong> kelas`;
        }
    }
</script>
@endsection

