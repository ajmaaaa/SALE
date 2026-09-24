<?php

namespace App\Support;

use App\Models\ClassSection;
use App\Models\Role;
use App\Models\StudentAssessmentScore;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DosenNavigation
{
    public static function pendingGradingCount(): int
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasTable('class_sections')
            || ! Schema::hasTable('assessments')
            || ! Schema::hasTable('student_assessment_scores')) {
            return 0;
        }

        $dosen = auth()->user();

        if (! $dosen && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $dosen = User::query()
                ->where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();
        }

        if (! $dosen?->hasRole(Role::DOSEN)) {
            return 0;
        }

        $sections = ClassSection::query()
            ->where(fn ($query) => $query
                ->where('dosen_id', $dosen->id)
                ->orWhere('dosen_pendamping_id', $dosen->id))
            ->withCount(['students', 'assessments'])
            ->get(['id']);

        if ($sections->isEmpty()) {
            return 0;
        }

        $gradedBySection = StudentAssessmentScore::query()
            ->join('assessments', 'assessments.id', '=', 'student_assessment_scores.assessment_id')
            ->whereIn('assessments.class_section_id', $sections->pluck('id'))
            ->whereNotNull('student_assessment_scores.score')
            ->selectRaw('assessments.class_section_id, COUNT(*) as graded_count')
            ->groupBy('assessments.class_section_id')
            ->pluck('graded_count', 'assessments.class_section_id');

        return $sections->filter(function (ClassSection $section) use ($gradedBySection) {
            $expected = $section->students_count * $section->assessments_count;
            $graded = (int) ($gradedBySection[$section->id] ?? 0);

            return $expected > 0 && $graded < $expected;
        })->count();
    }
}
