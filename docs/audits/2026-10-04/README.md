# Audit Menyeluruh SALE — 4 Oktober 2026

## Keputusan rilis

**Belum layak dipasang ke internet atau memakai data akademik nyata.** Build frontend dan unit test JavaScript lulus, tetapi masih ada dua celah isolasi data Critical, beberapa risiko integritas akademik High, advisory dependency High, serta pipeline test PHP yang belum dapat menjadi release gate yang andal.

Audit ini dilakukan pada commit `b07d6530866541941b867e4aa7446004ffd74987`. Working tree bersih saat audit dimulai. Pemeriksaan mencakup route, middleware, policy, controller, model, migration, Blade/JavaScript, penyimpanan berkas, chat/Reverb, AI, backup/restore, alur akademik, dependency, build, formatter, dan test suite. Audit tidak mengubah database development/production dan tidak menjalankan restore/backup nyata.

## Ringkasan

| Tingkat | Jumlah | Makna |
| --- | ---: | --- |
| Critical | 2 | Pengguna berotorisasi rendah dapat melewati batas kelas/prodi dan membaca data privat |
| High | 8 | Integritas akademik, kredensial, autentikasi, atau konsistensi transaksi berisiko serius |
| Medium | 9 | Performa, lifecycle data, hardening, dan operasional belum memadai |
| Low/quality | 4 | Maintainability, dokumentasi, dan hygiene rilis |

## Temuan Critical

### C-01 — Semua dosen dapat berlangganan channel realtime kelas mana pun

Authorization HTTP chat sudah memeriksa pengampu/enrollment, tetapi authorization channel Reverb berbeda. Callback `room.{roomId}` mengizinkan setiap akun yang mempunyai role `dosen`, walaupun bukan anggota room atau pengampu kelas tersebut.

**Bukti:** `routes/channels.php:7-19`; khususnya kondisi `|| $user->hasRole('dosen')` pada baris 18. Payload event membawa isi pesan, identitas pengirim, reply, dan mention (`app/Events/MessageSent.php:23-35`, `app/Models/Message.php:64-111`).

**Dampak:** dosen yang mengetahui/menebak ID room dapat menerima percakapan kelas lain melalui private WebSocket channel. Ini melanggar isolasi kelas walaupun endpoint REST sudah aman.

**Perbaikan:** gunakan satu policy yang sama dengan `ChatController::canAccessCourse`; channel hanya menerima room member aktif atau dosen yang `can('manage', $section)`. Tambahkan test subscribe untuk dosen pengampu, dosen luar kelas, mahasiswa enrolled, mahasiswa dropped/kicked, dan admin.

### C-02 — Admin Prodi dapat membuka attachment semua prodi

Endpoint file menerima role `admin_prodi`, UUID, dan juga ID numerik. `authorizeAttachmentAccess()` langsung mengizinkan setiap admin prodi tanpa membandingkan prodi attachment/assessment/class section dengan prodi yang dikelola.

**Bukti:** route `routes/web.php:81`; lookup UUID atau ID `app/Http/Controllers/LearningController.php:2516-2529`; bypass admin prodi `app/Http/Controllers/LearningController.php:2660-2664`.

**Dampak:** admin prodi dapat mengiterasi ID attachment dan mengunduh berkas tugas/submission mahasiswa prodi lain. UUID tidak membantu karena endpoint juga menerima primary key numerik.

**Perbaikan:** hapus lookup berdasarkan ID publik; gunakan UUID saja. Admin global boleh eksplisit, sedangkan admin prodi wajib melewati scope `class_section -> mata_kuliah -> prodi`. Attachment tanpa relasi lengkap harus fail closed, bukan bergantung pada heuristik JSON/LIKE.

## Temuan High

### H-01 — Reply dan mention chat tidak dibatasi ke room yang sedang dibuka

Validasi hanya memakai `exists:messages,id` dan `exists:users,id`. Server tidak memastikan pesan yang dibalas berada pada room yang sama dan user yang di-mention adalah anggota room. Reply lintas room kemudian dirender dengan nama pengirim serta cuplikan isi pesan asal.

**Bukti:** `app/Http/Controllers/ChatController.php:169-174`, penyimpanan langsung pada baris 194-200, mention/notifikasi pada 216-240, serta ekspose excerpt pada `app/Models/Message.php:75-82`.

