# Laporan Kesiapan Uji AI Tutor dan Koreksi Otomatis SALE

**Tanggal pengujian:** 21 September 2026  
**Aplikasi:** Smart Academic Learning Ecosystem (SALE)  
**Model yang dikonfigurasi:** `gemini-3.6-flash`  
**Status kesimpulan:** Layak untuk feedback dan pilot penilaian terbantu; belum layak menjadi penilai akhir otomatis karena deviasi pada jawaban parsial dan gangguan provider.

## 1. Tujuan

Pengujian ini bertujuan memastikan bahwa integrasi AI:

1. mencatat token dan estimasi biaya secara akurat;
2. dapat membandingkan koreksi esai dan kode dengan keluaran ringkas serta terperinci;
3. tidak mengubah nilai mahasiswa secara otomatis;
4. gagal secara aman ketika provider bermasalah atau respons tidak lengkap;
5. menyediakan bukti biaya, akurasi, konsistensi, dan latensi untuk keputusan kelayakan.

## 2. Ruang lingkup dan arsitektur biaya

Satu percakapan AI Tutor yang diizinkan memakai maksimal tiga panggilan provider:

| Tahap | Fungsi | Jumlah panggilan |
| --- | --- | ---: |
| Gate | Mengklasifikasikan permintaan yang boleh dibantu | 1 |
| Answer | Membuat calon jawaban pedagogis | 1 |
| Review | Memeriksa kebocoran solusi tugas | 1 |
| **Total chat diizinkan** |  | **3** |

Permintaan yang ditolak pada tahap gate hanya memakai satu panggilan. Semua tahap dicatat terpisah agar biaya jawaban, keamanan, dan review dapat dibandingkan.

Koreksi esai/kode pada benchmark memakai satu panggilan per strategi:

- `compact`: output maksimum 256 token, maksimal tiga poin umpan balik;
- `detailed`: output maksimum 640 token dengan alasan lebih lengkap.

Rumus estimasi biaya:

```text
((input - cached) × harga_input
 + cached × harga_cached
 + (output + thinking) × harga_output) / 1.000.000
```

