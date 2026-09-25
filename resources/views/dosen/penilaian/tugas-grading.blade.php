@extends('layouts.mahasiswa')

@section('title', 'Penilaian ' . $item['title'] . ' | SALE')
@section('header', 'Penilaian Tugas')

@section('content')
@php
    $poinTugas    = (float) $poinTugas;
    $hasCpmkBobot = !empty($manualWeights);
@endphp
<div class="space-y-6">
    {{-- Breadcrumb & Header --}}
    <div>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1.5" aria-label="Breadcrumb">
            <a href="{{ route('dosen.course.show', $course['id']) }}" class="hover:text-ink transition-colors">{{ $course['code'] }}</a>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $item['title'] }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="page-heading">{{ $item['title'] }}</h1>
                <p class="text-xs text-muted mt-0.5">
                    {{ $course['title'] }}
                    <span class="mx-1">&bull;</span>
                    Poin tugas: <span class="font-semibold text-ink">{{ (int)$poinTugas }}</span>
                    @if($hasCpmkBobot)
                        <span class="mx-1">&bull;</span>
                        {{ count($manualWeights) }} CPMK terpetakan
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                @if($totalPending > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-900 shadow-2xs">
                        {{ $totalPending }} mahasiswa perlu dinilai
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs font-semibold text-emerald-900 shadow-2xs">
                        <svg class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        Semua pengumpulan telah dinilai
                    </span>
                @endif
                <a href="{{ route('dosen.course.show', $course['id']) }}" class="button-secondary text-xs">
                    Kembali ke Course
                </a>
            </div>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if(session('notice'))
        <div class="rounded-xl border border-line bg-canvas p-3.5 text-xs text-ink flex items-center justify-between shadow-2xs">
            <span>{{ session('notice') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-muted hover:text-ink text-xs font-semibold">Tutup</button>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-line/80 flex items-center gap-6 text-sm" role="tablist">
        <button type="button" id="tab-btn-pending" onclick="switchTab('pending')"
            class="tab-btn pb-3 font-semibold border-b-2 border-brand text-brand transition-colors cursor-pointer flex items-center gap-2">
            <span>Perlu Dinilai</span>
            @if($totalPending > 0)
                <span class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">{{ $totalPending }}</span>
            @endif
        </button>
        <button type="button" id="tab-btn-results" onclick="switchTab('results')"
            class="tab-btn pb-3 font-semibold border-b-2 border-transparent text-muted hover:text-ink transition-colors cursor-pointer flex items-center gap-2">
            <span>Hasil Nilai</span>
            <span class="px-1.5 py-0.5 rounded-full bg-canvas text-muted text-[11px] font-bold">{{ count($results) }}</span>
        </button>
    </div>

    {{-- TAB 1: PERLU DINILAI --}}
    <div id="panel-pending" class="space-y-4">
        @if(count($pendingQueue) === 0)
            <div class="surface p-10 text-center rounded-2xl border border-line/60 space-y-3">
                <div class="mx-auto h-12 w-12 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <h2 class="text-base font-bold text-ink">Semua Pengumpulan Telah Dinilai</h2>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Seluruh pengumpulan tugas mahasiswa telah diberi skor. Nilai tugas dan distribusi ke CPMK sudah terhitung otomatis.
                </p>
                <div class="pt-2">
                    <button type="button" onclick="switchTab('results')" class="button-primary text-xs py-2 px-4 font-semibold">
                        Buka Hasil Nilai
                    </button>
                </div>
            </div>
        @else
            <div class="surface rounded-xl border border-line/60 overflow-hidden shadow-2xs">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th>Mahasiswa</th>
                            <th>Status Pengumpulan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingQueue as $idx => $row)
                            <tr>
                                <td class="text-center font-mono text-xs text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <p class="font-semibold text-ink leading-snug">{{ $row['student']['name'] }}</p>
                                    <p class="font-mono text-xs text-muted">{{ $row['student']['number'] }}</p>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200">
                                        Menunggu penilaian
                                    </span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <button type="button"
                                        onclick="openGradeModal({{ json_encode($row) }})"
                                        class="button-primary text-xs py-1.5 px-3.5 font-semibold inline-flex items-center gap-1">
                                        <span>Nilai</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- TAB 2: HASIL NILAI --}}
    <div id="panel-results" class="hidden space-y-4">
        <div class="surface rounded-xl border border-line/60 overflow-hidden shadow-2xs">
            <table class="admin-table w-full">
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th>Mahasiswa</th>
                        <th class="text-center min-w-[100px]">Skor</th>
                        <th class="text-center min-w-[120px]">Nilai Tugas</th>
                        <th class="text-center min-w-[140px]">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $idx => $row)
                        <tr>
                            <td class="text-center font-mono text-xs text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <p class="font-semibold text-ink leading-snug">{{ $row['student']['name'] }}</p>
                                <p class="font-mono text-xs text-muted">{{ $row['student']['number'] }}</p>
                            </td>
                            <td class="text-center">
                                @if($row['skor'] !== null)
                                    <span class="font-mono text-sm font-bold text-ink">{{ number_format($row['skor'], 0) }}</span>
                                    <span class="block text-[10px] text-muted">dari {{ (int)$poinTugas }}</span>
                                @else
                                    <span class="text-xs text-muted font-mono">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row['nilai_tugas'] !== null)
                                    <span class="font-mono text-sm font-bold text-brand">{{ number_format($row['nilai_tugas'], 2, ',', '.') }}</span>
                                    <span class="block text-[10px] text-muted">skala 100</span>
                                @elseif($row['status_key'] === 'menunggu')
                                    <span class="font-mono text-xs font-semibold text-amber-700">Menunggu</span>
                                @else
                                    <span class="text-xs text-muted font-mono">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center whitespace-nowrap">
                                @if($row['status_key'] === 'selesai')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>Selesai</span>
                                    </span>
                                @elseif($row['status_key'] === 'menunggu')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200">
                                        <span>Menunggu</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-muted bg-canvas border border-line/60">
                                        <span>Belum Dikumpulkan</span>
                                    </span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap space-x-1.5">
                                @if($row['has_submitted'])
                                    <button type="button"
                                        onclick="openDetailModal({{ json_encode($row) }}, {{ $poinTugas }}, {{ json_encode($manualWeights) }})"
                                        class="button-secondary text-xs py-1 px-2.5 font-medium">
                                        Rincian
                                    </button>
                                @endif
                                @if($row['status_key'] !== 'belum_dikerjakan')
                                    <button type="button" onclick="openGradeModal({{ json_encode($row) }})"
                                        class="{{ $row['status_key'] === 'selesai' ? 'button-secondary' : 'button-primary' }} text-xs py-1 px-2.5 font-semibold">
                                        {{ $row['status_key'] === 'selesai' ? 'Ubah Skor' : 'Nilai' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL INPUT SKOR TUNGGAL --}}
