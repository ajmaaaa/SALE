<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AdminPreviewController;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ClassSectionController;
use App\Http\Controllers\Dosen\PenilaianController;
use App\Http\Controllers\DosenAuthController;
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
    Route::get('/discussion', [LearningController::class, 'discussions'])->name('discussion.index');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
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
    Route::get('/course/{course}/create', [LearningController::class, 'createItem'])->whereNumber('course')->name('item.create');
    Route::post('/course/{course}/items', [LearningController::class, 'storeItem'])->whereNumber('course')->name('item.store');
    Route::view('/penilaian', 'dosen.grades')->name('grades');

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
    Route::prefix('penilaian-kelas/{section}')->name('penilaian.')->group(function () {
        Route::get('/', [PenilaianController::class, 'dashboard'])->name('dashboard');
        Route::get('/rekap', [PenilaianController::class, 'rekap'])->name('rekap');
        Route::get('/matriks', [PenilaianController::class, 'matriks'])->name('matriks');
        Route::get('/asesmen', [PenilaianController::class, 'asesmen'])->name('asesmen');
        Route::get('/asesmen/tambah', [AssessmentController::class, 'create'])->name('asesmen.create');
        Route::post('/asesmen', [AssessmentController::class, 'store'])->name('asesmen.store');
        Route::get('/asesmen/{assessment}/ubah', [AssessmentController::class, 'edit'])->whereNumber('assessment')->name('asesmen.edit');
        Route::put('/asesmen/{assessment}', [AssessmentController::class, 'update'])->whereNumber('assessment')->name('asesmen.update');
        Route::delete('/asesmen/{assessment}', [AssessmentController::class, 'destroy'])->whereNumber('assessment')->name('asesmen.destroy');
        Route::get('/cpmk', [PenilaianController::class, 'cpmk'])->name('cpmk');
        Route::get('/cpl', [PenilaianController::class, 'cpl'])->name('cpl');
        Route::get('/pengaturan', [PenilaianController::class, 'pengaturan'])->name('pengaturan');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/laporan/export', [AdminPreviewController::class, 'export'])->name('export');
    Route::post('/pengguna', [AdminPreviewController::class, 'user'])->name('users.store');
    Route::post('/pengguna/bulk', [AdminPreviewController::class, 'bulkUsers'])->name('users.bulk');
    Route::post('/akademik', [AdminPreviewController::class, 'academic'])->name('academic.store');
    Route::post('/pengaturan', [AdminPreviewController::class, 'settings'])->name('settings.store');
    Route::get('/{section?}', [AdminPreviewController::class, 'page'])->name('page');
});
