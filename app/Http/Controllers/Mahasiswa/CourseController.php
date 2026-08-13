<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return view('mahasiswa.course');
    }

    public function show($id)
    {
        return view('mahasiswa.course-detail', compact('id'));
    }
}