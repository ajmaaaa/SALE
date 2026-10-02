import React from "react";
import { useCurrentFrame } from "remotion";

/**
 * AcademicBackground
 *
 * Strictly adheres to DESIGN_RULES.md official palette:
 * - dark: Pure Brand SALE Navy (#102f50 / #0b223a) — NO purple/indigo tint
 * - light: Soft Cream (#F5F3EE) with warm slate / navy accents
 *
 * 100% vector SVG, ZERO AI artifacts, ZERO system screenshots in background.
 */
export const AcademicBackground: React.FC<{
  theme?: "dark" | "light";
  showNetwork?: boolean;
}> = ({ theme = "dark", showNetwork = true }) => {
  const frame = useCurrentFrame();

  const floatY = Math.sin(frame / 60) * 10;
  const floatX = Math.cos(frame / 75) * 8;
  const rotate1 = (frame / 25) % 360;

  const isLight = theme === "light";

  return (
    <div
      className={`absolute inset-0 pointer-events-none overflow-hidden select-none ${
        isLight ? "bg-[#F5F3EE]" : "bg-[#102f50]"
      }`}
    >
      {/* Base Brand Lighting — Pure Navy on dark, Clean Cream on light */}
      <div
        className="absolute inset-0"
        style={{
          background: isLight
            ? "radial-gradient(ellipse 90% 70% at 50% 20%, #ffffff 0%, #F5F3EE 70%, #ede8de 100%)"
            : "radial-gradient(ellipse 90% 70% at 50% 20%, #17426f 0%, #102f50 65%, #0a2139 100%)",
        }}
      />

      {/* Subtle Coordinate Grid Lines */}
      <div
        className={`absolute inset-0 ${isLight ? "opacity-[0.04]" : "opacity-[0.035]"}`}
        style={{
          backgroundImage: isLight
            ? "linear-gradient(to right, #102f50 1px, transparent 1px), linear-gradient(to bottom, #102f50 1px, transparent 1px)"
            : "linear-gradient(to right, #ffffff 1px, transparent 1px), linear-gradient(to bottom, #ffffff 1px, transparent 1px)",
          backgroundSize: "60px 60px",
          backgroundPosition: `${floatX}px ${floatY}px`,
        }}
      />

      {/* Academic Vector 1: Tree Network (Top Right) */}
      <svg
        className={`absolute -top-10 -right-10 h-80 w-80 pointer-events-none ${
          isLight ? "text-[#102f50] opacity-[0.08]" : "text-[#e8f1f8] opacity-[0.14]"
        }`}
        style={{
          transform: `translate(${floatX}px, ${floatY}px) rotate(${rotate1 * 0.04}deg)`,
        }}
        viewBox="0 0 120 120"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.75"
        aria-hidden="true"
      >
        <circle cx="60" cy="22" r="10" />
        <circle cx="31" cy="64" r="10" />
        <circle cx="89" cy="64" r="10" />
        <circle cx="17" cy="101" r="8" />
        <circle cx="47" cy="101" r="8" />
        <circle cx="75" cy="101" r="8" />
        <circle cx="104" cy="101" r="8" />
        <path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19" />
      </svg>

      {/* Academic Vector 2: Connected Learning Graph (Left Center) */}
      {showNetwork && (
        <svg
          className={`absolute top-[35%] -left-12 h-96 w-96 pointer-events-none ${
            isLight ? "text-[#102f50] opacity-[0.07]" : "text-[#e8f1f8] opacity-[0.12]"
          }`}
          style={{
            transform: `translate(${-floatX}px, ${-floatY}px) rotate(${-rotate1 * 0.03}deg)`,
          }}
          viewBox="0 0 140 140"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.5"
          aria-hidden="true"
        >
          <circle cx="70" cy="70" r="14" />
          <circle cx="25" cy="35" r="9" />
          <circle cx="115" cy="30" r="9" />
          <circle cx="20" cy="105" r="9" />
          <circle cx="120" cy="110" r="9" />
          <path d="M33 41l28 22M107 36L79 63M28 98l33-21M112 103L81 77" />
          <circle cx="70" cy="70" r="28" strokeDasharray="3 4" opacity="0.4" />
        </svg>
      )}

      {/* Academic Vector 3: Academic Architecture Diagram (Bottom Right) */}
      <svg
        className={`absolute -bottom-10 right-[12%] h-64 w-64 pointer-events-none ${
          isLight ? "text-[#102f50] opacity-[0.06]" : "text-white opacity-[0.08]"
        }`}
        style={{
          transform: `translate(${floatX * 0.7}px, ${-floatY * 0.7}px)`,
        }}
        viewBox="0 0 120 120"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
        aria-hidden="true"
      >
        <rect x="15" y="20" width="34" height="22" rx="4" />
        <rect x="70" y="20" width="34" height="22" rx="4" />
        <rect x="43" y="79" width="34" height="22" rx="4" />
        <path d="M49 31h21M32 42v24h28v13M87 42v24H60" />
      </svg>
    </div>
  );
};
