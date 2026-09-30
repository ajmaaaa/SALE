# STANDAR OPERASIONAL PROSEDUR (SOP) PRODUKSI VIDEO TUTORIAL SALE
> Dokumen ini adalah panduan teknis wajib bagi tim editor dan pengembang video panduan sistem SALE.
> Wajib dipatuhi agar seluruh 20 episode video memiliki kualitas visual seragam, profesional, dan bebas dari ciri "AI-Slop".

---

## 1. PRINSIP UTAMA: REKAMAN ASLI & ALUR AKSI-REAKSI NYATA

1. **Aksi Tombol Wajib Diikuti Reaksi Sistem (Action-Reaction)**:
   - Jika kursor menekan tombol (misal tombol "Masuk", menu navigasi, atau tombol konfirmasi), tampilan antarmuka **wajib langsung berganti ke halaman berikutnya** sebagai respon alami sistem web dalam tempo 6–10 frame setelah klik.
   - **DILARANG KERAS** menekan tombol tetapi layar tetap diam membeku di halaman yang sama lalu berganti secara paksa melalui transisi terpisah.
2. **Sistem Asli 100% (No Fake UI)**:
   - Seluruh konten demonstrasi **wajib menggunakan rekaman layar / antarmuka asli sistem SALE**.
   - **DILARANG** membuat pop-up tiruan, modal palsu, atau elemen UI buatan di luar sistem (contoh yang dilarang: membuat kartu "Pendaftaran Berhasil" buatan sendiri yang menutupi layar). Seluruh modal dan halaman yang ditampilkan adalah halaman sistem nyata (misal: modal pendaftaran kode kelas, halaman konfirmasi pendaftaran, dashboard).
3. **Dilarang Menambahkan Garis/Box Overlay Buatan yang Menimpa UI**:
   - Jangan menambahkan border/garis/glow buatan di atas tombol, form, atau card jika posisinya menimpa atau tidak presisi.
   - Cukup gunakan efek klik kursor alami (*click ripple*) dan respon visual bawaan web.

---

## 2. ATURAN TRANSISI (ANTI SALING TIMPA / GHOSTING)

1. **Gunakan Cut Alami Saat Aksi Web**:
   - Perpindahan antar halaman web setelah klik tombol harus terjadi secara **cut instan (seamless hard cut)**, persis seperti perilaku peramban web saat memuat halaman baru.
   - **DILARANG KERAS** menggunakan crossfade bertumpuk (`presentation={fade()}`) antar halaman screencast yang membuat dua halaman web transparan dan saling timpa (*ghosting*).
   - Transisi fade hanya diizinkan untuk cut cepat (≤ 6 frame) antara Cover Intro pembuka dan Screencast layar pertama.
2. **Kamera Stabil & Utuh (Full View 1920×1080)**:
   - Tampilkan layar secara utuh tanpa pemotongan canggung (*no awkward crop*).
   - Jangan melakukan zoom acak atau terpotong. Zoom hanya jika fokus membaca teks/kolom input sempit dalam durasi panjang, dan harus kembali zoom-out penuh sebelum berganti halaman.

---

## 3. ATURAN TIPOGRAFI & TEKS INSTRUKSI (ANTI-SLOP)

1. **Larangan Keras Simbol Titik (.) Sebagai Pemisah**:
   - **DILARANG** menggunakan simbol titik `.` atau bullet `•` sebagai pemisah judul langkah atau nomor bab.
   - Contoh SALAH:
     - ❌ `LANGKAH 01 . Masuk ke Sistem SALE`
     - ❌ `Langkah 1 . Masuk.`
     - ❌ `PANDUAN MAHASISWA • EPS. 01`
   - Gunakan format baku terstruktur atau em-dash murni:
     - ✅ Badge: `LANGKAH 01` lalu judul: `Masuk ke Sistem SALE`
     - ✅ `PANDUAN MAHASISWA — EPISODE 01`
2. **Ukuran Teks Deskripsi Jelas, Besar & Tebal (Interactive Typography)**:
   - Judul aksi instruksi wajib besar dan sangat tegas: **32–36px font-black / extra-bold text-white**.
   - Deskripsi langkah minimal **20–22px font-bold text-slate-100**, kontras tinggi dan mudah dibaca pada resolusi 1080p tanpa perlu card/box tebal yang menutupi layar.
   - Gunakan latar gradient gelap halus di bagian bawah (`bg-gradient-to-t from-slate-950/95 via-slate-950/80 to-transparent`) untuk keterbacaan sempurna.
3. **Desain Cover / Intro Bebas AI-Slop**:
   - Dilarang keras menggunakan *ambient blur orbs* neon (lingkaran blur ungu/cyan besar khas generator AI).
   - Gunakan latar solid berwibawa (`#0b1626` / `#0f1f38`), tipografi tegas percaya diri (Inter / Plus Jakarta Sans), dan sematkan **mockup jendela peramban asli** yang memperlihatkan antarmuka sistem SALE nyata.

---

## 4. ATURAN KURSOR & AKURASI INTERAKSI

1. **Akurasi Posisi (Pixel-Perfect Click)**:
   - Kursor wajib mendarat tepat di titik tengah (*center*) tombol, input field, atau tab navigasi yang dimaksud (`x + width/2`, `y + height/2`).
   - Tidak boleh ada kursor yang meleset, mendarat di luar tombol, atau mengklik area kosong.
2. **Gerakan Kursor yang Natural**:
   - Gerakan kursor menggunakan kurva kecepatan realistis: mulai perlahan, bergerak cepat di tengah lintasan, dan melambat saat mendekati target (*ease-out*).
3. **Umpan Balik Visual Klik**:
   - Saat tombol ditekan, kursor mengecil sejenak (*scale down* ke ~0.82) disertai riak lingkaran klik transparan (*click ripple*).

---

## 5. STRUKTUR EPISODE STANDAR

| Bagian | Durasi | Keterangan |
|---|---|---|
| **Cover / Intro** | 4 detik (120f) | Judul episode, nama peran (Mahasiswa/Dosen/Admin), preview jendela SALE nyata tanpa neon slop |
| **Screencast Inti** | 15–20 detik | Alur rekaman layar sistem asli dengan aksi-reaksi klik instan dan kursor presisi |
| **Konfirmasi / Status** | 3–5 detik | Menampilkan layar hasil akhir di sistem (status sukses, kelas aktif, data tersimpan) |
| **Outro / Next Eps** | 3–4 detik | Teks penutup singkat & arahan menuju episode panduan berikutnya |
