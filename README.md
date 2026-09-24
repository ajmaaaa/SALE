# SALE

Smart Academic Learning Ecosystem (SALE) adalah antarmuka ruang belajar mahasiswa yang dibangun dengan Laravel dan Blade.

## Status Proyek

Repository ini adalah aplikasi Laravel 12 yang menggabungkan modul database OBE/administrasi dengan sejumlah alur pembelajaran yang masih berupa pratinjau berbasis sesi. Mode persona tanpa password hanya tersedia saat `SALE_DEMO_MODE=true` **dan** `APP_ENV` adalah `local` atau `testing`. Pada lingkungan lain, route peran memerlukan akun database yang terautentikasi.

Jangan gunakan data mahasiswa nyata atau menganggap aplikasi siap produksi sebelum seluruh blocker pada [audit terbaru](docs/audits/2026-09-23/README.md) diselesaikan. Khususnya, isolasi data per program studi dan migrasi alur pembelajaran berbasis sesi ke model persisten masih diperlukan.

Dokumentasi pengembang: [arsitektur](docs/architecture.md), [panduan pengembangan](docs/development.md), dan [blocker/known limitations](docs/known-limitations.md).

Alur course, materi/tugas, diskusi, dan pengumpulan kini menggunakan sesi Laravel untuk pratinjau. Data tidak dibagikan antar pengguna. Upload sampul dan lampiran divalidasi dan disimpan pada disk privat `local`, hanya dapat dibuka oleh sesi pengunggah. Metadata hilang ketika sesi kedaluwarsa; berkas pada `storage/app/private/learning-preview` perlu dibersihkan setelah pengujian.

RAG, nilai otomatis, analitik, kehadiran, dan administrasi kampus belum sepenuhnya terhubung. Enrollment dan autentikasi peran sudah menggunakan database, sedangkan upload foto profil dan perubahan kata sandi belum aktif. Runner Python browser dipakai untuk latihan, bukan penilaian resmi.

## Tech Stack

- PHP 8.2 atau lebih baru
- Laravel 12
- Blade server-side templates
- Tailwind CSS 4 melalui plugin Vite
- Vite 6
- Vanilla JavaScript ES modules
- CodeMirror 6 untuk editor Python
- PHPUnit 11
- Laravel Pint
- Node.js 22 LTS dan npm 10 atau lebih baru

Proyek ini tidak menggunakan React, Vue, Alpine, Livewire, Inertia, Bootstrap, atau CDN frontend. Tailwind 4 menggunakan konfigurasi CSS-first di `resources/css/app.css`, sehingga tidak diperlukan `tailwind.config.js`.

## Menjalankan Proyek

Database project menggunakan MySQL/MariaDB. Di Windows, XAMPP dapat dipakai
untuk menyediakan PHP dan MySQL; aktifkan MySQL dari XAMPP Control Panel. Di
Linux, gunakan MySQL/MariaDB lokal atau XAMPP/LAMPP. Pastikan PHP CLI yang
dipakai Composer memiliki extension `pdo_mysql`.

```bash
composer install
composer run setup
composer run dev
```

`composer run setup` bekerja lintas Windows dan Linux. Perintah ini membuat
`.env` dan `APP_KEY` jika belum tersedia, membuat database development `sale`
serta database test `test_sale`, memasang dependency frontend, kemudian
menjalankan migration dan seeder. Tidak perlu membuat tabel manual atau
mengimpor file SQL.

Konfigurasi development bawaan memakai MySQL di `127.0.0.1:3306`, user `root`,
dan password kosong karena itu umum pada XAMPP. Jika port, user, atau password
MySQL berbeda, ubah nilainya di `.env` masing-masing komputer:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sale
DB_TEST_DATABASE=test_sale
DB_USERNAME=root
DB_PASSWORD=
```

### MariaDB Linux: error 1698

Pada sejumlah distro Linux, akun `root` MariaDB memakai autentikasi
`unix_socket`. Akun tersebut hanya dapat dibuka oleh administrator melalui
terminal dan tidak dapat dipakai oleh PHP/PDO. Jika setup menampilkan
`Access denied for user 'root'@'localhost'` dengan kode 1698, buat user khusus
aplikasi satu kali:

```bash
sudo mariadb
```

Kemudian jalankan SQL berikut di prompt MariaDB. Ganti password contoh dengan
password lokal Anda sendiri:

```sql
CREATE DATABASE IF NOT EXISTS `sale` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `test_sale` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sale_app'@'localhost' IDENTIFIED BY 'ganti-password-lokal';
GRANT ALL PRIVILEGES ON `sale`.* TO 'sale_app'@'localhost';
GRANT ALL PRIVILEGES ON `test_sale`.* TO 'sale_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Sesuaikan `.env`, lalu ulangi setup:

