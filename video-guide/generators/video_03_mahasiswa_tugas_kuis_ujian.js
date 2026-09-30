import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video03 = {
    id: '03_mahasiswa_tugas_kuis_ujian',
    title: 'Mengerjakan Tugas, Kuis, dan Ujian di SALE',
    role: 'Mahasiswa',
    duration: '4–5 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'EVALUASI TERPADU',
            title: 'Kendali Penuh Atas Seluruh Evaluasi',
            description: 'Lihat seluruh penugasan, batas waktu, dan capaian nilai dari satu tempat.',
            voiceover: 'Memantau berbagai tugas dan jadwal ujian dari banyak mata kuliah bisa terasa membebani. Di SALE, seluruh evaluasi pembelajaran dikumpulkan dalam satu daftar terpadu dengan status yang transparan.',
            floating: true
        },
        {
            id: 'scene_2_filter_list',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PENGELOMPOKAN EVALUASI',
            title: 'Filter Mata Kuliah dan Jenis Pekerjaan',
            description: 'Tab Semua Pekerjaan & Nilai dengan filter jenis: Tugas, Tugas coding, dan Kuis.',
            screenRec: {
                target: 'Halaman Tugas & Kuis Mahasiswa',
                zoom: { scale: 1.15, area: 'Filter navigasi & daftar tugas' },
                cursor: [{ action: 'select_filter_type', value: 'tugas' }, { action: 'click_assignment_row' }]
            },
            voiceover: 'Kamu bisa memfilter tugas berdasarkan mata kuliah atau jenisnya. Di sini, status pengerjaan dan batas waktu terlihat jelas, mencegahmu melewatkan tenggat penting. Mari buka salah satu tugas.',
            floating: false
        },
        {
            id: 'scene_3_submit_task',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'PENGUMPULAN TUGAS REGULER',
            title: 'Unggah Berkas dan Serahkan',
            description: 'Panel instruksi di kiri dan panel pengunggahan dokumen di kanan.',
            screenRec: {
                target: 'Halaman Detail Tugas Reguler',
                zoom: { scale: 1.2, area: 'Panel unggah berkas & tombol serahkan' },
                cursor: [{ action: 'upload_document_pdf' }, { action: 'click_submit_btn' }]
            },
            voiceover: 'Pada halaman tugas reguler, baca instruksi pengerjaan dengan cermat. Di panel samping, cukup unggah berkas tugasmu, lalu klik "Serahkan Tugas". Status pengumpulan akan langsung tercatat otomatis ke akun dosenmu.',
            floating: false
        },
        {
            id: 'scene_4_quiz_info',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'INFORMASI UJIAN',
            title: 'Kuis & Ujian Terstruktur',
            description: 'Periksa durasi pengerjaan, jumlah butir soal, total poin, dan tenggat waktu.',
            screenRec: {
                target: 'Halaman Detail Kuis / UTS / UAS',
                zoom: { scale: 1.25, area: 'Kartu Informasi Ujian & Tombol Mulai' },
                cursor: [{ action: 'click_start_quiz_btn' }]
            },
            voiceover: 'Untuk kuis dan ujian terstruktur, kamu akan melihat rincian durasi waktu, jumlah butir soal, serta tanggal tenggat. Pastikan koneksi internetmu stabil, lalu klik "Mulai Kerjakan Kuis" untuk memasuki ruang ujian.',
            floating: false
        },
        {
            id: 'scene_5_quiz_room',
            layout: 'full-left',
            bgColor: '#0f172a',
            textColor: '#ffffff',
            mutedColor: '#94a3b8',
            badge: 'RUANG UJIAN FOKUS',
            title: 'Timer Mundur dan Ragam Butir Soal',
            description: 'Pilihan ganda, benar/salah, menjodohkan, hingga esai dengan navigasi nomor yang responsif.',
            screenRec: {
                target: 'Ruang Ujian Layar Penuh (Quiz Room)',
                zoom: { scale: 1.15, area: 'Header timer hitung mundur & navigasi soal' },
                cursor: [{ action: 'answer_choice_a' }, { action: 'jump_next_question' }, { action: 'type_essay' }]
            },
            voiceover: 'Ruang ujian SALE dirancang bersih dan fokus. Perhatikan timer hitung mundur di bagian atas. Kamu bisa melompat antar soal menggunakan panel navigasi nomor. Sistem mendukung berbagai tipe soal, mulai dari pilihan ganda, benar-salah, menjodohkan, hingga uraian esai.',
            floating: false
        },
        {
            id: 'scene_6_evaluation',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'EVALUASI PURNA-KUIS',
            title: 'Hasil Instan dan Evaluasi Objektif',
            description: 'Lihat perolehan skor langsung dan ulas pemahaman materi secara transparan.',
            voiceover: 'Setelah semua terjawab, klik "Selesai & Kumpulkan". Untuk soal pilihan ganda, hasil evaluasi dapat langsung diketahui seketika, sementara soal esai akan diperiksa lebih lanjut oleh dosenmu.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video03);
    const previewPath = VideoRenderer.renderInteractivePreview(video03);
    console.log(`[Video 03] Manifest VO: ${manifestPath}`);
    console.log(`[Video 03] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_03_mahasiswa_tugas_kuis_ujian.js')) {
    generate();
}
