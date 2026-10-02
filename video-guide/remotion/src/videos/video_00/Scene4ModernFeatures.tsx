import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { ShowcaseDisplay } from "../../components/ShowcaseDisplay";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 4 — Fitur Cerdas & Kolaborasi Modern
 * Durasi: 756 frames (~25.2s)
 *
 * Theme: Light Soft Cream (#F5F3EE)
 * Tipografi: Hierarki tajam (Heading font-extrabold vs Subjudul font-normal),
 * Poin ringkas tanpa detail panjang, timing tersinkron VO.
 */
export const Scene4ModernFeatures: React.FC = () => {
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

  // Active feature conditions (Precisely matched to VO1 segments)
  // Seg 16-17 (0–365f): Asisten AI Terintegrasi
  // Seg 18 (365–495f): Diskusi Real-Time
  // Seg 19 (495–600f): Ujian Berwaktu Cerdas
  // Seg 20 (600–756f): Ekspor Portofolio Nilai
  const isFeature1 = frame < 365;
  const isFeature2 = frame >= 365 && frame < 495;
  const isFeature3 = frame >= 495 && frame < 600;
  const isFeature4 = frame >= 600;

  // Feature fades
  const fade1 = interpolate(frame, [0, 8, 350, 365], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fade2 = interpolate(frame, [365, 375, 485, 495], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fade3 = interpolate(frame, [495, 505, 590, 600], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fade4 = interpolate(frame - 600, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#F5F3EE] text-[#0f172a]"
    >
      <AcademicBackground theme="light" />

      {/* === FITUR 1: Asisten AI Coding (0–190) === */}
      {isFeature1 && (
        <div
          style={{ opacity: fade1 }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              KECERDASAN BUATAN ASISTIF
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Asisten AI Terintegrasi"
                startFrame={145}
                durationInFrames={25}
                theme="light"
                highlightWords={["AI"]}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Bimbingan Logika dan Evaluasi Otomatis"
                startFrame={175}
                durationInFrames={35}
                theme="light"
                highlightWords={["Logika", "Evaluasi"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(230, 0)} className="text-2xl font-semibold text-slate-800">
                In-Browser Code Workbench
              </div>

              <div style={getItemAnim(280, 0)} className="text-2xl font-semibold text-slate-800">
                Automated Test Runner
              </div>
            </div>
          </div>

          <div
            style={getItemAnim(5, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/18_coding_ai_assistant.png"
              theme="light"
            />
          </div>
        </div>
      )}

      {/* === FITUR 2: Diskusi Real-Time (190–380) === */}
      {isFeature2 && (
        <div
          style={{ opacity: fade2 }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div
            style={getItemAnim(370, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/19_forum_diskusi_interaktif.png"
              theme="light"
            />
          </div>

          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              KOLABORASI INTERAKTIF
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Diskusi Kelas Real-Time"
                startFrame={375}
                durationInFrames={25}
                theme="light"
                highlightWords={["Real-Time"]}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Ruang Percakapan Terstruktur Dua Arah"
                startFrame={405}
                durationInFrames={30}
                theme="light"
                highlightWords={["Terstruktur", "Dua", "Arah"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(440, 0)} className="text-2xl font-semibold text-slate-800">
                Perbincangan Berorientasi Materi
              </div>

              <div style={getItemAnim(465, 0)} className="text-2xl font-semibold text-slate-800">
                Notifikasi Kelas Seketika
              </div>
            </div>
          </div>
        </div>
      )}

      {/* === FITUR 3: Ujian Berwaktu Timer Pintar (380–565) === */}
      {isFeature3 && (
        <div
          style={{ opacity: fade3 }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div
            style={getItemAnim(500, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/20_kuis_ujian_timer.png"
              theme="light"
            />
          </div>

          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              INTEGRITAS ASESMEN
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Ujian Berwaktu Cerdas"
                startFrame={505}
                durationInFrames={20}
                theme="light"
                highlightWords={["Cerdas"]}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Perlindungan Integritas dengan Smart Timer"
                startFrame={530}
                durationInFrames={25}
                theme="light"
                highlightWords={["Integritas", "Smart", "Timer"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(555, 0)} className="text-2xl font-semibold text-slate-800">
                Sinkronisasi Waktu Server
              </div>

              <div style={getItemAnim(575, 0)} className="text-2xl font-semibold text-slate-800">
                Penyimpanan &amp; Auto-Submit
              </div>
            </div>
          </div>
        </div>
      )}

      {/* === FITUR 4: Ekspor Portofolio Nilai (565–756) === */}
      {isFeature4 && (
        <div
          style={{ opacity: fade4 }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              AKUNTABILITAS &amp; MUTU
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Ekspor Portofolio Nilai"
                startFrame={610}
                durationInFrames={25}
                theme="light"
                highlightWords={["Portofolio"]}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Rekapitulasi Ketercapaian Mutu Satu Klik"
                startFrame={640}
                durationInFrames={25}
                theme="light"
                highlightWords={["Satu", "Klik"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(670, 0)} className="text-2xl font-semibold text-slate-800">
                Format Resmi Standar Akreditasi
              </div>

              <div style={getItemAnim(705, 0)} className="text-2xl font-semibold text-slate-800">
                Otomatisasi Laporan Mutu Institusi
              </div>
            </div>
          </div>

          <div
            style={getItemAnim(605, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/16_dosen_rekap_gradebook.png"
              theme="light"
            />
          </div>
        </div>
      )}
    </div>
  );
};
