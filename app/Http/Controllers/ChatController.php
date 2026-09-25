<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessagePinned;
use App\Events\MessageSent;
use App\Models\ChatNotification;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Support\LearningPreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    /**
     * Get or resolve current authenticated user with fallback to session user.
     */
    protected function currentUser(): ?User
    {
        $user = auth()->user();

        if (! $user && is_array(session('auth_user'))) {
            $sessionUser = session('auth_user');
            $user = User::with('role')
                ->where('email', $sessionUser['email'] ?? '')
                ->orWhere('nim_nidn', $sessionUser['number'] ?? '')
                ->first();

            if (! $user && ! empty($sessionUser['role'])) {
                $user = User::with('role')
                    ->whereHas('role', fn ($q) => $q->where('name', $sessionUser['role']))
                    ->first();
            }

            if ($user) {
                auth()->login($user);
            }
        }

        if (! $user) {
            $user = User::with('role')->first();
        }

        return $user;
    }

    /**
     * Ensure the room exists and the user is a registered member.
     */
    protected function ensureRoomAndMembership(int $courseId, ?User $user = null): Room
    {
        $courses = LearningPreview::courses();
        $courseTitle = $courses[$courseId]['title'] ?? "Course {$courseId}";
        $room = Room::forCourse($courseId, $courseTitle);

        if ($user) {
            $member = RoomMember::where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $member) {
                $roleName = $user->role?->name ?? (session('auth_user.role') ?? 'mahasiswa');
                $chatRole = in_array($roleName, [Role::DOSEN, Role::KAPRODI], true) ? 'dosen' : 'mahasiswa';

                RoomMember::create([
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                    'role' => $chatRole,
                    'joined_at' => now(),
                ]);
            }
        }

        // Auto-seed default course members if room is new/empty
        if ($room->members()->count() === 0) {
            $allUsers = User::with('role')->get();
            foreach ($allUsers as $u) {
                $roleName = $u->role?->name ?? 'mahasiswa';
                $chatRole = in_array($roleName, [Role::DOSEN, Role::KAPRODI], true) ? 'dosen' : 'mahasiswa';
                RoomMember::firstOrCreate(
                    ['room_id' => $room->id, 'user_id' => $u->id],
                    ['role' => $chatRole, 'joined_at' => now()]
                );
            }
        }

        return $room;
    }

    /**
     * GET /chat/course/{course}/messages
     * Return messages, pinned messages, and room members.
     */
    public function getMessages(Request $request, int $course): JsonResponse
    {
        $user = $this->currentUser();
        $room = $this->ensureRoomAndMembership($course, $user);

        $limit = min((int) ($request->query('limit', 30)), 100);
        $beforeId = $request->query('before_id');

        $query = Message::where('room_id', $room->id)
            ->with(['user.role', 'replyTo.user', 'mentions.mentionedUser']);

        if ($beforeId && is_numeric($beforeId)) {
            $query->where('id', '<', (int) $beforeId);
        }

        // Ambil pesan terbaru lalu balik urutannya agar terurut kronologis (lama -> baru)
        $messagesRaw = $query->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $messagesRaw->count() > $limit;
        $messages = $messagesRaw->take($limit)->reverse()->values();

        $formattedMessages = $messages->map(fn (Message $m) => $m->toChatPayload($user));

        // Ambil pesan-pesan yang di-pin
        $pinnedMessages = Message::where('room_id', $room->id)
            ->where('is_pinned', true)
            ->with(['user.role', 'replyTo.user'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Message $m) => $m->toChatPayload($user));

        // Ambil daftar anggota room untuk dropdown @mention
        $members = $room->members()
            ->select('users.id', 'users.name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->pivot->role ?? 'mahasiswa',
                ];
            });

        // Update read counter in session
        $totalCount = Message::where('room_id', $room->id)->count();
        session(["learning.discussion_reads.$course" => $totalCount]);

        return response()->json([
            'success' => true,
            'room_id' => $room->id,
            'room_name' => $room->name,
            'messages' => $formattedMessages,
            'pinned_messages' => $pinnedMessages,
            'members' => $members,
            'has_more' => $hasMore,
            'oldest_id' => $messages->first()?->id ?? null,
            'latest_id' => $messages->last()?->id ?? null,
        ]);
    }

    /**
     * POST /chat/course/{course}/messages
     * Send new message, handle mentions & replies, broadcast event.
     */
    public function sendMessage(Request $request, int $course): JsonResponse
    {
        if (! $request->has('content') && $request->has('message')) {
            $request->merge(['content' => $request->input('message')]);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:3000',
            'reply_to_message_id' => 'nullable|integer|exists:messages,id',
            'mentioned_user_ids' => 'nullable|array',
            'mentioned_user_ids.*' => 'integer|exists:users,id',
        ]);

        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        $room = $this->ensureRoomAndMembership($course, $user);

        $message = Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'reply_to_message_id' => $validated['reply_to_message_id'] ?? null,
            'is_pinned' => false,
        ]);

        // Simpan Mention (@user)
        $mentionedIds = $validated['mentioned_user_ids'] ?? [];

        // Auto-detect mention dari teks jika user tidak sengaja melewatkan array mentioned_user_ids
        if (empty($mentionedIds) && str_contains($validated['content'], '@')) {
            $roomMembers = $room->members()->get();
            foreach ($roomMembers as $member) {
                if ($member->id !== $user->id && str_contains(mb_strtolower($validated['content']), '@'.mb_strtolower($member->name))) {
                    $mentionedIds[] = $member->id;
                }
            }
            $mentionedIds = array_unique($mentionedIds);
        }

        foreach ($mentionedIds as $targetUserId) {
            if ((int) $targetUserId !== (int) $user->id) {
                MessageMention::firstOrCreate([
                    'message_id' => $message->id,
                    'mentioned_user_id' => $targetUserId,
                ]);

                ChatNotification::create([
                    'user_id' => $targetUserId,
                    'type' => 'mention',
                    'message_id' => $message->id,
                    'is_read' => false,
                ]);
            }
        }

        // Jika ini balasan (reply) pesan orang lain, buat notifikasi reply
        if (! empty($validated['reply_to_message_id'])) {
            $repliedMessage = Message::find($validated['reply_to_message_id']);
            if ($repliedMessage && $repliedMessage->user_id !== $user->id) {
                ChatNotification::create([
                    'user_id' => $repliedMessage->user_id,
                    'type' => 'reply',
                    'message_id' => $message->id,
                    'is_read' => false,
                ]);
            }
        }

        $payload = $message->toChatPayload($user);

        // Broadcast WebSocket event via Reverb / Laravel Echo
        try {
            event(new MessageSent($message, $payload));
        } catch (\Throwable $e) {
            // Fail gracefully if broadcast driver is not running
        }

        // Update session tracking for fallback compatibility
        $totalCount = Message::where('room_id', $room->id)->count();
        session(["learning.discussion_reads.$course" => $totalCount]);

        return response()->json([
            'success' => true,
            'message' => $payload,
            'total' => $totalCount,
        ]);
    }

    /**
     * POST /chat/messages/{message}/pin
     * Pin / Unpin message (Dosen only).
     */
    public function pinMessage(Request $request, Message $message): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('pin', $message);

        $message->update([
            'is_pinned' => ! $message->is_pinned,
        ]);

        try {
            event(new MessagePinned($message));
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'is_pinned' => (bool) $message->is_pinned,
            'message' => $message->toChatPayload($user),
        ]);
    }

    /**
     * DELETE /chat/messages/{message}
     * Soft delete message.
     */
    public function deleteMessage(Request $request, Message $message): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('delete', $message);

        $roomId = $message->room_id;
        $messageId = $message->id;

        $message->delete();

        try {
            event(new MessageDeleted($message));
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'room_id' => $roomId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * GET /chat/course/{course}/members
     * Return list of members for mention autocomplete dropdown.
     */
    public function getMembers(Request $request, int $course): JsonResponse
    {
        $user = $this->currentUser();
        $room = $this->ensureRoomAndMembership($course, $user);

        $members = $room->members()
            ->select('users.id', 'users.name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->pivot->role ?? 'mahasiswa',
                ];
            });

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }
}
