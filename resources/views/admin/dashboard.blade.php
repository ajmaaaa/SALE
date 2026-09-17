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
    @php
        $facultyData = \App\Support\AdminPreview::facultyProdiData();
        $totalFaculties = count($facultyData);
        $totalProdis = array_sum(array_map(fn ($f) => count($f['prodis']), $facultyData));
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a class="surface p-5 hover:shadow-md transition group" href="{{ route('admin.page', 'pengguna') }}">
            <p class="text-xs text-muted">Pengguna aktif</p>
            <p class="mt-2 text-2xl font-bold text-ink group-hover:text-brand transition">{{ count(array_filter($users, fn ($u) => $u['status'] === 'aktif')) }}</p>
            <p class="mt-1 text-xs text-muted">Akun terdaftar aktif</p>
        </a>

        <a class="surface p-5 hover:shadow-md transition group border-l-4 border-l-brand" href="{{ route('admin.laporan.fakultas') }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-brand">Fakultas</p>
                <span class="text-[10px] bg-brand-soft text-brand px-1.5 py-0.5 rounded font-bold">Buka &rarr;</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-ink group-hover:text-brand transition">{{ $totalFaculties }} Fakultas</p>
            <p class="mt-1 text-xs text-muted">FIK, FT, FEB (Klik rincian)</p>
        </a>

        <a class="surface p-5 hover:shadow-md transition group border-l-4 border-l-emerald-500" href="{{ route('admin.laporan.prodi') }}">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-emerald-600">Program studi</p>
                <span class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded font-bold">Buka &rarr;</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-ink group-hover:text-emerald-600 transition">{{ $totalProdis }} Program Studi</p>
            <p class="mt-1 text-xs text-muted">4 prodi per fakultas (Klik rincian)</p>
        </a>

        <a class="surface p-5 hover:shadow-md transition group" href="{{ route('admin.page', 'akademik') }}">
            <p class="text-xs text-muted">Kelas berjalan</p>
            <p class="mt-2 text-2xl font-bold text-ink group-hover:text-brand transition">{{ count(array_filter($academic, fn ($a) => $a['type'] === 'kelas')) ?: 14 }}</p>
            <p class="mt-1 text-xs text-muted">Rombongan belajar aktif</p>
        </a>
    </div>

    {{-- Section Struktur Akademik: Fakultas & Program Studi --}}
    <section class="surface p-6">
        <div class="flex flex-wrap items-center justify-between pb-4 mb-5 border-b border-line/60 gap-3">
            <div>
                <h2 class="section-heading text-lg font-bold text-ink">Struktur Akademik: Fakultas &amp; Program Studi</h2>
                <p class="text-xs text-muted mt-0.5">Pilih kelompok data untuk meninjau data pimpinan, ketercapaian, kurikulum, dan mahasiswa.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Card 1: Fakultas (Bisa diklik menuju halaman rincian fakultas) --}}
            <a href="{{ route('admin.laporan.fakultas') }}" class="block p-5 rounded-xl border-2 border-line/60 bg-white hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-xl bg-brand/10 text-brand flex items-center justify-center font-bold text-xl group-hover:bg-brand group-hover:text-white transition shrink-0">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-ink group-hover:text-brand transition">Fakultas</h3>
                            <p class="text-xs text-muted">{{ $totalFaculties }} Fakultas Aktif (FIK, FT, FEB)</p>
                        </div>
                    </div>
                    <span class="button-secondary text-xs py-1.5 px-3 group-hover:border-brand group-hover:text-brand transition inline-flex items-center gap-1 shrink-0">
                        Buka Rincian &rarr;
                    </span>
                </div>
                <div class="mt-4 pt-3 border-t border-line/40 text-xs text-muted">
                    <span class="font-semibold text-ink block mb-1">Field informasi tersedia:</span>
                    Nama Fakultas (dropdown pilih fakultas), Nama Prodi (dropdown per fakultas), Jumlah Prodi, Dekan, Wakil, dan Jumlah Mahasiswa.
                </div>
            </a>

            {{-- Card 2: Program Studi (Bisa diklik menuju halaman rincian program studi) --}}
            <a href="{{ route('admin.laporan.prodi') }}" class="block p-5 rounded-xl border-2 border-line/60 bg-white hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-xl group-hover:bg-emerald-600 group-hover:text-white transition shrink-0">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-ink group-hover:text-emerald-600 transition">Program Studi</h3>
                            <p class="text-xs text-muted">{{ $totalProdis }} Program Studi Terdaftar (4 per Fakultas)</p>
                        </div>
                    </div>
                    <span class="button-secondary text-xs py-1.5 px-3 group-hover:border-brand group-hover:text-brand transition inline-flex items-center gap-1 shrink-0">
                        Buka Rincian &rarr;
                    </span>
                </div>
                <div class="mt-4 pt-3 border-t border-line/40 text-xs text-muted">
                    <span class="font-semibold text-ink block mb-1">Field informasi tersedia:</span>
                    Nama Prodi, Kaprodi, Wakil, Semester, Mata Kuliah, Jumlah Mahasiswa, dan IPK Rata-Rata.
                </div>
            </a>
        </div>
    </section>

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
