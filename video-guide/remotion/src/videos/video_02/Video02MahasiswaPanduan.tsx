import React from "react";
import { linearTiming, TransitionSeries } from "@remotion/transitions";
import { fade } from "@remotion/transitions/fade";
import { Audio, interpolate, staticFile, useCurrentFrame, useVideoConfig } from "remotion";
import { Scene1Intro } from "./Scene1Intro";
import { Scene2LoginJoinScreencast } from "./Scene2LoginJoinScreencast";
import { Scene3CourseForumScreencast } from "./Scene3CourseForumScreencast";
import { Scene4AssessmentScreencast } from "./Scene4AssessmentScreencast";
import { Scene5CodingAIScreencast } from "./Scene5CodingAIScreencast";
import { Scene6NilaiNotifikasiScreencast } from "./Scene6NilaiNotifikasiScreencast";
import { Scene7Outro } from "./Scene7Outro";

/**
 * Master Video 02 — Panduan Lengkap Mahasiswa
 * Cakupan: 5 BAB (Login, Dashboard/Forum, Tugas/Kuis, Coding AI, Nilai/Notifikasi/Profil)
 * Total durasi: 9360 frames (312 detik / 05:12) — Full sync dengan vo2.mp3
 *
 * Frame durations (fps 30):
 *   Scene 1  : 1105 frames / 36.83s — Intro Cover & Roadmap 5 BAB
 *   Scene 2  : 1896 frames / 63.20s — BAB 01 Login + Gabung Kelas
 *   Scene 3  : 1380 frames / 46.00s — BAB 02 Dashboard, Ruang Kelas, Forum
 *   Scene 4  : 1630 frames / 54.33s — BAB 03 Tugas Mandiri + Kuis Berwaktu
 *   Scene 5  : 1640 frames / 54.67s — BAB 04 Praktikum Coding AI
 *   Scene 6  : 1544 frames / 51.47s — BAB 05 Nilai, Notifikasi, Profil
 *   Scene 7  : 177  frames / 5.90s  — Outro Cover & Closing CTA
 *
 * Transitions:
 *   - Intro → Scene 2: 6-frame fade (diizinkan SOP pasal 2.1)
 *   - Antar screencast: seamless hard cut (anti-ghosting)
 *   - Scene 6 → Outro: 6-frame fade
 */
export const V02_DURATIONS = {
  scene1: 1105, // 0.0s - 36.83s (VO Seg 00 - 06)
  scene2: 1896, // 36.63s - 99.83s (VO Seg 07 - 22)
  scene3: 1380, // 99.83s - 145.83s (VO Seg 23 - 35)
  scene4: 1630, // 145.83s - 200.17s (VO Seg 36 - 52)
  scene5: 1640, // 200.17s - 254.83s (VO Seg 53 - 66)
  scene6: 1544, // 254.83s - 306.30s (VO Seg 67 - 80)
  scene7: 177,  // 306.10s - 312.00s (VO Seg 81 - 82 + graceful outro)
};

// 6-frame fade transitions (intro → screencast, outro ← screencast)
export const INTRO_TRANSITION = 6;
export const OUTRO_TRANSITION = 6;

export const TOTAL_V02_FRAMES =
  V02_DURATIONS.scene1 +
  V02_DURATIONS.scene2 +
  V02_DURATIONS.scene3 +
  V02_DURATIONS.scene4 +
  V02_DURATIONS.scene5 +
  V02_DURATIONS.scene6 +
  V02_DURATIONS.scene7 -
  INTRO_TRANSITION -
  OUTRO_TRANSITION; // 9360 frames (312.0 detik / 05:12)

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
        className="h-full bg-white/70 shadow-sm"
      />
    </div>
  );
};

export const Video02MahasiswaPanduan: React.FC = () => {
  return (
    <div className="relative w-full h-full bg-[#102f50]">
      {/* Primary Voiceover Audio Track (Continuous 311.84s sync) */}
      <Audio src={staticFile("vo/vo2.mp3")} volume={1} />

      <TransitionSeries
        style={{
          translate: "-5px 0px"
        }}
      >
        {/* Scene 1: Professional Intro Cover */}
        <TransitionSeries.Sequence
          name="01 - Pembuka Master Video 02"
          durationInFrames={V02_DURATIONS.scene1}
        >
          <Scene1Intro />
        </TransitionSeries.Sequence>

        {/* 6-frame fade: Cover → Screencast (diizinkan SOP pasal 2.1) */}
        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: INTRO_TRANSITION })}
        />

        {/* Scene 2: Autentikasi + Gabung Kelas (Hard cut antar screencast) */}
        <TransitionSeries.Sequence
          name="02 - Autentikasi dan Gabung Kelas"
          durationInFrames={V02_DURATIONS.scene2}
        >
          <Scene2LoginJoinScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: No transition — direct screencast-to-screencast */}
        <TransitionSeries.Sequence
          name="03 - Ruang Belajar dan Forum Diskusi"
          durationInFrames={V02_DURATIONS.scene3}
        >
          <Scene3CourseForumScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Tugas dan Kuis */}
        <TransitionSeries.Sequence
          name="04 - Penugasan Mandiri dan Ujian"
          durationInFrames={V02_DURATIONS.scene4}
        >
          <Scene4AssessmentScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Coding AI */}
        <TransitionSeries.Sequence
          name="05 - Praktikum Coding dan Asisten AI"
          durationInFrames={V02_DURATIONS.scene5}
        >
          <Scene5CodingAIScreencast />
        </TransitionSeries.Sequence>

        {/* Seamless Hard Cut: Nilai dan Profil */}
        <TransitionSeries.Sequence
          name="06 - Transkrip Nilai dan Profil"
          durationInFrames={V02_DURATIONS.scene6}
        >
          <Scene6NilaiNotifikasiScreencast />
        </TransitionSeries.Sequence>

        {/* 6-frame fade: Screencast → Outro (diizinkan SOP pasal 2.1) */}
        <TransitionSeries.Transition
          presentation={fade()}
          timing={linearTiming({ durationInFrames: OUTRO_TRANSITION })}
        />

        {/* Scene 7: Professional Outro Cover */}
        <TransitionSeries.Sequence
          name="07 - Penutup Master Video 02"
          durationInFrames={V02_DURATIONS.scene7}
        >
          <Scene7Outro />
        </TransitionSeries.Sequence>

      </TransitionSeries>

      <ProgressBar />
    </div>
  );
};
