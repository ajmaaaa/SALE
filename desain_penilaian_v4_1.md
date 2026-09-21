# Desain Penilaian Esai — Clean UI

**Versi:** 4.1  
**Fokus:** Menampilkan jawaban mahasiswa yang perlu dinilai dosen.  
**Prinsip:** Dosen hanya melihat pekerjaan yang membutuhkan tindakan manual.

## 1. Konsep Utama

Alur penilaian:

```text
Mahasiswa mengerjakan
        ↓
Sistem memisahkan jawaban
        ↓
Soal otomatis → langsung dinilai sistem
Soal esai    → masuk "Perlu Dinilai"
        ↓
Dosen membaca jawaban
        ↓
Dosen memberi skor
        ↓
Simpan & Berikutnya
        ↓
Sistem menghitung nilai akhir
```

Soal PG, PG kompleks, Benar/Salah, dan Jodohkan **tidak ditampilkan pada halaman kerja dosen** karena tidak membutuhkan tindakan manual. Nilainya tetap dihitung oleh backend.

---

## 2. Halaman Daftar Mahasiswa

Setelah dosen membuka sebuah asesmen:

```text
Tugas 1
Struktur Data dan Algoritma

2 mahasiswa perlu dinilai

┌──────────────────────┬───────────────┬──────────────┐
│ Mahasiswa            │ Perlu Dinilai │ Aksi         │
├──────────────────────┼───────────────┼──────────────┤
│ Fajar Ramadhan       │ 1 jawaban     │ [Nilai]      │
│ Siti Nurhaliza       │ 2 jawaban     │ [Nilai]      │
└──────────────────────┴───────────────┴──────────────┘
```

Jika semua selesai:

```text
✓ Semua jawaban telah dinilai
```

Untuk melihat nilai akhir gunakan halaman hasil terpisah.

---

## 3. Halaman Penilaian Esai

Gunakan layout dua kolom pada desktop.

```text
┌──────────────────────────────────────────────────────────────┐
│ ← Kembali                                                   │
│ Penilaian Tugas 1                                           │
│ Fajar Ramadhan · 231011401238                               │
│ 1 dari 2 jawaban perlu dinilai                              │
├──────────────────────────────────────────┬───────────────────┤
│                                          │ PENILAIAN         │
│ SOAL 3 · ESAI                            │                   │
│                                          │ Skor              │
│ Jelaskan perbedaan antara Stack dan      │ [ 15 ] / 20      │
│ Queue serta berikan contoh penggunaannya.│                   │
│                                          │ 75%               │
│ JAWABAN MAHASISWA                        │                   │
│                                          │ Nilai soal        │
│ ┌──────────────────────────────────────┐ │ 15,00             │
│ │ Stack adalah struktur data yang...   │ │                   │
│ │                                      │ │                   │
│ │ Queue adalah struktur data yang...   │ │                   │
│ └──────────────────────────────────────┘ │                   │
│                                          │ [Simpan &          │
│                                          │  Berikutnya]       │
└──────────────────────────────────────────┴───────────────────┘
```

### Prinsipnya

Bagian kiri = **membaca**  
Bagian kanan = **memberi nilai**

Dosen tidak perlu melihat informasi teknis lain ketika sedang menilai.

---

## 4. Bagian Pertanyaan

Tampilkan hanya informasi yang relevan:

```text
SOAL 3 · ESAI

Jelaskan perbedaan antara Stack dan Queue
serta berikan contoh penggunaannya.
```

Opsional:

```text
Maks. 20 poin
```

Jangan memenuhi area pertanyaan dengan CPMK, bobot, porsi soal, dan rumus.

---

## 5. Bagian Jawaban Mahasiswa

Jawaban harus terlihat sebagai **konten yang dibaca**, bukan sebagai input form.

```text
JAWABAN MAHASISWA

┌──────────────────────────────────────────────┐
│                                              │
│ Stack adalah struktur data yang menggunakan  │
│ konsep LIFO, sedangkan Queue menggunakan     │
│ konsep FIFO.                                 │
│                                              │
│ Stack dapat digunakan pada fitur undo,       │
│ sedangkan Queue dapat digunakan pada sistem  │
│ antrean.                                     │
│                                              │
└──────────────────────────────────────────────┘
```

Aturan UI:

- padding cukup besar;
- line-height nyaman;
- border tipis;
- tidak menggunakan textarea untuk jawaban yang hanya dibaca;
- jangan tampilkan ID database;
- jangan tampilkan metadata teknis;
- jika jawaban pendek, jangan gunakan scroll internal.

Untuk jawaban sangat panjang, area jawaban boleh memiliki batas tinggi dan scroll.

---

## 6. Panel Penilaian

Panel kanan hanya berisi hal yang dibutuhkan:

```text
PENILAIAN

Skor

[        ] / 20

[Simpan & Berikutnya]
```

Setelah skor dimasukkan:

```text
PENILAIAN

Skor

[ 15 ] / 20

75%

Nilai soal
15,00

[Simpan & Berikutnya]
```

Dosen hanya memasukkan **skor mentah**.

Persentase dan nilai soal dihitung sistem.

---

## 7. Tombol "Simpan & Berikutnya"

Gunakan tombol ini agar proses penilaian cepat:

```text
Baca jawaban
    ↓
Isi skor
    ↓
Simpan & Berikutnya
    ↓
Jawaban esai berikutnya
```

Untuk jawaban terakhir:

```text
[Simpan & Selesai]
```

Jika ada 4 jawaban esai:

