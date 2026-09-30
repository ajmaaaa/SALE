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
    icon: "🤖",
    title: "Asisten AI Pemrograman",
    badge: "PEDAGOGICAL AI",
    desc: "AI tutor cerdas yang membimbing mahasiswa memahami error logika kode dan algoritma secara mandiri tanpa membocorkan contekan jawaban.",
    color: "from-cyan-600 to-blue-700",
    highlightRange: [132, 357],
  },
  {
    icon: "💬",
    title: "Forum Diskusi & Live Chat",
    badge: "REAL-TIME SYNC",
    desc: "Ruang interaksi dinamis layaknya peramban pesan modern untuk kolaborasi sekelas, bimbingan dosen, serta diskusi terarah.",
    color: "from-blue-600 to-indigo-700",
    highlightRange: [357, 483],
  },
  {
    icon: "⏱️",
    title: "Ruang Ujian Timer Pintar",
    badge: "SECURE TESTING",
    desc: "Pelaksanaan kuis dan ujian semester berwaktu otomatis dengan proteksi integritas akademik dan sinkronisasi status pengerjaan.",
    color: "from-emerald-600 to-teal-700",
    highlightRange: [483, 588],
  },
  {
    icon: "📑",
    title: "Ekspor Portofolio Nilai",
    badge: "ONE-CLICK AUDIT",
    desc: "Ekspor rekapitulasi nilai dan peta capaian CPMK dalam hitungan detik untuk kebutuhan evaluasi kurikulum dan akreditasi prodi.",
    color: "from-indigo-600 to-purple-700",
    highlightRange: [588, 756],
  },
];

export const Scene4ModernFeatures: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const headerOpacity = interpolate(frame, [0, 20], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const headerY = interpolate(
    spring({ frame, fps, config: { damping: 15 } }),
    [0, 1],
    [30, 0]
  );

  const isAnyFeatureFocused = frame >= 132;

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
            Fasilitas Cerdas
          </div>
          <div className="text-sm font-semibold text-[#102f50]">
            Teknologi Modern Terintegrasi
          </div>
        </div>
      </div>

      {/* Header */}
      <div
        style={{
          transform: `translateY(${headerY}px)`,
          opacity: headerOpacity,
        }}
        className="relative z-10 my-3 text-center max-w-4xl mx-auto"
      >
        <AnimatedBadge text="MELAMPAUI LMS KONVENSIONAL" delay={0} />
        <h2 className="mt-3 text-5xl font-black tracking-tight text-[#102f50]">
          Fitur Cerdas dan Kolaborasi Modern
        </h2>
        <p className="mt-2 text-lg text-slate-600">
          Dilengkapi fasilitas mutakhir untuk menunjang pengalaman belajar yang
          adaptif, interaktif, dan berintegritas tinggi.
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
          const baseScale = interpolate(featSpring, [0, 1], [0.92, 1]);
          const baseOpacity = interpolate(frame - delay, [0, 12], [0, 1], {
            extrapolateLeft: "clamp",
            extrapolateRight: "clamp",
          });

          // Focus highlight in sync with audio
          const isFocused =
            frame >= feat.highlightRange[0] && frame < feat.highlightRange[1];

          const targetOpacity = isAnyFeatureFocused
            ? isFocused
              ? 1
              : 0.5
            : 1;

          const activeScale = isFocused ? 1.03 : 1;
          const floatY = Math.sin((frame + idx * 25) / 45) * 4;

          return (
            <div
              key={idx}
              style={{
                transform: `scale(${baseScale * activeScale}) translateY(${floatY}px)`,
                opacity: baseOpacity * targetOpacity,
              }}
              className={`flex items-start gap-5 rounded-3xl bg-white p-7 shadow-xl shadow-slate-200/50 border ${
                isFocused
                  ? "border-blue-600 ring-4 ring-blue-500/20 shadow-blue-200/80"
                  : "border-slate-200/80"
              }`}
            >
              <div
                className={`flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-3xl shadow-inner border ${
                  isFocused
                    ? "bg-blue-600 text-white border-blue-700 shadow-blue-500/30"
                    : "bg-slate-100 text-slate-700 border-slate-200"
                }`}
              >
                {feat.icon}
              </div>

              <div className="flex-1">
                <span
                  className={`inline-block rounded-md px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase ${
                    isFocused
                      ? "bg-blue-100 text-blue-800"
                      : "bg-[#102f50]/10 text-[#102f50]"
                  }`}
                >
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
        <span>Pengalaman perkuliahan cerdas, transparan, dan berstandar internasional</span>
        <span>Master Video 01 • Bagian 04: Fitur Cerdas</span>
      </div>
    </div>
  );
};
