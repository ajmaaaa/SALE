# DATA DEMO PRODUKSI VIDEO SALE

Paket ini menyiapkan data demo kontinuitas untuk produksi video SALE tanpa mengambil identitas pengguna produksi.

## Isi paket

File `sale_video_demo.sql` berisi:

- **4 peran** — Admin Sistem, Admin Program Studi, Dosen, Mahasiswa
- **12 akun** — 1 admin, 1 admin prodi, 3 dosen, 7 mahasiswa
- **2 program studi** — Teknik Informatika Demo, Sistem Informasi Demo
- **2 semester** — Ganjil 2026/2027 (aktif) dan Genap (tidak aktif)
- **3 mata kuliah** — IF204 Struktur Data, IF230 RPL, IF260 Pemrograman Web
- **4 kelas** — 3 aktif + 1 diarsipkan (untuk demo fitur arsip)
- **Link YouTube nyata** yang dikurasi per mata kuliah (lihat tabel di bawah)
- **26 konten/assessment** — mencakup seluruh tipe: materi, pengumuman, tugas, coding, kuis, UTS, UAS, PBL, lainnya
- **3 rubrik** — untuk PBL IF204, IF230, dan IF260
- **Diskusi forum** — 26 pesan di 3 kelas (dosen & mahasiswa)
- **Chat room** — 3 room, 23 pesan dengan reply dan mention
- **Submission & nilai** — dari beberapa mahasiswa
- **Riwayat AI Tutor** — 3 thread, 10 pesan, 5 API call log
- **Activity log** — 11 entri aktivitas sistem

## Link YouTube per Mata Kuliah

| Mata Kuliah | Video | URL |
|---|---|---|
| IF204 – Struktur Data | BST – Dr. Achmad Solichin | `https://www.youtube.com/watch?v=PEfAxWFY14Q` |
| IF204 – Struktur Data | Coding BST – Josef Bernadi | `https://www.youtube.com/watch?v=R2j9v7P59hI` |
| IF230 – RPL | Intro Software Engineering – Univ Teknokrat | `https://www.youtube.com/watch?v=Y6Q8Nl0YSIY` |
| IF230 – RPL | SDLC Tutorial | `https://www.youtube.com/watch?v=SaCYkPD4_K0` |
| IF260 – Pemrograman Web | Playlist HTML Dasar – WPU Sandhika Galih | `https://www.youtube.com/playlist?list=PLFIM0718LjIVuONHysfOK0ZtiqUWvrx4F` |
| IF260 – Pemrograman Web | Playlist CSS Dasar – WPU Sandhika Galih | `https://www.youtube.com/playlist?list=PLFIM0718LjIUBrbm6Gdh6k7ZUvPIAZm7p` |

## Asset File (storage)

Asset yang dirujuk SQL berada di `storage/app/private/learning-preview`.

| Asset | Fungsi video |
|---|---|
| `demo-sale-modul-binary-tree.pdf` | PDF materi IF204 dan tombol unduh |
| `demo-sale-diagram-binary-tree.png` | Gambar materi IF204 dan lampiran coding |
| `demo-sale-video-binary-tree.mp4` | Pemutar video materi lokal IF204 |
| `demo-sale-laporan-mahasiswa.pdf` | Berkas pengumpulan mahasiswa IF204 |
| `demo-sale-hasil-pengujian.png` | Gambar hasil pengujian mahasiswa IF204 |
| `demo-sale-modul-rpl.pdf` | PDF materi IF230 RPL |
| `demo-sale-diagram-sdlc.png` | Diagram SDLC IF230 |
| `demo-sale-template-srs.pdf` | Template SRS IEEE 830 IF230 |
| `demo-sale-modul-html-dasar.pdf` | Modul HTML Dasar IF260 |

## Akun

Semua akun memakai kata sandi demo `DemoSale2026!`.

| Peran | Email | Nama |
|---|---|---|
| Admin Sistem | `admin.video@sale.demo` | Admin Sistem SALE Demo |
| Admin Program Studi | `adminprodi.video@sale.demo` | Nadia Putri, M.Kom. |
| Dosen utama (IF204 & IF230) + Admin Prodi | `dosen.video@sale.demo` | Dr. Budi Santoso, M.Kom. |
| Dosen anggota IF204 | `dosen.anggota.video@sale.demo` | Sari Lestari, S.Kom., M.Cs. |
| Dosen IF260 | `dosen2.video@sale.demo` | Reza Firmansyah, M.T. |
| Mahasiswa utama (IF204 & IF230) | `ahmad.video@sale.demo` | Ahmad Maulana |
| Mahasiswa IF204 & IF230 | `siti.video@sale.demo` | Siti Nurhaliza |
| Mahasiswa IF204 & IF260 | `rizky.video@sale.demo` | Rizky Pratama |
| Akun wajib ganti kata sandi (IF260) | `dewi.video@sale.demo` | Dewi Anggraini |
| Mahasiswa IF204 & IF230 | `fajar.video@sale.demo` | Fajar Nugroho |
| Mahasiswa IF204 & IF260 | `maya.video@sale.demo` | Maya Kusuma |
| Mahasiswa IF230 & IF260 | `dimas.video@sale.demo` | Dimas Prasetyo |

## Kode Kelas

| Kelas | Kode Masuk |
|---|---|
| IF204 – Struktur Data, Kelas A | `SALEIF26` |
| IF230 – Rekayasa Perangkat Lunak, Kelas A | `SALERPL2` |
| IF260 – Pemrograman Web, Kelas B | `SALEWEB3` |
| IF204 – Kelas B *(diarsipkan, demo fitur arsip)* | `SALEIF2B` |

## Cara pakai aman

Gunakan hanya pada database lokal atau staging khusus video yang sudah menjalankan seluruh migration terbaru.

```bash
mysql NAMA_DATABASE < video-guide/data/sale_video_demo.sql
```

SQL menggunakan rentang ID **910000 – 919999** dan membersihkan ulang hanya data demo pada rentang tersebut agar dapat diimpor ulang. Jangan gunakan rentang itu untuk data produksi.

Sesudah impor, jalankan pemeriksaan berikut.

```bash
php artisan storage:link
php artisan route:list
php artisan test
```

Periksa secara manual bahwa:
- Login berhasil untuk semua peran
- IF204 Demo tampil pada akun dosen dengan video YouTube ter-embed
- Kode `SALEIF26` dapat dikonfirmasi mahasiswa
- Video YouTube berputar di materi IF204 dan IF260
- PDF serta gambar terbuka dari lampiran
- Tugas Ahmad tampil pada halaman penilaian dosen
- Chat berisi balasan dan mention yang benar
- Riwayat AI Tutor tampil di workbench coding BST dan SRS

File SQL tidak menyimpan kunci API asli, session, token login, atau data pribadi produksi.
