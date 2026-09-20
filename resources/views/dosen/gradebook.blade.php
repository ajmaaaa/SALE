@extends('layouts.mahasiswa')

@section('title', 'Rekap Nilai & Gradebook | SALE')
@section('header', 'Rekap Nilai Kelas')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-heading">Rekap Nilai Kelas</h1>
            <p class="page-description">Dari rekap kelas hingga rincian soal. Telusuri nilai setiap penilaian dan Ketercapaian CPMK secara terpisah.</p>
        </div>
        <div class="flex flex-wrap gap-2.5 shrink-0">
            <button type="button" onclick="document.getElementById('bulk-score-section').toggleAttribute('hidden')" class="button-secondary">
                Input Nilai Massal
            </button>
            <a class="button-secondary" href="{{ route('dosen.academic', $course['id']) }}">
                Atur Bobot &amp; CPMK
            </a>
        </div>
    </header>

    {{-- Course & Class Selector with Class CRUD Modal Trigger --}}
    @php
        $selectedClass = request()->query('section', 'A');
    @endphp
    <div class="surface p-5 rounded-2xl border border-line/70 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <form class="flex flex-wrap items-center gap-4 flex-1">
            <div class="flex items-center gap-2.5 min-w-[240px] max-w-sm flex-1">
                <label for="course" class="text-xs font-semibold text-muted shrink-0">Pilih Matkul:</label>
                <select name="course" id="course" onchange="this.form.submit()" class="field text-xs font-bold text-brand py-2 shadow-2xs">
                    @foreach(\App\Support\LearningPreview::courses() as $c)
                        <option value="{{ $c['id'] }}" @selected($c['id'] === $course['id'])>
                            {{ $c['code'] }} · {{ $c['title'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2.5 min-w-[160px]">
                <label for="section" class="text-xs font-semibold text-muted shrink-0">Pilih Kelas:</label>
                <select name="section" id="section" onchange="this.form.submit()" class="field text-xs font-bold text-ink py-2 w-36 shadow-2xs">
                    <option value="A" @selected($selectedClass === 'A')>Kelas A (Reguler)</option>
                    <option value="B" @selected($selectedClass === 'B')>Kelas B (Paralel)</option>
                    <option value="C" @selected($selectedClass === 'C')>Kelas C (Eksekutif)</option>
                </select>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <button type="button" onclick="document.getElementById('manage-class-modal').toggleAttribute('hidden')" class="button-secondary text-xs py-2 px-3.5 font-semibold">
                + Kelola / Tambah Kelas
            </button>
            <div class="flex items-center gap-2.5 text-xs shrink-0">
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
                    <span class="text-muted font-medium">Total Komponen:</span>
                    <span class="font-bold text-ink">{{ count($config['components']) }}</span>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-canvas border border-line/60 shadow-2xs">
                    <span class="text-muted font-medium">Total Bobot:</span>
                    <span class="font-bold text-ink">{{ array_sum(array_column($config['components'], 'weight')) }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Kelola / CRUD Kelas (Paralel) --}}
    <section id="manage-class-modal" hidden class="surface p-5 rounded-2xl border border-line/60 space-y-4 shadow-md bg-canvas">
        <div class="flex items-center justify-between border-b border-line/60 pb-3">
            <div>
                <h3 class="text-sm font-bold text-ink">Kelola Kelas (CRUD Kelas Parallel)</h3>
                <p class="text-xs text-muted">Tambah, ubah, atau atur kelas pararel untuk mata kuliah {{ $course['title'] }}.</p>
            </div>
            <button type="button" onclick="document.getElementById('manage-class-modal').setAttribute('hidden', '')" class="text-xs font-bold text-muted hover:text-ink">Tutup ×</button>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="p-3 rounded-xl bg-white border border-line/60 flex items-center justify-between">
                <div>
                    <span class="inline-block rounded bg-brand-soft px-2 py-0.5 text-xs font-bold text-brand">Kelas A</span>
                    <p class="text-xs font-medium text-ink mt-1">32 Mahasiswa · Reguler Pagi</p>
                </div>
                <span class="text-xs font-semibold text-emerald-700">Aktif</span>
            </div>
            <div class="p-3 rounded-xl bg-white border border-line/60 flex items-center justify-between">
                <div>
                    <span class="inline-block rounded bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700 border border-blue-200">Kelas B</span>
                    <p class="text-xs font-medium text-ink mt-1">28 Mahasiswa · Paralel Siang</p>
                </div>
                <span class="text-xs font-semibold text-emerald-700">Aktif</span>
            </div>
            <div class="p-3 rounded-xl bg-white border border-line/60 flex items-center justify-between">
                <div>
                    <span class="inline-block rounded bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 border border-amber-200">Kelas C</span>
                    <p class="text-xs font-medium text-ink mt-1">20 Mahasiswa · Eksekutif Malam</p>
                </div>
                <span class="text-xs font-semibold text-emerald-700">Aktif</span>
            </div>
        </div>

        <div class="pt-2 flex items-center justify-between text-xs">
            <span class="text-muted">Setiap kelas memiliki daftar mahasiswa dan rekap nilai yang terpisah secara independen.</span>
            <button type="button" onclick="alert('Kelas baru berhasil ditambahkan!')" class="button-primary text-xs py-1.5 px-3">
                + Tambah Paralel Kelas Baru
            </button>
        </div>
    </section>

    @include('dosen.partials.gradebook-detail')

    {{-- Bulk Score Section (Collapsible) --}}
    <section id="bulk-score-section" hidden class="surface p-6">
        <div class="flex items-center justify-between pb-3 border-b border-line/60">
            <div>
                <h2 class="text-base font-semibold text-ink">Input Nilai Massal (CSV / Salin-Tempel)</h2>
                <p class="mt-0.5 text-xs text-muted">Masukkan nilai seluruh mahasiswa sekaligus tanpa harus menginput satu per satu.</p>
            </div>
            <button type="button" onclick="document.getElementById('bulk-score-section').setAttribute('hidden', '')" class="text-xs text-muted hover:text-ink">
                Tutup
            </button>
        </div>

        <form class="mt-4 space-y-4" method="post" action="{{ route('dosen.scores.bulk', $course['id']) }}">
            @csrf
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="form-label text-xs" for="raw_scores">
                        Format Kolom: NIM, {{ implode(', ', array_column($config['components'], 'name')) }}
                    </label>
                    <button type="button" class="text-xs font-semibold text-brand hover:underline" onclick="
                        const template = '231011401234, 88, 92, 85, 90, 95, 100\n231011401235, 78, 85, 80, 82, 88, 90';
                        document.getElementById('raw_scores').value = template;
                    ">
                        Muat Contoh Nilai
                    </button>
                </div>
                <textarea id="raw_scores" name="raw_scores" rows="5" required class="field font-mono text-xs" placeholder="NIM, {{ implode(', ', array_column($config['components'], 'code')) }}&#10;231011401234, 88, 92, 85, 90, 95, 100"></textarea>
                <p class="mt-1 text-xs text-muted">Nilai berkisar 0–100. Pisahkan data mahasiswa dengan baris baru, dan nilai tiap komponen dengan koma (,) atau tab.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="button-primary text-xs py-2">
                    Terapkan Nilai Massal
                </button>
                <button type="button" onclick="document.getElementById('bulk-score-section').setAttribute('hidden', '')" class="button-secondary text-xs py-2">
                    Batal
                </button>
            </div>
        </form>
    </section>

    {{-- Main Gradebook Table --}}
    @if($componentFilter === '')
    <form method="post" action="{{ route('dosen.scores.save', $course['id']) }}">
        @csrf
        <div class="surface rounded-2xl border border-line/70 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-line/50 flex flex-wrap items-center justify-between gap-3 bg-canvas/30">
                <div>
                    <h3 class="text-base font-bold text-ink">Rekapitulasi Nilai &amp; Indeks Akhir</h3>
                    <p class="text-xs text-muted">Seluruh akumulasi nilai komponen terhitung secara otomatis berdasarkan bobot RPS.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-muted">Jumlah Mahasiswa: <strong class="text-ink">{{ count($students) }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">NIM</th>
                            <th>MAHASISWA</th>
                            @foreach($config['components'] as $component)
                                <th class="text-center min-w-[100px]">
                                    {{ strtoupper($component['name']) }}
                                    <span class="mt-0.5 block text-[11px] font-normal text-muted">Bobot {{ $component['weight'] }}%</span>
                                </th>
                            @endforeach
                            <th class="text-center min-w-[110px]">NILAI / 100</th>
                            <th class="text-center">INDEKS</th>
                            <th class="min-w-[220px]">KETERCAPAIAN CPMK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            @php
                                $result = \App\Support\AcademicPreview::result($course['id'], $student['id']);
                                $finalScore = $result['average'] ?? null;
                                $letter = '—';
                                if ($finalScore !== null) {
                                    if ($finalScore >= 85) { $letter = 'A'; }
                                    elseif ($finalScore >= 80) { $letter = 'A-'; }
                                    elseif ($finalScore >= 75) { $letter = 'B+'; }
                                    elseif ($finalScore >= 70) { $letter = 'B'; }
                                    elseif ($finalScore >= 65) { $letter = 'B-'; }
                                    elseif ($finalScore >= 60) { $letter = 'C+'; }
                                    elseif ($finalScore >= 55) { $letter = 'C'; }
                                    elseif ($finalScore >= 40) { $letter = 'D'; }
                                    else { $letter = 'E'; }
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="font-mono text-xs font-bold text-ink whitespace-nowrap">
                                    {{ $student['number'] }}
                                </td>
                                <td class="font-bold text-ink whitespace-nowrap">
                                    {{ $student['name'] }}
                                </td>
                                @foreach($config['components'] as $component)
                                    <td class="text-center p-2">
                                        <input aria-label="{{ $student['name'].' '.$component['name'] }}"
                                            class="field text-center font-mono text-xs py-1.5 px-2.5 max-w-[85px] mx-auto block rounded-lg shadow-2xs focus:border-brand"
                                            type="number" min="0" max="100" step="0.01"
                                            name="scores[{{ $student['id'] }}][{{ $component['code'] }}]"
                                            value="{{ $result['scores'][$component['code']] ?? '' }}"
                                            placeholder="—">
                                    </td>
                                @endforeach
                                <td class="text-center whitespace-nowrap">
                                    <span class="text-sm font-extrabold text-ink">{{ $result['average'] !== null ? number_format($result['average'], 1, ',', '.') : '—' }}</span>
                                    <span class="block text-[11px] font-medium text-muted">
                                        {{ $result['complete'] ? 'Nilai akhir' : 'Sementara' }}
                                    </span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-md bg-brand-soft text-xs font-bold text-brand shadow-2xs">
                                        {{ $letter }}
                                    </span>
                                </td>
                                <td>
                                    @php($attainment = \App\Support\AcademicPreview::breakdown($course['id'], $student['id']))
                                    <details class="text-xs">
                                        <summary class="cursor-pointer font-semibold {{ $attainment['passed'] === false ? 'text-danger' : 'text-brand' }} hover:underline">
                                            {{ $attainment['passed'] === null ? 'Menunggu penilaian' : ($attainment['passed'] ? 'Memenuhi seluruh CPMK' : 'Belum memenuhi CPMK') }}
                                        </summary>
                                        <div class="mt-3 p-3 rounded-xl bg-canvas/60 border border-line/50 space-y-2.5">
                                            @foreach($attainment['cpmk'] as $outcome)
                                                <div class="flex items-center justify-between text-[11px] border-b border-line/40 pb-1.5 last:border-0 last:pb-0">
                                                    <div>
                                                        <span class="font-bold text-ink">{{ $outcome['code'] }}</span>
                                                        <span class="text-muted ml-1">(Batas {{ $outcome['threshold'] }})</span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="font-semibold text-ink">{{ $outcome['score'] === null ? 'Belum lengkap' : number_format($outcome['score'], 1, ',', '.').' / 100' }}</span>
                                                        <span class="ml-1 text-[10px] font-bold {{ $outcome['passed'] ? 'text-emerald-700' : ($outcome['passed'] === false ? 'text-danger' : 'text-muted') }}">
                                                            · {{ $outcome['passed'] === null ? 'Menunggu' : ($outcome['passed'] ? 'Tercapai' : 'Belum tercapai') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($config['components']) + 5 }}" class="p-8 text-center text-xs text-muted">
                                    Belum ada mahasiswa terdaftar di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-4 p-1">
            <p class="text-xs text-muted leading-relaxed max-w-xl">
                Nilai sementara dihitung dari bobot komponen yang sudah dinilai. Nilai komponen manual tidak menjadi bukti ketercapaian CPMK. Buka filter penilaian untuk melihat rincian soal.
            </p>
            <div class="flex items-center gap-3">
                <a href="{{ route('dosen.grades') }}" class="quiet-link text-xs">
                    Tinjau pengumpulan tugas
                </a>
                <button type="submit" class="button-primary text-xs py-2.5 px-5 font-bold shadow-2xs">
                    Simpan Semua Nilai
                </button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection
