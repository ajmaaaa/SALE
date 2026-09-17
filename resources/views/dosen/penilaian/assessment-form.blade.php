@extends('layouts.mahasiswa')

@section('title', ($assessment ? 'Ubah Asesmen' : 'Tambah Asesmen').' | SALE')
@section('header', $assessment ? 'Ubah Asesmen' : 'Tambah Asesmen')

@section('content')
<div class="space-y-6 max-w-3xl">
    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $assessment ? 'Ubah' : 'Tambah' }}</span>
        </nav>
        <h1 class="page-heading">{{ $assessment ? 'Ubah Asesmen' : 'Tambah Asesmen' }}</h1>
        <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->mataKuliah->name }}</p>
    </header>

    <form method="post" action="{{ $assessment ? route('dosen.penilaian.asesmen.update', [$section->id, $assessment->id]) : route('dosen.penilaian.asesmen.store', $section->id) }}" class="space-y-5">
        @csrf
        @if($assessment)@method('PUT')@endif

        <section class="surface p-5 space-y-4">
            <h2 class="section-heading">Informasi Asesmen</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="form-label text-xs">Kode Asesmen</span>
                    <input class="field" required maxlength="30" name="code" value="{{ old('code', $assessment->code ?? '') }}" placeholder="TGS-01">
                </label>
                <label class="block">
                    <span class="form-label text-xs">Nama Asesmen</span>
                    <input class="field" required maxlength="120" name="name" value="{{ old('name', $assessment->name ?? '') }}" placeholder="Tugas 1">
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block">
                    <span class="form-label text-xs">Jenis Asesmen</span>
                    <input class="field" required maxlength="30" name="type" list="assessment-types" value="{{ old('type', $assessment->type ?? '') }}" placeholder="tugas">
                    <datalist id="assessment-types">
                        <option value="tugas"><option value="kuis"><option value="pbl">
                        <option value="uts"><option value="uas"><option value="proyek"><option value="partisipasi">
                    </datalist>
                </label>
                <label class="block">
                    <span class="form-label text-xs">Bobot Nilai Akhir (%)</span>
                    <input class="field" required type="number" min="0.01" max="100" step="0.01" name="final_weight" value="{{ old('final_weight', $assessment->final_weight ?? '') }}">
                </label>
                <label class="block">
                    <span class="form-label text-xs">Status</span>
                    <select class="field" name="status">
                        @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $assessment->status ?? 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="block">
                <span class="form-label text-xs">Deskripsi (opsional)</span>
                <textarea class="field" name="description" rows="3" placeholder="Ketentuan singkat asesmen ini...">{{ old('description', $assessment->description ?? '') }}</textarea>
            </label>
        </section>

        <section class="surface p-5" data-cpmk-checklist>
            <div class="mb-1 flex items-center justify-between gap-3">
                <h2 class="section-heading">CPMK yang Diukur</h2>
                <p class="text-sm font-semibold text-ink" data-cpmk-total>Total kontribusi: 0%</p>
            </div>
            <p class="text-xs text-muted mb-4">Centang setiap CPMK yang diukur oleh asesmen ini, lalu tentukan bobot kontribusinya. Total wajib tepat 100%.</p>

            @if($cpmks->isEmpty())
                <p class="text-sm text-muted">Belum ada CPMK untuk mata kuliah ini. Tambahkan CPMK terlebih dahulu di tab Pengaturan Penilaian.</p>
            @else
                <div class="space-y-2.5">
                    @foreach($cpmks as $cpmk)
                        <div class="grid items-center gap-3 sm:grid-cols-[28px_minmax(0,1fr)_110px]" data-cpmk-row>
                            <input type="hidden" name="cpmk_selected[{{ $cpmk->id }}]" value="0" data-cpmk-hidden-flag>
                            <input type="checkbox" class="h-4 w-4 rounded border-[#b9c0ca]" data-cpmk-check
                                   @checked(array_key_exists($cpmk->id, $selectedCpmk))>
                            <label class="text-sm text-ink">
                                <span class="font-medium">{{ $cpmk->code }}</span>
                                <span class="text-muted"> — {{ $cpmk->description }}</span>
                            </label>
                            <input class="field text-sm" type="number" min="0" max="100" step="0.01"
                                   name="cpmk[{{ $cpmk->id }}]" value="{{ old('cpmk.'.$cpmk->id, $selectedCpmk[$cpmk->id] ?? '') }}"
                                   data-cpmk-weight placeholder="%">
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="button-primary">Simpan Asesmen</button>
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
