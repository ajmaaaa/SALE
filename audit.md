# Audit Keamanan SALE — 27 September 2026

## Status

**Jangan pasang sistem ini ke internet atau memakai data mahasiswa nyata sebelum temuan Critical dan High di bawah diperbaiki.** Audit menemukan jalur yang dapat membuka percakapan kelas, berkas privat, akun, dan nilai kepada pihak yang tidak berhak.

Audit dilakukan pada commit `1c86a5652bebaf79110a2a0ccec30a5a91cb6072`. Temuan diuji pada database SQLite sementara. Tidak ada data development atau produksi yang diubah.

## Ringkasan risiko

| Tingkat | Jumlah kelompok | Kondisi |
| --- | ---: | --- |
| Critical | 5 | Ada jalur akses data atau perubahan data tanpa otorisasi objek yang benar |
| High | 9 | Integritas nilai, kuis, submission, dan sesi dapat dirusak atau dilewati |
| Medium | 6 | Data contoh, route lama, konfigurasi, dan konsistensi fitur berisiko menyesatkan pengguna |

## Temuan Critical

### C-01 — Chat dapat dibaca dan dikirim tanpa login

Route `/chat/course/{course}/messages` dan `/chat/course/{course}/members` tidak memakai middleware autentikasi. `ChatController::currentUser()` memilih pengguna pertama dari database ketika request tidak memiliki pengguna. Akibatnya, pengunjung tanpa login dapat:

- membaca pesan dan anggota ruang kelas;
- mengirim pesan memakai identitas akun lain;
- otomatis dimasukkan sebagai anggota room;
- menebak ID course untuk membuka room lain.

**Bukti:** reproduksi A01 dan A02 lulus.

**Perbaikan wajib:** pasang `auth` dan pemeriksaan enrollment/pengampu pada semua endpoint chat. Hapus seluruh fallback `User::first()` dan auto-membership. Room hanya boleh dibuat atau dibuka setelah policy kelas menyetujui pengguna.

### C-02 — Dosen yang bukan pengampu dapat mengubah kelas dan nilai

Sebagian route pembelajaran hanya memeriksa role `dosen`. Beberapa fungsi memakai `LearningPreview::course()` sebagai pemeriksaan “ownership”, padahal fungsi itu hanya memastikan course tersedia. Dosen lain dapat:

- membuat konten pada kelas yang bukan miliknya;
- memberi nilai kepada pengguna yang tidak terdaftar;
- mengambil alih kelas tanpa dosen hanya dengan membuka URL course melalui request GET;
- memoderasi, menyematkan, atau menghapus pesan kelas lain karena policy chat menerima semua akun ber-role dosen.

**Bukti:** reproduksi A03, A08, A09, dan A10 lulus.

**Perbaikan wajib:** buat policy terpusat untuk `ClassSection`, `Assessment`, `Submission`, dan `Room`. Setiap operasi harus memeriksa `dosen_id` atau `dosen_pendamping_id`. Jangan melakukan assignment dosen melalui GET.

### C-03 — Admin prodi dapat mengelola data prodi lain

Middleware `admin_prodi.auth` hanya memeriksa role. Controller admin-prodi menampilkan seluruh prodi dan menerima `prodi_id` atau route model binding tanpa membandingkannya dengan `auth()->user()->prodi_id`. Admin prodi dapat mengubah password, pengguna, mata kuliah, kelas, CPL, CPMK, mapping, laporan, dan kode enrollment prodi lain.

**Bukti:** reproduksi A05 membuktikan admin prodi dapat mengganti password mahasiswa pada prodi lain.

**Perbaikan wajib:** terapkan scope prodi pada seluruh query baca, tulis, import, export, print, QR, dan barcode. Admin global harus menjadi satu-satunya pengecualian eksplisit. Tebakan ID lintas prodi harus menghasilkan 403 atau 404.

### C-04 — Berkas privat dapat dibuka tanpa hubungan dengan kelas

