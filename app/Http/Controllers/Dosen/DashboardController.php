<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Support\LearningPreview;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dosen = $request->user();

        if (! $dosen) {
            return view('dosen.dashboard', [
                'courses' => collect(LearningPreview::courses()),
            ]);
        }

        $courses = ClassSection::query()
            ->where(function ($query) use ($dosen) {
                $query->where('dosen_id', $dosen->id)
                    ->orWhere('dosen_pendamping_id', $dosen->id);
            })
            ->with(['mataKuliah', 'semester', 'dosen', 'dosenPendamping'])
            ->withCount(['students', 'assessments'])
            ->orderByDesc('semester_id')
            ->orderBy('mata_kuliah_id')
            ->orderBy('section_code')
            ->get()
            ->map(function (ClassSection $section) {
                if (empty($section->enrollment_code)) {
                    $section->update(['enrollment_code' => ClassSection::generateUniqueEnrollmentCode()]);
                }

                return [
                    'id' => $section->id,
                    'code' => $section->display_code,
                    'sks' => $section->mataKuliah->sks.' SKS',
                    'title' => $section->mataKuliah->name,
                    'lecturer' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_ketua' => $section->dosen?->name ?? 'Belum ditetapkan',
                    'dosen_wakil' => $section->dosenPendamping?->name,
                    'cover' => null,
                    'type' => 'Kelas Aktif',
                    'work' => 'Perkuliahan semester '.($section->semester?->name ?? 'aktif'),
                    'due' => '',
                    'students_count' => $section->students_count,
                    'assessments_count' => $section->assessments_count,
                    'enrollment_code' => $section->enrollment_code,
                    'enrollment_url' => $section->enrollment_url,
                    'qr_url' => route('kelas.qr', $section->id),
                    'svg_index' => ($section->id % 4) + 1,
                ];
            });

        return view('dosen.dashboard', compact('courses'));
    }
}
