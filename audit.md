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

---

# Audit & Verifikasi Perbaikan — 28 September 2026

Laporan ini mendokumentasikan hasil audit presisi dan perbaikan bug sistemik berdasarkan umpan balik pengguna pada siklus perbaikan 28 September 2026. Seluruh perbaikan mempertahankan desain asli sistem SALE tanpa menambahkan elemen visual yang tidak diminta atau mengubah token desain Tailwind yang telah terstandarisasi.

## Koreksi Forensik Sesi Antigravity

Audit ulang terhadap percakapan `524422b6-a256-435b-b312-99690435818f`, artefak langkah Antigravity, riwayat Git, dan working tree menemukan bahwa beberapa view sempat diganti utuh memakai `git checkout HEAD -- ...`. Karena itu, status “desain aman” tidak boleh disimpulkan hanya dari `git diff` akhir.

Perbaikan selektif yang diterapkan:

- memulihkan desain kartu daftar kelas Penilaian/Rekap dari salinan tepat sebelum overwrite;
- menghapus hanya simbol panah pada tombol **Kelola Penilaian**, **Rekap CPMK**, dan **Rekap CPL**;
- mempertahankan isi terbaru halaman asesmen, rekap, dan header ringkas tanpa memasukkan kembali kartu agregat atau tab bernomor dari desain lama;
- menghapus nomor langkah yang masih tersisa pada judul Matriks dan Rekap CPL agar tidak bercampur dengan pola header terbaru;
- memperbaiki tabel rekap agar menampilkan bobot efektif asesmen × CPMK, bukan bobot internal yang dapat terlihat keliru sebagai 100%;
- memperbarui tes UI yang sebelumnya memaksa desain lama sehingga tes mengunci desain terbaru, bukan menyebabkan rollback tampilan produksi.

Verifikasi otomatis terbaru: **230 tes lulus, 2.026 assertion, 0 gagal** (`php artisan test`). Verifikasi ini mencakup alur penilaian, rekap OBE, admin prodi, enrollment, pembelajaran, keamanan, profil, notifikasi, dan submission. Kesesuaian visual lintas ukuran layar tetap harus diperiksa menggunakan checklist browser di bawah.

Catatan keselamatan working tree: terdapat banyak perubahan lintas sesi yang belum di-commit. Jangan menjalankan rollback massal (`git checkout`, `git restore`, atau reset) terhadap direktori/file yang berubah. Gunakan diff per-hunk dan arsip sesi sebagai sumber pemulihan.

## Ringkasan 12 Temuan & Resolusi

### 1. Halaman Login — Split-Screen 50:50 Layar Penuh dengan Desain Asli & Corak Course
- **Masalah:** Halaman login sempat dibuat kartu kecil mengambang, lalu sempat ditambahkan elemen/widget baru yang tidak diminta. Yang diinginkan adalah layout layar penuh (*full screen*) split 50:50 sesuai desain asli dengan panel biru di satu sisi beraksen corak grafis seperti pada card course agar tidak polos.
- **Penyebab:** Penambahan komponen buatan baru (*AI slop*) yang menyimpang dari teks dan struktur asli login SALE.
- **Perbaikan:**
  - Mengembalikan seluruh teks dan tipografi otentik SALE tanpa menambahkan kartu widget tiruan:
    - Sisi Biru (`bg-gradient-to-br from-[#0e2740] via-[#12385b] to-[#1c5384]`, 50% desktop): memuat teks asli *"Portal akademik"*, *"Satu ruang untuk aktivitas perkuliahan."*, *"Akses course, materi, asesmen, dan administrasi sesuai peran akun institusi Anda."*, dan *"Smart Academic Learning Ecosystem"*, diperkaya gradasi biru modern dan efek ambient glow halus serta corak grafis SVG asli cover card course (pohon hierarki modul dan graf simpul) dengan opasitas seimbang.
    - Sisi Form (50% desktop, full mobile): memuat teks asli *"Akun institusi"*, *"Masuk ke SALE"*, *"Gunakan email atau nomor induk yang terdaftar."*, input Email/NIM, toggle visibilitas kata sandi, dan tombol Masuk.