**Dampak:** anggota kelas A dapat menghubungkan pesan kelas B bila mengetahui ID, membocorkan cuplikan pesan/identitas ke kelas A, dan mengirim notifikasi chat kepada akun yang bukan anggota kelas.

**Perbaikan:** query reply melalui `$room->messages()->findOrFail(...)`; intersect mention IDs dengan member aktif room; gunakan transaksi untuk message, mentions, dan notifications.

### H-02 — API key AI disimpan plaintext dan dikirim tanpa verifikasi TLS

Kunci API disimpan langsung pada `system_settings.value`. Pengambilan daftar model dan fitur uji koneksi memakai `Http::withoutVerifying()` untuk Google, OpenAI, dan DeepSeek.

**Bukti:** penyimpanan plaintext `app/Http/Controllers/AdminPreviewController.php:725,737-746,857-864`; pembacaan langsung `app/Models/SystemSetting.php:18-20`; TLS dimatikan pada `app/Services/Ai/AiModelFetcher.php:83-150` dan `app/Http/Controllers/AdminPreviewController.php:866-977`.

**Dampak:** pembaca database/backup memperoleh credential provider; koneksi yang disadap dapat mencuri key atau memalsukan daftar/respons model.

**Perbaikan:** aktifkan verifikasi CA; jangan memakai `withoutVerifying()` di semua environment. Simpan secret di secret manager/environment, atau minimal encrypted cast dengan strategi rotasi. Jangan ekspor secret ke backup/report/log.

### H-03 — Dosen dapat melewati seluruh setup akademik dan membuat master data otomatis

Route dosen menyediakan “Tambah Course”. Saat submit, controller memilih prodi pertama atau membuat prodi `IF`, membuat/memakai mata kuliah hanya berdasarkan kode global, lalu membuat semester tetap `2026-1` bila semester aktif belum ada.

**Bukti:** `routes/web.php:95-96`; `app/Http/Controllers/LearningController.php:774-861`, terutama baris 807-824.

**Dampak:** tata kelola Admin Prodi dapat dilewati; semester dan prodi palsu tercipta; dosen prodi B dapat memakai mata kuliah berkode sama milik prodi A; kelas dapat terbentuk tanpa CPL/CPMK. Laporan OBE dan tenant menjadi tidak dapat dipercaya.

**Perbaikan:** hapus auto-provision master data dari request dosen. Dosen hanya boleh membuat ruang course untuk `ClassSection` yang sudah ditugaskan kepadanya, atau hilangkan tombol create dan jadikan Admin Prodi satu-satunya pembuat kelas. Bila self-service memang produk yang diinginkan, buat workflow proposal/approval eksplisit.

### H-04 — Kontrak unik mata kuliah bertentangan dengan validasi aplikasi

Database menetapkan `mata_kuliahs.code` unik global, sedangkan controller Admin Prodi menganggap kode unik per prodi.

**Bukti:** `database/migrations/2026_09_13_165938_create_mata_kuliahs_table.php:16-18`; validasi per-prodi `app/Http/Controllers/AdminProdi/AkademikProdiController.php:72-83,102-118`; lookup dosen global `LearningController.php:812-819`.

**Dampak:** kode mata kuliah yang sah di dua prodi dapat menghasilkan error database/500 atau terhubung ke tenant salah.

**Perbaikan:** tentukan invariant produk. Rekomendasi: unique composite `(prodi_id, code)` dan semua lookup memakai keduanya; mata kuliah lintas prodi perlu model kepemilikan/sharing eksplisit, bukan reuse kebetulan berdasarkan kode.

### H-05 — Login membocorkan status akun dan memungkinkan lockout 5 jam oleh pihak lain

Respons membedakan akun tidak terdaftar, akun nonaktif, password salah, serta jumlah percobaan tersisa. Lima password salah terhadap identifier korban mengunci akun berbasis user ID selama lima jam; throttle route masih 60 request/IP/menit.

**Bukti:** `app/Http/Controllers/AuthController.php:91-103,105-137`; route `routes/web.php:36`.

**Dampak:** enumerasi email/NIM dan denial-of-service akun yang murah, khususnya menjelang ujian.

**Perbaikan:** gunakan pesan generik dan limiter gabungan identifier-hash + IP/device dengan backoff singkat; jangan lock keras lima jam hanya berdasarkan input publik. Sediakan recovery password/MFA dan audit event login.

