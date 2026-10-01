<?php

namespace App\Policies;

use App\Models\ClassSection;
use App\Models\Role;
use App\Models\User;

class ClassSectionPolicy
{
    /**
     * Dosen utama dan dosen anggota (pendamping / team-teaching) memiliki hak
     * pengelolaan akademik yang sama pada kelas tempat mereka ditugaskan.
     */
    public function manage(User $user, ClassSection $section): bool
    {
        if (! $user->hasRole(Role::DOSEN)) {
            return false;
        }

        $leadAndAssistant = array_map('intval', array_filter([(int) $section->dosen_id, (int) $section->dosen_pendamping_id]));
        if (in_array((int) $user->id, $leadAndAssistant, true)) {
            return true;
        }

        return $section->relationLoaded('dosenAnggota')
            ? $section->dosenAnggota->contains('id', $user->id)
            : $section->dosenAnggota()->where('users.id', $user->id)->exists();
    }
}
