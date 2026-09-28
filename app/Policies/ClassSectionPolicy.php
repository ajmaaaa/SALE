<?php

namespace App\Policies;

use App\Models\ClassSection;
use App\Models\Role;
use App\Models\User;

class ClassSectionPolicy
{
    /**
     * Dosen utama dan dosen pendamping memiliki hak pengelolaan akademik
     * yang sama pada kelas tempat mereka ditugaskan.
     */
    public function manage(User $user, ClassSection $section): bool
    {
        return $user->hasRole(Role::DOSEN)
            && in_array((int) $user->id, array_map('intval', array_filter([(int) $section->dosen_id, (int) $section->dosen_pendamping_id])), true);
    }
}
