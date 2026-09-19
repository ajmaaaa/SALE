@extends('layouts.mahasiswa')

@section('title', 'Course | SALE')
@section('header', 'Course')

@section('content')
<div class="space-y-7">
    <header class="flex flex-wrap items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="page-heading">Course</h1>
            <p class="page-description">Kelas aktif yang telah ditetapkan oleh program studi pada semester ini.</p>
        </div>
        @if(request()->is('dosen*'))
            <a class="button-primary" href="{{ route('dosen.course.create') }}">+ Tambah course</a>
        @endif
    </header>

    <form class="flex flex-col gap-3 sm:flex-row" action="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" method="GET">
        <label class="sr-only" for="course-search">Cari course</label>
        <input id="course-search" name="q" type="search" class="field sm:max-w-md" placeholder="Cari judul, kode, atau dosen" value="{{ request('q') }}">
        <button type="submit" class="button-secondary">Terapkan</button>
    </form>

    {{-- Grid Course: Desain Asli dengan Detail Dosen Ketua/Wakil, Mahasiswa & Asesmen --}}
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar course">
        @php
            $displayCourses = $courseCards ?? [];
            if (empty($displayCourses) && !request()->is('dosen*') && empty(request('q'))) {
                foreach ($courses as $c) {
                    $contents = collect(\App\Support\LearningPreview::items())->where('course', $c['id']);
                    $next = $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->sortBy('due')->first();
                    $displayCourses[] = [
                        'id' => $c['id'],
                        'url' => route((request()->is('dosen*') ? 'dosen' : 'mahasiswa').'.course.show', $c['id']),
                        'code' => $c['code'],
                        'sks' => '3 SKS',
                        'title' => $c['title'],
                        'lecturer' => $c['lecturer'],
                        'dosen_ketua' => $c['lecturer'],
                        'dosen_wakil' => null,
                        'cover' => $c['cover'] ?? null,
                        'type' => $next ? \App\Support\LearningPreview::labels()[$next['type']] : 'Materi kelas',
                        'work' => $next['title'] ?? 'Belum ada tugas aktif',
                        'due' => !empty($next['due']) ? \Carbon\Carbon::parse($next['due'])->translatedFormat('d M, H:i') : '',
                        'students_count' => 5,
                        'assessments_count' => $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->count(),
                        'svg_index' => $c['id'],
                    ];
                }
            }
        @endphp

        @forelse ($displayCourses as $course)
            <a href="{{ $course['url'] }}" class="group flex min-h-64 flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md border border-line/70">
                @if(!empty($course['cover']))
                    <img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul {{ $course['title'] }}" class="h-36 w-full object-cover">
                    <div class="px-5 pt-4">
                        <p class="text-xs font-semibold text-brand">{{ $course['code'] }} · {{ $course['sks'] }}</p>
                        <h2 class="mt-1 text-lg font-semibold leading-snug text-ink">{{ $course['title'] }}</h2>
                        <div class="mt-2 space-y-0.5 text-xs text-muted">
                            <p><span class="font-medium text-ink">Dosen Ketua:</span> {{ $course['dosen_ketua'] ?? $course['lecturer'] }}</p>
                            @if(!empty($course['dosen_wakil']))
                                <p><span class="font-medium text-ink">Dosen Wakil:</span> {{ $course['dosen_wakil'] }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="relative min-h-36 overflow-hidden bg-[#102f50] px-5 py-5 text-white flex flex-col justify-between">
                        @php $svgIdx = $course['svg_index'] ?? 1; @endphp
                        @if($svgIdx === 1)
                            <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/></svg>
                        @elseif($svgIdx === 2)
                            <svg class="absolute -right-3 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/></svg>
                        @elseif($svgIdx === 3)
                            <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/></svg>
                        @else
                            <svg class="absolute -right-2 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="15" y="20" width="34" height="22" rx="4"/><rect x="70" y="20" width="34" height="22" rx="4"/><rect x="43" y="79" width="34" height="22" rx="4"/><path d="M49 31h21M32 42v24h28v13M87 42v24H60"/></svg>
                        @endif
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 text-xs font-semibold text-white/90">
                                <span class="bg-white/20 px-2 py-0.5 rounded font-mono">{{ $course['code'] }}</span>
                                <span>{{ $course['sks'] }}</span>
                            </div>
                            <h2 class="mt-2.5 text-lg sm:text-xl font-bold leading-snug text-white">{{ $course['title'] }}</h2>
                            
                            <div class="mt-2.5 space-y-0.5 text-xs text-white/90">
                                <div><span class="text-white/70">Dosen Ketua:</span> <span class="font-semibold text-white">{{ $course['dosen_ketua'] ?? $course['lecturer'] }}</span></div>
                                @if(!empty($course['dosen_wakil']))
                                    <div><span class="text-white/70">Dosen Wakil:</span> <span class="font-semibold text-white">{{ $course['dosen_wakil'] }}</span></div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                <div class="flex flex-1 flex-col px-5 py-4 justify-between">
                    <div>
                        <p class="text-xs font-semibold text-brand">{{ $course['type'] }}</p>
                        <p class="mt-1 text-sm font-medium text-ink">{{ $course['work'] }}</p>
                        @if(!empty($course['due']))
                            <p class="mt-2 text-xs font-medium text-danger">{{ $course['due'] }}</p>
                        @endif
                    </div>

                    <div class="mt-4">
                        @if(request()->is('dosen*') && !empty($course['enrollment_code']))
                            <div class="pt-3 pb-3 flex items-center justify-between text-xs border-t border-line/60" onclick="event.preventDefault(); event.stopPropagation();">
                                <div>
                                    <span class="text-[10px] text-muted uppercase font-bold block">Kode Masuk:</span>
                                    <code class="font-mono font-bold text-ink text-xs tracking-wide">{{ $course['enrollment_code'] }}</code>
                                </div>
                                <button type="button" 
                                        onclick="event.preventDefault(); event.stopPropagation(); openQrModal('{{ $course['code'] }}', '{{ addslashes($course['title']) }}', '{{ $course['enrollment_code'] }}', '{{ $course['enrollment_url'] }}', '{{ $course['qr_url'] }}')"
                                        class="button-secondary text-[11px] py-1 px-2.5 flex items-center gap-1 shrink-0 font-semibold hover:bg-slate-50 transition shadow-2xs">
                                    <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                                    Bagikan QR
                                </button>
                            </div>
                        @endif

                        {{-- Ganti 'X modul Y pekerjaan' menjadi Jumlah Mahasiswa dan Jumlah Asesmen --}}
                        <div class="pt-3 flex items-center justify-between text-xs font-medium text-muted border-t border-line/60">
                            <span class="flex items-center gap-1.5">
                                <span>{{ $course['students_count'] }} Mahasiswa</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span>{{ $course['assessments_count'] }} Asesmen</span>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full surface p-10 text-center rounded-2xl border border-line/70">
                <div class="h-12 w-12 rounded-full bg-brand-soft text-brand flex items-center justify-center mx-auto mb-3">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                </div>
                @if(request()->filled('q'))
                    <h3 class="text-base font-bold text-ink">Course tidak ditemukan</h3>
                    <p class="mt-1 text-xs text-muted max-w-md mx-auto">Tidak ada kelas atau mata kuliah yang cocok dengan kata pencarian "{{ request('q') }}". Coba gunakan kata kunci lainnya.</p>
                    <div class="mt-4">
                        <a href="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" class="button-secondary text-xs px-4 py-2 font-semibold">
                            Reset Pencarian
                        </a>
                    </div>
                @else
                    <h3 class="text-base font-bold text-ink">Belum Ada Course / Kelas</h3>
                    <p class="mt-1 text-xs text-muted max-w-md mx-auto">
                        {{ request()->is('dosen*') ? 'Anda belum memiliki kelas yang diampu pada semester ini. Anda dapat masuk kelas via Kode/QR di Daftar Kelas atau membuat course baru.' : 'Belum ada course perkuliahan yang aktif untuk Anda saat ini.' }}
                    </p>
                    @if(request()->is('dosen*'))
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <a href="{{ route('dosen.penilaian.index') }}" class="button-primary text-xs px-4 py-2 font-semibold">
                                Buka Daftar Kelas &amp; Masuk via Kode / QR
                            </a>
                            <a href="{{ route('dosen.course.create') }}" class="button-secondary text-xs px-4 py-2 font-semibold">
                                + Tambah Course Baru
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        @endforelse
    </section>
</div>

<!-- Modal Barcode / QR Code untuk Dosen -->
<div id="dosenQrModal" onclick="if(event.target === this) closeQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl text-center space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <h2 class="text-base font-bold text-ink">Bagikan Barcode / QR Code Kelas</h2>
            <button type="button" onclick="closeQrModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>

        <div>
            <span id="dosen_modal_class_code" class="text-xs font-bold text-brand font-mono px-2 py-0.5 rounded bg-brand-soft"></span>
            <h3 id="dosen_modal_mk_name" class="font-bold text-base text-ink mt-1"></h3>
            <p class="text-xs text-muted">Kirimkan link atau tampilkan QR Code ini kepada mahasiswa untuk otomatis bergabung ke kelas ini.</p>
        </div>

        <div class="flex justify-center p-4 bg-white border border-line rounded-xl shadow-xs">
            <img id="dosen_qr_img" src="" alt="QR Code" class="h-44 w-44 object-contain">
        </div>

        <div class="space-y-2 text-left">
            <div>
                <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Kode Masuk:</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="dosen_modal_code" readonly class="field font-mono font-bold text-xs bg-canvas text-center">
                    <button type="button" onclick="copyDosenCode()" class="button-secondary text-xs shrink-0 py-2 px-3">Salin Kode</button>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Link Langsung (Tautan):</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="dosen_modal_url" readonly class="field font-mono text-[11px] bg-canvas">
                    <button type="button" onclick="copyDosenUrl()" class="button-primary text-xs shrink-0 py-2 px-3" id="btnCopyDosenUrl">Salin Link</button>
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-line flex justify-end gap-2">
            <button type="button" onclick="window.print()" class="button-secondary text-xs">Cetak</button>
            <button type="button" onclick="closeQrModal()" class="button-primary text-xs">Tutup</button>
        </div>
    </div>
</div>

<script>
    function openQrModal(displayCode, mkName, code, url, qrSrc) {
        document.getElementById('dosen_modal_class_code').textContent = displayCode;
        document.getElementById('dosen_modal_mk_name').textContent = mkName;
        document.getElementById('dosen_modal_code').value = code;
        document.getElementById('dosen_modal_url').value = url;
        document.getElementById('dosen_qr_img').src = qrSrc;
        const modal = document.getElementById('dosenQrModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeQrModal() {
        const modal = document.getElementById('dosenQrModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function copyDosenCode() {
        const input = document.getElementById('dosen_modal_code');
        input.select();
        navigator.clipboard.writeText(input.value);
        alert(`Kode kelas ${input.value} tersalin!`);
    }

    function copyDosenUrl() {
        const input = document.getElementById('dosen_modal_url');
        input.select();
        navigator.clipboard.writeText(input.value);
        const btn = document.getElementById('btnCopyDosenUrl');
        btn.textContent = 'Tersalin!';
        setTimeout(() => { btn.textContent = 'Salin Link'; }, 2000);
    }
</script>
@endsection
