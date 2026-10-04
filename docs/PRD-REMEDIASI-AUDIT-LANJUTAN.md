# Product Requirements Document (PRD)
## Remediasi Audit Lanjutan SALE

**Versi:** 1.0
**Tanggal:** 4 Oktober 2026
**Status:** Siap diimplementasikan
**Acuan:** `docs/audits/2026-10-04/README.md`

## 1. Tujuan

Menutup 11 kelompok masalah Medium dan kualitas yang tersisa setelah perbaikan 10 temuan Critical/High, tanpa mengubah desain visual atau perilaku bisnis yang tidak berkaitan.

Hasil akhir yang diwajibkan:

1. Operasi backup, restore, notifikasi, attachment, dan evaluasi AI aman secara default.
2. Polling dan halaman pembelajaran tidak menjalankan query atau write yang tidak diperlukan.
3. Pipeline pengujian dapat digunakan sebagai release gate yang konsisten.
4. Seluruh perubahan memiliki tes regresi positif dan negatif.

## 2. Batasan Perubahan

### Dalam cakupan

- Temuan M-01 sampai M-09 serta L-01 sampai L-04, dikelompokkan menjadi 11 workstream.
- Controller, service, route, migration, job, command, policy, dan tes yang langsung dibutuhkan.
- Perubahan kecil pada JavaScript hanya jika diperlukan untuk mengganti request GET menjadi POST atau mengurangi polling.

### Di luar cakupan

- Redesign halaman, perubahan warna, layout, komponen, atau navigasi.
- Penambahan fitur akademik baru.
- Perubahan rumus OBE, nilai, CPMK, CPL, dan bobot asesmen.
- Penggantian provider AI.
- Restore terhadap database development/production saat implementasi.
- Penghapusan data/file lama tanpa laporan dry-run dan persetujuan operator.

## 3. Urutan Implementasi

| Fase | Workstream | Prioritas |
| --- | --- | --- |
| 1 | Backup/restore aman; pembatasan direktori backup; endpoint AI eval | P0 |
| 2 | Mutasi notifikasi; lifecycle attachment; side effect GET | P0/P1 |
| 3 | Optimasi live-status dan N+1 query | P1 |
| 4 | CSP bertahap | P1 |
| 5 | Pemecahan source, penghapusan kode mati, formatter, dan portability test | P1/P2 |

Fase berikutnya hanya boleh dimulai setelah tes regresi fase sebelumnya lulus.

## 4. Persyaratan per Workstream

### WS-01 — Backup dan restore memberikan hasil yang benar

**Masalah:** proses shell dapat gagal tetapi UI tetap menyatakan berhasil; password database muncul pada argumen proses.

**Wajib diperbaiki:**

- Jalankan dump/restore melalui `Symfony Process`, bukan string `exec()`.
- Credential diberikan melalui option file sementara berizin `0600` atau mekanisme environment yang didukung client; tidak boleh menjadi argumen CLI.
- Periksa exit code, timeout, dan stderr yang telah disanitasi.
- Upload restore hanya menerima `.sql`, MIME yang diizinkan, ukuran maksimum terkonfigurasi, dan file privat.
- Lakukan preflight: file terbaca, signature/struktur SQL masuk akal, koneksi DB tersedia, ruang disk cukup.
- Buat backup otomatis sebelum restore.
- Status sukses hanya diberikan setelah proses selesai dengan exit code `0` dan pemeriksaan pascarestore lulus.
- Catat actor, waktu, checksum, ukuran, hasil, dan error code tanpa credential.

**Validasi wajib:**

- Simulasikan binary tidak tersedia, password salah, file rusak, timeout, disk penuh, serta restore berhasil pada database disposable.
- Pastikan password tidak muncul pada process list, log, response, atau activity log.
- Pastikan database asli tidak disentuh oleh tes.

### WS-02 — Direktori backup dibatasi ke satu root aman

**Masalah:** admin dapat memasukkan hampir semua absolute path.

**Wajib diperbaiki:**

- Tetapkan satu root dari environment, misalnya `SALE_BACKUP_ROOT`, di luar `public/`.
- UI hanya boleh menyimpan subdirektori relatif, bukan absolute path.
- Canonicalize parent dengan `realpath()` dan lakukan boundary-aware comparison.
- Tolak `..`, symlink escape, null byte, path public, dan target di luar root.
- Direktori wajib `0700`; file backup wajib `0600`.
- Download dan delete hanya menerima nama file hasil allowlist, tidak menerima path.

**Validasi wajib:** path traversal, symlink escape, prefix collision (`/backup-safe-evil`), path public, nama valid, download, dan delete.

### WS-03 — `/live-status` menghitung notifikasi satu kali

**Masalah:** satu request dapat memanggil `forUser()` berulang melalui `unreadCount()`.

**Wajib diperbaiki:**