- **Berkas yang Diubah:** `resources/views/auth/login.blade.php`.

### 2. Sinkronisasi Data Dosen & Dropdown Pengampu Kelas
- **Masalah:** Dosen muncul pada dropdown pengampu saat pembuatan kelas/mata kuliah, namun tidak tercantum di tabel Manajemen Pengguna Admin Prodi.
- **Penyebab:** Query pada `UserProdiController@index` memfilter dosen strictly dengan `where('prodi_id', $prodiId)`, sehingga dosen yang kolom `prodi_id`-nya bernilai `NULL` (misalnya akun bawaan seeder) tidak terambil. Sebaliknya, dropdown pengampu mengambil semua dosen dari relasi global.
- **Perbaikan:**
  - Memperbarui query di `UserProdiController` dengan `where(function ($q) use ($prodiId) { $q->where('prodi_id', $prodiId)->orWhereNull('prodi_id'); })`.
  - Memperbarui otorisasi `AdminProdiController::assertUserScope` agar tidak menghasilkan HTTP 403 saat mengelola dosen dengan `prodi_id` null.
  - Menyelaraskan seluruh data dosen dengan `prodi_id` null pada database ke prodi terkait.
- **Berkas yang Diubah:**
  - `app/Http/Controllers/AdminProdi/UserProdiController.php`
  - `app/Http/Controllers/AdminProdi/AdminProdiController.php`

### 3. Card Petunjuk Pengerjaan & Tampilan Pengampu di Item View
- **Masalah:** Tinggi card "Petunjuk Pengerjaan" tidak sejajar dengan card "Pengelolaan Pengampu", terdapat label badge "Dosen" dengan teks pudar yang tidak rapi, dan informasi durasi/deadline ujian bertumpuk sempit.
- **Penyebab:** Card di kolom kiri tidak memiliki kelas `flex-1` dan container grid tidak menggunakan `items-stretch`.
- **Perbaikan:**
  - Menghapus badge teks pudar `<span ...>Dosen</span>` di card pengampu.
  - Menetapkan container grid menjadi `items-stretch`, kolom utama `flex flex-col`, dan card petunjuk `flex-1` sehingga tinggi card otomatis sejajar secara proporsional.
  - Memperbarui box informasi pengerjaan (durasi, jumlah soal, bobot, batas pengerjaan) menjadi responsif menggunakan `bg-canvas/40 p-5 rounded-xl border border-line/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5`.
- **Berkas yang Diubah:** `resources/views/learning/item.blade.php`.

### 4. Responsivitas Mobile — Klik Elemen Terhalang Header Sticky
- **Masalah:** Pada resolusi smartphone (layar sempit), beberapa tab, tombol aksi, dan konten tidak dapat diklik atau tertutup oleh header course.
- **Penyebab:** Header course `#course-header-sticky` dipasang `sticky top-16` secara global di semua breakpoint, sehingga pada mobile menempati porsi layar yang terlalu besar dan lapisan z-index menutupi tombol di bawahnya.
- **Perbaikan:** Mengubah sticky header menjadi `relative lg:sticky lg:top-16`, sehingga pada smartphone header mengalir secara alami (tidak mengambang menutupi tombol) dan fungsi klik tetap lancar.
- **Berkas yang Diubah:** `resources/views/learning/course.blade.php`.

### 5. Upload Foto Profil & Stimulus Gambar Soal
- **Masalah:**
  - Foto profil mahasiswa yang diunggah tidak tampil (menghasilkan 404).
  - Mahasiswa dibatasi hanya dapat mengunggah foto 1 kali tanpa opsi ganti/edit foto.
  - Gambar stimulus pada soal kuis terhapus jika dosen mengedit kuis tanpa mengunggah ulang file gambar.
- **Penyebab:**
  - Symlink `public/storage` belum dibuat di server.
  - `ProfileController` mahasiswa memiliki validasi `$user->profile_photo_path ? abort(...) : ...`.
  - Form edit soal (`item-form.blade.php`) tidak memiliki hidden input untuk mempertahankan gambar yang sudah tersimpan sebelumnya.
