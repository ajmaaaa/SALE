import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  ScreencastSubtitles,
  SubtitleItem,
} from "../../components/ScreencastEngine";
import { ChapterBumper } from "../../components/ChapterBumper";

/**
 * BAB 03 — Tugas Mandiri & Ujian Berwaktu
 * Durasi: 1630 frames (54.33 detik) — Full sync dengan VO2 Seg 36 - 52
 *
 * Skenario Layar:
 * 1. 22_mahasiswa_tugas_detail.png (0 - 822f):
 *    - Penugasan mandiri, petunjuk & CPMK, dropdown berkas, unggah PDF, serahkan tugas
 * 2. 20_kuis_ujian_timer.png (822 - 1630f):
 *    - Kuis berwaktu, countdown timer live, bilah nomor soal, kumpulkan ujian + modal konfirmasi
 */
export const Scene4AssessmentScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // === TIMING MILESTONES (Sync dengan vo2_timestamps.json Seg 36-52) ===
  const MOVE_TO_PETUNJUK = 364; // Seg 38: "Saat membuka penugasan, cermati instruksi pengerjaan"
  const MOVE_TO_CPMK = 504;     // Seg 39: "serta rubrik penilaian yang ditetapkan oleh dosen"
  const MOVE_TO_UPLOAD = 680;   // Seg 40: "Anda dapat mengunggah dokumen laporan atau berkas studi"
  const CLICK_ADD_FILE = 715;   // Klik tombol + Tambah atau buat
  const CLICK_FILE_OPTION = 745;// Klik opsi Berkas / File
  const CLICK_SUBMIT_TASK = 785;// Klik tombol Kumpulkan Tugas langsung (autentik SALE)

  const LOAD_QUIZ_SCREEN = 822; // Seg 45: "Sementara untuk kuis dan ujian terstruktur"
  const MOVE_TO_TIMER = 1050;   // Seg 48: "Sistem akan mengaktifkan penghitung waktu mundur otomatis"
  const MOVE_TO_NUMS = 1180;    // Seg 49: "Gunakan bilah nomor soal untuk meninjau status pengerjaan"
  const MOVE_TO_ANSWER = 1320;  // Seg 50: "Jawab seluruh butir instrumen dengan teliti"
  const MOVE_TO_SUBMIT = 1480;  // Seg 51: "dan klik tombol kumpulkan"
  const CLICK_SUBMIT_QUIZ = 1530;
  const CONFIRM_QUIZ_SUBMIT = 1575;

  const isQuizPhase = frame >= LOAD_QUIZ_SCREEN;

  // Live countdown timer calculation for Quiz header
  const quizElapsedSec = Math.floor(Math.max(0, frame - LOAD_QUIZ_SCREEN) / 30);
  const quizRemainingSec = Math.max(0, 59 * 60 + 57 - quizElapsedSec);
  const quizMins = Math.floor(quizRemainingSec / 60);
  const quizSecs = quizRemainingSec % 60;

  // === CURSOR LOGIC (CALM & PURPOSEFUL) ===
  let cursorX = 340;
  let cursorY = 215;
  let isClicking = false;
  let clickFrame = -1;

  if (!isQuizPhase) {
    // Fase 1: Halaman Detail Penugasan Mandiri (22_mahasiswa_tugas_detail.png)
    if (frame < MOVE_TO_PETUNJUK) {
      cursorX = 340;
      cursorY = 215;
    } else if (frame < MOVE_TO_CPMK) {
      cursorX = interpolate(frame, [MOVE_TO_PETUNJUK, MOVE_TO_PETUNJUK + 25], [340, 350], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [MOVE_TO_PETUNJUK, MOVE_TO_PETUNJUK + 25], [215, 340], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (frame < MOVE_TO_UPLOAD) {
      cursorX = interpolate(frame, [MOVE_TO_CPMK, MOVE_TO_CPMK + 25], [350, 340], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [MOVE_TO_CPMK, MOVE_TO_CPMK + 25], [340, 480], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (frame < CLICK_ADD_FILE) {
      // Bergerak ke tombol + Tambah atau buat (1695, 357)
      cursorX = interpolate(frame, [MOVE_TO_UPLOAD, CLICK_ADD_FILE - 2], [340, 1695], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [MOVE_TO_UPLOAD, CLICK_ADD_FILE - 2], [480, 357], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (frame < CLICK_FILE_OPTION) {
      // Menunjuk opsi Berkas / File di dropdown (1695, 410)
      cursorX = 1695;
      cursorY = interpolate(frame, [CLICK_ADD_FILE + 5, CLICK_FILE_OPTION - 2], [357, 410], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      if (frame >= CLICK_ADD_FILE && frame < CLICK_ADD_FILE + 16) {
        clickFrame = CLICK_ADD_FILE;
        if (frame <= CLICK_ADD_FILE + 5) isClicking = true;
      }
    } else if (frame < CLICK_SUBMIT_TASK) {
      // Bergerak ke tombol Kumpulkan Tugas (1695, 465)
      cursorX = 1695;
      cursorY = interpolate(frame, [CLICK_FILE_OPTION + 8, CLICK_SUBMIT_TASK - 2], [410, 465], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      if (frame >= CLICK_FILE_OPTION && frame < CLICK_FILE_OPTION + 16) {
        clickFrame = CLICK_FILE_OPTION;
        if (frame <= CLICK_FILE_OPTION + 5) isClicking = true;
      }
    } else {
      // Klik tombol Kumpulkan Tugas
      cursorX = 1695;
      cursorY = 465;
      if (frame >= CLICK_SUBMIT_TASK && frame < CLICK_SUBMIT_TASK + 16) {
        clickFrame = CLICK_SUBMIT_TASK;
        if (frame <= CLICK_SUBMIT_TASK + 5) isClicking = true;
      }
    }
  } else {
    // Fase 2: Halaman Kuis / Ujian Berwaktu (20_kuis_ujian_timer.png)
    if (frame < MOVE_TO_TIMER) {
      cursorX = interpolate(frame, [LOAD_QUIZ_SCREEN, LOAD_QUIZ_SCREEN + 30], [1035, 738], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [LOAD_QUIZ_SCREEN, LOAD_QUIZ_SCREEN + 30], [570, 28], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (frame < MOVE_TO_NUMS) {
      // Menunjuk timer mundur otomatis di header (738, 28)
      cursorX = 738;
      cursorY = 28;
    } else if (frame < MOVE_TO_ANSWER) {
      // Meninjau bilah nomor soal (920, 28)
      cursorX = interpolate(frame, [MOVE_TO_NUMS, MOVE_TO_NUMS + 25], [738, 920], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = 28;
    } else if (frame < MOVE_TO_SUBMIT) {
      // Menunjuk pasangan jawaban (880, 300)
      cursorX = interpolate(frame, [MOVE_TO_ANSWER, MOVE_TO_ANSWER + 25], [920, 880], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [MOVE_TO_ANSWER, MOVE_TO_ANSWER + 25], [28, 300], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (frame < CLICK_SUBMIT_QUIZ) {
      // Bergerak ke tombol Kumpulkan Kuis di kanan atas (1790, 28)
      cursorX = interpolate(frame, [MOVE_TO_SUBMIT, CLICK_SUBMIT_QUIZ - 2], [880, 1790], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [MOVE_TO_SUBMIT, CLICK_SUBMIT_QUIZ - 2], [300, 28], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (frame < CONFIRM_QUIZ_SUBMIT) {
      // Bergerak ke tombol Ya, Kumpulkan di modal konfirmasi (1055, 570)
      cursorX = interpolate(frame, [CLICK_SUBMIT_QUIZ + 5, CONFIRM_QUIZ_SUBMIT - 2], [1790, 1055], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [CLICK_SUBMIT_QUIZ + 5, CONFIRM_QUIZ_SUBMIT - 2], [28, 570], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      if (frame >= CLICK_SUBMIT_QUIZ && frame < CLICK_SUBMIT_QUIZ + 16) {
        clickFrame = CLICK_SUBMIT_QUIZ;
        if (frame <= CLICK_SUBMIT_QUIZ + 5) isClicking = true;
      }
    } else {
      cursorX = 1055;
      cursorY = 570;
      if (frame >= CONFIRM_QUIZ_SUBMIT && frame < CONFIRM_QUIZ_SUBMIT + 16) {
        clickFrame = CONFIRM_QUIZ_SUBMIT;
        if (frame <= CONFIRM_QUIZ_SUBMIT + 5) isClicking = true;
      }
    }
  }

  const isDropdownOpen = frame >= CLICK_ADD_FILE && frame < CLICK_FILE_OPTION;
  const isFileAttached = frame >= CLICK_FILE_OPTION;
  const isTaskSubmitted = frame >= CLICK_SUBMIT_TASK;
  const isQuizModalOpen = frame >= CLICK_SUBMIT_QUIZ && frame < CONFIRM_QUIZ_SUBMIT;
  const isQuizSubmitted = frame >= CONFIRM_QUIZ_SUBMIT;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* 100% Full Screen Authentic SALE Screens */}
      {isQuizPhase ? (
        <Img
          src={staticFile("screens/20_kuis_ujian_timer.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover", objectPosition: "top center" }}
        />
      ) : isTaskSubmitted ? (
        <Img
          src={staticFile("screens/28_mahasiswa_tugas_diserahkan.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : isDropdownOpen ? (
        <Img
          src={staticFile("screens/27_mahasiswa_tugas_dropdown.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : (
        <Img
          src={staticFile("screens/22_mahasiswa_tugas_detail.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      )}

      {/* PHASE 1: Attached File Pill before Submit (Authentic representation) */}
      {!isQuizPhase && isFileAttached && !isTaskSubmitted && (
        <div
          style={{
            position: "absolute",
            left: 1546,
            top: 388,
            width: 298,
            height: 38,
            backgroundColor: "#f8fafc",
            borderRadius: 8,
            border: "1px solid #cbd5e1",
            padding: "0 10px",
            display: "flex",
            alignItems: "center",
            gap: 8,
            zIndex: 25,
          }}
        >
          <span style={{ fontSize: 13, color: "#64748b" }}>📎</span>
          <span
            style={{
              fontSize: 11,
              fontWeight: 600,
              color: "#0f172a",
              whiteSpace: "nowrap",
              overflow: "hidden",
              textOverflow: "ellipsis",
            }}
          >
            Laporan_Praktikum_Struktur_Data_Ahma...
          </span>
        </div>
      )}

      {/* PHASE 2 OVERLAYS: Live Countdown Timer & Quiz Submission Modal */}
      {isQuizPhase && (
        <>
          {/* Live Countdown Timer in Header */}
          <div
            style={{
              position: "absolute",
              left: 686,
              top: 14,
              width: 106,
              height: 30,
              backgroundColor: "#f8fafc",
              borderRadius: 6,
              border: "1px solid #cbd5e1",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              gap: 6,
              zIndex: 25,
            }}
          >
            <span style={{ fontSize: 13, color: "#475569" }}>🕒</span>
            <span
              style={{
                fontFamily: "monospace",
                fontSize: 13,
                fontWeight: 700,
                color: "#1e293b",
                letterSpacing: 0.5,
              }}
            >
              {quizMins.toString().padStart(2, "0")}:{quizSecs.toString().padStart(2, "0")}
            </span>
          </div>

          {/* Quiz Submission Confirmation Modal */}
          {isQuizModalOpen && (
            <div
              style={{
                position: "absolute",
                inset: 0,
                backgroundColor: "rgba(15, 23, 42, 0.5)",
                backdropFilter: "blur(2px)",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                zIndex: 50,
              }}
            >
              <div
                style={{
                  width: 480,
                  backgroundColor: "#ffffff",
                  borderRadius: 16,
                  padding: 24,
                  boxShadow: "0 20px 40px rgba(0,0,0,0.25)",
                }}
              >
                <h3 style={{ fontSize: 18, fontWeight: 700, color: "#0f172a", marginBottom: 8 }}>
                  Konfirmasi Pengumpulan Kuis
                </h3>
                <p style={{ fontSize: 13, color: "#475569", lineHeight: "20px", marginBottom: 20 }}>
                  Apakah Anda yakin ingin menyelesaikan Praktikum Binary Tree? Seluruh 6 dari 6 butir instrumen evaluasi telah terjawab lengkap.
                </p>
                <div style={{ display: "flex", justifyContent: "flex-end", gap: 12 }}>
                  <div
                    style={{
                      padding: "8px 18px",
                      borderRadius: 8,
                      border: "1px solid #cbd5e1",
                      color: "#475569",
                      fontSize: 13,
                      fontWeight: 600,
                    }}
                  >
                    Periksa Kembali
                  </div>
                  <div
                    style={{
                      padding: "8px 20px",
                      borderRadius: 8,
                      backgroundColor: "#16a34a",
                      color: "#ffffff",
                      fontSize: 13,
                      fontWeight: 600,
                      boxShadow: "0 2px 8px rgba(22, 163, 74, 0.3)",
                    }}
                  >
                    Ya, Kumpulkan
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Success Banner after Quiz Submission */}
          {isQuizSubmitted && (
            <div
              style={{
                position: "absolute",
                top: 80,
                left: "50%",
                transform: "translateX(-50%)",
                backgroundColor: "#16a34a",
                color: "#ffffff",
                padding: "10px 24px",
                borderRadius: 10,
                boxShadow: "0 10px 25px rgba(22, 163, 74, 0.35)",
                display: "flex",
                alignItems: "center",
                gap: 10,
                fontSize: 14,
                fontWeight: 600,
                zIndex: 40,
              }}
            >
              <span>✓</span>
              <span>Praktikum Binary Tree Berhasil Dikumpulkan! Seluruh jawaban tersimpan aman.</span>
            </div>
          )}
        </>
      )}

      {/* Animated Cursor */}
      {frame >= 200 && (
        <AnimatedCursor
          x={cursorX}
          y={cursorY}
          isClicking={isClicking}
          clickFrame={clickFrame}
        />
      )}

      {/* Chapter 03 Title Slide Bumper (Intro 0 - 200f) */}
      {frame < 200 && (
        <ChapterBumper
          title="Penugasan Mandiri & Ujian Berwaktu"
          subtitle="Pengukuran Pemahaman Melalui Tugas Studi dan Evaluasi Terstruktur"
          highlightWords={["Penugasan", "Mandiri", "Ujian", "Berwaktu"]}
          durationInFrames={200}
        />
      )}

      {/* Dynamic Voiceover Subtitles with Broadcast Shadow Effect (No fake labels, no numbers) */}
      <ScreencastSubtitles
        items={[
          {
            startFrame: 200,
            durationInFrames: 164, // 200 to 364
            heading: "Evaluasi Akademik Terstruktur",
            subtext: "Tugas mandiri, kuis berwaktu, hingga ujian semester untuk mengukur kompetensi",
          },
          {
            startFrame: 364,
            durationInFrames: 140, // 364 to 504
            heading: "Petunjuk Pengerjaan Tugas",
            subtext: "Cermati instruksi penugasan dan kriteria pengerjaan sebelum memulai",
          },
          {
            startFrame: 504,
            durationInFrames: 169, // 504 to 673
            heading: "Rubrik Penilaian Objektif",
            subtext: "Pahami standar penilaian yang ditetapkan oleh dosen pengampu",
          },
          {
            startFrame: 673,
            durationInFrames: 149, // 673 to 822
            heading: "Unggah Dokumen Laporan",
            subtext: "Simpan berkas studi langsung ke sistem sebelum batas tenggat berakhir",
          },
          {
            startFrame: 822,
            durationInFrames: 176, // 822 to 998
            heading: "Kuis Berwaktu & Ujian Terstruktur",
            subtext: "Pastikan koneksi internet stabil sebelum memulai pengerjaan instrumen",
          },
          {
            startFrame: 998,
            durationInFrames: 211, // 998 to 1209
            heading: "Penghitung Waktu Mundur Otomatis",
            subtext: "Sistem mengaktifkan timer otomatis selama evaluasi berlangsung",
          },
          {
            startFrame: 1209,
            durationInFrames: 170, // 1209 to 1379
            heading: "Navigasi Bilah Nomor Soal",
            subtext: "Tinjau status pengerjaan seluruh butir pertanyaan secara berkala",
          },
          {
            startFrame: 1379,
            durationInFrames: 148, // 1379 to 1527
            heading: "Jawab Instrumen Evaluasi",
            subtext: "Kerjakan butir soal dengan teliti lalu klik tombol kumpulkan",
          },
          {
            startFrame: 1527,
            durationInFrames: 103, // 1527 to 1630
            heading: "Konfirmasi Akhir Pengumpulan",
            subtext: "Pastikan seluruh butir jawaban terisi lengkap sebelum dikonfirmasi",
          },
        ]}
      />
    </div>
  );
};
