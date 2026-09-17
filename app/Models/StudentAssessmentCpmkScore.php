<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentCpmkScore extends Model
{
    protected $fillable = [
        'assessment_id',
        'cpmk_id',
        'mahasiswa_id',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }
}