<dialog id="grade-modal" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/40 max-w-md w-[calc(100%-2rem)] h-fit overflow-hidden flex flex-col">
    <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30">
        <div>
            <h3 class="text-sm font-bold text-ink" id="grade-modal-title">Input Skor Tugas</h3>
            <p class="text-xs text-muted" id="grade-modal-subtitle"></p>
        </div>
        <button type="button" onclick="document.getElementById('grade-modal').close()"
            class="h-7 w-7 rounded-lg text-muted hover:text-ink hover:bg-canvas flex items-center justify-center font-bold text-sm cursor-pointer" aria-label="Tutup">
            &times;
        </button>
    </div>

    <form id="grade-form" method="POST" class="p-6 space-y-5">
        @csrf
        <div>
            <label class="form-label" for="skor-input">Skor Tugas</label>
            <p class="text-xs text-muted mb-2" id="grade-poin-hint">Masukkan skor antara 0 dan 100</p>
            <div class="flex items-center gap-3">
                <input type="number" id="skor-input" name="skor" min="0" step="0.01"
                    class="field w-32 text-center font-mono text-lg font-bold text-ink" placeholder="0" required>
                <span class="text-sm text-muted font-medium" id="grade-poin-label">/ 100</span>
            </div>
            <div id="grade-preview" class="mt-3 hidden rounded-lg bg-canvas border border-line/50 p-3 text-xs space-y-1.5">
                <p class="text-muted">Nilai tugas yang akan tersimpan:</p>
                <p class="font-mono font-bold text-brand text-lg" id="grade-nilai-preview">&mdash;</p>
                <p class="text-[10px] text-muted font-mono">= skor / poin &times; 100</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-line/60">
            <button type="button" onclick="document.getElementById('grade-modal').close()" class="button-secondary text-xs py-2 px-4">
                Batal
            </button>
            <button type="submit" id="grade-submit-btn" class="button-primary text-xs py-2 px-5 font-semibold">
                Simpan Nilai
            </button>
        </div>
    </form>
