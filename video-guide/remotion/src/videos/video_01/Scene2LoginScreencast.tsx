import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene2LoginScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Timing benchmarks
  const CLICK_NIM_FRAME = 36;
  const CLICK_PASS_FRAME = 98;
  const CLICK_SUBMIT_FRAME = 154;
  const LOAD_DASHBOARD_FRAME = 162; // Immediate system reaction 8 frames after clicking Masuk

  // Natural Cursor Trajectory
  let cursorX = 1350;
  let cursorY = 380;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < CLICK_NIM_FRAME) {
    cursorX = interpolate(frame, [8, CLICK_NIM_FRAME - 2], [1350, 960], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [8, CLICK_NIM_FRAME - 2], [380, 577], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_NIM_FRAME && frame < 78) {
    cursorX = 960;
    cursorY = 577;
    if (frame >= CLICK_NIM_FRAME && frame <= CLICK_NIM_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_NIM_FRAME;
    }
  } else if (frame >= 78 && frame < CLICK_PASS_FRAME) {
    cursorX = 960;
    cursorY = interpolate(frame, [78, CLICK_PASS_FRAME - 2], [577, 666], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_PASS_FRAME && frame < 132) {
    cursorX = 960;
    cursorY = 666;
    if (frame >= CLICK_PASS_FRAME && frame <= CLICK_PASS_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_PASS_FRAME;
    }
  } else if (frame >= 132 && frame < CLICK_SUBMIT_FRAME) {
    cursorX = 960;
    cursorY = interpolate(frame, [132, CLICK_SUBMIT_FRAME - 2], [666, 730], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_SUBMIT_FRAME && frame < LOAD_DASHBOARD_FRAME + 10) {
    cursorX = 960;
    cursorY = 730;
    if (frame >= CLICK_SUBMIT_FRAME && frame <= CLICK_SUBMIT_FRAME + 5) {
      isClicking = true;
      clickFrame = CLICK_SUBMIT_FRAME;
    }
  } else {
    // On the loaded dashboard, cursor rests naturally towards the center
    cursorX = interpolate(frame, [LOAD_DASHBOARD_FRAME + 10, LOAD_DASHBOARD_FRAME + 35], [960, 600], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_DASHBOARD_FRAME + 10, LOAD_DASHBOARD_FRAME + 35], [730, 400], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }

  // Simulated Text Typing
  const fullNim = "231011401234";
  const nimCharsTyped = Math.floor(
    interpolate(frame, [40, 74], [0, fullNim.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentNim = fullNim.slice(0, nimCharsTyped);

  const fullPass = "••••••••";
  const passCharsTyped = Math.floor(
    interpolate(frame, [102, 126], [0, fullPass.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentPass = fullPass.slice(0, passCharsTyped);

  const isBlinking = Math.floor(frame / 10) % 2 === 0;
  const isDashboardLoaded = frame >= LOAD_DASHBOARD_FRAME;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#0e2740] overflow-hidden select-none">
      {/* 
        AUTHENTIC ACTION-REACTION SCREEN SWAP:
        Before frame 162: Login Screen
        After frame 162: Mahasiswa Dashboard Screen
      */}
      {!isDashboardLoaded ? (
        <>
          <Img
            src={staticFile("screens/01_login_blank.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />

          {/* Clean Typed NIM inside original input without artificial border */}
          {frame >= 38 && (
            <div
              style={{
                position: "absolute",
                left: 772,
                top: 559,
                width: 376,
                height: 36,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 10,
                borderRadius: 4,
                fontFamily: "monospace",
                fontSize: 14,
                color: "#0f172a",
                fontWeight: 600,
              }}
            >
              <span>{currentNim}</span>
              {frame < 78 && isBlinking && (
                <span style={{ color: "#2563eb", fontWeight: 700, marginLeft: 2 }}>|</span>
              )}
            </div>
          )}

          {/* Clean Typed Password inside original input */}
          {frame >= 100 && (
            <div
              style={{
                position: "absolute",
                left: 772,
                top: 648,
                width: 376,
                height: 36,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 10,
                borderRadius: 4,
                fontSize: 18,
                letterSpacing: 3,
                color: "#0f172a",
              }}
            >
              <span>{currentPass}</span>
              {frame < 132 && isBlinking && (
                <span style={{ color: "#2563eb", fontSize: 14, letterSpacing: 0, marginLeft: 2 }}>|</span>
              )}
            </div>
          )}
        </>
      ) : (
        /* Instant Action-Reaction: Real Dashboard Loaded */
        <Img
          src={staticFile("screens/03_mahasiswa_dashboard.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/* Interactive, Bold Instruction Overlay (Bottom-left cinematic placement) */}
      {!isDashboardLoaded && (
        <InstructionOverlay
          step="01"
          actionText="Masuk ke Sistem SALE"
          detailText="Ketikkan NIM pada kolom identitas dan masukkan kata sandi Anda, lalu klik tombol Masuk"
          position="bottom-left"
        />
      )}
    </div>
  );
};
