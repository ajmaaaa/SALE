import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";

/**
 * ShowcaseDisplay
 *
 * Displays a rock-solid, crystal-clear 16:9 preview of the real SALE interface:
 * - Thick, substantial frame (border-[8px]) so it looks grounded and professional
 * - Exact 16:9 aspect ratio container matching the 1920x1080 screenshot perfectly
 * - PURE SCREENSHOT ONLY: NO text captions or title bars on the image
 * - Clean rounded corners and deep drop shadow
 */
export const ShowcaseDisplay: React.FC<{
  src: string;
  theme?: "dark" | "light";
  className?: string;
  style?: React.CSSProperties;
}> = ({
  src,
  theme = "dark",
  className = "",
  style,
}) => {
  const frame = useCurrentFrame();

  const fadeIn = interpolate(frame, [0, 8], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const isLight = theme === "light";

  return (
    <div
      style={{ opacity: fadeIn, ...style }}
      className={`relative w-full aspect-video rounded-3xl overflow-hidden shadow-[0_25px_60px_rgba(0,0,0,0.6)] ${
        isLight
          ? "border-[8px] border-slate-300 bg-slate-950 ring-1 ring-slate-400/60"
          : "border-[8px] border-slate-800 bg-slate-950 ring-1 ring-white/15"
      } ${className}`}
    >
      <Img
        src={staticFile(src)}
        className="w-full h-full object-cover object-top select-none pointer-events-none"
      />
    </div>
  );
};
