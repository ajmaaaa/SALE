@extends('layouts.mahasiswa')
@section('header', 'Tambah konten course')
@section('content')
<div class="mx-auto w-full max-w-6xl">
    <a class="button-secondary inline-flex items-center gap-2 px-3 py-2 text-xs" href="{{ route('dosen.course.show', $course['id']) }}">
        <span aria-hidden="true">←</span><span>Kembali ke course</span>
    </a>
    <h1 class="page-heading mt-5">Tambah konten</h1>
    <p class="page-description">Materi, tugas, kuis, dan pengumuman tetap terhubung ke course ini.</p>

    <form class="surface mt-7 space-y-6 p-6 sm:p-8" action="{{ route('dosen.item.store', $course['id']) }}" method="post" enctype="multipart/form-data" data-content-form novalidate>
        @csrf

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs shadow-2xs">
                <div class="flex items-center gap-2 font-bold text-rose-800 text-sm">
                    <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Terdapat kesalahan pada formulir:</span>
                </div>
                <ul class="mt-2 list-disc pl-5 space-y-1 text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border-b border-line/60 pb-3">
            <h2 class="text-base font-bold text-ink">Informasi Konten</h2>
            <p class="text-xs text-muted">Lengkapi data konten pembelajaran, kuis, atau tugas untuk course ini.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="type">Jenis konten <span class="text-danger">*</span></label>
                <select id="type" name="type" class="field" data-content-type required>
                    <option value="" disabled @selected(!old('type') && !request('type'))>-- Pilih jenis konten --</option>
                    @foreach(['materi' => 'Materi', 'tugas' => 'Tugas', 'kuis' => 'Kuis', 'uts' => 'Ujian Tengah Semester (UTS)', 'uas' => 'Ujian Akhir Semester (UAS)', 'pengumuman' => 'Pengumuman', 'lainnya' => 'Lainnya'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', request('type')) === $value || ($value === 'tugas' && old('type') === 'coding'))>{{ $label }}</option>
                    @endforeach
                </select>
                <div data-custom-type-container hidden class="mt-2.5 space-y-1">
                    <label class="form-label text-xs" for="custom_type">Nama jenis konten kustom</label>
                    <input id="custom_type" name="custom_type" class="field text-xs py-2 bg-white" placeholder="Ketik jenis konten..." data-custom-type value="{{ old('custom_type') }}" maxlength="20">
                </div>
            </div>
            <div>
                <label class="form-label" for="module">Nama modul / topik <span class="text-danger">*</span></label>
                <input id="module" name="module" class="field" required maxlength="100" list="modules" value="{{ old('module') }}" placeholder="Minggu 3: Tree dan traversal">
                <datalist id="modules">
                    @foreach(collect(\App\Support\LearningPreview::items())->where('course', $course['id'])->pluck('module')->unique() as $module)
                        <option value="{{ $module }}">
                    @endforeach
                </datalist>
            </div>
        </div>

        <input id="title" name="title" type="hidden" value="{{ old('title', old('module')) }}">

        <div>
            <label class="form-label" for="body">Materi / instruksi / stimulus soal <span class="text-danger">*</span></label>
            <textarea id="body" name="body" required rows="5" class="field" placeholder="Tuliskan petunjuk umum, stimulus materi, atau deskripsi singkat...">{{ old('body') }}</textarea>
        </div>

        <fieldset data-material-mode-settings hidden>
            <legend class="form-label">Jenis materi</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                <label class="cursor-pointer rounded-lg border border-line/70 bg-white p-3 text-xs">
                    <input type="radio" name="material_mode" value="regular" data-material-mode @checked(old('material_mode', 'regular') === 'regular')>
                    <span class="ml-1 font-semibold text-ink">Materi biasa</span>
                    <span class="mt-1 block pl-5 text-muted">Bacaan, video, atau lampiran pembelajaran.</span>
                </label>
                <label class="cursor-pointer rounded-lg border border-line/70 bg-white p-3 text-xs">
                    <input type="radio" name="material_mode" value="coding" data-material-mode @checked(old('material_mode') === 'coding')>
                    <span class="ml-1 font-semibold text-ink">Tutorial pemrograman</span>
                    <span class="mt-1 block pl-5 text-muted">Editor praktik dengan pendamping Lumina AI.</span>
                </label>
            </div>
        </fieldset>

        <section class="rounded-xl border border-line/70 bg-canvas/50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-ink">Tambahkan pendukung</h2>
                    <p class="mt-0.5 text-xs text-muted">Pilih hanya yang diperlukan agar form tetap ringkas.</p>
                </div>
                <details class="relative" data-content-addon-menu>
                    <summary class="button-secondary flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-semibold">
                        <span class="text-sm font-bold text-brand">+</span> Tambahkan
                    </summary>
                    <div class="absolute right-0 top-full z-20 mt-1.5 w-48 space-y-1 rounded-xl border border-line/60 bg-white p-1.5 shadow-lg">
                        <button type="button" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs text-ink hover:bg-slate-100" data-content-addon="files" aria-expanded="false">
                            <svg class="h-4 w-4 shrink-0 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.2 7 8.6 13.6a2 2 0 102.8 2.8l6.4-6.6a4 4 0 00-5.6-5.6l-6.4 6.6a6 6 0 108.4 8.4L20.5 13"/></svg>
                            Lampiran
                        </button>
                        <button type="button" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs text-ink hover:bg-slate-100" data-content-addon="link" aria-expanded="{{ old('link') ? 'true' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.8 10.2a4 4 0 00-5.6 0l-4 4a4 4 0 105.6 5.6l1.1-1.1m-.7-4.9a4 4 0 005.6 0l4-4a4 4 0 00-5.6-5.6l-1.1 1.1"/></svg>
                            Tautan
                        </button>
                    </div>
                </details>
            </div>

            <div class="mt-4 space-y-4">
                <div data-content-addon-panel="files" hidden>
                    <label class="form-label" for="attachments">Lampiran</label>
                    <input id="attachments" name="attachments[]" type="file" multiple data-file-input class="field" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png,.webp,.mp4">
                    <p class="mt-2 text-xs text-muted">Gambar akan tampil sebagai pratinjau. PDF, dokumen, slide, dan video ditampilkan sesuai jenis berkas. Maksimal 5 berkas.</p>
                    <div data-file-list class="mt-3 space-y-2"></div>
                </div>

                <div data-content-addon-panel="link" @if(!old('link')) hidden @endif>
                    <label class="form-label" for="link">Tautan materi / video</label>
                    <input id="link" name="link" type="url" class="field" value="{{ old('link') }}" placeholder="https://">
                </div>

                <label data-pin-video-option class="flex cursor-pointer items-start gap-3 rounded-lg border border-line/60 bg-white p-3 text-xs" hidden>
                    <input type="checkbox" name="pin_video" value="1" class="mt-0.5 rounded border-line text-brand" @checked(old('pin_video'))>
                    <span><span class="block font-semibold text-ink">Pin video ke bagian atas course</span><span class="mt-0.5 block text-muted">Gunakan untuk tautan YouTube atau lampiran MP4 agar bisa diputar langsung di halaman course.</span></span>
                </label>
            </div>
        </section>

        {{-- Pengaturan Batas Waktu & Durasi Kuis --}}
        <section data-quiz-duration-settings class="rounded-xl border border-line/70 bg-white p-4 shadow-xs transition-all duration-300 ease-out" hidden>
            <input type="hidden" name="duration_mode" id="duration_mode" value="{{ old('duration_mode', 'disabled') }}">
            <label class="flex cursor-pointer items-center justify-between gap-4">
                <span>
                    <span class="block text-sm font-bold text-ink">Pakai batas waktu</span>
                    <span class="mt-0.5 block text-xs text-muted">Aktifkan timer hitung mundur saat mahasiswa mulai mengerjakan.</span>
                </span>
                <input type="checkbox" data-duration-toggle class="h-4 w-4 rounded border-line text-brand" @checked(old('duration_mode') === 'enabled')>
            </label>
            <div data-duration-options class="mt-4 border-t border-line/60 pt-4" hidden>
                <label class="form-label" for="duration_minutes">Durasi pengerjaan</label>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="1" max="1440" class="field w-24 bg-white py-2 text-xs" aria-label="Durasi menit">
                    <span class="text-xs font-semibold text-muted">menit</span>
                    @foreach([15, 30, 60, 90, 120] as $minutes)
                        <button type="button" data-duration-preset="{{ $minutes }}" class="rounded border border-line/60 bg-canvas px-2.5 py-1 text-[11px] font-semibold text-ink hover:bg-slate-200">{{ $minutes }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 border-t border-line/60 pt-4">
                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <span>
                        <span class="block text-sm font-bold text-ink">Pakai tenggat kuis</span>
                        <span class="mt-0.5 block text-xs text-muted">Tentukan batas tanggal dan waktu kuis dapat dikerjakan.</span>
                    </span>
                    <input type="checkbox" data-quiz-due-toggle class="h-4 w-4 rounded border-line text-brand" @checked(old('due') && in_array(old('type'), ['kuis', 'uts', 'uas', 'lainnya'], true))>
                </label>
                <div data-quiz-due-options class="mt-4" @if(!(old('due') && in_array(old('type'), ['kuis', 'uts', 'uas', 'lainnya'], true))) hidden @endif>
                    <label class="form-label" for="quiz_due">Tanggal dan waktu tenggat</label>
                    <input type="datetime-local" id="quiz_due" name="due" class="field" value="{{ old('due') }}" @disabled(!(old('due') && in_array(old('type'), ['kuis', 'uts', 'uas', 'lainnya'], true)))>
                </div>
            </div>
        </section>

        {{-- Paket Soal Asesmen (Kuis, UTS, UAS) --}}
        <section data-question-builder class="space-y-4" hidden>
            {{-- Toolbar Soal --}}
            <div class="rounded-xl border border-line/80 bg-white p-4 shadow-2xs space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-sm font-bold text-ink" data-assessment-title-label>Susun Soal</h2>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md border bg-slate-50 text-slate-700 border-line/80" data-total-points-badge>
                            Total Skor: 0 / 100
                        </span>
                        <span class="text-xs text-muted font-medium" data-question-total>0 soal</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" data-toggle-cpmk-summary class="button-secondary text-xs py-1.5 px-3 font-medium">
                            <span data-cpmk-summary-stat>Ringkasan CPMK</span>
                        </button>
                        <button type="button" class="button-primary text-xs py-1.5 px-3.5 font-semibold" data-add-question>+ Tambah Soal</button>
                    </div>
                </div>

                {{-- Baris Pengaturan: Target Jumlah Soal & Bagi Rata --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line/40 pt-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-muted font-medium">Target Jumlah Soal:</span>
                        <input type="number" min="1" max="500" value="5" class="field w-16 py-1 px-2 text-center font-bold text-ink text-xs bg-slate-50" data-target-question-count placeholder="5">
                        <button type="button" class="button-secondary py-1 px-2.5 text-xs font-semibold" data-apply-question-count>Atur</button>
                        <button type="button" data-auto-distribute-points class="button-secondary py-1 px-2.5 text-xs font-semibold text-brand hover:text-brand-dark" title="Bagi rata total 100 poin ke seluruh soal">Bagi Rata (100 / n)</button>
                    </div>
                </div>

                {{-- Panel Rincian CPMK --}}
                <div data-cpmk-summary-panel class="pt-3 border-t border-line/50" hidden>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="text-muted text-[11px] font-semibold bg-slate-50 border-b border-line/50">
                                    <th class="py-2 px-3">Target CPMK</th>
                                    <th class="py-2 px-3 text-center">Jumlah Soal</th>
                                    <th class="py-2 px-3 text-center">Bobot CPMK</th>
                                    <th class="py-2 px-3 text-center">Porsi per Soal</th>
                                </tr>
                            </thead>
                            <tbody data-cpmk-summary-rows class="divide-y divide-line/40 font-medium">
                                <tr>
                                    <td colspan="4" class="py-2.5 px-3 text-center text-muted italic">Tambahkan soal untuk melihat ringkasan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Navigasi Tab Soal --}}
            <div data-question-pagination-header class="flex items-center justify-between gap-3 rounded-xl border border-line/70 bg-white px-3 py-2 shadow-2xs">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 max-w-full min-w-0 flex-1" data-question-tabs></div>
                <div class="flex items-center gap-1.5 shrink-0 pl-2 border-l border-line/60">
                    <button type="button" data-prev-question class="button-secondary text-xs py-1 px-2.5 font-medium">Sebelumnya</button>
                    <button type="button" data-next-question class="button-secondary text-xs py-1 px-2.5 font-medium">Selanjutnya</button>
                </div>
            </div>

            <div data-question-rows></div>

            {{-- Template Baris Soal --}}
            <template data-question-template>
                <section data-question-row class="rounded-xl border border-line/70 bg-white p-5 shadow-xs space-y-4">
                    {{-- Header soal --}}
                    <div class="flex items-center justify-between border-b border-line/50 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-slate-100 text-slate-700 text-xs font-bold font-mono" data-question-number-badge>1</span>
                            <h3 data-question-number class="text-sm font-bold text-ink">Soal 1</h3>
                        </div>
                        <button type="button" data-remove-question class="text-xs font-medium text-danger hover:underline">Hapus soal</button>
                    </div>

                    {{-- Jenis Soal, Poin Soal & CPMK --}}
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="form-label text-xs">Jenis Soal</label>
                            <select data-q-field="type" class="field text-xs py-2 bg-white font-medium">
                                <option value="pilihan">Pilihan Ganda (Satu Jawaban)</option>
                                <option value="kompleks">Pilihan Ganda Kompleks (Banyak Jawaban)</option>
                                <option value="uraian">Uraian / Esai</option>
                                <option value="benar_salah">Benar / Salah</option>
                                <option value="mencocokkan">Menjodohkan</option>
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="form-label text-xs mb-1">Poin Soal</label>
                                <span data-q-point-share class="text-xs font-mono font-bold text-brand">20 / 100</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input data-q-field="points" type="number" min="1" max="1000" value="20" class="field text-xs py-2 bg-white font-mono font-bold text-ink" placeholder="20" required>
                                <span class="text-xs text-muted shrink-0">poin</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label text-xs">Target CPMK <span class="text-danger">*</span></label>
                            <select data-q-field="cpmk" class="field text-xs py-2 bg-white" required>
                                @foreach(\App\Support\AcademicPreview::config($course['id'])['cpmk'] as $outcome)
                                    <option value="{{ $outcome['code'] }}">
                                        {{ $outcome['code'] }} ({{ $outcome['cpl'] }}) — {{ $outcome['description'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div data-q-score-mode-container class="sm:col-span-3" hidden>
                            <label class="form-label text-xs">Mode Penilaian PG Kompleks</label>
                            <select data-q-field="score_mode" class="field text-xs py-2 bg-white text-xs">
                                <option value="parsial">Mode Parsial (Proporsional)</option>
                                <option value="semua_atau_nol">Semua atau Nol (Tepat Sesuai Kunci)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Pertanyaan / Instruksi Soal --}}
                    <div>
                        <label class="form-label text-xs">Pertanyaan</label>
                        <textarea rows="3" data-q-field="prompt" class="field text-xs leading-relaxed" placeholder="Tuliskan pertanyaan atau instruksi soal..." required></textarea>
                    </div>

                    {{-- Gambar Stimulus / Ilustrasi Soal --}}
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-dashed border-line bg-canvas/30 p-3">
                        <label class="button-secondary cursor-pointer px-3 py-2 text-xs">
                            + Tambah gambar
                            <input type="file" data-q-field="image" class="sr-only" accept="image/jpeg,image/png,image/webp">
                        </label>
                        <span class="text-[11px] text-muted">Opsional · JPG, PNG, atau WebP · maks. 5 MB</span>
                        <div class="flex items-start gap-3">
                            <div data-q-preview-box hidden class="relative shrink-0">
                                <img data-q-preview class="h-16 w-24 rounded object-cover border border-line/60" alt="Pratinjau stimulus">
                                <button type="button" data-q-remove-image class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-rose-600 text-white text-xs flex items-center justify-center shadow hover:bg-rose-700 font-bold" title="Hapus gambar">×</button>
                            </div>
                        </div>
                        <input type="hidden" data-q-field="alt">
                    </div>

                    {{-- Pilihan Ganda & Kompleks --}}
                    <div data-q-options class="rounded-lg border border-line/50 bg-canvas/30 p-3.5 space-y-3" hidden>
                        <div class="flex items-center justify-between">
                            <span class="form-label text-xs mb-0">Pilihan Jawaban (A, B, C...)</span>
                            <button type="button" data-add-choice-btn class="button-secondary text-xs py-1.5 px-3 font-semibold">+ Tambah Pilihan</button>
                        </div>
                        <div data-choice-list class="space-y-2"></div>
                        <textarea data-q-field="options" hidden></textarea>
                    </div>

                    {{-- Benar / Salah --}}
                    <div data-q-boolean class="rounded-lg border border-line/50 bg-canvas/30 p-3.5 text-xs space-y-2" hidden>
                        <span class="form-label text-xs">Pilihan Jawaban</span>
                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer font-medium text-ink">
                                <input type="radio" data-q-field="boolean_answer" value="Benar" checked class="text-brand">
                                <span>Benar</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer font-medium text-ink">
                                <input type="radio" data-q-field="boolean_answer" value="Salah" class="text-brand">
                                <span>Salah</span>
                            </label>
                        </div>
                    </div>

                    {{-- Mencocokkan / Menjodohkan --}}
                    <div data-q-matching class="rounded-lg border border-line/50 bg-canvas/30 p-3.5 space-y-3" hidden>
                        <div class="flex items-center justify-between">
                            <span class="form-label text-xs mb-0">Daftar Pasangan Menjodohkan</span>
                            <button type="button" data-add-pair-btn class="button-secondary text-xs py-1.5 px-3 font-semibold">+ Tambah Pasangan</button>
                        </div>
                        <div data-pair-list class="space-y-2"></div>
                        <textarea data-q-field="options" hidden></textarea>
                    </div>
                </section>
            </template>
            <script type="application/json" data-old-questions>@json(old('questions', []))</script>
        </section>

        {{-- Tugas biasa dan pemrograman berada dalam satu jenis konten. --}}
        <div data-legacy-question-settings data-assignment-fields class="space-y-4 pt-2 transition-all duration-300 ease-out" hidden>
            <div class="grid gap-4 sm:grid-cols-2">
                <fieldset>
                    <legend class="form-label">Jenis tugas</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer rounded-lg border border-line/70 bg-white p-3 text-xs">
                            <input type="radio" name="task_mode" value="regular" data-task-mode @checked(old('task_mode', old('type') === 'coding' ? 'coding' : 'regular') === 'regular')>
                            <span class="ml-1 font-semibold text-ink">Tugas biasa</span>
                        </label>
                        <label class="cursor-pointer rounded-lg border border-line/70 bg-white p-3 text-xs">
                            <input type="radio" name="task_mode" value="coding" data-task-mode @checked(old('task_mode', old('type') === 'coding' ? 'coding' : 'regular') === 'coding')>
                            <span class="ml-1 font-semibold text-ink">Pemrograman</span>
                        </label>
                    </div>
                    <input id="question_type" name="question_type" type="hidden" data-question-type value="{{ old('question_type', 'uraian') }}">
                </fieldset>
                <div class="rounded-lg border border-line/60 bg-slate-50 px-4 py-3 text-xs text-muted">
                    <p class="font-semibold text-ink">Pengaturan bobot</p>
                    <p class="mt-1">Tugas biasa memakai persentase CPMK manual. Tugas pemrograman dihitung otomatis dari tahap yang dipetakan.</p>
                    <input type="hidden" id="points" name="points" value="100">
                </div>
            </div>

            <fieldset data-manual-cpmk-settings class="rounded-xl border border-line/70 bg-white p-4">
                <legend class="px-1 text-sm font-bold text-ink">CPMK dan persentase tugas</legend>
                <p class="mb-3 text-xs text-muted">Isi hanya CPMK yang dinilai. Total persentase harus 100%.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach(\App\Support\AcademicPreview::config($course['id'])['cpmk'] as $outcome)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-line/60 p-3">
                            <span class="min-w-0"><strong class="block text-xs text-ink">{{ $outcome['code'] }}</strong><span class="line-clamp-2 text-[11px] text-muted">{{ $outcome['description'] }}</span></span>
                            <span class="flex shrink-0 items-center gap-1"><input type="number" min="0" max="100" step="0.01" name="manual_cpmk_weights[{{ $outcome['code'] }}]" value="{{ old('manual_cpmk_weights.'.$outcome['code']) }}" class="field w-20 px-2 py-1.5 text-right text-xs" data-manual-cpmk-weight><span class="text-xs text-muted">%</span></span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-3 text-xs font-semibold text-muted" data-manual-weight-total>Total: 0%</p>
            </fieldset>

            <div class="rounded-xl border border-line/70 bg-white p-4">
                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <span>
                        <span class="block text-sm font-bold text-ink">Pakai tenggat waktu</span>
                        <span class="mt-0.5 block text-xs text-muted">Aktifkan jika tugas harus dikumpulkan sebelum waktu tertentu.</span>
                    </span>
                    <input type="checkbox" data-due-toggle class="h-4 w-4 rounded border-line text-brand" @checked(old('due') && in_array(old('type'), ['tugas', 'coding'], true))>
                </label>
                <div data-due-options class="mt-4 border-t border-line/60 pt-4" @if(!(old('due') && in_array(old('type'), ['tugas', 'coding'], true))) hidden @endif>
                    <label class="form-label" for="task_due">Tanggal dan waktu tenggat</label>
                    <input type="datetime-local" id="task_due" name="due" class="field" value="{{ old('due') }}" @disabled(!(old('due') && in_array(old('type'), ['tugas', 'coding'], true)))>
                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-xs text-ink">
                        <input type="hidden" name="allow_late" value="0">
                        <input type="checkbox" name="allow_late" value="1" @checked(old('allow_late', '1') == '1') class="rounded border-line text-brand">
                        Izinkan pengumpulan terlambat
                    </label>
                </div>
            </div>

            @foreach(['file', 'image', 'link', 'text'] as $format)
                <input type="hidden" name="formats[]" value="{{ $format }}">
            @endforeach
        </div>

        <section data-coding-step-builder class="rounded-xl border border-line/70 bg-canvas/40 p-4 sm:p-5" hidden>
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line/60 pb-3">
                <div><h2 class="section-heading">Tahapan pemrograman</h2><p class="mt-1 text-xs text-muted">Susun materi atau instruksi per tahap. Mahasiswa berpindah tahap seperti saat mengerjakan kuis.</p></div>
                <button type="button" class="button-primary px-3 py-2 text-xs" data-add-coding-step>+ Tambah tahap</button>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line/60 bg-white p-3">
                <div class="flex flex-wrap gap-1.5" data-coding-step-tabs></div>
                <div class="flex gap-2"><button type="button" class="button-secondary px-3 py-1.5 text-xs" data-prev-coding-step>← Sebelumnya</button><button type="button" class="button-secondary px-3 py-1.5 text-xs" data-next-coding-step>Selanjutnya →</button></div>
            </div>
            <div class="mt-4" data-coding-step-rows></div>
            <template data-coding-step-template>
                <article data-coding-step-row class="space-y-4 rounded-xl border border-line/70 bg-white p-4">
                    <div class="flex items-center justify-between gap-3"><h3 class="text-sm font-bold text-ink" data-coding-step-title>Tahap 1</h3><button type="button" class="text-xs font-semibold text-danger hover:underline" data-remove-coding-step>Hapus tahap</button></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label><span class="form-label text-xs">Judul tahap</span><input data-step-field="title" class="field" placeholder="Contoh: Memahami struktur data" required></label>
                        <label><span class="form-label text-xs">Target CPMK</span><select data-step-field="cpmk" class="field" required>@foreach(\App\Support\AcademicPreview::config($course['id'])['cpmk'] as $outcome)<option value="{{ $outcome['code'] }}">{{ $outcome['code'] }} — {{ $outcome['description'] }}</option>@endforeach</select></label>
                    </div>
                    <label><span class="form-label text-xs">Materi / instruksi tahap</span><textarea rows="4" data-step-field="body" class="field" placeholder="Jelaskan materi atau pekerjaan pada tahap ini..." required></textarea></label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label><span class="form-label text-xs">Lampiran (opsional)</span><span class="button-secondary flex cursor-pointer items-center justify-center px-3 py-2 text-xs">+ Pilih lampiran<input type="file" data-step-field="attachment" class="sr-only" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png,.webp,.mp4"></span></label>
                        <label><span class="form-label text-xs">Tautan (opsional)</span><input type="url" data-step-field="link" class="field" placeholder="https://"></label>
                    </div>
                </article>
            </template>
            <script type="application/json" data-old-coding-steps>@json(old('coding_steps', []))</script>
            <p class="mt-3 text-xs text-muted">Untuk penilaian otomatis, setiap tahap diperlakukan sebagai satu kriteria pada CPMK yang dipilih.</p>
        </section>

        <input type="hidden" id="cpmk" name="cpmk" value="{{ old('cpmk', \App\Support\AcademicPreview::config($course['id'])['cpmk'][0]['code'] ?? 'CPMK') }}">

        <div data-form-error class="hidden text-xs text-danger font-medium border-t border-line/60 pt-3"></div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line/60 pt-5">
            <a class="button-secondary" href="{{ route('dosen.course.show', $course['id']) }}">Batal</a>
            <button type="submit" class="button-primary" data-submit-content>Tambahkan ke course</button>
        </div>
    </form>
</div>
@endsection
