# Desain: Sistem Penilaian Asesmen Berbasis CPMK

**Cakupan:** Kuis, UTS, UAS
**Status:** Draft v2 (rumus final)

---

## 1. Latar Belakang

Dosen membuat asesmen (Kuis, UTS, UAS) yang tiap soalnya memetakan ke satu CPMK. Sistem menghitung nilai asesmen (maks 100) dan nilai tiap CPMK secara otomatis. Dosen cukup memilih CPMK untuk tiap soal dan mengisi poin soal sebagai skala input skor. Semua bobot (porsi soal dan bobot CPMK) dihitung sistem.

## 2. Tujuan

1. Dosen membuat soal dengan alur sederhana: pilih tipe soal, pilih CPMK, isi poin soal.
2. Porsi soal di dalam CPMK dan bobot CPMK dihitung otomatis dari jumlah soal.
3. Nilai asesmen selalu berskala 0 sampai 100.
4. Nilai per CPMK tersedia untuk rekap OBE.
5. Mendukung 5 tipe soal: pilihan ganda, pilihan ganda kompleks, esai, benar/salah, jodohkan.

**Di luar cakupan (v1):** penggabungan Kuis + UTS + UAS menjadi nilai akhir mata kuliah, bank soal lintas mata kuliah, soal yang memetakan ke lebih dari satu CPMK.

## 3. Pengguna

| Peran | Kebutuhan |
|---|---|
| Dosen | Membuat asesmen, memilih CPMK, mengisi poin soal, menilai esai |
| Mahasiswa | Mengerjakan asesmen, melihat nilai dan nilai per CPMK |
| Admin/Kaprodi | Mengelola daftar CPMK per mata kuliah, melihat rekap capaian |

## 4. Aturan Perhitungan (inti sistem)

### 4.1 Prinsip

- Tiap CPMK berdiri sendiri dengan total **100%**.
- Soal-soal di dalam satu CPMK membagi 100% itu **sama rata** (3 soal = 33,33 per soal, 2 soal = 50 per soal). Porsi ini tetap dan tidak bisa diubah dosen.
- **Poin soal dari dosen hanya skala input skor.** Poin tidak mengubah seberapa berat sebuah soal.
- Bobot CPMK terhadap asesmen ditentukan otomatis oleh jumlah soal yang memakai CPMK itu.

### 4.2 Rumus

```
porsi_soal[i]   = 100 / jumlah_soal_di_cpmk_soal[i]        (otomatis, tetap)
persen_soal[i]  = skor[i] / poin_dosen[i]                   (0 sampai 1)
nilai_soal[i]   = persen_soal[i] × porsi_soal[i]
nilai_cpmk[k]   = Σ nilai_soal[i] untuk soal i di CPMK k    (maks 100)
bobot_cpmk[k]   = jumlah_soal_k / total_soal                (otomatis, pecahan)
nilai_asesmen   = Σ (nilai_cpmk[k] × bobot_cpmk[k])         (maks 100)
```

### 4.3 Aturan tambahan

1. Setiap soal dihitung **1 soal**, apa pun tipenya. Soal jodohkan dengan 5 pasangan tetap 1 soal.
2. Setiap soal dipetakan ke **tepat satu CPMK**.
3. Porsi soal dan bobot CPMK **tidak bisa diubah dosen**.
4. Bobot CPMK dan porsi soal disimpan sebagai pecahan (jumlah soal dan total soal), pembulatan hanya saat ditampilkan, supaya total tidak meleset dari 100 (contoh 7/15 dan 8/15 tetap tepat 100%).
5. CPMK yang hanya punya 1 soal: porsi soalnya 100.
6. Poin soal dari dosen tidak perlu berjumlah 100. Poin hanya harus lebih dari 0. Nilai bawaan poin soal adalah 100.
7. Untuk soal yang dinilai otomatis (PG, PG kompleks, B/S, jodohkan), poin dosen tidak memengaruhi hasil karena `persen_soal` sama berapa pun poinnya. Poin berperan nyata pada soal **esai** (skor manual 0 sampai poin).

