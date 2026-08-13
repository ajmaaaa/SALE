<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mahasiswa;

Route::get('/', function () {
    return redirect('/mahasiswa/dashboard');
});

// Group Route tanpa Auth Middleware (khusus untuk cek tampilan frontend dulu)
Route::prefix('mahasiswa')->group(function () {
    
    // 1. Dashboard Mahasiswa
    Route::get('/dashboard', [Mahasiswa\DashboardController::class, 'index'])->name('mahasiswa.dashboard');

    // 2. Courses / Mata Kuliah
    Route::get('/course', [Mahasiswa\CourseController::class, 'index'])->name('mahasiswa.course.index');
    Route::get('/course/{id}', [Mahasiswa\CourseController::class, 'show'])->name('mahasiswa.course.show');

    // 3. Assignments / Tugas & Kuis
    Route::get('/assignment', [Mahasiswa\AssignmentController::class, 'index'])->name('mahasiswa.assignment.index');
    Route::get('/assignment/{id}/code', [Mahasiswa\AssignmentController::class, 'doCode'])->name('mahasiswa.assignment.code');

    // 4. Discussion / Forum Diskusi
    Route::get('/discussion', [Mahasiswa\DiscussionController::class, 'index'])->name('mahasiswa.discussion.index');

    // 5. Profile & Settings
    Route::get('/profile', [Mahasiswa\ProfileController::class, 'index'])->name('mahasiswa.profile.index');

});