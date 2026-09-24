<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Role;
use App\Models\User;
use App\Services\ObeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObeProgressController extends Controller
{
    public function __construct(private ObeCalculationService $obe) {}

    /**
     * Halaman capaian CPMK dan CPL mahasiswa.
     */
    public function index(Request $request): View
    {
        $student = $this->resolveStudent();
        abort_unless($student, 403, 'Akses khusus Mahasiswa.');

        // Hanya kelas yang diikuti oleh mahasiswa ini
        $enrolledSections = $student->classSectionsEnrolled()
            ->with(['mataKuliah', 'semester', 'dosen'])
            ->get();

        $courseProgress = $enrolledSections->map(function ($section) use ($student) {
            $cpmks = Cpmk::where('mata_kuliah_id', $section->mata_kuliah_id)->get();
            $cpmkScores = $this->obe->cpmkScoresFor($cpmks, $student->id);

            $cpmkDetails = $cpmks->map(function ($cpmk) use ($cpmkScores) {
                $score = $cpmkScores[$cpmk->id];

                return [
                    'cpmk' => $cpmk,
                    'score' => $score,
                    'is_achieved' => $this->obe->cpmkAchieved($cpmk, $score),
                ];
            });

            $cpls = Cpl::whereHas('cpmks', fn ($q) => $q->whereIn('cpmks.id', $cpmks->pluck('id')))->get();
            $cplScores = $this->obe->cplScoresFor($cpls, $student->id);

            $final = $this->obe->finalScore($section, $student->id);

            return [
                'section' => $section,
                'cpmks' => $cpmkDetails,
                'cpls' => $cpls->map(fn ($cpl) => [
                    'cpl' => $cpl,
                    'score' => $cplScores[$cpl->id],
                ]),
                'final_score' => $final['score'],
                'coverage' => $final['coverage'],
            ];
        });

        return view('mahasiswa.obe-progress', [
            'student' => $student,
            'courseProgress' => $courseProgress,
        ]);
    }

    private function resolveStudent(): ?User
    {
        $user = Auth::guard('web')->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        if ($user && $user->role && $user->role->name === Role::MAHASISWA) {
            return $user;
        }

        if (is_array(session('auth_user')) && (session('auth_user')['role'] ?? '') === Role::MAHASISWA) {
            return $user;
        }

        return null;
    }
}
