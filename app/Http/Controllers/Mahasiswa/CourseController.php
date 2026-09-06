<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        return view('mahasiswa.course');
    }

    public function show(int $course): View
    {
        abort_unless(in_array($course, [1, 2, 3, 4], true), 404);

        return view('mahasiswa.course-detail');
    }
}
