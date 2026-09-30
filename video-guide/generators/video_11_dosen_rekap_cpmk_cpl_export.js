import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video11 = {
    id: '11_dosen_rekap_cpmk_cpl_export',
    title: 'Melihat Rekap CPMK, CPL, dan Ekspor Laporan',
    role: 'Dosen',
    duration: '4–5 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KOMPUTASI OBE OTOMATIS',
            title: 'Ketercapaian Lulusan Tanpa Rumus Manual',
            description: 'Sistem SALE menghitung ketercapaian kompetensi mahasiswa secara otomatis.',
            voiceover: 'Menilai ujian adalah satu hal, tetapi memastikan mahasiswa benar-benar mencapai Capaian Pembelajaran Lulusan adalah tantangan yang jauh lebih besar. Di sinilah SALE melakukan komputasi berat untuk Anda.',
            floating: true
        },
        {
            id: 'scene_2_rekap_cpmk_matrix',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'TAB REKAP CPMK',
            title: 'Matriks Nilai Asesmen dan Sub-Kolom CPMK',
            description: 'Tinjau sebaran skor mahasiswa dan persentase penguasaan tiap indikator kompetensi.',
            screenRec: {
                target: 'Halaman Rekap Capaian per CPMK',
                zoom: { scale: 1.1, area: 'Header ganda asesmen & sub-kolom CPMK' },
                cursor: [{ action: 'scroll_horizontal_cpmk_table' }, { action: 'hover_cpmk_percentages' }]
            },
            voiceover: 'Masuki tab Rekap CPMK. Sistem menyajikan tabel komprehensif yang memetakan nilai mahasiswa ke butir-butir CPMK yang diukur pada setiap tugas, kuis, maupun ujian. Anda dapat melihat secara transparan sejauh mana setiap mahasiswa menguasai kompetensi yang ditargetkan.',
            floating: false
        },
        {
            id: 'scene_3_rekap_cpl_threshold',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'TAB REKAP CPL',
            title: 'Akumulasi Capaian Lulusan Program Studi',
            description: 'Konversi otomatis dari skor CPMK ke target CPL prodi dengan garis threshold.',
            screenRec: {
                target: 'Halaman Rekap CPL Mata Kuliah',
                zoom: { scale: 1.15, area: 'Tabel nilai CPL & batas kelulusan' },
                cursor: [{ action: 'view_cpl_summary' }]
            },
            voiceover: 'Selanjutnya, buka tab Rekap CPL. Di sini, nilai-nilai CPMK dikonversi secara otomatis menjadi capaian CPL program studi berdasarkan bobot kurikulum yang telah ditetapkan oleh Admin Prodi. Dosen dapat langsung mengevaluasi efektivitas pengajarannya terhadap standar prodi.',
            floating: false
        },
        {
            id: 'scene_4_export_options',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'MENU EXPORT DATA',
            title: 'Empat Pilihan Rekapitulasi Resmi',
            description: 'Unduh laporan berformat Excel (.xlsx) resmi dengan kop surat universitas.',
            details: [
                'Rekap Nilai & CPMK: Nilai akhir, grade, dan predikat',
                'Rekap Capaian CPMK: Kop surat & warna visual',
                'Rekap Capaian CPL: Evaluasi ketercapaian prodi',
                'Rekap Nilai Asesmen: Rincian per instrumen'
            ],
            screenRec: {
                target: 'Halaman Export Data Penilaian',
                zoom: { scale: 1.2, area: 'Kartu opsi ekspor & tombol unduh Excel' },
                cursor: [{ action: 'click_export_excel_keseluruhan' }]
            },
            voiceover: 'Untuk keperluan pelaporan atau arsip akreditasi, buka menu Export. SALE menyediakan format Excel resmi lengkap dengan kop surat institusi, identitas mata kuliah, serta penataan warna yang rapi dan siap diserahkan ke program studi tanpa perlu diedit ulang.',
            floating: false
        },
        {
            id: 'scene_5_excel_preview',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'DOKUMEN RESMI SIAP CETAK',
            title: 'Standar Akreditasi Internasional',
            description: 'Format rapi dan elegan yang menghemat waktu administrasi di akhir semester.',
            screenRec: {
                target: 'Pratinjau Berkas Excel Rekapitulasi Resmi',
                zoom: { scale: 1.15, area: 'Kop surat resmi & tabel rapi' },
                cursor: [{ action: 'scroll_excel_preview' }]
            },
            voiceover: 'Laporan selesai dalam hitungan detik. Transparan, terstandarisasi, dan sepenuhnya selaras dengan kurikulum OBE kampus Anda.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SALE OBE ANALYTICS',
            title: 'Laporan Selesai. Kualitas Terjaga',
            description: 'Kemudahan menyeluruh bagi dosen dalam menuntaskan tugas akhir semester.',
            voiceover: 'Dengan SALE, penjaminan mutu perkuliahan berjalan lancar dari awal hingga akhir semester.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video11);
    const previewPath = VideoRenderer.renderInteractivePreview(video11);
    console.log(`[Video 11] Manifest VO: ${manifestPath}`);
    console.log(`[Video 11] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_11_dosen_rekap_cpmk_cpl_export.js')) {
    generate();
}
