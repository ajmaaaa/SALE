import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 7 — Outro Cover & Closing CTA (Master Video 02)
 * Durasi: 177 frames (~5.90s) — Full sync dengan VO2 Seg 81 - 82
 *
 * Theme: Pure Brand Navy (#102f50)
 * Format: 3 Baris Bersih Tanpa Card Box (Sesuai Standar Video 00/01)
 * Baris 1: Judul Utama (font-extrabold)
 * Baris 2: Subjudul (font-normal kontras)
 * Baris 3: Nama Institusi (uppercase tracking-[0.25em])
 */
export const Scene7Outro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const getItemAnim = (startFrame: number, delay = 0) => {
    const rel = frame - (startFrame + delay);
    const opacity = interpolate(rel, [0, 8], [0, 1], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    const s = spring({ frame: rel, fps, config: { damping: 16, stiffness: 90 } });
    const scale = interpolate(s, [0, 1], [0.96, 1]);
    return { opacity, transform: `scale(${scale})` };
  };

  const fadeAnim = interpolate(frame, [0, 6], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily, opacity: fadeAnim }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#102f50] text-white"
    >
      <AcademicBackground theme="dark" />

      {/* 3 Baris Bersih Terpusat (Tanpa Card Pembungkus) */}
      <div className="relative z-10 flex-1 flex flex-col justify-center items-center px-24 max-w-[1720px] mx-auto w-full text-center my-auto">
        {/* SALE Logo Badge */}
        <div
          style={getItemAnim(0, 0)}
          className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-[#102f50] font-bold text-4xl shadow-2xl mb-8"
        >
          S
        </div>

        {/* Baris 1: Judul Utama (font-extrabold) */}
        <h1 className="text-[58px] font-extrabold tracking-tight text-white leading-tight whitespace-nowrap">
          <WordByWord
            text="Selamat Belajar dan Meraih Prestasi"
            startFrame={6}
            durationInFrames={55}
            highlightWords={["Belajar", "Prestasi"]}
          />
        </h1>

        {/* Baris 2: Subjudul (font-normal) */}
        <div className="mt-5 text-3xl font-normal text-slate-300 whitespace-nowrap">
          <WordByWord
            text="Wujudkan Kompetensi Unggul Bersama Ekosistem SALE"
            startFrame={62}
            durationInFrames={55}
            highlightWords={["Kompetensi", "SALE"]}
          />
        </div>

        {/* Baris 3: Nama Institusi */}
        <div
          style={getItemAnim(115, 0)}
          className="mt-8 text-base font-semibold text-slate-400 tracking-[0.25em] uppercase whitespace-nowrap"
        >
          Institut Teknologi Senggarang
        </div>
      </div>
    </div>
  );
};
