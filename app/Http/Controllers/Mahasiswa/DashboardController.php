<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Support\AcademicPreview;
use App\Support\LearningPreview;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $studentId = (int) session('auth_user.id', 1);
        $courses = LearningPreview::courses();
        $courseResults = [];
        foreach ($courses as $course) {
            $courseResults[$course['id']] = AcademicPreview::result($course['id'], $studentId);
        }

        $activeItems = collect(LearningPreview::items())
            ->filter(fn ($item) => in_array($item['type'], ['tugas', 'coding', 'kuis'], true))
            ->reject(fn ($item) => session("learning.submissions.{$item['id']}"))
            ->sortBy('due')
            ->values();

        return view('mahasiswa.dashboard', compact('courses', 'courseResults', 'activeItems'));
    }
}
