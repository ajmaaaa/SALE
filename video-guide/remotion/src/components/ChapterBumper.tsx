import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "./common";
import { AcademicBackground } from "./AcademicBackground";
import { WordByWord } from "./WordByWord";

export interface ChapterBumperProps {
  chapterNumber?: string;
  title: string;
  subtitle: string;
  highlightWords?: string[];
  durationInFrames: number;
}

/**
 * ChapterBumper — Slide Judul Transisi Bab (3-6 Detik)
 * Membuka setiap bab dengan tata letak bersih 3 baris konsisten dengan Cover & Outro.
 * Menggunakan latar Brand Navy (#102f50), logo emblem SALE, dan animasi WordByWord sinkron VO.
 */
export const ChapterBumper: React.FC<ChapterBumperProps> = ({
  chapterNumber,
  title,
  subtitle,
  highlightWords = [],
  durationInFrames,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Entrance spring animation
  const s = spring({
    frame,
    fps,
    config: { damping: 16, stiffness: 90 },
  });
  const scale = interpolate(s, [0, 1], [0.95, 1]);

  // Fade in at start (0-10 frames) and fade out at end (last 12 frames)
  const fadeIn = interpolate(frame, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeOut = interpolate(
    frame,
    [durationInFrames - 12, durationInFrames],
    [1, 0],
    {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    }
  );
  const opacity = fadeIn * fadeOut;

  return (
    <div
      style={{ fontFamily }}
      className="absolute inset-0 flex h-full w-full overflow-hidden select-none bg-[#102f50] text-white z-30"
    >
      <AcademicBackground theme="dark" />

      <div
        style={{ opacity, transform: `scale(${scale})` }}
        className="relative z-10 flex-1 flex flex-col justify-center items-center px-24 max-w-[1720px] mx-auto w-full text-center my-auto"
      >
        {/* SALE Logo Badge */}
        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-[#102f50] font-bold text-3xl shadow-2xl mb-6">
          S
        </div>


        {/* Baris 1: Judul Utama Bab (font-extrabold) */}
        <h2 className="text-[52px] font-extrabold tracking-tight text-white leading-tight max-w-5xl">
          <WordByWord
            text={title}
            startFrame={8}
            durationInFrames={Math.min(45, durationInFrames - 25)}
            highlightWords={highlightWords}
          />
        </h2>

        {/* Baris 2: Subjudul Bab (font-normal slate) */}
        <p className="mt-4 text-2xl font-normal text-slate-300 max-w-3xl leading-relaxed">
          {subtitle}
        </p>

        {/* Baris 3: Nama Institusi */}
        <div className="mt-8 text-xs font-semibold text-slate-400 tracking-[0.25em] uppercase">
          Institut Teknologi Senggarang
        </div>
      </div>
    </div>
  );
};
