@extends('layouts.mahasiswa')

@php
    $isRekap = ($mode ?? 'penilaian') === 'rekap';
@endphp

@section('title', ($isRekap ? 'Rekap Nilai' : 'Daftar Kelas') . ' | SALE')
@section('header', $isRekap ? 'Rekap Nilai OBE' : 'Daftar Kelas Saya')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('dosen.dashboard') }}" class="hover:text-brand">Dashboard</a>
                <span>/</span>
                <span class="text-ink font-semibold">{{ $isRekap ? 'Rekap Nilai' : 'Penilaian OBE' }}</span>
            </nav>
            <h1 class="page-heading">{{ $isRekap ? 'Rekap Nilai OBE' : 'Daftar Kelas Saya' }}</h1>
            <p class="page-description">
                {{ $isRekap ? 'Pantau dan evaluasi rekapitulasi ketercapaian CPMK serta CPL mahasiswa untuk setiap kelas yang Anda ampu.' : 'Kelola penilaian berbasis OBE untuk setiap kelas yang Anda ampu pada semester berjalan.' }}
            </p>
        </div>

        @if(! $isRekap)
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <form onsubmit="event.preventDefault(); joinByCode();" class="flex items-center gap-2">
                    <input type="text" id="dosen_enroll_code_input" required placeholder="KODE KELAS (MISAL: GG5BKBCY)" class="field text-xs font-mono font-bold uppercase" style="min-width: 220px; text-transform: uppercase;">
                    <button type="submit" class="button-primary text-xs font-semibold whitespace-nowrap py-2 px-4">
                        Masuk Kelas
                    </button>
                </form>
                <button type="button" onclick="openScanQrModal()" class="button-secondary text-xs font-semibold whitespace-nowrap py-2 px-3 flex items-center justify-center gap-1.5" title="Scan Barcode / QR Code Kelas">
                    <svg class="h-4 w-4 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                    <span>Scan QR</span>
                </button>
            </div>
        @endif
    </header>

    @if($sections->isEmpty())
        <div class="surface p-10 text-center">
            <h2 class="section-heading">Belum ada kelas yang diampu</h2>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Kelas yang ditugaskan kepada Anda oleh Admin Prodi atau kelas yang Anda masuki via Kode/QR akan muncul di sini beserta status penilaiannya.
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($sections as $section)
                @php
                    $progress = $section->grading_progress;
                    $statusLabel = match(true) {
                        $progress === null => 'Belum ada asesmen',
                        $progress >= 100 => 'Penilaian lengkap',
                        $progress > 0 => 'Sedang berjalan',
                        default => 'Belum dinilai',
                    };
                    $statusClasses = match(true) {
                        $progress === null => 'bg-canvas text-muted',
                        $progress >= 100 => 'bg-brand-soft text-brand',
                        $progress > 0 => 'bg-amber-50 text-amber-700',
                        default => 'bg-canvas text-muted',
                    };
                @endphp
                <div class="surface p-5 flex flex-col gap-4 justify-between">
                    @php
                        $currentUserId = auth()->id() ?? (session('auth_user.id') ?? null);
                        $isWakil = $section->dosen_pendamping_id && $section->dosen_pendamping_id == $currentUserId;
                    @endphp
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-brand">{{ $section->mataKuliah->code }}-{{ $section->section_code }}</p>
                                @if($isWakil)
                                    <span class="status bg-purple-50 text-purple-700 border-purple-200 text-[10px]">Dosen Wakil</span>
                                @endif
                            </div>
                            <h2 class="mt-1 text-base font-semibold text-ink leading-snug">{{ $section->mataKuliah->name }}</h2>
                            <p class="mt-1 text-xs text-muted">{{ $section->semester->name }}</p>
                        </div>

                        <div class="space-y-0.5 text-xs text-muted border-t border-line/60 pt-2.5">
                            <p><span class="font-medium text-ink">Dosen Ketua:</span> {{ $section->dosen?->name ?? 'Dosen Pengampu' }}</p>
                            @if($section->dosenPendamping)
                                <p><span class="font-medium text-ink">Dosen Wakil:</span> {{ $section->dosenPendamping->name }}</p>
                            @endif
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-xs pt-1">
                            <div>
                                <dt class="text-muted">Mahasiswa</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $section->students_count }} orang</dd>
                            </div>
                            <div>
                                <dt class="text-muted">Asesmen</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ $section->assessments_count }} dibuat</dd>
                            </div>
                        </dl>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="status {{ $statusClasses }}">{{ $statusLabel }}</span>
                                @if($progress !== null)
                                    <span class="font-semibold text-ink">{{ $progress }}%</span>
                                @endif
                            </div>
                            @if($progress !== null)
                                <div class="h-1.5 w-full rounded-full bg-canvas overflow-hidden" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full bg-brand" style="width: {{ $progress }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        @if(!empty($section->enrollment_code))
                            <div class="pt-2 pb-0.5 flex items-center justify-between text-xs border-t border-line/60" onclick="event.preventDefault(); event.stopPropagation();">
                                <div>
                                    <span class="text-[10px] text-muted uppercase font-bold block">Kode Masuk:</span>
                                    <code class="font-mono font-bold text-ink text-xs tracking-wide">{{ $section->enrollment_code }}</code>
                                </div>
                                <button type="button" 
                                        onclick="event.preventDefault(); event.stopPropagation(); openShareQrModal('{{ $section->display_code }}', '{{ addslashes($section->mataKuliah->name) }}', '{{ $section->enrollment_code }}', '{{ $section->enrollment_url }}', '{{ route('kelas.qr', $section->id) }}')"
                                        class="button-secondary text-[11px] py-1 px-2.5 flex items-center gap-1 shrink-0 font-semibold hover:bg-slate-50 transition shadow-2xs">
                                    <svg class="h-3.5 w-3.5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                                    Bagikan QR
                                </button>
                            </div>
                        @endif

                        @if($isRekap)
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('dosen.penilaian.rekap', $section->id) }}" class="button-primary text-xs py-2 text-center font-semibold">
                                    Rekap CPMK
                                </a>
                                <a href="{{ route('dosen.penilaian.cpl', $section->id) }}" class="button-secondary text-xs py-2 text-center font-semibold">
                                    Rekap CPL
                                </a>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('dosen.course.show', $section->id) }}" class="button-secondary text-xs py-2 text-center font-semibold flex items-center justify-center gap-1.5" title="Buka ruang pembelajaran materi, tugas, dan kuis">
                                    <svg class="h-3.5 w-3.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg>
                                    <span>Masuk Kelas</span>
                                </a>
                                <a href="{{ route('dosen.penilaian.matriks', $section->id) }}" class="button-primary text-xs py-2 text-center font-semibold flex items-center justify-center gap-1.5" title="Kelola matriks bobot dan input nilai OBE">
                                    <span>Kelola Penilaian</span>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Modal Scan Barcode / QR Code untuk Dosen -->
