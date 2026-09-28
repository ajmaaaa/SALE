<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationState extends Model
{
    protected $fillable = ['user_id', 'notification_key', 'read_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
