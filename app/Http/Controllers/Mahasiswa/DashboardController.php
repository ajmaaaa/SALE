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

        return view('mahasiswa.dashboard', [
            'student' => $user,
            'enrolledSections' => $enrolledSections,
        ]);
    }
}
