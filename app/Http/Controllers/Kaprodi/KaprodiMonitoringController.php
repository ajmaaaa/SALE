<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KaprodiMonitoringController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Dashboard Monitoring Capaian CPMK Tingkat Prodi (Step 18 & Step 20).
     */
    public function cpmk(Request $request): View
    {
        $this->authorizeKaprodi();

        $sections = ClassSection::with(['mataKuliah', 'semester', 'dosen'])
            ->whereHas('semester', fn ($q) => $q->where('is_active', true))
            ->get();

        $selectedSectionId = $request->integer('section_id', $sections->first()?->id ?? 0);
        $activeSection = $sections->firstWhere('id', $selectedSectionId);

        $cpmkStats = collect();
        if ($activeSection) {
            $cpmks = Cpmk::where('mata_kuliah_id', $activeSection->mata_kuliah_id)->get();
            $students = $activeSection->students;

            foreach ($cpmks as $cpmk) {
                $scores = $students->map(fn ($s) => $this->obe->cpmkScore($cpmk, $s->id))->filter(fn ($s) => $s !== null);
                $gradedCount = $scores->count();
                $achievedCount = $scores->filter(fn ($s) => $s >= (float)$cpmk->threshold)->count();
                $avgScore = $gradedCount > 0 ? round($scores->average(), 1) : null;
                $percentAchieved = $gradedCount > 0 ? round(($achievedCount / $gradedCount) * 100, 1) : 0;

                $cpmkStats->push([
                    'cpmk' => $cpmk,
                    'graded_count' => $gradedCount,
                    'total_students' => $students->count(),
                    'achieved_count' => $achievedCount,
                    'average_score' => $avgScore,
                    'percent_achieved' => $percentAchieved,
                ]);
            }
        }

        return view('kaprodi.monitoring-cpmk', [
            'sections' => $sections,
            'activeSection' => $activeSection,
            'cpmkStats' => $cpmkStats,
        ]);
    }

    /**
     * Dashboard Monitoring Capaian CPL Tingkat Prodi (Step 19 & Step 20).
     */
    public function cpl(Request $request): View
    {
        $this->authorizeKaprodi();

        $cpls = Cpl::with('cpmks')->orderBy('code')->get();
        $sections = ClassSection::with(['mataKuliah', 'dosen'])->get();

        $cplStats = $cpls->map(function ($cpl) use ($sections) {
            $allScores = collect();

            foreach ($sections as $section) {
                foreach ($section->students as $student) {
                    $score = $this->obe->cplScore($cpl, $student->id);
                    if ($score !== null) {
                        $allScores->push($score);
                    }
                }
            }

            $count = $allScores->count();
            $avg = $count > 0 ? round($allScores->average(), 1) : null;
            $achieved = $allScores->filter(fn ($s) => $s >= 65)->count();
            $percent = $count > 0 ? round(($achieved / $count) * 100, 1) : 0;

            return [
                'cpl' => $cpl,
                'count' => $count,
                'average' => $avg,
                'achieved_count' => $achieved,
                'percent_achieved' => $percent,
            ];
        });

        return view('kaprodi.monitoring-cpl', [
            'cplStats' => $cplStats,
            'sectionsCount' => $sections->count(),
        ]);
    }

    private function authorizeKaprodi(): void
    {
        $user = Auth::guard('web')->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        $isKaprodi = ($user && $user->role && $user->role->name === Role::KAPRODI)
            || (is_array(session('auth_user')) && (session('auth_user')['role'] ?? '') === Role::KAPRODI);

        abort_unless($isKaprodi, 403, 'Akses khusus Kaprodi (Monitoring & Evaluasi OBE).');
    }
}
