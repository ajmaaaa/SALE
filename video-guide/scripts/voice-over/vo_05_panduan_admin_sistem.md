Setelah mengenal Smart Academic Learning Ecosystem atau SALE pada video sebelumnya, sekarang kita akan melihat bagaimana seluruh alur kerja dijalankan langsung dari dalam aplikasi.

Panduan ini mengikuti Admin Sistem dalam menyiapkan struktur akademik, akun dan hak akses, identitas aplikasi, semester aktif, integrasi AI, monitoring pemakaian, laporan, serta backup dan pemulihan basis data.

Masuk sebagai Admin Sistem. Dashboard merangkum jumlah pengguna, struktur akademik, konfigurasi sistem, pemakaian AI, dan penyimpanan. Gunakan tombol cepat untuk menambah pengguna atau membuka data akademik.

Buka Data Akademik. Tambahkan fakultas terlebih dahulu, kemudian program studi dengan memilih fakultas induk. Setelah itu tambahkan semester atau tahun ajaran menggunakan kode yang unik, nama semester, dan status aktif. Data ini menjadi fondasi yang digunakan Admin Program Studi ketika membuat mata kuliah dan kelas. Gunakan pencarian dan filter untuk meninjau struktur. Perubahan atau penghapusan harus dilakukan dengan hati hati karena dapat terkait dengan data pengguna dan pembelajaran.

Buka Pengguna dan Hak Akses. Tambahkan pengguna baru dengan NIM, NIDN, atau NIP, nama, email, peran, status akun, kata sandi awal, serta program studi bila diperlukan. Peran yang tersedia mengikuti empat tanggung jawab utama, yaitu Admin Sistem, Admin Program Studi, Dosen, dan Mahasiswa. SALE mendukung lebih dari satu peran pada akun yang sama melalui hubungan hak akses. Dengan demikian seorang dosen yang juga mengelola program studi tidak memerlukan akun ganda. Pastikan program studi pengelolaan untuk Admin Program Studi ditetapkan dengan benar.

Gunakan filter peran dan program studi untuk menemukan akun. Buka akun untuk memperbarui identitas, status aktif, hak akses, atau kata sandi. Untuk penambahan massal, unduh template pengguna, isi spreadsheet, lalu gunakan Import Pengguna Sekaligus. Sistem menerima berkas Excel dan menyediakan masukan teks sebagai alternatif. Periksa hasil proses dan jangan menganggap seluruh baris berhasil sebelum membaca ringkasan validasi.

Sekarang buka Pengaturan Sistem. Pada Identitas dan Profil Institusi, isi nama resmi kampus, nama aplikasi SALE, email bantuan teknis, kementerian atau departemen, alamat, telepon, website, email institusi, serta logo. Simpan dan periksa identitas baru pada header atau dokumen keluaran.

Pada Konfigurasi Operasional Akademik Global, pilih semester aktif yang menjadi nilai awal sistem. Pastikan semester tersebut sudah dibuat pada Data Akademik. Perubahan semester aktif memengaruhi konteks tampilan dan pelaporan, sehingga lakukan setelah koordinasi dengan pengelola akademik.

Pada Integrasi dan Batas Kuota Layanan AI, pilih penyedia Google AI, Open AI, atau DeepSeek sesuai layanan institusi. Masukkan kunci API melalui kolom aman, ambil daftar model, pilih model yang akan digunakan, dan tentukan kuota token bulanan. Gunakan fungsi uji koneksi untuk memastikan kredensial dan model dapat merespons. Jangan menampilkan nilai kunci API pada rekaman. Tutup atau samarkan seluruh rahasia sebelum perekaman dimulai.

Pada Keamanan Sesi dan Pemeliharaan, pilih status operasional normal atau mode pemeliharaan, lalu atur durasi masa aktif sesi dalam menit. Simpan konfigurasi dan pastikan notifikasi berhasil muncul. Pengaturan ini adalah kendali yang benar benar tersedia. Naskah tidak menampilkan pembatasan alamat IP atau aturan kompleksitas kata sandi global karena pilihan tersebut tidak ada pada halaman saat ini.

Buka Monitoring Sistem. Bagian AI menampilkan jumlah permintaan, token masukan, token keluaran, total token, model, tahap pemrosesan, biaya estimasi, latensi, dan waktu selesai. Gunakan filter yang tersedia untuk meninjau pemakaian, kemudian unduh laporan pemakaian AI. Bagian penyimpanan menunjukkan kapasitas dan penggunaan berkas yang benar benar tercatat oleh sistem.

Lanjutkan ke Backup dan Pemulihan. Tekan Buat Backup Server dan tunggu sampai berkas baru muncul pada riwayat cadangan. Gunakan Unduh SQL untuk menyimpan salinan. Tampilkan pula pilihan Pulihkan dan Hapus, tetapi jangan menjalankan pemulihan atau penghapusan pada lingkungan produksi hanya untuk kebutuhan video. Pada lingkungan demo, pemulihan boleh direkam menggunakan basis data khusus yang dapat dikembalikan.

Atur lokasi penyimpanan backup, frekuensi harian, mingguan, bulanan, atau manual, serta jam pelaksanaan dalam WIB. Simpan pengaturan. Untuk cadangan eksternal, halaman juga menyediakan unggah berkas SQL dan pemulihan. Selalu validasi sumber berkas, buat cadangan terbaru, dan batasi akses sebelum menjalankan proses tersebut.

Buka Laporan dan Rekapitulasi. Halaman ini merangkum struktur institusi, pemakaian token AI dan komputasi, distribusi pengguna dan hak akses, serta riwayat audit administratif. Tekan Ekspor untuk mengunduh laporan. Buka Riwayat Audit agar perubahan penting dapat ditelusuri berdasarkan pelaku, aksi, konteks, dan waktu.

Alur Admin Sistem kini lengkap. Struktur institusi dan semester disiapkan lebih dahulu. Pengguna serta hak akses dikelola secara terpusat. Identitas aplikasi, semester aktif, AI, kuota, sesi, dan mode pemeliharaan dikonfigurasi sesuai kebijakan. Pemakaian dan penyimpanan dipantau, laporan diekspor, serta basis data dicadangkan secara terjadwal. Seluruh fondasi tersebut memungkinkan Admin Program Studi, dosen, dan mahasiswa menggunakan SALE, Smart Academic Learning Ecosystem, secara konsisten dan aman.
