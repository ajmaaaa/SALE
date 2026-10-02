import React from "react";
import { Audio, staticFile } from "remotion";
import { linearTiming, TransitionSeries } from "@remotion/transitions";
import { fade } from "@remotion/transitions/fade";
import { interpolate, useCurrentFrame, useVideoConfig } from "remotion";
import { Scene1Intro } from "./Scene1Intro";
import { Scene2LoginScreencast } from "./Scene2LoginScreencast";
import { Scene3DashboardScreencast } from "./Scene3DashboardScreencast";
import { Scene5CourseActiveScreencast } from "./Scene5CourseActiveScreencast";
import { Scene6Outro } from "./Scene6Outro";

// ============================================================
// TIMING ARCHITECTURE — SYNCHRONIZED WITH vo1.mp3
// vo1.mp3 duration: 150.56 seconds = 4517 frames @ 30fps
// Total target: 4530 frames (150 detik + 0.43 detik buffer outro)
// ============================================================

export const V01_DURATIONS = {
  scene1:  180, //  6.0s  Intro Cover (branding + judul + VO intro)
  scene2: 1300, // 43.3s  Login Screencast + typing + klik Masuk + eksplorasi dashboard
  scene3: 1450, // 48.3s  Dashboard → Course Nav → Modal Gabung → ketik kode → submit + tunggu
  scene4:  960, // 32.0s  Kelas Aktif + eksplorasi silabus, video, forum, lampiran
  scene5:  642, // 21.4s  Outro Cover (penutup + next episode info)
};

// 6-frame quick-cut fade transitions
export const INTRO_TRANSITION = 6;
export const OUTRO_TRANSITION = 6;

// Total: 180 + 1300 + 1450 + 960 + 642 - 6 - 6 = 4520 frames = 150.67 detik @ 30fps
// vo1.mp3 duration: 150.56 detik = 4516.8 frames — match sempurna
export const TOTAL_V01_FRAMES =
  V01_DURATIONS.scene1 +
  V01_DURATIONS.scene2 +
  V01_DURATIONS.scene3 +
  V01_DURATIONS.scene4 +
  V01_DURATIONS.scene5 -
  INTRO_TRANSITION -
  OUTRO_TRANSITION;

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

      {/* VO1 Voice Over — synchronized from frame 0 */}
      <Audio src={staticFile("vo/vo1.mp3")} startFrom={0} />

      <TransitionSeries>
        {/* Scene 1: Professional Intro Cover (6s) */}
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

        {/* Scene 2: Real Login Screencast (30s) */}
        <TransitionSeries.Sequence
          name="02 - Rekaman Layar Login"
          durationInFrames={V01_DURATIONS.scene2}
        >
          <Scene2LoginScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Dashboard → Course → Modal Gabung (35s) */}
        <TransitionSeries.Sequence
          name="03 - Navigasi Dashboard & Modal Gabung"
          durationInFrames={V01_DURATIONS.scene3}
        >
          <Scene3DashboardScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Kelas aktif + eksplorasi konten (30s) */}
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

        {/* Scene 5: Professional Outro Cover (21s) */}
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
