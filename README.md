# SALE

Smart Academic Learning Ecosystem (SALE) adalah antarmuka ruang belajar mahasiswa yang dibangun dengan Laravel dan Blade.

## Status Proyek

Repository ini masih berupa prototype frontend. Halaman mahasiswa menggunakan data contoh dan sengaja dapat diakses tanpa autentikasi untuk kebutuhan evaluasi antarmuka.

Jangan gunakan data mahasiswa nyata atau deploy sebagai aplikasi production sebelum autentikasi, authorization policy, validasi, dan model domain selesai dibuat.

Kontrol upload foto, perubahan kata sandi, forum, asisten course, eksekusi kode, dan pengumpulan tugas belum terhubung ke backend.

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

Data contoh saat ini berada di template Blade. Saat backend domain dibuat, pindahkan data tersebut ke model/controller dan gunakan Blade hanya untuk presentasi.

## Batas Keamanan

Sebelum aplikasi menerima data nyata:

1. Lindungi group route mahasiswa dengan middleware `auth`, verifikasi akun, dan role mahasiswa.
2. Gunakan route-model binding dan policy untuk memeriksa enrollment serta kepemilikan submission.
3. Gunakan Form Request, CSRF, validasi upload, dan rate limiting pada endpoint mutasi.
4. Jangan jalankan kode mahasiswa pada host Laravel. Gunakan sandbox terisolasi tanpa credential atau host mount, dengan batas jaringan, CPU, memori, proses, waktu, dan output.
5. Jalankan production dengan `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, dan secret yang dikelola di luar repository.

`package-lock.json` harus selalu ikut di-commit agar `npm ci` menghasilkan dependency tree yang konsisten.