### H-06 — Submission tidak memiliki constraint idempotensi pada database

Controller melakukan read-then-`updateOrCreate`, tetapi migration hanya membuat index non-unik `(assessment_id, user_id)` dan `(assessment_id, mahasiswa_id)`. Dua request paralel dapat sama-sama tidak melihat row lalu membuat dua submission/versi jawaban.

**Bukti:** `app/Http/Controllers/LearningController.php:2253-2289`; `database/migrations/2026_09_27_000001_create_submissions_table.php:27-28`.

**Dampak:** submission ganda, attempt/version tidak konsisten, penilaian otomatis ganda, dan hasil race saat timeout/autosubmit.

**Perbaikan:** pilih satu identitas owner, tambahkan unique `(assessment_id, mahasiswa_id)` (atau attempt bila multi-attempt nyata), lock row attempt/submission, dan gunakan idempotency key untuk autosubmit.

### H-07 — Dependency dev `promptfoo` membawa 7 advisory High

`npm audit` menemukan tujuh vulnerability High pada rantai `promptfoo@0.123.1`: `basic-ftp`, `get-uri`, `pac-proxy-agent`, `proxy-agent`, `jks-js`, dan `node-forge`. Tidak ada Critical. Composer audit bersih.

**Dampak:** terutama mengenai tool evaluasi/development, bukan bundle browser produksi, tetapi berisiko pada workstation/CI yang menjalankan promptfoo dan memproses proxy/JKS/input eksternal.

**Perbaikan:** isolasikan promptfoo dari dependency aplikasi (workspace/container dev), evaluasi versi aman yang kompatibel, lalu ulang `npm audit`. Jangan menerapkan saran downgrade otomatis tanpa regression test.

### H-08 — Guard akun demo AI memakai OR, bukan AND

Kontrak mode demo di README, `AuthController`, dan `DatabaseSeeder` mengharuskan `SALE_DEMO_MODE=true` **dan** environment local/testing. `AiTutorController` justru mengaktifkan auto-provision akun `demo.ai@sale.test` dengan password publik ketika salah satu kondisi benar.

**Bukti:** `app/Http/Controllers/AiTutorController.php:41-59`; pembanding yang benar ada di `app/Http/Controllers/AuthController.php:167-170` dan `database/seeders/DatabaseSeeder.php:16-18`. `.env.example` menetapkan `SALE_DEMO_MODE=true`.

**Dampak:** deployment ber-`APP_ENV=production` yang menyalin flag demo dari contoh tanpa mengubahnya tetap dapat membuat akun demo dan grant akses AI memakai kredensial yang diketahui publik. Di environment testing, mematikan flag demo juga tidak benar-benar mematikan jalur ini.

**Perbaikan:** gunakan `config('app.demo_mode') && app()->environment(['local', 'testing'])`, hapus auto-provision dari request web, dan pindahkan provisioning ke seeder/command lokal. Tambahkan regression test untuk keempat kombinasi flag × environment.

## Temuan Medium

### M-01 — Backup/restore dapat memberikan status sukses palsu dan credential DB terlihat di process list

Restore upload tidak memvalidasi ekstensi, MIME, ukuran, signature, atau provenance. Exit code proses tidak diperiksa, tetapi UI selalu mengatakan restore berhasil. Password database diberikan sebagai argumen CLI.

**Bukti:** `app/Http/Controllers/AdminPreviewController.php:1558-1618`; pola serupa pada create/download backup baris 1454-1555.

**Perbaikan:** validasi `.sql` dan batas ukuran; staging upload privat; verifikasi checksum/signature; preflight dan backup otomatis; cek exit code serta stderr; gunakan option file sementara berizin 0600 atau environment yang didukung client; restore harus job maintenance dengan audit immutable.

### M-02 — Direktori backup dapat diarahkan ke hampir semua absolute path

Admin dapat menyimpan path absolut apa pun selain yang terdeteksi sebagai public. Direktori dibuat 0755, dan fitur download/delete bekerja pada basename di direktori itu.

**Bukti:** `AdminPreviewController.php:1418-1428,1621-1682`.

**Perbaikan:** allowlist satu root backup di luar web root; canonicalize dengan `realpath` parent dan boundary-aware comparison; direktori 0700, file 0600; jangan izinkan path arbitrary dari UI.

