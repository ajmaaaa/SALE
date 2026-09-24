# Arsitektur SALE

## Gambaran sistem

SALE adalah aplikasi monolit Laravel 12 dengan Blade dan JavaScript ES modules. Saat ini terdapat dua jenis sumber data yang harus dibedakan saat mengembangkan fitur:

| Area | Sumber data | Status |
| --- | --- | --- |
| Akun, role, prodi, semester, mata kuliah, kelas, enrollment, CPL/CPMK, asesmen, rubrik, nilai OBE | Database melalui model Eloquent | Persisten |
| Course preview, materi, tugas/kuis preview, diskusi, submission preview, admin institusi preview | `app/Support/*Preview.php` dan session Laravel | Sementara, belum untuk produksi |
| Tutor dan evaluasi AI | Database, `app/Services/Ai`, Gemini API | Opsional dan dikendalikan konfigurasi |
| Latihan Python/web | Browser melalui Web Worker/Pyodide atau iframe sandbox | Bukan penilaian resmi |

Jangan menggabungkan data sesi dan database tanpa menuliskan rencana migrasi serta ownership objeknya. Batas ini adalah sumber risiko arsitektur utama yang masih terbuka.

## Lapisan kode

- `routes/web.php`: kontrak HTTP, method, middleware role, dan rate limit.
- `app/Http/Controllers`: orkestrasi request, validasi, transaksi, dan pemilihan view.
- `app/Http/Middleware`: autentikasi role serta security headers.
- `app/Models`: relasi domain akademik persisten.
- `app/Services`: kalkulasi OBE, laporan, QR, code runner, dan integrasi AI.
- `app/Support`: penyimpanan/pratinjau berbasis sesi; jangan dianggap repository produksi.
- `resources/views`: presentasi Blade; query database tidak boleh dilakukan di view.
- `resources/js`: runner, editor, import nilai, dan interaksi UI tanpa framework SPA.
- `database/migrations` dan `database/seeders`: skema serta data role/demo.
- `tests/Feature` dan `tests/js`: regression test HTTP/domain dan JavaScript.

## Autentikasi dan role

Role tetap adalah `admin`, `admin_prodi`, `kaprodi`, `dosen`, dan `mahasiswa`. `EnsureRole` memeriksa role aktif untuk setiap kelompok route. Mengetik path role lain tidak mengubah role dan harus menghasilkan redirect login atau 403.

Mode demo hanya aktif jika kedua kondisi berikut benar:

```text
SALE_DEMO_MODE=true
APP_ENV=local atau testing
```

Mode demo masih memerlukan persona/role aktif. Pergantian role dan logout memakai POST+CSRF. Di luar mode demo, autentikasi memakai tabel `users`, password hash Laravel, dan session Laravel biasa.

`admin_prodi.auth` masih middleware khusus karena area tersebut mempunyai kompatibilitas dengan akun demo/database. Semua perubahan baru sebaiknya diarahkan ke middleware/policy terpusat dan tidak membuat fallback login di controller.

## Alur domain utama

### Enrollment

1. Mahasiswa membuka GET `/join-kelas/{code}` untuk konfirmasi.
2. POST pada URL yang sama melakukan mutasi.
3. Controller mengunci row kelas dalam transaksi, memeriksa duplikasi dan kapasitas, lalu memakai `syncWithoutDetaching`.

### Penilaian OBE

`ObeCalculationService` adalah satu-satunya sumber rumus nilai asesmen, CPMK, CPL, coverage, dan nilai akhir. Controller dan Blade tidak boleh menduplikasi formula. Nilai `null` berarti belum dinilai dan tidak boleh dianggap nol.

### AI tutor

`GeminiTutor` menangani request tutor; `AiUsageRecorder` mencatat penggunaan provider; `GeminiEvaluationBenchmark` menguji kualitas tanpa mengubah nilai mahasiswa. Endpoint status/kirim membutuhkan pengguna database dan akses tugas eksplisit. Detail konfigurasi ada di [ai-tutor.md](ai-tutor.md).

### Berkas preview

Berkas berada pada disk privat `local`; session menyimpan metadata UUID dan ownership sementara. Ini cocok untuk evaluasi lokal, bukan akses multi-user persisten. Migrasi yang benar membutuhkan tabel metadata, relasi owner/course/submission, policy, retensi, dan cleanup job.

## Aturan perubahan

- Mutasi tidak boleh memakai GET.
- Setiap resource ID dari route atau request harus diperiksa ownership-nya di server.
- Gunakan policy/scope untuk objek database dan jangan hanya menyembunyikan tombol UI.
- Query database berada di controller/service, bukan Blade.
- Export spreadsheet wajib menetralkan nilai yang diawali `=`, `+`, `-`, atau `@`.
- Upload harus membatasi ukuran, MIME, ekstensi, jumlah, lokasi privat, dan cara file disajikan kembali.
- Perubahan rumus OBE wajib memiliki contoh hitung dan regression test.
- Fallback demo harus selalu bergantung pada `SALE_DEMO_MODE` dan environment lokal/testing.

## Batas yang belum selesai

Lihat [known-limitations.md](known-limitations.md). Kedua item P0 di sana harus selesai sebelum penggunaan data akademik nyata.

