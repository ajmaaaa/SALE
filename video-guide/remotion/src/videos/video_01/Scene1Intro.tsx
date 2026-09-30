import React from "react";
import { Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily, SaleLogo } from "../../components/common";

export const Scene1Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({ frame: frame - 4, fps, config: { damping: 14 } });
  const titleY = interpolate(titleSpring, [0, 1], [24, 0]);
  const titleOpacity = interpolate(frame - 4, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const previewSpring = spring({ frame: frame - 10, fps, config: { damping: 15 } });
  const previewScale = interpolate(previewSpring, [0, 1], [0.94, 1]);
  const previewOpacity = interpolate(frame - 10, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#0b1626] p-16 text-white overflow-hidden select-none"
    >
      {/* Crisp Technical Grid (Anti-Slop: No glowing blur orbs) */}
      <div
        className="absolute inset-0 opacity-[0.035] pointer-events-none"
        style={{
          backgroundImage: "linear-gradient(#ffffff 1px, transparent 1px), linear-gradient(90deg, #ffffff 1px, transparent 1px)",
          backgroundSize: "40px 40px",
        }}
      />

      {/* Top Header */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <div className="flex items-center gap-3">
          <span className="px-3.5 py-1 rounded bg-blue-600/30 border border-blue-400/40 text-blue-200 text-xs font-black tracking-widest uppercase">
            PANDUAN MAHASISWA — EPISODE 01
          </span>
        </div>
      </div>

      {/* Main Content: Split Hero with Real System Preview Window */}
      <div className="relative z-10 grid grid-cols-12 gap-12 items-center my-auto">
        {/* Left Column: Bold Typography & Clear Instructions */}
        <div className="col-span-7">
          <div className="inline-flex items-center gap-2 text-xs font-extrabold tracking-wider text-blue-400 uppercase">
            <span className="h-2 w-2 rounded-full bg-blue-500" />
            LANGKAH AWAL MAHASISWA
          </div>

          <h1
            style={{
              transform: `translateY(${titleY}px)`,
              opacity: titleOpacity,
            }}
            className="mt-4 text-5xl font-black tracking-tight text-white leading-[1.15]"
          >
            Cara Masuk & Bergabung <br />
            <span className="text-blue-400">ke Kelas Perkuliahan</span>
          </h1>

          <p className="mt-6 text-xl font-bold leading-relaxed text-slate-300 max-w-xl">
            Panduan lengkap alur login akun mahasiswa, navigasi antarmuka portal SALE, dan aktivasi kelas perkuliahan baru menggunakan kode akses resmi.
          </p>

          {/* Clean Functional Badges */}
          <div className="mt-8 flex flex-wrap gap-3">
            <div className="px-3.5 py-2 rounded-md bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs font-bold flex items-center gap-2">
              <span className="text-emerald-400">✓</span> Autentikasi NIM
            </div>
            <div className="px-3.5 py-2 rounded-md bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs font-bold flex items-center gap-2">
              <span className="text-emerald-400">✓</span> Navigasi Portal
            </div>
            <div className="px-3.5 py-2 rounded-md bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs font-bold flex items-center gap-2">
              <span className="text-emerald-400">✓</span> Aktivasi Kelas Instan
            </div>
          </div>
        </div>

        {/* Right Column: Authentic Browser Mockup of SALE Portal (Real Screenshot) */}
        <div
          className="col-span-5"
          style={{
            transform: `scale(${previewScale})`,
            opacity: previewOpacity,
          }}
        >
          <div className="rounded-xl overflow-hidden shadow-2xl border border-slate-700/80 bg-slate-900">
            {/* Browser Header Bar */}
            <div className="bg-slate-950 px-4 py-2.5 flex items-center gap-3 border-b border-slate-800">
              <div className="flex gap-1.5">
                <div className="w-3 h-3 rounded-full bg-rose-500/80" />
                <div className="w-3 h-3 rounded-full bg-amber-500/80" />
                <div className="w-3 h-3 rounded-full bg-emerald-500/80" />
              </div>
              <div className="flex-1 text-center bg-slate-900 text-slate-400 text-[11px] font-mono py-1 rounded px-3 border border-slate-800 truncate">
                http://sale.campus.ac.id/login
              </div>
            </div>

            {/* Authentic Screenshot Preview */}
            <div className="relative aspect-[16/10] overflow-hidden bg-slate-950">
              <Img
                src={staticFile("screens/01_login_blank.png")}
                className="w-full h-full object-cover object-top"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Footer */}
      <div className="relative z-10 flex items-center justify-between text-xs font-semibold text-slate-400 border-t border-slate-800/80 pt-4">
        <span>Smart Academic Learning Environment — Versi 2.6</span>
        <span className="text-blue-400 font-bold">Memulai Demonstrasi Sistem Langsung →</span>
      </div>
    </div>
  );
};
