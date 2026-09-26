<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\Role;
use App\Models\User;

class MessagePolicy
{
    /**
     * Determine whether the user can delete the message.
     * - Dosen dapat menghapus pesan siapapun di kelasnya (moderasi).
     * - Mahasiswa hanya dapat menghapus pesannya sendiri.
     */
    public function delete(User $user, Message $message): bool
    {
        // Cek role user di room terkait
        $roomRole = $message->room?->members()
            ->where('user_id', $user->id)
            ->value('role');

        $isDosen = ($roomRole === 'dosen')
            || $user->hasRole(Role::DOSEN)
            || (session('auth_role') === 'dosen');

        return $isDosen || ($message->user_id === $user->id);
    }

    /**
     * Determine whether the user can pin / unpin the message.
     * - Hanya Dosen kelas yang berhak melakukan Pin / Unpin pesan penting.
     */
    public function pin(User $user, Message $message): bool
    {
        $roomRole = $message->room?->members()
            ->where('user_id', $user->id)
            ->value('role');

        return ($roomRole === 'dosen')
            || $user->hasRole(Role::DOSEN)
            || (session('auth_role') === 'dosen');
    }
}
