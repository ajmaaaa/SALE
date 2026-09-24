<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClassSection extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'semester_id',
        'dosen_id',
        'dosen_pendamping_id',
        'section_code',
        'capacity',
        'enrollment_code',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClassSection $section) {
            if (empty($section->enrollment_code)) {
                $section->enrollment_code = static::generateUniqueEnrollmentCode();
            }
        });
    }

    public static function generateUniqueEnrollmentCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('enrollment_code', $code)->exists());

        return $code;
    }

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

    public function dosenPendamping(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_pendamping_id');
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

    public function getEnrollmentUrlAttribute(): string
    {
        return url('/join-kelas/'.($this->enrollment_code ?? ''));
    }

    /**
     * Total bobot seluruh komponen asesmen pada kelas ini.
     */
    public function getTotalAssessmentWeightAttribute(): float
    {
        $assessments = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();

        return round((float) $assessments->sum('final_weight'), 2);
    }

    /**
     * Mengecek apakah matriks penilaian valid (memiliki asesmen dan total tepat 100%).
     */
    public function isMatrixValid(): bool
    {
        $assessments = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();
        if ($assessments->isEmpty()) {
            return false;
        }

        return abs($this->total_assessment_weight - 100.0) < 0.01;
    }
}
