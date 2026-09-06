<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        return view('mahasiswa.assignment');
    }

    public function code(int $assignment): View
    {
        abort_unless($assignment === 1, 404);

        return view('mahasiswa.assignment-code');
    }
}
