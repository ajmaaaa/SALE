import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video17 = {
    id: '17_admin_kelola_pengguna_akademik',
    title: 'Mengelola Pengguna, Semester, dan Data Akademik Global',
    role: 'Admin Sistem',
    duration: '4–5 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'ADMINISTRATOR SISTEM',
            title: 'Kendali Terpusat Seluruh Kampus',
            description: 'Pusat tata kelola infrastruktur pengguna, penetapan semester, dan master data universitas.',
            voiceover: 'Sebagai Admin Sistem, Anda memegang kendali atas pondasi operasional seluruh kampus. Mengelola ratusan akun dan menetapkan periode akademik kini dapat dilakukan secara terpusat tanpa hambatan teknis.',
            floating: true
        },
        {
            id: 'scene_2_global_user_management',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PENGGUNA & HAK AKSES',
            title: 'Registrasi Akun Lintas Peran',
            description: 'Tambah manual atau kelola akun Dosen, Mahasiswa, Admin Prodi, dan Admin Sistem.',
            screenRec: {
                target: 'Halaman Pengguna & Hak Akses',
                zoom: { scale: 1.15, area: 'Modal Tambah Pengguna & dropdown Role' },
                cursor: [{ action: 'open_add_user_modal' }, { action: 'select_user_role' }, { action: 'save_user' }]
            },
            voiceover: 'Masuki menu Pengguna dan Hak Akses. Di sini Anda dapat menambahkan akun baru untuk peran apa pun—mulai dari Dosen, Mahasiswa, Admin Program Studi, hingga sesama Administrator Sistem. Tetapkan identitas dan hak aksesnya dengan aman.',
            floating: false
        },
        {
            id: 'scene_3_bulk_import_university',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'IMPOR MASSAL TINGKAT UNIVERSITAS',
            title: 'Pendaftaran Mahasiswa Baru Skala Besar',
            description: 'Template Excel terpadu untuk integrasi data penerimaan mahasiswa baru seluruh fakultas.',
            screenRec: {
                target: 'Modal Impor Massal Pengguna',
                zoom: { scale: 1.25, area: 'Tombol template excel & kotak upload berkas' },
                cursor: [{ action: 'download_global_template' }, { action: 'upload_bulk_users' }]
            },
            voiceover: 'Untuk penerimaan mahasiswa baru tingkat universitas, gunakan fitur Impor Massal. Unduh template resmi kami, masukkan data mahasiswa seluruh fakultas, dan unggah kembali. Sistem akan memproses dan membuat akun secara instan dengan verifikasi pencegahan duplikasi.',
            floating: false
        },
        {
            id: 'scene_4_semester_active_toggle',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'DATA AKADEMIK: SEMESTER',
            title: 'Penetapan Periode dan Semester Aktif',
            description: 'Buat semester baru dan tentukan semester mana yang aktif sebagai acuan operasional sistem.',
            screenRec: {
                target: 'Halaman Data Akademik: Tab Semester',
                zoom: { scale: 1.2, area: 'Modal Tambah Semester & toggle Semester Aktif' },
                cursor: [{ action: 'click_add_semester' }, { action: 'toggle_semester_active' }, { action: 'save_semester' }]
            },
            voiceover: 'Selanjutnya, buka menu Data Akademik untuk mengatur periode perkuliahan global. Anda dapat menambahkan semester baru dan menetapkan semester mana yang sedang berjalan aktif. Penetapan ini akan otomatis menjadi acuan bagi seluruh kelas dan kurikulum di semua program studi.',
            floating: false
        },
        {
            id: 'scene_5_master_prodi',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'DATA AKADEMIK: PROGRAM STUDI',
            title: 'Master Data Fakultas dan Jurusan',
            description: 'Daftarkan program studi baru beserta kode uniknya ke dalam ekosistem kampus.',
            screenRec: {
                target: 'Tab Program Studi: Daftar Prodi',
                zoom: { scale: 1.15, area: 'Tabel daftar prodi & tombol tambah prodi' },
                cursor: [{ action: 'click_tab_prodi' }, { action: 'hover_prodi_list' }]
            },
            voiceover: 'Pada tab Program Studi, Admin Sistem dapat mendaftarkan jurusan atau prodi baru beserta kode resminya. Setiap prodi yang ditambahkan langsung memiliki ruang tata kelola kurikulum dan data akademiknya sendiri.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'TATA KELOLA GLOBAL KAMPUS',
            title: 'Infrastruktur Kokoh. Tata Kelola Terpusat',
            description: 'Fondasi akademik yang andal untuk mendukung kemajuan universitas.',
            voiceover: 'Pengguna terorganisir, semester terkelola dengan presisi. Tata kelola sistem kampus Anda kini berjalan dengan standar keandalan tertinggi.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video17);
    const previewPath = VideoRenderer.renderInteractivePreview(video17);
    console.log(`[Video 17] Manifest VO: ${manifestPath}`);
    console.log(`[Video 17] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_17_admin_kelola_pengguna_akademik.js')) {
    generate();
}
