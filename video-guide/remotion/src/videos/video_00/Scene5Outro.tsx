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
  const titleOpacity = interpolate(frame - 6, [0, 15], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const cardsSpring = spring({
    frame: frame - 20,
    fps,
    config: { damping: 14 },
  });
  const cardsScale = interpolate(cardsSpring, [0, 1], [0.94, 1]);
  const cardsOpacity = interpolate(frame - 20, [0, 15], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // End fade-out in final 20 frames (frame 649 to 669)
  const endFadeOpacity = interpolate(frame, [645, 665], [1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // Next video emphasis during final call-to-action (frame 550+)
  const isFinalCta = frame >= 540;

  return (
    <div
      style={{
        fontFamily,
        opacity: endFadeOpacity,
      }}
      className="relative flex h-full w-full flex-col justify-between bg-[#102f50] p-16 text-white overflow-hidden select-none"
    >
      <FloatingBackground dark />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <AnimatedBadge
          text="5 SERI MASTER VIDEO PANDUAN SALE"
          dark
          delay={5}
        />
      </div>

      {/* Center Content */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-5xl mx-auto">
        <AnimatedBadge text="EKSPLORASI FITUR SESUAI PERAN" dark delay={0} />

        <h2
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="mt-5 text-6xl font-black tracking-tight text-white leading-tight"
        >
          Unggul. Adaptif. Terukur.
        </h2>

        <p
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="mt-3 text-2xl font-normal text-slate-300 max-w-3xl leading-relaxed"
        >
          Mewujudkan mutu pendidikan tinggi berorientasi masa depan bersama
          ekosistem cerdas SALE.
        </p>

        {/* 4 Guide Series Quick Nav */}
        <div
          style={{
            transform: `scale(${cardsScale})`,
            opacity: cardsOpacity,
          }}
          className="mt-10 grid grid-cols-4 gap-5 w-full"
        >
          {[
            {
              role: "Mahasiswa",
              videoNum: "Video 02",
              desc: "Autentikasi, Kelas, Tugas, Coding AI & Nilai",
              tag: "PANDUAN 02",
              isNext: true,
            },
            {
              role: "Dosen",
              videoNum: "Video 03",
              desc: "Kelola Kelas, Materi, Asesmen CPMK & Rubrik",
              tag: "PANDUAN 03",
              isNext: false,
            },
            {
              role: "Admin Prodi",
              videoNum: "Video 04",
              desc: "Kurikulum, CPL-CPMK, Import & Laporan",
              tag: "PANDUAN 04",
              isNext: false,
            },
            {
              role: "Admin Sistem",
              videoNum: "Video 05",
              desc: "Infrastruktur, Kuota Token AI & Keamanan",
              tag: "PANDUAN 05",
              isNext: false,
            },
          ].map((item, idx) => {
            const isHighlight = isFinalCta && item.isNext;

            return (
              <div
                key={idx}
                className={`flex flex-col justify-between rounded-2xl p-5 text-left ${
                  isHighlight
                    ? "bg-cyan-500/20 border-2 border-cyan-400 ring-4 ring-cyan-400/20 shadow-xl shadow-cyan-900/40"
                    : "bg-white/10 border border-white/15 backdrop-blur-md"
                }`}
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span
                      className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                        isHighlight
                          ? "bg-cyan-400 text-[#102f50]"
                          : "bg-white/20 text-white"
                      }`}
                    >
                      {item.tag}
                    </span>
                    <span className="text-[11px] font-semibold text-cyan-300">
                      {item.videoNum}
                    </span>
                  </div>

                  <h3 className="mt-3 text-xl font-bold text-white">
                    {item.role}
                  </h3>
                  <p className="mt-1 text-xs text-slate-300 leading-relaxed">
                    {item.desc}
                  </p>
                </div>

                <div className="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px] font-semibold text-cyan-300">
                  <span>{item.isNext ? "Siap Ditonton →" : "Tersedia"}</span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Footer Info */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Ekosistem Pembelajaran Cerdas Berbasis Outcome Based Education</span>
        <span>Master Video 01 • Penutup & Ajakan Eksplorasi</span>
      </div>
    </div>
  );
};
