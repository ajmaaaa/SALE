import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video19 = {
    id: '19_admin_monitoring_backup',
    title: 'Monitoring Sistem, Storage, dan Backup Database',
    role: 'Admin Sistem',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KEANDALAN INFRASTRUKTUR',
            title: 'Pengawasan Proaktif dan Keamanan Data',
            description: 'Pantau beban server, kapasitas penyimpanan berkas, dan cadangan database secara terpadu.',
            voiceover: 'Menjaga sistem tetap beroperasi tanpa henti memerlukan pengawasan proaktif. Melalui menu Monitoring Sistem di SALE, kesehatan server, beban penyimpanan, dan integritas data terpantau secara realtime.',
            floating: true
        },
        {
            id: 'scene_2_storage_realtime_widget',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'KAPASITAS PENYIMPANAN REALTIME',
            title: 'Beban Hard Disk dan Berkas Lampiran',
            description: 'Pantau Disk Used, Disk Free, dan akumulasi berkas app/ yang diperbarui tiap 10 detik.',
            screenRec: {
                target: 'Halaman Monitoring: Widget Storage Capacity',
                zoom: { scale: 1.25, area: 'Progress bar storage & rincian memori' },
                cursor: [{ action: 'hover_storage_progress_bar' }]
            },
            voiceover: 'Di bagian penyimpanan, Anda dapat memantau kapasitas hard disk server secara langsung. Sistem memperlihatkan persentase memori penyimpanan yang terpakai serta akumulasi berkas lampiran materi dan tugas mahasiswa. Pembaruan data berjalan otomatis tanpa perlu refresh berkala.',
            floating: false
        },
        {
            id: 'scene_3_ai_token_metrics',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'METRIK KONSUMSI TOKEN AI',
            title: 'Grafik Penggunaan Asisten Pemrograman',
            description: 'Evaluasi penggunaan kuota token, jumlah permintaan, dan latensi respon kecerdasan buatan.',
            screenRec: {
                target: 'Monitoring: Section Metrik AI',
                zoom: { scale: 1.2, area: 'Grafik token AI & statistik request' },
                cursor: [{ action: 'hover_token_chart' }]
            },
            voiceover: 'Ingin mengetahui seberapa sering mahasiswa berkonsultasi dengan asisten coding? Panel metrik AI menampilkan total token yang telah digunakan beserta riwayat request per hari, memudahkan Anda dalam merencanakan alokasi kuota token kampus.',
            floating: false
        },
        {
            id: 'scene_4_database_backup_restore',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'CADANGAN DATABASE (BACKUP & RESTORE)',
            title: 'Pencadangan SQL Satu Klik',
            description: 'Buat file backup database .sql, unduh ke penyimpanan offline, atau lakukan restore darurat.',
            screenRec: {
                target: 'Section Backup & Restore Database',
                zoom: { scale: 1.25, area: 'Tombol Buat Backup Baru & daftar file SQL' },
                cursor: [{ action: 'click_create_backup_btn' }, { action: 'hover_download_restore_action' }]
            },
            voiceover: 'Keamanan data adalah prioritas utama. Di bagian Backup Database, Anda dapat membuat cadangan pangkalan data SQL kapan saja hanya dengan satu klik. Unduh file cadangan tersebut ke penyimpanan dingin Anda, atau gunakan fitur pulihkan (restore) jika suatu saat dibutuhkan pemulihan darurat.',
            floating: false
        },
        {
            id: 'scene_5_activity_log',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'LOG AKTIVITAS SISTEM',
            title: 'Jejak Audit Seluruh Aksi Pengguna',
            description: 'Catatan transparan perubahan data, alamat IP, dan waktu eksekusi untuk auditabilitas.',
            screenRec: {
                target: 'Tabel Activity Log Sistem',
                zoom: { scale: 1.15, area: 'Tabel log aktivitas & alamat IP' },
                cursor: [{ action: 'scroll_activity_logs' }]
            },
            voiceover: 'Seluruh aksi penting tercatat secara transparan di Log Aktivitas. Anda selalu mengetahui siapa yang melakukan pembaruan nilai, perubahan kurikulum, maupun pembuatan kelas baru demi menjaga auditabilitas sistem.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SISTEM TANGGUH & AMAN',
            title: 'Sistem Tangguh. Data Terlindungi',
            description: 'Menjaga kontinuitas proses pembelajaran kampus dengan proteksi maksimal.',
            voiceover: 'Dengan pemantauan cerdas dan proteksi berlapis, SALE siap mendukung operasional akademik kampus Anda secara andal sepanjang waktu.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video19);
    const previewPath = VideoRenderer.renderInteractivePreview(video19);
    console.log(`[Video 19] Manifest VO: ${manifestPath}`);
    console.log(`[Video 19] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_19_admin_monitoring_backup.js')) {
    generate();
}
