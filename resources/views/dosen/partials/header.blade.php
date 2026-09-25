@php
    $isOnMatriks = request()->routeIs('dosen.penilaian.matriks');
    $hideNav = request()->routeIs('dosen.penilaian.asesmen', 'dosen.penilaian.rekap', 'dosen.penilaian.cpmk');
    $mainTabs = $isOnMatriks ? [
        'dosen.penilaian.matriks' => 'Matriks Penilaian',
        'dosen.penilaian.asesmen' => 'Input Nilai Asesmen',
    ] : [];
@endphp

<header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            @if(request()->routeIs('dosen.penilaian.rekap', 'dosen.penilaian.cpmk'))
                <a href="{{ route('dosen.rekap.index') }}" class="hover:text-brand">Rekap Nilai</a>
            @else
                <a href="{{ route('dosen.penilaian.index') }}" class="hover:text-brand">Penilaian</a>
            @endif
            <span>/</span>
            <span class="text-ink font-semibold">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
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

@if(!$hideNav && !empty($mainTabs))
<nav class="flex items-center gap-1 sm:gap-2 overflow-x-auto overflow-y-hidden border-b border-line/80 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Tab penilaian kelas">
    @foreach($mainTabs as $route => $label)
        <a href="{{ route($route, $section->id) }}"
           class="px-3.5 py-2.5 text-sm font-semibold border-b-2 -mb-px whitespace-nowrap transition-colors {{ request()->routeIs($route) ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
@endif
