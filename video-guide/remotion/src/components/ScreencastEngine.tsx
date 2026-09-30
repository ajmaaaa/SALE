import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";

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
  const { fps } = useVideoConfig();

  // Click pulse animation
  const framesSinceClick = frame - clickFrame;
  const showRipple = framesSinceClick >= 0 && framesSinceClick < 18;
  
  const rippleScale = interpolate(framesSinceClick, [0, 16], [0.4, 2.2], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const rippleOpacity = interpolate(framesSinceClick, [0, 16], [0.8, 0], {
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
      {/* Click Ripple Effect */}
      {showRipple && (
        <div
          style={{
            position: "absolute",
            left: 0,
            top: 0,
            width: 44,
            height: 44,
            borderRadius: "50%",
            transform: `translate(-50%, -50%) scale(${rippleScale})`,
            opacity: rippleOpacity,
            backgroundColor: "rgba(56, 189, 248, 0.4)",
            border: "2px solid rgba(14, 165, 233, 0.9)",
          }}
        />
      )}

      {/* SVG Modern macOS/Clean Pointer Cursor */}
      <svg
        width="28"
        height="28"
        viewBox="0 0 24 24"
        fill="none"
        style={{
          transform: `scale(${cursorScale})`,
          transformOrigin: "top left",
          filter: "drop-shadow(0 4px 6px rgba(0, 0, 0, 0.35))",
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
  // Center of viewport is (960, 540) for 1920x1080
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

export const StepCallout: React.FC<{
  step: string;
  title: string;
  description: string;
  position?: "top-left" | "top-right" | "bottom-left" | "bottom-right";
}> = ({ step, title, description, position = "top-right" }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const enter = spring({
    frame,
    fps,
    config: { damping: 14, stiffness: 100 },
  });
  const opacity = interpolate(frame, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const positionClasses = {
    "top-left": "top-8 left-8",
    "top-right": "top-8 right-8",
    "bottom-left": "bottom-12 left-8",
    "bottom-right": "bottom-12 right-8",
  };

  return (
    <div
      style={{
        transform: `translateY(${interpolate(enter, [0, 1], [-20, 0])}px)`,
        opacity,
      }}
      className={`absolute ${positionClasses[position]} z-50 flex items-start gap-3.5 max-w-md rounded-2xl bg-[#102f50]/95 p-4 text-white shadow-2xl backdrop-blur-md border border-white/20 select-none`}
    >
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-400 text-xs font-black text-[#102f50]">
        {step}
      </div>
      <div>
        <div className="text-xs font-bold uppercase tracking-wider text-cyan-300">
          {title}
        </div>
        <div className="mt-0.5 text-xs text-slate-200 leading-snug">
          {description}
        </div>
      </div>
    </div>
  );
};