Endpoint `/preview/files/{uuid}` hanya memeriksa role mahasiswa/dosen dan keberadaan metadata file. Metadata dapat dicari dari seluruh `assessment.learning_payload`. Tidak ada pemeriksaan bahwa pengguna terdaftar pada kelas, pengampu kelas, pemilik submission, atau penerima berkas.

**Dampak:** pengguna yang memperoleh atau menebak UUID dapat mengunduh lampiran kelas lain.

**Bukti:** reproduksi A07 lulus untuk mahasiswa yang tidak terdaftar.

**Perbaikan wajib:** buat tabel attachment dengan relasi owner, class section, assessment, dan submission. Gunakan policy sebelum mengirim file. UUID tidak boleh dianggap sebagai kontrol akses.

### C-05 — Akun AI demo dapat dibuat saat mode demo mati

POST `/ai/login` memiliki kredensial tetap `demo.ai@sale.test` / `password123456`. Controller membuat akun serta memberikan akses tugas walaupun `SALE_DEMO_MODE=false`. Endpoint ini juga berada di luar login utama aplikasi.

**Dampak:** akun yang diketahui publik dapat dibuat dan dipakai pada lingkungan operasional.

**Bukti:** reproduksi A04 lulus dengan mode demo dimatikan.

**Perbaikan wajib:** hapus auto-provision dari request web. Pembuatan akun dan grant akses hanya melalui proses admin/CLI yang diaudit. Cabang demo wajib memeriksa `SALE_DEMO_MODE=true` dan environment local/testing.

## Temuan High

### H-01 — Renderer chat memasukkan data pengguna sebagai HTML mentah

Pesan yang disematkan serta kutipan reply dimasukkan ke `innerHTML` tanpa escaping lengkap. Ini membuka risiko stored XSS: script atau event HTML dapat berjalan di browser pengguna lain jika payload berbahaya tersimpan.

**Bukti:** `chat-render-reproduction.mjs` membuktikan markup pengguna mencapai `innerHTML`. Eksekusi payload pada browser nyata belum diuji.

**Perbaikan wajib:** bangun elemen dengan `textContent`; jika rich text dibutuhkan, sanitasi dengan allowlist yang ketat. Jangan menyisipkan `author`, `content`, atau `reply excerpt` ke template HTML.

### H-02 — Submission tidak persisten dan hilang bersama sesi

Jawaban, lampiran, dan metadata submission disimpan pada `session("learning.submissions...")`. Database hanya menyimpan baris nilai dengan `score=null`. Setelah sesi habis, logout, ganti browser, atau cleanup, dosen tidak lagi memiliki jawaban mahasiswa untuk dinilai.

**Bukti:** reproduksi A17 lulus.

**Perbaikan wajib:** buat tabel `submissions`, `submission_answers`, dan `attachments`; simpan attempt, waktu kirim, status, owner, dan versi jawaban dalam transaksi.

### H-03 — Mahasiswa dapat submit ulang dan mengosongkan nilai yang sudah diberikan

Endpoint submit tetap menerima jawaban setelah `StudentAssessmentScore` berisi nilai. Untuk tugas biasa, submit ulang menjalankan `updateOrCreate(['score' => null])` tetapi membiarkan `graded_at` lama.

**Bukti:** reproduksi A13 lulus.

**Perbaikan wajib:** kunci submission setelah dinilai, atau buat mekanisme resubmit eksplisit yang membuat versi baru dan membatalkan nilai melalui tindakan dosen yang tercatat.

### H-04 — Asesmen berstatus closed masih menerima jawaban

Proses submit tidak memeriksa `assessment.status`. Status `closed` masih dapat dikirimi jawaban selama aturan tanggal mengizinkan.

**Bukti:** reproduksi A14 lulus.

**Perbaikan wajib:** hanya `published` yang boleh dibuka dan disubmit; `draft` dan `closed` harus ditolak oleh server.

### H-05 — Durasi kuis hanya dijaga oleh browser

Timer disimpan di `localStorage`/`sessionStorage`. Server tidak menyimpan waktu mulai atau batas attempt. Request submit langsung tetap diterima setelah durasi berlalu.

