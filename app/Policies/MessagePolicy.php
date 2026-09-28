<?php

namespace App\Policies;

use App\Models\ClassSection;
use App\Models\Message;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class MessagePolicy
{
    /**
     * Periksa apakah dosen adalah pengampu kelas dari room pesan ini.
     * Mencegah dosen kelas lain memoderasi pesan kelas yang bukan miliknya.
     */
    private function isDosenOfRoom(User $user, Message $message): bool
    {
        if (! $user->hasRole(Role::DOSEN)) {
            return false;
        }

        $room = $message->room;
        if (! $room || ! $room->course_id) {
            // Fallback: izinkan jika room tidak terhubung ke section (demo mode)
            return true;
        }

        if (! Schema::hasTable('class_sections')) {
            return true;
        }

        $section = ClassSection::find($room->course_id);
        if (! $section) {
            return $room->members()->where('user_id', $user->id)->where('role', 'dosen')->exists();
        }

        return $user->can('manage', $section);
    }

    /**
     * Determine whether the user can delete the message.
     * - Dosen pengampu dapat menghapus pesan siapapun di kelasnya (moderasi).
     * - Mahasiswa hanya dapat menghapus pesannya sendiri.
     */
    public function delete(User $user, Message $message): bool
    {
        if ($message->user_id === $user->id) {
            return true;
        }

        return $this->isDosenOfRoom($user, $message);
    }

    /**
     * Determine whether the user can pin / unpin the message.
     * - Hanya Dosen pengampu kelas yang berhak melakukan Pin / Unpin pesan.
     */
    public function pin(User $user, Message $message): bool
    {
        return $this->isDosenOfRoom($user, $message);
    }
}
