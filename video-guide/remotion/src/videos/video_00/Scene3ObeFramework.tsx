import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { ShowcaseDisplay } from "../../components/ShowcaseDisplay";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 3 — Kerangka Outcome-Based Education (OBE)
 * Durasi: 1103 frames (~36.77s)
 *
 * Theme: Pure Brand Navy (#102f50)
 * Tipografi: Hierarki tajam (Heading font-extrabold vs Subjudul font-normal),
 * Angka ber-badge background, poin ringkas tanpa detail panjang, timing tersinkron VO.
 */
export const Scene3ObeFramework: React.FC = () => {
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
  // Seg 10-12 (0–655f): Penerapan Penuh Kerangka OBE & 4 Tahapan
  // Seg 13 (655–845f): Peta Capaian Kelas Dosen
  // Seg 14-15 (845–1103f): Radar Portofolio Mahasiswa
  const isPipeline = frame < 655;
  const isDosenMap = frame >= 655 && frame < 845;
  const isMahasiswaRadar = frame >= 845;

  // Phase fades
  const fadePipeline = interpolate(frame, [0, 8, 640, 655], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeDosenMap = interpolate(frame, [655, 665, 835, 845], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeRadar = interpolate(frame - 845, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#102f50] text-white"
    >
      <AcademicBackground theme="dark" />

      {/* === FASE 1: "Penerapan Penuh Kerangka OBE" — List Ber-Badge (0–340) === */}
      {isPipeline && (
        <div
          style={{ opacity: fadePipeline }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              STANDAR AKADEMIK OBE
            </div>
            <h1 className="text-[52px] font-extrabold tracking-tight text-white leading-tight">
              <WordByWord
                text="Penerapan Penuh Kerangka OBE"
                startFrame={10}
                durationInFrames={40}
                highlightWords={["OBE"]}
              />
            </h1>
            <div className="mt-4 text-2xl font-normal text-slate-300 leading-relaxed">
              <WordByWord
                text="Terhubung Langsung ke Target CPMK dan CPL Program Studi"
                startFrame={60}
                durationInFrames={45}
                highlightWords={["CPMK", "CPL"]}
              />
            </div>
          </div>

          <div className="col-span-7 grid grid-cols-2 gap-x-12 gap-y-10 pl-6">
            <div style={getItemAnim(250, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                01
              </span>
              <div className="text-2xl font-semibold text-slate-100">Materi Terpetakan</div>
            </div>

            <div style={getItemAnim(310, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                02
              </span>
              <div className="text-2xl font-semibold text-slate-100">Kuis Berkelanjutan</div>
            </div>

            <div style={getItemAnim(370, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                03
              </span>
              <div className="text-2xl font-semibold text-slate-100">Ujian &amp; Koding Otomatis</div>
            </div>

            <div style={getItemAnim(430, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                04
              </span>
              <div className="text-2xl font-semibold text-slate-100">Portofolio Capaian CPL</div>
            </div>
          </div>
        </div>
      )}

      {/* === FASE 2: Peta Capaian Kelas Dosen — Poin Kunci Ringkas (340–680) === */}
      {isDosenMap && (
        <div
          style={{ opacity: fadeDosenMap }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div
            style={getItemAnim(660, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/16_dosen_rekap_gradebook.png"
              theme="dark"
            />
          </div>

          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              MONITORING MUTU KELAS
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-white leading-tight">
              <WordByWord
                text="Peta Capaian Kelas"
                startFrame={665}
                durationInFrames={20}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-300 mt-3 leading-relaxed">
              <WordByWord
                text="Melihat Ketercapaian Kompetensi Kelas Secara Instan"
                startFrame={685}
                durationInFrames={35}
                highlightWords={["Kompetensi", "Instan"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(730, 0)} className="text-2xl font-semibold text-slate-100">
                Evaluasi Bebas Rekapitulasi Manual
              </div>

              <div style={getItemAnim(785, 0)} className="text-2xl font-semibold text-slate-100">
                Kesiapan Akreditasi Program Studi
              </div>
            </div>
          </div>
        </div>
      )}

      {/* === FASE 3: Radar Portofolio Mahasiswa — Poin Kunci Ringkas (680–1103) === */}
      {isMahasiswaRadar && (
        <div
          style={{ opacity: fadeRadar }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div
            style={getItemAnim(850, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/17_mahasiswa_transkrip_radar.png"
              theme="dark"
            />
          </div>

          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              TRANSPARANSI KEAHLIAN
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-white leading-tight">
              <WordByWord
                text="Transparansi Keahlian"
                startFrame={840}
                durationInFrames={20}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-300 mt-3 leading-relaxed">
              <WordByWord
                text="Visualisasi Kompetensi Riil Mahasiswa"
                startFrame={865}
                durationInFrames={35}
                highlightWords={["Kompetensi", "Riil"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(920, 0)} className="text-2xl font-semibold text-slate-100">
                Grafik Radar Ketercapaian
              </div>

              <div style={getItemAnim(980, 0)} className="text-2xl font-semibold text-slate-100">
                Portofolio Bukti Autentik Keahlian
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
