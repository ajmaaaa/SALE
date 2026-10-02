import React from "react";
import { Img, interpolate, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  ScreencastSubtitles,
} from "../../components/ScreencastEngine";
import { ChapterBumper } from "../../components/ChapterBumper";

/**
 * BAB 05 — Transkrip Nilai, Capaian CPMK, Notifikasi & Profil Mahasiswa
 * Durasi: 1544 frames (51.47 detik) — Full sync dengan VO2 Seg 67 - 80
 *
 * Screen:
 * - 17_mahasiswa_transkrip_radar.png (Transkrip KHS, Capaian CPMK, Evaluasi)
 * - 25_mahasiswa_notifikasi.png (Pusat Notifikasi Akademik)
 * - 26_mahasiswa_profil.png (Profil & Pengaturan Mahasiswa)
 *
 * Alur interaktif otentik:
 * - Seg 67-68 (f0-f309): Transparansi penilaian akademik & tinjauan KHS
 * - Seg 69-70 (f309-f520): Klik mata kuliah & buka rincian capaian CPMK
 * - Seg 71-74 (f520-f886): Evaluasi CPMK, pemetaan keahlian & umpan balik dosen
 * - Seg 75-77 (f886-f1224): Buka pusat notifikasi & klik notifikasi nilai baru
 * - Seg 78-80 (f1224-f1544): Buka profil & pembaruan data kontak mahasiswa
 */
