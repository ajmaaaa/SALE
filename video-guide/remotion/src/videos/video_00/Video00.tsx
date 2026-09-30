import React from "react";
import { linearTiming, TransitionSeries } from "@remotion/transitions";
import { fade } from "@remotion/transitions/fade";
import { interpolate, useCurrentFrame, useVideoConfig } from "remotion";
import { Scene1Intro } from "./Scene1Intro";
import { Scene2FourRoles } from "./Scene2FourRoles";
import { Scene3ObeFramework } from "./Scene3ObeFramework";
import { Scene4ModernFeatures } from "./Scene4ModernFeatures";
import { Scene5Outro } from "./Scene5Outro";

export const SCENE_DURATIONS = {
  scene1: 180,
  scene2: 240,
  scene3: 240,
  scene4: 240,
  scene5: 180,
};

export const TRANSITION_DURATION = 20;

export const TOTAL_VIDEO_00_FRAMES =
  SCENE_DURATIONS.scene1 +
  SCENE_DURATIONS.scene2 +
  SCENE_DURATIONS.scene3 +
  SCENE_DURATIONS.scene4 +
  SCENE_DURATIONS.scene5 -
  4 * TRANSITION_DURATION; // 1000 frames (~33.3 detik)

const ProgressBar: React.FC = () => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();

  const progress = interpolate(frame, [0, durationInFrames], [0, 100], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div className="absolute bottom-0 left-0 right-0 h-1.5 bg-black/20 z-50">
      <div
        style={{ width: `${progress}%` }}
        className="h-full bg-cyan-400 shadow-sm transition-none"
      />
    </div>
  );
};

export const Video00: React.FC = () => {
  return (
    <div className="relative w-full h-full bg-[#102f50]">
      <TransitionSeries>
        <TransitionSeries.Sequence
          name="01 - Intro & Visi"
          durationInFrames={SCENE_DURATIONS.scene1}
        >
          <Scene1Intro />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: TRANSITION_DURATION })}
        />

        <TransitionSeries.Sequence
          name="02 - 4 Peran Pengguna"
          durationInFrames={SCENE_DURATIONS.scene2}
        >
          <Scene2FourRoles />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: TRANSITION_DURATION })}
        />

        <TransitionSeries.Sequence
          name="03 - Framework OBE"
          durationInFrames={SCENE_DURATIONS.scene3}
        >
          <Scene3ObeFramework />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: TRANSITION_DURATION })}
        />

        <TransitionSeries.Sequence
          name="04 - Fitur Cerdas Modern"
          durationInFrames={SCENE_DURATIONS.scene4}
        >
          <Scene4ModernFeatures />
        </TransitionSeries.Sequence>

        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: TRANSITION_DURATION })}
        />

        <TransitionSeries.Sequence
          name="05 - Outro & Penutup"
          durationInFrames={SCENE_DURATIONS.scene5}
        >
          <Scene5Outro />
        </TransitionSeries.Sequence>
      </TransitionSeries>

      <ProgressBar />
    </div>
  );
};
