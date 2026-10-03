<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    public const VERDICT_OK = 'ok';

    public const VERDICT_OFF_TOPIC = 'off_topic';

    public const VERDICT_ASKS_SOLUTION = 'asks_solution';

    public const VERDICT_INJECTION = 'injection';

    public const VERDICT_BLOCKED_OUTPUT = 'blocked_output';

    public const VERDICT_ERROR = 'error';

    public $timestamps = false;

    protected $fillable = [
        'thread_id',
        'role',
        'content',
        'verdict',
        'tokens_in',
        'tokens_out',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AiMessage $message) {
            if (empty($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(AiThread::class, 'thread_id');
    }
}
