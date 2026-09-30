import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video07 = {
    id: '07_dosen_buat_konten_materi',
    title: 'Membuat Konten: Materi, Pengumuman, dan Video',
    role: 'Dosen',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KONTEN PERKULIAHAN KAYA MEDIA',
            title: 'Materi Interaktif dan Terstruktur',
            description: 'Padukan berkas dokumen, modul teks, dan video pengantar ke dalam setiap topik pertemuan.',
            voiceover: 'Menyajikan materi perkuliahan yang interaktif dan kaya referensi akan meningkatkan fokus belajar mahasiswa. Di SALE, Anda dapat menyusun materi dokumen, video tersemat, hingga pengumuman kelas dalam satu form yang terpadu.',
            floating: true
        },
        {
            id: 'scene_2_add_item_type',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PILIHAN JENIS KONTEN',
            title: 'Materi Reguler vs Praktikum Coding',
            description: 'Pilih jenis konten sesuai tujuan capaian pembelajaran tiap sesi tatap muka.',
            screenRec: {
                target: 'Halaman Form Tambah Konten',
                zoom: { scale: 1.2, area: 'Dropdown tipe konten & radio mode materi' },
                cursor: [{ action: 'select_content_type_materi' }, { action: 'choose_mode_regular' }]
            },
            voiceover: 'Klik Tambah Konten dari halaman course. Pilih tipe "Materi". Untuk perkuliahan teori dan modul standar, pilih jenis materi reguler. Sistem juga mendukung materi berbasis praktikum pemrograman interaktif jika diperlukan.',
            floating: false
        },
        {
            id: 'scene_3_title_and_attachments',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'UNGGAH BERKAS LAMPIRAN',
            title: 'Modul PDF, Slide Presentasi, dan Dokumen',
            description: 'Tuliskan petunjuk pembelajaran dan lampirkan multi-file dengan proses unggah yang cepat.',
            screenRec: {
                target: 'Form Input Judul & Upload Lampiran',
                zoom: { scale: 1.15, area: 'Textarea instruksi & kotak unggah file' },
                cursor: [{ action: 'type_title_description' }, { action: 'drag_drop_attachments' }]
            },
            voiceover: 'Tuliskan judul dan petunjuk belajar Anda. Anda dapat mengunggah berbagai format file pendukung sekaligus—seperti PDF, presentasi PPT, hingga dokumen dokumen pendukung lainnya—yang dapat langsung diunduh atau dipratinjau oleh mahasiswa.',
            floating: false
        },
        {
            id: 'scene_4_video_url_and_pin',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'VIDEO YOUTUBE & PIN BERANDA',
            title: 'Sematkan Video ke Posisi Teratas Course',
            description: 'Tempel link video YouTube dan centang "Pin Video" agar mahasiswa langsung menonton.',
            screenRec: {
                target: 'Input Tautan Video & Checkbox Pin Video',
                zoom: { scale: 1.25, area: 'Checkbox Pin Video & tombol Simpan' },
                cursor: [{ action: 'paste_youtube_link' }, { action: 'check_pin_video' }, { action: 'submit_item' }]
            },
            voiceover: 'Jika memiliki rekaman kuliah atau video penjelas di YouTube, cukup tempel tautannya. Anda bahkan bisa mencentang opsi "Pin Video" agar player video tersebut disematkan langsung di bagian atas beranda course, memastikan mahasiswa menyimaknya sebelum membaca modul.',
            floating: false
        },
        {
            id: 'scene_5_announcement_push',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'PENGUMUMAN KELAS',
            title: 'Kirim Pengumuman dengan Notifikasi Instan',
            description: 'Sampaikan kabar penting perkuliahan langsung ke lonceng notifikasi seluruh peserta.',
            screenRec: {
                target: 'Form Konten: Tipe Pengumuman',
                zoom: { scale: 1.2, area: 'Form pengumuman & badge notifikasi' },
                cursor: [{ action: 'type_announcement' }, { action: 'publish_announcement' }]
            },
            voiceover: 'Untuk informasi yang membutuhkan perhatian segera, gunakan tipe Pengumuman. Setiap pengumuman yang Anda terbitkan akan memicu notifikasi realtime ke seluruh mahasiswa di kelas Anda.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KELAS BERMUTU TINGGI',
            title: 'Materi Tertata. Mahasiswa Siap Belajar',
            description: 'Berikan pengalaman belajar daring terbaik di setiap pertemuan.',
            voiceover: 'Dengan modul yang terstruktur dan media yang kaya, kelas daring Anda kini siap memberikan pengalaman belajar yang optimal.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video07);
    const previewPath = VideoRenderer.renderInteractivePreview(video07);
    console.log(`[Video 07] Manifest VO: ${manifestPath}`);
    console.log(`[Video 07] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_07_dosen_buat_konten_materi.js')) {
    generate();
}
