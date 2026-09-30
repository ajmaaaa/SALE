import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video05 = {
    id: '05_mahasiswa_nilai_notifikasi_profil',
    title: 'Melihat Nilai, Notifikasi, dan Profil Akun',
    role: 'Mahasiswa',
    duration: '2–3 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'HASIL STUDI & AKUN',
            title: 'Transparansi Nilai dan Kendali Akun',
            description: 'Akses transkrip evaluasi, notifikasi kegiatan, dan data diri secara mandiri.',
            voiceover: 'Memantau perkembangan akademik seharusnya bisa dilakukan kapan saja, tanpa harus menunggu akhir semester. Mari kita lihat halaman Transkrip Nilaimu.',
            floating: true
        },
        {
            id: 'scene_2_khs_accordion',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'KARTU HASIL STUDI (KHS)',
            title: 'Filter Semester & Rincian Komponen Nilai',
            description: 'Klik baris mata kuliah mana pun untuk membuka rincian nilai tugas, kuis, dan ujian.',
            screenRec: {
                target: 'Halaman Transkrip Nilai & Hasil Studi',
                zoom: { scale: 1.15, area: 'Dropdown semester & baris tabel KHS' },
                cursor: [{ action: 'change_semester_filter' }, { action: 'click_table_row_expand' }]
            },
            voiceover: 'Di halaman Transkrip Nilai, kamu bisa memilih tahun dan semester untuk melihat hasil evaluasi belajarmu. Cukup klik pada baris mata kuliah mana pun untuk membuka rincian komponen nilainya secara transparan, mulai dari tugas, kuis, hingga ujian akhir.',
            floating: false
        },
        {
            id: 'scene_3_realtime_notifications',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'NOTIFIKASI REALTIME',
            title: 'Kategori Pembelajaran & Notifikasi Live',
            description: 'Pembaruan otomatis dari tugas, diskusi forum, dan pengumuman sistem.',
            screenRec: {
                target: 'Halaman Notifikasi Pembelajaran',
                zoom: { scale: 1.2, area: 'Filter kategori notifikasi & tombol aksi' },
                cursor: [{ action: 'filter_category' }, { action: 'mark_all_read' }]
            },
            voiceover: 'Tetap ikuti setiap perkembangan lewat menu Notifikasi. Pembaruan tugas, balasan forum, hingga pengumuman dosen tersaji secara realtime dan terkelompok berdasarkan kategori. Kamu bisa langsung menandai semua telah dibaca atau menghapus notifikasi lama dengan satu klik.',
            floating: false
        },
        {
            id: 'scene_4_profile_and_security',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PROFIL & KEAMANAN',
            title: 'Foto Profil, Narahubung, dan Password',
            description: 'Kelola identitas, cek email narahubung bantuan, dan amankan akun.',
            details: [
                'Tab Informasi Profil: Foto avatar & bantuan resmi',
                'Tab Keamanan Akun: Form pembaruan kata sandi',
                'Tab Notifikasi: Pengaturan preferensi pesan'
            ],
            screenRec: {
                target: 'Halaman Profil & Pengaturan',
                zoom: { scale: 1.15, area: 'Tab profil, email narahubung & keamanan' },
                cursor: [{ action: 'open_tab_security' }]
            },
            voiceover: 'Pada menu Profil dan Pengaturan, kamu bisa mengganti foto profil, mengecek informasi narahubung resmi kampus jika butuh bantuan, serta memperbarui kata sandi secara berkala di tab Keamanan Akun agar akun belajarmu selalu terlindungi.',
            floating: false
        },
        {
            id: 'scene_5_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SALE STUDENT PORTAL',
            title: 'Transparan. Terintegrasi. Aman',
            description: 'Seluruh kebutuhan akademik mahasiswa dalam satu ekosistem terpercaya.',
            voiceover: 'Pantau nilai dengan jelas, dapatkan update tepat waktu, dan kendalikan akunmu dengan mudah di SALE.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video05);
    const previewPath = VideoRenderer.renderInteractivePreview(video05);
    console.log(`[Video 05] Manifest VO: ${manifestPath}`);
    console.log(`[Video 05] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_05_mahasiswa_nilai_notifikasi_profil.js')) {
    generate();
}
