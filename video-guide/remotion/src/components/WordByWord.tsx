import React from "react";
import { interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";

export interface WordByWordProps {
  text: string;
  startFrame: number;
  durationInFrames?: number;
  staggerFrames?: number;
  className?: string;
  theme?: "dark" | "light";
  highlightWords?: string[];
  highlightClassName?: string;
  style?: React.CSSProperties;
}

/**
 * WordByWord — Animasi teks per-kata tersinkronisasi Voice Over.
 * Kata muncul tepat sesuai ritme pengucapan narator dengan spring punch halus.
 */
export const WordByWord: React.FC<WordByWordProps> = ({
  text,
  startFrame,
  durationInFrames,
  staggerFrames,
  className = "",
  theme = "dark",
  highlightWords = [],
  highlightClassName,
  style = {},
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const words = text.split(" ");
  const totalWords = words.length;

  // Calculate per-word activation frame based on speech duration
  const getWordStart = (idx: number) => {
    if (durationInFrames) {
      const step = durationInFrames / Math.max(1, totalWords);
      return startFrame + Math.round(idx * step);
    }
    const step = staggerFrames ?? 4;
    return startFrame + idx * step;
  };

  const cleanWord = (w: string) =>
    w.replace(/[.,/#!$%^&*;:{}=\-_`~()?"']/g, "").toLowerCase();

  const isHighlighted = (w: string) => {
    const cleaned = cleanWord(w);
    return highlightWords.some(
      (hw) => cleanWord(hw) === cleaned || cleaned.includes(cleanWord(hw))
    );
  };

  const defaultHighlightClass =
    theme === "dark" ? "text-white font-extrabold" : "text-[#0f172a] font-extrabold";

  return (
    <span className={`inline-block ${className}`} style={style}>
      {words.map((word, idx) => {
        const wordStart = getWordStart(idx);
        const rel = frame - wordStart;

        const opacity = interpolate(rel, [-2, 3], [0, 1], {
          extrapolateLeft: "clamp",
          extrapolateRight: "clamp",
        });

        const s = spring({
          frame: rel,
          fps,
          config: { damping: 15, stiffness: 140 },
        });

        const scale = interpolate(s, [0, 1], [0.94, 1]);
        const y = interpolate(s, [0, 1], [4, 0]);

        const highlighted = isHighlighted(word);
        const highlightStyle = highlighted
          ? highlightClassName || defaultHighlightClass
          : "";

        return (
          <span
            key={idx}
            style={{
              opacity,
              transform: `scale(${scale}) translateY(${y}px)`,
              display: "inline-block",
              marginRight: "0.28em",
            }}
            className={highlightStyle}
          >
            {word}
          </span>
        );
      })}
    </span>
  );
};
