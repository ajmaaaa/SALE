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
  clickFrame = -1,
}) => {
  const frame = useCurrentFrame();

  // Strict ripple condition: Only trigger if clickFrame is valid (>= 0) and current frame is within ripple window
  const showRipple =
    clickFrame >= 0 &&
    frame >= clickFrame &&
    frame - clickFrame >= 0 &&
    frame - clickFrame < 16;

  const framesSinceClick = showRipple ? frame - clickFrame : 0;

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

export type OverlayPosition =
  | "bottom-left"
  | "bottom-right"
  | "bottom-center"
  | "split-cinematic"
  | "top-right";

/**
 * Text instruction overlay:
 * - Dynamic positioning (breaks monotony, balances screencast focus)
 * - Large, bold, interactive typography (anti-slop)
 * - Strict rule: NO dot (.) separator, NO em-dash (—) ornament
 * - Pure typography without artificial badges or cards
 */
export const InstructionOverlay: React.FC<{
  step: string;
  actionText: string;
  detailText?: string;
  position?: OverlayPosition;
}> = ({ step, actionText, detailText, position = "bottom-left" }) => {
  const frame = useCurrentFrame();

  const opacity = interpolate(frame, [0, 8], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const translateY = interpolate(frame, [0, 8], [10, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  if (position === "bottom-right") {
    return (
      <div
        style={{ transform: `translateY(${translateY}px)`, opacity }}
        className="absolute bottom-0 left-0 right-0 pt-16 pb-8 px-16 bg-gradient-to-t from-slate-950/95 via-slate-950/75 to-transparent z-40 pointer-events-none select-none flex justify-end"
      >
        <div className="max-w-4xl text-right">
          <div className="text-base font-black tracking-widest text-[#e8f1f8] uppercase">
            LANGKAH {step}
          </div>
          <div className="mt-1 text-3xl font-black tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
            {actionText}
          </div>
          {detailText && (
            <p className="mt-1.5 text-xl font-bold leading-snug text-slate-100 max-w-3xl drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)] ml-auto">
              {detailText}
            </p>
          )}
        </div>
      </div>
    );
  }

  if (position === "bottom-center") {
    return (
      <div
        style={{ transform: `translateY(${translateY}px)`, opacity }}
        className="absolute bottom-0 left-0 right-0 pt-16 pb-8 px-16 bg-gradient-to-t from-slate-950/95 via-slate-950/75 to-transparent z-40 pointer-events-none select-none flex justify-center"
      >
        <div className="max-w-4xl text-center">
          <div className="text-base font-black tracking-widest text-[#e8f1f8] uppercase">
            LANGKAH {step}
          </div>
          <div className="mt-1 text-3xl font-black tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
            {actionText}
          </div>
          {detailText && (
            <p className="mt-1.5 text-xl font-bold leading-snug text-slate-100 max-w-3xl drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)] mx-auto">
              {detailText}
            </p>
          )}
        </div>
      </div>
    );
  }

  if (position === "split-cinematic") {
    return (
      <div
        style={{ transform: `translateY(${translateY}px)`, opacity }}
        className="absolute bottom-0 left-0 right-0 pt-16 pb-8 px-16 bg-gradient-to-t from-slate-950/95 via-slate-950/75 to-transparent z-40 pointer-events-none select-none"
      >
        <div className="flex items-end justify-between gap-12 max-w-7xl mx-auto">
          <div>
            <div className="text-base font-black tracking-widest text-[#e8f1f8] uppercase">
              LANGKAH {step}
            </div>
            <div className="mt-1 text-3xl font-black tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
              {actionText}
            </div>
          </div>
          {detailText && (
            <p className="text-xl font-bold leading-snug text-slate-100 max-w-2xl text-right drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)]">
              {detailText}
            </p>
          )}
        </div>
      </div>
    );
  }

  // Default: "bottom-left"
  return (
    <div
      style={{ transform: `translateY(${translateY}px)`, opacity }}
      className="absolute bottom-0 left-0 right-0 pt-16 pb-8 px-16 bg-gradient-to-t from-slate-950/95 via-slate-950/75 to-transparent z-40 pointer-events-none select-none"
    >
      <div className="max-w-4xl text-left">
        <div className="text-base font-black tracking-widest text-[#e8f1f8] uppercase">
          LANGKAH {step}
        </div>
        <div className="mt-1 text-3xl font-black tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
          {actionText}
        </div>
        {detailText && (
          <p className="mt-1.5 text-xl font-bold leading-snug text-slate-100 drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)] max-w-3xl">
            {detailText}
          </p>
        )}
      </div>
    </div>
  );
};