- **Perbaikan:**
  - Menjalankan `php artisan storage:link`.
  - Menghapus pembatasan 1x upload pada `ProfileController` mahasiswa, menambahkan penghapusan berkas lama via `Storage::disk('public')->delete()` saat foto diperbarui, serta menampilkan tombol "Ganti foto".
  - Memperbarui navbar mahasiswa (`resources/views/layouts/mahasiswa.blade.php`) agar menampilkan foto profil jika tersedia.
  - Menambahkan input hidden `existing_image` pada question builder `item-form.blade.php` dan `app.js`, menampilkan thumbnail preview gambar yang tersimpan, serta menangani retensi gambar di `LearningController@updateItem`.
- **Berkas yang Diubah:**
  - `app/Http/Controllers/Mahasiswa/ProfileController.php`
  - `resources/views/mahasiswa/profile.blade.php`
  - `resources/views/layouts/mahasiswa.blade.php`
  - `resources/views/dosen/item-form.blade.php`
  - `app/Http/Controllers/LearningController.php`
  - `resources/js/app.js`
  - `tests/Feature/StudentProfileSettingsTest.php`

### 6. Membatalkan Garis Koneksi Soal Menjodohkan (Matching)
- **Masalah:** Siswa tidak dapat membatalkan pasangan soal menjodohkan dengan mengklik langsung pada garis penghubung.
- **Penyebab:** Layer SVG garis berada di bawah elemen interaktif atau tidak memiliki hit area yang memadai untuk menangkap event klik.
- **Perbaikan:**
  - Menyesuaikan SVG container pada `quiz-room.blade.php` ke `z-30 pointer-events-none`.
  - Menambahkan path transparan dengan lebar stroke 16px di atas setiap garis (`pointer-events: stroke; cursor: pointer;`) dan menambahkan listener `onclick="disconnectConnection(id)"` serta `title="Klik garis untuk membatalkan pasangan"`.
- **Berkas yang Diubah:** `resources/views/learning/quiz-room.blade.php`.

### 7. Audit & Pembersihan Tombol Close Ganda pada Modal
- **Masalah:** Ditemukan modal dan pop-up konfirmasi yang memiliki dua tombol tutup sekaligus (ikon '✕' di header dan tombol teks 'Tutup' di footer).
- **Penyebab:** Redundant markup tombol tutup di footer modal yang sudah memiliki tombol '✕' fungsional di header.
- **Perbaikan:** Menghapus tombol teks sekunder di footer pada:
  - Modal navigasi soal `quiz-room.blade.php` (`#modal-grid-close-btn`).
  - Modal petunjuk materi `assignment-code.blade.php` (`#modal-material-close-btn`).
  - Modal detail mahasiswa `item-grading.blade.php`.
  - Modal detail tugas `tugas-grading.blade.php`.
  - Modal tinjau jawaban esai `input-nilai.blade.php`.
- **Berkas yang Diubah:**
  - `resources/views/learning/quiz-room.blade.php`
  - `resources/views/mahasiswa/assignment-code.blade.php`
  - `resources/views/dosen/penilaian/item-grading.blade.php`
  - `resources/views/dosen/penilaian/tugas-grading.blade.php`
  - `resources/views/dosen/penilaian/input-nilai.blade.php`

### 8. Penyederhanaan Heading di Halaman Input Nilai
- **Masalah:** Header halaman input nilai terlalu padat dan berulang karena menyertakan dua blok header (header kelas dan header asesmen) secara bertumpuk.
- **Penyebab:** `@include('dosen.partials.header')` dipanggil sebelum header khusus asesmen.
- **Perbaikan:** Menghapus header duplikat, menyatukan navigasi breadcrumb ke kelas, menampilkan nama asesmen, progress badge, dan tombol aksi dalam satu baris header ringkas.
- **Berkas yang Diubah:** `resources/views/dosen/penilaian/input-nilai.blade.php`.

