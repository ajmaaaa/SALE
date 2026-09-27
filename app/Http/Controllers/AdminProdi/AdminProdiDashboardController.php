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
        $dosenRoleId = Role::where('name', Role::DOSEN)->value('id');
        $mahasiswaRoleId = Role::where('name', Role::MAHASISWA)->value('id');

        $activeSemester = Semester::where('is_active', true)->first() ?? Semester::latest()->first();

        $stats = [
            'total_prodi' => Prodi::when($prodiId, fn ($q) => $q->whereKey($prodiId))->count(),
            'total_dosen' => User::where('role_id', $dosenRoleId)->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))->count(),
            'total_mahasiswa' => User::where('role_id', $mahasiswaRoleId)->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))->count(),
            'total_matakuliah' => MataKuliah::when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))->count(),
            'total_kelas' => ClassSection::when($prodiId, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId)))->count(),
            'total_cpl' => Cpl::when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))->count(),
            'total_cpmk' => Cpmk::when($prodiId, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId)))->count(),
        ];

        $prodis = Prodi::when($prodiId, fn ($q) => $q->whereKey($prodiId))
            ->withCount(['mataKuliahs', 'cpls'])
            ->get();
        $recentClasses = ClassSection::with(['mataKuliah.prodi', 'dosen', 'dosenPendamping', 'semester'])
            ->when($prodiId, fn ($q) => $q->whereHas('mataKuliah', fn ($mk) => $mk->where('prodi_id', $prodiId)))
            ->withCount('students')
            ->latest()
            ->take(5)
            ->get();

        return view('admin-prodi.dashboard', compact('stats', 'prodis', 'recentClasses', 'activeSemester'));
    }
}
