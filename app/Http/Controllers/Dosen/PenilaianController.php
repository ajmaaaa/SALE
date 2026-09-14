<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman 2 — Dashboard Penilaian Kelas.
     */
    public function dashboard(ClassSection $section)
    {
        $this->authorizeOwnership($section);

        return redirect()->route('dosen.penilaian.rekap', $section);
    }

    /**
     * Tab: Rekap Keseluruhan.
     */
    public function rekap(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($section, $cpls) {
            $cplScores = $this->obe->cplScoresFor($cpls, $student->id);
            $final = $this->obe->finalScore($section, $student->id);

            return [
                'student' => $student,
                'cpl_scores' => $cplScores,
                'final_score' => $final['score'],
                'coverage' => $final['coverage'],
                'grade' => $this->gradeLetter($final['score']),
            ];
        });

        return view('dosen.rekap', [
            'section' => $this->withHeaderCounts($section, $cpls),
            'cpls' => $cpls,
            'rows' => $rows,
        ]);
    }

    /**
     * Tab: Matriks Penilaian.
     */
    public function matriks(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();

        return view('dosen.matriks', [
            'section' => $this->withHeaderCounts($section, null, $cpmks),
            'cpmks' => $cpmks,
            'assessments' => $assessments,
        ]);
    }

    /**
     * Tab: Daftar Asesmen.
     */
    public function asesmen(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $assessments = $section->assessments()->with('cpmks')->orderBy('code')->get();

        return view('dosen.penilaian.asesmen', [
            'section' => $this->withHeaderCounts($section),
            'assessments' => $assessments,
        ]);
    }

    /**
     * Tab: Rekap CPMK.
     */
    public function cpmk(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpmks = $this->cpmksFor($section);
        $students = $section->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($cpmks) {
            $scores = $this->obe->cpmkScoresFor($cpmks, $student->id);

            return [
                'student' => $student,
                'scores' => $scores,
            ];
        });

        return view('dosen.cpmk', [
            'section' => $this->withHeaderCounts($section, null, $cpmks),
            'cpmks' => $cpmks,
            'rows' => $rows,
        ]);
    }

    /**
     * Tab: Rekap CPL.
     */
    public function cpl(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $students = $section->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($cpls) {
            $scores = $this->obe->cplScoresFor($cpls, $student->id);

            return [
                'student' => $student,
                'scores' => $scores,
            ];
        });

        return view('dosen.cpl', [
            'section' => $this->withHeaderCounts($section, $cpls),
            'cpls' => $cpls,
            'rows' => $rows,
        ]);
    }

    /**
     * Tab: Pengaturan Penilaian.
     */
    public function pengaturan(ClassSection $section): View
    {
        $this->authorizeOwnership($section);

        $cpls = $this->cplsFor($section);
        $cpmks = $this->cpmksFor($section);
        $totalFinalWeight = $section->assessments()->sum('final_weight');

        return view('dosen.pengaturan', [
            'section' => $this->withHeaderCounts($section, $cpls, $cpmks),
            'cpls' => $cpls,
            'cpmks' => $cpmks,
            'totalFinalWeight' => (float) $totalFinalWeight,
        ]);
    }

    private function cpmksFor(ClassSection $section)
    {
        return Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->orderBy('code')->get();
    }

    private function cplsFor(ClassSection $section)
    {
        $cpmkIds = $this->cpmksFor($section)->pluck('id');

        return Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmkIds))
            ->with(['cpmks' => fn ($q) => $q->whereIn('cpmks.id', $cpmkIds)])
            ->orderBy('code')
            ->get();
    }

    private function withHeaderCounts(ClassSection $section, $cpls = null, $cpmks = null): ClassSection
    {
        $section->loadCount('students')->loadCount('assessments')->load(['mataKuliah', 'semester', 'dosen']);
        $section->cpmk_used_count = ($cpmks ?? $this->cpmksFor($section))->count();
        $section->cpl_used_count = ($cpls ?? $this->cplsFor($section))->count();

        return $section;
    }

    private function gradeLetter(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 85 => 'A',
            $score >= 80 => 'AB',
            $score >= 75 => 'B',
            $score >= 70 => 'BC',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }

    /**
     * Data-ownership check fleksibel (Auth Laravel + Switch Account Session).
     */
    private function authorizeOwnership(ClassSection $section): void
    {
        $currentUserId = Auth::guard('web')->id();

        if (! $currentUserId && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
            $currentUserId = $user?->hasRole(\App\Models\Role::DOSEN) ? $user->id : null;
        }

        abort_unless($currentUserId && $section->dosen_id === $currentUserId, 403, 'Anda tidak memiliki akses ke kelas ini.');
    }
}