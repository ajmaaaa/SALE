-- SALE video production demo data
-- Run after all Laravel migrations on a dedicated local or staging database
-- Demo password for every account is DemoSale2026!
--
-- YouTube links used in this file are publicly accessible videos curated per course:
--   IF204 Struktur Data  : youtube.com/watch?v=PEfAxWFY14Q  (BST - Dr. Achmad Solichin)
--                          youtube.com/watch?v=R2j9v7P59hI  (Coding BST - Josef Bernadi)
--   IF230 RPL            : Universitas Teknokrat – Introduction to Software Engineering
--   IF260 Pemrograman Web: Web Programming UNPAS (Sandhika Galih) – HTML Dasar playlist

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- ─── CLEAN-UP (idempotent re-run) ─────────────────────────────────────────────
DELETE FROM activity_logs             WHERE id BETWEEN 910000 AND 919999;
DELETE FROM ai_api_calls              WHERE id BETWEEN 910000 AND 919999;
DELETE FROM ai_messages               WHERE id BETWEEN 910000 AND 919999;
DELETE FROM ai_threads                WHERE id BETWEEN 910000 AND 919999;
DELETE FROM chat_notifications        WHERE id BETWEEN 910000 AND 919999;
DELETE FROM message_mentions          WHERE id BETWEEN 910000 AND 919999;
DELETE FROM messages                  WHERE id BETWEEN 910000 AND 919999;
DELETE FROM room_members              WHERE id BETWEEN 910000 AND 919999;
DELETE FROM rooms                     WHERE id BETWEEN 910000 AND 919999;
DELETE FROM course_discussions        WHERE id BETWEEN 910000 AND 919999;
DELETE FROM student_rubric_scores     WHERE id BETWEEN 910000 AND 919999;
DELETE FROM rubric_criteria           WHERE id BETWEEN 910000 AND 919999;
DELETE FROM rubrics                   WHERE id BETWEEN 910000 AND 919999;
DELETE FROM student_assessment_cpmk_scores WHERE id BETWEEN 910000 AND 919999;
DELETE FROM student_assessment_scores WHERE id BETWEEN 910000 AND 919999;
DELETE FROM assessment_attempts       WHERE id BETWEEN 910000 AND 919999;
DELETE FROM submission_answers        WHERE id BETWEEN 910000 AND 919999;
DELETE FROM attachments               WHERE id BETWEEN 910000 AND 919999;
DELETE FROM submissions               WHERE id BETWEEN 910000 AND 919999;
DELETE FROM assessment_cpmk           WHERE id BETWEEN 910000 AND 919999;
DELETE FROM assessments               WHERE id BETWEEN 910000 AND 919999;
DELETE FROM class_section_student     WHERE id BETWEEN 910000 AND 919999;
DELETE FROM class_section_dosen_anggota WHERE id BETWEEN 910000 AND 919999;
DELETE FROM class_sections            WHERE id BETWEEN 910000 AND 919999;
DELETE FROM cpmk_mata_kuliah          WHERE id BETWEEN 910000 AND 919999;
DELETE FROM cpl_cpmk                  WHERE id BETWEEN 910000 AND 919999;
DELETE FROM cpmks                     WHERE id BETWEEN 910000 AND 919999;
DELETE FROM cpls                      WHERE id BETWEEN 910000 AND 919999;
DELETE FROM mata_kuliahs              WHERE id BETWEEN 910000 AND 919999;
DELETE FROM role_user                 WHERE id BETWEEN 910000 AND 919999;
DELETE FROM users                     WHERE id BETWEEN 910000 AND 919999;
DELETE FROM semesters                 WHERE id BETWEEN 910000 AND 919999;
DELETE FROM prodis                    WHERE id BETWEEN 910000 AND 919999;

-- ─── ROLES (upsert; shared table) ─────────────────────────────────────────────
INSERT INTO roles (name, label, created_at, updated_at) VALUES
  ('admin',        'Admin Sistem',        NOW(), NOW()),
  ('admin_prodi',  'Admin Program Studi', NOW(), NOW()),
  ('dosen',        'Dosen',               NOW(), NOW()),
  ('mahasiswa',    'Mahasiswa',           NOW(), NOW())
ON DUPLICATE KEY UPDATE label = VALUES(label), updated_at = NOW();

SET @role_admin        = (SELECT id FROM roles WHERE name = 'admin'        LIMIT 1);
SET @role_admin_prodi  = (SELECT id FROM roles WHERE name = 'admin_prodi'  LIMIT 1);
SET @role_dosen        = (SELECT id FROM roles WHERE name = 'dosen'        LIMIT 1);
SET @role_mahasiswa    = (SELECT id FROM roles WHERE name = 'mahasiswa'    LIMIT 1);

-- ─── PROGRAM STUDI ────────────────────────────────────────────────────────────
INSERT INTO prodis (id, code, name, created_at, updated_at) VALUES
  (910201, 'IF-DEMO', 'Teknik Informatika Demo Video', NOW(), NOW()),
  (910202, 'SI-DEMO', 'Sistem Informasi Demo Video',   NOW(), NOW());

-- ─── SEMESTER ─────────────────────────────────────────────────────────────────
INSERT INTO semesters (id, code, name, academic_year, term, is_active, created_at, updated_at) VALUES
  (910301, '2026-DEMO-1', 'Ganjil 2026/2027 Demo Video', '2026/2027', 1, 1, NOW(), NOW()),
  (910302, '2026-DEMO-2', 'Genap 2026/2027 Demo Video',  '2026/2027', 2, 0, NOW(), NOW());

-- ─── USERS ────────────────────────────────────────────────────────────────────
-- bcrypt hash of DemoSale2026!
SET @pw = '$2y$12$SCn5FZnk4i78u7F0m5ORNeHUIO6jZDbuyrIQWTxdF/lfygM3gK1Hu';

