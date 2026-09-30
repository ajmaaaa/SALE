import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import {
  AnimatedBadge,
  FloatingBackground,
  fontFamily,
  SaleLogo,
} from "../../components/common";

const roles = [
  {
    role: "Admin Sistem",
    subtitle: "Infrastruktur & Keamanan",
    color: "#0f172a",
    iconBg: "bg-slate-900 text-white",
    desc: "Menjaga keandalan server institusi, parameter sistem global, alokasi kuota token AI, serta integritas pencadangan basis data.",
    badge: "GLOBAL ADMIN",
    tags: ["Server Monitoring", "AI Token Quota", "Database Backup"],
    highlightFrame: [205, 385],
  },
  {
    role: "Admin Prodi",
    subtitle: "Penyusun Kurikulum OBE",
    color: "#1e3a8a",
    iconBg: "bg-blue-900 text-white",
    desc: "Merancang struktur kurikulum, merumuskan butir CPL, memetakan matriks CPMK mata kuliah, serta distribusi kelas paralel dan dosen.",
    badge: "OBE DESIGNER",
    tags: ["Standar CPL/CPMK", "Plotting Kelas", "Laporan Mutu"],
    highlightFrame: [385, 565],
  },
  {
    role: "Dosen Pengampu",
    subtitle: "Fasilitator Pembelajaran",
    color: "#047857",
    iconBg: "bg-emerald-800 text-white",
    desc: "Memfasilitasi ruang belajar, menyusun asesmen bermutu berbasis CPMK, mengoreksi tugas in-browser, dan mengawal progres kelas.",
    badge: "EDUCATOR",
    tags: ["Manajemen Course", "Rubrik Penilaian", "Rekap CPMK"],
    highlightFrame: [565, 775],
  },
  {
    role: "Mahasiswa",
    subtitle: "Pusat Pembelajaran",
    color: "#4338ca",
    iconBg: "bg-indigo-700 text-white",
    desc: "Mengakses materi berkualitas, mengerjakan tugas & kuis interaktif, praktikum coding dengan asisten AI, dan memantau capaian diri.",
    badge: "LEARNER",
    tags: ["Ruang Ujian Pintar", "AI Tutor Coding", "Portofolio Radar"],
    highlightFrame: [775, 1050],
  },
];

export const Scene2FourRoles: React.FC = () => {
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

  // Check if any role is currently in focused speech
  const isAnyRoleFocused = frame >= 205 && frame < 1050;

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
            Arsitektur Pengguna
          </div>
          <div className="text-sm font-semibold text-[#102f50]">
            Kolaborasi Civitas Terpadu
          </div>
        </div>
      </div>

      {/* Header Section */}
      <div
        style={{
          transform: `translateY(${headerY}px)`,
          opacity: headerOpacity,
        }}
        className="relative z-10 my-4 text-center max-w-4xl mx-auto"
      >
        <AnimatedBadge text="KOLABORASI CIVITAS AKADEMIKA" delay={0} />
        <h2 className="mt-3 text-5xl font-black tracking-tight text-[#102f50]">
          Empat Peran dalam Satu Ekosistem
        </h2>
        <p className="mt-2 text-lg text-slate-600">
          SALE menghubungkan seluruh pemangku kepentingan akademik dalam satu
          kesatuan alur kerja yang harmonis dan terstruktur.
        </p>
      </div>

      {/* 4 Cards Grid */}
      <div className="relative z-10 grid grid-cols-4 gap-6 my-auto">
        {roles.map((item, idx) => {
          const entryDelay = 12 + idx * 8;
          const cardSpring = spring({
            frame: frame - entryDelay,
            fps,
            config: { damping: 14, stiffness: 100 },
          });
          const cardY = interpolate(cardSpring, [0, 1], [60, 0]);
          const baseOpacity = interpolate(frame - entryDelay, [0, 15], [0, 1], {
            extrapolateLeft: "clamp",
            extrapolateRight: "clamp",
          });

          // Dynamic focus highlight based on voiceover timestamp
          const isFocused =
            frame >= item.highlightFrame[0] && frame < item.highlightFrame[1];
          const targetOpacity = isAnyRoleFocused
            ? isFocused
              ? 1
              : 0.45
            : 1;

          const cardScale = isFocused ? 1.035 : 1;
          const floatY = Math.sin((frame + idx * 20) / 45) * 4;

          return (
            <div
              key={idx}
              style={{
                transform: `translateY(${cardY + floatY}px) scale(${cardScale})`,
                opacity: baseOpacity * targetOpacity,
              }}
              className={`flex flex-col justify-between rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/50 border ${
                isFocused
                  ? "border-blue-600 ring-4 ring-blue-500/20 shadow-blue-200/80"
                  : "border-slate-200/80"
              }`}
            >
              <div>
                <div className="flex items-center justify-between">
                  <span
                    className={`inline-flex items-center justify-center rounded-xl px-2.5 py-1 text-[11px] font-bold tracking-wider uppercase ${item.iconBg}`}
                  >
                    {item.badge}
                  </span>
                  <span
                    className={`text-xs font-semibold ${
                      isFocused ? "text-blue-700 font-bold" : "text-slate-400"
                    }`}
                  >
                    Peran 0{idx + 1}
                  </span>
                </div>

                <h3 className="mt-4 text-2xl font-black text-[#102f50] leading-tight">
                  {item.role}
                </h3>
                <div className="text-xs font-semibold text-blue-800 mt-0.5">
                  {item.subtitle}
                </div>

                <p className="mt-3 text-xs leading-relaxed text-slate-600">
                  {item.desc}
                </p>
              </div>

              <div className="mt-5 pt-4 border-t border-slate-100 flex flex-wrap gap-1.5">
                {item.tags.map((tag, tIdx) => (
                  <span
                    key={tIdx}
                    className={`rounded-md px-2 py-0.5 text-[10px] font-medium ${
                      isFocused
                        ? "bg-blue-50 text-blue-700 font-semibold"
                        : "bg-slate-100 text-slate-700"
                    }`}
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          );
        })}
      </div>

      {/* Footer Info */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Setiap peran memiliki hak akses terdedikasi sesuai tupoksi akademik</span>
        <span>Master Video 01 • Bagian 02: Arsitektur 4 Peran</span>
      </div>
    </div>
  );
};
