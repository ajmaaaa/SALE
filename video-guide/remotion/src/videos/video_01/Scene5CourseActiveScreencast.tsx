import React from "react";
import { Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from "remotion";

export const Scene5CourseActiveScreencast: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const cardSpring = spring({
    frame: frame - 10,
    fps,
    config: { damping: 14, stiffness: 100 },
  });
  const cardScale = interpolate(cardSpring, [0, 1], [0.85, 1]);
  const cardOpacity = interpolate(frame - 10, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div className="relative h-full w-full bg-[#f8fafc] overflow-hidden select-none">
      {/* Background Dashboard with Active Courses */}
      <div className="relative w-[1920px] h-[1080px]">
        <Img
          src={staticFile("screens/03_mahasiswa_dashboard.png")}
          style={{
            width: "100%",
            height: "100%",
            objectFit: "cover",
            filter: "blur(4px) brightness(0.92)",
          }}
        />
      </div>

      {/* Floating Success Modal */}
      <div className="absolute inset-0 flex items-center justify-center bg-black/20 z-50">
        <div
          style={{
            transform: `scale(${cardScale})`,
            opacity: cardOpacity,
          }}
          className="flex flex-col items-center text-center max-w-lg rounded-3xl bg-white p-10 shadow-2xl border border-slate-200"
        >
          <div className="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-4xl text-emerald-600 shadow-inner">
            ✓
          </div>

          <div className="mt-5 inline-block rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-bold text-emerald-700 uppercase tracking-widest">
            BERHASIL TERDAFTAR
          </div>

          <h2 className="mt-3 text-3xl font-black text-[#102f50]">
            Kelas Anda Telah Aktif!
          </h2>

          <p className="mt-3 text-sm leading-relaxed text-slate-600">
            Selamat belajar. Seluruh materi perkuliahan, kuis berbasis OBE, dan
            forum diskusi kelas sudah dapat Anda akses secara penuh.
          </p>

          <div className="mt-8 pt-6 border-t border-slate-100 w-full flex items-center justify-between text-xs text-slate-400 font-semibold">
            <span>Smart Academic Learning Environment</span>
            <span className="text-blue-800 font-bold">Lanjut ke Episode 02 →</span>
          </div>
        </div>
      </div>
    </div>
  );
};
