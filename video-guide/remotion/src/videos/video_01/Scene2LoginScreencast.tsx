import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  InstructionOverlay,
} from "../../components/ScreencastEngine";

export const Scene2LoginScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // =====================================================================
  // TIMING: 900 frames = 30 detik
  // Alur: Hover → Klik NIM → Tik NIM → Hover Password → Klik → Tik → Masuk → Dashboard load → Eksplorasi
  // =====================================================================
  const HOVER_NIM_START      =  20;  // Kursor mulai bergerak ke kolom NIM
  const CLICK_NIM_FRAME      =  70;  // Klik di kolom NIM
  const TYPE_NIM_START       =  76;  // Mulai ketik NIM
  const TYPE_NIM_END         = 165;  // Selesai ketik NIM (lambat + realistis)
  const MOVE_TO_PASS_START   = 180;  // Mulai gerak ke kolom password
  const CLICK_PASS_FRAME     = 230;  // Klik di kolom password
  const TYPE_PASS_START      = 238;  // Mulai ketik password
  const TYPE_PASS_END        = 320;  // Selesai ketik password
  const MOVE_TO_SUBMIT       = 340;  // Mulai gerak ke tombol Masuk
  const CLICK_SUBMIT_FRAME   = 400;  // Klik Masuk
  const LOAD_DASHBOARD_FRAME = 408;  // 8 frames pasca klik = dashboard muncul (action-reaction)
  const EXPLORE_START        = 420;  // Kursor mulai eksplorasi dashboard

  let cursorX = 1350;
  let cursorY = 380;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < HOVER_NIM_START) {
    // Diam di awal — kursor di kanan atas, belum bergerak
    cursorX = 1350;
    cursorY = 380;
  } else if (frame >= HOVER_NIM_START && frame < CLICK_NIM_FRAME) {
    // Bergerak menuju input NIM (960, 577)
    cursorX = interpolate(frame, [HOVER_NIM_START, CLICK_NIM_FRAME - 2], [1350, 960], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [HOVER_NIM_START, CLICK_NIM_FRAME - 2], [380, 577], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_NIM_FRAME && frame < MOVE_TO_PASS_START) {
    // Diam di input NIM, klik
    cursorX = 960;
    cursorY = 577;
    if (frame >= CLICK_NIM_FRAME && frame <= CLICK_NIM_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_NIM_FRAME;
    }
  } else if (frame >= MOVE_TO_PASS_START && frame < CLICK_PASS_FRAME) {
    // Bergerak ke input password (960, 666)
    cursorX = 960;
    cursorY = interpolate(frame, [MOVE_TO_PASS_START, CLICK_PASS_FRAME - 2], [577, 666], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_PASS_FRAME && frame < MOVE_TO_SUBMIT) {
    // Diam di input password, klik
    cursorX = 960;
    cursorY = 666;
    if (frame >= CLICK_PASS_FRAME && frame <= CLICK_PASS_FRAME + 4) {
      isClicking = true;
      clickFrame = CLICK_PASS_FRAME;
    }
  } else if (frame >= MOVE_TO_SUBMIT && frame < CLICK_SUBMIT_FRAME) {
    // Bergerak ke tombol Masuk (960, 730)
    cursorX = 960;
    cursorY = interpolate(frame, [MOVE_TO_SUBMIT, CLICK_SUBMIT_FRAME - 2], [666, 730], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_SUBMIT_FRAME && frame < LOAD_DASHBOARD_FRAME + 8) {
    // Klik Masuk
    cursorX = 960;
    cursorY = 730;
    if (frame >= CLICK_SUBMIT_FRAME && frame <= CLICK_SUBMIT_FRAME + 5) {
      isClicking = true;
      clickFrame = CLICK_SUBMIT_FRAME;
    }
  } else if (frame >= LOAD_DASHBOARD_FRAME && frame < EXPLORE_START) {
    // Dashboard sudah load — kursor masih di area bawah (loading transition)
    cursorX = 960;
    cursorY = 730;
  } else {
    // Eksplorasi dashboard: kursor bergerak natural melihat-lihat konten
    const relFrame = frame - EXPLORE_START;
    if (relFrame < 80) {
      // Bergerak ke area header/sambutan dashboard (600, 180)
      cursorX = interpolate(relFrame, [0, 75], [960, 600], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(relFrame, [0, 75], [730, 180], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (relFrame < 180) {
      // Hover area konten center — berhenti dan baca
      cursorX = 600;
      cursorY = 180;
    } else if (relFrame < 280) {
      // Bergerak ke card info semester/kelas di tengah (800, 380)
      cursorX = interpolate(relFrame, [180, 275], [600, 800], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(relFrame, [180, 275], [180, 380], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (relFrame < 380) {
      // Hover di area card semester, membaca
      cursorX = interpolate(relFrame, [280, 375], [800, 960], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(relFrame, [280, 375], [380, 420], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (relFrame < 520) {
      // Bergerak ke sidebar navigasi kiri — melihat menu-menu (124, 200)
      cursorX = interpolate(relFrame, [380, 515], [960, 124], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(relFrame, [380, 515], [420, 200], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (relFrame < 650) {
      // Hover di sidebar, melihat menu Course dan menu lain
      cursorX = 124;
      cursorY = interpolate(relFrame, [520, 645], [200, 280], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else if (relFrame < 780) {
      // Kembali ke area tengah / konten utama
      cursorX = interpolate(relFrame, [650, 775], [124, 700], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
      cursorY = interpolate(relFrame, [650, 775], [280, 350], {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      });
    } else {
      // Berhenti di tengah — menunggu scene berikutnya (siap ke Course)
      cursorX = 700;
      cursorY = 350;
    }
  }

  // =====================================================================
  // TYPING SIMULATION — Lambat dan realistis
  // NIM: "231011401234" (12 karakter, diketik perlahan selama 89 frames)
  // =====================================================================
  const fullNim = "231011401234";
  const nimCharsTyped = Math.floor(
    interpolate(frame, [TYPE_NIM_START, TYPE_NIM_END], [0, fullNim.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentNim = fullNim.slice(0, nimCharsTyped);

  // Password: 8 dots, ketik selama 82 frames
  const fullPass = "••••••••";
  const passCharsTyped = Math.floor(
    interpolate(frame, [TYPE_PASS_START, TYPE_PASS_END], [0, fullPass.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const currentPass = fullPass.slice(0, passCharsTyped);

  const isBlinking = Math.floor(frame / 10) % 2 === 0;
  const isDashboardLoaded = frame >= LOAD_DASHBOARD_FRAME;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#0e2740] overflow-hidden select-none">
      {/*
        AUTHENTIC ACTION-REACTION SCREEN SWAP:
        Before frame LOAD_DASHBOARD_FRAME: Login Screen
        After frame LOAD_DASHBOARD_FRAME: Mahasiswa Dashboard Screen (instant hard cut)
      */}
      {!isDashboardLoaded ? (
        <>
          <Img
            src={staticFile("screens/01_login_blank.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />

          {/* Clean Typed NIM inside original input without artificial border */}
          {frame >= TYPE_NIM_START - 2 && (
            <div
              style={{
                position: "absolute",
                left: 772,
                top: 559,
                width: 376,
                height: 36,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 10,
                borderRadius: 4,
                fontFamily: "monospace",
                fontSize: 14,
                color: "#0f172a",
                fontWeight: 600,
              }}
            >
              <span>{currentNim}</span>
              {frame < MOVE_TO_PASS_START && isBlinking && (
                <span style={{ color: "#2563eb", fontWeight: 700, marginLeft: 2 }}>|</span>
              )}
            </div>
          )}

          {/* Clean Typed Password inside original input */}
          {frame >= TYPE_PASS_START - 2 && (
            <div
              style={{
                position: "absolute",
                left: 772,
                top: 648,
                width: 376,
                height: 36,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 10,
                borderRadius: 4,
                fontSize: 18,
                letterSpacing: 3,
                color: "#0f172a",
              }}
            >
              <span>{currentPass}</span>
              {frame < MOVE_TO_SUBMIT && isBlinking && (
                <span style={{ color: "#2563eb", fontSize: 14, letterSpacing: 0, marginLeft: 2 }}>|</span>
              )}
            </div>
          )}
        </>
      ) : (
        /* Instant Action-Reaction: Real Dashboard Loaded (hard cut, no crossfade) */
        <Img
          src={staticFile("screens/03_mahasiswa_dashboard.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      )}

      {/* Animated Cursor */}
      <AnimatedCursor
        x={cursorX}
        y={cursorY}
        isClicking={isClicking}
        clickFrame={clickFrame}
      />

      {/* Instruction Overlay — bottom-left selama login, hilang saat dashboard tampil */}
      {!isDashboardLoaded && (
        <InstructionOverlay
          step="01"
          actionText="Masuk ke Sistem SALE"
          detailText="Ketikkan NIM pada kolom identitas dan masukkan kata sandi Anda, lalu klik tombol Masuk"
          position="bottom-left"
        />
      )}
    </div>
  );
};
