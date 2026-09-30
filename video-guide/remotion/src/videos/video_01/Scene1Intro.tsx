import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { FloatingBackground, fontFamily, SaleLogo } from "../../components/common";

export const Scene1Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({ frame: frame - 6, fps, config: { damping: 14 } });
  const titleY = interpolate(titleSpring, [0, 1], [28, 0]);
  const titleOpacity = interpolate(frame - 6, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const listSpring = spring({ frame: frame - 16, fps, config: { damping: 14 } });
  const listX = interpolate(listSpring, [0, 1], [30, 0]);
  const listOpacity = interpolate(frame - 16, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#102f50] p-16 text-white overflow-hidden select-none"
    >
      {/* Subtle Engineering Grid (No blur orbs) */}
      <FloatingBackground dark />

      {/* Top Header: Clean Brand Mark without corner clutter */}
      <div className="relative z-10 flex items-center">
        <SaleLogo dark />
      </div>

      {/* Main Asymmetrical Editorial Layout: 70px Bold Title on Left, Structured Steps on Right */}
      <div className="relative z-10 grid grid-cols-12 gap-16 items-center my-auto">
        {/* Left Column: Massive Bold Typography */}
        <div className="col-span-7">
          <div className="text-sm font-black tracking-widest text-[#e8f1f8] uppercase flex items-center gap-2">
            <span>PANDUAN MAHASISWA</span>
            <span className="text-slate-400 font-normal">/</span>
            <span>EPISODE 01</span>
          </div>

          <h1
            style={{
              transform: `translateY(${titleY}px)`,
              opacity: titleOpacity,
            }}
            className="text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.1] mt-4"
          >
            Cara Masuk & Bergabung <br />
            <span className="text-[#e8f1f8]">ke Kelas Perkuliahan</span>
          </h1>

          <p className="mt-6 text-2xl font-bold leading-relaxed text-slate-200 max-w-xl">
            Panduan resmi alur autentikasi akun mahasiswa, navigasi portal akademik, dan aktivasi kelas perkuliahan baru menggunakan sistem akademik SALE
          </p>
        </div>

        {/* Right Column: Structured Tutorial Roadmap (Pure Text, NO chips, NO dashes) */}
        <div
          className="col-span-5"
          style={{
            transform: `translateX(${listX}px)`,
            opacity: listOpacity,
          }}
        >
          <div className="space-y-7 border-l-2 border-white/20 pl-8">
            <div>
              <div className="text-xs font-black tracking-widest text-[#e8f1f8] uppercase">
                TAHAP 01
              </div>
              <div className="text-2xl font-black text-white mt-1">
                Login Akun Mahasiswa
              </div>
              <p className="text-base font-semibold text-slate-300 mt-1 leading-snug">
                Akses portal dan masukkan NIM serta kata sandi resmi
              </p>
            </div>

            <div>
              <div className="text-xs font-black tracking-widest text-[#e8f1f8] uppercase">
                TAHAP 02
              </div>
              <div className="text-2xl font-black text-white mt-1">
                Navigasi Menu Course
              </div>
              <p className="text-base font-semibold text-slate-300 mt-1 leading-snug">
                Buka daftar kelas dan masukkan kode akses dari dosen
              </p>
            </div>

            <div>
              <div className="text-xs font-black tracking-widest text-[#e8f1f8] uppercase">
                TAHAP 03
              </div>
              <div className="text-2xl font-black text-white mt-1">
                Aktivasi Kelas Semester
              </div>
              <p className="text-base font-semibold text-slate-300 mt-1 leading-snug">
                Ruang belajar aktif berisi materi kuliah dan forum diskusi
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Footer: Single Clean Prompt (No 4-corner clutter) */}
      <div className="relative z-10 flex items-center justify-between text-base font-bold text-slate-300 border-t border-white/10 pt-5">
        <span>Smart Academic Learning Environment 2026</span>
        <span className="text-white font-extrabold tracking-wide">Memulai Demonstrasi Layar Langsung &rarr;</span>
      </div>
    </div>
  );
};
