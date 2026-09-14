<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricCriterion extends Model
{
    protected $fillable = [
        'rubric_id',
        'name',
        'description',
        'weight',
        'max_score',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentRubricScore::class);
    }
}
