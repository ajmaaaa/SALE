import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import {
  AnimatedBadge,
  FloatingBackground,
  fontFamily,
  SaleLogo,
} from "../../components/common";

export const Scene1Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Animations
  const titleY = interpolate(
    spring({ frame: frame - 6, fps, config: { damping: 15 } }),
    [0, 1],
    [40, 0]
  );
  const titleOpacity = interpolate(frame - 6, [0, 15], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const descY = interpolate(
    spring({ frame: frame - 18, fps, config: { damping: 15 } }),
    [0, 1],
    [30, 0]
  );
  const descOpacity = interpolate(frame - 18, [0, 15], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const cardsY = interpolate(
    spring({ frame: frame - 28, fps, config: { damping: 14 } }),
    [0, 1],
    [50, 0]
  );
  const cardsOpacity = interpolate(frame - 28, [0, 15], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const floatingCardY = Math.sin(frame / 40) * 8;

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#102f50] p-16 text-white overflow-hidden select-none"
    >
      <FloatingBackground dark />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <AnimatedBadge
          text="PANDUAN SISTEM RESMI"
          dark
          delay={10}
        />
      </div>

      {/* Center Content */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-5xl mx-auto">
        <AnimatedBadge
          text="SMART ACADEMIC LEARNING ENVIRONMENT"
          dark
          delay={0}
        />

        <h1
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="mt-6 text-6xl font-black tracking-tight text-white leading-tight"
        >
          Sistem Pembelajaran Cerdas <br />
          <span className="text-cyan-300">Berbasis OBE</span>
        </h1>

        <p
          style={{
            transform: `translateY(${descY}px)`,
            opacity: descOpacity,
          }}
          className="mt-6 text-2xl font-normal text-slate-300 max-w-3xl leading-relaxed"
        >
          Ekosistem akademik digital yang tidak hanya mencatat angka nilai,
          melainkan mengukur capaian kompetensi nyata mahasiswa secara objektif
          dan terstandar.
        </p>

        {/* Feature Highlights Pill Bar */}
        <div
          style={{
            transform: `translateY(${cardsY + floatingCardY}px)`,
            opacity: cardsOpacity,
          }}
          className="mt-12 flex flex-wrap justify-center gap-5"
        >
          {[
            { label: "Outcome-Based Education", icon: "🎯" },
            { label: "Bimbingan Coding AI Cerdas", icon: "⚡" },
            { label: "Kolaborasi 4 Peran Civitas", icon: "👥" },
            { label: "Analitik Akreditasi Otomatis", icon: "📊" },
          ].map((item, idx) => (
            <div
              key={idx}
              className="flex items-center gap-3 rounded-2xl bg-white/10 px-5 py-3 border border-white/15 backdrop-blur-md shadow-lg"
            >
              <span className="text-2xl">{item.icon}</span>
              <span className="text-base font-semibold text-white">
                {item.label}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Bottom Subtitle Indicator */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Kementerian Pendidikan Tinggi, Sains, dan Teknologi</span>
        <span>SALE Guide Series • Episode 00</span>
      </div>
    </div>
  );
};
