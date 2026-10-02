import React from "react";
import { useCurrentFrame } from "remotion";

/**
 * Clean human-crafted open-source vector illustrations (Tabler / Feather style)
 * Crisp, modern, scalable, with subtle animations. ZERO AI generation.
 */

// 1. Server Infrastructure (Admin Sistem)
export const ServerVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  const frame = useCurrentFrame();
  const blink = (Math.sin(frame / 12) + 1) / 2;

  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <rect x="2" y="2" width="20" height="8" rx="2" ry="2" />
      <rect x="2" y="14" width="20" height="8" rx="2" ry="2" />
      <line x1="6" y1="6" x2="6.01" y2="6" strokeWidth="2.5" />
      <line x1="6" y1="18" x2="6.01" y2="18" strokeWidth="2.5" />
      <line
        x1="10"
        y1="6"
        x2="10.01"
        y2="6"
        strokeWidth="2.5"
        style={{ opacity: 0.4 + blink * 0.6 }}
      />
      <line
        x1="10"
        y1="18"
        x2="10.01"
        y2="18"
        strokeWidth="2.5"
        style={{ opacity: 1 - blink * 0.6 }}
      />
    </svg>
  );
};

// 2. Curriculum Network (Admin Prodi)
export const CurriculumVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <circle cx="12" cy="5" r="3" />
      <circle cx="5" cy="19" r="3" />
      <circle cx="19" cy="19" r="3" />
      <path d="M12 8v4" />
      <path d="M5 16l4-4h6l4 4" />
    </svg>
  );
};

// 3. Teaching / Lecture Board (Dosen)
export const TeachingVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <path d="M4 4h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
      <path d="M12 18v4" />
      <path d="M8 22h8" />
      <path d="M7 10l3 3 7-7" />
    </svg>
  );
};

// 4. Student & Code Learning (Mahasiswa)
export const StudentVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
      <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5" />
    </svg>
  );
};

// 5. Target / OBE Competence (Radar & Bullseye)
export const ObeTargetVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  const frame = useCurrentFrame();
  const rot = (frame / 2) % 360;

  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <circle cx="12" cy="12" r="10" />
      <circle cx="12" cy="12" r="6" />
      <circle cx="12" cy="12" r="2" />
      <line
        x1="12"
        y1="2"
        x2="12"
        y2="6"
        style={{ transformOrigin: "12px 12px", transform: `rotate(${rot}deg)` }}
      />
    </svg>
  );
};

// 6. AI Assistant Spark / Terminal
export const AiVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  const frame = useCurrentFrame();
  const pulse = Math.sin(frame / 15) * 0.15 + 1;

  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      style={{ transform: `scale(${pulse})` }}
    >
      <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" />
      <circle cx="12" cy="12" r="4" />
    </svg>
  );
};

// 7. Live Chat Messages
export const ChatVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      <line x1="8" y1="9" x2="16" y2="9" />
      <line x1="8" y1="13" x2="13" y2="13" />
    </svg>
  );
};

// 8. Smart Countdown Timer
export const TimerVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  const frame = useCurrentFrame();
  const handRot = (frame * 4) % 360;

  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <circle cx="12" cy="13" r="8" />
      <path d="M12 9v4l2.5 2.5" style={{ transformOrigin: "12px 13px", transform: `rotate(${handRot}deg)` }} />
      <path d="M12 2v3M9 2h6" />
    </svg>
  );
};

// 9. Certified Export Document
export const ExportDocVector: React.FC<{ size?: number; className?: string }> = ({
  size = 120,
  className = "",
}) => {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
      <polyline points="14 2 14 8 20 8" />
      <line x1="12" y1="18" x2="12" y2="12" />
      <polyline points="9 15 12 18 15 15" />
    </svg>
  );
};
