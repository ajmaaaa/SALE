@extends('layouts.mahasiswa')

@section('title', 'Penilaian ' . $item['title'] . ' | SALE')
@section('header', 'Penilaian Asesmen')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Header --}}
    <div>
        <nav class="flex items-center gap-2 text-xs text-muted mb-1.5" aria-label="Breadcrumb">
            <a href="{{ route('dosen.course.show', $course['id']) }}" class="hover:text-ink transition-colors">{{ $course['code'] }}</a>
            <span>/</span>
            <span class="text-muted">{{ $item['module'] ?? 'Asesmen' }}</span>
            <span>/</span>
            <span class="text-ink font-semibold">{{ $item['title'] }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="page-heading">{{ $item['title'] }}</h1>
                <p class="text-xs text-muted mt-0.5">{{ $course['title'] }} &bull; {{ count($questions) }} butir soal</p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                @if($totalPending > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-900 shadow-2xs">
                        <span>{{ $totalPending }} mahasiswa perlu dinilai</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs font-semibold text-emerald-900 shadow-2xs">
                        <svg class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>Semua jawaban telah dinilai</span>
                    </span>
                @endif
                <a href="{{ route('dosen.course.show', $course['id']) }}" class="button-secondary text-xs">
                    Kembali ke Course
                </a>
            </div>
        </div>
    </div>

    {{-- Alert Notice if any --}}
    @if(session('notice'))
        <div class="rounded-xl border border-line bg-canvas p-3.5 text-xs text-ink flex items-center justify-between shadow-2xs">
            <span>{{ session('notice') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-muted hover:text-ink text-xs font-semibold">Tutup</button>
        </div>
    @endif

    {{-- Tabs Pengalihan: Perlu Dinilai vs Hasil Nilai --}}
    <div class="border-b border-line/80 flex items-center gap-6 text-sm" role="tablist">
        <button type="button" id="tab-btn-pending" onclick="switchGradingTab('pending')" class="tab-btn pb-3 font-semibold border-b-2 border-brand text-brand transition-colors cursor-pointer flex items-center gap-2">
            <span>Perlu Dinilai</span>
            @if($totalPending > 0)
                <span class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">{{ $totalPending }}</span>
            @endif
        </button>
        <button type="button" id="tab-btn-results" onclick="switchGradingTab('results')" class="tab-btn pb-3 font-semibold border-b-2 border-transparent text-muted hover:text-ink transition-colors cursor-pointer flex items-center gap-2">
            <span>Hasil Nilai</span>
            <span class="px-1.5 py-0.5 rounded-full bg-canvas text-muted text-[11px] font-bold">{{ count($results) }}</span>
        </button>
    </div>

    {{-- TAB 1: MAHASISWA YANG PERLU DINILAI --}}
    <div id="panel-pending" class="space-y-4">
        @if(count($pendingQueue) === 0)
            <div class="surface p-10 text-center rounded-2xl border border-line/60 space-y-3">
                <div class="mx-auto h-12 w-12 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <h2 class="text-base font-bold text-ink">Semua Jawaban Telah Dinilai</h2>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Seluruh lembar jawaban esai mahasiswa telah diperiksa. Anda dapat melihat hasil kalkulasi nilai asesmen dan capaian CPMK pada tab Hasil Nilai.
                </p>
                <div class="pt-2">
                    <button type="button" onclick="switchGradingTab('results')" class="button-primary text-xs py-2 px-4 font-semibold">
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
                            <th>Perlu Dinilai</th>
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
                                        {{ $row['pending_essays_count'] }} jawaban
                                    </span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('dosen.item.penilaian.esai', [$course['id'], $item['id'], $row['student']['id']]) }}" class="button-primary text-xs py-1.5 px-3.5 font-semibold inline-flex items-center gap-1">
                                        <span>Nilai</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </a>
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
                        <th class="text-center min-w-[120px]">Nilai Asesmen</th>
                        <th class="text-center min-w-[140px]">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $idx => $res)
                        <tr>
                            <td class="text-center font-mono text-xs text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <p class="font-semibold text-ink leading-snug">{{ $res['student']['name'] }}</p>
                                <p class="font-mono text-xs text-muted">{{ $res['student']['number'] }}</p>
                            </td>
                            <td class="text-center">
                                @if($res['status_key'] === 'selesai')
                                    <span class="font-mono text-sm font-bold text-ink">{{ $res['nilai_display'] }}</span>
                                    <span class="block text-[10px] text-muted">dari 100</span>
                                @elseif($res['status_key'] === 'perlu_dinilai')
                                    <span class="font-mono text-xs font-semibold text-amber-800">Menunggu</span>
                                    <span class="block text-[10px] text-muted">({{ $res['pending_essays_count'] }} esai)</span>
                                @else
                                    <span class="text-xs text-muted font-mono">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center whitespace-nowrap">
                                @if($res['status_key'] === 'selesai')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>Selesai</span>
                                    </span>
                                @elseif($res['status_key'] === 'perlu_dinilai')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200">
                                        <span>Perlu Dinilai</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium text-muted bg-canvas border border-line/60">
                                        <span>Belum Dikerjakan</span>
                                    </span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap space-x-1.5">
                                @if($res['has_submitted'])
                                    <button type="button" onclick="openDetailModal({{ json_encode($res) }})" class="button-secondary text-xs py-1 px-2.5 font-medium">
                                        Lihat Rincian
                                    </button>
                                    @if($res['status_key'] === 'perlu_dinilai')
                                        <a href="{{ route('dosen.item.penilaian.esai', [$course['id'], $item['id'], $res['student']['id']]) }}" class="button-primary text-xs py-1 px-2.5 font-semibold">
                                            Nilai
                                        </a>
                                    @endif
                                @else
                                    <span class="text-xs text-muted">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL RINCIAN NILAI MAHASISWA & FORMULA PERHITUNGAN --}}
