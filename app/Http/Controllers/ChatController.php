<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessagePinned;
use App\Events\MessageSent;
use App\Models\ChatNotification;
use App\Models\ClassSection;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    /**
     * Kembalikan user yang sedang login. TIDAK ada fallback User::first().
     * Middleware 'role:mahasiswa,dosen' sudah memastikan user selalu terautentikasi.
     */
    protected function currentUser(): ?User
    {
        return auth()->user();
    }

    /**
     * Periksa apakah user boleh mengakses room kelas ini.
     * Mahasiswa harus terdaftar (enrolled), dosen harus pengampu kelas tersebut.
     */
    protected function canAccessCourse(int $courseId, User $user): bool
    {
        if (! Schema::hasTable('class_sections')) {
            // Fallback demo mode: izinkan akses
            return true;
        }

        $section = ClassSection::find($courseId);
        if (! $section) {
            return false;
        }

        if ($user->hasRole(Role::DOSEN)) {
            return in_array($user->id, [$section->dosen_id, $section->dosen_pendamping_id], true);
        }

        if ($user->hasRole(Role::MAHASISWA)) {
            return $section->students()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Pastikan room ada dan user terdaftar sebagai member.
     * Auto-seed semua user dihapus — hanya user yang memang terdaftar di kelas yang boleh masuk.
     */
    protected function ensureRoomAndMembership(int $courseId, User $user): Room
    {
        $section = Schema::hasTable('class_sections') ? ClassSection::find($courseId) : null;
        $courseTitle = $section?->mataKuliah?->name ?? "Course {$courseId}";
        $room = Room::forCourse($courseId, $courseTitle);

        // Daftarkan user ke room jika belum terdaftar
        $chatRole = $user->hasRole(Role::DOSEN) ? 'dosen' : 'mahasiswa';
        RoomMember::firstOrCreate(
            ['room_id' => $room->id, 'user_id' => $user->id],
            ['role' => $chatRole, 'joined_at' => now()]
        );

        return $room;
    }

    /**
     * GET /chat/course/{course}/messages
     * Return messages, pinned messages, and room members.
     */
    public function getMessages(Request $request, int $course): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        if (! $this->canAccessCourse($course, $user)) {
            return response()->json(['success' => false, 'error' => 'Anda tidak terdaftar pada kelas ini.'], 403);
        }

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

        if (! $this->canAccessCourse($course, $user)) {
            return response()->json(['success' => false, 'error' => 'Anda tidak terdaftar pada kelas ini.'], 403);
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

        try {
            event(new MessageDeleted($message));
        } catch (\Throwable $e) {
        }

        $message->delete();

        if ($message->room?->course_id) {
            $totalCount = Message::where('room_id', $roomId)->count();
            session(["learning.discussion_reads.{$message->room->course_id}" => $totalCount]);
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
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated.'], 401);
        }

        if (! $this->canAccessCourse($course, $user)) {
            return response()->json(['success' => false, 'error' => 'Anda tidak terdaftar pada kelas ini.'], 403);
        }

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
