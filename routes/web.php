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
Route::get('/mahasiswa/notifikasi', [LearningController::class, 'notifications'])->name('mahasiswa.notifications');
Route::get('/preview/files/{file}', [LearningController::class, 'file'])->whereUuid('file')->name('preview.file');

Route::prefix('dosen')->name('dosen.')->group(function () {
    Route::view('/dashboard', 'dosen.dashboard')->name('dashboard');
    Route::get('/course', [LearningController::class, 'courses'])->name('course.index');
    Route::get('/course/create', [LearningController::class, 'createCourse'])->name('course.create');
    Route::post('/course', [LearningController::class, 'storeCourse'])->name('course.store');
    Route::get('/course/{course}', [LearningController::class, 'course'])->whereNumber('course')->name('course.show');
    Route::get('/course/{course}/students', [LearningController::class, 'students'])->whereNumber('course')->name('course.students');
    Route::post('/course/{course}/students', [LearningController::class, 'saveStudents'])->whereNumber('course')->name('course.students.save');
    Route::post('/course/{course}/students/create', [LearningController::class, 'addStudent'])->whereNumber('course')->name('course.students.create');
    Route::get('/course/{course}/create', [LearningController::class, 'createItem'])->whereNumber('course')->name('item.create');
    Route::post('/course/{course}/items', [LearningController::class, 'storeItem'])->whereNumber('course')->name('item.store');
    Route::view('/penilaian', 'dosen.grades')->name('grades');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/laporan/export', [AdminPreviewController::class, 'export'])->name('export');
    Route::post('/pengguna', [AdminPreviewController::class, 'user'])->name('users.store');
    Route::post('/pengguna/bulk', [AdminPreviewController::class, 'bulkUsers'])->name('users.bulk');
    Route::post('/akademik', [AdminPreviewController::class, 'academic'])->name('academic.store');
    Route::post('/pengaturan', [AdminPreviewController::class, 'settings'])->name('settings.store');
    Route::get('/{section?}', [AdminPreviewController::class, 'page'])->name('page');
});

Route::get('/dosen/course/{course}/akademik', [\App\Http\Controllers\AcademicController::class,'settings'])->whereNumber('course')->name('dosen.academic');
Route::post('/dosen/course/{course}/akademik', [\App\Http\Controllers\AcademicController::class,'saveSettings'])->whereNumber('course')->name('dosen.academic.save');
Route::get('/dosen/gradebook', [\App\Http\Controllers\AcademicController::class,'gradebook'])->name('dosen.gradebook');
Route::post('/dosen/gradebook/{course}', [\App\Http\Controllers\AcademicController::class,'saveScores'])->whereNumber('course')->name('dosen.scores.save');
Route::post('/dosen/gradebook/{course}/bulk', [\App\Http\Controllers\AcademicController::class,'bulkScores'])->whereNumber('course')->name('dosen.scores.bulk');
Route::post('/dosen/penilaian/{item}', [\App\Http\Controllers\AcademicController::class,'gradeItem'])->whereNumber('item')->name('dosen.grade.save');
Route::get('/mahasiswa/nilai', [\App\Http\Controllers\AcademicController::class,'student'])->name('mahasiswa.nilai');

Route::post('/ai/login', [\App\Http\Controllers\AiTutorController::class, 'login'])->middleware('throttle:10,1')->name('ai.login');
Route::post('/ai/logout', [\App\Http\Controllers\AiTutorController::class, 'logout'])->name('ai.logout');
Route::get('/ai/tasks/{assignment}', [\App\Http\Controllers\AiTutorController::class, 'status'])->whereNumber('assignment')->name('ai.status');
Route::post('/ai/tasks/{assignment}', [\App\Http\Controllers\AiTutorController::class, 'send'])->whereNumber('assignment')->name('ai.send');

// Optional configured Piston runner; requires an authenticated account.
Route::post('/code/run/{assignment}', [\App\Http\Controllers\CodeRunnerController::class, 'run'])
    ->whereNumber('assignment')
    ->middleware(['auth', 'throttle:15,1'])
    ->name('code.run');
