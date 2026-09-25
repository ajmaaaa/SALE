<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AdminPreviewController;
use App\Http\Controllers\AdminProdi\AdminProdiDashboardController;
use App\Http\Controllers\AdminProdi\AkademikProdiController;
use App\Http\Controllers\AdminProdi\KurikulumController;
use App\Http\Controllers\AdminProdi\LaporanProdiController;
use App\Http\Controllers\AdminProdi\ProdiManagementController;
use App\Http\Controllers\AdminProdi\UserProdiController;
use App\Http\Controllers\AiTutorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CodeRunnerController;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ClassSectionController;
use App\Http\Controllers\Dosen\DashboardController as DosenDashboardController;
use App\Http\Controllers\Dosen\ExportController;
use App\Http\Controllers\Dosen\InputNilaiController;
use App\Http\Controllers\Dosen\PenilaianController;
use App\Http\Controllers\Dosen\RubricController;
use App\Http\Controllers\Kaprodi\KaprodiMonitoringController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\Mahasiswa\AssignmentController;
use App\Http\Controllers\Mahasiswa\DashboardController;
use App\Http\Controllers\Mahasiswa\EnrollmentController;
use App\Http\Controllers\Mahasiswa\ObeProgressController;
use App\Http\Controllers\Mahasiswa\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/switch-role/{role}', [AuthController::class, 'switchRole'])->name('switch-role');

// Demo personas still require an active matching role; operational environments use database users.
Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
        Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');

        Route::get('/assignment', [LearningController::class, 'assignments'])->name('assignment.index');
        Route::get('/grade', fn () => redirect()->route('mahasiswa.assignment.index', ['tab' => 'nilai']))->name('grade.index');
        Route::get('/discussion', [LearningController::class, 'discussions'])->name('discussion.index');
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    });

    Route::get('/assignment/{assignment}/code', [AssignmentController::class, 'code'])->whereNumber('assignment')->middleware('role:mahasiswa,dosen,kaprodi')->name('assignment.code');
});

Route::get('/mahasiswa/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->middleware('role:mahasiswa,dosen,kaprodi')->name('mahasiswa.course.item');
Route::post('/mahasiswa/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->middleware('role:mahasiswa,dosen,kaprodi')->name('mahasiswa.course.discuss');

Route::middleware('role:mahasiswa')->group(function () {
    Route::get('/mahasiswa/course/{course}/item/{item}/quiz', [LearningController::class, 'quizRoom'])->whereNumber(['course', 'item'])->name('mahasiswa.quiz.room');
    Route::post('/mahasiswa/course/{course}/discussion', [LearningController::class, 'discussCourse'])->whereNumber('course')->name('mahasiswa.course.discuss.class');
    Route::post('/mahasiswa/course/{course}/item/{item}/submission', [LearningController::class, 'submit'])->whereNumber(['course', 'item'])->name('mahasiswa.course.submit');
    Route::post('/mahasiswa/course/{course}/item/{item}/submission/cancel', [LearningController::class, 'cancelSubmission'])->whereNumber(['course', 'item'])->name('mahasiswa.course.submission.cancel');
    Route::get('/mahasiswa/notifikasi', [LearningController::class, 'notifications'])->name('mahasiswa.notifications');
    Route::match(['get', 'post'], '/mahasiswa/notifikasi/{id}/read', [LearningController::class, 'markNotificationRead'])->name('mahasiswa.notifications.read');
    Route::post('/mahasiswa/notifikasi/clear', [LearningController::class, 'clearNotifications'])->name('mahasiswa.notifications.clear');
    Route::post('/mahasiswa/notifikasi/{id}/delete', [LearningController::class, 'deleteNotification'])->name('mahasiswa.notifications.delete');
});
Route::get('/preview/files/{file}', [LearningController::class, 'file'])->middleware('role:mahasiswa,dosen')->whereUuid('file')->name('preview.file');

// Chat Real-Time & Diskusi Kelas
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('/course/{course}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages'])->whereNumber('course')->name('messages.index');
    Route::post('/course/{course}/messages', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->whereNumber('course')->name('messages.store');
    Route::post('/messages/{message}/pin', [\App\Http\Controllers\ChatController::class, 'pinMessage'])->whereNumber('message')->name('messages.pin');
    Route::delete('/messages/{message}', [\App\Http\Controllers\ChatController::class, 'deleteMessage'])->whereNumber('message')->name('messages.destroy');
    Route::get('/course/{course}/members', [\App\Http\Controllers\ChatController::class, 'getMembers'])->whereNumber('course')->name('members');
});

