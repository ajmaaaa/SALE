@extends('layouts.mahasiswa')
@section('header', 'Tambah konten course')
@section('content')
<div class="mx-auto max-w-3xl">
    <a class="quiet-link" href="{{ route('dosen.course.show', $course['id']) }}">← {{ $course['title'] }}</a>
    <h1 class="page-heading mt-5">Tambah konten</h1>
    <p class="page-description">Materi, tugas, kuis, dan pengumuman tetap terhubung ke course ini.</p>

    <form class="surface mt-7 space-y-6 p-6 sm:p-8" action="{{ route('dosen.item.store', $course['id']) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label" for="type">Jenis konten</label>
                <select id="type" name="type" class="field" data-content-type>
                    @foreach(\App\Support\LearningPreview::labels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="module">Nama modul / topik</label>
                <input id="module" name="module" class="field" required maxlength="100" list="modules" value="{{ old('module') }}" placeholder="Minggu 3 · Tree dan traversal">
                <datalist id="modules">
                    @foreach(collect(\App\Support\LearningPreview::items())->where('course', $course['id'])->pluck('module')->unique() as $module)
                        <option value="{{ $module }}">
                    @endforeach
                </datalist>
            </div>
        </div>

        <div>
            <label class="form-label" for="title">Judul</label>
            <input id="title" name="title" required maxlength="160" class="field" value="{{ old('title') }}">
        </div>

        <div>
            <label class="form-label" for="body">Materi / instruksi / stimulus soal</label>
            <textarea id="body" name="body" required rows="6" class="field">{{ old('body') }}</textarea>
        </div>

        {{-- Stimulus visual untuk materi / tugas tunggal --}}
        <section class="rounded-xl bg-canvas p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-sm">Gambar pada soal / materi</h2>
                    <p class="mt-1 text-xs leading-5 text-muted">Tambahkan diagram, tabel, ilustrasi, atau stimulus visual. Gambar tampil langsung di atas jawaban.</p>
                </div>
            </div>
            <div class="mt-4">
                <input id="question_image" name="question_image" type="file" accept="image/jpeg,image/png,image/webp" data-image-input class="field" aria-label="Gambar soal">
                <img data-image-preview hidden alt="Pratinjau gambar soal" class="mt-4 max-h-64 rounded-lg object-contain">
                <button data-image-remove type="button" hidden class="quiet-link mt-3">Hapus gambar soal</button>
            </div>
            <label class="form-label mt-4" for="image_alt">Deskripsi gambar</label>
            <input id="image_alt" name="image_alt" class="field" maxlength="300" placeholder="Jelaskan isi gambar untuk mahasiswa yang memakai pembaca layar" value="{{ old('image_alt') }}">
            <p class="mt-2 text-xs text-muted">JPG, PNG, atau WebP, maksimal 5 MB. Deskripsi wajib ketika gambar diunggah.</p>
        </section>

        <div>
            <label class="form-label" for="attachments">Lampiran materi atau berkas pendukung</label>
            <input id="attachments" name="attachments[]" type="file" multiple data-file-input class="field" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png,.webp,.mp4">
            <p class="mt-2 text-xs text-muted">PDF, PowerPoint, Word, gambar, atau MP4. Maksimal 5 berkas, 20 MB per berkas.</p>
            <div data-file-list class="mt-3 space-y-2"></div>
        </div>

        <div>
            <label class="form-label" for="link">Tautan materi / video (opsional)</label>
            <input id="link" name="link" type="url" class="field" value="{{ old('link') }}" placeholder="https://">
        </div>

        {{-- Pengaturan Batas Waktu & Durasi Kuis --}}
        <section data-quiz-duration-settings class="rounded-xl border border-line/70 bg-white p-5 shadow-xs space-y-4" hidden>
            <div class="border-b border-line/60 pb-3">
                <h2 class="text-sm font-bold text-ink">Batas Waktu &amp; Durasi Pengerjaan Kuis</h2>
                <p class="mt-0.5 text-xs text-muted">Tentukan apakah mahasiswa memiliki batas waktu countdown saat membuka ruang ujian kuis, atau pengerjaan bebas tanpa batas waktu.</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-start gap-3 rounded-lg border border-line/60 p-3.5 hover:bg-slate-50 cursor-pointer transition">
                    <input type="radio" name="duration_mode" value="enabled" checked class="mt-0.5" id="duration_mode_enabled">
                    <div class="space-y-2 flex-1">
                        <div>
                            <span class="text-xs font-bold text-ink block">Aktifkan Batas Waktu (Countdown Timer)</span>
                            <span class="text-[11px] text-muted block mt-0.5">Waktu akan berjalan mundur otomatis di pojok header ruang ujian saat mahasiswa mulai mengerjakan.</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <input type="number" name="duration_minutes" id="duration_minutes" value="60" min="1" max="1440" class="field text-xs py-1.5 w-24 bg-white" aria-label="Durasi menit">
                            <span class="text-xs font-semibold text-muted">Menit</span>
                            <div class="flex flex-wrap items-center gap-1.5 ml-2">
                                <button type="button" onclick="document.getElementById('duration_minutes').value=15" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-canvas border border-line/60 text-ink hover:bg-slate-200 transition">15 Menit</button>
                                <button type="button" onclick="document.getElementById('duration_minutes').value=30" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-canvas border border-line/60 text-ink hover:bg-slate-200 transition">30 Menit</button>
                                <button type="button" onclick="document.getElementById('duration_minutes').value=60" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-canvas border border-line/60 text-ink hover:bg-slate-200 transition">60 Menit</button>
                                <button type="button" onclick="document.getElementById('duration_minutes').value=90" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-canvas border border-line/60 text-ink hover:bg-slate-200 transition">90 Menit</button>
                                <button type="button" onclick="document.getElementById('duration_minutes').value=120" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-canvas border border-line/60 text-ink hover:bg-slate-200 transition">120 Menit (2 Jam)</button>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-line/60 p-3.5 hover:bg-slate-50 cursor-pointer transition">
                    <input type="radio" name="duration_mode" value="disabled" class="mt-0.5" id="duration_mode_disabled">
                    <div>
                        <span class="text-xs font-bold text-ink block">Matikan Durasi (Bebas / Tanpa Batas Waktu)</span>
                        <span class="text-[11px] text-muted block mt-0.5">Mahasiswa dapat mengerjakan tanpa rasa tertekan oleh timer hitung mundur. Cocok untuk latihan mandiri atau praktikum santai.</span>
                    </div>
                </label>
            </div>
        </section>

        {{-- Paket Soal Campuran / Multi-Question Builder --}}
        <section data-question-builder class="rounded-xl bg-canvas p-5" hidden>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="section-heading">Daftar Paket Soal</h2>
                    <p class="mt-1 text-xs text-muted">Buat beragam soal (Essay, Pilihan Ganda, Mencocokkan, Coding) dengan pemetaan CPMK &amp; CPL yang jelas.</p>
                </div>
                <button type="button" class="button-secondary text-xs font-semibold py-2 px-3.5" data-add-question>+ Tambah Soal</button>
            </div>

            <div class="mt-4">
                <label class="form-label text-xs" for="component">Komponen Nilai dalam Rencana Evaluasi</label>
                <select class="field text-xs py-2 bg-white" id="component" name="component">
                    @foreach(\App\Support\AcademicPreview::config($course['id'])['components'] as $component)
                        <option value="{{ $component['code'] }}">{{ $component['name'] }} · Bobot {{ $component['weight'] }}%</option>
                    @endforeach
                </select>
            </div>

            <div data-question-rows class="mt-5 space-y-4"></div>
            <p class="mt-4 text-sm font-semibold" data-question-total>0 soal · 0 poin</p>
            <p class="mt-2 text-xs text-muted">Maksimal 30 soal. Setiap soal memiliki target CPMK/CPL dan bobot nilai masing-masing.</p>

            {{-- Template Baris Soal --}}
            <template data-question-template>
                <section data-question-row class="rounded-xl border border-line/70 bg-white p-5 shadow-xs space-y-4">
                    {{-- Header Soal --}}
                    <div class="flex items-center justify-between border-b border-line/60 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-soft text-xs font-bold text-brand" data-question-number-badge>1</span>
                            <h3 data-question-number class="text-sm font-bold text-ink">Soal 1</h3>
                        </div>
                        <button type="button" data-remove-question class="text-xs font-semibold text-danger hover:underline">Hapus Soal</button>
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
                        <div>
                            <label class="form-label text-xs">Bobot Nilai / Poin</label>
                            <div class="flex items-center gap-2">
                                <input data-q-field="points" type="number" min="1" max="1000" value="10" class="field text-xs py-2 max-w-32" required>
                                <span class="text-xs text-muted">Poin</span>
                            </div>
                        </div>
                    </div>

                    {{-- Pemetaan CPMK & CPL --}}
                    <div class="rounded-lg bg-slate-50 p-3 border border-line/40">
                        <label class="form-label text-xs">Target Capaian Pembelajaran (CPMK &amp; CPL Terkait)</label>
                        <select data-q-field="cpmk" class="field text-xs py-2 bg-white mt-1">
                            @foreach(\App\Support\AcademicPreview::config($course['id'])['cpmk'] as $outcome)
                                <option value="{{ $outcome['code'] }}">
                                    {{ $outcome['code'] }} → {{ $outcome['cpl'] }} · {{ $outcome['description'] }}
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
                    <div class="rounded-lg border border-line/50 bg-canvas/40 p-3.5 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="form-label text-xs mb-0">Gambar Rujukan Utama Soal (Opsional)</span>
                            <span class="text-[11px] text-muted">JPG, PNG, WebP maks 5 MB</span>
                        </div>
                        <p class="text-[11px] text-muted">Gunakan bagian ini jika satu soal utuh memiliki <strong>1 gambar utama</strong> sebagai stimulus umum (contoh: satu diagram alur atau studi kasus). Bila Anda ingin mencocokkan beberapa gambar berbeda per baris, gunakan pilihan pada bagian Pasangan di bawah.</p>
                        <div class="flex flex-col sm:flex-row items-start gap-3">
                            <input type="file" data-q-field="image" class="field text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-soft file:text-brand hover:file:bg-brand/15" accept="image/jpeg,image/png,image/webp">
                            <div data-q-preview-box hidden class="relative shrink-0">
                                <img data-q-preview class="h-16 w-24 rounded object-cover border border-line/60" alt="Pratinjau stimulus">
                                <button type="button" data-q-remove-image class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-rose-600 text-white text-xs flex items-center justify-center shadow hover:bg-rose-700 font-bold" title="Hapus gambar">×</button>
                            </div>
                        </div>
                        <div data-q-alt-box hidden>
                            <label class="form-label text-[11px]">Deskripsi / Alt Teks Gambar <span class="text-danger">*</span></label>
                            <input data-q-field="alt" class="field text-xs py-1.5" placeholder="Jelaskan isi gambar untuk mahasiswa (contoh: Diagram alur Binary Search Tree)">
                        </div>
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

        {{-- Pengaturan Pekerjaan Tugas Tunggal (Legacy / Single Task) --}}
        <div data-legacy-question-settings data-assignment-fields class="space-y-5 pt-5">
            <h2 class="section-heading">Pengaturan Pekerjaan Tugas</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="question_type">Bentuk soal</label>
                    <select id="question_type" name="question_type" class="field" data-question-type>
                        @foreach(['uraian' => 'Uraian / jawaban terbuka', 'pilihan' => 'AKM · Pilihan ganda', 'kompleks' => 'AKM · Pilihan ganda kompleks', 'benar_salah' => 'Benar / Salah', 'mencocokkan' => 'Mencocokkan (Gambar / Teks)', 'coding' => 'Pemrograman'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('question_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="points">Poin maksimal</label>
                    <input type="number" id="points" name="points" min="1" max="1000" class="field" value="{{ old('points', 100) }}">
                </div>
            </div>

            {{-- Batas Waktu & Kebijakan Keterlambatan --}}
            <div class="rounded-xl bg-canvas p-4">
                <label class="form-label text-xs font-bold text-ink">Batas Waktu &amp; Kebijakan Keterlambatan</label>
                <div class="mt-3 grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-xs" for="due">Tenggat Waktu (Opsional)</label>
                        <input type="datetime-local" id="due" name="due" class="field text-xs" value="{{ old('due') }}">
                    </div>
                    <div>
                        <label class="form-label text-xs">Pengumpulan Terlambat</label>
                        <div class="mt-1.5 space-y-2">
                            <label class="flex items-center gap-2.5 text-xs text-ink cursor-pointer">
                                <input type="radio" name="allow_late" value="1" @checked(old('allow_late', '1') == '1') class="text-brand">
                                <span><strong>Izinkan kirim terlambat</strong> (Mahasiswa tetap dapat mengumpulkan)</span>
                            </label>
                            <label class="flex items-center gap-2.5 text-xs text-ink cursor-pointer">
                                <input type="radio" name="allow_late" value="0" @checked(old('allow_late') === '0') class="text-brand">
                                <span><strong>Kunci setelah tenggat</strong> (Tolak &amp; tidak bisa upload lagi)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div data-choice-fields>
                <label class="form-label" for="options">Pilihan jawaban</label>
                <textarea id="options" name="options" rows="4" class="field" placeholder="Satu pilihan per baris">{{ old('options') }}</textarea>
                <p class="mt-2 text-xs text-muted">Minimal dua pilihan. Penilaian dilakukan dosen; kunci otomatis belum tersedia.</p>
                <div data-option-images class="mt-4 space-y-3"></div>
                <p class="mt-2 text-xs text-muted">Setiap pilihan dapat dilengkapi gambar JPG, PNG, atau WebP (maks. 2 MB). Teks pilihan juga menjadi deskripsi gambarnya.</p>
            </div>

            <fieldset>
                <legend class="form-label">Format jawaban yang diterima</legend>
                <div class="flex flex-wrap gap-4">
                    @foreach(['file' => 'Dokumen / ZIP', 'image' => 'Gambar', 'link' => 'Tautan', 'text' => 'Teks'] as $value => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="formats[]" value="{{ $value }}" @checked(in_array($value, old('formats', ['file', 'image', 'link', 'text'])))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>

        <div>
            <label class="form-label" for="cpmk">Capaian pembelajaran / CPMK Umum</label>
            <textarea required id="cpmk" name="cpmk" rows="2" class="field" placeholder="Mahasiswa mampu…">{{ old('cpmk') }}</textarea>
        </div>

        <div class="flex justify-between pt-5">
            <a class="button-secondary" href="{{ route('dosen.course.show', $course['id']) }}">Batal</a>
            <button class="button-primary">Tambahkan ke course</button>
        </div>
    </form>
</div>
@endsection
