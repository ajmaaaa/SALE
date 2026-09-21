@extends('layouts.mahasiswa')

@section('title', 'Penilaian Esai - ' . ($student['name'] ?? 'Mahasiswa') . ' | SALE')
@section('header', 'Penilaian Esai')

@section('content')
@php
    $totalEssays    = count($essay_items);
    $gradedCount    = collect($essay_items)->filter(fn($e) => $e['current_score'] !== null)->count();
    $allGraded      = $gradedCount === $totalEssays;
@endphp

<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('dosen.item.penilaian', [$course['id'], $item['id']]) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink transition-colors mb-1.5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Daftar Mahasiswa
            </a>
            <h1 class="page-heading">{{ $item['title'] }}</h1>
            <p class="text-xs text-muted mt-0.5 font-medium">
                <span class="text-ink font-semibold">{{ $student['name'] }}</span>
                <span class="mx-1 text-line">·</span>
                <span class="font-mono">{{ $student['number'] ?? '' }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($allGraded)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs font-semibold text-emerald-800">
                    Semua dinilai ({{ $gradedCount }}/{{ $totalEssays }})
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-800">
                    {{ $gradedCount }}/{{ $totalEssays }} dinilai
                </span>
            @endif
        </div>
    </div>

    @if(session('notice'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs text-emerald-800 font-medium flex items-center justify-between">
            <span>{{ session('notice') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold text-sm">×</button>
        </div>
    @endif

    {{-- ===== SPLIT LAYOUT ===== --}}
    <div class="flex gap-0 rounded-2xl border border-line/70 overflow-hidden shadow-sm min-h-[70vh]" style="height: calc(100vh - 14rem);">

        {{-- KOLOM KIRI: INPUT SKOR SEMUA ESAI --}}
        <div class="flex-1 overflow-y-auto border-r border-line/70 bg-white">
            <div class="sticky top-0 z-10 bg-white border-b border-line/60 px-5 py-3">
                <p class="text-[11px] font-bold tracking-wider text-muted uppercase">Penilaian Esai</p>
                <p class="text-xs text-muted mt-0.5">Isi skor untuk setiap soal esai, lalu simpan.</p>
            </div>

            <div class="divide-y divide-line/40">
                @foreach($essay_items as $loop_i => $essayItem)
                    @php
                        $qIdx  = $essayItem['question_index'];
                        $q     = $essayItem['question'];
                        $max   = $essayItem['max_points'];
                        $score = $essayItem['current_score'];
                        $isGraded = $score !== null;
                    @endphp
                    <div class="px-5 py-4 space-y-3" id="essay-panel-{{ $loop_i }}">
                        {{-- Label soal --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-canvas border border-line text-[11px] font-bold text-ink">{{ $loop_i + 1 }}</span>
                                <span class="text-[11px] font-bold tracking-wider text-muted uppercase">Esai · Maks. {{ (float)$max }} poin</span>
                            </div>
                            @if($isGraded)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                                    Dinilai
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-canvas text-muted border border-line/60">
                                    Belum dinilai
                                </span>
                            @endif
                        </div>

                        {{-- Pertanyaan singkat (bisa klik untuk buka jawaban di sidebar) --}}
                        <button type="button"
                            onclick="showAnswer({{ $loop_i }})"
                            class="w-full text-left text-sm text-ink leading-relaxed line-clamp-2 hover:text-brand transition-colors cursor-pointer group">
                            <span class="font-medium">{{ $q['prompt'] ?? $q['title'] ?? 'Pertanyaan esai' }}</span>
                            <span class="ml-1.5 text-[10px] text-brand opacity-0 group-hover:opacity-100 transition-opacity">Lihat jawaban →</span>
                        </button>

                        {{-- Form input skor --}}
                        <form method="POST"
                              action="{{ route('dosen.item.penilaian.esai.save', [$course['id'], $item['id'], $student['id'], $qIdx]) }}"
                              class="flex items-center gap-3">
                            @csrf
                            <div class="flex items-center gap-2 flex-1">
                                <input
                                    type="number"
                                    name="score"
                                    value="{{ $score !== null ? (float)$score : '' }}"
                                    min="0"
                                    max="{{ $max }}"
                                    step="any"
                                    placeholder="0"
                                    class="score-input-{{ $loop_i }} h-10 w-24 text-center text-base font-bold rounded-lg border border-line bg-canvas px-3 text-ink focus:border-brand focus:ring-1 focus:ring-brand outline-none transition"
                                    data-max="{{ $max }}"
                                    data-loop="{{ $loop_i }}"
                                    oninput="calcLive({{ $loop_i }}, this)"
                                    {{ $isGraded ? 'autofocus' : '' }}
                                >
                                <span class="text-sm font-semibold text-muted shrink-0">/ {{ (float)$max }}</span>
                                <div class="flex items-center gap-3 text-xs ml-1">
                                    <span id="pct-{{ $loop_i }}" class="font-semibold text-muted">
                                        {{ $essayItem['persen'] !== null ? $essayItem['persen'] . '%' : '—' }}
                                    </span>
                                    <span class="text-line">·</span>
                                    <span class="text-muted">Nilai soal:</span>
                                    <span id="ns-{{ $loop_i }}" class="font-bold text-ink">
                                        {{ $essayItem['nilai_soal'] !== null ? number_format($essayItem['nilai_soal'], 2, ',', '.') : '—' }}
                                    </span>
                                </div>
                            </div>
                            <button type="submit"
                                    class="shrink-0 h-10 px-4 rounded-lg bg-brand text-white text-xs font-semibold hover:opacity-90 active:scale-95 transition shadow-sm cursor-pointer whitespace-nowrap">
                                Simpan
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- Footer: kembali ke daftar --}}
            @if($allGraded)
                <div class="px-5 py-4 border-t border-line/60 bg-canvas/30">
                    <a href="{{ route('dosen.item.penilaian', [$course['id'], $item['id']]) }}"
                       class="button-primary text-xs py-2.5 px-4 font-semibold inline-flex items-center gap-1.5">
                        Selesai, Kembali ke Daftar
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
            @endif
        </div>

        {{-- SIDEBAR KANAN: JAWABAN MAHASISWA --}}
        <div class="w-[420px] shrink-0 overflow-y-auto bg-canvas/40 flex flex-col">
            <div class="sticky top-0 z-10 bg-canvas/80 backdrop-blur border-b border-line/60 px-5 py-3">
                <p class="text-[11px] font-bold tracking-wider text-muted uppercase">Jawaban Mahasiswa</p>
                <div class="flex items-center gap-2 mt-1.5 overflow-x-auto pb-0.5 scrollbar-hide">
                    @foreach($essay_items as $loop_i => $essayItem)
                        <button
                            type="button"
                            id="tab-{{ $loop_i }}"
                            onclick="showAnswer({{ $loop_i }})"
                            class="shrink-0 h-7 min-w-[32px] px-2.5 rounded-md text-[11px] font-bold transition-colors cursor-pointer
                                {{ $loop_i === 0 ? 'bg-brand text-white' : 'bg-white text-muted border border-line hover:border-brand hover:text-brand' }}">
                            {{ $loop_i + 1 }}
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach($essay_items as $loop_i => $essayItem)
                @php
                    $q = $essayItem['question'];
                    $answerText = $essayItem['answer_text'];
                @endphp
                <div id="answer-panel-{{ $loop_i }}"
                     class="answer-panel flex-1 p-5 space-y-4 {{ $loop_i > 0 ? 'hidden' : '' }}">

                    {{-- Judul soal --}}
                    <div class="space-y-1.5">
                        <div class="text-[10px] font-bold tracking-wider text-muted uppercase">
                            Soal {{ $loop_i + 1 }} · Esai · Maks. {{ (float)$essayItem['max_points'] }} poin
                        </div>
                        <p class="text-sm font-medium text-ink leading-relaxed">
                            {{ $q['prompt'] ?? $q['title'] ?? 'Pertanyaan esai' }}
                        </p>
                    </div>

                    <hr class="border-line/50">

                    {{-- Jawaban mahasiswa --}}
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold tracking-wider text-muted uppercase">Jawaban</p>
                        <div class="rounded-xl border border-line/80 bg-white p-4 text-sm leading-relaxed text-ink/90 whitespace-pre-wrap font-sans selection:bg-brand/15 min-h-[120px]">
@if(!empty(trim($answerText)))
{{ trim($answerText) }}
@else
<span class="text-muted italic text-xs">Mahasiswa belum mengisi jawaban.</span>
@endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    const porsiMap = @json(collect($essay_items)->mapWithKeys(fn($e, $i) => [$i => ['max' => $e['max_points'], 'porsi' => $e['porsi_soal']]]));

    function calcLive(loopIndex, input) {
        const max   = parseFloat(input.getAttribute('data-max'));
        const val   = parseFloat(input.value);
        const pctEl = document.getElementById('pct-' + loopIndex);
        const nsEl  = document.getElementById('ns-'  + loopIndex);

        if (isNaN(val) || val < 0) {
            pctEl.textContent = '—';
            nsEl.textContent  = '—';
            return;
        }

        const porsi    = porsiMap[loopIndex]?.porsi ?? 100;
        const pct      = Math.min(100, (val / (max || 1)) * 100);
        const nilaiSoal = (val / (max || 1)) * porsi;

        pctEl.textContent = pct.toFixed(1) + '%';
        nsEl.textContent  = nilaiSoal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function showAnswer(idx) {
        // Sembunyikan semua panel jawaban
        document.querySelectorAll('.answer-panel').forEach(el => el.classList.add('hidden'));
        // Tampilkan panel yang dipilih
        const target = document.getElementById('answer-panel-' + idx);
        if (target) target.classList.remove('hidden');

        // Update tab aktif
        document.querySelectorAll('[id^="tab-"]').forEach(btn => {
            btn.classList.remove('bg-brand', 'text-white');
            btn.classList.add('bg-white', 'text-muted', 'border', 'border-line');
        });
        const activeTab = document.getElementById('tab-' + idx);
        if (activeTab) {
            activeTab.classList.remove('bg-white', 'text-muted', 'border', 'border-line');
            activeTab.classList.add('bg-brand', 'text-white');
        }

        // Scroll panel kiri agar soal yang bersangkutan terlihat
        const essayPanel = document.getElementById('essay-panel-' + idx);
        if (essayPanel) essayPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Sync: klik pada soal di kiri akan highlight jawaban di kanan
    document.addEventListener('DOMContentLoaded', () => {
        // Inisialisasi kalkulasi untuk nilai yang sudah ada
        @foreach($essay_items as $loop_i => $essayItem)
            @if($essayItem['current_score'] !== null)
            (function() {
                const inp = document.querySelector('.score-input-{{ $loop_i }}');
                if (inp) calcLive({{ $loop_i }}, inp);
            })();
            @endif
        @endforeach
    });
</script>
@endsection
