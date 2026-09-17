@extends('layouts.mahasiswa')

@section('title', 'Kelola Rubrik — ' . $assessment->name . ' | SALE')
@section('header', 'Kelola Rubrik')

@section('content')
<div class="space-y-6 max-w-4xl">
    @include('dosen.partials.header')

    <header>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="hover:text-brand">Daftar Asesmen</a>
            <span>/</span>
            <span class="text-ink font-semibold">Rubrik</span>
        </nav>
        <h2 class="section-heading">Rubrik: {{ $assessment->name }}</h2>
        <p class="mt-1 text-sm text-muted">
            {{ $assessment->code }} · {{ ucfirst($assessment->type) }} · Bobot {{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}%
        </p>
    </header>

    @if($errors->any())
        <div class="surface p-4 border-l-4 border-danger">
            <ul class="text-sm text-danger space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('dosen.penilaian.asesmen.rubrik.save', [$section->id, $assessment->id]) }}" id="rubric-form">
        @csrf

        <div class="surface p-5 space-y-4">
            <h3 class="section-heading">Informasi Rubrik</h3>
            <label class="block max-w-md">
                <span class="form-label text-xs">Nama Rubrik</span>
                <input class="field" required maxlength="120" name="rubric_name"
                       value="{{ old('rubric_name', $rubric->name ?? $assessment->name . ' — Rubrik') }}"
                       placeholder="Rubrik Penilaian Tugas 1">
            </label>
        </div>

        <div class="surface p-5 space-y-4 mt-5" id="criteria-section">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="section-heading">Kriteria Penilaian</h3>
                    <p class="text-xs text-muted mt-1">Total bobot semua kriteria harus tepat 100%.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-ink" id="total-weight-display">Total: 0%</span>
                    <button type="button" onclick="addCriterion()" class="button-secondary text-xs">+ Tambah Kriteria</button>
                </div>
            </div>

            <div id="criteria-container" class="space-y-3">
                {{-- Existing criteria or empty --}}
            </div>
        </div>

        <div class="flex items-center justify-between mt-5">
            <p class="text-xs text-muted">Setelah rubrik disimpan, Anda dapat mengisi nilai per kriteria di halaman "Input Nilai Rubrik".</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="quiet-link text-sm">Batal</a>
                <button type="submit" class="button-primary">Simpan Rubrik</button>
            </div>
        </div>
    </form>
</div>

{{-- Criterion row template --}}
<template id="criterion-template">
    <div class="soft-row criterion-row" data-index="__INDEX__">
        <div class="flex items-start justify-between gap-3 mb-3">
            <p class="text-sm font-semibold text-ink">Kriteria <span class="criterion-number"></span></p>
            <button type="button" onclick="removeCriterion(this)" class="text-xs text-danger font-semibold hover:underline">Hapus</button>
        </div>
        <input type="hidden" name="criteria[__INDEX__][id]" value="">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="block">
                <span class="form-label text-xs">Nama Kriteria</span>
                <input class="field text-sm" required maxlength="120" name="criteria[__INDEX__][name]" placeholder="Pemahaman Konsep">
            </label>
            <label class="block">
                <span class="form-label text-xs">Deskripsi (opsional)</span>
                <input class="field text-sm" maxlength="1000" name="criteria[__INDEX__][description]" placeholder="Penjelasan kriteria...">
            </label>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 mt-3">
            <label class="block">
                <span class="form-label text-xs">Bobot (%)</span>
                <input class="field text-sm criterion-weight" required type="number" min="0.01" max="100" step="0.01"
                       name="criteria[__INDEX__][weight]" placeholder="25" oninput="updateTotalWeight()">
            </label>
            <label class="block">
                <span class="form-label text-xs">Skor Maksimal</span>
                <input class="field text-sm" required type="number" min="1" max="1000" step="0.01"
                       name="criteria[__INDEX__][max_score]" value="100" placeholder="100">
            </label>
        </div>
    </div>
</template>

<script>
    let criterionIndex = 0;

    function addCriterion(data = null) {
        const template = document.getElementById('criterion-template').innerHTML;
        const html = template.replace(/__INDEX__/g, criterionIndex);

        const container = document.getElementById('criteria-container');
        const div = document.createElement('div');
        div.innerHTML = html;
        const row = div.firstElementChild;

        if (data) {
            row.querySelector('[name$="[id]"]').value = data.id || '';
            row.querySelector('[name$="[name]"]').value = data.name || '';
            row.querySelector('[name$="[description]"]').value = data.description || '';
            row.querySelector('[name$="[weight]"]').value = data.weight || '';
            row.querySelector('[name$="[max_score]"]').value = data.max_score || 100;
        }

        container.appendChild(row);
        criterionIndex++;
        renumberCriteria();
        updateTotalWeight();
    }

    function removeCriterion(btn) {
        const row = btn.closest('.criterion-row');
        row.remove();
        renumberCriteria();
        updateTotalWeight();
    }

    function renumberCriteria() {
        document.querySelectorAll('.criterion-row').forEach((row, i) => {
            row.querySelector('.criterion-number').textContent = i + 1;
        });
    }

    function updateTotalWeight() {
        let total = 0;
        document.querySelectorAll('.criterion-weight').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        const display = document.getElementById('total-weight-display');
        const formatted = total % 1 === 0 ? total.toFixed(0) : total.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
        display.textContent = 'Total: ' + formatted + '%';
        display.className = Math.abs(total - 100) < 0.01 ? 'text-sm font-semibold text-brand' : 'text-sm font-semibold text-danger';
    }

    // Initialize with existing data or one empty row
    document.addEventListener('DOMContentLoaded', function () {
        @php
            $existingCriteria = $criteria->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'weight' => $c->weight,
                'max_score' => $c->max_score,
            ])->values();
        @endphp
        const existing = @json($existingCriteria);

        if (existing.length > 0) {
            existing.forEach(c => addCriterion(c));
        } else {
            addCriterion();
        }
    });
</script>
@endsection
