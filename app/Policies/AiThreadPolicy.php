<?php

namespace App\Policies;

use App\Models\AiThread;
use App\Models\Assessment;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\User;

class AiThreadPolicy
{
    /**
     * Determine whether the user can view the thread.
     */
    public function view(User $user, AiThread $thread): bool
    {
        // Lecturer managing the section can view
        if ($user->hasRole(Role::DOSEN) && $user->can('manage', $thread->classSection)) {
            return true;
        }

        // Student must be the owner of the thread
        if ((int) $thread->user_id !== (int) $user->id) {
            return false;
        }

        // Kicked or dropped students are rejected
        return $this->isStudentActiveOrArchived($user, $thread->classSection);
    }

    /**
     * Determine whether the user can send a message in the assessment thread.
     */
    public function createMessage(User $user, Assessment $assessment): bool
    {
        $section = $assessment->classSection;
        if (! $section) {
            return false;
        }

        // Class archived = read-only (tidak bisa kirim pesan baru)
        if ($section->isArchived()) {
            return false;
        }

        // Lecturer managing the section can interact
        if ($user->hasRole(Role::DOSEN) && $user->can('manage', $section)) {
            return true;
        }

        // Student must be actively enrolled (status = enrolled, not kicked/dropped)
        return $this->isStudentEnrolled($user, $section);
    }

    /**
     * Determine whether the user can delete the thread.
     */
    public function delete(User $user, AiThread $thread): bool
    {
        return (int) $thread->user_id === (int) $user->id;
    }

    /**
     * Check if student is actively enrolled.
     */
    public function isStudentEnrolled(User $user, ClassSection $section): bool
    {
        return $section->students()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if student is enrolled or was enrolled in archived class (not kicked/dropped).
     */
    public function isStudentActiveOrArchived(User $user, ClassSection $section): bool
    {
        if ($this->isStudentEnrolled($user, $section)) {
            return true;
        }

        if ($section->isArchived()) {
            return $section->enrollmentRecords()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'enrolled')
                ->exists();
        }

        return false;
    }
}
