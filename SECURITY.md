# Kebijakan Keamanan SALE

## Status dukungan

Repository ini masih dalam tahap pengembangan. Hanya branch utama pada versi terbaru yang menerima perbaikan keamanan. Jangan memproses data akademik nyata sebelum seluruh blocker pada `docs/audits/2026-09-23/README.md` ditutup dan deployment produksi ditinjau ulang.

## Melaporkan kerentanan

Jangan membuka issue publik yang berisi exploit, credential, data pribadi, atau data mahasiswa. Laporkan secara privat kepada maintainer repository dengan informasi berikut:

- versi/commit yang terdampak;
- endpoint dan prasyarat reproduksi;
- dampak yang dapat diamati;
- langkah reproduksi minimal tanpa data pribadi;
- saran mitigasi bila tersedia.

Maintainer harus mengonfirmasi penerimaan, menilai tingkat risiko, menyiapkan perbaikan dan regression test, lalu mengoordinasikan pengungkapan setelah deployment aman.

## Baseline deployment

- Gunakan `APP_ENV=production`, `APP_DEBUG=false`, dan `SALE_DEMO_MODE=false`.
- Gunakan HTTPS, `SESSION_SECURE_COOKIE=true`, secret unik di luar repository, database account least-privilege, serta backup yang diuji.
- Jalankan `composer audit --locked`, `npm audit`, `php artisan test`, tes JavaScript, dan production build sebelum rilis.
- Jangan mengaktifkan persona demo, demo seeders, atau data contoh di lingkungan operasional.
- Jangan memasukkan API key, dump database, file `.env`, atau hasil scan yang mengandung secret ke Git.

