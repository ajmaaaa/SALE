<?php

namespace App\Http\Controllers\AdminProdi;

use App\Models\ClassSection;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class AdminProdiDashboardController extends AdminProdiController
{
    public function index(): View
    {
        $prodiId = $this->adminProdiId();
        $activeSemester = Semester::where('is_active', true)->first() ?? Semester::latest()->first();

        $stats = [
            'total_dosen' => User::withRoleName(Role::DOSEN)->where(fn ($scope) => $scope->where('prodi_id', $prodiId)->orWhere('managing_prodi_id', $prodiId))->count(),
            'total_mahasiswa' => User::withRoleName(Role::MAHASISWA)->where('prodi_id', $prodiId)->count(),
            'total_matakuliah' => MataKuliah::where('prodi_id', $prodiId)->count(),
            'total_kelas' => ClassSection::whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId))->count(),
            'total_cpl' => Cpl::where('prodi_id', $prodiId)->count(),
            'total_cpmk' => Cpmk::whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId))->count(),
        ];

        $recentClasses = ClassSection::with(['mataKuliah.prodi', 'dosen', 'dosenPendamping', 'semester'])
            ->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId))
            ->withCount('students')
            ->latest()
            ->take(5)
            ->get();

        $activeProdi = Prodi::findOrFail($prodiId);

        return view('admin-prodi.dashboard', compact('stats', 'recentClasses', 'activeSemester', 'activeProdi'));
    }
}
