<?php

use App\Http\Controllers\Mahasiswa\AssignmentController;
use App\Http\Controllers\Mahasiswa\CourseController;
use App\Http\Controllers\Mahasiswa\DashboardController;
use App\Http\Controllers\Mahasiswa\DiscussionController;
use App\Http\Controllers\Mahasiswa\GradeController;
use App\Http\Controllers\Mahasiswa\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('mahasiswa.dashboard');
});

// Public only while SALE remains a frontend prototype. See README before adding real data.
Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/course', [CourseController::class, 'index'])->name('course.index');
    Route::get('/course/{course}', [CourseController::class, 'show'])->whereNumber('course')->name('course.show');

    Route::get('/assignment', [AssignmentController::class, 'index'])->name('assignment.index');
    Route::get('/assignment/{assignment}/code', [AssignmentController::class, 'code'])->whereNumber('assignment')->name('assignment.code');

    Route::get('/grade', [GradeController::class, 'index'])->name('grade.index');
    Route::get('/discussion', [DiscussionController::class, 'index'])->name('discussion.index');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});