- Ambil koleksi notifikasi tepat satu kali per user/request.
- Hitung daftar dan jumlah unread dari koleksi yang sama.
- Jangan mengubah kontrak JSON frontend.
- Tambahkan cache singkat hanya bila pengukuran masih menunjukkan beban tinggi; cache wajib diinvalidasi saat event notifikasi berubah.
- Reverb dapat digunakan untuk mendorong perubahan, tetapi polling fallback harus tetap tersedia.

**Target:** jumlah query `/live-status` tidak bertambah seiring pemanggilan helper unread dan p95 membaik dibanding baseline.

**Validasi wajib:** query-count assertion untuk setiap role, response JSON lama vs baru, 100–500 virtual user dengan data sintetis, dan invalidasi unread.

### WS-04 — Query database dikeluarkan dari Blade

**Masalah:** view course, item, assignment coding, dan course card menjalankan query sehingga terjadi N+1.

**Wajib diperbaiki:**

- Semua query dipindahkan ke controller/query service.
- Gunakan eager loading, `withCount`, agregasi, dan batch query.
- Blade hanya menerima DTO/view model dan tidak boleh memanggil `Model::query()`, relationship query, atau `DB`.
- HTML dan perilaku tampilan harus tetap sama.

**Validasi wajib:** snapshot elemen penting, query-count budget untuk 1/10/50 course atau assessment, serta uji dosen dan mahasiswa.

### WS-05 — GET course tidak boleh menulis data

**Masalah:** pembentukan response course dapat melakukan unpin/update.

**Wajib diperbaiki:**

- Hapus seluruh update dari `LearningPreview::databaseCourse()` dan jalur baca terkait.
- Enforce satu pinned item pada command/service khusus saat aksi pin/unpin.
- Operasi pin berjalan dalam transaksi dan memeriksa otorisasi pengampu.
- Dua request GET identik tidak boleh mengubah database.

**Validasi wajib:** rekam state sebelum/sesudah GET, concurrent pin test, kelas lain, dosen bukan pengampu, dan kelas arsip.

### WS-06 — Lifecycle attachment konsisten

**Masalah:** row dapat terhapus tanpa file fisik; upload gagal dapat meninggalkan orphan.

**Wajib diperbaiki:**

- Tambahkan status attachment `pending`, `attached`, atau `failed/orphan` bila dibutuhkan.
- Upload dibuat `pending`, lalu dihubungkan setelah transaksi domain berhasil.
- Finalisasi file dijalankan `afterCommit`.
- Penghapusan assessment/submission menjadwalkan delete file setelah commit, bukan sebelumnya.
- Buat command scanner dengan mode dry-run dan job cleanup berdasarkan retensi.
- Attachment tanpa relasi domain tidak boleh otomatis dihapus sebelum melewati grace period.

**Validasi wajib:** rollback setelah upload, delete assessment, delete submission, shared-reference protection, file hilang, row hilang, dry-run, dan idempotensi cleanup.

### WS-07 — Content Security Policy efektif

**Masalah:** CSP belum memiliki `default-src`, `script-src`, `style-src`, dan `connect-src`.

**Wajib diperbaiki:**

- Inventaris seluruh inline script/style, Vite asset, Reverb, blob worker, Pyodide, video, dan preview file.
- Mulai dengan `Content-Security-Policy-Report-Only` pada staging.
- Migrasikan inline script ke asset atau gunakan nonce/hash per response.
- Terapkan allowlist minimum untuk `default-src`, `script-src`, `style-src`, `img-src`, `font-src`, `connect-src`, `worker-src`, `media-src`, dan `frame-src`.
- Jangan memakai `unsafe-eval`; `unsafe-inline` hanya sementara dengan tiket penghapusan.
- Pertahankan kebutuhan PDF preview dan code runner secara eksplisit.

**Validasi wajib:** login, seluruh dashboard role, Reverb, editor coding, Pyodide, PDF/image/video preview, console browser tanpa violation tak terduga, serta percobaan inline injection yang harus diblokir.

### WS-08 — Mutasi notifikasi hanya melalui POST/PATCH

**Masalah:** endpoint mark-read menerima GET.

**Wajib diperbaiki:**

- Route mark-read hanya menerima POST atau PATCH dengan CSRF.
- Link GET hanya melakukan navigasi; perubahan status dikirim lewat form/fetch terpisah.
- Request dapat diulang tanpa menghasilkan state yang salah.
- Redirect target tetap melalui validasi URL internal.

**Validasi wajib:** GET menghasilkan 405 dan tidak mengubah state; POST valid berhasil; tanpa CSRF ditolak; user lain tidak dapat mengubah notifikasi; target eksternal ditolak.

### WS-09 — Endpoint evaluasi AI fail-closed

**Masalah:** `/internal/ai-eval` memakai token contoh, tidak memiliki rate limit, dan dapat aktif di non-production yang terjangkau jaringan.

**Keputusan utama:** rekomendasi pertama adalah memindahkan evaluasi ke Artisan command/queue internal dan menghapus route HTTP.

Jika route HTTP tetap dibutuhkan:

