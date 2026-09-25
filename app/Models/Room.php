<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'name',
        'course_id',
        'class_section_id',
    ];

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function pinnedMessages(): HasMany
    {
        return $this->hasMany(Message::class)->where('is_pinned', true);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_members')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function roomMembers(): HasMany
    {
        return $this->hasMany(RoomMember::class);
    }

    /**
     * Get or create a room for a course/class.
     */
    public static function forCourse(int $courseId, ?string $courseTitle = null): self
    {
        $room = static::where('course_id', $courseId)->first();

        if (! $room) {
            $name = $courseTitle
                ? "Forum Diskusi - {$courseTitle}"
                : "Forum Diskusi Kelas {$courseId}";

            $room = static::create([
                'name' => $name,
                'course_id' => $courseId,
                'class_section_id' => ClassSection::where('id', $courseId)->exists() ? $courseId : null,
            ]);
        }

        return $room;
    }
}
