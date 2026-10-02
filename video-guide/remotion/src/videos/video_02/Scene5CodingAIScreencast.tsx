import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  ScreencastSubtitles,
  SubtitleItem,
} from "../../components/ScreencastEngine";
import { ChapterBumper } from "../../components/ChapterBumper";

/**
 * BAB 04 — Praktikum Pemrograman & Asisten Coding AI
 * Durasi: 1640 frames (54.67 detik) — Full sync dengan VO2 Seg 53 - 66
 *
 * Screen: 18_coding_ai_assistant.png (100% Layar Penuh Otentik)
 * Alur sinkron suara:
 * - Seg 53-56 (f0-f430): Lingkungan coding praktikum di browser
 * - Seg 57 (f452-f572): Tulis kode solusi pada editor interaktif
 * - Seg 58 (f596-f645): Klik tombol Jalankan (Run) pada terminal
 * - Seg 59 (f645-f812): Uji algoritma terhadap kasus uji otomatis di terminal
 * - Seg 60-61 (f823-f1019): Beralih ke Asisten AI di panel kanan (Camera Zoom)
 * - Seg 62-64 (f1036-f1355): Bimbingan pedagogis konseptual tanpa contekan
 * - Seg 65-66 (f1372-f1640): Kemandirian problem solving & kumpulkan kode solusi
 */