**Bukti:** reproduksi A22 lulus setelah waktu uji dimajukan melewati durasi.

**Perbaikan wajib:** simpan attempt dan deadline di database berdasarkan waktu server. Tolak submit setelah deadline dan catat alasan penolakan.

### H-06 — Kunci jawaban dan pengacakan kuis tidak konsisten

Form pembuat soal menyimpan kunci pilihan sebagai huruf (`A`, `B`), sedangkan pemeriksa membandingkannya dengan teks pilihan. Kunci benar/salah disimpan pada `boolean_answer`, tetapi pemeriksa membaca `correct_answer`. Jalur evaluasi dosen juga menganggap pilihan pertama sebagai kunci, dan jawaban mencocokkan dianggap benar selama terisi. Pengacakan mengubah urutan pertanyaan yang tampil tetapi submit dinilai memakai indeks urutan asli.

**Dampak:** jawaban benar dapat dinilai salah, jawaban salah dapat dinilai benar, dan nilai berubah saat randomisasi aktif.

**Bukti:** reproduksi A24, A25, A26, dan A27 lulus.

**Perbaikan wajib:** gunakan ID pertanyaan dan ID opsi stabil. Simpan kunci dalam satu format kanonik. Server harus menilai berdasarkan ID, bukan urutan array atau teks tampilan.

### H-07 — Submit kuis dapat menyimpan nilai sebagian lalu menghasilkan error 500

`LearningController` memakai `StudentAssessmentCpmkScore` tanpa import namespace. Nilai total dapat tersimpan sebelum PHP melempar error ketika menyimpan breakdown CPMK. Proses tidak dibungkus transaksi.

**Bukti:** reproduksi A11 menunjukkan nilai total 100 tersimpan, breakdown kosong, dan request gagal.

**Perbaikan wajib:** perbaiki import, bungkus submission dan seluruh kalkulasi nilai dalam satu transaksi, lalu tambahkan regression test rollback.

### H-08 — Nilai parsial dianggap selesai dan muncul kepada mahasiswa

- Esai dengan `score=null` sudah dapat masuk tab nilai karena keberadaan row dianggap nilai.
- Satu CPMK yang diisi pada asesmen multi-CPMK langsung menghasilkan nilai total dan `graded_at`, walau CPMK lain kosong.
- Nilai assessment dan nilai CPMK dapat berbeda karena jalur input langsung tidak selalu menyinkronkan breakdown.
- Input rubrik dari jalur input nilai menerima skor sampai 100 walaupun `max_score` kriteria hanya 10, sehingga nilai akhir dapat menjadi 1000.

**Bukti:** reproduksi A12, A18, A23, dan A33 lulus.

**Perbaikan wajib:** tambahkan status nilai `pending`, `partial`, `final`, dan `published`. Mahasiswa hanya melihat nilai `published`. Satu service harus menghitung dan menyinkronkan semua nilai dengan validasi batas kriteria.

### H-09 — Formula spreadsheet dapat disisipkan melalui nama pengguna

Ekspor XLSX menulis nama mahasiswa dengan `setCellValue`, sehingga nama seperti `=1+1` disimpan sebagai formula. CSV sudah memiliki sanitasi, tetapi XLSX belum konsisten.

**Bukti:** reproduksi A31 menemukan elemen formula pada XML workbook.

**Perbaikan wajib:** tulis seluruh teks pengguna dengan `setCellValueExplicit(..., TYPE_STRING)` dan sanitasi awalan `=`, `+`, `-`, `@`, tab, serta carriage return.

## Temuan Medium dan anomali fitur

### M-01 — Password awal bersama dan mudah ditebak

Pembuatan serta import pengguna memakai `password123` bila password kosong. Validasi password admin-prodi juga hanya minimal 6 karakter.

**Bukti:** reproduksi A06 lulus.

**Perbaikan:** gunakan password acak sekali pakai atau tautan aktivasi, minimal 12 karakter, dan wajib ganti saat login pertama.

### M-02 — Redirect notifikasi dapat menuju host luar

