<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Role;
use App\Support\LearningPreview;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        return view('mahasiswa.assignment');
    }

    public function courseCode(int $course, int $item): View
    {
        $user = auth()->user();
        $section = ClassSection::with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])->findOrFail($course);
        $canAccess = $user?->hasRole(Role::DOSEN)
            ? $user->can('manage', $section)
            : ($user?->hasRole(Role::MAHASISWA)
                && $section->students()->where('users.id', $user->id)->exists());

        abort_unless($canAccess, 403, 'Anda tidak terdaftar pada kelas ini.');

        $assessment = Assessment::where('class_section_id', $section->id)->findOrFail($item);
        if ($user->hasRole(Role::MAHASISWA)) {
            abort_unless($assessment->status === 'published', 403, 'Materi belum tersedia atau sudah ditutup.');
        }

        $resource = LearningPreview::databaseAssessment($assessment);
        $isCodingContent = $resource['type'] === 'coding'
            || ($resource['type'] === 'materi' && ($resource['material_mode'] ?? null) === 'coding')
            || (($resource['task_mode'] ?? null) === 'coding');
        abort_unless($isCodingContent, 404);

        return view('mahasiswa.assignment-code', [
            'item' => $resource,
            'course' => LearningPreview::databaseCourse($section),
        ]);
    }
}
