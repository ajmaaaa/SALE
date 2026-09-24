# Uji Strix lokal dengan Ollama — 23 September 2026

## Kesimpulan

Uji integrasi berhasil membuktikan Ollama, tool calling, Strix 1.6.2, dan sandbox
Docker dapat dihubungkan secara lokal dengan biaya provider USD 0. Namun laptop
ini belum mampu menjalankan audit Strix yang valid memakai `qwen3:8b`.

Run utama: `20260923t100154354824z_1e48`.

Status akhir: `interrupted`, 0 request selesai, 0 token tercatat Strix, 0 surface
direview, dan 0 temuan. Angka 0 temuan tidak boleh ditafsirkan sebagai hasil audit.

## Konfigurasi yang diuji

- Model dasar: `qwen3:8b`, Q4, sekitar 5,2 GB.
- Varian: `qwen3-strix`, `num_ctx=16384`, `temperature=0.2`,
  `num_predict=4096`.
- Inference: CPU Intel i5-1145G7; tidak ada GPU diskret.
- RAM: 15 GiB dan swap 4 GiB.
- Strix: 1.6.2, quick/full scope, maksimal 12 putaran.
- Target: snapshot 234 file di `.security/strix/targets/`; bukan source asli atau
  URL produksi.
- Telemetry dimatikan dan thinking Qwen3 dipaksa `false` pada request lokal.
- Sandbox dibatasi 4 GB RAM, 2 CPU, dan 256 PID.

Tool-call smoke test melalui LiteLLM berhasil mengembalikan native structured
`tool_calls`. Biaya provider tetap USD 0 karena endpoint adalah Ollama lokal.

## Blocker alat audit

### B1 — Docker bridge tidak didukung host

Docker gagal membuat pasangan interface `veth` untuk network bridge dengan pesan
`operation not supported`. Container Strix dapat dimulai memakai network `host`
setelah resolver endpoint diarahkan ke `127.0.0.1`. Ini hanya workaround lokal;
network host memperlebar akses container dan tidak menjadi konfigurasi baku untuk
audit instansi. Perbaiki dukungan bridge/veth pada host atau jalankan Strix pada
mesin/VM Docker yang mendukung isolasi jaringan normal.

### B2 — Prompt Strix melampaui context praktis model/laptop

Strix membuat system prompt 128.712 karakter dengan 41 tool. Log Ollama mencatat
55.359 token input lalu memotongnya menjadi 8.194 token meskipun model disetel
16.384 context. Tool schema atau instruksi penting dapat hilang, sehingga hasil
scan tidak reliabel. Saat itu RAM mencapai sekitar 13/15 GiB dan swap 2,2/4 GiB.
Menaikkan context pada laptop ini berisiko OOM dan tetap belum cukup untuk prompt
55 ribu token.

Syarat selesai:

- tidak ada pesan `truncating input prompt` di log Ollama;
- seluruh prompt dan ruang output muat dalam context model;
- request pertama menghasilkan structured tool call yang dijalankan Strix;
- coverage mencatat surface yang direview dan run berstatus `completed`;
- sandbox kembali memakai network terisolasi, bukan `host`.

## Keputusan

Jangan memakai hasil run lokal ini sebagai bukti keamanan atau pengganti regression
test. Pilihan realistis adalah menjalankan Strix pada mesin yang memiliki RAM/VRAM
lebih besar dan model dengan context memadai, atau memakai provider yang kompatibel
setelah persetujuan biaya serta kebijakan data instansi. Pemeriksaan test suite,
analisis route/policy, dependency audit, dan review manual tetap menjadi bukti audit
SALE yang berlaku saat ini.

Artefak lokal berada di `.security/strix/strix_runs/20260923t100154354824z_1e48/`
dan sengaja diabaikan Git karena dapat berisi salinan/konteks source.