### M-03 — Endpoint status realtime menghitung notifikasi berulang dan mahal

Satu request `/live-status` memanggil `forUser()` langsung, lalu dapat memanggilnya lagi melalui `unreadCount()` untuk workspace dosen dan mahasiswa. `forUser()` membangun notifikasi dari beberapa query domain; polling berkala mengalikan beban.

**Bukti:** `LearningController.php:2954-3005`; `DatabaseNotificationService.php:25-87`.

**Perbaikan:** hitung sekali per role/request, cache counter atau simpan event notifikasi persisten, dan dorong perubahan lewat Reverb. Tambahkan query-count assertion dan load test dengan dataset realistis.

### M-04 — Query database berada di Blade dan menghasilkan N+1

Course card memuat assessment per kartu dan mengecek submission per item. View course/item/coding juga menjalankan banyak query sendiri.

**Bukti:** `resources/views/learning/partials/course-card.blade.php:8-68`, `resources/views/learning/course.blade.php:67,448-522`, `resources/views/learning/item.blade.php:26-104,711-719,819`, `resources/views/mahasiswa/assignment-code.blade.php:161-186,299,366-369,542`.

**Perbaikan:** semua query pindah ke controller/query service; eager-load assessment/submission/score/count; view hanya menerima DTO/view model. Tetapkan budget query per halaman.

### M-05 — Side effect terjadi saat membaca course

`LearningPreview::databaseCourse()` melakukan update untuk meng-unpin materi lama saat sekadar membentuk response GET.

**Bukti:** `app/Support/LearningPreview.php:235-258`.

**Dampak:** GET tidak idempotent, membuka race dan write amplification pada halaman populer.

**Perbaikan:** enforce satu pinned item melalui service/transaksi saat pin dilakukan dan constraint/invariant yang jelas; reader tidak boleh melakukan update.

### M-06 — File fisik orphan dan retensi data belum ditangani

Menghapus assessment/submission menghapus row attachment tetapi tidak menghapus file dari disk. Upload juga terjadi sebelum transaksi domain selesai, sehingga error setelah upload meninggalkan file/row orphan.

**Bukti:** upload `LearningController.php:2488-2513`; delete row-only `LearningController.php:1949-1955,2460-2463`.

**Perbaikan:** state `pending/attached`, finalize setelah commit, cleanup job periodik, delete-after-commit, retention policy, dan audit orphan scanner.

### M-07 — CSP belum melindungi eksekusi script/style

Header CSP hanya menetapkan `base-uri`, `form-action`, `object-src`, dan `frame-ancestors`; tidak ada `default-src`, `script-src`, `style-src`, `connect-src`, atau nonce.

**Bukti:** `app/Http/Middleware/SecurityHeaders.php:15-29`.

**Perbaikan:** inventaris inline script/style, migrasikan ke asset/module, lalu terapkan CSP nonce/hash dan allowlist koneksi Reverb/provider yang minimal. Rollout awal dapat memakai Report-Only.

### M-08 — Mutasi “mark notification read” menerima GET

Route mahasiswa dan dosen memakai `Route::match(['get','post'], ...)`, sedangkan controller menulis state notifikasi/session.

**Bukti:** `routes/web.php:77,166`; `LearningController.php:3027-3038`.

**Perbaikan:** POST/PATCH saja dengan CSRF; link GET menuju halaman target, lalu client mengirim mutasi terpisah.

### M-09 — Endpoint evaluasi AI memakai token contoh publik tanpa rate limit

`/internal/ai-eval` tidak memakai auth/CSRF dan hanya dinonaktifkan bila environment persis `production`. Token default yang didokumentasikan di `.env.example` adalah nilai tetap, dan route tidak mempunyai throttle. Endpoint dapat membaca konteks assessment berdasarkan ID dan memanggil provider AI.

**Bukti:** `routes/web.php:213`; `bootstrap/app.php:25-27`; `app/Http/Controllers/AiEvalController.php:19-63`; `.env.example:106`.

**Dampak:** staging/local yang dapat dijangkau jaringan dan masih memakai token contoh dapat dipakai untuk menghabiskan biaya API serta menguji konteks assessment tanpa akun.

**Perbaikan:** pindahkan ke command/queue internal, atau wajibkan auth admin + token acak berentropy tinggi + throttle/IP allowlist. Default token harus kosong; fail closed untuk seluruh environment selain allowlist eksplisit.

