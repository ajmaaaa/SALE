<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'code',
        'name',
        'academic_year',
        'term',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'term' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getAcademicYearAttribute(?string $value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        if (preg_match('/(\d{4}\/\d{4})/', (string) $this->name, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(\d{4})/', (string) $this->code, $matches)) {
            return $matches[1] . '/' . ((int) $matches[1] + 1);
        }

        return null;
    }

    public function getTermAttribute(?int $value): int
    {
        if ($value !== null && (int) $value > 0) {
            return (int) $value;
        }

        return str_contains(strtolower((string) ($this->name . ' ' . $this->code)), 'genap') ? 2 : 1;
    }

    public function getTermLabelAttribute(): string
    {
        return match ($this->term) {
            1 => 'Ganjil',
            2 => 'Genap',
            3 => 'Pendek',
            default => str_contains(strtolower((string) $this->name), 'genap') ? 'Genap' : 'Ganjil',
        };
    }

    public function getAcademicYearStartAttribute(): ?int
    {
        if ($this->academic_year && preg_match('/^(\d{4})/', $this->academic_year, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d{4})/', (string) $this->name, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d{4})/', (string) $this->code, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->academic_year && $this->term) {
            return $this->term_label . ' ' . $this->academic_year;
        }

        return $this->name;
    }

    public function scopeOrderChronological($query, string $direction = 'desc')
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('semesters', 'academic_year')) {
            $query->orderBy('academic_year', $direction);
            if (\Illuminate\Support\Facades\Schema::hasColumn('semesters', 'term')) {
                $query->orderBy('term', $direction);
            }

            return $query->orderBy('id', $direction);
        }

        return $query->orderBy('id', $direction);
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }
}
