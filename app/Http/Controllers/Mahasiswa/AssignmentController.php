<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Support\LearningPreview;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        return view('mahasiswa.assignment');
    }

    public function code(int $assignment): View
    {
        $item = LearningPreview::items()[$assignment] ?? null;
        abort_unless($item && $item['type'] === 'coding', 404);

        return view('mahasiswa.assignment-code', ['item' => $item, 'course' => LearningPreview::course($item['course'])]);
    }
}
