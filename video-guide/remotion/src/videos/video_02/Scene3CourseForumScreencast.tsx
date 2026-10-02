import React from "react";
import { Img, interpolate, OffthreadVideo, staticFile, useCurrentFrame } from "remotion";
import {
  AnimatedCursor,
  ScreencastSubtitles,
  SubtitleItem,
} from "../../components/ScreencastEngine";
import { ChapterBumper } from "../../components/ChapterBumper";

/**
 * Ruang Belajar, Silabus & Forum Diskusi
 * Durasi: 1380 frames (46.00 detik) — Full sync dengan VO2 Seg 23 - 35
 *
 * Screen: 15_mahasiswa_course_detail.png (100% Layar Penuh Otentik)
 * Alur sinkron suara:
 * - Seg 23-25 (f0-f260): Header ruang perkuliahan terpadu
 * - Seg 26-27 (f264-f395): Silabus mingguan
 * - Seg 28 (f395-f522): Pemutar video instruksional terintegrasi
 * - Seg 29 (f534-f683): Unduh modul PDF perkuliahan
 * - Seg 30-31 (f701-f932): Panel forum diskusi di sebelah kanan layar
 * - Seg 32 (f945-f1053): Input pertanyaan & tombol kirim ke ruang kelas
 * - Seg 33-35 (f1069-f1380): Diskusi real-time kolaboratif dosen & mahasiswa
 */
