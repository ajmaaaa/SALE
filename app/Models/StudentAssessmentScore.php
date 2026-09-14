<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentScore extends Model
{
    protected $fillable = [
        'assessment_id',
        'mahasiswa_id',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            // Intentionally nullable float: NULL means "not graded yet"
            // and must never be treated as 0 by calculations.
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
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
        return $this->score !== null;
    }
}
