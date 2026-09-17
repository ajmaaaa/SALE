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
        <a href="{{ route('mahasiswa.assignment.index', ['tab'=>'nilai']) }}" class="pb-3 {{ request('tab') === 'nilai' ? 'border-b-2 border-brand text-brand' : 'text-muted hover:text-ink' }}">Nilai &amp; umpan balik</a>
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
                <option value="{{ $course['id'] }}" @selected(request('course') == $course['id'])>{{ $course['code'] }} · {{ $course['title'] }}</option>
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
        <p class="text-xs text-muted">Nilai dan umpan balik tampil setelah dosen mengevaluasi pengumpulan Anda.</p>
    @endif

    {{-- Clean Assignment List (Clickable rows) --}}
    <section class="surface overflow-hidden divide-y divide-line/40" aria-label="Daftar Penugasan">
        @forelse($items as $item)
            @php
                $isSubmitted = session('learning.submissions.'.$item['id']);
                $targetUrl = ($item['type'] === 'coding' && empty($item['questions'])) ? route('mahasiswa.assignment.code', $item['id']) : route('mahasiswa.course.item', [$item['course'], $item['id']]);
            @endphp
            <a href="{{ $targetUrl }}" class="group flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 hover:bg-canvas transition">
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-semibold text-ink group-hover:text-brand transition">{{ $item['title'] }}</h2>
                    <p class="mt-1 text-xs text-muted">
                        <span class="font-medium text-ink">{{ $courses[$item['course']]['code'] }}</span>
                        <span>·</span>
                        <span>{{ $courses[$item['course']]['title'] }}</span>
                        <span>·</span>
                        <span>{{ $item['module'] }}</span>
                        <span>·</span>
                        <span>{{ \App\Support\LearningPreview::labels()[$item['type']] }}</span>
                    </p>
                </div>
                @php
                    $isPast = !empty($item['due']) && \Carbon\Carbon::parse($item['due'])->isPast();
                @endphp
                <div class="shrink-0 flex flex-col sm:items-end gap-1 text-xs">
                    @if($isSubmitted)
                        <span class="text-xs font-semibold text-emerald-600">
                            Sudah dikumpulkan
                        </span>
                    @else
                        <span class="text-xs font-semibold text-rose-600">
                            Belum dikumpulkan
                        </span>
                    @endif
                    <p class="text-[11px] text-muted">
                        {{ $item['due'] ? 'Tenggat ' . \Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Tanpa batas tenggat' }}
                    </p>
                </div>
            </a>
        @empty
            <div class="p-8 text-center text-xs text-muted">
                Tidak ada penugasan yang sesuai dengan filter yang dipilih.
            </div>
        @endforelse
    </section>
</div>
@endsection
