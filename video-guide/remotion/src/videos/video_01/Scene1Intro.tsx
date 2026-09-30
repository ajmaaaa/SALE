import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import { FloatingBackground, fontFamily, SaleLogo } from "../../components/common";

export const Scene1Intro: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({ frame: frame - 6, fps, config: { damping: 15 } });
  const titleY = interpolate(titleSpring, [0, 1], [30, 0]);
  const titleOpacity = interpolate(frame - 6, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const subSpring = spring({ frame: frame - 18, fps, config: { damping: 15 } });
  const subY = interpolate(subSpring, [0, 1], [25, 0]);
  const subOpacity = interpolate(frame - 18, [0, 12], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{ fontFamily }}
      className="relative flex h-full w-full flex-col justify-between bg-[#102f50] p-16 text-white overflow-hidden select-none"
    >
      <FloatingBackground dark />

      {/* Top Bar */}
      <div className="relative z-10 flex items-center justify-between">
        <SaleLogo dark />
        <div className="text-right text-xs font-bold tracking-widest text-cyan-300 uppercase">
          PANDUAN MAHASISWA • EPS. 01
        </div>
      </div>

      {/* Center Content (Pure Clean Typography without bulky cards or neon badges) */}
      <div className="relative z-10 my-auto flex flex-col items-center text-center max-w-4xl mx-auto">
        <div className="text-xs font-bold tracking-widest text-slate-300 uppercase">
          LANGKAH AWAL PERKULIAHAN
        </div>

        <h1
          style={{
            transform: `translateY(${titleY}px)`,
            opacity: titleOpacity,
          }}
          className="mt-4 text-6xl font-black tracking-tight text-white leading-tight"
        >
          Cara Masuk & Bergabung <br />
          <span className="text-cyan-300">ke Kelas Perkuliahan</span>
        </h1>

        <p
          style={{
            transform: `translateY(${subY}px)`,
            opacity: subOpacity,
          }}
          className="mt-5 text-xl font-normal text-slate-300 max-w-2xl leading-relaxed"
        >
          Panduan mengakses portal akademik SALE, login dengan akun mahasiswa,
          dan mengaktifkan kelas perkuliahan baru.
        </p>
      </div>

      {/* Bottom Subtitle */}
      <div className="relative z-10 flex items-center justify-between text-xs text-slate-400 font-medium">
        <span>Smart Academic Learning Environment • 2026</span>
        <span>Memulai Demonstrasi Langsung →</span>
      </div>
    </div>
  );
};
