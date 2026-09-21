@extends('layouts.mahasiswa')
@section('header', 'Tambah konten course')
@section('content')
<div class="mx-auto w-full max-w-6xl">
    <a class="button-secondary inline-flex items-center gap-2 px-3 py-2 text-xs" href="{{ route('dosen.course.show', $course['id']) }}">
        <span aria-hidden="true">←</span><span>Kembali ke course</span>
    </a>
    <h1 class="page-heading mt-5">Tambah konten</h1>
    <p class="page-description">Materi, tugas, kuis, dan pengumuman tetap terhubung ke course ini.</p>

    <form class="surface mt-7 space-y-6 p-6 sm:p-8" action="{{ route('dosen.item.store', $course['id']) }}" method="post" enctype="multipart/form-data" data-content-form data-step="{{ $errors->has('questions.*') ? 'questions' : 'setup' }}">
        @csrf
        <div class="flex items-center gap-3 border-b border-line/60 pb-4" data-content-progress>
            <div class="flex items-center gap-2 text-xs font-bold text-brand" data-step-indicator="setup">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand text-white">1</span>
                <span>Informasi konten</span>
            </div>
            <span class="h-px flex-1 bg-line/70"></span>
            <div class="flex items-center gap-2 text-xs font-semibold text-muted" data-step-indicator="questions">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-line bg-white">2</span>
                <span>Susun soal</span>
            </div>
        </div>

        <div class="space-y-6" data-content-setup>
        <div class="border-b border-line/60 pb-3">
            <h2 class="text-base font-bold text-ink">Informasi Konten</h2>
            <p class="text-xs text-muted">Siapkan judul, instruksi, dan kebutuhan pendukung. Untuk kuis, soal disusun pada langkah berikutnya.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="type">Jenis konten <span class="text-danger">*</span></label>
                <select id="type" name="type" class="field" data-content-type required>
                    <option value="" disabled @selected(!old('type') && !request('type'))>-- Pilih jenis konten --</option>
                    @foreach(['materi' => 'Materi', 'tugas' => 'Tugas', 'kuis' => 'Kuis', 'pengumuman' => 'Pengumuman', 'lainnya' => 'Lainnya'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', request('type')) === $value || ($value === 'tugas' && old('type') === 'coding'))>{{ $label }}</option>
                    @endforeach
                </select>
                <div data-custom-type-container hidden class="mt-2.5 space-y-1">
                    <label class="form-label text-xs" for="custom_type">Nama jenis konten (Lainnya)</label>
                    <input id="custom_type" name="custom_type" class="field text-xs py-2 bg-white" placeholder="Ketik UTS atau UAS..." data-custom-type value="{{ old('custom_type') }}" maxlength="10">
                    <p class="text-[11px] text-muted">Hanya dapat diisi <strong>UTS</strong> atau <strong>UAS</strong>.</p>
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
        </div>

        {{-- Paket Soal Campuran / Multi-Question Builder --}}
        <section data-question-builder class="rounded-xl bg-canvas p-4 sm:p-5 transition-all duration-300 ease-out" hidden>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/60 pb-3">
                <div>
                    <h2 class="section-heading" data-assessment-title-label>Susun Soal</h2>
                    <p class="mt-1 text-xs text-muted">Satu soal ditampilkan dalam satu waktu agar penyusunan tetap fokus.</p>
                </div>
                <button type="button" class="button-primary text-xs font-semibold py-2 px-4 shadow-xs hover:shadow transition" data-add-question>+ Tambah Soal</button>
            </div>

            <div class="mt-4">
                <label class="form-label text-xs" for="component">Komponen Nilai dalam Rencana Evaluasi</label>
                <select class="field text-xs py-2 bg-white" id="component" name="component">
                    @foreach(\App\Support\AcademicPreview::config($course['id'])['components'] as $component)
                        <option value="{{ $component['code'] }}">{{ $component['name'] }} (Bobot {{ $component['weight'] }}%)</option>
                    @endforeach
                </select>
            </div>

            <div data-question-pagination-header class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line/60 bg-white p-3 shadow-2xs">
                <div class="flex flex-wrap items-center gap-1.5" data-question-tabs></div>
                <div class="flex items-center gap-2">
                    <button type="button" data-prev-question class="button-secondary text-xs py-1.5 px-3 font-semibold">← Sebelumnya</button>
                    <button type="button" data-next-question class="button-secondary text-xs py-1.5 px-3 font-semibold">Selanjutnya →</button>
                </div>
            </div>

            <div data-question-rows class="mt-4"></div>

            <div class="mt-4 border-t border-line/60 pt-4">
                <p class="text-sm font-semibold" data-question-total>0 soal</p>
            </div>
            <p class="mt-2 text-xs text-muted">Maksimal 30 soal. Nilai tiap CPMK selalu 100 dan dibagi rata berdasarkan jumlah soal pada CPMK tersebut; kontribusi CPMK mengikuti proporsi jumlah soalnya.</p>

            {{-- Template Baris Soal --}}
            <template data-question-template>
                <section data-question-row class="rounded-xl border border-line/70 bg-white p-5 shadow-xs space-y-4">
                    {{-- Header soal mengikuti pola branch frontend Brodhii. --}}
                    <div class="flex items-center justify-between border-b border-line/60 pb-3">
                        <h3 data-question-number class="text-sm font-bold text-ink">Soal 1</h3>
                        <button type="button" data-remove-question class="text-xs font-semibold text-danger hover:underline">Hapus soal</button>
                    </div>

                    {{-- Jenis Soal & Bobot Nilai --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label text-xs">Jenis Soal</label>
                            <select data-q-field="type" class="field text-xs py-2 bg-white">
                                <option value="uraian">Essay / Uraian Terbuka</option>
                                <option value="pilihan">Pilihan Ganda (Satu Jawaban)</option>
                                <option value="kompleks">Pilihan Ganda Kompleks (Banyak Jawaban)</option>
                                <option value="benar_salah">Benar / Salah</option>
                                <option value="mencocokkan">Mencocokkan (Premis &amp; Pasangan Jawaban)</option>
                                <option value="coding">Pemrograman / Coding</option>
                            </select>
                        </div>
                        <div class="rounded-lg border border-line/60 bg-slate-50 px-3 py-2.5">
                            <input data-q-field="points" type="hidden" value="100">
                            <p class="text-xs font-semibold text-ink">Bobot dihitung otomatis</p>
                            <p class="mt-0.5 text-[11px] leading-relaxed text-muted">Setiap soal dalam CPMK yang sama mendapat porsi yang setara.</p>
                        </div>
                    </div>

                    {{-- Pemetaan CPMK & CPL --}}
                    <div class="rounded-lg bg-slate-50 p-3 border border-line/40">
                        <label class="form-label text-xs">Target Capaian Pembelajaran (CPMK &amp; CPL Terkait)</label>
                        <select data-q-field="cpmk" class="field text-xs py-2 bg-white mt-1">
                            @foreach(\App\Support\AcademicPreview::config($course['id'])['cpmk'] as $outcome)
                                <option value="{{ $outcome['code'] }}">
                                    {{ $outcome['code'] }} ({{ $outcome['cpl'] }}) - {{ $outcome['description'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-muted">Bobot nilai soal ini langsung terpetakan ke dalam rekapitulasi CPMK &amp; CPL mahasiswa.</p>
                    </div>

                    {{-- Pertanyaan / Instruksi Soal --}}
                    <div>
                        <label class="form-label text-xs">Pertanyaan / Instruksi Soal</label>
                        <textarea rows="3" data-q-field="prompt" class="field text-xs leading-relaxed" placeholder="Tuliskan pertanyaan, studi kasus, atau instruksi soal di sini..." required></textarea>
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

                    {{-- Pilihan Ganda & Kompleks (Visual Options + Tambah Pilihan) --}}
                    <div data-q-options class="rounded-lg border border-line/50 bg-canvas/30 p-3.5 space-y-3" hidden>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="form-label text-xs mb-0">Daftar Pilihan Jawaban (A, B, C...)</span>
                                <p class="text-[11px] text-muted">Tambahkan pilihan jawaban satu per satu dengan mudah.</p>
                            </div>
                            <button type="button" data-add-choice-btn class="button-secondary text-xs py-1.5 px-3 font-semibold">+ Tambah Pilihan</button>
                        </div>
                        <div data-choice-list class="space-y-2"></div>
                        <textarea data-q-field="options" hidden></textarea>
                    </div>

                    {{-- Benar / Salah --}}
                    <div data-q-boolean class="rounded-lg border border-line/50 bg-canvas/30 p-3.5 text-xs space-y-2" hidden>
                        <span class="form-label text-xs">Pilihan Jawaban</span>
                        <p class="text-[11px] text-muted">Pilihan otomatis tersedia untuk mahasiswa.</p>
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

                    {{-- Mencocokkan / Menjodohkan (Visual Pairs + Format Selector) --}}
                    <div data-q-matching class="rounded-lg border border-line/50 bg-canvas/30 p-3.5 space-y-3" hidden>
                        {{-- Mode Selector Pasangan --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 p-2.5 rounded-lg bg-white border border-line/40 text-xs">
                            <div class="flex flex-wrap items-center gap-4">
                                <span class="font-semibold text-ink">Format Pasangan:</span>
                                <label class="flex items-center gap-1.5 cursor-pointer font-medium text-ink">
                                    <input type="radio" data-pair-mode value="text" checked class="text-brand">
                                    <span>Teks ↔ Teks</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer font-medium text-ink">
                                    <input type="radio" data-pair-mode value="image" class="text-brand">
                                    <span>Gambar ↔ Teks</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer font-medium text-ink">
                                    <input type="radio" data-pair-mode value="image_image" class="text-brand">
                                    <span>Gambar ↔ Gambar</span>
                                </label>
                            </div>
                            <span class="text-[11px] text-muted" data-pair-mode-hint>Ketik istilah di kiri dan penjelasan di kanan</span>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <div>
                                <span class="form-label text-xs mb-0">Daftar Pasangan Menjodohkan</span>
                                <p class="text-[11px] text-muted" data-pair-instruction>Isi item premis di sebelah kiri dan pasangan jawaban di sebelah kanan.</p>
                            </div>
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

        <div class="flex flex-wrap justify-between gap-3 pt-5">
            <a class="button-secondary" href="{{ route('dosen.course.show', $course['id']) }}">Batal</a>
            <div class="flex items-center gap-2">
                <button type="button" class="button-secondary" data-back-to-setup hidden>← Kembali</button>
                <button type="button" class="button-primary" data-next-to-questions hidden>Selanjutnya: Susun soal →</button>
                <button class="button-primary" data-submit-content>Tambahkan ke course</button>
            </div>
        </div>
    </form>
</div>
@endsection
