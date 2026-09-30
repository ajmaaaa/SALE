import React from "react";
import { linearTiming, TransitionSeries } from "@remotion/transitions";
import { fade } from "@remotion/transitions/fade";
import { interpolate, useCurrentFrame, useVideoConfig } from "remotion";
import { Scene1Intro } from "./Scene1Intro";
import { Scene2LoginScreencast } from "./Scene2LoginScreencast";
import { Scene3DashboardScreencast } from "./Scene3DashboardScreencast";
import { Scene5CourseActiveScreencast } from "./Scene5CourseActiveScreencast";
import { Scene6Outro } from "./Scene6Outro";

export const V01_DURATIONS = {
  scene1: 120, // 4.0s Intro Cover
  scene2: 210, // 7.0s Login Screencast + Action Reaction
  scene3: 180, // 6.0s Dashboard Nav + Modal Gabung Input Code
  scene4: 140, // 4.67s Active Course Detail (Video & Forum)
  scene5: 90,  // 3.0s Outro Cover
};

// Snappy 6-frame cut between Intro cover and Screencast, and Screencast to Outro
export const INTRO_TRANSITION = 6;
export const OUTRO_TRANSITION = 6;

export const TOTAL_V01_FRAMES =
  V01_DURATIONS.scene1 +
  V01_DURATIONS.scene2 +
  V01_DURATIONS.scene3 +
  V01_DURATIONS.scene4 +
  V01_DURATIONS.scene5 -
  INTRO_TRANSITION -
  OUTRO_TRANSITION; // 728 frames (~24.3 detik)

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

        {/* Snappy 6-frame quick cut to screencast */}
        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: INTRO_TRANSITION })}
        />

        {/* Scene 2: Real Login Screencast */}
        <TransitionSeries.Sequence
          name="02 - Rekaman Layar Login"
          durationInFrames={V01_DURATIONS.scene2}
        >
          <Scene2LoginScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Scene 3 starts directly on loaded Dashboard */}
        <TransitionSeries.Sequence
          name="03 - Navigasi Dashboard & Modal Gabung"
          durationInFrames={V01_DURATIONS.scene3}
        >
          <Scene3DashboardScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Direct transition into the activated course page */}
        <TransitionSeries.Sequence
          name="04 - Kelas Aktif & Eksplorasi"
          durationInFrames={V01_DURATIONS.scene4}
        >
          <Scene5CourseActiveScreencast />
        </TransitionSeries.Sequence>

        {/* Snappy 6-frame quick cut to Outro */}
        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: OUTRO_TRANSITION })}
        />

        {/* Scene 5: Professional Outro Cover */}
        <TransitionSeries.Sequence
          name="05 - Penutup Panduan"
          durationInFrames={V01_DURATIONS.scene5}
        >
          <Scene6Outro />
        </TransitionSeries.Sequence>
      </TransitionSeries>

      <ProgressBar />
    </div>
  );
};
