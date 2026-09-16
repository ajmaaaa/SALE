SALE — 5 ROLE MASTER DESIGN
Step 13–46 Implementation Specification

STATUS: Step 1–12 SUDAH SELESAI
START IMPLEMENTATION: Step 13 — Rubrik
BRANCH: redesign
GOAL: Sistem SALE OBE usable, terintegrasi, secure, responsive, dan siap digunakan nyata — bukan mockup/frontend-only.

0. INSTRUKSI TERAKHIR — WAJIB

JANGAN MULAI DARI STEP 1.

Step 1–12 sudah selesai.

Sebelum coding:

Periksa implementasi Step 12.

Jangan menghapus/mengganti implementasi yang sudah benar.

Identifikasi struktur Assessment, Kelas, Dosen, dan Assessment ↔ CPMK.

Integrasikan fitur berikut ke struktur existing.

Jangan membuat struktur duplikat jika entity/model sudah tersedia.

Implementasikan setiap step end-to-end: database → backend → authorization → UI → validation → testing.

Jangan lanjut ke step berikutnya sebelum step aktif selesai dan dapat diuji.

Jangan melompat step.

Jangan membuat role baru.

Jangan membuat Activity Log.

Jangan melakukan refactor besar tanpa kebutuhan.

Pertahankan design system SALE existing.

Jangan membuat mockup yang tidak terhubung backend.

Setelah setiap milestone stabil, lakukan testing sebelum lanjut.

1. 5 ROLE FINAL

SALE hanya memiliki 5 role:

Admin Sistem

Admin Prodi

Kaprodi

Dosen

Mahasiswa

Tidak boleh membuat role tambahan seperti Super Admin, Operator, Staff, Asisten, Reviewer, dll.

2. PRINSIP AUTHORIZATION

Authorization wajib dilakukan di backend, bukan hanya dengan menyembunyikan tombol frontend.

Admin Sistem

Fokus utama:

konfigurasi sistem

konfigurasi aplikasi

performa sistem

kesehatan sistem

backup dan restore

keamanan data

konfigurasi/monitoring token AI

monitoring sistem

pengelolaan user/role bila memang menjadi bagian administrasi sistem

dapat mengakses seluruh role/data untuk kebutuhan administrasi sistem sesuai permission

Admin Sistem bukan pengelola akademik harian.

Tidak perlu dashboard akademik yang kompleks.

Dashboard Admin Sistem lebih fokus pada:

system health

konfigurasi

backup

keamanan

performa

penggunaan token AI

status sistem

Catatan penting

Activity Log DIHAPUS dari scope sistem.

Jangan membuat:

Activity Log

halaman Activity Log

menu Activity Log

tabel Activity Log

service Activity Log

kecuali dependency teknis keamanan benar-benar membutuhkan audit internal yang berbeda; jangan membuat fitur Activity Log sebagai modul pengguna.

Admin Prodi

Fokus:

data akademik tingkat prodi

pengelolaan data yang menjadi kewenangan prodi

monitoring proses akademik

konfigurasi akademik yang memang menjadi kewenangannya

Tidak mengelola Rubrik Assessment milik Dosen.

Kaprodi

Fokus:

monitoring

evaluasi

rekap

dashboard akademik

monitoring CPMK/CPL

melihat hasil yang diperlukan untuk evaluasi

Kaprodi tidak boleh:

membuat Rubrik

edit Rubrik

delete Rubrik

mengubah nilai mahasiswa

mengambil alih Assessment Dosen

Kaprodi bersifat monitoring/evaluasi.

Dosen

Fokus utama proses OBE:

Kelas → Assessment → CPMK → Rubrik → Input Nilai → Rekap

Dosen dapat:

mengelola kelas yang diampu

membuat/mengelola Assessment sesuai kewenangan

menghubungkan Assessment dengan CPMK

membuat/mengelola Rubrik

mengatur Criterion

melakukan input nilai

melihat hasil penilaian

melihat rekap yang menjadi kewenangannya

Scope Dosen selalu dibatasi pada kelas/assessment yang diampu.

Mahasiswa

Fokus:

melihat kelas sendiri

melihat Assessment sendiri

melihat nilai sendiri

melihat hasil Rubrik sendiri jika dipublikasikan

melihat CPMK/CPL yang memang tersedia untuk mahasiswa

Mahasiswa tidak dapat:

membuat Assessment

mengubah Assessment

membuat Rubrik

mengubah Rubrik

menghapus Rubrik

mengubah nilai

mengakses data mahasiswa lain

3. CORE OBE FLOW

Struktur utama SALE:

Kelas
→ Assessment
→ Assessment ↔ CPMK
→ Rubrik
→ Criterion
→ Input Nilai
→ Rekap CPMK
→ Rekap CPL
→ Export

Rubrik adalah bagian dari Assessment, bukan modul akademik terpisah yang berdiri sendiri.

4. STEP 13 — RUBRIK

Rubrik bersifat OPTIONAL.

Relationship:

