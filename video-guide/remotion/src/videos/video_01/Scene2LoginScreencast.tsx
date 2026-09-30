import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  CameraViewport,
  StepCallout,
} from "../../components/ScreencastEngine";

export const Scene2LoginScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // 1. Camera Zoom Animation (1.0 -> 1.22 -> 1.0)
  const zoom = interpolate(
    frame,
    [10, 45, 185, 220],
    [1.0, 1.22, 1.22, 1.0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  // 2. Cursor Coordinates (X, Y)
  let cursorX = 1420;
  let cursorY = 320;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 38) {
    // Moving to NIM input
    cursorX = interpolate(frame, [8, 36], [1420, 960], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [8, 36], [320, 577], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= 38 && frame < 90) {
    cursorX = 960;
    cursorY = 577;
    if (frame >= 38 && frame <= 42) {
      isClicking = true;
      clickFrame = 38;
    }
  } else if (frame >= 90 && frame < 118) {
    // Moving to Password input
    cursorX = 960;
    cursorY = interpolate(frame, [90, 116], [577, 666], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= 118 && frame < 155) {
    cursorX = 960;
    cursorY = 666;
    if (frame >= 118 && frame <= 122) {
      isClicking = true;
      clickFrame = 118;
    }
  } else if (frame >= 155 && frame < 182) {
    // Moving to Masuk button
    cursorX = 960;
    cursorY = interpolate(frame, [155, 180], [666, 730], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 960;
    cursorY = 730;
    if (frame >= 182 && frame <= 188) {
      isClicking = true;
      clickFrame = 182;
    }
  }

  // 3. Typing Simulation
  const fullNim = "231011401234";
  const nimCharsTyped = Math.floor(
    interpolate(frame, [45, 85], [0, fullNim.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentNim = fullNim.slice(0, nimCharsTyped);

  const fullPass = "••••••••";
  const passCharsTyped = Math.floor(
    interpolate(frame, [122, 150], [0, fullPass.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentPass = fullPass.slice(0, passCharsTyped);

  const isBlinking = Math.floor(frame / 12) % 2 === 0;

  return (
    <div className="relative h-full w-full bg-[#0e2740] overflow-hidden select-none">
      <CameraViewport zoom={zoom} focusX={960} focusY={640}>
        {/* Authentic Background Screenshot */}
        <div className="relative w-[1920px] h-[1080px]">
          <Img
            src={staticFile("screens/01_login_blank.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />

          {/* Dynamic Typed NIM Overlay */}
          {frame >= 40 && (
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
                border: frame < 90 ? "2px solid #2563eb" : "1px solid #cbd5e1",
              }}
            >
              <span>{currentNim}</span>
              {frame < 90 && isBlinking && (
                <span style={{ color: "#2563eb", fontWeight: 700 }}>|</span>
              )}
            </div>
          )}

          {/* Dynamic Typed Password Overlay */}
          {frame >= 118 && (
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
                border: frame < 155 ? "2px solid #2563eb" : "1px solid #cbd5e1",
              }}
            >
              <span>{currentPass}</span>
              {frame >= 118 && frame < 155 && isBlinking && (
                <span style={{ color: "#2563eb", fontSize: 14, marginLeft: 2 }}>
                  |
                </span>
              )}
            </div>
          )}

          {/* Button Click Active State */}
          {isClicking && frame >= 182 && (
            <div
              style={{
                position: "absolute",
                left: 769,
                top: 710,
                width: 382,
                height: 40,
                backgroundColor: "rgba(16, 47, 80, 0.4)",
                borderRadius: 8,
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
        </div>
      </CameraViewport>

      {/* Instructional Callout */}
      <StepCallout
        step="01"
        title="Masuk ke Sistem"
        description="Ketikkan NIM pada kolom identitas dan masukkan kata sandi Anda, lalu klik tombol Masuk."
        position="top-right"
      />
    </div>
  );
};
