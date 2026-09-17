<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRubricScore extends Model
{
    protected $fillable = [
        'rubric_criterion_id',
        'mahasiswa_id',
        'score',
    ];

    protected function casts(): array
    {
        return [
            // Intentionally nullable: NULL means "not graded yet".
            'score' => 'decimal:2',
        ];
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function isGraded(): bool
    {
        return $this->score !== null;
    }
}
