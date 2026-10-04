<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\StudentAssessmentScore;
use App\Services\DatabaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DatabaseNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $dosen = $request->user();

        if (! $dosen) {
            $courses = collect();
            $pendingCount = 0;
            $recentDiscussions = collect();

            return view('dosen.dashboard', compact('courses', 'pendingCount', 'recentDiscussions'));
        }

        $sections = ClassSection::query()
            ->where(function ($query) use ($dosen) {
                $query->where('dosen_id', $dosen->id)
                    ->orWhere('dosen_pendamping_id', $dosen->id)
                    ->orWhereHas('dosenAnggota', fn ($sub) => $sub->where('users.id', $dosen->id));
            })
            ->with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota', 'assessments'])
            ->withCount(['students', 'assessments'])
            ->orderByDesc('semester_id')
            ->orderBy('mata_kuliah_id')
            ->orderBy('section_code')
            ->get();

        $activeSections = $sections->filter(fn (ClassSection $section) => ! $section->isArchived());

        $courses = $activeSections
            ->map(function (ClassSection $section) {
                if (empty($section->enrollment_code)) {
                    $section->update(['enrollment_code' => ClassSection::generateUniqueEnrollmentCode()]);
                }

                $secAssessments = $section->relationLoaded('assessments') ? $section->assessments : $section->assessments()->get();

                // 1. Tugas dalam waktu dekat (hanya yang belum terlewat / upcoming)
                $secTasks = $secAssessments->whereNotIn('type', ['materi', 'pengumuman'])->where('status', 'published');
                $upcomingTasks = $secTasks->filter(fn ($asm) => ! empty($asm->due_at) && $asm->due_at->isFuture())->sortBy('due_at');
                $nearestUpcomingTask = $upcomingTasks->first();

                // 2. Update materi terbaru
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
                    $sortKey = -1 * $section->id;
                }

                $anggotaNames = $section->relationLoaded('dosenAnggota') && $section->dosenAnggota->isNotEmpty()
                    ? $section->dosenAnggota->pluck('name')->join(', ')
                    : $section->dosenPendamping?->name;

                return [
                    'id' => $section->id,
                    'code' => $section->display_code,
                    'sks' => $section->mataKuliah->sks.' SKS',
                    'title' => $section->mataKuliah->name,
                    'lecturer' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_ketua' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_wakil' => $anggotaNames,
                    'dosen_anggota' => $anggotaNames,
                    'cover' => null,
                    'type' => 'Kelas Aktif',
                    'is_archived' => false,
                    'work' => 'Perkuliahan semester '.($section->semester?->name ?? 'aktif'),
                    'due' => '',
                    'students_count' => $section->students_count,
                    'assessments_count' => $section->assessments_count,
                    'enrollment_code' => $section->enrollment_code,
                    'enrollment_url' => $section->enrollment_url,
                    'qr_url' => route('kelas.qr', $section->id),
                    'svg_index' => ($section->id % 4) + 1,
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

        $pendingCount = $courses->filter(function (array $course): bool {
            if (! empty($course['is_archived'])) {
                return false;
            }
            $section = ClassSection::withCount('students')->find($course['id']);
            $assessmentIds = $section?->gradableAssessments()->pluck('id') ?? collect();
            $expected = $assessmentIds->count() * ($section?->students_count ?? 0);
            if ($expected === 0) {
                return false;
            }

            $graded = StudentAssessmentScore::whereIn('assessment_id', $assessmentIds)
                ->whereNotNull('score')
                ->count();

            return $graded < $expected;
        })->count();

        $recentDiscussions = collect($this->notifications->forUser($dosen, 'dosen'))
            ->where('category', 'diskusi')
            ->where('is_read', false)
            ->take(3);

        return view('dosen.dashboard', compact('courses', 'pendingCount', 'recentDiscussions'));
    }
}
