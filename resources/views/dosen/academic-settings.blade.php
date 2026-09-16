@extends('layouts.mahasiswa')
@section('title', 'Kelola CPL, CPMK & Rubrik Penilaian | SALE')
@section('header', 'Perencanaan & Rubrik Penilaian')

@section('content')
<div class="max-w-5xl space-y-6">
    <div class="flex items-center justify-end pb-1">
        <span class="rounded-md bg-brand-soft px-3 py-1 text-xs font-bold text-brand shadow-2xs">
            {{ $course['code'] }}
        </span>
    </div>

    <header>
        <h1 class="page-heading">Perencanaan, CPMK &amp; Rubrik Penilaian</h1>
        <p class="page-description">Kelola Capaian Pembelajaran Lulusan (CPL), Capaian Pembelajaran Mata Kuliah (CPMK), serta tentukan indikator rubrik evaluasi per-CPMK.</p>
    </header>

    <form class="space-y-6" method="post" action="{{ route('dosen.academic.save', $course['id']) }}">
        @csrf

        {{-- 1. CAPAIAN PEMBELAJARAN LULUSAN (CPL) --}}
        <section class="surface p-6 rounded-2xl border border-line/60 space-y-4 shadow-xs" data-repeat-group="cpl">
            <div class="flex items-center justify-between border-b border-line/50 pb-3">
                <div>
                    <h2 class="text-base font-bold text-ink">1. Capaian Pembelajaran Lulusan (CPL)</h2>
                    <p class="mt-0.5 text-xs text-muted">Daftar CPL program studi yang didukung oleh mata kuliah ini.</p>
                </div>
                <button class="button-secondary text-xs py-1.5 px-3 font-semibold" data-add-row type="button">+ Tambah CPL</button>
            </div>

            <div data-rows class="space-y-3">
                @foreach(old('cpl', $config['cpl'] ?? []) as $index => $row)
                    <div data-row class="grid items-start gap-3 sm:grid-cols-[140px_minmax(0,1fr)_32px] p-3 rounded-xl bg-canvas border border-line/40">
                        <label>
                            <span class="form-label text-xs">Kode CPL</span>
                            <input class="field text-xs font-bold" required name="cpl[{{ $index }}][code]" value="{{ $row['code'] }}" placeholder="cth: CPL-01">
                        </label>
                        <label>
                            <span class="form-label text-xs">Deskripsi Capaian Lulusan</span>
                            <input class="field text-xs" required name="cpl[{{ $index }}][description]" value="{{ $row['description'] }}" placeholder="Uraian kompetensi lulusan...">
                        </label>
                        <button type="button" data-remove-row class="mt-6 text-muted hover:text-danger font-bold text-lg text-center" aria-label="Hapus CPL">×</button>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 2. CAPAIAN PEMBELAJARAN MATA KULIAH (CPMK) & RUBRIK INDIKATOR --}}
        <section class="surface p-6 rounded-2xl border border-line/60 space-y-4 shadow-xs" data-repeat-group="cpmk">
            <div class="flex items-center justify-between border-b border-line/50 pb-3">
                <div>
                    <h2 class="text-base font-bold text-ink">2. Capaian Pembelajaran Mata Kuliah (CPMK) &amp; Rubrik Kriteria</h2>
                    <p class="mt-0.5 text-xs text-muted">Atur pemetaan CPL ke CPMK, batas ketercapaian minimal, serta kriteria rubrik penilainnya.</p>
                </div>
                <button class="button-secondary text-xs py-1.5 px-3 font-semibold" data-add-row type="button">+ Tambah CPMK</button>
            </div>

            <div data-rows class="space-y-4">
                @foreach(old('cpmk', $config['cpmk'] ?? []) as $index => $row)
                    <div data-row class="p-4 rounded-xl bg-canvas border border-line/50 space-y-3">
                        <div class="grid items-start gap-3 sm:grid-cols-[130px_130px_minmax(0,1fr)_110px_32px]">
                            <label>
                                <span class="form-label text-xs">Kode CPMK</span>
                                <input class="field text-xs font-bold text-brand" required name="cpmk[{{ $index }}][code]" value="{{ $row['code'] }}" placeholder="cth: CPMK-01">
                            </label>
                            <label>
                                <span class="form-label text-xs">Target CPL</span>
                                <input class="field text-xs" required name="cpmk[{{ $index }}][cpl]" value="{{ $row['cpl'] }}" list="cpl-codes" placeholder="Pilih CPL">
                            </label>
                            <label>
                                <span class="form-label text-xs">Deskripsi Kemampuan CPMK</span>
                                <input class="field text-xs" required name="cpmk[{{ $index }}][description]" value="{{ $row['description'] }}" placeholder="Deskripsi capaian mata kuliah...">
                            </label>
                            <label>
                                <span class="form-label text-xs">Batas Kelulusan</span>
                                <input class="field text-xs text-center font-bold" required type="number" min="0" max="100" step="0.5" name="cpmk[{{ $index }}][threshold]" value="{{ $row['threshold'] ?? 65 }}" placeholder="65">
                            </label>
                            <button type="button" data-remove-row class="mt-6 text-muted hover:text-danger font-bold text-lg text-center" aria-label="Hapus CPMK">×</button>
                        </div>
                        <div class="pt-2 border-t border-line/40">
                            <label>
                                <span class="form-label text-[11px] text-muted">Kriteria / Indokator Rubrik Penilaian (Dipisahkan koma atau baris baru)</span>
                                <input class="field text-xs font-mono" name="cpmk[{{ $index }}][rubric]" value="{{ $row['rubric'] ?? 'Sangat Baik (≥85): Pemahaman utuh & tanpa salah, Baik (70-84): Pemahaman memadai, Perlu Perbaikan (<70): Pemahaman belum memadai' }}" placeholder="Indikator rubrik penilaian...">
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 3. KOMPONEN & BOBOT EVALUASI NILAI --}}
        <section class="surface p-6 rounded-2xl border border-line/60 space-y-4 shadow-xs" data-repeat-group="components">
            <div class="flex items-center justify-between border-b border-line/50 pb-3">
                <div>
                    <h2 class="text-base font-bold text-ink">3. Komponen &amp; Bobot Evaluasi Penilaian</h2>
                    <p class="mt-0.5 text-xs text-muted">Tentukan persentase kontribusi setiap jenis asesmen (Total akumulasi harus 100%).</p>
                </div>
                <button class="button-secondary text-xs py-1.5 px-3 font-semibold" data-add-row type="button">+ Tambah Komponen</button>
            </div>

            <div data-rows class="space-y-3">
                @foreach(old('components', $config['components'] ?? []) as $index => $row)
                    <div data-row class="grid items-start gap-3 sm:grid-cols-[130px_minmax(0,1fr)_120px_32px] p-3 rounded-xl bg-canvas border border-line/40">
                        <label>
                            <span class="form-label text-xs">Kode Komponen</span>
                            <input class="field text-xs font-mono uppercase font-bold" required name="components[{{ $index }}][code]" value="{{ $row['code'] }}" placeholder="cth: uts">
                        </label>
                        <label>
                            <span class="form-label text-xs">Nama Komponen Evaluasi</span>
                            <input class="field text-xs font-medium" required name="components[{{ $index }}][name]" value="{{ $row['name'] }}" placeholder="cth: Ujian Tengah Semester">
                        </label>
                        <label>
                            <span class="form-label text-xs">Bobot Nilai (%)</span>
                            <input class="field text-xs text-center font-bold text-brand" required type="number" min="0" max="100" step="0.5" name="components[{{ $index }}][weight]" value="{{ $row['weight'] }}" data-weight placeholder="20">
                        </label>
                        <button type="button" data-remove-row class="mt-6 text-muted hover:text-danger font-bold text-lg text-center" aria-label="Hapus Komponen">×</button>
                    </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-line/50 flex items-center justify-between">
                <p class="text-xs text-muted">Pastikan total persentase seluruh komponen berjumlah 100%.</p>
                <p class="text-sm font-bold text-ink" data-weight-total>
                    Total Bobot: {{ array_sum(array_column($config['components'] ?? [], 'weight')) }}%
                </p>
            </div>
        </section>

        <datalist id="cpl-codes">
            @foreach($config['cpl'] ?? [] as $cpl)
                <option value="{{ $cpl['code'] }}">
            @endforeach
        </datalist>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ url()->previous() }}" onclick="if(window.history.length > 1 && document.referrer) { window.history.back(); return false; }" class="button-secondary text-xs">Batal</a>
            <button class="button-primary text-xs py-2.5 px-5 font-bold shadow-xs">Simpan Perubahan CPL, CPMK &amp; Rubrik</button>
        </div>
    </form>
</div>
@endsection

