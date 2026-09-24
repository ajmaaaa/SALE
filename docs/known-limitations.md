# Blocker dan keterbatasan SALE

Dokumen ini adalah backlog teknis wajib. Status audit rinci berada di [audits/2026-09-23/README.md](audits/2026-09-23/README.md).

## P0-1 — Isolasi data per program studi

**Status:** terbuka — blocker produksi.

Sebagian controller admin-prodi masih menerima ID prodi, mata kuliah, CPL/CPMK, kelas, dosen, atau mahasiswa tanpa scope terpusat terhadap `auth()->user()->prodi_id`. Pemeriksaan role saja belum mencegah IDOR lintas prodi.

Selesai apabila:

- admin-prodi hanya dapat membaca dan mengubah objek prodinya;
- admin global mempunyai pengecualian eksplisit dan teruji;
- seluruh query daftar, detail, mutasi, QR/barcode, import, print, dan export memakai scope yang sama;
- relasi pada payload juga harus berada dalam prodi yang diizinkan;
- regression test mencakup ID route dan payload lintas prodi untuk setiap controller terkait;
- percobaan lintas prodi menghasilkan 403 atau 404 tanpa membocorkan data.

## P0-2 — Migrasi domain pembelajaran dari session

**Status:** terbuka — blocker produksi multi-user.

Course preview, materi, diskusi, submission, dan metadata berkas belum seluruhnya menjadi model database. State sesi tidak cukup untuk kolaborasi, audit, retensi, atau ownership lintas perangkat.

Selesai apabila:

- course, content item, discussion, submission, attachment, dan status pengerjaan mempunyai skema serta relasi database;
- setiap akses memakai policy enrollment/teaching assignment/ownership;
- file privat mempunyai metadata owner, relasi domain, masa retensi, dan cleanup job;
- data preview dapat dimigrasikan atau dipisahkan tegas dari mode operasional;
- tes mencakup dua pengguna, dua kelas, session berbeda, tebakan UUID/ID, dan akses setelah enrollment dicabut;
- tidak ada controller operasional yang memakai `LearningPreview`, `SubmissionPreview`, atau session sebagai source of truth.

## P1

- Area admin institusi utama masih memakai state preview sesi.
- Belum ada CI repository yang memaksa test, build, formatter, dan dependency audit.
- Cleanup terjadwal berkas preview belum tersedia.
- Baseline Pint seluruh file lama belum dinormalisasi dalam satu perubahan terpisah.

