<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Support\LearningPreview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $isCodingContent = $item && (
            $item['type'] === 'coding'
            || ($item['type'] === 'materi' && ($item['material_mode'] ?? null) === 'coding')
        );
        abort_unless($isCodingContent, 404);

        if (Schema::hasTable('ai_tasks')) {
            $aiTask = DB::table('ai_tasks')->where('id', $assignment)->first();
            if ($aiTask) {
                $item['title'] = $aiTask->title;
                $item['body'] = $aiTask->body;
            }
        }

        return view('mahasiswa.assignment-code', ['item' => $item, 'course' => LearningPreview::course($item['course'])]);
    }
}