## Low / kualitas dan maintainability

### L-01 — Source utama terlalu besar dan bercampur tanggung jawab

`LearningController` 3.172 baris, `AdminPreviewController` 1.689, `InputNilaiController` 1.465, `ObeExcelExportService` 2.036, dan `resources/js/app.js` 4.737. Ini meningkatkan kemungkinan regresi authorization/transaction dan menyulitkan review.

### L-02 — Kode mati preview masih tertinggal

`LearningPreview::notifications()` return pada baris 769-772 tetapi masih menyimpan ratusan baris implementasi legacy yang tidak terjangkau. Dokumentasi arsitektur juga masih menyatakan submission/course operasional berbasis session walaupun sebagian besar sudah dipindah ke database.

### L-03 — Formatter gagal luas

`vendor/bin/pint --test` gagal pada puluhan controller, model, service, migration, dan test. Ini bukan celah langsung, tetapi mengurangi signal review dan konsistensi diff.

### L-04 — Migration baru hanya berjalan pada MySQL

Migration `2026_09_29_150000...` memakai `UPDATE ... JOIN`, `INSERT IGNORE`, dan `NOW()` secara langsung (`baris 22,37`). Seluruh test SQLite gagal sebelum assertion karena sintaks tersebut. Jika MySQL adalah satu-satunya target produksi, tetap sediakan jalur test MySQL yang reproducible; bila SQLite didukung untuk test, buat migration driver-aware.

## Audit alur bisnis dan prasyarat sistem

### Alur yang semestinya dijadikan satu-satunya source of truth

1. **Admin sistem** membuat/menetapkan prodi, semester aktif, akun Admin Prodi, dan konfigurasi institusi.
2. **Admin Prodi** memilih prodi kelolaan, membuat CPL, CPMK, relasi CPL–CPMK, mata kuliah, serta mapping mata kuliah–CPMK.
3. **Admin Prodi** membuat `ClassSection` untuk mata kuliah + semester, menetapkan kapasitas dan dosen ketua/anggota.
4. **Mahasiswa** dibuat/import dengan prodi dan angkatan, lalu masuk memakai enrollment code atau assignment terverifikasi.
5. **Dosen yang ditugaskan** membuat materi/asesmen pada kelas itu, menetapkan status, deadline, mode, rubrik, bobot akhir, dan kontribusi CPMK.
6. Total bobot asesmen gradable harus 100% sebelum nilai akhir/rekap dianggap final.
7. Mahasiswa enrolled mengerjakan; attempt bertimer dibuat server-side; submission dan attachment tersimpan persisten.
8. Dosen menilai, nilai berstatus final/published, lalu OBE/rekap/export menggunakan kelas dan semester yang sama.
9. Akhir semester: kelas diarsipkan read-only; riwayat peserta, submission, nilai, dan appeal dipertahankan sesuai retensi.

### Gap alur saat ini

| Gap | Kondisi aktual | Keputusan yang dibutuhkan |
| --- | --- | --- |
| Pembuatan kelas | Admin Prodi dan dosen sama-sama dapat mencipta, tetapi jalurnya berbeda | Tetapkan satu owner proses; rekomendasi Admin Prodi |
| Prasyarat kurikulum | Dosen dapat membuat kelas tanpa CPL/CPMK | Blok publish asesmen/kelas resmi sampai mapping minimum lengkap |
| Semester | Jalur dosen membuat semester hard-coded bila kosong | Semester hanya dari Admin; tidak boleh auto-create |
| Kode mata kuliah | DB global unique, UI per-prodi | Ubah ke composite unique atau definisikan katalog global formal |
| Bobot | UI memberi pesan bila belum 100%, tetapi komponen 0% dapat dibuat cepat | Tambahkan status `setup/incomplete/ready`; blok finalisasi, bukan sekadar notice |
| Attachment | Relasi baru sudah ada, tetapi fallback heuristik/session tetap aktif | Migrasikan data legacy lalu hapus fallback operasional |
| Notifikasi | Dihitung ulang dari data domain saat polling | Persist event notification/counter agar stabil dan murah |
| Dokumentasi | `architecture.md` dan `known-limitations.md` tertinggal | Perbarui setelah keputusan domain final |

## Verifikasi otomatis

