import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video06 = {
    id: '06_dosen_kelola_course',
    title: 'Mengelola Course: Buat, Atur, dan Bagikan ke Mahasiswa',
    role: 'Dosen',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'TATA KELOLA KELAS DOSEN',
            title: 'Persiapan Kelas Cepat dan Praktis',
            description: 'Buat ruang kelas daring dan bagikan akses ke mahasiswa dalam hitungan detik.',
            voiceover: 'Persiapan awal semester yang rapi akan membuat jalannya perkuliahan terasa ringan. Di SALE, Anda dapat menyiapkan ruang perkuliahan digital hanya dalam beberapa langkah sederhana.',
            floating: true
        },
        {
            id: 'scene_2_create_course_form',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'FORM COURSE BARU',
            title: 'Identitas Mata Kuliah dan Sampul',
            description: 'Kode course, judul mata kuliah, nama pengampu, deskripsi, dan link video YouTube pengantar.',
            screenRec: {
                target: 'Halaman Form Tambah Course',
                zoom: { scale: 1.15, area: 'Form input course & upload cover' },
                cursor: [{ action: 'fill_course_identity' }, { action: 'submit_course' }]
            },
            voiceover: 'Mulai dengan menekan tombol Tambah Course. Masukkan kode mata kuliah, judul kelas, nama pengampu, dan deskripsi singkat. Anda juga bisa mempercantik kelas dengan gambar sampul serta menyematkan tautan video pengantar kuliah agar mahasiswa langsung mendapatkan gambaran materi.',
            floating: false
        },
        {
            id: 'scene_3_share_enrollment_code_qr',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'AKSES MAHASISWA',
            title: 'Kode Pendaftaran 8 Karakter & QR Code',
            description: 'Salin kode unik kelas atau tampilkan QR Code ke proyektor tatap muka.',
            screenRec: {
                target: 'Kartu Course & Modal QR Code',
                zoom: { scale: 1.25, area: 'Modal QR Code di tengah layar' },
                cursor: [{ action: 'click_copy_code' }, { action: 'open_qr_modal' }]
            },
            voiceover: 'Setelah course dibuat, sistem secara otomatis menerbitkan kode akses unik 8 karakter serta QR Code resmi kelas. Anda dapat langsung menyalin kodenya untuk dibagikan ke mahasiswa atau menampilkan QR Code di proyektor saat pertemuan tatap muka pertama.',
            floating: false
        },
        {
            id: 'scene_4_live_chat_and_pin',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'KOMUNIKASI KELAS',
            title: 'Live Chat dan Sematkan Pengumuman',
            description: 'Sapa mahasiswa baru dan pin pesan prioritas di urutan teratas obrolan.',
            screenRec: {
                target: 'Kolom Kanan Course: Live Chat',
                zoom: { scale: 1.2, area: 'Menu titik tiga pesan chat & aksi pin' },
                cursor: [{ action: 'send_welcome_chat' }, { action: 'click_pin_message' }]
            },
            voiceover: 'Masuki ruang kelas Anda. Di sisi kanan, tersedia fitur Live Chat kelas untuk berinteraksi langsung dengan mahasiswa. Dosen dapat menyematkan atau melakukan \'pin\' pada pengumuman penting agar selalu berada di posisi teratas dan tidak tenggelam oleh percakapan lain.',
            floating: false
        },
        {
            id: 'scene_5_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KELAS SIAP DIAJAR',
            title: 'Kelas Siap. Mahasiswa Terhubung',
            description: 'Lanjutkan dengan mengunggah materi ajar dan instrumen penilaian.',
            voiceover: 'Ruang perkuliahan telah siap dan mahasiswa siap bergabung. Kini Anda dapat mulai menyusun materi dan rangkaian evaluasi pembelajaran.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video06);
    const previewPath = VideoRenderer.renderInteractivePreview(video06);
    console.log(`[Video 06] Manifest VO: ${manifestPath}`);
    console.log(`[Video 06] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_06_dosen_kelola_course.js')) {
    generate();
}
