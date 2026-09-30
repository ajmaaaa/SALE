# MASTER VIDEO 05 / PANDUAN LENGKAP ADMIN SISTEM

**Kategori:** Admin Sistem  
**Target Audiens:** Superadmin, Tim Pengelola IT Kampus, dan Administrator Server  
**Estimasi Durasi:** 10 hingga 12 menit  
**Format Visual:** 100% Rekaman Layar Antarmuka Asli Admin Sistem SALE, Kursor Presisi, Efek Bayangan Tepi Halus, Standar Desain Bebas AI Slop  

---

## BAB 01 / PENGELOLAAN PENGGUNA GLOBAL, MULTI PERAN, DAN SEMESTER INSTITUSI

### [VISUAL DIRECTION]
Layar Dashboard Superadmin SALE. Kursor membuka menu "Master Pengguna" pada sidebar navigasi global.
Layar menampilkan direktori seluruh pengguna lintas fakultas dan program studi, lengkap dengan atribut Role (Admin Sistem, Admin Prodi, Dosen, Mahasiswa), status keaktifan akun, dan log waktu login terakhir.

Kursor memperlihatkan penetapan peran ganda (misal: seorang Dosen yang juga diberi hak akses sebagai Admin Prodi). Kursor mengklik tombol "Edit Akses", mencentang hak wewenang terkait, dan menyimpan perubahan tanpa perlu membuat akun duplikat.

Selanjutnya, kursor berpindah ke menu "Master Semester & Tahun Akademik". Kursor memperlihatkan daftar semester (Semester Ganjil 2026/2027), menetapkan tanggal mulai dan berakhir perkuliahan, serta menyalakan toggle "Semester Aktif" yang secara global mengontrol periode perkuliahan di seluruh sistem kampus.

### [VOICEOVER / NASKAH NARASI]
Sebagai Administrator Sistem, Anda memegang kendali tertinggi atas keandalan, keteraturan, dan keamanan seluruh ekosistem digital SALE di tingkat institusi.

Tugas strategis pertama dimulai dari pengelolaan identitas pengguna secara global. Sistem SALE mendukung fleksibilitas hak akses multi-peran, memungkinkan Anda memberikan wewenang tambahan kepada tenaga pengajar yang mengemban tugas manajerial di tingkat program studi tanpa keharusan membuat kredensial akun ganda.

Melalui menu Master Semester, tetapkan kalender akademik aktif institusi. Periode semester yang diaktifkan di sini akan menjadi acuan global bagi seluruh operasional kelas, jadwal pengumpulan tugas, hingga pelaporan nilai di tingkat fakultas dan program studi.

---

## BAB 02 / PENGATURAN PARAMETER SISTEM, INTEGRASI AI, DAN KEAMANAN SESI

### [VISUAL DIRECTION]
Kursor membuka menu "Pengaturan Sistem" pada sidebar navigasi.
Tampilan halaman terbagi menjadi beberapa tab konfigurasi:
1. Tab Identitas Kampus: Nama institusi (Institut Teknologi Senggarang), logo resmi, alamat surel narahubung bantuan teknis (helpdesk), dan nomor kontak darurat kampus.
2. Tab Integrasi Layanan AI: Konfigurasi API Key mesin kecerdasan buatan untuk asisten praktikum pemrograman mahasiswa, penetapan kuota token harian per akun, dan pengaturan model komputasi yang digunakan.
3. Tab Keamanan & Sesi: Pengaturan batas waktu inaktivitas sesi (session idle timeout), pembatasan akses alamat IP jaringan lokal kampus, serta kebijakan kompleksitas kata sandi minimum.
Kursor mengubah salah satu parameter, lalu mengklik "Simpan Konfigurasi Global". Notifikasi sukses berwarna hijau muncul di sudut layar.

### [VOICEOVER / NASKAH NARASI]
Platform SALE memberikan fleksibilitas penuh bagi institusi dalam menyesuaikan parameter operasional sesuai kebijakan internal kampus.

Pada menu Pengaturan Sistem, Anda dapat mengkustomisasi identitas institusi, menentukan alamat kontak layanan bantuan resmi, serta mengonfigurasi integrasi layanan kecerdasan buatan. 

Anda memiliki kendali penuh untuk mengatur kuota pemakaian token AI agar efisien dan tepat sasaran bagi pembelajaran mahasiswa. 

Selain itu, atur kebijakan keamanan seperti durasi kedaluwarsa sesi otomatis dan standar kompleksitas kata sandi demi menjaga kerahasiaan data akademik civitas kampus dari potensi ancaman siber.

---

## BAB 03 / MONITORING KINERJA SERVER, STORAGE, DAN PENCADANGAN BASIS DATA

### [VISUAL DIRECTION]
Kursor membuka menu "Monitoring & Pemeliharaan" di sidebar Admin Sistem.
Layar memuat Dasbor Diagnostik Server:
1. Metrik Beban Sistem: Utilitas CPU (24%), Penggunaan RAM (4.2 GB dari 16 GB), dan status kesehatan koneksi database (Optimal).
2. Pengawasan Kapasitas Penyimpanan (Storage Analytics): Diagram lingkaran penggunaan kuota penyimpanan berkas materi perkuliahan, video pembelajaran, dan arsip tugas mahasiswa.
3. Manajemen Berkas Cadangan (Database Backup): Tabel daftar berkas cadangan otomatis harian.

Kursor mengklik tombol "+ Buat Backup Basis Data Sekarang". Sistem memproses ekspor snapshot SQL terenkripsi secara aman di latar belakang. Dalam beberapa detik, berkas cadangan baru muncul di urutan teratas tabel lengkap dengan waktu pembuatan dan tombol "Unduh Cadangan Aman".

Kursor kemudian memperlihatkan tab "Log Aktivitas Sistem": meninjau catatan log audit transaksi penting untuk kebutuhan penelusuran jika terjadi insiden teknis.

### [VOICEOVER / NASKAH NARASI]
Kelancaran kegiatan belajar mengajar ribuan civitas akademika bertumpu pada kestabilan performa infrastruktur server.

Dasbor Monitoring menyajikan visibilitas menyeluruh terhadap utilisasi memori, beban prosesor, serta konsumsi ruang penyimpanan berkas perkuliahan secara berkala. Anda dapat mendeteksi lonjakan trafik pada masa ujian semester dan melakukan tindakan preventif lebih dini.

Untuk mengantisipasi keadaan darurat, SALE menyediakan fitur pencadangan basis data otomatis dan manual dengan enkripsi aman. Anda dapat mengunduh salinan cadangan kapan saja dan menyimpannya di lokasi penyimpanan dingin institusi.

Dengan pengawasan log audit yang transparan, integritas data akademik institusi Anda selalu terjaga pada standar tertinggi bersama ekosistem SALE.
