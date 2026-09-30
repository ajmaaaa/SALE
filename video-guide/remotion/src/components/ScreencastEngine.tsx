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
  | "center-left"
  | "center-right"
  | "bottom-left"
  | "bottom-right"
  | "bottom-center"
  | "split-cinematic";

/**
 * Text instruction overlay:
 * - Supports vertical-center placement ("center-left", "center-right") to avoid monotony
 * - Positioned safely away from action targets so it NEVER covers buttons or inputs
 * - PURE TYPOGRAPHY (no cards, no badges, no border boxes)
 * - Strict rule: NO dot (.) separator, NO em-dash (—) ornament
 */
export const InstructionOverlay: React.FC<{
  step: string;
  actionText: string;
  detailText?: string;
  position?: OverlayPosition;
}> = ({ step, actionText, detailText, position = "center-left" }) => {
  const frame = useCurrentFrame();

  const opacity = interpolate(frame, [0, 8], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const translateY = interpolate(frame, [0, 8], [10, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });


  // 1. SIDE - LEFT (Flushed to left edge, soft feathered shadow, dark font)
  if (position === "center-left") {
    return (
      <div
        style={{
          transform: `translateY(${translateY}px)`,
          opacity,
        }}
        className="absolute left-0 top-[52%] -translate-y-1/2 z-40 max-w-xl pointer-events-none select-none text-left"
      >
        {/* Soft edge shadow: rapat ke sisi paling kiri, segiempat tapi kabur di sisinya, tanpa card */}
        <div
          className="absolute inset-0 -right-24 pointer-events-none -z-10"
          style={{
            background:
              "linear-gradient(to right, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.90) 65%, rgba(255, 255, 255, 0) 100%)",
            backdropFilter: "blur(12px)",
            WebkitMaskImage:
              "linear-gradient(to bottom, transparent 0%, black 16%, black 84%, transparent 100%)",
            maskImage:
              "linear-gradient(to bottom, transparent 0%, black 16%, black 84%, transparent 100%)",
          }}
        />

        <div className="pl-16 pr-12 py-10">
          <div className="text-base font-black tracking-widest uppercase text-blue-700">
            LANGKAH {step}
          </div>
          <div className="mt-2 text-4xl font-black tracking-tight leading-tight text-slate-950">
            {actionText}
          </div>
          {detailText && (
            <p className="mt-3 text-2xl font-bold leading-snug text-slate-700">
              {detailText}
            </p>
          )}
        </div>
      </div>
    );
  }

  // 2. SIDE - RIGHT (Flushed to right edge, soft feathered shadow, dark font, lower-right placement)
  if (position === "center-right") {
    return (
      <div
        style={{
          transform: `translateY(${translateY}px)`,
          opacity,
        }}
        className="absolute right-0 bottom-24 z-40 max-w-lg pointer-events-none select-none text-left"
      >
        {/* Soft edge shadow: rapat ke sisi paling kanan, segiempat tapi kabur di sisinya, tanpa card */}
        <div
          className="absolute inset-0 -left-16 pointer-events-none -z-10"
          style={{
            background:
              "linear-gradient(to left, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.92) 70%, transparent 100%)",
            backdropFilter: "blur(8px)",
            WebkitMaskImage:
              "linear-gradient(to bottom, transparent 0%, black 15%, black 85%, transparent 100%)",
            maskImage:
              "linear-gradient(to bottom, transparent 0%, black 15%, black 85%, transparent 100%)",
          }}
        />

        <div className="pr-16 pl-10 py-6">
          <div className="text-base font-black tracking-widest uppercase text-blue-700">
            LANGKAH {step}
          </div>
          <div className="mt-2 text-4xl font-black tracking-tight leading-tight text-slate-950">
            {actionText}
          </div>
          {detailText && (
            <p className="mt-3 text-xl font-bold leading-snug text-slate-700">
              {detailText}
            </p>
          )}
        </div>
      </div>
    );
  }

  // 3. BOTTOM - CENTER
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

  // 4. SPLIT CINEMATIC (Wide format)
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

  // 5. BOTTOM - RIGHT
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

  // 6. DEFAULT: BOTTOM - LEFT
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
