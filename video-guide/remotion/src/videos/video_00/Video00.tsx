import React from "react";
import { linearTiming, TransitionSeries } from "@remotion/transitions";
import { fade } from "@remotion/transitions/fade";
import { slide } from "@remotion/transitions/slide";
import { Audio, interpolate, staticFile, useCurrentFrame, useVideoConfig } from "remotion";
import { Scene1Intro } from "./Scene1Intro";
import { Scene2FourRoles } from "./Scene2FourRoles";
import { Scene3ObeFramework } from "./Scene3ObeFramework";
import { Scene4ModernFeatures } from "./Scene4ModernFeatures";
import { Scene5Outro } from "./Scene5Outro";

export const SCENE_DURATIONS = {
  scene1: 912, // 30.4s (VO 0.0s - 30.2s)
  scene2: 1138, // 37.93s (VO 30.2s - 67.7s)
  scene3: 1103, // 36.77s (VO 67.7s - 104.1s)
  scene4: 756, // 25.2s (VO 104.1s - 128.9s)
  scene5: 669, // 22.3s (VO 128.9s - 150.56s + graceful outro tail)
};

export const TRANSITION_DURATION = 12; // 0.4s transition centered within silence pauses

export const TOTAL_VIDEO_00_FRAMES =
  SCENE_DURATIONS.scene1 +
  SCENE_DURATIONS.scene2 +
  SCENE_DURATIONS.scene3 +
  SCENE_DURATIONS.scene4 +
  SCENE_DURATIONS.scene5 -
  4 * TRANSITION_DURATION; // 4530 frames (~151.0 detik / 02:31 menit)

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
        className="h-full bg-white/70 shadow-sm"
      />
    </div>
  );
};

export const Video00: React.FC = () => {
  return (
    <div className="relative w-full h-full bg-[#102f50]">
      {/* Primary Voiceover Audio Track (Continuous 150.56s sync) */}
      <Audio src={staticFile("vo/vo1.mp3")} volume={1} />

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
          presentation={slide({ direction: "from-right" })}
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
          presentation={slide({ direction: "from-right" })}
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
