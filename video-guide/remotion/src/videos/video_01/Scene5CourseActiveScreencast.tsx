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

      {/* Cinematic Wide Layout (Left: Step & Title, Right: Description & Next Episode) */}
      <InstructionOverlay
        step="04"
        actionText="Kelas Perkuliahan Resmi Aktif"
        detailText="Selamat belajar! Silabus perkuliahan, video materi, dan forum diskusi kelas sudah siap digunakan"
        position="split-cinematic"
      />

      {/* Sleek Next Episode Cue without em-dash */}
      <div className="absolute top-8 right-14 flex items-center gap-2 text-sm font-black text-slate-200 select-none z-50 bg-[#102f50]/90 px-4 py-2 rounded-lg border border-white/10 shadow-lg">
        <span className="text-slate-400 uppercase text-xs">BERIKUTNYA</span>
        <span className="text-white">Episode 02: Eksplorasi Materi dan Forum &rarr;</span>
      </div>
    </div>
  );
};
