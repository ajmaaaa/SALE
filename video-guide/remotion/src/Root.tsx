import "./index.css";
import React from "react";
import { Composition, Folder } from "remotion";
import { Scene1Intro } from "./videos/video_00/Scene1Intro";
import { Scene2FourRoles } from "./videos/video_00/Scene2FourRoles";
import { Scene3ObeFramework } from "./videos/video_00/Scene3ObeFramework";
import { Scene4ModernFeatures } from "./videos/video_00/Scene4ModernFeatures";
import { Scene5Outro } from "./videos/video_00/Scene5Outro";
import {
  SCENE_DURATIONS,
  TOTAL_VIDEO_00_FRAMES,
  Video00,
} from "./videos/video_00/Video00";

export const RemotionRoot: React.FC = () => {
  return (
    <>
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
          component={Scene1Intro}
          durationInFrames={SCENE_DURATIONS.scene1}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-2-Roles"
          component={Scene2FourRoles}
          durationInFrames={SCENE_DURATIONS.scene2}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-3-OBE"
          component={Scene3ObeFramework}
          durationInFrames={SCENE_DURATIONS.scene3}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-4-Features"
          component={Scene4ModernFeatures}
          durationInFrames={SCENE_DURATIONS.scene4}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Video-00-Scene-5-Outro"
          component={Scene5Outro}
          durationInFrames={SCENE_DURATIONS.scene5}
          fps={30}
          width={1920}
          height={1080}
        />
      </Folder>
    </>
  );
};
