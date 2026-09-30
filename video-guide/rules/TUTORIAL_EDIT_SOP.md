# STANDAR OPERASIONAL PROSEDUR (SOP) PRODUKSI VIDEO TUTORIAL SALE
> Dokumen ini adalah panduan teknis wajib bagi tim editor dan pengembang video panduan sistem SALE.
> Wajib dipatuhi agar seluruh 20 episode video memiliki kualitas visual seragam, dinamis, profesional, dan bebas dari ciri "AI-Slop".

---

## 1. PRINSIP UTAMA: REKAMAN ASLI & ALUR AKSI-REAKSI NYATA

1. **Aksi Tombol Wajib Diikuti Reaksi Sistem (Action-Reaction)**:
   - Jika kursor menekan tombol (misal tombol "Masuk", menu navigasi, tombol "+ Gabung Kelas", atau tombol konfirmasi), tampilan antarmuka **wajib langsung berganti ke halaman berikutnya** sebagai respon alami sistem web dalam tempo 6–10 frame setelah klik.
   - **DILARANG KERAS** menekan tombol tetapi layar tetap diam membeku di halaman yang sama lalu berganti secara paksa melalui transisi terpisah.
2. **Sistem Asli 100% (No Fake UI)**:
   - Seluruh konten demonstrasi **wajib menggunakan rekaman layar / antarmuka asli sistem SALE**.
   - **DILARANG** membuat pop-up tiruan, modal palsu, atau elemen UI buatan di luar sistem. Seluruh modal dan halaman yang ditampilkan adalah halaman sistem nyata (dialog modal pendaftaran kelas resmi, halaman konfirmasi pendaftaran, dashboard, halaman ruang materi perkuliahan).
3. **Dilarang Menambahkan Garis/Box Overlay Buatan yang Menimpa UI**:
   - Jangan menambahkan border/garis/glow buatan di atas tombol, form, atau card jika posisinya menimpa atau tidak presisi.
   - Cukup gunakan efek klik kursor alami (*click ripple*) dan respon visual bawaan web.

---

## 2. ATURAN TRANSISI & LOGIKA KURSOR

1. **Gunakan Cut Alami Saat Aksi Web (Anti-Ghosting)**:
   - Perpindahan antar halaman web setelah klik tombol harus terjadi secara **cut instan (seamless hard cut)**, persis seperti perilaku peramban web saat memuat halaman baru.
   - **DILARANG KERAS** menggunakan crossfade bertumpuk (`presentation={fade()}`) antar halaman screencast yang membuat dua halaman web transparan dan saling timpa (*ghosting*).
   - Transisi fade hanya diizinkan untuk cut cepat (≤ 6 frame) antara Cover Intro pembuka dan Screencast layar pertama.
2. **Kamera Stabil & Utuh (Full View 1920×1080)**:
   - Tampilkan layar secara utuh tanpa pemotongan canggung (*no awkward crop*).
   - Jangan melakukan zoom acak atau terpotong.
3. **Logika Ripple Kursor Bebas Klik Hantu (Anti Phantom Click)**:
   - Pada komponen `AnimatedCursor`, efek riak (*ripple*) **hanya boleh aktif** jika `clickFrame >= 0` dan `frame >= clickFrame`.
   - **DILARANG** memicu riak pada frame awal sebelum kursor benar-benar menekan elemen target.

---

## 3. ATURAN TATA LETAK & VARIASI (ANTI-MONOTON)

1. **Larangan Pola Monoton "Teks di 4 Sudut Layar"**:
   - Jangan menyusun teks identik di setiap sudut layar (kiri-atas, kanan-atas, kiri-bawah, kanan-bawah).
   - Gunakan tata letak editorial asimetris (misal: kolom judul besar di kiri 60% dan *roadmap* tahapan tutorial terstruktur di kanan 40%).
2. **Variasi Posisi Teks Instruksi (*InstructionOverlay*) Sesuai Aksi**:
   - Jangan menaruh teks instruksi selalu di kiri-bawah pada setiap adegan.
   - Sesuaikan posisi untuk menciptakan keseimbangan visual (*visual balance*):
     - Saat aksi terjadi di bilah navigasi kiri $\rightarrow$ Tempatkan teks di **`bottom-right`**.
     - Saat modal atau formulir muncul di tengah $\rightarrow$ Tempatkan teks di **`bottom-center`**.
     - Pada adegan penutup / status akhir kelas $\rightarrow$ Gunakan format **`split-cinematic`** (langkah dan judul di kiri, deskripsi dan arahan episode di kanan).
     - Pada aksi form login tengah $\rightarrow$ Tempatkan teks di **`bottom-left`**.

---

## 4. ATURAN TIPOGRAFI & SIMBOL (ANTI-SLOP)

1. **Larangan Simbol Titik (.), Bullet (•), dan Em-Dash (—) Sebagai Pemisah**:
   - **DILARANG** menggunakan simbol titik `.`, bullet `•`, atau dash ganda/em-dash `—` sebagai pemisah judul langkah atau nomor bab.
   - Contoh SALAH:
     - ❌ `LANGKAH 01 . Masuk ke Sistem SALE`
     - ❌ `PANDUAN MAHASISWA • EPS. 01`
     - ❌ `PANDUAN MAHASISWA — EPISODE 01`
     - ❌ `Autentikasi — Navigasi — Aktivasi`
   - Gunakan pemisahan baris alami, spasi hierarki, atau tanda garis miring tunggal (`/`):
     - ✅ `PANDUAN MAHASISWA / EPISODE 01`
     - ✅ Baris terstruktur:
       ```
       LANGKAH 01
       Masuk ke Sistem SALE
       ```
     - ✅ Daftar bernomor: `01 Login Mahasiswa`, `02 Akses Course`, `03 Aktivasi Kelas`
2. **Ukuran Teks Deskripsi Jelas, Besar & Tebal**:
   - Judul aksi instruksi: **32–36px font-black text-white**.
   - Deskripsi langkah: **20–22px font-bold text-slate-100**, kontras tinggi tanpa border/card buatan.
3. **Desain Cover Bebas AI-Slop**:
   - Gunakan latar solid berwibawa (`#102f50`), tipografi tegas percaya diri (Inter / Plus Jakarta Sans), dan kisi teknikal halus tanpa bola cahaya neon (*no blur orbs*).

---

## 5. STRUKTUR MODULAR SUMBER KODE UNTUK AUDIO & TIMING

- Seluruh durasi adegan didefinisikan secara terpusat pada file `Video01MahasiswaJoin.tsx` melalui objek `V01_DURATIONS`.
- Setiap adegan (`Scene1Intro`, `Scene2LoginScreencast`, dst.) merupakan komponen independen yang membaca `frame` lokal sehingga mudah diperpanjang atau dipersingkat beberapa frame saat proses sinkronisasi sulih suara (*voiceover* ElevenLabs) dilakukan.
