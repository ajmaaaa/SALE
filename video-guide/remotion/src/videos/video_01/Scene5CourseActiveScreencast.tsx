import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene5CourseActiveScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // =====================================================================
  // TIMING: 900 frames = 30 detik
  //
  // Kursor melakukan eksplorasi komprehensif di halaman Course Detail:
  //   Fase 1 (0–120)   : Dari modal submit → bergerak ke header kelas, membaca judul
  //   Fase 2 (120–280) : Hover silabus mingguan (kiri), scroll down secara visual
  //   Fase 3 (280–450) : Bergerak ke area pemutar video materi (tengah)
  //   Fase 4 (450–620) : Bergerak ke panel forum diskusi (kanan), hover pesan
  //   Fase 5 (620–820) : Hover attachment/modul PDF, lalu kembali ke tengah
  //   Fase 6 (820–900) : Kursor diam di area center — scene selesai
  // =====================================================================

  let cursorX = 1099;
  let cursorY = 641;

  if (frame < 120) {
    // Fase 1 — dari area modal submit ke header kelas (520, 155)
    if (frame < 80) {
      cursorX = interpolate(frame, [6, 75], [1099, 520], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [6, 75], [641, 155], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else {
      // Berhenti di header, membaca
      cursorX = 520;
      cursorY = 155;
    }
  } else if (frame < 280) {
    // Fase 2 — silabus mingguan di sisi kiri (250, 320) → (250, 480)
    if (frame < 180) {
      cursorX = interpolate(frame, [120, 175], [520, 250], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [120, 175], [155, 320], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else {
      // Scroll down visual pada silabus
      cursorX = 250;
      cursorY = interpolate(frame, [180, 275], [320, 480], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    }
  } else if (frame < 450) {
    // Fase 3 — bergerak ke area pemutar video materi (650, 390)
    if (frame < 380) {
      cursorX = interpolate(frame, [280, 375], [250, 650], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [280, 375], [480, 390], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else {
      // Hover di area video player
      cursorX = 650;
      cursorY = 390;
    }
  } else if (frame < 620) {
    // Fase 4 — bergerak ke panel forum diskusi (1450, 350) → hover pesan
    if (frame < 540) {
      cursorX = interpolate(frame, [450, 535], [650, 1450], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [450, 535], [390, 350], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else {
      // Hover di panel forum / chat
      cursorX = interpolate(frame, [540, 615], [1450, 1500], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [540, 615], [350, 500], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    }
  } else if (frame < 820) {
    // Fase 5 — bergerak ke area lampiran/modul PDF (630, 600)
    if (frame < 700) {
      cursorX = interpolate(frame, [620, 695], [1500, 630], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [620, 695], [500, 600], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else {
      // Hover area lampiran, lalu kembali ke tengah
      cursorX = interpolate(frame, [700, 815], [630, 960], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(frame, [700, 815], [600, 400], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    }
  } else {
    // Fase 6 — kursor diam di center, scene selesai
    cursorX = 960;
    cursorY = 400;
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Authentic Screen: Full Real Course Learning Page with Videos, Forums, and Modules */}
      <Img
        src={staticFile("screens/15_mahasiswa_course_detail.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Animated Cursor — no phantom clicks, no artificial overlay */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={false}
        clickFrame={-1}
      />

      {/*
        Cinematic Split Layout — teks instruksi di kiri bawah (langkah + aksi),
        deskripsi lanjutan di kanan bawah, sesuai SOP TUTORIAL_EDIT_SOP § 3.2 split-cinematic
      */}
      <InstructionOverlay
        step="03"
        actionText="Kelas Perkuliahan Resmi Aktif"
        detailText="Silabus mingguan, pemutar video materi, lampiran modul PDF, dan forum diskusi real-time sudah siap digunakan"
        position="split-cinematic"
      />
    </div>
  );
};