export const Scene6NilaiNotifikasiScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // === TIMING MILESTONES (Sync dengan vo2_timestamps.json Seg 67-80) ===
  const BUMPER_END = 210;
  const CLICK_COURSE_ROW = 475;   // Seg 69-70: Klik baris Struktur Data dan Algoritma
  const EXPAND_DETAILS = 485;
  const MOVE_TO_NOTIF_MENU = 840; // Seg 75: Menuju menu lonceng notifikasi
  const CLICK_NOTIF_MENU = 885;
  const NOTIF_SCREEN_START = 895;
  const CLICK_NOTIF_ITEM = 1015;  // Seg 76: Klik notifikasi nilai baru dirilis
  const MOVE_TO_PROFIL_MENU = 1180; // Seg 78: Menuju menu profil
  const CLICK_PROFIL_MENU = 1224;
  const PROFIL_SCREEN_START = 1234;

  const showProfil = frame >= PROFIL_SCREEN_START;
  const showNotif = frame >= NOTIF_SCREEN_START && !showProfil;
  const isRowExpanded = frame >= EXPAND_DETAILS && !showNotif && !showProfil;
  const isNotifRead = frame >= CLICK_NOTIF_ITEM + 10;

  // === CALM & PURPOSEFUL CURSOR MOVEMENTS ===
  let cursorX = 550;
  let cursorY = 220;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < 420) {
    // Meninjau ringkasan Transkrip Nilai & Hasil Studi
    cursorX = 550;
    cursorY = 220;
  } else if (frame < CLICK_COURSE_ROW) {
    // Bergerak ke baris Struktur Data dan Algoritma (550, 310)
    cursorX = interpolate(frame, [420, CLICK_COURSE_ROW - 2], [550, 550], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [420, CLICK_COURSE_ROW - 2], [220, 310], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < EXPAND_DETAILS) {
    // Klik baris mata kuliah untuk membuka rincian komponen nilai
    cursorX = 550;
    cursorY = 310;
    if (frame >= CLICK_COURSE_ROW && frame < CLICK_COURSE_ROW + 16) {
      clickFrame = CLICK_COURSE_ROW;
      if (frame <= CLICK_COURSE_ROW + 5) isClicking = true;
    }
  } else if (frame < 720) {
    // Meninjau rincian nilai komponen (Tugas 1: 87.0, Tugas 2: 82.0, Kuis 1: 90.0)
    cursorX = interpolate(frame, [EXPAND_DETAILS, EXPAND_DETAILS + 40], [550, 350], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [EXPAND_DETAILS, EXPAND_DETAILS + 40], [310, 400], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < MOVE_TO_NOTIF_MENU) {
    // Mengamati total beban SKS dan nilai akhir
    cursorX = interpolate(frame, [720, 760], [350, 550], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [720, 760], [400, 400], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < CLICK_NOTIF_MENU) {
    // Bergerak ke menu Notifikasi di bilah navigasi kiri (64, 388)
    cursorX = interpolate(frame, [MOVE_TO_NOTIF_MENU, CLICK_NOTIF_MENU - 2], [550, 64], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_NOTIF_MENU, CLICK_NOTIF_MENU - 2], [400, 388], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < NOTIF_SCREEN_START + 50) {
    // Klik menu Notifikasi
    cursorX = 64;
    cursorY = 388;
    if (frame >= CLICK_NOTIF_MENU && frame < CLICK_NOTIF_MENU + 16) {
      clickFrame = CLICK_NOTIF_MENU;
      if (frame <= CLICK_NOTIF_MENU + 5) isClicking = true;
    }
  } else if (frame < CLICK_NOTIF_ITEM) {
    // Bergerak ke kartu Notifikasi Pertama (350, 350)
    cursorX = interpolate(frame, [NOTIF_SCREEN_START + 50, CLICK_NOTIF_ITEM - 2], [64, 350], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [NOTIF_SCREEN_START + 50, CLICK_NOTIF_ITEM - 2], [388, 350], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < MOVE_TO_PROFIL_MENU) {
    // Klik notifikasi dan amati secara tenang
    cursorX = 350;
    cursorY = 350;
    if (frame >= CLICK_NOTIF_ITEM && frame < CLICK_NOTIF_ITEM + 16) {
      clickFrame = CLICK_NOTIF_ITEM;
      if (frame <= CLICK_NOTIF_ITEM + 5) isClicking = true;
    }
  } else if (frame < CLICK_PROFIL_MENU) {
    // Bergerak ke menu Profil & Pengaturan di sidebar (64, 428)
    cursorX = interpolate(frame, [MOVE_TO_PROFIL_MENU, CLICK_PROFIL_MENU - 2], [350, 64], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_PROFIL_MENU, CLICK_PROFIL_MENU - 2], [350, 428], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < PROFIL_SCREEN_START + 40) {
    // Klik menu Profil
    cursorX = 64;
    cursorY = 428;
    if (frame >= CLICK_PROFIL_MENU && frame < CLICK_PROFIL_MENU + 16) {
      clickFrame = CLICK_PROFIL_MENU;
      if (frame <= CLICK_PROFIL_MENU + 5) isClicking = true;
    }
  } else if (frame < PROFIL_SCREEN_START + 120) {
    // Bergerak menunjuk foto dan identitas profil mahasiswa (240, 365)
    cursorX = interpolate(frame, [PROFIL_SCREEN_START + 40, PROFIL_SCREEN_START + 118], [64, 240], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [PROFIL_SCREEN_START + 40, PROFIL_SCREEN_START + 118], [428, 365], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < PROFIL_SCREEN_START + 220) {
    // Menunjuk informasi kontak akademik & status aktif (600, 520)
    cursorX = interpolate(frame, [PROFIL_SCREEN_START + 160, PROFIL_SCREEN_START + 218], [240, 600], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [PROFIL_SCREEN_START + 160, PROFIL_SCREEN_START + 218], [365, 520], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else {
    // Meninjau data profil secara tenang hingga akhir video
    cursorX = 600;
    cursorY = 520;
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* 100% Full Screen Authentic SALE System Screenshots */}
      {showProfil ? (
        <Img
          src={staticFile("screens/26_mahasiswa_profil.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover", objectPosition: "top center" }}
        />
      ) : showNotif ? (
        <Img
          src={staticFile("screens/25_mahasiswa_notifikasi.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover", objectPosition: "top center" }}
        />
      ) : isRowExpanded ? (
        <Img
          src={staticFile("screens/17_mahasiswa_transkrip_expanded.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover", objectPosition: "top center" }}
        />
      ) : (
        <Img
          src={staticFile("screens/17_mahasiswa_transkrip.png")}
          style={{ width: "100%", height: "100%", objectFit: "cover", objectPosition: "top center" }}
        />
      )}


      {/* Animated Cursor */}
      {frame >= BUMPER_END && (
        <AnimatedCursor
          x={cursorX}
          y={cursorY}
          isClicking={isClicking}
          clickFrame={clickFrame}
        />
      )}

      {/* Chapter 05 Title Slide Bumper (Intro 0 - 210f) */}
      {frame < BUMPER_END && (
        <ChapterBumper
          title="Transkrip Nilai, Capaian CPMK & Profil"
          subtitle="Evaluasi Objektif Standar Kompetensi dan Pemantauan Informasi Akun"
          highlightWords={["Transkrip", "Nilai,", "CPMK", "Profil"]}
          durationInFrames={BUMPER_END}
        />
      )}

      {/* Dynamic Voiceover Subtitles with Broadcast Shadow Effect (No fake labels, no numbers) */}
      <ScreencastSubtitles
        items={[
          {
            startFrame: 210,
            durationInFrames: 99, // 210 to 309 (Seg 67-68)
            heading: "Transparansi Penilaian Akademik",
            subtext: "Evaluasi hasil belajar komprehensif, bukan sekadar skor mentah angka seratus",
          },
          {
            startFrame: 309,
            durationInFrames: 211, // 309 to 520 (Seg 69-70)
            heading: "Grafik Radar Capaian CPMK",
            subtext: "Pantau visualisasi capaian pembelajaran mata kuliah secara objektif dan terperinci",
          },
          {
            startFrame: 520,
            durationInFrames: 188, // 520 to 708 (Seg 71-72)
            heading: "Pemetaan Penguasaan Keahlian",
            subtext: "Ketahui secara objektif bidang keahlian yang telah dikuasai dengan sangat baik",
          },
          {
            startFrame: 708,
            durationInFrames: 178, // 708 to 886 (Seg 73-74)
            heading: "Umpan Balik Dosen Pengampu",
            subtext: "Identifikasi aspek materi yang memerlukan pendalaman lebih lanjut",
          },
          {
            startFrame: 886,
            durationInFrames: 250, // 886 to 1136 (Seg 75-76)
            heading: "Pusat Notifikasi Akademik",
            subtext: "Periksa panel lonceng notifikasi berkala untuk pembaruan pengumuman dan nilai terbaru",
          },
          {
            startFrame: 1136,
            durationInFrames: 89, // 1136 to 1225 (Seg 77)
            heading: "Pembaruan Informasi Berkala",
            subtext: "Dapatkan pemberitahuan penting perkuliahan secara tepat waktu",
          },
          {
            startFrame: 1225,
            durationInFrames: 319, // 1225 to 1544 (Seg 78-80)
            heading: "Pembaruan Profil Mahasiswa",
            subtext: "Pastikan data diri dan kontak selalu mutakhir demi kelancaran komunikasi akademik",
          },
        ]}
      />
    </div>
  );
};
