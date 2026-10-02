import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { fontFamily, SaleLogo } from "../../components/common";

export const Scene1Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({ frame: frame - 6, fps, config: { damping: 14 } });
  const titleY = interpolate(titleSpring, [0, 1], [30, 0]);
  const titleOpacity = interpolate(frame - 6, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const subSpring = spring({ frame: frame - 14, fps, config: { damping: 14 } });
  const subY = interpolate(subSpring, [0, 1], [24, 0]);
  const subOpacity = interpolate(frame - 14, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{
        fontFamily,
        background: "radial-gradient(ellipse at 50% 25%, #18477a 0%, #0d2847 50%, #061322 100%)",
      }}
      className="relative flex h-full w-full flex-col justify-between p-20 text-white overflow-hidden select-none"
    >
      {/* Rich Multi-point Luminous Glow (Deep royal navy depth, non-flat) */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background:
            "radial-gradient(ellipse 80% 50% at 50% 0%, rgba(37, 99, 235, 0.25) 0%, transparent 75%), radial-gradient(circle at 85% 85%, rgba(14, 165, 233, 0.12) 0%, transparent 50%), radial-gradient(circle at 15% 85%, rgba(30, 58, 138, 0.2) 0%, transparent 50%)",
        }}
      />

      {/* Crisp Architectural Grid Pattern */}
      <div
        className="absolute inset-0 opacity-[0.06] pointer-events-none"
        style={{
          backgroundImage:
            "linear-gradient(rgba(255, 255, 255, 0.25) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.25) 1px, transparent 1px)",
          backgroundSize: "60px 60px",
        }}
      />

      {/* Top Header: SALE Brand Logo */}
      <div className="relative z-10 flex items-center">
        <SaleLogo dark />
      </div>

      {/* Center Hero: Spacious, Minimal, Modern Tech Editorial */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-4xl mx-auto py-10">
        <div className="text-sm font-bold tracking-widest text-sky-400 uppercase">
          PANDUAN MAHASISWA / EPISODE 01
        </div>

        <h1
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="text-6xl font-bold tracking-tight text-white mt-6 leading-tight"
        >
          Masuk & Bergabung ke Kelas
        </h1>

        <p
          style={{
            transform: `translateY(${subY}px)`,
            opacity: subOpacity,
          }}
          className="mt-6 text-xl lg:text-2xl font-normal leading-relaxed text-slate-300 max-w-2xl"
        >
          Panduan resmi autentikasi akun mahasiswa dan aktivasi kelas perkuliahan baru pada portal akademik SALE
        </p>

        <div
          style={{
            transform: `translateY(${subY}px)`,
            opacity: subOpacity,
          }}
          className="mt-8 flex items-center gap-8 text-sm font-medium tracking-wide text-slate-400"
        >
          <span><span className="text-sky-500 font-bold">01</span> Login Akun</span>
          <span className="text-slate-700">/</span>
          <span><span className="text-sky-500 font-bold">02</span> Akses Portal</span>
          <span className="text-slate-700">/</span>
          <span><span className="text-sky-500 font-bold">03</span> Aktivasi Perkuliahan</span>
        </div>
      </div>

      {/* Bottom Footer: Simple, Clean System Info */}
      <div className="relative z-10 flex items-center justify-between text-base font-bold text-slate-300/80 border-t border-white/10 pt-6">
        <span>Smart Academic Learning Environment 2026</span>
        <span className="text-[#e8f1f8] font-extrabold tracking-wide">Institut Teknologi Senggarang</span>
      </div>
    </div>
  );
};
