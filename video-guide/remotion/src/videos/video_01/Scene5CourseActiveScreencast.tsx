import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene5CourseActiveScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Cursor moves naturally towards the active course card
  let cursorX = 960;
  let cursorY = 558;
  if (frame < 45) {
    cursorX = interpolate(frame, [5, 40], [960, 280], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [5, 40], [558, 440], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 280;
    cursorY = 440;
  }

  // Active focus glow on the enrolled course card (IF204-A Struktur Data)
  const cardHighlightOpacity = interpolate(frame, [30, 45], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Real Screen: Dashboard with Active Courses */}
      <Img
        src={staticFile("screens/03_mahasiswa_dashboard.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Subtle Focus Ring highlighting the newly joined course card */}
      {frame >= 30 && (
        <div
          style={{
            position: "absolute",
            left: 150,
            top: 350,
            width: 260,
            height: 290,
            borderRadius: 16,
            boxShadow: "0 0 0 3px rgba(37, 99, 235, 0.6), 0 10px 30px rgba(37, 99, 235, 0.15)",
            opacity: cardHighlightOpacity,
            pointerEvents: "none",
          }}
        />
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={false}
        clickFrame={-1}
      />

      {/* Clean text instruction (NO popup card) */}
      <InstructionOverlay
        step="04"
        actionText="Kelas Resmi Aktif & Siap Diakses"
        detailText="Selamat belajar. Seluruh materi perkuliahan, kuis berbasis OBE, dan forum diskusi kelas sudah aktif."
        darkScreen={false}
      />

      {/* Sleek next episode indicator in bottom right */}
      <div className="absolute bottom-10 right-12 flex items-center gap-3 text-xs font-bold text-slate-500 select-none">
        <span>SALE Guide Series</span>
        <span className="h-1 w-1 rounded-full bg-slate-400" />
        <span className="text-blue-700">Lanjut ke Episode 02 →</span>
      </div>
    </div>
  );
};
