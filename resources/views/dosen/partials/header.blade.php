@php
    $tabs = [
        'dosen.penilaian.rekap' => 'Rekap Keseluruhan',
        'dosen.penilaian.asesmen' => 'Asesmen',
        'dosen.penilaian.matriks' => 'Matriks Penilaian',
        'dosen.penilaian.cpmk' => 'CPMK',
        'dosen.penilaian.cpl' => 'CPL',
        'dosen.penilaian.pengaturan' => 'Pengaturan Penilaian',
    ];
@endphp

<header>
    <nav class="flex items-center gap-2 text-xs text-muted mb-1">
        <a href="{{ route('dosen.penilaian.index') }}" class="hover:text-brand">Daftar Kelas</a>
        <span>/</span>
        <span class="text-ink font-semibold">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</span>
    </nav>
    <h1 class="page-heading">{{ $section->mataKuliah->name }}</h1>
    <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->semester->name }} · Diampu oleh {{ $section->dosen->name }}</p>
</header>

<div class="surface p-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div>
        <p class="text-xs text-muted">Jumlah Mahasiswa</p>
        <p class="mt-1 text-xl font-semibold text-ink">{{ $section->students_count }}</p>
    </div>
    <div>
        <p class="text-xs text-muted">Jumlah Asesmen</p>
        <p class="mt-1 text-xl font-semibold text-ink">{{ $section->assessments_count }}</p>
    </div>
    <div>
        <p class="text-xs text-muted">CPMK Digunakan</p>
        <p class="mt-1 text-xl font-semibold text-ink">{{ $section->cpmk_used_count }}</p>
    </div>
    <div>
        <p class="text-xs text-muted">CPL Digunakan</p>
        <p class="mt-1 text-xl font-semibold text-ink">{{ $section->cpl_used_count }}</p>
    </div>
</div>

<nav class="flex flex-wrap gap-1 border-b border-line/60" aria-label="Tab penilaian kelas">
    @foreach($tabs as $route => $label)
        <a href="{{ route($route, $section->id) }}"
           class="px-3.5 py-2.5 text-sm font-medium border-b-2 -mb-px {{ request()->routeIs($route) ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
