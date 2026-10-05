# Editor dan eksekusi kode SALE

## Temuan riwayat 9 September 2026

Riwayat Antigravity `a4ce7e29-e279-42e0-89db-afbe3ff19cbe` (langkah 508) merekomendasikan Piston dengan klaim API publik gratis tanpa key dan tanpa batas. Ini tidak tepat: https://github.com/engineer-man/piston menjelaskan pembatasan akses sejak 15 Februari 2026. Langkah berikutnya mengganti API dengan `proc_open` Python pada host Laravel. Timeout saja bukan isolasi; jalur itu sudah dihapus.

## Pilihan saat ini

- CodeMirror 6 tetap cocok untuk editor Python yang sudah terintegrasi dengan draf, pengumpulan kode, dan Lumina AI.
- Pyodide menjalankan latihan Python di browser: https://pyodide.org/en/stable/usage/index.html. Tidak ada biaya API per eksekusi; hosting aplikasi dan unduhan runtime tetap memakai sumber daya.
- Piston self-hosted lebih cocok jika nantinya membutuhkan banyak bahasa dan pengujian server. Judge0 juga merupakan runner server, bukan pengganti editor. Keduanya memerlukan infrastruktur dan pemeliharaan.

## Menjalankan

Gunakan Node 22 sesuai package.json, lalu `npm ci` dan `npm run build` atau `npm run dev`. Skrip menyalin runtime dari paket Pyodide ke `public/vendor/pyodide`; sajikan direktori ini bersama `public/build`. Browser tidak mengunduh runtime dari CDN pihak ketiga. Server harus menyajikan `.mjs` sebagai JavaScript dan `.wasm` sebagai `application/wasm`.

Setiap Run membuat worker baru. Waktu pemuatan maksimum 60 detik, eksekusi maksimum 10 detik, output maksimum 64.000 karakter. Hentikan mematikan worker; perulangan Python tidak membekukan antarmuka. Stdout dan stderr ditampilkan sebagai teks, termasuk traceback dan hasil unittest. Versi Python diambil dari runtime sebenarnya.

Jalankan Kode mengeksekusi sesuai bahasa tugas. Tombol terpisah Uji Tugas BST pada tugas ID 1 menjalankan empat pengujian latihan yang membutuhkan kelas BinaryTree. Output sebelumnya dibersihkan setiap eksekusi. `input()` interaktif belum tersedia. Program dengan `if __name__ == '__main__'` tetap dijalankan.

Tugas coding memiliki `language` (default `python`):

- `python` dieksekusi dengan Pyodide di Web Worker. Versi Python diambil dari runtime sebenarnya.
- `web` merender kode HTML/CSS/JS langsung di panel Pratinjau melalui iframe `sandbox="allow-scripts allow-forms"` (tanpa `allow-same-origin`, jadi kode pratinjau tidak dapat membaca cookie, penyimpanan lokal, atau data situs). `console.log/warn/error/debug` dan error runtime diteruskan ke tab Konsol lewat `postMessage`. Panel output memakai tab Konsol/Pratinjau, bukan jendela terpisah.
- Bahasa lain (C, C++, Java, dst.) hanya melalui runner server opsional Piston/Judge0 di bawah; tidak ada fallback eksekusi di host Laravel.

## Multi-berkas kerja (workbench)

Editor menjalankan beberapa berkas dalam satu tugas, mirip editor teks: tab berkas di atas editor, tombol `+ Berkas` menambah berkas baru, klik dua kali tab (atau ikon ✎) mengubah nama, dan tombol × menghapus berkas. Berkas terakhir tidak dapat dihapus; maksimal 5 berkas per tugas.

