import React from "react";
import { loadFont } from "@remotion/google-fonts/Inter";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";

export const { fontFamily } = loadFont("normal", {
  weights: ["400", "500", "600", "700", "800"],
  subsets: ["latin"],
});

export const SaleLogo: React.FC<{
  dark?: boolean;
}> = ({ dark = false }) => {
  return (
    <div className="flex items-center gap-3">
      <div
        className={`flex h-11 w-11 items-center justify-center rounded-xl font-extrabold text-xl shadow-md ${
          dark
            ? "bg-white text-[#102f50]"
            : "bg-[#102f50] text-white"
        }`}
      >
        S
      </div>
      <div>
        <div
          className={`font-black tracking-wider text-xl leading-none ${
            dark ? "text-white" : "text-[#102f50]"
          }`}
        >
          SALE
        </div>
        <div
          className={`text-[11px] font-semibold tracking-widest uppercase mt-0.5 ${
            dark ? "text-slate-300" : "text-slate-500"
          }`}
        >
          Smart Academic Learning Environment
        </div>
      </div>
    </div>
  );
};

export const AnimatedBadge: React.FC<{
  text: string;
  dark?: boolean;
  delay?: number;
}> = ({ text, dark = false, delay = 0 }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const scale = spring({
    frame: frame - delay,
    fps,
    config: { damping: 14, stiffness: 120 },
  });

  const opacity = interpolate(frame - delay, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{
        transform: `scale(${scale})`,
        opacity,
      }}
      className={`inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-bold tracking-widest uppercase shadow-xs ${
        dark
          ? "bg-white/10 text-blue-200 border border-white/20 backdrop-blur-md"
          : "bg-[#102f50]/10 text-[#102f50] border border-[#102f50]/15"
      }`}
    >
      <span
        className={`h-2 w-2 rounded-full ${
          dark ? "bg-cyan-400" : "bg-[#102f50]"
        }`}
      />
      {text}
    </div>
  );
};

export const FloatingBackground: React.FC<{
  dark?: boolean;
}> = ({ dark = false }) => {
  const frame = useCurrentFrame();

  const float1Y = Math.sin(frame / 45) * 15;
  const float1X = Math.cos(frame / 60) * 15;

  return (
    <div className="absolute inset-0 pointer-events-none overflow-hidden">
      {/* Subtle Technical Grid (Pure CSS, no blur orbs) */}
      <div
        className={`absolute inset-0 ${
          dark ? "opacity-[0.035]" : "opacity-[0.03]"
        }`}
        style={{
          backgroundImage: dark
            ? "radial-gradient(#ffffff 1px, transparent 1px)"
            : "radial-gradient(#102f50 1px, transparent 1px)",
          backgroundSize: "36px 36px",
          backgroundPosition: `${float1X}px ${float1Y}px`,
        }}
      />
    </div>
  );
};

export const BrowserWindow: React.FC<{
  url: string;
  badge?: string;
  dark?: boolean;
  children: React.ReactNode;
  className?: string;
  style?: React.CSSProperties;
}> = ({ url, badge, dark = true, children, className = "", style }) => {
  return (
    <div
      style={style}
      className={`relative rounded-2xl overflow-hidden shadow-2xl flex flex-col ${
        dark
          ? "bg-slate-900/95 border border-white/15 shadow-[0_25px_60px_rgba(0,0,0,0.65)]"
          : "bg-white border border-slate-300/80 shadow-[0_25px_60px_rgba(16,47,80,0.14)]"
      } ${className}`}
    >
      {/* Browser Bar */}
      <div
        className={`h-10 px-5 flex items-center justify-between border-b shrink-0 select-none ${
          dark
            ? "bg-slate-800/90 border-white/10"
            : "bg-slate-100/90 border-slate-200"
        }`}
      >
        <div className="flex items-center gap-2">
          <div className="w-3 h-3 rounded-full bg-[#f87171]" />
          <div className="w-3 h-3 rounded-full bg-[#fbbf24]" />
          <div className="w-3 h-3 rounded-full bg-[#34d399]" />
        </div>
        <div
          className={`text-xs font-mono font-medium px-4 py-1 rounded-md border truncate max-w-md ${
            dark
              ? "bg-slate-950/70 border-white/10 text-slate-300"
              : "bg-white border-slate-200 text-slate-600"
          }`}
        >
          {url}
        </div>
        {badge ? (
          <div
            className={`text-[11px] font-black tracking-wider uppercase ${
              dark ? "text-sky-300" : "text-[#102f50]"
            }`}
          >
            {badge}
          </div>
        ) : (
          <div className="w-12" />
        )}
      </div>

      {/* Screen Viewport */}
      <div className="relative flex-1 overflow-hidden w-full h-full bg-slate-950">
        {children}
      </div>
    </div>
  );
};

