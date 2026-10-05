# SALE

Smart Academic Learning Ecosystem (SALE) adalah antarmuka ruang belajar mahasiswa yang dibangun dengan Laravel dan Blade.

## Status Proyek

Repository ini masih berupa prototype frontend. Halaman mahasiswa menggunakan data contoh dan sengaja dapat diakses tanpa autentikasi untuk kebutuhan evaluasi antarmuka.

Jangan gunakan data mahasiswa nyata atau deploy sebagai aplikasi production sebelum autentikasi, authorization policy, validasi, dan model domain selesai dibuat.

Alur course, materi/tugas, diskusi, dan pengumpulan kini menggunakan sesi Laravel untuk pratinjau. Data tidak dibagikan antar pengguna. Upload sampul dan lampiran divalidasi dan disimpan pada disk privat `local`, hanya dapat dibuka oleh sesi pengunggah. Metadata hilang ketika sesi kedaluwarsa; berkas pada `storage/app/private/learning-preview` perlu dibersihkan setelah pengujian.

RAG, runner Python terisolasi, nilai otomatis, enrollment, autentikasi peran, analitik, kehadiran, dan administrasi kampus belum terhubung. Upload foto profil dan perubahan kata sandi juga belum aktif. Navigasi pratinjau peran bukan mekanisme otorisasi.

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

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
composer run dev
```

Aplikasi tersedia melalui URL yang ditampilkan oleh `php artisan serve`. Preview mahasiswa dimulai dari `/mahasiswa/dashboard`.

## Pemeriksaan Kualitas

```bash
npm run build
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

1. Lindungi group route mahasiswa dengan middleware `auth`, verifikasi akun, dan role mahasiswa.
2. Gunakan route-model binding dan policy untuk memeriksa enrollment serta kepemilikan submission.
3. Gunakan Form Request, CSRF, validasi upload, dan rate limiting pada endpoint mutasi.
4. Jangan jalankan kode mahasiswa pada host Laravel. Gunakan sandbox terisolasi tanpa credential atau host mount, dengan batas jaringan, CPU, memori, proses, waktu, dan output.
5. Jalankan production dengan `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, dan secret yang dikelola di luar repository.

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

Seluruh perubahan admin masih berbasis sesi, bukan otorisasi atau sinkronisasi institusi. Penetapan peserta tercatat untuk pratinjau pengelolaan; pembatasan akses course berdasarkan enrollment belum diaktifkan. Backup, statistik layanan AI, dan laporan institusi tidak disimulasikan sebagai data operasional.

Form konten mendukung gambar stimulus (maks. 5 MB, deskripsi wajib), gambar per pilihan (maks. 2 MB), 2–20 pilihan berbeda, poin maksimal, dokumen, video, dan tautan. Menu pengumpulan menyediakan berkas, gambar, tautan/Google Drive, dan teks sesuai format dosen. Google Drive menggunakan tautan yang ditempel, bukan pemilih berkas terintegrasi atau pembuatan Google Docs. Lampiran dapat dilihat/dihapus sebelum pengumpulan. Berkas pengumpulan sebelumnya dipertahankan kecuali secara eksplisit dilepas.

Foto banner menggunakan aset foto yang sama dengan desain awal (Pexels photo 7989138), kini disimpan lokal di `public/images/learning-banner.jpg` agar tidak bergantung pada permintaan gambar eksternal saat membuka dashboard.

## Tutor AI Gemini

Lumina AI pada room coding terhubung melalui backend Gemini dengan akun berpassword, akses tugas eksplisit, kuota token, riwayat persisten, dan pemeriksaan bantuan kumulatif. Fitur dinonaktifkan sampai API key dikonfigurasi. Lihat [panduan aktivasi dan evaluasi AI](docs/ai-tutor.md). Login persona aplikasi tetap pratinjau; akses AI memakai autentikasi database tersendiri.

### Eksekusi kode

CodeMirror 6 tetap menjadi editor. Tugas coding memakai field `language`: `python` (default) dijalankan dengan Pyodide di Web Worker dengan batas 10 detik, tombol Hentikan, dan batas output 64.000 karakter; `web` merender HTML/CSS/JS di panel Pratinjau (iframe sandbox) dengan console log di tab Konsol. Editor mendukung banyak berkas (tab tambah/rename/hapus) dengan ekstensi yang disesuaikan bahasa dan batas 5 berkas, 8.000 karakter/berkas, 20.000 karakter total. Runtime Python disalin lokal saat `npm run dev` atau `npm run build`; deploy juga direktori `public/vendor/pyodide`. Tidak memerlukan API publik atau Python pada server Laravel. Lihat [panduan runner](docs/code-runner.md).
