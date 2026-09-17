<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $courses = collect(\App\Support\LearningPreview::courses());

        if ($user) {
            foreach ($enrolledSections as $sec) {
                $alreadyIncluded = $courses->contains(function ($card) use ($sec) {
                    return ($card['title'] ?? '') === $sec->mataKuliah->name || ($card['code'] ?? '') === $sec->display_code;
                });

                if (! $alreadyIncluded) {
                    $courses->prepend([
                        'id' => $sec->id,
                        'code' => $sec->display_code,
                        'sks' => $sec->mataKuliah->sks . ' SKS',
                        'title' => $sec->mataKuliah->name,
                        'lecturer' => $sec->dosen?->name ?? 'Dosen Pengampu',
                        'dosen_ketua' => $sec->dosen?->name ?? 'Dosen Pengampu',
                        'dosen_wakil' => $sec->dosenPendamping?->name ?? null,
                        'cover' => null,
                        'type' => 'Kelas Aktif',
                        'work' => 'Perkuliahan semester ' . ($sec->semester->name ?? 'aktif'),
                        'due' => '',
                        'students_count' => $sec->students_count,
                        'assessments_count' => $sec->assessments_count,
                        'enrollment_code' => $sec->enrollment_code,
                        'enrollment_url' => $sec->enrollment_url,
                        'qr_url' => route('kelas.qr', $sec->id),
                        'svg_index' => ($sec->id % 4) + 1,
                    ]);
                }
            }
        }

        return view('mahasiswa.dashboard', [
            'student' => $user,
            'enrolledSections' => $enrolledSections,
            'courses' => $courses,
        ]);
    }
}
