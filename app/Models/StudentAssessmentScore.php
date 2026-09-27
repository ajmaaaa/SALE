<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentScore extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FINAL = 'final';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'assessment_id',
        'mahasiswa_id',
        'score',
        'status',
        'feedback',
        'graded_by',
        'graded_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            // Intentionally nullable float: NULL means "not graded yet"
            // and must never be treated as 0 by calculations.
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return in_array($this->status, [self::STATUS_FINAL, self::STATUS_PUBLISHED], true)
            && $this->score !== null;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->score !== null;
    }
}
