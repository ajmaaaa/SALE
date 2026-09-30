import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { FloatingBackground, fontFamily, SaleLogo } from "../../components/common";

export const Scene1Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({ frame: frame - 6, fps, config: { damping: 14 } });
  const titleY = interpolate(titleSpring, [0, 1], [28, 0]);
  const titleOpacity = interpolate(frame - 6, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const subSpring = spring({ frame: frame - 16, fps, config: { damping: 14 } });
  const subY = interpolate(subSpring, [0, 1], [22, 0]);
  const subOpacity = interpolate(frame - 16, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#102f50] p-16 text-white overflow-hidden select-none"
    >
      {/* Subtle Engineering Grid (No blur orbs) */}
      <FloatingBackground dark />

      {/* Top Header Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <div className="text-base font-extrabold tracking-widest text-[#e8f1f8] uppercase">
          PANDUAN MAHASISWA — EPISODE 01
        </div>
      </div>

      {/* Center Bold Content (Follows DESIGN_RULES.md: #102f50, 68px bold heading, 26px description, NO chips, NO cards) */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-5xl mx-auto">
        <h1
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="text-7xl font-black tracking-tight text-white leading-[1.12]"
        >
          Cara Masuk & Bergabung <br />
          <span className="text-[#e8f1f8]">ke Kelas Perkuliahan</span>
        </h1>

        <p
          style={{
            transform: `translateY(${subY}px)`,
            opacity: subOpacity,
          }}
          className="mt-6 text-2xl font-bold leading-relaxed text-[#e8f1f8] max-w-3xl"
        >
          Panduan resmi alur login mahasiswa, navigasi portal akademik, dan aktivasi kelas perkuliahan baru menggunakan sistem akademik SALE
        </p>

        {/* Clean Text Separators (Pure text, NO badges, NO chips, NO dots) */}
        <div className="mt-8 flex items-center justify-center gap-6 text-base font-bold text-slate-300">
          <span>Autentikasi Akun Mahasiswa</span>
          <span className="text-slate-500 font-normal">—</span>
          <span>Navigasi Portal Akademik</span>
          <span className="text-slate-500 font-normal">—</span>
          <span>Aktivasi Kelas Perkuliahan</span>
        </div>
      </div>

      {/* Bottom Footer */}
      <div className="relative z-10 flex items-center justify-between text-base font-bold text-slate-300 border-t border-white/10 pt-5">
        <span>Smart Academic Learning Environment — Versi 2.6</span>
        <span className="text-white font-extrabold">Memulai Demonstrasi Layar Langsung →</span>
      </div>
    </div>
  );
};
