/**
 * Generator Voiceover ElevenLabs untuk SALE Video Guide
 */

import fs from 'node:fs';
import path from 'node:path';

export class ElevenLabsGenerator {
    constructor(apiKey = process.env.ELEVENLABS_API_KEY, voiceId = '21m00Tcm4TlvDq8ikWAM') {
        this.apiKey = apiKey;
        this.voiceId = voiceId; // Default voice (Rachel / Professional Indonesian compatible)
    }

    /**
     * Hitung perkiraan durasi detik dari teks VO (rata-rata 140 kata/menit = ~2.33 kata/detik)
     */
    static estimateDurationSeconds(text) {
        if (!text) return 3;
        const words = text.trim().split(/\s+/).length;
        const seconds = Math.max(3, Math.ceil(words / 2.33));
        return seconds;
    }

    /**
     * Ekspor daftar baris VO per scene ke file teks/JSON untuk ElevenLabs
     */
    exportVoManifest(video) {
        const manifest = {
            id: video.id,
            title: video.title,
            role: video.role,
            totalScenes: video.scenes.length,
            scenes: video.scenes.map((s, idx) => ({
                sceneIndex: idx + 1,
                id: s.id,
                estimatedDurationSec: ElevenLabsGenerator.estimateDurationSeconds(s.voiceover),
                voiceoverText: s.voiceover || '',
                tone: s.tone || 'Natural, professional, engaging'
            }))
        };

        const outDir = path.resolve('video-guide', 'assets-references', 'vo-manifests');
        if (!fs.existsSync(outDir)) {
            fs.mkdirSync(outDir, { recursive: true });
        }
        const filePath = path.join(outDir, `${video.id}_vo.json`);
        fs.writeFileSync(filePath, JSON.stringify(manifest, null, 2), 'utf-8');
        return filePath;
    }

    /**
     * Pemanggilan API ElevenLabs nyata (jika ELEVENLABS_API_KEY disetel)
     */
    async generateAudioForScene(text, outFilePath) {
        if (!this.apiKey) {
            console.log(`[ElevenLabs] API Key tidak ditemukan. Simpan teks untuk generate eksternal: "${text.substring(0, 40)}..."`);
            return null;
        }

        const url = `https://api.elevenlabs.io/v1/text-to-speech/${this.voiceId}`;
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'xi-api-key': this.apiKey,
            },
            body: JSON.stringify({
                text: text,
                model_id: 'eleven_multilingual_v2',
                voice_settings: {
                    stability: 0.5,
                    similarity_boost: 0.75,
                }
            })
        });

        if (!response.ok) {
            throw new Error(`ElevenLabs API error: ${response.statusText}`);
        }

        const buffer = await response.arrayBuffer();
        fs.writeFileSync(outFilePath, Buffer.from(buffer));
        return outFilePath;
    }
}