<dialog id="detail-modal" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/40 max-w-2xl w-[calc(100%-2rem)] h-fit max-h-[85vh] overflow-hidden flex flex-col">
    <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30">
        <div>
            <h3 class="text-sm font-bold text-ink" id="modal-title">Rincian Nilai</h3>
            <p class="text-xs text-muted" id="modal-subtitle">Mahasiswa</p>
        </div>
        <button type="button" onclick="document.getElementById('detail-modal').close()" class="h-7 w-7 rounded-lg text-muted hover:text-ink hover:bg-canvas flex items-center justify-center font-bold text-sm cursor-pointer" aria-label="Tutup">
            &times;
        </button>
    </div>

    <div class="p-6 overflow-y-auto space-y-5 text-xs flex-1">
        {{-- Ringkasan Nilai Asesmen & CPMK --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-canvas border border-line/60">
                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nilai Asesmen</span>
                <span class="text-2xl font-extrabold text-brand mt-1 block" id="modal-score">0,00</span>
                <span class="text-[11px] text-muted">Skala 0&ndash;100</span>
            </div>
            <div class="p-4 rounded-xl bg-canvas border border-line/60 space-y-2">
                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Nilai Capaian per CPMK</span>
                <div id="modal-cpmk-list" class="space-y-1.5 pt-0.5">
                    {{-- Dinamis diisi via JavaScript --}}
                </div>
            </div>
        </div>

        {{-- Collapsible: Lihat Perhitungan & Rincian Soal --}}
        <details class="rounded-xl border border-line/60 p-4 bg-white shadow-2xs group" open>
            <summary class="font-semibold text-xs text-brand hover:underline cursor-pointer select-none flex items-center justify-between">
                <span>Rincian Butir Soal &amp; Formula Perhitungan</span>
                <span class="text-muted group-open:rotate-180 transition-transform">&darr;</span>
            </summary>

            <div class="mt-4 pt-3 border-t border-line/60 space-y-4">
                {{-- Tabel Butir Soal --}}
                <div class="overflow-x-auto border border-line/50 rounded-lg">
                    <table class="w-full text-left text-[11px] border-collapse">
                        <thead>
                            <tr class="bg-canvas/60 border-b border-line/60 text-muted">
                                <th class="py-2 px-2.5">Soal</th>
                                <th class="py-2 px-2.5">Tipe</th>
                                <th class="py-2 px-2.5">CPMK</th>
                                <th class="py-2 px-2.5 text-right">Poin</th>
                                <th class="py-2 px-2.5 text-right">Skor</th>
                                <th class="py-2 px-2.5 text-right">Persen</th>
                                <th class="py-2 px-2.5 text-right">Porsi</th>
                                <th class="py-2 px-2.5 text-right">Nilai Soal</th>
                            </tr>
                        </thead>
                        <tbody id="modal-questions-tbody">
                            {{-- Dinamis diisi via JavaScript --}}
                        </tbody>
                    </table>
                </div>

                {{-- Penjelasan Formula (Sesuai desain 1.md Bagian 4) --}}
                <div class="rounded-lg bg-canvas p-3.5 space-y-1.5 text-[11px] text-muted border border-line/40">
                    <p class="font-bold text-ink">Formula Perhitungan OBE (desain 1.md):</p>
                    <p><span class="font-mono text-ink">porsi_soal[i] = 100 / jumlah_soal_di_cpmk</span> (otomatis &amp; sama rata di tiap CPMK)</p>
                    <p><span class="font-mono text-ink">persen_soal[i] = skor[i] / poin_dosen[i]</span> (skor mentah yang diberikan)</p>
                    <p><span class="font-mono text-ink">nilai_soal[i] = persen_soal[i] &times; porsi_soal[i]</span></p>
                    <p><span class="font-mono text-ink">nilai_cpmk[k] = &Sigma; nilai_soal[i]</span> (maksimal 100 per CPMK)</p>
                    <p><span class="font-mono text-ink">bobot_cpmk[k] = jumlah_soal_k / total_soal</span></p>
                    <p><span class="font-mono text-ink">nilai_asesmen = &Sigma; (nilai_cpmk[k] &times; bobot_cpmk[k])</span> (skala 0&ndash;100)</p>
                </div>
            </div>
        </details>
    </div>

    <div class="px-6 py-3 border-t border-line/60 bg-canvas/30 flex items-center justify-end">
        <button type="button" onclick="document.getElementById('detail-modal').close()" class="button-secondary text-xs py-1.5 px-4 font-semibold">
            Tutup
        </button>
    </div>
</dialog>

<script>
    function switchGradingTab(tab) {
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

    const allQuestionsMeta = @json($questions);

    function openDetailModal(res) {
        document.getElementById('modal-title').textContent = 'Rincian Nilai: ' + res.student.name;
        document.getElementById('modal-subtitle').textContent = 'NIM: ' + res.student.number + ' \u2022 ' + res.status_label;
        document.getElementById('modal-score').textContent = res.status_key === 'selesai' ? res.nilai_display : (res.has_submitted ? (res.nilai_asesmen ? res.nilai_asesmen.toFixed(2).replace('.', ',') : '—') : '—');

        // Render CPMK list
        const cpmkContainer = document.getElementById('modal-cpmk-list');
        cpmkContainer.innerHTML = '';
        for (const [cCode, cInfo] of Object.entries(res.cpmk_breakdown)) {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between text-xs';
            div.innerHTML = `<span class="font-bold text-ink">${cCode} <span class="text-muted font-normal text-[10px]">(Bobot ${cInfo.weight}%)</span></span>
                             <span class="font-mono font-bold text-ink">${cInfo.score.toFixed(2).replace('.', ',')}</span>`;
            cpmkContainer.appendChild(div);
        }

        // Render Question Table
        const tbody = document.getElementById('modal-questions-tbody');
        tbody.innerHTML = '';
        allQuestionsMeta.forEach((q, idx) => {
            const qData = res.questions_breakdown[idx] || {};
            const scoreVal = qData.score !== null && qData.score !== undefined ? qData.score : '—';
            const persenVal = qData.persen !== null && qData.persen !== undefined ? qData.persen.toFixed(1).replace('.', ',') + '%' : '—';
            const nilaiVal = qData.nilai_soal !== null && qData.nilai_soal !== undefined ? qData.nilai_soal.toFixed(2).replace('.', ',') : '—';
            const typeLabel = q.is_essay ? 'Esai' : 'Otomatis';

            const tr = document.createElement('tr');
            tr.className = 'border-b border-line/40 hover:bg-slate-50/70';
            tr.innerHTML = `
                <td class="py-2 px-2.5 font-semibold text-ink">Soal ${idx + 1}</td>
                <td class="py-2 px-2.5 text-muted">${typeLabel}</td>
                <td class="py-2 px-2.5 font-mono text-ink font-semibold">${q.cpmk}</td>
                <td class="py-2 px-2.5 text-right font-mono text-muted">${q.points}</td>
                <td class="py-2 px-2.5 text-right font-mono font-bold text-ink">${scoreVal}</td>
                <td class="py-2 px-2.5 text-right font-mono text-muted">${persenVal}</td>
                <td class="py-2 px-2.5 text-right font-mono text-muted">${q.porsi_soal}</td>
                <td class="py-2 px-2.5 text-right font-mono font-bold text-brand">${nilaiVal}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('detail-modal').showModal();
    }
</script>
@endsection
