# Strix + Gemini untuk SALE

Strix dipasang terpisah di `.security/strix/venv` (versi 1.6.2). Direktori
`.security/` diabaikan Git. Ini tidak mengganti dependency Laravel/npm.

## Isi API key

Edit `.security/strix/config.json`, isi `gemini_api_key` dengan key Google AI Studio.
File sudah disiapkan dengan izin baca/tulis pemilik saja. Jangan isi key di file
contoh dalam `docs/`, karena file contoh ikut repository.

Model default `gemini/gemini-3.8-flash` mengikuti contoh provider LiteLLM saat setup.
Ubah `model` bila akun Google menyediakan model lain; tetap gunakan prefix `gemini/`.
Ketersediaan model, quota, dan kredensial baru dapat dibuktikan setelah key diisi.

## Jalankan dari root SALE

```bash
python3 scripts/security-scan.py check
python3 scripts/security-scan.py scan --budget 2
```

`check` tidak memanggil Gemini. `scan` memulai permintaan berbayar sesuai akun Google;
`--budget` adalah batas estimasi USD Strix, bukan hard cap tagihan Google. Request
yang sedang berjalan dan ketidakakuratan perhitungan harga dapat melampaui batas.
Mode awal `quick`, batas 60 putaran per agent. Hasil quick scan bukan audit menyeluruh.

Scan selalu membuat snapshot baru dari file source tracked dan file source baru yang
belum di-track tetapi tidak diabaikan Git. Perubahan yang belum commit ikut. `.env` asli, storage, database,
Git history, API key, dan dependency host tidak disalin. Strix bekerja pada snapshot,
karena target direktori Strix dapat ditulis oleh agent. Source yang disalin dapat
dikirim ke Gemini sebagai konteks analisis. Runtime aplikasi dan data sintetis, jika
diperlukan, dibuat oleh Strix di sandbox; keberhasilan bootstrap belum dijamin.

Snapshot ada di `.security/strix/targets/`; hasil ada di `.security/strix/strix_runs/`.
Untuk menyiapkan snapshot tanpa API: `python3 scripts/security-scan.py prepare`.
Exit code scan 0 berarti selesai tanpa temuan, 2 berarti ada temuan, 1 berarti error.
Periksa juga status laporan: scan yang berhenti karena budget tidak berarti bersih.

## Instalasi ulang pada mesin lain

```bash
python3 -m venv .security/strix/venv
.security/strix/venv/bin/python -m pip install 'strix-agent==1.6.2'
cp docs/security/strix-config.example.json .security/strix/config.json
chmod 600 .security/strix/config.json
```

Python minimal 3.12 dan Docker daemon aktif diperlukan. Image sandbox diunduh oleh
Strix bila belum tersedia. Instalasi pin versi Strix; dependency transitif mengikuti
resolver pip. Jangan memberi target root SALE secara langsung bila ingin menjaga
source asli tetap utuh.

Referensi: [Strix CLI](https://docs.strix.ai/usage/cli),
[konfigurasi Strix](https://docs.strix.ai/advanced/configuration),
[Gemini melalui LiteLLM](https://docs.litellm.ai/docs/providers/gemini).

Setup tidak otomatis menjalankan scan. API key masih harus diisi oleh pemilik akun.

## Ollama lokal (eksperimental)

Percobaan aktual memakai `qwen3:8b` pada laptop RAM 16 GB tidak menghasilkan audit
yang valid. Prompt awal Strix 1.6.2 terukur 55.359 token dan dipotong menjadi 8.194
token oleh Ollama. Jangan menganggap keluaran 0 temuan sebagai kondisi aman bila log
memuat `truncating input prompt` atau coverage tetap nol. Bukti lengkap tersedia di
[`docs/audits/2026-09-23/strix-local-ollama.md`](../audits/2026-09-23/strix-local-ollama.md).

Launcher `scripts/security-scan.py` tetap dikunci ke Gemini agar konfigurasi lokal
yang belum valid tidak dijalankan tanpa sadar sebagai audit resmi. Integrasi Ollama
baru boleh dijadikan jalur baku setelah prompt tidak terpotong, structured tool call
terbukti pada scan sebenarnya, dan sandbox memakai jaringan terisolasi.

## Verifikasi setup 20 September 2026

- CLI `strix --version`: 1.6.2; `--help` dan `pip check` berhasil.
- Docker daemon 29.8.0; image `ghcr.io/usestrix/strix-sandbox:1.3.0` sudah diunduh.
- Digest image: `sha256:f6906c3114e504fd1a218fcf028d7a0e46851118403a438b63956de6ea7c4331`.
- Container berhasil menjalankan Python 3.13.14 tanpa jaringan/mount project.
  PHP, Composer, Node dan npm tidak ditemukan pada PATH image bawaan. Analisis source
  bisa dilakukan; pengujian Laravel dinamis perlu menyiapkan runtime tersebut di
  sandbox. Pemeriksaan runtime gabungan keluar 127 akibat tool yang tidak ditemukan.
- Snapshot 226 file terverifikasi tidak berisi .env asli, .git, storage atau .security.
- Key kosong menghentikan launcher sebelum memanggil Strix. Belum ada panggilan Gemini.
