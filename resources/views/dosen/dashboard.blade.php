@extends('layouts.mahasiswa')

@section('title', 'Dashboard Dosen | SALE')
@section('header', 'Dashboard Dosen')

@section('content')
<div class="space-y-8">
    @php
        $currentUser = auth()->user();
        if (! $currentUser && is_array(session('auth_user')) && \Illuminate\Support\Facades\Schema::hasTable('users')) {
            $sessionUser = session('auth_user');
            $currentUser = \App\Models\User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $dosenSections = \Illuminate\Support\Facades\Schema::hasTable('class_sections')
            ? \App\Models\ClassSection::query()
                ->when($currentUser, function ($query) use ($currentUser) {
                    $query->where(function ($q) use ($currentUser) {
                        $q->where('dosen_id', $currentUser->id)
                            ->orWhere('dosen_pendamping_id', $currentUser->id);
                    });
                })
                ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])
                ->withCount(['students', 'assessments'])
                ->orderByDesc('semester_id')
                ->orderBy('mata_kuliah_id')
                ->orderBy('section_code')
                ->get()
            : collect();

        $totalCourses = $dosenSections->count();

        $pendingCount = 0;
        foreach ($dosenSections as $sec) {
            $expectedSlots = $sec->assessments_count * $sec->students_count;
            if ($expectedSlots === 0) {
                $pendingCount++;
                continue;
            }
            $gradedSlots = \App\Models\StudentAssessmentScore::whereIn('assessment_id', $sec->assessments()->pluck('id'))
                ->whereNotNull('score')
                ->count();
            if ($gradedSlots < $expectedSlots) {
                $pendingCount++;
            }
        }

        $displayCourses = [];
        foreach ($dosenSections as $sec) {
            if (empty($sec->enrollment_code)) {
                $sec->enrollment_code = \App\Models\ClassSection::generateUniqueEnrollmentCode();
                $sec->save();
            }

            $dosenKetua = $sec->dosen?->name ?? 'Dosen Pengampu';
            $dosenWakil = $sec->dosenPendamping?->name ?? null;

            $displayCourses[] = [
                'id' => $sec->id,
                'url' => route('dosen.course.show', $sec->id),
                'code' => $sec->display_code,
                'sks' => ($sec->mataKuliah->sks ?? 3) . ' SKS',
                'title' => $sec->mataKuliah->name,
                'lecturer' => $dosenKetua,
                'dosen_ketua' => $dosenKetua,
                'dosen_wakil' => $dosenWakil,
                'cover' => null,
                'type' => 'Kelas Aktif',
                'work' => 'Perkuliahan semester ' . ($sec->semester->name ?? 'aktif'),
                'due' => '',
                'students_count' => $sec->students_count,
                'assessments_count' => $sec->assessments_count,
                'enrollment_code' => $sec->enrollment_code,
                'enrollment_url' => $sec->enrollment_url,
                'qr_url' => route('kelas.qr', $sec->id),
                'svg_index' => ($sec->id % 4) + 1,
            ];
        }
    @endphp

    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, {{ $currentUser->name ?? 'Dosen' }}.</h1>
            <p class="page-description">Ringkasan perkuliahan dan status penilaian kelas Anda.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dosen.penilaian.index') }}" class="button-secondary text-xs font-semibold py-2 px-3 flex items-center gap-1.5">
                <svg class="h-4 w-4 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                <span>Daftar Kelas &amp; Kode/QR</span>
            </a>
            <a href="{{ route('dosen.course.create') }}" class="button-primary text-xs font-semibold py-2 px-3">
                + Tambah Course
            </a>
        </div>
    </header>

    {{-- Stat Overview Section: High Contrast, Repetition, Proximity --}}
    <div class="grid gap-6 sm:grid-cols-2">
        {{-- Card 1: Jumlah Course --}}
        <div class="surface p-6 rounded-2xl flex items-center justify-between gap-5 border border-line/70 shadow-sm hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-soft text-brand shrink-0">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/>
                        <path d="M4 5.5v16M8 7h8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-muted">Jumlah Course (Matkul)</p>
                    <p class="mt-0.5 text-2xl font-extrabold text-ink">{{ $totalCourses }} <span class="text-xs font-normal text-muted">Matkul Aktif</span></p>
                </div>
            </div>
            <a href="{{ route('dosen.course.index') }}" class="button-secondary text-xs px-3.5 py-2 font-semibold shrink-0">
                Kelola Matkul
            </a>
        </div>

        {{-- Card 2: Jumlah Penilaian Belum Dinilai --}}
        <div class="surface p-6 rounded-2xl flex items-center justify-between gap-5 border border-line/70 shadow-sm hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shrink-0">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-muted">Jumlah Penilaian Belum Dinilai</p>
                    <p class="mt-0.5 text-2xl font-extrabold text-ink">{{ $pendingCount }} <span class="text-xs font-normal text-muted">Kelas Belum Dinilai</span></p>
                </div>
            </div>
            <a href="{{ route('dosen.penilaian.index') }}" class="button-secondary text-xs px-3.5 py-2 font-semibold shrink-0">
                Lihat Penilaian
            </a>
        </div>
    </div>

    {{-- Course List Section: Desain & Grid Persis seperti di Halaman Course --}}
    <section class="space-y-4" aria-label="Daftar course">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-ink">Daftar Matkul Diampu</h2>
                <p class="text-xs text-muted">Seluruh kuis, tugas, dan rekap nilai berada di dalam matkul masing-masing.</p>
            </div>
            <a href="{{ route('dosen.course.index') }}" class="button-secondary text-xs px-3.5 py-2 font-semibold flex items-center gap-1">
                <span>Lihat Semua Course</span>
                <span>&rarr;</span>
            </a>
        </div>

        @if(empty($displayCourses))
            <div class="surface p-10 text-center rounded-2xl border border-line/70">
                <div class="h-12 w-12 rounded-full bg-brand-soft text-brand flex items-center justify-center mx-auto mb-3">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                </div>
                <h3 class="text-base font-bold text-ink">Belum Ada Kelas yang Diampu</h3>
                <p class="mt-1 text-xs text-muted max-w-md mx-auto">
                    Anda belum terdaftar di kelas perkuliahan manapun pada semester ini. Anda dapat masuk ke kelas menggunakan Kode / QR Code atau membuka daftar kelas.
                </p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('dosen.penilaian.index') }}" class="button-primary text-xs px-4 py-2 font-semibold">
                        Buka Daftar Kelas / Masuk via Kode &amp; QR
                    </a>
                </div>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach($displayCourses as $course)
                    <a href="{{ $course['url'] }}" class="group flex min-h-64 flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md border border-line/70">
                        @if(!empty($course['cover']))
                            <img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul {{ $course['title'] }}" class="h-36 w-full object-cover">
                            <div class="px-5 pt-4">
                                <p class="text-xs font-semibold text-brand">{{ $course['code'] }} · {{ $course['sks'] }}</p>
                                <h3 class="mt-1 text-lg font-semibold leading-snug text-ink">{{ $course['title'] }}</h3>
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
                                    <h3 class="mt-2.5 text-lg font-bold leading-snug text-white">{{ $course['title'] }}</h3>
                                    
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
                                @if(!empty($course['enrollment_code']))
                                    <div class="pt-3 pb-3 flex items-center justify-between text-xs border-t border-line/60" onclick="event.preventDefault(); event.stopPropagation();">
                                        <div>
                                            <span class="text-[10px] text-muted uppercase font-bold block">Kode Masuk:</span>
                                            <code class="font-mono font-bold text-ink text-xs tracking-wide">{{ $course['enrollment_code'] }}</code>
                                        </div>
                                        <button type="button" 
                                                onclick="event.preventDefault(); event.stopPropagation(); openQrModal('{{ $course['code'] }}', '{{ addslashes($course['title']) }}', '{{ $course['enrollment_code'] }}', '{{ $course['enrollment_url'] }}', '{{ $course['qr_url'] ?? '' }}')"
                                                class="button-secondary text-[11px] py-1 px-2.5 flex items-center gap-1 shrink-0 font-semibold hover:bg-slate-50 transition shadow-2xs">
                                            <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                                            Bagikan QR
                                        </button>
                                    </div>
                                @endif

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
                @endforeach
            </div>
        @endif
    </section>
