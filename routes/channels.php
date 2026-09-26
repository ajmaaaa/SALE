<?php

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

    return $room->members()->where('user_id', $user->id)->exists()
        || $user->hasRole('dosen')
        || $user->hasRole('admin');
});
