<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AdminPreviewController;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ClassSectionController;
use App\Http\Controllers\Dosen\ExportController;
use App\Http\Controllers\Dosen\InputNilaiController;
use App\Http\Controllers\Dosen\PenilaianController;
use App\Http\Controllers\Dosen\RubricController;
use App\Http\Controllers\DosenAuthController;
use App\Http\Controllers\Kaprodi\KaprodiMonitoringController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\Mahasiswa\AssignmentController;
use App\Http\Controllers\Mahasiswa\DashboardController;
use App\Http\Controllers\Mahasiswa\ObeProgressController;
use App\Http\Controllers\Mahasiswa\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('mahasiswa.dashboard');
});

Route::get('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'authenticate'])->name('login.post');
Route::match(['get', 'post'], '/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
Route::match(['get', 'post'], '/switch-role/{role}', [\App\Http\Controllers\AuthController::class, 'switchRole'])->name('switch-role');

// Real, hashed-password authentication for Dosen accounts. Other roles
// (mahasiswa, admin, admin prodi, kaprodi) still use the persona-switcher
// above until their own real-auth flows are built.
Route::get('/dosen/login', [DosenAuthController::class, 'showLogin'])->name('dosen.login');
Route::post('/dosen/login', [DosenAuthController::class, 'login'])->name('dosen.login.post');
Route::post('/dosen/logout', [DosenAuthController::class, 'logout'])->name('dosen.logout');

// Public only while SALE remains a frontend prototype. See README before adding real data.
Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');

    Route::get('/assignment', [LearningController::class, 'assignments'])->name('assignment.index');
    Route::get('/assignment/{assignment}/code', [AssignmentController::class, 'code'])->whereNumber('assignment')->name('assignment.code');

    Route::get('/grade', fn () => redirect()->route('mahasiswa.assignment.index', ['tab' => 'nilai']))->name('grade.index');
    Route::redirect('/discussion', '/mahasiswa/dashboard')->name('discussion.index');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/capaian-obe', [ObeProgressController::class, 'index'])->name('obe.progress');
});

