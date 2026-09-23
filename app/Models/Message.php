<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_id',
        'user_id',
        'content',
        'attachment_url',
        'reply_to_message_id',
        'is_pinned',
        'edited_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class);
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_mentions', 'message_id', 'mentioned_user_id')
            ->withTimestamps();
    }

    /**
     * Format payload for API and WebSocket broadcast.
     */
    public function toChatPayload(?User $viewer = null): array
    {
        $sender = $this->user;
        $roomMember = RoomMember::where('room_id', $this->room_id)
            ->where('user_id', $this->user_id)
            ->first();

        $senderRole = $roomMember?->role
            ?? ($sender?->role?->name ?? 'mahasiswa');

        $isMe = $viewer ? ($this->user_id === $viewer->id) : false;

        $replyInfo = null;
        if ($this->replyTo) {
            $replySender = $this->replyTo->user;
            $replyInfo = [
                'id' => $this->replyTo->id,
                'sender_name' => $replySender?->name ?? 'Pengguna',
                'excerpt' => \Illuminate\Support\Str::limit($this->replyTo->content, 60),
            ];
        }

        $mentions = $this->mentions()->with('mentionedUser:id,name')->get()->map(function ($m) {
            return [
                'user_id' => $m->mentioned_user_id,
                'name' => $m->mentionedUser?->name ?? '',
            ];
        })->toArray();

        $createdAt = $this->created_at ?? now();

        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'content' => $this->content,
            'is_pinned' => (bool) $this->is_pinned,
            'user_id' => $this->user_id,
            'author' => $sender?->name ?? 'Pengguna',
            'role' => $senderRole,
            'is_me' => $isMe,
            'time' => $createdAt->format('H:i'),
            'date_key' => $createdAt->toDateString(),
            'date_label' => $createdAt->isToday()
                ? 'Hari ini'
                : ($createdAt->isYesterday() ? 'Kemarin' : $createdAt->translatedFormat('d F Y')),
            'timestamp' => $createdAt->timestamp,
            'reply_to' => $replyInfo,
            'mentions' => $mentions,
        ];
    }
}
