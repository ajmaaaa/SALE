import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video00 = {
    id: '00_pengenalan_sale',
    title: 'Pengenalan SALE: Sistem untuk Siapa & Apa yang Bisa Dilakukan',
    role: 'All Roles (Overview)',
    duration: '2–3 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            mutedColor: '#cbd5e1',
            badge: 'SMART ACADEMIC LEARNING ENVIRONMENT',
            title: 'Sistem Pembelajaran Cerdas Berbasis OBE',
            description: 'Ekosistem akademik digital yang tidak hanya mencatat angka nilai, melainkan mengukur capaian kompetensi nyata mahasiswa secara objektif dan terstandar.',
            voiceover: 'Bayangkan sebuah ekosistem akademik di mana setiap proses belajar tidak hanya menghasilkan nilai berupa angka, tapi benar-benar mengukur kemampuan nyata. Inilah SALE, Smart Academic Learning Environment.',
            floating: true
        },
        {
            id: 'scene_2_four_roles',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'KOLABORASI CIVITAS AKADEMIKA',
            title: 'Empat Peran dalam Satu Ekosistem',
            description: 'Sistem terpadu yang menghubungkan seluruh penggerak mutu akademik kampus.',
            details: [
                'Admin Sistem: Keamanan, server, AI token, dan data global',
                'Admin Prodi: Kurikulum OBE, butir CPL & CPMK, dan kelas',
                'Dosen: Course, asesmen terukur, materi, dan rubrik',
                'Mahasiswa: Belajar terarah, kuis interaktif, dan AI asisten'
            ],
            voiceover: 'Sistem ini dibangun untuk semua penggerak akademik. Admin Sistem yang mengelola infrastruktur, Admin Prodi yang merancang kurikulum, Dosen yang menjadi fasilitator, dan tentu saja... Mahasiswa sebagai pusat dari setiap proses pembelajaran.',
            floating: false
        },
        {
            id: 'scene_3_obe_framework',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'OUTCOME-BASED EDUCATION',
            title: 'Bukan Sekadar LMS Biasa',
            description: 'Setiap materi, tugas, dan kuis dipetakan langsung ke Capaian Pembelajaran Mata Kuliah (CPMK) dan Capaian Pembelajaran Lulusan (CPL).',
            details: [
                'Pemetaan Asesmen ke CPMK',
                'Perhitungan Ketercapaian Otomatis',
                'Laporan Akreditasi Siap Pakai'
            ],
            voiceover: 'SALE bukan sekadar LMS biasa. Kami mengadopsi standar Outcome-Based Education atau OBE. Artinya, setiap kuis, setiap tugas pemrograman, hingga diskusi di forum, semuanya dipetakan langsung ke Capaian Pembelajaran. Mahasiswa tahu persis skill apa yang sedang mereka bangun.',
            floating: true
        },
        {
            id: 'scene_4_modern_features',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'FITUR MODERN & REALTIME',
            title: 'Teknologi Cerdas untuk Kampus Masa Depan',
            description: 'Dilengkapi fasilitas interaksi mutakhir untuk menunjang pengalaman belajar.',
            details: [
                'Forum Diskusi & Live Chat Realtime',
                'Asisten AI Pembimbing Pemrograman',
                'Ruang Ujian Terpadu dengan Timer Pintar',
                'Rekapitulasi Nilai & Ekspor Resmi'
            ],
            voiceover: 'Mulai dari diskusi kelas real-time layaknya aplikasi chatting modern, ujian dengan sistem timer pintar, hingga asisten AI yang siap membimbing logika tugas coding mahasiswa. Semua terintegrasi dalam satu platform.',
            floating: false
        },
        {
            id: 'scene_5_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            mutedColor: '#94a3b8',
            badge: 'SIAP MEMULAI PERJALANAN',
            title: 'Cerdas. Terukur. Terintegrasi',
            description: 'Mari jelajahi setiap fitur SALE pada seri panduan video selanjutnya.',
            voiceover: 'Siap untuk mengubah cara kamu mengajar dan belajar? Mari kita jelajahi SALE lebih dalam di seri panduan berikutnya.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video00);
    const previewPath = VideoRenderer.renderInteractivePreview(video00);
    console.log(`[Video 00] Manifest VO: ${manifestPath}`);
    console.log(`[Video 00] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_00_pengenalan.js')) {
    generate();
}
