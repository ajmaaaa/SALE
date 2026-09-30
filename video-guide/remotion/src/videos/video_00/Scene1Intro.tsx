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

  // Phase transition at frame 410 (~13.7s where VO pauses before "Selamat datang di SALE...")
  const phase1Opacity = interpolate(frame, [0, 20, 390, 420], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const phase1Y = interpolate(
    spring({ frame: frame - 10, fps, config: { damping: 15 } }),
    [0, 1],
    [30, 0]
  );

  const phase2Opacity = interpolate(frame, [410, 440], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const phase2Y = interpolate(
    spring({ frame: frame - 430, fps, config: { damping: 15 } }),
    [0, 1],
    [30, 0]
  );

  const floatingOffset = Math.sin(frame / 45) * 6;

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
          text="PANDUAN RESMI SISTEM SALE • MASTER 01"
          dark
          delay={5}
        />
      </div>

      {/* Phase 1: Tantangan & Visi Akademik (Frames 0 - 410 / VO 0s - 13.7s) */}
      {frame < 430 && (
        <div
          style={{
            transform: `translateY(${phase1Y + floatingOffset}px)`,
            opacity: phase1Opacity,
          }}
          className="relative z-10 my-auto flex flex-col items-center text-center max-w-5xl mx-auto"
        >
          <AnimatedBadge
            text="PARADIGMA PENDIDIKAN TINGGI MODERN"
            dark
            delay={5}
          />

          <h1 className="mt-5 text-6xl font-black tracking-tight text-white leading-tight">
            Melampaui Sekadar Presensi & <br />
            <span className="text-cyan-300">Angka Nilai Akhir</span>
          </h1>

          <p className="mt-5 text-2xl font-normal text-slate-200 max-w-3xl leading-relaxed">
            Mampu mengukur dan membuktikan ketercapaian kompetensi nyata
            setiap mahasiswa secara objektif, transparan, dan terstandar.
          </p>

          {/* Visual Paradigma Cards */}
          <div className="mt-10 grid grid-cols-2 gap-8 w-full max-w-4xl text-left">
            <div className="rounded-2xl bg-white/5 border border-white/10 p-6 backdrop-blur-md">
              <div className="flex items-center gap-3">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/20 text-rose-300 font-bold text-sm">
                  ✕
                </span>
                <span className="text-sm font-bold uppercase tracking-wider text-slate-400">
                  LMS Konvensional
                </span>
              </div>
              <p className="mt-3 text-sm text-slate-300 leading-relaxed">
                Hanya mencatat log kehadiran dan kalkulasi angka mentah tanpa
                pembuktian capaian kompetensi lulusan.
              </p>
            </div>

            <div className="rounded-2xl bg-cyan-950/40 border border-cyan-500/30 p-6 backdrop-blur-md shadow-lg shadow-cyan-950/50">
              <div className="flex items-center gap-3">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-400 text-[#102f50] font-black text-sm">
                  ✓
                </span>
                <span className="text-sm font-bold uppercase tracking-wider text-cyan-300">
                  Ekosistem Cerdas SALE
                </span>
              </div>
              <p className="mt-3 text-sm text-cyan-100/90 leading-relaxed">
                Mengintegrasikan seluruh aktivitas belajar langsung ke target
                CPMK dan CPL berstandar Outcome Based Education.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Phase 2: Pengenalan SALE (Frames 410 - 912 / VO 13.7s - 30.2s) */}
      {frame >= 400 && (
        <div
          style={{
            transform: `translateY(${phase2Y + floatingOffset}px)`,
            opacity: phase2Opacity,
          }}
          className="relative z-10 my-auto flex flex-col items-center text-center max-w-5xl mx-auto"
        >
          <AnimatedBadge
            text="SMART ACADEMIC LEARNING ENVIRONMENT"
            dark
            delay={0}
          />

          <h1 className="mt-6 text-6xl font-black tracking-tight text-white leading-tight">
            Sistem Pembelajaran Cerdas <br />
            <span className="text-cyan-300">Berbasis Kurikulum OBE</span>
          </h1>

          <p className="mt-5 text-2xl font-normal text-slate-200 max-w-3xl leading-relaxed">
            Platform pembelajaran akademik cerdas yang dirancang khusus untuk
            mewujudkan tata kelola perkuliahan berstandar internasional,
            transparan, dan terukur secara komprehensif.
          </p>

          {/* 4 Feature Badges */}
          <div className="mt-10 flex flex-wrap justify-center gap-4">
            {[
              { label: "Outcome-Based Education", icon: "🎯" },
              { label: "Asisten AI Pemrograman", icon: "⚡" },
              { label: "Kolaborasi 4 Peran Civitas", icon: "👥" },
              { label: "Portofolio Akreditasi Otomatis", icon: "📊" },
            ].map((item, idx) => {
              const pillSpring = spring({
                frame: frame - (460 + idx * 12),
                fps,
                config: { damping: 14 },
              });
              const pillScale = interpolate(pillSpring, [0, 1], [0.85, 1]);
              const pillOpacity = interpolate(
                frame - (460 + idx * 12),
                [0, 15],
                [0, 1],
                { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
              );

              return (
                <div
                  key={idx}
                  style={{
                    transform: `scale(${pillScale})`,
                    opacity: pillOpacity,
                  }}
                  className="flex items-center gap-3 rounded-2xl bg-white/10 px-5 py-3 border border-white/15 backdrop-blur-md shadow-lg"
                >
                  <span className="text-2xl">{item.icon}</span>
                  <span className="text-base font-semibold text-white">
                    {item.label}
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Bottom Subtitle Indicator */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Kementerian Pendidikan Tinggi, Sains, dan Teknologi</span>
        <span>SALE Guide Series • Episode 01: Pengenalan & Arsitektur</span>
      </div>
    </div>
  );
};
