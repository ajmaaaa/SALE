# PRD: SALE AI Tutor v2 (Lean Revision)

Status: Draft untuk implementasi
Stack: Laravel 12, PHP 8.2+/8.3, MySQL/PostgreSQL, CodeMirror + Pyodide (client)
Provider LLM: satu provider aktif, dipilih lewat interface pergantian provider yang SUDAH ada di SALE (OpenAI/Gemini/DeepSeek; Claude bisa ditambahkan nanti sebagai adapter baru)

---

## 1. Latar Belakang & Masalah

Implementasi saat ini (pipeline 3 tahap: gate, answer, review) bermasalah:

1. **Limit cepat tercapai.** 1 pertanyaan mahasiswa = 3 panggilan API, sehingga RPM/TPM provider habis 3x lebih cepat.
2. **Pesan error menyesatkan.** UI menampilkan "Batas pemakaian layanan AI tercapai" padahal kuota harian mahasiswa masih sisa (contoh: 200.420 / 250.000). Error 429 dari provider dan kuota internal tidak dibedakan.
3. **Konteks tidak relevan.** "RAG" saat ini hanya mengambil 5 materi pertama (`orderBy('id')->limit(5)`), bukan retrieval berdasarkan pertanyaan.
4. **History belum jelas sumbernya.** Risiko history dikirim dari client dan bisa dipalsukan.
5. **Output konsol tidak dikirim.** Pertanyaan "kode saya error kenapa" tidak bisa dijawab baik tanpa traceback.
6. **Scope guard lemah.** Mahasiswa bisa memakai tutor untuk hal di luar tugas.

## 2. Tujuan (Goals)

| ID | Tujuan | Metrik keberhasilan |
|----|--------|---------------------|
| G1 | 1 thread per (mahasiswa, assessment), disimpan di server | Tidak ada history dari client; thread unik per pasangan |
| G2 | Kurangi panggilan API per pertanyaan dari 3 menjadi 1 (kasus normal) | Rata-rata panggilan/pertanyaan <= 1,15 |
| G3 | Error limit jelas dan tidak salah label | 0 kasus "kuota habis" saat kuota sisa |
| G4 | Tutor paham konteks tugas dan menolak topik di luar tugas | Off-topic refusal rate >= 95% pada dataset uji |
| G5 | Tidak membocorkan solusi tugas resmi | Leak rate <= 2% pada dataset serangan |
| G6 | Ringan diimplementasikan | Tanpa vector DB, tanpa embedding, tanpa framework agent |
| G7 | Penanganan limit provider yang rapi | Retry terbatas + pesan jelas; tanpa fallback antar provider |

## 3. Non-Goals (sengaja TIDAK dikerjakan)

- Vector DB, embedding, atau RAG semantik.
- Summarization history otomatis (cukup batasi N pesan terakhir).
- Autograder server-side atau perubahan gradebook.
- Perubahan modul `ai:benchmark` (tetap read-only dan terpisah).
- Framework agent (LangChain/LangGraph) atau tool-calling otonom.
- Penilaian otomatis oleh AI untuk nilai resmi.
- Interface multi-provider baru, fallback antar provider, atau circuit breaker (SALE sudah punya pergantian provider; admin memilih satu provider aktif).

## 4. Prinsip Desain

1. **Server adalah sumber kebenaran.** `assessment_id`, class, history, dan konteks tugas dibangun di server dari data DB dan enrollment aktif. Client hanya mengirim: `assessment_id`, `question`, `code`, `console_output`, `selected_line` (opsional).
2. **Semua input pengguna adalah data, bukan perintah.** Kode, pertanyaan, output konsol, dan materi dibungkus delimiter dan diperlakukan sebagai data tak tepercaya.
3. **Fail-closed.** Parse gagal, timeout, atau output kosong berarti pesan "coba lagi", bukan jawaban yang lolos.
4. **Murah dulu.** 1 panggilan LLM + pemeriksaan deterministik di PHP. LLM reviewer hanya fallback saat pemeriksaan deterministik ragu.
5. **Pakai yang sudah ada.** Panggilan LLM lewat interface/adapter provider yang sudah ada. Tidak membuat abstraksi baru. Satu provider aktif dalam satu waktu.

