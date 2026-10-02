import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene3DashboardScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // =====================================================================
  // TIMING: 1050 frames = 35 detik
  //
  // Fase 1 — Dashboard → Course Nav (0–280)
  //   Kursor dari center dashboard bergerak ke menu Course di sidebar kiri
  //   Klik → hard cut ke halaman Course list
  //
  // Fase 2 — Course Page → Klik + Gabung Kelas (280–480)
  //   Kursor bergerak dari kiri ke tombol + Gabung Kelas (kanan atas)
  //   Klik → hard cut ke modal dialog
  //
  // Fase 3 — Modal Dialog → Ketik kode → Submit (480–780)
  //   Kursor bergerak ke input kode, klik, ketik "IF204-A", lalu submit
  //
  // Fase 4 — Tunggu konfirmasi / baca halaman Course (780–1050)
  //   Layar tetap di modal (sudah klik submit), kursor bergerak natural menunggu
  // =====================================================================

  // ── Milestone frames ──────────────────────────────────────────────────
  const MOVE_TO_COURSE_START   =  30;   // Mulai bergerak ke sidebar Course
  const CLICK_COURSE_NAV_FRAME = 160;   // Klik menu Course
  const LOAD_COURSE_PAGE_FRAME = 168;   // 8 frames pasca klik = halaman Course muncul

  const MOVE_TO_JOIN_BTN_START = 220;   // Mulai bergerak ke tombol + Gabung Kelas
  const CLICK_JOIN_BTN_FRAME   = 420;   // Klik + Gabung Kelas
  const LOAD_MODAL_FRAME       = 428;   // 8 frames pasca klik = modal muncul

  const MOVE_TO_INPUT_START    = 500;   // Mulai bergerak ke kolom input kode
  const CLICK_CODE_INPUT_FRAME = 600;   // Klik kolom input
  const TYPE_CODE_START        = 608;   // Mulai ketik kode
  const TYPE_CODE_END          = 780;   // Selesai ketik kode (lambat + realistis)

  const MOVE_TO_SUBMIT_START   = 840;   // Mulai bergerak ke tombol Gabung Kelas
  const CLICK_MODAL_SUBMIT     = 980;   // Klik tombol submit

  // ── Cursor logic ─────────────────────────────────────────────────────
  let cursorX = 600;
  let cursorY = 400;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < MOVE_TO_COURSE_START) {
    // Diam di center dashboard
    cursorX = 600;
    cursorY = 400;
  } else if (frame >= MOVE_TO_COURSE_START && frame < CLICK_COURSE_NAV_FRAME) {
    // Bergerak ke sidebar Course (124, 194) — gerak perlahan
    cursorX = interpolate(frame, [MOVE_TO_COURSE_START, CLICK_COURSE_NAV_FRAME - 2], [600, 124], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_COURSE_START, CLICK_COURSE_NAV_FRAME - 2], [400, 194], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_COURSE_NAV_FRAME && frame < MOVE_TO_JOIN_BTN_START) {
    // Diam di tombol Course, klik
    cursorX = 124;
    cursorY = 194;
    if (frame >= CLICK_COURSE_NAV_FRAME && frame <= CLICK_COURSE_NAV_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_COURSE_NAV_FRAME;
    }
  } else if (frame >= MOVE_TO_JOIN_BTN_START && frame < CLICK_JOIN_BTN_FRAME) {
    // Pada halaman Course: bergerak ke tombol "+" Gabung Kelas (1802, 127) — panjang diagonal
    cursorX = interpolate(frame, [MOVE_TO_JOIN_BTN_START, CLICK_JOIN_BTN_FRAME - 2], [124, 1802], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_JOIN_BTN_START, CLICK_JOIN_BTN_FRAME - 2], [194, 127], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_JOIN_BTN_FRAME && frame < MOVE_TO_INPUT_START) {
    // Diam di tombol + Gabung Kelas, klik
    cursorX = 1802;
    cursorY = 127;
    if (frame >= CLICK_JOIN_BTN_FRAME && frame <= CLICK_JOIN_BTN_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_JOIN_BTN_FRAME;
    }
  } else if (frame >= MOVE_TO_INPUT_START && frame < CLICK_CODE_INPUT_FRAME) {
    // Modal sudah muncul — bergerak ke kolom input kode (952, 531)
    cursorX = interpolate(frame, [MOVE_TO_INPUT_START, CLICK_CODE_INPUT_FRAME - 2], [1802, 952], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_INPUT_START, CLICK_CODE_INPUT_FRAME - 2], [127, 531], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_CODE_INPUT_FRAME && frame < MOVE_TO_SUBMIT_START) {
    // Diam di input kode, klik
    cursorX = 952;
    cursorY = 531;
    if (frame >= CLICK_CODE_INPUT_FRAME && frame <= CLICK_CODE_INPUT_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_CODE_INPUT_FRAME;
    }
  } else if (frame >= MOVE_TO_SUBMIT_START && frame < CLICK_MODAL_SUBMIT) {
    // Bergerak ke tombol "Gabung Kelas" (1099, 641)
    cursorX = interpolate(frame, [MOVE_TO_SUBMIT_START, CLICK_MODAL_SUBMIT - 2], [952, 1099], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_SUBMIT_START, CLICK_MODAL_SUBMIT - 2], [531, 641], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    // Klik tombol Gabung Kelas, lalu diam
    cursorX = 1099;
    cursorY = 641;
    if (frame >= CLICK_MODAL_SUBMIT && frame <= CLICK_MODAL_SUBMIT + 5) {
      isClicking = true;
      clickFrame = CLICK_MODAL_SUBMIT;
    }
  }

  // ── Typing simulation (kode kelas) ───────────────────────────────────
  const codeStr = "IF204-A";
  const charsTyped = Math.floor(
    interpolate(frame, [TYPE_CODE_START, TYPE_CODE_END], [0, codeStr.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentCode = codeStr.slice(0, charsTyped);
  const isBlinking = Math.floor(frame / 10) % 2 === 0;

  // ── Screen state logic ───────────────────────────────────────────────
  const isCoursePage = frame >= LOAD_COURSE_PAGE_FRAME;
  const isModalOpen  = frame >= LOAD_MODAL_FRAME;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/*
        AUTHENTIC ACTION-REACTION SCREEN FLOW (seamless hard cuts):
        1. frame 0 – 128    : Mahasiswa Dashboard
        2. frame 128 – 288  : Mahasiswa Course List
        3. frame 288+        : Modal "Gabung Kelas Perkuliahan"
      */}
      {!isCoursePage ? (
        <Img
          src={staticFile("screens/03_mahasiswa_dashboard.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : !isModalOpen ? (
        <Img
          src={staticFile("screens/04_mahasiswa_course.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : (
        <>
          <Img
            src={staticFile("screens/14_modal_gabung_kelas.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />
          {/* Kode yang diketik di dalam input modal resmi */}
          {frame >= TYPE_CODE_START - 2 && (
            <div
              style={{
                position: "absolute",
                left: 752,
                top: 513,
                width: 400,
                height: 36,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 10,
                borderRadius: 4,
                fontFamily: "monospace",
                fontSize: 16,
                fontWeight: 700,
                color: "#0f172a",
                letterSpacing: 2,
              }}
            >
              <span>{currentCode}</span>
              {frame < MOVE_TO_SUBMIT_START && isBlinking && (
                <span style={{ color: "#2563eb", marginLeft: 2 }}>|</span>
              )}
            </div>
          )}
        </>
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/*
        Instruction Overlay — posisi bervariasi sesuai konteks aksi (anti-monoton):
        - Dashboard  → center-right (sidebar aksi di kiri, teks di kanan bawah)
        - Course List → bottom-left (tombol di kanan atas, teks di kiri bawah)
        - Modal       → bottom-center (aksi di tengah, teks di bawah tengah)
      */}
      {!isCoursePage ? (
        <InstructionOverlay
          step="02"
          actionText="Buka Menu Course"
          detailText="Klik menu Course pada bilah navigasi kiri untuk mengakses portal perkuliahan Anda"
          position="center-right"
        />
      ) : !isModalOpen ? (
        <InstructionOverlay
          step="02"
          actionText="Tambah Gabung Kelas"
          detailText="Klik tombol + Gabung Kelas di sudut kanan atas untuk membuka formulir pendaftaran kelas"
          position="bottom-left"
        />
      ) : (
        <InstructionOverlay
          step="02"
          actionText="Masukkan Kode Akses Perkuliahan"
          detailText="Ketikkan kode kelas dari dosen pengampu, lalu tekan tombol Gabung Kelas"
          position="bottom-center"
        />
      )}
    </div>
  );
};
