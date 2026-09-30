import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video18 = {
    id: '18_admin_pengaturan_sistem',
    title: 'Pengaturan Sistem: AI, Email Narahubung, dan Sesi',
    role: 'Admin Sistem',
    duration: '3–4 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KONFIGURASI SISTEM GLOBAL',
            title: 'Kustomisasi dan Parameter Operasional',
            description: 'Sesuaikan identitas kampus, keamanan sesi, dan integrasi kecerdasan buatan.',
            voiceover: 'Setiap institusi memiliki standar dan kebutuhan operasional yang unik. Di menu Pengaturan Sistem, Anda dapat mengkustomisasi identitas kampus, mengamankan sesi pengguna, hingga menghubungkan model kecerdasan buatan kelas dunia.',
            floating: true
        },
        {
            id: 'scene_2_institution_and_support_email',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'IDENTITAS & BANTUAN RESMI',
            title: 'Nama Kampus dan Email Narahubung',
            description: 'Email dukungan yang terhubung dinamis dan tampil di profil seluruh dosen serta mahasiswa.',
            screenRec: {
                target: 'Halaman Pengaturan: Identitas Institusi',
                zoom: { scale: 1.25, area: 'Input Nama Institusi & Email Narahubung' },
                cursor: [{ action: 'type_institution_name' }, { action: 'type_support_email' }]
            },
            voiceover: 'Mulai dari identitas kampus. Masukkan nama universitas dan semester aktif. Yang sangat penting: pastikan Anda mengisi kolom Email Narahubung. Email ini terhubung secara dinamis dan akan otomatis muncul di menu Bantuan pada profil seluruh dosen dan mahasiswa ketika mereka membutuhkan bantuan teknis.',
            floating: false
        },
        {
            id: 'scene_3_session_and_maintenance',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'KEAMANAN & MODE PEMELIHARAAN',
            title: 'Batas Sesi Login & Maintenance Mode',
            description: 'Atur session lifetime dan aktifkan mode pemeliharaan saat perbaikan server.',
            screenRec: {
                target: 'Pengaturan Parameter Sesi & Maintenance',
                zoom: { scale: 1.2, area: 'Input masa berlaku sesi & toggle maintenance' },
                cursor: [{ action: 'set_session_minutes' }, { action: 'toggle_maintenance_mode' }]
            },
            voiceover: 'Atur parameter keamanan operasional seperti durasi masa berlaku sesi login pengguna. Jika tim IT Anda perlu melakukan perbaikan server, aktifkan Mode Pemeliharaan—sistem akan menampilkan pesan pemeliharaan yang elegan kepada pengguna tanpa risiko kerusakan data.',
            floating: false
        },
        {
            id: 'scene_4_ai_integration_and_quota',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'INTEGRASI ASISTEN AI',
            title: 'Provider AI, API Key, dan Kuota Token',
            description: 'Pilih model Google AI, OpenAI, atau DeepSeek dan uji koneksi secara langsung.',
            screenRec: {
                target: 'Section Integrasi AI & Tombol Test Connection',
                zoom: { scale: 1.25, area: 'Dropdown model, API Key & tombol Uji Koneksi' },
                cursor: [{ action: 'select_ai_provider' }, { action: 'type_api_key' }, { action: 'click_test_ai_btn' }]
            },
            voiceover: 'SALE dilengkapi pendamping pemrograman bertenaga AI. Anda bebas memilih provider AI yang didukung—seperti Google AI, OpenAI, atau DeepSeek—menentukan model bahasa, dan memasukkan API key secara aman. Anda bahkan dapat menguji koneksinya langsung serta membatasi kuota token agar penggunaan anggaran tetap terkontrol.',
            floating: false
        },
        {
            id: 'scene_5_save_settings',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'PENYIMPANAN KONFIGURASI',
            title: 'Simpan dan Berlaku Seketika',
            description: 'Seluruh konfigurasi tersimpan aman dan terintegrasi ke seluruh modul.',
            screenRec: {
                target: 'Tombol Simpan Pengaturan & Alert Sukses',
                zoom: { scale: 1.2, area: 'Tombol Simpan & flash message sukses' },
                cursor: [{ action: 'click_save_settings' }]
            },
            voiceover: 'Klik Simpan Pengaturan. Seluruh konfigurasi global langsung berlaku seketika di seluruh lingkungan sistem SALE.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'SISTEM TERKONFIGURASI',
            title: 'Identitas Jelas. Teknologi Mutakhir',
            description: 'Menghadirkan layanan e-learning kampus yang terpercaya dan terstandarisasi.',
            voiceover: 'Dengan pengaturan yang tepat, sistem SALE siap beroperasi memberikan layanan prima bagi seluruh civitas akademika.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video18);
    const previewPath = VideoRenderer.renderInteractivePreview(video18);
    console.log(`[Video 18] Manifest VO: ${manifestPath}`);
    console.log(`[Video 18] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_18_admin_pengaturan_sistem.js')) {
    generate();
}
