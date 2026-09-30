import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  CameraViewport,
  StepCallout,
} from "../../components/ScreencastEngine";

export const Scene3DashboardScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Camera smooth zoom towards sidebar
  const zoom = interpolate(frame, [0, 30], [1.0, 1.15], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // Cursor movement to Course Menu (x: 124, y: 194)
  let cursorX = 720;
  let cursorY = 480;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 55) {
    cursorX = interpolate(frame, [10, 50], [720, 124], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [10, 50], [480, 194], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = 124;
    cursorY = 194;
    if (frame >= 55 && frame <= 60) {
      isClicking = true;
      clickFrame = 55;
    }
  }

  return (
    <div className="relative h-full w-full bg-[#f8fafc] overflow-hidden select-none">
      <CameraViewport zoom={zoom} focusX={380} focusY={300}>
        <div className="relative w-[1920px] h-[1080px]">
          <Img
            src={staticFile("screens/03_mahasiswa_dashboard.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />

          {/* Hover highlight on Course Nav */}
          {frame >= 45 && (
            <div
              style={{
                position: "absolute",
                left: 12,
                top: 174,
                width: 224,
                height: 40,
                backgroundColor: "rgba(16, 47, 80, 0.08)",
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
        step="02"
        title="Navigasi ke Menu Course"
        description="Masuk ke menu Course di sidebar untuk mengelola kelas dan bergabung ke perkuliahan baru."
        position="top-right"
      />
    </div>
  );
};
