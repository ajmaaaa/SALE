import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene3DashboardScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Natural cursor motion to Course Nav (x: 124, y: 194)
  let cursorX = 820;
  let cursorY = 460;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 52) {
    cursorX = interpolate(frame, [10, 48], [820, 124], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [10, 48], [460, 194], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 124;
    cursorY = 194;
    if (frame >= 52 && frame <= 58) {
      isClicking = true;
      clickFrame = 52;
    }
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Full 1920x1080 Dashboard (No cropped edges) */}
      <Img
        src={staticFile("screens/03_mahasiswa_dashboard.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Subtle Hover Highlight on Course Nav Item */}
      {frame >= 42 && (
        <div
          style={{
            position: "absolute",
            left: 12,
            top: 174,
            width: 224,
            height: 40,
            backgroundColor: "rgba(16, 47, 80, 0.08)",
            boxShadow: isClicking
              ? "0 0 0 2px rgba(16, 47, 80, 0.3)"
              : "0 0 12px rgba(56, 189, 248, 0.2)",
            borderRadius: 8,
            pointerEvents: "none",
          }}
        />
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/* Clean text instruction (light background) */}
      <InstructionOverlay
        step="02"
        actionText="Navigasi ke Menu Course"
        detailText="Pilih menu Course pada bilah navigasi kiri untuk mengakses seluruh kelas aktif Anda."
        darkScreen={false}
      />
    </div>
  );
};
