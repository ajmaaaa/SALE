import "./index.css";
import React from "react";
import { Composition, Folder } from "remotion";
import { Scene1Intro as V00Scene1 } from "./videos/video_00/Scene1Intro";
import { Scene2FourRoles as V00Scene2 } from "./videos/video_00/Scene2FourRoles";
import { Scene3ObeFramework as V00Scene3 } from "./videos/video_00/Scene3ObeFramework";
import { Scene4ModernFeatures as V00Scene4 } from "./videos/video_00/Scene4ModernFeatures";
import { Scene5Outro as V00Scene5 } from "./videos/video_00/Scene5Outro";
import {
  SCENE_DURATIONS as V00_SCENE_DURATIONS,
  TOTAL_VIDEO_00_FRAMES,
  Video00,
} from "./videos/video_00/Video00";

import { Scene1Intro as V01Scene1 } from "./videos/video_01/Scene1Intro";
import { Scene2LoginScreencast as V01Scene2 } from "./videos/video_01/Scene2LoginScreencast";
import { Scene3DashboardScreencast as V01Scene3 } from "./videos/video_01/Scene3DashboardScreencast";
import { Scene5CourseActiveScreencast as V01Scene4 } from "./videos/video_01/Scene5CourseActiveScreencast";
import { Scene6Outro as V01Scene5 } from "./videos/video_01/Scene6Outro";
import {
  TOTAL_V01_FRAMES,
  V01_DURATIONS,
  Video01MahasiswaJoin,
} from "./videos/video_01/Video01MahasiswaJoin";

import { Scene1Intro as V02Scene1 } from "./videos/video_02/Scene1Intro";
import { Scene2LoginJoinScreencast as V02Scene2 } from "./videos/video_02/Scene2LoginJoinScreencast";
import { Scene3CourseForumScreencast as V02Scene3 } from "./videos/video_02/Scene3CourseForumScreencast";
import { Scene4AssessmentScreencast as V02Scene4 } from "./videos/video_02/Scene4AssessmentScreencast";
import { Scene5CodingAIScreencast as V02Scene5 } from "./videos/video_02/Scene5CodingAIScreencast";
import { Scene6NilaiNotifikasiScreencast as V02Scene6 } from "./videos/video_02/Scene6NilaiNotifikasiScreencast";
import { Scene7Outro as V02Scene7 } from "./videos/video_02/Scene7Outro";
import {
  TOTAL_V02_FRAMES,
  V02_DURATIONS,
  Video02MahasiswaPanduan,
} from "./videos/video_02/Video02MahasiswaPanduan";

export const RemotionRoot: React.FC = () => {
  return (
    <>

      {/* Master Video 02: Panduan Lengkap Mahasiswa */}
      <Composition
        id="Master-02-Panduan-Mahasiswa"
        component={Video02MahasiswaPanduan}
        durationInFrames={TOTAL_V02_FRAMES}
        fps={30}
        width={1920}
        height={1080}
      />

      <Folder name="Video-02-Scenes">
        <Composition
          id="Video-02-Scene-1-Intro"
          component={V02Scene1}
          durationInFrames={V02_DURATIONS.scene1}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-02-Scene-2-Login-Join"
          component={V02Scene2}
          durationInFrames={V02_DURATIONS.scene2}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-02-Scene-3-Course-Forum"
          component={V02Scene3}
          durationInFrames={V02_DURATIONS.scene3}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-02-Scene-4-Assessment"
          component={V02Scene4}
          durationInFrames={V02_DURATIONS.scene4}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-02-Scene-5-Coding-AI"
          component={V02Scene5}
          durationInFrames={V02_DURATIONS.scene5}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-02-Scene-6-Nilai-Profil"
          component={V02Scene6}
          durationInFrames={V02_DURATIONS.scene6}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-02-Scene-7-Outro"
          component={V02Scene7}
          durationInFrames={V02_DURATIONS.scene7}
          fps={30}
          width={1920}
          height={1080}
        />
      </Folder>

      {/* Video 01: Mahasiswa Join Kelas (Screen Recording + Dynamic Cursor + Zoom) */}
      <Composition
        id="Video-01-Mahasiswa-Join-Kelas"
        component={Video01MahasiswaJoin}
        durationInFrames={TOTAL_V01_FRAMES}
        fps={30}
        width={1920}
        height={1080}
      />

      <Folder name="Video-01-Scenes">
        <Composition
          id="Video-01-Scene-1-Intro"
          component={V01Scene1}
          durationInFrames={V01_DURATIONS.scene1}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-01-Scene-2-Login-Screencast"
          component={V01Scene2}
          durationInFrames={V01_DURATIONS.scene2}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-01-Scene-3-Dashboard-Screencast"
          component={V01Scene3}
          durationInFrames={V01_DURATIONS.scene3}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-01-Scene-4-Course-Active"
          component={V01Scene4}
          durationInFrames={V01_DURATIONS.scene4}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-01-Scene-5-Outro"
          component={V01Scene5}
          durationInFrames={V01_DURATIONS.scene5}
          fps={30}
          width={1920}
          height={1080}
        />
      </Folder>

      {/* Master Video 01: Pengenalan & Arsitektur Ekosistem SALE */}
      <Composition
        id="Master-01-Pengenalan-SALE"
        component={Video00}
        durationInFrames={TOTAL_VIDEO_00_FRAMES}
        fps={30}
        width={1920}
        height={1080}
      />
      <Composition
        id="Video-01-Pengenalan-SALE"
        component={Video00}
        durationInFrames={TOTAL_VIDEO_00_FRAMES}
        fps={30}
        width={1920}
        height={1080}
      />
      <Composition
        id="Video-00-Pengenalan-SALE"
        component={Video00}
        durationInFrames={TOTAL_VIDEO_00_FRAMES}
        fps={30}
        width={1920}
        height={1080}
      />

      <Folder name="Video-00-Scenes">
        <Composition
          id="Video-00-Scene-1-Intro"
          component={V00Scene1}
          durationInFrames={V00_SCENE_DURATIONS.scene1}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-2-Roles"
          component={V00Scene2}
          durationInFrames={V00_SCENE_DURATIONS.scene2}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-3-OBE"
          component={V00Scene3}
          durationInFrames={V00_SCENE_DURATIONS.scene3}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-4-Features"
          component={V00Scene4}
          durationInFrames={V00_SCENE_DURATIONS.scene4}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-5-Outro"
          component={V00Scene5}
          durationInFrames={V00_SCENE_DURATIONS.scene5}
          fps={30}
          width={1920}
          height={1080}
        />
      </Folder>
    </>
  );
};
