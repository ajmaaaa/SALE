import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video09 = {
    id: '09_dosen_asesmen_input_nilai',
    title: 'Sistem Asesmen dan Input Nilai Mahasiswa',
    role: 'Dosen',
    duration: '4–5 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'EFISIENSI ADMINISTRASI NILAI',
            title: 'Fokus Evaluasi, Bukan Administrasi',
            description: 'Tinggalkan rekapitulasi kertas manual dan beralih ke alur penginputan nilai digital terpadu.',
            voiceover: 'Menghabiskan akhir pekan untuk merekap nilai satu per satu dari kertas ujian? Mari tinggalkan cara lama. SALE merancang alur penilaian agar Anda bisa fokus pada evaluasi, bukan administrasi.',
            floating: true
        },
        {
            id: 'scene_2_penilaian_dashboard',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'DASHBOARD KELAS',
            title: 'Metrik Perkembangan Penilaian',
            description: 'Pantau jumlah mahasiswa terdaftar, total instrumen asesmen, dan CPMK yang aktif.',
            details: [
                'Jumlah Mahasiswa Terdaftar',
                'Jumlah Asesmen yang Ditetapkan',
                'Jumlah Butir CPMK & CPL Digunakan'
            ],
            screenRec: {
                target: 'Dashboard Penilaian Kelas',
                zoom: { scale: 1.15, area: 'Empat kartu metrik dashboard penilaian' },
                cursor: [{ action: 'hover_stat_cards' }]
            },
            voiceover: 'Dari menu Penilaian, pilih kelas yang ingin dinilai. Dashboard penilaian langsung menampilkan gambaran kelas — berapa mahasiswa, berapa asesmen yang sudah dibuat, dan CPMK apa saja yang aktif. Dari sini semua proses penilaian dimulai.',
            floating: false
        },
        {
            id: 'scene_3_input_nilai_table',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'INPUT NILAI PER MAHASISWA',
            title: 'Simpan Draft dan Simpan & Terbitkan',
            description: 'Masukkan nilai di setiap baris mahasiswa dengan kontrol publikasi nilai yang aman.',
            screenRec: {
                target: 'Halaman Input Nilai Asesmen',
                zoom: { scale: 1.2, area: 'Tabel input nilai & tombol simpan' },
                cursor: [{ action: 'type_student_score' }, { action: 'click_save_draft' }]
            },
            voiceover: 'Pilih asesmen yang ingin dinilai, lalu masuk ke halaman Input Nilai. Setiap baris adalah satu mahasiswa. Untuk asesmen reguler, ketik nilainya langsung. Ada dua opsi simpan: "Simpan Draft" untuk menyimpan sementara, atau "Simpan & Terbitkan" agar nilai langsung terlihat oleh mahasiswa.',
            floating: false
        },
        {
            id: 'scene_4_import_excel',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'IMPOR NILAI MASSAL',
            title: 'Unduh Template dan Unggah Nilai Spreadsheet',
            description: 'Solusi efisien untuk kelas paralel dengan puluhan hingga ratusan peserta.',
            screenRec: {
                target: 'Modal Impor Nilai dari Excel',
                zoom: { scale: 1.25, area: 'Tombol download template & kotak upload' },
                cursor: [{ action: 'download_grade_template' }, { action: 'upload_grade_excel' }]
            },
            voiceover: 'Punya ratusan mahasiswa di kelas paralel? Unduh template Excel yang kami sediakan, kerjakan secara offline, dan unggah kembali. Sistem otomatis mendistribusikan nilai tersebut ke setiap mahasiswa dalam hitungan detik.',
            floating: false
        },
        {
            id: 'scene_5_preview_results',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'TRANSPARANSI EVALUASI',
            title: 'Verifikasi Akhir Sebelum Diterbitkan',
            description: 'Pastikan seluruh mahasiswa telah ternilai secara lengkap dan akurat.',
            screenRec: {
                target: 'Tabel Pratinjau Nilai Lengkap',
                zoom: { scale: 1.15, area: 'Indikator progress penilaian' },
                cursor: [{ action: 'review_all_scores' }]
            },
            voiceover: 'Sebelum dipublikasikan, Anda bisa mem-preview seluruh hasil. Pastikan semua kolom terisi, dan selesai. Nilai aman, transparan, dan terintegrasi langsung dengan capaian lulusan.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'PENILAIAN TERPERCAYA',
            title: 'Penilaian Cepat. Data Terintegrasi',
            description: 'Memudahkan dosen dalam mengawal kualitas capaian pembelajaran.',
            voiceover: 'Dengan alur yang rapi, proses penilaian menjadi lebih menyenangkan dan akuntabel di setiap semester.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video09);
    const previewPath = VideoRenderer.renderInteractivePreview(video09);
    console.log(`[Video 09] Manifest VO: ${manifestPath}`);
    console.log(`[Video 09] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_09_dosen_asesmen_input_nilai.js')) {
    generate();
}