| Pemeriksaan | Hasil |
| --- | --- |
| Commit | `b07d6530866541941b867e4aa7446004ffd74987` |
| Inventaris route | 190 route |
| `composer validate --strict` | Lulus |
| `composer audit --locked` | Lulus; tidak ada advisory |
| `npm audit` | Gagal; 7 High, seluruhnya pada rantai dev `promptfoo` |
| JavaScript tests | Lulus: 4/4 |
| Production build | Lulus; app JS utama 215,43 kB (61,82 kB gzip), CSS 144,65 kB (23,55 kB gzip) |
| Pyodide public runtime | Sekitar 13 MB; lazy-load wajib dipertahankan |
| Pint | Gagal pada banyak file |
| PHP test run 1 | Berjalan sebagian lalu fatal pada memory limit 128 MB saat menulis XLSX |
| PHP test run 2 (512 MB) | Tidak valid untuk aplikasi: MySQL `test_sale` tidak dapat dihubungi; 12 pass, 464 fail karena koneksi |
| Audit regression lama via SQLite | Tidak dapat berjalan: migration MySQL-only gagal sebelum assertion |

Jangan membaca angka 464 sebagai 464 bug aplikasi; itu satu kegagalan infrastruktur yang mengaskade. Namun kondisi ini tetap berarti test PHP belum dapat dijadikan bukti kelayakan rilis pada workstation ini.

## Hal yang membaik sejak audit 27 September

Review kode menunjukkan perbaikan nyata: endpoint chat HTTP sekarang memakai role + access check, fallback `User::first()` hilang, `ClassSectionPolicy` mencakup dosen ketua/pendamping/anggota, admin-prodi memiliki helper scope, submission/answer/attachment/attempt sudah memiliki model database, status asesmen dicek saat submit, timer server dan transaksi grading tersedia, formula spreadsheet diuji, dan redirect notifikasi memakai validasi internal. Temuan baru di laporan ini terutama berada pada celah antar-lapisan yang belum memakai policy yang sama dan pada dua workflow akademik yang saling bertentangan.

## Prioritas remediasi

### 0–24 jam

1. Tutup C-01 dan C-02; tambahkan regression test negatif lintas kelas/prodi.
2. Scope reply/mention chat ke room yang sama.
3. Hapus semua `withoutVerifying()` dan rotasi API key bila fitur uji/model fetch pernah dipakai pada jaringan tidak tepercaya.
4. Tutup H-08 dan hapus/nonaktifkan akun `demo.ai@sale.test` di semua database non-lokal.
5. Nonaktifkan jalur “Tambah Course” dosen sampai keputusan workflow H-03/H-04 selesai.

### 2–7 hari

1. Perbaiki autentikasi/lockout, constraint submission, dan schema kode mata kuliah.
2. Perbaiki backup/restore serta penyimpanan secret.
3. Stabilkan environment test MySQL dan memory export, lalu wajibkan seluruh suite hijau.
4. Pisahkan promptfoo dan selesaikan advisory npm.

### 2–4 minggu

1. Pindahkan query dari Blade, optimalkan `/live-status`, dan tetapkan budget query/p95.
2. Pecah controller/service besar berdasarkan bounded context.
3. Selesaikan migrasi/fallback preview, lifecycle attachment, CSP, dan dokumentasi.
4. Jalankan penetration test dan load test pada staging dengan data sintetis realistis setelah seluruh Critical/High ditutup.

## Kriteria siap produksi

- Tidak ada Critical/High terbuka dan seluruh reproduksi negatif lulus.
- Full PHP suite, JS suite, build, Pint, Composer audit, dan npm audit menjadi gate CI hijau.
- Restore backup diuji pada database disposable dan hasilnya diverifikasi.
- Semua akses objek diuji untuk guest, role salah, tenant lain, kelas lain, user dropped/kicked, dan kelas archived.
- Tidak ada secret plaintext di database/backup/log dan TLS provider selalu diverifikasi.
- Workflow kelas tunggal terdokumentasi serta dipaksa server-side.
- Load test staging memenuhi target terukur (p95 latency, error rate, query count, CPU/memori) yang disepakati.

Audit source dan test lokal tidak menggantikan pentest deployment: konfigurasi reverse proxy, HTTPS, database grants, object storage, queue worker, Reverb, backup host, monitoring, dan secret manager harus diaudit lagi pada staging/production.