<div id="scanQrModal" onclick="if(event.target === this) closeScanQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl text-left space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-line pb-3">
            <div>
                <h2 class="text-base font-bold text-ink">Masuk Kelas via Barcode / QR</h2>
                <p class="text-xs text-muted">Arahkan kamera atau masukkan kode kelas perkuliahan</p>
            </div>
            <button type="button" onclick="closeScanQrModal()" class="text-muted hover:text-ink text-2xl leading-none">&times;</button>
        </div>

        <div id="scanner_camera_box" class="relative rounded-xl overflow-hidden bg-slate-900 aspect-video flex items-center justify-center text-white">
            <video id="qr_video" class="w-full h-full object-cover hidden" playsinline></video>
            <div id="camera_placeholder" class="text-center p-4">
                <svg class="h-10 w-10 mx-auto text-white/50 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h7v7h-7z"/></svg>
                <p class="text-xs text-white/80">Kamera dinonaktifkan</p>
                <button type="button" onclick="startCameraScanner()" class="mt-2.5 button-primary text-xs py-1.5 px-3">
                    Aktifkan Kamera
                </button>
            </div>
            <div id="camera_scan_overlay" class="absolute inset-0 pointer-events-none hidden border-2 border-brand/60 rounded-xl m-4 flex items-center justify-center">
                <div class="w-48 h-48 border-2 border-white/80 rounded-lg animate-pulse"></div>
            </div>
        </div>

        <div class="space-y-2">
            <p class="text-xs font-semibold text-muted uppercase tracking-wider">Atau Masukkan Kode Kelas</p>
            <form onsubmit="event.preventDefault(); submitModalCode();" class="flex gap-2">
                <input type="text" id="modal_code_input" placeholder="GG5BKBCY atau tempel tautan" class="field text-xs font-mono font-bold uppercase flex-1">
                <button type="submit" class="button-primary text-xs px-4 py-2 font-semibold">
                    Masuk
                </button>
            </form>
        </div>

        <div class="pt-2 border-t border-line flex justify-end">
            <button type="button" onclick="closeScanQrModal()" class="button-secondary text-xs py-2 px-4">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Modal Barcode / QR Code untuk Dosen Bagikan -->
