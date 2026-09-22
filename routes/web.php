<?php

use App\Http\Controllers\AdminPreviewController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\Mahasiswa\AssignmentController;
use App\Http\Controllers\Mahasiswa\DashboardController;
use App\Http\Controllers\Mahasiswa\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('mahasiswa.dashboard');
});

Route::get('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'authenticate'])->name('login.post');
Route::match(['get', 'post'], '/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
Route::match(['get', 'post'], '/switch-role/{role}', [\App\Http\Controllers\AuthController::class, 'switchRole'])->name('switch-role');

// Public only while SALE remains a frontend prototype. See README before adding real data.
Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');

    Route::get('/assignment', [LearningController::class, 'assignments'])->name('assignment.index');
    Route::get('/assignment/{assignment}/code', [AssignmentController::class, 'code'])->whereNumber('assignment')->name('assignment.code');

    Route::get('/grade', fn () => redirect()->route('mahasiswa.assignment.index', ['tab' => 'nilai']))->name('grade.index');
    Route::get('/discussion', [LearningController::class, 'discussions'])->name('discussion.index');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});

Route::get('/mahasiswa/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->name('mahasiswa.course.item');
Route::get('/mahasiswa/course/{course}/item/{item}/quiz', [LearningController::class, 'quizRoom'])->whereNumber(['course', 'item'])->name('mahasiswa.quiz.room');
Route::post('/mahasiswa/course/{course}/discussion', [LearningController::class, 'discussCourse'])->whereNumber('course')->name('mahasiswa.course.discuss.class');
Route::post('/mahasiswa/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->name('mahasiswa.course.discuss');
Route::post('/mahasiswa/course/{course}/item/{item}/submission', [LearningController::class, 'submit'])->whereNumber(['course', 'item'])->name('mahasiswa.course.submit');
Route::view('/mahasiswa/notifikasi', 'learning.notifications')->name('mahasiswa.notifications');
Route::get('/preview/files/{file}', [LearningController::class, 'file'])->whereUuid('file')->name('preview.file');

Route::prefix('dosen')->name('dosen.')->group(function () {
    Route::view('/dashboard', 'dosen.dashboard')->name('dashboard');
    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/create', [LearningController::class, 'createCourse'])->name('course.create');
    Route::post('/course', [LearningController::class, 'storeCourse'])->name('course.store');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');
    Route::get('/course/{course}/create', [LearningController::class, 'createItem'])->whereNumber('course')->name('item.create');
    Route::post('/course/{course}/items', [LearningController::class, 'storeItem'])->whereNumber('course')->name('item.store');
    Route::view('/penilaian', 'dosen.grades')->name('grades');

    // OBE assessment & rekap nilai module from frontend branch
    Route::get('/penilaian-kelas', [\App\Http\Controllers\Dosen\ClassSectionController::class, 'index'])->name('penilaian.index');
    Route::get('/rekap-nilai', [\App\Http\Controllers\Dosen\ClassSectionController::class, 'rekapIndex'])->name('rekap.index');
    Route::prefix('penilaian-kelas/{section}')->name('penilaian.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Dosen\PenilaianController::class, 'dashboard'])->name('dashboard');
        Route::get('/rekap', [\App\Http\Controllers\Dosen\PenilaianController::class, 'rekap'])->name('rekap');
        Route::get('/rekap/export', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapKeseluruhan'])->name('rekap.export');
        Route::get('/cpmk/export', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapCpmk'])->name('cpmk.export');
        Route::get('/cpl/export', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapCpl'])->name('cpl.export');
        Route::get('/rekap/print', [\App\Http\Controllers\Dosen\ExportController::class, 'printRekap'])->name('rekap.print');
        Route::get('/matriks', [\App\Http\Controllers\Dosen\PenilaianController::class, 'matriks'])->name('matriks');
        Route::post('/matriks', [\App\Http\Controllers\Dosen\PenilaianController::class, 'saveMatriks'])->name('matriks.save');
        Route::get('/asesmen', [\App\Http\Controllers\Dosen\PenilaianController::class, 'asesmen'])->name('asesmen');
        Route::get('/asesmen/tambah', [\App\Http\Controllers\Dosen\AssessmentController::class, 'create'])->name('asesmen.create');
        Route::post('/asesmen', [\App\Http\Controllers\Dosen\AssessmentController::class, 'store'])->name('asesmen.store');
        Route::post('/asesmen/quick', [\App\Http\Controllers\Dosen\AssessmentController::class, 'quickStore'])->name('asesmen.quick');
        Route::get('/asesmen/{assessment}/ubah', [\App\Http\Controllers\Dosen\AssessmentController::class, 'edit'])->whereNumber('assessment')->name('asesmen.edit');
        Route::put('/asesmen/{assessment}', [\App\Http\Controllers\Dosen\AssessmentController::class, 'update'])->whereNumber('assessment')->name('asesmen.update');
        Route::delete('/asesmen/{assessment}', [\App\Http\Controllers\Dosen\AssessmentController::class, 'destroy'])->whereNumber('assessment')->name('asesmen.destroy');
        Route::get('/cpmk', [\App\Http\Controllers\Dosen\PenilaianController::class, 'cpmk'])->name('cpmk');
        Route::get('/cpl', [\App\Http\Controllers\Dosen\PenilaianController::class, 'cpl'])->name('cpl');
        Route::get('/pengaturan', [\App\Http\Controllers\Dosen\PenilaianController::class, 'pengaturan'])->name('pengaturan');

        // Input Nilai (per assessment)
        Route::get('/asesmen/{assessment}/nilai', [\App\Http\Controllers\Dosen\InputNilaiController::class, 'show'])->whereNumber('assessment')->name('asesmen.nilai');
        Route::post('/asesmen/{assessment}/nilai', [\App\Http\Controllers\Dosen\InputNilaiController::class, 'store'])->whereNumber('assessment')->name('asesmen.nilai.store');
        Route::get('/asesmen/{assessment}/nilai/template', [\App\Http\Controllers\Dosen\InputNilaiController::class, 'downloadTemplate'])->whereNumber('assessment')->name('asesmen.nilai.template');
        Route::get('/asesmen/{assessment}/nilai/import', [\App\Http\Controllers\Dosen\InputNilaiController::class, 'import'])->whereNumber('assessment')->name('asesmen.nilai.import');
        Route::post('/asesmen/{assessment}/nilai/import', [\App\Http\Controllers\Dosen\InputNilaiController::class, 'processImport'])->whereNumber('assessment')->name('asesmen.nilai.import.process');

        // Rubric
        Route::get('/asesmen/{assessment}/rubrik', [\App\Http\Controllers\Dosen\RubricController::class, 'edit'])->whereNumber('assessment')->name('asesmen.rubrik');
        Route::post('/asesmen/{assessment}/rubrik', [\App\Http\Controllers\Dosen\RubricController::class, 'save'])->whereNumber('assessment')->name('asesmen.rubrik.save');
        Route::get('/asesmen/{assessment}/rubrik/nilai', [\App\Http\Controllers\Dosen\RubricController::class, 'scores'])->whereNumber('assessment')->name('asesmen.rubrik.nilai');
        Route::post('/asesmen/{assessment}/rubrik/nilai', [\App\Http\Controllers\Dosen\RubricController::class, 'storeScores'])->whereNumber('assessment')->name('asesmen.rubrik.nilai.store');

        // Export
        Route::get('/export', [\App\Http\Controllers\Dosen\ExportController::class, 'index'])->name('export');
        Route::get('/export/keseluruhan', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapKeseluruhan'])->name('export.keseluruhan');
        Route::get('/export/cpmk', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapCpmk'])->name('export.cpmk');
        Route::get('/export/cpl', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapCpl'])->name('export.cpl');
        Route::get('/export/nilai-asesmen', [\App\Http\Controllers\Dosen\ExportController::class, 'rekapNilaiAssessment'])->name('export.nilai');
    });
    // Forum Diskusi
    Route::get('/discussion', [LearningController::class, 'discussions'])->name('discussion.index');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/laporan/export', [AdminPreviewController::class, 'export'])->name('export');
    Route::post('/pengguna', [AdminPreviewController::class, 'user'])->name('users.store');
    Route::post('/pengguna/bulk', [AdminPreviewController::class, 'bulkUsers'])->name('users.bulk');
    Route::post('/pengguna/{id}/delete', [AdminPreviewController::class, 'deleteUser'])->whereNumber('id')->name('users.destroy');
    Route::post('/akademik/reset', [AdminPreviewController::class, 'resetAcademic'])->name('academic.reset');
    Route::post('/akademik/{id}/delete', [AdminPreviewController::class, 'deleteAcademic'])->whereNumber('id')->name('academic.destroy');
    Route::post('/akademik', [AdminPreviewController::class, 'academic'])->name('academic.store');
    Route::post('/pengaturan', [AdminPreviewController::class, 'settings'])->name('settings.store');
    Route::get('/{section?}', [AdminPreviewController::class, 'page'])->name('page');
});

Route::middleware('dosen.auth')->group(function () {
    Route::get('/dosen/course/{course}/akademik', [\App\Http\Controllers\AcademicController::class,'settings'])->whereNumber('course')->name('dosen.academic');
    Route::post('/dosen/course/{course}/akademik', [\App\Http\Controllers\AcademicController::class,'saveSettings'])->whereNumber('course')->name('dosen.academic.save');
    Route::get('/dosen/gradebook', [\App\Http\Controllers\AcademicController::class,'gradebook'])->name('dosen.gradebook');
    Route::post('/dosen/gradebook/{course}', [\App\Http\Controllers\AcademicController::class,'saveScores'])->whereNumber('course')->name('dosen.scores.save');
    Route::post('/dosen/gradebook/{course}/bulk', [\App\Http\Controllers\AcademicController::class,'bulkScores'])->whereNumber('course')->name('dosen.scores.bulk');
    Route::post('/dosen/penilaian/{item}', [\App\Http\Controllers\AcademicController::class,'gradeItem'])->whereNumber('item')->name('dosen.grade.save');
    Route::get('/dosen/course/{course}/item/{item}/penilaian', [\App\Http\Controllers\AcademicController::class, 'assessmentGrading'])->whereNumber(['course', 'item'])->name('dosen.item.penilaian');
    Route::get('/dosen/course/{course}/item/{item}/penilaian/{student}/{questionIndex?}', [\App\Http\Controllers\AcademicController::class, 'evaluateEssay'])->whereNumber(['course', 'item', 'student'])->name('dosen.item.penilaian.esai');
    Route::post('/dosen/course/{course}/item/{item}/penilaian/{student}/{questionIndex}', [\App\Http\Controllers\AcademicController::class, 'saveEssayScore'])->whereNumber(['course', 'item', 'student'])->name('dosen.item.penilaian.esai.save');
});

Route::get('/mahasiswa/nilai', [\App\Http\Controllers\AcademicController::class,'student'])->name('mahasiswa.nilai');

Route::post('/ai/login', [\App\Http\Controllers\AiTutorController::class, 'login'])->middleware('throttle:10,1')->name('ai.login');
Route::post('/ai/logout', [\App\Http\Controllers\AiTutorController::class, 'logout'])->name('ai.logout');
Route::get('/ai/tasks/{assignment}', [\App\Http\Controllers\AiTutorController::class, 'status'])->whereNumber('assignment')->name('ai.status');
Route::post('/ai/tasks/{assignment}', [\App\Http\Controllers\AiTutorController::class, 'send'])->whereNumber('assignment')->name('ai.send');

Route::get('/mahasiswa/capaian-obe', [\App\Http\Controllers\Mahasiswa\ObeProgressController::class, 'index'])->name('mahasiswa.obe.progress');

// Mahasiswa Join Kelas via Link / Barcode QR Code
Route::get('/join-kelas/{code}', [\App\Http\Controllers\Mahasiswa\EnrollmentController::class, 'join'])->name('mahasiswa.join-kelas');
Route::get('/kelas/{section}/qr', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'qrCode'])->name('kelas.qr');
Route::get('/kelas/{section}/barcode', [\App\Http\Controllers\AdminProdi\AkademikProdiController::class, 'barcode'])->name('kelas.barcode');

Route::prefix('kaprodi')->name('kaprodi.')->group(function () {
    Route::get('/monitoring/cpmk', [\App\Http\Controllers\Kaprodi\KaprodiMonitoringController::class, 'cpmk'])->name('monitoring.cpmk');
    Route::get('/monitoring/cpl', [\App\Http\Controllers\Kaprodi\KaprodiMonitoringController::class, 'cpl'])->name('monitoring.cpl');
});

// Ruang Kerja Admin Program Studi (Admin Kaprodi)
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

// Optional configured Piston runner; requires an authenticated account.
Route::post('/code/run/{assignment}', [\App\Http\Controllers\CodeRunnerController::class, 'run'])
    ->whereNumber('assignment')
    ->middleware(['auth', 'throttle:15,1'])
    ->name('code.run');
