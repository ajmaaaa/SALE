import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene4JoinConfirmScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  const CLICK_CONFIRM_BTN_FRAME = 46;
  const LOAD_COURSE_DETAIL_FRAME = 54; // Instant Action-Reaction: Screen cuts directly into the opened course

  // Exact button coordinates measured from live DOM:
  // Button "Buka Course Saya": target center (1076, 564)
  let cursorX = 1099;
  let cursorY = 641;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < CLICK_CONFIRM_BTN_FRAME) {
    cursorX = interpolate(frame, [8, CLICK_CONFIRM_BTN_FRAME - 2], [1099, 1076], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [8, CLICK_CONFIRM_BTN_FRAME - 2], [641, 564], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_CONFIRM_BTN_FRAME && frame < LOAD_COURSE_DETAIL_FRAME + 4) {
    cursorX = 1076;
    cursorY = 564;
    if (frame >= CLICK_CONFIRM_BTN_FRAME && frame <= CLICK_CONFIRM_BTN_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_CONFIRM_BTN_FRAME;
    }
  } else {
    // Inside the opened course, cursor moves naturally towards the course title and forum
    cursorX = interpolate(frame, [LOAD_COURSE_DETAIL_FRAME + 4, LOAD_COURSE_DETAIL_FRAME + 30], [1076, 520], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_COURSE_DETAIL_FRAME + 4, LOAD_COURSE_DETAIL_FRAME + 30], [564, 155], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }

  const isCourseLoaded = frame >= LOAD_COURSE_DETAIL_FRAME;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* 
        AUTHENTIC ACTION-REACTION SCREEN SWAP:
        Before frame 54: Join Confirmation Screen
        After frame 54: Real Course Detail Screen (IF204-A Struktur Data)
      */}
      {!isCourseLoaded ? (
        <Img
          src={staticFile("screens/13_mahasiswa_join_confirm.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : (
        <Img
          src={staticFile("screens/15_mahasiswa_course_detail.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/* Interactive, Bold Instruction Overlay */}
      {!isCourseLoaded ? (
        <InstructionOverlay
          step="03"
          actionText="Konfirmasi Pendaftaran Kelas"
          detailText="Periksa informasi mata kuliah dan dosen pengampu pada layar konfirmasi, lalu klik Buka Course Saya"
        />
      ) : (
        <InstructionOverlay
          step="03"
          actionText="Kelas Berhasil Dibuka"
          detailText="Sistem langsung mengarahkan Anda ke ruang perkuliahan utama yang berisi materi dan forum diskusi"
        />
      )}
    </div>
  );
};
