<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        return view('mahasiswa.assignment');
    }

    public function doCode($id)
    {
        return view('mahasiswa.assignment-code', compact('id'));
    }
}