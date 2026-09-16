<?php

namespace App\Http\Controllers\AdminProdi;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class AdminProdiDashboardController extends Controller
{
    public function index(): View
    {
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mahasiswaRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $activeSemester = Semester::where('is_active', true)->first() ?? Semester::latest()->first();

        $stats = [
            'total_prodi' => Prodi::count(),
            'total_dosen' => User::where('role_id', $dosenRoleId)->count(),
            'total_mahasiswa' => User::where('role_id', $mahasiswaRoleId)->count(),
            'total_matakuliah' => MataKuliah::count(),
            'total_kelas' => ClassSection::count(),
            'total_cpl' => Cpl::count(),
            'total_cpmk' => Cpmk::count(),
        ];

        $prodis = Prodi::withCount(['mataKuliahs', 'cpls'])->get();
        $recentClasses = ClassSection::with(['mataKuliah.prodi', 'dosen', 'dosenPendamping', 'semester'])
            ->withCount('students')
            ->latest()
            ->take(5)
            ->get();

        return view('admin-prodi.dashboard', compact('stats', 'prodis', 'recentClasses', 'activeSemester'));
    }
}