### 9. Retensi Tenggat Waktu (Due Date) Saat Edit Konten
- **Masalah:** Saat mengedit asesmen atau kuis yang telah memiliki tenggat waktu, toggle tenggat dan input tanggal kembali ke kondisi default (nonaktif/kosong).
- **Penyebab:** Input form pada `item-form.blade.php` tidak membaca atribut `old('due', $item['due'])` dengan parsing format datetime yang sesuai untuk elemen `<input type="datetime-local">`.
- **Perbaikan:** Memperbarui binding checkbox dan input waktu agar membaca format `Y-m-d\TH:i` dari `$item['due']` jika tersedia.
- **Berkas yang Diubah:** `resources/views/dosen/item-form.blade.php`.

### 10. Soal Pilihan Ganda Kompleks (PG Kompleks)
- **Masalah:**
  - Opsi jawaban hilang saat membuka kembali form edit soal kuis.
  - Logika scoring salah: jawaban sebagian salah tetap dianggap benar.
  - Tampilan kunci jawaban pada reviu kuis membingungkan mahasiswa.
- **Penyebab:**
  - Canonicalization question mengubah `options` menjadi `option_items` tanpa direkonstruksi saat diedit kembali di frontend JavaScript.
  - Evaluasi kuis hanya mendukung all-or-nothing tanpa perhitungan penalti pilihan keliru.
- **Perbaikan:**
  - Di `resources/js/app.js`: merekonstruksi string baris `options` dan string `correct_answer` dari `option_items` dan `answer_key.option_ids` saat form dibuka kembali.
  - Di `app/Support/QuizQuestion.php`: mengimplementasikan formula penilaian parsial: $\text{Skor} = \max\left(0, \frac{C - W}{N_{\text{kunci}}}\right) \times \text{Poin}$, di mana $C$ adalah pilihan benar, $W$ adalah pilihan salah, dan $N_{\text{kunci}}$ adalah jumlah kunci jawaban.
  - Di `resources/views/learning/quiz-room.blade.php`: mengganti badge ambigu dengan status yang jelas: `"✓ Pilihan Tepat (Kunci)"`, `"✕ Pilihan Salah"`, dan `"○ Kunci Jawaban"`.
- **Berkas yang Diubah:**
  - `app/Support/QuizQuestion.php`
  - `resources/views/learning/quiz-room.blade.php`
  - `resources/js/app.js`
  - `app/Http/Controllers/LearningController.php`

### 11. Integrasi Akses Jawaban Esai Mahasiswa untuk Dosen
- **Masalah:** Dosen yang membuka halaman input nilai asesmen (`input-nilai.blade.php`) tidak dapat melihat teks jawaban esai mahasiswa; tombol "Jawaban" hanya menampilkan pop-up placeholder kosong.
- **Penyebab:** `InputNilaiController@show` tidak mem-prefetch relasi submission jawaban esai mahasiswa, dan JavaScript modal diisi dengan HTML statis.
- **Perbaikan:**
  - Di `app/Http/Controllers/Dosen/InputNilaiController.php`: mendeteksi soal bertipe `uraian`/`esai`, mem-prefetch seluruh submission dari tabel `submissions` dan relasi `answers` (`SubmissionAnswer`), memetakan jawaban per butir soal, dan mengoper data `$studentEssayData` serta flag `$hasEssay` ke view.
  - Di `resources/views/dosen/penilaian/input-nilai.blade.php`: tombol "Jawaban" menampilkan indikator titik hijau jika jawaban mahasiswa sudah terkumpul. Saat diklik, modal menampilkan:
    - Waktu pengumpulan dan total soal esai.
    - Setiap butir soal esai lengkap dengan nomor soal, bobot poin maksimal, badge CPMK, teks pertanyaan, dan jawaban asli mahasiswa dalam wadah teks yang rapi dan aman dari XSS.
    - Tautan lampiran eksternal jika disertakan oleh mahasiswa.
    - Pesan status yang jelas jika mahasiswa belum mengumpulkan lembar jawaban.
- **Berkas yang Diubah:**
  - `app/Http/Controllers/Dosen/InputNilaiController.php`
  - `resources/views/dosen/penilaian/input-nilai.blade.php`

---

## Daftar Checklist Pengujian (Testing Checklist)