Assessment 1 → 0..1 Rubrik

Rubrik 1 → many Criterion

Assessment tanpa Rubrik tetap valid.

Dosen yang memiliki kewenangan terhadap Assessment/Kelas dapat mengelola Rubrik.

Criterion minimal:

nama

deskripsi

bobot

scale/skor sesuai konfigurasi existing

display order

Total bobot Criterion wajib:

100%

Validasi backend:

nama wajib

bobot wajib

bobot > 0

bobot valid

total = 100%

score mengikuti scale existing

tidak ada criterion invalid/duplicate

authorization valid

Dosen hanya dapat mengelola assessment kelasnya

Calculation:

Nilai Assessment = Σ (Skor Criterion × Bobot Criterion)

Gunakan service/backend calculation.

Jangan membuat engine OBE baru.

UI:

Assessment Detail
→ section Rubrik Penilaian
→ aktifkan Rubrik
→ tambah Criterion
→ atur bobot
→ total 100%
→ simpan

Jika Rubrik aktif tetapi belum memiliki Criterion, tampilkan empty state.

5. STEP 14 — INPUT NILAI

Bangun input nilai berdasarkan Assessment existing.

Jika Assessment menggunakan Rubrik:

Mahasiswa × Criterion × Score

Jika tidak menggunakan Rubrik:

Mahasiswa × Nilai Assessment

Backend menjadi sumber nilai final.

Validasi:

mahasiswa harus terdaftar pada kelas

assessment harus valid

criterion harus berasal dari rubric assessment tersebut

score harus mengikuti scale

unauthorized user ditolak

calculation tidak boleh hanya berasal dari frontend

Jangan membuat duplicate Assessment.

6. STEP 15 — TEMPLATE EXCEL

Sediakan template Excel berdasarkan struktur Assessment/Rubrik existing.

Template harus:

konsisten dengan database

memiliki identifier yang jelas

tidak mengubah struktur OBE

siap digunakan untuk import

Jangan membuat format Excel yang tidak dapat dipetakan kembali ke database.

7. STEP 16 — EXCEL IMPORT

Implementasikan import Excel untuk data yang memang termasuk scope.

Flow:

Upload → Parse → Validate → Preview → Confirm → Save

Jangan langsung memasukkan data tanpa validasi.

8. STEP 17 — PREVIEW & VALIDATION

Sebelum commit:

tampilkan data valid

tampilkan data invalid

tampilkan error per field/baris

jangan menyimpan data invalid

User harus dapat membatalkan import.

9. STEP 18 — REKAP CPMK

Bangun rekap CPMK berdasarkan data Assessment/Nilai existing.

Jangan membuat sumber data OBE kedua.

Calculation harus berasal dari service/backend yang konsisten.

Dosen melihat sesuai scope kelasnya.

Kaprodi dapat melihat data monitoring sesuai kewenangan.

Mahasiswa hanya melihat data dirinya.

10. STEP 19 — REKAP CPL

Bangun agregasi:

Nilai → CPMK → CPL

Gunakan relationship OBE existing.

Jangan membuat mapping CPL/CPMK baru jika sudah tersedia.

11. STEP 20 — REKAP KESELURUHAN

Dashboard/rekap keseluruhan harus menjadi agregasi data existing.

Minimal dapat membantu:

Dosen

Kaprodi

Admin Prodi

sesuai authorization masing-masing.

Jangan menampilkan data lintas kewenangan.

12. STEP 21 — EXPORT EXCEL

Export menggunakan data yang sama dengan UI/backend.

Tidak boleh ada calculation berbeda antara:

UI

database

export

13. STEP 22 — EXPORT PDF

PDF harus merepresentasikan data yang sama dengan sistem.

Pastikan:

layout readable

tabel tidak terpotong

informasi role/scope benar

data mahasiswa tidak bocor ke user lain

14. STEP 23 — TESTING

Lakukan testing:

backend

API

authorization

validation

calculation

database relationship

import

export

responsive UI

Minimal test role:

Admin Sistem

Tidak mendapatkan akses sebagai pengelola akademik hanya karena memiliki akses sistem.

Admin Prodi

Tidak dapat mengubah Rubrik Dosen.

Kaprodi

Read/monitoring, bukan CRUD Rubrik.

Dosen

CRUD hanya untuk Assessment/Kelas yang diampu.

Mahasiswa

Read data sendiri saja.

15. STEP 24 — UI/UX REVIEW

Review seluruh flow Step 13+.

Pertahankan design system SALE existing:

sidebar

navbar

typography

colors

spacing

cards

tables

badges

modal/drawer

buttons

alerts

empty states

Jangan membuat template AI/generic baru.

Prioritas:

clarity

usability

consistency

responsive

minimal decoration

16. STEP 25 — AUTHORIZATION & SECURITY REVIEW

Audit semua endpoint.

Pastikan tidak ada:

IDOR

akses lintas kelas

akses nilai mahasiswa lain

privilege escalation

frontend-only authorization

