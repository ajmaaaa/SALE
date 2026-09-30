import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video14 = {
    id: '14_adminprodi_cpl_cpmk_kurikulum',
    title: 'Menetapkan CPL dan CPMK Kurikulum OBE Prodi',
    role: 'Admin Prodi',
    duration: '4–5 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'ARSITEKTUR KURIKULUM OBE',
            title: 'Peta Kompetensi Lulusan',
            description: 'Hubungkan setiap aktivitas belajar dengan Capaian Pembelajaran Lulusan program studi.',
            voiceover: 'Kurikulum berbasis Outcome-Based Education bukan sekadar dokumen administratif. Ini adalah peta kompetensi yang menghubungkan setiap aktivitas di kelas dengan standar lulusan program studi. Di SALE, pemetaan ini terintegrasi langsung ke dalam sistem.',
            floating: true
        },
        {
            id: 'scene_2_cpl_tab',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'TAB 1: CPL PROGRAM STUDI',
            title: 'Daftarkan Capaian Pembelajaran Lulusan',
            description: 'Input kode CPL, deskripsi profil lulusan, dan standar kelulusan minimal.',
            screenRec: {
                target: 'Tab Capaian Pembelajaran Lulusan (CPL)',
                zoom: { scale: 1.15, area: 'Modal Tambah CPL & tabel daftar CPL' },
                cursor: [{ action: 'click_add_cpl' }, { action: 'fill_cpl_form' }, { action: 'save_cpl' }]
            },
            voiceover: 'Masuki menu Kurikulum OBE. Pada tab pertama, daftarkan seluruh butir Capaian Pembelajaran Lulusan yang ditargetkan prodi Anda. Masukkan kode CPL, deskripsi kompetensi, serta standar kelulusan minimal. Butir CPL ini yang menjadi tolok ukur utama ketercapaian akademik prodi.',
            floating: false
        },
        {
            id: 'scene_3_cpmk_tab',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'TAB 2: MASTER BUTIR CPMK',
            title: 'Tabel Master CPMK dan Relasi CPL',
            description: 'Tambahkan butir CPMK prodi dan langsung hubungkan dengan CPL yang relevan.',
            screenRec: {
                target: 'Tab Butir CPMK Prodi',
                zoom: { scale: 1.2, area: 'Modal Tambah CPMK & dropdown pilihan CPL' },
                cursor: [{ action: 'click_add_cpmk' }, { action: 'select_related_cpl_dropdown' }, { action: 'save_cpmk' }]
            },
            voiceover: 'Di tab kedua, susun master butir Capaian Pembelajaran Mata Kuliah. Tambahkan butir CPMK baru dan langsung kaitkan dengan butir CPL yang relevan. Sistem menjaga konsistensi relasi ini agar perhitungan ketercapaian lulusan nantinya dapat ditarik secara otomatis.',
            floating: false
        },
        {
            id: 'scene_4_mapping_matrix_tab',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'TAB 3: MATRIKS PEMETAAN CPL-CPMK',
            title: 'Tinjau Silang Keterkaitan Kompetensi',
            description: 'Matriks komprehensif untuk memastikan seluruh CPMK mendukung CPL yang ditargetkan.',
            screenRec: {
                target: 'Tab Matriks Pemetaan CPL-CPMK',
                zoom: { scale: 1.1, area: 'Tabel silang matriks CPL x CPMK' },
                cursor: [{ action: 'scroll_mapping_matrix' }]
            },
            voiceover: 'Pada tab Matriks Pemetaan, Anda dapat meninjau peta keterkaitan antara CPL dan seluruh CPMK secara komprehensif. Pastikan seluruh CPMK mendukung CPL yang ditargetkan dan tidak ada capaian penting yang terlewat.',
            floating: false
        },
        {
            id: 'scene_5_mk_cpmk_assignment',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'PENETAPAN CPMK PADA MATA KULIAH',
            title: 'Pilih CPMK yang Dibebankan ke Mata Kuliah',
            description: 'Buka menu Mata Kuliah, pilih butir CPMK yang diampu lewat form multi-select.',
            screenRec: {
                target: 'Menu Mata Kuliah: Modal Edit MK',
                zoom: { scale: 1.2, area: 'Multi-select butir CPMK yang dibebankan' },
                cursor: [{ action: 'open_edit_mk' }, { action: 'select_cpmk_chips' }, { action: 'save_mk_changes' }]
            },
            voiceover: 'Terakhir, masuk ke menu Mata Kuliah untuk menetapkan CPMK apa saja yang dibebankan pada masing-masing mata kuliah. Cukup pilih butir-butir CPMK yang sesuai dari daftar master. Kini, saat dosen mengajar dan membuat asesmen, sistem otomatis menyajikan CPMK resmi prodi sebagai acuan penilaian.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'MUTU KURIKULUM TERJAMIN',
            title: 'Kurikulum Terstandar. Akreditasi Terukur',
            description: 'Pondasi OBE yang kokoh untuk mendukung akreditasi nasional dan internasional.',
            voiceover: 'Setup kurikulum selesai. Struktur pembelajaran prodi Anda kini kokoh, objektif, dan siap mendukung proses akreditasi secara transparan.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video14);
    const previewPath = VideoRenderer.renderInteractivePreview(video14);
    console.log(`[Video 14] Manifest VO: ${manifestPath}`);
    console.log(`[Video 14] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_14_adminprodi_cpl_cpmk_kurikulum.js')) {
    generate();
}
