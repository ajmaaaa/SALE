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
    desc: "Mengelola server, pemantauan performa, alokasi token AI, pencadangan basis data, dan konfigurasi global sistem.",
    badge: "GLOBAL ADMIN",
    tags: ["Server Monitoring", "AI Token", "Database Backup"],
  },
  {
    role: "Admin Prodi",
    subtitle: "Penyusun Kurikulum OBE",
    color: "#1e3a8a",
    iconBg: "bg-blue-900 text-white",
    desc: "Menetapkan butir Capaian CPL, CPMK mata kuliah, struktur semester, pembagian kelas, serta data akademik dosen & mahasiswa.",
    badge: "OBE DESIGNER",
    tags: ["Standar CPL/CPMK", "Plotting Kelas", "Laporan Akreditasi"],
  },
  {
    role: "Dosen Pengampu",
    subtitle: "Fasilitator Pembelajaran",
    color: "#047857",
    iconBg: "bg-emerald-800 text-white",
    desc: "Mendesain materi, merancang kuis & tugas terpetakan CPMK, membimbing diskusi, dan menilai mahasiswa dengan rubrik objektif.",
    badge: "EDUCATOR",
    tags: ["Manajemen Course", "Rubrik Penilaian", "Rekap CPMK"],
  },
  {
    role: "Mahasiswa",
    subtitle: "Pusat Pembelajaran",
    color: "#4338ca",
    iconBg: "bg-indigo-700 text-white",
    desc: "Mengakses ruang belajar, mengerjakan tugas & ujian terstandar, berdiskusi realtime, serta berkonsultasi dengan asisten AI.",
    badge: "LEARNER",
    tags: ["Ruang Ujian Pintar", "AI Tutor", "Portofolio Capaian"],
  },
];

export const Scene2FourRoles: React.FC = () => {
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
          Sistem terpadu yang menyatukan seluruh aktor akademik dengan hak akses
          dan alur kerja yang dirancang khusus.
        </p>
      </div>

      {/* 4 Cards Grid */}
      <div className="relative z-10 grid grid-cols-4 gap-6 my-auto">
        {roles.map((item, idx) => {
          const delay = 12 + idx * 8;
          const cardSpring = spring({
            frame: frame - delay,
            fps,
            config: { damping: 14, stiffness: 100 },
          });
          const cardY = interpolate(cardSpring, [0, 1], [60, 0]);
          const cardOpacity = interpolate(frame - delay, [0, 15], [0, 1], {
            extrapolateLeft: "clamp",
            extrapolateRight: "clamp",
          });

          const floatY = Math.sin((frame + idx * 20) / 40) * 5;

          return (
            <div
              key={idx}
              style={{
                transform: `translateY(${cardY + floatY}px)`,
                opacity: cardOpacity,
              }}
              className="flex flex-col justify-between rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/50 border border-slate-200/80 transition-shadow"
            >
              <div>
                <div className="flex items-center justify-between">
                  <span
                    className={`inline-flex items-center justify-center rounded-xl px-2.5 py-1 text-[11px] font-bold tracking-wider uppercase ${item.iconBg}`}
                  >
                    {item.badge}
                  </span>
                  <span className="text-xs font-semibold text-slate-400">
                    Role 0{idx + 1}
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
                    className="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700"
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
        <span>Setiap peran memiliki dashboard & izin akses khusus</span>
        <span>Halaman 02 • Arsitektur Pengguna</span>
      </div>
    </div>
  );
};