```dotenv
DB_USERNAME=sale_app
DB_PASSWORD=ganti-password-lokal
```

```bash
composer run setup
```

Langkah ini tidak diperlukan pada XAMPP Windows apabila `root` tanpa password
memang diizinkan. Jangan mengubah plugin autentikasi akun `root` hanya agar
aplikasi dapat tersambung.

File `.env` tidak dibagikan melalui Git sehingga kredensial anggota tim tidak
saling menimpa. Struktur database dibagikan melalui migration dan data
demo/referensi melalui seeder. Setelah menarik perubahan dari Git, jalankan
`composer install`, `npm ci`, dan `php artisan migrate --seed`.

Aplikasi tersedia melalui URL yang ditampilkan oleh `php artisan serve`. Preview
mahasiswa dimulai dari `/mahasiswa/dashboard`. `composer run dev` tidak memakai
Laravel Pail agar kompatibel dengan PHP native Windows yang tidak menyediakan
extension `pcntl`.

Untuk mereset database development tersedia `composer run db:reset`. Perintah
tersebut menghapus seluruh data dan hanya boleh dipakai pada database lokal.

`.env.example` mengaktifkan mode demo untuk pengembangan lokal. Untuk lingkungan operasional gunakan sedikitnya:

```dotenv
APP_ENV=production
APP_DEBUG=false
SALE_DEMO_MODE=false
SESSION_SECURE_COOKIE=true
```

Jangan jalankan `db:seed` produksi dengan mode demo aktif. `DatabaseSeeder` hanya membuat lima role tetap ketika mode demo tidak aktif.

## Pemeriksaan Kualitas

```bash
npm run build
node --test tests/js/*.test.mjs tests/grade-import.test.js
php artisan test
vendor/bin/pint --test
composer audit --locked
npm audit
```

## Struktur Frontend

- `resources/views/layouts/mahasiswa.blade.php`: shell dan navigasi mahasiswa
- `resources/views/mahasiswa`: halaman Blade mahasiswa
- `resources/css/app.css`: design tokens dan komponen Tailwind
- `resources/js/app.js`: sidebar, CodeMirror, dan interaksi tab profil
- `app/Http/Controllers/Mahasiswa`: controller halaman mahasiswa
- `routes/web.php`: route web dan route preview mahasiswa

Data course dan konten ada di `app/Support/LearningPreview.php`. Alur pratinjau ditangani `LearningController`, dengan view di `resources/views/learning` dan `resources/views/dosen`. Model persisten dan policy diperlukan sebelum penggunaan bersama.

## Batas Keamanan

Sebelum aplikasi menerima data nyata:

1. Selesaikan isolasi tenant/prodi pada seluruh controller admin-prodi dan policy objek domain.
2. Migrasikan course, submission, diskusi, dan berkas pratinjau dari sesi ke model persisten dengan pemeriksaan enrollment/kepemilikan.
3. Pertahankan CSRF, validasi upload, rate limiting, dan mutasi hanya melalui metode non-GET.
4. Jangan jalankan kode mahasiswa pada host Laravel. Gunakan runner browser saat ini hanya untuk latihan; penilaian server harus memakai sandbox terisolasi tanpa credential atau host mount, dengan batas jaringan, CPU, memori, proses, waktu, dan output.
5. Jalankan produksi dengan `APP_ENV=production`, `APP_DEBUG=false`, `SALE_DEMO_MODE=false`, HTTPS, secure cookie, dan secret yang dikelola di luar repository.

`package-lock.json` harus selalu ikut di-commit agar `npm ci` menghasilkan dependency tree yang konsisten.

## Alur penyesuaian September 2026

- Course → modul → detail materi/tugas → lampiran, pengumpulan, dan diskusi khusus konten tersebut.
- Tugas & Kuis adalah rekap lintas course dengan filter; tab Nilai & umpan balik menggantikan menu Nilai terpisah.
- Dosen: `/dosen/dashboard`, `/dosen/course`, dan `/dosen/penilaian`. Tambah course mendukung sampul JPG/PNG/WebP dan video pengantar. Tambah konten mendukung materi, pengumuman, tugas, coding, dan kuis dengan pilihan soal AKM.
- Pengumpulan menerima format yang ditentukan dosen: dokumen/ZIP, gambar, tautan, teks, atau pilihan jawaban.
- Ruang koding menyimpan draf lokal per tugas. Seleksi teks → Tanyakan baris terpilih → konteks berkas/baris → pratinjau pesan. Respons tutor Gemini memerlukan aktivasi dan akun AI; latihan Python berjalan lokal di browser menggunakan Pyodide (bukan penilaian resmi).
- Gaya visual mengikuti biru tua, Inter, dan struktur sederhana proyek awal. Referensi Figma tidak dapat diakses pada sesi pengerjaan; PDF dipakai sebagai acuan struktur.

