import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { ShowcaseDisplay } from "../../components/ShowcaseDisplay";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 1 — Pengenalan SALE / Intro & Visi
 * Durasi: 912 frames (~30.4s)
 *
 * Theme: Pure Brand Navy (#102f50)
 * Tipografi: Hierarki tajam (Heading font-extrabold vs Subjudul font-normal),
 * Angka ber-badge background, 3 baris cover bersih tanpa card, poin ringkas tanpa detail.
 */
export const Scene1Intro: React.FC = () => {
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

  // Phase conditions
  const isPhase1 = frame < 105;
  const isPhase2 = frame >= 105 && frame < 360;
  const isPhase3 = frame >= 360 && frame < 620;
  const isPhase4 = frame >= 620;

  // Phase fades
  const p1Opacity = interpolate(frame, [0, 8, 95, 105], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const p2Opacity = interpolate(frame, [105, 115, 345, 360], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const p3Opacity = interpolate(frame, [360, 372, 605, 620], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const p4Opacity = interpolate(frame - 620, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#102f50] text-white"
    >
      <AcademicBackground theme="dark" />

      {/* === FASE 1: Cover Pembuka — 3 Baris Bersih Tanpa Card (0–105) === */}
      {isPhase1 && (
        <div
          style={{ opacity: p1Opacity }}
          className="relative z-10 flex-1 flex flex-col justify-center items-center px-24 max-w-[1720px] mx-auto w-full text-center my-auto"
        >
          {/* SALE Logo Badge */}
          <div
            style={getItemAnim(0, 0)}
            className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-[#102f50] font-bold text-4xl shadow-2xl mb-8"
          >
            S
          </div>

          {/* Baris 1: Judul Utama Satu Baris (font-extrabold) */}
          <h1 className="text-[58px] font-extrabold tracking-tight text-white leading-tight whitespace-nowrap">
            <WordByWord
              text="Melampaui Presensi dan Angka"
              startFrame={6}
              durationInFrames={40}
              highlightWords={["Presensi", "Angka"]}
            />
          </h1>

          {/* Baris 2: Subjudul Satu Baris (font-normal kontras) */}
          <div className="mt-5 text-3xl font-normal text-slate-300 whitespace-nowrap">
            <WordByWord
              text="Membuktikan Ketercapaian Kompetensi Nyata Mahasiswa"
              startFrame={48}
              durationInFrames={45}
              highlightWords={["Kompetensi", "Nyata"]}
            />
          </div>

          {/* Baris 3: Nama Institusi Satu Baris */}
          <div
            style={getItemAnim(80, 0)}
            className="mt-8 text-base font-semibold text-slate-400 tracking-[0.25em] uppercase whitespace-nowrap"
          >
            Institut Teknologi Senggarang
          </div>
        </div>
      )}

      {/* === FASE 2: Bukan Sekadar Angka Nilai — List dengan Badge Angka (105–360) === */}
      {isPhase2 && (
        <div
          style={{ opacity: p2Opacity }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              PARADIGMA BARU
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight leading-tight text-white">
              <WordByWord
                text="Bukan Sekadar Angka Nilai"
                startFrame={110}
                durationInFrames={45}
                highlightWords={["Bukan", "Nilai"]}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-300 mt-4 leading-relaxed">
              <WordByWord
                text="Mengukur dan Membuktikan Kemampuan Nyata Mahasiswa"
                startFrame={160}
                durationInFrames={50}
                highlightWords={["Kemampuan", "Nyata"]}
              />
            </div>
          </div>

          <div className="col-span-7 flex flex-col gap-6 pl-8">
            <div style={getItemAnim(215, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                01
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Kompetensi Terukur per Mata Kuliah
              </div>
            </div>

            <div style={getItemAnim(245, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                02
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Portofolio Keahlian Nyata Mahasiswa
              </div>
            </div>

            <div style={getItemAnim(275, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                03
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Standar Mutu Berorientasi Global
              </div>
            </div>
          </div>
        </div>
      )}

      {/* === FASE 3: "Selamat Datang di SALE" — Poin Kunci Ringkas (360–620) === */}
      {isPhase3 && (
        <div
          style={{ opacity: p3Opacity }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div
            style={getItemAnim(365, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/03_mahasiswa_dashboard.png"
              theme="dark"
            />
          </div>

          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              PLATFORM CERDAS TERPADU
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-white leading-tight">
              <WordByWord
                text="Selamat Datang di SALE"
                startFrame={430}
                durationInFrames={35}
                highlightWords={["SALE"]}
              />
            </h2>

            <div className="text-2xl font-normal text-slate-300 mt-3 leading-snug">
              <WordByWord
                text="Smart Academic Learning Environment"
                startFrame={480}
                durationInFrames={40}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(520, 0)} className="text-2xl font-semibold text-slate-100">
                Kurikulum OBE Terpadu
              </div>

              <div style={getItemAnim(540, 0)} className="text-2xl font-semibold text-slate-100">
                Asisten AI Koding Mandiri
              </div>

              <div style={getItemAnim(560, 0)} className="text-2xl font-semibold text-slate-100">
                Transparansi Radar Capaian
              </div>
            </div>
          </div>
        </div>
      )}

      {/* === FASE 4: Tiga Pilar Mutu Ekosistem SALE — List Ber-Badge (620–912) === */}
      {isPhase4 && (
        <div
          style={{ opacity: p4Opacity }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              TATA KELOLA UNGGUL
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-white leading-tight">
              <WordByWord
                text="Tiga Pilar Mutu Ekosistem"
                startFrame={630}
                durationInFrames={40}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-300 mt-4 leading-relaxed">
              <WordByWord
                text="Internasional, Transparan, dan Terukur"
                startFrame={675}
                durationInFrames={35}
                highlightWords={["Internasional,", "Transparan,", "Terukur"]}
              />
            </div>
          </div>

          <div className="col-span-7 flex flex-col gap-6 pl-8">
            <div style={getItemAnim(710, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                01
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Kurikulum Berstandar Internasional
              </div>
            </div>

            <div style={getItemAnim(760, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                02
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Tata Kelola Akademik Transparan
              </div>
            </div>

            <div style={getItemAnim(815, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                03
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Pengukuran Terukur Komprehensif
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
