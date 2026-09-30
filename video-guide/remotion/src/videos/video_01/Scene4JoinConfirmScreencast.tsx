import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  CameraViewport,
  StepCallout,
} from "../../components/ScreencastEngine";

export const Scene4JoinConfirmScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Camera smooth zoom into card center
  const zoom = interpolate(frame, [0, 40], [1.0, 1.2], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // Cursor movement to action button (centered at x: 960, y: 558)
  let cursorX = 1450;
  let cursorY = 240;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 65) {
    cursorX = interpolate(frame, [15, 60], [1450, 960], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [15, 60], [240, 558], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 960;
    cursorY = 558;
    if (frame >= 65 && frame <= 70) {
      isClicking = true;
      clickFrame = 65;
    }
  }

  return (
    <div className="relative h-full w-full bg-[#f8fafc] overflow-hidden select-none">
      <CameraViewport zoom={zoom} focusX={960} focusY={450}>
        <div className="relative w-[1920px] h-[1080px]">
          <Img
            src={staticFile("screens/13_mahasiswa_join_confirm.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />

          {/* Button Active Highlight */}
          {isClicking && (
            <div
              style={{
                position: "absolute",
                left: 875,
                top: 538,
                width: 170,
                height: 40,
                backgroundColor: "rgba(16, 47, 80, 0.2)",
                borderRadius: 8,
              }}
            />
          )}

          <AnimatedCursor
            x={cursorX}
            y={cursorY}
            isClicking={isClicking}
            clickFrame={clickFrame}
          />
        </div>
      </CameraViewport>

      <StepCallout
        step="03"
        title="Konfirmasi & Masuk Kelas"
        description="Periksa informasi mata kuliah, semester, dan dosen pengampu, lalu klik tombol untuk masuk ke kelas."
        position="bottom-left"
      />
    </div>
  );
};
