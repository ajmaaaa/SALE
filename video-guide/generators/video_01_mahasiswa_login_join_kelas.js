import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video01 = {
    id: '01_mahasiswa_login_join_kelas',
    title: 'Cara Masuk & Bergabung ke Kelas Pertama Kalimu',
    role: 'Mahasiswa',
    duration: '2–3 menit',
    scenes: [
        {
            id: 'scene_1_hook',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'PANDUAN MAHASISWA',
            title: 'Memulai Semester Tanpa Hambatan',
            description: 'Akses seluruh materi perkuliahan dalam satu platform terpusat.',
            voiceover: 'Langkah pertama untuk memulai perkuliahan di kampus tidak perlu repot mencari ruangan secara manual. Seluruh akses pembelajaran sudah terpusat dalam satu platform cerdas.',
            floating: true
        },
        {
            id: 'scene_2_login',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'AUTENTIKASI AKUN',
            title: 'Masuk dengan Nomor Induk Mahasiswa',
            description: 'Gunakan NIM dan kata sandi resmi yang telah diberikan oleh kampus.',
            screenRec: {
                target: 'Halaman Login SALE',
                zoom: { scale: 1.15, area: 'Form login input' },
                cursor: [{ action: 'type_nim' }, { action: 'type_password' }, { action: 'click_login' }]
            },
            voiceover: 'Akses halaman login SALE. Gunakan NIM sebagai username dan masukkan password yang sudah diberikan oleh kampus. Pastikan data yang dimasukkan tepat, lalu klik Masuk.',
            floating: false
        },
        {
            id: 'scene_3_dashboard_course_nav',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'NAVIGASI UTAMA',
            title: 'Menuju Menu Course',
            description: 'Tempat berkumpulnya seluruh mata kuliah dan kelas yang kamu ikuti.',
            screenRec: {
                target: 'Dashboard Mahasiswa ke Menu Course',
                zoom: { scale: 1.2, area: 'Sidebar Navigasi Course' },
                cursor: [{ action: 'click_sidebar_course' }]
            },
            voiceover: 'Inilah akun barumu. Untuk memulai perkuliahan, masuk ke menu Course. Di sini seluruh kelas yang kamu ikuti akan terkumpul rapi. Jika belum ada kelas yang terdaftar, kamu bisa langsung bergabung menggunakan kode dari dosen.',
            floating: false
        },
        {
            id: 'scene_4_join_dialog',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'METODE KODE KELAS',
            title: 'Input Kode 8 Karakter',
            description: 'Klik tombol "+ Gabung Kelas" dan masukkan kode acak dari dosen.',
            screenRec: {
                target: 'Modal Gabung Kelas Perkuliahan',
                zoom: { scale: 1.25, area: 'Input modal kode kelas' },
                cursor: [{ action: 'click_join_btn' }, { action: 'type_code', value: 'A7K9M2QX' }, { action: 'submit_join' }]
            },
            voiceover: 'Cara pertama, klik tombol Gabung Kelas di bagian atas. Masukkan kode unik 8 karakter yang dibagikan oleh dosen pengampumu, lalu klik Gabung Kelas. Seketika kelasmu akan aktif.',
            floating: false
        },
        {
            id: 'scene_5_qr_scan',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'METODE SCAN QR CODE',
            title: 'Pindai QR dan Konfirmasi Seketika',
            description: 'Scan langsung dari proyektor kelas untuk membuka halaman konfirmasi.',
            screenRec: {
                target: 'Halaman Konfirmasi Bergabung Kelas',
                zoom: { scale: 1.2, area: 'Tombol Konfirmasi & Masuk Kelas' },
                cursor: [{ action: 'click_confirm_join' }]
            },
            voiceover: 'Or cara kedua yang lebih cepat di ruang kuliah: cukup pindai QR Code yang ditampilkan dosen di layar kelas. Browser akan otomatis membuka halaman konfirmasi. Periksa detail mata kuliah dan dosennya, lalu klik Konfirmasi & Masuk Kelas.',
            floating: false
        },
        {
            id: 'scene_6_course_ready',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KELAS BERHASIL DIAKSES',
            title: 'Selamat Belajar di SALE',
            description: 'Seluruh materi, tugas, dan ruang diskusi telah siap kamu gunakan.',
            voiceover: 'Selesai. Kamu resmi tergabung ke dalam kelas. Semua materi, forum diskusi, dan tugas perkuliahan sudah siap kamu akses kapan saja.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video01);
    const previewPath = VideoRenderer.renderInteractivePreview(video01);
    console.log(`[Video 01] Manifest VO: ${manifestPath}`);
    console.log(`[Video 01] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_01_mahasiswa_login_join_kelas.js')) {
    generate();
}
