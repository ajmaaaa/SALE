/**
 * Combiner & Stitcher untuk Video Panduan SALE
 * Menggabungkan beberapa video menjadi satu playlist atau video gabungan per kategori/role.
 */

import fs from 'node:fs';
import path from 'node:path';
import { VideoRenderer } from './renderer.js';

export class VideoCombiner {
    /**
     * Menggabungkan beberapa definisi video menjadi satu objek video master gabungan
     */
    static combine(title, id, videoList) {
        let combinedScenes = [];
        let totalDurationMinutes = 0;

        for (const vid of videoList) {
            // Beri label/bumper transisi antar video jika digabung
            combinedScenes.push({
                id: `${vid.id}_bumper`,
                layout: 'center-bold',
                bgColor: '#102f50',
                textColor: '#ffffff',
                mutedColor: '#cbd5e1',
                badge: 'BAGIAN PANDUAN',
                title: vid.title,
                description: `Panduan untuk ${vid.role}`,
                voiceover: `Kita masuk ke bagian: ${vid.title}.`,
                floating: true,
            });

            combinedScenes = combinedScenes.concat(vid.scenes);
        }

        const combinedVideo = {
            id,
            title,
            role: 'Gabungan / Kompilasi',
            duration: `~${videoList.length * 3}–${videoList.length * 4} menit`,
            scenes: combinedScenes
        };

        const previewPath = VideoRenderer.renderInteractivePreview(combinedVideo);
        return { combinedVideo, previewPath };
    }
}
