# Audit keamanan dan struktur SALE — 23 September 2026

## Ringkasan

Audit ulang dilakukan terhadap working tree saat ini dengan baseline OWASP ASVS 5.0.0, praktik Laravel 12, pemeriksaan route/controller, autentikasi dan otorisasi, mutasi data, upload/export, konfigurasi, dependency, serta regression test. Perbaikan berisiko tinggi yang dapat diterapkan tanpa mendesain ulang domain sudah dimasukkan.

Status saat ini: **lebih aman untuk pengembangan, tetapi belum siap memproses data akademik nyata di produksi**. Dua batas arsitektur utama masih terbuka: isolasi objek per program studi belum konsisten, dan sebagian course/submission/diskusi/berkas masih disimpan dalam sesi pratinjau.

Backlog dan acceptance criteria untuk kedua blocker dipertahankan di [`docs/known-limitations.md`](../../known-limitations.md).

Percobaan Strix aktual dengan Ollama lokal sudah dijalankan tanpa API berbayar pada
23 September 2026. Run dihentikan sebelum respons pertama karena prompt 55.359 token
dipotong Ollama menjadi 8.194 token; hasil nol temuan dari run tersebut **bukan** bukti
aplikasi aman. Detail dan bukti ada di [uji Strix lokal](strix-local-ollama.md).

## Temuan yang diperbaiki

| Risiko | Perbaikan |
| --- | --- |
| Critical — persona/passwordless login dan role switching dapat terbawa ke produksi | Persona dan fallback sesi hanya berlaku bila `SALE_DEMO_MODE=true` sekaligus environment `local/testing`; produksi memakai akun database dan password hash. |
| Critical — route mahasiswa, dosen, admin, kaprodi, file preview, QR/barcode, dan AI tidak konsisten terlindungi | Middleware role/auth dipasang pada seluruh kelompok terkait; cross-role menghasilkan 403 dan guest diarahkan ke login. |
| High — mode demo meloloskan middleware role sehingga URL role lain dapat dibuka langsung | Mode demo kini tetap mensyaratkan role aktif yang cocok; guest diarahkan ke login, cross-role menghasilkan 403, serta switch-role/logout hanya menerima POST+CSRF. |
| High — middleware dosen dapat memilih akun dosen pertama | Auto-login tersebut dihapus. |
| High — admin-prodi dapat mengubah/menghapus akun privileged lewat route model binding | Mutasi dibatasi pada role dosen dan mahasiswa serta dilindungi regression test. |
| High — enrollment berubah lewat GET dan rentan race/capacity overrun | GET hanya menampilkan konfirmasi; POST+CSRF melakukan transaksi, row lock, pemeriksaan kapasitas ulang, dan attach idempoten. |
| High — seed produksi membuat akun/data demo | `DatabaseSeeder` produksi hanya membuat lima role tetap; data demo dibatasi pada mode demo lokal/testing. |
| Medium — logout tidak menutup sesi dengan lengkap | Logout menginvalidasi sesi dan meregenerasi token CSRF; GET logout ditolak di mode operasional. |
| Medium — formula CSV dari data pengguna | Kolom dinamis pada export nilai dan laporan disanitasi. |
| Medium — query database berada di Blade | Query statistik AI dipindahkan ke controller. |
| Medium — konfigurasi PHP berisi path home developer | Override `PHPRC` hard-coded dihapus dan diganti konfigurasi opsional `PHP_CONFIG_DIR`. |
| Medium — header browser kurang lengkap | CSP diperketat dan COOP, CORP, HSTS produksi, serta kebijakan cross-domain ditambahkan. |
| Medium — password impor/manual lemah atau default | Mode operasional mensyaratkan password panjang dan template tidak lagi menyertakan password bersama. |
| Supply chain | Dependency diperbarui dalam constraint aman; Composer dan npm audit tidak menemukan advisory. |

## Temuan yang masih terbuka

### P0 sebelum produksi

1. **Isolasi tenant/program studi belum menyeluruh.** Beberapa controller admin-prodi masih dapat menerima ID prodi, mata kuliah, CPL/CPMK, kelas, dosen, atau mahasiswa milik prodi lain. Terapkan policy/scope terpusat berdasarkan `auth()->user()->prodi_id`; role admin global harus menjadi pengecualian eksplisit. Tambahkan tes IDOR lintas prodi untuk setiap operasi baca/tulis/export.
2. **Domain pembelajaran masih campuran sesi dan database.** Course, materi, diskusi, submission, serta metadata berkas pratinjau tidak semuanya mempunyai ownership persisten. Migrasikan ke model database dan policy enrollment/teaching assignment sebelum multi-user produksi.

### P1

3. Area admin utama masih memakai state sesi dan belum menjadi administrasi institusi persisten.
4. Belum ada kebijakan retensi/cleanup terjadwal untuk `storage/app/private/learning-preview`.
5. Belum ada CI repository yang memaksa test, build, dan audit dependency pada setiap perubahan.
6. Baseline format seluruh repository belum bersih; Pint pada file yang disentuh lulus, tetapi normalisasi menyeluruh perlu perubahan terpisah agar diff tetap dapat direview.
7. Runtime audit ini memakai Node 26.8.2, sedangkan project menetapkan Node 22 LTS. Build lulus, tetapi CI/deployment harus menggunakan Node 22 agar sesuai kontrak project.

## Bukti verifikasi

- `php artisan test`: 142 test dan 1.396 assertion lulus, termasuk regression test guest serta perubahan path lintas role pada mode demo.
- Tes JavaScript: 4 lulus.
- `npm run build`: lulus dengan Vite 6.4.3.
- `composer validate --strict --no-check-publish`: lulus.
- `composer audit --locked`: tidak ada advisory.
- `npm audit`: 0 vulnerability dari 166 dependency.
- `php artisan route:list --json`: lulus.
- `python3 scripts/security-scan.py prepare`: snapshot Strix aman berhasil dibuat dengan 234 file tracked/untracked non-ignored; jalankan kembali tepat sebelum scan aktual bila working tree berubah.
- Strix lokal mencapai sandbox dan model `qwen3-strix`, tetapi run `20260923t100154354824z_1e48` dihentikan karena context prompt terpotong; tidak ada coverage keamanan yang sah dari run tersebut.

## Acuan

- OWASP Application Security Verification Standard 5.0.0: <https://owasp.org/projects/asvs/>
- Laravel 12 request/file handling: <https://laravel.com/docs/12.x/requests>
- Laravel release policy: <https://laravel.com/docs/12.x/releases>
- Strix releases: <https://github.com/usestrix/strix/releases>
