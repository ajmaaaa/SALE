import React from "react";
import { interpolate, useCurrentFrame } from "remotion";

export interface CursorProps {
  x: number;
  y: number;
  isClicking?: boolean;
  clickFrame?: number;
}

export const AnimatedCursor: React.FC<CursorProps> = ({
  x,
  y,
  isClicking = false,
  clickFrame = 0,
}) => {
  const frame = useCurrentFrame();

  const framesSinceClick = frame - clickFrame;
  const showRipple = framesSinceClick >= 0 && framesSinceClick < 16;

  const rippleScale = interpolate(framesSinceClick, [0, 15], [0.3, 2.2], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const rippleOpacity = interpolate(framesSinceClick, [0, 15], [0.85, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const cursorScale = isClicking ? 0.82 : 1;

  return (
    <div
      style={{
        position: "absolute",
        left: x,
        top: y,
        transform: "translate(-2px, -2px)",
        pointerEvents: "none",
        zIndex: 9999,
      }}
    >
      {/* Click Ripple Circle */}
      {showRipple && (
        <div
          style={{
            position: "absolute",
            left: 0,
            top: 0,
            width: 40,
            height: 40,
            borderRadius: "50%",
            transform: `translate(-50%, -50%) scale(${rippleScale})`,
            opacity: rippleOpacity,
            backgroundColor: "rgba(56, 189, 248, 0.35)",
            border: "2.5px solid rgba(14, 165, 233, 0.95)",
          }}
        />
      )}

      {/* High-Resolution SVG Pointer Cursor */}
      <svg
        width="30"
        height="30"
        viewBox="0 0 24 24"
        fill="none"
        style={{
          transform: `scale(${cursorScale})`,
          transformOrigin: "top left",
          filter: "drop-shadow(0 4px 8px rgba(0, 0, 0, 0.55))",
        }}
      >
        <path
          d="M3 3L10.07 20.97L13.58 13.58L20.97 10.07L3 3Z"
          fill="#0f172a"
          stroke="#ffffff"
          strokeWidth="2"
          strokeLinejoin="round"
        />
      </svg>
    </div>
  );
};

/**
 * Text instruction overlay:
 * - Large, bold, interactive typography (anti-slop)
 * - Strict rule: NO dot (.) separator
 * - Crystal clear readability with bottom gradient backdrop
 */
export const InstructionOverlay: React.FC<{
  step: string;
  actionText: string;
  detailText?: string;
  darkScreen?: boolean;
}> = ({ step, actionText, detailText }) => {
  const frame = useCurrentFrame();

  const opacity = interpolate(frame, [0, 8], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const translateY = interpolate(frame, [0, 8], [10, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{
        transform: `translateY(${translateY}px)`,
        opacity,
      }}
      className="absolute bottom-0 left-0 right-0 pt-16 pb-8 px-14 bg-gradient-to-t from-slate-950/95 via-slate-950/80 to-transparent z-40 pointer-events-none select-none"
    >
      <div className="max-w-5xl">
        {/* Step Badge (No dot separator) */}
        <div className="inline-flex items-center gap-2.5 px-3.5 py-1 rounded bg-blue-600 text-white text-xs font-black tracking-widest uppercase shadow-sm">
          <span className="h-2 w-2 rounded-full bg-cyan-300" />
          LANGKAH {step}
        </div>

        {/* Action Title (Large bold font) */}
        <div className="mt-2 text-3xl font-black tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
          {actionText}
        </div>

        {/* Detailed Description (Clear, large, high-contrast bold font) */}
        {detailText && (
          <p className="mt-1.5 text-xl font-bold leading-snug text-slate-100 drop-shadow-[0_1px_3px_rgba(0,0,0,0.9)] max-w-4xl">
            {detailText}
          </p>
        )}
      </div>
    </div>
  );
};
