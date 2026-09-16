@extends('layouts.mahasiswa')

@section('title', 'Detail Asesmen: ' . $assessment->name . ' | SALE')
@section('header', 'Detail Asesmen')

@section('content')
<div class="space-y-6 max-w-5xl">
    <!-- Breadcrumbs -->
    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $assessment->code }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="page-heading">{{ $assessment->name }} <span class="font-mono text-base font-normal text-muted">({{ $assessment->code }})</span></h1>
                <p class="page-description">{{ $section->mataKuliah->code }}-{{ $section->section_code }} · {{ $section->mataKuliah->name }}</p>
            </div>
            <div class="flex items-center flex-wrap gap-2">
                <a href="{{ route('dosen.penilaian.asesmen.nilai', [$section->id, $assessment->id]) }}" class="button-primary text-xs">Input Nilai</a>
                <a href="{{ route('dosen.penilaian.asesmen.template', [$section->id, $assessment->id]) }}" class="button-secondary text-xs inline-flex items-center gap-1.5" title="Unduh Template Excel/CSV">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Unduh Template
                </a>
                <a href="{{ route('dosen.penilaian.asesmen.edit', [$section->id, $assessment->id]) }}" class="button-secondary text-xs">Ubah Asesmen</a>
                <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-xs">Kembali ke Daftar</a>
            </div>
        </div>
    </header>

    @if(session('notice'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between">
            <span>{{ session('notice') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 space-y-1">
            <div class="font-semibold">Terjadi kesalahan validasi:</div>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Kartu Informasi Asesmen -->
    <section class="surface p-5 space-y-4">
        <h2 class="section-heading">Informasi Asesmen</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-xs text-muted block">Jenis</span>
                <span class="font-medium text-ink capitalize">{{ $assessment->type }}</span>
            </div>
            <div>
                <span class="text-xs text-muted block">Bobot Nilai Akhir</span>
                <span class="font-medium text-ink">{{ rtrim(rtrim(number_format($assessment->final_weight, 2), '0'), '.') }}%</span>
            </div>
            <div>
                <span class="text-xs text-muted block">Status</span>
                <span>
                    @if($assessment->status === 'published')
                        <span class="status bg-brand-soft text-brand">Published</span>
                    @elseif($assessment->status === 'closed')
                        <span class="status bg-canvas text-muted">Closed</span>
                    @else
                        <span class="status bg-amber-50 text-amber-700">Draft</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-xs text-muted block">Metode Penilaian</span>
                <span>
                    @if($assessment->uses_rubric)
                        <span class="status bg-emerald-50 text-emerald-700 font-semibold">Rubrik Aktif</span>
                    @else
                        <span class="status bg-canvas text-muted">Nilai Langsung</span>
                    @endif
                </span>
            </div>
        </div>

        @if($assessment->description)
            <div class="pt-2 border-t border-line/40 text-sm">
                <span class="text-xs text-muted block">Deskripsi / Ketentuan:</span>
                <p class="text-ink mt-0.5">{{ $assessment->description }}</p>
            </div>
        @endif

        <div class="pt-2 border-t border-line/40">
            <span class="text-xs text-muted block mb-1.5">CPMK yang Diukur:</span>
            <div class="flex flex-wrap gap-1.5">
                @forelse($assessment->cpmks as $cpmk)
                    <span class="status bg-brand-soft text-brand text-xs font-medium">
                        {{ $cpmk->code }} ({{ rtrim(rtrim(number_format($cpmk->pivot->weight, 1), '0'), '.') }}%) — {{ $cpmk->description }}
                    </span>
                @empty
                    <span class="text-xs text-muted">Belum ada CPMK yang dipetakan.</span>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Section Rubrik Penilaian -->
    <section class="surface p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-line/50 pb-3">
            <div>
                <h2 class="section-heading flex items-center gap-2">
                    Rubrik Penilaian
                    @if($assessment->uses_rubric)
                        <span class="status bg-emerald-50 text-emerald-700 text-xs">Aktif</span>
                    @else
                        <span class="status bg-canvas text-muted text-xs">Non-aktif</span>
                    @endif
                </h2>
                <p class="text-xs text-muted mt-0.5">
                    Kelola kriteria penilaian dan bobot masing-masing kriteria. Nilai asesmen dihitung otomatis: Σ (Skor Criterion × Bobot Criterion).
                </p>
            </div>

            @if($assessment->uses_rubric)
                <form method="POST" action="{{ route('dosen.penilaian.asesmen.rubrik.destroy', [$section->id, $assessment->id]) }}" onsubmit="return confirm('Nonaktifkan rubrik untuk asesmen ini? Asesmen akan dinilai menggunakan skor langsung.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="button-secondary text-xs text-danger">Nonaktifkan Rubrik</button>
                </form>
            @endif
        </div>

        @if(! $assessment->uses_rubric)
            <!-- Rubrik Belum Aktif -->
            <div class="p-6 text-center rounded-lg border border-line/60 bg-canvas/40 space-y-3">
                <div class="w-10 h-10 mx-auto rounded-full bg-brand-soft flex items-center justify-center text-brand">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-ink">Rubrik Penilaian Belum Diaktifkan</h3>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Saat ini asesmen menggunakan penilaian langsung (skor tunggal per mahasiswa). Aktifkan rubrik untuk menilai berdasarkan kriteria berbobot.
                </p>
                <form method="POST" action="{{ route('dosen.penilaian.asesmen.rubrik.update', [$section->id, $assessment->id]) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enable_only" value="1">
                    <input type="hidden" name="uses_rubric" value="1">
                    <button type="submit" class="button-primary text-xs inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Aktifkan Rubrik Penilaian
                    </button>
                </form>
            </div>
        @else
            <!-- Rubrik Aktif: Form Kriteria -->
            <form method="POST" action="{{ route('dosen.penilaian.asesmen.rubrik.update', [$section->id, $assessment->id]) }}" id="rubric-form" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="uses_rubric" value="1">

                <div class="grid sm:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="form-label text-xs">Nama Rubrik</span>
                        <input class="field text-sm" name="rubric_name" value="{{ old('rubric_name', $rubric->name ?? 'Rubrik Penilaian ' . $assessment->name) }}" required placeholder="Contoh: Rubrik Penilaian Tugas 1">
                    </label>
                    <div class="flex items-end justify-end">
                        <div id="weight-indicator" class="rounded-lg p-2.5 text-xs font-semibold flex items-center gap-1.5">
                            <span id="weight-icon"></span>
                            <span id="weight-text">Total Bobot: 0%</span>
                        </div>
                    </div>
                </div>

                <!-- Empty State jika kriteria belum ada -->
                <div id="criteria-empty-state" class="{{ $criteria->isEmpty() && empty(old('criteria')) ? '' : 'hidden' }} p-8 text-center border-2 border-dashed border-line/70 rounded-lg space-y-3">
                    <div class="w-10 h-10 mx-auto rounded-full bg-canvas flex items-center justify-center text-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-ink">Belum Ada Kriteria Penilaian</h3>
                    <p class="text-xs text-muted max-w-sm mx-auto">
                        Rubrik aktif tetapi belum memiliki kriteria. Tambahkan minimal 1 kriteria dengan total bobot wajib 100%.
                    </p>
                    <button type="button" onclick="addCriterion()" class="button-primary text-xs inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kriteria Pertama
                    </button>
                </div>

                <!-- Tabel Kriteria -->
                <div id="criteria-table-container" class="{{ $criteria->isEmpty() && empty(old('criteria')) ? 'hidden' : '' }} overflow-x-auto">
                    <table class="admin-table w-full" id="criteria-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th class="min-w-[180px]">Nama Kriteria <span class="text-danger">*</span></th>
                                <th class="min-w-[220px]">Deskripsi Indikator (Opsional)</th>
                                <th class="w-28 text-right">Bobot (%) <span class="text-danger">*</span></th>
                                <th class="w-24 text-right">Skor Max</th>
                                <th class="w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="criteria-rows">
                            @php
                                $oldCriteria = old('criteria', null);
                                $itemsToRender = $oldCriteria !== null ? $oldCriteria : $criteria;
                            @endphp

                            @foreach($itemsToRender as $index => $crit)
                                @php
                                    $isModel = is_object($crit);
                                    $cName = $isModel ? $crit->name : ($crit['name'] ?? '');
                                    $cDesc = $isModel ? $crit->description : ($crit['description'] ?? '');
                                    $cWeight = $isModel ? (float)$crit->weight : (float)($crit['weight'] ?? 0);
                                    $cMaxScore = $isModel ? (float)$crit->max_score : (float)($crit['max_score'] ?? 100);
                                    $cOrder = $isModel ? (int)$crit->order : (int)($crit['order'] ?? ($index + 1));
                                @endphp
                                <tr class="criterion-row" data-index="{{ $index }}">
                                    <td class="text-center font-mono text-xs text-muted row-number">
                                        <input type="hidden" name="criteria[{{ $index }}][order]" value="{{ $cOrder }}" class="criterion-order">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <input type="text" class="field text-sm" name="criteria[{{ $index }}][name]" value="{{ $cName }}" required placeholder="Contoh: Pemahaman Konsep">
                                    </td>
                                    <td>
                                        <input type="text" class="field text-sm" name="criteria[{{ $index }}][description]" value="{{ $cDesc }}" placeholder="Keterangan / indikator penilaian">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" max="100" class="field text-sm text-right criterion-weight" name="criteria[{{ $index }}][weight]" value="{{ $cWeight ?: '' }}" required placeholder="0.00" oninput="calculateTotalWeight()">
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" max="1000" class="field text-sm text-right" name="criteria[{{ $index }}][max_score]" value="{{ $cMaxScore ?: 100 }}" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" onclick="removeCriterion(this)" class="p-1 text-muted hover:text-danger rounded" title="Hapus Kriteria">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold bg-canvas/40">
                                <td colspan="3" class="text-right text-xs text-muted pr-3">Total Bobot:</td>
                                <td class="text-right text-sm font-mono" id="total-weight-cell">0%</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                    <button type="button" onclick="addCriterion()" class="button-secondary text-xs inline-flex items-center gap-1.5 self-start">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kriteria
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="submit" id="save-rubric-btn" class="button-primary text-xs">Simpan Rubrik</button>
                        <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-xs">Kembali</a>
                    </div>
                </div>
            </form>
        @endif
    </section>
</div>

<template id="criterion-row-template">
    <tr class="criterion-row" data-index="__INDEX__">
        <td class="text-center font-mono text-xs text-muted row-number">
            <input type="hidden" name="criteria[__INDEX__][order]" value="__ORDER__" class="criterion-order">
            __ORDER__
        </td>
        <td>
            <input type="text" class="field text-sm" name="criteria[__INDEX__][name]" value="" required placeholder="Contoh: Pemahaman Konsep">
        </td>
        <td>
            <input type="text" class="field text-sm" name="criteria[__INDEX__][description]" value="" placeholder="Keterangan / indikator penilaian">
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" max="100" class="field text-sm text-right criterion-weight" name="criteria[__INDEX__][weight]" value="" required placeholder="0.00" oninput="calculateTotalWeight()">
        </td>
        <td>
            <input type="number" step="1" min="1" max="1000" class="field text-sm text-right" name="criteria[__INDEX__][max_score]" value="100" required>
        </td>
        <td class="text-center">
            <button type="button" onclick="removeCriterion(this)" class="p-1 text-muted hover:text-danger rounded" title="Hapus Kriteria">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    </tr>
</template>

<script>
    let criterionIndex = {{ max(1, count($itemsToRender ?? [])) }};

    function calculateTotalWeight() {
        const weightInputs = document.querySelectorAll('.criterion-weight');
        let total = 0;
        weightInputs.forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val)) {
                total += val;
            }
        });

        const formatted = total.toFixed(2).replace(/\.?0+$/, '');
        const totalCell = document.getElementById('total-weight-cell');
        if (totalCell) {
            totalCell.textContent = formatted + '%';
        }

        const indicator = document.getElementById('weight-indicator');
        const text = document.getElementById('weight-text');
        const icon = document.getElementById('weight-icon');

        if (!indicator || !text) return;

        const isExact = Math.abs(total - 100) < 0.01;
        if (isExact) {
            indicator.className = 'rounded-lg px-3 py-1.5 text-xs font-semibold flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200';
            text.textContent = 'Total Bobot: ' + formatted + '% (Tepat 100%)';
            icon.innerHTML = '<svg class="w-4 h-4 inline text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        } else {
            indicator.className = 'rounded-lg px-3 py-1.5 text-xs font-semibold flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200';
            text.textContent = 'Total Bobot: ' + formatted + '% (Wajib 100%)';
            icon.innerHTML = '<svg class="w-4 h-4 inline text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        }
    }

    function renumberRows() {
        const rows = document.querySelectorAll('#criteria-rows .criterion-row');
        rows.forEach((row, idx) => {
            const num = idx + 1;
            const orderInput = row.querySelector('.criterion-order');
            if (orderInput) orderInput.value = num;
            const numCell = row.querySelector('.row-number');
            if (numCell) {
                numCell.innerHTML = '';
                if (orderInput) numCell.appendChild(orderInput);
                numCell.appendChild(document.createTextNode(num));
            }
        });

        const emptyState = document.getElementById('criteria-empty-state');
        const tableContainer = document.getElementById('criteria-table-container');
        if (rows.length === 0) {
            if (emptyState) emptyState.classList.remove('hidden');
            if (tableContainer) tableContainer.classList.add('hidden');
        } else {
            if (emptyState) emptyState.classList.add('hidden');
            if (tableContainer) tableContainer.classList.remove('hidden');
        }
    }

    function addCriterion() {
        const template = document.getElementById('criterion-row-template');
        const container = document.getElementById('criteria-rows');
        const tableContainer = document.getElementById('criteria-table-container');
        const emptyState = document.getElementById('criteria-empty-state');

        if (tableContainer) tableContainer.classList.remove('hidden');
        if (emptyState) emptyState.classList.add('hidden');

        const rowsCount = document.querySelectorAll('#criteria-rows .criterion-row').length;
        const newOrder = rowsCount + 1;
        const newIndex = criterionIndex++;

        let html = template.innerHTML
            .replace(/__INDEX__/g, newIndex)
            .replace(/__ORDER__/g, newOrder);

        const tempDiv = document.createElement('tbody');
        tempDiv.innerHTML = html;
        const newRow = tempDiv.firstElementChild;
        container.appendChild(newRow);

        renumberRows();
        calculateTotalWeight();

        const nameInput = newRow.querySelector('input[type="text"]');
        if (nameInput) nameInput.focus();
    }

    function removeCriterion(btn) {
        const row = btn.closest('.criterion-row');
        if (row) {
            row.remove();
            renumberRows();
            calculateTotalWeight();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        calculateTotalWeight();
    });
</script>
@endsection