Route::get('/mahasiswa/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->name('mahasiswa.course.item');
Route::get('/mahasiswa/course/{course}/item/{item}/quiz', [LearningController::class, 'quizRoom'])->whereNumber(['course', 'item'])->name('mahasiswa.quiz.room');
Route::post('/mahasiswa/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->name('mahasiswa.course.discuss');
Route::post('/mahasiswa/course/{course}/item/{item}/submission', [LearningController::class, 'submit'])->whereNumber(['course', 'item'])->name('mahasiswa.course.submit');
Route::view('/mahasiswa/notifikasi', 'learning.notifications')->name('mahasiswa.notifications');
Route::get('/preview/files/{file}', [LearningController::class, 'file'])->whereUuid('file')->name('preview.file');
Route::get('/mahasiswa/nilai', [AcademicController::class, 'student'])->name('mahasiswa.nilai');

// Every dosen.* route below requires a real, authenticated Dosen account.
Route::prefix('dosen')->name('dosen.')->middleware('dosen.auth')->group(function () {
    Route::view('/dashboard', 'dosen.dashboard')->name('dashboard');
    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/create', [LearningController::class, 'createCourse'])->name('course.create');
    Route::post('/course', [LearningController::class, 'storeCourse'])->name('course.store');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');
    Route::get('/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->name('course.item');
    Route::post('/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->name('course.discuss');
    Route::get('/course/{course}/create', [LearningController::class, 'createItem'])->whereNumber('course')->name('item.create');
    Route::post('/course/{course}/items', [LearningController::class, 'storeItem'])->whereNumber('course')->name('item.store');
    Route::get('/penilaian', function (\Illuminate\Http\Request $request) {
        if ($request->has('room') || $request->has('course')) {
            return view('dosen.grades');
        }
        return redirect()->route('dosen.penilaian.index');
    })->name('grades');

    Route::get('/course/{course}/akademik', [AcademicController::class, 'settings'])->whereNumber('course')->name('academic');
    Route::post('/course/{course}/akademik', [AcademicController::class, 'saveSettings'])->whereNumber('course')->name('academic.save');
    Route::get('/gradebook', [AcademicController::class, 'gradebook'])->name('gradebook');
    Route::post('/gradebook/{course}', [AcademicController::class, 'saveScores'])->whereNumber('course')->name('scores.save');
    Route::post('/gradebook/{course}/bulk', [AcademicController::class, 'bulkScores'])->whereNumber('course')->name('scores.bulk');
    Route::post('/penilaian/{item}', [AcademicController::class, 'gradeItem'])->whereNumber('item')->name('grade.save');

    // OBE assessment module (new, DB-backed — separate from the legacy
    // session-based AcademicController routes above, which are being
    // phased out incrementally rather than removed outright).
    Route::get('/penilaian-kelas', [ClassSectionController::class, 'index'])->name('penilaian.index');
    Route::get('/rekap-nilai', [ClassSectionController::class, 'rekapIndex'])->name('rekap.index');
    Route::prefix('penilaian-kelas/{section}')->name('penilaian.')->group(function () {
        Route::get('/', [PenilaianController::class, 'dashboard'])->name('dashboard');
        Route::get('/rekap', [PenilaianController::class, 'rekap'])->name('rekap');
        Route::get('/matriks', [PenilaianController::class, 'matriks'])->name('matriks');
        Route::post('/matriks', [PenilaianController::class, 'saveMatriks'])->name('matriks.save');
        Route::get('/asesmen', [PenilaianController::class, 'asesmen'])->name('asesmen');
        Route::get('/asesmen/tambah', [AssessmentController::class, 'create'])->name('asesmen.create');
        Route::post('/asesmen', [AssessmentController::class, 'store'])->name('asesmen.store');
        Route::post('/asesmen/quick', [AssessmentController::class, 'quickStore'])->name('asesmen.quick');
        Route::get('/asesmen/{assessment}/ubah', [AssessmentController::class, 'edit'])->whereNumber('assessment')->name('asesmen.edit');
        Route::put('/asesmen/{assessment}', [AssessmentController::class, 'update'])->whereNumber('assessment')->name('asesmen.update');
        Route::delete('/asesmen/{assessment}', [AssessmentController::class, 'destroy'])->whereNumber('assessment')->name('asesmen.destroy');
        Route::get('/cpmk', [PenilaianController::class, 'cpmk'])->name('cpmk');
        Route::get('/cpl', [PenilaianController::class, 'cpl'])->name('cpl');
        Route::get('/pengaturan', [PenilaianController::class, 'pengaturan'])->name('pengaturan');

        // Input Nilai (per assessment)
        Route::get('/asesmen/{assessment}/nilai', [InputNilaiController::class, 'show'])->whereNumber('assessment')->name('asesmen.nilai');
        Route::post('/asesmen/{assessment}/nilai', [InputNilaiController::class, 'store'])->whereNumber('assessment')->name('asesmen.nilai.store');
        Route::get('/asesmen/{assessment}/nilai/template', [InputNilaiController::class, 'downloadTemplate'])->whereNumber('assessment')->name('asesmen.nilai.template');
        Route::get('/asesmen/{assessment}/nilai/import', [InputNilaiController::class, 'import'])->whereNumber('assessment')->name('asesmen.nilai.import');
        Route::post('/asesmen/{assessment}/nilai/import', [InputNilaiController::class, 'processImport'])->whereNumber('assessment')->name('asesmen.nilai.import.process');

        // Rubric
        Route::get('/asesmen/{assessment}/rubrik', [RubricController::class, 'edit'])->whereNumber('assessment')->name('asesmen.rubrik');
        Route::post('/asesmen/{assessment}/rubrik', [RubricController::class, 'save'])->whereNumber('assessment')->name('asesmen.rubrik.save');
        Route::get('/asesmen/{assessment}/rubrik/nilai', [RubricController::class, 'scores'])->whereNumber('assessment')->name('asesmen.rubrik.nilai');
        Route::post('/asesmen/{assessment}/rubrik/nilai', [RubricController::class, 'storeScores'])->whereNumber('assessment')->name('asesmen.rubrik.nilai.store');

        // Export
        Route::get('/export', [ExportController::class, 'index'])->name('export');
        Route::get('/export/keseluruhan', [ExportController::class, 'rekapKeseluruhan'])->name('export.keseluruhan');
        Route::get('/export/cpmk', [ExportController::class, 'rekapCpmk'])->name('export.cpmk');
        Route::get('/export/cpl', [ExportController::class, 'rekapCpl'])->name('export.cpl');
        Route::get('/export/nilai-asesmen', [ExportController::class, 'rekapNilaiAssessment'])->name('export.nilai');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/laporan/export', [AdminPreviewController::class, 'export'])->name('export');
    Route::get('/laporan/fakultas', [AdminPreviewController::class, 'laporanFakultas'])->name('laporan.fakultas');
    Route::get('/laporan/prodi', [AdminPreviewController::class, 'laporanProdi'])->name('laporan.prodi');
    Route::post('/pengguna', [AdminPreviewController::class, 'user'])->name('users.store');
    Route::post('/pengguna/bulk', [AdminPreviewController::class, 'bulkUsers'])->name('users.bulk');
    Route::post('/akademik', [AdminPreviewController::class, 'academic'])->name('academic.store');
    Route::delete('/akademik/{id}', [AdminPreviewController::class, 'destroyAcademic'])->name('academic.destroy');
    Route::post('/pengaturan', [AdminPreviewController::class, 'settings'])->name('settings.store');
    Route::get('/{section?}', [AdminPreviewController::class, 'page'])->name('page');
});

Route::prefix('kaprodi')->name('kaprodi.')->group(function () {
    Route::get('/monitoring/cpmk', [KaprodiMonitoringController::class, 'cpmk'])->name('monitoring.cpmk');
    Route::get('/monitoring/cpl', [KaprodiMonitoringController::class, 'cpl'])->name('monitoring.cpl');
    Route::get('/export', [KaprodiMonitoringController::class, 'exportIndex'])->name('export.index');
    Route::get('/export/cpmk', [KaprodiMonitoringController::class, 'exportCpmk'])->name('export.cpmk');
    Route::get('/export/cpl', [KaprodiMonitoringController::class, 'exportCpl'])->name('export.cpl');
});

// Mahasiswa Join Kelas via Link / Barcode QR Code
Route::get('/join-kelas/{code}', [\App\Http\Controllers\Mahasiswa\EnrollmentController::class, 'join'])->name('mahasiswa.join-kelas');
Route::get('/kelas/{section}/qr', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'qrCode'])->name('kelas.qr');
Route::get('/kelas/{section}/barcode', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'barcode'])->name('kelas.barcode');

// Ruang Kerja Admin Program Studi
Route::prefix('admin-prodi')->name('admin-prodi.')->middleware('admin_prodi.auth')->group(function () {
    Route::get('/', fn () => redirect()->route('admin-prodi.dashboard'));
    Route::get('/dashboard', [\App\Http\Controllers\AdminProdi\AdminProdiDashboardController::class, 'index'])->name('dashboard');

    // 1. Program Studi (dialihkan ke dashboard)
    Route::redirect('/prodi', '/admin-prodi/dashboard')->name('prodi.index');
    Route::post('/prodi', [\App\Http\Controllers\AdminProdi\ProdiManagementController::class, 'store'])->name('prodi.store');
    Route::put('/prodi/{prodi}', [\App\Http\Controllers\AdminProdi\ProdiManagementController::class, 'update'])->name('prodi.update');
    Route::delete('/prodi/{prodi}', [\App\Http\Controllers\AdminProdi\ProdiManagementController::class, 'destroy'])->name('prodi.destroy');

    // 2. Kurikulum (CPL, CPMK per MK, Pemetaan CPL-CPMK)
    Route::get('/kurikulum', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'index'])->name('kurikulum.index');
    Route::post('/kurikulum/cpl', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'storeCpl'])->name('kurikulum.cpl.store');
    Route::put('/kurikulum/cpl/{cpl}', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'updateCpl'])->name('kurikulum.cpl.update');
    Route::delete('/kurikulum/cpl/{cpl}', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'destroyCpl'])->name('kurikulum.cpl.destroy');
    Route::post('/kurikulum/cpmk', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'storeCpmk'])->name('kurikulum.cpmk.store');
    Route::put('/kurikulum/cpmk/{cpmk}', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'updateCpmk'])->name('kurikulum.cpmk.update');
    Route::delete('/kurikulum/cpmk/{cpmk}', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'destroyCpmk'])->name('kurikulum.cpmk.destroy');
    Route::post('/kurikulum/mapping', [\App\Http\Controllers\AdminProdi\KurikulumController::class, 'updateMapping'])->name('kurikulum.mapping.update');

    // 3. Akademik (Mata Kuliah & Kelas Perkuliahan dengan Dosen Ketua & Wakil)
    Route::get('/akademik/matakuliah', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'matakuliahIndex'])->name('akademik.matakuliah');
    Route::post('/akademik/matakuliah', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'storeMataKuliah'])->name('akademik.matakuliah.store');
    Route::put('/akademik/matakuliah/{mataKuliah}', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'updateMataKuliah'])->name('akademik.matakuliah.update');
    Route::delete('/akademik/matakuliah/{mataKuliah}', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'destroyMataKuliah'])->name('akademik.matakuliah.destroy');

    Route::get('/akademik/kelas', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'kelasIndex'])->name('akademik.kelas');
    Route::post('/akademik/kelas', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'storeKelas'])->name('akademik.kelas.store');
    Route::put('/akademik/kelas/{section}', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'updateKelas'])->name('akademik.kelas.update');
    Route::delete('/akademik/kelas/{section}', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'destroyKelas'])->name('akademik.kelas.destroy');
    Route::post('/akademik/kelas/{section}/regenerate-code', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'regenerateCode'])->name('akademik.kelas.regenerate-code');
    Route::get('/akademik/kelas/{section}/qr', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'qrCode'])->name('akademik.kelas.qr');
    Route::get('/akademik/kelas/{section}/barcode', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'barcode'])->name('akademik.kelas.barcode');

    // 4. Pengguna (Dosen & Mahasiswa Manual & Impor Excel)
    Route::get('/pengguna', [\App\Http\Controllers\AdminProdi\UserProdiController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [\App\Http\Controllers\AdminProdi\UserProdiController::class, 'store'])->name('users.store');
    Route::put('/pengguna/{user}', [\App\Http\Controllers\AdminProdi\UserProdiController::class, 'update'])->name('users.update');
    Route::delete('/pengguna/{user}', [\App\Http\Controllers\AdminProdi\UserProdiController::class, 'destroy'])->name('users.destroy');
    Route::get('/pengguna/template/{type}', [\App\Http\Controllers\AdminProdi\UserProdiController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/pengguna/import', [\App\Http\Controllers\AdminProdi\UserProdiController::class, 'import'])->name('users.import');

    // 5. Laporan Spesifik Prodi per Semester & Ekspor
    Route::get('/laporan', [\App\Http\Controllers\AdminProdi\LaporanProdiController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [\App\Http\Controllers\AdminProdi\LaporanProdiController::class, 'export'])->name('laporan.export');
    Route::get('/laporan/cetak', [\App\Http\Controllers\AdminProdi\LaporanProdiController::class, 'print'])->name('laporan.print');
});
