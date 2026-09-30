import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene5CourseActiveScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // Continuous natural cursor motion from modal submit (1099, 641) to course header, video, and forum
  let cursorX = 1099;
  let cursorY = 641;

  if (frame < 45) {
    cursorX = interpolate(frame, [6, 42], [1099, 520], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [6, 42], [641, 155], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= 45 && frame < 90) {
    cursorX = interpolate(frame, [48, 85], [520, 650], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [48, 85], [155, 520], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    cursorX = interpolate(frame, [90, 125], [650, 1450], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [90, 125], [520, 420], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* Authentic Screen: Full Real Course Learning Page with Videos, Forums, and Modules */}
      <Img
        src={staticFile("screens/15_mahasiswa_course_detail.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={false}
        clickFrame={-1}
      />

      {/* Cinematic Wide Layout with bottom shadow protection */}
      <InstructionOverlay
        step="03"
        actionText="Kelas Perkuliahan Resmi Aktif"
        detailText="Pendaftaran kelas berhasil, silabus perkuliahan, video materi, dan forum diskusi sudah siap digunakan"
        position="split-cinematic"
      />
    </div>
  );
};
