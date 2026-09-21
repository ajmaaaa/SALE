@extends('layouts.mahasiswa')

@section('title', 'Matriks Penilaian OBE | ' . $section->display_code . ' | SALE')
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
        {{-- MATRIKS PENILAIAN INTERAKTIF (SINGLE SOURCE OF TRUTH) --}}
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
                            @php
                                $roundedGrandTotal = round($totalCpmkWeights, 2);
                                $isExact100 = abs($roundedGrandTotal - 100.0) < 0.01;
                                $isOver100 = $roundedGrandTotal > 100.0 && !$isExact100;
                                $diff = round(abs($roundedGrandTotal - 100.0), 2);
                                $diffFormatted = rtrim(rtrim(number_format($diff, 2), '0'), '.');
                                $grandTotalFormatted = rtrim(rtrim(number_format($roundedGrandTotal, 2), '0'), '.');
                            @endphp
                            <td id="grand-total-cell" class="text-center font-mono font-extrabold text-sm border-l-2 {{ $isExact100 ? 'border-emerald-500/30 text-emerald-700 bg-emerald-50/40' : ($isOver100 ? 'border-rose-500/30 text-rose-700 bg-rose-50/40' : 'border-amber-500/30 text-amber-700 bg-amber-50/40') }}">
                                <span id="grand-total">{{ $grandTotalFormatted }}</span>%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Floating status bar --}}
            <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl border border-line bg-surface shadow-xs">
                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 text-xs">
                    <span class="font-semibold text-ink">Status Grand Total Matriks:</span>
                    <span id="status-badge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $isExact100 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($isOver100 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-800 border border-amber-200') }}">
                        {{ $isExact100 ? 'Valid (Tepat 100%)' : ($isOver100 ? 'Melebihi 100% (' . $grandTotalFormatted . '%)' : 'Belum 100% (' . $grandTotalFormatted . '%)') }}
                    </span>
                    <span id="difference-indicator" class="font-mono text-xs {{ $isExact100 ? 'text-emerald-700 font-medium' : ($isOver100 ? 'text-rose-600 font-semibold' : 'text-amber-700 font-semibold') }}">
                        {{ $isExact100 ? '✓ Siap input nilai' : ($isOver100 ? 'Kelebihan: +' . $diffFormatted . '%' : 'Kekurangan: -' . $diffFormatted . '%') }}
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    <div id="lanjut-nilai-wrapper">
                        @if($isExact100)
                            <a id="btn-lanjut-nilai" href="{{ route('dosen.penilaian.asesmen', $section->id) }}" class="button-secondary text-xs inline-flex items-center gap-1.5 shadow-2xs">
                                <span>Lanjut ke Input Nilai</span>
                                <svg class="h-3.5 w-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            <button id="btn-lanjut-nilai" type="button" disabled
                                    title="{{ $isOver100 ? 'Bobot matriks saat ini melebihi 100% (kelebihan +' . $diffFormatted . '%). Sesuaikan dan simpan matriks agar tepat 100% sebelum dapat menginput nilai.' : 'Bobot matriks belum mencapai 100% (kurang -' . $diffFormatted . '%). Lengkapi dan simpan matriks agar tepat 100% sebelum dapat menginput nilai.' }}"
                                    class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg border border-line bg-canvas/60 px-4 py-2 text-xs font-medium text-muted/60 cursor-not-allowed select-none transition-colors">
                                <svg class="h-3.5 w-3.5 text-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Lanjut ke Input Nilai</span>
                            </button>
                        @endif
                    </div>
                    <button type="submit" class="button-primary text-xs shadow-2xs">
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
            <span>Prinsip Matriks Penilaian OBE:</span>
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
    const grandTotalCell = document.getElementById('grand-total-cell');
    const statusBadge = document.getElementById('status-badge');
    const diffIndicator = document.getElementById('difference-indicator');
    const lanjutWrapper = document.getElementById('lanjut-nilai-wrapper');
    const asesmenUrl = "{{ route('dosen.penilaian.asesmen', $section->id) }}";

    // Track original values to detect if user has modified cells without saving
    const originalValues = new Map();
    cells.forEach(input => {
        originalValues.set(input, input.value);
    });

    function hasUnsavedChanges() {
        for (const [input, origVal] of originalValues.entries()) {
            if (input.value !== origVal) {
                return true;
            }
        }
        return false;
    }

    function setBtnLanjutState(isEnabled, message) {
        if (!lanjutWrapper) return;

        if (isEnabled) {
            lanjutWrapper.innerHTML = `
                <a id="btn-lanjut-nilai" href="${asesmenUrl}" class="button-secondary text-xs inline-flex items-center gap-1.5 shadow-2xs">
                    <span>Lanjut ke Input Nilai</span>
                    <svg class="h-3.5 w-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            `;
        } else {
            lanjutWrapper.innerHTML = `
                <button id="btn-lanjut-nilai" type="button" disabled
                        title="${message}"
                        class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg border border-line bg-canvas/60 px-4 py-2 text-xs font-medium text-muted/60 cursor-not-allowed select-none transition-colors">
                    <svg class="h-3.5 w-3.5 text-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Lanjut ke Input Nilai</span>
                </button>
            `;
        }
    }

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
            const formatted = (Math.round(total * 10) / 10).toString();
            const headerEl = document.getElementById(`header-col-${asmtId}`);
            const footerEl = document.getElementById(`col-total-${asmtId}`);
            if (headerEl) headerEl.textContent = formatted;
            if (footerEl) footerEl.textContent = formatted;
        }

        // Update row totals (CPMK)
        for (const [cpmkId, total] of Object.entries(rowTotals)) {
            const formatted = (Math.round(total * 10) / 10).toString();
            const rowEl = document.getElementById(`row-total-${cpmkId}`);
            if (rowEl) rowEl.textContent = formatted;
        }

        // Update Grand Total with clean rounding (no floating artifacts)
        const roundedGrand = Math.round(grandTotal * 100) / 100;
        const grandFormatted = (Math.round(roundedGrand * 10) / 10).toString();
        if (grandTotalEl) grandTotalEl.textContent = grandFormatted;

        const diff = Math.round(Math.abs(roundedGrand - 100) * 100) / 100;
        const diffFormatted = (Math.round(diff * 10) / 10).toString();

        const isExact = Math.abs(roundedGrand - 100) < 0.01;
        const isOver = roundedGrand > 100 && !isExact;
        const changed = hasUnsavedChanges();

        if (isExact) {
            if (statusBadge) {
                statusBadge.className = 'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200';
                statusBadge.textContent = 'Valid (Tepat 100%)';
            }
            if (diffIndicator) {
                diffIndicator.className = 'font-mono text-xs text-emerald-700 font-medium';
                diffIndicator.textContent = changed ? '✓ Pas 100% (Simpan untuk mengunci)' : '✓ Siap input nilai';
            }
            if (grandTotalCell) {
                grandTotalCell.className = 'text-center font-mono font-extrabold text-sm border-l-2 border-emerald-500/30 text-emerald-700 bg-emerald-50/40';
            }

            if (changed) {
                setBtnLanjutState(false, 'Perubahan matriks belum disimpan. Klik "Simpan Matriks Penilaian" terlebih dahulu.');
            } else {
                setBtnLanjutState(true, '');
            }
        } else if (isOver) {
            if (statusBadge) {
                statusBadge.className = 'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200';
                statusBadge.textContent = `Melebihi 100% (${grandFormatted}%)`;
            }
            if (diffIndicator) {
                diffIndicator.className = 'font-mono text-xs text-rose-600 font-semibold';
                diffIndicator.textContent = `Kelebihan: +${diffFormatted}%`;
            }
            if (grandTotalCell) {
                grandTotalCell.className = 'text-center font-mono font-extrabold text-sm border-l-2 border-rose-500/30 text-rose-700 bg-rose-50/40';
            }
            setBtnLanjutState(false, `Bobot matriks saat ini melebihi 100% (kelebihan +${diffFormatted}%). Sesuaikan dan simpan matriks agar tepat 100% sebelum dapat menginput nilai.`);
        } else {
            if (statusBadge) {
                statusBadge.className = 'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200';
                statusBadge.textContent = `Belum 100% (${grandFormatted}%)`;
            }
            if (diffIndicator) {
                diffIndicator.className = 'font-mono text-xs text-amber-700 font-semibold';
                diffIndicator.textContent = `Kekurangan: -${diffFormatted}%`;
            }
            if (grandTotalCell) {
                grandTotalCell.className = 'text-center font-mono font-extrabold text-sm border-l-2 border-amber-500/30 text-amber-700 bg-amber-50/40';
            }
            setBtnLanjutState(false, `Bobot matriks belum mencapai 100% (kurang -${diffFormatted}%). Lengkapi dan simpan matriks agar tepat 100% sebelum dapat menginput nilai.`);
        }
    }

    cells.forEach(input => {
        input.addEventListener('input', recalculateMatrix);
    });

    const matrixForm = document.getElementById('form-matriks-obe');
    if (matrixForm) {
        matrixForm.addEventListener('submit', function(e) {
            let total = 0;
            cells.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            total = Math.round(total * 100) / 100;
            const diff = Math.round(Math.abs(total - 100) * 100) / 100;
            const formattedTotal = (Math.round(total * 10) / 10).toString();
            const formattedDiff = (Math.round(diff * 10) / 10).toString();

            if (Math.abs(total - 100) > 0.01) {
                e.preventDefault();
                if (total > 100) {
                    alert(`Total bobot matriks penilaian melebihi batas 100% (saat ini ${formattedTotal}%, kelebihan +${formattedDiff}%).\n\nSilakan kurangi bobot sel matriks agar pas tepat 100% sebelum menyimpan.`);
                } else {
                    alert(`Total bobot matriks penilaian belum mencapai 100% (saat ini ${formattedTotal}%, kurang -${formattedDiff}%).\n\nSilakan lengkapi bobot sel matriks agar pas tepat 100% sebelum menyimpan.`);
                }
            }
        });
    }
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
