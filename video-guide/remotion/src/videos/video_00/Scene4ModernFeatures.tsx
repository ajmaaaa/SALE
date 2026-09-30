import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import {
  AnimatedBadge,
  FloatingBackground,
  fontFamily,
  SaleLogo,
} from "../../components/common";

const features = [
  {
    icon: "💬",
    title: "Forum Diskusi & Live Chat",
    badge: "REAL-TIME SYNC",
    desc: "Komunikasi interaktif antar mahasiswa dan dosen pengampu di dalam ruang kelas virtual tanpa perlu aplikasi luar.",
    color: "from-blue-600 to-indigo-700",
  },
  {
    icon: "🤖",
    title: "Asisten AI Pemrograman",
    badge: "POWERED BY GEMINI",
    desc: "AI tutor yang membimbing mahasiswa memahami error kode dan logika algoritma tanpa membocorkan jawaban mentah.",
    color: "from-cyan-600 to-blue-700",
  },
  {
    icon: "⏱️",
    title: "Ruang Ujian Timer Pintar",
    badge: "SECURE TESTING",
    desc: "Pelaksanaan kuis dan ujian berbasis batas waktu ketat, sinkronisasi otomatis, dan pencegahan kecurangan terpadu.",
    color: "from-emerald-600 to-teal-700",
  },
  {
    icon: "📑",
    title: "Rekapitulasi Nilai & Ekspor",
    badge: "INSTANT AUDIT",
    desc: "Dosen dan Kaprodi dapat mengunduh rekap nilai per CPMK dalam format Excel/PDF yang siap dijadikan lampiran akreditasi.",
    color: "from-indigo-600 to-purple-700",
  },
];

export const Scene4ModernFeatures: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const headerOpacity = interpolate(frame, [0, 15], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const headerY = interpolate(
    spring({ frame, fps, config: { damping: 15 } }),
    [0, 1],
    [30, 0]
  );

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#f8fafc] p-16 text-slate-900 overflow-hidden select-none"
    >
      <FloatingBackground />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo />
        <div className="text-right">
          <div className="text-xs font-bold uppercase tracking-wider text-slate-400">
            Fitur Unggulan
          </div>
          <div className="text-sm font-semibold text-[#102f50]">
            Teknologi Terkini
          </div>
        </div>
      </div>

      {/* Header */}
      <div
        style={{
          transform: `translateY(${headerY}px)`,
          opacity: headerOpacity,
        }}
        className="relative z-10 my-4 text-center max-w-4xl mx-auto"
      >
        <AnimatedBadge text="FITUR MODERN & REALTIME" delay={0} />
        <h2 className="mt-3 text-5xl font-black tracking-tight text-[#102f50]">
          Teknologi Cerdas untuk Kampus Masa Depan
        </h2>
        <p className="mt-2 text-lg text-slate-600">
          Dilengkapi fasilitas mutakhir untuk menunjang pengalaman belajar yang
          adaptif dan kolaboratif.
        </p>
      </div>

      {/* 4 Feature Cards Grid (2x2) */}
      <div className="relative z-10 grid grid-cols-2 gap-6 my-auto max-w-5xl mx-auto w-full">
        {features.map((feat, idx) => {
          const delay = 10 + idx * 8;
          const featSpring = spring({
            frame: frame - delay,
            fps,
            config: { damping: 14, stiffness: 100 },
          });
          const featScale = interpolate(featSpring, [0, 1], [0.92, 1]);
          const featOpacity = interpolate(frame - delay, [0, 12], [0, 1], {
            extrapolateLeft: "clamp",
            extrapolateRight: "clamp",
          });

          const floatY = Math.sin((frame + idx * 25) / 45) * 5;

          return (
            <div
              key={idx}
              style={{
                transform: `scale(${featScale}) translateY(${floatY}px)`,
                opacity: featOpacity,
              }}
              className="flex items-start gap-5 rounded-3xl bg-white p-7 shadow-xl shadow-slate-200/50 border border-slate-200/80"
            >
              <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-3xl shadow-inner border border-slate-200">
                {feat.icon}
              </div>

              <div className="flex-1">
                <span className="inline-block rounded-md bg-[#102f50]/10 px-2.5 py-0.5 text-[10px] font-bold text-[#102f50] tracking-wider uppercase">
                  {feat.badge}
                </span>
                <h3 className="mt-1 text-2xl font-black text-[#102f50] leading-snug">
                  {feat.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                  {feat.desc}
                </p>
              </div>
            </div>
          );
        })}
      </div>

      {/* Footer Info */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Pengalaman belajar yang fleksibel, aman, dan berstandar industri</span>
        <span>Halaman 04 • Fitur Unggulan</span>
      </div>
    </div>
  );
};
