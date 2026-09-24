@php
    $passedRole = $role ?? null;
    $currentRole = $passedRole ?: (request()->is('dosen*') ? 'dosen' : (auth()->user()?->role?->name ?? session('auth_user.role') ?? 'mahasiswa'));
    $isDosen = ($passedRole === 'dosen') || request()->is('dosen*') || in_array($currentRole, ['dosen', 'kaprodi'], true);

    $contents = collect(\App\Support\LearningPreview::items())->where('course', $course['id']);
    if ($contents->isEmpty() && \Illuminate\Support\Facades\Schema::hasTable('assessments')) {
        $dbAssessments = \App\Models\Assessment::where('class_section_id', $course['id'])
            ->where('status', 'published')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->get();
        if ($dbAssessments->isNotEmpty()) {
            $contents = $dbAssessments->map(function ($a) {
                return [
                    'id' => $a->id,
                    'course' => $a->class_section_id,
                    'type' => match ($a->type) {
                        'pbl', 'case', 'project', 'proyek' => 'tugas',
                        default => $a->type,
                    },
                    'title' => $a->name,
                    'due' => $a->due_at?->format('Y-m-d\TH:i'),
                ];
            });
        }
    }
    
    // Untuk mahasiswa: cek apakah ada tugas/kuis aktif yang belum diserahkan
    $uncompletedTask = $contents->whereIn('type', ['tugas', 'coding', 'kuis'])
        ->filter(fn($item) => empty(session('learning.submissions.'.$item['id'])))
        ->sortBy('due')
        ->first();

    $next = $uncompletedTask ?: $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->sortBy('due')->first();

    $sks = $course['sks'] ?? '3 SKS';
    $studentsCount = $course['students_count'] ?? (count($course['students'] ?? [1, 2, 3, 4, 5]));
    $assessmentsCount = $course['assessments_count'] ?? $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->count();
    $type = $course['type'] ?? ($next ? \App\Support\LearningPreview::labels()[$next['type']] : 'Materi kelas');
    $work = $course['work'] ?? ($next['title'] ?? 'Belum ada tugas aktif');
    $rawDue = $next['due'] ?? null;
    $dueFormatted = !empty($rawDue) ? \Carbon\Carbon::parse($rawDue)->translatedFormat('d M, H:i') : '';
    $hasPendingTask = $isDosen ? !empty($rawDue) : !empty($uncompletedTask);

    $targetRole = $isDosen ? 'dosen' : 'mahasiswa';
    $targetUrl = route($targetRole . '.course.show', $course['id']);
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
                <div class="inline-flex items-baseline gap-2 text-xs font-semibold leading-4 text-brand">
                    <span class="font-mono leading-4">{{ $course['code'] }}</span>
                    <span class="h-3 w-px self-center bg-brand/30" aria-hidden="true"></span>
                    <span class="leading-4">{{ $sks }}</span>
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
                <div class="inline-flex items-baseline gap-2 text-xs font-semibold leading-4 text-white/90">
                    <span class="font-mono leading-4">{{ $course['code'] }}</span>
                    <span class="h-3 w-px self-center bg-white/30" aria-hidden="true"></span>
                    <span class="leading-4">{{ $sks }}</span>
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
            <p class="text-xs font-semibold leading-4 text-brand">{{ $type }}</p>
            <p class="mt-1 min-h-10 text-sm font-medium leading-5 text-ink line-clamp-2">{{ $work }}</p>
            {{-- Baris jam (tenggat merah jika ada tugas yang harus dikumpulkan) + QR sejajar --}}
            <div class="mt-2 flex items-center justify-between gap-2">
                @if($hasPendingTask && !empty($dueFormatted))
                    <p class="text-xs font-semibold leading-5 text-rose-600 flex items-center gap-1.5" title="Tenggat Pengumpulan">
                        <svg class="h-3.5 w-3.5 text-rose-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>Tenggat: {{ $dueFormatted }} WIB</span>
                    </p>
                @elseif(!empty($dueFormatted))
                    <p class="text-xs font-medium leading-5 text-muted flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>{{ $dueFormatted }} WIB</span>
                    </p>
                @else
                    <span class="text-xs text-muted">Tidak ada tenggat</span>
                @endif
                @if($isDosen && !empty($enrollmentCode))
                    <button type="button"
                            onclick="event.preventDefault(); event.stopPropagation(); openQrModal('{{ $course['code'] }}', '{{ addslashes($course['title']) }}', '{{ $enrollmentCode }}', '{{ $enrollmentUrl }}', '{{ $qrUrl }}')"
                            title="Tampilkan QR Code Kelas"
                            aria-label="QR Code kelas {{ $course['code'] }}"
                            class="flex items-center gap-1.5 text-xs text-muted border border-line/60 rounded-md px-2 py-1 hover:border-brand hover:text-brand hover:bg-slate-50 transition cursor-pointer shrink-0">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <path d="M14 14h7v7h-7z"/>
                        </svg>
                        <span>QR</span>
                    </button>
                @endif
            </div>
        </a>

        {{-- Footer stats: Mahasiswa (kiri) | Asesmen (kanan) --}}
        <div class="mt-4 pt-3 flex items-center justify-between text-xs font-medium text-muted border-t border-line/60">
            <a href="{{ $targetUrl }}" class="flex items-center gap-1.5 hover:text-ink transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>{{ $studentsCount }}</span>
                <span class="sr-only">mahasiswa</span>
            </a>
            <a href="{{ $targetUrl }}" class="flex items-center gap-1.5 hover:text-ink transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>{{ $assessmentsCount }} Asesmen</span>
            </a>
        </div>
    </div>
