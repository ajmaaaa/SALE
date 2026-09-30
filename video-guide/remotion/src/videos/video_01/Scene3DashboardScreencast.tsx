import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene3DashboardScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Timing milestones
  const CLICK_COURSE_NAV_FRAME = 44;
  const LOAD_COURSE_PAGE_FRAME = 52; // Action-reaction 1: Screen cuts to Course page 8 frames after click
  const CLICK_JOIN_BTN_FRAME = 92;
  const LOAD_MODAL_FRAME = 100; // Action-reaction 2: Screen cuts to Modal dialog 8 frames after click
  const CLICK_CODE_INPUT_FRAME = 122;
  const CLICK_MODAL_SUBMIT_FRAME = 172;

  let cursorX = 600;
  let cursorY = 400;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < CLICK_COURSE_NAV_FRAME) {
    // 1. Moving from dashboard center to sidebar Course (124, 194)
    cursorX = interpolate(frame, [8, CLICK_COURSE_NAV_FRAME - 2], [600, 124], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [8, CLICK_COURSE_NAV_FRAME - 2], [400, 194], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_COURSE_NAV_FRAME && frame < LOAD_COURSE_PAGE_FRAME + 4) {
    cursorX = 124;
    cursorY = 194;
    if (frame >= CLICK_COURSE_NAV_FRAME && frame <= CLICK_COURSE_NAV_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_COURSE_NAV_FRAME;
    }
  } else if (frame >= LOAD_COURSE_PAGE_FRAME + 4 && frame < CLICK_JOIN_BTN_FRAME) {
    // 2. On Course page: Moving to "+ Gabung Kelas" button at top right (1802, 127)
    cursorX = interpolate(frame, [LOAD_COURSE_PAGE_FRAME + 4, CLICK_JOIN_BTN_FRAME - 2], [124, 1802], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_COURSE_PAGE_FRAME + 4, CLICK_JOIN_BTN_FRAME - 2], [194, 127], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_JOIN_BTN_FRAME && frame < LOAD_MODAL_FRAME + 4) {
    cursorX = 1802;
    cursorY = 127;
    if (frame >= CLICK_JOIN_BTN_FRAME && frame <= CLICK_JOIN_BTN_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_JOIN_BTN_FRAME;
    }
  } else if (frame >= LOAD_MODAL_FRAME + 4 && frame < CLICK_CODE_INPUT_FRAME) {
    // 3. Modal open: Moving to code input (952, 531)
    cursorX = interpolate(frame, [LOAD_MODAL_FRAME + 4, CLICK_CODE_INPUT_FRAME - 2], [1802, 952], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_MODAL_FRAME + 4, CLICK_CODE_INPUT_FRAME - 2], [127, 531], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_CODE_INPUT_FRAME && frame < 150) {
    cursorX = 952;
    cursorY = 531;
    if (frame >= CLICK_CODE_INPUT_FRAME && frame <= CLICK_CODE_INPUT_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_CODE_INPUT_FRAME;
    }
  } else if (frame >= 150 && frame < CLICK_MODAL_SUBMIT_FRAME) {
    // 4. Moving to modal submit button "Gabung Kelas" (1099, 641)
    cursorX = interpolate(frame, [150, CLICK_MODAL_SUBMIT_FRAME - 2], [952, 1099], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [150, CLICK_MODAL_SUBMIT_FRAME - 2], [531, 641], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 1099;
    cursorY = 641;
    if (frame >= CLICK_MODAL_SUBMIT_FRAME && frame <= CLICK_MODAL_SUBMIT_FRAME + 5) {
      isClicking = true;
      clickFrame = CLICK_MODAL_SUBMIT_FRAME;
    }
  }

  // Code typing simulation in modal
  const codeStr = "IF204-A";
  const charsTyped = Math.floor(
    interpolate(frame, [126, 148], [0, codeStr.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentCode = codeStr.slice(0, charsTyped);

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* 
        AUTHENTIC ACTION-REACTION SCREEN FLOW:
        1. frames < 52: Mahasiswa Dashboard
        2. frames 52 to 100: Mahasiswa Course List
        3. frames 100+: Real Modal "Gabung Kelas Perkuliahan"
      */}
      {frame < LOAD_COURSE_PAGE_FRAME ? (
        <Img
          src={staticFile("screens/03_mahasiswa_dashboard.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : frame < LOAD_MODAL_FRAME ? (
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
          {/* Typed Code inside real modal input */}
          {frame >= 126 && (
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
              {frame < 150 && Math.floor(frame / 10) % 2 === 0 && (
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

      {/* Interactive Instruction Overlay: Varied positioning, edge-flushed shadow, dark font, no cards */}
      {frame < LOAD_COURSE_PAGE_FRAME ? (
        <InstructionOverlay
          step="02"
          actionText="Buka Menu Course"
          detailText="Klik menu Course pada bilah navigasi kiri untuk mengakses portal perkuliahan Anda"
          position="center-right"
        />
      ) : frame < LOAD_MODAL_FRAME ? (
        <InstructionOverlay
          step="02"
          actionText="Pilih Tambah Gabung Kelas"
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