export const Scene3CourseForumScreencast: React.FC = () => {
  const frame = useCurrentFrame();

  // === TIMING MILESTONES (Sync dengan vo2_timestamps.json Seg 23-35) ===
  const MOVE_TO_SILABUS = 260;
  const CLICK_SILABUS = 320;

  const MOVE_TO_VIDEO = 395;
  const CLICK_VIDEO = 460;

  const MOVE_TO_MODUL = 535;
  const HOVER_MODUL = 610;

  const MOVE_TO_FORUM_PANEL = 720;
  const HOVER_FORUM_PANEL = 830;

  const MOVE_TO_CHAT_INPUT = 880;
  const CLICK_CHAT_INPUT = 910;
  const CLICK_SEND = 1010;
  const LECTURER_TYPING_FRAME = 1045;
  const LECTURER_REPLY_FRAME = 1100;

  const isVideoPlaying = frame >= CLICK_VIDEO + 5;
  const videoProgress = interpolate(frame, [CLICK_VIDEO, 1380], [0, 48], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const chatQuestion =
    "Pak, apakah untuk tugas pohon biner wajib menggunakan fungsi rekursif?";
  const chatTypedCount = Math.floor(
    interpolate(frame, [910, 995], [0, chatQuestion.length], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    })
  );
  const isBlinking = Math.floor(frame / 10) % 2 === 0;

  // === CURSOR LOGIC (CALM & PURPOSEFUL) ===
  let cursorX = 520;
  let cursorY = 180;
  let isClicking = false;
  let clickFrame = -1;

  if (frame < MOVE_TO_SILABUS) {
    // Header ruang perkuliahan
    cursorX = 520;
    cursorY = 180;
  } else if (frame < CLICK_SILABUS) {
    // Bergerak ke silabus mingguan (260, 935)
    cursorX = interpolate(frame, [MOVE_TO_SILABUS, CLICK_SILABUS - 2], [520, 260], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_SILABUS, CLICK_SILABUS - 2], [180, 935], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < MOVE_TO_VIDEO) {
    cursorX = 260;
    cursorY = 935;
    if (frame >= CLICK_SILABUS && frame < CLICK_SILABUS + 16) {
      clickFrame = CLICK_SILABUS;
      if (frame <= CLICK_SILABUS + 5) isClicking = true;
    }
  } else if (frame < CLICK_VIDEO) {
    // Bergerak ke tombol play video YouTube di tengah (872, 571)
    cursorX = interpolate(frame, [MOVE_TO_VIDEO, CLICK_VIDEO - 2], [260, 872], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_VIDEO, CLICK_VIDEO - 2], [935, 571], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < MOVE_TO_MODUL) {
    cursorX = 872;
    cursorY = 571;
    if (frame >= CLICK_VIDEO && frame < CLICK_VIDEO + 16) {
      clickFrame = CLICK_VIDEO;
      if (frame <= CLICK_VIDEO + 5) isClicking = true;
    }
  } else if (frame < HOVER_MODUL) {
    // Bergerak ke tautan modul perkuliahan / Buka Materi (1370, 985)
    cursorX = interpolate(frame, [MOVE_TO_MODUL, HOVER_MODUL - 2], [872, 1370], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_MODUL, HOVER_MODUL - 2], [571, 985], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < MOVE_TO_FORUM_PANEL) {
    cursorX = 1370;
    cursorY = 985;
  } else if (frame < HOVER_FORUM_PANEL) {
    // Bergerak ke panel forum diskusi di sebelah kanan (1650, 270)
    cursorX = interpolate(frame, [MOVE_TO_FORUM_PANEL, HOVER_FORUM_PANEL - 2], [1370, 1650], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_FORUM_PANEL, HOVER_FORUM_PANEL - 2], [985, 270], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < MOVE_TO_CHAT_INPUT) {
    cursorX = 1650;
    cursorY = 270;
  } else if (frame < CLICK_CHAT_INPUT) {
    // Bergerak ke kolom input chat di bawah (1620, 835)
    cursorX = interpolate(frame, [MOVE_TO_CHAT_INPUT, CLICK_CHAT_INPUT - 2], [1650, 1620], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [MOVE_TO_CHAT_INPUT, CLICK_CHAT_INPUT - 2], [270, 835], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < 995) {
    // Mengetik pesan pertanyaan di input box
    cursorX = 1620;
    cursorY = 835;
    if (frame >= CLICK_CHAT_INPUT && frame < CLICK_CHAT_INPUT + 16) {
      clickFrame = CLICK_CHAT_INPUT;
      if (frame <= CLICK_CHAT_INPUT + 5) isClicking = true;
    }
  } else if (frame < CLICK_SEND) {
    // Bergerak ke tombol kirim pesawat kertas (1824, 855)
    cursorX = interpolate(frame, [995, CLICK_SEND - 2], [1620, 1824], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [995, CLICK_SEND - 2], [835, 855], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  } else if (frame < LECTURER_REPLY_FRAME) {
    cursorX = 1824;
    cursorY = 855;
    if (frame >= CLICK_SEND && frame < CLICK_SEND + 16) {
      clickFrame = CLICK_SEND;
      if (frame <= CLICK_SEND + 5) isClicking = true;
    }
  } else {
    // Meninjau jawaban balasan dosen di panel forum (1650, 680)
    cursorX = interpolate(frame, [LECTURER_REPLY_FRAME, LECTURER_REPLY_FRAME + 25], [1824, 1650], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
    cursorY = interpolate(frame, [LECTURER_REPLY_FRAME, LECTURER_REPLY_FRAME + 25], [855, 680], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }

  return (
    <div className="relative w-[1920px] h-[1080px] bg-[#f8fafc] overflow-hidden select-none">
      {/* 100% Full Screen Course Learning Space */}
      <Img
        src={staticFile("screens/15_mahasiswa_course_detail.png")}
        style={{ width: "100%", height: "100%", objectFit: "cover" }}
      />

      {/* Authentic Lecture Video Playback inside the exact player box */}
      {isVideoPlaying && (
        <div
          style={{
            position: "absolute",
            left: 288,
            top: 242,
            width: 1169,
            height: 658,
            borderRadius: 12,
            overflow: "hidden",
            backgroundColor: "#000000",
            zIndex: 10,
          }}
        >
          <OffthreadVideo
            src={staticFile("videos/materi_struktur_data.mp4")}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
            volume={0}
            startFrom={0}
          />
        </div>
      )}

      {/* Realistic Academic Forum Chat Stream Overlay matching SALE Modern Design */}
      <div
        style={{
          position: "absolute",
          left: 1506,
          top: 450,
          width: 338,
          height: 345,
          backgroundColor: "transparent",
          display: "flex",
          flexDirection: "column",
          justifyContent: "flex-end",
          padding: "6px 8px",
          gap: 10,
          pointerEvents: "none",
          zIndex: 15,
        }}
      >
        {/* Sent Student Question Bubble (SALE Modern Native: Right-Aligned, No Avatar, #edf4fb) */}
        {frame >= CLICK_SEND + 5 && (
          <div
            style={{
              alignSelf: "flex-end",
              maxWidth: "90%",
              padding: "9px 13px",
              borderRadius: 12,
              backgroundColor: "#edf4fb",
              border: "1px solid #cfe0f2",
              boxShadow: "0 1px 4px rgba(0, 0, 0, 0.03)",
            }}
          >
            <div style={{ fontSize: 11, color: "#1e293b", lineHeight: "16px", fontWeight: 500 }}>
              Pak, apakah untuk tugas pohon biner wajib menggunakan fungsi rekursif?
            </div>
            <div style={{ fontSize: 9, color: "#94a3b8", textAlign: "right", marginTop: 3 }}>
              Baru saja
            </div>
          </div>
        )}

        {/* Lecturer Typing Indicator */}
        {frame >= LECTURER_TYPING_FRAME && frame < LECTURER_REPLY_FRAME && (
          <div
            style={{
              alignSelf: "flex-start",
              display: "flex",
              alignItems: "center",
              gap: 8,
              padding: "6px 10px",
              backgroundColor: "#f8fafc",
              border: "1px solid #e2e8f0",
              borderRadius: 10,
              width: "fit-content",
            }}
          >
            <div
              style={{
                width: 20,
                height: 20,
                borderRadius: "50%",
                backgroundColor: "#e2e8f0",
                color: "#334155",
                fontSize: 8,
                fontWeight: 700,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              BS
            </div>
            <span style={{ fontSize: 10, color: "#64748b", fontStyle: "italic" }}>
              Budi Santoso, M.Kom. sedang mengetik
            </span>
            <div style={{ display: "flex", gap: 3, alignItems: "center" }}>
              <div
                style={{
                  width: 4,
                  height: 4,
                  borderRadius: "50%",
                  backgroundColor: "#2563eb",
                  opacity: isBlinking ? 1 : 0.3,
                }}
              />
              <div
                style={{
                  width: 4,
                  height: 4,
                  borderRadius: "50%",
                  backgroundColor: "#2563eb",
                  opacity: !isBlinking ? 1 : 0.3,
                }}
              />
              <div
                style={{
                  width: 4,
                  height: 4,
                  borderRadius: "50%",
                  backgroundColor: "#2563eb",
                  opacity: isBlinking ? 1 : 0.3,
                }}
              />
            </div>
          </div>
        )}

        {/* Lecturer Reply Bubble (Left-Aligned with BS Avatar & · Dosen tag) */}
        {frame >= LECTURER_REPLY_FRAME && (
          <div
            style={{
              alignSelf: "flex-start",
              maxWidth: "92%",
              padding: "9px 12px",
              borderRadius: 12,
              backgroundColor: "#f8fafc",
              border: "1px solid #e2e8f0",
              boxShadow: "0 1px 4px rgba(0, 0, 0, 0.04)",
            }}
          >
            <div style={{ display: "flex", alignItems: "center", gap: 6, marginBottom: 3 }}>
              <div
                style={{
                  width: 20,
                  height: 20,
                  borderRadius: "50%",
                  backgroundColor: "#e2e8f0",
                  color: "#334155",
                  fontSize: 8,
                  fontWeight: 700,
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                BS
              </div>
              <span style={{ fontSize: 11, fontWeight: 700, color: "#0f172a" }}>
                Budi Santoso, M.Kom.
              </span>
              <span
                style={{
                  fontSize: 10,
                  fontWeight: 600,
                  color: "#2563eb",
                }}
              >
                · Dosen
              </span>
              <span style={{ fontSize: 9, color: "#94a3b8", marginLeft: "auto" }}>
                Baru saja
              </span>
            </div>
            <div style={{ fontSize: 11, color: "#1e293b", lineHeight: "16px" }}>
              Betul Ahmad. Gunakan rekursi agar penelusuran cabang kiri dan kanan BST berjalan efisien.
            </div>
          </div>
        )}
      </div>

      {/* Realistic Live Chat Input Box matching exact SALE UI (Only active while typing, unmounts cleanly on send) */}
      {frame >= CLICK_CHAT_INPUT && frame < CLICK_SEND + 8 && (
        <div
          style={{
            position: "absolute",
            left: 1506,
            top: 800,
            width: 338,
            height: 79,
            backgroundColor: "#ffffff",
            borderRadius: 12,
            border:
              frame < CLICK_SEND
                ? "1.5px solid #2563eb"
                : "1px solid #e2e8f0",
            boxShadow:
              frame < CLICK_SEND
                ? "0 0 0 3px rgba(37, 99, 235, 0.12)"
                : "none",
            padding: "8px 12px",
            display: "flex",
            flexDirection: "column",
            justifyContent: "space-between",
            zIndex: 20,
            pointerEvents: "none",
          }}
        >
          <div style={{ fontSize: 11, color: "#0f172a", lineHeight: "15px", minHeight: 30 }}>
            {frame < CLICK_SEND ? (
              <>
                <span>{chatQuestion.slice(0, chatTypedCount)}</span>
                {isBlinking && (
                  <span style={{ color: "#2563eb", fontWeight: 700, marginLeft: 2 }}>|</span>
                )}
              </>
            ) : (
              <span style={{ color: "#94a3b8" }}>
                Tulis pesan... Ketik @ untuk mention dosen / teman
              </span>
            )}
          </div>

          {/* Send Button at bottom right */}
          <div style={{ display: "flex", justifyContent: "flex-end" }}>
            <div
              style={{
                width: 26,
                height: 26,
                borderRadius: "50%",
                backgroundColor: "#18344e",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                color: "#ffffff",
                fontSize: 12,
                transform:
                  frame >= CLICK_SEND ? "scale(0.88)" : "scale(1)",
                transition: "transform 0.1s ease",
              }}
            >
              <svg
                style={{ width: 14, height: 14, fill: "currentColor" }}
                viewBox="0 0 24 24"
              >
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
              </svg>
            </div>
          </div>
        </div>
      )}

      {/* Animated Cursor */}
      {frame >= 200 && (
        <AnimatedCursor
          x={cursorX}
          y={cursorY}
          isClicking={isClicking}
          clickFrame={clickFrame}
        />
      )}

      {/* Chapter 02 Title Slide Bumper (Intro 0 - 200f) */}
      {frame < 200 && (
        <ChapterBumper
          title="Ruang Belajar, Silabus & Forum Diskusi"
          subtitle="Aktivitas Akademik Terstruktur dalam Satu Portal Terpadu"
          highlightWords={["Ruang", "Belajar,", "Silabus", "Forum"]}
          durationInFrames={200}
        />
      )}

      {/* Dynamic Voiceover Subtitles with Broadcast Shadow Effect (No fake labels, no numbers) */}
      <ScreencastSubtitles
        items={[
          {
            startFrame: 200,
            durationInFrames: 65, // 200 to 265
            heading: "Ruang Belajar Terpadu",
            subtext: "Seluruh aktivitas akademik tersaji secara terstruktur dalam satu portal",
          },
          {
            startFrame: 265,
            durationInFrames: 130, // 265 to 395
            heading: "Silabus Mingguan Terstruktur",
            subtext: "Periksa rincian silabus dan agenda perkuliahan setiap pekannya",
          },
          {
            startFrame: 395,
            durationInFrames: 140, // 395 to 535
            heading: "Pemutar Video Instruksional",
            subtext: "Tonton video materi perkuliahan langsung di dalam pemutar terintegrasi",
          },
          {
            startFrame: 535,
            durationInFrames: 166, // 535 to 701
            heading: "Unduh Modul & Dokumen Perkuliahan",
            subtext: "Akses materi format PDF atau dokumen presentasi untuk bahan studi",
          },
          {
            startFrame: 701,
            durationInFrames: 135, // 701 to 836
            heading: "Panel Forum Diskusi Interaktif",
            subtext: "Gunakan forum di sebelah kanan untuk pendalaman pemahaman materi",
          },
          {
            startFrame: 836,
            durationInFrames: 233, // 836 to 1069
            heading: "Kirim Pertanyaan Langsung ke Kelas",
            subtext: "Ketikkan pertanyaan dan kirimkan ke ruang kelas untuk didiskusikan",
          },
          {
            startFrame: 1069,
            durationInFrames: 311, // 1069 to 1380
            heading: "Diskusi Kolaboratif Real-Time",
            subtext: "Dosen dan rekan sekelas merespons dalam diskusi yang terdokumentasi rapi",
          },
        ]}
      />
    </div>
  );
};
