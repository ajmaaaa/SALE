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


