import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video02 = {
    id: '02_mahasiswa_dashboard_course_forum',
    title: 'Navigasi Dashboard, Course, dan Forum Diskusi',
    role: 'Mahasiswa',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'PRODUKTIVITAS MAHASISWA',
            title: 'Navigasi Cepat dan Terarah',
            description: 'Ketahui prioritas akademik harian tanpa harus mencari-cari dokumen secara terpisah.',
            voiceover: 'Antarmuka yang teratur adalah kunci belajar yang fokus. Di SALE, dashboard utama dirancang untuk menampilkan prioritas belajarmu dalam sekejap mata.',
            floating: true
        },
        {
            id: 'scene_2_academic_summary',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'RINGKASAN AKADEMIK',
            title: 'Status Kelas dan Pesan Masuk',
            description: 'Pantau jumlah kelas aktif, tugas mendatang, serta pesan obrolan kelas yang belum terbaca.',
            screenRec: {
                target: 'Dashboard Mahasiswa (Ringkasan Akademik)',
                zoom: { scale: 1.15, area: 'Kartu ringkasan akademik & daftar pesan' },
                cursor: [{ action: 'hover_summary_cards' }, { action: 'hover_unread_messages' }]
            },
            voiceover: 'Di bagian atas, kamu langsung melihat ringkasan akademik: berapa kelas yang aktif dan tugas yang menanti. Di bawahnya, daftar pesan diskusi yang belum sempat kamu buka tersusun rapi agar kamu tidak melewatkan obrolan penting dari kelas.',
            floating: false
        },
        {
            id: 'scene_3_two_column_course',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'TATA LETAK DUA KOLOM',
            title: 'Ruang Perkuliahan Modern',
            description: 'Kolom kiri untuk materi dan modul; kolom kanan untuk live chat dan komunikasi kelas.',
            screenRec: {
                target: 'Halaman Course Perkuliahan',
                zoom: { scale: 1.1, area: 'Tampilan dua kolom course' },
                cursor: [{ action: 'scroll_left_modules' }]
            },
            voiceover: 'Masuk ke dalam course, kamu disambut oleh tata letak dua kolom yang efisien. Kolom kiri menampilkan seluruh modul belajar—mulai dari video pengantar yang disematkan dosen, file materi presentasi, hingga instruksi tugas per pertemuan.',
            floating: false
        },
        {
            id: 'scene_4_reading_material',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'MODUL & LAMPIRAN',
            title: 'Belajar Fokus Tanpa Distraksi',
            description: 'Unduh lampiran dokumen resmi dan diskusikan materi secara spesifik.',
            screenRec: {
                target: 'Halaman Item Materi Pembelajaran',
                zoom: { scale: 1.2, area: 'Bagian lampiran berkas dan diskusi' },
                cursor: [{ action: 'hover_attachment' }]
            },
            voiceover: 'Setiap materi memiliki ruangnya sendiri yang bebas distraksi. Kamu bisa langsung mengunduh file lampiran materi pendukung, membaca penjelasan dosen, dan berdiskusi secara spesifik pada topik tersebut.',
            floating: false
        },
        {
            id: 'scene_5_live_chat_forum',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'REALTIME CHAT KELAS',
            title: 'Diskusi Interaktif dan Pesan Tersemat',
            description: 'Balas pesan, mention teman atau dosen, dan pantau pengumuman yang dipin.',
            screenRec: {
                target: 'Kolom Kanan: Forum Diskusi Kelas',
                zoom: { scale: 1.25, area: 'Live chat & pinned message' },
                cursor: [{ action: 'type_mention' }, { action: 'send_chat' }]
            },
            voiceover: 'Di kolom kanan, forum diskusi kelas berjalan secara realtime. Kamu bisa langsung berinteraksi dengan dosen dan teman sekelas tanpa berpindah aplikasi. Gunakan simbol at (@) untuk menyebut rekan kuliah atau dosen, dan perhatikan pesan yang disematkan di bagian atas untuk pengumuman prioritas.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SALE LEARNING EXPERIENCE',
            title: 'Belajar Terarah. Diskusi Lebih Hidup',
            description: 'Dapatkan pengalaman perkuliahan digital terbaik bersama SALE.',
            voiceover: 'Semua materi dalam genggaman, komunikasi mengalir lancar. Pengalaman belajar digitalmu kini jauh lebih menyenangkan.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video02);
    const previewPath = VideoRenderer.renderInteractivePreview(video02);
    console.log(`[Video 02] Manifest VO: ${manifestPath}`);
    console.log(`[Video 02] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_02_mahasiswa_dashboard_course_forum.js')) {
    generate();
}
