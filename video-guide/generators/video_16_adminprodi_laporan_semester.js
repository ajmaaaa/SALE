import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video16 = {
    id: '16_adminprodi_laporan_semester',
    title: 'Melihat Laporan Akademik Semester Prodi',
    role: 'Admin Prodi',
    duration: '2–3 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'EVALUASI AKADEMIK SEMESTER',
            title: 'Laporan Komprehensif Tanpa Menunggu',
            description: 'Pantau kinerja akademik semester program studi secara instan dan berbasis data.',
            voiceover: 'Akhir semester adalah momen krusial untuk mengevaluasi kinerja akademik. Di SALE, rekapitulasi data semester tersedia secara instan tanpa perlu menunggu pengumpulan berkas fisik yang memakan waktu.',
            floating: true
        },
        {
            id: 'scene_2_semester_filter_and_cards',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'METRIK KINERJA PRODI',
            title: 'Filter Semester & Empat Kartu Kunci',
            description: 'Pilih periode semester untuk meninjau indikator utama prodi.',
            details: [
                'Total Dosen Pengampu Aktif',
                'Total Mahasiswa Terdaftar',
                'Jumlah Kelas Perkuliahan yang Dibuka',
                'Rata-rata Nilai Semester Program Studi'
            ],
            screenRec: {
                target: 'Halaman Laporan Semester: Filter & 4 Metrik',
                zoom: { scale: 1.15, area: 'Dropdown semester & 4 kartu metrik' },
                cursor: [{ action: 'change_report_semester' }, { action: 'hover_average_score_card' }]
            },
            voiceover: 'Cukup pilih semester yang ingin dievaluasi. Dashboard laporan langsung menyajikan metrik penting: rasio dosen pengampu, jumlah mahasiswa aktif, total kelas yang berjalan, hingga indeks rata-rata nilai mahasiswa pada semester bersangkutan.',
            floating: false
        },
        {
            id: 'scene_3_course_breakdown_table',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'RINCIAN CAPAIAN KELAS',
            title: 'Tabel Rekapitulasi per Mata Kuliah',
            description: 'Pantau kinerja setiap kelas, dosen pengajar, dan rerata capaian belajar mahasiswa.',
            screenRec: {
                target: 'Tabel Rincian Kelas Perkuliahan',
                zoom: { scale: 1.1, area: 'Tabel daftar kelas & rata-rata capaian' },
                cursor: [{ action: 'scroll_course_breakdown' }]
            },
            voiceover: 'Di tabel rincian, Admin Prodi dapat meninjau capaian dari setiap mata kuliah. Hal ini memudahkan pimpinan prodi memetakan mata kuliah mana yang berjalan sangat baik dan mata kuliah mana yang memerlukan penguatan kurikulum.',
            floating: false
        },
        {
            id: 'scene_4_print_pdf_and_export_excel',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'CETAK RESMI & EKSPOR DATA',
            title: 'Cetak PDF Berkod Surat dan Ekspor Excel',
            description: 'Dua opsi output laporan siap pakai untuk rapat pimpinan atau akreditasi.',
            screenRec: {
                target: 'Tombol Aksi Laporan: Cetak PDF & Ekspor Excel',
                zoom: { scale: 1.25, area: 'Tombol Cetak PDF dan Ekspor Excel di kanan atas' },
                cursor: [{ action: 'click_print_pdf' }, { action: 'click_export_excel' }]
            },
            voiceover: 'Butuh bahan untuk rapat pimpinan fakultas atau laporan akreditasi LAM-INFOKOM? Gunakan opsi Cetak PDF untuk format siap cetak dengan kop resmi, atau Ekspor Excel jika data perlu dianalisis lebih mendalam.',
            floating: false
        },
        {
            id: 'scene_5_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'PENGAMBILAN KEPUTUSAN BERBASIS DATA',
            title: 'Data Akurat. Keputusan Cepat',
            description: 'Mewujudkan tata kelola program studi yang transparan dan unggul.',
            voiceover: 'Laporan akademik prodi tersaji akurat, cepat, dan terpercaya bersama SALE.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video16);
    const previewPath = VideoRenderer.renderInteractivePreview(video16);
    console.log(`[Video 16] Manifest VO: ${manifestPath}`);
    console.log(`[Video 16] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_16_adminprodi_laporan_semester.js')) {
    generate();
}
