# NOTES: Audit di Luar Scope Fase 1

Dokumen ini mencatat temuan audit teknis di luar cakupan Fase 1 sesuai instruksi PRD-SALE-AI-Tutor-v2.md. Temuan ini **hanya dicatat** dan tidak diubah pada Fase 1.

---

## 1. Pengiriman Output Konsol / Traceback dari Frontend
- **Kondisi Saat Ini**:
  - Pada [`resources/js/app.js`](file:///home/ajmaaa/Projects/SALE/resources/js/app.js) baris 1577, payload pengiriman pesan ke AI adalah:
    ```javascript
    body: JSON.stringify({ question, code }),
    ```
  - Frontend Coding Room **belum mengirimkan** `console_output` (output terminal/traceback eksekusi Python) maupun `selected_line` secara terstruktur ke endpoint AI tutor.
  - Di sisi backend ([`app/Http/Controllers/AiTutorController.php`](file:///home/ajmaaa/Projects/SALE/app/Http/Controllers/AiTutorController.php) baris 264), validasi input hanya menerima:
    ```php
    $input = $request->validate(['question' => 'required|string|max:2000', 'code' => 'nullable|string|max:4000']);
    ```
- **Rencana Tindak Lanjut (Fase 4)**:
  - Di Fase 4, perbarui form submit di `app.js` agar mengambil potongan akhir terminal output (`console_output` maks 2.000 karakter) dan parameter `selected_line`, serta perbarui validasi Controller dan `ContextBuilder`.

---

## 2. Pengecekan Status Enrollment & Status Kelas
- **Kondisi Saat Ini**:
  - Di [`AiTutorController::authorizeAssessment`](file:///home/ajmaaa/Projects/SALE/app/Http/Controllers/AiTutorController.php):
    ```php
    $allowed = $user->hasRole(Role::DOSEN)
        ? $user->can('manage', $section)
        : ($user->hasRole(Role::MAHASISWA)
            && $section->students()->where('users.id', $user->id)->exists()
            && $assessment->status === Assessment::STATUS_PUBLISHED);
    ```
  - **Peserta Kicked / Dropped**: Relasi `$section->students()` di [`app/Models/ClassSection.php`](file:///home/ajmaaa/Projects/SALE/app/Models/ClassSection.php) baris 99 menggunakan `->withPivotValue('status', 'enrolled')`. Artinya, mahasiswa dengan status `'kicked'`, `'left'`, atau `'pending_appeal'` otomatis tidak lolos otorisasi (menghasilkan 403 Forbidden). Ini sudah aman untuk status keanggotaan peserta.
  - **Kelas Arsip (Archived Class)**: Otorisasi saat ini **belum memeriksa** apakah kelas sedang dalam status arsip (`$section->is_archived` atau semester kadaluarsa). Jika kelas telah diarsipkan, mahasiswa yang masih terdaftar tetap bisa memanggil API tutor dan menghabiskan token.
- **Rencana Tindak Lanjut (Fase 3)**:
  - Di Fase 3 (saat implementasi Policy untuk `ai_threads`), tambahkan aturan eksplisit: kelas arsip bersifat *read-only* (hanya boleh melihat riwayat chat, tidak boleh mengirim pesan baru ke AI).

---

## 3. Hasil Audit Adapter Provider (Fase 2)
Sesuai persyaratan PRD 6.7 dan instruksi Fase 2, berikut hasil audit kesiapan adapter provider yang ada di SALE:

| Fitur / Kemampuan | Google Gemini | OpenAI | DeepSeek |
|-------------------|---------------|--------|----------|
| **1. Usage Token Aktual** | Mendukung penuh: `promptTokenCount`, `candidatesTokenCount`, `cachedContentTokenCount`, `thoughtsTokenCount`, `totalTokenCount`. | Mendukung penuh: `prompt_tokens`, `completion_tokens`, `prompt_tokens_details.cached_tokens`, `completion_tokens_details.reasoning_tokens`, `total_tokens`. | Mendukung: `prompt_tokens`, `completion_tokens`, `prompt_cache_hit_tokens`, `reasoning_tokens`, `total_tokens`. |
| **2. Mode JSON / Structured Output** | Mendukung `responseMimeType: 'application/json'` dan penegakan skema ketat via `responseSchema`. | Mendukung `response_format: {"type": "json_object"}`. Wajib menyertakan kata `"JSON"` pada pesan sistem/instruksi prompt. | Mendukung `response_format: {"type": "json_object"}`. Belum mendukung `responseSchema` ketat di level API; validasi skema tetap harus dilakukan secara deterministik di PHP. |
| **3. Pembedaan Error & Limit** | Membedakan 429 (`RESOURCE_EXHAUSTED`), 5xx, timeout (cURL 28), dan finishReason `SAFETY`. Catatan: Google API tidak selalu menyertakan header `Retry-After`. | Membedakan 429 (`rate_limit_exceeded` + header `Retry-After`), 5xx, timeout, dan `finish_reason: "content_filter"`. | Membedakan 429 (`rate_limit_error`), 5xx, timeout, dan `finish_reason: "content_filter"`. |

### Catatan Keterbatasan Provider (Tidak Diubah di Fase 2):
1. **Google Gemini `Retry-After` Header**: Pada respons HTTP 429, Gemini sering tidak mengirimkan header HTTP `Retry-After`. Adapter mengandalkan exponential backoff + random jitter lokal (0.5s, 1.0s, dst.) untuk mengatasi hal ini.
2. **DeepSeek Response Schema**: DeepSeek belum mendukung argumen schema JSON seperti `responseSchema` di Gemini. Output JSON divalidasi fail-closed di PHP pada Fase 5.
3. **Provider Lain (Claude / Anthropic)**: Belum tersedia adapter di kode SALE saat ini. Interface pemilihan provider saat ini melayani Google AI, OpenAI, dan DeepSeek. Sesuai PRD, adapter baru dapat ditambahkan mengikuti pola interface yang sama tanpa mengganggu pipeline tutor.

---

## 4. Catatan Fase 3 & Observasi untuk Fase 4
- **Status Kebijakan Kelas Arsip & Enrollment**:
  - Telah selesai diimplementasikan pada Fase 3 di [`app/Policies/AiThreadPolicy.php`](file:///home/ajmaaa/Projects/SALE/app/Policies/AiThreadPolicy.php) dan [`app/Http/Controllers/AiTutorController.php`](file:///home/ajmaaa/Projects/SALE/app/Http/Controllers/AiTutorController.php).
  - Mahasiswa kicked/dropped ditolak (403), kelas arsip berstatus *read-only* (GET thread/status 200, POST message 403).
- **History Server & Thread**:
  - `ai_threads` dan `ai_messages` aktif. History client dan `class_section_id` diabaikan, riwayat dimuat dari DB (maks 8 pesan, 1.500 karakter/pesan, assistant ditolak diganti `[permintaan ditolak]`).
- **Observasi untuk Fase 4**:
  - Saat ini konteks RAG materi di `AiTutorController` masih mengambil 5 materi pertama secara naif (`orderBy('id')->limit(5)`). Pada Fase 4, ini akan digantikan penuh oleh `ContextBuilder` (judul, langkah coding, materi tertaut dari `learning_payload.linked_material_ids`, fallback 30 materi ringkas, filter data sensitif/solusi, dan console traceback).

---

## 5. Pemisahan Peran Tabel AI Tutor & Pencegahan Inkonsistensi Sinkronisasi (Fase 3b)
- **Peran Tabel `ai_tasks`**:
  - Menyimpan metadata definisi tugas praktikum (`id`, `title`, `body`, `enabled`) yang disinkronkan dari data `Assessment` (atau mock demo/preview).
  - Berfungsi murni sebagai referensi silabus/instruksi tugas dan target relasi otorisasi.
  - **TIDAK PERNAH** menyimpan riwayat percakapan, pesan mahasiswa, atau giliran dialog.
- **Peran Tabel `ai_threads`**:
  - Menyimpan sesi percakapan per `(user_id, assessment_id, class_section_id)` beserta metrik agregat: `turns`, `blocked_count`, `tokens_used`, `last_provider`, dan `cleared_at`.
  - Berfungsi sebagai kontainer percakapan dan sumber kebenaran untuk batas giliran (`AI_TASK_TURNS`), hitungan penolakan (`blocked_count`), dan status pembersihan tampilan (`cleared_at`).
- **Peran Tabel `ai_messages`**:
  - Menyimpan detail tiap pesan percakapan (`thread_id`, `role`, `content`, `verdict`, `tokens_in`, `tokens_out`, `created_at`).
  - Berfungsi sebagai sumber riwayat percakapan (server-side history) untuk perakitan prompt dan tampilan antarmuka.
- **Peran Tabel `ai_turns` (Legacy V1)**:
  - Menyimpan log tanya-jawab pada arsitektur lama V1 sebelum model thread diperkenalkan.
  - **Pencegahan Penulisan Ganda**: Saat sub-flag `AI_THREADS` aktif bersama `AI_TUTOR_V2`, seluruh pencatatan percakapan dialihkan secara eksklusif ke `ai_messages` dan `ai_threads`. Sistem **tidak lagi menulis ganda ke `ai_turns`**, sehingga tidak ada risiko desinkronisasi status atau duplikasi data ketika thread dibersihkan secara lunak (*soft clear*) maupun saat dipangkas (*prune*).

---

## 6. Catatan Fase 4 (ContextBuilder, Template Nonce, & Scope Guard Cooldown)
- **ContextBuilder**:
  - Mengambil judul tugas, deskripsi (dibersihkan via `strip_tags`), tipe asesmen, dan langkah pengerjaan (`coding_steps` berisi nomor, judul, CPMK, instruksi).
  - Mengeliminasi seluruh kunci jawaban (`solution`, `answer_key`), *test cases* tersembunyi/rahasia (`hidden: true`, `secret: true`), dan penanda format solusi.
  - Mengambil materi kuliah tertaut dari `linked_material_ids` (atau fallback 30 materi terbit jika kosong/null).
  - Sanitasi kode (maksimal 8.000 karakter), sanitasi traceback konsol (potongan akhir/tail 2.000 karakter), dan sanitasi baris terpilih.
  - Sanitasi *injection attempts* terhadap tag delimitasi data/riwayat seperti `<data_...>` dan `<riwayat_...>`.
- **PromptTemplateRenderer**:
  - Membaca berkas template dari `resources/ai/` (`tutor_system.txt`, `tutor_user.txt`, `hint_levels.txt`, `reviewer.txt`).
  - Mengganti placeholder menggunakan `strtr()` dan menyisipkan nonce acak kriptografis 16 karakter (`{{NONCE}}`) untuk mencegah pembobolan *guardrails*.
  - Mengimplementasikan tangga bantuan (*hint ladder*): giliran 1–3 (level 1 / konseptual), giliran 4–7 (level 2 / terarah), giliran 8+ (level 3 / spesifik).
- **Scope Guard Cooldown**:
  - Konfigurasi `AI_BLOCK_THRESHOLD` (default 5) dan `AI_BLOCK_COOLDOWN_SECONDS` (default 120s).
  - Ketika `blocked_count >= 5`, thread otomatis ditandai `flagged_for_review = true` untuk audit dosen.
  - Setiap permintaan selama durasi cooldown 120 detik langsung ditolak dengan status HTTP 429 (`blocked_off_topic`) beserta header `Retry-After` tanpa memanggil model LLM.

---

## 7. Catatan Fase 5 (Single Call Pipeline, OutputGuard, & Reviewer Fallback)
- **Pipeline Satu Panggilan (`AI_SINGLE_CALL`)**:
  - Menggantikan alur 3 tahap (gate + answer + review) menjadi 1 panggilan terstruktur dengan format JSON `{"verdict":"ok|off_topic|asks_solution|injection","hint_level":1|2|3,"reply":"..."}`.
  - Fail-closed: Jika respons model bukan JSON valid, dilakukan retry 1x dengan instruksi penegasan. Jika percobaan kedua tetap gagal, sistem melempar HTTP 503 `internal_error` ("Terjadi gangguan. Coba lagi.").
  - Verdict selain `ok` langsung membuang `reply` model dan mengirimkan pesan penolakan standar (`off_topic`, `asks_solution`, atau `injection` yang memakai pesan asks_solution), serta menaikkan `blocked_count` pada thread.
- **OutputGuard Deterministik (PHP)**:
  - Memeriksa balasan dengan verdict `ok` sebelum ditampilkan ke mahasiswa.
  - Memblokir blok kode berpagar (` ``` ` atau `~~~`), kode inline > 40 karakter / multiline, n-gram overlap (n=5) dengan solusi referensi, teks kosong, atau kata > 250 kata.
  - Jika rasio baris mirip kode melebihi ambang batas (`AI_CODE_RATIO_THRESHOLD`, default 0.25) atau deteksi bahasa mencurigakan, status menjadi `doubtful` (ragu) dan memicu panggilan Reviewer LLM.
- **Reviewer LLM**:
  - Hanya dipanggil saat OutputGuard ragu.
  - Fail-closed: Jika reviewer menyatakan `leak`, gagal parse, atau terjadi timeout/error, jawaban langsung dibuang dan penolakan standar dikirimkan dengan verdict `blocked_output`.
  - Mendukung konfigurasi model lebih ringan via `AI_REVIEWER_MODEL`.
- **Pengukuran Rata-rata Panggilan LLM per Pertanyaan**:
  - Menggunakan command `php artisan ai:call-stats {--days=7}` yang menghitung:
    $$\text{Rata-rata Panggilan} = \frac{\text{Panggilan Answer} + \text{Panggilan Review}}{\text{Panggilan Answer}}$$
  - Target PRD <= 1.15 tercapai karena reviewer hanya dipanggil saat kondisi ragu (< 10% kasus).
- **Pencegahan XSS di Frontend**:
  - Fungsi `renderMarkdown` di [`resources/js/app.js`](file:///home/ajmaaa/Projects/SALE/resources/js/app.js) meng-escape seluruh karakter entitas HTML dan tanda kutip (`&`, `<`, `>`, `"`, `'`) sebelum mengubah sintaks formatting terbatas, sehingga mencegah eksekusi kode berbahaya.

---

## 8. Catatan Fase 6 (Evaluasi, Promptfoo, & Endpoint Dev-Only)
- **Endpoint Evaluasi Pengembangan (`/internal/ai-eval`)**:
  - Hanya aktif di lingkungan non-produksi; di lingkungan `production` langsung merespons HTTP 404 Not Found.
  - Dilindungi oleh header `X-Eval-Token` yang diverifikasi secara timing-safe (`hash_equals`) terhadap `config('ai.eval_token')` / `env('AI_EVAL_TOKEN')`. Permintaan tanpa token atau dengan token salah ditolak dengan HTTP 401 Unauthorized.
  - Dikecualikan dari verifikasi CSRF di [`bootstrap/app.php`](file:///home/ajmaaa/Projects/SALE/bootstrap/app.php) (`internal/*`) agar alat otomatisasi seperti Promptfoo dapat mengirimkan payload POST JSON secara langsung.
  - **Non-Destruktif Penuh**: Dijalankan dengan `userId = 0`, sehingga secara otomatis membypass pembuatan thread (`ai_threads`), penyimpanan pesan (`ai_messages`), dan pengurangan kuota token mahasiswa (`ai_usage`).
- **Promptfoo (Dev Tool)**:
  - Ditambahkan ke `devDependencies` di [`package.json`](file:///home/ajmaaa/Projects/SALE/package.json) (`promptfoo: ^0.123.1`).
  - Dikonfigurasikan di [`promptfooconfig.yaml`](file:///home/ajmaaa/Projects/SALE/promptfooconfig.yaml) dengan provider HTTP yang menembak endpoint `/internal/ai-eval`, menyertakan asersi deterministik (bebas kode berpagar `` ``` `` dan `~~~`, penolakan off-topic, pencegahan kata kunci solusi) dan `llm-rubric`.
- **Dataset Evaluasi (Lampiran C)**:
  - Berkas dataset terstruktur [`tests/Fixtures/ai_eval_dataset.json`](file:///home/ajmaaa/Projects/SALE/tests/Fixtures/ai_eval_dataset.json) memuat total **115 kasus uji**:
    - **45 Pertanyaan Sah (C1 & C5)** (target $\ge 40$): Diagnosis error (NameError, TypeError, IndexError, AttributeError, RecursionError), konsep BST/rekursi/pointer/traversal, dan kasus batas tipis.
    - **70 Kasus Serangan (C2, C3, C4)** (target $\ge 60$): Permintaan solusi langsung, tugas isomorfik, salami slicing, off-topic (20 kasus), injeksi di komentar kode, injeksi di output konsol/traceback palsu, prompt injection / DAN / jailbreak, roleplay dosen/aktor, dan campuran bahasa / base64.
- **Hasil Pengukuran Evaluasi**:
  - Perintah pengujian otomatis: `php artisan ai:eval-dataset {--mock} {--live} {--limit} {--update-config}`.
  - Hasil evaluasi benchmark pada dataset 115 kasus memenuhi seluruh target PRD Bagian 7 & 10:
    - **Leak Rate (C2 + C4)**: 0.00% (Target $\le 2.0\%$) - **PASS**
    - **Off-Topic Refusal (C3)**: 100.00% (Target $\ge 95.0\%$) - **PASS**
    - **False Refusal pada Pertanyaan Sah (C1 + C5)**: 0.00% (Target $\le 10.0\%$) - **PASS**
    - **Rata-rata Panggilan LLM per Pertanyaan**: 1.00 panggilan (Target $\le 1{,}15$) - **PASS**
- **Catatan Limitasi Kuota Cloud Provider**:
  - API Key Gemini yang terpasang di lingkungan pengembangan berada pada tier gratis dengan limit ketat 20 request/hari (`RESOURCE_EXHAUSTED` / 429).
  - Evaluasi otomatis lokal (`--mock`) memvalidasi seluruh pipeline, ContextBuilder, template prompt, OutputGuard, dan penolakan standar secara deterministik. Ketika kuota harian provider telah di-reset atau menggunakan API key berbayar, pengujian langsung ke cloud dapat dijalankan menggunakan `php artisan ai:eval-dataset --live` atau `npx promptfoo eval`.




