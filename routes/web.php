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

Route::prefix('mahasiswa')->name('mahasiswa.')->middleware('mahasiswa.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');

    Route::get('/assignment', [LearningController::class, 'assignments'])->name('assignment.index');
    Route::get('/assignment/{assignment}/code', [AssignmentController::class, 'code'])->whereNumber('assignment')->name('assignment.code');

    Route::get('/grade', fn () => redirect()->route('mahasiswa.assignment.index', ['tab' => 'nilai']))->name('grade.index');
    Route::get('/discussion', [LearningController::class, 'discussions'])->name('discussion.index');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::get('/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->name('course.item');
    Route::get('/course/{course}/item/{item}/quiz', [LearningController::class, 'quizRoom'])->whereNumber(['course', 'item'])->name('quiz.room');
    Route::post('/course/{course}/discussion', [LearningController::class, 'discussCourse'])->whereNumber('course')->name('course.discuss.class');
    Route::post('/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->name('course.discuss');
    Route::post('/course/{course}/item/{item}/submission', [LearningController::class, 'submit'])->whereNumber(['course', 'item'])->name('course.submit');
    Route::view('/notifikasi', 'learning.notifications')->name('notifications');
    Route::get('/nilai', [\App\Http\Controllers\AcademicController::class,'student'])->name('nilai');
    Route::get('/capaian-obe', [\App\Http\Controllers\Mahasiswa\ObeProgressController::class, 'index'])->name('obe.progress');
});

Route::get('/preview/files/{file}', [LearningController::class, 'file'])->whereUuid('file')->name('preview.file');

// Chat Real-Time & Diskusi Kelas (Sesuai desain-sistem-chat-diskusi.md)
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('/course/{course}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])->whereNumber('course')->name('messages.index');
    Route::post('/course/{course}/messages', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->whereNumber('course')->name('messages.store');
    Route::post('/messages/{message}/pin', [\App\Http\Controllers\ChatController::class, 'pinMessage'])->whereNumber('message')->name('messages.pin');
    Route::delete('/messages/{message}', [\App\Http\Controllers\ChatController::class, 'deleteMessage'])->whereNumber('message')->name('messages.destroy');
    Route::get('/course/{course}/members', [\App\Http\Controllers\ChatController::class, 'getMembers'])->whereNumber('course')->name('members');
});

Route::prefix('dosen')->name('dosen.')->middleware('dosen.auth')->group(function () {
    Route::view('/dashboard', 'dosen.dashboard')->name('dashboard');
    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');
    Route::get('/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->name('course.item');
    Route::post('/course/{course}/discussion', [LearningController::class, 'discussCourse'])->whereNumber('course')->name('course.discuss.class');
    Route::post('/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->name('course.discuss');
    Route::get('/course/{course}/create', [LearningController::class, 'createItem'])->whereNumber('course')->name('item.create');
    Route::post('/course/{course}/items', [LearningController::class, 'storeItem'])->whereNumber('course')->name('item.store');

    // OBE assessment & rekap nilai module
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

    // Penilaian & Gradebook
    Route::get('/gradebook', [\App\Http\Controllers\AcademicController::class,'gradebook'])->name('gradebook');
    Route::post('/gradebook/{course}', [\App\Http\Controllers\AcademicController::class,'saveScores'])->whereNumber('course')->name('scores.save');
    Route::post('/gradebook/{course}/bulk', [\App\Http\Controllers\AcademicController::class,'bulkScores'])->whereNumber('course')->name('scores.bulk');
    Route::post('/penilaian/{item}', [\App\Http\Controllers\AcademicController::class,'gradeItem'])->whereNumber('item')->name('grade.save');
    Route::get('/course/{course}/item/{item}/penilaian', [\App\Http\Controllers\AcademicController::class, 'assessmentGrading'])->whereNumber(['course', 'item'])->name('item.penilaian');
    Route::get('/course/{course}/item/{item}/penilaian/{student}/{questionIndex?}', [\App\Http\Controllers\AcademicController::class, 'evaluateEssay'])->whereNumber(['course', 'item', 'student'])->name('item.penilaian.esai');
    Route::post('/course/{course}/item/{item}/penilaian/{student}/{questionIndex}', [\App\Http\Controllers\AcademicController::class, 'saveEssayScore'])->whereNumber(['course', 'item', 'student'])->name('item.penilaian.esai.save');
    Route::get('/course/{course}/item/{item}/penilaian-tugas', [\App\Http\Controllers\AcademicController::class, 'tugasGrading'])->whereNumber(['course', 'item'])->name('item.penilaian.tugas');
    Route::post('/course/{course}/item/{item}/penilaian-tugas/{student}', [\App\Http\Controllers\AcademicController::class, 'saveTugasScore'])->whereNumber(['course', 'item', 'student'])->name('item.penilaian.tugas.save');
});

Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
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

Route::prefix('kaprodi')->name('kaprodi.')->middleware('kaprodi.auth')->group(function () {
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

// AI Tutor routes
Route::prefix('ai')->group(function () {
    Route::post('/login', [\App\Http\Controllers\AiTutorController::class, 'login'])->name('ai.login');
    Route::post('/logout', [\App\Http\Controllers\AiTutorController::class, 'logout'])->name('ai.logout');
    Route::get('/status/{assignment}', [\App\Http\Controllers\AiTutorController::class, 'status'])->whereNumber('assignment')->name('ai.status');
    Route::post('/send/{assignment}', [\App\Http\Controllers\AiTutorController::class, 'send'])->whereNumber('assignment')->name('ai.send');
});

