import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video13 = {
    id: '13_adminprodi_setup_awal',
    title: 'Setup Awal: Mata Kuliah, Kelas, dan Dosen Pengampu',
    role: 'Admin Prodi',
    duration: '4–5 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'TATA KELOLA AKADEMIK PRODI',
            title: 'Fondasi Perkuliahan yang Presisi',
            description: 'Setup awal mata kuliah dan kelas yang rapi memastikan kelancaran perkuliahan satu semester penuh.',
            voiceover: 'Fondasi dari seluruh sistem e-learning ada di tangan Anda, Admin Prodi. Jika setup awal ini presisi, jalannya perkuliahan selama satu semester akan mulus tanpa hambatan. Mari kita mulai menyusunnya.',
            floating: true
        },
        {
            id: 'scene_2_tambah_mata_kuliah',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'MANAJEMEN MATA KULIAH',
            title: 'Mendefinisikan Mata Kuliah Kurikulum',
            description: 'Kode MK resmi, nama mata kuliah, bobot SKS, dan penempatan semester paket.',
            screenRec: {
                target: 'Halaman Manajemen Mata Kuliah Prodi',
                zoom: { scale: 1.15, area: 'Modal Tambah MK & input SKS' },
                cursor: [{ action: 'click_add_mk' }, { action: 'fill_mk_form' }, { action: 'save_mk' }]
            },
            voiceover: 'Langkah pertama, mendefinisikan mata kuliah. Masukkan kode resmi, nama mata kuliah, bobot SKS, dan letakkan pada semester paket yang sesuai dengan kurikulum. Data ini akan menjadi cetak biru bagi kelas-kelas yang dibuka.',
            floating: false
        },
        {
            id: 'scene_3_buka_kelas_baru',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'PEMBUKAAN KELAS PARALEL',
            title: 'Membentuk Kelas Operasional',
            description: 'Pilih mata kuliah kurikulum dan tentukan periode semester aktif yang berjalan.',
            screenRec: {
                target: 'Halaman Kelas Perkuliahan',
                zoom: { scale: 1.2, area: 'Modal Buka Kelas Baru' },
                cursor: [{ action: 'click_open_class' }, { action: 'select_mk_dropdown' }]
            },
            voiceover: 'Setelah mata kuliah tersedia, kita pecah menjadi kelas-kelas operasional. Pilih mata kuliahnya, tentukan semester berjalan, dan yang krusial: tetapkan tim teaching.',
            floating: false
        },
        {
            id: 'scene_4_teaching_team',
            layout: 'split-card',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PENETAPAN PENGAJAR',
            title: 'Dosen Ketua dan Dosen Wakil',
            description: 'Hierarki pengampu kelas untuk pendistribusian hak akses pengajaran yang jelas.',
            details: [
                'Dosen Ketua: Penanggung jawab utama kelas & asesmen',
                'Dosen Wakil: Dosen pendamping proses pembelajaran'
            ],
            screenRec: {
                target: 'Dropdown Pemilihan Tim Dosen Pengampu',
                zoom: { scale: 1.25, area: 'Dropdown Dosen Ketua & Dosen Wakil' },
                cursor: [{ action: 'select_lead_lecturer' }, { action: 'select_co_lecturer' }]
            },
            voiceover: 'Tetapkan siapa Dosen Ketua yang memiliki akses penuh, dan Dosen Wakil yang akan membantu. Sistem otomatis mendistribusikan hak akses spesifik ke dashboard mereka masing-masing.',
            floating: false
        },
        {
            id: 'scene_5_qr_and_regenerate',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'KEAMANAN & AKSES KELAS',
            title: 'QR Code, Barcode, dan Regenerate Kode',
            description: 'Bagikan akses pendaftaran ke mahasiswa atau reset kode jika terjadi kebocoran akses.',
            screenRec: {
                target: 'Aksi Detail Kelas Perkuliahan',
                zoom: { scale: 1.2, area: 'Ikon QR Code & tombol Regenerate Kode' },
                cursor: [{ action: 'preview_class_qr' }, { action: 'click_regenerate_code' }]
            },
            voiceover: 'Untuk mempermudah mahasiswa bergabung, sistem men-generate QR code dan kode unik kelas. Butuh me-reset akses karena kode bocor? Cukup klik regenerate. Keamanan kelas tetap terjaga di tangan Anda.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'ADMIN PRODI MANAGEMENT',
            title: 'Struktur Teratur. Perkuliahan Siap Berjalan',
            description: 'Lanjutkan dengan pengaturan kurikulum CPL dan CPMK program studi.',
            voiceover: 'Setup awal tuntas dengan sempurna. Ruang kelas siap menyambut dosen dan mahasiswa untuk semester baru.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video13);
    const previewPath = VideoRenderer.renderInteractivePreview(video13);
    console.log(`[Video 13] Manifest VO: ${manifestPath}`);
    console.log(`[Video 13] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_13_adminprodi_setup_awal.js')) {
    generate();
}
