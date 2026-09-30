import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video04 = {
    id: '04_mahasiswa_tugas_coding_ai',
    title: 'Mengerjakan Tugas Pemrograman dengan AI Asisten',
    role: 'Mahasiswa',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'PRAKTIKUM CODING CERDAS',
            title: 'Lingkungan Pemrograman Interaktif',
            description: 'Uji logika kode langsung di browser dengan pendampingan konsep dari AI Asisten.',
            voiceover: 'Belajar coding seringkali penuh dengan pesan error yang membingungkan. Terjebak berjam-jam karena satu titik koma? Tidak perlu lagi. SALE dilengkapi dengan lingkungan pemrograman interaktif beserta Asisten AI.',
            floating: true
        },
        {
            id: 'scene_2_three_panel_workbench',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'TATA LETAK 3 PANEL',
            title: 'Ruang Kerja Pemrograman Lengkap',
            description: 'Instruksi di kiri, editor kode & terminal di tengah, AI Asisten di kanan.',
            details: [
                'Panel Kiri: Soal, stimulus & instruksi bertahap',
                'Panel Tengah: Editor Monaco & Terminal Linux terintegrasi',
                'Panel Kanan: AI Asisten pendamping konsep'
            ],
            screenRec: {
                target: 'Workbench Editor Kode Layar Penuh',
                zoom: { scale: 1.1, area: 'Tiga panel workbench' },
                cursor: [{ action: 'hover_instructions' }, { action: 'focus_monaco_editor' }]
            },
            voiceover: 'Saat kamu membuka tugas coding, kamu langsung masuk ke ruang kerja tiga panel. Instruksi soal ada di kiri, editor kode di tengah, dan AI Asisten siap di kanan. Semuanya tanpa perlu buka aplikasi lain.',
            floating: false
        },
        {
            id: 'scene_3_run_code_terminal',
            layout: 'full-left',
            bgColor: '#0d1117',
            textColor: '#c9d1d9',
            mutedColor: '#8b949e',
            badge: 'TERMINAL LINUX',
            title: 'Kompilasi & Eksekusi Instan',
            description: 'Jalankan program dan pantau hasil keluaran langsung di tab Konsol atau Pratinjau.',
            screenRec: {
                target: 'Toolbar Editor & Terminal Bawah',
                zoom: { scale: 1.25, area: 'Tombol Run Code dan panel terminal' },
                cursor: [{ action: 'type_sample_code' }, { action: 'click_run_btn' }]
            },
            voiceover: 'Coba tulis kodemu dan jalankan secara langsung. Terjadi error? Outputnya langsung muncul di terminal bawah. Jangan panik, itu adalah bagian dari proses belajar.',
            floating: false
        },
        {
            id: 'scene_4_ai_assistant_dialog',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'AI ASISTEN PEMROGRAMAN',
            title: 'Bimbingan Konsep & Fitur Mention Kode',
            description: 'Kirim baris kode terpilih ke AI untuk memahami akar masalah secara edukatif.',
            screenRec: {
                target: 'Panel Kanan: AI Asisten Chat',
                zoom: { scale: 1.25, area: 'Chat AI Asisten & Tombol Mention Kode' },
                cursor: [{ action: 'select_code_line' }, { action: 'click_mention_btn' }, { action: 'send_ai_prompt' }]
            },
            voiceover: 'Di sinilah AI Asisten bekerja. Kamu bisa langsung mengetik pertanyaanmu, atau pilih baris kode tertentu lalu klik "Mention kode" agar AI langsung tahu konteksnya. Ingat, AI ini bukan mesin contek — ia menuntunmu memahami konsep yang salah agar kamu benar-benar bisa coding sendiri.',
            floating: false
        },
        {
            id: 'scene_5_fix_and_submit',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PENGUMPULAN AKHIR',
            title: 'Perbaiki dan Kumpulkan Pekerjaan',
            description: 'Uji ulang hingga output sesuai, lalu serahkan tugas kepada dosen pengampu.',
            screenRec: {
                target: 'Tombol Selesai & Form Pengumpulan',
                zoom: { scale: 1.2, area: 'Tombol Kumpulkan Tugas' },
                cursor: [{ action: 'click_run_pass' }, { action: 'click_complete_submission' }]
            },
            voiceover: 'Setelah paham letak kesalahannya, perbaiki kodemu. Jalankan kembali hingga hasilnya benar. Setelah selesai, kumpulkan tugasmu melalui tombol yang tersedia — dan selesai.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SMART CODING JOURNEY',
            title: 'Coding Lebih Pintar, Bukan Lebih Keras',
            description: 'Pendamping belajar pemrograman pribadi selalu siap di sampingmu.',
            voiceover: 'Dengan SALE, belajar pemrograman terasa seperti selalu didampingi oleh asisten dosen pribadi kapan pun kamu butuhkan.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video04);
    const previewPath = VideoRenderer.renderInteractivePreview(video04);
    console.log(`[Video 04] Manifest VO: ${manifestPath}`);
    console.log(`[Video 04] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_04_mahasiswa_tugas_coding_ai.js')) {
    generate();
}
