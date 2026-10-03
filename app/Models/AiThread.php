<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiThread extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'class_section_id',
        'turns',
        'blocked_count',
        'tokens_used',
        'last_provider',
        'cleared_at',
    ];

    protected function casts(): array
    {
        return [
            'turns' => 'integer',
            'blocked_count' => 'integer',
            'tokens_used' => 'integer',
            'cleared_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'thread_id');
    }
}