</dialog>

{{-- MODAL RINCIAN NILAI --}}
<dialog id="detail-modal" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/40 max-w-lg w-[calc(100%-2rem)] h-fit max-h-[85vh] overflow-hidden flex flex-col">
    <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30">
        <div>
            <h3 class="text-sm font-bold text-ink" id="detail-modal-title">Rincian Nilai Tugas</h3>
            <p class="text-xs text-muted" id="detail-modal-subtitle"></p>
        </div>
        <button type="button" onclick="document.getElementById('detail-modal').close()"
            class="h-7 w-7 rounded-lg text-muted hover:text-ink hover:bg-canvas flex items-center justify-center font-bold text-sm cursor-pointer" aria-label="Tutup">
            &times;
        </button>
    </div>

    <div class="p-6 overflow-y-auto space-y-5 text-xs flex-1">
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-canvas border border-line/60">
                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nilai Tugas</span>
                <span class="text-2xl font-extrabold text-brand mt-1 block" id="detail-nilai-tugas">&mdash;</span>
                <span class="text-[11px] text-muted">Skala 0&ndash;100</span>
            </div>
            <div class="p-4 rounded-xl bg-canvas border border-line/60">
                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Skor Mentah</span>
                <span class="text-2xl font-extrabold text-ink mt-1 block" id="detail-skor">&mdash;</span>
                <span class="text-[11px] text-muted" id="detail-poin-label">dari &mdash;</span>
            </div>
        </div>

        <div class="rounded-lg bg-canvas border border-line/50 p-3.5 space-y-1.5 text-[11px] text-muted">
            <p class="font-bold text-ink text-xs">Formula (desain-tugas.md §4.2)</p>
            <p><span class="font-mono text-ink">nilai_tugas = skor / poin_tugas &times; 100</span></p>
            <p><span class="font-mono text-ink">nilai_cpmk[k] = nilai_tugas &times; bobot_cpmk[k] / 100</span></p>
        </div>

        <div id="detail-cpmk-section" class="hidden">
            <p class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-2">Distribusi ke CPMK</p>
            <div class="rounded-lg border border-line/50 overflow-hidden">
                <table class="w-full text-left text-[11px] border-collapse">
                    <thead>
                        <tr class="bg-canvas/60 border-b border-line/60 text-muted">
                            <th class="py-2 px-3">CPMK</th>
                            <th class="py-2 px-3 text-right">Bobot</th>
                            <th class="py-2 px-3 text-right">Nilai CPMK</th>
                        </tr>
                    </thead>
                    <tbody id="detail-cpmk-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="px-6 py-3 border-t border-line/60 bg-canvas/30 flex items-center justify-end">
        <button type="button" onclick="document.getElementById('detail-modal').close()" class="button-secondary text-xs py-1.5 px-4 font-semibold">
            Tutup
        </button>
    </div>
</dialog>

