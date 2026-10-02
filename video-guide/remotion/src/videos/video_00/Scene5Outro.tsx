import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 5 — Outro & Panduan Selanjutnya
 * Durasi: 669 frames (~22.3s)
 *
 * Theme: Pure Brand Navy (#102f50)
 * Tipografi: Hierarki tajam (Heading font-extrabold vs Subjudul font-normal),
 * Angka ber-badge background, penutup bersih tanpa card, timing tersinkron VO.
 */
export const Scene5Outro: React.FC = () => {
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

  // Phase conditions (Precisely matched to VO1 segments)
  // Seg 21-22 (0–305f): Seri Video Panduan Terstruktur
  // Seg 23-25 (305–669f): Closing Visi SALE & Final CTA
  const isFinalCta = frame >= 305;

  // Phase fades
  const fadeRoadmap = interpolate(frame, [0, 8, 290, 305], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeFinalCta = interpolate(frame - 305, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#102f50] text-white"
    >
      <AcademicBackground theme="dark" />

      {/* === FASE 1: "Seri Video Panduan Terstruktur" — List Ber-Badge (0–305) === */}
      {!isFinalCta && (
        <div
          style={{ opacity: fadeRoadmap }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              SERI PANDUAN SALE
            </div>
            <h1 className="text-[52px] font-extrabold tracking-tight text-white leading-tight">
              <WordByWord
                text="Seri Video Panduan Terstruktur"
                startFrame={15}
                durationInFrames={35}
                highlightWords={["Terstruktur"]}
              />
            </h1>
            <div className="mt-4 text-2xl font-normal text-slate-300 leading-relaxed">
              <WordByWord
                text="Menjelajahi Seluruh Fitur Sesuai Peran Anda"
                startFrame={55}
                durationInFrames={40}
                highlightWords={["Fitur", "Peran"]}
              />
            </div>
          </div>

          <div className="col-span-7 grid grid-cols-2 gap-x-12 gap-y-10 pl-6">
            <div style={getItemAnim(70, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                02
              </span>
              <div className="text-2xl font-semibold text-slate-100">Panduan Mahasiswa</div>
            </div>

            <div style={getItemAnim(110, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                03
              </span>
              <div className="text-2xl font-semibold text-slate-100">Panduan Dosen</div>
            </div>

            <div style={getItemAnim(150, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                04
              </span>
              <div className="text-2xl font-semibold text-slate-100">Panduan Admin Prodi</div>
            </div>

            <div style={getItemAnim(190, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                05
              </span>
              <div className="text-2xl font-semibold text-slate-100">Panduan Admin Sistem</div>
            </div>
          </div>
        </div>
      )}

      {/* === FASE 2: Final CTA — Bersih Tanpa Card, 3 Baris Rapi (305–669) === */}
      {isFinalCta && (
        <div
          style={{ opacity: fadeFinalCta }}
          className="relative z-10 flex-1 flex flex-col justify-center items-center px-24 max-w-[1720px] mx-auto w-full text-center my-auto"
        >
          {/* SALE Emblem */}
          <div
            style={getItemAnim(308, 0)}
            className="flex h-24 w-24 items-center justify-center rounded-2xl bg-white text-[#102f50] font-bold text-5xl shadow-2xl mb-8"
          >
            S
          </div>

          {/* Baris 1: Tagline Utama (font-extrabold) */}
          <h2 className="text-[60px] font-extrabold tracking-tight text-white leading-tight whitespace-nowrap">
            <WordByWord
              text="Cerdas, Terukur, dan Terintegrasi"
              startFrame={315}
              durationInFrames={35}
              highlightWords={["Cerdas,", "Terukur,", "Terintegrasi"]}
            />
          </h2>

          {/* Baris 2: Subjudul (font-normal kontras) */}
          <div className="mt-5 text-3xl font-normal text-slate-300 whitespace-nowrap">
            <WordByWord
              text="Mewujudkan Mutu Pendidikan Tinggi Unggul Berkelanjutan"
              startFrame={355}
              durationInFrames={40}
              highlightWords={["Unggul", "Berkelanjutan"]}
            />
          </div>

          {/* Baris 3: Institusi */}
          <div
            style={getItemAnim(400, 0)}
            className="mt-8 text-base font-semibold text-slate-400 tracking-[0.25em] uppercase whitespace-nowrap"
          >
            Institut Teknologi Senggarang
          </div>
        </div>
      )}
    </div>
  );
};
