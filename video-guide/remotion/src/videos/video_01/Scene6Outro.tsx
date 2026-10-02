import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily, SaleLogo } from "../../components/common";

export const Scene6Outro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // ── Animasi utama ─────────────────────────────────────────────────────
  const titleSpring = spring({ frame: frame - 6, fps, config: { damping: 14 } });
  const titleY      = interpolate(titleSpring, [0, 1], [25, 0]);
  const titleOpacity = interpolate(frame - 6, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const subSpring   = spring({ frame: frame - 18, fps, config: { damping: 14 } });
  const subY        = interpolate(subSpring, [0, 1], [20, 0]);
  const subOpacity  = interpolate(frame - 18, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const cardSpring  = spring({ frame: frame - 32, fps, config: { damping: 12, stiffness: 90 } });
  const cardOpacity = interpolate(frame - 32, [0, 16], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const cardY       = interpolate(cardSpring, [0, 1], [18, 0]);

  // Grid animasi halus — bergerak mengambang
  const gridOffsetY = Math.sin(frame / 80) * 6;
  const gridOffsetX = Math.cos(frame / 100) * 6;

  return (
    <div
      style={{
        fontFamily,
        background: "radial-gradient(ellipse at 50% 25%, #18477a 0%, #0d2847 50%, #061322 100%)",
      }}
      className="relative flex h-full w-full flex-col justify-between p-20 text-white overflow-hidden select-none"
    >
      {/* Multi-point Luminous Glow */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background:
            "radial-gradient(ellipse 80% 50% at 50% 0%, rgba(37, 99, 235, 0.25) 0%, transparent 75%), radial-gradient(circle at 85% 85%, rgba(14, 165, 233, 0.12) 0%, transparent 50%), radial-gradient(circle at 15% 85%, rgba(30, 58, 138, 0.2) 0%, transparent 50%)",
        }}
      />

      {/* Crisp Architectural Grid — animasi mengambang perlahan (wajib per DESIGN_RULES § Animasi) */}
      <div
        className="absolute inset-0 opacity-[0.055] pointer-events-none"
        style={{
          backgroundImage:
            "linear-gradient(rgba(255, 255, 255, 0.25) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.25) 1px, transparent 1px)",
          backgroundSize: "60px 60px",
          backgroundPosition: `${gridOffsetX}px ${gridOffsetY}px`,
        }}
      />

      {/* Top Header: SALE Brand Logo */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <div className="text-sm font-bold tracking-widest text-slate-400 uppercase">
          Episode 01 / Selesai
        </div>
      </div>

      {/* Center Hero */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-5xl mx-auto">
        <div className="text-sm font-bold tracking-widest text-sky-400 uppercase mb-6">
          PANDUAN MAHASISWA / SELESAI
        </div>

        <h1
          style={{ transform: `translateY(${titleY}px)`, opacity: titleOpacity }}
          className="text-6xl font-bold tracking-tight text-white leading-tight"
        >
          Akun &amp; Kelas Perkuliahan Aktif
        </h1>

        <p
          style={{ transform: `translateY(${subY}px)`, opacity: subOpacity }}
          className="mt-6 text-2xl font-normal leading-relaxed text-slate-300 max-w-3xl"
        >
          Login berhasil, kode kelas telah terverifikasi, dan ruang perkuliahan digital Anda kini aktif dan siap digunakan.
        </p>

        {/* "Apa berikutnya?" Card */}
        <div
          style={{ transform: `translateY(${cardY}px)`, opacity: cardOpacity }}
          className="mt-12 w-full max-w-3xl"
        >
          <div className="text-xs font-black tracking-widest text-slate-500 uppercase mb-4 text-left">
            Selanjutnya dalam seri ini
          </div>
          <div className="grid grid-cols-3 gap-4">
            {[
              { num: "02", label: "Eksplorasi Materi", desc: "Silabus, video, forum diskusi" },
              { num: "03", label: "Tugas & Ujian", desc: "Upload tugas, kuis timer, ujian" },
              { num: "04", label: "Coding & AI Tutor", desc: "Editor in-browser, bimbingan AI" },
            ].map(({ num, label, desc }) => (
              <div
                key={num}
                className="flex flex-col gap-1 p-5 rounded-xl border border-white/10 bg-white/5 text-left"
              >
                <span className="text-xs font-black text-sky-400 tracking-widest">{num}</span>
                <span className="text-base font-bold text-white leading-tight">{label}</span>
                <span className="text-sm text-slate-400 leading-snug">{desc}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Bottom Footer */}
      <div className="relative z-10 flex items-center justify-between text-base font-bold text-slate-300/80 border-t border-white/10 pt-6">
        <span>Smart Academic Learning Environment 2026</span>
        <span className="text-[#e8f1f8] font-extrabold tracking-wide">Institut Teknologi Senggarang</span>
      </div>
    </div>
  );
};
