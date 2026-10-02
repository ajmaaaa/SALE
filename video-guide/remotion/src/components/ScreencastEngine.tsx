import React from "react";
import { interpolate, useCurrentFrame } from "remotion";
import { WordByWord } from "./WordByWord";

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
            border: "2.5px solid rgba(148, 210, 255, 0.95)",
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
  | "top-left"
  | "top-right"
  | "center-left"
  | "center-right"
  | "bottom-center"
  | "split-cinematic";

/**
 * Text instruction overlay:
 * - PURE TYPOGRAPHY (NO cards, NO border boxes, NO label container pills, NO AI-slop)
 * - Soft photographic dark gradient backdrop ensures 100% legibility on any UI
 * - Sharp typography hierarchy: Heading font-extrabold (800) vs Subtitle font-normal (400)
 * - Varied positioning (bottom-left, bottom-right, split-cinematic) prevents monotony
 * - Positioned safely away from action targets so it NEVER covers buttons or inputs
 * - Strictly NO dot (.) separator, NO em-dash (—) ornament, NO slash (//)
 */
export type OverlayVariant =
  | "standard"
  | "compact"
  | "headline"
  | "split"
  | "step-badge";

export type OverlayTheme = "light" | "dark";

export const InstructionOverlay: React.FC<{
  step?: string;
  badge?: string;
  category?: string;
  actionText: string;
  detailText?: string;
  position?: OverlayPosition | "custom";
  customStyle?: React.CSSProperties;
  variant?: OverlayVariant;
  theme?: OverlayTheme;
  startFrame?: number;
  durationInFrames?: number;
}> = ({
  step,
  actionText,
  detailText,
  position = "bottom-left",
  customStyle,
  variant = "standard",
  theme = "light",
  startFrame = 0,
  durationInFrames,
}) => {
  const currentFrame = useCurrentFrame();
  const relFrame = currentFrame - startFrame;

  if (relFrame < 0) return null;
  if (durationInFrames !== undefined && relFrame > durationInFrames) return null;

  const fadeIn = interpolate(relFrame, [0, 10], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeOut = durationInFrames
    ? interpolate(relFrame, [durationInFrames - 10, durationInFrames], [1, 0], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      })
    : 1;
  const opacity = fadeIn * fadeOut;
  const translateY = interpolate(relFrame, [0, 10], [6, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // Badge Container according to WORKFLOW_DAN_STANDAR_VIDEO.md Section 4.1:
  // Dark Navy: h-12 w-12 rounded-xl bg-white/10 border border-white/15 text-white font-bold text-lg shadow-sm
  // Light: h-12 w-12 rounded-xl bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50] font-bold text-lg shadow-sm
  const isCompact = variant === "compact";

  const renderBadge = () => {
    if (!step) return null;
    return (
      <span
        className={`flex items-center justify-center ${
          isCompact ? "h-11 w-11 rounded-lg text-base" : "h-12 w-12 rounded-xl text-lg"
        } ${
          theme === "light"
            ? "bg-[#102f50]/10 border border-[#102f50]/15 text-[#102f50]"
            : "bg-white/10 border border-white/15 text-white"
        } font-bold shadow-sm shrink-0 mt-0.5`}
      >
        {step}
      </span>
    );
  };

  // Content body
  const content = (
    <div className="flex items-start gap-3.5">
      {renderBadge()}
      <div className={position === "bottom-right" ? "text-right" : "text-left"}>
        <div
          className={`${
            isCompact ? "text-2xl" : "text-3xl"
          } font-extrabold tracking-tight leading-tight ${
            theme === "light"
              ? "text-slate-900 drop-shadow-[0_1px_1px_rgba(255,255,255,0.8)]"
              : "text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]"
          }`}
        >
          {actionText}
        </div>
        {detailText && (
          <p
            className={`mt-1.5 ${
              isCompact ? "text-base leading-normal" : "text-lg leading-snug"
            } font-normal max-w-xl ${
              theme === "light"
                ? "text-slate-600 drop-shadow-[0_1px_1px_rgba(255,255,255,0.8)]"
                : "text-slate-300 drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)]"
            }`}
          >
            {detailText}
          </p>
        )}
      </div>
    </div>
  );

  // 1. Custom Empty Area Placement (No dark card/bar needed, sits cleanly directly in UI whitespace!)
  if (customStyle) {
    return (
      <div
        style={{
          position: "absolute",
          zIndex: 40,
          pointerEvents: "none",
          userSelect: "none",
          transform: `translateY(${translateY}px)`,
          opacity,
          ...customStyle,
        }}
      >
        {content}
      </div>
    );
  }

  // 2. Corner / Edge Placement (Desain Langkah 7 & 8 yang disukai user saat layar penuh)
  const bgStyle: React.CSSProperties = {
    background:
      theme === "light"
        ? "linear-gradient(to top, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.85) 60%, rgba(255, 255, 255, 0) 100%)"
        : "linear-gradient(to top, rgba(15, 23, 42, 0.98) 0%, rgba(15, 23, 42, 0.85) 65%, rgba(15, 23, 42, 0) 100%)",
    paddingTop: 56,
    paddingBottom: 32,
    paddingLeft: 64,
    paddingRight: 64,
  };

  if (position === "bottom-right") {
    return (
      <div
        style={{
          transform: `translateY(${translateY}px)`,
          opacity,
          ...bgStyle,
        }}
        className="absolute bottom-0 left-0 right-0 z-40 pointer-events-none select-none"
      >
        <div className="w-full max-w-[1720px] mx-auto px-20 flex justify-end">
          <div className="flex items-start gap-4 flex-row-reverse">
            {renderBadge()}
            <div className="text-right">
              <div
                className={`text-3xl font-extrabold tracking-tight leading-tight ${
                  theme === "light" ? "text-slate-900" : "text-white"
                }`}
              >
                {actionText}
              </div>
              {detailText && (
                <p
                  className={`mt-1.5 text-lg font-normal leading-snug max-w-xl ml-auto ${
                    theme === "light" ? "text-slate-600" : "text-slate-300"
                  }`}
                >
                  {detailText}
                </p>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Default: bottom-left (Langkah 08 style)
  return (
    <div
      style={{
        transform: `translateY(${translateY}px)`,
        opacity,
        ...bgStyle,
      }}
      className="absolute bottom-0 left-0 right-0 z-40 pointer-events-none select-none"
    >
      <div className="w-full max-w-[1720px] mx-auto px-20 flex justify-start">
        {content}
      </div>
    </div>
  );
};

export interface SubtitleItem {
  startFrame: number;
  durationInFrames: number;
  heading: string;
  subtext?: string;
  position?: "bottom-left" | "bottom-right" | "bottom-center";
  customStyle?: React.CSSProperties;
}

/**
 * Dynamic Screencast Subtitles:
 * - Direct sync with Voice Over spoken phrases (changes as narrator speaks, not static)
 * - NO fake card boxes, NO number badges, NO AI-slop
 * - Classic broadcast shadow effect underneath the text (from-slate-950/95 via-slate-950/75 to-transparent)
 * - Pure, crisp white typography with drop shadow for 100% legibility on any background
 */
export const ScreencastSubtitles: React.FC<{
  items: SubtitleItem[];
}> = ({ items }) => {
  const currentFrame = useCurrentFrame();

  const activeItem = items.find(
    (item) =>
      currentFrame >= item.startFrame &&
      currentFrame < item.startFrame + item.durationInFrames
  );

  if (!activeItem) return null;

  const relFrame = currentFrame - activeItem.startFrame;
  const fadeIn = interpolate(relFrame, [0, 8], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  const fadeOut = interpolate(
    relFrame,
    [activeItem.durationInFrames - 8, activeItem.durationInFrames],
    [1, 0],
    {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    }
  );
  const opacity = fadeIn * fadeOut;
  const translateY = interpolate(relFrame, [0, 8], [6, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  if (activeItem.customStyle) {
    return (
      <div
        style={{
          position: "absolute",
          zIndex: 40,
          pointerEvents: "none",
          userSelect: "none",
          transform: `translateY(${translateY}px)`,
          opacity,
          ...activeItem.customStyle,
        }}
      >
        <div className="text-3xl font-extrabold tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)]">
          {activeItem.heading}
        </div>
        {activeItem.subtext && (
          <p className="mt-1 text-xl font-normal leading-snug text-slate-100 max-w-3xl drop-shadow-[0_1px_3px_rgba(0,0,0,0.9)]">
            {activeItem.subtext}
          </p>
        )}
      </div>
    );
  }

  const isRight = activeItem.position === "bottom-right";
  const isCenter = activeItem.position === "bottom-center";

  return (
    <div
      style={{
        transform: `translateY(${translateY}px)`,
        opacity,
        maskImage:
          !isRight && !isCenter
            ? "linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,1) 68%, rgba(0,0,0,0) 92%)"
            : undefined,
        WebkitMaskImage:
          !isRight && !isCenter
            ? "linear-gradient(to right, rgba(0,0,0,1) 0%, rgba(0,0,0,1) 68%, rgba(0,0,0,0) 92%)"
            : undefined,
      }}
      className={`absolute bottom-0 left-0 right-0 pt-24 pb-9 px-16 bg-gradient-to-t from-slate-950/98 via-slate-950/85 to-transparent z-40 pointer-events-none select-none flex ${
        isRight ? "justify-end" : isCenter ? "justify-center" : "justify-start"
      }`}
    >
      <div className={`max-w-4xl ${isRight ? "text-right" : isCenter ? "text-center" : "text-left"}`}>
        <div className="text-3xl font-black tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
          <WordByWord
            text={activeItem.heading}
            startFrame={activeItem.startFrame}
            staggerFrames={5}
          />
        </div>
        {activeItem.subtext && (
          <p className="mt-1.5 text-xl font-bold leading-snug text-slate-100 max-w-3xl drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)]">
            <WordByWord
              text={activeItem.subtext}
              startFrame={activeItem.startFrame + 8}
              durationInFrames={Math.max(25, activeItem.durationInFrames - 20)}
            />
          </p>
        )}
      </div>
    </div>
  );
};

