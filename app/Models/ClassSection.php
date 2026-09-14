<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSection extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'semester_id',
        'dosen_id',
        'section_code',
        'capacity',
    ];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_section_student', 'class_section_id', 'mahasiswa_id')
            ->withTimestamps();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Convenience accessor for a human-readable label, e.g. "IF204-A".
     */
    public function getDisplayCodeAttribute(): string
    {
        return $this->mataKuliah->code.'-'.$this->section_code;
    }
}
