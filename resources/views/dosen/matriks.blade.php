@extends('layouts.mahasiswa')

@section('title', 'Matriks Penilaian | ' . $section->display_code . ' | SALE')
@section('header', 'Matriks Penilaian')

@section('content')
<div class="space-y-5">
    @include('dosen.partials.header')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="section-heading">1. Rancangan Matriks Penilaian (Versi C)</h2>
            <p class="mt-1 text-sm text-muted">Alokasikan bobot setiap asesmen ke CPMK. Grand total harus tepat <strong class="text-ink">100%</strong>.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.remove('hidden')" class="button-secondary text-xs">
                + Asesmen
            </button>
            <button type="submit" form="form-matriks-obe" class="button-primary text-xs">
                Simpan
            </button>
        </div>
    </div>

    @if(session('notice'))
        <div class="rounded-xl border border-line px-4 py-3 text-sm text-ink flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            {{ session('notice') }}
        </div>
    @endif

    @if($cpmks->isEmpty())
        <div class="surface p-12 text-center">
            <h3 class="text-base font-semibold text-ink mb-1.5">Belum Ada CPMK</h3>
            <p class="text-sm text-muted">Tambahkan CPMK terlebih dahulu atau hubungi koordinator prodi.</p>
        </div>
    @elseif($assessments->isEmpty())
        <div class="surface p-12 text-center space-y-4">
            <h3 class="text-base font-semibold text-ink">Belum Ada Komponen Asesmen</h3>
            <p class="text-sm text-muted">Tambahkan asesmen untuk mulai mengisi matriks.</p>
            <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.remove('hidden')" class="button-primary text-xs">
                + Tambah Asesmen
            </button>
        </div>
    @else
        <form id="form-matriks-obe" method="POST" action="{{ route('dosen.penilaian.matriks.save', $section->id) }}">
            @csrf

            <div class="surface overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-left border-collapse" id="matriks-table">
                    <thead>
                        <tr class="border-b border-line bg-canvas/30">
                            <th class="min-w-[180px] w-56 py-3 px-4 text-xs font-medium text-muted">CPMK</th>
                            @foreach($assessments as $assessment)
                                <th class="group relative text-center min-w-[96px] w-24 py-3 px-2 border-l border-line/50">
                                    <button type="button"
                                        onclick="hapusAsesmen('{{ $assessment->id }}', '{{ $assessment->name }}')"
                                        class="absolute top-1.5 right-1.5 p-1 text-muted/40 hover:text-danger rounded opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Hapus asesmen {{ $assessment->name }}">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    <div class="font-semibold text-ink text-xs truncate px-1" title="{{ $assessment->name }}">{{ $assessment->name }}</div>
                                    <div class="text-xs text-muted mt-0.5">
                                        <span id="header-col-{{ $assessment->id }}" class="font-semibold text-ink">{{ rtrim(rtrim(number_format($assessment->final_weight, 1), '0'), '.') }}</span>%
                                    </div>
                                </th>
                            @endforeach
                            <th class="text-center min-w-[80px] w-20 py-3 px-2 border-l border-line/50 text-xs font-medium text-muted">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalCpmkWeights = 0;
                            $colTotals = array_fill_keys($assessments->pluck('id')->all(), 0.0);
                        @endphp

                        @foreach($cpmks as $cpmk)
                            @php $rowSum = 0.0; @endphp
                            <tr class="border-b border-line/40 last:border-0 hover:bg-canvas/30 transition-colors">
                                <td class="py-2.5 px-4">
                                    <div class="text-xs font-semibold text-brand tracking-wide">{{ $cpmk->code }}</div>
                                    <div class="text-xs text-muted mt-0.5 line-clamp-2" title="{{ $cpmk->description }}">{{ $cpmk->description }}</div>
                                </td>

                                @foreach($assessments as $assessment)
                                    @php
                                        $cellWeight = $obe->assessmentCpmkEffectiveWeight($assessment, $cpmk);
                                        $rowSum += $cellWeight;
                                        $colTotals[$assessment->id] += $cellWeight;
                                    @endphp
                                    <td class="p-1.5 text-center border-l border-line/40">
                                        <input type="number"
                                               step="0.1" min="0" max="100"
                                               name="matrix[{{ $assessment->id }}][{{ $cpmk->id }}]"
                                               value="{{ $cellWeight > 0 ? rtrim(rtrim(number_format($cellWeight, 1), '0'), '.') : '' }}"
                                               placeholder=""
                                               data-assessment-id="{{ $assessment->id }}"
                                               data-cpmk-id="{{ $cpmk->id }}"
                                               class="matrix-cell w-16 h-8 text-center text-xs font-semibold rounded-md border border-line bg-white text-ink focus:border-brand focus:ring-1 focus:ring-brand focus:outline-none transition-all">
                                    </td>
                                @endforeach

                                <td class="text-center border-l border-line/40 py-2.5 px-2">
                                    <span id="row-total-{{ $cpmk->id }}" class="font-semibold text-xs text-ink">{{ rtrim(rtrim(number_format($rowSum, 1), '0'), '.') }}</span><span class="text-xs text-muted">%</span>
                                </td>
                            </tr>
                            @php $totalCpmkWeights += $rowSum; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-line bg-canvas/30">
                            <td class="py-2.5 px-4 text-xs text-muted font-medium">Total per Asesmen</td>
                            @foreach($assessments as $assessment)
                                <td class="text-center py-2.5 px-2 border-l border-line/50">
                                    <span id="col-total-{{ $assessment->id }}" class="font-semibold text-xs text-ink">{{ rtrim(rtrim(number_format($colTotals[$assessment->id], 1), '0'), '.') }}</span><span class="text-xs text-muted">%</span>
                                </td>
                            @endforeach
                            <td class="text-center py-2.5 px-2 border-l border-line/50">
                                <span id="grand-total" class="font-bold text-sm text-ink">{{ rtrim(rtrim(number_format($totalCpmkWeights, 1), '0'), '.') }}</span><span class="text-xs text-muted">%</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Status bar --}}
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 px-1">
                <div class="flex items-center gap-2 text-xs text-muted">
                    <span id="status-text">
                        @if(abs($totalCpmkWeights - 100) < 0.1)
                            Grand total tepat 100%, siap disimpan.
                        @else
                            Grand total belum 100%. <span id="difference-indicator">Selisih {{ number_format(100 - $totalCpmkWeights, 1) }}%.</span>
                        @endif
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-xs">Lanjut ke Input Nilai</a>
                    <button type="submit" class="button-primary text-xs">Simpan Matriks</button>
                </div>
            </div>
        </form>

        {{-- Keterangan --}}
        <div class="border-t border-line/50 pt-3 text-xs text-muted">
            <p>Pastikan total akhir tepat 100%. Total baris adalah bobot tiap CPMK dan total kolom adalah bobot tiap asesmen.</p>
        </div>
    @endif