INSERT INTO users (id, name, email, email_verified_at, must_change_password, is_active, password, role_id, prodi_id, managing_prodi_id, nim_nidn, angkatan, remember_token, created_at, updated_at, notification_preferences, profile_photo_path) VALUES
  -- Admin
  (910101, 'Admin Sistem SALE Demo',    'admin.video@sale.demo',           NOW(), 0, 1, @pw, @role_admin,       NULL,   NULL,   'ADM-DEMO-01',  NULL, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  -- Admin Prodi
  (910102, 'Nadia Putri, M.Kom.',       'adminprodi.video@sale.demo',      NOW(), 0, 1, @pw, @role_admin_prodi, 910201, 910201, 'AP-DEMO-01',   NULL, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  -- Dosen
  (910103, 'Dr. Budi Santoso, M.Kom.', 'dosen.video@sale.demo',           NOW(), 0, 1, @pw, @role_dosen,       910201, NULL,   '0012048501',   NULL, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910104, 'Sari Lestari, S.Kom., M.Cs.','dosen.anggota.video@sale.demo', NOW(), 0, 1, @pw, @role_dosen,       910201, NULL,   '0018079002',   NULL, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910109, 'Reza Firmansyah, M.T.',    'dosen2.video@sale.demo',          NOW(), 0, 1, @pw, @role_dosen,       910201, NULL,   '0025059103',   NULL, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  -- Mahasiswa IF204 & IF230
  (910105, 'Ahmad Maulana',      'ahmad.video@sale.demo',   NOW(), 0, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401234', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910106, 'Siti Nurhaliza',     'siti.video@sale.demo',    NOW(), 0, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401235', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910107, 'Rizky Pratama',      'rizky.video@sale.demo',   NOW(), 0, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401236', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910108, 'Dewi Anggraini',     'dewi.video@sale.demo',    NOW(), 1, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401237', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910110, 'Fajar Nugroho',      'fajar.video@sale.demo',   NOW(), 0, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401238', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910111, 'Maya Kusuma',        'maya.video@sale.demo',    NOW(), 0, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401239', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL),
  (910112, 'Dimas Prasetyo',     'dimas.video@sale.demo',   NOW(), 0, 1, @pw, @role_mahasiswa, 910201, NULL, '231011401240', 2023, NULL, NOW(), NOW(), '{"grade":true,"forum":true}', NULL);

-- ─── ROLE_USER ────────────────────────────────────────────────────────────────
INSERT INTO role_user (id, user_id, role_id, created_at, updated_at) VALUES
  (910001, 910101, @role_admin,       NOW(), NOW()),
  (910002, 910102, @role_admin_prodi, NOW(), NOW()),
  (910003, 910103, @role_dosen,       NOW(), NOW()),
  (910004, 910103, @role_admin_prodi, NOW(), NOW()),
  (910005, 910104, @role_dosen,       NOW(), NOW()),
  (910006, 910109, @role_dosen,       NOW(), NOW()),
  (910007, 910105, @role_mahasiswa,   NOW(), NOW()),
  (910008, 910106, @role_mahasiswa,   NOW(), NOW()),
  (910009, 910107, @role_mahasiswa,   NOW(), NOW()),
  (910010, 910108, @role_mahasiswa,   NOW(), NOW()),
  (910011, 910110, @role_mahasiswa,   NOW(), NOW()),
  (910012, 910111, @role_mahasiswa,   NOW(), NOW()),
  (910013, 910112, @role_mahasiswa,   NOW(), NOW());

-- ─── MATA KULIAH ──────────────────────────────────────────────────────────────
INSERT INTO mata_kuliahs (id, prodi_id, code, name, sks, semester_paket, is_lintas_prodi, created_at, updated_at) VALUES
  (910401, 910201, 'IF204-DEMO', 'Struktur Data dan Algoritma', 3, 3, 0, NOW(), NOW()),
  (910402, 910201, 'IF230-DEMO', 'Rekayasa Perangkat Lunak',    3, 4, 0, NOW(), NOW()),
  (910403, 910201, 'IF260-DEMO', 'Pemrograman Web',             3, 4, 1, NOW(), NOW());

-- ─── CPL ──────────────────────────────────────────────────────────────────────
INSERT INTO cpls (id, prodi_id, code, description, created_at, updated_at) VALUES
  (910501, 910201, 'CPL-DEMO-01', 'Mampu menerapkan pengetahuan komputasi untuk menyelesaikan masalah secara sistematis.', NOW(), NOW()),
  (910502, 910201, 'CPL-DEMO-02', 'Mampu merancang dan menguji solusi perangkat lunak yang berkualitas.',                  NOW(), NOW()),
  (910503, 910201, 'CPL-DEMO-03', 'Mampu berkomunikasi dan bekerja sama secara profesional.',                              NOW(), NOW()),
  (910504, 910201, 'CPL-DEMO-04', 'Mampu mengembangkan aplikasi berbasis web menggunakan teknologi terkini.',              NOW(), NOW());

-- ─── CPMK ─────────────────────────────────────────────────────────────────────
INSERT INTO cpmks (id, prodi_id, mata_kuliah_id, code, description, threshold, created_at, updated_at) VALUES
  -- IF204
  (910601, 910201, 910401, 'CPMK-IF204-01', 'Menjelaskan konsep struktur data tree dan traversal.', 65, NOW(), NOW()),
  (910602, 910201, 910401, 'CPMK-IF204-02', 'Mengimplementasikan operasi Binary Search Tree.',       70, NOW(), NOW()),
  (910603, 910201, 910401, 'CPMK-IF204-03', 'Menganalisis kompleksitas dan kualitas solusi algoritmik.', 70, NOW(), NOW()),
  -- IF230
  (910611, 910201, 910402, 'CPMK-IF230-01', 'Memahami konsep dan proses rekayasa perangkat lunak.', 65, NOW(), NOW()),
  (910612, 910201, 910402, 'CPMK-IF230-02', 'Merancang sistem menggunakan UML dan dokumentasi kebutuhan.', 70, NOW(), NOW()),
  (910613, 910201, 910402, 'CPMK-IF230-03', 'Menerapkan metodologi pengembangan perangkat lunak (Agile/Scrum).', 70, NOW(), NOW()),
  -- IF260
  (910621, 910201, 910403, 'CPMK-IF260-01', 'Membangun halaman web dengan HTML dan CSS yang valid dan aksesibel.', 65, NOW(), NOW()),
  (910622, 910201, 910403, 'CPMK-IF260-02', 'Menerapkan interaktivitas dengan JavaScript dan manipulasi DOM.', 70, NOW(), NOW()),
  (910623, 910201, 910403, 'CPMK-IF260-03', 'Mengembangkan aplikasi web full-stack sederhana.', 70, NOW(), NOW());

-- ─── CPL-CPMK ─────────────────────────────────────────────────────────────────
INSERT INTO cpl_cpmk (id, cpl_id, cpmk_id, weight, created_at, updated_at) VALUES
  -- IF204
  (910001, 910501, 910601, 100, NOW(), NOW()),
  (910002, 910502, 910602,  70, NOW(), NOW()),
  (910003, 910502, 910603,  30, NOW(), NOW()),
  (910004, 910503, 910603, 100, NOW(), NOW()),
  -- IF230
  (910011, 910501, 910611, 100, NOW(), NOW()),
  (910012, 910502, 910612,  60, NOW(), NOW()),
  (910013, 910502, 910613,  40, NOW(), NOW()),
  (910014, 910503, 910613, 100, NOW(), NOW()),
  -- IF260
  (910021, 910504, 910621,  50, NOW(), NOW()),
  (910022, 910504, 910622,  30, NOW(), NOW()),
  (910023, 910504, 910623,  20, NOW(), NOW());

-- ─── CPMK-MATAKULIAH ──────────────────────────────────────────────────────────
INSERT INTO cpmk_mata_kuliah (id, mata_kuliah_id, cpmk_id, cpl_id, created_at, updated_at) VALUES
  (910001, 910401, 910601, 910501, NOW(), NOW()),
  (910002, 910401, 910602, 910502, NOW(), NOW()),
  (910003, 910401, 910603, 910502, NOW(), NOW()),
  (910011, 910402, 910611, 910501, NOW(), NOW()),
  (910012, 910402, 910612, 910502, NOW(), NOW()),
  (910013, 910402, 910613, 910502, NOW(), NOW()),
  (910021, 910403, 910621, 910504, NOW(), NOW()),
  (910022, 910403, 910622, 910504, NOW(), NOW()),
  (910023, 910403, 910623, 910504, NOW(), NOW());

-- ─── CLASS SECTIONS ───────────────────────────────────────────────────────────
INSERT INTO class_sections (id, mata_kuliah_id, semester_id, dosen_id, dosen_pendamping_id, section_code, capacity, enrollment_code, learning_payload, archived_at, archived_by, created_at, updated_at) VALUES
  -- IF204 Kelas A (kelas utama dengan team teaching)
  (910701, 910401, 910301, 910103, 910104, 'A', 40, 'SALEIF26',
   '{"description":"Kelas utama produksi video SALE - Struktur Data dan Algoritma","room":"Laboratorium Komputasi 1"}',
   NULL, NULL, NOW(), NOW()),
  -- IF230 Kelas A
  (910702, 910402, 910301, 910103, NULL,   'A', 35, 'SALERPL2',
   '{"description":"Rekayasa Perangkat Lunak - Kelas A","room":"Ruang Kuliah 301"}',
   NULL, NULL, NOW(), NOW()),
  -- IF260 Kelas B (dosen berbeda)
  (910703, 910403, 910301, 910109, NULL,   'B', 35, 'SALEWEB3',
   '{"description":"Pemrograman Web - Kelas B (lintas prodi)","room":"Laboratorium Komputasi 2"}',
   NULL, NULL, NOW(), NOW()),
  -- IF204 Kelas B (diarsipkan, untuk demo fitur arsip)
  (910704, 910401, 910302, 910103, NULL,   'B', 30, 'SALEIF2B',
   '{"description":"Kelas demo arsip semester lalu"}',
   DATE_SUB(NOW(), INTERVAL 30 DAY), 910102, NOW(), NOW());

-- ─── DOSEN ANGGOTA ────────────────────────────────────────────────────────────
INSERT INTO class_section_dosen_anggota (id, class_section_id, dosen_id, created_at, updated_at) VALUES
  (910001, 910701, 910104, NOW(), NOW());

-- ─── PESERTA ──────────────────────────────────────────────────────────────────
-- IF204 Kelas A: 6 mahasiswa
INSERT INTO class_section_student (id, class_section_id, mahasiswa_id, status, kick_count, kicked_at, kicked_by, kick_reason, dropped_at, is_locked, created_at, updated_at) VALUES
  (910001, 910701, 910105, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910002, 910701, 910106, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910003, 910701, 910107, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910004, 910701, 910108, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910005, 910701, 910110, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910006, 910701, 910111, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  -- IF230 Kelas A: 4 mahasiswa
  (910011, 910702, 910105, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910012, 910702, 910106, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910013, 910702, 910110, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910014, 910702, 910112, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  -- IF260 Kelas B: 4 mahasiswa
  (910021, 910703, 910107, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910022, 910703, 910108, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910023, 910703, 910111, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
  (910024, 910703, 910112, 'enrolled', 0, NULL, NULL, NULL, NULL, 0, NOW(), NOW());

-- ═══════════════════════════════════════════════════════════════════════════════
-- ASSESSMENTS — IF204 (Struktur Data)
-- YouTube materi: https://www.youtube.com/watch?v=PEfAxWFY14Q
--                 https://www.youtube.com/watch?v=R2j9v7P59hI
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO assessments (id, class_section_id, code, name, type, description, learning_payload, final_weight, uses_rubric, status, due_at, published_at, allow_late, created_at, updated_at) VALUES

-- 1. Materi: menggunakan pinned_media YouTube + lampiran PDF & gambar
(911001, 910701, 'MATERI-IF204-01', 'Pengenalan Binary Search Tree', 'materi',
 'Materi multimedia: video YouTube, PDF modul, dan gambar diagram.',
 '{"module":"Minggu 1 – Konsep Tree","body":"Tonton video YouTube berikut lalu baca modul PDF sebelum mengerjakan latihan. Perhatikan perbedaan Binary Tree dan BST.","attachments":["10000000-0000-4000-8000-000000000001","20000000-0000-4000-8000-000000000002","30000000-0000-4000-8000-000000000003"],"pin_video":true,"pinned_media":"https://www.youtube.com/watch?v=PEfAxWFY14Q","pinned_media_type":"youtube","pinned_media_kind":"video","youtube_links":["https://www.youtube.com/watch?v=PEfAxWFY14Q","https://www.youtube.com/watch?v=R2j9v7P59hI"],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- 2. Materi: Implementasi Coding BST (video kedua sebagai pinned)
(911010, 910701, 'MATERI-IF204-02', 'Coding Binary Search Tree – Praktikum', 'materi',
 'Materi praktikum coding BST dengan video panduan.',
 '{"module":"Minggu 2 – Praktikum BST","body":"Ikuti video coding BST berikut langkah demi langkah. Siapkan IDE Python sebelum menonton.","attachments":["20000000-0000-4000-8000-000000000002"],"pin_video":true,"pinned_media":"https://www.youtube.com/watch?v=R2j9v7P59hI","pinned_media_type":"youtube","pinned_media_kind":"video","youtube_links":["https://www.youtube.com/watch?v=R2j9v7P59hI"],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- 3. Pengumuman
(911002, 910701, 'PENGUMUMAN-IF204-01', 'Perubahan Ruang Perkuliahan Minggu 3', 'pengumuman',
 'Pertemuan minggu depan dipindah ke Laboratorium Komputasi 1.',
 '{"module":"Informasi Kelas","body":"Bawa laptop dan pastikan Python sudah terinstal. Kita akan langsung praktikum BST.","attachments":[],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- 4. Tugas
(911003, 910701, 'TUGAS-IF204-01', 'Laporan Analisis Kompleksitas Algoritma', 'tugas',
 'Kumpulkan analisis kompleksitas operasi BST dalam kondisi seimbang dan skewed.',
 '{"module":"Minggu 3 – Analisis Algoritma","body":"Bandingkan kompleksitas waktu dan ruang pada kondisi seimbang dan skewed. Sertakan diagram ilustrasi.","attachments":["40000000-0000-4000-8000-000000000004"],"formats":["file","image","link","text"],"question_type":"uraian","manual_cpmk_weights":{"CPMK-IF204-03":100},"points":100,"scoring_mode":"manual_cpmk"}',
 15, 0, 'published', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), 1, NOW(), NOW()),

-- 5. Coding
(911004, 910701, 'CODING-IF204-01', 'Implementasi Binary Search Tree', 'coding',
 'Lengkapi operasi insert, search, dan traversal pada BST.',
 '{"module":"Minggu 4 – Praktikum Coding","body":"Kerjakan setiap langkah secara berurutan. Gunakan video materi sebagai panduan.","task_mode":"coding","question_type":"coding","code_language":"python","ai_enabled":true,"duration_enabled":false,"points":100,"coding_steps":[{"title":"Membuat Node","body":"Buat kelas Node dengan atribut value, left, dan right","cpmk":"CPMK-IF204-02","points":25,"attachments":["20000000-0000-4000-8000-000000000002"]},{"title":"Operasi Insert","body":"Implementasikan penyisipan rekursif sesuai aturan BST","cpmk":"CPMK-IF204-02","points":40,"attachments":[]},{"title":"Traversal Inorder","body":"Kembalikan urutan nilai dari traversal inorder","cpmk":"CPMK-IF204-03","points":35,"attachments":[]}],"questions":[{"type":"coding","prompt":"Membuat Node","cpmk":"CPMK-IF204-02","points":25},{"type":"coding","prompt":"Operasi Insert","cpmk":"CPMK-IF204-02","points":40},{"type":"coding","prompt":"Traversal Inorder","cpmk":"CPMK-IF204-03","points":35}]}',
 20, 0, 'published', DATE_ADD(NOW(), INTERVAL 18 DAY), NOW(), 0, NOW(), NOW()),

-- 6. Kuis
(911005, 910701, 'KUIS-IF204-01', 'Kuis Lengkap Struktur Data', 'kuis',
 'Kuis seluruh jenis soal non-coding (30 menit).',
 '{"module":"Minggu 5 – Evaluasi","body":"Kerjakan lima soal dalam waktu tiga puluh menit. Tidak ada referensi terbuka.","task_mode":"quiz","formats":["cbt"],"duration_enabled":true,"duration_minutes":30,"points":100,"questions":[{"id":"q1","type":"pilihan","prompt":"Traversal yang menghasilkan urutan menaik pada BST adalah","options":["Inorder","Preorder","Postorder","Level order"],"correct_answer":"Inorder","points":20,"cpmk":"CPMK-IF204-01","cpl":"CPL-DEMO-01"},{"id":"q2","type":"kompleks","prompt":"Pilih karakteristik BST seimbang","options":["Pencarian rata-rata logaritmik","Inorder terurut","Selalu memiliki dua anak","Tidak memiliki akar"],"correct_answers":["Pencarian rata-rata logaritmik","Inorder terurut"],"score_mode":"parsial","points":20,"cpmk":"CPMK-IF204-01","cpl":"CPL-DEMO-01"},{"id":"q3","type":"benar_salah","prompt":"BST dari data terurut dapat menjadi skewed","correct_answer":"Benar","points":20,"cpmk":"CPMK-IF204-01","cpl":"CPL-DEMO-01"},{"id":"q4","type":"mencocokkan","prompt":"Cocokkan traversal dan urutannya","matching":[{"left":"Inorder","right":"Kiri–Akar–Kanan"},{"left":"Preorder","right":"Akar–Kiri–Kanan"}],"points":20,"cpmk":"CPMK-IF204-01","cpl":"CPL-DEMO-01"},{"id":"q5","type":"uraian","prompt":"Jelaskan alasan penyeimbangan BST penting untuk performa","essay_guide":"Jelaskan dampak tinggi tree terhadap kompleksitas operasi","points":20,"cpmk":"CPMK-IF204-03","cpl":"CPL-DEMO-02"}]}',
 10, 0, 'published', DATE_ADD(NOW(), INTERVAL 21 DAY), NOW(), 0, NOW(), NOW()),

-- 7. UTS
(911006, 910701, 'UTS-IF204', 'Ujian Tengah Semester – Struktur Data', 'uts',
 'Ujian terjadwal berbasis paket soal (90 menit).',
 '{"module":"UTS","body":"Baca instruksi dengan seksama sebelum memulai. Tidak boleh kerja sama.","task_mode":"quiz","duration_enabled":true,"duration_minutes":90,"points":100,"questions":[{"id":"uts1","type":"uraian","prompt":"Analisis perbedaan kompleksitas BST seimbang vs skewed pada operasi pencarian, penyisipan, dan penghapusan","essay_guide":"Nilai berdasarkan ketepatan analisis dan contoh konkret","points":50,"cpmk":"CPMK-IF204-03","cpl":"CPL-DEMO-02"},{"id":"uts2","type":"pilihan","prompt":"Operasi mana yang membutuhkan O(n) pada worst case BST?","options":["Pencarian","Traversal Inorder","Penyisipan node daun","Akses indeks"],"correct_answer":"Traversal Inorder","points":25,"cpmk":"CPMK-IF204-01","cpl":"CPL-DEMO-01"},{"id":"uts3","type":"benar_salah","prompt":"Penghapusan node dengan dua anak memerlukan pencarian successor atau predecessor","correct_answer":"Benar","points":25,"cpmk":"CPMK-IF204-01","cpl":"CPL-DEMO-01"}]}',
 20, 0, 'published', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), 0, NOW(), NOW()),

-- 8. UAS
(911007, 910701, 'UAS-IF204', 'Ujian Akhir Semester – Struktur Data', 'uas',
 'Ujian komprehensif akhir semester.',
 '{"module":"UAS","body":"Kerjakan secara mandiri. Unggah jawaban dalam format PDF.","task_mode":"regular","formats":["file","text"],"manual_cpmk_weights":{"CPMK-IF204-02":50,"CPMK-IF204-03":50},"points":100,"scoring_mode":"manual_cpmk"}',
 20, 0, 'published', DATE_ADD(NOW(), INTERVAL 60 DAY), NOW(), 0, NOW(), NOW()),

-- 9. PBL (dengan rubrik)
(911008, 910701, 'PBL-IF204-01', 'Proyek Visualisasi Struktur Data', 'pbl',
 'Bangun aplikasi visualisasi traversal BST interaktif.',
 '{"module":"Proyek Semester","body":"Kumpulkan: kode sumber, dokumentasi, dan video demonstrasi aplikasi. Referensi boleh menggunakan VisuAlgo sebagai inspirasi.","task_mode":"regular","formats":["file","link","text"],"manual_cpmk_weights":{"CPMK-IF204-02":60,"CPMK-IF204-03":40},"points":100,"scoring_mode":"manual_cpmk","reference_links":["https://visualgo.net/id/bst"]}',
 15, 1, 'published', DATE_ADD(NOW(), INTERVAL 45 DAY), NOW(), 1, NOW(), NOW()),

-- 10. Lainnya (referensi)
(911009, 910701, 'LAINNYA-IF204-01', 'Referensi Tambahan Struktur Data', 'lainnya',
 'Kumpulan referensi eksternal untuk pendalaman mandiri.',
 '{"module":"Referensi","body":"Gunakan referensi ini untuk pendalaman mandiri. Video YouTube sangat direkomendasikan.","link":"https://en.wikipedia.org/wiki/Binary_search_tree","youtube_links":["https://www.youtube.com/watch?v=PEfAxWFY14Q","https://www.youtube.com/watch?v=R2j9v7P59hI"],"attachments":[],"formats":[],"points":0,"scoring_mode":"none"}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW());

-- ═══════════════════════════════════════════════════════════════════════════════
-- ASSESSMENTS — IF230 (Rekayasa Perangkat Lunak)
-- YouTube materi: https://www.youtube.com/watch?v=Y6Q8Nl0YSIY  (Intro SE - Univ Teknokrat)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO assessments (id, class_section_id, code, name, type, description, learning_payload, final_weight, uses_rubric, status, due_at, published_at, allow_late, created_at, updated_at) VALUES

-- Materi RPL 1
(912001, 910702, 'MATERI-IF230-01', 'Pengantar Rekayasa Perangkat Lunak', 'materi',
 'Materi pengantar RPL lengkap dengan video kuliah dan modul.',
 '{"module":"Minggu 1 – Pengantar RPL","body":"Tonton video pengantar RPL berikut, lalu baca modul yang disediakan. Pahami perbedaan antara software engineering dan programming biasa.","attachments":["11000000-0000-4000-8000-000000000011","12000000-0000-4000-8000-000000000012"],"pin_video":true,"pinned_media":"https://www.youtube.com/watch?v=Y6Q8Nl0YSIY","pinned_media_type":"youtube","pinned_media_kind":"video","youtube_links":["https://www.youtube.com/watch?v=Y6Q8Nl0YSIY"],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- Materi RPL 2: SDLC
(912002, 910702, 'MATERI-IF230-02', 'Software Development Life Cycle (SDLC)', 'materi',
 'Materi SDLC: Waterfall, Agile, dan Scrum.',
 '{"module":"Minggu 2 – SDLC","body":"Pelajari berbagai model SDLC dan kapan masing-masing digunakan. Fokus pada perbedaan Waterfall dan Agile.","attachments":["11000000-0000-4000-8000-000000000011"],"pin_video":false,"youtube_links":["https://www.youtube.com/watch?v=SaCYkPD4_K0"],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- Pengumuman RPL
(912003, 910702, 'PENGUMUMAN-IF230-01', 'Jadwal Presentasi Proyek RPL', 'pengumuman',
 'Jadwal presentasi proyek akhir minggu ke-14.',
 '{"module":"Informasi Kelas","body":"Setiap kelompok mendapat waktu 15 menit presentasi dan 10 menit tanya jawab. Urutan kelompok ditentukan berdasarkan nomor urut.","attachments":[],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- Tugas RPL
(912004, 910702, 'TUGAS-IF230-01', 'Dokumen Software Requirements Specification (SRS)', 'tugas',
 'Buat dokumen SRS lengkap untuk sistem informasi perpustakaan kampus.',
 '{"module":"Minggu 4 – Requirements Engineering","body":"Gunakan template IEEE 830 sebagai panduan. Dokumen harus mencakup functional requirements, non-functional requirements, dan use case diagram.","attachments":["13000000-0000-4000-8000-000000000013"],"formats":["file","text"],"question_type":"uraian","manual_cpmk_weights":{"CPMK-IF230-02":100},"points":100,"scoring_mode":"manual_cpmk"}',
 20, 0, 'published', DATE_ADD(NOW(), INTERVAL 10 DAY), NOW(), 1, NOW(), NOW()),

-- Kuis RPL
(912005, 910702, 'KUIS-IF230-01', 'Kuis SDLC dan Requirements Engineering', 'kuis',
 'Kuis pilihan berganda dan uraian singkat (25 menit).',
 '{"module":"Minggu 5 – Evaluasi","body":"Kerjakan dalam waktu 25 menit. Bacalah setiap pertanyaan dengan cermat.","task_mode":"quiz","formats":["cbt"],"duration_enabled":true,"duration_minutes":25,"points":100,"questions":[{"id":"rpl_q1","type":"pilihan","prompt":"Model SDLC yang paling cocok untuk proyek dengan kebutuhan yang berubah-ubah adalah","options":["Waterfall","Agile/Scrum","V-Model","Big Bang"],"correct_answer":"Agile/Scrum","points":25,"cpmk":"CPMK-IF230-01","cpl":"CPL-DEMO-01"},{"id":"rpl_q2","type":"kompleks","prompt":"Pilih artefak yang dihasilkan pada fase Requirements Engineering","options":["SRS Document","Source Code","Test Plan","Use Case Diagram","Database Schema"],"correct_answers":["SRS Document","Use Case Diagram"],"score_mode":"parsial","points":25,"cpmk":"CPMK-IF230-02","cpl":"CPL-DEMO-02"},{"id":"rpl_q3","type":"benar_salah","prompt":"Sprint dalam Scrum berlangsung 1-4 minggu","correct_answer":"Benar","points":25,"cpmk":"CPMK-IF230-01","cpl":"CPL-DEMO-01"},{"id":"rpl_q4","type":"uraian","prompt":"Jelaskan perbedaan antara functional dan non-functional requirements. Berikan masing-masing satu contoh.","essay_guide":"Functional: apa yang sistem lakukan. Non-functional: bagaimana kualitas sistem.","points":25,"cpmk":"CPMK-IF230-02","cpl":"CPL-DEMO-02"}]}',
 10, 0, 'published', DATE_ADD(NOW(), INTERVAL 15 DAY), NOW(), 0, NOW(), NOW()),

-- UTS RPL
(912006, 910702, 'UTS-IF230', 'Ujian Tengah Semester – RPL', 'uts',
 'UTS RPL – studi kasus analisis dan perancangan sistem (90 menit).',
 '{"module":"UTS","body":"Kerjakan studi kasus yang diberikan. Jawaban harus disertai diagram UML yang relevan.","task_mode":"quiz","duration_enabled":true,"duration_minutes":90,"points":100,"questions":[{"id":"uts_rpl1","type":"uraian","prompt":"Sebuah startup ingin membangun aplikasi ride-hailing. Buatlah: (1) minimal 5 functional requirements, (2) minimal 3 non-functional requirements, dan (3) use case diagram dengan minimal 4 aktor.","essay_guide":"Nilai berdasarkan kelengkapan, kejelasan, dan kualitas diagram.","points":100,"cpmk":"CPMK-IF230-02","cpl":"CPL-DEMO-02"}]}',
 20, 0, 'published', DATE_ADD(NOW(), INTERVAL 28 DAY), NOW(), 0, NOW(), NOW()),

-- PBL RPL (dengan rubrik)
(912007, 910702, 'PBL-IF230-01', 'Proyek Pengembangan Perangkat Lunak', 'pbl',
 'Kembangkan aplikasi web sederhana menggunakan metodologi Agile/Scrum.',
 '{"module":"Proyek Semester","body":"Kelompok terdiri dari 3-4 mahasiswa. Lakukan minimal 3 sprint dengan backlog yang terdokumentasi. Gunakan GitHub untuk version control.","task_mode":"regular","formats":["file","link","text"],"manual_cpmk_weights":{"CPMK-IF230-02":40,"CPMK-IF230-03":60},"points":100,"scoring_mode":"manual_cpmk"}',
 30, 1, 'published', DATE_ADD(NOW(), INTERVAL 50 DAY), NOW(), 1, NOW(), NOW());

-- ═══════════════════════════════════════════════════════════════════════════════
-- ASSESSMENTS — IF260 (Pemrograman Web)
-- YouTube: Web Programming UNPAS – Sandhika Galih
--   HTML Dasar playlist : https://www.youtube.com/playlist?list=PLFIM0718LjIVuONHysfOK0ZtiqUWvrx4F
--   CSS Dasar playlist  : https://www.youtube.com/playlist?list=PLFIM0718LjIUBrbm6Gdh6k7ZUvPIAZm7p
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO assessments (id, class_section_id, code, name, type, description, learning_payload, final_weight, uses_rubric, status, due_at, published_at, allow_late, created_at, updated_at) VALUES

-- Materi Web 1: HTML
(913001, 910703, 'MATERI-IF260-01', 'Dasar HTML dan Struktur Halaman Web', 'materi',
 'Materi HTML dasar lengkap dengan video playlist WPU.',
 '{"module":"Minggu 1 – HTML Dasar","body":"Ikuti playlist HTML Dasar dari Web Programming UNPAS. Kerjakan latihan membuat halaman profil sederhana setelah menonton.","attachments":["21000000-0000-4000-8000-000000000021"],"pin_video":true,"pinned_media":"https://www.youtube.com/playlist?list=PLFIM0718LjIVuONHysfOK0ZtiqUWvrx4F","pinned_media_type":"youtube","pinned_media_kind":"playlist","youtube_links":["https://www.youtube.com/playlist?list=PLFIM0718LjIVuONHysfOK0ZtiqUWvrx4F"],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- Materi Web 2: CSS
(913002, 910703, 'MATERI-IF260-02', 'Styling dengan CSS – Selector, Box Model, Flexbox', 'materi',
 'Materi CSS dari dasar hingga layout Flexbox.',
 '{"module":"Minggu 2 – CSS Dasar","body":"Ikuti playlist CSS Dasar dari WPU. Pastikan memahami Box Model dan Flexbox sebelum mengerjakan tugas layout.","attachments":[],"pin_video":true,"pinned_media":"https://www.youtube.com/playlist?list=PLFIM0718LjIUBrbm6Gdh6k7ZUvPIAZm7p","pinned_media_type":"youtube","pinned_media_kind":"playlist","youtube_links":["https://www.youtube.com/playlist?list=PLFIM0718LjIUBrbm6Gdh6k7ZUvPIAZm7p"],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- Pengumuman Web
(913003, 910703, 'PENGUMUMAN-IF260-01', 'Pengumpulan Proyek Akhir – Format dan Ketentuan', 'pengumuman',
 'Ketentuan pengumpulan proyek web akhir semester.',
 '{"module":"Informasi Kelas","body":"Kumpulkan: repository GitHub publik, link deploy (Vercel/Netlify), dan video demo 3-5 menit. Pastikan README lengkap.","attachments":[],"formats":[],"points":0}',
 0, 0, 'published', NULL, NOW(), 1, NOW(), NOW()),

-- Tugas Web
(913004, 910703, 'TUGAS-IF260-01', 'Membuat Halaman Web Profil Pribadi', 'tugas',
 'Buat halaman web profil pribadi menggunakan HTML dan CSS.',
 '{"module":"Minggu 3 – Praktikum HTML & CSS","body":"Halaman harus responsif dan mencakup: header dengan navigasi, section profil dengan foto, section keahlian, dan footer. Gunakan Flexbox atau Grid untuk layout.","attachments":["21000000-0000-4000-8000-000000000021"],"formats":["file","link","text"],"question_type":"uraian","manual_cpmk_weights":{"CPMK-IF260-01":100},"points":100,"scoring_mode":"manual_cpmk"}',
 15, 0, 'published', DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), 1, NOW(), NOW()),

-- Kuis Web
(913005, 910703, 'KUIS-IF260-01', 'Kuis HTML, CSS, dan JavaScript Dasar', 'kuis',
 'Kuis pilihan berganda dan uraian singkat (20 menit).',
 '{"module":"Minggu 6 – Evaluasi","body":"Kerjakan dalam waktu 20 menit.","task_mode":"quiz","formats":["cbt"],"duration_enabled":true,"duration_minutes":20,"points":100,"questions":[{"id":"web_q1","type":"pilihan","prompt":"Tag HTML yang benar untuk membuat hyperlink adalah","options":["<a>","<link>","<href>","<url>"],"correct_answer":"<a>","points":20,"cpmk":"CPMK-IF260-01","cpl":"CPL-DEMO-04"},{"id":"web_q2","type":"pilihan","prompt":"Property CSS untuk mengatur jarak dalam elemen (antara konten dan border) adalah","options":["margin","padding","spacing","gap"],"correct_answer":"padding","points":20,"cpmk":"CPMK-IF260-01","cpl":"CPL-DEMO-04"},{"id":"web_q3","type":"kompleks","prompt":"Pilih metode JavaScript yang digunakan untuk memanipulasi DOM","options":["getElementById","querySelector","console.log","parseInt","addEventListener"],"correct_answers":["getElementById","querySelector","addEventListener"],"score_mode":"parsial","points":20,"cpmk":"CPMK-IF260-02","cpl":"CPL-DEMO-04"},{"id":"web_q4","type":"benar_salah","prompt":"CSS Flexbox dan Grid dapat digunakan bersama dalam satu halaman web","correct_answer":"Benar","points":20,"cpmk":"CPMK-IF260-01","cpl":"CPL-DEMO-04"},{"id":"web_q5","type":"uraian","prompt":"Jelaskan perbedaan antara margin dan padding dalam CSS beserta contoh penggunaannya","essay_guide":"Margin: jarak luar elemen. Padding: jarak dalam elemen. Sertakan contoh konkret.","points":20,"cpmk":"CPMK-IF260-01","cpl":"CPL-DEMO-04"}]}',
 10, 0, 'published', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), 0, NOW(), NOW()),

-- PBL Web (dengan rubrik)
(913006, 910703, 'PBL-IF260-01', 'Proyek Web: Aplikasi To-Do List Interaktif', 'pbl',
 'Bangun aplikasi to-do list menggunakan HTML, CSS, dan JavaScript murni.',
 '{"module":"Proyek Akhir Semester","body":"Fitur wajib: tambah tugas, tandai selesai, hapus tugas, filter berdasarkan status, dan simpan data ke localStorage. Bonus: dark mode, drag & drop.","task_mode":"regular","formats":["file","link","text"],"manual_cpmk_weights":{"CPMK-IF260-01":30,"CPMK-IF260-02":50,"CPMK-IF260-03":20},"points":100,"scoring_mode":"manual_cpmk","reference_links":["https://www.youtube.com/playlist?list=PLFIM0718LjIVuONHysfOK0ZtiqUWvrx4F","https://www.youtube.com/playlist?list=PLFIM0718LjIUBrbm6Gdh6k7ZUvPIAZm7p"]}',
 25, 1, 'published', DATE_ADD(NOW(), INTERVAL 40 DAY), NOW(), 1, NOW(), NOW());

-- ─── ASSESSMENT-CPMK ──────────────────────────────────────────────────────────
INSERT INTO assessment_cpmk (id, assessment_id, cpmk_id, weight, created_at, updated_at) VALUES
  -- IF204
  (910001, 911003, 910603, 100, NOW(), NOW()),
  (910002, 911004, 910602,  65, NOW(), NOW()),
  (910003, 911004, 910603,  35, NOW(), NOW()),
  (910004, 911005, 910601,  80, NOW(), NOW()),
  (910005, 911005, 910603,  20, NOW(), NOW()),
  (910006, 911006, 910603, 100, NOW(), NOW()),
  (910007, 911007, 910602,  50, NOW(), NOW()),
  (910008, 911007, 910603,  50, NOW(), NOW()),
  (910009, 911008, 910602,  60, NOW(), NOW()),
  (910010, 911008, 910603,  40, NOW(), NOW()),
  -- IF230
  (910011, 912004, 910612, 100, NOW(), NOW()),
  (910012, 912005, 910611,  50, NOW(), NOW()),
  (910013, 912005, 910612,  50, NOW(), NOW()),
  (910014, 912006, 910612, 100, NOW(), NOW()),
  (910015, 912007, 910612,  40, NOW(), NOW()),
  (910016, 912007, 910613,  60, NOW(), NOW()),
  -- IF260
  (910021, 913004, 910621, 100, NOW(), NOW()),
  (910022, 913005, 910621,  60, NOW(), NOW()),
  (910023, 913005, 910622,  40, NOW(), NOW()),
  (910024, 913006, 910621,  30, NOW(), NOW()),
  (910025, 913006, 910622,  50, NOW(), NOW()),
  (910026, 913006, 910623,  20, NOW(), NOW());

-- ─── ATTACHMENTS ──────────────────────────────────────────────────────────────
-- IF204 assets
INSERT INTO attachments (id, uuid, user_id, class_section_id, assessment_id, submission_id, path, name, mime, size, created_at, updated_at) VALUES
  (913101, '10000000-0000-4000-8000-000000000001', 910103, 910701, 911001, NULL, 'learning-preview/demo-sale-modul-binary-tree.pdf',     'Modul Binary Search Tree.pdf',  'application/pdf', 802816,  NOW(), NOW()),
  (913102, '20000000-0000-4000-8000-000000000002', 910103, 910701, 911001, NULL, 'learning-preview/demo-sale-diagram-binary-tree.png',   'Diagram Binary Tree.png',       'image/png',       4505600, NOW(), NOW()),
  (913103, '30000000-0000-4000-8000-000000000003', 910103, 910701, 911001, NULL, 'learning-preview/demo-sale-video-binary-tree.mp4',     'Video Materi Binary Tree.mp4',  'video/mp4',       1840000, NOW(), NOW()),
  (913104, '40000000-0000-4000-8000-000000000004', 910103, 910701, 911003, NULL, 'learning-preview/demo-sale-modul-binary-tree.pdf',     'Panduan Tugas Analisis.pdf',    'application/pdf', 802816,  NOW(), NOW()),
  -- submission IF204
  (913105, '50000000-0000-4000-8000-000000000005', 910105, 910701, 911003, 914001, 'learning-preview/demo-sale-laporan-mahasiswa.pdf',  'Laporan Ahmad Maulana.pdf',     'application/pdf', 838656,  NOW(), NOW()),
  (913106, '60000000-0000-4000-8000-000000000006', 910105, 910701, 911003, 914001, 'learning-preview/demo-sale-hasil-pengujian.png',    'Hasil Pengujian BST.png',       'image/png',       4505600, NOW(), NOW()),
  -- IF230 assets
  (913111, '11000000-0000-4000-8000-000000000011', 910103, 910702, 912001, NULL, 'learning-preview/demo-sale-modul-rpl.pdf',             'Modul RPL Pengantar.pdf',       'application/pdf', 921600,  NOW(), NOW()),
  (913112, '12000000-0000-4000-8000-000000000012', 910103, 910702, 912001, NULL, 'learning-preview/demo-sale-diagram-sdlc.png',          'Diagram SDLC.png',              'image/png',       3276800, NOW(), NOW()),
  (913113, '13000000-0000-4000-8000-000000000013', 910103, 910702, 912004, NULL, 'learning-preview/demo-sale-template-srs.pdf',          'Template SRS IEEE 830.pdf',     'application/pdf', 614400,  NOW(), NOW()),
  -- IF260 assets
  (913121, '21000000-0000-4000-8000-000000000021', 910109, 910703, 913001, NULL, 'learning-preview/demo-sale-modul-html-dasar.pdf',      'Modul HTML Dasar.pdf',          'application/pdf', 716800,  NOW(), NOW());

-- ─── SUBMISSIONS ──────────────────────────────────────────────────────────────
INSERT INTO submissions (id, assessment_id, user_id, mahasiswa_id, attempt, version, status, submitted_at, answer, link, question_answers, file_ids, student_number, created_at, updated_at) VALUES
  -- IF204 submissions (Ahmad)
  (914001, 911003, 910105, 910105, 1, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 2 DAY),
   'Analisis BST seimbang: O(log n) rata-rata. BST skewed: O(n) worst case. Kompleksitas ruang O(n).',
   'https://example.test/ahmad/bst-analysis', NULL,
   '["50000000-0000-4000-8000-000000000005","60000000-0000-4000-8000-000000000006"]',
   '231011401234', NOW(), NOW()),
  (914002, 911005, 910105, 910105, 1, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 1 DAY),
   NULL, NULL,
   '{"q1":{"choices":["Inorder"]},"q2":{"choices":["Pencarian rata-rata logaritmik","Inorder terurut"]},"q3":{"boolean_choice":"Benar"},"q4":{"matching":{"Inorder":"Kiri–Akar–Kanan","Preorder":"Akar–Kiri–Kanan"}},"q5":{"answer_text":"Penyeimbangan menjaga tinggi tree tetap logaritmik sehingga pencarian tidak turun menjadi O(n) pada kasus worst case."}}',
   '[]', '231011401234', NOW(), NOW()),
  (914003, 911004, 910105, 910105, 1, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 3 DAY),
   'class Node:\n    def __init__(self, value):\n        self.value = value\n        self.left = None\n        self.right = None\n\ndef insert(root, value):\n    if root is None:\n        return Node(value)\n    if value < root.value:\n        root.left = insert(root.left, value)\n    else:\n        root.right = insert(root.right, value)\n    return root\n\ndef inorder(root):\n    if root is None:\n        return []\n    return inorder(root.left) + [root.value] + inorder(root.right)',
   NULL, NULL, '[]', '231011401234', NOW(), NOW()),
  -- IF204 submissions (Siti)
  (914011, 911003, 910106, 910106, 1, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 2 DAY),
   'BST seimbang memiliki O(log n) untuk search, insert, dan delete. Skewed tree mendegradasi ke O(n).',
   NULL, NULL, '[]', '231011401235', NOW(), NOW()),
  -- IF230 submission (Ahmad)
  (914021, 912004, 910105, 910105, 1, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 1 DAY),
   NULL, 'https://docs.example.test/ahmad/srs-perpustakaan', NULL, '[]', '231011401234', NOW(), NOW()),
  -- IF260 submission (Rizky)
  (914031, 913004, 910107, 910107, 1, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 1 DAY),
   NULL, 'https://rizky-profile.vercel.app', NULL, '[]', '231011401236', NOW(), NOW());

-- ─── SUBMISSION ANSWERS ───────────────────────────────────────────────────────
INSERT INTO submission_answers (id, submission_id, question_index, question_id, version, answer_text, link, choices, boolean_choice, matching, earned_score, max_score, grading_status, graded_by_id, graded_at, created_at, updated_at) VALUES
  (915001, 914002, 0, 'q1', 1, NULL, NULL, '["Inorder"]',                                         NULL, NULL, 20, 20, 'graded', 910103, NOW(), NOW(), NOW()),
  (915002, 914002, 1, 'q2', 1, NULL, NULL, '["Pencarian rata-rata logaritmik","Inorder terurut"]', NULL, NULL, 20, 20, 'graded', 910103, NOW(), NOW(), NOW()),
  (915003, 914002, 2, 'q3', 1, NULL, NULL, NULL, 'Benar',                                          NULL, 20, 20, 'graded', 910103, NOW(), NOW(), NOW()),
  (915004, 914002, 3, 'q4', 1, NULL, NULL, NULL, NULL, '{"Inorder":"Kiri–Akar–Kanan","Preorder":"Akar–Kiri–Kanan"}', 20, 20, 'graded', 910103, NOW(), NOW(), NOW()),
  (915005, 914002, 4, 'q5', 1, 'Penyeimbangan menjaga tinggi tree tetap logaritmik sehingga pencarian tidak turun menjadi O(n) pada kasus worst case.', NULL, NULL, NULL, NULL, NULL, 20, 'pending', NULL, NULL, NOW(), NOW());

-- ─── ASSESSMENT ATTEMPTS ──────────────────────────────────────────────────────
INSERT INTO assessment_attempts (id, assessment_id, mahasiswa_id, attempt, status, started_at, deadline_at, submitted_at, rejected_at, rejection_reason, created_at, updated_at) VALUES
  (916001, 911005, 910105, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 25 MINUTE), DATE_ADD(NOW(), INTERVAL 5 MINUTE), NOW(), NULL, NULL, NOW(), NOW()),
  (916002, 912005, 910105, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 20 MINUTE), DATE_ADD(NOW(), INTERVAL 5 MINUTE), NOW(), NULL, NULL, NOW(), NOW()),
  (916003, 913005, 910107, 1, 'submitted', DATE_SUB(NOW(), INTERVAL 15 MINUTE), DATE_ADD(NOW(), INTERVAL 5 MINUTE), NOW(), NULL, NULL, NOW(), NOW());

-- ─── SCORES ───────────────────────────────────────────────────────────────────
INSERT INTO student_assessment_scores (id, assessment_id, mahasiswa_id, score, status, feedback, graded_by, graded_at, published_at, created_at, updated_at) VALUES
  -- IF204 Ahmad
  (917001, 911003, 910105, 88, 'published', 'Analisis sudah tepat. Tambahkan contoh kasus skewed untuk memperkuat argumen.', 910103, NOW(), NOW(), NOW(), NOW()),
  (917002, 911004, 910105, 92, 'published', 'Implementasi lengkap. Traversal berjalan dengan baik.', 910103, NOW(), NOW(), NOW(), NOW()),
  (917003, 911005, 910105, 80, 'pending',   'Soal objektif selesai. Uraian menunggu pemeriksaan.', 910103, NOW(), NULL,  NOW(), NOW()),
  -- IF204 Siti
  (917011, 911003, 910106, 82, 'published', 'Laporan terstruktur dengan baik. Tambahkan kompleksitas ruang.', 910103, NOW(), NOW(), NOW(), NOW()),
  -- IF204 Rizky
  (917012, 911003, 910107, 76, 'published', 'Perlu memperdalam analisis kompleksitas kasus rata-rata.', 910103, NOW(), NOW(), NOW(), NOW()),
  -- IF230 Ahmad
  (917021, 912004, 910105, 85, 'published', 'Dokumen SRS cukup lengkap. Use case diagram perlu diperjelas relasi antar aktor.', 910103, NOW(), NOW(), NOW(), NOW()),
  -- IF260 Rizky
  (917031, 913004, 910107, 90, 'published', 'Halaman responsif dan navigasi berfungsi baik. Tambahkan animasi transisi untuk UX lebih baik.', 910109, NOW(), NOW(), NOW(), NOW());

-- ─── CPMK SCORES ─────────────────────────────────────────────────────────────
INSERT INTO student_assessment_cpmk_scores (id, assessment_id, cpmk_id, mahasiswa_id, score, created_at, updated_at) VALUES
  (918001, 911003, 910603, 910105, 88, NOW(), NOW()),
  (918002, 911004, 910602, 910105, 94, NOW(), NOW()),
  (918003, 911004, 910603, 910105, 88, NOW(), NOW()),
  (918004, 911005, 910601, 910105, 80, NOW(), NOW()),
  (918005, 911003, 910603, 910106, 82, NOW(), NOW()),
  (918006, 911003, 910603, 910107, 76, NOW(), NOW()),
  (918007, 912004, 910612, 910105, 85, NOW(), NOW()),
  (918008, 913004, 910621, 910107, 90, NOW(), NOW());

-- ─── RUBRIK — IF204 PBL ───────────────────────────────────────────────────────
INSERT INTO rubrics (id, assessment_id, name, created_at, updated_at) VALUES
  (918101, 911008, 'Rubrik Proyek Visualisasi Struktur Data', NOW(), NOW()),
  (918102, 912007, 'Rubrik Proyek Pengembangan Perangkat Lunak', NOW(), NOW()),
  (918103, 913006, 'Rubrik Proyek Web To-Do List', NOW(), NOW());

INSERT INTO rubric_criteria (id, rubric_id, name, description, weight, max_score, `order`, created_at, updated_at) VALUES
  -- IF204
  (918201, 918101, 'Arsitektur Solusi',  'Ketepatan struktur data dan desain komponen.',         25, 100, 1, NOW(), NOW()),
  (918202, 918101, 'Implementasi',       'Kelengkapan fungsi dan kualitas kode.',                 35, 100, 2, NOW(), NOW()),
  (918203, 918101, 'Pengujian',          'Cakupan dan kualitas pengujian.',                       20, 100, 3, NOW(), NOW()),
  (918204, 918101, 'Dokumentasi',        'Kejelasan laporan dan video demonstrasi.',              20, 100, 4, NOW(), NOW()),
  -- IF230
  (918211, 918102, 'Perencanaan Proyek', 'Kualitas backlog, sprint planning, dan retrospective.', 25, 100, 1, NOW(), NOW()),
  (918212, 918102, 'Implementasi',       'Kelengkapan fitur dan kualitas kode.',                  40, 100, 2, NOW(), NOW()),
  (918213, 918102, 'Pengujian',          'Unit test dan integrasi test.',                         15, 100, 3, NOW(), NOW()),
  (918214, 918102, 'Presentasi',         'Kejelasan presentasi dan kemampuan menjawab.',          20, 100, 4, NOW(), NOW()),
  -- IF260
  (918221, 918103, 'Fungsionalitas',     'Semua fitur wajib berjalan dengan benar.',              40, 100, 1, NOW(), NOW()),
  (918222, 918103, 'Desain UI/UX',       'Tampilan responsif, konsisten, dan estetik.',           25, 100, 2, NOW(), NOW()),
  (918223, 918103, 'Kualitas Kode',      'Kode bersih, terstruktur, dan terdokumentasi.',         20, 100, 3, NOW(), NOW()),
  (918224, 918103, 'Fitur Bonus',        'Dark mode, drag & drop, atau fitur tambahan lain.',     15, 100, 4, NOW(), NOW());

INSERT INTO student_rubric_scores (id, rubric_criterion_id, mahasiswa_id, score, created_at, updated_at) VALUES
  -- IF204 Ahmad
  (918301, 918201, 910105, 90, NOW(), NOW()),
  (918302, 918202, 910105, 92, NOW(), NOW()),
  (918303, 918203, 910105, 86, NOW(), NOW()),
  (918304, 918204, 910105, 88, NOW(), NOW());

-- ═══════════════════════════════════════════════════════════════════════════════
-- DISKUSI KELAS — IF204
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO course_discussions (id, class_section_id, user_id, author_name, role, sender_key, message, created_at, updated_at) VALUES
  (919001, 910701, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Selamat datang di kelas IF204 Struktur Data dan Algoritma. Gunakan forum ini untuk pertanyaan seputar materi dan praktikum. Selalu baca modul dan tonton video materi sebelum bertanya.',
   DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (919002, 910701, 910105, 'Ahmad Maulana', 'mahasiswa', 'user:910105',
   'Pak, apa perbedaan utama antara traversal inorder dan preorder? Saya sudah menonton video YouTube di materi tapi masih bingung urutan kunjungannya.',
   DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
  (919003, 910701, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Inorder mengunjungi: kiri → akar → kanan, sehingga menghasilkan data terurut pada BST. Preorder: akar → kiri → kanan, berguna untuk menyalin struktur tree. Coba praktikkan dengan contoh kecil di kertas.',
   DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
  (919004, 910701, 910106, 'Siti Nurhaliza', 'mahasiswa', 'user:910106',
   'Terima kasih Pak, penjelasannya sangat membantu. Kalau postorder urutannya bagaimana ya Pak?',
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (919005, 910701, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Postorder: kiri → kanan → akar. Sering digunakan untuk menghapus tree karena node anak diproses sebelum parent.',
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (919006, 910701, 910107, 'Rizky Pratama', 'mahasiswa', 'user:910107',
   'Pak, untuk tugas CODING-IF204-01, apakah boleh menggunakan class atau hanya fungsi biasa saja?',
   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919007, 910701, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Boleh menggunakan class. Justru penggunaan class lebih direkomendasikan karena lebih terstruktur dan OOP. Ikuti contoh di video materi kedua.',
   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919008, 910701, 910104, 'Sari Lestari, S.Kom., M.Cs.', 'dosen', 'user:910104',
   'Tambahan dari Bu Sari: untuk referensi implementasi BST, teman-teman bisa cek juga visualisasi interaktif di VisuAlgo (https://visualgo.net/id/bst). Sangat membantu untuk memahami setiap operasi secara visual.',
   DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (919009, 910701, 910110, 'Fajar Nugroho', 'mahasiswa', 'user:910110',
   'Bu, apakah kompleksitas best case BST selalu O(1)?',
   DATE_SUB(NOW(), INTERVAL 12 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR)),
  (919010, 910701, 910104, 'Sari Lestari, S.Kom., M.Cs.', 'dosen', 'user:910104',
   'Best case O(1) hanya untuk akses root. Pencarian best case O(1) jika yang dicari tepat root. Untuk kasus umum, best case O(log n) saat tree seimbang.',
   DATE_SUB(NOW(), INTERVAL 10 HOUR), DATE_SUB(NOW(), INTERVAL 10 HOUR)),
  (919011, 910701, 910111, 'Maya Kusuma', 'mahasiswa', 'user:910111',
   'Pak, untuk PBL-IF204-01 apakah visualisasinya harus berbasis web atau bisa desktop juga?',
   DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR)),
  (919012, 910701, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Bebas, bisa web maupun desktop. Yang penting aplikasi dapat menampilkan proses traversal secara visual dan interaktif. Sertakan video demonstrasi saat pengumpulan.',
   DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- ─── DISKUSI KELAS — IF230 ────────────────────────────────────────────────────
INSERT INTO course_discussions (id, class_section_id, user_id, author_name, role, sender_key, message, created_at, updated_at) VALUES
  (919021, 910702, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Selamat datang di IF230 Rekayasa Perangkat Lunak. Tonton video materi sebelum setiap pertemuan. Proyek akhir dikerjakan dalam kelompok 3-4 orang.',
   DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (919022, 910702, 910105, 'Ahmad Maulana', 'mahasiswa', 'user:910105',
   'Pak, untuk dokumen SRS boleh menggunakan template selain IEEE 830?',
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (919023, 910702, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Untuk tugas ini wajib menggunakan template IEEE 830 yang sudah disediakan. Silakan unduh dari lampiran materi.',
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (919024, 910702, 910110, 'Fajar Nugroho', 'mahasiswa', 'user:910110',
   'Pak, apa perbedaan utama Waterfall dan Agile dalam konteks proyek nyata?',
   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919025, 910702, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Waterfall: sekuensial dan cocok untuk kebutuhan yang stabil. Agile: iteratif dan cocok untuk kebutuhan yang berubah. Lihat video materi SDLC untuk penjelasan lebih lengkap.',
   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919026, 910702, 910112, 'Dimas Prasetyo', 'mahasiswa', 'user:910112',
   'Apakah proyek akhir harus deploy online Pak?',
   DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (919027, 910702, 910103, 'Dr. Budi Santoso, M.Kom.', 'dosen', 'user:910103',
   'Deploy online tidak wajib tapi sangat direkomendasikan. Alternatifnya, siapkan demo lokal yang bisa dijalankan saat presentasi.',
   DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ─── DISKUSI KELAS — IF260 ────────────────────────────────────────────────────
INSERT INTO course_discussions (id, class_section_id, user_id, author_name, role, sender_key, message, created_at, updated_at) VALUES
  (919041, 910703, 910109, 'Reza Firmansyah, M.T.', 'dosen', 'user:910109',
   'Selamat datang di IF260 Pemrograman Web. Ikuti playlist WPU di materi untuk belajar HTML dan CSS. Kita akan mulai dari dasar.',
   DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (919042, 910703, 910107, 'Rizky Pratama', 'mahasiswa', 'user:910107',
   'Pak, apakah untuk tugas profil pribadi harus ada animasi CSS?',
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (919043, 910703, 910109, 'Reza Firmansyah, M.T.', 'dosen', 'user:910109',
   'Animasi tidak wajib tapi akan mendapat nilai bonus. Yang wajib: HTML semantik, CSS responsif dengan Flexbox/Grid, dan navigasi berfungsi.',
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (919044, 910703, 910108, 'Dewi Anggraini', 'mahasiswa', 'user:910108',
   'Pak, untuk proyek To-Do List boleh menggunakan framework CSS seperti Bootstrap atau harus vanilla CSS?',
   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919045, 910703, 910109, 'Reza Firmansyah, M.T.', 'dosen', 'user:910109',
   'Wajib vanilla CSS untuk proyek ini agar benar-benar memahami dasar CSS. Framework boleh digunakan sebagai referensi tapi tidak sebagai library langsung.',
   DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919046, 910703, 910111, 'Maya Kusuma', 'mahasiswa', 'user:910111',
   'Pak, saya sudah selesai tugas profil pribadi. Linknya: https://maya-profile.vercel.app — mohon diperiksa.',
   DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR)),
  (919047, 910703, 910109, 'Reza Firmansyah, M.T.', 'dosen', 'user:910109',
   'Sudah saya cek Maya, bagus! Hanya saja halaman mobile-nya perlu diperbaiki di bagian section keahlian. Silakan perbaiki sebelum deadline.',
   DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- ═══════════════════════════════════════════════════════════════════════════════
-- CHAT (ROOMS & MESSAGES)
-- ═══════════════════════════════════════════════════════════════════════════════
INSERT INTO rooms (id, name, course_id, class_section_id, created_at, updated_at) VALUES
  (919101, 'Forum IF204 Demo – Kelas A', 910701, 910701, NOW(), NOW()),
  (919102, 'Forum IF230 Demo – Kelas A', 910702, 910702, NOW(), NOW()),
  (919103, 'Forum IF260 Demo – Kelas B', 910703, 910703, NOW(), NOW());

INSERT INTO room_members (id, room_id, user_id, role, joined_at, created_at, updated_at) VALUES
  -- IF204
  (919201, 919101, 910103, 'dosen',     NOW(), NOW(), NOW()),
  (919202, 919101, 910104, 'dosen',     NOW(), NOW(), NOW()),
  (919203, 919101, 910105, 'mahasiswa', NOW(), NOW(), NOW()),
  (919204, 919101, 910106, 'mahasiswa', NOW(), NOW(), NOW()),
  (919205, 919101, 910107, 'mahasiswa', NOW(), NOW(), NOW()),
  (919206, 919101, 910110, 'mahasiswa', NOW(), NOW(), NOW()),
  (919207, 919101, 910111, 'mahasiswa', NOW(), NOW(), NOW()),
  -- IF230
  (919211, 919102, 910103, 'dosen',     NOW(), NOW(), NOW()),
  (919212, 919102, 910105, 'mahasiswa', NOW(), NOW(), NOW()),
  (919213, 919102, 910106, 'mahasiswa', NOW(), NOW(), NOW()),
  (919214, 919102, 910110, 'mahasiswa', NOW(), NOW(), NOW()),
  (919215, 919102, 910112, 'mahasiswa', NOW(), NOW(), NOW()),
  -- IF260
  (919221, 919103, 910109, 'dosen',     NOW(), NOW(), NOW()),
  (919222, 919103, 910107, 'mahasiswa', NOW(), NOW(), NOW()),
  (919223, 919103, 910108, 'mahasiswa', NOW(), NOW(), NOW()),
  (919224, 919103, 910111, 'mahasiswa', NOW(), NOW(), NOW()),
  (919225, 919103, 910112, 'mahasiswa', NOW(), NOW(), NOW());

-- Chat IF204
INSERT INTO messages (id, room_id, user_id, content, attachment_url, reply_to_message_id, is_pinned, edited_at, deleted_at, created_at, updated_at) VALUES
  (919301, 919101, 910103, 'Silakan baca modul BST dan tonton kedua video YouTube yang ada di materi sebelum kuis dimulai minggu depan.', NULL, NULL, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR)),
  (919302, 919101, 910105, 'Pak, apa perbedaan utama traversal inorder dan preorder? @Siti Nurhaliza', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR 30 MINUTE), DATE_SUB(NOW(), INTERVAL 2 HOUR 30 MINUTE)),
  (919303, 919101, 910103, 'Inorder: kiri→akar→kanan → menghasilkan urutan terurut pada BST. Preorder: akar→kiri→kanan → berguna saat akar harus diproses lebih dahulu.', NULL, 919302, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
  (919304, 919101, 910106, 'Terima kasih Pak! Berarti kalau kita traversal BST dengan inorder dan hasilnya tidak terurut, berarti ada bug di implementasi BST-nya ya?', NULL, 919303, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR 45 MINUTE), DATE_SUB(NOW(), INTERVAL 1 HOUR 45 MINUTE)),
  (919305, 919101, 910103, 'Tepat sekali Siti! Itu adalah salah satu cara paling mudah untuk mendeteksi bug pada implementasi BST.', NULL, 919304, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR 30 MINUTE), DATE_SUB(NOW(), INTERVAL 1 HOUR 30 MINUTE)),
  (919306, 919101, 910107, 'Pak, untuk coding BST di Python, apakah perlu handle duplikat value?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (919307, 919101, 910103, 'Untuk tugas ini, asumsikan tidak ada duplikat. Tapi dalam implementasi nyata, duplikat bisa ditempatkan di kanan atau diabaikan, tergantung requirement.', NULL, 919306, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 50 MINUTE), DATE_SUB(NOW(), INTERVAL 50 MINUTE)),
  (919308, 919101, 910110, 'Terima kasih Pak. Satu lagi, apakah kita perlu implementasi delete juga untuk coding submission?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
  (919309, 919101, 910103, 'Untuk CODING-IF204-01, tidak perlu delete. Fokus pada insert, search, dan traversal inorder sesuai coding steps yang ada.', NULL, 919308, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 MINUTE), DATE_SUB(NOW(), INTERVAL 20 MINUTE));

-- Chat IF230
INSERT INTO messages (id, room_id, user_id, content, attachment_url, reply_to_message_id, is_pinned, edited_at, deleted_at, created_at, updated_at) VALUES
  (919311, 919102, 910103, 'Ingat: deadline SRS minggu depan. Pastikan use case diagram sudah lengkap dengan relasi include dan extend.', NULL, NULL, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
  (919312, 919102, 910105, 'Pak, untuk SRS kami buat dalam Bahasa Indonesia atau Inggris?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR 30 MINUTE), DATE_SUB(NOW(), INTERVAL 1 HOUR 30 MINUTE)),
  (919313, 919102, 910103, 'Bahasa Indonesia saja untuk kali ini. Yang penting terminologi teknis tetap menggunakan istilah baku.', NULL, 919312, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (919314, 919102, 910112, 'Pak, apakah use case diagram bisa dibuat dengan draw.io? @Fajar Nugroho kamu pakai tool apa?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 45 MINUTE), DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
  (919315, 919102, 910110, 'Aku pakai Lucidchart Dimas, gratis untuk mahasiswa. Coba daftar dengan email kampus.', NULL, 919314, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
  (919316, 919102, 910103, 'Draw.io, Lucidchart, atau bahkan Figma semua bisa digunakan. Tidak ada tool yang diwajibkan.', NULL, 919314, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 MINUTE), DATE_SUB(NOW(), INTERVAL 20 MINUTE));

-- Chat IF260
INSERT INTO messages (id, room_id, user_id, content, attachment_url, reply_to_message_id, is_pinned, edited_at, deleted_at, created_at, updated_at) VALUES
  (919321, 919103, 910109, 'Tonton playlist HTML Dasar WPU di materi sebelum kuliah besok. Kita langsung praktikum.', NULL, NULL, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR)),
  (919322, 919103, 910107, 'Pak, untuk tugas profil, foto profil harus foto asli atau bisa placeholder?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR)),
  (919323, 919103, 910109, 'Foto asli lebih baik, tapi placeholder juga diterima untuk demo. Yang penting elemen img ada dengan alt text yang deskriptif.', NULL, 919322, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR 30 MINUTE), DATE_SUB(NOW(), INTERVAL 2 HOUR 30 MINUTE)),
  (919324, 919103, 910108, 'Pak, kalau pakai CSS Grid untuk layout, nilai bonusnya sama dengan Flexbox? @Maya Kusuma kamu pakai yang mana?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
  (919325, 919103, 910111, 'Aku pakai Flexbox Dewi, lebih familiar. Tapi Grid juga bagus untuk layout 2D.', NULL, 919324, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR 30 MINUTE), DATE_SUB(NOW(), INTERVAL 1 HOUR 30 MINUTE)),
  (919326, 919103, 910109, 'Keduanya setara. Yang penting implementasinya benar dan responsif. Malah lebih bagus kalau kombinasi keduanya.', NULL, 919324, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (919327, 919103, 910112, 'Pak, saya kebingungan dengan CSS specificity. Ada resource bagus yang bisa direkomendasikan?', NULL, NULL, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
  (919328, 919103, 910109, 'Tonton playlist CSS WPU di materi, ada penjelasan specificity yang sangat bagus. Juga bisa cek MDN Web Docs untuk referensi teknis.', NULL, 919327, 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 MINUTE), DATE_SUB(NOW(), INTERVAL 15 MINUTE));

-- ─── MESSAGE MENTIONS ─────────────────────────────────────────────────────────
INSERT INTO message_mentions (id, message_id, mentioned_user_id, created_at, updated_at) VALUES
  (919401, 919302, 910106, NOW(), NOW()),
  (919402, 919314, 910110, NOW(), NOW()),
  (919403, 919324, 910111, NOW(), NOW());

-- ─── CHAT NOTIFICATIONS ───────────────────────────────────────────────────────
INSERT INTO chat_notifications (id, user_id, type, message_id, is_read, created_at, updated_at) VALUES
  (919501, 910106, 'mention', 919302, 0, NOW(), NOW()),
  (919502, 910105, 'reply',   919303, 1, NOW(), NOW()),
  (919503, 910106, 'reply',   919305, 0, NOW(), NOW()),
  (919504, 910107, 'reply',   919307, 0, NOW(), NOW()),
  (919505, 910110, 'reply',   919309, 0, NOW(), NOW()),
  (919506, 910105, 'reply',   919313, 1, NOW(), NOW()),
  (919507, 910110, 'mention', 919314, 0, NOW(), NOW()),
  (919508, 910112, 'reply',   919316, 0, NOW(), NOW()),
  (919509, 910107, 'reply',   919323, 1, NOW(), NOW()),
  (919510, 910111, 'mention', 919324, 0, NOW(), NOW()),
  (919511, 910108, 'reply',   919326, 0, NOW(), NOW()),
  (919512, 910112, 'reply',   919328, 0, NOW(), NOW());

-- ─── AI THREADS & MESSAGES ────────────────────────────────────────────────────
-- Ahmad: IF204 coding BST
INSERT INTO ai_threads (id, user_id, assessment_id, class_section_id, turns, blocked_count, tokens_used, last_provider, cleared_at, flagged_for_review, last_blocked_at, created_at, updated_at) VALUES
  (919601, 910105, 911004, 910701, 4, 0,  972, 'demo', NULL, 0, NULL, NOW(), NOW()),
  (919602, 910105, 912004, 910702, 2, 0,  480, 'demo', NULL, 0, NULL, NOW(), NOW()),
  (919603, 910107, 913004, 910703, 3, 0,  640, 'demo', NULL, 0, NULL, NOW(), NOW());

INSERT INTO ai_messages (id, thread_id, role, content, verdict, tokens_in, tokens_out, created_at) VALUES
  -- IF204 coding thread (Ahmad)
  (919701, 919601, 'user',      'Mengapa fungsi insert saya berhenti ketika node kiri masih kosong?', 'ok', 84, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
  (919702, 919601, 'assistant', 'Periksa kondisi dasar sebelum memanggil rekursi. Jika `root is None`, kembalikan `Node(value)`. Pastikan selalu return node setelah rekursi agar parent ter-update.', 'ok', 0, 132, DATE_SUB(NOW(), INTERVAL 29 MINUTE)),
  (919703, 919601, 'user',      'Oke sudah diperbaiki. Sekarang insert berjalan tapi inorder-nya masih salah urutannya.', 'ok', 76, 0, DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
  (919704, 919601, 'assistant', 'Pastikan inorder mengunjungi: kiri → akar → kanan. Cek apakah rekursi kiri dan kanan dipanggil dengan benar: `return inorder(root.left) + [root.value] + inorder(root.right)`.', 'ok', 0, 148, DATE_SUB(NOW(), INTERVAL 19 MINUTE)),
  -- IF230 SRS thread (Ahmad)
  (919711, 919602, 'user',      'Bagaimana cara menulis non-functional requirement yang baik untuk aplikasi perpustakaan?', 'ok', 92, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
  (919712, 919602, 'assistant', 'NFR yang baik menggunakan format SMART: Spesifik, Measurable, dll. Contoh: "Sistem harus merespons query pencarian dalam < 2 detik pada 95% permintaan". Kategori umum: performance, security, usability, scalability.', 'ok', 0, 196, DATE_SUB(NOW(), INTERVAL 1 HOUR 58 MINUTE)),
  -- IF260 HTML/CSS thread (Rizky)
  (919721, 919603, 'user',      'Bagaimana membuat navigation bar yang responsif dengan CSS Flexbox?', 'ok', 78, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (919722, 919603, 'assistant', 'Gunakan `display: flex` pada container nav, `justify-content: space-between` untuk logo dan menu. Tambahkan `@media` query untuk mobile: sembunyikan menu dan tampilkan hamburger icon.', 'ok', 0, 142, DATE_SUB(NOW(), INTERVAL 59 MINUTE)),
  (919723, 919603, 'user',      'Sudah berhasil! Tapi hamburger icon saya tidak bisa toggle menu. Perlu JavaScript ya?', 'ok', 66, 0, DATE_SUB(NOW(), INTERVAL 50 MINUTE)),
  (919724, 919603, 'assistant', 'Ya, perlu JavaScript kecil. Tambahkan event listener pada tombol hamburger untuk toggle class (misal `.active`) pada menu. Dengan CSS, atur `.menu.active { display: flex }`.', 'ok', 0, 132, DATE_SUB(NOW(), INTERVAL 49 MINUTE));

-- ─── AI API CALLS ─────────────────────────────────────────────────────────────
INSERT INTO ai_api_calls (id, turn_id, user_id, task_id, thread_id, feature, stage, provider, model, model_version, status, usage_source, input_tokens, cached_tokens, output_tokens, thinking_tokens, total_tokens, estimated_cost_usd, latency_ms, finish_reason, created_at) VALUES
  (919801, NULL, 910105, NULL, 919601, 'tutor', 'answer', 'demo', 'sale-ai-demo', 'video-1', 'success', 'estimated', 160, 0, 132, 24, 316, 0.001264, 2450, 'stop', DATE_SUB(NOW(), INTERVAL 29 MINUTE)),
  (919802, NULL, 910105, NULL, 919601, 'tutor', 'answer', 'demo', 'sale-ai-demo', 'video-1', 'success', 'estimated', 142, 0, 148, 18, 308, 0.001232, 1980, 'stop', DATE_SUB(NOW(), INTERVAL 19 MINUTE)),
  (919803, NULL, 910105, NULL, 919602, 'tutor', 'answer', 'demo', 'sale-ai-demo', 'video-1', 'success', 'estimated', 148, 0, 196, 30, 374, 0.001496, 2810, 'stop', DATE_SUB(NOW(), INTERVAL 1 HOUR 58 MINUTE)),
  (919804, NULL, 910107, NULL, 919603, 'tutor', 'answer', 'demo', 'sale-ai-demo', 'video-1', 'success', 'estimated', 134, 0, 142, 20, 296, 0.001184, 2120, 'stop', DATE_SUB(NOW(), INTERVAL 59 MINUTE)),
  (919805, NULL, 910107, NULL, 919603, 'tutor', 'answer', 'demo', 'sale-ai-demo', 'video-1', 'success', 'estimated', 118, 0, 132, 16, 266, 0.001064, 1840, 'stop', DATE_SUB(NOW(), INTERVAL 49 MINUTE));

-- ─── ACTIVITY LOGS ────────────────────────────────────────────────────────────
INSERT INTO activity_logs (id, actor_id, action, context, created_at, updated_at) VALUES
  (919901, 910101, 'video_demo.academic.created',      '{"type":"semester","code":"2026-DEMO-1"}',                                            DATE_SUB(NOW(), INTERVAL 7 DAY),  DATE_SUB(NOW(), INTERVAL 7 DAY)),
  (919902, 910102, 'video_demo.class.created',         '{"class_section_id":910701,"course":"IF204-DEMO"}',                                   DATE_SUB(NOW(), INTERVAL 6 DAY),  DATE_SUB(NOW(), INTERVAL 6 DAY)),
  (919903, 910102, 'video_demo.class.created',         '{"class_section_id":910702,"course":"IF230-DEMO"}',                                   DATE_SUB(NOW(), INTERVAL 6 DAY),  DATE_SUB(NOW(), INTERVAL 6 DAY)),
  (919904, 910102, 'video_demo.class.created',         '{"class_section_id":910703,"course":"IF260-DEMO"}',                                   DATE_SUB(NOW(), INTERVAL 6 DAY),  DATE_SUB(NOW(), INTERVAL 6 DAY)),
  (919905, 910103, 'video_demo.assessment.published',  '{"assessment_id":911001,"type":"materi","youtube":true}',                             DATE_SUB(NOW(), INTERVAL 5 DAY),  DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (919906, 910103, 'video_demo.assessment.published',  '{"assessment_id":911005,"type":"kuis"}',                                              DATE_SUB(NOW(), INTERVAL 4 DAY),  DATE_SUB(NOW(), INTERVAL 4 DAY)),
  (919907, 910109, 'video_demo.assessment.published',  '{"assessment_id":913001,"type":"materi","youtube":true}',                             DATE_SUB(NOW(), INTERVAL 4 DAY),  DATE_SUB(NOW(), INTERVAL 4 DAY)),
  (919908, 910105, 'video_demo.submission.created',    '{"submission_id":914001,"assessment_id":911003}',                                     DATE_SUB(NOW(), INTERVAL 2 DAY),  DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (919909, 910107, 'video_demo.submission.created',    '{"submission_id":914031,"assessment_id":913004}',                                     DATE_SUB(NOW(), INTERVAL 1 DAY),  DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (919910, 910103, 'video_demo.score.published',       '{"assessment_id":911003,"mahasiswa_count":3}',                                        DATE_SUB(NOW(), INTERVAL 1 DAY),  DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (919911, 910102, 'video_demo.class.archived',        '{"class_section_id":910704,"course":"IF204-DEMO","section":"B"}',                     DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY));

-- ─── SYSTEM SETTINGS ──────────────────────────────────────────────────────────
INSERT INTO system_settings (`key`, `value`, created_at, updated_at) VALUES
  ('app_name',         'SALE',                          NOW(), NOW()),
  ('institution',      'Institut Teknologi Senggarang', NOW(), NOW()),
  ('support',          'helpdesk@sale.demo',            NOW(), NOW()),
  ('semester',         'Ganjil 2026/2027 Demo Video',   NOW(), NOW()),
  ('ai_provider',      'Google AI',                     NOW(), NOW()),
  ('ai_model',         'sale-ai-demo',                  NOW(), NOW()),
  ('ai_token_quota',   '1000000',                       NOW(), NOW()),
  ('maintenance_mode', '0',                             NOW(), NOW()),
  ('session_lifetime', '120',                           NOW(), NOW())
ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW();

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- ─── VERIFICATION ─────────────────────────────────────────────────────────────
SELECT 'SALE video demo data imported successfully' AS result;
SELECT email, name FROM users WHERE id BETWEEN 910101 AND 910112 ORDER BY id;
SELECT m.code AS kode_mk, cs.section_code AS kelas, cs.enrollment_code AS kode_masuk
  FROM class_sections cs JOIN mata_kuliahs m ON m.id = cs.mata_kuliah_id
  WHERE cs.id BETWEEN 910701 AND 910704 ORDER BY cs.id;
SELECT COUNT(*) AS total_assessments FROM assessments WHERE class_section_id IN (910701,910702,910703);
SELECT COUNT(*) AS total_messages    FROM messages     WHERE room_id         IN (919101,919102,919103);
SELECT COUNT(*) AS total_diskusi     FROM course_discussions WHERE class_section_id IN (910701,910702,910703);
