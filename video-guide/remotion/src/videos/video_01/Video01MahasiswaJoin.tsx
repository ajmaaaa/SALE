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
  scene1: 120, // 4.0s Intro Cover
  scene2: 210, // 7.0s Login Screencast + Action Reaction
  scene3: 180, // 6.0s Dashboard Nav + Gabung Modal + Action Reaction
  scene4: 150, // 5.0s Confirmation Screen + Action Reaction
  scene5: 120, // 4.0s Active Course Completion & Outro
};

// Only 1 snappy 6-frame cut between Intro cover and Screencast; screencast scenes cut seamlessly
export const INTRO_TRANSITION = 6;

export const TOTAL_V01_FRAMES =
  V01_DURATIONS.scene1 +
  V01_DURATIONS.scene2 +
  V01_DURATIONS.scene3 +
  V01_DURATIONS.scene4 +
  V01_DURATIONS.scene5 -
  INTRO_TRANSITION; // 774 frames (~25.8 detik)

const ProgressBar: React.FC = () => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();

  const progress = interpolate(frame, [0, durationInFrames], [0, 100], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div className="absolute bottom-0 left-0 right-0 h-1.5 bg-black/40 z-50">
      <div
        style={{ width: `${progress}%` }}
        className="h-full bg-blue-500 shadow-sm"
      />
    </div>
  );
};

export const Video01MahasiswaJoin: React.FC = () => {
  return (
    <div className="relative w-full h-full bg-[#0b1626]">
      <TransitionSeries>
        {/* Scene 1: Professional Intro Cover */}
        <TransitionSeries.Sequence
          name="01 - Pembuka Panduan"
          durationInFrames={V01_DURATIONS.scene1}
        >
          <Scene1Intro />
        </TransitionSeries.Sequence>

        {/* Snappy 6-frame quick cut to screencast (NO long ghosting overlap) */}
        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: INTRO_TRANSITION })}
        />

        {/* Scene 2: Real Login Screencast (Types & immediately cuts to loaded Dashboard) */}
        <TransitionSeries.Sequence
          name="02 - Rekaman Layar Login"
          durationInFrames={V01_DURATIONS.scene2}
        >
          <Scene2LoginScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Scene 3 starts directly on the loaded Dashboard */}
        <TransitionSeries.Sequence
          name="03 - Navigasi Dashboard & Modal Gabung"
          durationInFrames={V01_DURATIONS.scene3}
        >
          <Scene3DashboardScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Scene 4 shows the confirmed enrollment status */}
        <TransitionSeries.Sequence
          name="04 - Konfirmasi Kelas"
          durationInFrames={V01_DURATIONS.scene4}
        >
          <Scene4JoinConfirmScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Scene 5 shows the activated course view */}
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