Gunakan session driver `file` atau `database` untuk mencoba alur lintas halaman. Untuk batas upload UI (20 MB/berkas, 5 berkas), atur PHP `upload_max_filesize=20M`, `post_max_size=110M`, dan batas web server yang sesuai. Batas default PHP yang lebih kecil tetap berlaku bila belum diatur.

Snapshot sebelum perubahan: `7c019fb`. Untuk meninjau versi lama tanpa menimpa pekerjaan sekarang, gunakan `git worktree add ../SALE-before-redesign 7c019fb`.

## Revisi visual dan administrator

Dashboard kembali memakai susunan visual awal: foto rekomendasi, tenggat, dan pesan diskusi terbaru. Area kartu course diganti grafik IP semester / IPK kumulatif (data contoh; bukan nilai mahasiswa sebenarnya). Komponen course dashboard awal disimpan utuh di `resources/views/mahasiswa/partials/dashboard-courses-original.blade.php` dan tidak dirender secara default.

Area admin dimulai dari `/admin/dashboard` dan menyediakan:

- Pengguna: tambah/edit identitas, peran, status, filter; mencegah email/identitas ganda dan penonaktifan admin aktif terakhir.
- Akademik: fakultas, prodi, semester, kelas, course terkait, dan peserta mahasiswa; validasi induk serta peran peserta.
- Activity log untuk perubahan admin; rekap akademik dengan ekspor CSV.
- Pengaturan institusi dan semester; monitoring menyatakan integrasi yang belum tersedia.

Area admin utama masih memakai data pratinjau berbasis sesi. Area admin-prodi/OBE memakai database dan role, tetapi pembatasan objek berdasarkan prodi belum lengkap. Penetapan peserta sudah persisten; pembatasan akses pada course pratinjau berdasarkan enrollment belum lengkap. Backup dan laporan institusi belum menjadi layanan operasional.

Form konten mendukung gambar stimulus (maks. 5 MB, deskripsi wajib), gambar per pilihan (maks. 2 MB), 2–20 pilihan berbeda, poin maksimal, dokumen, video, dan tautan. Menu pengumpulan menyediakan berkas, gambar, tautan/Google Drive, dan teks sesuai format dosen. Google Drive menggunakan tautan yang ditempel, bukan pemilih berkas terintegrasi atau pembuatan Google Docs. Lampiran dapat dilihat/dihapus sebelum pengumpulan. Berkas pengumpulan sebelumnya dipertahankan kecuali secara eksplisit dilepas.

Foto banner menggunakan aset foto yang sama dengan desain awal (Pexels photo 7989138), kini disimpan lokal di `public/images/learning-banner.jpg` agar tidak bergantung pada permintaan gambar eksternal saat membuka dashboard.

## Tutor AI Gemini

Lumina AI pada room coding terhubung melalui backend Gemini dengan akun berpassword, akses tugas eksplisit, kuota token, riwayat persisten, dan pemeriksaan bantuan kumulatif. Fitur dinonaktifkan sampai API key dikonfigurasi. Lihat [panduan aktivasi dan evaluasi AI](docs/ai-tutor.md). Persona aplikasi hanya tersedia di mode demo lokal; endpoint status/kirim AI memerlukan autentikasi database.

### Eksekusi kode

CodeMirror 6 tetap menjadi editor. Tugas coding memakai field `language`: `python` (default) dijalankan dengan Pyodide di Web Worker dengan batas 10 detik, tombol Hentikan, dan batas output 64.000 karakter; `web` merender HTML/CSS/JS di panel Pratinjau (iframe sandbox) dengan console log di tab Konsol. Editor mendukung banyak berkas (tab tambah/rename/hapus) dengan ekstensi yang disesuaikan bahasa dan batas 5 berkas, 8.000 karakter/berkas, 20.000 karakter total. Runtime Python disalin lokal saat `npm run dev` atau `npm run build`; deploy juga direktori `public/vendor/pyodide`. Tidak memerlukan API publik atau Python pada server Laravel. Lihat [panduan runner](docs/code-runner.md).
