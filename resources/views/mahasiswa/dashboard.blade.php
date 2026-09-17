@extends('layouts.mahasiswa')

@section('title', 'Dashboard | SALE')
@section('header', 'Dashboard')

@section('content')
<style>
    .dashboard-two-col {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 1.5rem;
        align-items: start;
        width: 100%;
    }
    @media (max-width: 860px) {
        .dashboard-two-col {
            grid-template-columns: 1fr;
        }
    }
    .deadline-row {
        display: grid;
        grid-template-columns: 74px minmax(0, 1fr);
        gap: 0.875rem;
        padding: 1rem 1.25rem;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }
    .deadline-row:hover {
        background-color: #f8fafc;
    }
    .discussion-row {
        display: block;
        padding: 1rem 1.25rem;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }
    .discussion-row:hover {
        background-color: #f8fafc;
    }
</style>

<div class="space-y-6">
    {{-- Header: Greeting & Quick Code Join Form --}}
    <header style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%; gap: 1.25rem; flex-wrap: wrap; padding-bottom: 0.25rem;">
        <div style="flex: 1 1 280px; min-width: 0;">
            <p class="mb-1 text-xs sm:text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading" style="margin: 0;">Selamat datang, {{ $student->name ?? 'Mahasiswa' }}.</h1>
            <p class="page-description" style="margin-top: 0.25rem;">Lanjutkan perkuliahan dari materi terakhir atau periksa pekerjaan yang segera berakhir.</p>
        </div>
        <form onsubmit="event.preventDefault(); joinByCode();" style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; margin-top: 0.25rem;">
            <input type="text" id="enroll_code_input" required placeholder="KODE KELAS (MISAL: GG5BKBCY)" class="field text-xs font-mono font-bold uppercase" style="width: 230px; max-width: 100%; padding: 0.625rem 0.875rem; text-transform: uppercase;">
            <button type="submit" class="button-primary text-xs font-semibold" style="white-space: nowrap; padding: 0.625rem 1.25rem; flex-shrink: 0;">
                Gabung
            </button>
        </form>
    </header>

    {{-- Main 2-Column Grid: Left (Mata Kuliah Saya - Format Simple List) | Right (Deadlines & Discussions) --}}
    <div class="dashboard-two-col">
        {{-- Sisi Sebelah Kiri: Mata Kuliah Saya (Gaya disamakan persis dengan Tenggat Terdekat) --}}
        <section aria-labelledby="mata-kuliah-heading">
            <div class="mb-3 flex items-start justify-between gap-2">
                <div>
                    <h2 id="mata-kuliah-heading" class="text-base sm:text-lg font-bold text-ink tracking-tight">Mata Kuliah Saya</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-muted">Pilih mata kuliah untuk melihat kelas dan tugas.</p>
                </div>
                <a href="{{ route('mahasiswa.course.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-[#102f50] hover:text-brand transition p-1" title="Lihat semua mata kuliah">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>

            <div class="rounded-xl bg-white border border-line/70 shadow-2xs divide-y divide-line/60 overflow-hidden">
                @forelse($enrolledSections as $sec)
                    @php
                        $previewCourse = collect(\App\Support\LearningPreview::courses())->first(function($c) use ($sec) {
                            $code = $sec->mataKuliah->code ?? '';
                            $disp = $sec->display_code ?? '';
                            return str_contains($disp, $c['code']) || ($code && str_contains($code, $c['code'])) || strtolower($c['title']) === strtolower($sec->mataKuliah->name);
                        });
                        $courseUrl = $previewCourse ? route('mahasiswa.course.show', $previewCourse['id']) : route('mahasiswa.course.show', $sec->id);
                    @endphp
                    <a href="{{ $courseUrl }}" class="deadline-row group" title="Buka Course {{ $sec->mataKuliah->name }}">
                        <div>
                            <span class="block text-xs font-bold text-ink">{{ $sec->display_code ?? ($sec->mataKuliah->code ?? 'MK') }}</span>
                            <span class="mt-0.5 block text-[11px] text-muted">{{ $sec->mataKuliah->sks ? $sec->mataKuliah->sks . ' SKS' : '3 SKS' }}</span>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-xs sm:text-sm font-semibold leading-snug text-ink group-hover:text-brand transition truncate">
                                {{ $sec->mataKuliah->name }}
                            </span>
                            <span class="mt-0.5 block text-[11px] text-muted truncate">
                                {{ $sec->dosen->name ?? 'Dosen Pengampu' }} &middot; {{ $sec->semester->name ?? 'Semester Ganjil 2026/2027' }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center space-y-2">
                        <div class="h-10 w-10 rounded-full bg-brand-soft text-brand flex items-center justify-center mx-auto text-lg">
                            📚
                        </div>
                        <h3 class="font-semibold text-xs sm:text-sm text-ink">Belum Ada Mata Kuliah yang Diikuti</h3>
                        <p class="text-xs text-muted max-w-xs mx-auto">
                            Masukkan kode kelas dari dosen pada kolom di atas untuk bergabung ke perkuliahan.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Sisi Tengah dan Kanan: Tenggat Terdekat & Diskusi Terbaru (Persis SS Gambar ke-3) --}}
        <div class="space-y-6">
            {{-- Bagian: Tenggat Terdekat --}}
            <aside aria-labelledby="deadline-heading">
                <div class="mb-3 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="deadline-heading" class="text-base sm:text-lg font-bold text-ink tracking-tight">Tenggat terdekat</h2>
                        <p class="mt-0.5 text-xs sm:text-sm text-muted">Pekerjaan yang perlu segera diselesaikan.</p>
                    </div>
                    <a href="{{ route('mahasiswa.assignment.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-[#102f50] hover:text-brand transition p-1" title="Lihat semua tenggat">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="rounded-xl bg-white border border-line/70 shadow-2xs divide-y divide-line/60 overflow-hidden">
                    @forelse(collect(\App\Support\LearningPreview::items())->whereIn('type',['tugas','coding','kuis'])->filter(fn($i)=>!session('learning.submissions.'.$i['id']))->sortBy('due')->take(3) as $item)
                        @php
                            $isToday = $item['due'] && \Carbon\Carbon::parse($item['due'])->isToday();
                            $dateText = $item['due'] ? ($isToday ? 'Hari ini' : \Carbon\Carbon::parse($item['due'])->translatedFormat('d M')) : 'Bebas';
                            $timeText = $item['due'] ? \Carbon\Carbon::parse($item['due'])->format('H.i') : '';
                        @endphp
                        <a href="{{ route('mahasiswa.course.item', [$item['course'], $item['id']]) }}" class="deadline-row group">
                            <div>
                                <span class="block text-xs font-bold {{ $isToday ? 'text-danger' : 'text-ink' }}">{{ $dateText }}</span>
                                @if($timeText)
                                    <span class="mt-0.5 block text-[11px] text-muted">{{ $timeText }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <span class="block text-xs sm:text-sm font-semibold leading-snug text-ink group-hover:text-brand transition truncate">{{ $item['title'] }}</span>
                                <span class="mt-0.5 block text-[11px] text-muted truncate">{{ \App\Support\LearningPreview::course($item['course'])['title'] }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="p-4 text-xs text-muted">Semua pekerjaan telah dikumpulkan.</p>
                    @endforelse
                </div>
            </aside>

            {{-- Bagian: Diskusi Terbaru --}}
            <section aria-labelledby="discussion-heading">
                <div class="mb-3">
                    <h2 id="discussion-heading" class="text-base sm:text-lg font-bold text-ink tracking-tight">Diskusi terbaru</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-muted">Percakapan dari course aktif.</p>
                </div>

                <div class="rounded-xl bg-white border border-line/70 shadow-2xs divide-y divide-line/60 overflow-hidden">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                        <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="discussion-row group">
                            <p class="text-[11px] font-medium text-muted truncate">{{ $discussion['course_title'] }}</p>
                            <p class="mt-1 text-xs sm:text-sm font-semibold leading-snug text-ink group-hover:text-brand transition line-clamp-2">{{ $discussion['message'] }}</p>
                            <p class="mt-1.5 text-[11px] text-muted">{{ $discussion['author'] }} &middot; {{ $discussion['time'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    function joinByCode() {
        const input = document.getElementById('enroll_code_input');
        const code = input.value.trim().toUpperCase();
        if (!code) return;
        window.location.href = "{{ url('/join-kelas') }}/" + encodeURIComponent(code);
    }
</script>
@endsection
