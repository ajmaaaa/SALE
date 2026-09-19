@php
    $mainTabs = [
        'dosen.penilaian.matriks' => '1. Matriks Penilaian',
        'dosen.penilaian.asesmen' => '2. Input Nilai',
        'dosen.penilaian.rekap'   => '3. Rekap CPMK',
        'dosen.penilaian.cpl'     => '4. Rekap CPL',
    ];
@endphp

<header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.index') }}" class="hover:text-brand">Daftar Kelas</a>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
        </nav>
        <h1 class="page-heading">{{ $section->mataKuliah->name }}</h1>
        <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->semester->name }} · Pengampu: {{ $section->dosen->name }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface border border-line px-3 py-1.5 font-medium text-ink shadow-sm">
            <svg class="h-3.5 w-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            {{ $section->students_count }} Mahasiswa
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface border border-line px-3 py-1.5 font-medium text-ink shadow-sm">
            <svg class="h-3.5 w-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            {{ $section->assessments_count }} Komponen Asesmen
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-soft border border-brand/20 px-3 py-1.5 font-semibold text-brand shadow-sm">
            <svg class="h-3.5 w-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $section->cpmk_used_count }} CPMK
        </span>
    </div>
</header>

<nav class="flex items-center gap-1 sm:gap-2 overflow-x-auto overflow-y-hidden border-b border-line/80 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Tab penilaian kelas">
    @php
        $isMatrixValid = method_exists($section, 'isMatrixValid') ? $section->isMatrixValid() : true;
    @endphp
    @foreach($mainTabs as $route => $label)
        <a href="{{ route($route, $section->id) }}"
           @if($route === 'dosen.penilaian.asesmen' && ! $isMatrixValid) title="Bobot matriks belum tepat 100%. Selesaikan Langkah 1 terlebih dahulu." @endif
           class="px-3.5 py-2.5 text-sm font-semibold border-b-2 -mb-px whitespace-nowrap transition-colors inline-flex items-center gap-1.5 {{ request()->routeIs($route) || ($route === 'dosen.penilaian.rekap' && request()->routeIs('dosen.penilaian.cpmk')) ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            <span>{{ $label }}</span>
            @if($route === 'dosen.penilaian.asesmen' && ! $isMatrixValid)
                <svg class="h-3.5 w-3.5 text-muted/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            @endif
        </a>
    @endforeach
</nav>
