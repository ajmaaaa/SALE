@extends('layouts.mahasiswa')

@section('title', 'Pendaftaran Kelas | SALE')
@section('header', 'Pendaftaran Kelas Perkuliahan')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-4 justify-center">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('mahasiswa.dashboard') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>Dashboard</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800" aria-current="page">
            Status Pendaftaran Kelas
        </span>
    </nav>
    <div class="surface p-8 text-center space-y-6">
        @if($status === 'success')
            <div class="flex items-center justify-center mx-auto text-emerald-600">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div>
                <h1 class="page-heading text-xl">Selamat Datang di Kelas Perkuliahan!</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @elseif($status === 'already_enrolled')
            <div class="flex items-center justify-center mx-auto text-slate-700">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4m0 4h.01M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z"/></svg>
            </div>
            <div>
                <h1 class="page-heading text-xl">Anda Sudah Bergabung</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @elseif($status === 'full')
            <div class="flex items-center justify-center mx-auto text-amber-600">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h1 class="page-heading text-xl">Kapasitas Kelas Terpenuhi</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @elseif($status === 'blocked')
            <div class="flex items-center justify-center mx-auto text-slate-700 py-1">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div>
                <h1 class="page-heading mt-2 text-xl">Akses Bergabung Dibatasi</h1>
                <p class="text-sm text-muted mt-2 leading-relaxed max-w-lg mx-auto">
                    Anda telah dikeluarkan dari kelas ini sebanyak dua kali oleh dosen pengampu. Harap pastikan kembali apakah ini benar kelas yang tercantum pada Kartu Rencana Studi (KRS) Anda semester ini.
                </p>
                @if(isset($latestAppeal) && $latestAppeal)
                    <div class="mt-4 text-center max-w-md mx-auto">
                        @if($latestAppeal->isPending())
                            <p class="text-xs font-medium text-emerald-600">
                                Permohonan verifikasi peserta telah diajukan dan sedang menunggu peninjauan Admin Prodi.
                            </p>
                        @elseif($latestAppeal->status === 'approved')
                            <p class="text-xs font-medium text-emerald-600">
                                Permohonan Anda telah disetujui Admin Prodi. Silakan masuk kembali ke kelas perkuliahan.
                            </p>
                        @else
                            <p class="text-xs font-medium text-rose-600">
                                Permohonan verifikasi peserta ditolak.
                                @if($latestAppeal->admin_notes)
                                    <span class="block text-[11px] text-muted mt-0.5">Alasan: {{ $latestAppeal->admin_notes }}</span>
                                @endif
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="flex items-center justify-center mx-auto text-slate-700">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            </div>
            <div>
                <h1 class="page-heading text-xl">Tautan Khusus Mahasiswa</h1>
                <p class="text-xs text-muted mt-1">{{ $message }}</p>
            </div>
        @endif

        <div class="border-t border-b border-line/60 py-5 text-left space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-line/40">
                <span class="px-2.5 py-1 rounded bg-brand text-white font-mono font-bold text-xs">{{ $section->display_code }}</span>
                <span class="text-xs text-muted">{{ $section->semester->name ?? 'Semester Aktif' }}</span>
            </div>

            <div>
                <h3 class="text-base font-bold text-ink">{{ $section->mataKuliah->name }}</h3>
                <p class="text-xs text-muted">{{ $section->mataKuliah->prodi->name ?? '' }} ({{ $section->mataKuliah->sks }} SKS)</p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                <div>
                    <span class="text-muted text-[11px] block">Dosen Ketua (Koordinator):</span>
                    <span class="font-bold text-ink">{{ $section->dosen?->name ?? '-' }}</span>
                </div>
                <div>
                    @php
                        $allAnggota = $section->relationLoaded('dosenAnggota') && $section->dosenAnggota->isNotEmpty()
                            ? $section->dosenAnggota
                            : ($section->dosenPendamping ? collect([$section->dosenPendamping]) : collect());
                    @endphp
                    <span class="text-muted text-[11px] block">Dosen Anggota:</span>
                    <span class="font-bold text-ink">{{ $allAnggota->isNotEmpty() ? $allAnggota->pluck('name')->join(', ') : 'Tidak ada' }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-center gap-3 pt-2">
            @if($status === 'blocked')
                @if(isset($latestAppeal) && $latestAppeal->isPending())
                    <a href="{{ route('mahasiswa.course.index') }}" class="button-primary text-xs px-5 py-2.5">
                        Kembali ke Halaman Course
                    </a>
                @else
                    <a href="{{ route('mahasiswa.course.index') }}" class="button-secondary text-xs px-5 py-2.5">
                        Bukan Kelas Saya
                    </a>
                    @if(!empty($canAppeal))
                        <button type="button" onclick="document.getElementById('appeal-modal').showModal()" class="button-primary text-xs px-5 py-2.5">
                            Ajukan Verifikasi Peserta
                        </button>
                    @endif
                @endif
            @elseif($isDosen ?? false)
                <a href="{{ route('dosen.penilaian.index') }}" class="button-secondary text-xs px-5 py-2.5">
                    Daftar Kelas Saya
                </a>
                <a href="{{ route('dosen.course.index') }}" class="button-primary text-xs px-5 py-2.5">
                    Buka Course Saya
                </a>
            @else
                <a href="{{ route('mahasiswa.dashboard') }}" class="button-secondary text-xs px-5 py-2.5">
                    Ke Dashboard Utama
                </a>
                <a href="{{ route('mahasiswa.course.index') }}" class="button-primary text-xs px-5 py-2.5">
                    Buka Course Saya
                </a>
            @endif
        </div>
    </div>
</div>

@if($status === 'blocked' && !empty($canAppeal))
{{-- Modal Formulir Verifikasi Peserta (PRD §5.2) --}}
<dialog id="appeal-modal" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 max-w-lg w-[calc(100%-2rem)] overflow-hidden">
    <div class="px-5 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30">
        <h3 class="font-bold text-ink text-sm">Formulir Verifikasi Peserta</h3>
        <button type="button" onclick="document.getElementById('appeal-modal').close()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <form action="{{ route('mahasiswa.course.appeal', $section->id) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4 text-left">
        @csrf
        <div>
            <label for="student_notes" class="block text-xs font-semibold text-ink mb-1">
                Penjelasan / Alasan Sanggahan <span class="text-rose-500">*</span>
            </label>
            <p class="text-[11px] text-muted mb-2">Jelaskan bahwa Anda resmi terdaftar pada mata kuliah dan seksi kelas ini (minimal 20 karakter).</p>
            <textarea id="student_notes" name="student_notes" rows="4" required minlength="20" placeholder="Contoh: Saya adalah mahasiswa aktif semester ini yang terdaftar resmi pada kelas ini sesuai KRS..." class="field w-full text-xs resize-none"></textarea>
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink mb-1">
                Bukti Pendukung / KRS (Opsional)
            </label>
            <p class="text-[11px] text-muted mb-2">Unggah tangkapan layar atau berkas PDF Kartu Rencana Studi resmi (maks. 2MB, format PDF/JPG/PNG).</p>
            <input type="file" id="appeal_attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="hidden" onchange="handleAppealFileChange(this)">
            <div class="rounded-xl border border-dashed border-line bg-canvas/40 p-4 text-center">
                <div id="attachment-empty-state" class="flex flex-col items-center justify-center gap-2">
                    <svg class="h-6 w-6 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <button type="button" onclick="document.getElementById('appeal_attachment').click()" class="button-secondary text-xs py-1.5 px-3 font-semibold text-brand hover:text-brand-dark hover:bg-brand/5 border-brand/30 cursor-pointer">
                        Pilih Berkas KRS
                    </button>
                    <span class="text-[11px] text-muted">Format: PDF, JPG, JPEG, PNG (Maks. 2 MB)</span>
                </div>
                <div id="attachment-selected-state" class="hidden flex items-center justify-between gap-3 text-left bg-white rounded-lg p-2.5 border border-line">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="h-5 w-5 text-brand shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <div class="min-w-0">
                            <p id="attachment-filename" class="text-xs font-semibold text-ink truncate"></p>
                            <p id="attachment-filesize" class="text-[10px] text-muted"></p>
                        </div>
                    </div>
                    <button type="button" onclick="clearAppealAttachment()" class="text-muted hover:text-rose-600 transition p-1 cursor-pointer" title="Hapus berkas">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="document.getElementById('appeal-modal').close()" class="button-secondary text-xs py-2 px-4 cursor-pointer">
                Batal
            </button>
            <button type="submit" class="button-primary text-xs py-2 px-4 cursor-pointer">
                Kirim Permohonan
            </button>
        </div>
    </form>
</dialog>
<script nonce="{{ $cspNonce }}">
    document.getElementById('appeal-modal')?.addEventListener('click', function(e) {
        if (e.target === this) this.close();
    });

    function handleAppealFileChange(input) {
        if (!input || !input.files || !input.files[0]) return;
        const file = input.files[0];
        const maxFileSize = 2 * 1024 * 1024; // 2 MB

        if (file.size > maxFileSize) {
            input.value = '';
            clearAppealAttachment();
            const message = 'Berkas bukti pendukung / KRS tidak dapat diunggah jika ukurannya lebih dari 2 MB.';
            if (typeof window.saleNotice === 'function') {
                window.saleNotice({
                    title: 'Ukuran Berkas Terlalu Besar',
                    message: message,
                    confirmLabel: 'Mengerti'
                });
            } else {
                alert(message);
            }
            return;
        }

        const emptyState = document.getElementById('attachment-empty-state');
        const selectedState = document.getElementById('attachment-selected-state');
        const filenameEl = document.getElementById('attachment-filename');
        const filesizeEl = document.getElementById('attachment-filesize');

        if (emptyState) emptyState.classList.add('hidden');
        if (selectedState) selectedState.classList.remove('hidden');
        if (filenameEl) filenameEl.textContent = file.name;
        if (filesizeEl) filesizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
    }

    function clearAppealAttachment() {
        const input = document.getElementById('appeal_attachment');
        if (input) input.value = '';
        const emptyState = document.getElementById('attachment-empty-state');
        const selectedState = document.getElementById('attachment-selected-state');
        if (emptyState) emptyState.classList.remove('hidden');
        if (selectedState) selectedState.classList.add('hidden');
    }
</script>
@endif
@endsection

