@php
    $contents = collect(\App\Support\LearningPreview::items())->where('course', $course['id']);
    $next = $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->sortBy('due')->first();
    $sks = $course['sks'] ?? '3 SKS';
    $studentsCount = $course['students_count'] ?? (count($course['students'] ?? [1, 2, 3, 4, 5]));
    $assessmentsCount = $course['assessments_count'] ?? $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->count();
    $type = $course['type'] ?? ($next ? \App\Support\LearningPreview::labels()[$next['type']] : 'Materi kelas');
    $work = $course['work'] ?? ($next['title'] ?? 'Belum ada tugas aktif');
    $due = $course['due'] ?? (!empty($next['due']) ? \Carbon\Carbon::parse($next['due'])->translatedFormat('d M, H:i') : '');
    $role = $role ?? (request()->is('dosen*') ? 'dosen' : 'mahasiswa');
    $targetUrl = route($role . '.course.show', $course['id']);
    $isDosen = ($role === 'dosen' || request()->is('dosen*'));
    $enrollmentCode = $course['enrollment_code'] ?? ($course['code'] . '-2026');
    $enrollmentUrl = $course['enrollment_url'] ?? url('/join-kelas/' . $enrollmentCode);
    $qrUrl = $course['qr_url'] ?? ('https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($enrollmentUrl));
    $dosenKetua = $course['dosen_ketua'] ?? ($course['lecturer'] ?? 'Dr. Budi Santoso, M.Kom.');
    $dosenWakil = $course['dosen_wakil'] ?? null;
@endphp

<div class="group relative flex min-h-64 flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md border border-line/70">
    @if(!empty($course['cover']))
        <a href="{{ $targetUrl }}" class="block">
            <img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul {{ $course['title'] }}" class="h-36 w-full object-cover">
            <div class="px-5 pt-4">
                <div class="flex items-center gap-2 text-xs font-semibold text-brand">
                    <span class="font-mono">{{ $course['code'] }}</span>
                    <span class="h-2.5 w-px bg-brand/30"></span>
                    <span>{{ $sks }}</span>
                </div>
                <h2 class="mt-1 text-lg font-semibold leading-snug text-ink group-hover:text-brand transition">{{ $course['title'] }}</h2>
                <div class="mt-2 space-y-0.5 text-xs text-muted">
                    <p><span class="font-medium text-ink">Dosen Ketua:</span> {{ $dosenKetua }}</p>
                    @if(!empty($dosenWakil))
                        <p><span class="font-medium text-ink">Dosen Wakil:</span> {{ $dosenWakil }}</p>
                    @endif
                </div>
            </div>
        </a>
    @else
        <a href="{{ $targetUrl }}" class="relative block min-h-36 overflow-hidden bg-[#102f50] px-5 py-5 text-white flex flex-col justify-between">
            @php $svgIdx = (($course['id'] ?? 1) % 4) ?: 4; @endphp
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
                    <span>{{ $sks }}</span>
                </div>
                <h2 class="mt-2.5 text-lg sm:text-xl font-bold leading-snug text-white group-hover:text-slate-100 transition">{{ $course['title'] }}</h2>
                
                <div class="mt-2.5 space-y-0.5 text-xs text-white/90">
                    <div><span class="text-white/70">Dosen Ketua:</span> <span class="font-semibold text-white">{{ $dosenKetua }}</span></div>
                    @if(!empty($dosenWakil))
                        <div><span class="text-white/70">Dosen Wakil:</span> <span class="font-semibold text-white">{{ $dosenWakil }}</span></div>
                    @endif
                </div>
            </div>
        </a>
    @endif

    <div class="flex flex-1 flex-col px-5 py-4 justify-between">
        <a href="{{ $targetUrl }}" class="block">
            <p class="text-xs font-semibold text-brand">{{ $type }}</p>
            <p class="mt-1 text-sm font-medium text-ink line-clamp-2">{{ $work }}</p>
            {{-- Baris jam + QR sejajar --}}
            <div class="mt-2 flex items-center justify-between gap-2">
                @if($due)
                    <p class="text-xs font-medium {{ ($isFirst ?? false) ? 'text-danger' : 'text-muted' }}">{{ $due }}</p>
                @else
                    <span></span>
                @endif
                @if($isDosen && !empty($enrollmentCode))
                    <button type="button"
                            onclick="event.preventDefault(); event.stopPropagation(); openQrModal('{{ $course['code'] }}', '{{ addslashes($course['title']) }}', '{{ $enrollmentCode }}', '{{ $enrollmentUrl }}', '{{ $qrUrl }}')"
                            title="Tampilkan QR Code Kelas"
                            class="flex items-center gap-1 text-muted border border-line/60 rounded-md p-1.5 hover:border-brand hover:text-brand hover:bg-slate-50 transition cursor-pointer shrink-0">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <path d="M14 14h7v7h-7z"/>
                        </svg>
                    </button>
                @endif
            </div>
        </a>

        {{-- Footer stats: Mahasiswa (kiri) | Asesmen (kanan) --}}
        <div class="mt-4 pt-3 flex items-center justify-between text-xs font-medium text-muted border-t border-line/60">
            <a href="{{ $targetUrl }}" class="flex items-center gap-1.5 hover:text-ink transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>{{ $studentsCount }} Mahasiswa</span>
            </a>
            <a href="{{ $targetUrl }}" class="flex items-center gap-1.5 hover:text-ink transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>{{ $assessmentsCount }} Asesmen</span>
            </a>
        </div>
    </div>
</div>

@once
<!-- Modal Barcode / QR Code untuk Dosen -->
<div id="dosenQrModal" onclick="if(event.target === this) closeQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl text-center space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <h2 class="text-base font-bold text-ink">QR Code Kelas</h2>
            <button type="button" onclick="closeQrModal()" class="text-muted hover:text-ink text-xl leading-none cursor-pointer">&times;</button>
        </div>
        <div class="space-y-1 text-left">
            <h3 id="modalCourseTitle" class="text-base font-bold text-ink"></h3>
            <p class="text-xs text-muted">Mahasiswa dapat memindai barcode di bawah ini atau memasukkan kode masuk untuk bergabung ke dalam kelas.</p>
        </div>
        <div class="flex justify-center p-4 bg-white rounded-xl border border-line shadow-inner max-w-xs mx-auto">
            <img id="modalQrImage" src="" alt="QR Code Kelas" class="w-48 h-48 object-contain">
        </div>
        <div class="space-y-1 bg-slate-50 p-3 rounded-lg border border-line text-center">
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
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeQrModal() {
        const modal = document.getElementById('dosenQrModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
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
@endonce
