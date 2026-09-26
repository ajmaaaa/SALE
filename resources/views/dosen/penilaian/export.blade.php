@extends('layouts.mahasiswa')

@section('title', 'Export Rekap | SALE')
@section('header', 'Export Rekap')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <header>
        <h2 class="section-heading">5. Export Data Penilaian</h2>
        <p class="mt-1 text-sm text-muted">Pilih jenis rekap data penilaian yang ingin diunduh. Tersedia format <strong>Excel (.xlsx)</strong> lengkap dengan kop surat resmi &amp; header berwarna, serta format <strong>CSV</strong>.</p>
    </header>

    <div class="grid gap-5 sm:grid-cols-2">
        {{-- Rekap Nilai & CPMK --}}
        <div class="surface p-5 rounded-xl border border-line/70 shadow-2xs flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <h3 class="text-sm font-semibold text-ink">Rekap Nilai &amp; CPMK</h3>
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-800 rounded-full border border-emerald-200">OBE Terpadu</span>
                </div>
                <p class="text-xs text-muted leading-relaxed">Nilai akhir, grade, predikat, dan capaian seluruh CPMK mata kuliah beserta bobotnya per mahasiswa.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-line/40">
                <a href="{{ route('dosen.penilaian.export.keseluruhan.excel', $section->id) }}"
                   class="button-primary text-xs flex-1 justify-center items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                    <span>Excel (.xlsx)</span>
                </a>
                <a href="{{ route('dosen.penilaian.export.keseluruhan', [$section->id, 'format' => 'csv']) }}"
                   class="button-secondary text-xs justify-center">
                    CSV
                </a>
            </div>
        </div>

        {{-- Rekap CPMK --}}
        <div class="surface p-5 rounded-xl border border-line/70 shadow-2xs flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <h3 class="text-sm font-semibold text-ink">Rekap Capaian CPMK</h3>
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-800 rounded-full border border-amber-200">Kop Surat &amp; Warna</span>
                </div>
                <p class="text-xs text-muted leading-relaxed">Skor CPMK setiap mahasiswa dengan format pembobotan banner hijau (#92D050) &amp; oranye (#F79646) persis standar Rekap OBE.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-line/40">
                <a href="{{ route('dosen.penilaian.export.cpmk.excel', $section->id) }}"
                   class="button-primary text-xs flex-1 justify-center items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                    <span>Excel (.xlsx)</span>
                </a>
                <a href="{{ route('dosen.penilaian.export.cpmk', [$section->id, 'format' => 'csv']) }}"
                   class="button-secondary text-xs justify-center">
                    CSV
                </a>
            </div>
        </div>

        {{-- Rekap CPL --}}
        <div class="surface p-5 rounded-xl border border-line/70 shadow-2xs flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <h3 class="text-sm font-semibold text-ink">Rekap Capaian CPL</h3>
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-blue-100 text-blue-800 rounded-full border border-blue-200">Ketercapaian</span>
                </div>
                <p class="text-xs text-muted leading-relaxed">Skor CPL setiap mahasiswa berdasarkan kontribusi CPMK beserta status ketercapaian target minimum.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-line/40">
                <a href="{{ route('dosen.penilaian.export.cpl.excel', $section->id) }}"
                   class="button-primary text-xs flex-1 justify-center items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                    <span>Excel (.xlsx)</span>
                </a>
                <a href="{{ route('dosen.penilaian.export.cpl', [$section->id, 'format' => 'csv']) }}"
                   class="button-secondary text-xs justify-center">
                    CSV
                </a>
            </div>
        </div>

        {{-- Nilai per Asesmen --}}
        <div class="surface p-5 rounded-xl border border-line/70 shadow-2xs flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <h3 class="text-sm font-semibold text-ink">Nilai per Asesmen</h3>
                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-purple-100 text-purple-800 rounded-full border border-purple-200">Komponen</span>
                </div>
                <p class="text-xs text-muted leading-relaxed">Nilai setiap komponen asesmen (tugas, kuis, UTS, UAS, PBL) per mahasiswa beserta bobot masing-masing.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-line/40">
                <a href="{{ route('dosen.penilaian.export.nilai.excel', $section->id) }}"
                   class="button-primary text-xs flex-1 justify-center items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                    <span>Excel (.xlsx)</span>
                </a>
                <a href="{{ route('dosen.penilaian.export.nilai', [$section->id, 'format' => 'csv']) }}"
                   class="button-secondary text-xs justify-center">
                    CSV
                </a>
            </div>
        </div>
    </div>

    <div class="surface p-4 rounded-xl border border-line/60 bg-canvas/30 text-xs text-muted flex items-start gap-2.5">
        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <strong class="text-ink">Format Ekspor:</strong> File <strong>Excel (.xlsx)</strong> diformat resmi menggunakan Kop Surat Kementerian &amp; SALE, banner pembobotan hijau (#92D050), garis aksen biru (#00B0F0), serta banner oranye (#F79646) sesuai standar OBE. File <strong>CSV</strong> menggunakan encoding UTF-8 dengan BOM dan delimiter titik koma (;) untuk kompatibilitas data mentah.
        </div>
    </div>
</div>
@endsection
