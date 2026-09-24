<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assessment extends Model
{
    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'class_section_id',
        'code',
        'name',
        'type',
        'description',
        'learning_payload',
        'final_weight',
        'uses_rubric',
        'status',
        'due_at',
        'allow_late',
    ];

    protected function casts(): array
    {
        return [
            'uses_rubric' => 'boolean',
            'learning_payload' => 'array',
            'due_at' => 'datetime',
            'allow_late' => 'boolean',
        ];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    /**
     * CPMK measured by this assessment, many-to-many, with this
     * assessment's contribution weight (%) toward each CPMK on the pivot.
     */
    public function cpmks(): BelongsToMany
    {
        return $this->belongsToMany(Cpmk::class, 'assessment_cpmk', 'assessment_id', 'cpmk_id')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function rubric(): HasOne
    {
        return $this->hasOne(Rubric::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }
}