- Ekstensi yang diizinkan mengikuti bahasa tugas: `python` hanya `.py`, `web` menerima `.html/.htm/.css/.js`. Nama berkas unik (tidak peka huruf besar/kecil) dan tidak boleh memuat jalur folder. Mode penyorotan editor berganti otomatis mengikuti ekstensi berkas aktif.
- Untuk `python`, semua berkas `.py` selain berkas utama dieksekusi lebih dulu sebagai modul (dapat di-`import`), lalu berkas utama (`main.py`; jika tidak ada, berkas terbesar terakhir) dijalankan. Untuk `web`, `index.html` (atau berkas HTML pertama) menjadi dokumen utama; berkas `.css` disisipkan ke `<head>` dan berkas `.js` sebelum `</body>`, sementara tag `<link href="*.css">` dan `<script src="*.js">` yang merujuk berkas virtual dibuang agar tidak duplikat.
- Batas untuk mencegah beban berlebih: 5 berkas, 8.000 karakter per berkas, 20.000 karakter total. Ditegakkan saat Jalankan/Kumpulkan (pesan error di terminal/status), dengan penghitung karakter langsung di status bar editor.
- Templat awal per tugas dimuat dari `@json($defaultFiles)` di `assignment-code.blade.php`; draf seluruh berkas disimpan ke `localStorage` sebagai JSON dan dikirim sebagai `answer` JSON saat mengumpulkan.

Pengujian browser dapat dilihat/diubah pengguna dan bukan sumber nilai resmi. Pyodide bukan batas keamanan untuk menjalankan kode pihak ketiga yang tidak dipercaya. Tidak ada klaim terminal Linux penuh atau batas memori keras browser. Jangan gunakan hasil browser sebagai bukti kelulusan tugas.

Pratinjau Web juga bukan sandbox keamanan; iframe `sandbox` melindungi data situs, tetapi satu ular JS sinkron (mis. `while (true);`) membekukan tab karena iframe berbagi thread utama dengan halaman. Mulai dari 8 detik tanpa acara `load`, eksekusi dianggap selesai agar tombol kembali aktif, tetapi halaman beku tidak dapat dihentikan lewat tombol Hentikan. Untuk kode yang tidak dipercaya gunakan Piston/Judge0.

## Runner server opsional

`POST /code/run/{assignment}` hanya untuk akun terautentikasi, dibatasi 15 permintaan/menit, dan default-nya tidak aktif. Untuk integrasi server berikutnya, set `PISTON_URL` ke endpoint instance Piston sendiri (misalnya `http://runner:2000/api/v2/piston`) dan `PISTON_PYTHON_VERSION` sesuai runtime terpasang. Tombol Run saat ini tetap menggunakan browser. Tidak ada fallback mengeksekusi kode di host Laravel.

Runner ini belum merupakan sistem penilaian resmi: enrollment, otorisasi per tugas, test tersembunyi yang benar, dan pencatatan nilai harus dibangun sebelum dipakai untuk penilaian. Menyatukan kode dan suite dalam satu proses tidak menjamin kerahasiaan suite.

## Verifikasi

`npm run build`

`node tests/js/python-worker.test.mjs`

`node tests/js/web-preview.test.mjs`

`php artisan test tests/Feature`

## Regresi browser: Vite dan Laravel

Worker dibentuk dari sumber `?raw` melalui Blob agar asalnya mengikuti halaman Laravel, termasuk saat JavaScript dimuat dari port Vite berbeda. URL Blob dilepas setelah selesai/gagal/dibatalkan. Jika memasang CSP yang membatasi worker, izinkan `worker-src 'self' blob:`.

Tes browser ada di `tests/browser/python-runner.cjs` dan membutuhkan Playwright dengan Chromium. Jalankan dengan `node tests/browser/python-runner.cjs`; variabel opsional `SALE_URL` dan `CHROMIUM_PATH` mengatur server dan executable. Tes memakai sesi browser terpisah, menjalankan BST benar, menghentikan infinite loop, serta memeriksa timeout 10 detik. Verifikasi pada 10 September 2026 dengan Laravel port 8000 dan Vite port 5173 berhasil tanpa error JavaScript.