```text
Jawaban 1 dari 4
Jawaban 2 dari 4
Jawaban 3 dari 4
Jawaban 4 dari 4
```

Tidak perlu menampilkan semua jawaban panjang dalam satu halaman.

---

## 8. Jika Satu Mahasiswa Memiliki Banyak Esai

Gunakan navigasi:

```text
Fajar Ramadhan

Jawaban 1 dari 4

Soal 3 · Esai
Pertanyaan...

Jawaban mahasiswa
...

Skor
[ 15 ] / 20

[Simpan & Berikutnya]
```

Kemudian otomatis berpindah ke:

```text
Jawaban 2 dari 4
```

Ini lebih bersih daripada menumpuk empat jawaban dalam satu halaman.

---

## 9. Halaman Hasil Nilai

Halaman hasil tidak perlu menampilkan jawaban mahasiswa.

```text
Hasil Tugas 1

┌──────────────────────┬────────────┬──────────────┐
│ Mahasiswa            │ Nilai      │ Status       │
├──────────────────────┼────────────┼──────────────┤
│ Ahmad Maulana        │ 85,00      │ ✓ Selesai    │
│ Dewi Anggraini       │ 78,00      │ ✓ Selesai    │
│ Fajar Ramadhan       │ 82,00      │ ✓ Selesai    │
│ Rizky Pratama        │ 90,00      │ ✓ Selesai    │
│ Siti Nurhaliza       │ 76,00      │ ✓ Selesai    │
└──────────────────────┴────────────┴──────────────┘
```

Tambahkan:

```text
[Lihat Rincian]
```

jika dosen ingin melihat bagaimana nilai terbentuk.

---

## 10. Rincian Nilai

Rincian hanya dibuka ketika diperlukan.

```text
Rincian Nilai Fajar Ramadhan

Nilai Tugas
82,00

CPMK-01
82,00

[ Lihat Perhitungan ]
```

Jika `Lihat Perhitungan` dibuka:

```text
Nilai soal
= skor / poin × porsi soal

Nilai CPMK
= jumlah nilai soal dalam CPMK

Bobot CPMK
= jumlah soal CPMK / total soal

Nilai asesmen
= Σ(nilai CPMK × bobot CPMK)
```

Rumus tidak menjadi bagian dari halaman kerja utama.

---

## 11. Pembagian Soal

| Tipe Soal | Perlu Ditampilkan ke Dosen Saat Penilaian? | Penilaian |
|---|---:|---|
| Pilihan Ganda | Tidak | Otomatis |
| PG Kompleks | Tidak | Otomatis |
| Benar/Salah | Tidak | Otomatis |
| Jodohkan | Tidak | Otomatis |
| **Esai** | **Ya** | **Manual** |

Aturan:

> Jika tidak membutuhkan tindakan dosen, jangan tampilkan pada halaman kerja penilaian.

---

## 12. Status Penilaian

Gunakan status sederhana:

| Status | Arti |
|---|---|
| `Perlu Dinilai` | Masih ada jawaban esai yang belum diberi skor |
| `Selesai` | Semua jawaban sudah memiliki skor |
| `Belum Dikerjakan` | Mahasiswa belum mengerjakan |

---

## 13. Rumus Tidak Berubah

UI yang sederhana tidak mengubah rumus sistem:

```text
porsi_soal[i]
= 100 / jumlah_soal_di_cpmk_soal[i]

persen_soal[i]
= skor[i] / poin_dosen[i]

nilai_soal[i]
= persen_soal[i] × porsi_soal[i]

nilai_cpmk[k]
= Σ nilai_soal[i]

bobot_cpmk[k]
= jumlah_soal_k / total_soal

nilai_asesmen
= Σ (nilai_cpmk[k] × bobot_cpmk[k])
```

Soal otomatis menghasilkan skor dari sistem. Soal esai menghasilkan skor setelah dosen menilai jawaban.

---

## 14. Struktur Halaman Final

```text
PENILAIAN
│
├── Daftar Asesmen
│
├── Penilaian Asesmen
│   ├── Mahasiswa yang perlu dinilai
│   └── Hasil nilai
│
└── Penilaian Esai
    ├── Identitas mahasiswa
    ├── Pertanyaan
    ├── Jawaban mahasiswa
    ├── Input skor
    └── Simpan & Berikutnya
```

---

## 15. Prinsip UX

Gunakan prinsip:

> **Tampilkan hanya informasi yang membutuhkan tindakan.**

Ketika dosen sedang menilai, layar idealnya hanya berisi:

```text
Pertanyaan
+
Jawaban mahasiswa
+
Input skor
+
Simpan & Berikutnya
```

Tidak perlu menampilkan:

```text
Soal otomatis
CPMK
Bobot CPMK
Porsi soal
Rumus panjang
Database ID
Status teknis
```

Informasi tersebut tetap tersedia pada halaman hasil atau rincian jika diperlukan.

---

## 16. Kesimpulan

Desain penilaian dibuat seperti **inbox pekerjaan dosen**:

```text
Dosen masuk
    ↓
Melihat "2 mahasiswa perlu dinilai"
    ↓
Klik [Nilai]
    ↓
Melihat pertanyaan
    ↓
Membaca jawaban mahasiswa
    ↓
Mengisi skor
    ↓
[Simpan & Berikutnya]
    ↓
Selesai
```

Inti desain:

> **Dosen membaca jawaban, memberi skor, lalu sistem mengurus perhitungannya.**

Dengan pendekatan ini, halaman penilaian tidak lagi terasa seperti halaman input data, tetapi seperti halaman kerja dosen yang fokus pada proses koreksi jawaban.