### 4.4 Skor per tipe soal

| Tipe soal | Penilaian | Rumus persen soal |
|---|---|---|
| Pilihan ganda | Otomatis | benar = 100%, salah/kosong = 0% |
| Pilihan ganda kompleks | Otomatis | Mode dosen: **semua-atau-nol** (harus persis sama dengan kunci), atau **parsial** |
| Benar/Salah | Otomatis | benar = 100%, salah/kosong = 0% |
| Jodohkan | Otomatis | `pasangan_benar / jumlah_pasangan` |
| Esai | Manual oleh dosen | Dosen input skor 0 sampai `poin_dosen` (opsional pakai rubrik) |

**Mode parsial pilihan ganda kompleks:**

```
persen_soal = max(0, (jumlah_benar_dipilih − jumlah_salah_dipilih) / jumlah_kunci)
```

Contoh: kunci ada 3 (A, C, D). Pilih A dan C (2 benar, 0 salah) menghasilkan 2/3. Pilih A, C, B (2 benar, 1 salah) menghasilkan 1/3.

## 5. Alur Dosen Membuat Asesmen

1. Dosen memilih mata kuliah, lalu membuat asesmen baru (Kuis / UTS / UAS).
2. Sistem menampilkan daftar CPMK milik mata kuliah tersebut.
3. Dosen menambah soal: pilih **tipe soal**, isi pertanyaan dan kunci, pilih **CPMK** dari dropdown, isi **poin soal** (bawaan 100).
4. Sistem menampilkan **panel ringkasan** yang diperbarui otomatis:

| CPMK | Jumlah soal | Bobot CPMK | Porsi per soal |
|---|---|---|---|
| CPMK 1 | 3 | 60% | 33,33 |
| CPMK 2 | 2 | 40% | 50 |

5. Tombol **Publikasikan** aktif jika semua validasi lolos.

## 6. Kebutuhan Fungsional

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-01 | Dosen dapat membuat asesmen bertipe Kuis, UTS, atau UAS | Wajib |
| FR-02 | Dosen dapat membuat soal dengan 5 tipe yang disebutkan | Wajib |
| FR-03 | Dosen memilih CPMK tiap soal dari daftar CPMK mata kuliah | Wajib |
| FR-04 | Sistem menghitung bobot CPMK otomatis dari jumlah soal | Wajib |
| FR-05 | Sistem menghitung porsi soal di dalam CPMK otomatis (100 / jumlah soal) | Wajib |
| FR-06 | Dosen mengisi poin soal sebagai skala input skor | Wajib |
| FR-07 | Penilaian otomatis untuk PG, PG kompleks, B/S, jodohkan | Wajib |
| FR-08 | Penilaian manual esai dengan input skor 0 sampai poin soal | Wajib |
| FR-09 | Asesmen hanya bisa dipublikasikan jika semua validasi lolos | Wajib |
| FR-10 | Sistem menghitung nilai per CPMK dan nilai asesmen setelah penilaian | Wajib |
| FR-11 | Mahasiswa melihat nilai asesmen dan nilai per CPMK | Wajib |
| FR-12 | Dosen dapat mengubah skor esai setelah dinilai (dengan log perubahan) | Sebaiknya ada |
| FR-13 | Ekspor rekap nilai dan nilai CPMK (Excel/CSV) | Opsional |

## 7. Validasi

| Kode | Aturan | Pesan ke dosen |
|---|---|---|
| V-01 | Setiap soal punya tepat 1 CPMK | "Pilih CPMK untuk soal ini" |
| V-02 | Poin soal > 0 | "Poin soal harus lebih dari 0" |
| V-03 | Skor soal: 0 ≤ skor ≤ poin soal | "Skor harus antara 0 dan {poin}" |
| V-04 | Asesmen minimal 1 soal | "Tambahkan minimal 1 soal" |
| V-05 | Kunci jawaban wajib untuk tipe otomatis | "Isi kunci jawaban" |
| V-06 | CPMK tanpa soal tidak dihitung (bobot 0) | Peringatan saja |
| V-07 | Soal yang sudah dikerjakan tidak boleh diubah CPMK-nya | "Asesmen sudah dikerjakan, gunakan fitur revisi" |

