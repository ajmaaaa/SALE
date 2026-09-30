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

  const rippleScale = interpolate(framesSinceClick, [0, 14], [0.5, 2.0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const rippleOpacity = interpolate(framesSinceClick, [0, 14], [0.7, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const cursorScale = isClicking ? 0.84 : 1;

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
            width: 36,
            height: 36,
            borderRadius: "50%",
            transform: `translate(-50%, -50%) scale(${rippleScale})`,
            opacity: rippleOpacity,
            backgroundColor: "rgba(56, 189, 248, 0.3)",
            border: "2px solid rgba(14, 165, 233, 0.8)",
          }}
        />
      )}

      {/* SVG Pointer Cursor */}
      <svg
        width="26"
        height="26"
        viewBox="0 0 24 24"
        fill="none"
        style={{
          transform: `scale(${cursorScale})`,
          transformOrigin: "top left",
          filter: "drop-shadow(0 3px 5px rgba(0, 0, 0, 0.45))",
        }}
      >
        <path
          d="M3 3L10.07 20.97L13.58 13.58L20.97 10.07L3 3Z"
          fill="#0f172a"
          stroke="#ffffff"
          strokeWidth="1.8"
          strokeLinejoin="round"
        />
      </svg>
    </div>
  );
};

export const CameraViewport: React.FC<{
  zoom?: number;
  focusX?: number;
  focusY?: number;
  children: React.ReactNode;
}> = ({ zoom = 1, focusX = 960, focusY = 540, children }) => {
  const translateX = -(focusX - 960) * (zoom - 1);
  const translateY = -(focusY - 540) * (zoom - 1);

  return (
    <div
      style={{
        width: 1920,
        height: 1080,
        overflow: "hidden",
        position: "relative",
      }}
    >
      <div
        style={{
          width: 1920,
          height: 1080,
          transform: `scale(${zoom}) translate(${translateX / zoom}px, ${translateY / zoom}px)`,
          transformOrigin: "center center",
          willChange: "transform",
        }}
      >
        {children}
      </div>
    </div>
  );
};

/**
 * Clean typography-only instruction overlay (NO bulky cards, NO neon slop)
 */
export const InstructionOverlay: React.FC<{
  step: string;
  actionText: string;
  detailText?: string;
  darkScreen?: boolean;
}> = ({ step, actionText, detailText, darkScreen = false }) => {
  const frame = useCurrentFrame();

  const opacity = interpolate(frame, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const translateY = interpolate(frame, [0, 10], [8, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{
        transform: `translateY(${translateY}px)`,
        opacity,
      }}
      className="absolute bottom-10 left-12 z-50 pointer-events-none select-none max-w-2xl"
    >
      <div className="flex items-center gap-2.5">
        <span
          className={`text-xs font-black tracking-widest uppercase ${
            darkScreen ? "text-cyan-300" : "text-blue-600"
          }`}
        >
          LANGKAH {step}
        </span>
        <span
          className={`h-1 w-1 rounded-full ${
            darkScreen ? "bg-slate-400" : "bg-slate-400"
          }`}
        />
        <span
          className={`text-base font-extrabold tracking-tight ${
            darkScreen
              ? "text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]"
              : "text-[#0f172a] drop-shadow-[0_1px_2px_rgba(255,255,255,0.8)]"
          }`}
        >
          {actionText}
        </span>
      </div>

      {detailText && (
        <p
          className={`mt-1 text-xs font-medium leading-relaxed max-w-xl ${
            darkScreen
              ? "text-slate-300 drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)]"
              : "text-slate-600"
          }`}
        >
          {detailText}
        </p>
      )}
    </div>
  );
};
