import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video15 = {
    id: '15_adminprodi_kelola_pengguna',
    title: 'Mengelola Data Dosen dan Mahasiswa (Import & Manual)',
    role: 'Admin Prodi',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'MANAJEMEN CIVITAS PRODI',
            title: 'Pengelolaan Akun Cepat dan Akurat',
            description: 'Kelola ratusan akun dosen dan mahasiswa dengan metode manual maupun impor massal Excel.',
            voiceover: 'Menyiapkan ratusan akun civitas akademika di awal semester tidak harus melelahkan. Admin Prodi di SALE memiliki kendali penuh untuk mengelola data dosen dan mahasiswa, baik satu per satu maupun secara massal.',
            floating: true
        },
        {
            id: 'scene_2_tambah_dosen_manual',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'TAB DOSEN: TAMBAH MANUAL',
            title: 'Registrasi Akun Dosen Pengampu',
            description: 'Nama lengkap, NIDN/NIP, email institusi, dan password awal sementara.',
            screenRec: {
                target: 'Halaman Pengguna: Tab Dosen',
                zoom: { scale: 1.15, area: 'Modal Tambah Dosen Manual' },
                cursor: [{ action: 'click_tab_dosen' }, { action: 'open_add_dosen_modal' }, { action: 'save_dosen_user' }]
            },
            voiceover: 'Untuk menambah akun pengampu secara perorangan, klik Tambah Dosen Manual. Cukup masukkan nama, NIDN, email, serta password awal. Dosen akan langsung dapat login dan diarahkan untuk memperbarui kata sandinya secara aman.',
            floating: false
        },
        {
            id: 'scene_3_unduh_template_excel',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'TAB MAHASISWA: TEMPLATE RESMI',
            title: 'Unduh Format Spreadsheet Terstandar',
            description: 'Template Excel resmi untuk memastikan struktur data NIM dan nama tersusun rapi.',
            screenRec: {
                target: 'Tab Mahasiswa: Tombol Unduh Template',
                zoom: { scale: 1.25, area: 'Tombol Unduh Template Excel' },
                cursor: [{ action: 'click_tab_mahasiswa' }, { action: 'download_student_template' }]
            },
            voiceover: 'Bagaimana dengan ratusan mahasiswa baru? Manfaatkan fitur impor massal. Pertama, unduh template resmi Excel yang sudah disediakan. Format kolomnya sudah disesuaikan agar tidak terjadi kesalahan pemetaan data.',
            floating: false
        },
        {
            id: 'scene_4_import_excel_validation',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'IMPOR MASSAL & VALIDASI PENCEGAHAN DUPLIKAT',
            title: 'Unggah dan Verifikasi Otomatis',
            description: 'Seret file spreadsheet yang telah diisi, sistem memverifikasi keunikan NIM dan email.',
            screenRec: {
                target: 'Modal Impor File Excel Mahasiswa',
                zoom: { scale: 1.2, area: 'Drag & drop area berkas Excel' },
                cursor: [{ action: 'open_import_modal' }, { action: 'drop_excel_file' }, { action: 'process_import' }]
            },
            voiceover: 'Unggah kembali file Excel yang telah Anda lengkapi. Sistem SALE secara cerdas akan memvalidasi NIM dan email untuk mencegah terjadinya duplikasi data akun. Dalam hitungan detik, seluruh mahasiswa berhasil terdaftar ke dalam pangkalan data program studi.',
            floating: false
        },
        {
            id: 'scene_5_export_user_data',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'EKSPOR & SINKRONISASI DATA',
            title: 'Ekspor Data Pengguna Program Studi',
            description: 'Unduh seluruh data pengguna prodi kapan saja untuk kebutuhan sinkronisasi.',
            screenRec: {
                target: 'Tabel Pengguna & Tombol Ekspor',
                zoom: { scale: 1.15, area: 'Tombol hijau Ekspor Data Pengguna' },
                cursor: [{ action: 'click_export_users' }]
            },
            voiceover: 'Seluruh akun kini aktif dan siap didistribusikan ke kelas perkuliahan. Anda juga dapat mengekspor kembali data pengguna kapan saja untuk sinkronisasi dengan pangkalan data universitas.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'EFISIENSI DATA CIVITAS',
            title: 'Data Pengguna Lengkap dan Terverifikasi',
            description: 'Mendukung kelancaran perkuliahan prodi sepanjang semester.',
            voiceover: 'Manajemen pengguna yang mudah dan akurat menghemat waktu berharga Anda sebagai pengelola akademik.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video15);
    const previewPath = VideoRenderer.renderInteractivePreview(video15);
    console.log(`[Video 15] Manifest VO: ${manifestPath}`);
    console.log(`[Video 15] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_15_adminprodi_kelola_pengguna.js')) {
    generate();
}