Validasi target memakai pemeriksaan prefix `str_starts_with($target, url('/'))`. Domain seperti `https://host-aplikasi.attacker.example/...` lolos.

**Bukti:** reproduksi A20 lulus.

**Perbaikan:** hanya terima path relatif internal atau parse URL lalu bandingkan scheme, host, dan port secara tepat.

### M-03 — Konten database dan data preview saling bertabrakan

ID item preview disimpan ke `learning_payload`, lalu dapat menimpa ID assessment database saat dirender. Akibatnya halaman penilaian dapat membuka item contoh yang tidak sesuai. Course, item, nilai preview, dan data database juga dicampur berdasarkan ada/tidaknya data.

**Bukti:** reproduksi A15 lulus dan membuka “Praktikum Binary Tree” untuk assessment yang berbeda.

**Perbaikan:** pisahkan mode preview sepenuhnya dari mode operasional. Jangan simpan ID preview dalam payload persisten. Gunakan ID database sebagai identitas tunggal.

### M-04 — Data contoh muncul pada akun nyata

- Mahasiswa tanpa enrollment melihat daftar tugas contoh.
- Assessment bertipe materi dipetakan sebagai tugas pada halaman assignment.
- Kuis database tanpa pertanyaan otomatis mendapat soal BST contoh.
- Dashboard dan KHS menampilkan IP/IPK, rekomendasi, dan statistik contoh.
- Notifikasi sistem menyatakan sinkronisasi, AI, dan kalender berhasil walaupun tidak bersumber dari event nyata.

**Bukti:** reproduksi A16, A19, dan A21 lulus.

**Perbaikan:** data contoh hanya boleh aktif pada demo mode. Kondisi data kosong harus menampilkan empty state.

### M-05 — Perhitungan OBE dapat memakai kelas lama

`ObeProgressController` meminta skor CPMK tanpa `class_section_id`. Bila mahasiswa mengulang mata kuliah, service memilih kelas enrollment pertama dan dapat menampilkan skor CPMK kelas lama bersama nilai akhir kelas baru.

**Bukti:** reproduksi A28 lulus.

**Perbaikan:** selalu kirim section ID pada kalkulasi CPMK/CPL per kelas dan semester.

### M-06 — Aturan dosen pendamping tidak konsisten

Dosen pendamping dapat mengelola assessment dan input nilai di sebagian controller, tetapi ditolak oleh `RubricController` yang hanya menerima dosen utama.

**Bukti:** reproduksi A29 lulus.

**Perbaikan:** tetapkan hak dosen pendamping dalam satu policy dan gunakan policy yang sama pada semua fitur.

## Integrasi database

| Area | Kondisi saat audit |
| --- | --- |
| User, role, prodi, semester, mata kuliah, kelas, enrollment | Database |
| CPL, CPMK, assessment, rubric, nilai OBE | Database, tetapi beberapa jalur penilaian masih bercampur session |
| Course dan konten | Sebagian database, sebagian `LearningPreview` dan session |
| Submission dan jawaban | Session; belum persisten |
| Lampiran course/submission | File privat, metadata tersebar di session/payload JSON; belum memiliki ownership kuat |
| Diskusi course | Database tersedia, tetapi ada fallback session dan endpoint chat tidak aman |
| Notifikasi | Dibangkitkan saat request dan status baca disimpan di session; bukan sistem notifikasi persisten lengkap |
| Admin institusi `/admin/*` | Session preview; perubahan hilang saat sesi berakhir |
| Admin prodi `/admin-prodi/*` | Database, tetapi isolasi prodi belum ada |
| Profil/password/preferensi | Database |
| Dashboard akademik, IPS/IPK, beberapa laporan | Masih mengandung angka atau fallback contoh |
| AI tutor | Database dan provider eksternal, tetapi auto-provision demo berbahaya |
| Runner kode browser | Lokal di browser; bukan penilaian resmi |

## Route tersembunyi dan kode lama

Inventaris menemukan 158 route. Route yang tidak tampak sebagai navigasi utama bukan otomatis celah, tetapi tetap dapat dipanggil langsung dan harus memiliki authorization sendiri. Area yang perlu dibersihkan:

- tiga controller mahasiswa tidak terhubung ke route: `GradeController`, `DiscussionController`, dan `CourseController`;
- sejumlah view lama masih tersisa, termasuk `mahasiswa/grade.blade.php`, `mahasiswa/course.blade.php`, `mahasiswa/discussion.blade.php`, `dosen/grades.blade.php`, `dosen/asesmen.blade.php`, `dosen/cpmk.blade.php`, dan partial assessment lama;
- `/dosen/penilaian-kelas/{section}/pengaturan` hanya redirect ke matriks;
- route QR/barcode dan beberapa export dapat dibuka langsung walau tidak selalu terlihat di menu;
- `AdminLaporanService` berisi jalur laporan database/preview tetapi tidak dipakai oleh controller saat ini;
- file `dashboard-courses-original.blade.php` sengaja disimpan namun tidak dirender.

Jangan langsung menghapus kandidat tersebut. Pastikan tidak ada tautan dinamis, bookmark operasional, atau kebutuhan migrasi, lalu hapus bersama tes dan route yang terkait.

## Kualitas dan verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| Uji tambahan audit | 33 lulus, 175 assertion; tes ini membuktikan perilaku/bug yang dicatat |
| Test suite proyek | 186 lulus, 15 gagal, 1.484 assertion |
| JavaScript unit test | 4 lulus |
| Production build | Lulus |
| Composer validation | Lulus |
| Composer advisory audit | Tidak ada advisory pada lockfile |
| npm audit | 0 vulnerability dari 169 dependency |
| Pint | Gagal; banyak file belum sesuai format |

Kegagalan test proyek mencakup error submit kuis, perbedaan navigasi/tampilan OBE, route preview yang sekarang membutuhkan autentikasi, dan ekspektasi UI yang sudah tidak sesuai implementasi. Test suite yang merah berarti perubahan tidak boleh dipromosikan ke produksi.

## Tindakan darurat sebelum sistem dibuka kembali

1. Batasi akses aplikasi dari internet sampai C-01 sampai C-05 dan H-01 diperbaiki.
2. Bila aplikasi pernah online, periksa log akses untuk `/chat/*`, `/preview/files/*`, `/ai/login`, `/dosen/course/*`, `/dosen/penilaian/*`, dan `/admin-prodi/*`.
3. Ganti semua password default dan nonaktifkan akun `demo.ai@sale.test` bila ada.
4. Rotasi `APP_KEY`, credential database, Reverb secret, Gemini API key, serta secret lain bila ada indikasi akses tidak sah. Mengganti `APP_KEY` akan memutus sesi lama dan harus dilakukan terencana.
5. Audit perubahan nilai dan akun dari tabel `student_assessment_scores`, `users`, `messages`, serta log web/database. Sistem belum memiliki audit trail lengkap, jadi backup dan log eksternal mungkin diperlukan.
6. Setelah perbaikan, tambah regression test untuk guest, mahasiswa luar kelas, dosen luar kelas, admin prodi lintas tenant, file lintas kelas, dan nilai yang belum dipublikasikan.

## Bukti audit

Bukti teknis tersedia di folder [`evidence`](evidence/):

- `AuditReproductionTest.php` dan `reproductions.txt` — 33 reproduksi terisolasi;
- `chat-render-reproduction.mjs` dan `chat-render.txt` — bukti data chat mencapai HTML mentah;
- `feature-tests.txt` dan `test-failures.json` — hasil test suite utama;
- `routes.csv` dan `inventory.json` — inventaris route, controller, view, dan file besar;
- `composer-audit.json` dan `npm-audit.json` — audit dependency;
- `build.txt`, `js-tests.txt`, `pint.txt`, dan `composer-validate.txt` — pemeriksaan kualitas.

Audit statis dan pengujian lokal tidak dapat menjamin tidak ada celah lain. Penetration test pada deployment staging tetap diperlukan setelah semua temuan Critical dan High ditutup.