Berikut adalah panduan langkah demi langkah untuk memverifikasi seluruh perbaikan secara manual di browser:

### 1. Pengujian Halaman Login
- [ ] Buka URL `/login`.
- [ ] Pastikan tampilan memenuhi layar penuh (*full screen*) dengan layout split 50:50 pada desktop:
  - Sisi kiri (50%): berlatar gradasi biru modern (`#0e2740` via `#12385b` ke `#1c5384`) dengan ambient glow halus dan corak grafis SVG asli course (pohon hierarki modul dan simpul pengetahuan) sehingga tidak tampak polos.
  - Sisi kanan (50%): form login berlatar putih bersih dengan identitas SALE resmi, input Email/NIM, toggle visibilitas kata sandi, dan tombol Masuk.
- [ ] Pada layar ponsel (mobile), pastikan form tampil penuh secara responsif dan rapi.
- [ ] Uji login menggunakan akun dosen atau mahasiswa; pastikan proses autentikasi berhasil tanpa kendala.

### 2. Pengujian Sinkronisasi Data Dosen & Kelas
- [ ] Login sebagai **Admin Prodi**.
- [ ] Buka menu **Manajemen Pengguna** (`/admin-prodi/users?role=dosen`).
- [ ] Pastikan seluruh dosen yang terdaftar di prodi (termasuk dosen yang sebelumnya hanya muncul di dropdown pembuatan kelas) kini tercantum di tabel dosen.
- [ ] Buka menu **Akademik & Kelas** (`/admin-prodi/akademik/kelas`), buat atau edit kelas, dan verifikasi nama dosen di dropdown pengampu cocok dengan daftar pengguna.

### 3. Pengujian Tampilan Item View (Petunjuk & Pengampu)
- [ ] Login sebagai **Dosen** atau **Mahasiswa**, buka salah satu asesmen/kuis di course (`/course/{course}/item/{item}`).
- [ ] Periksa tinggi card "Petunjuk Pengerjaan" dan card "Pengampu": pastikan kedua card memiliki tinggi yang sejajar (*equal height*).
- [ ] Periksa card pengampu: pastikan tidak ada label teks pudar bertuliskan "Dosen".
- [ ] Periksa kotak informasi pengerjaan di card petunjuk (Durasi, Batas Pengerjaan, Jumlah Soal): pastikan tersusun rapi secara horizontal dan tidak berdempetan.

### 4. Pengujian Responsivitas Mobile
- [ ] Buka browser di perangkat smartphone atau aktifkan *Device Mode* (layar ponsel, lebar ≤ 430px) pada browser DevTools.
- [ ] Buka halaman course (`/course/{course}`).
- [ ] Gulir halaman ke bawah. Pastikan header tidak menutupi tombol tab materi/asesmen/forum di bawahnya.
- [ ] Ketuk setiap tab dan tombol aksi di course; pastikan seluruh tombol merespons sentuhan/klik secara akurat.

### 5. Pengujian Upload & Ganti Foto Profil
- [ ] Login sebagai **Mahasiswa**.
- [ ] Buka halaman **Profil** (`/mahasiswa/profil#profil`).
- [ ] Unggah foto profil baru format PNG/JPG. Pastikan foto langsung tampil di halaman profil dan avatar navbar kanan atas.
- [ ] Unggah foto pengganti (klik "Ganti foto"). Pastikan foto berhasil diperbarui dan foto lama terhapus dari server.
- [ ] Login sebagai **Dosen**, buka form kuis yang memiliki gambar soal. Simpan kuis tanpa mengunggah file baru. Pastikan gambar soal yang sudah ada tetap tersimpan dan tidak terhapus.

### 6. Pengujian Soal Menjodohkan (Matching)
- [ ] Login sebagai **Mahasiswa**, buka ruang ujian kuis yang berisi soal menjodohkan (`/course/{course}/quiz/{item}`).
- [ ] Hubungkan satu premis di kolom kiri ke opsi jawaban di kolom kanan hingga muncul garis penghubung.
- [ ] Klik langsung pada garis penghubung yang terbentuk.
- [ ] Pastikan garis langsung terputus dan status koneksi pasangan dibatalkan seketika.