endpoint tanpa permission

data leakage

Scope authorization harus diverifikasi di backend.

17. STEP 26 — OBE CALCULATION REVIEW

Pastikan calculation:

Criterion Score
→ Assessment
→ CPMK
→ CPL

menggunakan sumber data yang konsisten.

Jangan membuat calculation engine kedua.

Pastikan rounding, scale, weight, dan normalization mengikuti konfigurasi existing.

18. STEP 27–34 — SYSTEM INTEGRATION

Setelah fitur inti stabil, lanjutkan integrasi bertahap:

Step 27

Audit seluruh relationship database.

Step 28

Audit API dan route.

Step 29

Audit frontend ↔ backend integration.

Step 30

Audit permission seluruh 5 role.

Step 31

Audit validation dan error handling.

Step 32

Audit calculation OBE.

Step 33

Audit import/export.

Step 34

End-to-end integration test.

Tidak boleh melakukan perubahan besar tanpa dependency nyata.

19. STEP 35–40 — QUALITY & USABILITY
Step 35

Responsive desktop.

Step 36

Responsive tablet.

Step 37

Responsive mobile.

Step 38

Empty/loading/error states.

Step 39

Form usability dan validation message.

Step 40

Final UI consistency review.

Semua harus tetap menggunakan design system SALE.

20. STEP 41–46 — FINALIZATION
Step 41 — Security Final Review

Review:

authentication

authorization

ownership

input validation

data isolation

API protection

Step 42 — Database Final Review

Review:

FK

unique constraint

nullable field

cascade

indexing

migration

duplicate entity

Step 43 — Calculation Final Review

Pastikan hasil:

Rubrik → Assessment → CPMK → CPL

konsisten di:

UI

API

database/service

export

Step 44 — Full Regression Test

Pastikan Step 1–12 tetap berjalan.

Kemudian test seluruh Step 13–43.

Jangan menganggap fitur lama aman hanya karena tidak diedit.

Step 45 — Final 5 Role Audit

Verifikasi kembali:

Role	Fokus
Admin Sistem	Sistem, konfigurasi, performa, backup, keamanan, AI token
Admin Prodi	Administrasi akademik tingkat prodi
Kaprodi	Monitoring/evaluasi
Dosen	Operasional OBE dan penilaian
Mahasiswa	Data dan hasil milik sendiri

Activity Log tidak termasuk sistem.

Step 46 — FINAL RELEASE READINESS

SALE dianggap siap apabila:

Step 1–12 tetap berjalan

Step 13–46 selesai

tidak ada mockup-only feature

database terintegrasi

backend terintegrasi

frontend terintegrasi

authorization valid

calculation valid

responsive

import/export valid

regression test lulus

tidak ada Activity Log

tidak ada role tambahan

tidak ada duplicate Assessment/Rubrik/OBE engine

siap dilanjutkan ke penggunaan nyata

21. ATURAN UMUM IMPLEMENTASI
WAJIB

Reuse existing entity/model.

Reuse existing design system.

Reuse existing OBE relationship.

Backend sebagai source of truth.

Authorization backend.

Validation backend + UX validation frontend.

Test sebelum pindah step.

Perubahan minimum.

Dokumentasikan perubahan penting.

DILARANG

Mulai ulang Step 1.

Mengulang Assessment CRUD.

Membuat Assessment kedua.

Membuat OBE engine baru.

Membuat role baru.

Membuat Activity Log.

Membuat UI mockup tanpa backend.

Menghapus fitur existing yang sudah benar.

Refactor besar tanpa dependency.

Melompat step.

Hard-code scale jika konfigurasi sudah tersedia.

Membuka akses lintas kelas kepada Dosen.

Membuka data mahasiswa kepada Mahasiswa lain.

22. DEFINITION OF DONE

Setiap step hanya boleh dinyatakan DONE apabila:

Database/model benar.

Backend/service benar.

Authorization benar.

UI terhubung backend.

Validation berjalan.

Error handling tersedia.

Responsive.

Test berhasil.

Tidak merusak step sebelumnya.

Tidak membuat duplicate architecture.

Setelah itu baru lanjut ke step berikutnya.

23. LAPORAN SETIAP STEP

Setelah menyelesaikan setiap step, berikan laporan singkat:

Files changed

Database/migration

Backend/API/service

Authorization

UI

Validation

Testing

Regression impact

Status step

Next step

Jangan memberikan penjelasan panjang. Fokus implementasi.

24. FINAL INSTRUCTION TO CODING AGENT

MULAI SEKARANG DARI STEP 13 — RUBRIK.

Jangan mengerjakan Step 14 sebelum Step 13 benar-benar selesai dan dapat diuji.

Jangan kembali ke Step 1.

Tujuan akhir bukan sekadar menyelesaikan checklist, tetapi menghasilkan SALE OBE yang benar-benar usable, terintegrasi, aman, konsisten dengan 5 role, dan mempertahankan implementasi Step 1–12 yang sudah benar.