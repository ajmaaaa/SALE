<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'assessment_id',
        'user_id',
        'mahasiswa_id',
        'attempt',
        'version',
        'status',
        'submitted_at',
        'answer',
        'link',
        'question_answers',
        'file_ids',
        'student_number',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'version' => 'integer',
            'question_answers' => 'array',
            'file_ids' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Submission $submission): void {
            if ($submission->user_id && ! $submission->mahasiswa_id) {
                $submission->mahasiswa_id = $submission->user_id;
            }
        });
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