- Wajib autentikasi Admin Sistem, token acak minimal 32 byte, throttle ketat, dan IP allowlist.
- Default token kosong; nilai contoh dilarang.
- Hanya aktif melalui allowlist environment eksplisit, bukan sekadar `APP_ENV != production`.
- Assessment harus melewati otorisasi dan scope tenant.
- Catat usage/cost tanpa prompt rahasia atau API key.

**Validasi wajib:** production/staging/local, token kosong/salah/benar, role salah, tenant lain, rate limit, assessment tidak sah, dan provider tidak terpanggil saat request ditolak.

### WS-10 — Source besar dan kode mati dikurangi bertahap

**Masalah:** controller/service besar meningkatkan risiko regresi; implementasi preview lama masih tidak terjangkau.

**Wajib diperbaiki:**

- Ekstrak berdasarkan domain, bukan sekadar memecah jumlah baris: attachment, submission, notification, backup, AI settings, dan course query.
- Controller hanya melakukan validasi request, authorization, pemanggilan service, dan response.
- Hapus kode mati setelah coverage membuktikan tidak ada caller.
- Jangan mengubah route contract atau tampilan dalam refactor ini.
- Satu PR hanya menangani satu bounded context agar mudah direview dan dirollback.

**Validasi wajib:** characterization test sebelum refactor, feature test setelah refactor, route inventory sebelum/sesudah, dan static search terhadap caller kode yang dihapus.

### WS-11 — Release gate, formatter, dan portability migration

**Masalah:** formatter belum bersih; migration lama MySQL-only; full test dapat berhenti karena memory XLSX.

**Wajib diperbaiki:**

- Tentukan database test resmi. Rekomendasi: MySQL sebagai gate utama karena production MySQL.
- Bila SQLite tetap didukung, semua migration wajib driver-aware.
- Ubah migration `UPDATE JOIN`, `INSERT IGNORE`, dan `NOW()` menjadi query builder atau cabang driver teruji.
- Pisahkan tes export berat dan ukur penggunaan memorinya; hindari menyimpan workbook besar sekaligus jika dapat dilakukan streaming/chunking.
- Tetapkan memory test CI secara eksplisit dan pastikan konfigurasi PHP tidak menimpanya kembali menjadi 128 MB.
- Jalankan Pint secara bertahap per bounded context agar diff format tidak bercampur dengan perubahan perilaku.

**Release gate wajib:**

```text
composer validate --strict
composer audit --locked
vendor/bin/pint --test
php artisan test
npm ci
npm audit --audit-level=high
node --test tests/js/*.test.mjs
npm run build
```

Semua perintah harus exit code `0` pada CI bersih.

## 5. Validasi Lintas Workstream

Setiap perubahan wajib menguji:

1. Guest, role salah, user benar, user tenant/prodi lain, dan objek milik user lain.
2. Happy path, input invalid, kegagalan dependency, timeout, dan request paralel bila relevan.
3. Tidak ada secret, password, token, path privat, atau stack trace dalam response/log.
4. Migration `up` pada salinan data realistis dan `down` bila rollback memang aman.
5. Tidak ada perubahan HTML visual di luar kebutuhan teknis yang disetujui.
6. Tidak ada perubahan rumus nilai atau data akademik.
7. Query count dan waktu respons dibandingkan terhadap baseline sebelum perubahan.

## 6. Strategi Deployment dan Rollback

1. Implementasi dan uji menggunakan data sintetis.
2. Jalankan migration dry-run/preflight pada salinan database staging.
3. Backup staging, deploy, jalankan smoke test semua role.
4. Jalankan load test `/live-status`, course list, dan item detail.
5. CSP dimulai dalam mode Report-Only; enforcement dilakukan setelah laporan bersih.
6. Cleanup attachment dimulai dengan dry-run; penghapusan nyata memerlukan review operator.
7. Restore hanya diuji pada database disposable.
8. Siapkan rollback per fase; jangan mencampur migration destruktif dengan refactor besar.

## 7. Definition of Done

Remediasi dinyatakan selesai hanya jika:

- Seluruh 11 workstream memenuhi acceptance criteria dan memiliki bukti tes.
- Tidak ada Critical/High yang terbuka kembali.
- Seluruh release gate lulus pada CI bersih.
- Backup dan restore berhasil diuji end-to-end pada database disposable.
- Query budget dan hasil load test terdokumentasi.
- CSP staging tidak memblokir fungsi resmi.
- Scanner attachment melaporkan hasil yang dapat ditinjau dan cleanup idempotent.
- Dokumentasi arsitektur, known limitations, runbook backup/restore, dan prosedur deployment diperbarui.
- Audit ulang authorization, dependency, secret, route, migration, dan performa selesai sebelum production.

## 8. Bukti yang Harus Diserahkan

- Daftar file yang berubah per workstream.
- Hasil seluruh command release gate.
- Laporan query count dan load test sebelum/sesudah.
- Matriks tes otorisasi.
- Bukti process list tanpa password DB.
- Bukti restore database disposable beserta checksum.
- Laporan CSP staging.
- Laporan dry-run attachment orphan.
- Daftar risiko yang masih diterima dan persetujuan pemilik sistem.
