import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';

export const video08 = {
    id: '08_dosen_buat_tugas_kuis_ujian',
    title: 'Membuat Tugas, Kuis, dan Ujian Berbasis CPMK',
    role: 'Dosen',
    duration: '5–6 menit',
    scenes: [
        {
            id: 'scene_1_intro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'ASESMEN BERBASIS CAPAIAN',
            title: 'Evaluasi Pembelajaran Berstandar OBE',
            description: 'Instrumen evaluasi terpadu yang memetakan setiap butir soal ke Capaian Pembelajaran Mata Kuliah.',
            voiceover: 'Mengukur capaian pembelajaran butuh instrumen yang selaras. Kekuatan utama sistem SALE ada di fitur evaluasinya yang terintegrasi penuh dengan standar OBE. Mari kita mulai membuat asesmen yang terukur.',
            floating: true
        },
        {
            id: 'scene_2_penilaian_asesmen_cpmk',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'KELOLA ASESMEN KELAS',
            title: 'Tambah Asesmen dan Bobot Nilai Akhir',
            description: 'Akses menu Penilaian → Daftar Asesmen untuk menetapkan kode, nama, jenis, dan pemetaan CPMK.',
            screenRec: {
                target: 'Halaman Form Tambah Asesmen (Menu Penilaian)',
                zoom: { scale: 1.15, area: 'Form asesmen & dropdown pilih CPMK' },
                cursor: [{ action: 'fill_assessment_identity' }, { action: 'select_cpmk_mapping' }]
            },
            voiceover: 'Asesmen di SALE dikelola dari menu Penilaian, terpisah dari konten materi. Begitu membuat asesmen baru, Anda langsung bisa memetakan: CPMK mana yang diukur lewat asesmen ini, dan berapa persen bobotnya terhadap nilai akhir. Ini yang membuat penilaian Anda tidak sekadar memberi angka, tapi bermakna secara kurikulum.',
            floating: false
        },
        {
            id: 'scene_3_kuis_susun_soal',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'SUSUN BUTIR SOAL KUIS',
            title: 'Pilihan Ganda, Kompleks, Esai, dan Menjodohkan',
            description: 'Di langkah kedua, susun bank soal dan tandai kunci jawaban untuk koreksi otomatis.',
            screenRec: {
                target: 'Step 2 Form Kuis: Susun Soal',
                zoom: { scale: 1.25, area: 'Pilihan tipe soal & centang kunci jawaban' },
                cursor: [{ action: 'select_question_type_pilihan' }, { action: 'check_correct_answer' }, { action: 'add_essay_question' }]
            },
            voiceover: 'Untuk membuat kuis dengan soal terstruktur, tambahkan konten bertipe Kuis dari halaman course. Di langkah kedua, Anda menyusun soal. Ada empat tipe: Pilihan Ganda, Pilihan Ganda Kompleks, Uraian/Esai, Benar/Salah, dan Menjodohkan. Untuk pilihan ganda, tandai kunci jawaban — sistem akan mengoreksinya otomatis.',
            floating: false
        },
        {
            id: 'scene_4_timer_and_due',
            layout: 'full-left',
            bgColor: '#f4f5f7',
            textColor: '#0f172a',
            badge: 'PENGATURAN DISIPLIN UJIAN',
            title: 'Timer Mundur dan Batas Waktu Akses',
            description: 'Aktifkan durasi menit untuk kuis atau ujian UTS dan UAS.',
            screenRec: {
                target: 'Form Pengaturan Durasi & Tenggat Kuis',
                zoom: { scale: 1.2, area: 'Input durasi menit & input tanggal tenggat' },
                cursor: [{ action: 'enable_duration_toggle' }, { action: 'set_due_datetime' }]
            },
            voiceover: 'Atur timer dan tenggat waktunya. Untuk ujian seperti UTS dan UAS, cukup pilih tipe kontennya saat membuat — sistemnya sama, tapi label dan bobot penilaiannya berbeda. Fleksibel sesuai kebutuhan semester Anda.',
            floating: false
        },
        {
            id: 'scene_5_matriks_penilaian',
            layout: 'full-left',
            bgColor: '#ffffff',
            textColor: '#0f172a',
            badge: 'MATRIKS DISTRIBUSI BOBOT',
            title: 'Tabel Silang CPMK dan Asesmen',
            description: 'Tetapkan persentase kontribusi setiap sel CPMK x Asesmen dengan kalkulasi total otomatis.',
            screenRec: {
                target: 'Halaman Matriks Penilaian Kelas',
                zoom: { scale: 1.15, area: 'Tabel matriks & kolom total bobot' },
                cursor: [{ action: 'fill_matrix_cell' }, { action: 'save_matrix_weight' }]
            },
            voiceover: 'Setelah semua asesmen dibuat, masuk ke halaman Matriks Penilaian. Di sini Anda mengatur distribusi bobot antara setiap CPMK dan setiap asesmen — hasilnya langsung menjadi dasar perhitungan rekap capaian akhir semester.',
            floating: false
        },
        {
            id: 'scene_6_outro',
            layout: 'center-bold',
            bgColor: '#102f50',
            textColor: '#ffffff',
            badge: 'KUALITAS PENILAIAN TERJAMIN',
            title: 'Evaluasi Objektif, Mahasiswa Aktif',
            description: 'Wujudkan asesmen yang terukur dan bermakna untuk akreditasi kampus.',
            voiceover: 'Dengan pemetaan yang jelas dan pengkoreksian yang efisien, Anda tidak hanya memberi nilai, tapi memandu mahasiswa mencapai kompetensinya.',
            floating: true
        }
    ]
};

export function generate() {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video08);
    const previewPath = VideoRenderer.renderInteractivePreview(video08);
    console.log(`[Video 08] Manifest VO: ${manifestPath}`);
    console.log(`[Video 08] Preview HTML: ${previewPath}`);
    return { manifestPath, previewPath };
}

if (process.argv[1] && process.argv[1].endsWith('video_08_dosen_buat_tugas_kuis_ujian.js')) {
    generate();
}
