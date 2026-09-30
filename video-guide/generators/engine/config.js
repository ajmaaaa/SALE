/**
 * Config & Standar Visual Video Panduan SALE
 * Berdasarkan dokumen DESIGN_RULES.md
 */

export const VIDEO_CONFIG = {
    resolution: {
        width: 1920,
        height: 1080,
        aspectRatio: '16:9',
        fps: 30,
    },
    colors: {
        brandNavy: '#102f50',       // Warna utama sistem SALE
        brandNavyDark: '#0a1d32',   // Background varian gelap
        brandCanvas: '#f4f5f7',     // Background canvas terang
        brandWhite: '#ffffff',      // Putih bersih
        brandInk: '#0f172a',        // Teks gelap utama (hampir hitam)
        brandMuted: '#64748b',      // Teks pendukung / deskripsi
        brandAccent: '#e8f1f8',     // Aksen lembut biru muda
        brandBorder: '#e2e8f0',     // Border tipis fungsional
        brandSuccess: '#059669',    // Indikator sukses / benar
        brandWarning: '#d97706',    // Indikator perhatian
    },
    typography: {
        fontFamily: "'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif",
        headingSize: '56px',
        subheadingSize: '32px',
        bodySize: '22px',
        captionSize: '16px',
    },
    animation: {
        defaultDurationMs: 400,
        zoomScaleDefault: 1.25,
        cursorSpeedNormal: 800, // ms per transition
    }
};

/**
 * Validasi Anti-AI Slop pada konfigurasi scene
 */
export function validateSceneAntiSlop(scene) {
    const errors = [];
    if (scene.title && scene.title.endsWith('.')) {
        errors.push(`[Anti-Slop] Judul "${scene.title}" tidak boleh diakhiri tanda titik (.)`);
    }
    if (scene.title && scene.title.includes('--')) {
        errors.push(`[Anti-Slop] Judul "${scene.title}" tidak boleh mengandung dash ganda (--)`);
    }
    if (scene.bgColor && scene.bgColor.toLowerCase().includes('purple')) {
        errors.push(`[Anti-Slop] Dilarang menggunakan warna gradient ungu.`);
    }
    return errors;
}
