<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use App\Services\DatabaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DatabaseNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $user = Auth::guard('web')->user();
        $enrolledSections = $user
            ? $user->classSectionsEnrolled()
                ->with(['mataKuliah.prodi', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota', 'assessments'])
                ->withCount(['students', 'assessments'])
                ->get()
            : collect();

        $activeItems = collect();
        if ($user && $enrolledSections->isNotEmpty()) {
            if (Schema::hasTable('assessments')) {
                // Hanya hitung tugas aktif dari kelas yang belum diarsipkan
                $activeSectionIds = $enrolledSections->filter(fn ($s) => ! $s->isArchived())->pluck('id');
                $assessments = Assessment::whereIn('class_section_id', $activeSectionIds)
                    ->whereIn('type', ['tugas', 'coding', 'kuis', 'uts', 'uas', 'pbl', 'case', 'project', 'lainnya'])
                    ->where('status', 'published')
                    ->get();

                $scoredIds = [];
                if (Schema::hasTable('student_assessment_scores')) {
                    $scoredIds = StudentAssessmentScore::where('mahasiswa_id', $user->id)
                        ->where('status', StudentAssessmentScore::STATUS_PUBLISHED)
                        ->whereNotNull('score')
                        ->pluck('assessment_id')
                        ->toArray();
                }

                $submittedIds = \App\Models\Submission::where('mahasiswa_id', $user->id)
                    ->whereIn('assessment_id', $assessments->pluck('id'))
                    ->pluck('assessment_id')->all();
                $activeItems = $assessments->reject(fn ($asm) => in_array($asm->id, $scoredIds, true)
                    || in_array($asm->id, $submittedIds, true));
            }
        }

        if ($user) {
            $activeSections = $enrolledSections->filter(fn ($s) => ! $s->isArchived());
            $courses = $activeSections->map(function ($sec) use ($activeItems) {
                $secAssessments = $sec->relationLoaded('assessments') ? $sec->assessments : $sec->assessments()->get();

                // 1. Cek tugas dalam waktu dekat (belum dikerjakan dan belum terlewat / upcoming)
                $secActiveTasks = $activeItems->where('class_section_id', $sec->id);
                $upcomingTasks = $secActiveTasks->filter(fn ($asm) => ! empty($asm->due_at) && $asm->due_at->isFuture())->sortBy('due_at');
                $nearestUpcomingTask = $upcomingTasks->first();

                // 2. Cek update materi terbaru
                $latestMaterial = $secAssessments
                    ->where('type', 'materi')
                    ->where('status', 'published')
                    ->sortByDesc(fn ($asm) => $asm->updated_at ? $asm->updated_at->timestamp : ($asm->created_at ? $asm->created_at->timestamp : 0))
                    ->first();

                // Tentukan level prioritas & ranking
                if ($nearestUpcomingTask) {
                    $priorityLevel = 1;
                    $sortKey = $nearestUpcomingTask->due_at ? $nearestUpcomingTask->due_at->timestamp : 1900000000;
                } elseif ($latestMaterial) {
                    $priorityLevel = 2;
                    $materialTime = $latestMaterial->updated_at ? $latestMaterial->updated_at->timestamp : ($latestMaterial->created_at ? $latestMaterial->created_at->timestamp : 0);
                    $sortKey = -1 * $materialTime;
                } else {
                    $priorityLevel = 3;
                    $sortKey = -1 * $sec->id;
                }

                return [
                    'id' => $sec->id,
                    'code' => $sec->display_code,
                    'sks' => $sec->mataKuliah->sks.' SKS',
                    'title' => $sec->mataKuliah->name,
                    'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                    'dosen_ketua' => $sec->dosen?->name ?? 'Dosen Pengampu',
                    'dosen_wakil' => $sec->relationLoaded('dosenAnggota') && $sec->dosenAnggota->isNotEmpty() ? $sec->dosenAnggota->pluck('name')->join(', ') : $sec->dosenPendamping?->name,
                    'dosen_anggota' => $sec->relationLoaded('dosenAnggota') && $sec->dosenAnggota->isNotEmpty() ? $sec->dosenAnggota->pluck('name')->join(', ') : $sec->dosenPendamping?->name,
                    'cover' => null,
                    'type' => 'Kelas Aktif',
                    'is_archived' => false,
                    'work' => 'Perkuliahan semester '.($sec->semester->name ?? 'aktif'),
                    'semester_id' => $sec->semester_id,
                    'semester_name' => $sec->semester?->name,
                    'semester_display' => $sec->semester?->display_name ?? $sec->semester?->name,
                    'semester_paket' => $sec->mataKuliah?->semester_paket,
                    'due' => '',
                    'students_count' => $sec->students_count,
                    'assessments_count' => $sec->assessments_count,
                    'enrollment_code' => $sec->enrollment_code,
                    'enrollment_url' => $sec->enrollment_url,
                    'qr_url' => route('kelas.qr', $sec->id),
                    'svg_index' => ($sec->id % 4) + 1,
                    '_priority' => $priorityLevel,
                    '_sort_key' => $sortKey,
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['_priority'] !== $b['_priority']) {
                    return $a['_priority'] <=> $b['_priority'];
                }
                return $a['_sort_key'] <=> $b['_sort_key'];
            })
            ->values();
        } else {
            $courses = collect();
        }

        return view('mahasiswa.dashboard', [
            'student' => $user,
            'enrolledSections' => $enrolledSections,
            'courses' => $courses,
            'activeItems' => $activeItems,
            'recentDiscussions' => $user
                ? collect($this->notifications->forUser($user))
                    ->where('category', 'diskusi')
                    ->where('is_read', false)
                    ->take(3)
                : collect(),
        ]);
    }
}
