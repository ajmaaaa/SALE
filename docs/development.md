# Panduan Pengembangan SALE

## Persiapan lokal

Gunakan PHP 8.2+, Composer, Node 22 LTS, npm 10+, serta MySQL 8 atau MariaDB yang kompatibel. Node 22 adalah runtime yang didukung project meskipun build mungkin berjalan pada versi lain. Windows dapat memakai PHP dan MySQL dari XAMPP; Linux dapat memakai MySQL/MariaDB lokal atau XAMPP/LAMPP.

```bash
composer install
composer run setup
composer run dev
```

Aktifkan MySQL terlebih dahulu. `composer run setup` membuat `.env`, `APP_KEY`, database `sale`, dan database test `test_sale`, lalu menjalankan migration dan seeder. Konfigurasi bawaan cocok untuk XAMPP (`127.0.0.1:3306`, user `root`, password kosong); sesuaikan `DB_PORT`, `DB_USERNAME`, atau `DB_PASSWORD` di `.env` jika instalasi lokal berbeda. Jangan commit `.env` atau dump database lokal.

MariaDB Linux sering mengamankan `root` dengan autentikasi `unix_socket`. Jika muncul error 1698, jangan menonaktifkan perlindungan tersebut. Buat database dan user `sale_app` melalui `sudo mariadb` dengan langkah pada [README](../README.md#mariadb-linux-error-1698), masukkan kredensialnya ke `.env`, lalu ulangi `composer run setup`.

`.env.example` ditujukan untuk demo lokal dan menggunakan MySQL. Untuk menguji perilaku operasional, set `SALE_DEMO_MODE=false`; akun harus tersedia di database dan login memakai password sebenarnya. PHPUnit juga menggunakan MySQL dengan database terpisah `test_sale` agar test otomatis tidak mengubah data development.

## Peta dokumentasi

- [README](../README.md): status, setup singkat, dan batas keamanan.
- [architecture.md](architecture.md): modul, sumber data, autentikasi, dan aturan desain.
- [known-limitations.md](known-limitations.md): blocker dan acceptance criteria.
- [ai-tutor.md](ai-tutor.md): konfigurasi, kuota, evaluasi, dan biaya AI.
- [code-runner.md](code-runner.md): runner Python/web.
- [security/strix.md](security/strix.md): membuat snapshot dan menjalankan audit Strix.
- [SECURITY.md](../SECURITY.md): pelaporan kerentanan dan baseline deployment.

## Alur kerja perubahan

1. Tentukan apakah fitur memakai domain database atau masih preview sesi.
2. Tambahkan route dengan method dan middleware role yang tepat.
3. Validasi semua input dan periksa ownership objek di server.
4. Taruh aturan bisnis reusable di service; controller hanya mengorkestrasi.
5. Tambahkan migration/model bila data harus bertahan atau dipakai lintas pengguna.
6. Tambahkan feature test untuk happy path, guest, cross-role, dan IDOR/cross-owner.
7. Jalankan seluruh pemeriksaan sebelum handoff.

```bash
php artisan test
node --test tests/js/*.test.mjs tests/grade-import.test.js
npm run build
vendor/bin/pint --test
composer validate --strict --no-check-publish
composer audit --locked
npm audit
```

Tes preview tertentu menonaktifkan `EnsureRole` untuk menguji perilaku halaman secara terisolasi. Perlindungan middleware diuji terpisah dalam `AuthLoginTest` dan `SecurityModeTest`; jangan menghapus regression test path tampering ketika mengubah route.

## Menambah endpoint

Checklist minimum:

- gunakan GET hanya untuk baca;
- gunakan POST/PUT/PATCH/DELETE + CSRF untuk mutasi;
- pasang `role:*` atau middleware khusus;
- gunakan route constraint dan binding;
- verifikasi ownership setelah binding;
- rate-limit login, AI, runner, dan endpoint mahal;
- jangan menaruh credential, prompt sensitif, atau data pribadi di log;
- buat tes guest, role salah, resource milik pihak lain, input invalid, dan hasil berhasil.

## Seeder dan data demo

`DatabaseSeeder` selalu membuat lima role. Akun serta data contoh hanya dibuat ketika mode demo aktif di `local/testing`. Seeder produksi tidak boleh memperbarui password akun yang sudah ada atau menciptakan kredensial bersama.

## Gaya kode dan komentar

Gunakan Pint untuk PHP. Komentar dipakai untuk menjelaskan alasan, invariant keamanan, rumus, atau perilaku yang tidak terlihat dari kode. Jangan menambahkan komentar yang hanya mengulang nama method, nomor langkah lama, atau separator dekoratif. Dokumentasi arsitektur ditempatkan di `docs`, bukan sebagai blok komentar panjang di controller.

## Definition of done

Perubahan dianggap selesai bila implementasi, migration, validasi, otorisasi objek, tes, dokumentasi yang terdampak, formatter, build, dan audit dependency semuanya konsisten. Fitur tidak boleh diberi label siap produksi selama item P0 terkait masih terbuka.