export const Scene5CodingAIScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // === TIMING MILESTONES (Sync dengan vo2_timestamps.json Seg 53-66) ===
  const TYPE_CODE_START = 260;   // Seg 56-57: Mulai menuliskan kode logika rekursif
  const TYPE_CODE_END = 540;
  const MOVE_TO_RUN_BTN = 560;   // Seg 57: Persiapkan pengujian algoritma
  const CLICK_RUN_BTN = 620;     // Seg 58: Klik tombol Jalankan (Run)
  const START_AI_PHASE = 820;    // Seg 60: Beralih ke fokus Asisten Lumina AI (Zoom in)
  const CLICK_AI_CHIP = 880;     // Seg 61: Tanya konsep ke asisten AI
  const END_AI_PHASE = 1350;     // Seg 65: Selesai bimbingan AI, kembali ke pengumpulan (Zoom out)
  const CLICK_SUBMIT_CODE = 1500;// Seg 66: Kumpulkan kode solusi akhir

  const isAiPhase = frame >= START_AI_PHASE && frame < END_AI_PHASE;
  const isBlinking = Math.floor(frame / 10) % 2 === 0;

  // === CALM & PURPOSEFUL CURSOR (NO AIMLESS JITTER) ===
  let cursorX = 500;
  let cursorY = 320;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < MOVE_TO_RUN_BTN) {
    // Berada di editor kode selama penulisan solusi
    cursorX = 500;
    cursorY = 320;
  } else if (frame < CLICK_RUN_BTN) {
    // Bergerak langsung ke tombol Jalankan (Run ▶) hijau di toolbar (1521, 100)
    cursorX = interpolate(frame, [MOVE_TO_RUN_BTN, CLICK_RUN_BTN - 2], [500, 1521], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_RUN_BTN, CLICK_RUN_BTN - 2], [320, 100], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < START_AI_PHASE) {
    // Meninjau hasil keluaran terminal (650, 880)
    cursorX = interpolate(frame, [CLICK_RUN_BTN + 10, CLICK_RUN_BTN + 35], [1521, 650], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [CLICK_RUN_BTN + 10, CLICK_RUN_BTN + 35], [100, 880], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    if (frame >= CLICK_RUN_BTN && frame < CLICK_RUN_BTN + 16) {
      clickFrame = CLICK_RUN_BTN;
      if (frame <= CLICK_RUN_BTN + 5) isClicking = true;
    }
  } else if (frame < CLICK_AI_CHIP) {
    // Bergerak ke panel AI Asisten di kanan (1700, 415)
    cursorX = interpolate(frame, [START_AI_PHASE, CLICK_AI_CHIP - 2], [650, 1700], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [START_AI_PHASE, CLICK_AI_CHIP - 2], [880, 415], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < 1100) {
    // Mengamati pertanyaan dan bimbingan awal AI (1700, 415)
    cursorX = 1700;
    cursorY = 415;
    if (frame >= CLICK_AI_CHIP && frame < CLICK_AI_CHIP + 16) {
      clickFrame = CLICK_AI_CHIP;
      if (frame <= CLICK_AI_CHIP + 5) isClicking = true;
    }
  } else if (frame < 1280) {
    // Menunjuk jawaban bimbingan pedagogis konseptual AI (1700, 560)
    cursorX = 1700;
    cursorY = interpolate(frame, [1100, 1140], [415, 560], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < END_AI_PHASE) {
    // Menunjuk kolom input prompt pertanyaan di bagian bawah (1700, 930)
    cursorX = 1700;
    cursorY = interpolate(frame, [1280, 1310], [560, 930], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < CLICK_SUBMIT_CODE) {
    // Bergerak ke tombol Serahkan ✓ di kanan atas (1844, 28)
    cursorX = interpolate(frame, [END_AI_PHASE, CLICK_SUBMIT_CODE - 2], [1700, 1844], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [END_AI_PHASE, CLICK_SUBMIT_CODE - 2], [930, 28], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 1844;
    cursorY = 28;
    if (frame >= CLICK_SUBMIT_CODE && frame < CLICK_SUBMIT_CODE + 16) {
      clickFrame = CLICK_SUBMIT_CODE;
      if (frame <= CLICK_SUBMIT_CODE + 5) isClicking = true;
    }
  }

  const isAiAnswerShown = frame >= CLICK_AI_CHIP;
  const isCodeSubmitted = frame >= CLICK_SUBMIT_CODE;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Full screen authentic SALE workbench without cropping bottom input box */}
      <div
        style={{
          width: "100%",
          height: "100%",
          position: "relative",
        }}
      >
        {/* Authentic System Screens (100% genuine SALE workbench) */}
        <Img
          src={staticFile(
            isAiAnswerShown
              ? "screens/24_mahasiswa_coding_ai_chat.png"
              : frame >= CLICK_RUN_BTN
              ? "screens/23_mahasiswa_coding_terminal.png"
              : frame >= TYPE_CODE_START
              ? "screens/23_mahasiswa_coding_code.png"
              : "screens/23_mahasiswa_coding_initial.png"
          )}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />

        {/* Submitted Status Pill over 'Serahkan ✓' button */}
        {isCodeSubmitted && (
          <div
            style={{
              position: "absolute",
              left: 1793,
              top: 8,
              width: 103,
              height: 40,
              backgroundColor: "#16a34a",
              color: "#ffffff",
              borderRadius: 8,
              fontSize: 13,
              fontWeight: 600,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              gap: 4,
              zIndex: 30,
              boxShadow: "0 2px 8px rgba(22, 163, 74, 0.35)",
            }}
          >
            <span>✓</span>
            <span>Terkumpul</span>
          </div>
        )}

        {/* Success Toast after Code Submission */}
        {isCodeSubmitted && (
          <div
            style={{
              position: "absolute",
              top: 24,
              left: "50%",
              transform: "translateX(-50%)",
              backgroundColor: "#16a34a",
              color: "#ffffff",
              padding: "10px 24px",
              borderRadius: 8,
              boxShadow: "0 10px 25px rgba(22, 163, 74, 0.35)",
              fontSize: 13,
              fontWeight: 600,
              display: "flex",
              alignItems: "center",
              gap: 8,
              zIndex: 40,
            }}
          >
            <span style={{ fontSize: 16 }}>✓</span>
            <span>Solusi Praktikum Berhasil Diserahkan! Seluruh test case terpenuhi.</span>
          </div>
        )}

        {/* Animated Cursor pinned to screen coordinates */}
        {frame >= 210 && (
          <AnimatedCursor
            x={cursorX}
            y={cursorY}
            isClicking={isClicking}
            clickFrame={clickFrame}
          />
        )}
      </div>

      {/* Chapter 04 Title Slide Bumper (Intro 0 - 210f) */}
      {frame < 210 && (
        <ChapterBumper
          title="Praktikum Coding & Asisten AI"
          subtitle="Lingkungan Pemrograman In-Browser dengan Bimbingan Cerdas"
          highlightWords={["Praktikum", "Coding", "Asisten", "AI"]}
          durationInFrames={210}
        />
      )}

      {/* Dynamic Voiceover Subtitles with Broadcast Shadow Effect (No fake labels, no numbers) */}
      <ScreencastSubtitles
        items={[
          {
            startFrame: 210,
            durationInFrames: 106, // 210 to 316
            heading: "Lingkungan Praktikum In-Browser",
            subtext: "Ekosistem pemrograman modern yang terintegrasi langsung di web peramban",
          },
          {
            startFrame: 316,
            durationInFrames: 137, // 316 to 453
            heading: "Bebas Instalasi Compiler Lokal",
            subtext: "Mulai praktikum seketika tanpa konfigurasi rumit di komputer lokal",
          },
          {
            startFrame: 453,
            durationInFrames: 143, // 453 to 596
            heading: "Editor Kode Solusi Interaktif",
            subtext: "Tuliskan struktur logika algoritma langsung pada editor kode terintegrasi",
          },
          {
            startFrame: 596,
            durationInFrames: 227, // 596 to 823
            heading: "Eksekusi & Pengujian Otomatis",
            subtext: "Jalankan kode dan uji algoritma terhadap contoh kasus uji publik seketika",
          },
          {
            startFrame: 823,
            durationInFrames: 214, // 823 to 1037
            heading: "Asisten Coding AI Cerdas",
            subtext: "Manfaatkan panduan AI di panel kanan saat menghadapi kendala pemrograman",
          },
          {
            startFrame: 1037,
            durationInFrames: 245, // 1037 to 1282
            heading: "Bimbingan Pedagogis & Analisis Eror",
            subtext: "Dapatkan petunjuk konseptual dan analogi perbaikan logika terarah",
          },
          {
            startFrame: 1282,
            durationInFrames: 90, // 1282 to 1372
            heading: "Kemandirian Problem Solving",
            subtext: "Membimbing penalaran mandiri tanpa memberikan contekan jawaban langsung",
          },
          {
            startFrame: 1372,
            durationInFrames: 268, // 1372 to 1640
            heading: "Pengumpulan Kode Solusi",
            subtext: "Selesaikan tantangan algoritma hingga seluruh kasus uji terpenuhi sempurna",
          },
        ]}
      />
    </div>
  );
};