Route::prefix('dosen')->name('dosen.')->middleware('role:dosen')->group(function () {
    Route::get('/dashboard', [DosenDashboardController::class, 'index'])->name('dashboard');
    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/create', [LearningController::class, 'createCourse'])->name('course.create');
    Route::post('/course', [LearningController::class, 'storeCourse'])->name('course.store');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');
    Route::get('/course/{course}/item/{item}', [LearningController::class, 'item'])->whereNumber(['course', 'item'])->name('course.item');
    Route::post('/course/{course}/item/{item}/discussion', [LearningController::class, 'discuss'])->whereNumber(['course', 'item'])->name('course.discuss');
    Route::get('/assignment/{assignment}/code', [AssignmentController::class, 'code'])->whereNumber('assignment')->name('assignment.code');
    Route::get('/course/{course}/create', [LearningController::class, 'createItem'])->whereNumber('course')->name('item.create');
    Route::post('/course/{course}/items', [LearningController::class, 'storeItem'])->whereNumber('course')->name('item.store');
    Route::post('/course/{course}/discussion', [LearningController::class, 'discussCourse'])->whereNumber('course')->name('course.discuss.class');
    Route::view('/penilaian', 'dosen.grades')->name('grades');

    Route::get('/penilaian-kelas', [ClassSectionController::class, 'index'])->name('penilaian.index');
    Route::get('/rekap-nilai', [ClassSectionController::class, 'rekapIndex'])->name('rekap.index');
    Route::prefix('penilaian-kelas/{section}')->name('penilaian.')->group(function () {
        Route::get('/', [PenilaianController::class, 'dashboard'])->name('dashboard');
        Route::get('/rekap', [PenilaianController::class, 'rekap'])->name('rekap');
        Route::get('/rekap/export', [ExportController::class, 'rekapKeseluruhan'])->name('rekap.export');
        Route::get('/cpmk/export', [ExportController::class, 'rekapCpmk'])->name('cpmk.export');
        Route::get('/cpl/export', [ExportController::class, 'rekapCpl'])->name('cpl.export');
        Route::get('/rekap/print', [ExportController::class, 'printRekap'])->name('rekap.print');
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

        Route::get('/asesmen/{assessment}/nilai', [InputNilaiController::class, 'show'])->whereNumber('assessment')->name('asesmen.nilai');
        Route::post('/asesmen/{assessment}/nilai', [InputNilaiController::class, 'store'])->whereNumber('assessment')->name('asesmen.nilai.store');
        Route::get('/asesmen/{assessment}/nilai/template', [InputNilaiController::class, 'downloadTemplate'])->whereNumber('assessment')->name('asesmen.nilai.template');
        Route::get('/asesmen/{assessment}/nilai/import', [InputNilaiController::class, 'import'])->whereNumber('assessment')->name('asesmen.nilai.import');
        Route::post('/asesmen/{assessment}/nilai/import', [InputNilaiController::class, 'processImport'])->whereNumber('assessment')->name('asesmen.nilai.import.process');

        Route::get('/asesmen/{assessment}/rubrik', [RubricController::class, 'edit'])->whereNumber('assessment')->name('asesmen.rubrik');
        Route::post('/asesmen/{assessment}/rubrik', [RubricController::class, 'save'])->whereNumber('assessment')->name('asesmen.rubrik.save');
        Route::get('/asesmen/{assessment}/rubrik/nilai', [RubricController::class, 'scores'])->whereNumber('assessment')->name('asesmen.rubrik.nilai');
        Route::post('/asesmen/{assessment}/rubrik/nilai', [RubricController::class, 'storeScores'])->whereNumber('assessment')->name('asesmen.rubrik.nilai.store');

        Route::get('/export', [ExportController::class, 'index'])->name('export');
        Route::get('/export/keseluruhan', [ExportController::class, 'rekapKeseluruhan'])->name('export.keseluruhan');
        Route::get('/export/cpmk', [ExportController::class, 'rekapCpmk'])->name('export.cpmk');
        Route::get('/export/cpl', [ExportController::class, 'rekapCpl'])->name('export.cpl');
        Route::get('/export/nilai-asesmen', [ExportController::class, 'rekapNilaiAssessment'])->name('export.nilai');
    });
    Route::get('/discussion', [LearningController::class, 'discussions'])->name('discussion.index');
    Route::get('/notifikasi', [LearningController::class, 'dosenNotifications'])->name('notifications');
    Route::match(['get', 'post'], '/notifikasi/{id}/read', [LearningController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifikasi/clear', [LearningController::class, 'clearNotifications'])->name('notifications.clear');
    Route::post('/notifikasi/{id}/delete', [LearningController::class, 'deleteNotification'])->name('notifications.delete');
    Route::get('/profil', [\App\Http\Controllers\Dosen\ProfileController::class, 'index'])->name('profile.index');
});

Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
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

Route::middleware('role:dosen')->group(function () {
    Route::get('/dosen/course/{course}/akademik', [AcademicController::class, 'settings'])->whereNumber('course')->name('dosen.academic');
    Route::post('/dosen/course/{course}/akademik', [AcademicController::class, 'saveSettings'])->whereNumber('course')->name('dosen.academic.save');
    Route::get('/dosen/gradebook', [AcademicController::class, 'gradebook'])->name('dosen.gradebook');
    Route::post('/dosen/gradebook/{course}', [AcademicController::class, 'saveScores'])->whereNumber('course')->name('dosen.scores.save');
    Route::post('/dosen/gradebook/{course}/bulk', [AcademicController::class, 'bulkScores'])->whereNumber('course')->name('dosen.scores.bulk');
    Route::post('/dosen/penilaian/{item}', [AcademicController::class, 'gradeItem'])->whereNumber('item')->name('dosen.grade.save');
    Route::get('/dosen/course/{course}/item/{item}/penilaian', [AcademicController::class, 'assessmentGrading'])->whereNumber(['course', 'item'])->name('dosen.item.penilaian');
    Route::get('/dosen/course/{course}/item/{item}/penilaian/{student}/{questionIndex?}', [AcademicController::class, 'evaluateEssay'])->whereNumber(['course', 'item', 'student'])->name('dosen.item.penilaian.esai');
    Route::post('/dosen/course/{course}/item/{item}/penilaian/{student}/{questionIndex}', [AcademicController::class, 'saveEssayScore'])->whereNumber(['course', 'item', 'student'])->name('dosen.item.penilaian.esai.save');
    Route::get('/dosen/course/{course}/item/{item}/penilaian-tugas', [AcademicController::class, 'tugasGrading'])->whereNumber(['course', 'item'])->name('dosen.item.penilaian.tugas');
    Route::post('/dosen/course/{course}/item/{item}/penilaian-tugas/{student}', [AcademicController::class, 'saveTugasScore'])->whereNumber(['course', 'item', 'student'])->name('dosen.item.penilaian.tugas.save');
});
Route::get('/mahasiswa/nilai', [AcademicController::class, 'student'])->middleware('role:mahasiswa')->name('mahasiswa.nilai');

Route::post('/ai/login', [AiTutorController::class, 'login'])->middleware('throttle:10,1')->name('ai.login');
Route::post('/ai/logout', [AiTutorController::class, 'logout'])->middleware('auth')->name('ai.logout');
Route::get('/ai/tasks/{assignment}', [AiTutorController::class, 'status'])->middleware('auth')->whereNumber('assignment')->name('ai.status');
Route::post('/ai/tasks/{assignment}', [AiTutorController::class, 'send'])->middleware('auth')->whereNumber('assignment')->name('ai.send');

Route::get('/mahasiswa/capaian-obe', [ObeProgressController::class, 'index'])->middleware('role:mahasiswa')->name('mahasiswa.obe.progress');

Route::get('/join-kelas/{code}', [EnrollmentController::class, 'confirm'])->middleware('role:mahasiswa,dosen')->name('mahasiswa.join-kelas');
Route::post('/join-kelas/{code}', [EnrollmentController::class, 'join'])->middleware('role:mahasiswa,dosen')->name('mahasiswa.join-kelas.post');
Route::post('/join-kelas-langsung', [EnrollmentController::class, 'joinDirect'])->middleware('role:mahasiswa,dosen')->name('mahasiswa.join-kelas.direct');
Route::get('/kelas/{section}/qr', [AkademikProdiController::class, 'qrCode'])->middleware('role:dosen,admin_prodi,admin')->name('kelas.qr');
Route::get('/kelas/{section}/barcode', [AkademikProdiController::class, 'barcode'])->middleware('role:dosen,admin_prodi,admin')->name('kelas.barcode');

Route::prefix('kaprodi')->name('kaprodi.')->middleware('role:kaprodi')->group(function () {
    Route::get('/monitoring/cpmk', [KaprodiMonitoringController::class, 'cpmk'])->name('monitoring.cpmk');
    Route::get('/monitoring/cpl', [KaprodiMonitoringController::class, 'cpl'])->name('monitoring.cpl');
});

Route::prefix('admin-prodi')->name('admin-prodi.')->middleware('admin_prodi.auth')->group(function () {
    Route::get('/', fn () => redirect()->route('admin-prodi.dashboard'));
    Route::get('/dashboard', [AdminProdiDashboardController::class, 'index'])->name('dashboard');

    Route::redirect('/prodi', '/admin-prodi/dashboard')->name('prodi.index');
    Route::post('/prodi', [ProdiManagementController::class, 'store'])->name('prodi.store');
    Route::put('/prodi/{prodi}', [ProdiManagementController::class, 'update'])->name('prodi.update');
    Route::delete('/prodi/{prodi}', [ProdiManagementController::class, 'destroy'])->name('prodi.destroy');

    Route::get('/kurikulum', [KurikulumController::class, 'index'])->name('kurikulum.index');
    Route::post('/kurikulum/cpl', [KurikulumController::class, 'storeCpl'])->name('kurikulum.cpl.store');
    Route::put('/kurikulum/cpl/{cpl}', [KurikulumController::class, 'updateCpl'])->name('kurikulum.cpl.update');
    Route::delete('/kurikulum/cpl/{cpl}', [KurikulumController::class, 'destroyCpl'])->name('kurikulum.cpl.destroy');
    Route::post('/kurikulum/cpmk', [KurikulumController::class, 'storeCpmk'])->name('kurikulum.cpmk.store');
    Route::put('/kurikulum/cpmk/{cpmk}', [KurikulumController::class, 'updateCpmk'])->name('kurikulum.cpmk.update');
    Route::delete('/kurikulum/cpmk/{cpmk}', [KurikulumController::class, 'destroyCpmk'])->name('kurikulum.cpmk.destroy');
    Route::post('/kurikulum/mapping', [KurikulumController::class, 'updateMapping'])->name('kurikulum.mapping.update');

    Route::get('/akademik/matakuliah', [AkademikProdiController::class, 'matakuliahIndex'])->name('akademik.matakuliah');
    Route::post('/akademik/matakuliah', [AkademikProdiController::class, 'storeMataKuliah'])->name('akademik.matakuliah.store');
    Route::put('/akademik/matakuliah/{mataKuliah}', [AkademikProdiController::class, 'updateMataKuliah'])->name('akademik.matakuliah.update');
    Route::delete('/akademik/matakuliah/{mataKuliah}', [AkademikProdiController::class, 'destroyMataKuliah'])->name('akademik.matakuliah.destroy');

    Route::get('/akademik/kelas', [AkademikProdiController::class, 'kelasIndex'])->name('akademik.kelas');
    Route::post('/akademik/kelas', [AkademikProdiController::class, 'storeKelas'])->name('akademik.kelas.store');
    Route::put('/akademik/kelas/{section}', [AkademikProdiController::class, 'updateKelas'])->name('akademik.kelas.update');
    Route::delete('/akademik/kelas/{section}', [AkademikProdiController::class, 'destroyKelas'])->name('akademik.kelas.destroy');
    Route::post('/akademik/kelas/{section}/regenerate-code', [AkademikProdiController::class, 'regenerateCode'])->name('akademik.kelas.regenerate-code');
    Route::get('/akademik/kelas/{section}/qr', [AkademikProdiController::class, 'qrCode'])->name('akademik.kelas.qr');
    Route::get('/akademik/kelas/{section}/barcode', [AkademikProdiController::class, 'barcode'])->name('akademik.kelas.barcode');

    Route::get('/pengguna', [UserProdiController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [UserProdiController::class, 'store'])->name('users.store');
    Route::put('/pengguna/{user}', [UserProdiController::class, 'update'])->name('users.update');
    Route::delete('/pengguna/{user}', [UserProdiController::class, 'destroy'])->name('users.destroy');
    Route::get('/pengguna/template/{type}', [UserProdiController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/pengguna/import', [UserProdiController::class, 'import'])->name('users.import');

    Route::get('/laporan', [LaporanProdiController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [LaporanProdiController::class, 'export'])->name('laporan.export');
    Route::get('/laporan/cetak', [LaporanProdiController::class, 'print'])->name('laporan.print');
});

Route::post('/code/run/{assignment}', [CodeRunnerController::class, 'run'])
    ->whereNumber('assignment')
    ->middleware(['auth', 'throttle:15,1'])
    ->name('code.run');