</div>

@once
<!-- Modal QR Presensi & Akses Kelas untuk Dosen -->
<div id="dosenQrModal" onclick="if(event.target === this) closeQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl overflow-hidden" onclick="event.stopPropagation()">

        {{-- Header --}}
        <div class="flex items-start justify-between px-5 pt-5 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-ink leading-snug">QR Presensi &amp; Akses Kelas</h2>
                <p id="modalCourseSubtitle" class="text-xs text-muted mt-0.5"></p>
            </div>
            <button type="button" onclick="closeQrModal()"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition text-lg leading-none cursor-pointer -mt-0.5"
                aria-label="Tutup">&times;</button>
        </div>

        {{-- Body --}}
        <div class="px-5 pt-5 pb-4 flex flex-col items-center gap-4">

            {{-- QR Real Scannable --}}
            <div class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-3 shadow-inner w-52 h-52 mx-auto">
                <img id="modalQrImage" src="" alt="QR Code Kelas" class="w-full h-full object-contain">
            </div>

            {{-- Kode Akses --}}
            <div class="w-full text-center space-y-0.5">
                <p class="text-[11px] uppercase tracking-wider font-semibold text-muted">Kode Akses Kelas</p>
                <p id="modalEnrollmentCode" class="text-2xl font-mono font-bold tracking-widest text-ink select-all"></p>
            </div>

            {{-- Deskripsi --}}
            <p class="text-xs text-center text-muted leading-relaxed px-2">
                Mahasiswa dapat memindai kode QR di atas atau memasukkan kode akses kelas untuk mencatat presensi dan bergabung.
            </p>
        </div>

        {{-- Footer tindakan salin --}}
        <div class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4">
            <button id="copyEnrollmentCodeButton" type="button" onclick="copyEnrollmentCode(this)" class="button-secondary inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3"/></svg>
                <span>Salin kode</span>
            </button>
            <button id="copyEnrollmentUrlButton" type="button" onclick="copyEnrollmentUrl(this)" class="button-primary inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>Salin link</span>
            </button>
        </div>
    </div>
</div>

<script>
    let currentEnrollmentUrl = '';
    let currentEnrollmentCode = '';

    function openQrModal(code, title, enrollmentCode, enrollmentUrl, qrUrl) {
        // Subtitle: "Nama MK (KODE)"
        document.getElementById('modalCourseSubtitle').textContent = title + ' (' + code + ')';
        document.getElementById('modalEnrollmentCode').textContent = enrollmentCode;

        // QR real scannable — encode URL enrollment atau fallback kode
        const dataToEncode = enrollmentUrl || (window.location.origin + '/join-kelas/' + enrollmentCode);
        const qrSrc = qrUrl || (
            'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=14&color=102f50&bgcolor=ffffff&data='
            + encodeURIComponent(dataToEncode)
        );
        document.getElementById('modalQrImage').src = qrSrc;

        currentEnrollmentCode = enrollmentCode;
        currentEnrollmentUrl = dataToEncode;

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

    function copyEnrollmentCode(btn) {
        if (!currentEnrollmentCode) return;
        copyQrText(currentEnrollmentCode, btn);
    }

    function copyEnrollmentUrl(btn) {
        if (!currentEnrollmentUrl) return;
        copyQrText(currentEnrollmentUrl, btn);
    }

    async function copyQrText(value, btn) {
        const label = btn?.querySelector('span');
        const originalLabel = label?.textContent;

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(value);
            } else {
                const input = document.createElement('textarea');
                input.value = value;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }

            if (label) label.textContent = 'Tersalin';
        } catch (error) {
            if (label) label.textContent = 'Gagal menyalin';
        } finally {
            if (label) setTimeout(() => { label.textContent = originalLabel; }, 1600);
        }
    }
</script>
@endonce
