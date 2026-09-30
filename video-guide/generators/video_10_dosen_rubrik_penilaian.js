import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video10 = {
    id: '10_dosen_rubrik_penilaian',
    title: 'Membuat Rubrik Penilaian dan Penilaian Berbasis Rubrik',
    role: 'Dosen',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'OBJEKTIVITAS PENILAIAN',
            title: 'Keadilan dan Transparansi Penilaian',
            description: 'Berikan ekspektasi yang jelas kepada mahasiswa melalui rubrik kriteria berbobot.',
            voiceover: 'Mahasiswa sering bertanya mengapa mereka mendapat nilai B sementara temannya mendapat A? Penilaian tanpa standar yang jelas memicu kebingungan. Dengan rubrik di SALE, Anda memberikan ekspektasi yang transparan sejak hari pertama.',
            floating: true
        },
        {
            id: 'scene_2_open_rubric_form',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'MENU RUBRIK ASESMEN',
            title: 'Akses Kelola Rubrik per Asesmen',
            description: 'Dari Daftar Asesmen, buka menu aksi dan pilih "Kelola Rubrik".',
            screenRec: {
                target: 'Halaman Kelola Rubrik Asesmen',
                zoom: { scale: 1.15, area: 'Header nama rubrik & section kriteria' },
                cursor: [{ action: 'open_rubric_settings' }, { action: 'click_add_criterion_btn' }]
            },
            voiceover: 'Akses rubrik dari menu Asesmen. Setiap asesmen bisa punya rubriknya sendiri. Tambahkan kriteria penilaian — misalnya Penguasaan Materi, Analisis, atau Presentasi — lalu tentukan bobotnya. Total bobot semua kriteria harus tepat 100%.',
            floating: false
        },
        {
            id: 'scene_3_add_criteria_weights',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'KRITERIA & BOBOT 100%',
            title: 'Validasi Bobot Otomatis',
            description: 'Sistem memastikan total seluruh kriteria tepat 100% sebelum disimpan.',
            screenRec: {
                target: 'Tabel Kriteria Penilaian Rubrik',
                zoom: { scale: 1.2, area: 'Indikator Total Bobot di kanan atas' },
                cursor: [{ action: 'fill_criterion_1' }, { action: 'fill_criterion_2' }, { action: 'save_rubric_btn' }]
            },
            voiceover: 'Isi nama setiap kriteria beserta bobot persentasenya. Indikator total di atas akan berubah real-time — sistem memastikan Anda tidak bisa menyimpan rubrik jika total bobotnya tidak tepat 100%. Setelah simpan, rubrik ini siap digunakan untuk menilai mahasiswa.',
            floating: false
        },
        {
            id: 'scene_4_input_rubric_scores',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'INPUT NILAI BERBASIS RUBRIK',
            title: 'Skor per Kriteria Tanpa Kalkulator',
            description: 'Ketik nilai pada kolom kriteria, total nilai akhir terhitung otomatis berdasarkan bobot.',
            screenRec: {
                target: 'Halaman Input Nilai Rubrik',
                zoom: { scale: 1.25, area: 'Tabel input nilai rubrik per mahasiswa' },
                cursor: [{ action: 'type_rubric_criterion_score' }, { action: 'observe_final_score_update' }]
            },
            voiceover: 'Untuk input nilai berbasis rubrik, masuk ke halaman "Input Nilai Rubrik". Setiap mahasiswa punya kolom nilai per kriteria. Masukkan nilainya satu per satu — skor akhir terhitung otomatis berdasarkan bobot yang sudah ditetapkan. Tidak perlu kalkulator, tidak perlu spreadsheet terpisah.',
            floating: false
        },
        {
            id: 'scene_5_save_and_feedback',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'HASIL PENILAIAN TRANSPARAN',
            title: 'Mahasiswa Mengetahui Area Pengembangan',
            description: 'Nilai rubrik langsung terhubung ke capaian CPMK mata kuliah.',
            screenRec: {
                target: 'Tombol Simpan Nilai Rubrik',
                zoom: { scale: 1.15, area: 'Pesan sukses simpan nilai rubrik' },
                cursor: [{ action: 'click_save_rubric_scores' }]
            },
            voiceover: 'Hasil akhirnya langsung terhubung ke sistem. Mahasiswa tidak hanya melihat angka, tapi mengerti di area mana mereka harus berkembang.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'STANDAR AKADEMIK OBJEKTIF',
            title: 'Keadilan Penilaian untuk Semua',
            description: 'Tingkatkan kualitas evaluasi pembelajaran dengan rubrik terstandar di SALE.',
            voiceover: 'Penilaian transparan membangun rasa percaya dan mendorong mahasiswa mencapai potensi terbaik mereka.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video10);
    const previewPath = VideoRenderer.renderInteractivePreview(video10);
    console.log(`[Video 10] Manifest VO: ${manifestPath}`);
    console.log(`[Video 10] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_10_dosen_rubrik_penilaian.js')) {
    generate();
}
