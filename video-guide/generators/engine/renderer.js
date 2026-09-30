/**
 * Engine Renderer Slide & Preview Player untuk SALE Video Guide
 * Mendukung layout 16:9, animasi mengambang (float), transisi smooth zoom, dan cursor track.
 */

import fs from 'node:fs';
import path from 'node:path';
import { VIDEO_CONFIG, validateSceneAntiSlop } from './config.js';

export class VideoRenderer {
    /**
     * Render satu video menjadi preview player HTML interaktif
     */
    static renderInteractivePreview(video) {
        // Validasi Anti-Slop terlebih dahulu
        for (const scene of video.scenes) {
            const errors = validateSceneAntiSlop(scene);
            if (errors.length > 0) {
                console.warn(`Peringatan untuk video ${video.id}:`, errors);
            }
        }

        const outDir = path.resolve('video-guide', 'previews');
        if (!fs.existsSync(outDir)) {
            fs.mkdirSync(outDir, { recursive: true });
        }

        const htmlContent = `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${video.title} - Preview Player | SALE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-navy: ${VIDEO_CONFIG.colors.brandNavy};
            --brand-navy-dark: ${VIDEO_CONFIG.colors.brandNavyDark};
            --brand-canvas: ${VIDEO_CONFIG.colors.brandCanvas};
            --brand-ink: ${VIDEO_CONFIG.colors.brandInk};
            --brand-muted: ${VIDEO_CONFIG.colors.brandMuted};
            --brand-accent: ${VIDEO_CONFIG.colors.brandAccent};
            --brand-success: ${VIDEO_CONFIG.colors.brandSuccess};
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b1320;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .player-container {
            width: 100%;
            max-width: 1280px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .video-viewport {
            width: 100%;
            aspect-ratio: 16 / 9;
            background-color: var(--brand-canvas);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
        }
        .scene {
            display: none;
            width: 100%;
            height: 100%;
            position: absolute;
            inset: 0;
            padding: 60px 80px;
            opacity: 0;
            transition: opacity 0.4s ease-in-out;
        }
        .scene.active {
            display: flex;
            opacity: 1;
        }
        /* Layout Variant: Full Left */
        .layout-full-left {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }
        /* Layout Variant: Center Bold */
        .layout-center-bold {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 24px;
        }
        /* Layout Variant: Split Card */
        .layout-split-card {
            flex-direction: row;
            align-items: stretch;
            gap: 32px;
        }
        /* Floating object animation */
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: floating 4s ease-in-out infinite;
        }
        /* Cursor representation */
        .simulated-cursor {
            position: absolute;
            width: 24px;
            height: 24px;
            pointer-events: none;
            transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 100;
        }
        .click-ripple {
            position: absolute;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--brand-navy);
            transform: translate(-50%, -50%) scale(0);
            opacity: 0.8;
            animation: ripple 0.6s ease-out forwards;
        }
        @keyframes ripple {
            to { transform: translate(-50%, -50%) scale(2); opacity: 0; }
        }
        /* Control Bar */
        .control-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 10px;
        }
        .btn {
            background: #ffffff;
            color: #0f172a;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { background: #e2e8f0; }
        .vo-caption-box {
            background: #1e293b;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 15px;
            line-height: 1.6;
            color: #cbd5e1;
            border-left: 4px solid #38bdf8;
        }
    </style>
</head>
<body>
    <div class="player-container">
        <header style="display: flex; justify-content: space-between; align-items: baseline;">
            <div>
                <h1 style="font-size: 20px; font-weight: 800;">${video.title}</h1>
                <p style="color: #94a3b8; font-size: 13px; margin-top: 2px;">Role: <strong>${video.role}</strong> &bull; Durasi Est.: <strong>${video.duration}</strong></p>
            </div>
            <span style="font-size: 13px; color: #64748b; font-family: monospace;">ID: ${video.id}</span>
        </header>

        <div class="video-viewport" id="viewport">
            ${video.scenes.map((scene, idx) => `
                <div class="scene layout-${scene.layout || 'center-bold'} ${idx === 0 ? 'active' : ''}" 
                     id="scene-${idx}"
                     style="background-color: ${scene.bgColor || '#f4f5f7'}; color: ${scene.textColor || '#0f172a'};">
                    <div style="flex: 1; max-width: 900px;" class="${scene.floating ? 'animate-float' : ''}">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #64748b; margin-bottom: 12px;">
                            ${scene.badge || 'SALE ACADEMIC SYSTEM'}
                        </div>
                        <h2 style="font-size: 44px; font-weight: 800; line-height: 1.2; margin-bottom: 16px;">
                            ${scene.title}
                        </h2>
                        <p style="font-size: 20px; line-height: 1.5; color: ${scene.mutedColor || '#475569'}; max-width: 720px;">
                            ${scene.description || ''}
                        </p>
                        ${scene.details ? `
                            <div style="margin-top: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
                                ${scene.details.map(d => `
                                    <div style="background: rgba(16, 47, 80, 0.06); padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                                        ${d}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('')}
        </div>

        <div class="vo-caption-box">
            <strong style="color: #38bdf8;">Voiceover [VO]:</strong> 
            <span id="vo-text">${video.scenes[0]?.voiceover || ''}</span>
        </div>

        <div class="control-bar">
            <button class="btn" onclick="prevScene()">← Scene Sebelumnya</button>
            <span id="scene-indicator" style="font-weight: 600; font-size: 14px;">Scene 1 dari ${video.scenes.length}</span>
            <button class="btn" onclick="nextScene()">Scene Berikutnya →</button>
        </div>
    </div>

    <script>
        const scenes = ${JSON.stringify(video.scenes)};
        let currentScene = 0;

        function updateScene(idx) {
            document.querySelectorAll('.scene').forEach((el, i) => {
                el.classList.toggle('active', i === idx);
            });
            document.getElementById('scene-indicator').innerText = 'Scene ' + (idx + 1) + ' dari ' + scenes.length;
            document.getElementById('vo-text').innerText = scenes[idx].voiceover || '';
            currentScene = idx;
        }

        function nextScene() {
            if (currentScene < scenes.length - 1) {
                updateScene(currentScene + 1);
            }
        }

        function prevScene() {
            if (currentScene > 0) {
                updateScene(currentScene - 1);
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') nextScene();
            if (e.key === 'ArrowLeft') prevScene();
        });
    </script>
</body>
</html>`;

        const filePath = path.join(outDir, `${video.id}_preview.html`);
        fs.writeFileSync(filePath, htmlContent, 'utf-8');
        return filePath;
    }
}
