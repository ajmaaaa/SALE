@extends('layouts.mahasiswa')

@section('title', 'Dashboard Administrator | SALE')
@section('header', 'Dashboard Administrator')

@section('content')
<div class="space-y-8">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, Admin.</h1>
            <p class="page-description">Kelola data institusi, pantau aktivitas pengguna, dan tinjau kegiatan akademik dari satu ruang kerja.</p>
        </div>
        <div class="flex flex-wrap gap-3 shrink-0">
            <a href="{{ route('admin.page', 'pengguna') }}?create=1" class="button-primary">+ Tambah Pengguna</a>
            <a href="{{ route('admin.page', 'akademik') }}" class="button-secondary">Kelola Struktur</a>
        </div>
    </header>

    {{-- Quick Stat Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Pengguna aktif', count(array_filter($users, fn($u) => $u['status'] === 'aktif')), 'pengguna', 'Akun terdaftar aktif'],
            ['Course aktif', count(\App\Support\LearningPreview::courses()), 'akademik', 'Mata kuliah semester ini'],
            ['Program studi', count(array_filter($academic, fn($a) => $a['type'] === 'prodi')), 'akademik', 'Struktur institusi'],
            ['Kelas berjalan', count(array_filter($academic, fn($a) => $a['type'] === 'kelas')), 'akademik', 'Rombongan belajar']
        ] as [$label, $count, $target, $sub])
            <a class="surface p-5 hover:shadow-md transition" href="{{ route('admin.page', $target) }}">
                <p class="text-xs text-muted">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-ink">{{ $count }}</p>
                <p class="mt-1 text-xs text-muted">{{ $sub }}</p>
            </a>
        @endforeach
    </div>

    {{-- 3-Column Dashboard Container matching Mahasiswa Layout --}}
    <div class="rounded-2xl bg-[#e9edf1] p-4 sm:p-5">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            {{-- Column 1: Aktivitas terbaru --}}
            <section aria-labelledby="activity-heading">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="activity-heading" class="section-heading">Aktivitas terbaru</h2>
                        <p class="mt-1 text-sm leading-5 text-muted">Perubahan data administratif dan pengguna.</p>
                    </div>
                    <a href="{{ route('admin.page', 'aktivitas') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark">
                        <span class="hidden min-[1320px]:inline">Semua log</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm divide-y divide-line/40">
                    @forelse(array_slice(session('admin.logs', []), 0, 5) as $log)
                        <article class="py-3.5 first:pt-0 last:pb-0">
                            <p class="text-sm font-semibold text-ink">{{ $log['action'] }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $log['actor'] }} · {{ $log['time'] }}</p>
                        </article>
                    @empty
                        <p class="py-2 text-sm text-muted">Belum ada perubahan administratif di sesi ini.</p>
                    @endforelse
                </div>
            </section>

            {{-- Column 2: Tugas perlu dinilai (Exact same structure & style as mahasiswa deadlines) --}}
            <aside aria-labelledby="grading-heading">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="grading-heading" class="section-heading">Tugas perlu dinilai</h2>
                        <p class="mt-1 text-sm leading-5 text-muted">Pengumpulan menunggu penilaian.</p>
                    </div>
                    <a href="{{ route('dosen.grades') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark">
                        <span class="hidden min-[1320px]:inline">Lihat semua</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="rounded-2xl bg-white px-2 py-2 shadow-sm">
                    @php
                        $pendingItems = collect(\App\Support\LearningPreview::items())->whereIn('type', ['tugas', 'coding', 'kuis'])->filter(fn($i) => session('learning.submissions.'.$i['id']))->take(3);
                        if ($pendingItems->isEmpty()) {
                            $pendingItems = collect(\App\Support\LearningPreview::items())->whereIn('type', ['tugas', 'coding'])->take(2);
                        }
                    @endphp
                    @forelse($pendingItems as $item)
                        @if(!$loop->first)
                            <div class="mx-4 h-px bg-[#e7eaee]" aria-hidden="true"></div>
                        @endif
                        <a href="{{ route('dosen.grades') }}" class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 rounded-xl px-3 py-4 hover:bg-[#f3f6f9] transition">
                            <span>
                                <span class="block text-xs font-semibold text-brand">Masuk</span>
                                <span class="mt-1 block text-xs text-muted">Perlu nilai</span>
                            </span>
                            <span>
                                <span class="block text-sm font-semibold leading-5 text-ink">{{ $item['title'] }}</span>
                                <span class="mt-1 block text-xs leading-5 text-muted">{{ \App\Support\LearningPreview::course($item['course'])['title'] }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="p-4 text-sm text-muted">Belum ada pengumpulan baru.</p>
                    @endforelse
                </div>
            </aside>

            {{-- Column 3: Diskusi terbaru (Exact same structure & style as mahasiswa discussions) --}}
            <section aria-labelledby="discussion-heading">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="discussion-heading" class="section-heading">Diskusi terbaru</h2>
                        <p class="mt-1 text-sm leading-5 text-muted">Percakapan dari course aktif.</p>
                    </div>
                    <a href="{{ route('mahasiswa.discussion.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand hover:text-brand-dark">
                        <span class="hidden min-[1320px]:inline">Buka forum</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="space-y-3">
                    @foreach(\App\Support\LearningPreview::recentDiscussions() as $discussion)
                        <a href="{{ route('mahasiswa.course.item', [$discussion['course'], $discussion['item']]) }}#diskusi" class="block rounded-xl bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <p class="text-xs font-semibold text-muted">{{ $discussion['course_title'] }}</p>
                            <p class="mt-2 text-sm font-semibold leading-5 text-ink">{{ $discussion['message'] }}</p>
                            <p class="mt-2 text-xs text-muted">{{ $discussion['author'] }} · {{ $discussion['time'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    {{-- System Status Section --}}
    <div class="grid gap-6 md:grid-cols-2">
        <section class="surface p-5">
            <h2 class="font-semibold text-sm text-ink">Status Integrasi Sistem</h2>
            <dl class="mt-4 space-y-3 text-xs">
                <div class="flex justify-between gap-3 pb-2 border-b border-line/40">
                    <dt class="text-muted">Sistem Informasi Akademik (SIAKAD)</dt>
                    <dd class="font-medium text-ink">Pratinjau Mandiri</dd>
                </div>
                <div class="flex justify-between gap-3 pb-2 border-b border-line/40">
                    <dt class="text-muted">Backup Basis Data</dt>
                    <dd class="font-medium text-ink">Tersimpan di Sesi</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Layanan AI Assistant / Agen Evaluasi</dt>
                    <dd class="font-medium text-ink">Siap Dihubungkan</dd>
                </div>
            </dl>
        </section>

        <section class="surface p-5 flex flex-col justify-between">
            <div>
                <h2 class="font-semibold text-sm text-ink">Monitoring &amp; Pemeliharaan</h2>
                <p class="mt-2 text-xs text-muted leading-relaxed">
                    Sistem saat ini berjalan dalam mode pratinjau interaktif. Seluruh simulasi data pengguna, perubahan bobot CPMK, dan pengumpulan tugas tersimpan dalam sesi lokal.
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a class="quiet-link text-xs" href="{{ route('admin.page', 'monitoring') }}">
                    Buka panel pemantauan sistem →
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