Tidak ada validasi "total poin = 100", karena porsi soal dan bobot CPMK sudah dijamin 100% oleh sistem.

## 8. Model Data (usulan)

```
mata_kuliah   (id, kode, nama)
cpmk          (id, mata_kuliah_id, kode, deskripsi)

asesmen       (id, mata_kuliah_id, jenis[KUIS|UTS|UAS], judul, status[DRAFT|PUBLISHED|CLOSED])

soal          (id, asesmen_id, urutan, tipe[PG|PG_KOMPLEKS|ESAI|BENAR_SALAH|JODOHKAN],
               pertanyaan, cpmk_id, poin, mode_skor[SEMUA_ATAU_NOL|PARSIAL])
soal_opsi     (id, soal_id, teks, is_kunci)              -- PG, PG kompleks, B/S
soal_pasangan (id, soal_id, kiri, kanan)                 -- jodohkan
soal_rubrik   (id, soal_id, kriteria, skor_maks)         -- esai (opsional)

pengerjaan    (id, asesmen_id, mahasiswa_id, status, nilai_asesmen)
jawaban       (id, pengerjaan_id, soal_id, jawaban_json, skor, status_nilai[OTOMATIS|MENUNGGU|DINILAI])
nilai_cpmk    (id, pengerjaan_id, cpmk_id, nilai, jumlah_soal, total_soal)
```

`porsi_soal` dan `bobot_cpmk` tidak disimpan sebagai angka desimal. Keduanya dihitung dari `jumlah_soal` dan `total_soal` agar tidak ada masalah pembulatan.

## 9. Alur Perhitungan di Backend

```
1. Ambil semua soal asesmen
2. Hitung jumlah soal per CPMK dan total soal
3. Untuk tiap jawaban mahasiswa:
     - Tipe otomatis  -> hitung skor sesuai tabel 4.4
     - Esai           -> tunggu input dosen (status MENUNGGU)
4. Jika semua soal sudah bernilai:
     nilai_soal[i]  = skor[i] / poin[i] × (100 / jumlah_soal_cpmk)
     nilai_cpmk[k]  = Σ nilai_soal[i] di CPMK k
     nilai_asesmen  = Σ (nilai_cpmk[k] × jumlah_soal_k / total_soal)
5. Simpan nilai_cpmk dan nilai_asesmen
```

Pseudocode:

```python
def hitung_asesmen(soal_list, skor):
    total_soal = len(soal_list)

    # jumlah soal per CPMK
    jumlah = {}
    for s in soal_list:
        jumlah[s["cpmk"]] = jumlah.get(s["cpmk"], 0) + 1

    nilai_cpmk = {k: 0.0 for k in jumlah}
    for s in soal_list:
        i, k, poin = s["id"], s["cpmk"], s["poin"]
        if poin <= 0:
            raise ValueError(f"Poin soal {i} harus > 0")
        if not (0 <= skor[i] <= poin):
            raise ValueError(f"Skor soal {i} harus 0 - {poin}")
        porsi = 100 / jumlah[k]                # porsi_soal (otomatis)
        nilai_cpmk[k] += skor[i] / poin * porsi

    nilai_asesmen = sum(
        nilai_cpmk[k] * jumlah[k] / total_soal for k in jumlah   # bobot_cpmk
    )
    return nilai_asesmen, nilai_cpmk
```

## 10. Contoh Lengkap

**Kuis 1, 5 soal, 2 CPMK, tipe soal campuran.**

Otomatis dari sistem:

- Bobot CPMK 1 = 3/5 = **60%**, porsi per soal = 100/3 = **33,33**
- Bobot CPMK 2 = 2/5 = **40%**, porsi per soal = 100/2 = **50**

