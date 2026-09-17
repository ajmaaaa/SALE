@extends('layouts.mahasiswa')

@section('title', 'Matriks Penilaian OBE (Versi C) | ' . $section->display_code . ' | SALE')
@section('header', 'Matriks Penilaian OBE')

@section('content')
<div class="space-y-6">
    @include('dosen.partials.header')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="section-heading">1. Rancangan Matriks Penilaian (Versi C)</h2>
            <p class="text-sm text-muted">
                Tentukan alokasi bobot kontribusi setiap instrumen asesmen terhadap masing-masing CPMK. Grand total seluruh sel wajib berjumlah tepat <strong>100%</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.remove('hidden')" class="button-secondary text-xs">
                + Tambah Komponen Asesmen
            </button>
            <button type="submit" form="form-matriks-obe" class="button-primary text-xs shadow-sm">
                Simpan Matriks Bobot
            </button>
        </div>
    </div>

    @if(session('notice'))
        <div class="rounded-xl border border-brand/30 bg-brand-soft/40 p-4 text-sm text-brand font-medium flex items-center gap-2">
            <svg class="h-5 w-5 shrink-0 text-brand" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('notice') }}</span>
        </div>
    @endif

    @if($cpmks->isEmpty())
        <div class="surface p-10 text-center">
            <h3 class="section-heading">Belum Ada CPMK untuk Mata Kuliah Ini</h3>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Tambahkan CPMK mata kuliah ini terlebih dahulu atau hubungi koordinator prodi.
            </p>
        </div>
    @elseif($assessments->isEmpty())
        <div class="surface p-10 text-center space-y-3">
            <h3 class="section-heading">Belum Ada Komponen Asesmen</h3>
            <p class="text-sm text-muted max-w-md mx-auto">
                Tambahkan instrumen asesmen (Tugas, Kuis, UTS, UAS, Proyek) untuk memulai pembobotan pada matriks.
            </p>
            <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.remove('hidden')" class="button-primary text-xs inline-flex">
                + Tambah Komponen Asesmen Sekarang
            </button>
        </div>
    @else
        {{-- ============================================================ --}}
        {{-- MATRIKS PENILAIAN VERSI C INTERAKTIF (SINGLE SOURCE OF TRUTH) --}}
        {{-- ============================================================ --}}
        <form id="form-matriks-obe" method="POST" action="{{ route('dosen.penilaian.matriks.save', $section->id) }}">
            @csrf

            <div class="surface overflow-x-auto rounded-xl border border-line shadow-sm">
                <table class="admin-table text-left" id="matriks-table">
                    <thead>
                        <tr>
                            <th class="min-w-[240px] bg-canvas/60">
                                <span class="text-xs uppercase tracking-wider text-muted font-bold">CPMK \ Komponen Asesmen</span>
                            </th>
                            @foreach($assessments as $assessment)
                                <th class="text-center font-mono min-w-[130px] bg-canvas/30 py-3 px-2 border-l border-line/60">
                                    <div class="font-bold text-ink text-xs">{{ $assessment->name }}</div>
                                    <div class="text-[10px] text-muted font-normal uppercase mt-0.5 tracking-wider">{{ $assessment->type }}</div>
                                    <div class="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-brand bg-brand-soft/60 px-2 py-0.5 rounded">
                                        Bobot: <span id="header-col-{{ $assessment->id }}">{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}</span>%
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-center gap-2">
                                        <button type="button" onclick="hapusAsesmen('{{ $assessment->id }}', '{{ $assessment->name }}')" class="text-[10px] text-rose-600 hover:underline" title="Hapus Asesmen">
                                            Hapus
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                            <th class="text-center font-mono text-xs font-bold bg-brand-soft/40 text-brand min-w-[120px] border-l-2 border-brand/20">
                                Bobot CPMK (%)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalCpmkWeights = 0;
                            $colTotals = array_fill_keys($assessments->pluck('id')->all(), 0.0);
                        @endphp

                        @foreach($cpmks as $cpmk)
                            @php $rowSum = 0.0; @endphp
                            <tr class="hover:bg-canvas/40 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-brand font-mono text-xs">{{ $cpmk->code }}</div>
                                    <div class="text-xs text-muted line-clamp-2 mt-0.5" title="{{ $cpmk->description }}">
                                        {{ $cpmk->description }}
                                    </div>
                                    <div class="text-[10px] text-muted font-mono mt-1">
                                        Ambang: {{ (int)($cpmk->threshold ?: 60) }}
                                    </div>
                                </td>

                                @foreach($assessments as $assessment)
                                    @php
                                        $cellWeight = $obe->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                        $rowSum += $cellWeight;
                                        $colTotals[$assessment->id] += $cellWeight;
                                    @endphp
                                    <td class="text-center p-2 border-l border-line/60">
                                        <input type="number"
                                               step="0.1"
                                               min="0"
                                               max="100"
                                               name="matrix[{{ $assessment->id }}][{{ $cpmk->id }}]"
                                               value="{{ $cellWeight > 0 ? rtrim(rtrim(number_format($cellWeight, 1), '0'), '.') : '' }}"
                                               placeholder="0"
                                               data-assessment-id="{{ $assessment->id }}"
                                               data-cpmk-id="{{ $cpmk->id }}"
                                               class="matrix-cell w-20 text-center font-mono font-bold text-sm py-1.5 px-2 rounded-md border border-line bg-surface focus:border-brand focus:ring-1 focus:ring-brand focus:outline-none transition">
                                    </td>
                                @endforeach

                                <td class="text-center font-mono font-bold text-sm bg-brand-soft/20 text-brand border-l-2 border-brand/20">
                                    <span id="row-total-{{ $cpmk->id }}">{{ rtrim(rtrim(number_format($rowSum, 1), '0'), '.') }}</span>%
                                </td>
                            </tr>
                            @php $totalCpmkWeights += $rowSum; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold bg-canvas/80 border-t-2 border-line">
                            <td class="py-3 px-4 font-bold text-ink text-xs uppercase tracking-wider">
                                Total Bobot Komponen (Asesmen):
                            </td>
                            @foreach($assessments as $assessment)
                                <td class="text-center font-mono font-bold text-ink text-xs border-l border-line/60">
                                    <span id="col-total-{{ $assessment->id }}">{{ rtrim(rtrim(number_format($colTotals[$assessment->id], 1), '0'), '.') }}</span>%
                                </td>
                            @endforeach
                            <td class="text-center font-mono font-extrabold text-sm border-l-2 border-brand/20 text-brand bg-brand-soft/40">
                                <span id="grand-total">{{ rtrim(rtrim(number_format($totalCpmkWeights, 1), '0'), '.') }}</span>%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Floating status bar --}}
            <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl border border-line bg-surface shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-ink">Status Grand Total Matriks:</span>
                    <span id="status-badge" class="px-2.5 py-1 rounded-full text-xs font-bold {{ abs($totalCpmkWeights - 100) < 0.1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                        {{ abs($totalCpmkWeights - 100) < 0.1 ? 'Valid (Tepat 100%)' : 'Belum 100% (Target: 100%)' }}
                    </span>
                    <span id="difference-indicator" class="text-xs font-mono text-muted">
                        {{ abs($totalCpmkWeights - 100) < 0.1 ? '' : 'Selisih: ' . (100 - $totalCpmkWeights) . '%' }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-xs">
                        Lanjut ke Input Nilai
                    </a>
                    <button type="submit" class="button-primary text-xs">
                        Simpan Matriks Penilaian
                    </button>
                </div>
            </div>
        </form>
    @endif

    {{-- Penjelasan Aturan Matriks OBE Sesuai Dokumen Desain --}}
    <div class="rounded-xl border border-line bg-surface p-4 text-xs text-muted space-y-1.5">
        <div class="font-semibold text-ink flex items-center gap-1.5">
            <svg class="h-4 w-4 text-brand" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <span>Prinsip Matriks Penilaian OBE (Versi C):</span>
        </div>
        <p>
            1. <strong>Grand Total 100%:</strong> Seluruh sel matriks dijumlahkan wajib menghasilkan tepat 100% untuk satu mata kuliah.
        </p>
        <p>
            2. <strong>Total Baris = Bobot CPMK:</strong> Total kontribusi satu CPMK terhadap nilai akhir mata kuliah. Nilai ini menjadi pembobot resmi di Rekap Capaian CPMK.
        </p>
        <p>
            3. <strong>Total Kolom = Bobot Komponen Asesmen:</strong> Bobot final satu komponen instrumen penilaian (Tugas, UTS, UAS, PBL, dll.) otomatis tersinkronkan dari total kolom matriks.
        </p>
    </div>
</div>

{{-- MODAL TAMBAH KOMPONEN ASESMEN --}}
<div id="modal-tambah-asesmen" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 hidden backdrop-blur-sm">
    <div class="surface max-w-md w-full p-6 space-y-4 rounded-2xl shadow-xl border border-line">
        <div class="flex items-center justify-between border-b border-line pb-3">
            <h3 class="text-base font-bold text-ink">+ Tambah Komponen Asesmen</h3>
            <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.add('hidden')" class="text-muted hover:text-ink text-lg">&times;</button>
        </div>

        <form method="POST" action="{{ route('dosen.penilaian.asesmen.quick', $section->id) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label text-xs">Nama Asesmen</label>
                <input class="field text-sm" required name="name" placeholder="Misal: Tugas 1, UTS, Proyek PBL">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label text-xs">Kode Instrumen</label>
                    <input class="field text-sm uppercase font-mono" required maxlength="30" name="code" placeholder="TGS-01">
                </div>
                <div>
                    <label class="form-label text-xs">Jenis Komponen</label>
                    <input class="field text-sm lowercase" required name="type" list="assessment-types" placeholder="tugas">
                    <datalist id="assessment-types">
                        <option value="tugas"><option value="kuis"><option value="uts">
                        <option value="uas"><option value="pbl"><option value="proyek"><option value="partisipasi">
                    </datalist>
                </div>
            </div>

            <p class="text-[11px] text-muted leading-relaxed">
                Setelah komponen dibuat, komponen akan langsung muncul sebagai kolom baru pada matriks di atas untuk Anda atur bobot selnya.
            </p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.add('hidden')" class="button-secondary text-xs">
                    Batal
                </button>
                <button type="submit" class="button-primary text-xs">
                    Tambahkan ke Matriks
                </button>
            </div>
        </form>
    </div>
</div>

{{-- FORM HIDDEN UNTUK HAPUS ASESMEN --}}
<form id="form-delete-asesmen" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('matriks-table');
    if (!table) return;

    const cells = table.querySelectorAll('.matrix-cell');
    const grandTotalEl = document.getElementById('grand-total');
    const statusBadge = document.getElementById('status-badge');
    const diffIndicator = document.getElementById('difference-indicator');

    function recalculateMatrix() {
        const colTotals = {};
        const rowTotals = {};
        let grandTotal = 0;

        cells.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const asmtId = input.dataset.assessmentId;
            const cpmkId = input.dataset.cpmkId;

            colTotals[asmtId] = (colTotals[asmtId] || 0) + val;
            rowTotals[cpmkId] = (rowTotals[cpmkId] || 0) + val;
            grandTotal += val;
        });

        // Update column headers and footers
        for (const [asmtId, total] of Object.entries(colTotals)) {
            const formatted = Math.round(total * 10) / 10;
            const headerEl = document.getElementById(`header-col-${asmtId}`);
            const footerEl = document.getElementById(`col-total-${asmtId}`);
            if (headerEl) headerEl.textContent = formatted;
            if (footerEl) footerEl.textContent = formatted;
        }

        // Update row totals (CPMK)
        for (const [cpmkId, total] of Object.entries(rowTotals)) {
            const formatted = Math.round(total * 10) / 10;
            const rowEl = document.getElementById(`row-total-${cpmkId}`);
            if (rowEl) rowEl.textContent = formatted;
        }

        // Update Grand Total
        const roundedGrand = Math.round(grandTotal * 10) / 10;
        if (grandTotalEl) grandTotalEl.textContent = roundedGrand;

        const diff = Math.round((100 - grandTotal) * 10) / 10;
        if (Math.abs(grandTotal - 100) < 0.1) {
            statusBadge.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
            statusBadge.textContent = 'Valid (Tepat 100%)';
            if (diffIndicator) diffIndicator.textContent = '';
        } else {
            statusBadge.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200';
            statusBadge.textContent = 'Belum 100% (Target: 100%)';
            if (diffIndicator) diffIndicator.textContent = `Selisih: ${diff > 0 ? '+' : ''}${diff}%`;
        }
    }

    cells.forEach(input => {
        input.addEventListener('input', recalculateMatrix);
    });
});

function hapusAsesmen(id, name) {
    if (confirm(`Hapus komponen asesmen "${name}" dari matriks kelas ini?`)) {
        const form = document.getElementById('form-delete-asesmen');
        form.action = `{{ url('dosen/penilaian-kelas/' . $section->id . '/asesmen') }}/${id}`;
        form.submit();
    }
}
</script>
@endsection
