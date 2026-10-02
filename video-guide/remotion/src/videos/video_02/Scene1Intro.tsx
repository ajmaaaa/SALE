import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 1 — Intro & Roadmap Panduan Mahasiswa (Video 02)
 * Durasi: 1105 frames (~36.83s) — Full sync dengan VO2 Seg 00 - 06
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
  // Phase 1 (0–450f): Cover Pembuka 3 Baris Bersih (VO Seg 00–02)
  // Phase 2 (450–1105f): Roadmap 5 Bab Pembelajaran Ber-badge (VO Seg 03–06)
  const isPhase1 = frame < 450;
  const isPhase2 = frame >= 450;

  // Phase fades
  const p1Opacity = interpolate(frame, [0, 8, 435, 450], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const p2Opacity = interpolate(frame - 450, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#102f50] text-white"
    >
      <AcademicBackground theme="dark" />

      {/* === FASE 1: Cover Pembuka — 3 Baris Bersih Tanpa Card (0–450) === */}
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
              text="Panduan Lengkap Mahasiswa"
              startFrame={10}
              durationInFrames={40}
              highlightWords={["Mahasiswa"]}
            />
          </h1>

          {/* Baris 2: Subjudul Satu Baris (font-normal kontras) */}
          <div className="mt-5 text-3xl font-normal text-slate-300 whitespace-nowrap">
            <WordByWord
              text="Alur Pembelajaran Mandiri, Interaktif, dan Terukur"
              startFrame={60}
              durationInFrames={50}
              highlightWords={["Mandiri,", "Interaktif,", "Terukur"]}
            />
          </div>

          {/* Baris 3: Nama Institusi Satu Baris */}
          <div
            style={getItemAnim(120, 0)}
            className="mt-8 text-base font-semibold text-slate-400 tracking-[0.25em] uppercase whitespace-nowrap"
          >
            Institut Teknologi Senggarang
          </div>
        </div>
      )}

      {/* === FASE 2: Roadmap 5 Bab Pembelajaran — List Ber-Badge (450–1105) === */}
      {isPhase2 && (
        <div
          style={{ opacity: p2Opacity }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-slate-400 uppercase mb-3">
              ALUR AKADEMIK MAHASISWA
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight leading-tight text-white">
              <WordByWord
                text="Lima Tahapan Pembelajaran"
                startFrame={460}
                durationInFrames={35}
                highlightWords={["Tahapan"]}
              />
            </h2>
            <div className="text-2xl font-normal text-slate-300 mt-4 leading-relaxed">
              <WordByWord
                text="Menjelajahi Seluruh Fitur Perkuliahan Anda"
                startFrame={505}
                durationInFrames={40}
                highlightWords={["Fitur", "Perkuliahan"]}
              />
            </div>
          </div>

          <div className="col-span-7 flex flex-col gap-6 pl-8">
            <div style={getItemAnim(640, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                01
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Autentikasi Akun &amp; Bergabung ke Kelas
              </div>
            </div>

            <div style={getItemAnim(740, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                02
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Ruang Belajar &amp; Forum Diskusi Real-Time
              </div>
            </div>

            <div style={getItemAnim(810, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                03
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Pengerjaan Tugas Mandiri &amp; Ujian Berwaktu
              </div>
            </div>

            <div style={getItemAnim(895, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                04
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Praktikum Pemrograman &amp; Asisten Coding AI
              </div>
            </div>

            <div style={getItemAnim(975, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm shrink-0">
                05
              </span>
              <div className="text-2xl font-semibold text-slate-100">
                Transparansi Capaian Nilai, CPMK &amp; Profil
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
