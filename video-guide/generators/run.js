/**
 * Master Runner & CLI untuk Generator Video Panduan SALE
 * Penggunaan:
 *   node video-guide/generators/run.js --all
 *   node video-guide/generators/run.js --video=04
 *   node video-guide/generators/run.js --combine=mahasiswa
 *   node video-guide/generators/run.js --combine=dosen
 *   node video-guide/generators/run.js --combine=all
 */

import { video00 } from './video_00_pengenalan.js';
import { video01 } from './video_01_mahasiswa_login_join_kelas.js';
import { video02 } from './video_02_mahasiswa_dashboard_course_forum.js';
import { video03 } from './video_03_mahasiswa_tugas_kuis_ujian.js';
import { video04 } from './video_04_mahasiswa_tugas_coding_ai.js';
import { video05 } from './video_05_mahasiswa_nilai_notifikasi_profil.js';
import { video06 } from './video_06_dosen_kelola_course.js';
import { video07 } from './video_07_dosen_buat_konten_materi.js';
import { video08 } from './video_08_dosen_buat_tugas_kuis_ujian.js';
import { video09 } from './video_09_dosen_asesmen_input_nilai.js';
import { video10 } from './video_10_dosen_rubrik_penilaian.js';
import { video11 } from './video_11_dosen_rekap_cpmk_cpl_export.js';
import { video12 } from './video_12_dosen_forum_chat_notifikasi.js';
import { video13 } from './video_13_adminprodi_setup_awal.js';
import { video14 } from './video_14_adminprodi_cpl_cpmk_kurikulum.js';
import { video15 } from './video_15_adminprodi_kelola_pengguna.js';
import { video16 } from './video_16_adminprodi_laporan_semester.js';
import { video17 } from './video_17_admin_kelola_pengguna_akademik.js';
import { video18 } from './video_18_admin_pengaturan_sistem.js';
import { video19 } from './video_19_admin_monitoring_backup.js';

import { ElevenLabsGenerator } from './engine/elevenlabs.js';
import { VideoRenderer } from './engine/renderer.js';
import { VideoCombiner } from './engine/combiner.js';

export const ALL_VIDEOS = [
    video00, video01, video02, video03, video04, video05,
    video06, video07, video08, video09, video10, video11, video12,
    video13, video14, video15, video16,
    video17, video18, video19
];

const GROUPS = {
    mahasiswa: [video01, video02, video03, video04, video05],
    dosen: [video06, video07, video08, video09, video10, video11, video12],
    adminprodi: [video13, video14, video15, video16],
    adminsistem: [video17, video18, video19],
    all: ALL_VIDEOS
};

function runSingle(video) {
    const generator = new ElevenLabsGenerator();
    const manifestPath = generator.exportVoManifest(video);
    const previewPath = VideoRenderer.renderInteractivePreview(video);
    console.log(`[OK] ${video.id} -> Manifest: ${manifestPath} | Preview: ${previewPath}`);
}

function main() {
    const args = process.argv.slice(2);
    const videoArg = args.find(a => a.startsWith('--video='));
    const combineArg = args.find(a => a.startsWith('--combine='));
    const allFlag = args.includes('--all');

    if (combineArg) {
        const groupName = combineArg.split('=')[1].toLowerCase();
        const list = GROUPS[groupName];
        if (!list) {
            console.error(`Grup "${groupName}" tidak ditemukan. Pilihan: ${Object.keys(GROUPS).join(', ')}`);
            process.exit(1);
        }
        console.log(`Menggabungkan ${list.length} video untuk grup: ${groupName}...`);
        const { combinedVideo, previewPath } = VideoCombiner.combine(
            `Kompilasi Video Panduan SALE: ${groupName.toUpperCase()}`,
            `combined_${groupName}`,
            list
        );
        new ElevenLabsGenerator().exportVoManifest(combinedVideo);
        console.log(`[SUKSES] Kompilasi selesai! Preview player: ${previewPath}`);
        return;
    }

    if (videoArg) {
        const query = videoArg.split('=')[1];
        const match = ALL_VIDEOS.find(v => v.id.startsWith(query) || v.id.includes(query));
        if (!match) {
            console.error(`Video "${query}" tidak ditemukan.`);
            process.exit(1);
        }
        console.log(`Memproses satu video: ${match.id}`);
        runSingle(match);
        return;
    }

    // Default or --all: jalankan seluruh 20 video
    console.log(`Memproses seluruh ${ALL_VIDEOS.length} video panduan SALE...`);
    for (const v of ALL_VIDEOS) {
        runSingle(v);
    }
    console.log(`\nSemua generator video berhasil dijalankan!`);
    console.log(`- Manifest VO ElevenLabs: video-guide/assets-references/vo-manifests/`);
    console.log(`- Preview Player HTML: video-guide/previews/`);
}

main();