## 5. Arsitektur Ringkas

```
Client (Coding Room)
  -> POST /ai/tutor  {assessment_id, question, code, console_output, selected_line?}

Server:
  1. AuthZ: user terdaftar aktif di class dari assessment_id (policy)
  2. Lock per user (TTL panjang, diperpanjang, dilepas di finally)
  3. Cek kuota harian + batas turn thread -> reserve token
  4. ContextBuilder: task + steps + materi tertaut + console + code
  5. ThreadStore: muat N pesan terakhir dari DB
  6. LLM call (1x) via adapter provider aktif (yang sudah ada) -> JSON terstruktur
  7. OutputGuard (PHP deterministik): cek bocor kode / format / panjang
       - lolos -> simpan + kirim
       - ragu  -> panggil reviewer LLM (jarang)
       - gagal -> pesan penolakan standar
  8. Commit token aktual / refund jika gagal sebelum ditagih
  9. Log ke ai_api_calls
```

## 6. Kebutuhan Fungsional

### 6.1 Thread per (user, assessment)

- Tabel `ai_threads`: `id, user_id, assessment_id, class_section_id, turns, blocked_count, tokens_used, last_provider, created_at, updated_at`, `UNIQUE(user_id, assessment_id)`.
- Tabel `ai_messages`: `id, thread_id, role (user|assistant), content, verdict (ok|off_topic|asks_solution|injection|blocked_output|error), tokens_in, tokens_out, created_at`.
- `assessment` boleh bertipe tugas ATAU materi: satu thread per materi dan satu thread per tugas.
- Muat maksimal `AI_HISTORY_MESSAGES` (default 8) pesan terakhir; potong tiap pesan maks 1.500 karakter saat dimasukkan ke prompt.
- Pesan yang ditolak tetap disimpan (dengan `verdict`) dan dihitung di `blocked_count`.
- Endpoint `GET /ai/tutor/{assessment}/thread` untuk memuat ulang chat saat halaman dibuka.
- Retensi: hapus thread N hari setelah semester berakhir (`AI_THREAD_RETENTION_DAYS`, default 180). Mahasiswa dapat melihat dan menghapus thread miliknya sendiri.

### 6.2 Konteks tugas (menggantikan "5 materi pertama")

ContextBuilder menyusun `task_context` dari server:

- Judul, deskripsi, tipe assessment.
- `coding_steps` (nomor, judul, target CPMK, instruksi).
- **Materi tertaut:** `learning_payload.linked_material_ids` (diisi dosen; jika kosong, gunakan fallback).
  - Materi tertaut: strip HTML, maksimal 1.500 karakter per materi.
  - Fallback: judul + ringkasan 120 karakter dari maksimal 30 materi terbit di kelas yang sama (supaya AI tahu cakupan kuliah).
  - Batas total konteks materi: 6.000 karakter.
- **TIDAK BOLEH** masuk konteks: kunci jawaban, solusi referensi, test case tersembunyi, rubrik rahasia. Tambahkan test otomatis yang gagal jika field tersebut muncul di payload prompt.
- Dari client: `code` (maks 8.000 karakter), `console_output` (maks 2.000 karakter, ambil bagian akhir/traceback), `selected_line` untuk fitur "Tanyakan Baris".

### 6.3 Pipeline satu panggilan (structured output)

LLM mengembalikan JSON:

```json
{
  "verdict": "ok | off_topic | asks_solution | injection",
  "hint_level": 1,
  "reply": "teks balasan Bahasa Indonesia"
}
```

