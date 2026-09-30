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

  const headerOpacity = interpolate(frame, [0, 15], [0, 1], {
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
      title: "Pemetaan Asesmen ke CPMK",
      desc: "Setiap butir soal kuis, tugas, ujian, dan rubrik esai dikaitkan secara presisi ke sub-capaian CPMK tertentu.",
      highlight: "Asesmen Terarah",
    },
    {
      step: "02",
      title: "Perhitungan Capaian Otomatis",
      desc: "Sistem mengagregasikan nilai mahasiswa menjadi skor ketercapaian CPMK tanpa perlu rekap kalkulasi Excel manual.",
      highlight: "Real-time Math Engine",
    },
    {
      step: "03",
      title: "Laporan Akreditasi Siap Ekspor",
      desc: "Menghasilkan matriks portofolio kelas dan laporan ketercapaian CPL standar BAN-PT, LAM-INFOKOM, dan IABEE.",
      highlight: "Standar Akreditasi",
    },
  ];

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-white p-16 text-slate-900 overflow-hidden select-none"
    >
      <FloatingBackground />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo />
        <AnimatedBadge text="FRAMEWORK AKADEMIK OBE" delay={0} />
      </div>

      {/* Header */}
      <div
        style={{
          transform: `translateY(${headerY}px)`,
          opacity: headerOpacity,
        }}
        className="relative z-10 my-4 text-center max-w-4xl mx-auto"
      >
        <h2 className="text-5xl font-black tracking-tight text-[#102f50]">
          Bukan Sekadar LMS Biasa
        </h2>
        <p className="mt-2 text-lg text-slate-600">
          Setiap materi, tugas, dan kuis dipetakan langsung ke Capaian
          Pembelajaran Mata Kuliah (CPMK) dan Capaian Pembelajaran Lulusan (CPL).
        </p>
      </div>

      {/* Visual OBE Flow & 3 Pillars */}
      <div className="relative z-10 grid grid-cols-12 gap-8 my-auto items-center">
        {/* Flow Diagram Box (Left, 5 cols) */}
        <div className="col-span-5 flex flex-col gap-3 rounded-3xl bg-[#102f50] p-8 text-white shadow-2xl">
          <div className="text-xs font-bold uppercase tracking-wider text-cyan-300">
            Alur Pengukuran Capaian
          </div>
          <div className="text-2xl font-bold">Siklus Tertutup Kurikulum OBE</div>

          <div className="mt-4 flex flex-col gap-3">
            {[
              {
                label: "CPL (Capaian Pembelajaran Lulusan)",
                sub: "Standar profil kompetensi lulusan prodi",
                color: "bg-blue-600",
              },
              {
                label: "CPMK (Capaian Mata Kuliah)",
                sub: "Diturunkan ke target belajar tiap semester",
                color: "bg-cyan-600",
              },
              {
                label: "Asesmen & Rubrik Objektif",
                sub: "Kuis, tugas coding AI, esai & proyek",
                color: "bg-emerald-600",
              },
              {
                label: "Portofolio & Laporan Capaian",
                sub: "Evaluasi berbasis data untuk perbaikan mutu",
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

              return (
                <div
                  key={idx}
                  style={{
                    transform: `translateX(${interpolate(
                      sSpring,
                      [0, 1],
                      [-20, 0]
                    )}px)`,
                    opacity: sOpacity,
                  }}
                  className="flex items-center gap-3 rounded-xl bg-white/10 p-3.5 border border-white/10 backdrop-blur-sm"
                >
                  <div
                    className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${step.color} text-xs font-black text-white`}
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
                className="flex items-start gap-5 rounded-2xl bg-slate-50 p-6 border border-slate-200 shadow-sm"
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
        <span>Kepatuhan terhadap standar akreditasi pendidikan tinggi nasional</span>
        <span>Halaman 03 • OBE Standard</span>
      </div>
    </div>
  );
};
