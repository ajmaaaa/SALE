@extends('layouts.mahasiswa')

@section('title', 'Verifikasi Peserta | SALE')
@section('header', 'Verifikasi Peserta')

@section('content')
<div class="space-y-6 w-full">
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
                <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.dashboard') }}">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Admin Prodi</span>
                </a>
                <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="font-semibold text-slate-800" aria-current="page">Verifikasi Peserta</span>
            </nav>
            <h1 class="page-heading">Verifikasi Peserta</h1>
            <p class="page-description">Antrean permohonan mahasiswa yang telah dikeluarkan dua kali dari sebuah kelas dan mengajukan verifikasi untuk bergabung kembali.</p>
        </div>
    </header>

    @if(! $activeProdi)
        @include('admin-prodi.partials.prodi-selector', [
            'hideHeader' => true,
            'menuTitle' => 'Verifikasi Peserta',
            'description' => 'Silakan pilih program studi terlebih dahulu untuk mengelola permohonan verifikasi peserta.',
            'targetRoute' => 'admin-prodi.akademik.verifikasi-peserta',
            'actionLabel' => 'Lihat Permohonan',
        ])
    @else


    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead>
                    <tr class="bg-canvas/60">
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Mahasiswa</th>
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Kelas &amp; Dosen</th>
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Alasan Dosen</th>
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Pembelaan Mahasiswa</th>
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Berkas Bukti</th>
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Waktu Pengajuan</th>
                        <th class="px-4 py-3 text-left font-semibold text-ink text-xs">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-ink text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60 bg-white">
                    @forelse($appeals as $appeal)
                    <tr class="hover:bg-canvas/30 transition">
                        <td class="px-4 py-3 align-middle">
                            <div>
                                <p class="font-semibold text-ink text-xs">{{ $appeal->mahasiswa->name ?? '-' }}</p>
                                <p class="text-[11px] text-muted font-mono">{{ $appeal->mahasiswa->number ?? $appeal->mahasiswa->email ?? '' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-middle">
                            <p class="font-medium text-ink text-xs">{{ $appeal->classSection->display_code ?? '-' }}</p>
                            <p class="text-[11px] text-muted">{{ $appeal->classSection->mataKuliah->name ?? '' }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Dosen: {{ $appeal->classSection->dosen->name ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 align-middle max-w-xs">
                            <p class="text-xs text-ink line-clamp-3">{{ $appeal->kick_reason ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-3 align-middle max-w-xs">
                            <p class="text-xs text-ink line-clamp-3">{{ $appeal->student_notes }}</p>
                        </td>
                        <td class="px-4 py-3 align-middle whitespace-nowrap">
                            @if($appeal->attachment_path)
                                <button type="button"
                                        onclick="document.getElementById('preview-bukti-modal-{{ $appeal->id }}').showModal()"
                                        class="button-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1.5 font-medium text-brand hover:text-brand-dark cursor-pointer shadow-2xs">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span>Lihat KRS</span>
                                </button>
                            @else
                                <span class="text-xs text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle text-xs text-muted whitespace-nowrap">
                            {{ $appeal->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-3 align-middle text-xs">
                            @if($appeal->status === 'pending')
                                <span class="text-amber-600 font-medium">Menunggu</span>
                            @elseif($appeal->status === 'approved')
                                <span class="text-emerald-600 font-medium">Disetujui</span>
                            @else
                                <span class="text-slate-400 font-medium">Ditolak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                            @if($appeal->status === 'pending')
                                <div class="inline-flex items-center gap-2">
                                    {{-- Approve --}}
                                    <form action="{{ route('admin-prodi.akademik.verifikasi-peserta.approve', $appeal->id) }}" method="POST"
                                          data-confirm="Setujui permohonan {{ $appeal->mahasiswa->name ?? 'mahasiswa' }} untuk kelas {{ $appeal->classSection->display_code ?? '' }}?"
                                          data-confirm-title="Setujui Verifikasi"
                                          data-confirm-label="Setujui"
                                          class="inline">
                                        @csrf
                                        <button type="submit" class="button-secondary text-xs py-1 px-2.5 text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50 border border-line cursor-pointer">
                                             Setujui
                                        </button>
                                    </form>
                                    {{-- Reject (opens dialog modal) --}}
                                    <button type="button"
                                            onclick="document.getElementById('reject-modal-{{ $appeal->id }}').showModal()"
                                            class="button-secondary text-xs py-1 px-2.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-line cursor-pointer">
                                        Tolak
                                    </button>
                                </div>
                            @else
                                <div class="text-left text-xs">
                                    @if($appeal->admin_notes)
                                        <p class="text-[11px] text-muted italic">Catatan: {{ $appeal->admin_notes }}</p>
                                    @endif
                                    @if($appeal->reviewer)
                                        <p class="text-[11px] text-muted">oleh {{ $appeal->reviewer->name }}</p>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-muted !align-middle">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="h-8 w-8 text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                                </svg>
                                <p class="text-xs">Belum ada permohonan verifikasi peserta yang masuk.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appeals->hasPages())
            <div class="px-4 py-3 border-t border-line/60">
                {{ $appeals->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Penolakan Permohonan --}}
    @foreach($appeals as $appeal)
        @if($appeal->status === 'pending')
        <dialog id="reject-modal-{{ $appeal->id }}" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 max-w-md w-[calc(100%-2rem)] overflow-hidden">
            <div class="px-5 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30">
                <h3 class="font-bold text-ink text-sm">Tolak Permohonan Verifikasi</h3>
                <button type="button" onclick="document.getElementById('reject-modal-{{ $appeal->id }}').close()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form action="{{ route('admin-prodi.akademik.verifikasi-peserta.reject', $appeal->id) }}" method="POST" class="p-5 space-y-4 text-left">
                @csrf
                <div class="rounded-lg bg-canvas/60 border border-line/70 p-3 text-xs space-y-1">
                    <p><span class="text-muted">Mahasiswa:</span> <strong class="text-ink">{{ $appeal->mahasiswa->name ?? '-' }}</strong> ({{ $appeal->mahasiswa->number ?? $appeal->mahasiswa->email ?? '-' }})</p>
                    <p><span class="text-muted">Kelas:</span> <strong class="text-ink">{{ $appeal->classSection->display_code ?? '-' }}</strong> - {{ $appeal->classSection->mataKuliah->name ?? '-' }}</p>
                </div>
                <div>
                    <label for="admin_notes_{{ $appeal->id }}" class="block text-xs font-semibold text-ink mb-1">
                        Alasan Penolakan <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="admin_notes_{{ $appeal->id }}" name="admin_notes" rows="3" required placeholder="Tuliskan alasan penolakan permohonan verifikasi ini (misal: Berkas bukti tidak valid atau tidak terdaftar resmi)..." class="field w-full text-xs resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" onclick="document.getElementById('reject-modal-{{ $appeal->id }}').close()" class="button-secondary text-xs py-2 px-4 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-800 transition cursor-pointer">
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </dialog>
        @endif

        {{-- Modal Tinjau Berkas Bukti Mahasiswa (Mirip Lembar Jawaban Penilaian) --}}
        @if($appeal->attachment_path)
        <dialog id="preview-bukti-modal-{{ $appeal->id }}" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 max-w-3xl w-[calc(100%-2rem)] max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30 shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-ink">Tinjau Bukti Permohonan Verifikasi</h3>
                    <p class="text-xs text-muted mt-0.5">{{ $appeal->mahasiswa->name ?? 'Mahasiswa' }} · {{ $appeal->classSection->display_code ?? '-' }} ({{ $appeal->classSection->mataKuliah->name ?? '-' }})</p>
                </div>
                <button type="button" onclick="document.getElementById('preview-bukti-modal-{{ $appeal->id }}').close()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup modal">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 space-y-4 text-xs">
                {{-- Info Ringkasan --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-xl border border-line/70 bg-canvas/40">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted block mb-1">Alasan Dikeluarkan Dosen:</span>
                        <p class="text-xs text-ink font-medium bg-white p-2.5 rounded-lg border border-line/60 leading-relaxed">{{ $appeal->kick_reason ?: 'Tidak ada catatan spesifik dari dosen.' }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted block mb-1">Pembelaan Mahasiswa:</span>
                        <p class="text-xs text-ink font-medium bg-white p-2.5 rounded-lg border border-line/60 leading-relaxed">{{ $appeal->student_notes }}</p>
                    </div>
                </div>

                {{-- Pratinjau Berkas Bukti --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-ink">Berkas Bukti Terlampir:</span>
                        <a href="{{ route('admin-prodi.akademik.verifikasi-peserta.attachment', $appeal->id) }}" target="_blank" class="button-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1.5 text-brand hover:underline font-semibold shadow-2xs">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            <span>Buka di Tab Baru</span>
                        </a>
                    </div>
                    @php
                        $ext = strtolower(pathinfo($appeal->attachment_path, PATHINFO_EXTENSION));
                        $isImg = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif']);
                    @endphp
                    <div class="rounded-xl border border-line bg-canvas/20 p-2 overflow-hidden flex items-center justify-center min-h-[300px]">
                        @if($isImg)
                            <img src="{{ route('admin-prodi.akademik.verifikasi-peserta.attachment', $appeal->id) }}" alt="Pratinjau Bukti" class="max-h-[55vh] max-w-full rounded-lg object-contain shadow-xs">
                        @else
                            <iframe src="{{ route('admin-prodi.akademik.verifikasi-peserta.attachment', $appeal->id) }}" class="w-full h-[55vh] rounded-lg border-0 bg-white"></iframe>
                        @endif
                    </div>
                </div>
            </div>
            <div class="px-6 py-3.5 border-t border-line/60 bg-canvas/30 flex items-center justify-between shrink-0">
                <span class="text-xs text-muted">Waktu Pengajuan: {{ $appeal->created_at->format('d M Y H:i') }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('preview-bukti-modal-{{ $appeal->id }}').close()" class="button-secondary text-xs py-1.5 px-3.5 cursor-pointer">
                        Tutup
                    </button>
                    @if($appeal->status === 'pending')
                        <form action="{{ route('admin-prodi.akademik.verifikasi-peserta.approve', $appeal->id) }}" method="POST"
                              data-confirm="Setujui permohonan {{ $appeal->mahasiswa->name ?? 'mahasiswa' }} untuk kelas {{ $appeal->classSection->display_code ?? '' }}?"
                              data-confirm-title="Setujui Verifikasi"
                              data-confirm-label="Setujui"
                              class="inline">
                            @csrf
                            <button type="submit" class="button-primary text-xs py-1.5 px-3.5 cursor-pointer">
                                Setujui
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </dialog>
        @endif
    @endforeach
    <script>
        document.querySelectorAll('dialog[id^="reject-modal-"], dialog[id^="preview-bukti-modal-"]').forEach(dialog => {
            dialog.addEventListener('click', function(e) {
                if (e.target === this) this.close();
            });
        });
    </script>
    @endif
</div>
@endsection
