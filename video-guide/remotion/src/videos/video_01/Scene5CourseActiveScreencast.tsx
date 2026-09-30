import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene5CourseActiveScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Natural cursor motion inspecting the active course page (from header to video to forum)
  let cursorX = 520;
  let cursorY = 155;

  if (frame < 50) {
    cursorX = interpolate(frame, [5, 45], [520, 450], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [5, 45], [155, 520], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = interpolate(frame, [50, 90], [450, 850], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [50, 90], [520, 600], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Authentic Screen: Full Real Course Learning Page with Videos, Forums, and Modules */}
      <Img
        src={staticFile("screens/15_mahasiswa_course_detail.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={false}
        clickFrame={-1}
      />

      {/* Interactive, Bold Instruction Overlay */}
      <InstructionOverlay
        step="04"
        actionText="Kelas Perkuliahan Resmi Aktif"
        detailText="Selamat belajar! Seluruh silabus mata kuliah, materi video, kuis OBE, dan forum diskusi sudah siap Anda akses"
      />

      {/* Sleek next episode indicator in bottom right */}
      <div className="absolute bottom-10 right-14 flex items-center gap-3 text-xs font-black text-slate-100 select-none z-50 drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
        <span className="uppercase tracking-wider">SALE GUIDE SERIES</span>
        <span className="text-slate-400 font-bold">—</span>
        <span className="text-blue-300 uppercase tracking-wider font-extrabold">LANJUT KE EPISODE 02 — EKSPLORASI MATERI & FORUM →</span>
      </div>
    </div>
  );
};
