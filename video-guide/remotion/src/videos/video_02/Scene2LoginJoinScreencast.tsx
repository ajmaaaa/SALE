import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  ScreencastSubtitles,
} from "../../components/ScreencastEngine";
import { ChapterBumper } from "../../components/ChapterBumper";

/**
 * BAB 01 — Autentikasi Akun & Bergabung ke Kelas
 * Durasi: 1896 frames (63.20 detik) — Full sync dengan VO2 Seg 07 - 22
 *
 * Skenario Layar:
 * 1. 01_login_blank.png (0 - 480f) : Login, ketik NIM, ketik Password, klik Masuk
 * 2. 03_mahasiswa_dashboard.png (480 - 850f) : Dashboard mahasiswa, eksplorasi, klik Course nav
 * 3. 04_mahasiswa_course.png (850 - 1010f) : Daftar Course, klik + Gabung Kelas
 * 4. 14_modal_gabung_kelas.png (1010 - 1265f) : Modal kode akses, ketik IF204-A, klik Gabung
 * 5. 13_mahasiswa_join_confirm.png (1265 - 1735f) : Opsi QR code fisik, klik konfirmasi
 * 6. 15_mahasiswa_course_detail.png (1735 - 1896f) : Ruang kelas aktif siap digunakan
 */
export const Scene2LoginJoinScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // === KEY MILESTONES (Local frame based on VO Seg 07-22) ===
  const CLICK_NIM_FRAME = 200;
  const TYPE_NIM_START = 206;
  const TYPE_NIM_END = 260;

  const CLICK_PASS_FRAME = 280;
  const TYPE_PASS_START = 290;
  const TYPE_PASS_END = 360;

  const CLICK_SUBMIT_LOGIN = 470;
  const LOAD_DASHBOARD = 480;

  const CLICK_COURSE_NAV = 840;
  const LOAD_COURSE = 850;

  const CLICK_JOIN_BTN = 1000;
  const LOAD_MODAL = 1010;

  const CLICK_CODE_INPUT = 1050;
  const TYPE_CODE_START = 1060;
  const TYPE_CODE_END = 1140;

  const CLICK_MODAL_SUBMIT = 1250;
  const LOAD_JOIN_CONFIRM = 1265;

  const CLICK_QR_CONFIRM = 1720;
  const LOAD_COURSE_ACTIVE = 1735;

  // === CURSOR COORDINATE & CLICK INTERPOLATION ===
  let cursorX = 1350;
  let cursorY = 380;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < CLICK_NIM_FRAME - 30) {
    cursorX = 1350;
    cursorY = 380;
  } else if (frame < CLICK_NIM_FRAME) {
    // Bergerak ke kolom NIM (960, 577)
    cursorX = interpolate(frame, [CLICK_NIM_FRAME - 30, CLICK_NIM_FRAME - 2], [1350, 960], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [CLICK_NIM_FRAME - 30, CLICK_NIM_FRAME - 2], [380, 577], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_NIM_FRAME && frame < CLICK_PASS_FRAME - 20) {
    cursorX = 960;
    cursorY = 577;
    if (frame <= CLICK_NIM_FRAME + 5) {
      isClicking = true;
      clickFrame = CLICK_NIM_FRAME;
    }
  } else if (frame < CLICK_PASS_FRAME) {
    // Bergerak ke kolom Password (960, 666)
    cursorX = 960;
    cursorY = interpolate(frame, [CLICK_PASS_FRAME - 20, CLICK_PASS_FRAME - 2], [577, 666], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_PASS_FRAME && frame < CLICK_SUBMIT_LOGIN - 30) {
    cursorX = 960;
    cursorY = 666;
    if (frame <= CLICK_PASS_FRAME + 5) {
      isClicking = true;
      clickFrame = CLICK_PASS_FRAME;
    }
  } else if (frame < CLICK_SUBMIT_LOGIN) {
    // Bergerak ke tombol Masuk (960, 730)
    cursorX = 960;
    cursorY = interpolate(frame, [CLICK_SUBMIT_LOGIN - 30, CLICK_SUBMIT_LOGIN - 2], [666, 730], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_SUBMIT_LOGIN && frame < LOAD_DASHBOARD + 30) {
    cursorX = 960;
    cursorY = 730;
    if (frame <= CLICK_SUBMIT_LOGIN + 6) {
      isClicking = true;
      clickFrame = CLICK_SUBMIT_LOGIN;
    }
  } else if (frame < 760) {
    // Meninjau beranda dashboard secara tenang tanpa gerakan kursor acak
    cursorX = 450;
    cursorY = 220;
  } else if (frame < CLICK_COURSE_NAV) {
    // Bergerak langsung dan mulus ke menu Course di navigasi kiri (124, 193)
    cursorX = interpolate(frame, [760, CLICK_COURSE_NAV - 2], [450, 124], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [760, CLICK_COURSE_NAV - 2], [220, 193], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < LOAD_COURSE + 30) {
    cursorX = 124;
    cursorY = 193;
    if (frame >= CLICK_COURSE_NAV && frame < CLICK_COURSE_NAV + 16) {
      clickFrame = CLICK_COURSE_NAV;
      if (frame <= CLICK_COURSE_NAV + 5) isClicking = true;
    }
  } else if (frame < CLICK_JOIN_BTN - 30) {
    // Mengamati daftar kelas
    cursorX = interpolate(frame, [LOAD_COURSE + 30, 950], [124, 700], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_COURSE + 30, 950], [193, 380], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < CLICK_JOIN_BTN) {
    // Bergerak ke tombol + Gabung Kelas di kanan atas (1802, 127)
    cursorX = interpolate(frame, [CLICK_JOIN_BTN - 30, CLICK_JOIN_BTN - 2], [700, 1802], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [CLICK_JOIN_BTN - 30, CLICK_JOIN_BTN - 2], [380, 127], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_JOIN_BTN && frame < LOAD_MODAL + 20) {
    cursorX = 1802;
    cursorY = 127;
    if (frame <= CLICK_JOIN_BTN + 5) {
      isClicking = true;
      clickFrame = CLICK_JOIN_BTN;
    }
  } else if (frame < CLICK_CODE_INPUT) {
    // Bergerak ke input modal kode akses (952, 531)
    cursorX = interpolate(frame, [LOAD_MODAL + 20, CLICK_CODE_INPUT - 2], [1802, 952], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_MODAL + 20, CLICK_CODE_INPUT - 2], [127, 531], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_CODE_INPUT && frame < CLICK_MODAL_SUBMIT - 30) {
    cursorX = 952;
    cursorY = 531;
    if (frame <= CLICK_CODE_INPUT + 5) {
      isClicking = true;
      clickFrame = CLICK_CODE_INPUT;
    }
  } else if (frame < CLICK_MODAL_SUBMIT) {
    // Bergerak ke tombol submit modal (1099, 641)
    cursorX = interpolate(frame, [CLICK_MODAL_SUBMIT - 30, CLICK_MODAL_SUBMIT - 2], [952, 1099], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [CLICK_MODAL_SUBMIT - 30, CLICK_MODAL_SUBMIT - 2], [531, 641], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame >= CLICK_MODAL_SUBMIT && frame < LOAD_JOIN_CONFIRM + 30) {
    cursorX = 1099;
    cursorY = 641;
    if (frame <= CLICK_MODAL_SUBMIT + 5) {
      isClicking = true;
      clickFrame = CLICK_MODAL_SUBMIT;
    }
  } else if (frame < CLICK_QR_CONFIRM - 40) {
    // Mengamati jendela konfirmasi QR code di ruang fisik
    cursorX = interpolate(frame, [LOAD_JOIN_CONFIRM + 30, 1500], [1099, 1076], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_JOIN_CONFIRM + 30, 1500], [641, 563], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < CLICK_QR_CONFIRM) {
    // Bergerak ke tombol Buka Course Saya (1076, 563)
    cursorX = 1076;
    cursorY = 563;
  } else if (frame >= CLICK_QR_CONFIRM && frame < LOAD_COURSE_ACTIVE + 30) {
    cursorX = 1076;
    cursorY = 563;
    if (frame <= CLICK_QR_CONFIRM + 6) {
      isClicking = true;
      clickFrame = CLICK_QR_CONFIRM;
    }
  } else {
    // Eksplorasi ruang kelas aktif
    cursorX = interpolate(frame, [LOAD_COURSE_ACTIVE + 30, 1860], [1080, 600], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LOAD_COURSE_ACTIVE + 30, 1860], [680, 360], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }

  // === TYPING SIMULATION ===
  const fullNim = "231011401234";
  const nimTyped = Math.floor(
    interpolate(frame, [TYPE_NIM_START, TYPE_NIM_END], [0, fullNim.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );

  const fullPass = "••••••••";
  const passTyped = Math.floor(
    interpolate(frame, [TYPE_PASS_START, TYPE_PASS_END], [0, fullPass.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );

  const codeStr = "IF204-A";
  const codeTyped = Math.floor(
    interpolate(frame, [TYPE_CODE_START, TYPE_CODE_END], [0, codeStr.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );

  const isBlinking = Math.floor(frame / 10) % 2 === 0;

  // Screen state conditions
  const showDashboard = frame >= LOAD_DASHBOARD && frame < LOAD_COURSE;
  const showCourse = frame >= LOAD_COURSE && frame < LOAD_MODAL;
  const showModal = frame >= LOAD_MODAL && frame < LOAD_JOIN_CONFIRM;
  const showJoinConfirm = frame >= LOAD_JOIN_CONFIRM && frame < LOAD_COURSE_ACTIVE;
  const showCourseActive = frame >= LOAD_COURSE_ACTIVE;

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#0e2740] overflow-hidden select-none">
      {/* === 100% FULL SCREEN ACTION-REACTION SCREENSHOTS === */}
      {showCourseActive ? (
        <Img
          src={staticFile("screens/15_mahasiswa_course_detail.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : showJoinConfirm ? (
        <Img
          src={staticFile("screens/13_mahasiswa_join_confirm.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : showModal ? (
        <>
          <Img
            src={staticFile("screens/14_modal_gabung_kelas.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />
          {frame >= TYPE_CODE_START && frame < CLICK_MODAL_SUBMIT && (
            <div
              style={{
                position: "absolute",
                left: 752,
                top: 513,
                width: 400,
                height: 36,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 10,
                borderRadius: 4,
                fontFamily: "monospace",
                fontSize: 16,
                fontWeight: 700,
                color: "#0f172a",
                letterSpacing: 2,
              }}
            >
              <span>{codeStr.slice(0, codeTyped)}</span>
              {frame < CLICK_MODAL_SUBMIT && isBlinking && (
                <span style={{ color: "#2563eb", marginLeft: 2 }}>|</span>
              )}
            </div>
          )}
        </>
      ) : showCourse ? (
        <Img
          src={staticFile("screens/04_mahasiswa_course.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover" }}
        />
      ) : showDashboard ? (
        <>
          <Img
            src={staticFile("screens/03_mahasiswa_dashboard.png")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />
          {frame >= CLICK_COURSE_NAV && frame < LOAD_COURSE && (
            <div
              style={{
                position: "absolute",
                left: 12,
                top: 174,
                width: 224,
                height: 40,
                borderRadius: 8,
                backgroundColor: "rgba(24, 52, 78, 0.25)",
                border: "1px solid rgba(24, 52, 78, 0.35)",
                pointerEvents: "none",
              }}
            />
          )}
        </>
      ) : (
        <>
          <Img
            src={staticFile(
              frame >= TYPE_PASS_END
                ? "screens/02_login_typed.png"
                : "screens/01_login_blank.png"
            )}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />
          {/* Typed NIM (during typing only, frame < TYPE_PASS_END) */}
          {frame >= TYPE_NIM_START && frame < TYPE_PASS_END && (
            <div
              style={{
                position: "absolute",
                left: 780,
                top: 558,
                width: 320,
                height: 38,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 6,
                fontFamily: "Inter, system-ui, -apple-system, sans-serif",
                fontSize: 14,
                fontWeight: 500,
                color: "#0f172a",
              }}
            >
              <span>{fullNim.slice(0, nimTyped)}</span>
              {frame < CLICK_PASS_FRAME && isBlinking && (
                <span style={{ color: "#2563eb", fontWeight: 700, marginLeft: 2 }}>|</span>
              )}
            </div>
          )}
          {/* Typed Password (during typing only, stops before eye icon at x: 1115+) */}
          {frame >= TYPE_PASS_START && frame < TYPE_PASS_END && (
            <div
              style={{
                position: "absolute",
                left: 780,
                top: 647,
                width: 310,
                height: 38,
                backgroundColor: "#ffffff",
                display: "flex",
                alignItems: "center",
                paddingLeft: 6,
                fontSize: 18,
                letterSpacing: 3,
                color: "#0f172a",
              }}
            >
              <span>{fullPass.slice(0, passTyped)}</span>
              {isBlinking && (
                <span style={{ color: "#2563eb", fontSize: 14, letterSpacing: 0, marginLeft: 2 }}>|</span>
              )}
            </div>
          )}
        </>
      )}

      {/* Animated Cursor */}
      {frame >= 120 && (
        <AnimatedCursor
          x={cursorX}
          y={cursorY}
          isClicking={isClicking}
          clickFrame={clickFrame}
        />
      )}

      {/* Chapter 01 Title Slide Bumper (Intro 0 - 120f) */}
      {frame < 120 && (
        <ChapterBumper
          title="Autentikasi Akun & Registrasi Kursus"
          subtitle="Langkah Awal Memulai Aktivitas Akademik di Portal SALE"
          highlightWords={["Autentikasi", "Registrasi"]}
          durationInFrames={120}
        />
      )}

      {/* Dynamic Voiceover Subtitles with Broadcast Shadow Effect (No fake labels, no numbers) */}
      <ScreencastSubtitles
        items={[
          {
            startFrame: 120,
            durationInFrames: 143, // 120 to 263 (Seg 8-9)
            heading: "Akses Portal Akademik SALE",
            subtext: "Ketikkan Nomor Induk Mahasiswa (NIM) resmi sebagai identitas akun Anda",
          },
          {
            startFrame: 263,
            durationInFrames: 150, // 263 to 413 (Seg 10)
            heading: "Masukkan Kata Sandi Resmi",
            subtext: "Isikan kata sandi terdaftar yang diberikan oleh pihak kampus",
          },
          {
            startFrame: 413,
            durationInFrames: 108, // 413 to 521 (Seg 11)
            heading: "Autentikasi Akun Mahasiswa",
            subtext: "Pastikan data terisi dengan benar lalu klik tombol Masuk",
          },
          {
            startFrame: 521,
            durationInFrames: 147, // 521 to 668 (Seg 12)
            heading: "Dashboard Utama Mahasiswa",
            subtext: "Sistem mengarahkan Anda langsung ke portal ringkasan perkuliahan aktif",
          },
          {
            startFrame: 668,
            durationInFrames: 208, // 668 to 876 (Seg 13-14)
            heading: "Buka Menu Navigasi Course",
            subtext: "Akses seluruh daftar perkuliahan semester berjalan pada bilah menu sisi kiri",
          },
          {
            startFrame: 876,
            durationInFrames: 161, // 876 to 1037 (Seg 15)
            heading: "Tambah & Gabung Kelas",
            subtext: "Klik tombol tambah di sudut kanan atas untuk mendaftarkan mata kuliah baru",
          },
          {
            startFrame: 1037,
            durationInFrames: 273, // 1037 to 1310 (Seg 16-17)
            heading: "Masukkan Kode Akses Unik",
            subtext: "Ketikkan kode akses resmi dari dosen pengampu lalu tekan Gabung Kelas",
          },
          {
            startFrame: 1310,
            durationInFrames: 250, // 1310 to 1560 (Seg 18-19)
            heading: "Opsi Pindai QR Code di Kelas",
            subtext: "Pindai kode QR yang ditampilkan dosen pada proyektor ruang kuliah fisik",
          },
          {
            startFrame: 1560,
            durationInFrames: 207, // 1560 to 1767 (Seg 20-21)
            heading: "Konfirmasi Pendaftaran Kelas",
            subtext: "Jendela konfirmasi terbuka otomatis, klik konfirmasi untuk aktivasi instan",
          },
          {
            startFrame: 1767,
            durationInFrames: 129, // 1767 to 1896 (Seg 22)
            heading: "Ruang Perkuliahan Aktif",
            subtext: "Mata kuliah terdaftar secara resmi dan ruang belajar siap digunakan",
          },
        ]}
      />
    </div>
  );
};
