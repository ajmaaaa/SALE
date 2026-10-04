<?php

use App\Models\ClassSection;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('room.{roomId}', function (User $user, int $roomId) {
    if (! $user) {
        return false;
    }

    $room = Room::find($roomId);
    if (! $room) {
        return false;
    }

    $section = $room->classSection
        ?? ($room->course_id ? ClassSection::find($room->course_id) : null);

    if ($section) {
        if ($user->hasRole(Role::DOSEN)) {
            return $user->can('manage', $section);
        }

        if ($user->hasRole(Role::MAHASISWA)) {
            return $section->students()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    // Compatibility for legacy rooms that are not linked to a class. Never
    // grant access from a role alone; an explicit room membership is required.
    return $room->members()->where('users.id', $user->id)->exists();
});
