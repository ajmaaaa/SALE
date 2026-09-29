@extends('layouts.mahasiswa')

@section('title', 'Capaian Pembelajaran OBE | SALE Mahasiswa')
@section('header', 'Capaian Pembelajaran OBE')

@section('content')
<div class="space-y-6 w-full">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('mahasiswa.dashboard') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>Dashboard</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800" aria-current="page">
            Capaian Pembelajaran OBE
        </span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
        <div class="min-w-0 flex-1">
            <h2 class="section-heading text-xl font-bold text-ink">Capaian Pembelajaran OBE &amp; Nilai Mandiri</h2>
            <p class="mt-1 text-xs text-muted">Pantau pemenuhan standar CPMK dan ketercapaian CPL Anda secara transparan pada setiap mata kuliah yang diambil.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 shrink-0 w-full sm:w-auto sm:ml-auto">
            @if(!empty($semesters) && $semesters->count())
                <form method="GET" action="{{ route('mahasiswa.obe.progress') }}" class="flex items-center gap-2 w-full sm:w-auto">
                    <label for="filter_semester" class="sr-only">Semester</label>
                    <select id="filter_semester" name="semester" onchange="this.form.submit()" class="field py-1.5 text-xs font-semibold w-full sm:w-56">
                        <option value="">Semua Semester</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ (string) request('semester', $selectedSemesterId ?? '') === (string) $sem->id ? 'selected' : '' }}>
                                {{ $sem->display_name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
            <div class="surface px-4 py-2 flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <div class="flex h-8 w-8 items-center justify-center rounded-full border border-line bg-white text-brand-dark font-bold text-xs shadow-2xs">
                    {{ mb_substr($student->name, 0, 1) }}
                </div>
                <div>
                    <p class="text-xs font-bold text-ink leading-tight">{{ $student->name }}</p>
                    <p class="text-[11px] text-muted">{{ $student->nim_nip ?? 'Mahasiswa' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigasi: Transkrip Nilai & Capaian Pembelajaran OBE --}}
    <div class="bg-[#f4f5f7] pt-2 pb-1">
        <nav class="flex border-b border-line/60 gap-6" aria-label="Tab nilai dan capaian">
            <a href="{{ route('mahasiswa.nilai') }}" class="pb-3 text-sm font-medium border-b-2 -mb-px border-transparent text-muted hover:text-ink flex items-center gap-2 transition">
                <span>Transkrip Nilai (KHS)</span>
            </a>
            <a href="{{ route('mahasiswa.obe.progress') }}" class="pb-3 text-sm font-semibold border-b-2 -mb-px border-brand text-brand flex items-center gap-1.5 transition">
                <span>Capaian Pembelajaran OBE</span>
                <span class="text-xs text-muted">({{ $courseProgress->count() }})</span>
            </a>
        </nav>
        <div class="h-2 -mt-1 bg-[#f4f5f7] shadow-[0_8px_16px_-2px_rgba(29,39,48,0.10)] pointer-events-none" aria-hidden="true"></div>
    </div>

    @if($courseProgress->isEmpty())
        <div class="surface p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-canvas text-muted">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h3 class="mt-3 text-base font-semibold text-ink">Belum Ada Kelas yang Diikuti</h3>
            <p class="mt-1 text-sm text-muted">Anda belum terdaftar di kelas perkuliahan aktif manapun pada semester ini.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($courseProgress as $item)
                @php
                    $sec = $item['section'];
                    $cpmks = $item['cpmks'];
                    $cpls = $item['cpls'];
                    $finalScore = $item['final_score'];
                    $coverage = $item['coverage'];
                @endphp
                <div class="surface overflow-hidden">
                    <!-- Course Header Card -->
                    <div class="border-b border-line/60 bg-canvas/40 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-brand">{{ $sec->mataKuliah->code }}</span>
                                @if($sec->mataKuliah->semester_paket)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">Sem. {{ $sec->mataKuliah->semester_paket }}</span>
                                @endif
                                <span class="text-xs font-medium text-muted">Kelas {{ $sec->name }} &bull; {{ $sec->semester->display_name ?? ($sec->semester->name ?? '') }}</span>
                            </div>
                            <h3 class="mt-1 text-base font-bold text-ink">{{ $sec->mataKuliah->name }}</h3>
                            <p class="text-xs text-muted">Dosen Pengampu: {{ $sec->dosen->name ?? '' }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[11px] uppercase tracking-wider text-muted font-semibold">Kelengkapan Asesmen</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="w-24 h-2 rounded-full bg-slate-200 overflow-hidden">
                                        <div class="h-full bg-brand rounded-full" style="width: {{ min(100, $coverage) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-ink">{{ round($coverage) }}%</span>
                                </div>
                            </div>
                            <div class="rounded-xl border border-line bg-white px-4 py-2 text-right">
                                <p class="text-[10px] uppercase font-semibold text-muted">Nilai Akhir</p>
                                <p class="text-lg font-bold {{ $finalScore !== null && $finalScore >= 65 ? 'text-brand' : 'text-ink' }}">
                                    {{ $finalScore !== null ? number_format($finalScore, 1) : '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- CPMK Section -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Capaian Pembelajaran Mata Kuliah (CPMK)</h4>
                            @if($cpmks->isEmpty())
                                <p class="text-xs text-muted italic">Mata kuliah ini belum memiliki definisi CPMK aktif.</p>
                            @else
                                <div class="overflow-x-auto rounded-lg border border-line">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-canvas/50 text-[11px] uppercase tracking-wider text-muted border-b border-line">
                                            <tr>
                                                <th class="px-4 py-2.5 font-semibold">Kode CPMK</th>
                                                <th class="px-4 py-2.5 font-semibold">Deskripsi Indikator Kemampuan</th>
                                                <th class="px-4 py-2.5 font-semibold text-center">Threshold</th>
                                                <th class="px-4 py-2.5 font-semibold text-center">Skor Anda</th>
                                                <th class="px-4 py-2.5 font-semibold text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-line text-xs">
                                            @foreach($cpmks as $cpmkItem)
                                                @php
                                                    $cpmk = $cpmkItem['cpmk'];
                                                    $score = $cpmkItem['score'];
                                                    $achieved = $cpmkItem['is_achieved'];
                                                @endphp
                                                <tr class="hover:bg-canvas/30 transition-colors">
                                                    <td class="px-4 py-3 font-bold text-brand whitespace-nowrap">{{ $cpmk->code }}</td>
                                                    <td class="px-4 py-3 text-ink leading-relaxed">{{ $cpmk->description }}</td>
                                                    <td class="px-4 py-3 text-center text-muted font-medium">{{ number_format($cpmk->threshold, 1) }}</td>
                                                    <td class="px-4 py-3 text-center font-bold">
                                                        @if($score === null)
                                                            <span class="text-muted"></span>
                                                        @else
                                                            <span class="{{ $achieved ? 'text-brand' : 'text-amber-700' }}">{{ number_format($score, 1) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        @if($score === null)
                                                            <span class="text-xs font-medium text-muted">Belum Dinilai</span>
                                                        @elseif($achieved)
                                                            <span class="text-xs font-semibold text-emerald-800">Tercapai</span>
                                                        @else
                                                            <span class="text-xs font-semibold text-ink">Belum Tercapai</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <!-- CPL Section -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Kontribusi Terhadap Capaian Pembelajaran Lulusan (CPL)</h4>
                            @if($cpls->isEmpty())
                                <p class="text-xs text-muted italic">Mata kuliah ini belum dipetakan ke butir CPL.</p>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 w-full">
                                    @foreach($cpls as $cplItem)
                                        @php
                                            $cpl = $cplItem['cpl'];
                                            $score = $cplItem['score'];
                                            $passed = $score !== null && $score >= 65;
                                        @endphp
                                        <div class="rounded-lg border border-line p-3 bg-canvas/30">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-bold text-brand">{{ $cpl->code }}</span>
                                                @if($score === null)
                                                    <span class="text-[10px] text-muted">Belum lengkap</span>
                                                @elseif($passed)
                                                    <span class="text-[11px] font-semibold text-emerald-800">Lulus</span>
                                                @else
                                                    <span class="text-[11px] font-semibold text-amber-800">Remedial</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-[11px] text-muted line-clamp-2" title="{{ $cpl->description }}">{{ $cpl->description }}</p>
                                            <div class="mt-2 flex items-center justify-between border-t border-line/60 pt-2 text-xs">
                                                <span class="text-muted">Skor CPL:</span>
                                                <span class="font-bold {{ $passed ? 'text-brand' : 'text-ink' }}">
                                                    {{ $score !== null ? number_format($score, 1) : '' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
