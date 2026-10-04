<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAnswer extends Model
{
    protected $fillable = [
        'submission_id',
        'question_index',
        'question_id',
        'version',
        'answer_text',
        'link',
        'choices',
        'boolean_choice',
        'matching',
        'earned_score',
        'max_score',
        'grading_status',
        'graded_by_id',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'question_index' => 'integer',
            'version' => 'integer',
            'choices' => 'array',
            'matching' => 'array',
            'earned_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by_id');
    }
}