Tarif konfigurasi pada saat laporan dibuat adalah USD 0,75/input, USD 0,075/cached input, dan USD 3,75/output termasuk thinking per satu juta token. Tarif disimpan sebagai konfigurasi karena dapat berubah. Sumber: [Gemini pricing](https://ai.google.dev/gemini-api/docs/pricing) dan [GenerateContent usage metadata](https://ai.google.dev/api/generate-content).

## 3. Pengujian otomatis yang dilaksanakan

### 3.1 Ringkasan hasil

| Kelompok pengujian | Hasil | Bukti kuantitatif |
| --- | --- | ---: |
| Seluruh feature test Laravel | Lulus | 136 test, 1.337 assertion |
| Test khusus AI Tutor | Lulus | 19 test, 84 assertion |
| Test khusus benchmark koreksi | Lulus | 7 test, 30 assertion |
| Test JavaScript | Lulus | 3 test, 0 gagal |
| Build frontend produksi | Lulus | 87 modul ditransformasi |
| Audit dependensi Composer | Lulus | 0 security advisory |
| Audit dependensi npm produksi | Lulus | 0 vulnerability |
| Pemeriksaan whitespace/diff | Lulus | Tidak ada error `git diff --check` |

Perintah verifikasi:

```bash
php artisan test tests/Feature --no-ansi
node --test tests/js/*.test.mjs
npm run build
composer audit --locked --no-interaction
npm audit --omit=dev --audit-level=high
```

### 3.2 Skenario AI yang dibuktikan

| Area | Skenario | Hasil yang diverifikasi |
| --- | --- | --- |
| Akuntansi | Input, cached, output, thinking, total, dan biaya | Nilai dicatat sesuai metadata provider tiruan |
| Harga | Cached input dan thinking memakai tarif berbeda | Rumus menghasilkan USD 0,00030375 untuk fixture pengujian |
| Batas skor | Provider mengembalikan skor 140 dari maksimum 100 | Skor dijepit menjadi 100 |
| Integritas nilai | Benchmark koreksi dijalankan | Tabel nilai dan rubrik mahasiswa tetap kosong/tidak berubah |
| Respons terpotong | Provider berhenti dengan `MAX_TOKENS` | Respons ditolak dan dicatat sebagai `incomplete` |
| Dry run | Benchmark tanpa `--live` | Tidak ada request provider |
| Kredensial | Benchmark live tanpa API key | Dihentikan sebelum request provider |
| Laporan | Enam sampel benchmark dijalankan melalui provider tiruan | Enam call dan satu laporan JSON tercatat |
| Prompt injection | Jawaban mahasiswa ditempatkan sebagai data tidak tepercaya | Instruksi sistem dan structured output tetap digunakan |
| Privasi observabilitas | Pencatatan pemakaian dilakukan | Pertanyaan, jawaban, rubrik, kode, dan API key tidak menjadi kolom log |
| Tutor aman | Permintaan solusi penuh | Ditolak setelah gate tanpa membuat jawaban |
| Review | Calon jawaban membocorkan solusi | Calon jawaban diblokir dan tidak disimpan sebagai jawaban mahasiswa |
| Kuota | Kuota akun/global habis | Provider tidak dipanggil |
| Gangguan | Timeout, autentikasi gagal, 503, JSON rusak | Gagal aman, tanpa retry tersembunyi atau bocoran detail provider |
| Otorisasi | Akun tanpa akses tugas | Provider tidak dipanggil |
| Batas penggunaan | Turn, panjang input, rate, dan concurrent lock | Permintaan berlebih dihentikan di server |

## 4. Dataset benchmark awal

Dataset awal terdiri dari enam jawaban sintetis:

| Jenis | Kualitas kuat | Kualitas parsial | Kualitas lemah |
| --- | ---: | ---: | ---: |
| Esai | 1 | 1 | 1 |
| Kode | 1 | 1 | 1 |

Setiap sampel memiliki pertanyaan, rubrik, jawaban mahasiswa sintetis, skor maksimum, dan `expected_score`. Dataset ini cukup untuk menguji pipeline, tetapi tidak cukup untuk membuktikan akurasi akademik model.

### 4.1 Hasil provider nyata Gemini 3.6 Flash

Benchmark `compact` berhasil menyelesaikan seluruh enam sampel melalui API nyata:

| Sampel | Skor acuan | Skor AI | Selisih absolut | Token total | Biaya USD | Latensi |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Esai kuat | 100 | 100 | 0 | 323 | 0,000668 | 7,3 dtk |
| Esai parsial | 65 | 55 | 10 | 318 | 0,000740 | 11,2 dtk |
| Esai lemah | 10 | 0 | 10 | 236 | 0,000453 | 13,1 dtk |
| Kode kuat | 100 | 100 | 0 | 319 | 0,000631 | 21,0 dtk |
| Kode parsial | 60 | 35 | 25 | 316 | 0,000627 | 16,6 dtk |
| Kode salah/syntax error | 0 | 0 | 0 | 286 | 0,000529 | 29,4 dtk |
| **Total/rerata** |  |  | **MAE 7,5** | **1.798** | **0,003647** | **rerata 16,4 dtk** |

Ringkasan kualitas:

- tiga dari enam skor sama persis dengan acuan;
- lima dari enam berada dalam rentang ±10 poin (83,3%);
- satu dari enam meleset lebih dari 15 poin (16,7%);
- pengulangan yang berhasil konsisten: esai parsial tetap 55, kode parsial tetap 35, dan kasus kuat/lemah tetap pada skor ekstremnya;
- target pilot usulan pada Bagian 5 belum terpenuhi karena targetnya ≥90% dalam ±10 dan deviasi berat ≤5%.

Temuan kritis terdapat pada kode parsial. AI mengidentifikasi dengan benar bahwa fungsi gagal pada nol dan bilangan negatif. Namun, alasan AI menyebut sintaks 20 poin, kasus positif 25 poin, dan kualitas 10 poin—yang seharusnya berjumlah 55—tetapi skor akhir yang dikembalikan adalah 35. Ini membuktikan bahwa kemampuan menemukan bug tidak menjamin ketepatan perhitungan nilai.

Skor acuan pada dataset ini masih ditetapkan oleh satu rancangan awal, belum konsensus dua dosen. Contohnya, skor acuan 65 untuk esai parsial dapat dianggap terlalu murah atau terlalu mahal tergantung interpretasi kredit parsial. Karena itu angka MAE ini adalah hasil awal, bukan validasi akademik final.

### 4.2 Reliabilitas dan pembanding model murah

Selama sesi pengujian Gemini 3.6 Flash, ledger mencatat 12 respons berhasil dan 9 HTTP 503 dari 21 percobaan mentah. Respons 503 tidak mempunyai usage token terkonfirmasi dan tidak dimasukkan ke estimasi biaya. Dokumentasi Google menjelaskan 503 sebagai layanan yang sementara overload/down dan menyarankan exponential backoff. Benchmark kemudian ditambah maksimal dua retry dengan jeda eksponensial; setiap percobaan tetap dicatat.

Tiga uji tambahan memakai Gemini 3.5 Flash-Lite secara sementara, tanpa mengubah model `.env` permanen:

| Sampel | Acuan | Flash-Lite | Selisih | Biaya USD | Latensi |
| --- | ---: | ---: | ---: | ---: | ---: |
| Esai parsial | 65 | 40 | 25 | 0,000333 | 1,2 dtk |
| Esai lemah | 10 | 10 | 0 | 0,000328 | 1,7 dtk |
| Kode parsial | 60 | 45 | 15 | 0,000415 | 2,3 dtk |

Pada sampel kecil ini Flash-Lite lebih cepat dan murah, tetapi lebih jauh dari skor acuan untuk jawaban parsial. Sampelnya terlalu sedikit untuk memilih model secara final. Model ringan lebih masuk akal sebagai pemberi feedback atau fallback, bukan penentu nilai.

## 5. Rancangan uji model nyata

### Tahap A — smoke test berbiaya rendah

```bash
php artisan ai:benchmark --strategy=compact --repeat=1 --live
```

- 6 sampel × 1 strategi × 1 pengulangan = 6 API call.
- Tujuan: memastikan key, model, token, biaya, latensi, dan laporan provider nyata bekerja.

### Tahap B — perbandingan strategi

```bash
php artisan ai:benchmark --repeat=3 --live
```

- 6 sampel × 2 strategi × 3 pengulangan = 36 API call.
- Tujuan: membandingkan biaya, akurasi, dan variasi `compact` dengan `detailed`.

### Tahap C — validasi akademik

Gunakan sekurang-kurangnya 30 jawaban mahasiswa yang telah dianonimkan: 15 esai dan 15 kode, mencakup jawaban kuat, sedang, lemah, kosong/tidak relevan, serta upaya prompt injection. Dua dosen menilai secara independen; perbedaan diselesaikan menjadi skor konsensus yang dimasukkan sebagai `expected_score`.

Dengan dua strategi dan tiga pengulangan, 30 sampel menghasilkan 180 API call. Data yang dilaporkan:

1. Mean Absolute Error (MAE) skor AI terhadap konsensus dosen.
2. Persentase skor dalam rentang ±5 dan ±10 poin.
3. Persentase deviasi berat, misalnya lebih dari 15 poin.
4. Simpangan/rentang skor antar-pengulangan.
5. Biaya rata-rata dan persentil ke-95 latensi per jawaban.
6. Jumlah umpan balik faktual, tidak relevan, atau memberi solusi penuh.
7. Kesepakatan antarpenilai; untuk laporan lanjutan dapat memakai ICC atau weighted kappa sesuai bentuk nilai.

### Usulan kriteria penerimaan pilot

Angka berikut adalah batas operasional yang perlu disetujui dosen, bukan standar universal:

| Metrik | Usulan batas |
| --- | ---: |
| MAE nilai skala 0–100 | ≤ 5 poin |
| Kasus dalam rentang ±10 | ≥ 90% |
| Deviasi lebih dari 15 poin | ≤ 5% |
| Perubahan nilai otomatis oleh benchmark | 0 |
| Kebocoran solusi penuh pada feedback | 0 |
| Respons gagal/tidak lengkap yang diterima | 0 |
| P95 latensi | ≤ 10 detik |

Jika batas akurasi tidak terpenuhi, AI tetap dapat dipakai untuk umpan balik formatif tanpa menghasilkan nilai.

## 6. Contoh proyeksi biaya

Angka di bawah adalah ilustrasi berdasarkan tarif konfigurasi, bukan hasil benchmark live:

| Pola | Input | Output + thinking | Estimasi/call | Estimasi 1.000 call |
| --- | ---: | ---: | ---: | ---: |
| Koreksi compact | 1.500 | 150 | USD 0,001688 | USD 1,69 |
| Koreksi detailed | 1.500 | 500 | USD 0,003000 | USD 3,00 |
| Satu chat tutor, agregat tiga tahap | 3.000 | 300 | USD 0,003375 | USD 3,38 |

Biaya aktual dapat lebih tinggi ketika riwayat chat panjang dikirim ulang. Karena itu keputusan kuota sebaiknya berdasarkan distribusi biaya aktual per fitur, bukan hanya satu batas token gabungan.

## 7. Rekomendasi teknis dan akademik

1. **Kode:** jadikan unit test deterministik sebagai sumber nilai utama. AI hanya menjelaskan test yang gagal, kualitas kode, atau kemungkinan perbaikan. Untuk tugas kode, bobot tes otomatis dapat ditentukan dosen sebelum AI dilibatkan.
2. **Esai:** gunakan AI untuk feedback dan rekomendasi skor. Nilai akhir tetap memerlukan dosen sampai validasi Tahap C memenuhi batas penerimaan.
3. **Output ringkas:** mulai dengan strategi `compact`; pilih `detailed` hanya jika peningkatan kualitas feedback terbukti sebanding dengan biaya.
4. **Kuota:** pisahkan batas chat tutor dan koreksi. Chat tutor dapat memakai tiga call dan mengirim ulang riwayat, sedangkan satu koreksi benchmark memakai satu call.
5. **Riwayat:** batasi atau ringkas riwayat lama setelah diuji agar biaya input tidak terus meningkat.
6. **Tinjauan manusia:** arahkan kasus dengan deviasi tinggi, respons gagal, jawaban kosong, atau indikator prompt injection ke dosen.
7. **Privasi:** hanya gunakan jawaban yang dianonimkan untuk benchmark dan tetapkan kebijakan retensi sebelum pilot mahasiswa nyata.

## 8. Kesimpulan

Secara perangkat lunak, pipeline pengukuran, pengamanan, dan pelaporan telah lulus pengujian otomatis. Sistem sudah mampu menghasilkan bukti token, biaya, latensi, status, serta selisih skor tanpa mengubah gradebook.

Pengujian provider nyata menunjukkan bahwa biayanya sangat rendah dan model mampu membedakan jawaban kuat, parsial, dan salah. Namun, deviasi 25 poin, kesalahan aritmetika pada alasan penilaian kode, latensi, serta rasio 503 selama jendela uji membuat model belum aman sebagai penentu nilai akhir.

Keputusan yang dapat dipertanggungjawabkan adalah **menggunakan AI untuk feedback dan rekomendasi skor dalam pilot terkontrol**, bukan mengaktifkan penilaian otomatis penuh. Untuk coding, unit test deterministik harus menghitung kebenaran fungsional. Untuk esai, server sebaiknya meminta skor per kriteria lalu menjumlahkannya sendiri, sementara dosen menangani kasus parsial dan deviasi tinggi. Kelayakan akhir tetap memerlukan dataset jawaban nyata yang dianonimkan dan konsensus sedikitnya dua dosen.