</div>

{{-- Modal --}}
<div id="modal-tambah-asesmen" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 hidden backdrop-blur-sm">
    <div class="surface max-w-md w-full p-6 space-y-4 rounded-2xl shadow-xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <h3 class="text-base font-bold text-ink">Tambah Asesmen</h3>
            <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.add('hidden')" class="text-muted hover:text-ink text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('dosen.penilaian.asesmen.quick', $section->id) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label text-xs">Nama Asesmen</label>
                <input class="field text-sm" required name="name" placeholder="Tugas 1, UTS, Proyek...">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label text-xs">Kode</label>
                    <input class="field text-sm uppercase font-mono" required maxlength="30" name="code" placeholder="TGS-01">
                </div>
                <div>
                    <label class="form-label text-xs">Jenis</label>
                    <input class="field text-sm lowercase" required name="type" list="assessment-types" placeholder="tugas">
                    <datalist id="assessment-types">
                        <option value="tugas"><option value="kuis"><option value="uts">
                        <option value="uas"><option value="pbl"><option value="proyek">
                    </datalist>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="document.getElementById('modal-tambah-asesmen').classList.add('hidden')" class="button-secondary text-xs">Batal</button>
                <button type="submit" class="button-primary text-xs">Tambahkan</button>
            </div>
        </form>
    </div>
</div>

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
    const statusText = document.getElementById('status-text');

    function recalculateMatrix() {
        const colTotals = {}, rowTotals = {};
        let grandTotal = 0;
        cells.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const asmtId = input.dataset.assessmentId;
            const cpmkId = input.dataset.cpmkId;
            colTotals[asmtId] = (colTotals[asmtId] || 0) + val;
            rowTotals[cpmkId] = (rowTotals[cpmkId] || 0) + val;
            grandTotal += val;
        });
        for (const [asmtId, total] of Object.entries(colTotals)) {
            const f = Math.round(total * 10) / 10;
            const h = document.getElementById(`header-col-${asmtId}`);
            const c = document.getElementById(`col-total-${asmtId}`);
            if (h) h.textContent = f;
            if (c) c.textContent = f;
        }
        for (const [cpmkId, total] of Object.entries(rowTotals)) {
            const el = document.getElementById(`row-total-${cpmkId}`);
            if (el) el.textContent = Math.round(total * 10) / 10;
        }
        const rounded = Math.round(grandTotal * 10) / 10;
        if (grandTotalEl) grandTotalEl.textContent = rounded;
        const diff = Math.round((100 - grandTotal) * 10) / 10;
        if (statusText) {
            if (Math.abs(grandTotal - 100) < 0.1) {
                statusText.textContent = 'Grand total tepat 100%, siap disimpan.';
            } else {
                statusText.textContent = `Grand total belum 100%. Selisih ${diff > 0 ? '+' : ''}${diff}%.`;
            }
        }
    }
    cells.forEach(input => input.addEventListener('input', recalculateMatrix));
});

function hapusAsesmen(id, name) {
    if (confirm(`Hapus asesmen "${name}"?`)) {
        const form = document.getElementById('form-delete-asesmen');
        form.action = `{{ url('dosen/penilaian-kelas/' . $section->id . '/asesmen') }}/${id}`;
        form.submit();
    }
}
</script>
@endsection
