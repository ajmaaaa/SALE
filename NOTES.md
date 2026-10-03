# NOTES: Audit di Luar Scope Fase 1

Dokumen ini mencatat temuan audit teknis di luar cakupan Fase 1 sesuai instruksi PRD-SALE-AI-Tutor-v2.md. Temuan ini **hanya dicatat** dan tidak diubah pada Fase 1.

---

## 1. Pengiriman Output Konsol / Traceback dari Frontend
- **Kondisi Saat Ini**:
  - Pada [`resources/js/app.js`](file:///home/ajmaaa/Projects/SALE/resources/js/app.js) baris 1577, payload pengiriman pesan ke AI adalah:
    ```javascript
    body: JSON.stringify({ question, code }),
    ```
  - Frontend Coding Room **belum mengirimkan** `console_output` (output terminal/traceback eksekusi Python) maupun `selected_line` secara terstruktur ke endpoint AI tutor.
  - Di sisi backend ([`app/Http/Controllers/AiTutorController.php`](file:///home/ajmaaa/Projects/SALE/app/Http/Controllers/AiTutorController.php) baris 264), validasi input hanya menerima:
    ```php
    $input = $request->validate(['question' => 'required|string|max:2000', 'code' => 'nullable|string|max:4000']);
    ```
- **Rencana Tindak Lanjut (Fase 4)**:
  - Di Fase 4, perbarui form submit di `app.js` agar mengambil potongan akhir terminal output (`console_output` maks 2.000 karakter) dan parameter `selected_line`, serta perbarui validasi Controller dan `ContextBuilder`.

---

## 2. Pengecekan Status Enrollment & Status Kelas
- **Kondisi Saat Ini**:
  - Di [`AiTutorController::authorizeAssessment`](file:///home/ajmaaa/Projects/SALE/app/Http/Controllers/AiTutorController.php):
    ```php
    $allowed = $user->hasRole(Role::DOSEN)
        ? $user->can('manage', $section)
        : ($user->hasRole(Role::MAHASISWA)
            && $section->students()->where('users.id', $user->id)->exists()
            && $assessment->status === Assessment::STATUS_PUBLISHED);
    ```
  - **Peserta Kicked / Dropped**: Relasi `$section->students()` di [`app/Models/ClassSection.php`](file:///home/ajmaaa/Projects/SALE/app/Models/ClassSection.php) baris 99 menggunakan `->withPivotValue('status', 'enrolled')`. Artinya, mahasiswa dengan status `'kicked'`, `'left'`, atau `'pending_appeal'` otomatis tidak lolos otorisasi (menghasilkan 403 Forbidden). Ini sudah aman untuk status keanggotaan peserta.
  - **Kelas Arsip (Archived Class)**: Otorisasi saat ini **belum memeriksa** apakah kelas sedang dalam status arsip (`$section->is_archived` atau semester kadaluarsa). Jika kelas telah diarsipkan, mahasiswa yang masih terdaftar tetap bisa memanggil API tutor dan menghabiskan token.
- **Rencana Tindak Lanjut (Fase 3)**:
  - Di Fase 3 (saat implementasi Policy untuk `ai_threads`), tambahkan aturan eksplisit: kelas arsip bersifat *read-only* (hanya boleh melihat riwayat chat, tidak boleh mengirim pesan baru ke AI).
