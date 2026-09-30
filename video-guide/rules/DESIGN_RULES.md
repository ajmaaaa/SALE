# ATURAN DESAIN VIDEO PANDUAN SALE
> Dokumen ini adalah panduan wajib untuk semua yang mengerjakan visual video panduan sistem SALE.
> Baca dan pahami sebelum membuat satu slide pun.

---

## BAGIAN 1 — LARANGAN MUTLAK (Anti-AI-Slop)

Hal-hal di bawah ini **dilarang keras**. Ini adalah tanda-tanda desain AI-slop yang harus dihindari sepenuhnya.

### Warna & Gradient
- Jangan gunakan gradient neon atau gradient ungu sebagai latar utama
- Jangan pakai warna-warna yang saling tabrakan dalam satu slide (contoh: merah + hijau terang + kuning)
- Jangan gunakan badge/chip dengan warna background pudar + border solid mencolok (contoh: bg-green-50 + border-green-600)
- Palet warna harus konsisten di seluruh video — gunakan palet yang sudah ditetapkan dan jangan keluar dari itu

### Tipografi
- Jangan gunakan font kaku seperti Courier New, Times New Roman untuk elemen utama
- Jangan biarkan semua slide punya susunan teks yang identik (label kecil di atas → heading besar di bawah → deskripsi)
- Jangan pasang titik (.) di akhir heading utama atau keterangan ikon
- Jangan pakai simbol dash ganda (--) sebagai separator atau ornamen
- Pastikan teks dan elemen mengisi area desain secara proporsional — tidak ada zona kosong yang dibiarkan menganga (lebih dari 1/4 layar kosong tanpa elemen apa pun)

### Ikon & Label
- Jangan gunakan ikon berwarna-warni berbeda-beda dalam satu slide (misal: ikon merah, ikon biru, ikon kuning di satu baris)
- Jangan beri label teks di bawah setiap ikon seolah sedang membuat tombol aplikasi mobile
- Jangan buat list/card dengan garis vertikal atau horizontal sebagai dekorasi utama (garis pembatas boleh tipis dan fungsional, bukan ornamen)

### Tata Letak & Monotoni
- Jangan buat desain slide 1 dan slide 2 persis sama tata letaknya — variasikan
- Jangan biarkan area kosong besar di bagian bawah, atas, atau samping slide

### Aset Gambar & Ilustrasi (Anti-AI-Image)
- **DILARANG KERAS menggunakan gambar hasil generate AI** (seperti Midjourney, DALL-E, Stable Diffusion, atau generator ilustrasi AI sejenis). Ciri gambar AI generatif yang dilarang: tangan/jari terdistorsi, artefak rendering plastik, teks/ornamen ngawur, pencahayaan neon tak natural, dan vibe "AI slop" yang merusak citra profesional sistem SALE.
- Dilarang menyisipkan karakter atau ilustrasi pseudo-3D glossy hasil prompt generator AI.

---

## BAGIAN 2 — STANDAR DESAIN YANG WAJIB DITERAPKAN

### Palet Warna Utama
```
Biru Tua Sistem  : #102f50  (warna brand SALE)
Putih Bersih     : #FFFFFF
Krem Lembut      : #F5F3EE
Abu Muted        : #64748b
Aksen Hangat     : #e8f1f8  (biru muda terang)
Teks Heading     : #0f172a  (hampir hitam)
```
Untuk variasi antar slide, bisa gunakan latar #102f50 (gelap) atau #F5F3EE (terang) secara bergantian.

### Tipografi
- Gunakan font modern dan bold: **Inter**, **Plus Jakarta Sans**, atau **Geist** (semua tersedia di Google Fonts)
- Heading utama: Bold, besar, tegas — minimal 60px di 1920x1080
- Sub-heading: Semi-bold, 32–40px
- Body/deskripsi: Regular atau medium, 22–28px, jangan terlalu kecil
- Variasikan tata letak tipografi tiap slide (kiri rata, tengah, besar-kecil, dsb)

### Ukuran & Rasio
- Semua video: **1920 × 1080 piksel (16:9)**
- Untuk layar rekaman (screen recording): ekspor dalam resolusi yang sama

### Animasi Wajib
- Setiap slide non-rekaman harus punya minimal 1 elemen bergerak: objek mengambang (float), fade-in bertahap, slide-in dari sisi, atau elemen yang berputar perlahan
- Transisi antar slide: minimal 0.4 detik, smooth — pilih fade, slide, atau scale
- Tidak boleh ada slide yang 100% diam selama lebih dari 2 detik

### Aturan Screen Recording
- Pastikan cursor terlihat dan bergerak smooth (gunakan cursor highlighting)
- Gunakan zoom-in yang smooth (ease-in-out) saat menyoroti fitur tertentu — zoom 110–130% cukup, jangan berlebihan
- Zoom harus kembali (zoom-out) secara smooth sebelum pindah ke area lain
- Kecepatan cursor realistis — jangan terlalu cepat atau terlalu lambat
- Jika ada instruksi klik, pastikan ada visual feedback (highlight/klik animasi)

### Komponen Visual Tambahan (Wajib Non-Generate AI)
- Gunakan ilustrasi atau ikon murni dari sumber open-source terpercaya: **Heroicons, Tabler Icons, Feather Icons, unDraw, Storyset (Freepik)**. Wajib human-made/vektor asli, bukan hasil prompt AI generator.
- Prioritaskan gambar animasi vektor (SVG animasi, Lottie) untuk elemen visual dekoratif yang elegan.
- Untuk visual UI, selalu gunakan **screenshot asli dari antarmuka sistem SALE** atau mockup browser berbasis kode nyata.
- Pastikan semua aset visual bebas royalti atau berlisensi open-source komersial.

---

## BAGIAN 3 — VARIASI LAYOUT PER SLIDE (Template Reference)

Gunakan variasi ini secara bergantian, jangan gunakan satu pola berulang:

1. **Full Left** — Teks di kiri, ilustrasi/visual di kanan
2. **Center Bold** — Heading besar di tengah, sedikit deskripsi, background polos atau halus
3. **Top Visual** — Ilustrasi besar di atas, teks ringkas di bawah
4. **Split Card** — Dua kolom card berisi konten berbeda
5. **Bottom Anchor** — Visual/screenshot di bagian atas 70%, teks kecil di bawah
6. **Diagonal Flow** — Elemen teks dan visual disusun diagonal, tidak lurus
7. **Full Bleed** — Background penuh satu warna, teks putih besar di tengah

---

## BAGIAN 4 — STRUKTUR PER VIDEO

Setiap file video memiliki:
- **Intro** (3–5 detik): Judul video + nama sistem SALE + logo
- **Konten Utama** (variabel tergantung topik)
- **Outro** (3–5 detik): Tagline penutup, link/referensi jika ada

---

## BAGIAN 5 — VOICEOVER GUIDE

- Voiceover di-generate dari ElevenLabs — pilih suara yang natural, tidak robotik
- Gunakan bahasa Indonesia yang santai tapi profesional (bukan formal kaku)
- Jangan mulai kalimat voiceover dengan: "Halo semuanya", "Pada video kali ini", "Kita akan belajar"
- Mulai langsung dengan konteks: "Satu hal yang sering bikin bingung adalah..." atau "Ini cara paling cepat untuk..."
- Tempo bicara: 130–150 kata/menit (tidak terlalu cepat, tidak terlalu lambat)
- Setiap kalimat voiceover di script diberi tanda `[VO]` sebelumnya

---

*Dokumen ini berlaku untuk semua video panduan SALE — update jika ada perubahan standar.*
