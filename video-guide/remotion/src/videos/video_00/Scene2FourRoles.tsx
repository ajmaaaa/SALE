import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily } from "../../components/common";
import { AcademicBackground } from "../../components/AcademicBackground";
import { ShowcaseDisplay } from "../../components/ShowcaseDisplay";
import { WordByWord } from "../../components/WordByWord";

/**
 * Scene 2 — Arsitektur Empat Peran Civitas Akademika
 * Durasi: 1138 frames (~37.93s)
 *
 * Theme: Light Soft Cream (#F5F3EE)
 * Tipografi: Hierarki tajam (Heading font-extrabold vs Subjudul font-normal),
 * Angka ber-badge background, poin ringkas tanpa detail panjang, timing tersinkron VO.
 */
export const Scene2FourRoles: React.FC = () => {
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
  // Seg 04 (0–205f): Overview Alur Kerja
  // Seg 05 (205–360f): Admin Sistem
  // Seg 06 (360–550f): Admin Prodi
  // Seg 07 (550–765f): Dosen Pengampu
  // Seg 08-09 (765–1138f): Mahasiswa
  const isOverview = frame < 205;
  const isAdminSistem = frame >= 205 && frame < 360;
  const isAdminProdi = frame >= 360 && frame < 550;
  const isDosen = frame >= 550 && frame < 765;
  const isMahasiswa = frame >= 765;

  // Phase fades
  const overviewOpacity = interpolate(frame, [0, 8, 195, 205], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeAdminSistem = interpolate(frame, [205, 215, 345, 360], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeAdminProdi = interpolate(frame, [360, 370, 535, 550], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeDosen = interpolate(frame, [550, 560, 750, 765], [0, 1, 1, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeMahasiswa = interpolate(frame - 765, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full overflow-hidden select-none bg-[#F5F3EE] text-[#0f172a]"
    >
      <AcademicBackground theme="light" />

      {/* === FASE 1: "Satu Kesatuan Alur Kerja Harmonis" — List Ber-Badge (0–140) === */}
      {isOverview && (
        <div
          style={{ opacity: overviewOpacity }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              ARSITEKTUR MULTI-PERAN
            </div>
            <h1 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Satu Kesatuan Alur Kerja Harmonis"
                startFrame={15}
                durationInFrames={45}
                theme="light"
                highlightWords={["Harmonis"]}
              />
            </h1>
            <div className="mt-4 text-2xl font-normal text-slate-600 leading-relaxed">
              <WordByWord
                text="Menghubungkan Seluruh Pemangku Kepentingan Akademik"
                startFrame={65}
                durationInFrames={45}
                theme="light"
                highlightWords={["Pemangku", "Akademik"]}
              />
            </div>
          </div>

          <div className="col-span-7 grid grid-cols-2 gap-x-12 gap-y-10 pl-6">
            <div style={getItemAnim(20, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50] font-bold text-lg shadow-sm shrink-0">
                01
              </span>
              <div className="text-2xl font-semibold text-[#0f172a]">Admin Sistem</div>
            </div>

            <div style={getItemAnim(28, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50] font-bold text-lg shadow-sm shrink-0">
                02
              </span>
              <div className="text-2xl font-semibold text-[#0f172a]">Admin Program Studi</div>
            </div>

            <div style={getItemAnim(36, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50] font-bold text-lg shadow-sm shrink-0">
                03
              </span>
              <div className="text-2xl font-semibold text-[#0f172a]">Dosen Pengampu</div>
            </div>

            <div style={getItemAnim(44, 0)} className="flex items-center gap-5">
              <span className="flex items-center justify-center h-12 w-12 rounded-xl bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50] font-bold text-lg shadow-sm shrink-0">
                04
              </span>
              <div className="text-2xl font-semibold text-[#0f172a]">Mahasiswa</div>
            </div>
          </div>
        </div>
      )}

      {/* === FASE 2: Admin Sistem — Poin Kunci Ringkas (140–370) === */}
      {isAdminSistem && (
        <div
          style={{ opacity: fadeAdminSistem }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              INFRASTRUKTUR &amp; SERVER
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Admin Sistem"
                startFrame={215}
                durationInFrames={20}
                theme="light"
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Keandalan Infrastruktur dan Parameter Institusi"
                startFrame={235}
                durationInFrames={35}
                theme="light"
                highlightWords={["Keandalan", "Infrastruktur"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(275, 0)} className="text-2xl font-semibold text-slate-800">
                Ketersediaan Server &amp; Akses Pengguna
              </div>

              <div style={getItemAnim(315, 0)} className="text-2xl font-semibold text-slate-800">
                Tata Kelola Kuota Token AI
              </div>
            </div>
          </div>

          <div
            style={getItemAnim(210, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/12_admin_dashboard.png"
              theme="light"
            />
          </div>
        </div>
      )}

      {/* === FASE 3: Admin Prodi — Poin Kunci Ringkas (370–600) === */}
      {isAdminProdi && (
        <div
          style={{ opacity: fadeAdminProdi }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              STANDAR KURIKULUM OBE
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Admin Program Studi"
                startFrame={360}
                durationInFrames={25}
                theme="light"
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Merancang Struktur Kurikulum dan Penugasan Kelas"
                startFrame={385}
                durationInFrames={40}
                theme="light"
                highlightWords={["Kurikulum", "Penugasan"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(430, 0)} className="text-2xl font-semibold text-slate-800">
                Matriks Kurikulum CPL &amp; CPMK
              </div>

              <div style={getItemAnim(480, 0)} className="text-2xl font-semibold text-slate-800">
                Distribusi Kelas &amp; Dosen Pengampu
              </div>
            </div>
          </div>

          <div
            style={getItemAnim(375, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/09_adminprodi_dashboard.png"
              theme="light"
            />
          </div>
        </div>
      )}

      {/* === FASE 4: Dosen Pengampu — Poin Kunci Ringkas (600–850) === */}
      {isDosen && (
        <div
          style={{ opacity: fadeDosen }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              FASILITATOR &amp; ASESOR
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Dosen Pengampu"
                startFrame={560}
                durationInFrames={20}
                theme="light"
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Memfasilitasi Ruang Belajar dan Mengawal Capaian"
                startFrame={585}
                durationInFrames={35}
                theme="light"
                highlightWords={["Ruang", "Belajar", "Capaian"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(630, 0)} className="text-2xl font-semibold text-slate-800">
                Modul Perkuliahan &amp; Kuis Terjadwal
              </div>

              <div style={getItemAnim(690, 0)} className="text-2xl font-semibold text-slate-800">
                Rekapitulasi Ketercapaian Nilai
              </div>
            </div>
          </div>

          <div
            style={getItemAnim(555, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/05_dosen_dashboard.png"
              theme="light"
            />
          </div>
        </div>
      )}

      {/* === FASE 5: Mahasiswa — Poin Kunci Ringkas (850–1138) === */}
      {isMahasiswa && (
        <div
          style={{ opacity: fadeMahasiswa }}
          className="relative z-10 flex-1 grid grid-cols-12 gap-14 items-center px-24 max-w-[1720px] mx-auto w-full my-auto"
        >
          <div className="col-span-5 flex flex-col justify-center">
            <div className="text-xs font-bold tracking-[0.25em] text-[#102f50]/70 uppercase mb-3">
              PUSAT PEMBELAJARAN
            </div>
            <h2 className="text-[52px] font-extrabold tracking-tight text-[#0f172a] leading-tight">
              <WordByWord
                text="Mahasiswa"
                startFrame={775}
                durationInFrames={15}
                theme="light"
              />
            </h2>
            <div className="text-2xl font-normal text-slate-600 mt-3 leading-relaxed">
              <WordByWord
                text="Pusat dari Seluruh Proses Pembelajaran"
                startFrame={795}
                durationInFrames={35}
                theme="light"
                highlightWords={["Pusat", "Pembelajaran"]}
              />
            </div>

            <div className="mt-10 flex flex-col gap-5">
              <div style={getItemAnim(865, 0)} className="text-2xl font-semibold text-slate-800">
                Akses Materi &amp; Latihan Koding AI
              </div>

              <div style={getItemAnim(935, 0)} className="text-2xl font-semibold text-slate-800">
                Transparansi Radar Kompetensi
              </div>
            </div>
          </div>

          <div
            style={getItemAnim(770, 0)}
            className="col-span-7 flex justify-center"
          >
            <ShowcaseDisplay
              src="screens/03_mahasiswa_dashboard.png"
              theme="light"
            />
          </div>
        </div>
      )}
    </div>
  );
};
