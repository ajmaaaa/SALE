# STANDAR OPERASIONAL PROSEDUR (SOP) PRODUKSI VIDEO TUTORIAL SALE
> Dokumen ini adalah panduan teknis wajib bagi tim editor dan pengembang video panduan sistem SALE.
> Wajib dipatuhi agar seluruh 20 episode video memiliki kualitas visual seragam, profesional, dan bebas dari ciri "AI-Slop".

---

## 1. PRINSIP UTAMA: REKAMAN ASLI SEBAGAI KONTEN UTAMA

1. **Sistem Asli 100%**:
   - Seluruh konten demonstrasi **wajib menggunakan rekaman layar / antarmuka asli sistem SALE**.
   - **DILARANG** membuat pop-up tiruan, modal palsu, atau elemen UI buatan di luar sistem (contoh yang dilarang: membuat kartu "Pendaftaran Berhasil" buatan sendiri yang menutupi layar). Biarkan sistem berbicara sesuai alur aslinya.
2. **Peran Teks & Elemen Tambahan**:
   - Rekaman layar adalah menu utama. Teks penjelasan hanya berfungsi sebagai **petunjuk pendukung minimalis**.
   - Teks instruksi disajikan **hanya teks (clean typography)** dengan kontras tajam atau bayangan halus.
   - **DILARANG KERAS** membungkus teks ke dalam card tebal, badge warna-warni bertumpuk, atau label neon ala infografis murah.

---

## 2. ATURAN ZOOM & KAMERA (CAMERA DYNAMICS)

1. **Kapan Boleh Zoom?**:
   - **HANYA** lakukan zoom jika fokus berada pada satu area interaksi spesifik dalam durasi yang cukup lama (contoh: mengetik pesan chat panjang di forum dan menunggu balasan dosen, atau mengisi baris rubrik penilaian yang padat).
   - Jika hanya navigasi menu cepat, klik tombol biasa, atau pindah halaman: **TETAP GUNAKAN TAMPILAN PENUH (FULL VIEW 1920x1080)**.
2. **Kerapian Framing**:
   - Layar sistem tidak boleh terpotong sembarangan (sidebar atau header tidak boleh terpotong canggung di tengah-tengah teks).
   - Jika melakukan zoom-in (maksimal 115%–125%), kamera harus melakukan **zoom-out kembali secara mulus (*ease-in-out*)** sebelum berpindah ke halaman atau menu lainnya.
3. **Kecepatan Transisi Kamera**:
   - Zoom in dan zoom out wajib menggunakan interpolasi halus (*spring easing* atau *cubic bezier*), durasi minimal 0.5–0.8 detik. Dilarang ada zoom mendadak yang membuat pusing penonton.

---

## 3. ATURAN KURSOR & AKURASI INTERAKSI

1. **Akurasi Posisi (Pixel-Perfect Click)**:
   - Kursor wajib mendarat tepat di titik tengah (*center*) tombol, input field, atau tab navigasi yang dimaksud (`x + width/2`, `y + height/2`).
   - Tidak boleh ada kursor yang meleset, mendarat di luar tombol, atau mengklik area kosong.
2. **Gerakan Kursor yang Natural**:
   - Gerakan kursor menggunakan kurva kecepatan realistis: mulai perlahan, bergerak cepat di tengah lintasan, dan melambat saat mendekati target (*ease-out*).
   - Jangan biarkan kursor melompat secara instan (*teleport*).
3. **Umpan Balik Visual Klik**:
   - Saat tombol ditekan, kursor mengecil sejenak (*scale down* ke ~0.85) disertai riak lingkaran klik transparan (*click ripple*).
   - Tombol target menampilkan efek hover/active sesuai antarmuka aslinya.

---

## 4. ELEMEN PENDUKUNG & ESTETIKA (ANTI-MONOTON & ANTI-SLOP)

1. **Ciri AI-Slop yang Wajib Dihindari**:
   - Label/chip warna-warni neon (merah, ungu, hijau menyala saling tabrak).
   - Kotak card mengambang dengan border tebal yang menutupi layar rekaman.
   - Ikon-ikon dekoratif yang tidak fungsional.
2. **Komponen Pendukung yang Dianjurkan**:
   - **Spotlight Halus**: Area sekitar target sedikit meredup (*subtle vignette/dim*) saat fokus instruksi tertentu agar mata penonton langsung tertuju ke aksi penting.
   - **Typing Simulation**: Input teks (NIM, password, pencarian) tampil huruf demi huruf secara dinamis dengan kursor kedip (*caret*), tidak langsung muncul dalam satu blok.
   - **Progress Bar Bawah**: Garis progres tipis (tinggi 3–4px) di dasar layar dengan warna aksen brand SALE (`#102f50` / `cyan-400`).
   - **Typography**: Inter / Plus Jakarta Sans, kontras tinggi, penempatan di area negatif yang tidak menutupi informasi penting aplikasi.

---

## 5. STRUKTUR EPISODE STANDAR

| Bagian | Durasi | Keterangan |
|---|---|---|
| **Intro** | 3–4 detik | Judul episode, nama peran (Mahasiswa/Dosen/Admin), logo SALE minimalis & elegan |
| **Screencast Inti** | 80–90% video | Alur rekaman layar sistem asli dengan gerakan kursor presisi dan teks petunjuk clean |
| **Konfirmasi / Status** | 3–5 detik | Menampilkan layar hasil akhir di sistem (status sukses, data terisi, halaman course aktif) |
| **Outro** | 3–4 detik | Teks penutup singkat & arahan menuju episode panduan berikutnya |
