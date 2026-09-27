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
    ];

    protected function casts(): array
    {
        return [
            'question_index' => 'integer',
            'version'        => 'integer',
            'choices'        => 'array',
            'matching'       => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
