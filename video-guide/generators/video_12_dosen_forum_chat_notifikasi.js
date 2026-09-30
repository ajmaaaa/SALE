import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video12 = {
    id: '12_dosen_forum_chat_notifikasi',
    title: 'Forum Diskusi Kelas, Chat Real-time, dan Notifikasi Dosen',
    role: 'Dosen',
    duration: '2–3 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'INTERAKSI KELAS DINAMIS',
            title: 'Komunikasi Kelas yang Responsif',
            description: 'Tingkatkan keterlibatan mahasiswa melalui obrolan kelas langsung dan notifikasi instan.',
            voiceover: 'Komunikasi yang responsif antara dosen dan mahasiswa menciptakan suasana belajar yang hidup. Di SALE, ruang interaksi kelas hadir berdampingan langsung dengan materi perkuliahan.',
            floating: true
        },
        {
            id: 'scene_2_live_chat_and_mention',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'LIVE CHAT PERKULIAHAN',
            title: 'Obrolan Realtime dan Mention Mahasiswa',
            description: 'Gunakan simbol @ untuk berinteraksi langsung dengan mahasiswa spesifik.',
            screenRec: {
                target: 'Panel Kanan Course: Live Chat',
                zoom: { scale: 1.25, area: 'Form kirim pesan & autocomplete mention' },
                cursor: [{ action: 'type_message' }, { action: 'mention_student' }, { action: 'send_chat' }]
            },
            voiceover: 'Di panel Live Chat, obrolan berjalan secara realtime. Anda dapat langsung menjawab pertanyaan mahasiswa, berdiskusi santai, atau menggunakan simbol at (@) untuk memanggil mahasiswa tertentu secara langsung di dalam ruang kelas virtual.',
            floating: false
        },
        {
            id: 'scene_3_pin_important_messages',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'PIN PENGUMUMAN PENTING',
            title: 'Pesan Tersemat di Posisi Teratas',
            description: 'Kunci pesan utama agar tidak tergeser oleh obrolan kelas yang aktif.',
            screenRec: {
                target: 'Menu Dropup Titik Tiga Pesan Chat',
                zoom: { scale: 1.25, area: 'Menu aksi pesan & tombol Pin Pesan' },
                cursor: [{ action: 'click_message_menu' }, { action: 'select_pin_message' }]
            },
            voiceover: 'Memiliki informasi penting seperti perubahan jadwal atau link referensi utama? Cukup klik opsi pesan dan pilih \'Pin Pesan\'. Pesan tersebut akan terkunci di posisi teratas sehingga selalu terbaca oleh seluruh anggota kelas.',
            floating: false
        },
        {
            id: 'scene_4_realtime_dosen_notifications',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'NOTIFIKASI AKTIVITAS KELAS',
            title: 'Pantau Tugas Masuk Tanpa Reload',
            description: 'Pemberitahuan realtime saat mahasiswa mengumpulkan tugas atau bertanya di forum.',
            screenRec: {
                target: 'Halaman Notifikasi Dosen',
                zoom: { scale: 1.2, area: 'Filter kategori & tombol Tandai Semua Dibaca' },
                cursor: [{ action: 'filter_task_submissions' }, { action: 'mark_all_read' }]
            },
            voiceover: 'Untuk memantau aktivitas dari seluruh kelas yang Anda ampu, buka menu Notifikasi. Setiap ada mahasiswa yang mengumpulkan tugas atau bertanya di forum, sistem akan memberitahu Anda secara instan tanpa perlu memuat ulang halaman.',
            floating: false
        },
        {
            id: 'scene_5_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SALE INTERACTION HUB',
            title: 'Komunikasi Aktif. Perkuliahan Interaktif',
            description: 'Jaga kehangatan dan keaktifan ruang kelas digital Anda setiap hari.',
            voiceover: 'Tetap terhubung dengan mahasiswa Anda kapan saja dan di mana saja melalui ekosistem komunikasi terpadu SALE.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video12);
    const previewPath = VideoRenderer.renderInteractivePreview(video12);
    console.log(`[Video 12] Manifest VO: ${manifestPath}`);
    console.log(`[Video 12] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_12_dosen_forum_chat_notifikasi.js')) {
    generate();
}
