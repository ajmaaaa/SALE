import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import {
  AnimatedBadge,
  FloatingBackground,
  fontFamily,
  SaleLogo,
} from "../../components/common";

export const Scene3ObeFramework: React.FC = () => {
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

  const pillars = [
    {
      step: "01",
      title: "Pemetaan Asesmen ke Target CPMK",
      desc: "Setiap butir materi, soal kuis, tugas mandiri, dan proyek coding dikaitkan secara presisi ke target CPMK spesifik.",
      highlight: "Asesmen Berorientasi Hasil",
    },
    {
      step: "02",
      title: "Kalkulasi Capaian Otomatis & Real Time",
      desc: "Sistem mengagregasikan nilai mahasiswa menjadi peta ketercapaian kompetensi kelas secara instan tanpa rekap manual.",
      highlight: "Mesin Analitik Cerdas",
    },
    {
      step: "03",
      title: "Transparansi Radar Kompetensi Mahasiswa",
      desc: "Mahasiswa memantau langsung radar capaian pribadi untuk mengetahui penguasaan keahlian nyata yang telah berhasil diraih.",
      highlight: "Transparansi Akademik",
    },
  ];

  // Active step in hierarchy flow (VO 75.4s - 89.2s -> local frames 230 - 645)
  const isFlowFocus = frame >= 230 && frame < 645;
  const activeFlowIndex =
    frame < 230
      ? -1
      : frame < 330
      ? 0
      : frame < 430
      ? 1
      : frame < 540
      ? 2
      : 3;

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-white p-16 text-slate-900 overflow-hidden select-none"
    >
      <FloatingBackground />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo />
        <AnimatedBadge text="PENJAMINAN MUTU AKADEMIK • OBE" delay={0} />
      </div>

      {/* Header */}
      <div
        style={{
          transform: `translateY(${headerY}px)`,
          opacity: headerOpacity,
        }}
        className="relative z-10 my-3 text-center max-w-4xl mx-auto"
      >
        <h2 className="text-5xl font-black tracking-tight text-[#102f50]">
          Penerapan Penuh Kerangka OBE
        </h2>
        <p className="mt-2 text-lg text-slate-600">
          Setiap materi perkuliahan, kuis, ujian, hingga baris kode pemrograman
          terhubung langsung ke target CPL dan CPMK institusi.
        </p>
      </div>

      {/* Visual OBE Flow & 3 Pillars */}
      <div className="relative z-10 grid grid-cols-12 gap-8 my-auto items-center">
        {/* Flow Diagram Box (Left, 5 cols) */}
        <div className="col-span-5 flex flex-col gap-3 rounded-3xl bg-[#102f50] p-8 text-white shadow-2xl">
          <div className="text-xs font-bold uppercase tracking-wider text-cyan-300">
            Alur Pengukuran Capaian
          </div>
          <div className="text-2xl font-black">Hierarki Mutu Kurikulum</div>

          <div className="mt-4 flex flex-col gap-3">
            {[
              {
                label: "CPL (Capaian Pembelajaran Lulusan)",
                sub: "Standar profil kompetensi lulusan program studi",
                color: "bg-blue-600",
              },
              {
                label: "CPMK (Capaian Mata Kuliah)",
                sub: "Target kompetensi spesifik yang diturunkan per semester",
                color: "bg-cyan-600",
              },
              {
                label: "Asesmen & Tantangan Coding",
                sub: "Tugas mandiri, kuis, ujian berwaktu, dan praktikum AI",
                color: "bg-emerald-600",
              },
              {
                label: "Portofolio & Laporan Capaian",
                sub: "Peta ketercapaian kelas dan grafik radar kompetensi diri",
                color: "bg-amber-600",
              },
            ].map((step, idx) => {
              const sSpring = spring({
                frame: frame - (10 + idx * 8),
                fps,
                config: { damping: 14 },
              });
              const sOpacity = interpolate(
                frame - (10 + idx * 8),
                [0, 10],
                [0, 1],
                { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
              );

              const isStepActive = isFlowFocus && activeFlowIndex === idx;

              return (
                <div
                  key={idx}
                  style={{
                    transform: `translateX(${interpolate(
                      sSpring,
                      [0, 1],
                      [-20, 0]
                    )}px) scale(${isStepActive ? 1.03 : 1})`,
                    opacity: isFlowFocus ? (isStepActive ? 1 : 0.6) : sOpacity,
                  }}
                  className={`flex items-center gap-3.5 rounded-xl p-3.5 border ${
                    isStepActive
                      ? "bg-white/20 border-cyan-400 ring-2 ring-cyan-400/40 shadow-lg"
                      : "bg-white/10 border-white/10 backdrop-blur-sm"
                  }`}
                >
                  <div
                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${step.color} text-xs font-black text-white shadow-sm`}
                  >
                    0{idx + 1}
                  </div>
                  <div>
                    <div className="text-sm font-bold text-white">
                      {step.label}
                    </div>
                    <div className="text-[11px] text-slate-300">{step.sub}</div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* 3 Pillars (Right, 7 cols) */}
        <div className="col-span-7 flex flex-col gap-4">
          {pillars.map((item, idx) => {
            const pSpring = spring({
              frame: frame - (20 + idx * 10),
              fps,
              config: { damping: 14 },
            });
            const pOpacity = interpolate(
              frame - (20 + idx * 10),
              [0, 10],
              [0, 1],
              { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
            );

            // Right side cards get highlighted in the second half of the scene (frame 645+)
            const isRightSideFocused = frame >= 645;

            return (
              <div
                key={idx}
                style={{
                  transform: `translateX(${interpolate(
                    pSpring,
                    [0, 1],
                    [30, 0]
                  )}px)`,
                  opacity: pOpacity,
                }}
                className={`flex items-start gap-5 rounded-2xl bg-slate-50 p-6 border shadow-sm ${
                  isRightSideFocused
                    ? "border-blue-300 ring-2 ring-blue-500/10 shadow-md bg-white"
                    : "border-slate-200"
                }`}
              >
                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#102f50] text-lg font-black text-white shadow-md">
                  {item.step}
                </div>
                <div>
                  <div className="inline-block rounded-md bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-900 uppercase tracking-wide">
                    {item.highlight}
                  </div>
                  <h3 className="mt-1 text-xl font-extrabold text-[#102f50]">
                    {item.title}
                  </h3>
                  <p className="mt-1 text-sm text-slate-600 leading-relaxed">
                    {item.desc}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Footer Info */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Kepatuhan terhadap standar akreditasi BAN-PT, LAM-INFOKOM, dan IABEE</span>
        <span>Master Video 01 • Bagian 03: Kerangka OBE</span>
      </div>
    </div>
  );
};
