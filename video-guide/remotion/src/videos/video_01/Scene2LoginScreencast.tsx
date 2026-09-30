import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene2LoginScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Cursor Coordinates (X, Y)
  let cursorX = 1350;
  let cursorY = 380;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 36) {
    // Moving smoothly to NIM field (960, 577)
    cursorX = interpolate(frame, [8, 34], [1350, 960], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [8, 34], [380, 577], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= 36 && frame < 85) {
    cursorX = 960;
    cursorY = 577;
    if (frame >= 36 && frame <= 40) {
      isClicking = true;
      clickFrame = 36;
    }
  } else if (frame >= 85 && frame < 112) {
    // Moving smoothly to Password field (960, 666)
    cursorX = 960;
    cursorY = interpolate(frame, [85, 110], [577, 666], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= 112 && frame < 145) {
    cursorX = 960;
    cursorY = 666;
    if (frame >= 112 && frame <= 116) {
      isClicking = true;
      clickFrame = 112;
    }
  } else if (frame >= 145 && frame < 172) {
    // Moving to button Masuk (960, 730)
    cursorX = 960;
    cursorY = interpolate(frame, [145, 170], [666, 730], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 960;
    cursorY = 730;
    if (frame >= 172 && frame <= 178) {
      isClicking = true;
      clickFrame = 172;
    }
  }

  // Typing Simulation
  const fullNim = "231011401234";
  const nimCharsTyped = Math.floor(
    interpolate(frame, [42, 80], [0, fullNim.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentNim = fullNim.slice(0, nimCharsTyped);

  const fullPass = "••••••••";
  const passCharsTyped = Math.floor(
    interpolate(frame, [118, 142], [0, fullPass.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentPass = fullPass.slice(0, passCharsTyped);

  const isBlinking = Math.floor(frame / 12) % 2 === 0;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#0e2740] overflow-hidden select-none">
      {/* Authentic Background Screenshot (Full 1920x1080 without awkward crop) */}
      <Img
        src={staticFile("screens/01_login_blank.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Dynamic Typed NIM Overlay */}
      {frame >= 38 && (
        <div
          style={{
            position: "absolute",
            left: 771,
            top: 558,
            width: 378,
            height: 38,
            backgroundColor: "#ffffff",
            display: "flex",
            alignItems: "center",
            paddingLeft: 12,
            borderRadius: 6,
            fontFamily: "monospace",
            fontSize: 14,
            color: "#0f172a",
            fontWeight: 600,
            border: frame < 85 ? "2px solid #2563eb" : "1px solid #cbd5e1",
          }}
        >
          <span>{currentNim}</span>
          {frame < 85 && isBlinking && (
            <span style={{ color: "#2563eb", fontWeight: 700 }}>|</span>
          )}
        </div>
      )}

      {/* Dynamic Typed Password Overlay */}
      {frame >= 114 && (
        <div
          style={{
            position: "absolute",
            left: 771,
            top: 647,
            width: 378,
            height: 38,
            backgroundColor: "#ffffff",
            display: "flex",
            alignItems: "center",
            paddingLeft: 12,
            borderRadius: 6,
            fontSize: 18,
            letterSpacing: 2,
            color: "#0f172a",
            border: frame < 145 ? "2px solid #2563eb" : "1px solid #cbd5e1",
          }}
        >
          <span>{currentPass}</span>
          {frame >= 114 && frame < 145 && isBlinking && (
            <span style={{ color: "#2563eb", fontSize: 14, marginLeft: 2 }}>
              |
            </span>
          )}
        </div>
      )}

      {/* Button Hover Glow & Click Active State */}
      {frame >= 165 && (
        <div
          style={{
            position: "absolute",
            left: 769,
            top: 710,
            width: 382,
            height: 40,
            borderRadius: 8,
            boxShadow: isClicking
              ? "0 0 0 3px rgba(37, 99, 235, 0.4)"
              : "0 0 16px rgba(56, 189, 248, 0.35)",
            backgroundColor: isClicking
              ? "rgba(16, 47, 80, 0.25)"
              : "transparent",
            pointerEvents: "none",
          }}
        />
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/* Clean text instruction (NO bulky card, dark screen mode) */}
      <InstructionOverlay
        step="01"
        actionText="Masuk ke Sistem SALE"
        detailText="Ketikkan NIM pada kolom identitas dan masukkan kata sandi Anda, lalu klik tombol Masuk."
        darkScreen={true}
      />
    </div>
  );
};
