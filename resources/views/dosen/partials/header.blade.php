<header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
            <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ request()->routeIs('dosen.penilaian.rekap', 'dosen.penilaian.cpmk') ? route('dosen.rekap.index') : route('dosen.penilaian.index') }}">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                <span>{{ request()->routeIs('dosen.penilaian.rekap', 'dosen.penilaian.cpmk') ? 'Rekap Nilai' : 'Penilaian' }}</span>
            </a>
            <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-semibold text-slate-800" aria-current="page">
                {{ $section->mataKuliah->code }}-{{ $section->section_code }}
            </span>
        </nav>
        <h1 class="page-heading">{{ $section->mataKuliah->name }}</h1>
        <div class="flex flex-wrap items-center gap-2 text-xs text-muted mt-1">
            <span>{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
            <span class="h-3 w-px bg-line"></span>
            <span>{{ $section->semester->name }}</span>
            <span class="h-3 w-px bg-line"></span>
            <span>Pengampu: {{ $section->dosen->name }}</span>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 text-xs text-muted">
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span><strong class="font-medium text-ink">{{ $section->students_count }}</strong> Mahasiswa</span>
        </span>
        <span class="h-3 w-px bg-line"></span>
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            <span><strong class="font-medium text-ink">{{ $section->assessments_count }}</strong> Asesmen</span>
        </span>
        <span class="h-3 w-px bg-line"></span>
        <span class="inline-flex items-center gap-1.5 text-brand font-medium">
            <svg class="h-3.5 w-3.5 text-brand shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ $section->cpmk_used_count }} CPMK</span>
        </span>
    </div>
</header>