</div>

<!-- Modal Barcode / QR Code untuk Dosen -->
<div id="dosenQrModal" onclick="if(event.target === this) closeQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl text-center space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <h2 class="text-base font-bold text-ink">Bagikan Barcode / QR Code Kelas</h2>
            <button type="button" onclick="closeQrModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>
        <div class="space-y-2">
            <h3 id="modalCourseTitle" class="text-base font-bold text-ink"></h3>
            <p class="text-xs text-muted">Mahasiswa dapat memindai barcode di bawah ini atau memasukkan kode masuk untuk bergabung ke dalam kelas.</p>
        </div>
        <div class="flex justify-center p-4 bg-white rounded-xl border border-line shadow-inner max-w-xs mx-auto">
            <img id="modalQrImage" src="" alt="QR Code" class="w-48 h-48 object-contain">
        </div>
        <div class="space-y-1 bg-slate-50 p-3 rounded-lg border border-line">
            <span class="text-xs text-muted">Kode Masuk Kelas:</span>
            <p id="modalEnrollmentCode" class="text-2xl font-mono font-bold tracking-widest text-brand"></p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="copyEnrollmentCode()" class="button-secondary flex-1 text-xs py-2 font-semibold">Salin Kode</button>
            <button type="button" onclick="copyEnrollmentUrl()" class="button-primary flex-1 text-xs py-2 font-semibold">Salin Link Masuk</button>
        </div>
    </div>
</div>

<script>
    let currentEnrollmentUrl = '';
    let currentEnrollmentCode = '';

    function openQrModal(code, title, enrollmentCode, enrollmentUrl, qrUrl) {
        document.getElementById('modalCourseTitle').textContent = code + ' - ' + title;
        document.getElementById('modalEnrollmentCode').textContent = enrollmentCode;
        document.getElementById('modalQrImage').src = qrUrl || ('https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(enrollmentUrl));
        currentEnrollmentCode = enrollmentCode;
        currentEnrollmentUrl = enrollmentUrl;
        
        const modal = document.getElementById('dosenQrModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeQrModal() {
        const modal = document.getElementById('dosenQrModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function copyEnrollmentCode() {
        if (!currentEnrollmentCode) return;
        navigator.clipboard.writeText(currentEnrollmentCode).then(() => {
            alert('Kode masuk kelas berhasil disalin: ' + currentEnrollmentCode);
        });
    }

    function copyEnrollmentUrl() {
        if (!currentEnrollmentUrl) return;
        navigator.clipboard.writeText(currentEnrollmentUrl).then(() => {
            alert('Link pendaftaran kelas berhasil disalin!');
        });
    }
</script>
@endsection