<script>
    function switchTab(tab) {
        const pPending = document.getElementById('panel-pending');
        const pResults = document.getElementById('panel-results');
        const bPending = document.getElementById('tab-btn-pending');
        const bResults = document.getElementById('tab-btn-results');
        if (tab === 'pending') {
            pPending.classList.remove('hidden');
            pResults.classList.add('hidden');
            bPending.classList.add('border-brand', 'text-brand');
            bPending.classList.remove('border-transparent', 'text-muted');
            bResults.classList.remove('border-brand', 'text-brand');
            bResults.classList.add('border-transparent', 'text-muted');
        } else {
            pPending.classList.add('hidden');
            pResults.classList.remove('hidden');
            bResults.classList.add('border-brand', 'text-brand');
            bResults.classList.remove('border-transparent', 'text-muted');
            bPending.classList.remove('border-brand', 'text-brand');
            bPending.classList.add('border-transparent', 'text-muted');
        }
    }

    const _courseId = {{ $course['id'] }};
    const _itemId   = {{ $item['id'] }};
    const _poin     = {{ $poinTugas }};

    function openGradeModal(row) {
        document.getElementById('grade-modal-title').textContent = 'Input Skor Tugas';
        document.getElementById('grade-modal-subtitle').textContent = row.student.name + ' \u00b7 ' + (row.student.number || '');
        document.getElementById('grade-poin-hint').textContent = 'Skor: 0 sampai ' + _poin;
        document.getElementById('grade-poin-label').textContent = '/ ' + _poin;

        const skorInput = document.getElementById('skor-input');
        skorInput.max = _poin;
        skorInput.value = row.skor !== null && row.skor !== undefined ? row.skor : '';

        function updatePreview() {
            const val = parseFloat(skorInput.value);
            const preview = document.getElementById('grade-preview');
            const nilaiPrev = document.getElementById('grade-nilai-preview');
            if (!isNaN(val) && val >= 0 && val <= _poin) {
                const nilaiTugas = (val / _poin * 100).toFixed(2).replace('.', ',');
                nilaiPrev.textContent = nilaiTugas;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
        skorInput.removeEventListener('input', updatePreview);
        skorInput.addEventListener('input', updatePreview);
        updatePreview();

        const form = document.getElementById('grade-form');
        form.action = '/dosen/course/' + _courseId + '/item/' + _itemId + '/penilaian-tugas/' + row.student.id;

        document.getElementById('grade-submit-btn').textContent = row.skor !== null ? 'Simpan Perubahan' : 'Simpan Nilai';
        document.getElementById('grade-modal').showModal();
    }

    function openDetailModal(row, poin, manualWeights) {
        document.getElementById('detail-modal-title').textContent = 'Rincian Nilai: ' + row.student.name;
        document.getElementById('detail-modal-subtitle').textContent = 'NIM: ' + (row.student.number || '');

        const nilaiEl = document.getElementById('detail-nilai-tugas');
        nilaiEl.textContent = row.nilai_tugas !== null && row.nilai_tugas !== undefined
            ? parseFloat(row.nilai_tugas).toFixed(2).replace('.', ',')
            : '\u2014';

        document.getElementById('detail-skor').textContent = row.skor !== null && row.skor !== undefined ? row.skor : '\u2014';
        document.getElementById('detail-poin-label').textContent = 'dari ' + poin;

        const cpmkSection  = document.getElementById('detail-cpmk-section');
        const tbody        = document.getElementById('detail-cpmk-tbody');
        tbody.innerHTML    = '';

        const keys = manualWeights ? Object.keys(manualWeights) : [];
        if (keys.length > 0 && row.nilai_tugas !== null) {
            cpmkSection.classList.remove('hidden');
            keys.forEach(cCode => {
                const bobot    = manualWeights[cCode];
                const nilaiCpmk = (row.nilai_cpmk && row.nilai_cpmk[cCode] !== undefined)
                    ? row.nilai_cpmk[cCode]
                    : (parseFloat(row.nilai_tugas) * parseFloat(bobot) / 100);
                const tr = document.createElement('tr');
                tr.className = 'border-b border-line/40 hover:bg-slate-50/70';
                tr.innerHTML = `
                    <td class="py-2 px-3 font-semibold text-ink">${cCode}</td>
                    <td class="py-2 px-3 text-right font-mono text-muted">${bobot}%</td>
                    <td class="py-2 px-3 text-right font-mono font-bold text-brand">${parseFloat(nilaiCpmk).toFixed(2).replace('.', ',')}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            cpmkSection.classList.add('hidden');
        }

        document.getElementById('detail-modal').showModal();
    }
</script>
@endsection
