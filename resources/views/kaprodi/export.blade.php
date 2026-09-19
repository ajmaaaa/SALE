@extends('layouts.mahasiswa')

@section('title', 'Export Rekap Nilai OBE | SALE Kaprodi')
@section('header', 'Export Rekap Nilai OBE')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div>
        <h2 class="section-heading text-xl font-bold text-ink">Pusat Ekspor Rekapitulasi Nilai &amp; Capaian OBE</h2>
        <p class="mt-1 text-xs text-muted">Unduh rekapitulasi capaian CPMK per-kelas dan profil ketercapaian CPL tingkat program studi dalam format spreadsheet (CSV) standar akreditasi.</p>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="surface p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Kelas Aktif</p>
                <span class="rounded-full bg-brand-soft p-1.5 text-brand">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-ink">{{ $sections->count() }}</p>
            <p class="mt-1 text-xs text-muted">Kelas perkuliahan semester aktif</p>
        </div>
        <div class="surface p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Standar CPL Prodi</p>
                <span class="rounded-full bg-emerald-50 p-1.5 text-emerald-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15l-2 5l9-13h-6l2-5l-9 13h6z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-ink">{{ $cpls->count() }}</p>
            <p class="mt-1 text-xs text-muted">Butir capaian pembelajaran lulusan</p>
        </div>
        <div class="surface p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Standar Format File</p>
                <span class="rounded-full bg-blue-50 p-1.5 text-blue-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
            </div>
            <p class="mt-2 text-base font-bold text-ink">CSV &middot; UTF-8 BOM</p>
            <p class="mt-1 text-xs text-muted">Delimiter titik koma (;) &mdash; Kompatibel Excel</p>
        </div>
    </div>

    <!-- Export Actions Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        
        <!-- Card 1: Export Rekap CPMK -->
        <div class="surface flex flex-col justify-between p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-soft text-brand">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10M6 14h6"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-ink">Rekap Nilai &amp; Capaian CPMK</h3>
                            <p class="text-xs text-muted">Per-Kelas / Mata Kuliah</p>
                        </div>
                    </div>
                    <span class="rounded bg-brand-soft px-2 py-0.5 text-[11px] font-semibold text-brand">Per-Kelas</span>
                </div>

                <p class="text-xs leading-relaxed text-muted">
                    Mengekspor daftar seluruh mahasiswa dalam kelas terpilih beserta nilai capaian per-CPMK (dengan persentase bobot), Nilai Akhir (0&ndash;100), Grade (A&ndash;E), Predikat Mutu, dan Persentase Kelengkapan Asesmen (Coverage).
                </p>

                <form id="cpmkExportForm" method="GET" action="{{ route('kaprodi.export.cpmk') }}" class="space-y-4 pt-2">
                    <div>
                        <label for="cpmk_section_id" class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1.5">
                            Pilih Kelas / Mata Kuliah:
                        </label>
                        <select id="cpmk_section_id" name="section_id" required class="w-full rounded-lg border border-line bg-canvas px-3 py-2.5 text-sm text-ink focus:border-brand focus:outline-none">
                            @forelse($sections as $sec)
                                <option value="{{ $sec->id }}" {{ $selectedSection && $selectedSection->id === $sec->id ? 'selected' : '' }}>
                                    {{ $sec->mataKuliah->code }} - {{ $sec->mataKuliah->name }} (Kelas {{ $sec->section_code }}) &middot; Dosen: {{ $sec->dosen->name ?? '—' }}
                                </option>
                            @empty
                                <option value="" disabled>Belum ada kelas aktif di semester ini</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="rounded-lg border border-line/60 bg-[#f9faf9] p-3 text-xs text-muted space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">Cakupan Data:</span>
                            <span>Asesmen RPS aktif</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">Kolom Tambahan:</span>
                            <span>Grade Huruf, Predikat, Coverage %</span>
                        </div>
                    </div>

                    <button type="submit" @if($sections->isEmpty()) disabled @endif class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Download Rekap CPMK (CSV)
                    </button>
                </form>
            </div>
        </div>

        <!-- Card 2: Export Rekap CPL -->
        <div class="surface flex flex-col justify-between p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-line/60 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 16l4-4 4 4 5-6"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-ink">Rekapitulasi Capaian CPL</h3>
                            <p class="text-xs text-muted">Tingkat Program Studi / Per-Kelas</p>
                        </div>
                    </div>
                    <span class="rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Prodi / Kelas</span>
                </div>

                <p class="text-xs leading-relaxed text-muted">
                    Mengekspor skor capaian seluruh CPL program studi per-mahasiswa yang dihitung dari pemetaan asesmen CPMK. Dilengkapi dengan Rata-rata Capaian CPL dan Status Ketercapaian Mutu (&ge; 65).
                </p>

                <form id="cplExportForm" method="GET" action="{{ route('kaprodi.export.cpl') }}" class="space-y-4 pt-2">
                    <div>
                        <label for="cpl_section_id" class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1.5">
                            Cakupan Mahasiswa:
                        </label>
                        <select id="cpl_section_id" name="section_id" class="w-full rounded-lg border border-line bg-canvas px-3 py-2.5 text-sm text-ink focus:border-brand focus:outline-none">
                            <option value="0" selected>
                                Seluruh Mahasiswa Program Studi (Semua Kelas Aktif)
                            </option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}">
                                    Hanya Kelas: {{ $sec->mataKuliah->code }} - {{ $sec->mataKuliah->name }} (Kelas {{ $sec->section_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-lg border border-line/60 bg-[#f9faf9] p-3 text-xs text-muted space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">Butir CPL Terhitung:</span>
                            <span>{{ $cpls->count() }} Butir ({{ $cpls->pluck('code')->implode(', ') }})</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">Standar Kelulusan:</span>
                            <span class="font-medium text-emerald-600">Ambang Batas Nilai &ge; 65</span>
                        </div>
                    </div>

                    <button type="submit" @if($cpls->isEmpty()) disabled @endif class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Download Rekap CPL (CSV)
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Info & Guide Box -->
    <div class="surface p-5 border border-line/80">
        <div class="flex items-start gap-3">
            <span class="rounded-full bg-brand-soft p-1.5 text-brand shrink-0 mt-0.5">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </span>
            <div class="text-xs space-y-2">
                <p class="font-bold text-ink">Panduan Pembacaan Berkas Rekapitulasi Nilai</p>
                <ul class="list-disc list-inside space-y-1 text-muted leading-relaxed">
                    <li>Berkas diunduh dalam format <strong>CSV UTF-8 BOM</strong> dengan pemisah titik koma (<code>;</code>), sehingga otomatis terformat rapi menjadi kolom-kolom saat dibuka langsung menggunakan <strong>Microsoft Excel</strong>, LibreOffice Calc, atau diimpor ke Google Sheets.</li>
                    <li><strong>Rekap CPMK:</strong> Menampilkan capaian per CPMK mahasiswa berdasarkan bobot asesmen yang dikonfigurasi dosen pengampu. Nilai Akhir dihitung proporsional dari seluruh asesmen yang memiliki bobot akhir.</li>
                    <li><strong>Rekap CPL:</strong> Menampilkan rata-rata skor ketercapaian per CPL untuk keperluan dokumen akreditasi LAM-INFOKOM/BAN-PT dan tindak lanjut perbaikan mutu pembelajaran (CQI).</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