<div id="shareQrModal" onclick="if(event.target === this) closeShareQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="surface w-full max-w-md p-6 shadow-2xl text-center space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-line pb-2">
            <h2 class="text-base font-bold text-ink">Bagikan Barcode / QR Code Kelas</h2>
            <button type="button" onclick="closeShareQrModal()" class="text-muted hover:text-ink text-xl">&times;</button>
        </div>

        <div>
            <span id="share_qr_badge" class="px-2 py-0.5 rounded bg-brand text-white font-mono font-bold text-xs"></span>
            <h3 id="share_qr_title" class="mt-1 text-base font-semibold text-ink"></h3>
            <p class="text-xs text-muted mt-1">Kirimkan link atau tampilkan QR Code ini kepada mahasiswa dan dosen pendamping untuk bergabung ke kelas ini.</p>
        </div>

        <div class="flex justify-center p-3 bg-white rounded-xl border border-line">
            <img id="share_qr_img" src="" alt="QR Code" class="h-44 w-44 object-contain">
        </div>

        <div class="text-left space-y-1">
            <label class="text-[11px] font-semibold text-muted uppercase">Kode Masuk Kelas:</label>
            <div class="flex items-center gap-2">
                <input type="text" id="share_qr_code_field" readonly class="field text-xs font-mono font-bold bg-canvas text-ink flex-1">
                <button type="button" onclick="copyShareCode()" class="button-secondary text-xs py-2 px-3">Salin</button>
            </div>
        </div>

        <div class="text-left space-y-1">
            <label class="text-[11px] font-semibold text-muted uppercase">Tautan Gabung Langsung:</label>
            <div class="flex items-center gap-2">
                <input type="text" id="share_qr_url_field" readonly class="field text-xs font-mono bg-canvas text-ink flex-1">
                <button type="button" onclick="copyShareUrl()" class="button-secondary text-xs py-2 px-3">Salin</button>
            </div>
        </div>

        <div class="pt-2">
            <button type="button" onclick="closeShareQrModal()" class="button-primary text-xs w-full py-2">Tutup</button>
        </div>
    </div>
</div>

<script>
    function joinByCode() {
        const input = document.getElementById('dosen_enroll_code_input');
        let code = (input ? input.value : '').trim();
        if (!code) return;
        if (code.includes('/join-kelas/')) {
            const parts = code.split('/join-kelas/');
            code = parts[parts.length - 1];
        }
        window.location.href = "{{ url('/join-kelas') }}/" + encodeURIComponent(code);
    }

    function submitModalCode() {
        const input = document.getElementById('modal_code_input');
        let code = (input ? input.value : '').trim();
        if (!code) return;
        if (code.includes('/join-kelas/')) {
            const parts = code.split('/join-kelas/');
            code = parts[parts.length - 1];
        }
        window.location.href = "{{ url('/join-kelas') }}/" + encodeURIComponent(code);
    }

    let cameraStream = null;

    function openScanQrModal() {
        const modal = document.getElementById('scanQrModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeScanQrModal() {
        stopCameraScanner();
        const modal = document.getElementById('scanQrModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function startCameraScanner() {
        const video = document.getElementById('qr_video');
        const placeholder = document.getElementById('camera_placeholder');
        const overlay = document.getElementById('camera_scan_overlay');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Browser Anda tidak mendukung akses kamera langsung. Silakan gunakan input kode kelas.');
            return;
        }

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            if (video) {
                video.srcObject = cameraStream;
                video.classList.remove('hidden');
                video.play();
            }
            if (placeholder) placeholder.classList.add('hidden');
            if (overlay) overlay.classList.remove('hidden');

            // Jika browser mendukung BarcodeDetector API bawaan
            if ('BarcodeDetector' in window) {
                const barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
                const interval = setInterval(async () => {
                    if (!cameraStream) {
                        clearInterval(interval);
                        return;
                    }
                    try {
                        const barcodes = await barcodeDetector.detect(video);
                        if (barcodes.length > 0) {
                            clearInterval(interval);
                            let detectedRaw = barcodes[0].rawValue;
                            if (detectedRaw.includes('/join-kelas/')) {
                                window.location.href = detectedRaw;
                            } else {
                                window.location.href = "{{ url('/join-kelas') }}/" + encodeURIComponent(detectedRaw);
                            }
                        }
                    } catch (e) {
                        // Scan loop error silent
                    }
                }, 400);
            }
        } catch (err) {
            alert('Tidak dapat mengakses kamera: ' + err.message);
        }
    }

    function stopCameraScanner() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        const video = document.getElementById('qr_video');
        const placeholder = document.getElementById('camera_placeholder');
        const overlay = document.getElementById('camera_scan_overlay');
        if (video) {
            video.pause();
            video.srcObject = null;
            video.classList.add('hidden');
        }
        if (placeholder) placeholder.classList.remove('hidden');
        if (overlay) overlay.classList.add('hidden');
    }

    function openShareQrModal(displayCode, title, code, url, qrSrc) {
        document.getElementById('share_qr_badge').textContent = displayCode;
        document.getElementById('share_qr_title').textContent = title;
        document.getElementById('share_qr_code_field').value = code;
        document.getElementById('share_qr_url_field').value = url;
        document.getElementById('share_qr_img').src = qrSrc;
        const modal = document.getElementById('shareQrModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeShareQrModal() {
        const modal = document.getElementById('shareQrModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function copyShareCode() {
        const codeInput = document.getElementById('share_qr_code_field');
        if (codeInput) {
            navigator.clipboard.writeText(codeInput.value);
            alert('Kode masuk kelas disalin ke papan klip: ' + codeInput.value);
        }
    }

    function copyShareUrl() {
        const urlInput = document.getElementById('share_qr_url_field');
        if (urlInput) {
            navigator.clipboard.writeText(urlInput.value);
            alert('Tautan bergabung kelas disalin ke papan klip.');
        }
    }
</script>
@endsection
