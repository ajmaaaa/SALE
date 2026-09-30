import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import {
  AnimatedBadge,
  FloatingBackground,
  fontFamily,
  SaleLogo,
} from "../../components/common";

export const Scene5Outro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({ frame: frame - 6, fps, config: { damping: 15 } });
  const titleY = interpolate(titleSpring, [0, 1], [40, 0]);
  const titleOpacity = interpolate(frame - 6, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const cardsSpring = spring({
    frame: frame - 20,
    fps,
    config: { damping: 14 },
  });
  const cardsScale = interpolate(cardsSpring, [0, 1], [0.94, 1]);
  const cardsOpacity = interpolate(frame - 20, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#102f50] p-16 text-white overflow-hidden select-none"
    >
      <FloatingBackground dark />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <AnimatedBadge text="PENUTUP PENGENALAN" dark delay={10} />
      </div>

      {/* Center Content */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-4xl mx-auto">
        <AnimatedBadge text="SIAP MEMULAI PERJALANAN" dark delay={0} />

        <h2
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="mt-6 text-6xl font-black tracking-tight text-white leading-tight"
        >
          Cerdas. Terukur. Terintegrasi.
        </h2>

        <p
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="mt-4 text-2xl font-normal text-slate-300 max-w-2xl leading-relaxed"
        >
          Mari jelajahi setiap fitur SALE secara mendalam pada episode panduan
          berikutnya sesuai peran Anda.
        </p>

        {/* 4 Guide Series Quick Nav */}
        <div
          style={{
            transform: `scale(${cardsScale})`,
            opacity: cardsOpacity,
          }}
          className="mt-12 grid grid-cols-4 gap-4 w-full"
        >
          {[
            {
              role: "Mahasiswa",
              count: "5 Video",
              desc: "Login, Course, Tugas, Kuis & AI",
              tag: "Grup 1",
            },
            {
              role: "Dosen",
              count: "7 Video",
              desc: "Course, Asesmen, Rubrik & Rekap",
              tag: "Grup 2",
            },
            {
              role: "Admin Prodi",
              count: "4 Video",
              desc: "Kurikulum, CPL/CPMK & Kelas",
              tag: "Grup 3",
            },
            {
              role: "Admin Sistem",
              count: "3 Video",
              desc: "Infrastruktur, AI & Keamanan",
              tag: "Grup 4",
            },
          ].map((item, idx) => (
            <div
              key={idx}
              className="flex flex-col items-center rounded-2xl bg-white/10 p-5 border border-white/15 backdrop-blur-md text-center shadow-lg"
            >
              <span className="rounded-md bg-white/15 px-2 py-0.5 text-[10px] font-bold text-cyan-300 uppercase">
                {item.tag}
              </span>
              <div className="mt-2 text-lg font-bold text-white">
                {item.role}
              </div>
              <div className="text-xs font-semibold text-slate-300">
                {item.count}
              </div>
              <div className="mt-2 text-[11px] text-slate-400 leading-snug">
                {item.desc}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Footer Info */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Smart Academic Learning Environment • 2026</span>
        <span>Lanjutkan ke Video 01: Panduan Mahasiswa</span>
      </div>
    </div>
  );
};
