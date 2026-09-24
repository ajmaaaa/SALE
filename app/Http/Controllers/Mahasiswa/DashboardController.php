<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Support\LearningPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('web')->user();
        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $enrolledSections = $user
            ? $user->classSectionsEnrolled()
                ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping'])
                ->withCount(['students', 'assessments'])
                ->get()
            : collect();

        if ($user) {
            $courses = $enrolledSections->map(fn ($sec) => [
                'id' => $sec->id,
                'code' => $sec->display_code,
                'sks' => $sec->mataKuliah->sks.' SKS',
                'title' => $sec->mataKuliah->name,
                'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                'dosen_ketua' => $sec->dosen?->name ?? 'Dosen Pengampu',
                'dosen_wakil' => $sec->dosenPendamping?->name,
                'cover' => null,
                'type' => 'Kelas Aktif',
                'work' => 'Perkuliahan semester '.($sec->semester->name ?? 'aktif'),
                'due' => '',
                'students_count' => $sec->students_count,
                'assessments_count' => $sec->assessments_count,
                'enrollment_code' => $sec->enrollment_code,
                'enrollment_url' => $sec->enrollment_url,
                'qr_url' => route('kelas.qr', $sec->id),
                'svg_index' => ($sec->id % 4) + 1,
            ]);
        } else {
            $courses = collect(LearningPreview::courses());
        }

        $activeItems = collect();
        if ($user && $enrolledSections->isNotEmpty()) {
            if (Schema::hasTable('assessments')) {
                $sectionIds = $enrolledSections->pluck('id');
                $assessments = Assessment::whereIn('class_section_id', $sectionIds)
                    ->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project'])
                    ->where('status', 'published')
                    ->get();

                $scoredIds = [];
                if (Schema::hasTable('student_assessment_scores')) {
                    $scoredIds = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                        ->whereNotNull('score')
                        ->pluck('assessment_id')
                        ->toArray();
                }

                $activeItems = $assessments->reject(function ($asm) use ($scoredIds) {
                    return in_array($asm->id, $scoredIds, true)
                        || session("learning.submissions.{$asm->id}") !== null
                        || session("learning.grades.{$asm->id}") !== null
                        || session("academic.item_grades.{$asm->id}.1") !== null;
                });
            }
        } elseif (! $user) {
            $activeItems = collect(LearningPreview::items())
                ->filter(fn ($item) => in_array($item['type'], ['tugas', 'coding', 'kuis', 'uts', 'uas'], true))
                ->reject(fn ($item) => session("learning.submissions.{$item['id']}") !== null || session("learning.grades.{$item['id']}") !== null)
                ->sortBy('due')
                ->values();
        }

        return view('mahasiswa.dashboard', [
            'student' => $user,
            'enrolledSections' => $enrolledSections,
            'courses' => $courses,
            'activeItems' => $activeItems,
        ]);
    }
}
