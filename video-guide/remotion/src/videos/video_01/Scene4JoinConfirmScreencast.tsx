import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene4JoinConfirmScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Exact button coordinates measured from live DOM:
  // x: 1003.8, y: 543.6, width: 145.3, height: 40
  // Target center: x = 1076, y = 564
  let cursorX = 960;
  let cursorY = 240;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 52) {
    cursorX = interpolate(frame, [10, 48], [960, 1076], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [10, 48], [240, 564], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 1076;
    cursorY = 564;
    if (frame >= 52 && frame <= 58) {
      isClicking = true;
      clickFrame = 52;
    }
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Full 1920x1080 Join Confirmation Screen */}
      <Img
        src={staticFile("screens/13_mahasiswa_join_confirm.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Button Hover Glow & Click Active State directly over the exact button */}
      {frame >= 42 && (
        <div
          style={{
            position: "absolute",
            left: 1004,
            top: 544,
            width: 145,
            height: 40,
            borderRadius: 8,
            boxShadow: isClicking
              ? "0 0 0 3px rgba(37, 99, 235, 0.5)"
              : "0 0 16px rgba(56, 189, 248, 0.4)",
            backgroundColor: isClicking
              ? "rgba(16, 47, 80, 0.2)"
              : "transparent",
            pointerEvents: "none",
          }}
        />
      )}

      {/* Animated Cursor hitting exact center (1076, 564) */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/* Clean text instruction */}
      <InstructionOverlay
        step="03"
        actionText="Konfirmasi Pendaftaran Kelas"
        detailText="Periksa informasi mata kuliah, semester, dan dosen pengampu, lalu klik tombol untuk membuka kelas."
        darkScreen={false}
      />
    </div>
  );
};