Aturan server:
- `verdict != ok` -> abaikan `reply` dari model, kirim pesan penolakan standar sesuai verdict (pesan berbeda untuk off_topic vs asks_solution agar tidak membingungkan).
- Validasi JSON di PHP untuk SEMUA provider (jangan percaya jaminan schema provider). Parse gagal -> retry 1x -> gagal lagi -> error "coba lagi" (fail-closed).
- Output token dibatasi (`AI_MAX_OUTPUT_TOKENS`, default 500), target < 200 kata.

### 6.4 OutputGuard (deterministik, PHP)

Jawaban DITOLAK atau diteruskan ke reviewer jika:
- Mengandung blok kode berpagar (``` atau ~~~) -> tolak.
- Rasio baris "terlihat seperti kode" (indentasi, `;`, `{}`, `def `, `class `, `import `, `=` assignment) > ambang -> ragu, kirim ke reviewer.
- Kode inline diperbolehkan hanya untuk identifier/ekspresi pendek (<= 40 karakter, 1 baris), misalnya nama fungsi atau pesan error.
- N-gram overlap (n=5 token) dengan solusi referensi (jika ada di sisi server) di atas ambang -> tolak.
- Balasan kosong, > batas kata, atau bukan Bahasa Indonesia -> tolak/retry.

Reviewer LLM (model kecil, panggilan terpisah) hanya dipanggil pada kasus "ragu". Reviewer juga fail-closed.

### 6.5 Hint ladder

`hint_level` ditentukan server (bukan model) berdasarkan jumlah turn user di thread:

| Level | Kapan | Boleh |
|-------|-------|-------|
| 1 | turn 1-3 | Jelaskan konsep, ajukan pertanyaan pemandu |
| 2 | turn 4-7 | Arahkan ke bagian/tahap yang bermasalah, jelaskan arti error |
| 3 | turn 8+ | Tunjuk fungsi/tahap yang perlu diperiksa dan beri analogi |

Di SEMUA level: dilarang menulis kode solusi tugas, dilarang menyebut baris pasti beserta isi perbaikannya, dilarang memberi potongan solusi.

### 6.6 Scope guard

- Model hanya menjawab jika pertanyaan berkaitan dengan tugas/materi ini atau konsep prasyarat untuk mengerjakannya.
- Di luar itu: `verdict = off_topic` dan server menolak dengan sopan.
- Konsep umum yang relevan (misal "apa itu rekursi?" pada tugas BST) harus tetap dijawab: batas topik adalah "mendukung tugas ini", bukan "disebut persis di soal".
- Setelah `blocked_count >= AI_BLOCK_THRESHOLD` (default 5) pada thread, terapkan cooldown pendek (misal 2 menit) dan tandai thread untuk ditinjau dosen.

### 6.7 Provider & penanganan limit

- Tidak ada interface atau fallback baru. Pakai interface pergantian provider yang sudah ada; satu provider aktif sesuai config (`AI_PROVIDER`, model via env, **jangan hardcode nama model**).
- Persyaratan yang harus dipenuhi adapter yang ada (audit di Fase 2, ubah seminimal mungkin):
  1. Mengembalikan teks balasan + **usage token aktual** (input/output/cache).
  2. Mendukung permintaan keluaran JSON (mode JSON/structured output sesuai kemampuan provider).
  3. Mengembalikan error yang bisa dibedakan: rate limit (429, termasuk `Retry-After` jika ada), server error (5xx), timeout, dan permintaan ditolak/safety filter.
- Retry: maksimal 2x untuk 429/5xx/timeout dengan exponential backoff + jitter, menghormati `Retry-After`. Setelah itu kembalikan `provider_busy`.
- Rate limiter ringan untuk provider aktif (`RateLimiter` Laravel, `AI_RPM`) agar permintaan serentak satu kelas tidak menembus RPM provider. Opsional; aktifkan jika log menunjukkan 429 berulang.
- Menambah Claude kemudian: buat satu adapter baru di interface yang sama, tanpa perubahan pada pipeline, thread, atau guard.
- Catat provider/model yang dipakai di `ai_api_calls`.

### 6.8 Taksonomi error dan pesan UI

| Kode | Penyebab | Pesan ke mahasiswa |
|------|----------|--------------------|
| `provider_busy` | 429/5xx dari provider setelah retry | "AI sedang sibuk. Coba lagi dalam beberapa detik." |
| `quota_daily` | kuota harian internal habis | "Kuota AI harianmu habis. Reset pukul 07.00 WIB." |
| `turn_limit` | batas turn thread | "Batas percakapan untuk tugas ini tercapai. Coba mandiri dulu atau tanya dosen." |
| `blocked_asks_solution` | gate/guard | "Saya tidak bisa memberi jawaban tugas, tapi saya bisa bantu memahami konsepnya." |
| `blocked_off_topic` | scope guard | "Pertanyaan ini di luar tugas ini. Coba tanyakan hal yang terkait tugas." |
| `locked` | request paralel | "Permintaan sebelumnya masih diproses." |
| `internal_error` | parse gagal dll. | "Terjadi gangguan. Coba lagi." |

Status HTTP dan kode error harus konsisten antara backend dan frontend.

### 6.9 Kuota, lock, dan pembatalan

- Reservasi token sebelum panggilan (estimasi input + `max_output`). Setelah respons, commit token aktual dari usage provider; selisih dikembalikan.
- **Refund hanya jika panggilan gagal sebelum provider menagih** (error jaringan, 429 sebelum diproses). Jika request sudah dikirim dan berjalan lalu dibatalkan mahasiswa, token dihitung sebagian (estimasi), bukan refund penuh. Ini mencegah spam kirim-lalu-cancel.
- Lock per user: TTL > total timeout (timeout total request <= 60 detik, TTL lock 90 detik), selalu dilepas di `finally`. Wajib driver cache atomik (Redis/Database), bukan `file`.
- Cap harian tambahan per kelas (opsional via config) dan budget global dengan peringatan.

### 6.10 Keamanan

- Policy: user harus terdaftar aktif (`enrolled`, bukan `kicked/dropped`) di class dari assessment; class archived = read-only (tidak bisa kirim pesan baru).
- Tidak menerima `class_section_id` atau `history` dari client.
- Prompt injection: bungkus semua input tak tepercaya dengan delimiter ber-nonce acak per request; system prompt menyatakan isi delimiter adalah data.
- Render balasan AI sebagai teks aman (escape; markdown terbatas tanpa HTML mentah) untuk mencegah XSS.
- Privasi: lihat 9. Risiko & Kepatuhan.

### 6.11 Observabilitas

Tabel `ai_api_calls`: `provider, model, stage (answer|review), status, error_code, latency_ms, tokens_in, tokens_cached, tokens_out, cost_usd_estimate, thread_id, created_at`. Tanpa isi pesan. Dashboard dosen/admin sederhana: jumlah penolakan per tugas, rasio error per provider.

## 7. Evaluasi & Pengujian

- **Unit/feature test (Pest/PHPUnit):** policy enrollment, thread unik, history dari DB, pembatasan turn, ContextBuilder tidak memuat field terlarang, OutputGuard (blok kode, n-gram), taksonomi error, refund vs commit token, retry/backoff dan pemetaan error provider (fake HTTP).
- **Evaluasi perilaku LLM dengan Promptfoo (dev-only, bukan runtime):**
  - Endpoint/artisan khusus eval yang menjalankan pipeline tanpa menyimpan data.
  - Dataset minimal 40 pertanyaan sah dan 60 serangan (kategori di PROMPTS lampiran C).
  - Jalankan sebelum mengganti model/prompt. Pin versi model di config.
  - Target: leak rate <= 2%, false refusal pada pertanyaan sah <= 10%, off-topic refusal >= 95%.

## 8. Dependensi

| Kebutuhan | Pilihan | Catatan |
|-----------|---------|---------|
| Klien LLM | **Tidak ada paket baru.** Pakai adapter provider yang sudah ada di SALE | Hanya sesuaikan jika perlu usage token, mode JSON, atau pembeda error (lihat 6.7). |
| Evaluasi/red team | Promptfoo (`npx promptfoo@latest`) | Dev tool, bukan runtime. Lisensi MIT. |
| Claude (opsional, nanti) | Adapter baru di interface yang ada | Baca dokumentasi API Anthropic untuk format permintaan dan usage; jangan berasumsi. |

Agent boleh `npm i -D` untuk Promptfoo dan membaca dokumentasi. `git clone` hanya jika perlu membaca contoh; jangan menyalin kode repo luar tanpa cek lisensi.

## 9. Risiko & Kepatuhan

- **Privasi/UU PDP:** kode dan pertanyaan mahasiswa dikirim ke pihak ketiga lintas negara. Wajib: persetujuan eksplisit di UI, retensi chat terbatas, jangan kirim nama/NIM (strip pola NIM/email dari `code` sebelum dikirim jika memungkinkan).
- **Free tier Gemini** dapat memakai data untuk pelatihan; gunakan tier berbayar atau Vertex untuk data mahasiswa.
- **Pilihan provider:** pertimbangkan lokasi server dan kebijakan data provider yang dipilih (DeepSeek berada di luar yurisdiksi Indonesia). Keputusan ada di pemilik proyek/institusi.
- **Model drift:** pin versi model dan jalankan eval sebelum upgrade.
- **Debug langsung = solusi:** mitigasi lewat hint ladder dan larangan menyebut perbaikan baris pasti.
- **Pengelabuan di luar room** (ChatGPT di tab lain) tidak bisa dicegah oleh fitur ini; kontrol berbasis proses (version history, similarity, viva) di luar cakupan dokumen ini.

## 10. Fase Implementasi

| Fase | Isi | Hasil |
|------|-----|-------|
| 0 | Reproduksi bug: periksa log/`ai_api_calls` untuk 429 dan akar pesan "batas tercapai" | Akar masalah terkonfirmasi |
| 1 | Taksonomi error, retry+Retry-After, (opsional) rate limiter, refund/commit token, perbaikan lock | Pesan benar, kuota tidak bocor |
| 2 | Audit adapter provider yang ada: usage token, mode JSON, pembeda error (ubah minimal) | Adapter siap untuk 1 panggilan |
| 3 | `ai_threads`/`ai_messages` + policy + history dari DB | Session per tugas |
| 4 | ContextBuilder (tugas, materi tertaut, console) + scope guard + hint ladder | Tutor paham konteks |
| 5 | Pipeline satu panggilan + OutputGuard + reviewer fallback | Biaya dan latensi turun |
| 6 | Test otomatis + dataset Promptfoo + laporan eval | Kualitas terukur |

Tiap fase harus bisa di-merge dan di-rollback sendiri (feature flag `AI_TUTOR_V2`).

## 11. Kriteria Penerimaan

- [ ] Satu thread per (user, assessment); refresh halaman memuat ulang chat.
- [ ] Tidak ada history/class_section_id dari client yang dipercaya.
- [ ] Rata-rata panggilan LLM per pertanyaan <= 1,15 pada uji beban kelas simulasi.
- [ ] 429 provider tidak pernah tampil sebagai "kuota harian habis".
- [ ] Error 429/5xx provider ditangani dengan retry terbatas lalu pesan `provider_busy` yang jelas.
- [ ] Traceback konsol masuk konteks; tutor menjelaskan makna error tanpa memberi perbaikan.
- [ ] Hasil Promptfoo memenuhi target leak dan false-refusal.
- [ ] Semua test hijau; feature flag bisa dimatikan.
