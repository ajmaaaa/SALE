import React from "react";
import { linearTiming, TransitionSeries } from "@remotion/transitions";
import { fade } from "@remotion/transitions/fade";
import { interpolate, useCurrentFrame, useVideoConfig } from "remotion";
import { Scene1Intro } from "./Scene1Intro";
import { Scene2LoginScreencast } from "./Scene2LoginScreencast";
import { Scene3DashboardScreencast } from "./Scene3DashboardScreencast";
import { Scene4JoinConfirmScreencast } from "./Scene4JoinConfirmScreencast";
import { Scene5CourseActiveScreencast } from "./Scene5CourseActiveScreencast";

export const V01_DURATIONS = {
  scene1: 120,
  scene2: 240,
  scene3: 180,
  scene4: 180,
  scene5: 150,
};

export const V01_TRANSITION = 20;

export const TOTAL_V01_FRAMES =
  V01_DURATIONS.scene1 +
  V01_DURATIONS.scene2 +
  V01_DURATIONS.scene3 +
  V01_DURATIONS.scene4 +
  V01_DURATIONS.scene5 -
  4 * V01_TRANSITION; // 790 frames (~26.3 detik)

const ProgressBar: React.FC = () => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();

  const progress = interpolate(frame, [0, durationInFrames], [0, 100], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div className="absolute bottom-0 left-0 right-0 h-1.5 bg-black/30 z-50">
      <div
        style={{ width: `${progress}%` }}
        className="h-full bg-cyan-400 shadow-sm"
      />
    </div>
  );
};

export const Video01MahasiswaJoin: React.FC = () => {
  return (
    <div className="relative w-full h-full bg-[#0e2740]">
      <TransitionSeries>
        <TransitionSeries.Sequence
          name="01 - Pembuka Panduan"
          durationInFrames={V01_DURATIONS.scene1}
        >
          <Scene1Intro />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: V01_TRANSITION })}
        />

        <TransitionSeries.Sequence
          name="02 - Rekaman Layar Login"
          durationInFrames={V01_DURATIONS.scene2}
        >
          <Scene2LoginScreencast />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: V01_TRANSITION })}
        />

        <TransitionSeries.Sequence
          name="03 - Navigasi Dashboard"
          durationInFrames={V01_DURATIONS.scene3}
        >
          <Scene3DashboardScreencast />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: V01_TRANSITION })}
        />

        <TransitionSeries.Sequence
          name="04 - Konfirmasi Kelas"
          durationInFrames={V01_DURATIONS.scene4}
        >
          <Scene4JoinConfirmScreencast />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: V01_TRANSITION })}
        />

        <TransitionSeries.Sequence
          name="05 - Kelas Aktif Selesai"
          durationInFrames={V01_DURATIONS.scene5}
        >
          <Scene5CourseActiveScreencast />
        </TransitionSeries.Sequence>
      </TransitionSeries>

      <ProgressBar />
    </div>
  );
};