### 7. Pengujian Audit Tombol Close Modal
- [ ] Buka modal navigasi nomor soal pada ruang kuis (`quiz-room`). Pastikan hanya ada tombol '✕' di sudut kanan atas modal dan tidak ada tombol teks "Tutup" duplikat di footer modal.
- [ ] Buka halaman input nilai dosen dan klik tombol "Jawaban" pada salah satu mahasiswa. Pastikan modal jawaban hanya memiliki tombol '✕' di header.
- [ ] Tekan tombol keyboard `Escape` pada setiap modal untuk memastikan modal tetap dapat ditutup dengan cepat.

### 8. Pengujian Heading Halaman Input Nilai
- [ ] Login sebagai **Dosen**, buka halaman input nilai asesmen (`/dosen/kelas/{section}/asesmen/{assessment}/nilai`).
- [ ] Periksa bagian atas halaman: pastikan hanya ada satu header ringkas yang memuat breadcrumb, judul asesmen, dan progress status penilaian.
- [ ] Pastikan tidak ada pengulangan nama mata kuliah atau teks header yang bertumpuk.

### 9. Pengujian Tenggat Waktu pada Form Edit Asesmen
- [ ] Login sebagai **Dosen**, buka form edit kuis/tugas yang sudah memiliki batas waktu (`/dosen/course/{course}/item/{item}/edit`).
- [ ] Periksa checkbox toggle tenggat waktu: pastikan checkbox dalam keadaan tercentang dan tanggal/waktu tersimpan terisi dengan benar (tidak kosong).
- [ ] Ubah tanggal tenggat, klik simpan, dan buka kembali form edit: pastikan tanggal baru tetap tersimpan sesuai input terakhir.

### 10. Pengujian Soal Pilihan Ganda Kompleks (PG Kompleks)
- [ ] Buat soal PG Kompleks dengan 4 butir opsi/pernyataan dan tentukan 2 opsi sebagai kunci jawaban yang benar.
- [ ] Simpan kuis, lalu buka kembali form edit kuis: pastikan seluruh 4 opsi tetap utuh dan kunci jawaban tercentang dengan benar.
- [ ] Kerjakan kuis sebagai **Mahasiswa** dengan memilih:
  - Skenario A (semua benar): pilih kedua opsi kunci. Nilai harus 100% dari bobot soal.
  - Skenario B (sebagian benar, ada salah): pilih 1 opsi benar dan 1 opsi salah. Skor parsial harus dihitung secara proporsional dan tidak bernilai penuh.
- [ ] Periksa lembar reviu kuis setelah selesai dikerjakan: pastikan badge status menampilkan pilihan yang tepat, pilihan yang salah, dan kunci jawaban dengan keterangan yang jelas.

### 11. Pengujian Tinjauan Jawaban Esai Mahasiswa di Halaman Input Nilai
- [ ] Pastikan mahasiswa telah mengumpulkan jawaban pada kuis yang memiliki butir soal esai/uraian atau tugas tertulis.
- [ ] Login sebagai **Dosen**, buka menu **Input Nilai** untuk asesmen tersebut (`/dosen/kelas/{section}/asesmen/{assessment}/nilai`).
- [ ] Perhatikan kolom "Jawaban": mahasiswa yang telah mengumpulkan akan memiliki tombol "Jawaban" dengan aksen aktif dan titik hijau indikator.
- [ ] Klik tombol "Jawaban" pada baris mahasiswa:
  - Pastikan modal terbuka menampilkan nama mahasiswa.
  - Pastikan setiap butir soal esai menampilkan nomor soal, pertanyaan, bobot poin, dan teks jawaban mahasiswa secara lengkap.
  - Jika mahasiswa melampirkan tautan eksternal, pastikan tautan dapat diklik.
- [ ] Klik tombol "Jawaban" pada mahasiswa yang belum mengumpulkan: pastikan modal menampilkan pesan bahwa lembar jawaban belum dikumpulkan.
- [ ] Masukkan skor capaian CPMK pada form berdasarkan jawaban yang telah ditinjau, lalu simpan nilai.