| Soal | Tipe | CPMK | Poin dosen | Jawaban Budi | Skor | Persen | Porsi | Nilai soal |
|---|---|---|---|---|---|---|---|---|
| 1 | Pilihan ganda | 1 | 5 | Benar | 5 | 100% | 33,33 | 33,33 |
| 2 | PG kompleks (parsial, kunci 3) | 1 | 15 | 2 benar, 0 salah | 10 | 66,67% | 33,33 | 22,22 |
| 3 | Esai | 1 | 20 | Dinilai dosen 15 | 15 | 75% | 33,33 | 25 |
| 4 | Benar/Salah | 2 | 20 | Salah | 0 | 0% | 50 | 0 |
| 5 | Jodohkan (5 pasangan) | 2 | 40 | 4 pasangan benar | 32 | 80% | 50 | 40 |

- Nilai CPMK 1 = 33,33 + 22,22 + 25 = **80,56**
- Nilai CPMK 2 = 0 + 40 = **40**
- Nilai Kuis = (80,56 × 0,6) + (40 × 0,4) = 48,33 + 16 = **64,33**

**Contoh bobot CPMK untuk 15 soal:** CPMK 1 punya 7 soal dan CPMK 2 punya 8 soal. Bobot CPMK 1 = 7/15 = 46,67%, bobot CPMK 2 = 8/15 = 53,33%, total tepat 100%. Porsi soal CPMK 1 = 100/7 = 14,29, porsi soal CPMK 2 = 100/8 = 12,5.

## 11. Kasus Tepi

| Kasus | Penanganan |
|---|---|
| Semua soal memakai CPMK yang sama | Bobot CPMK = 100%, nilai asesmen = nilai CPMK itu |
| Jumlah soal tidak habis dibagi (7/15, 8/15, 100/3) | Simpan pecahan, tampilkan hasil dibulatkan 2 desimal |
| Dosen menambah/menghapus soal saat draft | Porsi soal dan bobot CPMK dihitung ulang otomatis |
| Esai belum dinilai | Nilai asesmen berstatus "Menunggu penilaian", tidak dihitung final |
| Mahasiswa tidak menjawab | Skor 0 |
| Soal berpoin besar dan berpoin kecil di CPMK yang sama | Tetap berbobot sama, karena poin hanya skala input skor |
| Soal dibatalkan setelah dikerjakan | Butuh fitur revisi/regrade (lihat pertanyaan terbuka) |
| PG kompleks parsial menghasilkan nilai negatif | Dibatasi minimal 0 |

## 12. Kriteria Penerimaan

1. Kuis 15 soal (CPMK 1 = 7 soal, CPMK 2 = 8 soal) menghasilkan bobot CPMK 46,67% dan 53,33% dengan total tepat 100%.
2. CPMK dengan 3 soal memberi porsi 33,33 per soal, CPMK dengan 2 soal memberi porsi 50 per soal.
3. Contoh pada Bagian 10 menghasilkan nilai CPMK 1 = 80,56, CPMK 2 = 40, dan nilai kuis **64,33**.
4. Skor soal tidak dapat diinput melebihi poin soal.
5. Dosen tidak dapat mengubah porsi soal maupun bobot CPMK.
6. Kelima tipe soal dapat dibuat, dikerjakan, dan dinilai sesuai Bagian 4.4.
7. Nilai per CPMK dan nilai asesmen tampil di halaman hasil mahasiswa.

## 13. Pertanyaan Terbuka

1. Apakah satu soal boleh memetakan ke **lebih dari satu CPMK** (misalnya esai besar)? Rumus v1 mengasumsikan satu CPMK per soal.
2. Apakah PG kompleks memakai mode **semua-atau-nol** atau **parsial** secara default?
3. Apakah ada **pengurangan nilai untuk jawaban salah** (negative marking) pada PG dan B/S?
4. Bagaimana **penggabungan Kuis + UTS + UAS** menjadi nilai akhir mata kuliah, dan siapa yang menentukan bobot ketiganya?
5. Jika ada beberapa kuis dalam satu mata kuliah, bagaimana **nilai CPMK** dirata-rata lintas kuis?
6. Bagaimana kebijakan **revisi kunci jawaban** setelah asesmen dikerjakan (regrade otomatis)?
