<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentAssessmentScore;
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

        abort_unless($dosen?->hasRole(Role::DOSEN), 403, 'Akses ditolak. Halaman ini khusus Dosen.');

        $selectedSemesterId = $request->query('semester');
        $q = trim((string) $request->query('q', ''));

        $semesters = Semester::orderChronological()->get();

        $sections = ClassSection::query()
            ->where(function ($query) use ($dosen) {
                $query->where('dosen_id', $dosen->id)
                    ->orWhere('dosen_pendamping_id', $dosen->id)
                    ->orWhereHas('dosenAnggota', fn ($sub) => $sub->where('users.id', $dosen->id));
            })
            ->with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping', 'dosenAnggota'])
            ->withCount('students')
            ->withCount(['assessments' => fn ($query) => $query->whereNotIn('type', ['materi', 'pengumuman', 'lainnya'])])
            ->when($selectedSemesterId, function ($query) use ($selectedSemesterId) {
                $query->where('semester_id', $selectedSemesterId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $lower = mb_strtolower($q);
                $query->where(function ($sub) use ($lower) {
                    $sub->whereHas('mataKuliah', function ($mk) use ($lower) {
                        $mk->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                            ->orWhereRaw('LOWER(code) LIKE ?', ["%{$lower}%"]);
                    })
                    ->orWhereRaw('LOWER(section_code) LIKE ?', ["%{$lower}%"]);
                });
            })
            ->orderByDesc('id')
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
                ->whereIn('assessment_id', $section->gradableAssessments()->pluck('id'))
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
            'semesters' => $semesters,
            'selectedSemesterId' => $selectedSemesterId,
        ]);
    }
}
