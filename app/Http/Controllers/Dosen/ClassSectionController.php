<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassSectionController extends Controller
{
    /**
     * Halaman 1 — Daftar Kelas Dosen.
     *
     * Only shows class sections owned by the authenticated Dosen
     * (data ownership enforced at the query level, not just hidden in
     * the UI — a Dosen cannot see another lecturer's classes by guessing
     * a URL either, since every downstream route re-checks ownership).
     */
    public function index(Request $request): View
    {
        return $this->renderView($request, 'penilaian');
    }

    /**
     * Halaman Rekap Nilai OBE — Daftar Kelas Saya.
     */
    public function rekapIndex(Request $request): View
    {
        return $this->renderView($request, 'rekap');
    }

    private function renderView(Request $request, string $mode = 'penilaian'): View
    {
        $dosen = Auth::guard('web')->user();

        if (! $dosen && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $dosen = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        abort_unless($dosen?->hasRole(Role::DOSEN), 403, 'Akses ditolak. Halaman ini khusus Dosen.');

        $sections = ClassSection::query()
            ->where(function ($q) use ($dosen) {
                $q->where('dosen_id', $dosen->id)
                    ->orWhere('dosen_pendamping_id', $dosen->id);
            })
            ->with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])
            ->withCount('students')
            ->withCount('assessments')
            ->orderByDesc('semester_id')
            ->orderBy('mata_kuliah_id')
            ->orderBy('section_code')
            ->get();

        // Grading progress per section: how many (assessment × enrolled
        // student) score slots have actually been graded.
        $sections->each(function (ClassSection $section) {
            if (! $section->enrollment_code) {
                $section->update(['enrollment_code' => ClassSection::generateUniqueEnrollmentCode()]);
            }

            $expectedSlots = $section->assessments_count * $section->students_count;

            if ($expectedSlots === 0) {
                $section->grading_progress = null;

                return;
            }

            $gradedSlots = StudentAssessmentScore::query()
                ->whereIn('assessment_id', $section->assessments()->pluck('id'))
                ->whereNotNull('score')
                ->count();

            $section->grading_progress = round(min($gradedSlots, $expectedSlots) / $expectedSlots * 100);
        });

        $viewName = view()->exists('dosen.class-section.index')
            ? 'dosen.class-section.index'
            : 'dosen.penilaian-kelas.index';

        return view($viewName, [
            'sections' => $sections,
            'mode' => $mode,
        ]);
    }
}
