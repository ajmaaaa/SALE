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
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/laporan/export', [AdminPreviewController::class, 'export'])->name('export');
    Route::post('/pengguna', [AdminPreviewController::class, 'user'])->name('users.store');
    Route::post('/akademik', [AdminPreviewController::class, 'academic'])->name('academic.store');
    Route::post('/pengaturan', [AdminPreviewController::class, 'settings'])->name('settings.store');
    Route::get('/{section?}', [AdminPreviewController::class, 'page'])->name('page');
});
