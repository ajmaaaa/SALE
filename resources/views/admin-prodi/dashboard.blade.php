@extends('layouts.mahasiswa')

@section('title', 'Dashboard Admin Prodi | SALE')
@section('header', 'Dashboard Admin Program Studi')

@section('content')
<div class="space-y-6 w-full">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
                <span class="flex items-center gap-1.5 font-medium text-slate-500">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Admin Prodi</span>
                </span>
                <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="font-semibold text-slate-800" aria-current="page">
                    Dashboard
                </span>
            </nav>
            <h1 class="page-heading">Tata Kelola Akademik &amp; Kurikulum Prodi</h1>
            <p class="page-description">Kelola kurikulum OBE (CPL &amp; CPMK), penugasan Dosen Ketua &amp; Dosen Anggota kelas, input mahasiswa, serta laporan semesteran.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto sm:ml-auto">
            <a href="{{ route('admin-prodi.kurikulum.index') }}" class="button-secondary text-xs flex-1 sm:flex-initial text-center justify-center">Kelola Kurikulum OBE</a>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-primary text-xs flex-1 sm:flex-initial text-center justify-center">+ Buat Kelas Baru</a>
        </div>
    </header>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Program Studi -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Program Studi</p>
                <div class="mt-3">
                    <span class="text-xl sm:text-2xl font-bold tracking-tight text-ink block truncate" title="{{ $activeProdi?->name ?? 'Program Studi' }}">
                        {{ $activeProdi?->code ?? '-' }}
                    </span>
                    <span class="block text-xs font-medium text-muted mt-0.5 truncate" title="{{ $activeProdi?->name }}">
                        {{ $activeProdi?->name ?? 'Program Studi Terdaftar' }}
                    </span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.kurikulum.index') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Kelola Kurikulum
                </a>
            </div>
        </div>

        <!-- Mata Kuliah & Kelas -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Mata Kuliah &amp; Kelas</p>
                <div class="mt-3 grid grid-cols-2 gap-3 divide-x divide-line/60">
                    <div>
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_matakuliah'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Mata Kuliah</span>
                    </div>
                    <div class="pl-3 sm:pl-4">
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_kelas'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Kelas</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Daftar Kelas
                </a>
            </div>
        </div>

        <!-- Dosen & Mahasiswa -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Dosen &amp; Mahasiswa</p>
                <div class="mt-3 grid grid-cols-2 gap-3 divide-x divide-line/60">
                    <div>
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_dosen'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Dosen</span>
                    </div>
                    <div class="pl-3 sm:pl-4">
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_mahasiswa'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">Mahasiswa</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.users.index') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Input Data
                </a>
            </div>
        </div>

        <!-- Standar Mutu OBE -->
        <div class="surface p-4 sm:p-5 flex flex-col justify-between border border-line/60">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Standar Mutu OBE</p>
                <div class="mt-3 grid grid-cols-2 gap-3 divide-x divide-line/60">
                    <div>
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_cpl'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">CPL</span>
                    </div>
                    <div class="pl-3 sm:pl-4">
                        <span class="text-2xl font-bold tracking-tight text-ink">{{ $stats['total_cpmk'] }}</span>
                        <span class="block text-xs font-medium text-muted mt-0.5">CPMK</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-line/40">
                <a href="{{ route('admin-prodi.kurikulum.index') }}" class="button-secondary w-full text-xs py-2 min-h-9 justify-center">
                    Kelola CPL &amp; CPMK
                </a>
            </div>
        </div>
    </div>




    <!-- Kelas Aktif Terbaru -->
    <div class="surface p-5 border border-line/60">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-ink">Kelas Perkuliahan Aktif Terbaru</h2>
                <p class="text-xs text-muted">Daftar seksi kelas dengan penetapan Dosen Ketua &amp; Dosen Anggota</p>
            </div>
            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="text-xs font-semibold text-brand hover:underline">Lihat Semua Kelas</a>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas/60 text-muted">
                        <th class="px-4 py-3.5 w-32 !align-middle">Kode / Kelas</th>
                        <th class="px-4 py-3.5 !align-middle">Mata Kuliah</th>
                        <th class="px-4 py-3.5 w-48 !align-middle">Dosen Ketua (Koordinator)</th>
                        <th class="px-4 py-3.5 w-44 !align-middle">Dosen Anggota</th>
                        <th class="px-4 py-3.5 text-center w-20 !align-middle">QR</th>
                        <th class="px-4 py-3.5 text-center w-28 !align-middle">Mahasiswa</th>
                        <th class="px-4 py-3.5 text-right w-28 !align-middle">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($recentClasses as $rc)
                    <tr class="hover:bg-canvas/30 transition-colors">
                        <td class="px-4 py-3.5 font-bold text-brand font-mono !align-middle whitespace-nowrap">{{ $rc->display_code }}</td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-semibold text-ink block">{{ $rc->mataKuliah->name }}</span>
                            <span class="block text-[11px] text-muted mt-0.5">{{ $rc->mataKuliah->prodi->name ?? '-' }} ({{ $rc->mataKuliah->sks }} SKS)</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            <span class="font-medium text-ink block">{{ $rc->dosen?->name ?? 'Belum ditentukan' }}</span>
                        </td>
                        <td class="px-4 py-3.5 !align-middle">
                            @php
                                $rcAnggota = $rc->relationLoaded('dosenAnggota') && $rc->dosenAnggota->isNotEmpty()
                                    ? $rc->dosenAnggota
                                    : ($rc->dosenPendamping ? collect([$rc->dosenPendamping]) : collect());
                            @endphp
                            @if($rcAnggota->isNotEmpty())
                                @foreach($rcAnggota as $anggota)
                                    <span class="font-medium text-ink block">{{ $anggota->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted italic text-[11px]">Tidak ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center !align-middle whitespace-nowrap">
                            <button type="button"
                                onclick="showDashboardQrModal('{{ $rc->display_code }}', '{{ addslashes($rc->mataKuliah->name) }}', '{{ $rc->enrollment_code }}', '{{ $rc->enrollment_url }}', '{{ route('kelas.qr', $rc->id) }}')"
                                class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-muted hover:text-brand hover:bg-canvas transition cursor-pointer"
                                title="Tampilkan QR Code">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="3" height="3"/><rect x="19" y="14" width="2" height="2"/><rect x="14" y="19" width="2" height="2"/><rect x="18" y="18" width="3" height="3"/></svg>
                            </button>
                        </td>
                        <td class="px-4 py-3.5 text-center font-bold text-ink !align-middle whitespace-nowrap">
                            {{ $rc->students_count }} <span class="font-normal text-muted text-[11px]">/ {{ $rc->capacity ?? '∞' }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-right !align-middle whitespace-nowrap">
                            <a href="{{ route('admin-prodi.akademik.kelas') }}" class="button-secondary text-[11px] py-1 px-2.5">Kelola</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-muted !align-middle">
                            <p class="text-xs">Belum ada kelas yang dibuat. Buat kelas di menu Kelas &amp; Dosen Pengampu.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal QR Code Kelas (Dashboard) --}}
<div id="dashboardQrModal" onclick="if(event.target === this) closeDashboardQrModal()" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-2xs p-4">
    <div class="surface w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-line max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between px-6 pt-6 pb-4">
            <div>
                <h2 class="text-base font-bold text-ink">QR Code &amp; Akses Kelas</h2>
                <p id="dash_qr_subtitle" class="text-xs text-muted mt-0.5"></p>
            </div>
            <button type="button" onclick="closeDashboardQrModal()"
                    class="text-muted hover:text-ink transition p-1 rounded-lg hover:bg-canvas ml-3 shrink-0"
                    aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="flex justify-center px-6 pb-4">
            <div class="p-4 bg-white border border-line rounded-2xl shadow-xs inline-flex">
                <img id="dash_qr_image" src="" alt="QR Code Akses Kelas" class="h-44 w-44 object-contain">
            </div>
        </div>
        <div class="text-center px-6 pb-4">
            <p class="text-[10px] font-bold text-muted uppercase tracking-widest mb-1">Kode Akses Kelas</p>
            <p id="dash_qr_code_display" class="text-3xl font-bold text-ink tracking-[0.15em] font-mono"></p>
            <p class="mt-3 text-xs text-muted leading-relaxed max-w-[260px] mx-auto">
                Mahasiswa dapat memindai QR Code di atas atau memasukkan kode akses kelas untuk bergabung ke kelas ini.
            </p>
        </div>
        <input type="hidden" id="dash_qr_code">
        <input type="hidden" id="dash_qr_url">
        <div class="flex gap-2 px-6 pb-6">
            <button type="button" onclick="dashCopyCode(this)" id="dashBtnCopyCode"
                    class="flex-1 button-secondary text-xs py-2.5 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <span>Salin Kode</span>
            </button>
            <button type="button" onclick="dashCopyUrl(this)" id="dashBtnCopyUrl"
                    class="flex-1 button-primary text-xs py-2.5 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>Salin Link</span>
            </button>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
function showDashboardQrModal(classCode, mkName, code, url, qrSrc) {
    document.getElementById('dash_qr_subtitle').textContent = mkName + ' (' + classCode + ')';
    document.getElementById('dash_qr_code_display').textContent = code;
    document.getElementById('dash_qr_code').value = code;
    document.getElementById('dash_qr_url').value = url;
    document.getElementById('dash_qr_image').src = qrSrc;
    document.getElementById('dashboardQrModal').classList.remove('hidden');
    document.getElementById('dashboardQrModal').classList.add('flex');
}
function closeDashboardQrModal() {
    document.getElementById('dashboardQrModal').classList.add('hidden');
    document.getElementById('dashboardQrModal').classList.remove('flex');
}

async function dashCopyTextToClipboard(text) {
    if (!text) return false;

    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {}
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '0';
        textarea.style.left = '0';
        textarea.style.width = '2em';
        textarea.style.height = '2em';
        textarea.style.padding = '0';
        textarea.style.border = 'none';
        textarea.style.outline = 'none';
        textarea.style.boxShadow = 'none';
        textarea.style.background = 'transparent';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);

        if (navigator.userAgent.match(/ipad|ipod|iphone/i)) {
            const range = document.createRange();
            range.selectNodeContents(textarea);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            textarea.setSelectionRange(0, 999999);
        } else {
            textarea.focus();
            textarea.select();
        }

        const successful = document.execCommand('copy');
        document.body.removeChild(textarea);
        return successful;
    } catch (err) {
        console.error('Fallback copy error:', err);
        return false;
    }
}

async function dashCopyCode(btn) {
    const code = document.getElementById('dash_qr_code').value;
    const targetBtn = btn || document.getElementById('dashBtnCopyCode');
    const textSpan = targetBtn ? (targetBtn.querySelector('span') || targetBtn) : null;
    const originalText = textSpan ? textSpan.textContent : 'Salin Kode';

    const success = await dashCopyTextToClipboard(code);
    if (success && textSpan) {
        textSpan.textContent = 'Tersalin!';
        setTimeout(() => {
            if (textSpan) textSpan.textContent = originalText;
        }, 1800);
    }
}

async function dashCopyUrl(btn) {
    const url = document.getElementById('dash_qr_url').value;
    const targetBtn = btn || document.getElementById('dashBtnCopyUrl');
    const textSpan = targetBtn ? (targetBtn.querySelector('span') || targetBtn) : null;
    const originalText = textSpan ? textSpan.textContent : 'Salin Link';

    const success = await dashCopyTextToClipboard(url);
    if (success && textSpan) {
        textSpan.textContent = 'Tersalin!';
        setTimeout(() => {
            if (textSpan) textSpan.textContent = originalText;
        }, 1800);
    }
}
</script>
@endsection
